<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace IntelligenceArtificielle\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use IntelligenceArtificielle\Entity\Regle;

class IndexController extends AbstractActionController {

	public function indexAction() {
		$baseDeRegle = $this->getArrayOfRegles();

		foreach ($baseDeRegle as $regle) {
			$this->addPremiss($regle['conclusion']);
			foreach ($regle['premisses'] as $premiss) {
				$this->addPremiss($premiss);
			}
		}


		return new ViewModel(array(
					'baseDeRegle' => $baseDeRegle,
				));
	}

	private function addPremiss($data) {
		$regleEntity = new Regle();
		$regleEntity->setProposition($data['proposition']);
		$regleEntity->setVerbe($data['verbe']);
		if (isset($data['negative'])) {
			if (1 == $data['negative']) {
				$regleEntity->setNegative(TRUE);
			}
		}
		$this->getEntityManager()->persist($regleEntity);
		$this->getEntityManager()->flush();
	}

	private function getArrayOfRegles($pathToRegleText = FALSE) {
		$regleText = file_get_contents(__DIR__ . '/../Regle/Regle.txt');
		if (FALSE !== $pathToRegleText) {
			$regleText = file_get_contents($pathToRegleText);
		}

		$baseDeRegle = array();
		$regles = preg_split("/SI/", $regleText);

		foreach ($regles as $regleKey => $regle) {
			$premissesEtConclution = preg_split("/ALORS/", $regle);
			if (isset($premissesEtConclution[0]) && isset($premissesEtConclution[1])) {
				$baseDeRegle[$regleKey]['premisses'] = preg_split('/ET/', $premissesEtConclution[0]);
				$premissesArray = array();
				foreach ($baseDeRegle[$regleKey]['premisses'] as $key => $premiss) {
//					var_dump($premiss);
					$premissesArray[$key] = $this->splitPremiss($premiss);
				}
				$baseDeRegle[$regleKey]['premisses'] = $premissesArray;
				$baseDeRegle[$regleKey]['conclusion'] = $this->splitPremiss($premissesEtConclution[1]);
			}
		}
		return $baseDeRegle;
	}

	private function splitPremiss($premiss) {
		$premissesArray = array();
		$premiss = $this->replaceparentheses($premiss);
		if (preg_match('/[ne|n\'](.*)pas(.*)/', $premiss)) {
			$premiss = trim(preg_replace('/ne|n\'|pas/', '', $premiss));
			$premissesArray['negative'] = TRUE;
		}
		$splitedPremiss = preg_split('/\ /', $premiss, 2);
		if (isset($splitedPremiss[1])) {
			$premissesArray['verbe'] = $splitedPremiss[0];
			$premissesArray['proposition'] = $splitedPremiss[1];
		} else {
			$premissesArray['verbe'] = '';
			$premissesArray['proposition'] = $splitedPremiss[0];
		}
		return $premissesArray;
	}

	private function replaceparentheses($subject) {
		$result = preg_replace('/\(animal/', '', $subject);
		$result = preg_replace('/\)/', '', $result);
		$result = trim($result);
		return $result;
	}

	/**
	 * Entity manager instance
	 *           
	 * @var Doctrine\ORM\EntityManager
	 */
	protected $em;

	/**
	 * Returns an instance of the Doctrine entity manager loaded from the service 
	 * locator
	 * 
	 * @return Doctrine\ORM\EntityManager
	 */
	public function getEntityManager() {
		if (null === $this->em) {
			$this->em = $this->getServiceLocator()
					->get('doctrine.entitymanager.orm_default');
		}
		return $this->em;
	}

}
