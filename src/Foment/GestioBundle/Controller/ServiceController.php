<?php

namespace Foment\GestioBundle\Controller;

use Doctrine\ORM\EntityManager;

class ServiceController
{
	// Us dels serveis
	// $servei = $this->get('foment.serveis');
	// $servei->quotaSeccioAny( ...);
	
	protected $em;
	
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}
	
	/**
	 * Quotes secció anual segons paràmetres
	 */
	public function quotaSeccioAny($juvenil, $familianombrosa, $descompte, $exempt, $seccio, $any, $diainicimembre)
	{
		if ($exempt == true && $seccio->esGeneral() == true) return 0;  // Exempts de la secció general
		
		if ($familianombrosa == true && $seccio->getExemptfamilia() == true) return 0; // Execmpts secció quota 0 famílies nombroses
		
		// Obtenir quotes per l'any
		$quota = $seccio->getQuotaAny($any, $juvenil);
		if (!$seccio->esGeneral()) return $quota;  // No apliquen els descomptes a seccions
		 
		$percentproporcio = 1;
	
		if ($diainicimembre > 0 && $seccio->esGeneral()) { 

			$periode = $this->em->getRepository('FomentGestioBundle:Periode')
						->findOneBy( array('anyperiode' => $any, 'semestre' => 1));
			if ($periode != null && $periode->existeixenFacturacionsActivesAbans($diainicimembre) == true) {  // Proporcional només després de la primera facturació 
				// Tractament proporcional quota general. Quotes de secció es cobren integres
				$percentproporcio = (365 - $diainicimembre)/365;
			}
		}
		 
		$percentdescompte = 1;
		if ($descompte == true) $percentdescompte = 1-UtilsController::PERCENT_DESCOMPTE_FAMILIAR;
		return $quota * $percentdescompte * $percentproporcio;
	}
   
	/**
	 * s'han fet les primeres facturacions de l'any?
	 */
	public function facturacionsIniciadesAny($any, $diainicimembre)
	{
		$periode = $this->em->getRepository('FomentGestioBundle:Periode')
				->findOneBy( array('anyperiode' => $any, 'semestre' => 1));

		if ($periode != null && $periode->existeixenFacturacionsActivesAbans($diainicimembre) == true) return true;
	
		return false;
	}
}
