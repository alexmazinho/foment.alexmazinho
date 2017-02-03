<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;

//use Doctrine\Common\Persistence\Registry;

use Foment\GestioBundle\Entity\Soci;
use Foment\GestioBundle\Entity\Persona;
use Foment\GestioBundle\Entity\Seccio;
use Foment\GestioBundle\Entity\Activitat;
use Foment\GestioBundle\Entity\Periode;
use Foment\GestioBundle\Form\FormPagament;
use Foment\GestioBundle\Form\FormRebut;
use Foment\GestioBundle\Entity\Rebut;
use Foment\GestioBundle\Entity\FacturacioSeccio;
use Foment\GestioBundle\Entity\Pagament;


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
	
		if (isset($queryparams['id']) and $queryparams['id'] != '')  $defaultData['id'] = $queryparams['id']; // Per id
		
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
		->add('id', 'hidden', array('required' => false))
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
				'choices'   => array(0 => 'tots', UtilsController::INDEX_FINESTRETA => 'finestreta', UtilsController::INDEX_DOMICILIACIO => 'banc', UtilsController::INDEX_FINES_RETORNAT => 'fines. retornat'),
				'data'		=> $queryparams['tipus'] ) )    
		->add('facturacio', 'entity', array(
				'error_bubbling'	=> true,
				'class' 	=> 'FomentGestioBundle:FacturacioSeccio',
				'query_builder' => function(EntityRepository $er) {
					return $er->createQueryBuilder('f')
					->where('f.databaixa IS NULL')
					->orderBy('f.id', 'DESC');
				},
				'property' 	=> 'descripcioCompleta',
				'multiple' 	=> false,
				'required'  => false,
				'empty_data'=> null,
				'data' 		=> $this->getDoctrine()->getRepository('FomentGestioBundle:Facturacio')->find($queryparams['facturacio'])
		))
		->getForm();
		
		return $this->render('FomentGestioBundle:Rebuts:cercarebuts.html.twig', array('form' => $form->createView(), 'rebuts' => $rebuts, 'queryparams' => $queryparams));
	}
	
	/* Crear rebuts pendents d'una facturació */
	public function crearrebutsAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		$em = $this->getDoctrine()->getManager();
		
		$id = $request->query->get('id', 0); // Curs
		
		$facturacio = $em->getRepository('FomentGestioBundle:Facturacio')->find($id);
		
		try {
		
			if ($facturacio == null) throw new \Exception('Facturació no trobada' );
			
			// Generar rebuts participants actius si escau (checkrebuts)
			$anyFactura = $facturacio->getDatafacturacio()->format('Y');
			$numrebut = $this->getMaxRebutNumAnyActivitat($anyFactura); // Max
			
			$activitat = $facturacio->getActivitat();
			
			if ($activitat == null) throw new \Exception('La facturació no és d\'un curs o taller' );
			
			foreach ($activitat->getParticipantsActius() as $participacio) {
				$rebut = $this->generarRebutActivitat($facturacio, $participacio, $numrebut);
				if ($rebut != null) $numrebut++;
						
			}
			$em->flush();
			
			$this->get('session')->getFlashBag()->add('notice',	'Rebuts creats correctament');
		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
		}
		
		if ($activitat != null ) return $this->redirect($this->generateUrl('foment_gestio_activitat', array( 'id' => $activitat->getId())));
		return $this->redirect($this->generateUrl('foment_gestio_activitats'));
	
	}
	
	
	public function anularrebutAction(Request $request)
	{
		try {
			if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
				throw new AccessDeniedException();
			}
	
			$em = $this->getDoctrine()->getManager();
			
			$ids = $request->query->get('id', array());
			
			if (!is_array($ids)) $ids = array ( $ids );
			
			foreach ($ids as $idrebut) {
					$rebut = $em->getRepository('FomentGestioBundle:Rebut')->find( $idrebut );
					
					if ($rebut == null) throw new \Exception('No s\'ha trobat el rebut '.$idrebut);
						
					if (!$rebut->esEsborrable()) throw new \Exception('El rebut '.$rebut->getNumFormat(). ' no es pot anul·lar');
		
					$rebut->baixa();
					
					$em->flush();
							
					$this->get('session')->getFlashBag()->add('notice',	'Rebut anul·lat correctament');
			}
			
			$response = new Response("Ok");
		} catch (\Exception $e) {
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
		}
		
		return $response;
	}
	
	public function anulardetallAction(Request $request)
	{
		try {
			if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
				throw new AccessDeniedException();
			}
		
			$em = $this->getDoctrine()->getManager();
			
			$id = $request->query->get('id', array());
		
		
			$rebutdetall = $em->getRepository('FomentGestioBundle:RebutDetall')->find( $id );
		
			if ($rebutdetall == null) throw new \Exception('No s\'ha trobat el concepte '.$id);
				
			if (!$rebutdetall->esEsborrable()) throw new \Exception('No es pot esborrar el concepte del rebut '.$rebutdetall->getRebut()->getNumFormat());
		
			$rebutdetall->baixa();

			if ($rebutdetall->getRebut()->esCorreccio() == true) 
				$rebutdetall->getRebut()->setImportcorreccio($rebutdetall->getRebut()->getImportcorreccio() - $rebutdetall->getImport());
					
			$em->flush();
		
			$this->get('session')->getFlashBag()->add('notice',	'Concepte anul·lat correctament');
		
			$response = new Response("Ok");
		} catch (\Exception $e) {
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
		}
			
		return $response;
	}
	
	public function cobrarrebutAction(Request $request)
	{
		try {
		
			if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
				throw new AccessDeniedException();
			}
		
			$em = $this->getDoctrine()->getManager();
			
			$ids = $request->query->get('id', array());
			
			if (!is_array($ids)) $ids = array ( $ids );
			
			foreach ($ids as $idrebut) {
				
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
						
			}
			$response = new Response("Ok");

		} catch (\Exception $e) {
			
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
		}
		
		return $response;
	}

	public function retornarrebutAction(Request $request)
	{
		try {
			if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
				throw new AccessDeniedException();
			}
		
			$em = $this->getDoctrine()->getManager();
		
			$serveis = $this->get('foment.serveis');
			
			$ids = $request->query->get('id', array());
		
			$recarrec = $request->query->get('recarrec', 0);
			
			if (!is_array($ids)) $ids = array ( $ids );
			
			foreach ($ids as $idrebut) {
				
					$rebut = $em->getRepository('FomentGestioBundle:Rebut')->find( $idrebut );
		
					if ($rebut == null) throw new \Exception('No s\'ha trobat el rebut '.$idrebut);
						
					if (!$rebut->enDomiciliacio()) throw new \Exception('El rebut '.$rebut->getNumFormat(). ' no es pot retornar');
					
					// Crear correcció
					$importcorreccio = $rebut->getImport() + $recarrec;
					
					//$nouconcepte = UtilsController::CONCEPTE_RECARREC_RETORNAT.' '.number_format($recarrec, 2, ',', '.');
					$nouconcepte = $serveis->getParametre('CONCEPTE_RECARREC_RETORNAT').' '.number_format($recarrec, 2, ',', '.').'€';
					
					$this->correccioRebut($rebut, $importcorreccio, $nouconcepte);
					
					$rebut->setTipuspagament(UtilsController::INDEX_FINES_RETORNAT);
					$rebut->setDataretornat(new \DateTime());
					$rebut->setDatapagament(null);
					$rebut->setDatamodificacio(new \DateTime());
					
					$em->flush();
		
					$this->get('session')->getFlashBag()->add('notice',	'Rebut retornat correctament');
						
			}
		
			$response = new Response("Ok");
		} catch (\Exception $e) {
			$response = new Response($e->getMessage());
			$response->setStatusCode(500);
		}
		return $response;
	}
	
	/* Veure informació i gestionar caixa periodes. Rebuts generals */
	public function infoseccionsAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		
		$current = $request->query->get('current', date('Y'));
		
		$facturacions = UtilsController::queryGetFacturacions($em, $current);  // Ordenades per data facturacio DESC
		
		$form = $this->formFacturacionsPage($request);
		
		$form->add('facturacions', 'entity', array(
				'error_bubbling'	=> true,
				'class' 	=> 'FomentGestioBundle:FacturacioSeccio',
				'property' 	=> 'descripcioCompleta',
				'multiple' 	=> false,
				'required'  => false,
				'empty_value' => 'Totes ...',
				'choices' 	=> $facturacions
		));
		
		return $this->render('FomentGestioBundle:Rebuts:infoseccions.html.twig',
				array('form' => $form->createView()));
	}
	
	/* AJAX. Veure informació seccions acumulats*/
	public function infoseccionscontentAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		
		$em = $this->getDoctrine()->getManager();
		
		$id = $request->query->get('facturacio', 0);
		
		$facturacio = $em->getRepository('FomentGestioBundle:FacturacioSeccio')->find($id);
		
		if ($facturacio != null) {
			$facturacions = array( $facturacio );
		} else {
		
			$current = $request->query->get('current', date('Y'));
			
			$facturacions = UtilsController::queryGetFacturacions($em, $current);  // Ordenades per data facturacio DESC
		}	
		$infoseccions = $this->infoSeccionsQuotes($facturacions);
	
		return $this->render('FomentGestioBundle:Rebuts:gestiofacturacionscontent.html.twig',
				array( 'link' => 'seccio', 'facturacions' => $infoseccions));
	}
	
	/* AJAX. Veure informació seccions no semestrals */
	public function infoaltrescontentAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		
		$current = $request->query->get('current', date('Y'));
		$seccioid = $request->query->get('seccio', 0); // Per defecte cap
	
		// Cercar informació periode
		$dataini = \DateTime::createFromFormat('Y-m-d H:i:s', $current."-01-01 00:00:00");
		$datafi = \DateTime::createFromFormat('Y-m-d H:i:s', $current."-12-31 23:59:59");
		
		// Llista de les seccions per crar el menú que permet carregar les dades de cadascuna
		$strQuery = "SELECT s FROM Foment\GestioBundle\Entity\Seccio s WHERE 
									s.semestral = 0 AND s.databaixa IS NULL AND s.dataentrada <= :datafi 
									ORDER BY s.nom ";
		
		$query = $em->createQuery($strQuery);
		
		$query->setParameter('datafi', $current."-12-31 23:59:59");
		
		$listSeccionsAltres = $query->getResult();
		
		// Obtenir la secció seleccionada
		$seccioMembres = array();
		$seccio = null;
		if ($seccioid > 0) {
				
			for( $i=0; $i<count($listSeccionsAltres) && $seccio == null; $i++ ) {
				if ($listSeccionsAltres[$i]->getId() == $seccioid) {
					$seccio = $listSeccionsAltres[$i];
				}
			}
				
			if ($seccio != null) {
				// Carregar dades membres seccio escollida
				$membres = $seccio->getMembresPeriode($dataini, $datafi);
		
				$rebutsPeriode = array();
				$mesos = array();	
				
				setlocale(LC_TIME, 'ca_ES', 'Catalan_Spain', 'Catalan');
				for( $mes=1; $mes <= 12; $mes++ ) {
					//$mesText =  $currentAnyMes->format('F \d\e Y');
					//$mesText = date("F \de Y", $currentAnyMes->format('U'));
					$mesText = utf8_encode(strftime("%B", strtotime(sprintf('%02s', $mes)."/01/".$current)));
					//$mesText = date('F',strtotime('01/'.$mes.'/'.$current));
					$rebutsPeriode[$mes] = array('rebuts' => array());
					$mesos[$mes] = array('nommes' => $mesText, 'total' => 0, 'cobrats' => 0, 'pendents' => 0);
				}
				
				$seccioMembres[$seccioid] = array(
						'nom' 			=> $seccio->getNom().'. '.$current,
						'subtitol' 		=> 'Seccions no semestrals',
						'importrebuts' 	=> 0, 'importcobrats' => 0, 'importpendents' => 0,
						'mesostext' 	=> $mesos,
						'facturacions'	=> $seccio->getFacturacions(),
						//'facturacionsTotals' =>	$facturacionsTotalsArray,
						//'participantsactius' => $activitat->getTotalParticipants(), 
						'totalmembres' 	=> count($membres),
						'detallmembres' => array()
				);
		
				foreach ($membres as $index => $membre) { 
					$soci = $membre->getSoci();
					
					//$rebutsMes = clone $rebutsPeriode;
					$rebutsMes = new \ArrayObject($rebutsPeriode);
				
					// create a copy of the array
					$rebutsMes = $rebutsMes->getArrayCopy();
					
					$detalls = $membre->getDetallsrebuts();
					foreach ($detalls as $detall) {
						$rebut = $detall->getRebut();
						if ($rebut != null && $rebut->getDataemissio() != null) {
							$mes = $rebut->getDataemissio()->format('n');
							
							$rebutsMes[$mes]['rebuts'][] = $rebut;
							
							$seccioMembres[$seccioid]['importrebuts'] += $rebut->getImport();
							$seccioMembres[$seccioid]['mesostext'][$mes]['total'] += $rebut->getImport();
							if ($rebut->cobrat()) {
								$seccioMembres[$seccioid]['importcobrats'] += $rebut->getImport();
								$seccioMembres[$seccioid]['mesostext'][$mes]['cobrats'] += $rebut->getImport();
							}
							else {
								if (!$rebut->anulat()) {
									$seccioMembres[$seccioid]['importpendents'] += $rebut->getImport();
									$seccioMembres[$seccioid]['mesostext'][$mes]['pendents'] += $rebut->getImport();
								}
							}
						}
					}
					
					$seccioMembres[$seccioid]['detallmembres'][$soci->getId()] = array(
							'index' => $index + 1,
							'nom' => $soci->getNumSoci() .' '. $soci->getNomCognoms().'('.$soci->estatAmpliat().')' ,
							'contacte' => $soci->getContacte(),
							'quota'	=> $membre->getQuotaAny($current),
							//'cancelat' => ($participant->getDatacancelacio() != null),
							'rebutsperiode' => $rebutsMes
							//'facturacions' => $facturacionsInitArray
					);
				}
			} else {
				$this->get('session')->getFlashBag()->add('error', 'No s\'ha trobat dades de la secció ' .$seccioid  );
			}
		
		}
		
		return $this->render('FomentGestioBundle:Rebuts:infoaltrescontent.html.twig',
				array('current' => $current, 'currentseccio' => $seccioid, 'listseccions' => $listSeccionsAltres, 'dades' => $seccioMembres));
	}
	
	/* AJAX. Veure informació i gestionar caixa activitats */
	public function infoactivitatAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		
		$current = $request->query->get('current', date('Y'));

		$activitatid = $request->query->get('id', 0); // Per defecte cap

		try {
			$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($activitatid);
		
			// Obtenir l'activitat seleccionada
			$activitatParticipants = array();
			if ($activitat == null) throw new \Exception('No s\'ha trobat dades del curs o taller ' .$activitatid);
			
			// Carregar dades participants activitat escollida
			$facturacionsActives =	$activitat->getFacturacionsSortedByDatafacturacio();
				
			$facturacionsTotalsArray = array();
			$facturacionsInitArray = array();
			foreach ($facturacionsActives as $facturacio) { // Només les actives, les altres no haurien de tenir rebuts vàlids
				$facturacionsTotalsArray[$facturacio->getId()] = array( 'id' => $facturacio->getId(),		// Info fact. capçalera
																		'titol' => substr($facturacio->getDescripcio(), 0, 20).'...',	
																		'preu' => $facturacio->getImportactivitat(),
																		'preunosoci' => $facturacio->getImportactivitat(),
																		'data' => $facturacio->getDatafacturacio(),
																		'totalrebuts' => 0,
																		'totalpendent' => 0,
																		'totalfacturaciocurs' => 0
				);
				$facturacionsInitArray[$facturacio->getId()] = array( 	'rebut' => '' );  // Info participant sense rebut
			}
				
			/*
			index nom contacte importtotal 	( facturacio data preu 		)  ( facturacio data preu 		)  	...
										 	 rebut import emissio estat	 rebut import emissio estat		...
			*/
			
			$activitatParticipants[$activitatid] = array('descripcio' => $activitat->getDescripcio(), 
					'facturaciorebuts' => 0, 'facturaciocobrada' => 0, 'facturaciopendent' => 0, 
					'facturacionsTotals' =>	$facturacionsTotalsArray, 
					'participantsactius' => $activitat->getTotalParticipants(), 'participants' => array(),
					'pagaments' => array()
			);				
				
			foreach ($activitat->getParticipantsSortedByCognom(true) as $index => $participant) {  // Tots inclús si han cancel·lat participació
				$persona = $participant->getPersona();
					
				$activitatParticipants[$activitatid]['participants'][$persona->getId()] = array(
						'index' => $index + 1, 
						'persona' => $persona,
						'soci'	=> $persona->esSociVigent(),
						'nom' => $persona->getNumSoci() .' '. $persona->getNomCognoms().'('.$persona->estatAmpliat().')' ,
						'contacte' => $persona->getContacte(),
						'preu'	=> 0,
						'cancelat' => ($participant->getDatacancelacio() != null), 	
						'facturacions' => $facturacionsInitArray
				);
			}
				
			foreach ($facturacionsActives as $facturacio) {  // Només les actives, les altres no haurien de tenir rebuts vàlids
					
				foreach ($facturacio->getRebuts() as $rebut) {
					$personaId = $rebut->getDeutor()->getId();
					$import = $rebut->getImport();
							
					$dadesParticipantFacturacio = array( 'rebut' => $rebut 	);
							
					if (!isset($activitatParticipants[$activitatid]['participants'][$personaId]['facturacions'][$facturacio->getId()])) 
								throw new \Exception('Informació de la facturació "'.$facturacio->getDescripcio().'" desconeguda per a '.$rebut->getDeutor()->getNomCognoms());

					$activitatParticipants[$activitatid]['participants'][$personaId]['facturacions'][$facturacio->getId()] = $dadesParticipantFacturacio;
						
					// Acumular rebuts
					if (!$rebut->anulat()) {
						$activitatParticipants[$activitatid]['facturaciorebuts'] += $import;  // No anulats
						$activitatParticipants[$activitatid]['participants'][$personaId]['preu'] += $import; // Anulat no comptabilitza
								
						if ($rebut->cobrat()) {
							$activitatParticipants[$activitatid]['facturaciocobrada'] += $import;  // Cobrats
							$activitatParticipants[$activitatid]['facturacionsTotals'][$facturacio->getId()]['totalrebuts'] += $import;
							$activitatParticipants[$activitatid]['facturacionsTotals'][$facturacio->getId()]['totalfacturaciocurs'] += $import;
						}
						else  {
							$activitatParticipants[$activitatid]['facturaciopendent'] += $import;  // Pendents
							$activitatParticipants[$activitatid]['facturacionsTotals'][$facturacio->getId()]['totalpendent'] += $import;
						}
						
					}
				}					
			}
				
			
			// Professors
			if (count($facturacionsActives) > 0) {




// =================================================>  VERSIO OLD 2				
				
/*				$df = new \IntlDateFormatter('ca_ES', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, 'Europe/Madrid', \IntlDateFormatter::GREGORIAN, "MMMM 'de' yyyy");
				
				$pagamentsActivitat = array();
				
				$mesosPagaments = array();
				$docencies = array();
				foreach ($facturacionsActives as $facturacio) {
					
					$mesosPagaments = array_merge($mesosPagaments, $facturacio->getMesosPagaments());
					
					$docencies = array_merge($docencies, $facturacio->getDocenciesOrdenades());
				}
				
				foreach ($mesosPagaments as $mes) {
					$anyMes = sprintf('%s-%02s', $mes['any'], $mes['mes']);
						
					$currentAnyMes = \DateTime::createFromFormat('Y-m-d', $anyMes.'-01');
						
					$mesText = $df->format($currentAnyMes->format('U'));
					
					foreach ($docencies as $c => $docencia) {
						$docent = $docencia->getProveidor();
						
						if ($c > 0) $mesText = '';
						
						//$mesText .= ' <span class="nom-professor">'.$docent->getRaosocial().'</span>';
						
						
						if ( !isset($pagamentsActivitat[$anyMes][$docent->getId()] ) ) {
								
							$pagamentsActivitat[$anyMes][$docent->getId()] = array(
									'anymespagament' => $mesText,
									'raosocial' => $docent->getRaosocial(),
									'datapagament' => urlencode($currentAnyMes->format('t/m/Y')),  // 't' => últim dia del mes,
									'totalsessions' => 0,
									'facturacions' => array()
							);
						}
						
						foreach ($facturacionsActives as $facturacio) {
							
							if ($facturacio->getId() != $docencia->getFacturacio()->getId()) {
								// La docència pertany a la facturació actual
								$pagamentsActivitat[$anyMes][$docent->getId()]['facturacions'][] = array(
										'concepte' => '',
										'import' => 0,
										'sessions' => 0,
										'liquidacions' => array(),
								);
							} else {
								$sessions = $docencia->getSessionsMensual($mes['any'], $mes['mes']);
								$import = $docencia->getImportMensual($mes['any'], $mes['mes']);
								
								$pagamentsActivitat[$anyMes][$docent->getId()]['facturacions'][] = array(
										'concepte' => 'Liquidació '.$docent->getRaosocial().' '.$currentAnyMes->format('m/Y').' '.$facturacio->getDescripcio(),
										'import' => $import,
										'sessions' => $sessions,
										'liquidacions' => $docencia->getPagamentsMesAny($mes['any'], $mes['mes']),
								);
								
								$pagamentsActivitat[$anyMes][$docent->getId()]['totalsessions'] += $sessions;
							}
						}
							
					}
					
				}
*/				
				
				
				
				
				
// =================================================>  VERSIO OLD				
				
				/*$mesosFacturacions = array(); // Han d'estar entre l'inici i el final del curs
				$mesosPagaments = array();
				$graellaPagamentMesFacturacions = array();
				$totalsDocencia = array();
				$arrDocents = array();
				$docencies = array();
				
				foreach ($facturacionsActives as $facturacio) {
					$mesosPagaments = array_merge($mesosPagaments, $facturacio->getMesosPagaments());
						
					$mesosFacturacions[] = array(
						'facturacio'	=> $facturacio->getId(),
						'anyfacturacio' => $facturacio->getDatafacturacio()->format('Y'),
						'mesfacturacio' => $facturacio->getDatafacturacio()->format('m')
									
					);
					
					$graellaPagamentMesFacturacions[$facturacio->getId()] = false;// Cada més té una graella com aquesta per cada facturació
					$totalsDocencia[$facturacio->getId()] = 0;
					
					$docencies = array_merge($docencies, $facturacio->getDocenciesOrdenades());
				}

				foreach ($docencies as $docencia) {
					$proveidor = $docencia->getProveidor();
					if (!isset($arrDocents[$proveidor->getId()])) $arrDocents[$proveidor->getId()] = $proveidor->getRaosocial();
				}
				
				$pagamentsActivitat = array('professors' => array('titol' => implode(',',$arrDocents), 'totals' => $totalsDocencia));
					
				setlocale(LC_TIME, 'ca_ES', 'Catalan_Spain', 'Catalan');
				foreach ($mesosPagaments as $mes) {
						
					$anyMes = sprintf('%s-%02s', $mes['any'], $mes['mes']); 
						
					$currentAnyMes = \DateTime::createFromFormat('Y-m-d', $anyMes.'-01');
						
					//$mesText =  $currentAnyMes->format('F \d\e Y');
					//$mesText = date("F \de Y", $currentAnyMes->format('U'));
					
					//$mesText = utf8_encode(strftime("%B de %Y", $currentAnyMes->format('U')));
						
					$df = new \IntlDateFormatter('ca_ES', \IntlDateFormatter::FULL, \IntlDateFormatter::FULL, 'Europe/Madrid', \IntlDateFormatter::GREGORIAN, "MMMM 'de' yyyy");
							
					$mesText = $df->format($currentAnyMes->format('U'));
						
					foreach ($docencies as $c => $docencia) {
					
						$docent = $docencia->getProveidor();
						
						if ($c > 0) $mesText = '';
						
						if ( !isset($pagamentsActivitat[$anyMes][$docent->getId()]) ) {
							
							// El docent encara no existeix a pagaments
							$mesText .= ' <span class="nom-professor">'.$docent->getRaosocial().'</span>';
	
							$pagamentsActivitat[$anyMes][$docent->getId()] = array( 
										'anymespagament' => $mesText, 
										'datapagament' => urlencode($currentAnyMes->format('t/m/Y')),  // 't' => últim dia del mes
										'concepte' => 'Liquidació '.$docent->getRaosocial().' '.$currentAnyMes->format('m/Y').' '.$activitat->getDescripcio(),
										//'import' => floor($docencia->getImport()/count($mesosPagaments)),
										'import' => $docencia->getImportMensual($mes['any'], $mes['mes']),
										'sessions' => $docencia->getSessionsMensual($mes['any'], $mes['mes']),
										'professor' =>  $docent,
										'graellapagaments' => $graellaPagamentMesFacturacions,
										'liquidacions' => array() );
						
						} else {
							
							// Acumular pagament del docent ja existent
							$pagamentsActivitat[$anyMes][$docent->getId()]['import'] += $docencia->getImportMensual($mes['any'], $mes['mes']);
							$pagamentsActivitat[$anyMes][$docent->getId()]['sessions'] += $docencia->getSessionsMensual($mes['any'], $mes['mes']);
						}
						
						
						// Desplaça els corresponents mesos de les facturacions per ubicar els pagament
						if (!isset($mesosFacturacions[0])) throw new \Exception('Mes de '.$mesText.' fora dels periodes de facturació ');
						
						if ( count($mesosFacturacions) > 1 ) { // Encara no estem a l'última facturació 
							$anyMesCandidat = sprintf('%s-%02s', $mesosFacturacions[1]['anyfacturacio'], $mesosFacturacions[1]['mesfacturacio']);
							if ($anyMes >= $anyMesCandidat) array_shift($mesosFacturacions);
						}
						
						$facturacioIdMesPagament = $mesosFacturacions[0]['facturacio'];
						
						if ( isset ($pagamentsActivitat[$anyMes][$docent->getId()]['graellapagaments'][$facturacioIdMesPagament]) 
								&& $pagamentsActivitat[$anyMes][$docent->getId()]['sessions'] > 0 ) {
							
							$pagamentsActivitat[$anyMes][$docent->getId()]['graellapagaments'][$facturacioIdMesPagament] = true;
							
							$currentLiq = $docencia->getPagamentsMesAny($mes['any'], $mes['mes']);
							$pagamentsActivitat[$anyMes][$docent->getId()]['liquidacions'] = $currentLiq;
							foreach ($currentLiq as $liq) {
									
								$pagamentsActivitat['professors']['totals'][$facturacioIdMesPagament] += $liq->getImport();
									
								$activitatParticipants[$activitatid]['facturacionsTotals'][$facturacioIdMesPagament]['totalfacturaciocurs'] -= $liq->getImport();
									
							}
								
								
						}
					}
				}
					
				$activitatParticipants[$activitatid]['pagaments'] = $pagamentsActivitat;*/
					
			}
				
		} catch (\Exception $e) {
			$response = new Response($e->getMessage());
    		$response->setStatusCode(500);
    		return $response;
		}
		
		return $this->render('FomentGestioBundle:Rebuts:infoactivitat.html.twig',
				array('current' => $current, 'currentactivitat' => $activitatid, 'dades' => $activitatParticipants));
	}
	
	/*public function pagamentproveidorsAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		$pagament = null;
		$proveidor = null;
		$docencia = null;
		$datapagament = null;
		$strConcepte = '';
		$import = 0;
		$num = '';
		$checkbaixa = false;
		
		if ($request->getMethod() == 'POST') {
			$data = $request->request->get('pagament');
			 
			$id = (isset($data['id'])?$data['id']:0);
			
			$docenciaid = (isset($data['docencia'])?$data['docencia']:0);
			
			$docencia = $em->getRepository('FomentGestioBundle:Docencia')->find($docenciaid);
				
			if ($docencia != null) $proveidor = $docencia->getProveidor();
			
			if (isset($data['checkbaixa']) && $data['checkbaixa'] == 1) $checkbaixa = true;
		} else {
			
			
			$id = $request->query->get('id', 0);

			$docenciaid = $request->query->get('docencia', 0); 
			
			$docencia = $em->getRepository('FomentGestioBundle:Docencia')->find($docenciaid);
			
			if ($docencia != null) $proveidor = $docencia->getProveidor();
			
			$strDatapagament = $request->query->get('datapagament', '');
			
			if ($strDatapagament == '') $datapagament = new \DateTime();
			else $datapagament = \DateTime::createFromFormat('d/m/Y', urldecode($strDatapagament));
			
			$strConcepte = $request->query->get('concepte', '');
			
			$import = $request->query->get('import', 0);
		}
		$pagament = $em->getRepository('FomentGestioBundle:Pagament')->find($id);
		if ($pagament == null) {
			$pagament = new Pagament($num, $proveidor, $docencia, $datapagament, $strConcepte, $import);
			$em->persist($pagament);
		}
		$form = $this->createForm(new FormPagament(), $pagament);
		$response = '';
		if ($request->getMethod() == 'POST') {
			try {
				$form->handleRequest($request);
				
				if ($pagament->getNum() == null) $pagament->setNum('');
				
				if (!$form->isValid()) throw new \Exception('Dades incorrectes, cal revisar les dades del pagament' ); 
				
				if ($pagament->esPagamentcurs()) {
					if ($pagament->getDocencia() == null) throw new \Exception('Falta indicar el curs' );
					 
					// Docencencia != null
					if ($pagament->getDocencia()->getProveidor() != $pagament->getProveidor()) throw new \Exception('El professor i el curs no coincideixen' );
				}
				
				// Validacions
				if ($checkbaixa == true && $pagament->getDatabaixa() == null)  throw new \Exception('Per anul·lar el pagament cal indicar una data' ); 
				
				$pagament->setDatamodificacio(new \DateTime());
				 
				if ($pagament->getId() == 0) $em->persist($pagament);
			
				$em->flush();
				
				$this->get('session')->getFlashBag()->add('notice',	'El pagament s\'ha desat correctament');
				$response = $this->renderView('FomentGestioBundle:Rebuts:pagament.html.twig',
						array('form' => $form->createView(), 'pagament' => $pagament));
				
			// Ok, retorn form sms ok
			} catch (\Exception $e) {
				// Ko, mostra form amb errors
				$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
				$response = $this->renderView('FomentGestioBundle:Rebuts:pagament.html.twig',
					array('form' => $form->createView(), 'pagament' => $pagament));
			}
			
			
		} else {
			// GET mostrar form
			$response = $this->renderView('FomentGestioBundle:Rebuts:pagament.html.twig',
					array('form' => $form->createView(), 'pagament' => $pagament));
			
		}
		return new Response($response);
	}*/
	
	public function pagamentproveidorsAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		
		$currentYear = 0;
		$currentMonth = 0;
			
		$strPendents = '';
		$docencies = array();
		$proveidor = null;
		$import = 0;
		$descripcions = array();
		$datapagament = null;
		if ($request->getMethod() == 'POST') {
			$data = $request->request->get('form');
				
			$currentYear = isset($data['currentyear'])?$data['currentyear']:date('Y');
			$currentMonth =  isset($data['currentmonth'])?$data['currentmonth']:date('m');
					
			$strPendents = isset($data['pendents'])?$data['pendents']:'';
		} else {
			$currentYear = $request->query->get('currentyear', date('Y'));
			$currentMonth = $request->query->get('currentmonth', date('m'));
			
			$strPendents = $request->query->get('pendents', '');
		}
		
		$pendents = explode(",", $strPendents);
		foreach ($pendents as $id) {
			$docencia = $em->getRepository('FomentGestioBundle:Docencia')->find($id);
		
			if ($docencia != null) {
					
				$pagaments = $docencia->getPagamentsMesAny($currentYear, $currentMonth);
				
				if ($proveidor == null) $proveidor = $docencia->getProveidor();
					
				if (count($pagaments) == 0) {
					$descripcions[] = $docencia->getFacturacio()->getDescripcio();
					
					$docencies[] = $docencia;
					$import += $docencia->getImportSessionsMensuals($currentYear, $currentMonth);
				}
			}
		}
		
		if ($request->getMethod() == 'POST') {
			try {
				$strDatapagament = isset($data['datapagament'])?$data['datapagament']:'';
				if ($strDatapagament == '') throw new \Exception('cal indicar la data de pagament');
				$datapagament = \DateTime::createFromFormat('d/m/Y', $strDatapagament);

				$concepte = isset($data['concepte'])?$data['concepte']:'';
				
				$num = $this->getMaxPagamentNumAny($datapagament->format('Y'));
				
				foreach ($docencies as $docencia) {
					$import = $docencia->getImportSessionsMensuals($currentYear, $currentMonth);
					$pagament = new Pagament($num, $proveidor, $docencia, $datapagament, $concepte, $currentYear, $currentMonth, $import);
					$em->persist($pagament);
				}
				
				$em->flush();
				
				$response = new Response('Liquidació realitzada correctament');
			} catch (\Exception $e) {
				// Ko, mostra form amb errors
				/*$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
				$response = $this->renderView('FomentGestioBundle:Rebuts:pagament.html.twig',
				array('form' => $form->createView(), 'pagament' => $pagament));*/
				
				$response = new Response($e->getMessage());
				$response->setStatusCode(500);
			}
			
			return $response;
				
		}
		
		if ($datapagament == null) $datapagament = new \DateTime();
			
		setlocale(LC_TIME, "ca", "ca_ES.UTF-8", "ca_ES.utf8", "ca_ES", "Catalan_Spain", "Catalan");
		setlocale(LC_TIME, "ca_ES.utf8");
			
		$strMonth = strftime('%B', mktime(0, 0, 0, $currentMonth));
			
		$concepte = 'Liquidació mes '.mb_strtoupper($strMonth).' de '.$currentYear.'  '.implode(", ", $descripcions);

		$num = $this->getMaxPagamentNumAny($datapagament->format('Y'));
		
		$form = $this->createFormBuilder()
			->add('num', 'text', array('required' => true, 'data' => $num, 'read_only' => true))
			->add('import', 'text', array('required' => true, 'data' => $import, 'read_only' => true))
			->add('datapagament', 'text', array( 'required' => true, 'data' => $datapagament->format('d/m/Y')  ))
			->add('proveidor', 'text', array( 'required' => true, 'data' => $proveidor->getRaosocial(), 'read_only' => true ))
			->add('concepte', 'textarea', array( 'required' => true, 'data' => $concepte  ))
			->add('pendents', 'hidden', array( 'required' => true, 'data' => $strPendents  ))
			->add('currentyear', 'hidden', array( 'required' => true, 'data' => $currentYear  ))
			->add('currentmonth', 'hidden', array( 'required' => true, 'data' => $currentMonth  ))
		->getForm();		
		
		// GET mostrar form
		$response = $this->renderView('FomentGestioBundle:Rebuts:liquidacio.html.twig',
				array('form' => $form->createView(), 'docencies' => $docencies,  'year' => $currentYear, 'month' => $currentMonth));
		
		return new Response($response);
	}
	
	public function pagamentsmensualsproveidorsAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
		
		$currentYear = $request->query->get('year', date('Y'));  // Mes de la consulta
		$currentMonth = $request->query->get('month', date('m'));  // Mes de la consulta
		
		$tots = $em->getRepository('FomentGestioBundle:Proveidor')->findBy(array('databaixa' => null), array('raosocial' => 'ASC'));
		
		$proveidors = array('proveidors' => array(), 'total' => 0, 'pagat' => 0, 'pagaments' => array(), 'pendents' => array());
		foreach ($tots as $proveidor) {
			$sessions = $proveidor->getSessionsActives($currentYear, $currentMonth); // Sessions del mes indicat 
		
			if (count($sessions) > 0) {
			
				$docencies = array();
				$total = 0;
				$liquidat = 0;
				$pagamentsIds = array();
				$pendentsIds = array();
				foreach ($sessions as $sessio) {
					
					$docencia = $sessio->getDocencia();
					
					$facturacio = $docencia->getFacturacio();
					
					$durada = $sessio->getHorari()->getDurada();
					$preu = $docencia->getPreuhora();
					$pagat = 0;
					
					$pagaments = $docencia->getPagamentsMesAny($currentYear, $currentMonth);
					if (count($pagaments) == 0) {
						// docencia pendent
						if (!in_array($docencia->getId(), $pendentsIds)) $pendentsIds[] = $docencia->getId();
					} else {
						// docencia pagada
						foreach ($pagaments as $pagament) {
							
							if (!in_array($pagament->getId(), $pagamentsIds)) {
								$pagamentsIds[] = $pagament->getId();
							
								$pagat += $pagament->getImport();
							}
						}
					}
					
					$total += $preu;
					$liquidat += $pagat;
					$proveidors['total'] += $preu;
					$proveidors['pagat'] += $pagat;
					
					$key = $facturacio->getId().'_'.$docencia->getId();
					
					if (!isset($docencies[$key])) {
						$docencies[$key] = array (
							'idfac'		=> $facturacio->getId(),
							'iddoc'		=> $docencia->getId(),
							'descripcio' => $facturacio->getDescripcio(),
							'total'		=> $preu,	
							'pagat'		=> $pagat,	
							'sessions'	=> array (	
								$durada =>  array(
									'num'		=> 1,
									'durada'	=> $durada,
									'preu'		=> $preu,
								)
							)
						);
						
					} else {
						if (!isset($docencies[$key]['sessions'][$durada])) {
							$docencies[$key]['total'] += $preu;
							$docencies[$key]['pagat'] += $pagat;
							$docencies[$key]['sessions'][$durada] = array( 
									'num'		=> 1,
									'durada'	=> $durada,
									'preu'		=> $preu
							);
							
						} else {
							$docencies[$key]['total'] += $preu;
							$docencies[$key]['pagat'] += $pagat;
							$docencies[$key]['sessions'][$durada]['num']++;
						}
					}
				}

				$proveidors['proveidors'][] = array(
					'id'			=> 	$proveidor->getId(),
					'nom'			=> 	$proveidor->getRaosocial(),
					'total'			=>  $total,
					'pagat'			=>  $liquidat,
					'pagaments'		=> 	implode(",",$pagamentsIds),
					'pendents'		=>  implode(",", $pendentsIds),
					'docencies'		=> 	$docencies	
				);
			}
		}
		
		setlocale(LC_TIME, "ca", "ca_ES.UTF-8", "ca_ES.utf8", "ca_ES", "Catalan_Spain", "Catalan");
		setlocale(LC_TIME, "ca_ES.utf8");
		
		$strMonth = strftime('%B', mktime(0, 0, 0, $currentMonth));
		
		return $this->render('FomentGestioBundle:Rebuts:pagamentsmensualsproveidors.html.twig',
				array('proveidors' => $proveidors, 'year' => $currentYear, 'month' => $currentMonth, 'strMonth' => $strMonth ));
	}
	
	public function editarrebutAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		$em = $this->getDoctrine()->getManager();
	
		$rebut = null;

		if ($request->getMethod() == 'POST') {
			$data = $request->request->get('rebut');
			$id = (isset($data['id'])?$data['id']:0);
			$tipus = (isset($data['tipusrebut'])?$data['tipusrebut']:UtilsController::TIPUS_SECCIO);
		} else {
			$id = $request->query->get('id', 0);
			$tipus = $request->query->get('tipus',UtilsController::TIPUS_SECCIO);
		}
	
		$rebut = $em->getRepository('FomentGestioBundle:Rebut')->find($id);

		if ($rebut == null) {
			// Nou rebut
			$deutor = null;
			$numrebut = 0;
			$facturacio = null;
			$activitat = null;
			$participant = null;
			$dataemissio = null;
			
			if ($request->getMethod() == 'POST') {
			
				$idpersona = (isset($data['deutor'])?$data['deutor']:0);
				$dataemissio =  (isset($data['dataemissio'])?\DateTime::createFromFormat('d/m/Y', $data['dataemissio']):new \DateTime());
				$current = $dataemissio->format('Y'); 

				if ($tipus == UtilsController::TIPUS_SECCIO ||
					$tipus == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL) {
					$idseccio =  ( isset($data['origen'])?$data['origen']:0);
			
				} else {
					$idfacturacio = (isset($data['facturacio'])?$data['facturacio']:0);
					$idactivitat = (isset($data['origen'])?$data['origen']:0);
					
				}
			} else {
				$idpersona = $request->query->get('idpersona', 0);
				$current = $request->query->get('current', date('Y'));
				
				if ($tipus == UtilsController::TIPUS_SECCIO ||
					$tipus == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL) {
					$idseccio = $request->query->get('idseccio', 0);
					$mesfacturacio = $request->query->get('mesfacturacio', 0);  // > 0 Per seccions no semestrals. Indica el número de mes
					if ($mesfacturacio > 0) $dataemissio =  \DateTime::createFromFormat('d/m/Y', '15/'.$mesfacturacio.'/'. $current );
				} else {
					$idfacturacio = $request->query->get('idfacturacio', 0);
					$idactivitat = $request->query->get('idactivitat', 0);
				}
			}
			if ($tipus == UtilsController::TIPUS_SECCIO ||
				$tipus == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL) {
					
				$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($idseccio);
				if ($seccio != null && $idpersona != 0) $membre = $seccio->getMembreBySociId($idpersona);
				
				$numrebut = $this->getMaxRebutNumAnySeccio($current);

				if ($membre == null) $this->get('session')->getFlashBag()->add('error',	'El soci no pertany a la secció '.$seccio->getNom()); 
				else {
					//$deutor = $membre->getSoci();
					if ($tipus == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL) {  // Seccions no semestrals
						// posar el número de mes a la facturació no semestral
						$rebut = $this->generarRebutSeccioNoSemestral($membre, $dataemissio, $numrebut); // Ja està persistit
					}
					if ($tipus == UtilsController::TIPUS_SECCIO) {  // Seccions semestrals
						
					}
				}
			} else {
				
				$facturacio = $em->getRepository('FomentGestioBundle:Facturacio')->find($idfacturacio);
				$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($idactivitat);
				if ($activitat != null && $idpersona != 0) $participant = $activitat->getParticipacioByPersonaId($idpersona);
				if ($facturacio != null && $participant != null) {
					$numrebut = $this->getMaxRebutNumAnyActivitat($facturacio->getDatafacturacio()->format('Y'));
					$rebut = $this->generarRebutActivitat($facturacio, $participant, $numrebut); // Ja està persistit
				} else {
					$deutor = $em->getRepository('FomentGestioBundle:Persona')->find($idpersona);
					
					$dataemissio = new \DateTime();
					$numrebut = $this->getMaxRebutNumAnyActivitat($dataemissio->format('Y'));
					$rebut = new Rebut($deutor, $dataemissio, $numrebut, false);
						
					$em->persist($rebut);
				}
			}
			if ($rebut == null) $rebut = new Rebut($deutor, new \DateTime(), $numrebut); // Error
		}
		
		$form = $this->createForm(new FormRebut(), $rebut);
	
		$response = '';
		if ($request->getMethod() == 'POST') {
			
			try {
				$form->handleRequest($request);
			
				$importcorreccio = $form->get('importcorreccio')->getData();
				$nouconcepte = $form->get('nouconcepte')->getData();
				
				if (!$form->isValid()) {
					
					throw new \Exception('Dades incorrectes, cal revisar les dades del rebut ' ); //$form->getErrorsAsString()
				}
				
				if ($importcorreccio <= 0) {
					throw new \Exception('L\'import ha de ser superior a 0');
				}
				
				if ($rebut->getDeutor() == null) {
					throw new \Exception('Cal indicar el deutor del rebut' );
				}
				
				if ($rebut->esActivitat() == true) { // Validacions rebut Activitat
					// Validacions. Si es activitat no pot modificar-se el tipus
					if ($rebut->getTipuspagament() != UtilsController::INDEX_FINESTRETA)
							throw new \Exception('El pagament ha de ser finestreta' );
					
					if ($rebut->getFacturacio() == null) 
							throw new \Exception('Cal indicar la facturació del rebut' );
					
					if ($rebut->getFacturacio()->getActivitat() == null) 
							throw new \Exception('Cal indicar el curs o taller' );
					
					// Validar si la persona ja té rebut per aquest curs/facturacio
					if ($rebut->getId() == 0) {
						$existent = $rebut->getDeutor()->getRebutFacturacio($rebut->getFacturacio());
						if ($existent != null) 	throw new \Exception('Aquesta persona ja té un rebut per aquesta facturació: '.$existent->getNumFormat() );
					}
				} 
				if ($rebut->esSeccio() == true) {
					if ($rebut->getTipusrebut() == UtilsController::TIPUS_SECCIO) { // Validacions rebut Seccions semestrals
						// Validacions. 
						if ($rebut->getPeriodenf() == null && $rebut->getFacturacio() == null)
							throw new \Exception('Cal indicar la facturació del rebut' );
							
						$periode = $rebut->getPeriodenf();
						//if ($periode == null) $periode = $rebut->getFacturacio()->getPeriode();
						// Validar si la persona ja té rebut per aquest curs/facturacio
						if ($rebut->getId() == 0) {
							$existent = $rebut->getDeutor()->getRebutPeriode($periode);
							if ($existent != null) 	throw new \Exception('Aquesta persona ja té un rebut de la secció per al periode indicat: '.$existent->getNumFormat() );
						}
						
						if ($rebut->getDataretornat() != null) {
							if ($rebut->getFacturacio() == null) throw new \Exception('No es pot retornar un rebut que no s\'ha enviat a domiciliació' );
							
							$rebut->setTipuspagament(UtilsController::INDEX_FINES_RETORNAT);
						}
						
						/*if ($rebut->getTipuspagament() == UtilsController::INDEX_DOMICILIACIO && $rebut->getPeriodenf() == null) {
							if ($periode == null) throw new \Exception('El rebut no es pot marcar per domiciliar' );
							else $rebut->setPeriodenf($periode);
						}*/
					}
				
					if ($rebut->getTipusrebut() == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL) { // Validacions rebut Seccions no semestrals
						// Validacions. Si es secció no semestral el tipus finestreta
						if ($rebut->getTipuspagament() != UtilsController::INDEX_FINESTRETA)
							throw new \Exception('El pagament de les seccions no semestrals ha de ser finestreta' );
								
						if ($rebut->getPeriodenf() != null)
							throw new \Exception('Les seccions no semestrals no s\'assignen a cap periode o semestre' );
									
						if ($rebut->getFacturacio() != null)
							throw new \Exception('Les seccions no semestrals no entren a les facturacions normals' );
							
					}
					
					// ninguna data: pagat, retornat o baixa abans que emissió
	
					// Validació eliminada petició Olga
					//if ($rebut->getDatapagament() != null && $rebut->getDatapagament() < $rebut->getDataemissio()) throw new \Exception('La data de pagament no pot ser anterior a la data d\'emissió' );
					//if ($rebut->getDataretornat()!= null && $rebut->getDataretornat() < $rebut->getDataemissio()) throw new \Exception('La data de retornat no pot ser anterior a la data d\'emissió' );
					// La data de baixa si pot ser posterior, es poden anul·lar rebuts futurs
					//if ($rebut->getDatabaixa()!= null && $rebut->getDatabaixa() < $rebut->getDataemissio()) throw new \Exception('La data de baixa no pot ser anterior a la data d\'emissió' );
				}
				
				if ($rebut->getId() == 0) {
					$rebut->setDatamodificacio(new \DateTime());
					$em->persist($rebut);
				} else {
					// Crear rebut correcció
					if ($rebut->esCorreccio() || $rebut->getImport() != $importcorreccio) {
						$this->correccioRebut($rebut, $importcorreccio, $nouconcepte);
					} else {
						if ($nouconcepte != '') throw new \Exception('No cal indicar cap concepte mentre no canviï l\'import del rebut' );
					}
				}
				$em->flush();
				$em->refresh($rebut);
				
				/*$this->get('session')->getFlashBag()->add('notice',	'El rebut s\'ha desat correctament');
				$response = $this->renderView('FomentGestioBundle:Rebuts:rebut.html.twig',
						array('form' => $form->createView(), 'rebut' => $rebut));*/
					
				return new Response('El rebut s\'ha desat correctament');
				
				// Ok, retorn form sms ok
			} catch (\Exception $e) {
				// Ko, mostra form amb errors
				/*$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
				$response = $this->renderView('FomentGestioBundle:Rebuts:rebut.html.twig',
						array('form' => $form->createView(), 'rebut' => $rebut));*/
				
				$response = new Response($e->getMessage());
				$response->setStatusCode(500);
			
				return $response;
			}
				
				
		} 
	
		// GET mostrar form
		/*$response = $this->renderView('FomentGestioBundle:Rebuts:rebut.html.twig',
						array('form' => $form->createView(), 'rebut' => $rebut));
		
		return new Response($response);*/
		
		return $this->render('FomentGestioBundle:Rebuts:rebut.html.twig',
						array('form' => $form->createView(), 'rebut' => $rebut));
	}
	
	private function correccioRebut($rebut, $importcorreccio, $nouconcepte)
	{
		$em = $this->getDoctrine()->getManager();
		if ($importcorreccio <= 0) throw new \Exception('L\'import del rebut és incorrecte '.$importcorreccio );
		if ($nouconcepte == '') throw new \Exception('Cal indicar algún concepte per al rebut' );
			
		$rebut->setDatamodificacio(new \DateTime());
		if ($rebut->esCorreccio()) {
			$rebut->setNouconcepte($nouconcepte);
			$rebut->setImportcorreccio($importcorreccio);
			$rebut->setDatamodificacioc(new \DateTime());
		} else {
			// Herència directament contra BBDD
			$current = new \DateTime();
			$query = "INSERT INTO rebutscorreccions (id, importcorreccio, nouconcepte, dataentradac, datamodificacioc) VALUES ";
			$query .= "('".$rebut->getId()."','".$importcorreccio."', '".str_replace("'","''",$nouconcepte)."', '".$current->format('Y-m-d H:i:s')."', '".$current->format('Y-m-d H:i:s')."')";
				
			$em->getConnection()->exec( $query );
				
			// Canvi a Soci directament des de SQL. Doctrine no deixa
			$query = "UPDATE rebuts SET rol = 'X' WHERE id = ".$rebut->getId();
			$em->getConnection()->exec( $query );
				
			$em->refresh($rebut);
		}
	}
	
	/* AJAX. Veure informació i gestionar caixa periodes. Rebuts generals */
	public function gestiofacturacionscontentAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		
		$em = $this->getDoctrine()->getManager();
			
		$current = $request->query->get('current', date('Y'));
		
		try {
		
			if ($request->getMethod() == 'POST') {
				throw new \Exception('Opció incorrecte');
			} else {
				$accio = $request->query->get('action', '');
			
				switch ($accio) {
					case '':
					case 'query':  // Consultar dades any fins a data actual
							
						break;
		
					case 'remove':  // Remove rebuts i facturació
						
						$facturacioid = $request->query->get('facturacio', 0);
						 
						$facturacio = $em->getRepository('FomentGestioBundle:FacturacioSeccio')->find($facturacioid);
						
						if ($facturacio == null) throw new \Exception('No s\'ha trobat les dades');
						 
						if (!$facturacio->esEsborrable())  throw new \Exception('No es pot esborrar la facturació');
						
						
						// Esborrar rebuts de la facturació, i finalment també la facturació
						
						foreach ($facturacio->getRebuts() as $rebut) {
							foreach ($rebut->getDetalls() as $detall) $em->remove($detall);
							$em->remove($rebut);
						}
						$em->remove($facturacio);
						
						$em->flush();
						
						$this->get('session')->getFlashBag()->add('notice',	'Facturació i rebuts esborrats correctament');

						break;
						
					case 'facturar':  // Esborrar periode
						/*
						 * Facturar tots els rebuts pendents que cal domiciliar de l'any fins a la data actual
						 * Si existeix facturació oberta (no domiciliada), afegir en aquests
						 * En  cas contrari crear-ne una de nova
						 * Crear o afegir els rebuts de finestreta a la facturació corresponent, només una
						 */
						
						$dataemissio = new \DateTime();
						$strDataemissio = $request->query->get('dataemissio', '');
						if ($strDataemissio != '') $dataemissio = \DateTime::createFromFormat('d/m/Y', urldecode($strDataemissio));
						
						$facturacio = $this->generarRebutsSeccions($current, $dataemissio);
							
						$em->flush();
							
						$this->get('session')->getFlashBag()->add('notice',	'Els rebuts pendents s\'han afegit a la facturació '.$facturacio->getDescripcio().' correctament');
						
						break;
					default:  // Altres
						$this->get('session')->getFlashBag()->add('error',	'Acció incorrecte');
						break;
				}
			}
		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error', $e->getMessage());
		}
		
		$facturacions = UtilsController::queryGetFacturacions($em, $current);  // Ordenades per data facturacio DESC
		
		return $this->render('FomentGestioBundle:Rebuts:gestiofacturacionscontent.html.twig',
				array( 'link' => 'facturacio', 'facturacions' => $facturacions));
	}
	
	/* Veure informació i gestionar caixa periodes. Rebuts generals */
	public function gestiofacturacionsAction(Request $request)
	{
		return $this->render('FomentGestioBundle:Rebuts:gestiofacturacions.html.twig', 
							array('form' => $this->formFacturacionsPage($request)->createView()));
    }
    
    private function formFacturacionsPage(Request $request) {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$current = $request->query->get('current', date('Y'));
    	$dataemissio = new \DateTime();
    	
    	$anysSelectable = $this->getAnysSelectableToNow();
    	$anysSelectable[date('Y') + 1] = date('Y') + 1;
    	
    	$form = $this->createFormBuilder()
    	->add('dataemissio', 'hidden', array(
    			'data'		=> $dataemissio->format('d/m/Y')
    	))
    	->add('selectoranys', 'choice', array(
    			'required'  => true,
    			'choices'   => $anysSelectable,
    			'data'		=> $current))->getForm();
    	
    	return $form;
    }
    
    /* Generar els rebuts pendents per als membres de les seccions que facturen semestralment
     * Si els rebuts ja existeixen no se'ls crea */
    private function generarRebutsSeccions($anydades, $dataemissio)
    {
    	$em = $this->getDoctrine()->getManager();
    
    	// Mirar si cal crear una nova facturació. No hi ha cap per aquest any o la última està tancada (domiciliada)
    	$facturacio = $this->queryGetFacturacioOberta($anydades);
  	
    	if ($facturacio == null) throw new \Exception('Facturació incorrecte');
    
    	if ($dataemissio == null) $dataemissio = new \DateTime();
    	
    	// Obtenir els socis actius en el periode ordenats per soci rebut, num compte i seccio
    	$datedesde = \DateTime::createFromFormat('Y-m-d', $anydades.'-01-01');
    	$datefins = \DateTime::createFromFormat('Y-m-d', $anydades.'-12-31');

    	if ($dataemissio->format('Y-m-d') < $datedesde->format('Y-m-d') || $dataemissio->format('Y-m-d') > $datefins->format('Y-m-d')) throw new \Exception('La data d\'emissió no es troba dins el periode de facturació');
    	
    	$membres = $this->queryGetMembresActiusPeriodeAgrupats($datedesde, $datefins); // Totes les quotes del soci pagador arriben juntes
    
    	$numrebut = $this->getMaxRebutNumAnySeccio($anydades); // Max num rebut anual
    	$total = 0;
    	$socipagarebut = null; // Soci agrupa rebuts per pagar
    	$membresAmbfraccio = array();
    	$fraccio  = 1;
    	foreach ($membres as $membre) {
    		$seccio = $membre->getSeccio();
    		if ($seccio->getSemestral() == true) {

	    		$socipagarebut = $membre->getSoci()->getSocirebut();

	    		if ($socipagarebut == null) throw new \Exception('Cal indicar qui es farà càrrec dels rebuts '.($membre->getSoci()->getSexe()=='H'?'del soci ':'de la sòcia ').$membre->getSoci()->getNomCognoms() );
	    		
	    		$this->generarRebutMembre($facturacio, $socipagarebut, $membre, $numrebut, $anydades, $dataemissio, $fraccio);
	    		
    			$total++;
	    			
    			if ($seccio->esGeneral() && $socipagarebut->getPagamentfraccionat()) {
	    			// Mirar si és la secció general i el soci té fraccionament crear els dos rebuts ara
	    			// Enviar $facturació 1 o 2 a 	generarRebutDetallMembre  per simplificar el mètode
	    			$membresAmbfraccio[] = $membre;
	    		}
    		} else {
    			// Les seccions no semestrals sempre les paguen els propis socis per finestreta
    			//$soci  = $noumembre->getSoci();
    			 
    			// Crear tants rebuts com facturacions mensualment
    			$dataemissioAuxNoSemestral = clone $dataemissio;
    			
    			for($numfacturacio = 0; $numfacturacio < $seccio->getFacturacions(); $numfacturacio++) {
    				if ($this->generarRebutSeccioNoSemestral($membre, $dataemissioAuxNoSemestral, $numrebut) != null) {
    			
    					$dataemissioAuxNoSemestral->add(new \DateInterval('P1M'));	// Totes les facturacions de cop, incrementar un mes
    			
    					$numrebut++;
    				}
    			}
    		}
    	}
    	
    	// emissió 2na fracció
    	$dataemissio2 = UtilsController::getDataIniciEmissioSemestre2($anydades);
    	if ($dataemissio2->format('Y-m-d') > $dataemissio->format('Y-m-d')) $dataemissio = $dataemissio2;  // Per si es facturés per primera vegada passat l'inici 2n semestre
    	
    	// Fraccions 2n semestre
    	$fraccio  = 2;
    	foreach ($membresAmbfraccio as $membre) {
    		$socipagarebut = $membre->getSoci()->getSocirebut();
    			
    		$this->generarRebutMembre($facturacio, $socipagarebut, $membre, $numrebut, $anydades, $dataemissio, $fraccio);
	    		
   			$total++;
   		}
   		
    	if ($total <= 0) throw new \Exception('No s\'ha afegit cap rebut a la facturació');
    	
    	return $facturacio;
    }
    
    /* Revisar la morositat dels socis */
    public function morososAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'deute', 'direction' => 'desc'));
    
    	$queryparams['tipus'] =  $request->query->get('tipus', UtilsController::OPTION_TOTS);
    	
    	$morososArray = $this->getMorosos($queryparams);
    	
    	// Paginator
    	$paginator  = $this->get('knp_paginator');
    	 
    	$morosos = $paginator->paginate(
    			//$query,
    			$morososArray,
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
    			))->add('tipus', 'choice', array(
    					'required'  => true,
    					'choices'   => UtilsController::getTipusRebutOptions(),
    					'data'		=> $queryparams['tipus'],
    					'attr' 		=> array('class' => 'select-tipusrebut')
    			))->getForm();
    			 
    	if ($request->isXmlHttpRequest() == true) {
    		// Ajax call renders only table morosos
    		return $this->render('FomentGestioBundle:Rebuts:taulamorosos.html.twig',
    				array('form' => $form->createView(), 'morosos' => $morosos, 'total' => count($morososArray),
    						'queryparams' => $queryparams));
    	}
    			
    	return $this->render('FomentGestioBundle:Rebuts:morosos.html.twig',
    			array('form' => $form->createView(), 'morosos' => $morosos, 'total' => count($morososArray),
    					'queryparams' => $queryparams));
    }
    

}
