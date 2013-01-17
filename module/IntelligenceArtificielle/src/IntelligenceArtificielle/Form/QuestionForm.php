<?php

namespace IntelligenceArtificielle\Form;

use Zend\Form\Form;

class QuestionForm extends Form {

	public function __construct($name = null) {
		// we want to ignore the name passed
		parent::__construct('question');
		$this->setAttribute('method', 'post');

		$this->add(array(
			'name' => 'questionKey',
			'attributes' => array(
				'type' => 'hidden',
			),
		));
		$this->add(array(
			'type' => 'Zend\Form\Element\Checkbox',
			'name' => 'show',
			'attributes' => array(
				'onclick' => 'showAnswers(this)',
				'style' => 'display : none;'
			),
			'options' => array(
//				'label' => 'Les Reponds',
				'label_attributes' => array(
					'class' => 'checkbox'
				),
				'checked_value' => 'show',
				'unchecked_value' => 'hide'
				)));

		$this->add(array(
			'name' => 'yes',
			'attributes' => array(
				'type' => 'submit',
				'class' => 'btn',
				'value' => 'Oui',
			),
		));
		$this->add(array(
			'name' => 'no',
			'attributes' => array(
				'type' => 'submit',
				'class' => 'btn',
				'value' => 'Non',
			),
		));
		$this->add(array(
			'name' => 'reset',
			'attributes' => array(
				'type' => 'submit',
				'class' => 'btn',
				'value' => 'Remettre',
			),
		));
	}

}