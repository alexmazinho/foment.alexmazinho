<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;

use Foment\GestioBundle\Controller\UtilsController;
use Foment\GestioBundle\Entity\Apunt;
use Foment\GestioBundle\Entity\Saldo;


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
		->add('saldo', 'text', array(
			'data'		=> $saldo,
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
	
	public function saldoAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$apuntsAsArray = array();
		$saldosPerAnular = array();
		$nouSaldo = null;
error_log('0');		
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
error_log('1');
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
error_log('2');			
			$nouSaldo = new Saldo($datasaldo, $import);
			$em->persist($nouSaldo);
			
			$em->flush();
			
			$saldo = $this->getSaldoMetallic(); // Saldo actual, després de l'últim apunt
			
			$apuntsAsArray = $this->queryApunts($request, 1 * UtilsController::DEFAULT_PERPAGE, $saldo);
			
		} catch (\Exception $e) {
error_log('errror');			
			if ($nouSaldo != null) $em->detach($nouSaldo);
			foreach ($saldosPerAnular as $saldoPerAnular) $em->refresh($saldoPerAnular);
			
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
			return $response;
		}
error_log('fi');		
		return $this->render('FomentGestioBundle:Includes:taulaapunts.html.twig',
				array('apunts' => $apuntsAsArray));
	}
	
	public function exportapuntsAction(Request $request)
	{
			return new Response("export apunts");
	}
	
	
    
}
