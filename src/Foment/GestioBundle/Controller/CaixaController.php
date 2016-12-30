<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Foment\GestioBundle\Controller\UtilsController;
use Foment\GestioBundle\Entity\Apunt;
use Foment\GestioBundle\Entity\Saldo;
use Foment\GestioBundle\Form\FormApunt;


class CaixaController extends BaseController
{
	public function caixaAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		
		$page = $request->query->get('page', 1);  // Última
		$perpage = $request->query->get('perpage', UtilsController::DEFAULT_PERPAGE);  // Sempre mostra els 'perpage' primers
		$codi = $request->query->get('codi', '');
		$concepte = $request->query->get('filtre', '');
		
		$queryparams = array( 'page' => $page, 'perpage' => $perpage,	'codi' =>  $codi, 'filtre' => $concepte );
		
		$saldo = ''; 
		$total = 0;
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
			
			$apuntsAsArray = $this->queryApunts($page * $perpage, $saldo, $codi, $concepte);
			
		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
		}
		
		if ($request->isXmlHttpRequest() == true) {
			// Filtre
			return $this->printTaulaApunts($queryparams, $ultimsaldo, $saldo);
		}
		
		$form = $this->createFormBuilder()
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
		->add('codi', 'choice', array(
			'required'  => false,
			'choices'   => UtilsController::getCodisComptables(),
			'data'		=> $codi
		))
		->add('filtre', 'text', array(     			// Camps formulari de filtre
			'required' 	=> false,
			'attr' 		=> array('class' => 'form-control filtre-text'),
			'data'		=> $concepte
		))
		->add('midapagina', 'choice', array(
			'required'  => true,
			'choices'   => UtilsController::getPerPageOptions(),
			'attr' 		=> array('class' => 'select-midapagina'),
			'data'		=> $perpage
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
			$strQuery .= " AND (a.codi = :inicial OR a.codi = :ajust) ";
			
			$query = $em->createQuery($strQuery);
			$query->setParameter('current', $datasaldo->format('Y-m-d H:i:s'));
			$query->setParameter('inicial', UtilsController::CODI_COMPTABLE_AJUST_INICIAL);
			$query->setParameter('ajust', UtilsController::CODI_COMPTABLE_CORRECCIO);
			$apuntsPerAnular = $query->getResult();
			foreach ($apuntsPerAnular as $apuntPerAnular) $apuntPerAnular->setDatabaixa(new \DateTime());
			
			$saldo = $this->getSaldoMetallic($datasaldo); // Saldo en el moment $datasaldo
			
			if ($saldo == null) {
				$dataapunt = clone $datasaldo;
				// Saldo, posterior a apunt correcció
				$dataapunt->sub(new \DateInterval('PT1M'));
				
				$num = $this->getMaxApuntNumAny($dataapunt->format('Y'));

				$apunt = new Apunt($num, $import, $dataapunt, UtilsController::TIPUS_APUNT_ENTRADA, 
									UtilsController::CODI_COMPTABLE_AJUST_INICIAL, UtilsController::getCodiComptable(UtilsController::CODI_COMPTABLE_AJUST_INICIAL).' (automàtic)');
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
					$apunt = new Apunt($num, $correccio, $dataapunt, $correccio > 0?UtilsController::TIPUS_APUNT_ENTRADA:UtilsController::TIPUS_APUNT_SORTIDA,
							UtilsController::CODI_COMPTABLE_CORRECCIO, UtilsController::getCodiComptable(UtilsController::CODI_COMPTABLE_CORRECCIO).' (automàtic)');
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
	
	private function printTaulaApunts($queryparams, $ultimsaldo = null, $saldo = null) {
	
		if (!isset($queryparams['page'])) $queryparams['page'] = 1;
		if (!isset($queryparams['perpage'])) $queryparams['perpage'] = UtilsController::DEFAULT_PERPAGE;
		if (!isset($queryparams['codi'])) $queryparams['codi'] = '';
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
			
		$apuntsAsArray = $this->queryApunts(1 * UtilsController::DEFAULT_PERPAGE, $saldo, $queryparams['codi'], $queryparams['filtre']);
	
		$this->get('session')->getFlashBag()->add('notice',	'Apunt afegit correctament');
	
		$data = $this->renderView('FomentGestioBundle:Caixa:taulaapunts.html.twig', 
									array('apunts' => $apuntsAsArray, 'ultimsaldo' => $importultimsaldo, 
											'dataultimsaldo' => $dataultimsaldo, 'queryparams' => $queryparams));
	
		$response = new Response( );
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent( json_encode( array( 'data' => $data, 'saldo' => $saldo, 'dataultimsaldo' => $dataultimsaldo->format('Y-m-d H:i')) ) ); // html + saldo + dataultimsaldo per actualitzar
	
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
	
	
	public function exportapuntsAction(Request $request)
	{
			return new Response("export apunts");
	}
	
	
    
}
