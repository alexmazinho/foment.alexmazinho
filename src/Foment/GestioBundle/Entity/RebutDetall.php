<?php 
// src/Foment/GestioBundle/Entity/RebutDetall.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity
 * @ORM\Table(name="rebutsdetall")
 */
class RebutDetall
{
	
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $numdetall;  // p.e. 1 de 5 
    
    /**
     * @ORM\Column(type="decimal", precision=6, scale=2)
     */
    protected $import;
    
    /**
     * @ORM\ManyToOne(targetEntity="Membre", inversedBy="detallsrebuts")
     * @ORM\JoinColumn(name="quotaseccio", referencedColumnName="id")
     */
    protected $quotaseccio; // FK taula membres
    
    /**
     * @ORM\ManyToOne(targetEntity="Participant", inversedBy="detallsrebuts")
     * @ORM\JoinColumn(name="activitat", referencedColumnName="id")
     */
    protected $activitat; // FK taula participants
    
    /**
     * @ORM\Column(type="string", length=80, nullable=false)
     *
     */
    protected $concepte; // p.e. "Foment familiar anual"
    
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
     * @ORM\ManyToOne(targetEntity="Rebut", inversedBy="detalls")
     * @ORM\JoinColumn(name="rebut", referencedColumnName="id")
     */
    protected $rebut; // FK taula rebuts
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	$this->id = 0;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	
    	// Hack per permetre múltiples constructors
    	$a = func_get_args();
    	$i = func_num_args();
    	 
    	if ($i >= 1) {
    		if ($a[0] instanceof Membre and method_exists($this,$f='__constructSeccio')) {
    			call_user_func_array(array($this,$f),$a);
    		}
    		if ($a[0] instanceof Participant and method_exists($this,$f='__constructActivitat')) {
    			call_user_func_array(array($this,$f),$a);
    		}
    	}
    }
    

    /**
     * Constructor rebut quota Secció
     *
     * @param \Foment\GestioBundle\Entity\Membre $membre
     * @param \Foment\GestioBundle\Entity\Rebut $rebut
     * ...
     */
    public function __constructSeccio($membre, $rebut, $import)
    {
    	$this->rebut = $rebut;
    	$this->quotaseccio = $membre; //  seccio (Membre)
    	
    	$this->concepte = $membre->getSeccio()->getNom().' '.UtilsController::concepteMembreSeccioRebut($membre, $rebut->getDataemissio()->format('Y'));
    	
    	$membre->addRebutDetall($this); // Afegir detall a membre
    	
    	$this->import = $import;	
    	$this->activitat = null;	// activitat (Participacio)
    	$this->numdetall = $rebut->getNextNumDetall();
    }
    
    
    /**
     * Constructor rebut participacio activitat
     *
     * @param \Foment\GestioBundle\Entity\Participant $participacio
     * ....
     */
    public function __constructActivitat($participacio, $rebut, $import)
    {
    	$this->rebut = $rebut;
    	$this->activitat = $participacio;	// activitat (Participacio)
    	//$this->concepte = UtilsController::concepteParticipantRebut($participacio);
    	$this->concepte = $rebut->getConcepte();
    	
    	$participacio->addRebutdetall($this); // Afegir detall a participacio 
    	
    	$this->quotaseccio = null; //  seccio (Membre)
    	$this->import = $import;
    	$this->numdetall = $rebut->getNextNumDetall();

    }
    
    /**
     * dadesRegistre
     */
    public function dadesRegistre()
    {
        $dades = array(   'id' => $this->id,
            'num' => $this->getNumdetall(),
            'rebut' => $this->getRebut()== null?'':$this->getRebut()->getId(),
            'import' => number_format($this->getImport(), 2, ',', '.').' €',
            'estat' => $this->getEstat(),
            'concepte' => $this->getConcepte(),
            'entrada' => $this->getDataentrada()->format('Y-m-d  H:i:s'),
            'baixa' => $this->getDatabaixa()==null?'':$this->getDatabaixa()->format('Y-m-d  H:i:s'),
        );
        
        if ($this->getActivitat() != null && $this->getActivitat()->getActivitat() != null) {
            $dades['activitat'] = $this->getActivitat()->getActivitat()->getDescripcio();
        }
        
        if ($this->getSeccio() != null) {
            $dades['seccions'] = $this->getSeccio()->getNom();
        }
        
        return $dades;
    }
    
    /**
     * Get csvRow, qualsevol Entitat que s'exporti a CSV ha d'implementar aquest mètode
     * Delimiter ;
     * Quotation ""
     *
     * @return string
     */
    public function getCsvRow()
    {
    	/*array( '"id detall"', '"num detall"', '"beneficiari"', '"concepte detall"', '"import detall"', '"seccio"', '"activitat"', '"databaixa detall"' );*/
    	
    	// Detall adaptat als camps CSV de Rebut
    	$fields = array();
    	$fields[] = $this->getRebut()->getId()."-".$this->id;
    	$fields[] = $this->numdetall;
    	$fields[] = $this->getPersona()->getNomCognoms();
    	$fields[] = $this->getConcepte();
    	$fields[] = number_format($this->getImport(), 2, ',', '.');
    	
    	$fields[] = ($this->getSeccio() != null?$this->getSeccio()->getNom():'');
    	$fields[] = ($this->getActivitat() != null && $this->getActivitat()->getActivitat() != null?$this->getActivitat()->getActivitat()->getDescripcio():'');
    	
    	if ($this->databaixa != null) $fields[] = $this->databaixa->format('Y-m-d');
    	else $fields[] = '';
    	
    	$row = '"'.implode('";"', $fields).'"';

    	return $row;
    }
    
    /**
     * És baixa? false
     *
     * @return boolean
     */
    public function esBaixa() { return $this->databaixa != null; }
    
    /**
     * Get persona de la quota/import del detall del rebut
     *
     * @return string
     */
    public function getPersona()
    {
    	if ($this->quotaseccio != null) return $this->quotaseccio->getSoci();
    	if ($this->activitat != null) return $this->activitat->getPersona();
    	return '';
    }
    
    /**
     * Get seccio del detall del rebut
     *
     * @return Seccio
     */
    public function getSeccio()
    {
    	if ($this->quotaseccio != null) return $this->quotaseccio->getSeccio();
    	
    	return null;
    }
    
    /**
     * Es pot anular el rebut? 
     *
     * @return boolean
     *
     */
    public function esEsborrable() {
    	return $this->getRebut()->esModificable() && $this->databaixa == null;
    }
    
    /**
     * Baixa del detall
     *
     */
    public function baixa() {
    	$this->setDatamodificacio(new \DateTime());
    	$this->setDatabaixa(new \DateTime());
    	
    	if ($this->getRebut()->getNumDetallsActius() == 0) {
    		$this->getRebut()->setDatamodificacio(new \DateTime());
    		$this->getRebut()->setDatabaixa(new \DateTime());
    	}
    }
    
    /**
     * Get import del detall del rebuts si no està pagat, 0 en cas que estigui pagat
     *
     * @return double
     */
    public function getImportPendent()
    {
    	if ( $this->getDatabaixa() == null && 
    			$this->getRebut() != null 
    			&& !$this->getRebut()->cobrat() ) return $this->getImport();
    	
    	return 0;
    }
    
    /**
     * Get estat del detall del rebuts 
     *
     * @return string
     */
    public function getEstat()
    {
    	if ( $this->getDatabaixa() != null) 'Anul·lat';
    	
    	if ( $this->getRebut() != null) return UtilsController::getEstatsResum($this->getRebut()->getEstat()); 
    	 
    	return "";
    }
    
    /**
     * Get detall info
     *
     * @return string
     */
    public function getDetallInfo()
    {
    	$rebut = $this->getRebut();
    	$info = $rebut->getNumFormat().' <b>'.number_format($this->getImport(), 2, ',', '.').'€</b> ';
    	$info .= '('.UtilsController::getEstatsResum($rebut->getEstat()).')';
    
    	return $info;
    }
    
    /**
     * Get detall breu info
     *
     * @return string
     */
    public function getDetallBreuInfo()
    {
    	$rebut = $this->getRebut();
    	$info = $this->concepte.' <b>'.number_format($this->getImport(), 2, ',', '.').'€</b> ';
    	$info .= '('.UtilsController::getEstatsResum($rebut->getEstat()).')';
    
    	return $info;
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
     * Set import
     *
     * @param string $import
     * @return RebutDetall
     */
    public function setImport($import)
    {
        $this->import = $import;

        return $this;
    }

    /**
     * Get import
     *
     * @return string 
     */
    public function getImport()
    {
        return $this->import;
    }
    
    /**
     * Set numdetall
     *
     * @param integer $numdetall
     * @return RebutDetall
     */
    public function setNumdetall($numdetall)
    {
    	$this->numdetall = $numdetall;
    
    	return $this;
    }
    
    /**
     * Get numdetall
     *
     * @return integer
     */
    public function getNumdetall()
    {
    	return $this->numdetall;
    }
    
    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return RebutDetall
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
     * @return RebutDetall
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
     * @return RebutDetall
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
     * Set quotaseccio
     *
     * @param \Foment\GestioBundle\Entity\Membre $quotaseccio
     * @return RebutDetall
     */
    public function setQuotaseccio(\Foment\GestioBundle\Entity\Membre $quotaseccio = null)
    {
        $this->quotaseccio = $quotaseccio;

        return $this;
    }

    /**
     * Get quotaseccio
     *
     * @return \Foment\GestioBundle\Entity\Membre 
     */
    public function getQuotaseccio()
    {
        return $this->quotaseccio;
    }

    /**
     * Set activitat
     *
     * @param \Foment\GestioBundle\Entity\Participant $activitat
     * @return RebutDetall
     */
    public function setActivitat(\Foment\GestioBundle\Entity\Participant $activitat = null)
    {
        $this->activitat = $activitat;

        return $this;
    }

    /**
     * Get activitat
     *
     * @return \Foment\GestioBundle\Entity\Participant 
     */
    public function getActivitat()
    {
        return $this->activitat;
    }
    
    /**
     * Set concepte
     *
     * @param string $concepte
     * @return RebutDetall
     */
    public function setConcepte($concepte)
    {
    	$this->concepte = $concepte;
    
    	return $this;
    }
    
    /**
     * Get concepte
     *
     * @return string
     */
    public function getConcepte()
    {
    	if ($this->activitat != null) return $this->rebut->getConcepte(); // Cursos
    	return $this->concepte;
    }
    
    
    /**
     * Set rebut
     *
     * @param \Foment\GestioBundle\Entity\Rebut $rebut
     * @return RebutDetall
     */
    public function setRebut(\Foment\GestioBundle\Entity\Rebut $rebut = null)
    {
    	$this->rebut = $rebut;
    
    	return $this;
    }
    
    /**
     * Get rebut
     *
     * @return \Foment\GestioBundle\Entity\Rebut
     */
    public function getRebut()
    {
    	return $this->rebut;
    }
}
