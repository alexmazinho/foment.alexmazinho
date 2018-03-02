<?php

namespace Foment\GestioBundle\Controller;

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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Filesystem\Exception\IOException; 

use Foment\GestioBundle\Entity\Soci;
use Foment\GestioBundle\Entity\Persona;
use Foment\GestioBundle\Entity\Proveidor;
use Foment\GestioBundle\Entity\Seccio;
use Foment\GestioBundle\Entity\Activitat;
use Foment\GestioBundle\Entity\Docencia;
use Foment\GestioBundle\Form\FormSoci;
use Foment\GestioBundle\Form\FormPersona;
use Foment\GestioBundle\Form\FormProveidor;
use Foment\GestioBundle\Form\FormSeccio;
use Foment\GestioBundle\Form\FormJunta;
use Foment\GestioBundle\Form\FormActivitat;
use Foment\GestioBundle\Entity\Rebut;
use Foment\GestioBundle\Entity\Imatge;
use Foment\GestioBundle\Entity\FacturacioActivitat;


class PagesController extends BaseController
{
    public function indexAction(Request $request)
    {
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}

    	// Incidències. Socis actius (sense data baixa). No són membres Foment
    	$incidencies = $this->queryIncidenciesSocisActiusSenseFoment();
    	
    	return $this->render('FomentGestioBundle:Pages:index.html.twig', array( 'incidencies' => $incidencies ));
    }
    
    public function llistacorreuAction(Request $request)
    {
    	$mail = $request->query->get('mail', '');
    
    	$token = $request->query->get('token', '');
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$current = new \DateTime('now');
    	
    	try {
    	
	    	if ($mail != '' && $token != '') {
	    		// Confirmació de baixa
	    		/*$form = $this->createFormBuilder()
	    			->add('mail', 'hidden', array( 'required' => false, 'data'	=> $mail ) )
	    			->add('baixa', 'hidden')->getForm();*/
	    		$form = null;
	    		
		    	$repository = $em->getRepository('FomentGestioBundle:Persona');
		    	$persones = $repository->findBy(array('correu' => $mail));
		    		
		    	if (count($persones) > 0) {
		    		foreach ($persones as $persona) {
		    			// Validar token
		    			if (sha1($token) != $persona->getUnsubscribetoken()) throw new \Exception('Les dades de l\'enllaç no són correctes, no s\'ha pogut confirmar la baixa');
		    				
		    			$expiration = $persona->getUnsubscribeexpiration();
		    			$url = $this->generateUrl('foment_gestio_llistacorreu', array( 'mail' => $mail ));
		    			if ($expiration != null && $current->format('Y-m-d H:i:s') > $expiration->format('Y-m-d H:i:s'))
		    				throw new \Exception('L\'enllaç per poder tramitar la baixa ha expirat, per tornar a sol·licitar-la <a href="'.$url.'" target="_blank">'.$url.'</a>');
		    			
		    			$persona->setUnsubscribetoken('');
		    			$persona->setUnsubscribeexpiration(null);
		    			$persona->setNewsletter(false);
		    			$persona->setUnsubscribedate($current);
		    		}
		    		
		    		$em->flush();
		    	}
		    	$this->get('session')->getFlashBag()->add('notice', 'Baixa de la llista de correu tramitada correctament' );
	    		
	    		return $this->render('FomentGestioBundle:Pages:llistacorreu.html.twig',
	    				array('form' => null ));
	    	}
    	
	    	$form = $this->createFormBuilder()
	    		->add('mail', 'email', array(
	        		'required'  => true,
	    			'data'		=> $mail	
	        	))
	    		->add('baixa', 'checkbox', array(
	        		'required'  => false,
	    			'data'		=> false 
	        	))->getForm();
	    	
	   		if ($request->getMethod() == 'POST') {
	   			// Soci o persona Foment envia formulari per baixa de la llista. Generar correu amb Token
	   			$form->bind($request);
	   			
	   			if (!$form->isValid()) throw new \Exception('Cal indicar l\'adreça de correu');
	   					 
	   			$mail = $form->get('mail')->getData();
	   			
	   			if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) throw new \Exception($mail. ' no és una adreça de correu vàlida');
	   			
	   			$repository = $em->getRepository('FomentGestioBundle:Persona');
	   			$persones = $repository->findBy(array('correu' => $mail));
	   				
	   			if (count($persones) > 0) {
	
	   				$token = base64_encode(openssl_random_pseudo_bytes(30));
	   				$expiration = clone $current;
	   				$expiration->add(new \DateInterval('PT4H'));
	   					
	   				foreach ($persones as $persona) {
	   					// Save token information encrypted
	   					$persona->setUnsubscribetoken(sha1($token));
	   					$persona->setUnsubscribeexpiration($expiration);
	   				}
	   					
	   				$em->flush();
	   					
	   				$subject 	= 'Confirmació baixa llista de correu Associació Foment Martinenc';
	// !!!!!!!!!!!!!!!!!!!!!!!!  CREAR MAIL ENVIAMENT CORREU llistacorreu   					
	   				$from 		= $this->container->getParameter('fomentgestio.emails.llistacorreu');
	   				$tomails 	= array( $mail );
	
	   				$url = $this->generateUrl('foment_gestio_llistacorreu', array( 'mail' => $mail, 'token' => $token ), UrlGeneratorInterface::ABSOLUTE_URL);
	   					
	   				$body = "";
	   				$body .= "<p>Benvolgut/da</p>";
	   				$body .= "<p>Recentment has sol·licitat donar-te de baixa de la llista de correu de l'Associació Foment Martinenc. ";
	   				$body .= "Per confirmar la baixa i deixar de rebre les informacions de l'Associació clica a l'enllaç següent:</p>";
	   				$body .= "<p><a href=".$url." target=\"_blank\">".$url."</a></p>";
	   				$body .= "<p>En cas de no haber tramitat aquesta sol·licitud pots ignorar aquest correu.</p>";
	   				$body .= "<p>Atentament</p>";
	   					
	   				$this->buildAndSendMail($subject, $from, $tomails, $body);
	   					
	   			} else {
	   					// Mostrar missatge però no fer res
				}
	
				//$this->get('session')->clear();
				$this->get('session')->getFlashBag()->add('notice', 'S\'han enviat un correu amb les instruccions per confirmar la baixa de la llista de l\'adreça ' . $mail);
			}
    	
		} catch (\Exception $e) {
			$this->get('session')->getFlashBag()->add('error', $e->getMessage());
		}
		
		return $this->render('FomentGestioBundle:Pages:llistacorreu.html.twig',
    					array('form' => ($form != null?$form->createView():null) ));
    }
    
    public function parametresAction(Request $request)
    {
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$parametres = $this->getDoctrine()->getRepository('FomentGestioBundle:Parametre')->findAll();
    	 
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
    	 
    	 
    	return $this->render('FomentGestioBundle:Pages:parametres.html.twig', array( 'parametres' => $parametres ));
    }
    
    
    public function desarparametreAction(Request $request)
    {
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$paramId = $request->query->get('id', 0);
    	$valor = $request->query->get('valor', '');
    	
    	try {
    		if ($valor == '') throw new \Exception('Cal indicar algún valor');
    		
    		$em = $this->getDoctrine()->getManager();
    		
    		$parametre = $em->getRepository('FomentGestioBundle:Parametre')->find($paramId);
    		
    		if ($parametre == null) throw new \Exception('No s\'ha pogut modificar el paràmetre');

    		// Validacions específiques
	    	switch ($parametre->getClau()) {
	    		case UtilsController::RECARREC_REBUT_RETORNAT:	 // numèric
	    			if (!is_numeric($valor)) throw new \Exception('El recàrrec ha de ser numèric');
	    			$valor = number_format($valor, 2, ',', '.');
	    			
	    			
	    			break;
	    		case UtilsController::DIES_FESTIUS_ANUALS:	 // separats per coma
	    			$dies = explode(",", $valor);
	    			
	    			foreach ($dies as $festiu) {
	    				$festiu = trim($festiu);
	    				
	    				$arrFestiu = explode("/", $festiu);
	    				
	    				if (count($arrFestiu) != 2) throw new \Exception('Format del festiu incorrecte (dd/mm), valor: '.$festiu);
	    				
	    				$diafestiu = \DateTime::createFromFormat('d/m/Y', $festiu.'/'.date('Y'));
	    				
	    				if ($diafestiu === false) throw new \Exception('Dia festiu incorrecte, valor: '.$festiu);
	    			}
	    			
	    			break;
	    		default:  
	    			break;
	    	}
    		
    		$parametre->setValor( $valor );
    		
    		$em->flush();
    
    	} catch (\Exception $e) {
    		$response = new Response($e->getMessage());
    		$response->setStatusCode(500);
    		
    		return $response;
    	}
    	 
    	 
    	return new Response('Paràmetre desat correctament');
    }
    
    

    public function comunicacionsAction(Request $request)
    {
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
    	
    	$datamin = \DateTime::createFromFormat('Y-m-d H:i:s', (date('Y')-1)."-01-01 00:00:00"); // Un any endarrera
    	
    	$form = $this->createFormBuilder()
    	->add('facturacions', 'entity', array(
    			'error_bubbling'	=> true,
    			'class' => 'FomentGestioBundle:FacturacioSeccio',
    	        'query_builder' => function(EntityRepository $er) use($datamin) {
    				return $er->createQueryBuilder('f')
    				->where('f.databaixa IS NULL')
    				->andWhere('f.datadomiciliada IS NULL OR f.datadomiciliada > \''.$datamin->format('Y-m-d H:i:s').'\'')
    				->orderBy('f.id', 'DESC');
    			},
    			'choice_label' 		=> 'descripcio',
    			'multiple' 			=> true,
    			'required'  		=> true,
    	))
    	->add('datafins', 'text', array(
    			//'read_only' 	=> true,
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
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
		
    	$queryparams = $this->queryPersones($request);
    	
    	/* Si $p == true (pendents vist i plua) i $s == false (socis i no socis. Cal revisar resultat de la $query a ma */
    	 
    	// Form
    	$defaultData = array('sexehome' => $queryparams['h'], 'sexedona' => $queryparams['d'],
    			'nom' => $queryparams['nom'], 'cognoms' => $queryparams['cognoms'], 'dni' => $queryparams['dni'], 'socis' => $queryparams['s'],  
    			'nomail' => $queryparams['nomail'], 'mail' => $queryparams['mail'],
    			'cercaactivitats' => implode(",", $queryparams['activitats']), 'seccions' => $queryparams['seccions'],
    	        'newsletter' => $queryparams['newsletter'], 'dretsimatge' => $queryparams['dretsimatge'], 'lopd' => $queryparams['lopd']
    	);
    
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
    			'choice_label' 		=> 'info',
    			'multiple' 			=> true,
    			'required'  		=> false,
    	))
    	->add('cercaactivitats', 'hidden', array('required'	=> false ))
    	
    	->add('socis', 'choice', array(
    		'required'  => true,
    		'choices'   => array(
    		    UtilsController::INDEX_CERCA_SOCIS => 'Vigents',
    		    UtilsController::INDEX_CERCA_BAIXES => 'Baixes', 
    		    UtilsController::INDEX_CERCA_NOSOCIS => 'No socis'),
    		'data' 		=> $defaultData['socis'],
    		'mapped'	=> false,
    		'expanded' 	=> true,
    		'multiple'	=> true
    	))
    	
    	->add('newsletter', 'choice', array(
    	    'required'  => true,
    	    'choices'   => array(
    	        UtilsController::INDEX_DEFAULT_TOTS => 'Tot',
    	        UtilsController::INDEX_DEFAULT_SI => 'Si',
    	        UtilsController::INDEX_DEFAULT_NO => 'No'
    	        ),
    	    'data' 		=> $defaultData['newsletter'],
    	    'mapped'	=> false,
    	    'expanded' 	=> true,
    	    'multiple'	=> false
    	))
    	->add('dretsimatge', 'choice', array(
    	    'required'  => true,
    	    'choices'   => array(
    	        UtilsController::INDEX_DEFAULT_TOTS => 'Tot',
    	        UtilsController::INDEX_DEFAULT_SI => 'Si',
    	        UtilsController::INDEX_DEFAULT_NO => 'No'
    	    ),
    	    'data' 		=> $defaultData['dretsimatge'],
    	    'mapped'	=> false,
    	    'expanded' 	=> true,
    	    'multiple'	=> false
    	))
    	->add('lopd', 'choice', array(
    	    'required'  => true,
    	    'choices'   => array(
    	        UtilsController::INDEX_DEFAULT_TOTS => 'Tot',
    	        UtilsController::INDEX_DEFAULT_SI => 'Si',
    	        UtilsController::INDEX_DEFAULT_NO => 'No'
    	    ),
    	    'data' 		=> $defaultData['lopd'],
    	    'mapped'	=> false,
    	    'expanded' 	=> true,
    	    'multiple'	=> false
    	))
    	->getForm();
    	
    	$persones = $this->sortPersones(isset($queryparams['query'])?$queryparams['query']:'', 
    	                               isset($queryparams['querynosocis'])?$queryparams['querynosocis']:'',
    	                               $queryparams['sort'],
    	                               $queryparams['direction']);
    	
    	$queryparams['rowcount'] = count($persones);    // p.e. 22
    	$queryparams['pagetotal'] = ceil($queryparams['rowcount']/$queryparams['perpage']);  // perpage = 5 => 5 pages
    	
        $fromIndex = ($queryparams['page'] - 1) * $queryparams['perpage'];
        $persones = array_splice($persones, $fromIndex, $queryparams['perpage']); 
    	
    	if (count($persones) == 1) {
    		$persona = $persones[0];
    		return $this->redirect($this->generateUrl('foment_gestio_veuredadespersonals', array( 'id' => $persona->getId(), 'soci' => $persona->esSoci(), 'tab' => 0 )));
    	}
    	
    	return $this->render('FomentGestioBundle:Pages:cercapersones.html.twig', array('form' => $form->createView(), 'persones' => $persones, 'queryparams' => $queryparams));
    }
    
    /* Veure / actualitzar dades personals (soci o no) existents (amb id) */
    public function veuredadespersonalsAction(Request $request)
    {
    	
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
    	$persona = null;
    	$errorField = array('field' => '', 'text' => '');
    	
    	try {
	    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	try {
    	
	    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
	    		throw new AccessDeniedException();
	    	}
	    	
	    	if ($request->getMethod() == 'GET') return $this->forward('FomentGestioBundle:Pages:nousoci');
	    	 
	    	$data = $request->request->get('soci');
	    	$activitatstmp = (isset($data['activitatstmp'])?$data['activitatstmp']:'');
	    	$membredetmp = (isset($data['membredetmp'])?$data['membredetmp']:'');

	    	$id = (isset($data['id'])?$data['id']:0);
	    	$tab = (isset($data['tab'])?$data['tab']:UtilsController::TAB_SECCIONS);
	    	
	    	$soci = $em->getRepository('FomentGestioBundle:Soci')->find($id);
	    	
	    	if ($soci == null) {
	    		$soci = new Soci();
	    		$em->persist($soci);
	    	}
	    	
	    	$form = $this->createForm(new FormSoci(), $soci);
	    	
	    	$form->handleRequest($request);
	    	
	    	$activitatsids = array();
	    	if ($activitatstmp != '') $activitatsids = explode(',',$activitatstmp); // array ids activitats llista
	    	
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
	    	
	    	if (!$soci->esBaixa()) {
	    		// Deudor rebut
	    		if ($data['deudorrebuts'] == 1) $soci->setSocirebut($soci);
	    		else {
	    			if ($soci->getSocirebut() == null) {
	    				$tab = UtilsController::TAB_CAIXA;
	    				throw new \Exception('Cal indicar el soci que es farà càrrec dels rebuts');
	    			}
	    		}
	    		
		    	$seccionsIds = array();
		    	if ($membredetmp != '') $seccionsIds = explode(',',$membredetmp);

		    	$this->actualitzarSeccionsSoci($soci, $seccionsIds);
		    	
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
				    throw new \Exception('Cal revisar les dades del formulari del soci'.$form->getErrors(true, true));
				}
				
		   		$desvincular = (isset($data['socisdesvincular'])?$data['socisdesvincular']:'');
		   		$this->desvincularSocisRebuts($soci, $desvincular);
		   		
	   		} else {
	   			// Baixes => cancel·lar inscripcions seccions i validar deutors
	   			$this->baixaSoci($soci);
	   		}
	   		
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
    				
    				foreach ($membreGrupFacturar as $quotes) {
    					// Crear nou rebut per al soci desvinculat amb les quotes del rebut original
    					$nourebut = new Rebut($quotes['soci'], $rebut->getDataemissio(), $numrebuts[ $anyrebut ], true, false);
    					if ($rebut->getFacturacio() != null) $rebut->getFacturacio()->addRebut($nourebut);
    					
    					if ($nourebut->enDomiciliacio()) $nourebut->$this->setTipuspagament( UtilsController::INDEX_FINESTRETA );
    					$em->persist($nourebut);
    	
    					foreach ($quotes['quotes'] as $membre) {
    						
    						$rebutdetall = $this->generarRebutDetallMembre($membre, $nourebut, $anyrebut);
    	
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
		    	
	    	for ($i=0; $i<8; $i++) $controlCS += intval($strBancAgencia[$i]) * $valores[$i+2]; // Banc+Oficina
		    	   	
	    	$controlCS = 11 - ($controlCS % 11);
	    	if ($controlCS == 10) $controlCS = 1;
	    	if ($controlCS == 11) $controlCS = 0;
		    	 
	    	for ($i=0; $i<10; $i++) $controlCC += intval($strCCC[$i]) * $valores[$i];
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
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
	    		// BAIXA SOCI
    			$soci->setDatabaixa(new \DateTime('today'));
    			$this->baixaSoci($soci);
    			//$soci->setNum(null);
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
    		
    		return $this->forward('FomentGestioBundle:Pages:veuredadespersonals', array(),
    				array( 'id' => $soci->getId(), 'soci' => true, 'tab' => $tab ));
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
    
    private function baixaSoci($soci) {
    	// Actualitzar deutor rebuts
    	   
    	if ($soci->esDeudorDelGrup()) {
    		$socisacarrec = $soci->getSocisDepenents();
    	
    		if (count($socisacarrec) > 0) throw new \Exception('Aquest soci es fa càrrec dels rebuts d\'altres i no es pot esborrar. Cal assignar algú altre que se\'n faci càrrec' );
    	} else {
    		// Els rebuts del soci són a càrrec d'altri. Actualitzar, una persona paga els seus rebuts
    		// A mes a finestreta
    		$soci->setSocirebut($soci);
    	}
    	$soci->setCompte(null);
    	
    	// Donar de baixa de les seccions
    	$databaixa = $soci->getDatabaixa();
    	
    	$inscripcionsActives =  $soci->getMembreDeSortedById( false );
    	foreach ($inscripcionsActives as $membrede)  {
    		$this->esborrarMembre($membrede->getSeccio(), $soci, $databaixa != null?$databaixa->format('Y'):date('Y'));
    	}
    }
    
        
    /* Mostrar form nou soci.
     * 	Sense id blank,
    * 	sense id amb dades de soci GET (canvi soci nou -> persona)
    * 	o amb id de persona (canvi persona existent -> soci). Alta soci */
    public function nousociAction(Request $request)
    {
	    if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
	    $queryparams = $this->queryTableSort($request, array( 'id' => 'dataemissio', 'direction' => 'desc'));
	    	
	    $id = $request->query->get('id', 0);
	    $keepnum = $request->query->get('keepnum', 0) == 1?true:false;
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

					if (!$keepnum) {
						$soci->setnum($this->getMaxNumSoci()); // Número nou
						$soci->setDataalta(new \DateTime());
					}
	    		}
	    		else {
					if ($strNaixement == '') throw new \Exception('Cal indicar la data de naixement');

					$datanaixement = \DateTime::createFromFormat('d/m/Y', $strNaixement );
					$persona->setDatanaixement($datanaixement);
					
					$em->flush();

	    			$soci = new Soci($persona);
	    			$soci->setnum($this->getMaxNumSoci()); // Número nou
	    			
	    			$em->persist($soci);
	    		}
	    		    		
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
		    	$membre = $soci->getMembreBySeccioId(UtilsController::ID_FOMENT, true);
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
    	
    	// Soci desactiva rebre newsletter foment
    	if ($persona->getNewsletter() == false && $persona->getUnsubscribedate() == null) $persona->setUnsubscribedate(new \DateTime());
    	
    	// Soci activa accés a la newsletter foment
    	if ($persona->getNewsletter() == true && $persona->getUnsubscribedate() != null) $persona->setUnsubscribedate(null);
    	
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
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
    		if ($request->query->has('action')) {
    			if ($request->query->get('action') == 'edit') {
    		
	    			// Editar la llista de seccions per modificar tots els preus
	    			$edicioQuotes = true;
    			} 
    			if ($request->query->get('action') == 'quotes') {
    				// Traspassar quotes any anterior
    				$anydesde = $request->query->get('anydesde', $anyselect - 1);

    				$quotes = $this->queryQuotes($anydesde); 
    				$novesQuotes = array(); 
    				$smsRes = 'Quotes copiades correctament';
    				foreach ($arraySeccions as $seccio) {
    					
    					if (isset($quotes[ $seccio->getId()])) {
    						$novesQuotes[] = $seccio->setQuotaAny($anydesde + 1, $quotes[$seccio->getId()]['import'], false);
    						$novesQuotes[] = $seccio->setQuotaAny($anydesde + 1, $quotes[$seccio->getId()]['importjuvenil'], true);
    					} else {
    						$smsRes .= '<br/>La secció '.$seccio->getNom().' no té quotes per l\'any '.$anydesde; 
    					}
    				}
    				foreach ($novesQuotes as $quota) $em->persist($quota);
    				$em->flush();
    				
    				$this->get('session')->getFlashBag()->add('notice',	$smsRes);
    				
    				return $this->redirect($this->generateUrl('foment_gestio_seccions', array( 'anydades' => $anydesde + 1)));
    			}
    		} else {
    			// Mentre no hi hagi edició actualitzo els paràmetres de cerca
    			//$queryparams = $this->queryTableSort($request, array( 'id' => 's.id', 'direction' => 'asc'));
    			$queryparams['quotes'] = $quotes;
    		}
    	}
    	
    	$query = $this->filtrarArraySeccions($arraySeccions, $queryparams, $anyselect);
    	
    	//$sortkeys = array('nom' => 's.nom', 'ordre' => 's.ordre', 'import' =>  'q.import', 'importjuvenil' => 'q.importjuvenil', 'membres' => 'membres');
    	//$query = $this->ordenarArrayClausVariables($query, $queryparams, $sortkeys);

    	
    	$paginator  = $this->get('knp_paginator');
    	
    	$seccions = $paginator->paginate(
    			json_decode(json_encode($query), FALSE),	// convert to object
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
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
    	$queryparams['anydades'] = $anydades;
    	
    	// Filtre i ordenació dels membres
    	$query = $this->filtrarArrayNomCognoms($seccio->getMembresActius($anydades), $queryparams);

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

    	
    	
    	$queryparams['rebutsgenerats'] = ($this->rebutsCreatsAny($anydades));
    	$queryparams['anysSelectable'] = $this->getAnysSelectable();
    	
    	/* Baixes període */
    	$ini = \DateTime::createFromFormat('Y-m-d', $anydades."-01-01");
    	$fi = \DateTime::createFromFormat('Y-m-d', $anydades."-12-31");
    	$baixes = count($this->queryBaixesMembresAny($ini , $fi, $id));
    	
    	
    	$form = $this->createForm(new FormSeccio($queryparams), $seccio);
    	
    	if ($request->getMethod() == 'POST') {

    		try {
	    		$form->handleRequest($request);
	    		
	    		if ($form->isValid()) {
	    			
	    			$anydades = $form->get('quotaany')->getData();
	    			
	    			$this->postFormSeccioQuotes($form);
	    			
	    			if ($seccio->getId() == 0) $em->persist($seccio);
	    			
	    			if ($seccio->getOrdre() == null || !is_numeric($seccio->getOrdre())) $seccio->setOrdre(999);
	    			
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
    					    'membres' => $membres, 'baixes' => $baixes, 'queryparams' => $queryparams));
    		}
    	}
    	
    	return $this->render('FomentGestioBundle:Pages:seccio.html.twig',
    			array('form' => $form->createView(), 'seccio' => $seccio,
    			    'membres' => $membres, 'baixes' => $baixes, 'queryparams' => $queryparams));
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
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
    
    
    /* Veure / actualitzar activitats */
    public function activitatsAction(Request $request)
    {
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}

    	$queryparams = $this->queryTableSort($request, array( 'id' => 'a.id', 'direction' => 'desc'));
    	
    	if ($request->query->has('finalitzats') && $request->query->get('finalitzats') == 1) $queryparams['finalitzats'] = true;
    	else $queryparams['finalitzats'] = false;
    	
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
	    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
    
    
    /* Veure / actualitzar curs */
    public function activitatAction(Request $request)
    {
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	$em = $this->getDoctrine()->getManager();
    	$queryparams = $this->queryTableSort($request, array( 'id' => 'cognomsnom', 'direction' => 'desc', 'perpage' => UtilsController::DEFAULT_PERPAGE_WITHFORM));
    	$baixes = false;
    	
    	if ($request->getMethod() == 'POST') {
    		$data = $request->request->get('activitat');
    		$id = (isset($data['id'])?$data['id']:0);
    		$strFacturacionsIds = (isset($data['facturacionsdeltemp'])?$data['facturacionsdeltemp']:'');
    		$facturacionsIdsEsborrar = array();
    		if ($strFacturacionsIds != '') $facturacionsIdsEsborrar = explode(',',$strFacturacionsIds); // array ids facturacions per esborrar
    		
    		$facturacionsNoves = (isset($data['facturacions'])?$data['facturacions']:array());
    	} else {
    		$id = $request->query->get('id', 0);
    		$baixes = $request->query->get('baixes', 0)==1?true:false;
    	}
    	
    	$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($id);
    	
    	// Crear una facturació segons data d'avui
    	$dataFacturacio = new \DateTime();
    	$desc = '';
    	if ($activitat == null ) {
    		$activitat = new Activitat();
    		$em->persist($activitat);
    		$dataFacturacio = new \DateTime();
    		
    		if ($request->getMethod() != 'POST') { // Get nou curs
	    		$facturacio = new FacturacioActivitat($dataFacturacio, $desc, $activitat, 0, 0);
	    		$em->persist($facturacio);
    		}
    	} else {

    		if ($activitat->getDatafinal() != null) {
    			// Existeix altra facturació
    			$dataFacturacio = clone $activitat->getDatafinal();
    			$dataFacturacio->add(new \DateInterval('P3M')); // 3 mesos;
    		}
    	}
    	
    	$queryparams['descproto'] = $desc; // Per al proto
    	$queryparams['dataproto'] = $dataFacturacio; // Per al proto
    	$queryparams['ordinalsproto'] = '';
    	$queryparams['baixes'] = $baixes;
    	/*
    	//$query = $activitat->getParticipantsActius();
    	$query = $activitat->getParticipantsSortedByCognom();
    	if ($request->getMethod() == 'GET') { 
	    	// Filtre i ordenació dels membres
	    	$query = $this->filtrarArrayNomCognoms($query, $queryparams);
	    	$query = $this->ordenarArrayObjectes($query, $queryparams);
    	}
    	
    	$participants = $query;
    	
    	$paginator  = $this->get('knp_paginator');
    	 
    	$participants = $paginator->paginate(
    			$query,
    			$queryparams['page'],
    			$queryparams['perpage'] //limit per page
    	);
    	unset($queryparams['page']); // Per defecte els canvis reinicien a la pàgina 1
    	$participants->setParam('id', $id); // Add extra request params. Activitat id
    	$participants->setParam('perpage', $queryparams['perpage']);*/
   	
    	$form = $this->createForm(new FormActivitat(), $activitat);
   	
    	if ($request->getMethod() == 'POST') {
    		
    		$form->handleRequest($request);
   		
    		try {
    		
	    		if ($form->isValid()) {
	    			
	    			$activitat->setDatamodificacio(new \DateTime());

	    			if ($activitat->getId() == 0) $em->persist($activitat);
	    			
	    			if ($activitat->getDescripcio() == '' || $activitat->getDescripcio() == null) {
	    				$form->get( 'descripcio' )->addError( new FormError('No pot estar buit') );
	    				throw new \Exception('Cal indicar la descripció de l\'activitat' );
	    			}
	    			
    				$this->cursTractamentFacturacio($activitat, $facturacionsIdsEsborrar, $facturacionsNoves, $form);
	    			
	    			$em->flush();
	    			
	    			$this->get('session')->getFlashBag()->add('notice',	'Activitat desada correctament');
	    			// Prevent posting again F5
	    			return $this->redirect($this->generateUrl('foment_gestio_activitat', array( 'id' => $activitat->getId())));
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
    			return $this->render('FomentGestioBundle:Rebuts:infoactivitat.html.twig',
    					array('dades' => $activitat->getDadesFacturacio($baixes), 'queryparams' => array()));
    								
    		}
    		
    	}
    	
    	return $this->render('FomentGestioBundle:Pages:activitat.html.twig',
    			array('form' => $form->createView(), 'activitat' => $activitat, 'queryparams' => $queryparams));
    	 
    }
    
    private function cursTractamentFacturacio($activitat, $facturacionsIdsEsborrar, $facturacionsNoves, $form) { 
    	$em = $this->getDoctrine()->getManager();
    	
    	$facturacions = $activitat->getFacturacionsActives();

    	// Baixa facturacions
    	foreach ($facturacions as $facturacio)  {
    		if (in_array($facturacio->getId(), $facturacionsIdsEsborrar)) {
    			// Baixa
    			if (!$facturacio->esEsborrable()) throw new \Exception('La facturació "'.$facturacio->getDescripcio().'" no es pot esborrar perquè té rebuts pagats');
    			$facturacio->baixa();
    		}
    	}
    	
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
    		
    		if (!isset($nova['datafacturacio']) || $nova['datafacturacio'] == '') {
    			$form->get( 'facturacions' )->get( $k )->get('datafacturacio')->addError( new FormError('') );
    			$errors[] = $desc.' > Cal indicar la data per poder fer l\'emissió dels rebuts'; 
    		}
    	
    		$dataFacturacio = \DateTime::createFromFormat('d/m/Y', $nova['datafacturacio'] );
    	
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
    			$errors[] = $desc.' > L\'import per a socis no és correcte '. $import;
    		}
    		 
    		$strImport = $nova['importactivitatnosoci'];
    		$fmt = numfmt_create( 'es_CA', \NumberFormatter::DECIMAL );
    		$importnosoci = numfmt_parse($fmt, $strImport);
    		if (!is_numeric($importnosoci) || $importnosoci <= 0) {
    			$form->get( 'facturacions' )->get( $k )->get('importactivitatnosoci')->addError( new FormError('') );
    			$errors[] = $desc.' > L\'import per a no socis no és correcte '. $importnosoci;
    		}
    		
    		if (count( $errors ) == 0) { 
    			$facturacio = new FacturacioActivitat($dataFacturacio, $nova['descripcio'], $activitat, $import, $importnosoci);
	    		$em->persist($facturacio);
	    		// Generar rebuts participants actius si escau (checkrebuts)
	    		if (isset($nova['checkrebuts'])) { // El check només s'envia si está activat
					if ($anyFacturaAnt == 0 || ($anyFacturaAnt > 0 && $anyFacturaAnt != $dataFacturacio->format('Y')) ) {
						// Obtenir $maxnumrebut per l'any 
						$anyFacturaAnt = $dataFacturacio->format('Y');
						$numrebut = $this->getMaxRebutNumAnyActivitat($anyFacturaAnt); // Max
					}
	    			foreach ($activitat->getParticipantsActius() as $participacio) {
	    				$rebut = $this->generarRebutActivitat($facturacio, $participacio, $numrebut);
	    				if ($rebut != null) $numrebut++;
	    			}
	    		}
	    	}
	    }
	    		 
	    		
    	if (count( $errors ) > 0) {
   			throw new \Exception( implode('<br/>', $errors) );
    	}
    }
    
    /* Desar planificació i docències de la facturació */
    public function desarcalendariAction(Request $request)
    {
    	try {
    		
	    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
	    		throw new AccessDeniedException();
	    	}
	    	
	    	$em = $this->getDoctrine()->getManager();
    		
	    	//$docenciesArray = json_decode( isset($data['docenciesjson'])?$data['docenciesjson']:'{}' );
	   		$docenciesArray = json_decode( $request->request->get('docenciesjson', '{}'), true );
	   		
	    	$facturacioId = (isset($docenciesArray['facturacio'])?$docenciesArray['facturacio']:0);
	    	
	    	$facturacio = $em->getRepository('FomentGestioBundle:FacturacioActivitat')->find($facturacioId);
	    	
	    	if ($facturacio == null) throw new \Exception('Facturació no trobada. No s\'ha desat la planificació');
    	
	    	$docencies = (isset($docenciesArray['docencies'])?$docenciesArray['docencies']: array());

	    	// Tractar les docències per totes les facturacions noves
	    	/*
	    	 *
	    	 {
   "facturacio":77,
   "docencies":[
      {
         "docent":2,
         "docentnom":"Mireia",
         "datadesde":"21/07/2017",
         "sessions":10,
         "preusessio":12,
         "horari":[
            {
               "tipus":"setmanal",
               "dades":"12:00+12:30+2",
               "hora":"12:00",
               "final":"12:30",
               "info":"dimarts"
            },
            {
               "tipus":"setmanal",
               "dades":"15:00+16:30+4",
               "hora":"15:00",
               "final":"16:30",
               "info":"dijous"
            },
            {
               "tipus":"mensual",
               "dades":"08:00+08:30+2+3",
               "hora":"08:00",
               "final":"08:30",
               "info":"segon dimecres"
            },
            {
               "tipus":"sessio",
               "dades":"11:00+11:45+28/07/2017",
               "hora":"11:00",
               "final":"11:45",
               "info":"28/07/2017"
            }
         ]
      }
   ]
}
	    	 *
	    	 */
	    	
	    	// Array amb els docents que cal esborrar. Inicialment tots
	    	$idsEsborrar = $facturacio->getDocentsIds();
	    	
	    	// Dies festius
	    	$paramFestius = $em->getRepository('FomentGestioBundle:Parametre')->findOneBy(array('clau' => UtilsController::DIES_FESTIUS_ANUALS));
	    	$strFestius = ($paramFestius != null?$paramFestius->getValor():'');
	    	$festius = explode(",", $strFestius);
	    	foreach ($festius as $festiu) $festiu = trim($festiu);
	    	
	    	foreach ($docencies as $docenciaArray) {
	    		// Validacions
	    		$datadesde = \DateTime::createFromFormat('d/m/Y', $docenciaArray['datadesde']);
	    		if ($datadesde === false) throw new \Exception('Cal indicar la data d\'inici');
	    		
	    		$sessions = $docenciaArray['sessions'];
	    		if ($sessions == '' || !is_numeric($sessions) || $sessions <= 0) throw new \Exception('El nombre de sessions ha de ser major que 0 ');
	    		
	    		$preusessio = $docenciaArray['preusessio'];
	    		if ($preusessio == '' || !is_numeric($preusessio) || $preusessio <= 0) throw new \Exception('El preu per sessions ha de ser major que 0€ ');
	    		$docencia = $facturacio->getDocenciaByDocentId($docenciaArray['docent']);
	    		
	    		if ($docencia != null) {
	    			// Existeix. Treure de l'array per esborrar i actualitzar
	    			//unset($idsEsborrar[ $docenciaArray['docent'] ]);
	    			$pos = array_search($docenciaArray['docent'], $idsEsborrar);
	    			if ($pos !== false) array_splice($idsEsborrar, $pos, 1 );
	    			$docencia->setDatadesde($datadesde);
	    			$docencia->setTotalhores($sessions);
	    			$docencia->setPreuhora($preusessio);
	    			
	    			$docencia->initCalendari(); // Baixa sessions
	    		} else {
	    			// Nova docència i planificació
	    			$professor = $em->getRepository('FomentGestioBundle:Proveidor')->find($docenciaArray['docent']);
	    			
	    			if ($professor == null) throw new \Exception('No s\'ha trobat el professor '.$docenciaArray['docentnom']);
	    			
	    			$docencia = new Docencia($facturacio, $professor, $datadesde, $sessions, $preusessio);
	    			
	    			$em->persist($docencia);
	    			
	    		}

	    		$errors = $docencia->setArrayDocencia( $docenciaArray['horari'] );
	    		if (count($errors) > 0)  throw new \Exception( implode(PHP_EOF, $errors) );
	    		
	    		$sessions = $docencia->crearCalendari( $festius );  // ... i crear sessions nova planificació
  	    		
	    	}

	    	// Esborrar la resta de docències existents
	    	foreach ($idsEsborrar as $id) {
	    		 
	    		$docencia = $facturacio->getDocenciaByDocentId($id);
	    		 
	    		if ($docencia != null) {
	    			if (!$docencia->esEsborrable()) throw new \Exception('Hi ha pagaments associats a '.$docencia->getProveidor()->getRaosocial());
	    			 
	    			$docencia->baixa();
	    		}
	    	}
	    	
	    	$em->flush();  // Ok
	    	
    	} catch (\Exception $e) {
    		$response = new Response($e->getMessage());
    		$response->setStatusCode(400);
    		return $response;
    	}
    	 
    	return new Response('Planificació desada correctament');
    	
    }
    
    /* Carregar form calendari facturació i docències */
    public function carregarcalendariAction(Request $request)
    {
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	$id = $request->query->get('id', 0);
    	 
    	$em = $this->getDoctrine()->getManager();
    
    	$facturacio = $em->getRepository('FomentGestioBundle:FacturacioActivitat')->find($id);
    	 
    	
    	// Camps relacionats amb el calendari i els docents
    	$form = $this->createFormBuilder()
    	->add('docenciesjson', 'hidden', array( 'data' => json_encode($facturacio->getArrayDocencies()) )) 
    	->add('facturacioid', 'hidden', array( 'data' => $id ))
    	->add('cercardocent', 'entity', array(
    		'error_bubbling'	=> true,
    		'read_only' 		=> false,
    		'class' 			=> 'FomentGestioBundle:Proveidor',
    		'query_builder' => function(EntityRepository $er) {
    			return $er->createQueryBuilder('p')
    			->where('p.databaixa IS NULL')
    			->orderBy('p.raosocial', 'ASC');
    		},
    		'choice_label' 		=> 'raosocial',
    		'multiple' 			=> false,
    		'placeholder' 		=> ''
   		))
   		->add('hores', 'number', array(
    			'required' 	=> false,
    			'scale'		=> 0,
    	))
    	->add('preuhora', 'number', array(
    			'required' 	=> false,
    			'scale'		=> 2,
    	))
    	->add('datadesde', 'text', array( 'mapped'	=> false, ) )
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
    			'expanded' 	=> true,
    			'multiple'	=> false,
    			'data'		=> UtilsController::PROG_SETMANAL
    	))
    	// Mensual
    	->add('setmanadelmes', 'choice', array(
    			'required'  => false,
    			'choices'   => UtilsController::getDiesDelMes(),	// select primer, segon...
    			'expanded' 	=> false,
    			'multiple'	=> false,
    	))
    	 
    	->add('diadelmes', 'choice', array(
    			'required'  => false,
    			'choices'   => UtilsController::getDiesSetmana(),	// select dilluns, dimarts...
    			'expanded' 	=> false,
    			'multiple'	=> false,
    	))
    	->add('horainicidiadelmes', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control')
    	))
    	->add('horafinaldiadelmes', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control')
    	))
    	// Setmanal
    	->add('datadesdesetmana', 'text', array( 'mapped'	=> false, ) )
    	->add('dlhorainici', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DILLUNS]['hora']
    	))
    	->add('dmhorainici', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMARTS]['hora']
    	))
    	->add('dxhorainici', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMECRES]['hora']
    	))
    	->add('djhorainici', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIJOUS]['hora']
    	))
    	->add('dvhorainici', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIVENDRES]['hora']
    	))
    	->add('dlhorafinal', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DILLUNS]['final']
    	))
    	->add('dmhorafinal', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMARTS]['final']
    	))
    	->add('dxhorafinal', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIMECRES]['final']
    	))
    	->add('djhorafinal', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control'),
    			//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIJOUS]['final']
    	))
    	->add('dvhorafinal', 'time', array(
    			'input'  => 'datetime', // o string
    			'widget' => 'single_text', // choice, text, single_text
    			'attr' 		=> array('class' => 'select-hora form-control'),
				//'data'		=> $setmanaCompleta[UtilsController::INDEX_DIVENDRES]['final']
		))
		// per sessions
  		->add('datasessio', 'text', array( 'mapped'	=> false, ) )
		->add('horainicisessio', 'time', array(
			'input'  => 'datetime', // o string
			'widget' => 'single_text', // choice, text, single_text
			'attr' 		=> array('class' => 'select-hora form-control')
    	))
    	->add('horafinalsessio', 'time', array(
			'input'  => 'datetime', // o string
			'widget' => 'single_text', // choice, text, single_text
			'attr' 		=> array('class' => 'select-hora form-control')
    	))->getForm();
    			
 
    	return $this->render('FomentGestioBundle:Includes:facturaciocalendari.html.twig',
    			array('form' => $form->createView(), 'facturacio' => $facturacio));
    }
    
    public function updatetaulaprogramacioAction(Request $request) {
    	// Carrega les programacions sese persistència, només per generar la taula
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$em = $this->getDoctrine()->getManager();
    	$facturacioId = $request->query->get('id', 0); // Curs
    	 
    	$facturacio = $em->getRepository('FomentGestioBundle:FacturacioActivitat')->find($facturacioId);
    	
    	if ($facturacio == null) $facturacio = new FacturacioActivitat();
    	 
    	$strDocencies = $request->query->get('docencies', '[]');
    
    	// recull JSON creat des de la vista i l'envia per repintar taula
    	return $this->render('FomentGestioBundle:Includes:taulaprogramaciofacturacio.html.twig',
    			array('facturacio' => $facturacio, 'docencies' => json_decode($strDocencies)));
    
    }
    
    public function esborraractivitatAction(Request $request)
    {
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$em = $this->getDoctrine()->getManager();
    	 
    	$id = $request->query->get('id', 0);
    	
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
   		
   		return $this->render('FomentGestioBundle:Rebuts:infoactivitat.html.twig',
   				array('dades' => $activitat->getDadesFacturacio(), 'queryparams' => array()));
   		 
    }
    
	public function activitatCancelacioAction(Request $request)
    {
    	if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$id = $request->query->get('id', 0);
    	
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
	    
		return $this->render('FomentGestioBundle:Rebuts:infoactivitat.html.twig',
   				array('dades' => $activitat->getDadesFacturacio(), 'queryparams' => array()));
    }
    
    private function inscriureParticipant($activitat, $nouparticipant, $generarrebut = true) {
    	$em = $this->getDoctrine()->getManager();
    	
    	if ($activitat == null) throw new \Exception('L\'activitat no existeix ');
    	
    	$participacio = $activitat->getParticipacioByPersonaId($nouparticipant->getId());
    	
    	if ($participacio != null && $participacio->getDatacancelacio() == null) throw new \Exception('Aquesta persona ja està inscrita a l\'activitat' );
    	 
    	if ($participacio != null && $participacio->getDatacancelacio() != null) {
    		// reactivar alta
    		$participacio->setDatacancelacio(null);
    		
    		// Activar rebuts de baixa
    		$rebuts = $participacio->getRebutsParticipant(true);
    		foreach ($rebuts as $rebut) $rebut->anularbaixa(); 
    	} else {
    	
	    	$participacio = $activitat->addParticipacioActivitat($nouparticipant);
	
	    	$em->persist($participacio);
	    	 
	    	/**************************** Crear els rebuts per aquesta inscripció ****************************/
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
	    	
	    	if ($facturacio != null && $generarrebut == true) {
		    	$anyFactura = $facturacio->getDatafacturacio()->format('Y');
	    		$numrebut = $this->getMaxRebutNumAnyActivitat($anyFactura); // Max
	    		$rebut = $this->generarRebutActivitat($facturacio, $participacio, $numrebut);
	    	}
    	}
	}
    
	 
	private function esborrarParticipant($activitatid, $esborrarparticipant) {
		$em = $this->getDoctrine()->getManager();
		
		$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($activitatid);
		if ($activitat == null) throw new \Exception('L\'activitat no existeix');
		
		$participacio = $activitat->getParticipacioByPersonaId($esborrarparticipant->getId());
		
		if ($participacio == null) throw new \Exception('Aquesta persona no està inscrita a l\'activitat');
		
		if ($participacio->getDatacancelacio() != null) throw new \Exception('Aquesta persona ja es troba de baixa de l\'activitat');
		
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
	
	/* Veure / actualitzar proveïdors */
	public function proveidorsAction(Request $request)
	{
		if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		
		$page =  $request->query->get('page', 1);
		$perpage =  $request->query->get('perpage', UtilsController::DEFAULT_PERPAGE_WITHFORM);
		$filtre = $request->query->get('filtre', '');
		
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
		
		if ($request->isXmlHttpRequest() == true) {
			// Ajax call renders only table activitats
			return $this->render('FomentGestioBundle:Includes:taulaproveidors.html.twig',
				array('form' => $form->createView(), 
						'proveidors' => $proveidors, 'queryparams' => $queryparams));
		}
	
		return $this->render('FomentGestioBundle:Pages:proveidors.html.twig',
				array('form' => $form->createView(), 
						'proveidors' => $proveidors, 'queryparams' => $queryparams));
	}
	
	public function desarproveidorAction(Request $request) {
		if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
			throw new AccessDeniedException();
		}
		$em = $this->getDoctrine()->getManager();
		
		$id = 0;
		$action = '';
		if ($request->getMethod() != 'POST') {
			$id = $request->query->get('id', 0);
			$action = $request->query->get('action', '');
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
				
				if ($proveidor->getCorreu() != '' && !filter_var($proveidor->getCorreu(), FILTER_VALIDATE_EMAIL)) {
					$form->get( 'correu' )->addError( new FormError('Format incorrecte') );
					throw new \Exception('L\'adreça de correu no és correcta');
				}
				
				$proveidor->setDatamodificacio(new \DateTime());
					 
				$em->flush();
			   
				$this->get('session')->getFlashBag()->add('notice',	'Proveidor desat correctament');
			} else {
				
				if ($action == 'baixa') {
					$proveidor->setDatabaixa( new \DateTime() );
					$proveidor->setDatamodificacio(new \DateTime());
					
					$em->flush();
					
					$this->get('session')->getFlashBag()->add('notice',	'Proveidor donat de baixa correctament');
				}
			}
		} catch (\Exception $e) {
			
			$this->get('session')->getFlashBag()->add('error',	$e->getMessage());
		}
	
		return $this->render('FomentGestioBundle:Includes:formproveidors.html.twig',
				array('form' => $form->createView()));
	
	}
	
	public function clonaractivitatAction(Request $request) {
		if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
				$this->inscriureParticipant($activitat, $participant->getPersona(), false);
			}
			
			// Afegir un any a les facturacions			
			$facturacions = $activitat->getFacturacions();
			 
			foreach ($facturacions as $facturacio_iter) {
				$em->persist($facturacio_iter);
				$facturacio_iter->getDatafacturacio()->add(new \DateInterval('P1Y'));
				
				$docents = $facturacio_iter->getDocents(); // Clone docents
						
				foreach ($docents as $docent_iter) $em->persist($docent_iter);
			}
			
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
		if (false === $this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
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
