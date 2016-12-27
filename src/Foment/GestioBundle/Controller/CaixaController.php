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
		
		$saldo = ''; 
		$total = 0;
		$datasaldo = new \DateTime();
		$apuntsAsArray = array();
		
		try {
		
			$saldo = $this->getSaldoMetallic(); // Saldo actual, després de l'últim apunt
			
			if ($saldo == null) throw new \Exception('Cal indicar un saldo i data inicials');
			
			$apuntsAsArray = $this->queryApunts($request, $page * $perpage, $saldo, $codi, $concepte);
			
		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
		}
		
		$form = $this->createFormBuilder()
		->add('datasaldo', 'text', array(
			'data' 	=> $datasaldo->format('d/m/Y')
		))
		->add('saldo', 'number', array(
			'data'		=> $saldo,
			'precision'	=> 2,
			'read_only'	=> true	
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
		
		$queryparams = array( 'page' => $page, 'perpage' => $perpage,	'codi' =>  $codi, 'concepte' => $concepte );
		
		return $this->render('FomentGestioBundle:Caixa:caixa.html.twig', 
				array('form' => $form->createView(), 'apunts' => $apuntsAsArray, 'queryparams' => $queryparams));
	}
	
	public function apuntAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}	
	
		$em = $this->getDoctrine()->getManager();
		
		if ($request->getMethod() == 'POST') {
			$data = $request->request->get('apunt');
		
			$id = (isset($data['id'])?$data['id']:0);
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
				
				$num = $this->getMaxApuntNumAny($apunt->getDataapunt()->format('Y'));
				$apunt->setNum($num);

				$apunt->setDatamodificacio(new \DateTime());
					
				$em->flush();
				
				$saldo = $this->getSaldoMetallic(); // Saldo actual, després de l'últim apunt
					
				$apuntsAsArray = $this->queryApunts($request, 1 * UtilsController::DEFAULT_PERPAGE, $saldo);
				
				$this->get('session')->getFlashBag()->add('notice',	'Apunt afegit correctament');

				// Ok, retorn form sms ok				
				return $this->render('FomentGestioBundle:Includes:taulaapunts.html.twig',
										array('apunts' => $apuntsAsArray));
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
	
	public function saldoAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$apuntsAsArray = array();
		$saldosPerAnular = array();
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

			// Anul·lar tots els saldos posteriors, queden compromesos
			$strQuery  = " SELECT s FROM Foment\GestioBundle\Entity\Saldo s ";
			$strQuery .= " WHERE s.databaixa IS NULL ";
			$strQuery .= " AND s.datasaldo > :current ";

			$query = $em->createQuery($strQuery);
			$query->setParameter('current', $datasaldo->format('Y-m-d H:i:s'));
			$saldosPerAnular = $query->getResult();
			foreach ($saldosPerAnular as $saldoPerAnular) $saldoPerAnular->setDatabaixa(new \DateTime());
			
			$saldo = $this->getSaldoMetallic($datasaldo); // Saldo en el moment $datasaldo
			
			if ($saldo != null && abs($import - $saldo) >= 0.01  ) {
				// Cal fer apunt correcció del saldo
				
			}
			
			$nouSaldo = new Saldo($datasaldo, $import);
			$em->persist($nouSaldo);
			
			$em->flush();
			
			$saldo = $this->getSaldoMetallic(); // Saldo actual, després de l'últim apunt
			
			$apuntsAsArray = $this->queryApunts($request, 1 * UtilsController::DEFAULT_PERPAGE, $saldo);
			
		} catch (\Exception $e) {
		
			if ($nouSaldo != null) $em->detach($nouSaldo);
			foreach ($saldosPerAnular as $saldoPerAnular) $em->refresh($saldoPerAnular);
			
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
			return $response;
		}
		
		return $this->render('FomentGestioBundle:Includes:taulaapunts.html.twig',
				array('apunts' => $apuntsAsArray));
	}
	
	public function exportapuntsAction(Request $request)
	{
			return new Response("export apunts");
	}
	
	
    
}
