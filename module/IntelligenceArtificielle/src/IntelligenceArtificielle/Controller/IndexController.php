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
	private $currentTestingTerminal;
	private $isTerminated = FALSE;
	private $showAnswer;

	public function indexAction() {
		$baseDeRegle = $this->getArrayOfRegles();
		$propositionToConclution = $this->getPropositionToConclution($baseDeRegle);
		$terminalesPropositions = $this->getAllFaitTerminal($propositionToConclution);
		$allDemandablesProposition = $this->getAllDemandablePropositionsForEachTerminalsPropositions($terminalesPropositions, $baseDeRegle);

//		echo '<pre>';
//		print_r($allDemandablesProposition);
//		echo '</pre>';

		$request = $this->getRequest();
		if ($request->isPost()) {
			$post = $request->getPost();
			if ($post->show == 'hide') {
				$this->showAnswer = FALSE;
			} else if ($post->show == 'show') {
				$this->showAnswer = TRUE;
			}

			$this->handleAnswer($post);
		}

		$nextQuestionKey = $this->getNextQuestionKey($this->getAnswers(), $allDemandablesProposition, $propositionToConclution, $terminalesPropositions);

		$questionString = '';
		if ($nextQuestionKey === TRUE) {
			$arrayTerminalProposition = preg_split('/\|/', $this->currentTestingTerminal);
			$this->isTerminated = TRUE;
			$questionString = 'Votre animal ' . $arrayTerminalProposition[1] . ' <b>' . $arrayTerminalProposition[2] . '</b>';
		} else if (FALSE === $nextQuestionKey) {
			$this->isTerminated = TRUE;
			$questionString = 'Ce système n\'a pas votre animal';
		} else if (NULL !== $nextQuestionKey) {
			$questionString = $this->getQuestionStringForUser($nextQuestionKey);
		}

		$form = new \IntelligenceArtificielle\Form\QuestionForm();
		$form->get('questionKey')->setValue($nextQuestionKey);
		$form->get('show')->setValue($this->showAnswer);

		if ($this->isTerminated === TRUE) {
			$form->get('yes')->setAttribute('disabled', TRUE);
			$form->get('no')->setAttribute('disabled', TRUE);
		}

		return new ViewModel(array(
					'baseDeRegle' => $baseDeRegle,
					'store' => $propositionToConclution,
					'form' => $form,
					'question' => $questionString,
					'ans' => $this->getAnswers(),
					'showed' => $this->showAnswer,
				));
	}

	private function getQuestionStringForUser($querstionKey) {
		if ($querstionKey) {
			$premiss = preg_split('/\|/', $querstionKey);
			return 'Animal ' . $premiss[0] . ' ' . $premiss[1] . '?';
		}
		return '';
	}

	private function getAllDemandablePropositionsForEachTerminalsPropositions($terminalesPropositions, $baseDeRegle) {
		$allDemandablesProposition = array();
		foreach ($terminalesPropositions as $value) {
			$this->currentDemandablesProposition = array();
			$this->getAllDemandablesPremisses($value, $this->getAllDemandablePropositions($baseDeRegle), $baseDeRegle);
			$allDemandablesProposition[$this->getKeyUniqueForArray($value)] = $this->currentDemandablesProposition;
		}
		return $allDemandablesProposition;
	}

	private function addTestedTerminalProposition($key) {
		$session = $this->getSession();
		if (!is_array($session->testedTerminalProposition)) {
			$session->testedTerminalProposition = array();
		}
		$session->testedTerminalProposition = array_merge($session->testedTerminalProposition, array($key => $key));
	}

	private function getTestedTerminalPropositions() {
		$session = $this->getSession();
		return $session->testedTerminalProposition;
	}

	private function getTerminalKeyForTesting($demandablesProposition, $terminalesPropositions) {
		if (is_array($this->getTestedTerminalPropositions())) {
			foreach ($this->getTestedTerminalPropositions() as $propositionKey) {
				unset($demandablesProposition[$propositionKey]);
				if (empty($demandablesProposition))
					return NULL;
			}
		}

		$break = array(
			'deductive' => FALSE,
			'terminal' => FALSE,
			'premisses' => FALSE,
		);

		$succedDeductive = array();
		
		foreach ($demandablesProposition as $terminalKey => $eachTerminalProposition) {
			$break['terminal'] = FALSE;
			$propossitionMatchedForATerminalCounter = 0;
			$nbDeductiveForAterminal = count($eachTerminalProposition);
			$nextTerminal = FALSE;
			foreach ($succedDeductive as $key) {
				if(!isset($eachTerminalProposition[$key])){
					$this->addTestedTerminalProposition($terminalKey);
					unset($demandablesProposition[$terminalKey]);
					$nextTerminal = TRUE;
					break;
				}
			}
			if($nextTerminal){
				continue;
			}
			
			foreach ($eachTerminalProposition as $deductiveKey => $deductiveProposition) {
				$break['deductive'] = FALSE;
				$nbDeductivePossible = count($deductiveProposition['OR']);
				$deductiveNotMatchedCounter = 0;
				$notMatchedTerminal = FALSE;
				foreach ($deductiveProposition['OR'] as $premisses) {
					$break['premisses'] = FALSE;
					$matchedCounter = 0;
					$nbPremisses = count($premisses);
					foreach ($premisses as $premisskey => $premiss) {
						$currentPropostionKey = substr($premisskey, 2);
						$ans = $this->getAnswers();
						if (isset($ans[$currentPropostionKey])) {
							$this->currentTestingTerminal = $terminalKey;
							$ansForcurrenProposition = $ans[$currentPropostionKey];
							$currentPropostionStatusMatched = FALSE;

							if ($ansForcurrenProposition) {
								$negative = 1;
							} else {
								$negative = 0;
							}

							if ($premiss['negative'] != $negative) {
								$currentPropostionStatusMatched = TRUE;
							} else {
								$currentPropostionStatusMatched = FALSE;
							}

							if (!$currentPropostionStatusMatched) {
								$deductiveNotMatchedCounter++;
								break;
							}
							if ($currentPropostionStatusMatched) {
								$matchedCounter++;
							}

							if ($nbPremisses == $matchedCounter) {
								$break['premisses'] = TRUE;
								$succedDeductive[$deductiveKey] = $deductiveKey;
								$propossitionMatchedForATerminalCounter++;
								break;
							}

							if ($nbDeductivePossible == $deductiveNotMatchedCounter) {
								$this->addTestedTerminalProposition($terminalKey);
								$break['deductive'] = TRUE;
							}
						} else {
							if (array_key_exists(($premisskey), $terminalesPropositions)) {
								return NULL;
							}
							return $premisskey;
						}
					}
					if ($break['premisses'])
						break;
				}
				if ($deductiveNotMatchedCounter == $nbDeductivePossible) {
					break;
				}
				if ($break['deductive'])
					break;
			}
			if ($nbDeductiveForAterminal == $propossitionMatchedForATerminalCounter) {
				return $eachTerminalProposition;
			}
			if ($break['terminal'])
				break;
		}
		return NULL;
	}

	private function getNextQuestionKey($ans, $demandablesProposition, $propositionToConclution, $terminalesPropositions) {
		$terminalKey = $this->getTerminalKeyForTesting($demandablesProposition, $terminalesPropositions);
		if (is_array($terminalKey)) {
			$this->getSession()->terminalReached = $terminalKey;
			return TRUE;
		}

		return substr($terminalKey, 2);
	}

	private function selecteRandomKeyOfArray(Array $array = array()) {
		$keys = array_keys($array);
		$index = rand(0, count($keys) - 1);
		return $keys[$index];
	}

	private function handleAnswer($data) {
		$ans = $this->getAnswers();
		$respond = NULL;
		if (isset($data->yes)) {
			$respond = TRUE;
		} else if (isset($data->no)) {
			$respond = FALSE;
		}
		$ans[$data->questionKey] = $respond;
		if (isset($data->reset)) {
			$this->resetAnswer();
		} else {
			$this->setAnswers($ans);
		}
	}

	private function resetAnswer() {
		$this->getSession()->ans = NULL;
		$this->getSession()->testedTerminalProposition = NULL;
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

	private function getSession($newKey = FALSE) {
		if ($newKey)
			return new Container($newKey);
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

	private function getKeyUniqueForArray($premiss, $includeNegative = TRUE) {
		if (!$includeNegative) {
			return $premiss['verbe'] . '|' . $premiss['proposition'];
		}
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
					$this->currentDemandablesProposition[$currentKey]['OR'][$tmpKey][$this->getKeyUniqueForArray($premiss)] = $premiss;
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
