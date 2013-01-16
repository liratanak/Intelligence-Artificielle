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
use Zend\Session\Container;
use Zend\Session\SessionManager;

class IndexController extends AbstractActionController {

	private $currentDemandablesProposition = array();
	private $sessionKey = 'answers';

	public function indexAction() {
		$baseDeRegle = $this->getArrayOfRegles();
		$demandablesPropositions = $this->getAllDemandablePropositions($baseDeRegle);
		$propositionToConclution = $this->getPropositionToConclution($baseDeRegle);
		$terminalesPropositions = $this->getAllFaitTerminal($propositionToConclution);

		$allDemandablesProposition = array();
		foreach ($terminalesPropositions as $key => $value) {
			$this->currentDemandablesProposition = array();
			$this->getAllDemandablesPremisses($value, $demandablesPropositions, $baseDeRegle);
			$allDemandablesProposition[$this->getKeyUniqueForArray($value)] = $this->currentDemandablesProposition;
		}

		$request = $this->getRequest();
		if ($request->isPost()) {
			$this->handleAnswer($request->getPost());
		}
		
		$form = new \IntelligenceArtificielle\Form\QuestionForm();
		$form->get('questionKey')->setValue("Question Key");

		return new ViewModel(array(
					'baseDeRegle' => $baseDeRegle,
					'store' => $propositionToConclution,
					'form' => $form,
				));
	}
	
	private function handleAnswer($data){
		$ans = $this->getAnswers();
		$ans[] = $data;
		$this->setAnswers($ans);
		var_dump($ans);
	}

	private function setAnswers($newAns) {
		$session = $this->getSession();
		$session->ans = $newAns;
		return $session->ans;
	}

	private function getAnswers() {
		$session = $this->getSession();
		if (isset($session->ans)) {
			return $session->ans;
		}
		return array();
	}

	private function getSession() {
		return new Container($this->sessionKey);
	}

	private function getAllFaitTerminal($propositionToConclution) {
		$terminalesPropositions = array();
		foreach ($propositionToConclution as $key => $value) {
			if (NULL === $value) {
				$tmp = preg_split('/\|/', $key);
				$terminalesPropositions [$key]['negative'] = $tmp[0];
				$terminalesPropositions [$key]['verbe'] = $tmp[1];
				$terminalesPropositions [$key]['proposition'] = $tmp[2];
			}
		}
		return $terminalesPropositions;
	}

	private function getPropositionToConclution($baseDeRegle) {
		$propositionToConclution = array();
		foreach ($baseDeRegle as $regle) {
			foreach ($regle['premisses'] as $premiss) {
				$key = $this->getKeyUniqueForArray($premiss);
				$propositionToConclution[$key][] = $regle['conclusion'];
			}
			$premiss = $regle['conclusion'];
			$key = $this->getKeyUniqueForArray($premiss);
			$propositionToConclution[$key] = NULL;
		}
		return $propositionToConclution;
	}

	private function getAllDemandablePropositions($baseDeRegle) {
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
			$premiss = $regle['conclusion'];
			$key = $premiss['verbe'] . '|' . $premiss['proposition'];
			if (isset($demandablesPropositions[$key])) {
				unset($demandablesPropositions[$key]);
			}
		}

		foreach ($demandablesPropositions as &$proposition) {
			unset($proposition['negative']);
		}
		return $demandablesPropositions;
	}

	private function getRandomNumber($start = 0, $end = 0) {
		return rand($start, $end);
	}

	private function getAllQuestion($allDemandablesProposition) {
		$questions = array();
		foreach ($allDemandablesProposition as $aTerminal) {
			foreach ($aTerminal as $premisses) {
				$index = $this->getRandomNumber(0, (count($premisses['OR']) - 1));
				foreach ($premisses['OR'][$index] as $eachDemandable) {
					$questions[] = $this->getKeyUniqueForArray($eachDemandable);
				}
			}
		}
		return $questions;
	}

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
		$currentKey = $this->getKeyUniqueForArray($value);
		$tmpKey = 0;
		foreach ($deductiblesPropositions as $premisses) {
			foreach ($premisses as $premiss) {
				if ($this->isDemandableProposition($premiss, $demandablesPropositions)) {
					if (isset($this->currentDemandablesProposition[$currentKey]['OR'])) {
						foreach ($this->currentDemandablesProposition[$currentKey]['OR'] as $sub) {
							foreach ($sub as $p) {
								if ($p == $premiss)
									return;
							}
						}
					}
					$this->currentDemandablesProposition[$currentKey]['OR'][$tmpKey][] = $premiss;
				} else {
					$this->getAllDemandablesPremisses($premiss, $demandablesPropositions, $baseDeRegle);
				}
			}
			$tmpKey++;
		}
	}

	private function getAllPremesses($value, $baseDeRegle) {
		$premisses = array();
		$negative = FALSE;
		if (1 == $value['negative']) {
			$negative = TRUE;
			$value['negative'] = 0;
		}

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
