<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Extension\Redis;

use Kdyby;
use Nette;
use Nette\Caching\Cache;



/**
 * Redis journal for tags and priorities of cached values.
 *
 * @author Filip Procházka <filip@prochazka.su>
 */
class RedisJournal extends Nette\Object implements Nette\Caching\Storages\IJournal
{

	/** @internal cache structure */
	const NS_NETTE = 'Nette.Journal';

	/** dependency */
	const PRIORITY = 'priority',
		TAGS = 'tags',
		KEYS = 'keys';

	/**
	 * @var RedisClient
	 */
	private $client;



	/**
	 * @param RedisClient $client
	 */
	public function __construct(RedisClient $client)
	{
		$this->client = $client;
	}



	/**
	 * Writes entry information into the journal.
	 *
	 * @param  string $key
	 * @param  array  $dp
	 *
	 * @return void
	 */
	public function write($key, array $dp)
	{
		$this->cleanEntry($key);

		$this->client->multi();

		// add entry to each tag & tag to entry
		$tags = empty($dp[Cache::TAGS]) ? array() : (array)$dp[Cache::TAGS];
		foreach (array_unique($tags) as $tag) {
			$this->client->rPush($this->formatKey($tag, self::KEYS), $key);
			$this->client->rPush($this->formatKey($key, self::TAGS), $tag);
		}

		if (isset($dp[Cache::PRIORITY])) {
			$this->client->zAdd($this->formatKey(self::PRIORITY), $dp[Cache::PRIORITY], $key);
		}

		$this->client->exec();
	}



	/**
	 * Deletes all keys from associated tags and all priorities
	 *
	 * @param string $key
	 */
	private function cleanEntry($key)
	{
		$entries = $this->entryTags($key);

		$this->client->multi();
		foreach ($entries as $tag) {
			$this->client->lRem($this->formatKey($tag, self::KEYS), 0, $key);
		}

		// drop tags of entry and priority, in case there are some
		$this->client->del($this->formatKey($key, self::TAGS));
		$this->client->del($this->formatKey($key, self::PRIORITY));
		$this->client->zRem($this->formatKey(self::PRIORITY), $key);

		$this->client->exec();
	}



	/**
	 * Cleans entries from journal.
	 *
	 * @param  array  $conds
	 *
	 * @return array of removed items or NULL when performing a full cleanup
	 */
	public function clean(array $conds)
	{
		if (!empty($conds[Cache::ALL])) {
			$all = $this->client->keys(self::NS_NETTE . ':*');

			$this->client->multi();
			foreach ($all as $entry) {
				$this->client->del($entry);
			}
			$this->client->exec();
			return NULL;
		}

		$entries = array();
		if (!empty($conds[Cache::TAGS])) {
			foreach ((array)$conds[Cache::TAGS] as $tag) {
				foreach ($found = $this->tagEntries($tag) as $entry) {
					$this->cleanEntry($entry);
				}
				$entries = array_merge($entries, $found);
			}
		}

		if (isset($conds[Cache::PRIORITY])) {
			foreach ($found = $this->priorityEntries($conds[Cache::PRIORITY]) as $entry) {
				$this->cleanEntry($entry);
			}
			$entries = array_merge($entries, $found);
		}

		return array_unique($entries);
	}



	/**
	 * @param int $priority
	 * @return array
	 */
	private function priorityEntries($priority)
	{
		return $this->client->zRangeByScore($this->formatKey(self::PRIORITY), 0, (int)$priority) ?: array();
	}



	/**
	 * @param string $key
	 *
	 * @return array
	 */
	private function entryTags($key)
	{
		return $this->client->lRange($this->formatKey($key, self::TAGS), 0, -1) ? : array();
	}



	/**
	 * @param string $tag
	 *
	 * @return array
	 */
	private function tagEntries($tag)
	{
		return $this->client->lRange($this->formatKey($tag, self::KEYS), 0, -1) ? : array();
	}



	/**
	 * @param string $key
	 * @param string $suffix
	 *
	 * @return string
	 */
	protected function formatKey($key, $suffix = NULL)
	{
		return self::NS_NETTE . ':' . str_replace(Cache::NAMESPACE_SEPARATOR, ':', $key) . ($suffix ? ':' . $suffix : NULL);
	}

}
