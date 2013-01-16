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

		$store = array();

		$demandablesPropositions = array();
		foreach ($baseDeRegle as $regle) {
			$demandablesPropositions = array_merge($demandablesPropositions, $regle['premisses']);
		}

		$demandablesPropositionsTmp = $demandablesPropositions;
		$demandablesPropositions = array();
		foreach ($demandablesPropositionsTmp as $value) {
			$key = $value['verbe'] . '|' . $value['proposition'];
			$demandablesPropositions[$key] = $value;
		}

		foreach ($baseDeRegle as $regle) {
			foreach ($regle['premisses'] as $premiss) {
				$key = $premiss['negative'] . '|' . $premiss['verbe'] . '|' . $premiss['proposition'];
				$store[$key][] = $regle['conclusion'];
			}
			$premiss = $regle['conclusion'];
			$key = $premiss['negative'] . '|' . $premiss['verbe'] . '|' . $premiss['proposition'];
			$store[$key] = NULL;

			$key = $premiss['verbe'] . '|' . $premiss['proposition'];
			if (isset($demandablesPropositions[$key])) {
				unset($demandablesPropositions[$key]);
			}
		}

		foreach ($demandablesPropositions as &$proposition) {
			unset($proposition['negative']);
		}

		$terminalesPropositions = array();
		foreach ($store as $key => $value) {
			if (NULL === $value) {
				$tmp = preg_split('/\|/', $key);
				$terminalesPropositions [$key]['negative'] = $tmp[0];
				$terminalesPropositions [$key]['verbe'] = $tmp[1];
				$terminalesPropositions [$key]['proposition'] = $tmp[2];
			}
		}

//		var_dump($terminalesPropositions);
//		var_dump($demandablesPropositions);
//		echo '<pre>';
//		print_r($baseDeRegle);
//		echo '</pre>';

		foreach ($terminalesPropositions as $key => $value) {
			$this->getFirstQuestion($value, $demandablesPropositions);
			$this->currentDemandablesProposition = array();
			$this->getAllDemandablesPremisses($value, $demandablesPropositions, $baseDeRegle);
			var_dump($value);
			var_dump($this->currentDemandablesProposition);
//			die();
		}

		return new ViewModel(array(
					'baseDeRegle' => $baseDeRegle,
					'store' => $store,
				));
	}

	private function getFirstQuestion($value, $demandablesPropositions) {
		if (in_array($value, $demandablesPropositions)) {
//			var_dump($value);
		} else {
//			var_dump('NOT DEMANDABLE');
		}
	}

	private $currentDemandablesProposition = array();

	private function getKeyUniqueForArray($premiss) {
		return $premiss['negative'] . '|' . $premiss['verbe'] . '|' . $premiss['proposition'];
	}

	/**
	 * 
	 * @param type $value
	 * @param type $demandablesPropositions
	 * @param type $baseDeRegle
	 * @return array
	 */
	private function getAllDemandablesPremisses($value, $demandablesPropositions, $baseDeRegle) {
		$deductiblesPropositions = $this->getAllPremesses($value, $baseDeRegle);
		foreach ($deductiblesPropositions as $or) {
			foreach ($or as $premiss) {
				if ($this->isDemandableProposition($premiss, $demandablesPropositions)) {
					$currentKey = $this->getKeyUniqueForArray($value);
					$tmpArray = array(
						array(
							$currentKey => $premiss,
						)
					);
					if (array_key_exists($currentKey, $this->currentDemandablesProposition)) {
						
					}
					$this->currentDemandablesProposition = array_merge($this->currentDemandablesProposition, $tmpArray);
				} else {
					$this->getAllDemandablesPremisses($premiss, $demandablesPropositions, $baseDeRegle);
				}
			}
		}
	}

	private function getAllPremesses($value, $baseDeRegle) {
		$premisses = array();
		foreach ($baseDeRegle as $regle) {
			if ($value == $regle['conclusion']) {
				$premisses[] = $regle['premisses'];
			}
		}
		return $premisses;
	}

	private function isDemandableProposition($goal = array(), $demandablesPropositions = array()) {
		unset($goal['negative']);
		return in_array($goal, $demandablesPropositions);
	}

	/**
	 * 
	 * @param array $premiss
	 * @return \IntelligenceArtificielle\Entity\Regle
	 */
	private function getPremissEntityObject($premiss) {
		$existEntity = $this->getEntityManager()->getRepository('IntelligenceArtificielle\Entity\Regle')->findOneBy(array(
			'proposition' => $premiss['proposition'],
			'negative' => $premiss['negative'],
			'verbe' => $premiss['verbe'],
				));
		return $existEntity;
	}

	private function isPremissNotExist($premiss) {
		$regle = $this->getPremissEntityObject($premiss);
		return is_null($regle);
	}

	/**
	 * 
	 * @param array $premiss
	 * @return \IntelligenceArtificielle\Entity\Regle
	 */
	private function newRegleEntity($premiss) {
		$regleEntity = new Regle();
		$regleEntity->setProposition(($premiss['proposition']));
		$regleEntity->setVerbe(($premiss['verbe']));
		$regleEntity->setNegative($premiss['negative']);
		return $regleEntity;
	}

	private function addPremiss($premiss = array(), $conclusion = FALSE) {
		if (FALSE !== $conclusion) {
			$newConclutionEntity = $this->newRegleEntity($conclusion);
			$this->flushRegleToDb($newConclutionEntity);

			$newPremissEntity = $this->newRegleEntity($premiss);
			$newPremissEntity->addPremis($this->getPremissEntityObject($conclusion));
			$this->flushRegleToDb($newPremissEntity);
			die();
		}
	}

	private function flushRegleToDb($entity) {
		$this->getEntityManager()->persist($entity);
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
		$premissesArray['negative'] = '0';
		if (preg_match('/[ne|n\'](.*)pas(.*)/', $premiss)) {
			$premiss = trim(preg_replace('/ne|n\'|pas/', '', $premiss));
			$premissesArray['negative'] = '1';
		}
		$splitedPremiss = preg_split('/\ /', $premiss, 2);
		if (isset($splitedPremiss[1])) {
			$premissesArray['verbe'] = trim($splitedPremiss[0]);
			$premissesArray['proposition'] = trim($splitedPremiss[1]);
		} else {
			$premissesArray['verbe'] = '';
			$premissesArray['proposition'] = trim($splitedPremiss[0]);
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
