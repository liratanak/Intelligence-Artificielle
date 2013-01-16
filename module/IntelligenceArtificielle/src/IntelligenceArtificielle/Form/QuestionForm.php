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
			'name' => 'yes',
			'attributes' => array(
				'type' => 'submit',
				'value' => 'YES',
			),
		));
		$this->add(array(
			'name' => 'no',
			'attributes' => array(
				'type' => 'submit',
				'value' => 'NO',
			),
		));
	}

}