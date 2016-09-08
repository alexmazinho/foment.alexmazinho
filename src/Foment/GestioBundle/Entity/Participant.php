<?php 
// src/Foment/GestioBundle/Entity/Participant.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
 * @ORM\Table(name="participants")
 */

class Participant
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Persona", inversedBy="participacions")
     * @ORM\JoinColumn(name="persona", referencedColumnName="id")
     */
    protected $persona; // FK taula persones
    
    /**
     * @ORM\ManyToOne(targetEntity="Activitat", inversedBy="participants")
     * @ORM\JoinColumn(name="activitat", referencedColumnName="id")
     */
    protected $activitat; // FK taula activitats

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $datacancelacio;
    
    /**
     * @ORM\OneToMany(targetEntity="RebutDetall", mappedBy="activitat")
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
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->detallsrebuts = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Get info as string
     *
     * @return string
     */
    public function getInfo()
    {
    	return $this->getActivitat()->getInfo();
    }
    
    /**
     * Get info plus import as string
     *
     * @return string
     */
    public function getInfoPreu()
    {
    	return $this->getActivitat()->getInfoPreu();
    }
    
    /**
     * Get rebuts detalls vigents
     *
     * @return array
     */
    public function getRebutsDetallsVigents()
    {
    	$detallsVigents = array();
    	foreach ($this->detallsrebuts as $detall) {
    		if ($detall->getDatabaixa() == null) {
    			$detallsVigents[] =  $detall;
    		}
    	}
    	return $detallsVigents;
    }
    
    /**
     * Get rebuts vigents. Per defecte no inclou anul·lacions 
     *
     * @return array
     */
    public function getRebutsParticipant($baixa = false)
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
    
    public function getRebutParticipantFacturacio($facturacio, $baixa = false)
    {
    	foreach ($this->detallsrebuts as $detall) {
    		$rebut = $detall->getRebut();
    		if ($rebut != null && $rebut->getFacturacio()->getId() == $facturacio->getId()) {
    			if ($baixa == true || ($detall->getDatabaixa() == null && $rebut->getDatabaixa() == null)) return $rebut;
    		}
    	}
    	 
    	return null;
    }
    
    /**
     * Get import facturacio
     *
     * @return array
     */
    public function getInfoFacturacio($facturacio)
    {
    	$info = array('import' => '--', 'estat' => 'NA');
    	
    	foreach ($this->detallsrebuts as $detall) {
   			$rebut = $detall->getRebut();
   			if ($rebut != null  && $rebut->getFacturacio() == $facturacio) {
   				$info['import'] = number_format($detall->getImport(), 2);
   				$info['estat'] = $detall->getEstat();
   				return $info;
   			}
    	}
    	 
    	return $info;
    }
    
    /**
     * Get rebuts vigents info
     *
     * @return double
     */
    public function getRebutsImport()
    {
    	$import = 0;
    	 
    	foreach ($this->detallsrebuts as $detall) {
    		$rebut = $detall->getRebut();
    		if ($detall->getDatabaixa() == null && $rebut != null && $rebut->getDatabaixa() == null) $import += $detall->getImport();
    	}
    	 
    	return $import;
    }
    
    /**
     * Get rebuts vigents info
     *
     * @return array
     */
    public function getRebutsInfo()
    {
    	$info = array('import' => 0, 'estat' => 'rebut pendent');
    	
    	foreach ($this->detallsrebuts as $detall) {
    		if ($detall->getDatabaixa() == null) {
    			$rebut = $detall->getRebut();
    			if ($rebut != null) {
    				$info['import'] += $detall->getImport();
    				$info['estat'] = $rebut->getNumFormat().' '.UtilsController::getEstats($rebut->getEstat() .' ('.number_format($detall->getImport(), 2).')' );
    			}
    		}
    	}
    	
    	if ($info['estat'] == '') $info['estat'] = 'rebut pendent';
    	
    	return $info;
    }
    
    /**
     * Get nom (usat pel sort)
     *
     * @return string
     */
    public function getNom()
    {
    	return $this->getPersona()->getNom();
    }
    
    /**
     * Get cognoms (usat pel sort)
     *
     * @return string
     */
    public function getCognoms()
    {
    	return $this->getPersona()->getCognoms();
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
     * @return Participant
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
     * @return Participant
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
     * Set persona
     *
     * @param \Foment\GestioBundle\Entity\Persona $persona
     * @return Participant
     */
    public function setPersona(\Foment\GestioBundle\Entity\Persona $persona = null)
    {
        $this->persona = $persona;

        return $this;
    }

    /**
     * Get persona
     *
     * @return \Foment\GestioBundle\Entity\Persona 
     */
    public function getPersona()
    {
        return $this->persona;
    }

    /**
     * Set activitat
     *
     * @param \Foment\GestioBundle\Entity\Activitat $activitat
     * @return Participant
     */
    public function setActivitat(\Foment\GestioBundle\Entity\Activitat $activitat = null)
    {
        $this->activitat = $activitat;

        return $this;
    }

    /**
     * Get activitat
     *
     * @return \Foment\GestioBundle\Entity\Activitat 
     */
    public function getActivitat()
    {
        return $this->activitat;
    }

    /**
     * Set datacancelacio
     *
     * @param \DateTime $datacancelacio
     * @return Participant
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
     * Add $detallrebut
     *
     * @param \Foment\GestioBundle\Entity\RebutDetall $detallrebut
     * @return Participant
     */
    public function addRebutDetall(\Foment\GestioBundle\Entity\RebutDetall $detallrebut)
    {
        $this->detallsrebuts[] = $detallrebut;

        return $this;
    }

    /**
     * Remove $detallrebut
     *
     * @param \Foment\GestioBundle\Entity\RebutDetall $detallrebut
     */
    public function removeRebutDetall(\Foment\GestioBundle\Entity\RebutDetall $detallrebut)
    {
        $this->detallsrebuts->removeElement($detallrebut);
    }

    /**
     * Get detallsrebuts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDetallsrebuts()
    {
        return $this->detallsrebuts;
    }
}
