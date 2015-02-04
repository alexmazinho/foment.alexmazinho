<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends Controller
{

	public function loginAction(Request $request)
    {
    	
    	$session = $request->getSession();
    	
    	// get the login error if there is one
    	if ($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
    		$this->get('session')->getFlashBag()->add(
    				'error',
    				$request->attributes->get(SecurityContextInterface::AUTHENTICATION_ERROR)->getMessage()
    		);
    	} elseif (null !== $session && $session->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
    		/*$error = $session->get(SecurityContextInterface::AUTHENTICATION_ERROR);*/
    		$this->get('session')->getFlashBag()->add(
    				'error',
    				$session->get(SecurityContextInterface::AUTHENTICATION_ERROR)->getMessage()
    		);
    		$session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);
    		
    	} else {
    		//return $this->forward('FomentGestioBundle:Pages:index', array());
    	}
    	
    	// last username entered by the user
    	$lastUsername = (null === $session) ? '' : $session->get(SecurityContextInterface::LAST_USERNAME);
    	 
    	/*
    	$username = $this->get('request')->request->get('username','');
    	    
    	if ($username != "") {
    		return $this->redirect($this->generateUrl('foment_gestio_homepage'));
    		//return $this->forward('FomentGestioBundle:Pages:index', array());
    	}*/
    	
    	return $this->render('FomentGestioBundle:Pages:login.html.twig', array(
                // last username entered by the user
                'last_username' => $lastUsername
            ));
    }
   
}
