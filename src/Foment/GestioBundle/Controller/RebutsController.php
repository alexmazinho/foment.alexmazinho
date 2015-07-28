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
use Foment\GestioBundle\Form\FormPagament;
use Foment\GestioBundle\Form\FormRebut;
use Foment\GestioBundle\Form\FormActivitatPuntual;
use Foment\GestioBundle\Entity\AuxMunicipi;
use Foment\GestioBundle\Classes\TcpdfBridge;
use Foment\GestioBundle\Entity\Rebut;
use Symfony\Component\Validator\Constraints\Length;
use Foment\GestioBundle\Entity\Facturacio;
use Foment\GestioBundle\Entity\Pagament;
use Foment\GestioBundle\Entity\RebutCorreccio;


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
				'facturacio' => $queryparams['facturacio'], 'periode' => $queryparams['periode']);
	
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
				'choices'   => array(0 => 'tots', UtilsController::INDEX_FINESTRETA => 'finestreta', UtilsController::INDEX_DOMICILIACIO => 'banc'),
				/*'data'		=> $queryparams['tipus']*/ ) )    
		->add('facturacio', 'entity', array(
				'error_bubbling'	=> true,
				'class' 	=> 'FomentGestioBundle:Facturacio',
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
		->add('periode', 'entity', array(
				'error_bubbling'	=> true,
				'class' 	=> 'FomentGestioBundle:Periode',
				'query_builder' => function(EntityRepository $er) {
					return $er->createQueryBuilder('p')
					->orderBy('p.anyperiode', 'DESC')
					->orderBy('p.semestre', 'DESC');
				},
				'property' 	=> 'titol',
				'multiple' 	=> false,
				'required'  => false,
				'empty_data'=> null,
				'data' 		=> $this->getDoctrine()->getRepository('FomentGestioBundle:Periode')->find($queryparams['periode'])
		))
		->add('recarrec', 'number', array (
					'required' => true,
					'precision' => 2,
					'data' => UtilsController::RECARREC_REBUT_RETORNAT,
					'mapped' => false,
					'constraints' => array (
							new NotBlank ( array (
									'message' => 'Cal indicar l\'import.'
							) ),
							new Type ( array (
									'type' => 'numeric',
									'message' => 'L\'import ha de ser numèric.'
							) ),
							new GreaterThanOrEqual ( array (
									'value' => 0,
									'message' => 'L\'import no és vàlid.'
							) )
					)
			) ) // Recàrrec retornats
		->getForm();
		
		return $this->render('FomentGestioBundle:Rebuts:cercarebuts.html.twig', array('form' => $form->createView(), 'rebuts' => $rebuts, 'queryparams' => $queryparams));
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
		$request->query->set('anulats', true);
		$request->query->set('sort', 'r.databaixa');
		$request->query->set('direction', 'desc');
		
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
		
		$request->query->set('cobrats', true);
		$request->query->set('sort', 'r.datapagament');
		$request->query->set('direction', 'desc');
		
		return $this->redirect($this->generateUrl('foment_gestio_rebuts', $request->query->all()));
		
	}

	public function retornarrebutAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$em = $this->getDoctrine()->getManager();
	
		$ids = $request->query->get('id', array());
	
		$recarrec = $request->query->get('recarrec', 0);
	
		
		if (!is_array($ids)) $ids = array ( $ids );
	
		foreach ($ids as $idrebut) {
			try {
				$rebut = $em->getRepository('FomentGestioBundle:Rebut')->find( $idrebut );
	
				if ($rebut == null) throw new \Exception('No s\'ha trobat el rebut '.$idrebut);
					
				if (!$rebut->enDomiciliacio()) throw new \Exception('El rebut '.$rebut->getNumFormat(). ' no es pot retornar');
				
				// Crear correcció
				$importcorreccio = $rebut->getImport() + $recarrec;
				$nouconcepte = UtilsController::CONCEPTE_RECARREC_RETORNAT.' '.number_format($recarrec, 2, ',', '.');
				$this->correccioRebut($rebut, $importcorreccio, $nouconcepte);
				
				$rebut->setTipuspagament(UtilsController::INDEX_FINES_RETORNAT);
				$rebut->setDataretornat(new \DateTime());
				$rebut->setDatapagament(null);
				$rebut->setDatamodificacio(new \DateTime());
				
				$em->flush();
	
				$this->get('session')->getFlashBag()->add('notice',	'Rebut retornat correctament');
					
			} catch (\Exception $e) {
				$this->get('session')->getFlashBag()->add('error', $e->getMessage());
			}
		}
	
		$request->query->remove('id');
		// Cerca mostra retornats 
		$request->query->set('retornats', true);
		$request->query->set('tipus', UtilsController::INDEX_FINES_RETORNAT);
		$request->query->set('sort', 'r.dataretornat');
		$request->query->set('direction', 'desc');
		
		return $this->redirect($this->generateUrl('foment_gestio_rebuts', $request->query->all()));
	}
	
	/* Veure informació i gestionar caixa periodes. Rebuts generals */
	public function infoseccionsAction(Request $request)
	{
		return $this->render('FomentGestioBundle:Rebuts:infoseccions.html.twig', $this->arrayFacturacionsPageParams($request));
	}
	
	/* AJAX. Veure informació seccions acumulats*/
	public function infoseccionscontentAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		$current = $request->query->get('current', date('Y'));
		$semestre = $request->query->get('semestre', 0);
		
		$selectedPeriodes = $this->getPeriodesSeleccionats($current, $semestre);

		$infoseccions = $this->infoSeccionsQuotes($selectedPeriodes);
	
		$strPeriodes = array();
		foreach ($selectedPeriodes as $periode) {
			$strPeriodes[] = $periode->getTitol();
		}
		
		return $this->render('FomentGestioBundle:Rebuts:infoseccionscontent.html.twig', 
				array('current' => $current, 'semestre' => $semestre, 'periodes' => $selectedPeriodes, 'subtitol' => implode(", ", $strPeriodes), 'infoseccions' => $infoseccions ));
	
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
		$dataini = \DateTime::createFromFormat('Y-m-d', $current."-01-01");
		$datafi = \DateTime::createFromFormat('Y-m-d', $current."-12-31");
		
		// Llista de les seccions per crar el menú que permet carregar les dades de cadascuna
		$strQuery = "SELECT s FROM Foment\GestioBundle\Entity\Seccio s WHERE 
									s.semestral = 0 AND s.databaixa IS NULL AND s.dataentrada <= :datafi 
									ORDER BY s.nom ";
		$query = $em->createQuery($strQuery);
		$query->setParameter('datafi', $datafi);
		
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
	
	/* AJAX. Veure informació seccio concreta */
	/*public function infosecciodetallAction(Request $request)
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
				$this->get('session')->getFlashBag()->add('notice', 'Les dades encara no estan disponibles');
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
						
						
						$concepte = UtilsController::concepteMembreSeccioRebut($membre, $current);
						
						//$atributs = array();
						//if ($membre->getSoci()->esJuvenil()) $atributs[] = 'juvenil';
						//if ($membre->getSoci()->getSocirebut() != null && $membre->getSoci()->getSocirebut()->getDescomptefamilia()) $atributs[] = 'des. fam.';
						//if (count($atributs) > 0) $nom .= ' <i>('.implode(', ', $atributs).')</i>  <br/>'.$concepte; 
						if (trim($concepte) != '') $nom .= ' <i>('.trim($concepte).')</i>';
						
						
						$seccionsmembresperiodes[$seccioId]['membres'][$sociId]['nom'] = $nom;
						$seccionsmembresperiodes[$seccioId]['membres'][$sociId]['numsoci'] = $membre->getSoci()->getNumsoci();
						
						$seccionsmembresperiodes[$seccioId]['membres'][$sociId]['periodes'][$periode->getId()] = $dadesMembrePeriode;
						
					} catch (\Exception $e) {
						$smsError = $e->getMessage();
						if (!in_array($smsError, $errors)) { 
							$errors[] = $smsError;
						}
					}
				}
			}
		
			foreach ($errors as $error) $this->get('session')->getFlashBag()->add('error', $error);
		} else {
			$this->get('session')->getFlashBag()->add('error', 'No s\'ha trobat dades de la secció ' .$seccioid  );
		}
		
		return $this->render('FomentGestioBundle:Rebuts:infosecciodetall.html.twig',
				array('current' => $current, 'semestre' => $semestre, 'periodes' => $selectedPeriodes,
				'dades' => $seccionsmembresperiodes, 'listseccions' => $listSeccions));
		
	}
	*/
	
	/* Veure informació i gestionar caixa periodes. Rebuts generals */
	public function infoactivitatsAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		 
		$current = $request->query->get('current', date('Y'));

		$anysSelectable = $this->getAnysSelectable();
		 
		$form = $this->createFormBuilder()
		->add('selectoranys', 'choice', array(
				'required'  => true,
				'choices'   => $anysSelectable,
				'data'		=> $current
		))->getForm();
		 
		return $this->render('FomentGestioBundle:Rebuts:infoactivitats.html.twig', array('form' => $form->createView(), 'current' => $current ));
	}
	
	/* AJAX. Veure informació i gestionar caixa activitats */
	public function infoactivitatscontentAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
	
		//$em = $this->getDoctrine()->getManager();
		
		$current = $request->query->get('current', date('Y'));

		$activitatid = $request->query->get('activitat', 0); // Per defecte cap

		// Cercar activitats periode
		$dataini = \DateTime::createFromFormat('Y-m-d', $current."-01-01"); 
    	$datafi = \DateTime::createFromFormat('Y-m-d', $current."-12-31");
		
		// Llista de les seccions per crar el menú que permet carregar les dades de cadascuna
		$listActivitats = $this->queryActivitatsPeriode($dataini, $datafi);
	
		// Obtenir l'activitat seleccionada
		$activitatParticipants = array();
		$activitat = null;
		if ($activitatid > 0) {
			
			//$key = -1;
			for( $i=0; $i<count($listActivitats) && $activitat == null; $i++ ) {
				if ($listActivitats[$i]->getId() == $activitatid) {
					$activitat = $listActivitats[$i];
					//$key = $i;
				}
			}
			
			if ($activitat != null) {
				// Carregar dades participants activitat escollida
					
				//unset($listActivitats[$key]); // Treure l'activitat activa de la llista

				$errors = array();
				
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
				
				$activitatParticipants[$activitatid] = array('descripcio' => $activitat->getDescripcio().'. '.$activitat->getCurs(), 
						'subtitol' => $activitat->getTipus(), 'escurs' => $activitat->esAnual(),
						'facturaciorebuts' => 0, 'facturaciocobrada' => 0, 'facturaciopendent' => 0, 
						'facturacionsTotals' =>	$facturacionsTotalsArray, 
						'participantsactius' => $activitat->getTotalParticipants(), 'participants' => array(),
						'pagaments' => array()
				);				
				
				foreach ($activitat->getParticipantsSortedByCognom(true) as $index => $participant) {  // Tots inclús si han cancel·lat participació
					$persona = $participant->getPersona();
					
					
					$activitatParticipants[$activitatid]['participants'][$persona->getId()] = array(
						'index' => $index + 1, 	
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
						try {
							
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
							
						} catch (\Exception $e) {
							$smsError = $e->getMessage();
							if (!in_array($smsError, $errors)) {
								$errors[] = $smsError;
							}
						}
					}					
				}
				
				$docents = $activitat->getDocentsActius(); 
				
				if (count($docents) > 0 && count($facturacionsActives) > 0) {
					
					$pagamentsActivitat = array();
					
					$mesInici = $activitat->getDatainici()->format('m'); // Mes 01 a 12
					
					$mesFinal = $activitat->getDatafinal()->format('m'); // Mes 01 a 12
					$anyFinal = $activitat->getDatafinal()->format('Y');
					
					$mesosPagaments = array(); // Han d'estar entre l'inici i el final del curs
					$currentMes = $mesInici;
					$currentAny = $activitat->getDatainici()->format('Y');
					while ($currentAny < $anyFinal || ( $currentAny == $anyFinal && $currentMes <= $mesFinal ) ) {
						$mesosPagaments[] = array('any' => $currentAny, 'mes' => $currentMes);
						
						$currentMes++;
						
						if ($currentMes > 12) {
							$currentAny++;	
							$currentMes = 1;
						}
					}
					
					$mesosFacturacions = array(); // Han d'estar entre l'inici i el final del curs
					$graellaPagamentMesFacturacions = array();
					$totalsDocencia = array();
					foreach ($facturacionsActives as $facturacio) {
						// Les facturacions haurien d'estar ordenades. Comprovació des de creació de facturacions
						$mesosFacturacions[] = array('facturacio'=> $facturacio->getId(), 
													'anyfacturacio' => $facturacio->getDatafacturacio()->format('Y'),
													'mesfacturacio' => $facturacio->getDatafacturacio()->format('m')
													
						);
						
						$graellaPagamentMesFacturacions[$facturacio->getId()] = false;// Cada més té una graella com aquesta per cada facturació
						$totalsDocencia[$facturacio->getId()] = 0;
					}
					
					$arrDocents = array();
					foreach ($docents as $docent) {
						$arrDocents[] = $docent->getProveidor()->getRaosocial();
					}
					$pagamentsActivitat['professors'] = array('titol' => implode(',',$arrDocents), 'totals' => $totalsDocencia);
					
					setlocale(LC_TIME, 'ca_ES', 'Catalan_Spain', 'Catalan');
					foreach ($mesosPagaments as $mes) {
						
						$anyMes = sprintf('%s-%02s', $mes['any'], $mes['mes']); 
						
						$currentAnyMes = \DateTime::createFromFormat('Y-m-d', $anyMes.'-01');
						
						//$mesText =  $currentAnyMes->format('F \d\e Y');
						//$mesText = date("F \de Y", $currentAnyMes->format('U'));
						
						$mesText = utf8_encode(strftime("%B de %Y", $currentAnyMes->format('U')));
						
						foreach ($docents as $c => $docent) {
							
							if (count($docents) > 1) {
								if ($c == 0) $mesText .= ' <span class="nom-professor">'.$docent->getProveidor()->getRaosocial().'</span>';							
								else $mesText = ' <span class="nom-professor">'.$docent->getProveidor()->getRaosocial().'</span>';
							}
							$pagamentsActivitat[$anyMes][$docent->getId()] = array( 'anymespagament' => $mesText, 
																	'datapagament' => urlencode($currentAnyMes->format('t/m/Y')),  // 't' => últim dia del mes
																	'concepte' => 'Liquidació '.$docent->getProveidor()->getRaosocial().
																					' '.$currentAnyMes->format('m/Y'). ' '.$activitat->getDescripcio(),
																	'import' => floor($docent->getImport()/count($mesosPagaments)),
																	'professor' =>  $docent->getProveidor(),
									 								'graellapagaments' => $graellaPagamentMesFacturacions,
																	'liquidacions' => array() );
							
							if (!isset($mesosFacturacions[0])) {
								$errors[] = 'Mes de '.$mesText.' fora dels periodes de facturació ';
								continue;
							}
							
							if ( count($mesosFacturacions) > 1 ) { // Sinó és així estem a l'última facturació 
								$anyMesCandidat = sprintf('%s-%02s', $mesosFacturacions[1]['anyfacturacio'], $mesosFacturacions[1]['mesfacturacio']);
								if ($anyMes >= $anyMesCandidat) array_shift($mesosFacturacions);
							}
							$facturacioMesPagament = $mesosFacturacions[0]['facturacio'];
							if ( isset ($pagamentsActivitat[$anyMes][$docent->getId()]['graellapagaments'][$facturacioMesPagament]) ) {
								$pagamentsActivitat[$anyMes][$docent->getId()]['graellapagaments'][$facturacioMesPagament] = true;
								
								$currentLiq = $docent->getPagamentsMesAny($mes['any'], $mes['mes']);
								$pagamentsActivitat[$anyMes][$docent->getId()]['liquidacions'] = $currentLiq;
								foreach ($currentLiq as $liq) {
									$pagamentsActivitat['professors']['totals'][$facturacioMesPagament] += $liq->getImport();
									$activitatParticipants[$activitatid]['facturacionsTotals'][$facturacioMesPagament]['totalfacturaciocurs'] -= $liq->getImport();
								}
								
								
							}
						}
					}
						
					$activitatParticipants[$activitatid]['pagaments'] = $pagamentsActivitat;
					
				}
				
				foreach ($errors as $error) $this->get('session')->getFlashBag()->add('error', $error);
				
			} else {
				$this->get('session')->getFlashBag()->add('error', 'No s\'ha trobat dades del curs o taller ' .$activitatid  );
			}
				
		}
		
		return $this->render('FomentGestioBundle:Rebuts:infoactivitatscontent.html.twig',
				array('current' => $current, 'currentactivitat' => $activitatid, 'listactivitats' => $listActivitats, 'dades' => $activitatParticipants));
	}
	
	public function pagamentproveidorsAction(Request $request)
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
				
				$numrebut = $this->getMaxRebutNumAnySeccio($current) + 1;

				if ($membre == null) $this->get('session')->getFlashBag()->add('error',	'El soci no pertany a la secció '.$seccio->getNom()); 
				else {
					//$deutor = $membre->getSoci();
					if ($tipus == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL) {  // Seccions no semestrals
						// posar el número de mes a la facturació no semestral
						$rebut = $this->generarRebutSeccio($membre, $dataemissio, $numrebut); // Ja està persistit
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
					$numrebut++;
					$rebut = $this->generarRebutActivitat($facturacio, $participant, $numrebut); // Ja està persistit
				} else {
					$deutor = $em->getRepository('FomentGestioBundle:Persona')->find($idpersona);
					
					$dataemissio = new \DateTime();
					$numrebut = $this->getMaxRebutNumAnyActivitat($dataemissio->format('Y'));
					$numrebut++;
					//$rebut = new Rebut($deutor, $dataemissio, $numrebut, $periode, $seccio );
					$rebut = new Rebut($deutor, $dataemissio, $numrebut, false, null);
						
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

				if ($rebut->esSeccio() == true && $rebut->getTipusrebut() == UtilsController::TIPUS_SECCIO) { // Validacions rebut Seccions semestrals
					// Validacions. 
					if ($rebut->getPeriodenf() == null && $rebut->getFacturacio() == null)
						throw new \Exception('Cal indicar la facturació del rebut' );
						
					$periode = $rebut->getPeriodenf();
					if ($periode == null) $periode = $rebut->getFacturacio()->getPeriode();
					// Validar si la persona ja té rebut per aquest curs/facturacio
					if ($rebut->getId() == 0) {
						$existent = $rebut->getDeutor()->getRebutPeriode($periode);
						if ($existent != null) 	throw new \Exception('Aquesta persona ja té un rebut de la secció per al periode indicat: '.$existent->getNumFormat() );
					}
				}
				
				
				if ($rebut->esSeccio() == true && $rebut->getTipusrebut() == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL) { // Validacions rebut Seccions no semestrals
					// Validacions. Si es secció no semestral el tipus finestreta
					if ($rebut->getTipuspagament() != UtilsController::INDEX_FINESTRETA)
						throw new \Exception('El pagament de les seccions no semestrals ha de ser finestreta' );
							
					if ($rebut->getPeriodenf() != null)
						throw new \Exception('Les seccions no semestrals no s\'assignen a cap periode o semestre' );
								
					if ($rebut->getFacturacio() != null)
						throw new \Exception('Les seccions no semestrals no entren a les facturacions normals' );
						
				}
				
				if ($rebut->getDataretornat() != null && $rebut->getTipuspagament() == UtilsController::INDEX_DOMICILIACIO) {
					throw new \Exception('La data de retornat ha d\'anar acompanyada del pagament per finestreta corresponent' );
				}
				
				if ($rebut->getDataretornat() != null) $rebut->setTipuspagament(UtilsController::INDEX_FINES_RETORNAT);
				else $rebut->setTipuspagament(UtilsController::INDEX_FINESTRETA);
				
				// ninguna data: pagat, retornat o baixa abans que emissió
				if ($rebut->getDatapagament() != null && $rebut->getDatapagament() < $rebut->getDataemissio()) throw new \Exception('La data de pagament no pot ser anterior a la data d\'emissió' );
				if ($rebut->getDataretornat()!= null && $rebut->getDataretornat() < $rebut->getDataemissio()) throw new \Exception('La data de retornat no pot ser anterior a la data d\'emissió' );
				// La data de baixa si pot ser posterior, es poden anul·lar rebuts futurs
				//if ($rebut->getDatabaixa()!= null && $rebut->getDatabaixa() < $rebut->getDataemissio()) throw new \Exception('La data de baixa no pot ser anterior a la data d\'emissió' );
				
				
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
				
				$this->get('session')->getFlashBag()->add('notice',	'El rebut s\'ha desat correctament');
				$response = $this->renderView('FomentGestioBundle:Rebuts:rebut.html.twig',
						array('form' => $form->createView(), 'rebut' => $rebut));
				
				// Ok, retorn form sms ok
			} catch (\Exception $e) {
				// Ko, mostra form amb errors
				$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
				$response = $this->renderView('FomentGestioBundle:Rebuts:rebut.html.twig',
						array('form' => $form->createView(), 'rebut' => $rebut));
			}
				
				
		} else {
	
			// GET mostrar form
			$response = $this->renderView('FomentGestioBundle:Rebuts:rebut.html.twig',
						array('form' => $form->createView(), 'rebut' => $rebut));
			
		}
		return new Response($response);
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
		$semestre = $request->query->get('semestre', 0); // els 2 per defecte
		
		if ($request->getMethod() == 'POST') {
			$this->get('session')->getFlashBag()->add('error',	'Opció incorrecte');
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
	
						$this->get('session')->getFlashBag()->add('notice',	'Rebuts afegits correctament');
					} catch (\Exception $e) {
						$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
					}
	
					// Prevent posting again F5
					//return $this->redirect($this->generateUrl('foment_gestio_facturacions', array('current' => $current, 'semestre' => $semestre)));
						
					break;
				case 'remove':  // Esborrar periode
					$periodeid = $request->query->get('periode', 0);
					try {
						$this->esborrarPeriodeFacturacio($periodeid);
						$this->get('session')->getFlashBag()->add('notice',	'Rebuts esborrats correctament');
					} catch (\Exception $e) {
						$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
					}
						
					//return $this->redirect($this->generateUrl('foment_gestio_facturacions', array('current' => $current, 'semestre' => $semestre)));
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
						$this->get('session')->getFlashBag()->add('notice',	'Els rebuts pendents s\'han afegit a la facturació '.$num.' correctament');
					} catch (\Exception $e) {
						$this->get('session')->getFlashBag()->add('error', $e->getMessage());
					}
					break;
				default:  // Altres
					$this->get('session')->getFlashBag()->add('error',	'Acció incorrecte');
					break;
			}
		}
			
		$selectedPeriodes = null;
		if ($semestre == 0) $selectedPeriodes = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $current));
		else $selectedPeriodes = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => $current, 'semestre' => $semestre));
	
		if (count($selectedPeriodes) <= 0) {
			$this->get('session')->getFlashBag()->add('notice',	'Aquestes dades encara no estan disponibles');
		}
		
		return $this->render('FomentGestioBundle:Rebuts:gestiofacturacionscontent.html.twig',
				array('current' => $current, 'semestre' => $semestre, 'periodes' => $selectedPeriodes));
	}
	
	/* Veure informació i gestionar caixa periodes. Rebuts generals */
	public function gestiofacturacionsAction(Request $request)
	{
		return $this->render('FomentGestioBundle:Rebuts:gestiofacturacions.html.twig', $this->arrayFacturacionsPageParams($request));
    }

    
    private function arrayFacturacionsPageParams(Request $request) {
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
    	
    	$params = array('form' => $form->createView(), 'periodes' => $selectedPeriodes, 'current' => $current, 'semestre' => $semestre );
    	return $params;
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
    
    	$numrebut = $this->getMaxRebutNumAnySeccio($periode->getAnyperiode()); // Max
    
    	$current = new \DateTime();
    	$dataemissio = $periode->getDatainici();  // Inici periode o posterior
    	if ($current > $periode->getDatainici()) $dataemissio = $current;
    	$socipagarebut = null; // Soci agrupa rebuts per pagar
    	$rebut = null;
    	foreach ($membres as $membre) {

    		if ($membre->getSeccio()->getSemestral() == true) {
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
			
    	$current = new \DateTime();
   		$datafacturacio = $periode->getDatainici();  // Inici periode o posterior
    	if ($current > $periode->getDatainici()) $datafacturacio = $current;
    	
    	
    	$desc = $periode->getAnyperiode().' semestre '.$periode->getSemestre();
		$facturacio = new Facturacio($periode, $num, UtilsController::INDEX_DOMICILIACIO, $desc, $datafacturacio); // Facturació periode (seccions)
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
