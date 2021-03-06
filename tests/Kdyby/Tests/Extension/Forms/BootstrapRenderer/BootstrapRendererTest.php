<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2012 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Kdyby\Tests\Extension\Forms\BootstrapRenderer;

use Kdyby;
use Kdyby\Extension\Forms\BootstrapRenderer;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class BootstrapRendererTest extends Kdyby\Tests\TestCase
{

	/**
	 * @return \Nette\Application\UI\Form
	 */
	protected function dataCreateRichForm()
	{
		$form = new Form();
		$form->addError("General failure!");

		$grouped = $form->addContainer('grouped');
		$grouped->currentGroup = $form->addGroup('Skupina', FALSE);
		$grouped->addText('name', 'Jméno')
			->getLabelPrototype()->addClass('test');
		$grouped->addText('email', 'Email')
			->setType('email');
		$grouped->addSelect('sex', 'Pohlaví', array(1 => 'Muž', 2 => 'Žena'));
		$grouped->addCheckbox('mailing', 'Zasílat novinky');
		$grouped->addButton('add', 'Přidat');

		$grouped->addSubmit('poke', 'Šťouchnout');
		$grouped->addSubmit('poke2', 'Ještě Šťouchnout')
			->setAttribute('class', 'btn-success');

		$other = $form->addContainer('other');
		$other->currentGroup = $form->addGroup('Other', FALSE);
		$other->addRadioList('sexy', 'Sexy', array(1 => 'Ano', 2 => 'Ne'));
		$other->addPassword('heslo', 'Heslo')
			->addError('chybka!');
		$other->addSubmit('pass', "Nastavit heslo")
			->setAttribute('class', 'btn-warning');

		$form->addUpload('photo', 'Fotka');
		$form->addSubmit('up', 'Nahrát fotku');

		$form->addTextArea('desc', 'Popis');

		$form->addProtection('nemam', 10);
		$form[$form::PROTECTOR_ID]->__construct('ale mam');

		$form->addSubmit('submit', 'Uložit')
			->setAttribute('class', 'btn-primary');
		$form->addSubmit('delete', 'Smazat');

		return $form;
	}


	/**
	 * @return array
	 */
	public function dataRenderingBasics()
	{
		return $this->findInputOutput('basic/input/*.latte', 'basic/output/*.html');
	}



	/**
	 * @dataProvider dataRenderingBasics
	 *
	 * @param string $latteFile
	 * @param string $expectedOutput
	 */
	public function testRenderingBasics($latteFile, $expectedOutput)
	{
		$form = $this->dataCreateRichForm();
		$this->assertFormTemplateOutput($latteFile, $expectedOutput, $form);
	}



	/**
	 * @return array
	 */
	public function dataRenderingComponents()
	{
		return $this->findInputOutput('components/input/*.latte', 'components/output/*.html');
	}



	/**
	 * @dataProvider dataRenderingComponents
	 *
	 * @param string $latteFile
	 * @param string $expectedOutput
	 */
	public function testRenderingComponents($latteFile, $expectedOutput)
	{
		// create form
		$form = $this->dataCreateRichForm();
		$this->assertFormTemplateOutput($latteFile, $expectedOutput, $form);
	}



	/**
	 * @return \Nette\Application\UI\Form
	 */
	protected function dataCreateForm()
	{
		$form = new Form;
		$form->addText('name', 'Name');
		$form->addCheckbox('check', 'Indeed');
		$form->addUpload('image', 'Image');
		$form->addRadioList('sex', 'Sex', array(1 => 'Man', 'Woman'));
		$form->addSelect('day', 'Day', array(1 => 'Monday', 'Tuesday'));
		$form->addTextArea('desc', 'Description');
		$form->addSubmit('send', 'Odeslat');

		$form['checks'] = new \Kdyby\Forms\Controls\CheckboxList('Regions', array(
			1 => 'Jihomoravský',
			2 => 'Severomoravský',
			3 => 'Slezský',
		));

		$someGroup = $form->addGroup('Some Group', FALSE)
			->setOption('id', 'nemam')
			->setOption('class', 'beauty')
			->setOption('data-custom', '{"this":"should work too"}');
		$someGroup->add($form->addText('groupedName', 'Name'));

		// the div here and fieldset in template is intentional
		$containerGroup = $form->addGroup('Group with container', FALSE)
			->setOption('container', Html::el('div')->id('mam')->class('yes')->data('magic', 'is real'));
		$containerGroup->add($form->addText('containerGroupedName', 'Name'));

		return $form;
	}



	/**
	 * @return array
	 */
	public function dataRenderingIndividual()
	{
		return $this->findInputOutput('individual/input/*.latte', 'individual/output/*.html');
	}



	/**
	 * @dataProvider dataRenderingIndividual
	 *
	 * @param string $latteFile
	 * @param string $expectedOutput
	 */
	public function testRenderingIndividual($latteFile, $expectedOutput)
	{
		// create form
		$form = $this->dataCreateForm();
		$this->assertFormTemplateOutput($latteFile, $expectedOutput, $form);
	}



	public function testMultipleFormsInTemplate()
	{
		$control = new Nette\ComponentModel\Container();

		$control->addComponent($a = new Form, 'a');
		$a->addText('nemam', 'Nemam');
		$a->setRenderer(new BootstrapRenderer\BootstrapRenderer());

		$control->addComponent($b = new Form, 'b');
		$b->addText('mam', 'Mam');
		$b->setRenderer(new BootstrapRenderer\BootstrapRenderer());

		$this->assertTemplateOutput(array(
			'control' => $control, '_control' => $control
		), __DIR__ . '/edge/input/multipleFormsInTemplate.latte',
			__DIR__ . '/edge/output/multipleFormsInTemplate.html');

		$this->assertTemplateOutput(array(
				'control' => $control, '_control' => $control
			), __DIR__ . '/edge/input/multipleFormsInTemplate_parts.latte',
			__DIR__ . '/edge/output/multipleFormsInTemplate_parts.html');
	}



	/**
	 * @param $latteFile
	 * @param $expectedOutput
	 * @param \Nette\Application\UI\Form $form
	 * @throws \Exception
	 */
	protected function assertFormTemplateOutput($latteFile, $expectedOutput, Form $form)
	{
		// create form
		$form->setRenderer(new BootstrapRenderer\BootstrapRenderer());

		// params
		$control = new ControlMock();
		$control['foo'] = $form;

		$this->assertTemplateOutput(array(
			'form' => $form, '_form' => $form,
			'control' => $control, '_control' => $control
		), $latteFile, $expectedOutput);
	}



	/**
	 * @param array $params
	 * @param string $latteFile
	 * @param string $expectedOutput
	 * @throws \Exception
	 */
	protected function assertTemplateOutput(array $params, $latteFile, $expectedOutput)
	{
		$container = $this->createContainer(NULL, array(
			new BootstrapRenderer\DI\RendererExtension()
		));

		// create template
		$template = $container->nette->createTemplate();
		/** @var \Nette\Templating\FileTemplate $template */
		$template->setCacheStorage($container->nette->templateCacheStorage);
		$template->setFile($latteFile);

		// params
		$control = new ControlMock();
		$template->setParameters(array('control' => $control, '_control' => $control));
		$template->setParameters($params);

		// render template
		ob_start();
		try {
			$template->render();
		} catch (\Exception $e) {
			ob_end_clean();
			throw $e;
		}
		$output = Strings::normalize(ob_get_clean());
		$expected = Strings::normalize(file_get_contents($expectedOutput));

		// assert
		$this->assertSame($expected, $output);
	}

}



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ControlMock extends \Nette\Application\UI\Control
{

}
