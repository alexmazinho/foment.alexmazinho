<?php 
// src/Foment/GestioBundle/Entity/Membre.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="rol", type="string", length=1)
 * @ORM\DiscriminatorMap({"M" = "Membre", "J" = "Junta"}) 
 * @ORM\Table(name="membres")
 */

class Membre
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Soci", inversedBy="membrede")
     * @ORM\JoinColumn(name="soci", referencedColumnName="id")
     */
    protected $soci; // FK taula socis
    
    /**
     * @ORM\ManyToOne(targetEntity="Seccio", inversedBy="membres")
     * @ORM\JoinColumn(name="seccio", referencedColumnName="id")
     */
    protected $seccio; // FK taula seccions

    /**
     * @ORM\Column(type="date", nullable=false)
     */
    protected $datainscripcio;
    
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $datacancelacio;
    
    /**
     * @ORM\OneToMany(targetEntity="RebutDetall", mappedBy="quotaseccio")
     */
    protected $detallsrebuts;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $dataentrada;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datamodificacio;

    /**
     * Constructor
     */
    public function __construct()
    {
    	$this->id = 0;
    	$this->datainscripcio = new \DateTime();
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->detallsrebuts = new \Doctrine\Common\Collections\ArrayCollection();
    	
    	// Hack per permetre múltiples constructors
    	$a = func_get_args();
    	$i = func_num_args();
    	
    	if ($i == 1) {
    		if ($a[0] instanceof Membre and method_exists($this,$f='__constructMembre')) {
    			call_user_func_array(array($this,$f),$a);
    		}
    	}
    }
    
    /**
     * Constructor from Membre
     *
     * @param \Foment\GestioBundle\Entity\Membre $membre
     */
    public function __constructMembre($membre)
    {
    	$this->setDatainscripcio($membre->getDatainscripcio());
    	$this->setDatacancelacio(null);
    	
    	$detalls = $membre->getDetallsrebuts();
    	$this->setDetallsrebuts($detalls);
    	foreach ($detalls as $detall) $detall->setQuotaseccio($this);
    	
    	$membre->setDetallsrebuts(new \Doctrine\Common\Collections\ArrayCollection()); // init
    	$membre->setDatamodificacio(new \DateTime());
    	$membre->setDatacancelacio(new \DateTime());
    	
    }
    
    /**
     * És junta? false
     *
     * @return boolean
     */
    public function esJunta() { return false; }
    
    
    /**
     * És junta vigent? false
     *
     * @return boolean
     */
    public function esJuntaVigent() { return false; }
    
    
    /**
     * Get carrec junta as string
     *
     * @return string
     */
    public function getCarrecjunta()
    {
    	return '';
    }
    
    /**
     * Get info as string
     *
     * @return string
     */
    public function getInfo()
    {
    	if ($this->datacancelacio != null) return "baixa des del ".$this->datacancelacio->format('d/m/Y'); 
    	
    	return "";
    }
    
    /**
     * Get info plus import as string
     *
     * @return string
     */
    public function getInfoPreu()
    {
    	return $this->getSeccio()->getInfoPreu(); 
    }

    /**
     * Get nom (usat pel sort)
     *
     * @return string
     */
    public function getNom()
    {
    	return $this->getSoci()->getNom();
    }
    
    /**
     * Get cognoms (usat pel sort)
     *
     * @return string
     */
    public function getCognoms()
    {
    	return $this->getSoci()->getCognoms();
    }
    
    
	/**
     * Get datanaixement
     *
     * @return \DateTime 
     */
    public function getDatanaixement()
    {
        return $this->getSoci()->getDatanaixement();
    }
    
    /**
     * És membre alta en el periode indicat
     *
     * @return boolean
     */
    public function esMembreAltaPeriode($datainici, $datafinal)
    {
    	// Altes i baixes dins el mateix periode només surten a baixes
    	
    	$baixa = $this->esMembreBaixaPeriode($datainici, $datafinal);
    	
    	if ($baixa) return false;
    	
    	if ($this->datainscripcio == null) return false; // No hauria de passar
    	
    	// $this->datainscripcio != null
    	
    	if ($datainici == null) return $this->datainscripcio->format('Y-m-d') <= $datafinal->format('Y-m-d');
    	 
    	// $datainici != null
    	if ($datafinal == null) return true;
    	 
    	// $datafinal != null
    	 
    	return  $this->datainscripcio->format('Y-m-d') >= $datainici->format('Y-m-d') &&
    			$this->datainscripcio->format('Y-m-d') <= $datafinal->format('Y-m-d');

    }
    
    /**
     * És membre baixa en el periode indicat
     *
     * @return boolean
     */
    public function esMembreBaixaPeriode($datainici, $datafinal)
    {
    	// Altes i baixes dins el mateix periode només surten a baixes
    	$soci = $this->getSoci();
    	
    	if ($this->datacancelacio == null) {
    		// Mirar si és soci de baixa. No hauria de passar
    		if ($soci->esBaixa()) {
    			$databaixa = $soci->getDatabaixa();
    			
    			if ($datainici == null) {
    				if ($datafinal == null) return true;
    				
    				return $databaixa->format('Y-m-d') <= $datafinal->format('Y-m-d');
    			} 
    			// $datainici != null
    			
    			if ($datafinal == null) return ($databaixa->format('Y-m-d') >= $datainici->format('Y-m-d'));
    			
    			// $datafinal != null
    			
    			return ($databaixa->format('Y-m-d') >= $datainici->format('Y-m-d') &&
    					$databaixa->format('Y-m-d') <= $datafinal->format('Y-m-d'));
    		}
    	}
    	
    	// $this->datacancelacio != null
    	
    	if ($datainici == null) return $this->datacancelacio->format('Y-m-d') <= $datafinal->format('Y-m-d');
    	
    	// $datainici != null
    	if ($datafinal == null) return true;
    	
    	// $datafinal != null
    	
    	return $this->datacancelacio->format('Y-m-d') >= $datainici->format('Y-m-d') && 
    			$this->datacancelacio->format('Y-m-d') <= $datafinal->format('Y-m-d');
    }
    
    /**
     * És membre actiu en el periode indicat
     *
     * @return boolean
     */
    public function esMembreActiuPeriode($datainici, $datafinal) 
    {
    	// datainscripcio <= datafinalperiode
    	//				&&
    	// datacancelacio NULL o datacancelacio >= datafinalperiode
    	
    	$soci = $this->getSoci();
    	
    	if ($soci->esBaixa()) {
    		$databaixa = $soci->getDatabaixa();
    		
    		if ($this->datainscripcio == null || $this->datacancelacio == null) return false; // No hauria de passar
    		
    		if ($datafinal == null) return false;
    		
    		if ($databaixa->format('Y-m-d') <= $datafinal->format('Y-m-d')) return false;
    		
    		// Si continua baixa posterior al periode consultat
    	}
    	
    	if ($datainici == null) {
    		if ($datafinal == null) return $this->datacancelacio == null;
    		
    		return ($this->datacancelacio == null || 
    				($this->datacancelacio != null && $this->datacancelacio->format('Y-m-d') <= $datafinal->format('Y-m-d')) );
    	}
    	
    	if ($datafinal == null) return ($this->datainscripcio != null && $this->datainscripcio->format('Y-m-d') <= $datafinal->format('Y-m-d'));
    	
    	
    	// $datainici != null && $datafinal != null
    	if ($this->datainscripcio == null) return false; // No hauria de passar
    	
    	if ($this->datacancelacio == null) return $this->datainscripcio->format('Y-m-d') <= $datafinal->format('Y-m-d'); 
    	
    	// $this->datainscripcio != null && $this->datacancelacio != null
    	
    	return 	$this->datainscripcio->format('Y-m-d') <= $datafinal->format('Y-m-d')  && 
    			$this->datacancelacio->format('Y-m-d') >= $datainici->format('Y-m-d');
    	
    	return true;
    }
    
    /**
     * És membre actiu en el periode indicat
     *
     * @return boolean
     */
    public function esMembreActiuAny($any)
    {
    	// Cercar informació entre dates
    	$desde = \DateTime::createFromFormat('Y-m-d', $any."-01-01");
    	$fins = \DateTime::createFromFormat('Y-m-d', $any."-12-31");
    	
    	return $this->esMembreActiuPeriode($desde, $fins);
    }
    
    /**
     * Get quota membre per any $current
     *
     * @return double
     */
    public function getQuotaAny($current)
    {
    	return UtilsController::quotaMembreSeccioAny($this, $current);
    }

    /**
     * Get text quota membre per any $current
     *
     * @return string
     */
    public function getTextQuotaAny($current)
    {
    	return trim(UtilsController::concepteMembreSeccioRebut($this, $current));
    }
    
    /**
     * Get rebuts detalls tots sense incloure baixes
     *
     * @return boolean
     */
    public function getRebutDetallTots()
    {
    	$detalls = array();
    	foreach ($this->detallsrebuts as $detall) {
    		$rebut = $detall->getRebut();
    		 
    		if ($rebut != null && $detall->getDatabaixa() == null) $detalls[] = $detall; // trobat
    		 
    	}
    
    	return $detalls;
    }
    
    /**
     * Get rebuts tots. Per defecte no inclou anul·lacions 
     *
     * @return boolean
     */
    public function getRebutsMembre($baixa = false)
    {
    	$rebuts = array();
    	foreach ($this->detallsrebuts as $detall) {
    		$rebut = $detall->getRebut();
    		if ($rebut != null && !isset($rebuts['id'.$rebut->getId()])) {
    			if ($baixa == true || ($detall->getDatabaixa() == null && $rebut->getDatabaixa() == null)) $rebuts['id'.$rebut->getId()] = $rebut;
    		}
    	}

    	return $rebuts;
    }
    
    
    /**
     * Get rebuts detall de l'any ordenat, incloent opcionalment baixes i amb possibilitat d'ordenar
     *
     * @return boolean
     */
    public function getRebutDetallAny($current, $baixes = false, $ordre = true)
    {
  		$detallsCurrent = array();
		foreach ($this->detallsrebuts as $detall) {
			$rebut = $detall->getRebut();
			
			if ($rebut != null && $rebut->getDataemissio() != null 
				&& ($detall->getDatabaixa() == null || $baixes == true)
				&& $rebut->getDataemissio()->format('Y') == $current) $detallsCurrent[] = $detall; // trobat
			
		}
    	if ($ordre == true && count($detallsCurrent) > 1) {
    		usort($detallsCurrent, function($a, $b) {
    			if ($a === $b) {
    				return 0;
    			}
    			return ($a->getId() < $b->getId())? -1:1;
    		});
    	}
	    	
    	return $detallsCurrent;
    }
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Membre
     */
    public function setDataentrada($dataentrada)
    {
        $this->dataentrada = $dataentrada;

        return $this;
    }

    /**
     * Get dataentrada
     *
     * @return \DateTime 
     */
    public function getDataentrada()
    {
        return $this->dataentrada;
    }

    /**
     * Set datamodificacio
     *
     * @param \DateTime $datamodificacio
     * @return Membre
     */
    public function setDatamodificacio($datamodificacio)
    {
        $this->datamodificacio = $datamodificacio;

        return $this;
    }

    /**
     * Get datamodificacio
     *
     * @return \DateTime 
     */
    public function getDatamodificacio()
    {
        return $this->datamodificacio;
    }

    /**
     * Set soci
     *
     * @param \Foment\GestioBundle\Entity\Soci $soci
     * @return Membre
     */
    public function setSoci(\Foment\GestioBundle\Entity\Soci $soci = null)
    {
        $this->soci = $soci;

        return $this;
    }

    /**
     * Get soci
     *
     * @return \Foment\GestioBundle\Entity\Soci 
     */
    public function getSoci()
    {
        return $this->soci;
    }

    /**
     * Set seccio
     *
     * @param \Foment\GestioBundle\Entity\Seccio $seccio
     * @return Membre
     */
    public function setSeccio(\Foment\GestioBundle\Entity\Seccio $seccio = null)
    {
        $this->seccio = $seccio;

        return $this;
    }

    /**
     * Get seccio
     *
     * @return \Foment\GestioBundle\Entity\Seccio 
     */
    public function getSeccio()
    {
        return $this->seccio;
    }

    /**
     * Set datainscripcio
     *
     * @param \DateTime $datainscripcio
     * @return Membre
     */
    public function setDatainscripcio($datainscripcio)
    {
        $this->datainscripcio = $datainscripcio;

        return $this;
    }

    /**
     * Get datainscripcio
     *
     * @return \DateTime 
     */
    public function getDatainscripcio()
    {
        return $this->datainscripcio;
    }

    /**
     * Set datacancelacio
     *
     * @param \DateTime $datacancelacio
     * @return Membre
     */
    public function setDatacancelacio($datacancelacio)
    {
        $this->datacancelacio = $datacancelacio;

        return $this;
    }

    /**
     * Get datacancelacio
     *
     * @return \DateTime 
     */
    public function getDatacancelacio()
    {
        return $this->datacancelacio;
    }
    
    /**
     * Add detallrebut
     *
     * @param \Foment\GestioBundle\Entity\RebutDetall $detallrebut
     * @return Membre
     */
    public function addRebutDetall(\Foment\GestioBundle\Entity\RebutDetall $detallrebut)
    {
    	
    	$this->detallsrebuts->add($detallrebut);

        return $this;
    }

    /**
     * Remove detallrebut
     *
     * @param \Foment\GestioBundle\Entity\RebutDetall $detallrebut
     */
    public function removeRebutDetall(\Foment\GestioBundle\Entity\RebutDetall $detallrebut)
    {
        $this->detallsrebuts->removeElement($detallrebut);
    }

    /**
     * Get detallsrebut
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDetallsrebuts()
    {
    	return $this->detallsrebuts;
    }
    
    /**
     * Set detallsrebut
     *
     * @param \Doctrine\Common\Collections\Collection  $detallsrebut
     * @return Membre
     */
    public function setDetallsrebuts(\Doctrine\Common\Collections\Collection $detallsrebut = null)
    {
    	$this->detallsrebuts = $detallsrebut;
    
    	return $this;
    }
    
}
