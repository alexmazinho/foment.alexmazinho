<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

//use Doctrine\Common\Persistence\Registry;


use Foment\GestioBundle\Classes\CSVWriter;
use Foment\GestioBundle\Entity\Soci;
use Foment\GestioBundle\Entity\Persona;
use Foment\GestioBundle\Entity\Seccio;
use Foment\GestioBundle\Entity\Junta;
use Foment\GestioBundle\Entity\Activitat;
use Foment\GestioBundle\Entity\ActivitatPuntual;
use Foment\GestioBundle\Entity\ActivitatAnual;
use Foment\GestioBundle\Entity\Periode;
use Foment\GestioBundle\Form\FormSoci;
use Foment\GestioBundle\Form\FormPersona;
use Foment\GestioBundle\Form\FormSeccio;
use Foment\GestioBundle\Form\FormJunta;
use Foment\GestioBundle\Form\FormActivitatPuntual;
use Foment\GestioBundle\Entity\AuxMunicipi;
use Foment\GestioBundle\Classes\TcpdfBridge;
use Foment\GestioBundle\Entity\Rebut;
use Symfony\Component\Validator\Constraints\Length;
use Foment\GestioBundle\Entity\Facturacio;


class RebutsController extends BaseController
{
	public function gestiorebutsAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		 
		$tots = false;
		if ($request->query->has('tots') && $request->query->get('tots') == 1) $tots = true;
		
		 
		$queryparams = $this->queryRebuts($request);
	
		/* Si $p == true (pendents vist i plua) i $s == false (socis i no socis. Cal revisar resultat de la $query a ma */
	
		$paginator  = $this->get('knp_paginator');
		$rebuts = $paginator->paginate(
				$queryparams['query'],
				$queryparams['page'],
				($tots == true)?9999:10 //limit per page
		);
		 
		// Form
		$defaultData = array('anulats' => $queryparams['anulats'], 'retornats' => $queryparams['retornats'],
				'cobrats' => $queryparams['cobrats'], 'tipus' => $queryparams['tipus'], 'persona' => $queryparams['persona'],
				'cercaactivitats' => implode(",", $queryparams['activitats']), 'seccions' => $queryparams['seccions'],
				'facturacio' => $queryparams['facturacio']);
	
		if (isset($queryparams['nini']) and $queryparams['nini'] > 0)  $defaultData['numini'] = $queryparams['nini'];
		if (isset($queryparams['nfi']) and $queryparams['nfi'] > 0)  {
			$defaultData['numfi'] = $queryparams['nfi'];
			$defaultData['numficheck'] = true;
		} else {
			$defaultData['numficheck'] = false;
		}
		if (isset($queryparams['dini']) and $queryparams['dini'] != '')  $defaultData['dataemissioini'] = $queryparams['dini'];
		if (isset($queryparams['dfi']) and $queryparams['dfi'] != '')  $defaultData['dataemissiofi'] = $queryparams['dfi'];
	
		 
		$form = $this->createFormBuilder($defaultData)
		->add('numini', 'integer', array('required' => false))
		->add('numficheck', 'checkbox')
		->add('numfi', 'integer', array('required' => false, 'read_only' => ($defaultData['numficheck'] == false) ) )
		->add('persona', 'text', array() )
		->add('dataemissioini', 'text', array() )
		->add('dataemissiofi', 'text', array() )
		->add('seccions', 'entity', array(
				'error_bubbling'	=> true,
				'class' => 'FomentGestioBundle:Seccio',
				'property' 			=> 'info',
				'multiple' 			=> true,
				'required'  		=> false,
		))
		->add('cercaactivitats', 'hidden', array('required'	=> false ))
		->add('anulats', 'checkbox')
		->add('retornats', 'checkbox')
		->add('selectorcobrats', 'choice', array(
				'required'  => true,
				'choices'   => array(0 => 'tots', 1 => 'cobrats', 2 => 'no cobrats'),
				'data'		=> $queryparams['cobrats']) )
		->add('selectortipuspagament', 'choice', array(
				'required'  => true,
				'choices'   => array(0 => 'tots', UtilsController::INDEX_FINESTRETA => 'finestreta', UtilsController::INDEX_DOMICILIACIO => 'banc'),
				'data'		=> $queryparams['tipus']) )   
		->add('facturacio', 'entity', array(
				'error_bubbling'	=> true,
				'class' 	=> 'FomentGestioBundle:Facturacio',
				'query_builder' => function(EntityRepository $er) {
					return $er->createQueryBuilder('f')
					->orderBy('f.id', 'DESC');
				},
				'property' 	=> 'descripcio',
				'multiple' 	=> false,
				'required'  => false,
				'empty_data'=> null,
				'data' 		=> $this->getDoctrine()->getRepository('FomentGestioBundle:Facturacio')->find($queryparams['facturacio'])
		))
		->add('recarrec', 'number', array() ) // Recàrrec retornats
		->getForm();
		
		return $this->render('FomentGestioBundle:Rebuts:cercarebuts.html.twig', array('form' => $form->createView(), 'rebuts' => $rebuts, 'queryparams' => $queryparams));
	}
	
	
	protected function queryRebuts(Request $request) {
		// Opcions de filtre del formulari
		$sort = $request->query->get('sort', 'r.id'); // default 'r.id desc'
		$direction = $request->query->get('direction', 'desc');
		
		$nini = $request->query->get('nini', 0);
		$nfi = $request->query->get('nfi', 0);
	
		$persona = $request->query->get('persona', '');
		
		$dini = $request->query->get('dini', '');
		$dfi = $request->query->get('dfi', '');

		$cobrats = $request->query->get('selectorcobrats', 0);
		$tipus = $request->query->get('selectortipuspagament', 0);
		
		$facturacio = $request->query->get('facturacio', 0);
		$page = $request->query->get('page', 1);
		
		
		$anulats = false;
		if ($request->query->has('anulats') && $request->query->get('anulats') == 1) $anulats = true;
		$retornats = false;
		if ($request->query->has('retornats') && $request->query->get('retornats') == 1) $retornats = true;
		
		$queryparams = array('sort' => $sort,'direction' => $direction, 'page' => $page,   
				'anulats' => $anulats, 'retornats' =>  $retornats, 'cobrats' => $cobrats, 'tipus' => $tipus,
				'persona' => $persona, 'facturacio' => $facturacio
		);
		
		if ($nini > 0)  $queryparams['nini'] = $nini;
		if ($nfi > 0)  $queryparams['nfi'] = $nfi;
		if ($dini != '')  $queryparams['dini'] = $dini;
		if ($dfi != '')  $queryparams['dfi'] = $dfi;
		
		
		$seccionsIds = $request->query->get('seccions', array());
		$seccions = array();
		foreach ($seccionsIds as $i => $id) {  // Retrive selected seccions
			$seccions[] = $this->getDoctrine()->getRepository('FomentGestioBundle:Seccio')->find($id);
			$queryparams['seccions['.$i.']'] = $id;
		}
		$queryparams['seccions'] = $seccions;
		
		$activitatsIds = $request->query->get('activitats', array());
		foreach ($activitatsIds as $i => $id) {  // Retrive selected seccions
			$queryparams['activitats['.$i.']'] = $id;
		}
		$queryparams['activitats'] = $activitatsIds;
		
		
		
		/* Query */
		$qParams = array();
		
		$em = $this->getDoctrine()->getManager();
		 
		$strQuery = " SELECT r FROM Foment\GestioBundle\Entity\Rebut r ";
		
		if ($persona != '') {
			$strQuery .= "  JOIN r.detalls d LEFT JOIN d.quotaseccio m LEFT JOIN d.activitat a ";
			$strQuery .= " WHERE (m.soci = :persona OR a.persona = :persona) ";
			
			$qParams['persona'] = $persona;
			if (count($seccions) > 0) { // Seccions filtrades 
				$strQuery .= " AND d.quotaseccio IN (:seccions) ";
				$qParams['seccions'] = $seccionsIds;
			}
			if (count($activitatsIds) > 0) {
				$strQuery .= " AND d.activitat IN (:activitats) ";
				$qParams['activitats'] = $activitatsIds;
			}
			
		} else {
			if (count($seccions) > 0 || count($activitatsIds) > 0) { // Seccions o activitats filtrades, cal fer JOIN
				$strQuery .= "  JOIN r.detalls d ";
				
				if (count($seccions) > 0) $strQuery .= " LEFT JOIN d.quotaseccio m ";
			
				if (count($activitatsIds) > 0) $strQuery .= " LEFT JOIN d.activitat a ";
			} 
			$strQuery .= "  WHERE 1 = 1 ";
			
			if (count($seccions) > 0) {
				$strQuery .= " AND m.seccio IN (:seccions) ";
				$qParams['seccions'] = $seccionsIds;
			}
			if (count($activitatsIds) > 0) {
				$strQuery .= " AND a.activitat IN (:activitats) ";
				$qParams['activitats'] = $activitatsIds;
			}
			
		}
		
		if ($nini > 0) {
			if ($nfi > 0)  {
				$strQuery .= " AND r.num BETWEEN :nini AND :nfi ";
				$qParams['nini'] = $nini;
				$qParams['nfi'] = $nfi;
			} else {
				$strQuery .= " AND r.num = :nini ";
				$qParams['nini'] = $nini;
			}
		}
		
		if ($cobrats > 0) {
			if ($cobrats == 1) $strQuery .= " AND r.datapagament IS NOT NULL ";
			if ($cobrats == 2) $strQuery .= " AND r.datapagament IS NULL ";
		}
		
		if ($tipus > 0) {
			$strQuery .= " AND r.tipuspagament = :tipus ";
			$qParams['tipus'] = $tipus;
		}

		if ($anulats) $strQuery .= " AND r.databaixa IS NOT NULL ";
		if ($retornats) $strQuery .= " AND r.dataretornat IS NOT NULL ";
		
		if ($dini != '' || $dfi != '') {
			// Alguna data indicada
			if ($dini != '') {
				$diniISO = \DateTime::createFromFormat('d/m/Y', $dini);
				$strQuery .= " AND r.dataemissio >= :dini ";
				$qParams['dini'] = $diniISO->format('Y-m-d');
			}
		
			if ($dfi != '') {
				$dfiISO = \DateTime::createFromFormat('d/m/Y', $dfi);
		
				$strQuery .= " AND r.dataemissio <= :dfi ";
				$qParams['dfi'] = $dfiISO->format('Y-m-d');
			}
		}
		
		if ($facturacio > 0) {
			$strQuery .= " AND r.facturacio = :facturacio ";
			$qParams['facturacio'] = $facturacio;
		}
		
		$strQuery .= " ORDER BY " . $sort . " " . $direction;
		 
		$query = $em->createQuery($strQuery);
		 
		foreach ($qParams as $k => $p) {  // Add query parameters
			$query->setParameter($k, $p);
		}
		 
		$queryparams['query'] = $query;
		
		return $queryparams;
	}
	
	public function anularrebutAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}

		$em = $this->getDoctrine()->getManager();
		
		$ids = $request->query->get('id', array());
		
		if (!is_array($ids)) $ids = array ( $ids );
		
		foreach ($ids as $idrebut) {
			try {		
				$rebut = $em->getRepository('FomentGestioBundle:Rebut')->find( $idrebut );
				
				if ($rebut == null) throw new \Exception('No s\'ha trobat el rebut '.$idrebut);
					
				if (!$rebut->esEsborrable()) throw new \Exception('El rebut '.$rebut->getNumFormat(). ' no es pot anul·lar');
	
				$rebut->baixa();
				
				$em->flush();
						
				$this->get('session')->getFlashBag()->add('notice',	'Rebut anul·lat correctament');
			
			} catch (\Exception $e) {
				$this->get('session')->getFlashBag()->add('error', $e->getMessage());
			}
		}
		
		$request->query->remove('id');
		return $this->redirect($this->generateUrl('foment_gestio_rebuts', $request->query->all()));
	}
	
	public function anulardetallAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		
		$id = $request->query->get('id', array());
		
		try {
			$rebutdetall = $em->getRepository('FomentGestioBundle:RebutDetall')->find( $id );
		
			if ($rebutdetall == null) throw new \Exception('No s\'ha trobat el concepte '.$id);
				
			if (!$rebutdetall->esEsborrable()) throw new \Exception('No es pot esborrar el concepte del rebut '.$rebutdetall->getRebut()->getNumFormat());
		
			$rebutdetall->baixa();
		
			$em->flush();
		
			$this->get('session')->getFlashBag()->add('notice',	'Concepte anul·lat correctament');
				
		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error', $e->getMessage());
		}
		
		$request->query->remove('id');
		return $this->redirect($this->generateUrl('foment_gestio_rebuts', $request->query->all()));
	}
	
	public function cobrarrebutAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		
		$ids = $request->query->get('id', array());
		
		if (!is_array($ids)) $ids = array ( $ids );
		
		foreach ($ids as $idrebut) {
			try {
				$rebut = $em->getRepository('FomentGestioBundle:Rebut')->find( $idrebut );
		
				if ($rebut == null) throw new \Exception('No s\'ha trobat el rebut '.$idrebut);
					
				if ($rebut->cobrat()) throw new \Exception('El rebut '.$rebut->getNumFormat(). ' ja estava cobrat');
		
				// Si el rebut ja ha estat retornat, estarà marcat com tipus 3 => finestreta retornat
				/*$tipus = $request->query->get('tipus', UtilsController::INDEX_FINESTRETA);
				
				if ($tipus != UtilsController::INDEX_FINESTRETA && $tipus != UtilsController::INDEX_DOMICILIACIO) 
					throw new \Exception('La forma de pagament indicada és incorrecte');
				*/
				// Crear finestreta
				
				$rebut->setDatapagament(new \DateTime());
				//$rebut->setTipuspagament($tipus);
				$rebut->setDatamodificacio(new \DateTime());
		
				//if ($tipus == UtilsController::INDEX_FINESTRETA && $rebut->enDomiciliacio()) $rebut->setDataretornat(new \DateTime());
				
				$em->flush();
		
				$this->get('session')->getFlashBag()->add('notice',	'Rebut cobrat correctament');
					
			} catch (\Exception $e) {
				$this->get('session')->getFlashBag()->add('error', $e->getMessage());
			}
		}
		
		$request->query->remove('id');
		return $this->redirect($this->generateUrl('foment_gestio_rebuts', $request->query->all()));
		
		//return $this->forward('FomentGestioBundle:Rebuts:gestiorebuts');
		
	}
	
	

	public function retornarrebutAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
	
		$ids = $request->query->get('id', array());
	
		if (!is_array($ids)) $ids = array ( $ids );
	
		foreach ($ids as $idrebut) {
			try {
				$rebut = $em->getRepository('FomentGestioBundle:Rebut')->find( $idrebut );
	
				if ($rebut == null) throw new \Exception('No s\'ha trobat el rebut '.$idrebut);
					
				if (!$rebut->enDomiciliacio()) throw new \Exception('El rebut '.$rebut->getNumFormat(). ' no es pot retornar');
	
				$rebut->setTipuspagament(UtilsController::INDEX_FINES_RETORNAT);
				$rebut->setDataretornat(new \DateTime());
				$rebut->setDatamodificacio(new \DateTime());
				
				// Moure a finestreta
				
				$em->flush();
	
				$this->get('session')->getFlashBag()->add('notice',	'Rebut retornat correctament');
					
			} catch (\Exception $e) {
				$this->get('session')->getFlashBag()->add('error', $e->getMessage());
			}
		}
	
		$request->query->remove('id');
		return $this->redirect($this->generateUrl('foment_gestio_rebuts', $request->query->all()));
	}
	
	/* AJAX. Veure informació i gestionar caixa seccions */
	public function gestiocaixaseccionsAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		
		$current = $request->query->get('current', date('Y'));
		$semestre = $request->query->get('semestre', 0); // els 2 per defecte
		
		$seccioid = $request->query->get('seccio', 1); // Per defecte foment
		
		$selectedPeriodes = null;
		if ($semestre == 0) {
			$selectedPeriodes = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $current), array('semestre' => 'ASC'));
		} else {
			$selectedPeriodes = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $current, 'semestre' => $semestre), array('semestre' => 'ASC'));
		}
		
		// Obtenir les seccions actives
		//$seccions = $em->getRepository('FomentGestioBundle:Seccio')->findBy(array( 'databaixa' => null ));
		$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($seccioid);
		$seccionsmembresperiodes = array();
		// Crear array seccions per Id
		$strPeriodes = array();
		foreach ($selectedPeriodes as $periode) {
			$strPeriodes[] = $periode->getTitol();
		}

		// Llista de les seccions per crar el menú que permet carregar les dades de cadascuna
		$listSeccions = $em->getRepository('FomentGestioBundle:Seccio')->findBy(array( 'databaixa' => null )); 
		
		if(($key = array_search($seccio, $listSeccions)) !== false) {
		    unset($listSeccions[$key]); // Treure la secció activa de la llista
		}
		
		$errors = array();
		
		if ($seccio != null) {
		
		
		//foreach ($seccions as $seccio) {
			if ( !$seccio->checkQuotesAny($current) ) $strPeriodes = array('(Secció sense quotes l\'any '.$current.')'); 
			$seccionsmembresperiodes[$seccio->getId()] = array('nom' => $seccio->getNom(), 'subtitol' => implode(", ", $strPeriodes), 
					'quotatotal' => 0, 'quotacobrada' => 0, 'quotapendent' => 0, 'membres' => array());
		//}
			if (count($selectedPeriodes) == 0) {
				$this->get('session')->getFlashBag()->add('inner-notice', 'Aquestes dades encara no estan disponibles');
			}
			
			foreach ($selectedPeriodes as $periode) {
			
				// Obtenir els socis actius en el periode ordenats per soci rebut, num compte i seccio
				$membres = $this->queryGetMembresActiusPeriodeSeccio($periode->getDatainici(), $periode->getDatafinal(), $seccio);
				$periodeTitol = $periode->getTitol();
				
				foreach ($membres as $membre) {
					
					try {
						if ($membre->getSoci() == null) throw new \Exception('Dades incorrectes d\'un soci, identificador de membre '.$membre->getId());
						//if ($membre->getSeccio() == null) throw new \Exception('Dades incorrectes d\'una secció, identificador de membre '.$membre->getId());
						
						$sociId = $membre->getSoci()->getId();
						$seccioId = $membre->getSeccio()->getId();
						
						//if (!isset($seccionsmembresperiodes[$seccioId])) throw new \Exception('El soci '.$sociId.' està a una secció desconeguda: '.$seccioId);
	
						$rebutDetall = $membre->getRebutDetallPeriode($periode);
						
						$quota = UtilsController::quotaMembreSeccioPeriode($membre, $periode);
						
						$importDetall = ($rebutDetall == null)?"":$rebutDetall->getImport();
						// Possibles errors en imports
						$estat = "";
						if ($rebutDetall != null) {
							if (abs($importDetall-$quota) < 0.01) $estat = UtilsController::getEstats($rebutDetall->getRebut()->getEstat());
							else $estat = "Possible error, revisar ";
						}
						
						$dadesMembrePeriode = array(
							'titol' => $periodeTitol,
							'import' => $quota,
							'tipuspagament' => ($rebutDetall == null)?"":UtilsController::getTipusPagament($rebutDetall->getRebut()->getTipuspagament()),
							'rebut'	=> ($rebutDetall == null)?"--":$rebutDetall->getRebut()->getNumFormat(),
							'rebutnum'	=> ($rebutDetall == null)?0:$rebutDetall->getRebut()->getNum(),
							'detall'	=> ($rebutDetall == null)?"":$rebutDetall->getNumdetall(),
							'emissio' =>  ($rebutDetall == null)?"":$rebutDetall->getRebut()->getDataemissio(), 
							'importdetall' => $importDetall, // Import del rebut
							'concepte' => ($rebutDetall == null)?"":$rebutDetall->getConcepte(),
							'estat' => 	$estat 
						);
						
						// Dades per al soci a la secció existents
						$seccionsmembresperiodes[$seccioId]['quotatotal'] += $quota;
						if ($rebutDetall != null && $rebutDetall->getRebut()->cobrat()) $seccionsmembresperiodes[$seccioId]['quotacobrada'] += $quota;
						else $seccionsmembresperiodes[$seccioId]['quotapendent'] += $quota;
						
						$nom = $membre->getSoci()->getNomCognoms();
						$atributs = array();
						if ($membre->getSoci()->esJuvenil()) $atributs[] = 'juvenil';
						if ($membre->getSoci()->getSocirebut() != null && $membre->getSoci()->getSocirebut()->getDescomptefamilia()) $atributs[] = 'des. fam.';
						if (count($atributs) > 0) $nom .= ' <i>('.implode(', ', $atributs).')</i>'; 
						
						$seccionsmembresperiodes[$seccioId]['membres'][$sociId]['nom'] = $nom;
						
						$seccionsmembresperiodes[$seccioId]['membres'][$sociId]['periodes'][$periode->getId()] = $dadesMembrePeriode;
						
					} catch (\Exception $e) {
						$smsError = $e->getMessage();
						if (!in_array($smsError, $errors)) { 
							$errors[] = $smsError;
						}
					}
				}
			}
		
			foreach ($errors as $error) $this->get('session')->getFlashBag()->add('inner-error', $error);
		} else {
			$this->get('session')->getFlashBag()->add('inner-error', 'No s\'ha trobat dades de la secció ' .$seccioid  );
		}
		
		return $this->render('FomentGestioBundle:Rebuts:gestiocaixatabseccions.html.twig',
				array('current' => $current, 'semestre' => $semestre, 'periodes' => $selectedPeriodes,
				'dades' => $seccionsmembresperiodes, 'listseccions' => $listSeccions));
		
	}

	
	/* AJAX. Veure informació i gestionar caixa activitats */
	public function gestiocaixaactivitatsAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		
		$current = $request->query->get('current', date('Y'));
		$semestre = $request->query->get('semestre', 0); // els 2 per defecte
		
		$selectedPeriodes = null;
		if ($semestre == 0) $selectedPeriodes = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $current));
		else $selectedPeriodes = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $current, 'semestre' => $semestre));
		
		return $this->render('FomentGestioBundle:Rebuts:gestiocaixatabactivitats.html.twig',
				array('current' => $current, 'semestre' => $semestre, 'periodes' => $selectedPeriodes));
		
	}
	
	/* AJAX. Veure informació i gestionar caixa periodes. Rebuts generals */
	public function gestiocaixageneralAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
			
		$current = $request->query->get('current', date('Y'));
		$semestre = $request->query->get('semestre', 0); // els 2 per defecte
		
		if ($request->getMethod() == 'POST') {
			
			/*$data = $request->request->get('form');
			if (isset($data['selectoranys'])) $current = $data['selectoranys'];
				
			if (isset($data['selectorsemestre'])) $semestre = $data['selectorsemestre'];
	
			//echo $current;
			// Comprovar que no existeixin periodes per aquest any i semestre
	
			if ($semestre == 0 ) $arraySemestres = array (1, 2);
			else $arraySemestres = array ( $semestre );
	
			foreach ($arraySemestres as $s) {
				$periode_existeix = $em->getRepository('FomentGestioBundle:Periode')->findOneBy( array('anyperiode' => $current, 'semestre' => $s));
	
				if ($periode_existeix == null) {
					// Crear el periode
					$periode = new Periode($current, $s);
					$em->persist($periode);
	
					$this->get('session')->getFlashBag()->add('notice',	'El semestre ' . $s . ' de l\'any ' . $current . ' s\'a creat correctament');
				} else {
					$this->get('session')->getFlashBag()->add('error',	'El semestre ' . $s . ' de l\'any ' . $current . ' ja existeix');
				}
			}
	
			$em->flush();
	
			// Prevent posting again F5
			return $this->redirect($this->generateUrl('foment_gestio_caixa'));*/
			$this->get('session')->getFlashBag()->add('inner-error',	'Opció incorrecte');
		} else {
				
			$accio = $request->query->get('action', '');
			switch ($accio) {
				case '':
				case 'query':  // Consultar dades periode : any / semestre
						
					$current = $request->query->get('current', $current);
					$semestre = $request->query->get('semestre', $semestre);
						
					break;
	
				case 'create':  // Crear periode/s i rebuts pendents
					$current = $request->query->get('current', $current);
					$semestre = $request->query->get('semestre', $semestre);
	
					try {
	
						if ($semestre == 0 || $semestre == 1)  {
							$this->crearPeriodeFacturacio($current, 1);
						}
						if ($semestre == 0 || $semestre == 2)  {
							$this->crearPeriodeFacturacio($current, 2);
						}
	
						$this->get('session')->getFlashBag()->add('inner-notice',	'Rebuts afegits correctament');
					} catch (\Exception $e) {
						$this->get('session')->getFlashBag()->add('inner-error',	$e->getMessage());
					}
	
					// Prevent posting again F5
					//return $this->redirect($this->generateUrl('foment_gestio_caixa', array('current' => $current, 'semestre' => $semestre)));
						
					break;
				case 'remove':  // Esborrar periode
					$periodeid = $request->query->get('periode', 0);
						
					try {
						$this->esborrarPeriodeFacturacio($periodeid);
						$this->get('session')->getFlashBag()->add('inner-notice',	'Rebuts esborrats correctament');
					} catch (\Exception $e) {
						$this->get('session')->getFlashBag()->add('inner-error',	$e->getMessage());
					}
						
					//return $this->redirect($this->generateUrl('foment_gestio_caixa', array('current' => $current, 'semestre' => $semestre)));
					break;
					
				case 'facturar':  // Esborrar periode
					
					/*
					 * Facturar tots els rebuts pendents que cal domiciliar del periode fins al moment
					 * Crear una facturació (grup de rebuts) per enviar al banc i fer-ne el seguiment
					 * Crear o afegir els rebuts de finestreta a la facturació corresponent, només una
					 */
					$periodeid = $request->query->get('periode', 0);
					
					try {
						$num = $this->facturarRebuts($periodeid);
						$this->get('session')->getFlashBag()->add('inner-notice',	'Els rebuts pendents s\'han afegit a la facturació '.$num.' correctament');
						
					} catch (\Exception $e) {
						$this->get('session')->getFlashBag()->add('inner-error', $e->getMessage());
					}
					break;
				default:  // Altres
						
					$this->get('session')->getFlashBag()->add('inner-error',	'Acció incorrecte');
					break;
			}
		}
	
	
		$selectedPeriodes = null;
		if ($semestre == 0) $selectedPeriodes = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $current));
		else $selectedPeriodes = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $current, 'semestre' => $semestre));
	
		return $this->render('FomentGestioBundle:Rebuts:gestiocaixatabgeneral.html.twig',
				array('current' => $current, 'semestre' => $semestre, 'periodes' => $selectedPeriodes));
	}
	
	/* Veure informació i gestionar caixa periodes. Rebuts generals */
	public function gestiocaixaAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		 
		$current = $request->query->get('current', date('Y'));
		$semestre = $request->query->get('semestre', 0); // els 2 per defecte
		
		$selectedPeriodes = null;
		if ($semestre == 0) $selectedPeriodes = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $current));
		else $selectedPeriodes = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $current, 'semestre' => $semestre));
		
		$anysSelectable = $this->getAnysSelectable();
		
		$form = $this->createFormBuilder()
		->add('selectoranys', 'choice', array(
				'required'  => true,
				'choices'   => $anysSelectable,
				'data'		=> $current
		))->add('selectorsemestre', 'choice', array(
				'required'  => true,
				'choices'   => array('0' => 'Tots els semestres', '1' => '1er semestre', '2' => '2n semestre'),
				'data'		=> $semestre
		))->getForm();
		
		if (count($selectedPeriodes) <= 0) {
			$this->get('session')->getFlashBag()->add('inner-notice',	'Aquestes dades encara no estan disponibles');
		}
		
		return $this->render('FomentGestioBundle:Rebuts:gestiocaixa.html.twig',
				array('form' => $form->createView(), 'periodes' => $selectedPeriodes));
    }
	
    
    private function crearPeriodeFacturacio($anyperiode, $semestre)
    {
    	$em = $this->getDoctrine()->getManager();
    	
    	$periode = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $anyperiode, 'semestre' => $semestre));
    	
    	if ($periode != null) throw new \Exception('Existeixen dades per aquest semestre de l\'any');
    	
    	$periode = new Periode($anyperiode, $semestre);
    	
    	$em->persist($periode);
    	
    	// Crear rebuts seccions del periode (emesos entre inici i final)
    	$this->generarRebutsSeccionsSemestre($periode);
    	
    	$em->flush();
    }
    
    private function esborrarPeriodeFacturacio($periodeid)
    {
    	$em = $this->getDoctrine()->getManager();
    		
    	$periode = $em->getRepository('FomentGestioBundle:Periode')->find($periodeid);
    		
    	if ($periode == null) throw new \Exception('No s\'ha trobat les dades');
    	
    	if (!$periode->esborrable())  throw new \Exception('Aquestes dades no es poden esborrar, hi ha rebuts facturats');
    	
    	// Esborrar rebuts pendents del periode (emesos entre inici i final)
    	foreach ($periode->getRebutsnofacturats() as $rebut) {
    		foreach ($rebut->getDetalls() as $detall) {	
    			$em->remove($detall);
    		}
    		$em->remove($rebut);
    	}
    	
    	$em->remove($periode);
   		
   		
   		$em->flush();
    }
    
    /* Generar els rebuts d'un període (Any / Semestre) per als membres de les seccions
     * Els rebuts inicialment no estan associats a cap facturació  */
    private function generarRebutsSeccionsSemestre($periode)
    {
    	$em = $this->getDoctrine()->getManager();
    
    	if ($periode == null) new \Exception('Període incorrecte');
    
    	// Obtenir els socis actius en el periode ordenats per soci rebut, num compte i seccio
    	$membres = $this->queryGetMembresActiusPeriodeAgrupats($periode->getDatainici(), $periode->getDatafinal());
    
    	$numrebut = $this->getMaxRebutNumAny($periode->getAnyperiode()); // Max
    
    	$current = new \DateTime();
    	$dataemissio = $periode->getDatainici();  // Inici periode o posterior
    	if ($current > $periode->getDatainici()) $dataemissio = $current;
    	
    	$socipagarebut = null; // Soci agrupa rebuts per pagar
    	$rebut = null;
    	foreach ($membres as $membre) {
    		$currentsocipagarebut = $membre->getSoci()->getSocirebut();
    		
    		if ($currentsocipagarebut == null) throw new \Exception('Cal indicar qui es farà càrrec dels rebuts del soci '.$currentsocipagarebut->getNomCognoms().'' );
    		
    		if ($currentsocipagarebut != $socipagarebut  ) {
    			// Canvi pagador si el rebut té import 0 esborrar-lo
    			if ($rebut != null && $rebut->getImport() <= 0) {
    				$rebut->detach();
    				$em->detach($rebut);
    			}
    			
    			// Nou pagador, crear rebut i prepara nova agrupació
    			$numrebut++;
    			$socipagarebut = $currentsocipagarebut;
    
    			$rebut = new Rebut($socipagarebut, $dataemissio, $numrebut, true, $periode);
    
    			$em->persist($rebut);
    		}
   			$rebutdetall = $this->generarRebutDetallMembre($membre, $rebut, $periode);
    
   			if ($rebutdetall != null) $em->persist($rebutdetall);
    	}
    
    	// Últim rebut
    	if ($rebut != null && $rebut->getImport() <= 0) {
    		$rebut->detach();
    		$em->detach($rebut);
    	}
    	
    	
    	$periode->setEstat( 1 );
    	$periode->setDatarebuts( new \DateTime());
    	$periode->setDatamodificacio( new \DateTime());
    		
    	$em->flush();
    }
    
    
    public function facturarRebuts($periodeid)
    {
    	$em = $this->getDoctrine()->getManager();
    	
    	$periode = $em->getRepository('FomentGestioBundle:Periode')->find( $periodeid );
    	
   		if ($periode == null) throw new \Exception('No s\'ha trobat el periode '.$periodeid);
    	
    	if ($periode->pendents() == false) throw new \Exception('No hi ha rebuts pendents de facturar');
    		
    	$num = $this->getMaxFacturacio();
			
		$facturacio = new Facturacio($num, $periode, UtilsController::INDEX_DOMICILIACIO);
		$periode->facturarPendents($facturacio);
						
		$em->persist($facturacio);
						
		$em->flush();
						
		return $num;
    }
    
    
    /* Revisar la morositat dels socis */
    public function morososAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'deute', 'direction' => 'desc'));
    
    	$query = $this->queryMorosos($queryparams);
    
    	// Paginator
    	$paginator  = $this->get('knp_paginator');
    	 
    	$morosos = $paginator->paginate(
    			$query,
    			$queryparams['page'],
    			$queryparams['perpage'] //limit per page
    	);
    	unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
    	$morosos->setParam('perpage', $queryparams['perpage']); // Add extra request params
    	 
    	// Formulari de filtre
    	$form = $this->createFormBuilder()
    		->add('filtre', 'text', array(
    			'required' => false,
    			'data'		=> $queryparams['filtre'],
    			'attr' => array('class' => 'form-control filtre-text')))
    			->add('midapagina', 'choice', array(
    					'required'  => true,
    					'choices'   => UtilsController::getPerPageOptions(),
    					'data'		=> $queryparams['perpage'],
    					'attr' 		=> array('class' => 'select-midapagina')
    			))->getForm();
    			 
    	if ($request->isXmlHttpRequest() == true) {
    		// Ajax call renders only table morosos
    		return $this->render('FomentGestioBundle:Rebuts:taulamorosos.html.twig',
    				array('form' => $form->createView(), 'morosos' => $morosos, 'total' => $this->queryTotalMorosos(),
    						'queryparams' => $queryparams));
    	}
    			
    	return $this->render('FomentGestioBundle:Rebuts:morosos.html.twig',
    			array('form' => $form->createView(), 'morosos' => $morosos, 'total' => $this->queryTotalMorosos(),
    					'queryparams' => $queryparams));
    }
           
    protected function queryMorosos($queryparams) {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = "SELECT p, SUM(d.import) AS deute, MIN(r.dataemissio) AS mindataemissio  FROM Foment\GestioBundle\Entity\Persona p ";
    	$strQuery .= " JOIN p.rebuts r JOIN r.detalls d ";
    	$strQuery .= " WHERE r.databaixa IS NULL AND d.databaixa IS NULL ";
    	$strQuery .= " AND r.datapagament IS NULL ";
    	    	
    	if ($queryparams['filtre'] != '') $strQuery .= " AND CONCAT(CONCAT(p.nom, ' '), p.cognoms) LIKE :filtre ";
    
    	$strQuery .= " GROUP BY p.id ORDER BY " . $queryparams['sort'] . " " . $queryparams['direction'];
    	
    	$query = $em->createQuery($strQuery);
    
    	if ($queryparams['filtre'] != '') $query->setParameter('filtre', '%'.$queryparams['filtre'].'%');
    
    	return $query;
    }
    
    protected function queryTotalMorosos() {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = "SELECT COUNT(DISTINCT p.id) FROM Foment\GestioBundle\Entity\Persona p ";
    	$strQuery .= " JOIN p.rebuts r JOIN r.detalls d ";
    	$strQuery .= " WHERE r.databaixa IS NULL AND d.databaixa IS NULL ";
    	$strQuery .= " AND r.datapagament IS NULL ";
    	//$strQuery .= " GROUP BY p.id ";
    	
    	$total = $em->createQuery($strQuery)->getSingleScalarResult();
    	
    	return $total;
    }
    
}
