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
		$codi = $request->query->get('codi', 0);
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
			
			if ($codi <= 0) throw new \Exception('El codi és incorrecte');
			
			$conceptesCodiExistents = $em->getRepository('FomentGestioBundle:ApuntConcepte')->findBy( array('codi' => $codi ) );
			
			foreach ($conceptesCodiExistents as $concepteExistent) {
				if ($concepteExistent->getId() != $concepte->getId()) throw new \Exception('Aquest codi ja està assignat al concepte '.$concepteExistent->getConcepteLlarg());
			}
			
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
				$concepte->setCodi($codi);
				$concepte->setConcepte($textconcepte);
				$concepte->setDatabaixa($databaixa);
				$concepte->setSeccions($seccions);
				$concepte->setActivitats($activitats);
			} else {
				// Nou concepte
				if ($databaixa != null) throw new \Exception('No es pot crear un concepte de baixa');
				$concepte = new ApuntConcepte($tipus, $codi, $textconcepte, $seccions, $activitats);
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
		$current = new \DateTime();
		
		$queryparams = $this->getCaixaParams($request);
		
		$apuntsAsArray = array();
		$saldoConsolidat = null;
		$dataSaldoConsolidat = null;
		$desglossament = '';
		$importcaixa = 0;
		$saldoapunts = 0;
		$desde = clone $current;
		
		if (!$request->isXmlHttpRequest()) $this->get('session')->getFlashBag()->clear();
		
		try {
			$currentSaldo = $this->getCurrentSaldo();
			$desglossament = $currentSaldo->getDesglossament();
			$importcaixa = $currentSaldo->getImport();
			
			$saldoConsolidat = $this->getSaldoConsolidat();
			
			if ($saldoConsolidat == null) throw new \Exception('Cal indicar un saldo inicial de caixa');
			
			$saldoapunts = $this->getSaldoApunts(); // Saldo actual, després de l'últim apunt
			
			$apuntsAsArray = $this->queryApunts(0, $saldoapunts, $queryparams['tipusconcepte'], $queryparams['filtre']);
			
			$queryparams['rowcount'] = count($apuntsAsArray);    // p.e. 22
			$queryparams['pagetotal'] = ceil($queryparams['rowcount']/$queryparams['perpage']);  // perpage = 5 => 5 pages
			if ($queryparams['page'] == '') {
				$queryparams['page'] = $queryparams['pagetotal']; 	// Situar-se a la darrera pàgina
			}
			
			$fromIndex = $queryparams['rowcount'] - (($queryparams['pagetotal'] - $queryparams['page'] + 1) * $queryparams['perpage']);
			if ($fromIndex > 0) array_splice($apuntsAsArray, 0, $fromIndex);  // offset + length
			else {
				// Primera pàgina. Els que queden
				$toIndex = $queryparams['rowcount'] - ($queryparams['pagetotal'] - 1) * $queryparams['perpage'];
				array_splice($apuntsAsArray, $toIndex, $queryparams['rowcount'] - $toIndex);
			}
			
			if ($request->isXmlHttpRequest() && $queryparams['action'] != 'form') {
				// Update taula apunts
				$this->get('session')->getFlashBag()->clear();
				
				return $this->render('FomentGestioBundle:Caixa:taulaapunts.html.twig',
						array('apunts' => $apuntsAsArray, 'saldoconsolidat' => $saldoConsolidat, 'queryparams' => $queryparams));
			}
			
			$dataSaldoConsolidat = $saldoConsolidat->getDataconsolidat();
			
			$desde = clone $dataSaldoConsolidat;
			//$desde->sub(new \DateInterval('P1Y'));
			
		} catch (\Exception $e) {
			
			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
		}
		
		$form = $this->createFormBuilder()
		->add('desde', 'text', array(
				'data' 	=> $desde->format('d/m/Y')
		))
		->add('fins', 'text', array(
				'data' 	=> $current->format('d/m/Y')  // current
		))
		->add('saldoapunts', 'number', array(
				'data'		=> $saldoapunts,
				'precision'	=> 2,
				'read_only'	=> true
		))
		->add('desglossament', 'hidden', array(
				'data' 	=> $desglossament == ''?UtilsController::JSON_DESGLOSSAMENT:$desglossament
		))
		->add('importcaixa', 'number', array(
				'data'		=> $importcaixa,
				'precision'	=> 2,
				'read_only'	=> true
		))
		->add('datasaldoconsolidat', 'hidden', array(
				'data' 	=> $dataSaldoConsolidat != null?$dataSaldoConsolidat->format('d/m/Y H:i'):''
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

		if ($saldoConsolidat == null || abs($saldoapunts - $importcaixa) >= 0.01) {
			$queryparams['saldogap'] = 1;
		} else {
			$queryparams['saldogap'] = 0;
		}
		
		if ($request->isXmlHttpRequest()) {
			return $this->render('FomentGestioBundle:Caixa:caixapage.html.twig',
					array('form' => $form->createView(), 'apunts' => $apuntsAsArray, 'saldoconsolidat' => $saldoConsolidat, 'queryparams' => $queryparams));
		}
		
		return $this->render('FomentGestioBundle:Caixa:caixa.html.twig', 
				array('form' => $form->createView(), 'apunts' => $apuntsAsArray, 'saldoconsolidat' => $saldoConsolidat, 'queryparams' => $queryparams));
	}
	
	public function apuntAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}	
	
		$queryparams = $this->getCaixaParams($request);
		
		$em = $this->getDoctrine()->getManager();
		
		$this->get('session')->getFlashBag()->clear();
		
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
						//if (abs($rebut->getImport() - $apunt->getImport()) > 0.01) throw new \Exception('L\'import del rebut no es correspon amb l\'import indicat' );
						
						$apunt->setRebut($rebut);
					}
				} else {
					$apunt->setRebut(null);
				}
				
				$ultimsaldo = $this->getCurrentSaldo();
				
				if ($ultimsaldo == null) throw new \Exception('Cal indicar un saldo i data inicials');
				
				$dataultimsaldo = $ultimsaldo->getDatasaldo();
				
				if ($apunt->getDataapunt()->format('Y-m-d H:i') <= $dataultimsaldo->format('Y-m-d H:i')) 
					throw new \Exception('No es poden afegir apunts abans del darrer registre de saldo de caixa '.$dataultimsaldo->format('Y-m-d H:i'));
				
				$num = $this->getMaxApuntNumAny($apunt->getDataapunt()->format('Y'));
				$apunt->setNum($num);

				$apunt->setDatamodificacio(new \DateTime());
				
				$em->flush();
				
				$this->get('session')->getFlashBag()->add('notice',	'Apunt afegit correctament');
				
				return $this->forward('FomentGestioBundle:Caixa:caixa');
				
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
	
		$this->get('session')->getFlashBag()->clear();
		
		$id = $request->query->get('id', 0);
		$apunt = $em->getRepository('FomentGestioBundle:Apunt')->find($id);

		try {	
		
			if ($apunt == null) throw new \Exception('No s\'ha trobat l\'apunt');
		
			if ($apunt->getConcepte()->getId() == UtilsController::ID_CONCEPTE_APUNT_INTERN) {
				// últim ajust. Anul·lar consolidació darrer saldo			
				$saldo = $this->getSaldoConsolidat();
				if ($saldo != null) {
					$saldo->setImportconsolidat(null);
					$saldo->setDataconsolidat(null);
				}
			}
			
			$apunt->setDatabaixa(new \DateTime());				

			$em->flush();
			
			
			$this->get('session')->getFlashBag()->add('notice',	'Apunt esborrat correctament');
			
			return $this->forward('FomentGestioBundle:Caixa:caixa');
			
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
		
		$this->get('session')->getFlashBag()->clear();
		
		$apuntsAsArray = array();
		$saldosPerAnular = array();
		$apuntsPerAnular = array();
		$saldo = null;
		$current = new \DateTime();
		try {
			if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
				throw new AccessDeniedException();
			}
		
			$action  = $request->query->get('action', 0);
			
			$saldoapunts = $this->getSaldoApunts();
			
			$saldo = $this->getCurrentSaldo();
		
			$pendent = $request->query->get('pendent', 0);
			$cent1 = $request->query->get('cent1', 0);
			$cent2 = $request->query->get('cent2', 0);
			$cent5 = $request->query->get('cent5', 0);
			$cent10 = $request->query->get('cent10', 0);
			$cent20 = $request->query->get('cent20', 0);
			$cent50 = $request->query->get('cent50', 0);
			$eur1 = $request->query->get('eur1', 0);
			$eur2 = $request->query->get('eur2', 0);
			$eur5 = $request->query->get('eur5', 0);
			$eur10 = $request->query->get('eur10', 0);
			$eur20 = $request->query->get('eur20', 0);
			$eur50 = $request->query->get('eur50', 0);
			$eur100 = $request->query->get('eur100', 0);
			$eur200 = $request->query->get('eur200', 0);
			$eur500 = $request->query->get('eur500', 0);
			
			$desglossament = UtilsController::crearDesglossament($pendent, $cent1, $cent2, $cent5, $cent10, $cent20, $cent50, $eur1, $eur2, $eur5, $eur10, $eur20, $eur50, $eur100, $eur200, $eur500);
			
			if ($action == 'calcular') {
				// Calcula import desglossament
				return new Response( UtilsController::calcularDesglossament( json_encode($desglossament) ) );
			}
			
			
			if ($saldo->consolidat()) {
				// Si estava consolidat, crear-ne un de nou
				$saldo = new Saldo($current, json_encode($desglossament)); 
				$em->persist($saldo);
			} else {
				$saldo->setDesglossament( json_encode($desglossament) );
				$saldo->setDatasaldo($current);
			}
			
			$saldocaixa = $saldo->getImport();
			$correccio = $saldocaixa - $saldoapunts;  // p.e. caixa diu 10€ i apunts 11€ => ajust de sortida 1 €
			
			$smsExit = '';
			if ($action == 'save') {
				// Desar o consolidar saldo. Si import caixa indicat coincideix amb saldo apunts calculat aleshores consolida 
				$smsExit = 'Saldo desat correctament';
			}
				
			if ($action == 'annotation') {
				// Consolidar saldo i crear apunt ajust
				if (abs($correccio) >= 0.01  ) {
					$num = $this->getMaxApuntNumAny($current->format('Y'));
					
					// Concepte per ajustos interns i correccions
					$concepteApuntIntern = $em->getRepository('FomentGestioBundle:ApuntConcepte')->find(UtilsController::ID_CONCEPTE_APUNT_INTERN);
					// $correccio > 0 saldo indicat > actual actual => fer apunt entrada
					// $correccio < 0 saldo indicat < actual actual => fer apunt sortida
					$apunt = new Apunt($num, abs($correccio), $current, $correccio > 0?UtilsController::TIPUS_APUNT_ENTRADA:UtilsController::TIPUS_APUNT_SORTIDA, $concepteApuntIntern);
					$em->persist($apunt);
					
					$smsExit = 'Saldo consolidat i afegit ajust';
				} else {
					$smsExit = 'Saldo consolidat correctament';
				}
				
				$correccio = 0; // Consolidar
			}
			
			if (abs($correccio) < 0.01  ) {
				// Si ja estava consolidat crear un de nouConsolidar si els imports estan quadrats
				$saldo->setImportconsolidat($saldocaixa);
				$saldo->setDataconsolidat($current);
			}
			
			$em->flush();

			$this->get('session')->getFlashBag()->add('notice', $smsExit);
			
			return $this->forward('FomentGestioBundle:Caixa:caixa');
			
		} catch (\Exception $e) {
		
			if ($saldo != null) {
				if ($saldo->getId() == 0) $em->detach($saldo);
				else $em->refresh($saldo);
			}
			
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
	
	
}
