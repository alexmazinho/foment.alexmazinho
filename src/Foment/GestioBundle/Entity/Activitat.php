<?php 
// src/Foment/GestioBundle/Entity/Activitat.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity 
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="tipus", type="string", length=8)
 * @ORM\DiscriminatorMap({"anual" = "ActivitatAnual", "puntual" = "ActivitatPuntual"}) 
 * @ORM\Table(name="activitats")
 */
class Activitat
{
	const DEFAULT_MAX_PARTICIPANTS = 20;
	const TIPUS_PUNTUAL = 'puntual';
	const TIPUS_ANUAL = 'anual';
	
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=70, nullable=false)
     */
    protected $descripcio;
    
    /**
	 * @ORM\Column(type="decimal", precision=8, scale=2, nullable=true)
	 * @Assert\Type(type="numeric", message="Format incorrecte.")
	 * @Assert\GreaterThanOrEqual(value="0", message="Valor incorrecte.")
	 * 
	 */
	protected $estimadespeses;

	/**
	 * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type(type="integer", message="Format incorrecte.")
	 * @Assert\GreaterThanOrEqual(value="0", message="Valor incorrecte.")
	 */
	protected $totalhores;
	
	/**
	 * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type(type="integer", message="Format incorrecte.")
	 * @Assert\GreaterThanOrEqual(value="0", message="Valor incorrecte.")
	 */
	protected $maxparticipants;
	
	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $lloc;
	
	/**
	 * @ORM\Column(type="decimal", precision=7, scale=5, nullable=true)
	 */
	protected $latitud;  //Latitude: -85 to +85
	
	/**
	 * @ORM\Column(type="decimal", precision=8, scale=5, nullable=true)
	 */
	protected $longitud; //Longitude: -180 to +180
	
	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $observacions;
	
	/**
	 * @ORM\OneToMany(targetEntity="Participant", mappedBy="activitat")
	 */
	protected $participants;
	
	/**
	 * @ORM\OneToMany(targetEntity="Facturacio", mappedBy="activitat")
	 */
	protected $facturacions;
	
	/**
	 * @ORM\Column(type="boolean", nullable=false)
	 */
	protected $finalitzat;
	
	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 */
	protected $dataentrada;
	
	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 */
	protected $datamodificacio;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;
	
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = 0;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->databaixa = null;
        $this->maxparticipants = self::DEFAULT_MAX_PARTICIPANTS;
        $this->finalitzat = false;
        $this->participants = new \Doctrine\Common\Collections\ArrayCollection();
        $this->facturacions = new \Doctrine\Common\Collections\ArrayCollection();	// Facturacions de l'activitat
    }
   
    
    public function __clone() {
    	$this->id = null;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	
    	$this->participants = new \Doctrine\Common\Collections\ArrayCollection(); // Init participants
    	
    	$facturacions = $this->getFacturacions();
    
    	if ($facturacions != null) {
	    	$this->facturacions = new \Doctrine\Common\Collections\ArrayCollection();
	    
	    	foreach ($facturacions as $facturacio_iter) {
	    		if (!$facturacio_iter->esBaixa()) {
	    			$cloneFacturacio = clone $facturacio_iter;
	    
	    			$this->addFacturacions($cloneFacturacio);
	    			$cloneFacturacio->setActivitat($this);
	    		}
	    	}
    	}
    }
    
    /**
     * És baixa? false
     *
     * @return boolean
     */
    public function esBaixa() { return $this->databaixa != null; }
    
    /**
     * Get csvRow, qualsevol Entitat que s'exporti a CSV ha d'implementar aquest mètode
     * Delimiter ;
     * Quotation ""
     *
     * @return string
     */
    public function getCsvRow()
    {
    	$row = '"'.$this->id.'";"'.$this->descripcio.'";"'.$this->getCurs().'";';
    	$row .= '"'.$this->getQuotaparticipant().'";"'.$this->getQuotaparticipantnosoci().'";"'.$this->getTotalParticipants().'"';
    	 
    	return $row;
    }
    
    /**
     * es anual?.
     *
     * @return boolean
     */
    public function esAnual()
    {
    	return true;
    }
    
    /**
     * Get array() buit, per sobreescriure
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocentsActius()
    {
    	return array();
    }
    
    /**
     * es modificable?. Només si encara no hi ha rebuts generats
     *
     * @return boolean
     */
    public function esModificable()
    {
	    foreach ($this->facturacions as $facturacio) {

	    	if ($facturacio->getTotalrebuts() > 0) return false;
	    }
	    return true;
    }
    
    /**
     * es esborrable?. Només si cap rebut pagat
     *
     * @return boolean
     */
    public function esEsborrable()
    {
    	foreach ($this->facturacions as $facturacio) {
    		if ($facturacio->esEsborrable() == false) return false;
    	}
    	return true;
    }
    
    
    
    /**
     * Get tipus as string. must implement
     *
     * @return string
     */
    public function getTipus()
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
		return $this->descripcio;
    }
    
    /**
     * Get info del calendari de l'activitat as string
     *
     * @return string
     */
    public function getInfoCalendari()
    {
    	return '';
    }
    
    /**
     * Get info plus import as string
     *
     * @return string
     */
    public function getInfoPreu()
    {
    	return $this->descripcio . '    (' . number_format($this->quotaparticipant, 2, ',', '.') .' / '. 
    		number_format($this->quotaparticipantnosoci, 2, ',', '.').' €) ';
    }

    /**
     * Get preu orientatiu 
     *
     * @return string
     */
    public function getPreuOrientatiu()
    {
    	if (is_numeric($this->estimadespeses) && is_numeric($this->maxparticipants) && $this->maxparticipants > 0) 
    		return number_format($this->estimadespeses / $this->maxparticipants, 2, ',', '.') .' €';
    	
    	return '--';
    }
    
    /**
     * Get participants no cancelats
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipantsActius()
    {
    	$arr = array();
    	foreach ($this->participants as $participant) {
    		if ($participant->getDatacancelacio() == null) {
    			$arr[] = $participant;
    		}
    	}
    
    	return $arr;
    }
    
    public function getTotalParticipants()
    {
    	return count($this->getParticipantsActius());
    }
    
    /**
     * quotaparticipant facturacions actives
     *
     * @return decimal
     */
    public function getQuotaparticipant()
    {
    	$import = 0;
    	$facturacions = $this->getFacturacionsActives();
    	foreach ($facturacions as $facturacio) {
    		$import += $facturacio->getImportactivitat();
    	}
    	return $import;
    }
    
    /**
     * quotaparticipantnosoci facturacions actives
     *
     * @return decimal
     */
    public function getQuotaparticipantnosoci()
    {
    	$import = 0;
    	$facturacions = $this->getFacturacionsActives();
    	foreach ($facturacions as $facturacio) {
    		$import += $facturacio->getImportactivitatnosoci();
    	}
    	return $import;
    }
    
    
    
    /**
     * Get participants sorted by cognom. No cancelats o tots
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipantsSortedByCognom($cancelats = false)
    {
    	if ($cancelats == true) $arr = $this->getParticipants()->toArray();// Tots
    	else $arr = $this->getParticipantsActius();
    
    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getPersona()->getCognoms() < $b->getPersona()->getCognoms())? -1:1;;
    	});
    		 
    	return $arr;
    }
    
    
    /**
     * Get facturacions actives ordenades per data
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFacturacionsSortedByDatafacturacio()
    {
    	$actives = $this->getFacturacionsActives();
    	
    	usort($actives, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getDatafacturacio() < $b->getDatafacturacio())? -1:1;;
    	});

    	return $actives;
    }
    
    /**
     * Returns participacio with Persona identified by $id or null no cancelades
     *
     * @param integer $id
     * @return \Foment\GestioBundle\Entity\Participant
     */
    public function getParticipacioByPersonaId($id) {
    	foreach ($this->participants as $participant)  {
			if ($participant->getDatacancelacio() == null && $participant->getPersona()->getId() == $id) return $participant;
    	}	
    	return null;
    }
    
    /**
     * Add participacio $this. La persona participa de l'activitat
     *
     * @param \Foment\GestioBundle\Entity\Persona $persona
     * @return \Foment\GestioBundle\Entity\Participant
     */
    public function addParticipacioActivitat(\Foment\GestioBundle\Entity\Persona $persona)
    {
    	$participacio = new Participant();
    	$participacio->setPersona($persona);
    	$participacio->setActivitat($this);
    
    	$persona->addParticipacio($participacio);
    	$this->addParticipant($participacio);
    
    	return $participacio;
    }
    
    /**
     * Get curs. Per sobreescriure 
     *
     * @return ''
     */
    public function getCurs()
    {
    	return '';
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
     * Set descripcio
     *
     * @param string $descripcio
     * @return Activitat
     */
    public function setDescripcio($descripcio)
    {
        $this->descripcio = $descripcio;

        return $this;
    }

    /**
     * Get descripcio
     *
     * @return string 
     */
    public function getDescripcio()
    {
        return $this->descripcio;
    }

    /**
     * Set estimadespeses
     *
     * @param string $estimadespeses
     * @return Activitat
     */
    public function setEstimadespeses($estimadespeses)
    {
        $this->estimadespeses = $estimadespeses;

        return $this;
    }

    /**
     * Get estimadespeses
     *
     * @return string 
     */
    public function getEstimadespeses()
    {
        return $this->estimadespeses;
    }

    /**
     * Get facturacions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFacturacions()
    {
    	return $this->facturacions;
    }
    
    /**
     * Get facturacions actives
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFacturacionsActives()
    {
    	$actives = array();
    	
    	foreach ($this->facturacions as $facturacio) {
    		if (!$facturacio->esBaixa()) $actives[] = $facturacio;
    	}
    	
    	return $actives;
    }

    /**
     * Add Facturacio
     *
     * @param \Foment\GestioBundle\Entity\Facturacio $facturacio
     * @return Activitat
     */
    public function addFacturacions(\Foment\GestioBundle\Entity\Facturacio $facturacio)
    {
    	$this->facturacions->add($facturacio);
    
    	return $this;
    }

    /**
     * Set finalitzat
     *
     * @param boolean $finalitzat
     * @return Soci
     */
    public function setFinalitzat($finalitzat)
    {
    	$this->finalitzat = $finalitzat;
    
    	return $this;
    }
    
    /**
     * Get finalitzat
     *
     * @return boolean
     */
    public function getFinalitzat()
    {
    	return $this->finalitzat;
    }
    
    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Activitat
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
     * @return Activitat
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
     * Set databaixa
     *
     * @param \DateTime $databaixa
     * @return Activitat
     */
    public function setDatabaixa($databaixa)
    {
    	$this->databaixa = $databaixa;
    
    	return $this;
    }
    
    /**
     * Get databaixa
     *
     * @return \DateTime
     */
    public function getDatabaixa()
    {
    	return $this->databaixa;
    }
    
    /**
     * Add participants
     *
     * @param \Foment\GestioBundle\Entity\Participant $participants
     * @return Activitat
     */
    public function addParticipant(\Foment\GestioBundle\Entity\Participant $participants)
    {
    	$this->participants->add($participants);
    	//$this->participants[] = $participants;

        return $this;
    }
    
    /**
     * Remove participants
     *
     * @param \Foment\GestioBundle\Entity\Participant $participants
     */
    public function removeParticipant(\Foment\GestioBundle\Entity\Participant $participants)
    {
        $this->participants->removeElement($participants);
    }

    /**
     * Get participants
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    /**
     * Set totalhores
     *
     * @param integer $totalhores
     * @return Activitat
     */
    public function setTotalhores($totalhores)
    {
        $this->totalhores = $totalhores;

        return $this;
    }

    /**
     * Get totalhores
     *
     * @return integer 
     */
    public function getTotalhores()
    {
        return $this->totalhores;
    }

    /**
     * Set lloc
     *
     * @param string $lloc
     * @return Activitat
     */
    public function setLloc($lloc)
    {
        $this->lloc = $lloc;

        return $this;
    }

    /**
     * Get lloc
     *
     * @return string 
     */
    public function getLloc()
    {
        return $this->lloc;
    }

    /**
     * Set latitud
     *
     * @param string $latitud
     * @return Activitat
     */
    public function setLatitud($latitud)
    {
        $this->latitud = $latitud;

        return $this;
    }

    /**
     * Get latitud
     *
     * @return string 
     */
    public function getLatitud()
    {
        return $this->latitud;
    }

    /**
     * Set longitud
     *
     * @param string $longitud
     * @return Activitat
     */
    public function setLongitud($longitud)
    {
        $this->longitud = $longitud;

        return $this;
    }

    /**
     * Get longitud
     *
     * @return string 
     */
    public function getLongitud()
    {
        return $this->longitud;
    }

    /**
     * Set observacions
     *
     * @param string $observacions
     * @return Activitat
     */
    public function setObservacions($observacions)
    {
        $this->observacions = $observacions;

        return $this;
    }

    /**
     * Get observacions
     *
     * @return string 
     */
    public function getObservacions()
    {
        return $this->observacions;
    }

    /**
     * Set maxparticipants
     *
     * @param integer $maxparticipants
     * @return Activitat
     */
    public function setMaxparticipants($maxparticipants)
    {
        $this->maxparticipants = $maxparticipants;

        return $this;
    }

    /**
     * Get maxparticipants
     *
     * @return integer 
     */
    public function getMaxparticipants()
    {
        return $this->maxparticipants;
    }
}
