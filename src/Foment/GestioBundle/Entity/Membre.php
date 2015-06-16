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
    public $soci; // FK taula socis
    
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
     * És membre actiu en el periode indicat
     *
     * @return boolean
     */
    public function esMembreActiuPeriode(\DateTime $datainici, \DateTime $datafinal) 
    {
    	// datainscripcio <= datafinalperiode
    	//				&&
    	// datacancelacio NULL o datacancelacio >= datafinalperiode
    	
    	if ($this->datacancelacio != null && $this->datacancelacio <= $datafinal) return false;
    	 
    	if ($datafinal != null && $this->datainscripcio > $datafinal) return false;
    	
    	return true;
    }
    
    /**
     * És membre actiu en el periode indicat
     *
     * @return boolean
     */
    public function esMembreActiuAny($any)
    {
    	// datainscripcio <= datafinalperiode
    	//				&&
    	// datacancelacio NULL o datacancelacio >= datainiciperiode
    	 
    	if ($this->datacancelacio != null && $this->datacancelacio->format('Y') <= $any) return false;
    	
    	if ($this->datainscripcio->format('Y') > $any) return false;
    	 
    	return true;
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
     * Get rebut detall del període, inclouent baixes
     *
     * @return boolean
     */
    public function getRebutDetallDates(\DateTime $datainici, \DateTime $datafinal)
    {
    	$detallsCurrent = array();
		foreach ($this->detallsrebuts as $detall) {
			$rebut = $detall->getRebut();
			
			if ($rebut != null && $rebut->getDataemissio() != null &&
    			$rebut->getDataemissio() >= $datainici && $rebut->getDataemissio() <= $datafinal ) $detallsCurrent[] = $detall; // trobat
			
		}
    	 
    	return $detallsCurrent;
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
     * Get rebuts detall de l'any sense incloure baixes
     *
     * @return boolean
     */
    public function getRebutDetallAny($current)
    {
    	$detallsCurrent = array();
    	foreach ($this->detallsrebuts as $detall) {
    		$rebut = $detall->getRebut();
    			
    		if ($rebut != null && $rebut->getDataemissio() != null 
    			&& $detall->getDatabaixa() == null 
    			&& $rebut->getDataemissio()->format('Y') == $current) $detallsCurrent[] = $detall; // trobat
    			
    	}
    
    	if (count($detallsCurrent) > 1) {
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
     * Get rebut detall del període, inclouent baixes
     *
     * @return boolean
     */
    public function getRebutDetallPeriode($periode)
    {
    	foreach ($this->detallsrebuts as $detall) {
    		$rebut = $detall->getRebut();
    			
    		if ($rebut != null && $rebut->getPeriodenf() != null && $rebut->getPeriodenf() == $periode) return $detall; // trobat sense facturar al periode
    		if ($rebut != null && $rebut->getFacturacio() != null && $rebut->getFacturacio()->getPeriode() != null &&
    				$rebut->getFacturacio()->getPeriode() == $periode) return $detall; // trobat facturat
    	}
    
    	return null;
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
