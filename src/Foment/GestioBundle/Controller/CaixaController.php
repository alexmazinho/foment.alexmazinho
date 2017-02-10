<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Foment\GestioBundle\Controller\UtilsController;
use Foment\GestioBundle\Entity\Apunt;
use Foment\GestioBundle\Entity\ApuntConcepte;
use Foment\GestioBundle\Entity\Saldo;
use Foment\GestioBundle\Form\FormApunt;
use Foment\GestioBundle\Form\FormApuntConcepte;


class CaixaController extends BaseController
{
	public function conceptesAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		
		$conceptes = $em->getRepository('FomentGestioBundle:ApuntConcepte')->findBy(array('tipus' => UtilsController::getTipusConceptesApunts(false)), array('tipus' => 'ASC'));
		
		$associacions = $this->getAssociacionsConceptes($conceptes);
		
		$form = $this->createForm(new FormApuntConcepte(), new ApuntConcepte());
		
		return $this->render('FomentGestioBundle:Caixa:conceptes.html.twig', array(
				'form' 			=> $form->createView(),
				'conceptes' 	=> $conceptes,
				'associacions' 	=> $associacions
		));
	}
	
	private function getAssociacionsConceptes($conceptes) {
		$em = $this->getDoctrine()->getManager();
		
		$associacions = array();
		foreach ($conceptes as $concepte) {
				
			$associacions[ $concepte->getId() ] = array( 'seccions' => array(), 'activitats' => array() );
				
			$seccions = $concepte->getSeccions();
				
			foreach (explode(",", $seccions) as $seccioId) {
				$seccioId = trim($seccioId);
		
				$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($seccioId);
		
				if ($seccio != null) {
					$associacions[ $concepte->getId() ]['seccions'][] = array( 'id' => $seccio->getId(), 'nom' => $seccio->getNom() );
				}
			}
				
			$activitats = $concepte->getActivitats();
				
			foreach (explode(",", $activitats) as $activitatId) {
				$activitatId = trim($activitatId);
					
				$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($activitatId);
					
				if ($activitat != null) {
					$associacions[ $concepte->getId() ]['activitats'][] = array( 'id' => $activitat->getId(), 'descripcio' => $activitat->getDescripcio() );
				}
			}
		}
		
		return $associacions;
	}
	
	public function conceptebaixaAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
	
		$id = $request->query->get('id', 0);
		$concepte = $em->getRepository('FomentGestioBundle:ApuntConcepte')->find($id);
	
		$response = '';
		try {
	
			if ($concepte == null) throw new \Exception('No s\'ha trobat el concepte');
	
			$concepte->setDatabaixa(new \DateTime());
	
			$em->flush();
			
			$conceptes = $em->getRepository('FomentGestioBundle:ApuntConcepte')->findBy(array(), array('tipus' => 'ASC'));
	
			$associacions = $this->getAssociacionsConceptes($conceptes);
			
			$response = $this->render('FomentGestioBundle:Caixa:taulaconceptes.html.twig', array(
				'conceptes' 	=> $conceptes,
				'associacions' 	=> $associacions
			));
				
		} catch (\Exception $e) {
			// Ko, mostra form amb errors
			if ($concepte != null) $em->refresh($concepte);
	
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
				
		}
	
		return $response;
	}
	
	public function conceptedesarAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
	
		$id = $request->query->get('id', 0);
		$tipus = $request->query->get('tipus', '');
		$textconcepte = $request->query->get('concepte', '');
		$seccions = $request->query->get('seccions', '');
		$activitats = $request->query->get('activitats', '');
		$strDatabaixa = $request->query->get('databaixa', '');
		$databaixa = null;
		if ($strDatabaixa != '') $databaixa = \DateTime::createFromFormat('d/m/Y', urldecode($strDatabaixa));
		
		$concepte = $em->getRepository('FomentGestioBundle:ApuntConcepte')->find($id);
	
		$response = '';
		try {
			if ($id > 0 && $concepte == null) throw new \Exception('No s\'ha trobat el concepte');
			
			if ($tipus == '') throw new \Exception('Cal indicar el tipus');
			
			$tipusDeConceptes = UtilsController::getTipusConceptesApunts();
			
			if (!in_array($tipus, $tipusDeConceptes)) throw new \Exception('El tipus de concepte no és correcte');
			
			if ($textconcepte == '') throw new \Exception('Cal indicar un text pel concepte');
			
			if ($seccions != '' && $activitats != '') throw new \Exception('No es poden associar activitats i seccions al mateix concepte');
			
			if ($seccions != '') {
				// Validar que la secció no estigui associada a un altre concepte
				foreach (explode(",", $seccions) as $seccioId) {
					$seccioId = trim($seccioId);
				
					$concepteExistent = $this->queryApuntConcepteBySeccioActivitat($seccioId, true, $id);
					
					if ($concepteExistent != null) throw new \Exception('Secció associada al concepte '.$concepteExistent->getConcepte());
				}
			}
			
			if ($activitats != '') {
				// Validar que l'activitat no estigui associada a un altre concepte
				foreach (explode(",", $activitats) as $activitatId) {
					$activitatId = trim($activitatId);
				
					$concepteExistent = $this->queryApuntConcepteBySeccioActivitat($activitatId, false, $id);
						
					if ($concepteExistent != null) throw new \Exception('Activitat associada al concepte '.$concepteExistent->getConcepte());
				}
			}
				
			
			if ($id > 0 && $concepte != null) {
				// Modificació
				$concepte->setTipus($tipus);
				$concepte->setConcepte($textconcepte);
				$concepte->setDatabaixa($databaixa);
				$concepte->setSeccions($seccions);
				$concepte->setActivitats($activitats);
			} else {
				// Nou concepte
				if ($databaixa != null) throw new \Exception('No es pot crear un concepte de baixa');
				$concepte = new ApuntConcepte($tipus, $textconcepte, $seccions, $activitats);
				$em->persist($concepte);
			}
			
			$em->flush();
				
			$conceptes = $em->getRepository('FomentGestioBundle:ApuntConcepte')->findAll();
				
			$associacions = $this->getAssociacionsConceptes($conceptes);
			
			$response = $this->render('FomentGestioBundle:Caixa:taulaconceptes.html.twig', array(
					'conceptes' 	=> $conceptes,
					'associacions' 	=> $associacions
			));
	
		} catch (\Exception $e) {
			// Ko, mostra form amb errors
			if ($concepte != null) {
				if ($id > 0) $em->refresh($concepte);
				else $em->detach($concepte);
			}
	
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
	
		}
	
		return $response;
	}
	
	public function caixaAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		
		$queryparams = $this->getCaixaParams($request);
		
		$saldo = null; 
		$total = 0;
		$importultimsaldo = 0;
		$datasaldo = new \DateTime();
		$dataultimsaldo = null;
		$apuntsAsArray = array();
		
		try {
			$ultimsaldo = $this->getUltimSaldo();
			 
			if ($ultimsaldo == null) throw new \Exception('Cal indicar un saldo i data inicials');
			 
			$dataultimsaldo = $ultimsaldo->getDatasaldo();
			$importultimsaldo = $ultimsaldo->getImport();
			
			$saldo = $this->getSaldoMetallic(); // Saldo actual, després de l'últim apunt
			
			if ($saldo == null) throw new \Exception('Cal indicar un saldo i data inicials');
			
			$apuntsAsArray = $this->queryApunts($queryparams['page'] * $queryparams['perpage'], $saldo, $queryparams['tipusconcepte'], $queryparams['filtre']);
		
			if ($request->isXmlHttpRequest() == true) {
				// Filtre
				return $this->printTaulaApunts($queryparams, $ultimsaldo, $saldo);
			}
			
		} catch (\Exception $e) {
			if ($request->isXmlHttpRequest() == true) {
				$response = new Response($e->getMessage());
				$response->setStatusCode(500);
				return $response;
			} else {
				$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
			}
		}
		
		$desde = clone $datasaldo;
		$desde->sub(new \DateInterval('P1Y'));
		
		$form = $this->createFormBuilder()
		->add('desde', 'text', array(
				'data' 	=> $desde->format('d/m/Y')
		))
		->add('fins', 'text', array(
				'data' 	=> $datasaldo->format('d/m/Y')  // current
		))
		->add('datasaldo', 'text', array(
			'data' 	=> $datasaldo->format('d/m/Y H:i')
		))
		->add('saldo', 'number', array(
			'data'		=> $saldo,
			'precision'	=> 2,
			'read_only'	=> true	
		))
		->add('dataultimsaldo', 'hidden', array(
			'data' 	=> $dataultimsaldo == null?'':$dataultimsaldo->format('d/m/Y H:i')
		))
		->add('tipusconcepte', 'choice', array(
			'required'  => false,
			'choices'   => UtilsController::getTipusConceptesApunts(true),
			'data'		=> $queryparams['tipusconcepte'],
			'empty_value' => 'escollir...'
		))
		->add('filtre', 'text', array(     			// Camps formulari de filtre
			'required' 	=> false,
			'attr' 		=> array('class' => 'form-control filtre-text'),
			'data'		=> $queryparams['filtre']
		))
		->add('midapagina', 'choice', array(
			'required'  => true,
			'choices'   => UtilsController::getPerPageOptions(),
			'attr' 		=> array('class' => 'select-midapagina'),
			'data'		=> $queryparams['perpage']
		))
		->getForm();
		
		return $this->render('FomentGestioBundle:Caixa:caixa.html.twig', 
				array('form' => $form->createView(), 'apunts' => $apuntsAsArray, 'ultimsaldo' => $importultimsaldo, 'dataultimsaldo' => $dataultimsaldo, 'queryparams' => $queryparams));
	}
	
	public function apuntAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}	
	
		$queryparams = $this->getCaixaParams($request);
		
		$em = $this->getDoctrine()->getManager();
		
		$rebutId = 0;
		if ($request->getMethod() == 'POST') {
			$data = $request->request->get('apunt');
		
			$id = (isset($data['id'])?$data['id']:0);
			$rebutId = (isset($data['rebut'])?$data['rebut']:0);
		} else {
			$id = $request->query->get('id', 0);
		}
		$apunt = $em->getRepository('FomentGestioBundle:Apunt')->find($id);
		
		if ($apunt == null) {
			$apunt = new Apunt(0);
			$em->persist($apunt);
			
			$num = $this->getMaxApuntNumAny($apunt->getDataapunt()->format('Y'));
			$apunt->setNum($num);
		}
		$form = $this->createForm(new FormApunt(), $apunt);
		$response = '';
		if ($request->getMethod() == 'POST') {
			try {
				$form->handleRequest($request);
		
				if (!$form->isValid()) throw new \Exception('Dades incorrectes, cal revisar les dades de l\'apunt' );

				if ($apunt->getImport() < 0) throw new \Exception('No estan permesos valors negatius');

				if ($apunt->getConcepte() == null) throw new \Exception('Cal indicar un concepte');
				
				if ($rebutId > 0) {
					$rebut = $em->getRepository('FomentGestioBundle:Rebut')->find($rebutId);
					if ($rebut != null) {
						if (abs($rebut->getImport() - $apunt->getImport()) > 0.01) throw new \Exception('L\'import del rebut no es correspon amb l\'import indicat' );
						
						$apunt->setRebut($rebut);
					}
				} else {
					$apunt->setRebut(null);
				}
				
				$ultimsaldo = $this->getUltimSaldo();
				
				if ($ultimsaldo == null) throw new \Exception('Cal indicar un saldo i data inicials');
				
				$dataultimsaldo = $ultimsaldo->getDatasaldo();
				
				if ($apunt->getDataapunt()->format('Y-m-d H:i') <= $dataultimsaldo->format('Y-m-d H:i')) 
					throw new \Exception('No es poden afegir apunts abans del darrer registre de saldo de caixa '.$dataultimsaldo->format('Y-m-d H:i'));
				
				$num = $this->getMaxApuntNumAny($apunt->getDataapunt()->format('Y'));
				$apunt->setNum($num);

				$apunt->setDatamodificacio(new \DateTime());
				
				$em->flush();
				
				return $this->printTaulaApunts($queryparams, $ultimsaldo);
				
				// Ok, retorn form sms ok				
				/*return $this->render('FomentGestioBundle:Caixa:taulaapunts.html.twig',
										array('apunts' => $apuntsAsArray));*/
			} catch (\Exception $e) {
				// Ko, mostra form amb errors
				if ($apunt->getId() == 0) $em->detach($apunt);
				else $em->refresh($apunt);
					
				$response = new Response($e->getMessage());
				$response->setStatusCode(500);
				return $response;
			}
		} 
		// GET mostrar form
		return	$this->render('FomentGestioBundle:Caixa:apunt.html.twig',
					array('form' => $form->createView(), 'apunt' => $apunt));
	}
	
	public function apuntbaixaAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$queryparams = $this->getCaixaParams($request);
		
		$em = $this->getDoctrine()->getManager();
	
		$id = $request->query->get('id', 0);
		$apunt = $em->getRepository('FomentGestioBundle:Apunt')->find($id);

		try {	
		
			if ($apunt == null) throw new \Exception('No s\'ha trobat l\'apunt');
		
			$apunt->setDatabaixa(new \DateTime());				

			$em->flush();
			
			$response = $this->printTaulaApunts($queryparams);
			
		} catch (\Exception $e) {
			// Ko, mostra form amb errors
			if ($apunt != null) $em->refresh($apunt);
				
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
			
		}
		
		return $response;
	}
	
	public function saldoAction(Request $request)
	{
		$queryparams = $this->getCaixaParams($request);
		
		$em = $this->getDoctrine()->getManager();
		
		$apuntsAsArray = array();
		$saldosPerAnular = array();
		$apuntsPerAnular = array();
		$nouSaldo = null;
		
		try {
		
			if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
				throw new AccessDeniedException();
			}
		
			$strDatasaldo = $request->query->get('data', '');
			if ($strDatasaldo != '') $datasaldo = \DateTime::createFromFormat('d/m/Y H:i', urldecode($strDatasaldo));
			else $datasaldo = new \DateTime(); 
			
			$import = $request->query->get('import', '');
			
			if (!is_numeric($import)) throw new \Exception('L\'import no és numèric');
			
			if ($import < 0) throw new \Exception('No estan permesos valors negatius');

			// Anul·lar tots els saldos posteriors i els apunts automàtics d'ajust, queden compromesos
			$strQuery  = " SELECT s FROM Foment\GestioBundle\Entity\Saldo s ";
			$strQuery .= " WHERE s.databaixa IS NULL ";
			$strQuery .= " AND s.datasaldo > :current ";

			$query = $em->createQuery($strQuery);
			$query->setParameter('current', $datasaldo->format('Y-m-d H:i:s'));
			$saldosPerAnular = $query->getResult();
			foreach ($saldosPerAnular as $saldoPerAnular) $saldoPerAnular->setDatabaixa(new \DateTime());
			
			$strQuery  = " SELECT a FROM Foment\GestioBundle\Entity\Apunt a ";
			$strQuery .= " WHERE a.databaixa IS NULL ";
			$strQuery .= " AND a.dataapunt > :current ";
			$strQuery .= " AND a.concepte = :ajust ";
			
			$query = $em->createQuery($strQuery);
			$query->setParameter('current', $datasaldo->format('Y-m-d H:i:s'));
			$query->setParameter('ajust', UtilsController::ID_CONCEPTE_APUNT_INTERN);
			$apuntsPerAnular = $query->getResult();
			foreach ($apuntsPerAnular as $apuntPerAnular) $apuntPerAnular->setDatabaixa(new \DateTime());
			
			$saldo = $this->getSaldoMetallic($datasaldo); // Saldo en el moment $datasaldo
			
			/* Concepte per ajustos interns i correccions */
			$concepteApuntIntern = $em->getRepository('FomentGestioBundle:ApuntConcepte')->find(UtilsController::ID_CONCEPTE_APUNT_INTERN);
			
			if ($saldo == null) {
				$dataapunt = clone $datasaldo;
				// Saldo, posterior a apunt correcció
				$dataapunt->sub(new \DateInterval('PT1M'));
				
				$num = $this->getMaxApuntNumAny($dataapunt->format('Y'));

				$apunt = new Apunt($num, $import, $dataapunt, UtilsController::TIPUS_APUNT_ENTRADA, $concepteApuntIntern);
				$em->persist($apunt);
			} else {
				$correccio = $import - $saldo;
				if (abs($correccio) >= 0.01  ) {
					// Cal fer apunt correcció del saldo
					$dataapunt = clone $datasaldo;
					// Saldo, posterior a apunt correcció
					$dataapunt->sub(new \DateInterval('PT1M'));
						
					$num = $this->getMaxApuntNumAny($dataapunt->format('Y'));
					
					// $correccio > 0 saldo indicat > actual actual => fer apunt entrada 
					// $correccio < 0 saldo indicat < actual actual => fer apunt sortida
					$apunt = new Apunt($num, $correccio, $dataapunt, $correccio > 0?UtilsController::TIPUS_APUNT_ENTRADA:UtilsController::TIPUS_APUNT_SORTIDA, $concepteApuntIntern);
				}	
			}
			
			$nouSaldo = new Saldo($datasaldo, $import);
			$em->persist($nouSaldo);
			
			$em->flush();
			
			$response = $this->printTaulaApunts($queryparams, $nouSaldo);
			
		} catch (\Exception $e) {
		
			if ($nouSaldo != null) $em->detach($nouSaldo);
			foreach ($saldosPerAnular as $saldoPerAnular) $em->refresh($saldoPerAnular);
			foreach ($apuntsPerAnular as $apuntPerAnular) $em->refresh($apuntPerAnular);
			
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
		}
		
		return $response;
	}
	
	
	public function saldosAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		
		$em = $this->getDoctrine()->getManager();
		
		$strQuery  = " SELECT s FROM Foment\GestioBundle\Entity\Saldo s ";
		$strQuery .= " WHERE s.databaixa IS NULL ";
		$strQuery .= " ORDER BY s.datasaldo DESC ";
	
		$query = $em->createQuery($strQuery);
		$query->setMaxResults(10);
		
		$saldos = $query->getResult();
	
		return $this->render('FomentGestioBundle:Caixa:taulasaldos.html.twig',
		 array('saldos' => $saldos));
	}
	
	private function printTaulaApunts($queryparams, $ultimsaldo = null, $saldo = null) {
	
		if (!isset($queryparams['page'])) $queryparams['page'] = 1;
		if (!isset($queryparams['perpage'])) $queryparams['perpage'] = UtilsController::DEFAULT_PERPAGE;
		if (!isset($queryparams['tipusconcepte'])) $queryparams['tipusconcepte'] = '';
		if (!isset($queryparams['filtre'])) $queryparams['filtre'] = '';
	
		if ($ultimsaldo == null) {
			$ultimsaldo = $this->getUltimSaldo();
	
			if ($ultimsaldo == null) throw new \Exception('Cal indicar un saldo i data inicials');
		}
		$dataultimsaldo = $ultimsaldo->getDatasaldo();
		$importultimsaldo = $ultimsaldo->getImport();
	
	
		if ($saldo == null) {
			$saldo = $this->getSaldoMetallic(); // Saldo actual, després de l'últim apunt
			if ($saldo == null) throw new \Exception('Cal indicar un saldo i data inicials');
		}
			
		$apuntsAsArray = $this->queryApunts(1 * UtilsController::DEFAULT_PERPAGE, $saldo, $queryparams['tipusconcepte'], $queryparams['filtre']);
	
		$data = $this->renderView('FomentGestioBundle:Caixa:taulaapunts.html.twig',
								array('apunts' => $apuntsAsArray, 'ultimsaldo' => $importultimsaldo,
										'dataultimsaldo' => $dataultimsaldo, 'queryparams' => $queryparams));
	
		$response = new Response( );
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent( json_encode( array( 'data' => $data, 'saldo' => $saldo, 'dataultimsaldo' => $dataultimsaldo->format('Y-m-d H:i')) ) ); // html + saldo + dataultimsaldo per actualitzar
	
		return $response;
	}
	
}
