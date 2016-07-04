<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\FormError;

use Foment\GestioBundle\Entity\Soci;
use Foment\GestioBundle\Entity\Persona;
use Foment\GestioBundle\Entity\Proveidor;
use Foment\GestioBundle\Entity\Seccio;
use Foment\GestioBundle\Entity\Junta;
use Foment\GestioBundle\Entity\Activitat;
use Foment\GestioBundle\Entity\Periode;
use Foment\GestioBundle\Entity\Docencia;
use Foment\GestioBundle\Form\FormSoci;
use Foment\GestioBundle\Form\FormPersona;
use Foment\GestioBundle\Form\FormProveidor;
use Foment\GestioBundle\Form\FormSeccio;
use Foment\GestioBundle\Form\FormJunta;
use Foment\GestioBundle\Form\FormActivitat;
use Foment\GestioBundle\Entity\Rebut;
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
    			'data'		=> date('Y') - 1 ))
    	/*->add('telefon', 'integer', array(
    			'required'  => true,
    	))
    	->add('nom', 'text', array(
    			'required'  => true,
    	))
    	->add('justificant', 'integer', array(
    			'required'  => true,
    	))*/ 
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
    			'nomail' => $queryparams['nomail'], 'mail' => $queryparams['mail'],
    			'cercaactivitats' => implode(",", $queryparams['activitats']), 'seccions' => $queryparams['seccions']);
    
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
    	->add('numini', 'integer', array('required' => false,  'read_only' => false))
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
    	->add('nomail', 'checkbox')
    	->add('mail', 'text', array('required' => false,  'read_only' => ($defaultData['nomail'] == true)))
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
    					0 => 'tots',
    					1 => 'vigents',
    					2 => 'baixas', 
    					3 => 'no socis', 
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
    	
    	if (count($persones) == 1) {
    		$persona = $persones[0];
    		return $this->redirect($this->generateUrl('foment_gestio_veuredadespersonals', array( 'id' => $persona->getId(), 'soci' => $persona->esSoci(), 'tab' => 0 )));
    	}
    	
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
    	 
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'dataemissio', 'direction' => 'desc', 'perpage' => 5));
    	$queryparams['persona'] = $persona->getId();
    	$queryparams['tab'] = $tab;

    	$rebutspaginate = $this->getRebutsPersona($queryparams, $persona);
    	
    	if (!$essoci) {
    		
    		$form = $this->createForm(new FormPersona(), $persona);

    		return $this->render('FomentGestioBundle:Pages:persona.html.twig',
    				array('form' => $form->createView(), 'persona' => $persona,
    					'rebuts' => $rebutspaginate, 'queryparams' => $queryparams ));
    	}
    	
    	$form = $this->createForm(new FormSoci(), $persona);
    	
    	return $this->render('FomentGestioBundle:Pages:soci.html.twig',
    			array('form' => $form->createView(), 'persona' => $persona,
    					'rebuts' => $rebutspaginate, 'queryparams' => $queryparams ));
    }
    
    private function getRebutsPersona($queryparams, $persona) {
    	$rebutsArray = $this->ordenarArrayObjectes($persona->getRebutsPersona(true), $queryparams);
    	$paginator  = $this->get('knp_paginator');
    	 
    	$rebutspaginate = $paginator->paginate(
    			$rebutsArray,
    			$queryparams['page'],
    			$queryparams['perpage'] //limit per page
    			);
    	unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
    	//$rebutsdetallpaginate->setParam('id', $id); // Add extra request params. Seccio id
    	//$rebutsdetallpaginate->setParam('perpage', $queryparams['perpage']);
    	return $rebutspaginate;
    }
       
    /* Desar dades personals no soci */
    public function desarpersonaAction(Request $request)
    {
    	$activitatstmp = '';
    	$membredetmp = '';
    	$persona = null;
    	$errorField = array('field' => '', 'text' => '');
    	
    	try {
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
	    		$persona = $em->getRepository('FomentGestioBundle:Persona')->find($id);
	    	} else {
		    	$persona = new Persona();
	    	}
	    	
	    	$form = $this->createForm(new FormPersona(), $persona);
	    	 
	    	$form->handleRequest($request);
	    	
	    	$this->validacionsDadesPersonals($form, $persona, $errorField); // Validacions camps persona només per a socis
	    	
	    	if ($form->isValid() != true) { // Validacions camps persona només per a socis
	    		//$errorField = array('field' => 'titular', 'text' => 'informar titular');
	    		throw new \Exception('Cal revisar les dades del formulari d\'aquesta persona');
	    	}
	    	
	    	
	    	$activitatsActualsIds = $persona->getActivitatsIds();
	    	foreach ($activitatsids as $actid)  {
	    		if (!in_array($actid, $activitatsActualsIds)) {
	    			// No està nova activitat
	    			$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($actid);
	    			
	    			$this->inscriureParticipant($activitat, $persona);
	    		} else {
	    			// Manté la secció
	    			unset($activitatsActualsIds[$actid]);
	    		}
	    	}
	    	foreach ($activitatsActualsIds as $actid)  {  // Per esborrar les que queden
	    		$this->esborrarParticipant($actid, $persona);
	    	}
	    		
	    	$persona->setDatamodificacio(new \DateTime());
	    	
	    	
	    	if ($persona->getId() == 0) {
	    		$em->persist($persona);
	    		$em->flush();
	    		$this->get('session')->getFlashBag()->add('notice',	'Noves dades personals desades correctament, afegir-ne un altre');
	    		return $this->redirect($this->generateUrl('foment_gestio_novapersona')); // Novament formulari si era una alta
	    	}
	    		
	    	$em->flush();
	    		
	    	$this->get('session')->getFlashBag()->add('notice',	'Dades personals desades correctament');
	    		
	    	return $this->redirect($this->generateUrl('foment_gestio_veuredadespersonals', 
	    					array( 'id' => $persona->getId(), 'soci' => false, 'tab' => $tab ))); 
    	
    	} catch (\Exception $e) {
    	
    		$this->get('session')->getFlashBag()->clear();
    		$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    	
    		$form = $this->createForm(new FormPersona(), $persona);  // Tornar a carregar dades enviades amb la persona modificada
    			
    		// Afegir els errors dels camps si escau
    		if (isset($errorField['field']) && isset($errorField['text'])) {
    			if ($form->has($errorField['field'])) $form->get( $errorField['field'] )->addError(new FormError( $errorField['text'] ));
    			else {
    				if ($form->get('compte')->has( $errorField['field'] ) ) $form->get('compte')->get( $errorField['field'] )->addError(new FormError( $errorField['text'] ));
    			}
    		}
    	}
    	
    	
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'dataemissio', 'direction' => 'desc'));
    	$queryparams['persona'] = $persona->getId();
    	$queryparams['tab'] = $tab;
    	
    	$rebutspaginate = $this->getRebutsPersona($queryparams, $persona);
    	
    	return $this->render('FomentGestioBundle:Pages:persona.html.twig',
    			array('form' => $form->createView(), 'persona' => $persona,
    					'rebuts' => $rebutspaginate, 'queryparams' => $queryparams ));
    }
    
    /* Desar dades personals soci */
    public function desarsociAction(Request $request)
    {
    	$activitatstmp = '';
    	$membredetmp = '';
    	$soci = null;
    	$errorField = array('field' => '', 'text' => '');
    	
    	try {
    	
	    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
	    		throw new AccessDeniedException();
	    	}
	    	
	    	if ($request->getMethod() == 'GET') return $this->forward('FomentGestioBundle:Pages:nousoci');
	    	 
	    	$em = $this->getDoctrine()->getManager();
	    	 
	    	$data = $request->request->get('soci');
	    	$activitatstmp = (isset($data['activitatstmp'])?$data['activitatstmp']:'');
	    	$membredetmp = (isset($data['membredetmp'])?$data['membredetmp']:'');

	    	$id = (isset($data['id'])?$data['id']:0);
	    	$tab = (isset($data['tab'])?$data['tab']:UtilsController::TAB_SECCIONS);
	    	
	    	$soci = $em->getRepository('FomentGestioBundle:Soci')->find($id);
	    	
	    	$pagamentfraccionatOriginal = false; 
	    	if ($soci == null) {
	    		$soci = new Soci();
	    		$em->persist($soci);
	    	} else {
	    		$pagamentfraccionatOriginal = $soci->getPagamentfraccionat();
	    	}
	    	
	    	$form = $this->createForm(new FormSoci(), $soci);
	    	
	    	$form->handleRequest($request);

	    	// Deudor rebut
	    	if ($data['deudorrebuts'] == 1) $soci->setSocirebut($soci);
	    	else {
	    		if ($soci->getSocirebut() == null) {
	    			$tab = UtilsController::TAB_CAIXA;
	    			throw new \Exception('Cal indicar el soci que es farà càrrec dels rebuts');
	    		}
	    	}
	    	
	    	$activitatsids = array();
	    	if ($activitatstmp != '') $activitatsids = explode(',',$activitatstmp); // array ids activitats llista
	    	
	    	$seccionsIds = array();
	    	if ($membredetmp != '') $seccionsIds = explode(',',$membredetmp);
	    	
	    	$seccionsActualsIds = $soci->getSeccionsIds();
	    	foreach ($seccionsIds as $secid)  {
	    		if (!in_array($secid, $seccionsActualsIds)) {
	    			$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($secid);
	    			// No pertany a la secció
	    			$this->inscriureMembre($seccio, $soci, date('Y'));
	    		} else {
	    			// Manté la secció
	    			$key = array_search($secid, $seccionsActualsIds);
	    			unset($seccionsActualsIds[$key]);
	    		}
	    	}
	    	foreach ($seccionsActualsIds as $secid)  {  // Per esborrar les que queden
	    		$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($secid);
	    		$this->esborrarMembre($seccio, $soci, date('Y'));
	    	}
	    	
	    	
	    	$activitatsActualsIds = $soci->getActivitatsIds();
	    	foreach ($activitatsids as $actid)  {
	    		if (!in_array($actid, $activitatsActualsIds)) {
	    			$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($actid);
	    			// No està nova activitat
	    			$this->inscriureParticipant($activitat, $soci);
	    		} else {
	    			// Manté l'activitat
	    			$key = array_search($actid, $activitatsActualsIds);
	    			unset($activitatsActualsIds[$key]);
	    		}
	    	}
	    	foreach ($activitatsActualsIds as $actid)  {  // Per esborrar les que queden
	    		$this->esborrarParticipant($actid, $soci);
	    	}
	    	
	    	$this->validacionsSociDadesPersonals($form, $soci, $errorField); // Validacions camps persona només per a socis
	    	
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
	    	
	    	// Esborrar avaladors
	    	foreach ($arrayAvaladorRemove as $currAvaladors) {
	    		$soci->removeAvalador($currAvaladors);
	    		$currAvaladors->removeAvalat($soci);
	    	}
	    	
	    	// Afegir avaladors
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
	   		
	   		// Compte totalment informat sinó error
	   		$this->validarCompteCorrent($form, $soci, $tab, $errorField);
			
			if ($form->isValid() != true) { // Validacions camps persona només per a socis
				//$errorField = array('field' => 'titular', 'text' => 'informar titular');
				throw new \Exception('Cal revisar les dades del formulari del soci');
			}
			
	   		// Vigilar canvis pagament fraccionata => anual si existeix la primera facturació però no la segona
	   		// Soci podria paga només la meitat de la quota
	   		$periode1 = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => date('Y'), 'semestre' => 1));
	   		$periode2 = $em->getRepository('FomentGestioBundle:Periode')->findBy(array('anyperiode' => date('Y'), 'semestre' => 2));
	   		  
	   		if ($periode1 != null && $periode2 == null && $pagamentfraccionatOriginal == true && $soci->getPagamentfraccionat() ==false) {
	   			$tab = UtilsController::TAB_CAIXA;
	   		 	throw new \Exception('No es pot activar el pagament anual fins que es generi la facturació del 2n semestre ');
	   		}

	   		$desvincular = (isset($data['socisdesvincular'])?$data['socisdesvincular']:'');
	   		$this->desvincularSocisRebuts($soci, $desvincular);
	   			
	   		$soci->setDatamodificacio(new \DateTime());
		    	
	   		//if ($soci->getId() == 0) $em->persist($soci);
		    
	   		if ($soci->getId() == 0) {
	   			$em->flush();
	   			$this->get('session')->getFlashBag()->add('notice',	'Afegir un altre soci');
	   			return $this->redirect($this->generateUrl('foment_gestio_nousoci')); // Novament formulari si és alta soci
	   		}
	   		
	   		$em->flush();
	    	
			$this->get('session')->getFlashBag()->add('notice',	'Dades del soci desades correctament');

			return $this->redirect($this->generateUrl('foment_gestio_veuredadespersonals',
	  					array( 'id' => $soci->getId(), 'soci' => $soci->esSociVigent(), 'tab' => $tab )));
    	
    	} catch (\Exception $e) {
    		
    		$this->get('session')->getFlashBag()->clear();
    		$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    		
    		
    		if ($soci->getCompte() != null) $em->persist($soci->getCompte());
    		
    		$form = $this->createForm(new FormSoci(), $soci);  // Tornar a carregar dades enviades amb el soci modificat
 		
    		// Afegir els errors dels camps si escau
    		if (isset($errorField['field']) && isset($errorField['text'])) {
    			if ($form->has($errorField['field'])) $form->get( $errorField['field'] )->addError(new FormError( $errorField['text'] ));
    			else {
    				if ($form->get('compte')->has( $errorField['field'] ) ) $form->get('compte')->get( $errorField['field'] )->addError(new FormError( $errorField['text'] ));
    			}
    		}
    	}

    	$queryparams = $this->queryTableSort($request, array( 'id' => 'dataemissio', 'direction' => 'desc'));
    	$queryparams['persona'] = $soci->getId();
    	$queryparams['tab'] = $tab;
    	
    	$queryparams['activitatstmp'] = $activitatstmp;
    	$queryparams['membredetmp'] = $membredetmp;
    	
    	
    	$rebutspaginate = $this->getRebutsPersona($queryparams, $soci);

    	return $this->render('FomentGestioBundle:Pages:soci.html.twig',
    			array('form' => $form->createView(), 'persona' => $soci,
    					'rebuts' => $rebutspaginate, 'queryparams' => $queryparams )); 
    }
    
    private function desvincularSocisRebuts($soci, $desvincular) {
		
    	$socisIdsDesvincular = array(); 
    	if ($desvincular != '') $socisIdsDesvincular = explode(",",$desvincular);
    		
    	if (count($socisIdsDesvincular) > 0) {
    	
    		$em = $this->getDoctrine()->getManager();
    		
    		$socirebut = $soci->getSocirebut(); // pagador actual
    		
    		$socisacarrec = array();
    		if($soci->getSocirebut() === $soci) $socisacarrec = $soci->getSocisacarrec();
    		else $socisacarrec = $soci->getSocirebut()->getSocisacarrec();
    		foreach ($socisacarrec as $sociGrup) {
    			// Comprovar si cal desvincular soci
    			$id = $sociGrup->getId();
    			if ($sociGrup->esSociVigent() && in_array($id, $socisIdsDesvincular)) {
    				$sociGrup->setSocirebut($sociGrup);
    				if ($sociGrup->getCompte() == null) $sociGrup->setTipuspagament(UtilsController::INDEX_FINESTRETA);
    			}
    		}
    		
    		$rebuts = $socirebut->getRebutsPersona(false);  // rebuts actuals del pagador
    	
    		$membreGrupFacturar = array();
    		$numrebuts = array( date('Y') => $this->getMaxRebutNumAnySeccio(date('Y')) ); // Max
    		foreach ($rebuts as $rebut) {
    			
    			if (!$rebut->cobrat() && $rebut->esSeccio()) { // Canvia els rebuts de secció pendents (ni cobrats ni de baixa), donar de baixa detalls soci desvinculat

    				$anyrebut = $rebut->getDataemissio()->format('Y');
    				if (!isset($numrebuts[ $anyrebut ])) {
    					$numrebuts[ $anyrebut ] = $this->getMaxRebutNumAnySeccio( $anyrebut );
    				}
    	
    				$periodenf = $rebut->getPeriodenf();
    				$periode = $rebut->getFacturacio() == null?$periodenf:$rebut->getFacturacio()->getPeriode();
    	
    				$detalls = $rebut->getDetallsSortedByNum(false);
    				foreach ($detalls as $d) {
    					$sociQuota = $d->getQuotaseccio()->getSoci();

    					if ($sociQuota->getId() != $socirebut->getId() && in_array($sociQuota->getId(), $socisIdsDesvincular)) {  // Quota a desvincular
    						
							if (!isset($membreGrupFacturar[$sociQuota->getId()])) $membreGrupFacturar[$sociQuota->getId()] = array('soci' => null, 'quotes' => array());
    						
    						$novaquota = clone $d->getQuotaseccio();
    						
    						$novaquota->setSoci( $sociQuota );
    						$membreGrupFacturar[$sociQuota->getId()]['soci'] = $sociQuota;
    						$membreGrupFacturar[$sociQuota->getId()]['quotes'][] = $novaquota; 
    						$d->baixa();
    					}
    				}
    	
    				foreach ($membreGrupFacturar as $sociQuotaId => $quotes) {
    					// Crear nou rebut per al soci desvinculat amb les quotes del rebut original
    					$nourebut = new Rebut($quotes['soci'], $rebut->getDataemissio(), $numrebuts[ $anyrebut ], true, $periodenf);
    					$nourebut->setFacturacio( $rebut->getFacturacio() );
    					if ($nourebut->enDomiciliacio()) $nourebut->$this->setTipuspagament( UtilsController::INDEX_FINESTRETA );
    					$em->persist($nourebut);
    	
    					foreach ($quotes['quotes'] as $membre) {
    						
    						$rebutdetall = $this->generarRebutDetallMembre($membre, $nourebut, $periode);
    	
    						if ($rebutdetall != null) {
    							$em->persist($membre);
    							$em->persist($rebutdetall);
    						}
    					}
    	
    					if ($nourebut->getImport() <= 0) {
    						$nourebut->detach();
    						$em->detach($nourebut);
    					} else {
    						$numrebuts[ $anyrebut ]++;
    					}
    				}
    			}
    		}
    	}
    }
    
    
    private function validarCompteCorrent($form, $soci, &$tab, &$errorField) {
    	
    	$compte = $soci->getCompte();
    	 
    	try {
    		if ($compte == null || ($compte->getCompte20() == '' && $compte->getTitular() == '' &&  $compte->getIban() == '')) {
    			$soci->setCompte(null);
    			
    			if ($soci->esPagamentFinestreta()) return;
    			
    			if (!$soci->esDeudorDelGrup()) {
    				$soci->setTipuspagament(UtilsController::INDEX_FINESTRETA);
    				return;
    			}
    			
    			throw new \Exception('Cal indicar les dades del compte corrent');
    		}
    		
    		if ($form->get('compte')->isValid() != true) {
    			$tab = UtilsController::TAB_CAIXA;
    		
    			if ($compte->getTitular() == '') $errorField = array('field' => 'titular', 'text' => 'informar titular');
    			if ($compte->getBanc() == '')  $errorField = array('field' => 'banc', 'text' => 'revisar la entitat');
    			if ($compte->getAgencia() == '')  $errorField = array('field' => 'agencia', 'text' => 'revisar oficina');
    			if ($compte->getDc() == '')  $errorField = array('field' => 'dc', 'text' => 'revisar dígits de control');
    			if ($compte->getNumcompte() == '')  $errorField = array('field' => 'numcompte', 'text' => 'revisar el compte');
    			if ($compte->getCompte20() == '')  $errorField = array('field' => 'iban', 'text' => 'revisar iban');
    			 
    			throw new \Exception('El número de compte no és correcte');
    		}
    		
    		
    		if ($compte->getTitular() == '') {
    			$errorField = array('field' => 'titular', 'text' => 'informar titular');
	    		throw new \Exception('Cal indicar el titular del compte');
	    	}
	    	
	    	if ($compte->getIban() != '') {
	    		$iban = $compte->getIban();
	    		//ESXXBBBBOOOODDNNNNNNNNNN
	    		$numbanc = substr($iban, 4, 4);
	    		$numagencia = substr($iban, 8, 4);
	    		$numdc = substr($iban, 12, 2); 
	    		$numcompte = substr($iban, 14, 10);
	    	} else {
	    		// Calcular iban
	    		$ibandigits = str_pad(98 - bcmod($compte->getCompte20().'142800',97), 2, "0", STR_PAD_LEFT);
	    		$iban = 'ES'.$ibandigits.$compte->getCompte20();
	    		$iban = $compte->setIban($iban);
	    		
		    	$numcompte = str_pad($compte->getNumcompte(), 10, "0", STR_PAD_LEFT);
	    		$numbanc = str_pad($compte->getBanc(), 4, "0", STR_PAD_LEFT);
	    		$numagencia = str_pad($compte->getAgencia(), 4, "0", STR_PAD_LEFT);
	    		$numdc = str_pad($compte->getDc(), 2, "0", STR_PAD_LEFT);
	    	}
	    	
	    	$compte->setNumcompte($numcompte);
	    	if (!is_numeric($numcompte)) {
	    		$errorField = array('field' => 'numcompte', 'text' => 'revisar el compte');
	   			throw new \Exception('El número de compte no és numèric');
	   		}
	   		if ($numcompte < 0 || $numcompte > 9999999999) {
	   			$errorField = array('field' => 'numcompte', 'text' => 'revisar el compte');
	   			throw new \Exception('El número de compte ha d\'estar entre 0 i 9999999999');
	   		}
	   		
	    	$compte->setBanc($numbanc);
	    	if (!is_numeric($numbanc)) {
	    		$errorField = array('field' => 'banc', 'text' => 'revisar la entitat');
	    		throw new \Exception('El número de banc no és numèric');
	    	}
	    	if ($numbanc < 0 || $numbanc > 9999) {
	    		$errorField = array('field' => 'banc', 'text' => 'revisar la entitat');
	    		throw new \Exception('El número de banc ha d\'estar entre 0 i 9999');
	    	} 
	    	
	    	$compte->setAgencia($numagencia);
	        if (!is_numeric($numagencia)) {
	    		$errorField = array('field' => 'agencia', 'text' => 'revisar oficina');
	    		throw new \Exception('El número d\'oficina no és numèric');
	    	}
	    	if ($numagencia < 0 || $numagencia > 9999) {
	    		$errorField = array('field' => 'agencia', 'text' => 'revisar oficina');
	    		throw new \Exception('El número d\'oficina ha d\'estar entre 0 i 9999');
	    	} 
	    	
	    	$compte->setDc($numdc);
	    	if (!is_numeric($numdc)) {
	    		$errorField = array('field' => 'dc', 'text' => 'revisar dígits de control');
	    		throw new \Exception('Els dígits de control no són numèrics');
	    	}
	    	if ($numdc < 0 || $numdc > 99) {
	    		$errorField = array('field' => 'dc', 'text' => 'revisar dígits de control');
	    		throw new \Exception('Els dígits de control han d\'estar entre 0 i 99');
	    	}
	    	 
	    	// Dígits de control
	    	$valores = array(1, 2, 4, 8, 5, 10, 9, 7, 3, 6);
		    	
	    	$controlCS = 0;
	    	$controlCC = 0;
		    	
	    	$strBancAgencia = $numbanc.$numagencia;
	    	$strCCC = $numcompte;
		    	
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
	    		$errorField = array('field' => 'dc', 'text' => 'dígits incorrectes');
	    		throw new \Exception('El valor dels dígits no és l\'esperat '.$dcCalc);
	    	}    	
	    } catch (\Exception $e) {
	    	$tab = UtilsController::TAB_CAIXA;
	    	throw new \Exception($e->getMessage());
	    }
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
	    			$socisacarrec = $soci->getSocisDepenents();
	    			
	    			if (count($socisacarrec) > 0) throw new \Exception('Aquest soci es fa càrrec dels rebuts d\'altres i no es pot esborrar. Cal assignar algú altre que se\'n faci càrrec' ); 
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
	    		
	    		$queryparams['persona'] = $soci->getId();
	    		$queryparams['tab'] = UtilsController::TAB_CAIXA;
	    		 
	    		$rebutspaginate = $this->getRebutsPersona($queryparams, $soci);
	    		
	    		return $this->render('FomentGestioBundle:Pages:soci.html.twig',
	    				array('form' => $form->createView(), 'persona' => $soci,
	    						'rebuts' => $rebutspaginate, 'queryparams' => $queryparams ));
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
    				$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($actid);
    				
    				$this->inscriureParticipant($activitat, $persona);
    			}
    		}
    	}

    	$form = $this->createForm(new FormPersona(), $persona);
    
    	$queryparams['persona'] = $persona->getId();
    	$queryparams['tab'] = $tab;
    	
    	$rebutspaginate = $this->getRebutsPersona($queryparams, $persona);
    	
    	return $this->render('FomentGestioBundle:Pages:persona.html.twig',
    			array('form' => $form->createView(), 'persona' => $persona,
    					'rebuts' => $rebutspaginate, 'queryparams' => $queryparams));
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
	    $strNaixement = $request->query->get('datanaixement', '');
	    $errorField = array('field' => '', 'text' => '');
	    	
	    $em = $this->getDoctrine()->getManager();
	
	    $soci = null;
	    $form = null;
	    $persona = $em->getRepository('FomentGestioBundle:Persona')->find($id);
	    
	    try {
	    	
	    	if ($persona != null) {
	    		// Cercar persona i convertir en soci
	    		if ($persona->esSoci()) {
					$soci = $em->getRepository('FomentGestioBundle:Soci')->find($id);
	    		}
	    		else {
					if ($strNaixement == '') throw new \Exception('Cal indicar la data de naixement');

					$datanaixement = \DateTime::createFromFormat('d/m/Y', $strNaixement );
					$persona->setDatanaixement($datanaixement);
					
					$em->flush();

	    			$soci = new Soci($persona);
	    			$em->persist($soci);
	    		}
	    		
	    		$soci->setnum($this->getMaxNumSoci()); // Número nou
	    		    		
	    		$soci->setDatamodificacio(new \DateTime());
	    		$soci->setDatabaixa(null);
	    		
	    		// Per defecte ell com a soci
	    		if (!$soci->getSocirebut()->esSociVigent()) $soci->setSocirebut($soci);
	    		
	    		$form = $this->createForm(new FormSoci(), $soci);
	    		$this->validacionsSociDadesPersonals($form, $soci, $errorField);
	    		if (!$persona->esSoci())  {
					// Desactivar generació automàtica identificar per la classe AUTO id     		
		    		$metadata = $em->getClassMetaData('FomentGestioBundle:Soci');
	
			    	//$metadata->setIdGenerator(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
		    		$metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
			    		
					// Canvi a Soci directament des de SQL. Doctrine no deixa
			    	$query = "UPDATE persones SET rol = 'S' WHERE id = ".$id;
			    	$em->getConnection()->exec( $query );
	
					// Inserció només Soci
			    	$query =  "INSERT INTO socis (id, num, tipus, vistiplau, datavistiplau, dataalta, ";
			    	$query .= "tipuspagament, descomptefamilia, pagamentfraccionat, exempt, quotajuvenil, familianombrosa) ";
			    	$query .= " VALUES (".$id.", ".$soci->getNum().", ".$soci->getTipus().", 0, '".$soci->getDataalta()->format('Y-m-d')."', '".$soci->getDataalta()->format('Y-m-d')."',";
			    	$query .=  UtilsController::INDEX_FINESTRETA.", 0, 0, 0, 0, 0)";
			    	$em->getConnection()->exec( $query );
			    	
			    	
			    	$em->clear();
			    	
			    	$soci = $em->getRepository('FomentGestioBundle:Soci')->find($id);
		    	}  
		    	
		    	$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find(UtilsController::ID_FOMENT);
		    	$membre = $soci->getMembreBySeccioId(UtilsController::ID_FOMENT);
		    	if ($seccio != null && ( $membre == null || ($membre != null && $membre->getDatacancelacio() != null) ) ) $this->inscriureMembre($seccio, $soci, date('Y')); // Crear rebuts si ja estan generats en el periode
		    	
			    $em->flush();
	    		
			    return $this->redirect($this->generateUrl('foment_gestio_veuredadespersonals',
			    		array( 'id' => $id, 'soci' => true, 'tab' => UtilsController::TAB_SECCIONS )));
	    	} else {
	    		// nou soci
	    		$datapersona = $request->query->get('persona', null);
	    		
	    		$bagTmp = $this->get('session')->getFlashBag()->peekAll();
	    		
	    		$soci = new Soci();
	    		if ($datapersona != null) {
	    			// Carregar dades form
	    			$soci = new Soci($datapersona);

	    			// Activitats
	    			$stractivitats = (isset($datapersona['activitatstmp'])?$datapersona['activitatstmp']:'');
	    			$activitatsids = array();
	    			if ($stractivitats != '') $activitatsids = explode(',',$stractivitats); // array ids activitats llista
	    			
	    			foreach ($activitatsids as $actid)  {
	    				$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($actid);
	    				
	    				$this->inscriureParticipant($activitat, $soci);
	    			}
	    		}
	    		
	    		$em->persist($soci);
	    		
	    		$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find(UtilsController::ID_FOMENT);
	    		if ($seccio != null) $this->inscriureMembre($seccio, $soci, date('Y')); // Crear rebuts si ja estan generats en el periode
	    		
	    		$soci->setnum($this->getMaxNumSoci());
	    		
	    		$form = $this->createForm(new FormSoci(), $soci);
	    		
	    		$this->get('session')->getFlashBag()->clear(); // No missatge rebuts ni inscripcio
	    		if (count($bagTmp) > 0) $this->get('session')->getFlashBag()->setAll($bagTmp);
	    	}
	    	
    	} catch (\Exception $e) {
    	
    		$this->get('session')->getFlashBag()->clear();
    		$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
    		
    	}
    	
    	$queryparams['persona'] = $soci->getId();
    	$queryparams['tab'] = $tab;
    	 
    	$rebutspaginate = $this->getRebutsPersona($queryparams, $soci);

    	if ($persona != null && !$persona->esSoci()) {
    		$form = $this->createForm(new FormPersona(), $persona);
    		 
    		return $this->render('FomentGestioBundle:Pages:persona.html.twig',
    				array('form' => $form->createView(), 'persona' => $persona,
    						'rebuts' => $rebutspaginate, 'queryparams' => $queryparams ));
    	}
    	
    	if ($form == null) $form = $this->createForm(new FormSoci(), $soci);
    	
    	return $this->render('FomentGestioBundle:Pages:soci.html.twig',
    			array('form' => $form->createView(), 'persona' => $soci,
    					'rebuts' => $rebutspaginate, 'queryparams' => $queryparams ));
    }
    
    private function validacionsDadesPersonals($form, $persona, &$errorField) {
    	// Validacions camps persona només per a socis
    	if ($persona->getNom() == null || $persona->getNom() == '') {
    		$errorField = array('field' => 'nom', 'text' => 'Nom obligatori');
    		throw new \Exception('Cal indicar el nom');
    	}
    	 
    	if ($persona->getCognoms() == null || $persona->getCognoms() == '') {
    		$errorField = array('field' => 'cognoms', 'text' => 'Cognoms obligatoris');
    		throw new \Exception('Cal indicar els cognoms');
    	}
    }
    
    private function validacionsSociDadesPersonals($form, $soci, &$errorField) {
    	// Validacions camps persona només per a socis
    	$this->validacionsDadesPersonals($form, $soci, $errorField);
    	 
    	if ($soci->getDatanaixement() == null) {
    		$errorField = array('field' => 'datanaixement', 'text' => 'Data de naixement');
    		throw new \Exception('Cal indicar la data de naixement del soci');
    	} 
    	
    	/*if ($soci->getDni() == null || $soci->getDni() == '') {
    		$errorField = array('field' => 'dni', 'text' => 'Indicar DNI');
    		throw new \Exception('Cal indicar el DNI del soci');
    	}*/
    	 
    	/*if ($soci->getAdreca() == null || $soci->getAdreca() == '') {
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
    	}*/
    	
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
    	
    	$queryparams = $this->queryTableSort($request, array( 'id' => 's.ordre', 'direction' => 'asc'));
    	
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
    	
    	$sortkeys = array('nom' => 's.nom', 'ordre' => 's.ordre', 'import' =>  'q.import', 'importjuvenil' => 'q.importjuvenil', 'membres' => 'membres');
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
	    			
	    			if ($seccio->getSemestral() == true) $seccio->setFacturacions(2);
	    			
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
    	
    	$numrebut = $this->getMaxRebutNumAnySeccio($anydades); // Max
    	
    	if ($seccio->getSemestral() == true) {
	    	// Si no existeixen facturacions iguals o posteriors a l'any d'alta, no cal fer res
	    	// En cas contrari cal afegir els rebuts
	    	$periodes = $this->queryGetPeriodesPendents($membre->getDatainscripcio()); // Obtenir els periodes actual i futurs i afegir factures
	   
	    	$socipagarebut = null; // Soci agrupa rebuts per pagar
	    	$rebut = null;
	    	
	    	$strRebuts = "";
	    	
	    	$current = new \DateTime();
	    	
	    	foreach ($periodes as $periode) {
	    		
	    		/**************************** Crear el rebut per aquest nou soci per cada periode facturat ****************************/
	    		
	    		//if (!$periode->facturable()) throw new \Exception('No es poden afegir rebuts a les dates indicades' );
	    		
	    		if ($periode->facturable()) {  // Si hi ha periode facturar. Té rebuts pendents de domiciliar
		    		$socipagarebut  = $noumembre->getSocirebut();
		    		
		    		if ($socipagarebut == null) throw new \Exception('Cal indicar qui es farà càrrec dels rebuts del soci: '.$noumembre->getNomCognoms().'' ); 
		    		
		    		$rebut = $periode->getRebutPendentByPersonaDeutora($socipagarebut);
		    		
		    		$dataemissio = $periode->getDatainici();  // Inici periode o posterior
		    		if ($current > $periode->getDatainici()) $dataemissio = $current;
		    		
		    		if ($rebut == null) {
		    			// Crear rebut nou
		    			$rebut = new Rebut($socipagarebut, $dataemissio, $numrebut, true, $periode);
		    			$numrebut++;
		    			
		    			$em->persist($rebut);
		    			
		    			$strRebuts .= 'Nou rebut generat '. $rebut->getNumFormat() . '<br/>';
		    		} else {
		    			$strRebuts .= 'Quota afegida al rebut '. $rebut->getNumFormat() . '<br/>';
		    		}
		    		
		    		$rebutdetall = $this->generarRebutDetallMembre($membre, $rebut, $periode);
		    		
		    		if ($rebutdetall != null) $em->persist($rebutdetall);
		    		else {
		    			$strRebuts = ""; // No hi ha detall, secció quota 0
		    			//$em->clear();
		    			
		    			/*if ($rebut != null) {
		    				$socipagarebut->removeRebut($rebut);
		    				if ($rebut->getId() == 0) $em->detach($rebut);
		    				else $em->refresh($rebut);
		    			}
		    			throw new \Exception('No s\'ha pogut generar el rebut correctament' ); */
		    		}
	    		}
	    	}
	    	
	    	$this->get('session')->getFlashBag()->add('notice',	($noumembre->getSexe()=='H'?'En ':'Na ').$noumembre->getNomCognoms().' s\'ha inscrit correctament a la secció '.$seccio->getNom());
	    	if ($strRebuts != "") {
	    		$this->get('session')->getFlashBag()->add('notice',	$strRebuts);
	    	}
    	} else {
    		// Les seccions no semestrals sempre les paguen els propis socis per finestreta 
    		//$soci  = $noumembre->getSoci();
    		
    		// Crear tants rebuts com facturacions mensualment
    		$dataemissio = clone $membre->getDatainscripcio();
    			
    		for($facturacio = 0; $facturacio < $seccio->getFacturacions(); $facturacio++) {
    			if ($this->generarRebutSeccio($membre, $dataemissio, $numrebut) != null) { 
    			
	    			$dataemissio = clone $dataemissio; // Totes les facturacions de cop, incrementar un mes
	    			$dataemissio->add(new \DateInterval('P1M'));
		    			
	    			$numrebut++;
    			}
    		}
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
    	
    		if ($seccio->getSemestral() == true) {
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
    		} else {
    			if ($rebut != null) {
    				$detall->baixa();
    				$strRebuts .= ' Rebut '. $rebut->getNumFormat() .' anul·lat<br/>';
    			}
    		}
    	}
    	
    	if (count($esborrarmembre->getSeccionsSortedById()) == 0) {
    		$quotaDelStr = ($esborrarmembre->getSexe()=='H'?'El soci ':'La sòcia ').$esborrarmembre->getNumSoci().'-'.$esborrarmembre->getNomCognoms() .' no pertany a cap secció';
    		$this->get('session')->getFlashBag()->add('error',	$quotaDelStr );
    	}
    	
    	$this->get('session')->getFlashBag()->add('notice',	($esborrarmembre->getSexe()=='H'?'En ':'Na ').$esborrarmembre->getNomCognoms().' ha estat baixa de la secció '.
    							$membre->getSeccio()->getNom().' en data '. $membre->getDatacancelacio()->format('d/m/Y'));
    	if ($strRebuts != "") {
    		$this->get('session')->getFlashBag()->add('notice',	$strRebuts); 
    	} 
    }
    
    
    /* Veure / actualitzar activitats */
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
    
    /* Finalitzar/mostrar activitats. AJAX */
    public function finalitzaractivitatAction(Request $request)
    {
    	try {
	    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
	    		throw new AccessDeniedException();
	    	}
	    
	    	$em = $this->getDoctrine()->getManager();
	    	$activitatId = $request->query->get('id', 0); // Activitat
	    	$ocultar = $request->query->get('ocultar', 1)==1?true:false; // Finalitzat?
	    	 
	    	$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($activitatId);
	    	
	    	if ($activitat == null) throw new NotFoundHttpException('Activitat no trobada');
		    	
		    $activitat->setFinalitzat($ocultar);
		    	
		    $em->flush();
    	} catch (\Exception $e) {
    		$response = new Response($e->getMessage());
    		$response->setStatusCode(500);
    		return $response;
    	}
    	//return $this->forward('FomentGestioBundle:Pages:activitats');
	    return new Response('OK');
    }
    
    
    public function programaciofacturacioAction(Request $request) {
    	// Carrega les programacions sese persistència, només per generar la taula
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$em = $this->getDoctrine()->getManager();
    	$facturacioId = $request->query->get('id', 0); // Curs
    	
    	$facturaciocurs = $em->getRepository('FomentGestioBundle:FacturacioActivitat')->find($facturacioId);
    	
    	if ($facturaciocurs == null) throw new \Exception('Facturtacio no trobada');
    	
    	$setmanal = $request->query->get('setmanal', '');
    	$mensual = $request->query->get('mensual', '');
    	$persessions = $request->query->get('persessions', '');
    	
    	
    	$facturaciocurs->setSetmanal( urldecode($setmanal) );
    	$facturaciocurs->setMensual( urldecode($mensual) );
    	$facturaciocurs->setPersessions( urldecode($persessions) );
    	
    	$em->persist($facturaciocurs);
    	
    	
    	return $this->render('FomentGestioBundle:Includes:taulaprogramaciofacturacio.html.twig',
    			array('facturacio' => $facturaciocurs));

    }
    
    
    /* Veure / actualitzar curs */
    public function activitatAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	$em = $this->getDoctrine()->getManager();
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'cognomsnom', 'direction' => 'desc', 'perpage' => UtilsController::DEFAULT_PERPAGE_WITHFORM));
    	 
    	
    	$tab = UtilsController::TAB_SECCIONS;
    	if ($request->getMethod() == 'POST') {
    		$data = $request->request->get('activitat');
    		 
    		$id = (isset($data['id'])?$data['id']:0);
    		
    		$strFacturacionsIds = (isset($data['facturacionsdeltemp'])?$data['facturacionsdeltemp']:'');
    		
    		$facturacionsIdsEsborrar = array();
    		if ($strFacturacionsIds != '') $facturacionsIdsEsborrar = explode(',',$strFacturacionsIds); // array ids facturacions per esborrar
    		 
    		
    		$facturacionsNoves = (isset($data['facturacions'])?$data['facturacions']:array());
    	} else {
    		$id = $request->query->get('id', 0);
    		$tab = $request->query->get('tab', UtilsController::TAB_SECCIONS);
    		
    	}
    	
    	$curs = $em->getRepository('FomentGestioBundle:Activitat')->find($id);
    	
    	// Crear una facturació segons data d'avui
    	$dataFacturacio = new \DateTime();
    	$desc = '';
    	if ($dataFacturacio->format('m')*1 > 10 )  {
    		$dataFacturacio = \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_FACTURA_CURS_GENER. (date('Y')+1) );
    		$desc = UtilsController::TEXT_FACTURACIO_GENER;
    	}
    	if ($dataFacturacio->format('m')*1 <= 10 &&
    		$dataFacturacio->format('m')*1 > 5  ) {
    		$dataFacturacio = \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_FACTURA_CURS_OCTUBRE. date('Y') );
    		$desc = UtilsController::TEXT_FACTURACIO_OCTUBRE;
    	}
    	if ($dataFacturacio->format('m')*1 <= 5 ) {
    		$dataFacturacio = \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_FACTURA_CURS_ABRIL. (date('Y')+1) );
    		$desc = UtilsController::TEXT_FACTURACIO_ABRIL;
    	}
    	$queryparams['descproto'] = UtilsController::TEXT_FACTURACIO_GENERIC; // Per al proto
    	$queryparams['dataproto'] = $dataFacturacio; // Per al proto
    	$queryparams['ordinalsproto'] = UtilsController::getOrdinalNumbersSeq(12);
    	
    	if ($curs == null ) {
    		$curs = new Activitat();
    		$em->persist($curs);
    		
    		if ($request->getMethod() != 'POST') { // Get nou curs
	    		$facturacio = new FacturacioActivitat($dataFacturacio, UtilsController::INDEX_FINESTRETA, $desc, $curs, 0, 0);
	    		$em->persist($facturacio);
    		}
    	} 
    	
    	$query = $curs->getParticipantsActius();
    	if ($request->getMethod() == 'GET') { 
	    	// Filtre i ordenació dels membres
	    	$query = $this->filtrarArrayNomCognoms($query, $queryparams);
	    	$query = $this->ordenarArrayObjectes($query, $queryparams);
    	}
    	
    	$paginator  = $this->get('knp_paginator');
    	 
    	$participants = $paginator->paginate(
    			$query,
    			$queryparams['page'],
    			$queryparams['perpage'] //limit per page
    	);
    	unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
    	$participants->setParam('id', $id); // Add extra request params. Activitat id
    	$participants->setParam('perpage', $queryparams['perpage']);
    	
    	$form = $this->createForm(new FormActivitat($queryparams), $curs);
    	if ($request->getMethod() == 'POST') {
    
    		$form->handleRequest($request);
    		
    		try {
    		
	    		if ($form->isValid()) {
	    			
	    			$curs->setDatamodificacio(new \DateTime());

	    			if ($curs->getId() == 0) $em->persist($curs);
	    			
	    			if ($curs->getDescripcio() == '' || $curs->getDescripcio() == null) {
	    				$form->get( 'descripcio' )->addError( new FormError('No pot estar buit') );
	    				throw new \Exception('Cal indicar la descripció de l\'activitat' );
	    			}
	    			
	    			$tab = UtilsController::TAB_CURS_FACTURACIO;
    				$this->cursTractamentFacturacio($curs, $participants, $facturacionsIdsEsborrar, $facturacionsNoves, $form);
	    			
	    			$em->flush();
	    			
	    			$tab = UtilsController::TAB_SECCIONS;
	    			$this->get('session')->getFlashBag()->add('notice',	'Curs desat correctament');
	    			// Prevent posting again F5
	    			return $this->redirect($this->generateUrl('foment_gestio_activitat', array( 'id' => $curs->getId(), 'tab' => $tab)));
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
    	
    	return $this->render('FomentGestioBundle:Pages:activitat.html.twig',
    			array('form' => $form->createView(), 'activitat' => $curs,
    					'participants' => $participants, 'queryparams' => $queryparams,
    					'tab' => $tab));
    	 
    }
    
    private function cursTractamentFacturacio($curs, $participants, $facturacionsIdsEsborrar, $facturacionsNoves, $form) {
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
    	
    	$errors = array();
    	$anyFacturaAnt = 0;
    	$numrebut = 0;
    	foreach ($facturacionsNoves as $k => $nova) {
    		$desc = $k;
    		if (!isset($nova['descripcio']) || $nova['descripcio'] == '') {
    			$form->get( 'facturacions' )->get( $k )->get('descripcio')->addError( new FormError('') );
    			$errors[] = 'Cal indicar una descripció';
    		} else {
    			$desc = $nova['descripcio'];
    		}
    		//$desc = str_replace('curs (pendent)', 'curs '.$curs->getDescripcio(), $nova['descripcio']);
    		
    		
    		if (!isset($nova['datafacturacio']) || $nova['datafacturacio'] == '') {
    			$form->get( 'facturacions' )->get( $k )->get('datafacturacio')->addError( new FormError('') );
    			$errors[] = $desc.' > Cal indicar la data per poder fer l\'emissió dels rebuts'; 
    		}
    	
    		$datafacturacio = \DateTime::createFromFormat('d/m/Y', $nova['datafacturacio'] );
    	
    		if (!isset($nova['importactivitat'])  || $nova['importactivitat'] == '') {
    			$form->get( 'facturacions' )->get( $k )->get('importactivitat')->addError( new FormError('') );
    			$errors[] = $desc.' > Cal indicar l\'import del rebut per als socis';
    		}
    		
    		if (!isset($nova['importactivitatnosoci']) || $nova['importactivitatnosoci'] == '') {
    			$form->get( 'facturacions' )->get( $k )->get('importactivitatnosoci')->addError( new FormError('') );
    			$errors[] = $desc.' > Cal indicar l\'import del rebut per als no socis';
    		}
    	
    		$strImport = $nova['importactivitat'];
    		//$import = sscanf($strImport, "%f");
    		$fmt = numfmt_create( 'es_CA', \NumberFormatter::DECIMAL );
    		$import = numfmt_parse($fmt, $strImport);
    		if (!is_numeric($import) || $import <= 0) {
    			$form->get( 'facturacions' )->get( $k )->get('importactivitat')->addError( new FormError('') );
    			$errors[] = $desc.' > L\'import per a socis no és incorrecte '. $import;
    		}
    		 
    		$strImport = $nova['importactivitatnosoci'];
    		$fmt = numfmt_create( 'es_CA', \NumberFormatter::DECIMAL );
    		$importnosoci = numfmt_parse($fmt, $strImport);
    		if (!is_numeric($importnosoci) || $importnosoci <= 0) {
    			$form->get( 'facturacions' )->get( $k )->get('importactivitatnosoci')->addError( new FormError('') );
    			$errors[] = $desc.' > L\'import per a no socis no és incorrecte '. $importnosoci;
    		}
    		
    		$num = $this->getMaxFacturacio();
   	
    		if (count( $errors ) == 0) { 
    			$facturacio = new FacturacioActivitat($dataFacturacio, UtilsController::INDEX_FINESTRETA, $nova['descripcio'], $curs, $import, $importnosoci);
	    		$em->persist($facturacio);
	    		// Generar rebuts participants actius si escau (checkrebuts)
	    		if (isset($nova['checkrebuts'])) { // El check només s'envia si está activat
					if ($anyFacturaAnt == 0 || ($anyFacturaAnt > 0 && $anyFacturaAnt != $datafacturacio->format('Y')) ) {
						// Obtenir $maxnumrebut per l'any 
						$anyFacturaAnt = $datafacturacio->format('Y');
						$numrebut = $this->getMaxRebutNumAnyActivitat($anyFacturaAnt); // Max
					}
	    			foreach ($curs->getParticipantsActius() as $participacio) {
	    				$rebut = $this->generarRebutActivitat($facturacio, $participacio, $numrebut);
	    				if ($rebut != null) $numrebut++;
	    			}
	    		}
	    		
	    		// Tractar les docències per totes les facturacions noves
	    		$strDocenciesJSON = (isset($nova['docenciestmp'])?$nova['docenciestmp']:'');
	    		if ($strDocenciesJSON != '') $this->cursTractamentDocencia($facturacio, $strDocenciesJSON, $form);
    		}
    	}
    	
    	if (count($facturacions = $curs->getFacturacionsActives()) == 0)  throw new \Exception('Cal indicar mínim una facturació ');
    	
    	
    	foreach ($curs->getFacturacionsActives() as $facturacio)  {
    		// Tractar el calendari per totes les facturacions existents
    		$this->cursTractamentCalendari($facturacio, $facturacio->getSetmanal(), $facturacio->getMensual(), $facturacio->getPersessions());
    	}
    	
    	if (count( $errors ) > 0) {
   			throw new \Exception( implode('<br/>', $errors) );
    	}
    }
    
    
    private function cursTractamentCalendari($facturacio, $setmanalPrevi, $setmanal, $mensual, $persessions) {
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
    
    	 
    	// !!!!!!!!!!!!!!!!  PENDENT  !!!!!!!!!!!!!
    	 
    }
    
    private function cursTractamentDocencia($facturacio, $strDocenciesJSON, $form) {
    	$em = $this->getDoctrine()->getManager();
    	// Tractament docencies
    	$json = json_decode($strDocenciesJSON, true);
    	 
    	foreach ($json as $docent) {
    
    		switch ($docent['accio']) {
    			case 'addNew':
    				// Afegir docència
    				$professor = $em->getRepository('FomentGestioBundle:Proveidor')->find($docent['proveidor']);
    
    				if ($professor == null) {
    					$form->get( 'cercardocent' )->addError( new FormError('') );
    					throw new \Exception('No s\'ha trobat el professor '.$docent['proveidor']);
    				}
    
    				/*if ($docent['preutotal'] == '') {
    				 $form->get( 'preutotal' )->addError( new FormError('') );
    				 throw new \Exception('Cal informar l\'import total de la docència');
    				 }
    
    				 $import = $docent['preutotal'];
    				 if (!is_numeric($import) || $import <= 0) {
    				 $form->get( 'preutotal' )->addError( new FormError('') );
    				 throw new \Exception('L\'import total del professor '.$professor->getRaosocial().' és incorrecte '. $import);
    				 }*/
    
    
    				$preuhora = 0;
    				if ($docent['preuhora'] != '') {
    					$preuhora = $docent['preuhora'];
    					if (!is_numeric($preuhora) || $preuhora <= 0) {
    						$form->get( 'preuhora' )->addError( new FormError('') );
    						throw new \Exception('El preu per sessió del professor '.$professor->getRaosocial().' és incorrecte '. $preuhora);
    					}
    				}
    					
    				$hores = 0;
    				if ($docent['hores'] != '') {
    					$hores = $docent['hores'];
    					if (!is_numeric($hores) || $hores <= 0) {
    						$form->get( 'hores' )->addError( new FormError('') );
    						throw new \Exception('El nombre de sessions del professor '.$professor->getRaosocial().' són incorrectes '. $hores);
    					}
    				}
    				$import = $preuhora * $hores;
    				$docencia = new Docencia($facturacio, $professor, $hores, $preuhora, $import);
    				$em->persist($docencia);
    					
    				break;
    			case 'remove':
    				// Cancel·lar docència
    				$facturacio->removeProfessorById($docent['proveidor']);
    
    				break;
    			default:
    				throw new \Exception('Acció desconeguda '.$docent['accio']);
    				break;
    		}
    		 
    	}
    	 
    }
    
    
    /* Veure / actualitzar activitat puntual o taller un dia */
    /*
    public function activitatAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
   		$em = $this->getDoctrine()->getManager();
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'cognomsnom', 'direction' => 'desc', 'perpage' => UtilsController::DEFAULT_PERPAGE_WITHFORM));
    	
    	$data = array();
    	if ($request->getMethod() == 'POST') {
    		$data = $request->request->get('puntual');
    	
    		$id = (isset($data['id'])?$data['id']:0);
    		if ($id > 0) {
    			$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($id);
    		} else {
    			$activitat = new Activitat();
    			$em->persist($activitat);
    		}
    	} else {
    		$id = $request->query->get('id', 0);
    		$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($id);
    		
    		if ($activitat == null) { 
    			$activitat = new Activitat();
    			$em->persist($activitat);
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
     	
    	$form = $this->createForm(new FormActivitat($queryparams), $activitat);
    	if ($request->getMethod() == 'POST') {
    		
    		$form->handleRequest($request);

    		if ($form->isValid()) {

    			try {
	    			$activitat->setDatamodificacio(new \DateTime());

	    			if ($activitat->getDescripcio() == '' || $activitat->getDescripcio() == null) {
	    				$form->get( 'descripcio' )->addError( new FormError('No pot estar buit') );
	    				throw new \Exception('Cal indicar la descripció de l\'activitat' );
	    			}
	    			if ($activitat->getDataactivitat() == '' || $activitat->getDataactivitat() == null) {
	    				$form->get( 'dataactivitat' )->addError( new FormError('No pot estar buit') );
	    				throw new \Exception('Cal indicar la data de l\'activitat' ); 
	    			}
	    			
	    			$quotasoci = (isset($data['quotaparticipant'])?$data['quotaparticipant']:0)*1;
	    			$quotanosoci = (isset($data['quotaparticipantnosoci'])?$data['quotaparticipantnosoci']:0)*1;

	    			if (!is_numeric($quotasoci) || $quotasoci <= 0) {
	    				$form->get( 'quotaparticipant' )->addError( new FormError('Valor incorrecte'.$quotasoci) );
	    				throw new \Exception('El preu per als socis no és correcte' );
	    			}
	    			if (!is_numeric($quotanosoci) || $quotanosoci <= 0) {
	    				$form->get( 'quotaparticipantnosoci' )->addError( new FormError('Valor incorrecte'.$quotanosoci) );
	    				throw new \Exception('El preu per als no socis no és correcte' );
	    			}
	    			
	    			$rebutsModificats = false;
	    			if ($activitat->getId() == 0) {
	    				
	    				// Crear 1 facturació per defecte
	    				$num = $this->getMaxFacturacio();
	    				$desc = 'Facturació '.substr($activitat->getDescripcio(), 0, 40).' data '.$activitat->getDataactivitat()->format('d/m/Y');
	    				
	    				$facturacio = new FacturacioActivitat($activitat->getDataactivitat(), UtilsController::INDEX_FINESTRETA, $desc, $activitat, $quotasoci, $quotanosoci);
	    				
	    				$em->persist($facturacio);
	    				
	    			} else {
	    				$facturacio = $activitat->getFacturacionsActives();
	    				
	    				$rebutsAnular = array();
	    				if (isset($facturacio[0]) && $facturacio[0]->getImportactivitat() != $quotasoci) {
	    					// Canvia import rebuts. 
	    					foreach ($facturacio[0]->getRebuts() as $rebut) {
	    						if (!$rebut->cobrat() && !$rebut->anulat() && 
	    								$rebut->getDeutor() != null && $rebut->getDeutor()->esSociVigent()) {
	    									$rebut->setImportActivitat($quotasoci, $activitat->getId());
	    									$rebutsModificats = true;
	    						}
	    					}
	    					
	    					$facturacio[0]->setImportactivitat($quotasoci);
	    				}
	    				if (isset($facturacio[0]) && $facturacio[0]->getImportactivitatnosoci() != $quotanosoci) {
	    					// Canvia import rebuts. 
	    					foreach ($facturacio[0]->getRebuts() as $rebut) {
	    						if (!$rebut->cobrat() && !$rebut->anulat() && 
	    								$rebut->getDeutor() != null && !$rebut->getDeutor()->esSociVigent()) {
	    									$rebut->setImportActivitat($quotanosoci, $activitat->getId());
	    									$rebutsModificats = true;
	    						}
	    					}
	    					$facturacio[0]->setImportactivitatnosoci($quotanosoci);
	    				}
	    				
	    				if ( $facturacio[0]->getDatafacturacio()->format('Y-m-d') != $activitat->getDataactivitat()->format('Y-m-d') ) {
	    					
	    					$facturacio[0]->setDatafacturacio( $activitat->getDataactivitat() );
	    					
	    					$desc = 'Facturació '.$facturacio[0]->getId().' '.substr($activitat->getDescripcio(), 0, 40).' data '.$activitat->getDataactivitat()->format('d/m/Y');
	    					$facturacio[0]->setDescripcio( $desc );
	    					
	    					foreach ($facturacio[0]->getRebuts() as $rebut) {
	    						if (!$rebut->cobrat() && !$rebut->anulat()) {
	    							$rebut->setDataemissio($activitat->getDataactivitat());
	    							$rebutsModificats = true;
	    						}
	    					}
	    				}
	    			}
	
	    			$em->flush();
	    		
	    			$this->get('session')->getFlashBag()->add('notice',	'Activitat desada correctament');
	    			if ($rebutsModificats == true) $this->get('session')->getFlashBag()->add('notice',	'Els rebuts pendents han estat modificats');
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
   		return $this->render('FomentGestioBundle:Pages:activitat.html.twig',
   				array('form' => $form->createView(), 'activitat' => $activitat,
   						'participants' => $participants, 'queryparams' => $queryparams));
   		
    }
    
    */
    
    /* Carregar form calendari facturació i docències */
    public function carregarcalendariAction(Request $request)
    {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	$id = $request->query->get('id', 0);
    	 
    	$em = $this->getDoctrine()->getManager();
    
    	$facturacio = $em->getRepository('FomentGestioBundle:FacturacioActivitat')->find($id);
    	 
    	
    	// Camps relacionats amb el calendari i els docents
    	$form = $this->createFormBuilder($facturacio)
    	->add('cercardocent', 'entity', array(
    		'error_bubbling'	=> true,
    		'read_only' 		=> false,
    		'mapped'			=> false,
    		'class' 			=> 'FomentGestioBundle:Proveidor',
    		'query_builder' => function(EntityRepository $er) {
    			return $er->createQueryBuilder('p')
    			->orderBy('p.raosocial', 'ASC');
    		},
    		'property' 			=> 'raosocial',
    		'multiple' 			=> false,
    		'empty_value' 		=> ''
   		))
   		->add('hores', 'number', array(
    			'required' 	=> false,
    			'mapped'	=> false,
    			'precision'	=> 0,
    	))
    	->add('preuhora', 'number', array(
    			'required' 	=> false,
    			'mapped'	=> false,
    			'precision'	=> 2,
    	))
    	->add('preutotal', 'number', array(
    			'required' 	=> true,
    			'mapped'	=> false,
    			'precision'	=> 2,
    			'grouping'	=> true,
    			'disabled'	=> true
    	))
    	->add('docenciestmp', 'hidden', array(
    			'required' 	=> true,
    			'mapped'	=> false,
    	))
    	/* 3 opcions
    	 *
    	 * Data inici i data final del periode del curs
    	 *
    	 * semanal dl, dm, dx, dj, dv amb horari per cada dia
    	 *
    	 * mensual  selector primer/segon/tercer/quart
    	 *          selector dl, dm, dx, dj, dv
    	 *
    	 * per sessions Indicar dia / hora un a un => anar afegint al calendari en forma de llista
    	 *
    	 */
    	->add('tipusprogramacio', 'choice', array(
    			'required'  => true,
    			'choices'   => UtilsController::getTipusProgramacions(),	// Per sessions, setmanal,mensual => radio
    			'mapped'	=> false,
    			'expanded' 	=> true,
    			'multiple'	=> false,
    			'data'		=> UtilsController::INDEX_PROG_SETMANAL
    	))
    	->add('setmanal', 'hidden', array( 'mapped'	=> false, ))
    	->add('mensual', 'hidden', array( 'mapped'	=> false, ))
    	->add('persessions', 'hidden', array( 'mapped'	=> false, ))
    	// Mensual
    	->add('setmanadelmes', 'choice', array(
    			'required'  => false,
    			'choices'   => UtilsController::getDiesDelMes(),	// select primer, segon...
    			'mapped'	=> false,
    			'expanded' 	=> false,
    			'multiple'	=> false,
    	))
    	 
    	->add('diadelmes', 'choice', array(
    			'required'  => false,
    			'choices'   => UtilsController::getDiesSetmana(),	// select dilluns, dimarts...
    			'mapped'	=> false,
    			'expanded' 	=> false,
    			'multiple'	=> false,
    	))
    	->add('horainicidiadelmes', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control')
    	))
    	->add('horafinaldiadelmes', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control')
    	))
    	// Setmanal
    	//$setmanaCompleta = $activitat->getDadesDiesSetmanal();
    	->add('diessetmana', 'choice', array(
    			'required'  => true,
    			'choices'   => UtilsController::getDiesSetmana(),	// dilluns, dimarts...
    			'mapped'	=> false,
    			'expanded' 	=> true,
    			'multiple'	=> true,
    			//'data'		=> $activitat->getDiesSetmanal()
    	))
    	->add('dlhorainici', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DILLUNS]['hora']
    	))
    	->add('dmhorainici', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMARTS]['hora']
    	))
    	->add('dxhorainici', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMECRES]['hora']
    	))
    	->add('djhorainici', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIJOUS]['hora']
    	))
    	->add('dvhorainici', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIVENDRES]['hora']
    	))
    	->add('dlhorafinal', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DILLUNS]['final']
    	))
    	->add('dmhorafinal', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMARTS]['final']
    	))
    	->add('dxhorafinal', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMECRES]['final']
    	))
    	->add('djhorafinal', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIJOUS]['final']
    	))
    	->add('dvhorafinal', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'mapped'	=> false,
    			'attr' 		=> array('class' => 'select-hora form-control'),
				//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIVENDRES]['final']
		// per sessions
		))
  		->add('datahorasessio', 'text', array( 'mapped'	=> false, ) )
		->add('horafinalsessio', 'time', array(
			'input'  => 'datetime', // o string
			'widget' => 'single_text', // choice, text, single_text
			'mapped'	=> false,
			'attr' 		=> array('class' => 'select-hora form-control')
    	))->getForm();
    			
 
    	return $this->render('FomentGestioBundle:Includes:facturaciocalendari.html.twig',
    			array('form' => $form->createView(), 'facturacio' => $facturacio));
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
    		
    		if ($activitat == null) throw new \Exception('No s\'ha trobat l\'activitat '. $id); 
	    	
	    	if (!$activitat->esEsborrable()) throw new \Exception('No es pot esborrar, primerament cal anul·lar els rebuts');
	    	
	    	$activitat->setDatabaixa(new \DateTime());
	    	
	    	foreach ($activitat->getFacturacions() as $facturacio) {
	    		$facturacio->baixa(); // $facturació i rebuts
	    	}
	    	
	    	$em->flush();
	    	
	    	$this->get('session')->getFlashBag()->add('notice',	$activitat->getDescripcio().': anul·lat correctament ');
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
    		
			$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($id);
			
			if ($activitat == null) throw new \Exception('Activitat no trobada '.$id.'' );

			$this->inscriureParticipant($activitat, $nouparticipant);
				
			$em->flush();
			 
			$this->get('session')->getFlashBag()->add('notice',	($nouparticipant->getSexe()=='H'?'En ':'Na ').$nouparticipant->getNomCognoms().' inscrit correctament a l\'activitat');
				
			// Aplicar filtre si OK
			//$filtre = $nouparticipant->getNomCognoms();
    	
			
			
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

    		$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($id);
    		
    		if ($activitat == null) throw new \Exception('Activitat no trobada '.$id.'' );
    		
    		$this->esborrarParticipant($id, $esborrarparticipant);

    		$em->flush();
    		
    		$this->get('session')->getFlashBag()->add('notice',	($esborrarparticipant->getSexe()=='H'?'En ':'Na ').$esborrarparticipant->getNomCognoms().' és baixa de l\'activitat');

	    } catch (\Exception $e) {
	    	$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
	    }	
	    
		return $this->redirect($this->generateUrl('foment_gestio_activitat', array( 'id' => $id, 'perpage' => $perpage, 'filtre' => $filtre)));
    }
    
    private function inscriureParticipant($activitat, $nouparticipant) {
    	$em = $this->getDoctrine()->getManager();
    	
    	if ($activitat == null) throw new \Exception('L\'activitat no existeix ');
    	
    	$participacio = $activitat->getParticipacioByPersonaId($nouparticipant->getId());
    	
    	if ($participacio != null) throw new \Exception('Aquesta persona ja està inscrita a l\'activitat' );
    	 
    	$participacio = $activitat->addParticipacioActivitat($nouparticipant);

    	$em->persist($participacio);
    	 
    	/**************************** Crear els rebuts per aquesta inscripció ****************************/
    	$anyFacturaAnt = 0;
    	$numrebut = 0;
    	$facturacionsOrdenades = $activitat->getFacturacionsSortedByDatafacturacio();
    	
    	
    	/* Saltar facturacions passades i crear només rebut per la primera facturació futura */
    	
    	$i = 0;
    	$current = new \DateTime();
    	while (isset($facturacionsOrdenades[$i]) && $facturacionsOrdenades[$i]->getDatafacturacio()->format('Y-m-d') < $current->format('Y-m-d'))  $i++;
    	
    	$facturacio = null;
    	if (isset($facturacionsOrdenades[$i])) $facturacio = $facturacionsOrdenades[$i];
    	else {  // Totes passades, crear rebut per última
    		if (isset($facturacionsOrdenades[$i - 1])) $facturacio = $facturacionsOrdenades[$i - 1];
    	}
    	
    	if ($facturacio != null) {
	    	$anyFactura = $facturacio->getDatafacturacio()->format('Y');
    		$numrebut = $this->getMaxRebutNumAnyActivitat($anyFactura); // Max
    		$rebut = $this->generarRebutActivitat($facturacio, $participacio, $numrebut);
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
						
			if (!$rebut->cobrat()) $rebut->baixa();
			
		}

		
		$participacio->setDatacancelacio(new \DateTime());
		$participacio->setDatamodificacio(new \DateTime());
	}
	
	public function taulaproveidorsAction(Request $request) {
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		
		$page =  $request->query->get('page', 1);
		$perpage =  $request->query->get('perpage', UtilsController::DEFAULT_PERPAGE_WITHFORM);
		$filtre = $request->query->get('filtre', '');
		
		$em = $this->getDoctrine()->getManager();
		$queryparams = $this->queryTableSort($request, array( 'id' => 'p.raosocial', 'direction' => 'desc', 'perpage' => UtilsController::DEFAULT_PERPAGE_WITHFORM));			
		
		$query = $this->queryProveidors($filtre);
		 
		$paginator  = $this->get('knp_paginator');
		 
		$proveidors = $paginator->paginate(
				$query,
				$page,
				$perpage //limit per page
				);
		//unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
		//$proveidors->setParam('perpage', $perpage);
		
		//$proveidors = $em->getRepository('FomentGestioBundle:Proveidor')->findAll();
		$form = $this->createForm(new FormProveidor(), new Proveidor());
			
		$form->get('filtre')->setData( $queryparams['filtre'] );
		$form->get('midapagina')->setData( $queryparams['perpage'] );
			
		
		try {

			
		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
		}
	
		return $this->render('FomentGestioBundle:Includes:taulaproveidors.html.twig',
				array('form' => $form->createView(), 
						'proveidors' => $proveidors, 'queryparams' => $queryparams));
		
	}
	
	public function desarproveidorAction(Request $request) {
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		$em = $this->getDoctrine()->getManager();
		
		$id = 0;
		if ($request->getMethod() != 'POST') {
			$id = $request->query->get('id', 0);
		} else {
			$data = $request->request->get('proveidor');
			$id = $data['id'];
		}
		$proveidor = $em->getRepository('FomentGestioBundle:Proveidor')->find($id);
		if ($proveidor == null) $proveidor = new Proveidor();
		
		$form = $this->createForm(new FormProveidor(), $proveidor);
		
		try {
			
			if ($request->getMethod() == 'POST') { 
			
				$form->handleRequest($request);
			
				if ($proveidor->getId() == 0) $em->persist($proveidor);
				
				if (!$form->isValid()) throw new \Exception('Cal revisar les dades del formaulari'); 
				
				if ($proveidor->getRaosocial() == '') {
					$form->get( 'raosocial' )->addError( new FormError('Indicar el nom') );
					throw new \Exception('Cal indicar el nom o la Raó social del proveidor');
				}
				
				if ($proveidor->getRaosocial() == '') {
					$form->get( 'raosocial' )->addError( new FormError('Indicar el nom') );
					throw new \Exception('Cal indicar el nom o la Raó social del proveidor');
				}
				
				if ($proveidor->getTelffix() != '' && !is_numeric($proveidor->getTelffix())) {
					$form->get( 'telffix' )->addError( new FormError('No és numèric') );
					throw new \Exception('El número de telèfon no és correcte');
				}
				
				if ($proveidor->getTelfmobil() != '' && !is_numeric($proveidor->getTelfmobil())) { 
					$form->get( 'telfmobil' )->addError( new FormError('No és numèric') );
					throw new \Exception('El número de mòbil no és correcte');
				}
				
				if ($proveidor->getCorreu() != '' && filter_var($proveidor->getCorreu(), FILTER_VALIDATE_EMAIL)) {
					$form->get( 'correu' )->addError( new FormError('Format incorrecte') );
					throw new \Exception('L\'adreça de correu no és correcta');
				}
				
				$proveidor->setDatamodificacio(new \DateTime());
					 
				$em->flush();
			   
				$this->get('session')->getFlashBag()->add('notice',	'Proveidor desat correctament');
			}
		} catch (\Exception $e) {
			
			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
		}
	
		return $this->render('FomentGestioBundle:Includes:formproveidors.html.twig',
				array('form' => $form->createView()));
	
	}
	
	public function clonaractivitatAction(Request $request) {
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		 
		$em = $this->getDoctrine()->getManager();
		 
		$id = $request->query->get('id', 0);
		$clonarParticipants = $request->query->get('participants', 0) == 0?false:true;
	
		$participants = array();
		
		try {
			// Obtenir activitat a clonar			 
			$original = $em->getRepository('FomentGestioBundle:Activitat')->find($id);
			 
			if ($original == null) throw new \Exception('Activitat no trobada '.$id.'' );
			 
			if ($clonarParticipants == true) $participants = $original->getParticipantsActius();
			
			$activitat = clone $original;
			
			$em->persist($activitat);

			foreach ($participants as $participant) {
				$this->inscriureParticipant($activitat, $participant->getPersona());
			}
			
			// Afegir un any a les facturacions			
			$facturacions = $activitat->getFacturacions();
			 
			foreach ($facturacions as $facturacio_iter) {
				$em->persist($facturacio_iter);
				$facturacio_iter->getDatafacturacio()->add(new \DateInterval('P1Y'));
				
				$docents = $activitat->getDocents(); // Clone docents
						
				foreach ($docents as $docent_iter) $em->persist($docent_iter);
			}
			
			
			// Afegir un any a la data inici i final
			$activitat->getDatainici()->add(new \DateInterval('P1Y'));
			$activitat->getDatafinal()->add(new \DateInterval('P1Y'));
				
			$em->flush();
			 
			$this->get('session')->getFlashBag()->add('notice',	'Activitat '.$original->getInfo().' clonada correctament ');

			return $this->redirect($this->generateUrl('foment_gestio_activitat', array( 'id' => $activitat->getId())));
		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
		}
	
		return $this->redirect($this->generateUrl('foment_gestio_activitats'));
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
