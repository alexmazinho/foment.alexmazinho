<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormError;
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
use Foment\GestioBundle\Entity\Docencia;
use Foment\GestioBundle\Form\FormSoci;
use Foment\GestioBundle\Form\FormPersona;
use Foment\GestioBundle\Form\FormSeccio;
use Foment\GestioBundle\Form\FormJunta;
use Foment\GestioBundle\Form\FormActivitatPuntual;
use Foment\GestioBundle\Form\FormActivitatAnual;
use Foment\GestioBundle\Entity\AuxMunicipi;
use Foment\GestioBundle\Classes\TcpdfBridge;
use Foment\GestioBundle\Entity\Rebut;
use Foment\GestioBundle\Entity\RebutDetall;
use Foment\GestioBundle\Entity\Imatge;
use Foment\GestioBundle\Entity\Facturacio;


class PagesController extends BaseController
{
    public function indexAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}

    	//$session = $request->getSession();
    	try {
    		//echo $session->get(SecurityContextInterface::USERNAME);
    		//echo "username";
    		//throw new \Exception('errrrrror');
    		//throw $this->createNotFoundException('The product does not exist');
    		
    	} catch (\Exception $e) {
			//$this->logEntryAuth('IMPORT CSV KO', $e->getMessage());
				
    		//echo "no username " .$e->getMessage();
    	}
    	
    	
    	return $this->render('FomentGestioBundle:Pages:index.html.twig', array());
    }

    public function comunicacionsAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$fs = new Filesystem();
    	$ruta = __DIR__.UtilsController::PATH_TO_FILES.UtilsController::PATH_REL_TO_DOMICILIACIONS_FILES;

    	
    	// Comparació dates descendent
    	$sort = function (\SplFileInfo $a, \SplFileInfo $b)
    	{
    		return strcmp($b->getFilename(), $a->getFilename());
    	};
    	
    	$domiciliacions = array();
    	if ($fs->exists($ruta)) {
    		$finder = new Finder();
    		$finder->files()->in($ruta);
    		$finder->sort($sort);
    		
    		foreach ($finder as $i => $file) { 
    			$domiciliacions[$i] = array(
    					'path' => urlencode(UtilsController::PATH_REL_TO_DOMICILIACIONS_FILES.$file->getRelativePathname()), 
    					'nom' => $file->getRelativePathname() );
    		}
    	}
    	
    	$ruta = __DIR__.UtilsController::PATH_TO_FILES.UtilsController::PATH_REL_TO_DECLARACIONS_FILES;
    	$declaracions = array();
    	if ($fs->exists($ruta)) {
    		$finder = new Finder();
    		$finder->files()->in($ruta);
    		$finder->sort($sort);
    		 
    		foreach ($finder as $i => $file) {
    			$declaracions[$i] = array(
    					'path' => urlencode(UtilsController::PATH_REL_TO_DECLARACIONS_FILES.$file->getRelativePathname()), 
    					'nom' => $file->getRelativePathname() );
    		}
    	}
    	
    	$anysSelectable = $this->getAnysSelectableToNow();
    	
    	$form = $this->createFormBuilder()
    	->add('facturacions', 'entity', array(
    			'error_bubbling'	=> true,
    			'class' => 'FomentGestioBundle:Facturacio',
    			'query_builder' => function(EntityRepository $er) {
    				return $er->createQueryBuilder('f')
    				->where('f.tipuspagament = :tipuspagament')
    				->setParameter('tipuspagament', UtilsController::INDEX_DOMICILIACIO)
    				->orderBy('f.id', 'DESC');
    			},
    			'property' 			=> 'descripcio',
    			'multiple' 			=> false,
    			'required'  		=> true,
    	))
    	->add('selectoranys', 'choice', array(
    			'required'  => true,
    			'choices'   => $anysSelectable,
    			'data'		=> date('Y') ))
    	->add('telefon', 'integer', array(
    			'required'  => true,
    	))
    	->add('nom', 'text', array(
    			'required'  => true,
    	))
    	->add('justificant', 'integer', array(
    			'required'  => true,
    	))
    	->getForm();
    	 
    	return $this->render('FomentGestioBundle:Rebuts:comunicacions.html.twig', 
    				array('form' => $form->createView(), 'domiciliacions' => $domiciliacions, 'declaracions' => $declaracions));
    }
    
    public function cercapersonesAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
		
    	$page = $request->query->get('page', 1);
    	
    	$queryparams = $this->queryPersones($request);
    	
    	/* Si $p == true (pendents vist i plua) i $s == false (socis i no socis. Cal revisar resultat de la $query a ma */
    	 
    	// Form
    	
    	
    	$defaultData = array('sexehome' => $queryparams['h'], 'sexedona' => $queryparams['d'],
    			'nom' => $queryparams['nom'], 'cognoms' => $queryparams['cognoms'], 'dni' => $queryparams['dni'], 'socis' => $queryparams['s'],  
    			'simail' => $queryparams['simail'], 'nomail' => $queryparams['nomail'], 'mail' => $queryparams['mail'],
    			'cercaactivitats' => implode(",", $queryparams['activitats']), 'seccions' => $queryparams['seccions']);
    
    	error_log($queryparams['s']. ' '. $defaultData['socis']);
    	
    	if (isset($queryparams['nini']) and $queryparams['nini'] > 0)  $defaultData['numini'] = $queryparams['nini'];
    	if (isset($queryparams['nfi']) and $queryparams['nfi'] > 0)  {
    		$defaultData['numfi'] = $queryparams['nfi'];
    		$defaultData['numficheck'] = true;
    	} else {
    		$defaultData['numficheck'] = false;
    	}
    	if (isset($queryparams['dini']) and $queryparams['dini'] != '')  $defaultData['datanaixementini'] = $queryparams['dini'];
    	if (isset($queryparams['dfi']) and $queryparams['dfi'] != '')  $defaultData['datanaixementfi'] = $queryparams['dfi'];
    
    	
    	$form = $this->createFormBuilder($defaultData)
    	->add('numini', 'integer', array('required' => false))
    	->add('numficheck', 'checkbox', array('required' => false))
    	->add('numfi', 'integer', array('required' => false, 'read_only' => ($defaultData['numficheck'] == false) ) )
    	->add('nom', 'text', array('required' => false))
    	->add('cognoms', 'text', array('required' => false))
    	->add('dni', 'text', array('required' => false))
    	->add('datanaixementini', 'text', array(
    			//'read_only' 	=> true,
    	))
    	->add('datanaixementfi', 'text', array(
    			//'read_only' 	=> true,
    	))
    	->add('sexehome', 'checkbox')
    	->add('sexedona', 'checkbox')
    	->add('simail', 'checkbox')
    	->add('nomail', 'checkbox')
    	->add('mail', 'text', array('required' => false,  'read_only' => ($defaultData['simail'] == false)))
    	->add('seccions', 'entity', array(
    			'error_bubbling'	=> true,
    			'class' => 'FomentGestioBundle:Seccio',
    			'property' 			=> 'info',
    			'multiple' 			=> true,
    			'required'  		=> false,
    	))
    	->add('cercaactivitats', 'hidden', array('required'	=> false ))
    	->add('socis', 'choice', array(
    		'required'  => true,
    		'choices'   => array(
    					0 => 'socis', 
    					1 => 'baixas', 
    					2 => 'no socis', 
    					/*'3' => 'tothom',
    					4 => 's/ vip'*/),
    		'data' 		=> $defaultData['socis'],
    		'mapped'	=> false,
    		'expanded' 	=> true,
    		'multiple'	=> false
    	))
    	->getForm();
    	
    	//unset($queryparams['activitats']); // Per ajax
    	//unset($queryparams['seccions']);
    
    	$paginator  = $this->get('knp_paginator');
    	$persones = $paginator->paginate(
    			$queryparams['query'],
    			$page,
    			UtilsController::DEFAULT_PERPAGE_WITHFORM //limit per page
    	);
    	
    	return $this->render('FomentGestioBundle:Pages:cercapersones.html.twig', array('form' => $form->createView(), 'persones' => $persones, 'queryparams' => $queryparams));
    }
    
    /* Veure / actualitzar dades personals (soci o no) existents (amb id) */
    public function veuredadespersonalsAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$id = $request->query->get('id', 0);
    	$tab = $request->query->get('tab', UtilsController::TAB_SECCIONS);
    	$essoci = true;
    	if ($request->query->has('soci') && $request->query->get('soci') == 0) $essoci = false;
    
    	$em = $this->getDoctrine()->getManager();
    	 
    	$persona = null;
    	if ($essoci) {
    		$persona = $em->getRepository('FomentGestioBundle:Soci')->find($id);
    	}
    	else {
    		$persona = $em->getRepository('FomentGestioBundle:Persona')->find($id);
    	}
    
    	if ($persona == null) {
    		$this->get('session')->getFlashBag()->add('error',	'Persona no trobada '.$id );
    		return $this->redirect( $this->generateUrl('foment_gestio_cercapersones') );
    	}
    	 
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'dataemissio', 'direction' => 'desc'));
    	
    	$rebutsdetallpaginate = $this->getDetallRebutsPersona($queryparams, $persona);
    	
    	if (!$essoci) {
    		$form = $this->createForm(new FormPersona(), $persona);
    		return $this->render('FomentGestioBundle:Pages:persona.html.twig',
    				array('form' => $form->createView(), 'persona' => $persona,
    					'rebutsdetall' => $rebutsdetallpaginate, 'queryparams' => $queryparams, 'tab' => $tab ));
    	}
    
    	$form = $this->createForm(new FormSoci(), $persona);
    	return $this->render('FomentGestioBundle:Pages:soci.html.twig',
    			array('form' => $form->createView(), 'persona' => $persona,
    					'rebutsdetall' => $rebutsdetallpaginate, 'queryparams' => $queryparams, 'tab' => $tab ));
    }
    
    private function getDetallRebutsPersona($queryparams, $persona) {
    	$rebutsDetallArray = $this->ordenarArrayObjectes($persona->getRebutDetalls(), $queryparams);
    	$paginator  = $this->get('knp_paginator');
    	
    	$rebutsdetallpaginate = $paginator->paginate(
    			$rebutsDetallArray,
    			$queryparams['page'],
    			$queryparams['perpage'] //limit per page
    	);
    	unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
    	//$rebutsdetallpaginate->setParam('id', $id); // Add extra request params. Seccio id
    	//$rebutsdetallpaginate->setParam('perpage', $queryparams['perpage']);
    	return $rebutsdetallpaginate;
    }
    
    
    /* Desar dades personals no soci */
    public function desarpersonaAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	if ($request->getMethod() == 'GET') 
    			return $this->forward('FomentGestioBundle:Pages:novapersona');
    	
    	$this->get('session')->getFlashBag()->clear();
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	//$id = $request->request->get('id', 0);
    	
    	$data = $request->request->get('persona');
    	
    	$id = (isset($data['id'])?$data['id']:0);
    	$tab = (isset($data['tab'])?$data['tab']:UtilsController::TAB_SECCIONS);
    	$stractivitats = (isset($data['activitatstmp'])?$data['activitatstmp']:'');
    	$activitatsids = array();
    	if ($stractivitats != '') $activitatsids = explode(',',$stractivitats); // array ids activitats llista
    	
    	
    	if ($id > 0) {
    		$em = $this->getDoctrine()->getManager();
    		$persona = $em->getRepository('FomentGestioBundle:Persona')->find($id);
    	} else {
	    	$persona = new Persona();
    	}
    	
    	$form = $this->createForm(new FormPersona(), $persona);
    	 
    	$form->handleRequest($request);
    	
    	if ($form->isValid()) {
    		$activitatsActualsIds = $persona->getActivitatsIds();
    		foreach ($activitatsids as $actid)  {
    			if (!in_array($actid, $activitatsActualsIds)) {
    				// No està nova activitat
    				$this->inscriureParticipant($actid, $persona);
    			} else {
    				// Manté la secció
    				unset($activitatsActualsIds[$actid]);
    			}
    		}
    		foreach ($activitatsActualsIds as $actid)  {  // Per esborrar les que queden
    			$this->esborrarParticipant($actid, $persona);
    		}
    		
    		$persona->setDatamodificacio(new \DateTime());
    		
    		if ($persona->getId() == 0) $em->persist($persona);
    		
    		$em->flush();
    		
    		$this->get('session')->getFlashBag()->add('notice',	'Dades personals desades correctament');
    		
    		return $this->redirect($this->generateUrl('foment_gestio_veuredadespersonals', 
    					array( 'id' => $persona->getId(), 'soci' => false, 'tab' => $tab )));
    	} else {
    		$this->get('session')->getFlashBag()->add('error',	'Cal revisar les dades del formulari');    		
    	}
    	
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'dataemissio', 'direction' => 'desc'));
    	
    	$rebutsdetallpaginate = $this->getDetallRebutsPersona($queryparams, $persona);
    	
    	return $this->render('FomentGestioBundle:Pages:persona.html.twig',
    			array('form' => $form->createView(), 'persona' => $persona,
    					'rebutsdetall' => $rebutsdetallpaginate, 'queryparams' => $queryparams, 'tab' => $tab ));
    }
    
    /* Desar dades personals soci */
    public function desarsociAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	if ($request->getMethod() == 'GET')
    		return $this->forward('FomentGestioBundle:Pages:nousoci');
    	 
    	//$this->get('session')->getFlashBag()->clear();
    	 
    	$em = $this->getDoctrine()->getManager();
    	 
    	$data = $request->request->get('soci');
    	
    	$id = (isset($data['id'])?$data['id']:0);
    	$tab = (isset($data['tab'])?$data['tab']:UtilsController::TAB_SECCIONS);
    	
    	$em = $this->getDoctrine()->getManager();
    	$soci = $em->getRepository('FomentGestioBundle:Soci')->find($id);
    	
    	
    	if ($soci == null) {
    		$soci = new Soci();
    	} else {
    		$pagamentfraccionatOriginal = $soci->getPagamentfraccionat();
    	}
        	
    	$form = $this->createForm(new FormSoci(), $soci);
    	$form->handleRequest($request);
    	if ($form->isValid() && $this->validacionsSociDadesPersonals($form, $soci) == true) { // Validacions camps persona només per a socis
    		// Membres 
    		try {
    			// Deudor rebut
    			if ($data['deudorrebuts'] == 1) $soci->setSocirebut($soci);
    			
    			if ($data['deudorrebuts'] == 2 || $data['pagamentfinestreta'] == UtilsController::INDEX_FINESTRETA ) { // Rebuts a càrrec d'altri
    				$soci->setCompte(null);
    			} else {
    				// 1 -> a càrrec propi, si compte null -> pagament finestreta
    				$soci->setSocirebut($soci);
    				if ($soci->getCompte() != null) {
    					$compte = $soci->getCompte();
    					
    					if ($compte->getTitular() == '' && $compte->getAgencia() == '' &&
    						$compte->getBanc() == '' && $compte->getDc() == '' && $compte->getNumcompte() == '') {
    						// Compte no informat
    						$soci->setCompte(null);
    					} else {
    						/*if ($compte->getId() <= 0) {
    							$compte->setId($soci->getNum());
    						}*/
    						// Compte totalment informat sinó error
    						
	    					$errorStr = $this->validarCompteCorrent($form, $compte);
						    if ($errorStr != "") {
						    	$tab = 3;
						    	throw new \Exception($errorStr);
						    }
    						
    					}
    				}
    			}
    			if ($soci->getSocirebut() == null) {
    				$tab = 3;
    				throw new \Exception('Cal indicar el soci que es farà càrrec dels rebuts');
    			}
    
    			// Vigilar canvis pagament fraccionata => anual si existeix la primera facturació però no la segona
    			// Soci podria paga només la meitat de la quota
    			$periode1 = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => date('Y'), 'semestre' => 1));
    			$periode2 = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => date('Y'), 'semestre' => 2));
    			 
    			if ($periode1 != null && $periode2 == null && $pagamentfraccionatOriginal == true && $soci->getPagamentfraccionat() ==false) {
    				$tab = 3;
    				throw new \Exception('No es pot activar el pagament anual fins que es generi la facturació del 2n semestre ');
    			}
    			
    			// Avaladors
    			$avaladors = $soci->getAvaladors();
    			$arrayAvaladorRemove = array();
    			$arrayAvaladorSubmit = array();
    			if ($data['avalador1'] != '') $arrayAvaladorSubmit[] = $data['avalador1'];
    			if ($data['avalador2'] != '') $arrayAvaladorSubmit[] = $data['avalador2'];
    			
    			foreach ($avaladors as $currAvaladors) {
    				if (in_array($currAvaladors->getId(), $arrayAvaladorSubmit )) {
    					// No fer res avalador ja existent
    					if ( isset($arrayAvaladorSubmit[0]) && $arrayAvaladorSubmit[0] == $currAvaladors->getId() ) unset($arrayAvaladorSubmit[0]);
    					if ( isset($arrayAvaladorSubmit[1]) && $arrayAvaladorSubmit[1] == $currAvaladors->getId() ) unset($arrayAvaladorSubmit[1]);
    				} else {
    					// Esborrar avalador;
    					$arrayAvaladorRemove[] = $currAvaladors; 
    				}
    			}
    			// Esborrar
    			foreach ($arrayAvaladorRemove as $currAvaladors) {
    				$soci->removeAvalador($currAvaladors);
    				$currAvaladors->removeAvalat($soci);
    			}
    			// Afegir
    			foreach ($arrayAvaladorSubmit as $nouAvaladorId) {  // Els que queden alta
    				$nouAvalador = $em->getRepository('FomentGestioBundle:Soci')->find($nouAvaladorId);
    				if ($nouAvalador != null) {
    					$soci->addAvalador($nouAvalador);
    					$nouAvalador->addAvalat($soci);
    				}
    			}	

    			if ($soci->getVistiplau() == true) $soci->setDatavistiplau(new \DateTime());
    			else $soci->setDatavistiplau(null);
    			
    			// Foto
    			if ($form->has('foto'))  {
    				$file = $form->get('foto')->getData();
    			
    				if ($file != null) {
    			
	    				if (!($file instanceof UploadedFile) or !is_object($file))  throw new \Exception('No s\'ha pogut carregar la foto');
	    					
	    				if (!$file->isValid()) throw new \Exception('No s\'ha pogut carregar la foto ('.$file->isValid().')'); // Codi d'error
	    			
	    				// Amb imagik
	    				// $uploaded = UtilsController::uploadAndScale($file, $soci->getNomCognoms(), 300, 200);
	    				// $foto = new Imatge($uploaded['path']);
	    				
	    				//
	    				$foto = new Imatge($file);
	    				$foto->upload($soci->getId()."_".$soci->getNomCognoms());
	    				$foto->setTitol("Foto carnet soci/a " . $soci->getNomCognoms() ." carregada en data ". date('d/m/Y'));
	    				$em->persist($foto);
	    				$soci->setFoto($foto);
    				}
    			}
    			
	    		// $data['membredeadded'] ==> Afegides
	    		// $data['seccionsremoved'] ==> esborrades
	    		$aux = (isset($data['activitatstmp'])?$data['activitatstmp']:'');
	    		
	    		$activitatsids = array();
	    		if ($aux != '') $activitatsids = explode(',',$aux); // array ids activitats llista
	    		$aux = (isset($data['membredeadded'])?$data['membredeadded']:'');
	    		$seccionsPerAfegir = array();
	    		if ($aux != '') $seccionsPerAfegir = explode(',',$aux);
	    		$aux = (isset($data['seccionsremoved'])?$data['seccionsremoved']:'');
	    		$seccionsPerEsborrar = array();
	    		if ($aux != '') $seccionsPerEsborrar = explode(',',$aux);
	    		
	    		foreach ($seccionsPerEsborrar as $secid)  {
	    			$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($secid);
	    			$this->esborrarMembre($seccio, $soci, date('Y'));
	    		}
	    		
	    		foreach ($seccionsPerAfegir as $secid)  {
	    			$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($secid);
	    			$this->inscriureMembre($seccio, $soci, date('Y')); // Crear rebuts si ja estan generats en el periode
	    		}
	    		
	    		$activitatsActualsIds = $soci->getActivitatsIds();
	    		foreach ($activitatsids as $actid)  {
	    			if (!in_array($actid, $activitatsActualsIds)) {
	    				
	    				// No està nova activitat
	    				$this->inscriureParticipant($actid, $soci);
	    			} else {
	    				// Manté la secció
	    				$key = array_search($actid, $activitatsActualsIds);
	    				unset($activitatsActualsIds[$key]);
	    			}
	    		}
	    		foreach ($activitatsActualsIds as $actid)  {  // Per esborrar les que queden
	    			$this->esborrarParticipant($actid, $soci);
	    		}
	    		
	    		$soci->setDatamodificacio(new \DateTime());
	    	
	    		if ($soci->getId() == 0) $em->persist($soci);
	    	
	    		$em->flush();
    	
    			$this->get('session')->getFlashBag()->add('notice',	'Dades del soci desades correctament');
    	
    			return $this->redirect($this->generateUrl('foment_gestio_veuredadespersonals',
    					array( 'id' => $soci->getId(), 'soci' => true, 'tab' => $tab )));
    		} catch (\Exception $e) {
    			$tab = 3;
    			$this->get('session')->getFlashBag()->clear();
    			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    		}
    	} else {
    		
    		$this->get('session')->getFlashBag()->clear();
    		
    		if ($data['deudorrebuts'] != 2 && $data['pagamentfinestreta'] != UtilsController::INDEX_FINESTRETA && !$form->get('compte')->isValid()) {
    			$tab = 3;
    			$compte = $soci->getCompte();
    			if ($compte->getTitular() == '') {
    				$form->get('compte')->get('titular')->addError(new FormError('informar titular'));
    			}
    			if ($compte->getBanc() == '') $form->get('compte')->get('banc')->addError(new FormError('arevisar la entitat'));
    			if ($compte->getAgencia() == '') $form->get('compte')->get('agencia')->addError(new FormError('raevisar agència'));
    			if ($compte->getDc() == '') $form->get('compte')->get('dc')->addError(new FormError('revisar dígits de control'));
    			if ($compte->getNumcompte() == '') $form->get('compte')->get('numcompte')->addError(new FormError('revisar el compte'));
    			if ($compte->getCompte20() == '') $form->get('compte')->get('iban')->addError(new FormError('revisar iban'));
    			$this->get('session')->getFlashBag()->add('error',	'El número de compte no és correcte');
    		} else {
    			$this->get('session')->getFlashBag()->add('error',	'Cal revisar les dades del formulari');
    		}
    	}
    	 
    	/*return $this->redirect($this->generateUrl('foment_gestio_veuredadespersonals',
    			array( 'id' => $soci->getId(), 'soci' => true, 'tab' => $tab )));*/
    	
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'dataemissio', 'direction' => 'desc'));
    	 
    	$rebutsdetallpaginate = $this->getDetallRebutsPersona($queryparams, $soci);
    	 
    	return $this->render('FomentGestioBundle:Pages:soci.html.twig',
    			array('form' => $form->createView(), 'persona' => $soci,
    					'rebutsdetall' => $rebutsdetallpaginate, 'queryparams' => $queryparams, 'tab' => $tab )); 
    }
    
    
    private function validarCompteCorrent($form, $compte) {
    	if ($compte->getTitular() == '') {
    		$form->get('compte')->get('titular')->addError(new FormError('informar titular'));
    		return 'Cal indicar el titular del compte';
    	}
    	
    	$errorCCC = false;
    	
    	$numcompte = str_pad($compte->getNumcompte(), 10, "0", STR_PAD_LEFT);
    	$numbanc = str_pad($compte->getBanc(), 4, "0", STR_PAD_LEFT);
    	$numagencia = str_pad($compte->getAgencia(), 4, "0", STR_PAD_LEFT);
    	$numdc = str_pad($compte->getDc(), 2, "0", STR_PAD_LEFT);
    	
    	$compte->setNumcompte($numcompte);
    	if (is_numeric($numcompte)) {
    		if ($numcompte < 0 || $numcompte > 9999999999) {
    			$errorCCC = true;
    			$form->get('compte')->get('numcompte')->addError(new FormError('revisar el número'));
    		}
    	} else {
    		$errorCCC = true;
    		$form->get('compte')->get('numcompte')->addError(new FormError('num. compte no és numèric'));
    	}
    	
    	$compte->setBanc($numbanc);
    	if (is_numeric($numbanc)) {
    		if ($numbanc < 0 || $numbanc > 9999) {
    			$errorCCC = true;
    			$form->get('compte')->get('banc')->addError(new FormError('revisar el banc'));
    		}
    	} else {
    		$errorCCC = true;
    		$form->get('compte')->get('banc')->addError(new FormError('banc no és numèric'));
    	}
    	
    	$compte->setAgencia($numagencia);
    	if (is_numeric($numagencia)) {
    		if ($numagencia < 0 || $numagencia > 9999) {
    			$errorCCC = true;
    			$form->get('compte')->get('agencia')->addError(new FormError('revisar el agencia'));
    		}
    	} else {
    		$errorCCC = true;
    		$form->get('compte')->get('agencia')->addError(new FormError('agència no és numèrica'));
    	}
    	
    	$compte->setDc($numdc);
    	if (is_numeric($numdc)) {
    		if ($numdc < 0 || $numdc > 99) {
    			$errorCCC = true;
    			$form->get('compte')->get('dc')->addError(new FormError('revisar dígits'));
    		}
    	} else {
    		$errorCCC = true;
    		$form->get('compte')->get('dc')->addError(new FormError('dígits no són numèrics'));
    	}
    	
    	// Dígits de control
    	if ($errorCCC == false) {
	    	$valores = array(1, 2, 4, 8, 5, 10, 9, 7, 3, 6);
	    	
	    	$controlCS = 0;
	    	$controlCC = 0;
	    	
	    	$strBancAgencia = $numbanc.$numagencia;
	    	$strCCC = $numcompte;
	    	
	    	//error_log($strBancAgencia."-".$strCCC);
	    	
	    	for ($i=0; $i<8; $i++) $controlCS += intval($strBancAgencia{$i}) * $valores[$i+2]; // Banc+Oficina
	    	   	
	    	$controlCS = 11 - ($controlCS % 11);
	    	if ($controlCS == 10) $controlCS = 1;
	    	if ($controlCS == 11) $controlCS = 0;
	    	 
	    	
	    	for ($i=0; $i<10; $i++) $controlCC += intval($strCCC{$i}) * $valores[$i];
	    	$controlCC = 11 - ($controlCC % 11);
	    	if ($controlCC == 10) $controlCC = 1;
	    	if ($controlCC == 11) $controlCC = 0;
	    	 
	    	$dcCalc = intval($controlCS.$controlCC);
	    			
	    	if ($dcCalc != $numdc) {
	    		$errorCCC = true;
	    		$form->get('compte')->get('dc')->addError(new FormError('càlcul dígits incorrecte'));
	    	} 
    	}    	
    	
    	if ($errorCCC == true) return 'El número de compte no és correcte';
    	return "";
    }
    
    
    
    public function baixaSociAction(Request $request)
    {
    	$id = $request->query->get('id', 0);
    	
    	$this->get('session')->getFlashBag()->clear();
    	
    	return $this->redirect($this->generateUrl('foment_gestio_novapersona', array( 'id' => $id)));
    }
    
    /* Mostrar form nova persona.
     * 	Sense id blank,
    * 	sense id amb dades persona GET (canvi soci nou -> persona)
    * 	o amb id de soci (canvi soci existent -> persona). Baixa soci  -->  Consolidat */
    public function novapersonaAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$em = $this->getDoctrine()->getManager();
    
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'dataemissio', 'direction' => 'desc'));
    	
    	$id = $request->query->get('id', 0);
    	$tab = $request->query->get('tab', UtilsController::TAB_ACTIVITATS);
    	
    	if ($id > 0) {
    		// Cercar soci i convertir soci de baixa
    		$em = $this->getDoctrine()->getManager();
    		$soci = $em->getRepository('FomentGestioBundle:Soci')->find($id);
    		try {
	    		// Actualitzar deutor rebuts
	    		if ($soci->esDeudorDelGrup()) {
	    			$socisacarrec = $soci->getSocisacarrec();
	    			if (count($socisacarrec) > 1) throw new \Exception('Aquest soci es fa càrrec dels rebuts d\'altres i no es pot esborrar. Cal assignar algú altre que se\'n faci càrrec' ); 
	    		} else {
	    			// Els rebuts del soci són a càrrec d'altri. Actualitzar, una persona paga els seus rebuts
	    			// A mes a finestreta
	    			$soci->setSocirebut($soci);
	    			$soci->setCompte(null);
	    		}
	    		
	    		// Donar de baixa de les seccions
	    		$seccionsPerEsborrar = $soci->getSeccionsSortedById();
	    		foreach ($seccionsPerEsborrar as $seccio)  {
	    			$this->esborrarMembre($seccio, $soci, date('Y')); 
	    		}
	    		// BAIXA SOCI
	    		$soci->setNum(null);
	    		$soci->setDatabaixa(new \DateTime('today'));
	    		$soci->setDatamodificacio(new \DateTime());
	    	} catch (\Exception $e) {
	    		$this->get('session')->getFlashBag()->clear();
	    		$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
	    		
	    		$form = $this->createForm(new FormSoci(), $soci);
	    		
	    		$rebutsdetallpaginate = $this->getDetallRebutsPersona($queryparams, $soci);
	    		
	    		$tab = 3;
	    		
	    		return $this->render('FomentGestioBundle:Pages:soci.html.twig',
	    				array('form' => $form->createView(), 'persona' => $soci,
	    						'rebutsdetall' => $rebutsdetallpaginate, 'queryparams' => $queryparams, 'tab' => $tab ));
	    	}    		
    		$persona = $soci;
    		// Fer persistent
    		$em->flush();
    	} else {
    		$datasoci = $request->query->get('soci', null);
    		// nova persona
    		$persona = new Persona();
    		if ($datasoci != null) {
    			// Carregar dades form
    			$persona = new Persona($datasoci);
    			 
    			// Activitats
    			$stractivitats = (isset($datasoci['activitatstmp'])?$datasoci['activitatstmp']:'');
    			$activitatsids = array();
    			if ($stractivitats != '') $activitatsids = explode(',',$stractivitats); // array ids activitats llista
    
    			foreach ($activitatsids as $actid)  {
    				$this->inscriureParticipant($actid, $persona);
    			}
    		}
    	}

    	$form = $this->createForm(new FormPersona(), $persona);
    
    	$rebutsdetallpaginate = $this->getDetallRebutsPersona($queryparams, $persona);
    	 
    	return $this->render('FomentGestioBundle:Pages:persona.html.twig',
    			array('form' => $form->createView(), 'persona' => $persona,
    					'rebutsdetall' => $rebutsdetallpaginate, 'queryparams' => $queryparams, 'tab' => $tab));
    }
        
    /* Mostrar form nou soci.
     * 	Sense id blank,
    * 	sense id amb dades de soci GET (canvi soci nou -> persona)
    * 	o amb id de persona (canvi persona existent -> soci). Alta soci */
    public function nousociAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'dataemissio', 'direction' => 'desc'));
    	
    	$id = $request->query->get('id', 0);
    	$tab = $request->query->get('tab', UtilsController::TAB_SECCIONS);
    	
    	$em = $this->getDoctrine()->getManager();

    	$form = null;
    	$persona = $em->getRepository('FomentGestioBundle:Persona')->find($id);
    	
    	if ($persona != null) {
    		// Cercar persona i convertir en soci
    		
    		if ($persona->esSoci()) $soci = $persona; // Existeix a la taula de socis i el regitre rol = 'S'
    		else {
    			$soci = new Soci($persona);
    			$em->persist($soci);
    		}
    		
    		$soci->setnum($this->getMaxNumSoci()); // Número nou
    		    		
    		$soci->setDatamodificacio(new \DateTime());
    		$soci->setDatabaixa(null);
    		
    		$form = $this->createForm(new FormSoci(), $soci);

    		// Per defecte ell com a soci
    		if ($soci->getSocirebut() == null) $soci->setSocirebut($soci);
    		else {
    			if (!$soci->getSocirebut()->esSociVigent()) $soci->setSocirebut($soci);
    		}	
    		
    		if ($this->validacionsSociDadesPersonals($form, $soci) == false) {
    			//$form = $this->createForm(new FormPersona(), $persona);
    			
    			//$rebutsdetallpaginate = $this->getDetallRebutsPersona($queryparams, $persona);
    			$this->get('session')->getFlashBag()->add('error',	'Cal informar la data de naixement, DNI i adreça ');
    			
    			return $this->redirect($this->generateUrl('foment_gestio_veuredadespersonals',
    					array( 'id' => $id, 'soci' => false, 'tab' => UtilsController::TAB_ACTIVITATS )));
    			
    		}
    			
    		if ($soci->getId() > 0)  {
    			//$em->persist($soci);
					
				// Desactivar generació automàtica identificar per la classe AUTO id     		
	    		$metadata = $em->getClassMetaData('FomentGestioBundle:Soci');

		    	//$metadata->setIdGenerator(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
	    		$metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
		    		
				// Canvi a Soci directament des de SQL. Doctrine no deixa
		    	$query = "UPDATE persones SET rol = 'S' WHERE id = ".$id;
		    	$em->getConnection()->exec( $query );

	    		//$em->refresh($persona);
		    		
		    	//$em->persist($soci);
		    		
		    		
	    	} else {
	    		$em->remove($persona);
	    		
	    	}  
		    $em->flush();
    		
		    return $this->redirect($this->generateUrl('foment_gestio_veuredadespersonals',
		    		array( 'id' => $soci->getId(), 'soci' => true, 'tab' => UtilsController::TAB_SECCIONS )));
    	} else {
    		$datapersona = $request->query->get('persona', null);
    	
    		// nou soci
    		$soci = new Soci();
    		
    		if ($datapersona != null) {
    			// Carregar dades form
    			$soci = new Soci($datapersona);
    			
    			// Activitats
    			$stractivitats = (isset($datapersona['activitatstmp'])?$datapersona['activitatstmp']:'');
    			$activitatsids = array();
    			if ($stractivitats != '') $activitatsids = explode(',',$stractivitats); // array ids activitats llista
    			
    			foreach ($activitatsids as $actid)  {
    				$this->inscriureParticipant($actid, $soci);
    			}
    		}
    		$soci->setnum($this->getMaxNumSoci());
    	}
    	 
    	if ($form == null) $form = $this->createForm(new FormSoci(), $soci);    	
    	 
    	$rebutsdetallpaginate = $this->getDetallRebutsPersona($queryparams, $soci);
    	
    	return $this->render('FomentGestioBundle:Pages:soci.html.twig',
    			array('form' => $form->createView(), 'persona' => $soci,
    					'rebutsdetall' => $rebutsdetallpaginate, 'queryparams' => $queryparams, 'tab' => $tab ));
    }
    
    private function validacionsSociDadesPersonals($form, $soci) {
    	// Validacions camps persona només per a socis
    	
    	if ($soci->getDatanaixement() == null) {
    		$error = new FormError("Data de naixement");
    		$form->get('datanaixement')->addError($error);
    		return false;
    	} 
    	
    	if ($soci->getDni() == null || $soci->getDni() == '') {
    		$error = new FormError("Indicar DNI");
    		$form->get('dni')->addError($error);
    		return false;
    	}
    	 
    	if ($soci->getAdreca() == null || $soci->getAdreca() == '') {
    		$error = new FormError("Adreça incompleta");
    		$form->get('adreca')->addError($error);
    		return false;
    	}
    	
    	if ($soci->getCp() == null || $soci->getCp() == '') {
    		$error = new FormError("Adreça incompleta");
    		$form->get('cp')->addError($error);
    		return false;
    	}
    	 
    	if ($soci->getPoblacio() == null || $soci->getPoblacio() == '') {
    		$error = new FormError("Adreça incompleta");
    		$form->get('poblacio')->addError($error);
    		return false;
    	}
    	if ($soci->getProvincia() == null || $soci->getProvincia() == '') {
    		$error = new FormError("Adreça incompleta");
    		$form->get('provincia')->addError($error);
    		return false;
    	}
    	
    	return true;
    }
    
    /* Veure / actualitzar seccions */
    public function seccionsAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$em = $this->getDoctrine()->getManager();
    	 
    	//$queryparams = array('perpage' => 999, 'page' => 1, 'sort' => 'id', 'direction' => 'asc', 'filtre' => '');
    	
    	$edicioQuotes = false;
    	
    	$queryparams = $this->queryTableSort($request, array( 'id' => 's.id', 'direction' => 'asc'));
    	
    	$anydades = date('Y');
    	$anyselect = $request->query->get('anydades', date('Y')); 
    	
    	$quotes = $this->queryQuotes($anyselect); // array quotes (minimitzar querys)
    	
    	$queryparams['quotes'] = $quotes;
    	// Comprovar si encara es poden canviar quotes. Amb els rebuts generats no s'hauria de poder
    	$queryparams['rebutsgenerats'] = ($this->rebutsCreatsAny($anyselect));
    	
    	$anysSelectable = $this->getAnysSelectable();
    	
    	$arraySeccions = $em->getRepository('FomentGestioBundle:Seccio')->findAll();
    	$queryparams['anydades'] = $anyselect;
    	$queryparams['anysSelectable'] = $anysSelectable;
    	 
    	$form = $this->createFormBuilder()
    			->add('quotes',	'collection', array(
    				'type' => new FormSeccio($queryparams),
    				//'type' => new FormQuotesSeccio($quotes),
    				'cascade_validation' => true,
    				'error_bubbling' => false,
    				'data' => $arraySeccions ))
    			->add('selectoranys', 'choice', array(
    				'required'  => true,
    				'choices'   => $anysSelectable,
    				'constraints' => array(
    						new NotBlank(array(	'message' => 'Cal indicar l\'any.' ) ),
    						new Type(array(
    								'type'    => 'integer',
    								'message' => 'Any incorrecte.'
    						) ),
    						new GreaterThanOrEqual(array( 'value' => $anydades, 'message' => 'No es poden modificar quotes passades' ) )
    				),
    				'attr' 		=> array('data-value-init' => $anyselect),
    				'data'		=> $anyselect ))
    			->add('filtre', 'text', array(     			// Camps formulari de filtre
   					'required' 	=> false,
   					'data'		=> $queryparams['filtre'],
   					'attr' 		=> array('class' => 'form-control filtre-text')))
				->add('midapagina', 'choice', array(
  					'required'  => true,
   					'choices'   => UtilsController::getPerPageOptions(),
   					'data'		=> $queryparams['perpage'],
   					'attr' 		=> array('class' => 'select-midapagina')
    			))->getForm();
				
    	if ($request->getMethod() == 'POST') {
    		// Bulk action -->  Actualitzar preus seccions
    		// $params = $request->request;
    		$edicioQuotes = true;
    		try {
    			$dataform = $request->request->get('form', null);    			

    			if ($dataform == null) throw new \Exception('Dades del formulari incorrectes');
    			
    			$form->handleRequest($request);

    			if ($form->isValid()) {
    				foreach ($form->get('quotes') as $formseccio) {
    					$this->postFormSeccioQuotes($formseccio); 
    				}
    	
    				$em->flush();
    	
    				$this->get('session')->getFlashBag()->add('notice',	'Imports de les quotes de l\'any '.$formseccio->get('quotaany')->getData().' actualitzats correctament');
    				
    				return $this->redirect($this->generateUrl('foment_gestio_seccions', array( 'anydades' => $anyselect)));
    			}
    			
    		} catch (\Exception $e) {
    					
    			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    		}
    	} else {
    		if ($request->query->has('action') && $request->query->get('action') == 'edit') {
    			// Editar la llista de seccions per modificar tots els preus
    			$edicioQuotes = true;
    			
    		} else {
    			// Mentre no hi hagi edició actualitzo els paràmetres de cerca
    			//$queryparams = $this->queryTableSort($request, array( 'id' => 's.id', 'direction' => 'asc'));
    			$queryparams['quotes'] = $quotes;
    		}
    	}
    	
    	$query = $this->filtrarArraySeccions($arraySeccions, $queryparams, $anyselect);
    	
    	$sortkeys = array('nom' => 's.nom', 'id' => 's.id', 'import' =>  'q.import', 'importjuvenil' => 'q.importjuvenil', 'membres' => 'membres');
    	$query = $this->ordenarArrayClausVariables($query, $queryparams, $sortkeys);

    	
    	$paginator  = $this->get('knp_paginator');
    	
    	$seccions = $paginator->paginate(
    			$query,
    			$queryparams['page'],
    			$queryparams['perpage'] //limit per page
    			 
    	);
    	unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
    	$seccions->setParam('perpage', $queryparams['perpage']); // Add extra request params

    	if ($request->getMethod() != 'POST') {
    		// No es poden modificar dades del formulari si s'ha enviat
    		$form->get('filtre')->setData($queryparams['filtre']);
    		$form->get('midapagina')->setData($queryparams['perpage']);
    	
    	}
    	
    	if ($request->isXmlHttpRequest() == true) {
    		// Ajax call renders only table activitats
    		return $this->render('FomentGestioBundle:Includes:taulaseccions.html.twig',
    				array('form' => $form->createView(), 'seccions' => $seccions, 'total' => $this->queryTotal('Seccio'),
    						'queryparams' => $queryparams, 'edicioQuotes' => $edicioQuotes));
    	}
    	
    	return $this->render('FomentGestioBundle:Pages:seccions.html.twig',
    			array('form' => $form->createView(), 'seccions' => $seccions, 'total' => $this->queryTotal('Seccio'),
    							'queryparams' => $queryparams, 'edicioQuotes' => $edicioQuotes ));
    }
    
    /* Obtenir taula Junta editable */
    public function editjuntaAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$id = $request->query->get('id', 0);
    	$edit = false;
    	if ($request->query->has('edit')) $edit = true;
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$seccio = new Seccio();
    	
    	if ($id > 0) {
    		$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($id);
    	}
    	
    	if ($request->query->has('action')) {
    		// Afegir o treure membre de la junta
    		$action = $request->query->get('action');
    		$idsoci = $request->query->get('soci', 0);
    		
    		// Membres temporals no persistents
    		$dataJuntaTemp = array();
    		$data = $request->query->get('form', null);
    		
    		if ($data != null && isset($data['membresjunta'])) {
    			$dataJuntaTemp = $data['membresjunta'];
    		}
    		
    		if ($action == 'add') {
    			// Afegir idsoci nou a la llista de temporals
    			$dataJuntaTemp[] = array('idsoci' => $idsoci, 'carrec' => 5);// Vocal

    			
    			foreach ($dataJuntaTemp as $tmp) {
	    			$membre = $seccio->getMembreBySociId($tmp['idsoci']);
	    			if (!$membre->esJuntaVigent()) {
		    			$membrejunta = $seccio->addMembreJunta($membre);
		    			$em->remove($membre); // Substituir pel nou
		    			$membrejunta->setCarrec($tmp['carrec']);
		    			$em->persist($membrejunta);
	    			}
    			} 
    		} else {
    			$membre = $seccio->getMembreBySociId($idsoci, 'junta');
    			
    			if ($membre == null) { // No existeix. Ok, no fer res

    			} else {
    				// Cancelar participació junta
    				$membre->setDatamodificacio(new \DateTime());
    				$membre->setDatafins(new \DateTime());
    			}
    		}
    	}
    	
    	$form = $this->createFormBuilder()->add('membresjunta',	'collection', 
    		array('type' => new FormJunta(),
    				'data' => $seccio->getMembresjunta()))->getForm();
    	 
   		return $this->render('FomentGestioBundle:Includes:taulajuntaseccio.html.twig',
   				array('formjunta' => $form->createView(), 'seccio' => $seccio, 'edicioTaulajunta' => $edit));
    	
    }
    
    private function tractarMembreJuntaTemporals($seccio, $dataJuntaTemp)
    {
    	
    	$em = $this->getDoctrine()->getManager();
    	// Membres temporals no persistents afegir a secció temporalment
    	
    	// Consulta per millorar rendiment 
    	$em = $this->getDoctrine()->getManager();
    	
    	$strQuery = 'SELECT j FROM Foment\GestioBundle\Entity\Junta j ';
    	$strQuery .= 'WHERE j.datacancelacio IS NULL AND j.datafins IS NULL 
    				AND j.seccio = :seccioid ';
    	
    	$query = $em->createQuery($strQuery)->setParameter('seccioid', $seccio->getId());
    	
    	$membresActuals = $query->getResult();
   	
    	
    	//$membresActualsIds = $seccio->getMembresActius('junta');
    	foreach ($dataJuntaTemp as $d) {
    		$membrejunta = null;
    		$membrejuntaIndex = 0;
    		$i = 0;
    		while ($membrejunta == null && isset($membresActuals[$i]) ) {
    			if ($d['idsoci'] == $membresActuals[$i]->getSoci()->getId()) {
    				$membrejunta = $membresActuals[$i];
    				$membrejuntaIndex = $i;
    				error_log(" trobat index ".$i );
    			}
    			$i++;
    		}
    		
    		if ($membrejunta == null) {
    			// El soci no està a la Junta actual. Afegir membre junta i cancelar membre normal
    			$membre = $seccio->getMembreBySociId($d['idsoci']);
    			$membrejunta = $seccio->addMembreJunta($membre); 
    			$em->remove($membre); // Substituir pel nou
    			$em->persist($membrejunta);
    			
    		} else {
    			// Manté el membre de la junta. Treiem de la llista 
    			array_splice($membresActuals, $membrejuntaIndex, 1);
    		}
    		$membrejunta->setCarrec($d['carrec']);
    		if (isset($d['area'])) $membrejunta->setArea($d['area']);
    		$em->persist($membrejunta);
    	}
    	// Els que queden no estaven a la llista del formulari i s'esborren
    	foreach ($membresActuals as $peresborrar)  {  // Per esborrar les que queden
    		$peresborrar->setDatamodificacio(new \DateTime());
    		$peresborrar->setDatafins(new \DateTime());
    		$em->persist($peresborrar);
    	}    	
    }
    
    /* Veure / actualitzar seccions */
    public function seccioAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$junta = 0;
    	$anydades = date('Y');
    	if ($request->getMethod() == 'POST') {
    		$data = $request->request->get('seccio');
    		
    		$id = (isset($data['id'])?$data['id']:0);
    	} else {
    		$id = $request->query->get('id', 0);
    		$anydades = $request->query->get('anydades', date('Y'));
    		
    		// Boolean indica l'estat de l'edició de la taula de junta junta
    		$junta = $request->query->get('junta', 0); 
    	}
    	
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'cognomsnom', 'direction' => 'asc'));
    	
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$seccio = new Seccio();
    	if ($id > 0) {
    		$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($id);
    	}
    	
    	// Afegir membres temporals no persistents
    	$dataJuntaTemp = array();
    	 
    	if ($request->getMethod() != 'POST') {
    		$dataJunta = $request->query->get('form', null);
    	} else {
    		$dataJunta = $request->request->get('form', null);
    	}
    	if ($dataJunta != null && isset($dataJunta['membresjunta'])) {
    		$dataJuntaTemp = $dataJunta['membresjunta'];
    		$this->tractarMembreJuntaTemporals($seccio, $dataJuntaTemp);
    	}
    	
    	$queryparams['junta'] = $junta;
    	
    	// Filtre i ordenació dels membres
    	$query = $this->filtrarArrayNomCognoms($seccio->getMembresActius(''), $queryparams);

    	$query = $this->ordenarArrayObjectes($query, $queryparams);
    	 
    	$paginator  = $this->get('knp_paginator');
    	 
    	$membres = $paginator->paginate(
    			$query,
    			$queryparams['page'],
    			$queryparams['perpage'] //limit per page
    	);
    	unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
    	$membres->setParam('id', $id); // Add extra request params. Seccio id
    	$membres->setParam('perpage', $queryparams['perpage']);
    	$membres->setParam('junta', $junta);

    	
    	$queryparams['anydades'] = $anydades;
    	$queryparams['rebutsgenerats'] = ($this->rebutsCreatsAny($anydades));
    	$queryparams['anysSelectable'] = $this->getAnysSelectable();
    	 
    	$form = $this->createForm(new FormSeccio($queryparams), $seccio);
    	
    	if ($request->getMethod() == 'POST') {

    		try {
	    		$form->handleRequest($request);
	    		
	    		if ($form->isValid()) {
	    			
	    			$anydades = $form->get('quotaany')->getData();
	    			
	    			$this->postFormSeccioQuotes($form);
	    			
	    			if ($seccio->getId() == 0) $em->persist($seccio);
	    			 
	    			$em->flush();
	    			 
	    			$this->get('session')->getFlashBag()->add('notice',	'Secció desada correctament');
	    			
	    			return $this->redirect($this->generateUrl('foment_gestio_seccio', array( 'id' => $seccio->getId(), 'anydades' => $anydades)));
	    			 
	    		} else {
	    			$this->get('session')->getFlashBag()->add('error',	'Cal revisar les dades del formulari. ');
	    		}
			} catch (\Exception $e) {
    		
    			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    		}
    		
    		
    	} else {
    		if ($request->isXmlHttpRequest() == true) {
    			return $this->render('FomentGestioBundle:Includes:taulamembresseccio.html.twig',
    					array('form' => $form->createView(), 'seccio' => $seccio,
    						'membres' => $membres, 'queryparams' => $queryparams));
    		}
    	}
		
    	return $this->render('FomentGestioBundle:Pages:seccio.html.twig',
    			array('form' => $form->createView(), 'seccio' => $seccio,
    					'membres' => $membres, 'queryparams' => $queryparams));
    }
    
    private function postFormSeccioQuotes($form) {
    	$em = $this->getDoctrine()->getManager();
    
    	$seccio = $form->getData();
    
    	$quotaany = $form->get('quotaany')->getData();
    	$quotaimport = $form->get('quotaimport')->getData();
    	$quotaimportjuvenil = $form->get('quotaimportjuvenil')->getData();
    	
    	if ($quotaany >= date('Y')) {
    		 
    		$quota = null;
    		if ($quotaimport >= 0) $quota = $seccio->setQuotaAny($quotaany, $quotaimport);
    		if ($quotaimportjuvenil >= 0) $quota = $seccio->setQuotaAny($quotaany, $quotaimportjuvenil, true);
    		if ($quota != null) $em->persist($quota);
    		 
    	} else {
    		throw new \Exception('No es poden modificar quotes d\'anys anteriors');
    	}
    
    	$seccio->setDatamodificacio(new \DateTime());
    }
    
    public function seccioInscripcioAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$em = $this->getDoctrine()->getManager();
    
    	$id = $request->query->get('id', 0);
    	$perpage =  $request->query->get('perpage', UtilsController::DEFAULT_PERPAGE);
    	$filtre = $request->query->get('filtre', '');
    	$anydades = $request->query->get('anydades', date('Y'));
    	
    	try {
    		$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($id);
    		if ($seccio == null) throw new \Exception('La secció no existeix');
    
    		// Inscriure persona
    		$afegirsociid = $request->query->get('soci', 0);
    
    		$noumembre = $em->getRepository('FomentGestioBundle:Persona')->find($afegirsociid);
    
    		if ($noumembre == null || $noumembre->getDatabaixa() != null) throw new \Exception('Soci no trobat '.$afegirsociid.'' );
    		
    		$this->inscriureMembre($seccio, $noumembre, $anydades);
    
    		$em->flush();
    		
    		// Aplicar filtre si OK
    		$filtre = $noumembre->getNomCognoms();
    		 
    	} catch (\Exception $e) {
    		$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    	}
    	 
    	return $this->redirect($this->generateUrl('foment_gestio_seccio', array( 'id' => $id, 'perpage' => $perpage, 'filtre' => $filtre, 'anydades' => $anydades)));
    }
    
    public function seccioCancelacioAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$em = $this->getDoctrine()->getManager();
    	 
    	$id = $request->query->get('id', 0);
    	$perpage =  $request->query->get('perpage', UtilsController::DEFAULT_PERPAGE);
    	$filtre = $request->query->get('filtre', '');
    	$anydades = $request->query->get('anydades', date('Y'));
    	
    	try {
    		$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($id);
    		if ($seccio == null) throw new \Exception('La secció no existeix');
    		 
    		// Cancel·lar inscripcio
    		$treuresociid = $request->query->get('soci', 0);
    
    		$esborrarmembre = $em->getRepository('FomentGestioBundle:Soci')->find($treuresociid);
    
    		if ($esborrarmembre == null) throw new \Exception('Soci no trobat '.$treuresociid.'' );
    
    
    		$this->esborrarMembre($seccio, $esborrarmembre, $anydades);
    
    		$em->flush();
    	} catch (\Exception $e) {
    		$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    	}
    	 
    	return $this->redirect($this->generateUrl('foment_gestio_seccio', array( 'id' => $id, 'perpage' => $perpage, 'filtre' => $filtre, 'anydades' => $anydades)));
    }
    
    private function inscriureMembre($seccio, $noumembre, $anydades) {
    	$em = $this->getDoctrine()->getManager();
    	 
    	$membre = $seccio->getMembreBySociId($noumembre->getId());
    	
    	if ($membre != null) throw new \Exception('Aquest soci ja pertany a la Secció' );
    	
    	$membre = $seccio->addMembreSeccio($noumembre);
    	
    	if ($anydades > date('Y')) {  // Inscripció futura, canviar data d'inscripció
    		$membre->setDatainscripcio( \DateTime::createFromFormat('d/m/Y', '01/01/'.$anydades ) );
    	}
    	
    	$em->persist($membre);
    	
    	// Si no existeixen facturacions iguals o posteriors a l'any d'alta, no cal fer res
    	// En cas contrari cal afegir els rebuts
    	$periodes = $this->queryGetPeriodesPendents($anydades); // Obtenir els periodes actual i futurs i afegir factures
    	
    	$socipagarebut = null; // Soci agrupa rebuts per pagar
    	$rebut = null;
    	
    	$strRebuts = "";
    	
    	$current = new \DateTime();
    	
    	foreach ($periodes as $periode) {
    		
    		/**************************** Crear el rebut per aquest nou soci per cada periode facturat ****************************/
    		
    		//if (!$periode->facturable()) throw new \Exception('No es poden afegir rebuts a les dates indicades' );
    		
    		if ($periode->facturable()) {  // Si hi ha periode facturar
	    		$socipagarebut  = $noumembre->getSocirebut();
	    		
	    		if ($socipagarebut == null) throw new \Exception('Cal indicar qui es farà càrrec dels rebuts del soci '.$noumembre->getNomCognoms().'' ); 
	    		
	    		$rebut = $periode->getRebutPendentByPersonaDeutora($socipagarebut);
	    		
	    		$dataemissio = $periode->getDatainici();  // Inici periode o posterior
	    		if ($current > $periode->getDatainici()) $dataemissio = $current;
	    		
	    		if ($rebut == null) {
	    			// Crear rebut nou
	    			$numrebut = $this->getMaxRebutNumAnySeccio($anydades); // Max
	    			$numrebut++;
	    			 
	    			$rebut = new Rebut($socipagarebut, $dataemissio, $numrebut, true, $periode);
	    			 
	    			$em->persist($rebut);
	    			
	    			$strRebuts .= 'Nou rebut generat '. $rebut->getNumFormat() . '<br/>';
	    		} else {
	    			$strRebuts .= 'Quota afegida al rebut '. $rebut->getNumFormat() . '<br/>';
	    		}
	    		
	    		$rebutdetall = $this->generarRebutDetallMembre($membre, $rebut, $periode);
	    		
	    		if ($rebutdetall != null) $em->persist($rebutdetall);
    		}
    	}
    	
    	$this->get('session')->getFlashBag()->add('notice',	'En/Na '.$noumembre->getNomCognoms().' ha estat inscrit/a correctament a la secció '.$seccio->getNom());
    	if ($strRebuts != "") {
    		$this->get('session')->getFlashBag()->add('notice',	'S\'ha modificat el/s rebut/s '. $strRebuts);
    	}
    }
    
    private function esborrarMembre($seccio, $esborrarmembre, $anydades) {
    	$membre = $seccio->getMembreBySociId($esborrarmembre->getId());
    	
    	if ($membre == null) throw new \Exception('Aquest soci no pertany a la secció');
    

    	if ($anydades > date('Y')) {  // Cancel·lació futura, canviar data de cancel·lació
    		$membre->setDatacancelacio( \DateTime::createFromFormat('d/m/Y', '31/12/'.($anydades - 1) ) );
    	} else {
    	   	$membre->setDatacancelacio(new \DateTime());
    	}
    	$membre->setDatamodificacio(new \DateTime());
    
    	$strRebuts = "";
    	 
    	// Anul·lar rebuts vigents o futurs no cobrats
    	$detallsrebuts = $membre->getRebutDetallTots();// Rebuts actius
    	foreach ( $detallsrebuts as $detall ) {
    		$rebut = $detall->getRebut();
    		$iniciPeriodeActual =  \DateTime::createFromFormat('d/m/Y', '01/01/'. date('Y') );
    	
    		if ($rebut != null && $rebut->getDataemissio() >= $iniciPeriodeActual) {
    			if ($rebut != null && $rebut->esEsborrable()) {
    				$detall->baixa();
    				$strRebuts = 'Quota del soci '.number_format($detall->getImport(),'2','.',',');
    				$strRebuts .= ' esborrada del rebut '. $rebut->getNumFormat() .'<br/>';
    			}
    			if ($rebut != null && !$rebut->esEsborrable()) {
    				$strRebuts = 'La quota del soci '.number_format($detall->getImport(),'2','.',',');
    				$strRebuts .= ' està inclosa al rebut '. $rebut->getNumFormat() . ' i no s\'ha esborrat. ';
    				$strRebuts .= UtilsController::getEstats($rebut->getEstat()) .'<br/>';
    			}
    		}
    	}
    	
    	if (count($esborrarmembre->getSeccionsSortedById()) == 0) {
    		$quotaDelStr = 'El soci '.$esborrarmembre->getNumSoci().'-'.$esborrarmembre->getNomCognoms() .' no pertany a cap secció';
    		$this->get('session')->getFlashBag()->add('error',	$quotaDelStr );
    	}
    	
    	$this->get('session')->getFlashBag()->add('notice',	'En/Na '.$esborrarmembre->getNomCognoms().' ha estat donat de baixa de la secció '.
    							$membre->getSeccio()->getNom().' en data '. $membre->getDatacancelacio()->format('d/m/Y'));
    	if ($strRebuts != "") {
    		$this->get('session')->getFlashBag()->add('notice',	'S\'ha modificat el/s rebut/s '. $strRebuts); 
    	} 
    }
    
    
    /* Veure / actualitzar seccions */
    public function activitatsAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'a.id', 'direction' => 'desc'));
    	 
    	$query = $this->queryActivitats($queryparams);

    	$paginator  = $this->get('knp_paginator');
    	
    	$activitats = $paginator->paginate(
    			$query,
    			$queryparams['page'],
    			$queryparams['perpage'] //limit per page
    			
    	);
    	unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
    	$activitats->setParam('perpage', $queryparams['perpage']); // Add extra request params 
    	
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
    		// Ajax call renders only table activitats
    		return $this->render('FomentGestioBundle:Includes:taulaactivitats.html.twig',
    				array('form' => $form->createView(), 'activitats' => $activitats, 'total' => $this->queryTotal('Activitat'),
    						'queryparams' => $queryparams));
    	}
    		
    	return $this->render('FomentGestioBundle:Pages:activitats.html.twig',
    			array('form' => $form->createView(), 'activitats' => $activitats, 'total' => $this->queryTotal('Activitat'),
    				  'queryparams' => $queryparams));
    }
    
    
    public function programaciocursAction(Request $request) {
    	// Carrega les programacions sese persistència, només per generar la taula
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$em = $this->getDoctrine()->getManager();
    	$activitatId = $request->query->get('id', 0); // Curs
    	
    	$curs = $em->getRepository('FomentGestioBundle:ActivitatAnual')->find($activitatId);
    	
    	if ($curs == null) {
    		//throw new \Exception('Curs no trobat');
    		$curs = new ActivitatAnual();
    	}
    	
    	$setmanal = $request->query->get('setmanal', '');
    	$mensual = $request->query->get('mensual', '');
    	$persessions = $request->query->get('persessions', '');
    	
    	
    	$curs->setSetmanal( urldecode($setmanal) );
    	$curs->setMensual( urldecode($mensual) );
    	$curs->setPersessions( urldecode($persessions) );
    	
    	$em->persist($curs);
    	
    	
    	return $this->render('FomentGestioBundle:Includes:taulaprogramaciocurs.html.twig',
    			array('activitat' => $curs));

    }
    
    /* Veure / actualitzar curs */
    public function cursAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	$em = $this->getDoctrine()->getManager();
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'cognomsnom', 'direction' => 'desc', 'perpage' => UtilsController::DEFAULT_PERPAGE_WITHFORM));
    	 
    	
    	$tab = 0;
    	if ($request->getMethod() == 'POST') {
    		$data = $request->request->get('activitatanual');
    		 
    		$id = (isset($data['id'])?$data['id']:0);
    		
    		$strFacturacionsIds = (isset($data['facturacionsdeltemp'])?$data['facturacionsdeltemp']:'');
    		
    		$facturacionsIdsEsborrar = array();
    		if ($strFacturacionsIds != '') $facturacionsIdsEsborrar = explode(',',$strFacturacionsIds); // array ids facturacions per esborrar
    		 
    		
    		$facturacionsNoves = (isset($data['facturacions'])?$data['facturacions']:array());
    		
    		$strDocenciesJSON = (isset($data['docenciestmp'])?$data['docenciestmp']:'');

    	} else {
    		$id = $request->query->get('id', 0);
    		$tab = $request->query->get('tab', 0);
    		
    	}
    	
    	$curs = $em->getRepository('FomentGestioBundle:ActivitatAnual')->find($id);
    	
    	if ($curs == null ) {
    		$curs = new ActivitatAnual();
    		$em->persist($curs);
    		
    		if ($request->getMethod() != 'POST') { // Get nou curs
    		// Crear 3 facturacions per defecte
	    		$num = $this->getMaxFacturacio();
	    		
	    		$dataFactu1 = \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_FACTURA_CURS_OCTUBRE. date('Y') );
	    		$dataFactu2 = \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_FACTURA_CURS_GENER. (date('Y')+1) );
	    		$dataFactu3 = \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_FACTURA_CURS_ABRIL. (date('Y')+1) );
	    		
	    		$desc = UtilsController::TEXT_FACTURACIO_OCTUBRE;
	    		$facturacio1 = new Facturacio($curs, $num, $desc, 0, 0, $dataFactu1);  
	    		$em->persist($facturacio1);
	    		
	    		$num++;
	    		$desc =  UtilsController::TEXT_FACTURACIO_GENER; 
	    		$facturacio2 = new Facturacio($curs, $num, $desc, 0, 0, $dataFactu2);
	    		$em->persist($facturacio2);
	    		
	    		$num++;
	    		$desc =  UtilsController::TEXT_FACTURACIO_ABRIL;
	    		$facturacio3 = new Facturacio($curs, $num, $desc, 0, 0, $dataFactu3);
	    		$em->persist($facturacio3);
	    		
    		}
    	} 
    	
    	$query = $curs->getParticipantsActius();
    	if ($request->getMethod() == 'GET') { 
	    	// Filtre i ordenació dels membres
	    	$query = $this->filtrarArrayNomCognoms($query, $queryparams);
	    	$query = $this->ordenarArrayObjectes($query, $queryparams);
    	}
    	
    	$setmanalPrevi = $curs->getSetmanal();
    	
    	$paginator  = $this->get('knp_paginator');
    	 
    	$participants = $paginator->paginate(
    			$query,
    			$queryparams['page'],
    			$queryparams['perpage'] //limit per page
    	);
    	unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
    	$participants->setParam('id', $id); // Add extra request params. Activitat id
    	$participants->setParam('perpage', $queryparams['perpage']);
    	
    	$form = $this->createForm(new FormActivitatAnual($queryparams), $curs);
    	if ($request->getMethod() == 'POST') {
    
    		$form->handleRequest($request);
    		
    		try {
    		
	    		if ($form->isValid()) {
	    			
	    			$curs->setDatamodificacio(new \DateTime());

	    			if ($curs->getId() == 0) $em->persist($curs);
	    			 
	    			try {
	    				$this->cursTractamentCalendari($curs, $setmanalPrevi, $curs->getSetmanal(), $curs->getMensual(), $curs->getPersessions());
	    			} catch (\Exception $e) {
	    				$tab = 1;
	    				throw new \Exception($e->getMessage());
	    			}

	    			try {
	    				if ($strDocenciesJSON != '') $this->cursTractamentDocencia($curs, $strDocenciesJSON, $form);
	    			} catch (\Exception $e) {
	    				$tab = 2;
	    				throw new \Exception($e->getMessage());
	    			}
	    			
	    			try {
	    				$this->cursTractamentFacturacio($curs, $participants, $facturacionsIdsEsborrar, $facturacionsNoves);
	    			} catch (\Exception $e) {
	    				$tab = 3;
	    				throw new \Exception($e->getMessage());
	    			}
	    			
	    			$em->flush();
	    			
	    			$this->get('session')->getFlashBag()->add('notice',	'Curs desat correctament');
	    			// Prevent posting again F5
	    			return $this->redirect($this->generateUrl('foment_gestio_curs', array( 'id' => $curs->getId(), 'tab' => $tab)));
	    		} else {
	    			$sms_kernel = '';
	    			if ($this->container->get('kernel')->getEnvironment() && false) $sms_kernel =  $form->getErrorsAsString();
	    
	    			throw new \Exception('Cal revisar les dades del formulari. ' . $sms_kernel);
	    		}
    		
    		} catch (\Exception $e) {
    			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    		}
    		
    	} else {
    		if ($request->isXmlHttpRequest() == true) {
    			// Table participants action
    			return $this->render('FomentGestioBundle:Includes:taulaparticipantsactivitat.html.twig',
    					array('form' => $form->createView(), 'activitat' => $curs,
    							'participants' => $participants, 'queryparams' => $queryparams));
    		}
    	}
    	
    	return $this->render('FomentGestioBundle:Pages:cursanual.html.twig',
    			array('form' => $form->createView(), 'activitat' => $curs,
    					'participants' => $participants, 'queryparams' => $queryparams,
    					'tab' => $tab));
    	 
    }
    
    private function cursTractamentCalendari($curs, $setmanalPrevi, $setmanal, $mensual, $persessions) { 
    	// Afegir / esborrar sessions i esdeveniments
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$setmanalPreviArray = array();
    	if ($setmanalPrevi != '') $setmanalPreviArray = explode(';',$setmanalPrevi); // array pogramacions

    	$setmanalArray = array();
    	if ($setmanal != '') $setmanalArray = explode(';',$setmanal); // array pogramacions

    	$mensualArray = array();
    	if ($mensual != '') $mensualArray = explode(';',$mensual); // array pogramacions

    	$persessionsArray = array();
    	if ($persessions != '') $persessionsArray = explode(';',$persessions); // array pogramacions
    	   
    }
    
    private function cursTractamentDocencia($curs, $strDocenciesJSON, $form) {
    	$em = $this->getDoctrine()->getManager();
    	// Tractament docencies
    	$json = json_decode($strDocenciesJSON, true);
    	
    	foreach ($json as $docent) {

    		switch ($docent['accio']) {
    			case 'addNew':
    				// Afegir docència
    				$professor = $em->getRepository('FomentGestioBundle:Proveidor')->find($docent['proveidor']);
    				
    				if ($professor == null) throw new \Exception('No s\'ha trobat el professor '.$docent['proveidor']);
    				
    				if ($docent['preutotal'] == '') throw new \Exception('Cal informar l\'import total de la docència');
    				
    				$import = $docent['preutotal'];
    				if (!is_numeric($import) || $import <= 0) throw new \Exception('L\'import total del professor '.$professor->getRaosocial().' és incorrecte '. $import);
    				
					$preuhora = null;
					if ($docent['preuhora'] != '') {
						$preuhora = $docent['preuhora'];
						if (!is_numeric($preuhora) || $preuhora <= 0) throw new \Exception('El preu per hora del professor '.$professor->getRaosocial().' és incorrecte '. $preuhora); 
					}
    					
					$hores = null;
					if ($docent['hores'] != '') {
						$hores = $docent['hores'];
						if (!is_numeric($hores) || $hores <= 0) throw new \Exception('El nombre d\'hores del professor '.$professor->getRaosocial().' són incorrectes '. $hores);
					}
    					
    				$docencia = new Docencia($curs, $professor, $hores, $preuhora, $import);
    				$em->persist($docencia);
    					
    				break;
    			case 'remove':
    				// Cancel·lar docència
    				$curs->removeProfessorById($docent['proveidor']);
    				
    				break;
    			default:
    				throw new \Exception('Acció desconeguda '.$docent['accio']);
    				break;
    		}
    	
    	}
    	
    }
    
    private function cursTractamentFacturacio($curs, $participants, $facturacionsIdsEsborrar, $facturacionsNoves) {
    	if ($curs->getQuotaparticipant() <= 0) {
    		
    		throw new \Exception('La quota ha de ser més gran que 0' );
    	}
    	if ($curs->getQuotaparticipantnosoci() <= 0) {
    	
    		throw new \Exception('La quota no soci ha de ser més gran que 0' );
    	}
    	 
    	$facturacions = $curs->getFacturacionsActives();
    	
    	$total = 0;
    	$totalns = 0;
    	
    	foreach ($facturacions as $facturacio)  {
    		
    		if (in_array($facturacio->getId(), $facturacionsIdsEsborrar)) {
    			// Baixa
    			if (!$facturacio->esEsborrable()) throw new \Exception('La facturació "'.$facturacio->getDescripcio().'" no es pot esborrar perquè té rebuts pagats');
    				
    			$facturacio->baixa();
    		} else {
    			
    			$total += $facturacio->getImportactivitat();
    			$totalns += $facturacio->getImportactivitatnosoci();
    		}
    	}
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	
    	foreach ($facturacionsNoves as $nova) {
    		
    		if (!isset($nova['descripcio'])) throw new \Exception('Cal indicar una descripció per la facturació del curs');

    		$desc = str_replace('curs (pendent)', 'curs '.$curs->getDescripcio(), $nova['descripcio']);
    	
    		if (!isset($nova['datafacturacio'])) throw new \Exception('Cal indicar la data per a cada facturació per poder fer l\'emissió dels rebuts del curs');
    	
    		$datafacturacio = \DateTime::createFromFormat('d/m/Y', $nova['datafacturacio'] );
    	
    		if (!isset($nova['importactivitat']) || !isset($nova['importactivitatnosoci'])) throw new \Exception('Cal indicar els imports dels rebuts de la facturació del curs');
    	
    		$strImport = $nova['importactivitat'];
    		//$import = sscanf($strImport, "%f");
    		$fmt = numfmt_create( 'es_CA', \NumberFormatter::DECIMAL );
    		$import = numfmt_parse($fmt, $strImport);
    		if (!is_numeric($import)) throw new \Exception('L\'import de la facturació és incorrecte '. $import);
    		 
    		$strImport = $nova['importactivitatnosoci'];
    		$fmt = numfmt_create( 'es_CA', \NumberFormatter::DECIMAL );
    		$importnosoci = numfmt_parse($fmt, $strImport);
    		if (!is_numeric($importnosoci)) throw new \Exception('L\'import de la facturació no socis és incorrecte '. $importnosoci);
    		
    		$num = $this->getMaxFacturacio();
    	
    		//$total += $import;
    		 
    		$facturacio = new Facturacio($curs, $num, $desc, $import, $importnosoci, $datafacturacio);
    		 
    		// Generar rebuts participants actius
    		$em->persist($facturacio);
    		
    		$anyFacturaAnt = $datafacturacio->format('Y');
    		$numrebut = $this->getMaxRebutNumAnyActivitat($anyFacturaAnt); // Max
    		// Si datafacturacio + 2 mesos > avui => fa menys de 2 mesos de la facturació => crear rebuts
    		$datafacturacioPlus2Mesos = \DateTime::createFromFormat('d/m/Y', $nova['datafacturacio'] );
    		$datafacturacioPlus2Mesos->add(new \DateInterval('P2M'));
    		if ($datafacturacioPlus2Mesos > new \DateTime()) {
    			foreach ($curs->getParticipantsActius() as $participacio) {
    				 
    				$rebut = $this->generarRebutActivitat($facturacio, $participacio, $numrebut);
    				if ($rebut != null) $numrebut++;
    			
    			}
    		}
    		
    		$total += $import;
    		$totalns += $importnosoci;
    	}
    	
    	if ( abs($total - $curs->getQuotaparticipant()) > 0.01 || abs($totalns - $curs->getQuotaparticipantnosoci()) > 0.01 ) 
    			throw new \Exception('La suma dels imports de les facturacions ha de coincidir amb l\'import de l\'activitat ');
    	
    	/*
    	$numrebut = 0;
    	$anyFacturaAnt = 0;
    	
    	
    	$facturacionsOrdenades = $curs->getFacturacionsSortedByDatafacturacio();
    	foreach ($facturacionsOrdenades as $i => $facturacio) {
    		// No s'afegeixen rebuts facturacions passades
    		if (isset($facturacionsOrdenades[$i+1]) && $facturacionsOrdenades[$i+1]->getDatafacturacio() < new \DateTime()) continue;
    		
    		if ($anyFacturaAnt != $facturacio->getDatafacturacio()->format('Y')) {
    			// Canvi any tornar a calcular numrebut
    			$anyFacturaAnt = $facturacio->getDatafacturacio()->format('Y');
    			
    			$numrebut = $this->getMaxRebutNumAnyActivitat($anyFacturaAnt); // Max
    			$numrebut++;
    			
    		}
    		
    		foreach ($curs->getParticipantsActius() as $participacio) {
    	
	    		$rebut = $this->generarRebutActivitat($facturacio, $participacio, $numrebut);
    			if ($rebut != null) $numrebut++;
    		
    		}
    	}
    	*/
    }
    
    
    /* Veure / actualitzar activitat puntual o taller un dia */
    public function activitatAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
   		$em = $this->getDoctrine()->getManager();
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'cognomsnom', 'direction' => 'desc', 'perpage' => UtilsController::DEFAULT_PERPAGE_WITHFORM));
    	
    	if ($request->getMethod() == 'POST') {
    		$data = $request->request->get('puntual');
    	
    		$id = (isset($data['id'])?$data['id']:0);
    		if ($id > 0) {
    			$activitat = $em->getRepository('FomentGestioBundle:ActivitatPuntual')->find($id);
    		} else {
    			$activitat = new ActivitatPuntual();
    		}
    	} else {
    		$id = $request->query->get('id', 0);
    		$activitat = $em->getRepository('FomentGestioBundle:ActivitatPuntual')->find($id);
    		
    		if ($activitat == null) { 
    			$activitat = new ActivitatPuntual();
    		}     		
    	}
    	// Filtre i ordenació dels membres
    	$query = $this->filtrarArrayNomCognoms($activitat->getParticipantsActius(), $queryparams);
    	$query = $this->ordenarArrayObjectes($query, $queryparams);
    	
    	$paginator  = $this->get('knp_paginator');
    	
    	$participants = $paginator->paginate(
    			$query,
    			$queryparams['page'],
    			$queryparams['perpage'] //limit per page
    	);
    	unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
    	$participants->setParam('id', $id); // Add extra request params. Activitat id
    	$participants->setParam('perpage', $queryparams['perpage']);
    	
    	$form = $this->createForm(new FormActivitatPuntual($queryparams), $activitat);
    	if ($request->getMethod() == 'POST') {
    		
    		$form->handleRequest($request);

    		if ($form->isValid()) {

    			try {
	    			$activitat->setDatamodificacio(new \DateTime());
	    			 
	    			if ($activitat->getQuotaparticipant() <= 0) throw new \Exception('El preu per als socis ha de ser més gran que 0' );
	    			if ($activitat->getQuotaparticipantnosoci() <= 0) throw new \Exception('El preu per als no socis ha de ser més gran que 0' );
	    			
	    			if ($activitat->getId() == 0) {
	    				
	    				// Facturació si no existeix
	    				$num = $this->getMaxFacturacio();
	    				
	    				$desc = 'Facturació '.$num.' '.substr($activitat->getDescripcio(), 0, 40).' data '.$activitat->getDataactivitat()->format('d/m/Y');
	    				$facturacio = new Facturacio($activitat, $num, $desc, $activitat->getQuotaparticipant(), $activitat->getQuotaparticipantnosoci(), $activitat->getDataactivitat()); // Facturació activitat puntual, només una
	    				
	    				$em->persist($facturacio);
	    				$em->persist($activitat);
	    			} else {
	    				$facturacions = $activitat->getFacturacions();
	    				if (!isset($facturacions[0])) throw new \Exception('Dades incompletes, activitat sense facturacio');
	    				
	    				if ($facturacions[0]->getTotalrebuts() == 0) { // Una facturació sense rebuts canviar data facturació. Amb rebuts no pq modificaria rebuts
	    					$facturacions[0]->setDatafacturacio($activitat->getDataactivitat());
	    				} 
	    			}
	
	    			$em->flush();
	    		
	    			$this->get('session')->getFlashBag()->add('notice',	'Activitat desada correctament');
	    			// Prevent posting again F5
	    			return $this->redirect($this->generateUrl('foment_gestio_activitat', array( 'id' => $activitat->getId())));
    			
    			} catch (\Exception $e) {
    				$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    			}
    		} else {
    			$sms_kernel = '';
    			if ($this->container->get('kernel')->getEnvironment() && false) $sms_kernel =  $form->getErrorsAsString();
    			 
    			$this->get('session')->getFlashBag()->add('error',	'Cal revisar les dades del formulari. ' . $sms_kernel);
    		}
		} else {
    		if ($request->isXmlHttpRequest() == true) {
    			// Table participants action
    			return $this->render('FomentGestioBundle:Includes:taulaparticipantsactivitat.html.twig',
    					array('form' => $form->createView(), 'activitat' => $activitat, 
    							'participants' => $participants, 'queryparams' => $queryparams));
    		}
   		}
   		return $this->render('FomentGestioBundle:Pages:activitatpuntual.html.twig',
   				array('form' => $form->createView(), 'activitat' => $activitat,
   						'participants' => $participants, 'queryparams' => $queryparams));
   		
    }
    
    
    public function esborraractivitatAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$em = $this->getDoctrine()->getManager();
    
    	$id = $request->query->get('id', 0);
    	
    	$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($id);
    	
    	try {
    		$nom = 'curs';
    		if (!$activitat->esAnual()) $nom = 'taller o activitat';
	    	
    		
    		if ($activitat == null) throw new \Exception('No s\'ha trobat el '.$nom.' '. $id); 
	    	
	    	if (!$activitat->esEsborrable()) throw new \Exception('Aquest '.$nom.' no es pot esborrar, cal anul·lar els rebuts');
	    	
	    	$activitat->setDatabaixa(new \DateTime());
	    	
	    	foreach ($activitat->getFacturacions() as $facturacio) {
	    		$facturacio->baixa(); // $facturació i rebuts
	    	}
	    	
	    	$em->flush();
	    	
	    	$this->get('session')->getFlashBag()->add('notice',	'El '.$nom.' '.$activitat->getDescripcio().' ha estat anul·lat correctament ');
    	} catch (\Exception $e) {
    		$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    	}
    		
    		
    	return $this->redirect($this->generateUrl('foment_gestio_activitats'));
    }
    
    
    public function activitatInscripcioAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$em = $this->getDoctrine()->getManager();
    	 
    	$id = $request->query->get('id', 0);
    	$perpage =  $request->query->get('perpage', UtilsController::DEFAULT_PERPAGE);
    	$filtre = $request->query->get('filtre', '');
    	
    	try {
    	    // Inscriure persona
    		$afegirpersonaid = $request->query->get('persona', 0);
    		
			$nouparticipant = $em->getRepository('FomentGestioBundle:Persona')->find($afegirpersonaid);
   			  
			if ($nouparticipant == null) throw new \Exception('Participant no trobat '.$afegirpersonaid.'' );
    			   
			$this->inscriureParticipant($id, $nouparticipant);
   			
			$em->flush();
			 
			$this->get('session')->getFlashBag()->add('notice',	'En/Na '.$nouparticipant->getNomCognoms().' ha estat inscrit/a correctament a l\'activitat');
				
			
			// Aplicar filtre si OK
			$filtre = $nouparticipant->getNomCognoms();
    	
   		} catch (\Exception $e) {
   			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
   		}
   		
   		return $this->redirect($this->generateUrl('foment_gestio_activitat', array( 'id' => $id, 'perpage' => $perpage, 'filtre' => $filtre)));
    }
    
	public function activitatCancelacioAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$id = $request->query->get('id', 0);
    	$perpage =  $request->query->get('perpage', UtilsController::DEFAULT_PERPAGE);
    	$filtre = $request->query->get('filtre', '');
    	
    	try {
    		// Cancel·lar inscripcio
    		$treurepersonaid = $request->query->get('persona', 0);
    		
    		$esborrarparticipant = $em->getRepository('FomentGestioBundle:Persona')->find($treurepersonaid);
    		
    		if ($esborrarparticipant == null) throw new \Exception('Participant no trobat '.$treurepersonaid.'' );
    		
    		$this->esborrarParticipant($id, $esborrarparticipant);

    		$em->flush();
    		
    		$this->get('session')->getFlashBag()->add('notice',	'En/Na '.$esborrarparticipant->getNomCognoms().' ha estat donat de baixa de l\'activitat');
    		
	    } catch (\Exception $e) {
	    	$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
	    }	
	    
	    return $this->redirect($this->generateUrl('foment_gestio_activitat', array( 'id' => $id, 'perpage' => $perpage, 'filtre' => $filtre)));
    }
    
    private function inscriureParticipant($activitatid, $nouparticipant) {
    	$em = $this->getDoctrine()->getManager();
    	
    	$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($activitatid);
    	
    	if ($activitat == null) throw new \Exception('L\'activitat no existeix ' .$activitatid);
    	
    	$participacio = $activitat->getParticipacioByPersonaId($nouparticipant->getId());
    	
    	if ($participacio != null) throw new \Exception('Aquesta persona ja està inscrita a l\'activitat' );
    	 
    	$participacio = $activitat->addParticipacioActivitat($nouparticipant);

    	$em->persist($participacio);
    	 
    	/**************************** Crear els rebuts per aquesta inscripció ****************************/
    	$anyFacturaAnt = 0;
    	$numrebut = 0;
    	$facturacionsOrdenades = $activitat->getFacturacionsSortedByDatafacturacio();
    	foreach ($facturacionsOrdenades as $i => $facturacio) {
    		// No s'afegeixen rebuts facturacions passades
    		if (isset($facturacionsOrdenades[$i+1]) && $facturacionsOrdenades[$i+1]->getDatafacturacio() < new \DateTime()) continue;
    		
    		if ($anyFacturaAnt != $facturacio->getDatafacturacio()->format('Y')) {
    			// Canvi any tornar a calcular numrebut
    			$anyFacturaAnt = $facturacio->getDatafacturacio()->format('Y');
    			$numrebut = $this->getMaxRebutNumAnyActivitat($anyFacturaAnt); // Max
    			$numrebut++;
    		}
    		
    		$rebut = $this->generarRebutActivitat($facturacio, $participacio, $numrebut);
    		if ($rebut != null) $numrebut++; // Nou número
    	}
	}
    
	 
	private function esborrarParticipant($activitatid, $esborrarparticipant) {
		$em = $this->getDoctrine()->getManager();
		
		$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($activitatid);
		if ($activitat == null) throw new \Exception('L\'activitat no existeix');
		
		$participacio = $activitat->getParticipacioByPersonaId($esborrarparticipant->getId());
		
		if ($participacio == null) throw new \Exception('Aquesta persona no està inscrita a l\'activitat');
		
		/**************************** baixa del rebut per aquesta persona ****************************/
		
		$rebutsdetalls = $participacio->getRebutsDetallsVigents();
		
		foreach ($rebutsdetalls as $detall) {
			$rebut = $detall->getRebut();
			
			if ($rebut == null) throw new \Exception('Falten rebuts per aquest participant ');
			
			if (!$rebut->esEsborrable()) throw new \Exception('Abans de poder cancel·lar la participació d\'aquesta persona cal anul·lar els seus rebuts ');
						
			$rebut->baixa();
			
		}

		
		$participacio->setDatacancelacio(new \DateTime());
		$participacio->setDatamodificacio(new \DateTime());
	}
	
	
	
	/* Carregar fotos socis. Migració 2015 inicial fotos  */
	public function carregarfotosAction(Request $request)
	{
		// http://www.foment.dev/carregarfotos
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		 
		// Obtenir id's de socis i mirar si tenen foto
		$em = $this->getDoctrine()->getManager();
	
		$strQuery = 'SELECT s FROM Foment\GestioBundle\Entity\Soci s ORDER BY s.id';
	
    	$query = $em->createQuery($strQuery);
	
	    $socis = $query->getResult();
	    	 
	    $fotos_temp = __DIR__.'/../../../../web/tmp/';
    	$upload_dir = __DIR__.UtilsController::PATH_TO_WEB_FILES.UtilsController::PATH_REL_TO_UPLOADS;
	    	 
	    $i = 0;
		$total = 0;
		 
		$j = 0;
		 
		$fs = new Filesystem();
		try {
		// Carregar imatges trobades
			foreach ($socis as $s) {
				$filename = $s->getId().".jpg";
				$total++;
				if ($fs->exists($fotos_temp.$filename)) {
					$i++;
					$dst_filename = $s->getId()."_".$s->getNomCognoms().".jpg";
					$fs->copy($fotos_temp.$filename, $upload_dir.$dst_filename);
			
					$foto = new Imatge(new File($upload_dir.$dst_filename));
					
					$foto->setPath($dst_filename);
					$foto->setTitol("Foto carnet soci/a " . $s->getNomCognoms() ." carregada en data ". date('d/m/Y'));
					$s->setFoto($foto);
					$s->setDatamodificacio(new \DateTime());
					$em->persist($foto);
					
					$fs->remove($fotos_temp.$filename);
					//$fs->rename($fotos_temp.$filename, $fotos_temp."_".$filename);
				}
			}
	
			$em->flush();
			
			// Llistar imatges no trobades que queden
			$finder = new Finder();
	
			$finder->in($fotos_temp);
			foreach ($finder as $file) {
				$j++;
				print $file->getFilename().", \n";
			}
	
		} catch (IOException $e) {
			throw new NotFoundHttpException("Error ".$i." de " .$total." ". $e->getMessage());
		}
		return new Response("Carregades ". $i . " fotos de " . $total . " socis (".$j." no trobades)");
	 
	}

}
