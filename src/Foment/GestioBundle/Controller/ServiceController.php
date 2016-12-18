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
		if ($familianombrosa == true && $seccio->getExemptfamilia() == true) return 0; // Execmpts secció quota 0 famílies nombroses
		
		// Obtenir quotes per l'any
		$quota = $seccio->getQuotaAny($any, $juvenil);
		
		if ($seccio->getSemestral() == false) return  $quota * $seccio->getFacturacions();
		
		if (!$seccio->esGeneral()) return $quota;  // No apliquen els descomptes a seccions
		 
		$percentproporcio = 1;

		if ($exempt > 0 && $exempt <= 100) $quota = $quota * ( (100 - $exempt) / 100);  // % exempt de la quota secció general
		
		if ($diainicimembre > 0 && $seccio->esGeneral()) { 

			if ($this->existeixenFacturacionsActivesAbans($any, $diainicimembre) == true) {  // Proporcional només després de la primera facturació 
				// Tractament proporcional quota general. Quotes de secció es cobren integres
				$percentproporcio = (365 - $diainicimembre)/365;
			}
		}
		 
		$percentdescompte = 1;
		if ($descompte == true) $percentdescompte = 1-UtilsController::PERCENT_DESCOMPTE_FAMILIAR;
		return round($quota * $percentdescompte * $percentproporcio);
	}
   
	/**
	 * Existeixen facturacions actives abans del dia de l'any
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function existeixenFacturacionsActivesAbans($any, $diaany)
	{
		$datadomiciliacio = $this->getDataPrimeraDomiciliacio($any);
	
		if ($datadomiciliacio != null && $datadomiciliacio->format('z') < $diaany) return true;
	
		return false;
	}
	
	
	/**
	 * Data del primer enviament de les domiciliacions anuals. Moment inflexió per començar a comptar quotes proporcionals
	 * Si no n'hi ha retorna null
	 */
	private function getDataPrimeraDomiciliacio($current) {
		if ($current <= 0) $current = date('Y');
		 
		$facturacio = null;
		$avui = new \DateTime();
		$facturacions = UtilsController::queryGetFacturacions($this->em, $current);  // Ordenades per data facturacio DESC
		$primera = count($facturacions) - 1; /* Estan ordenades al revés */
	 
		if (count($facturacions) == 0) return null;
		if (!$facturacions[$primera]->domiciliada()) return null;
		return $facturacions[$primera]->getDatadomiciliada();
	}
	
	
	/**
	 * obtenir paràmetre
	 */
	public function getParametre($clau)
	{
		$parametre = $this->em->getRepository('FomentGestioBundle:Parametre')
			->findOneBy( array('clau' => $clau ));
			
		return ($parametre != null?$parametre->getValor():'');
	}
	
}
