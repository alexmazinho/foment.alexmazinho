<?php 
// src/Foment/GestioBundle/Entity/Apunt.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity
 * @ORM\Table(name="apunts")
 */
class Apunt
{
	
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $num;		// Reset anual MAX $num de l'any en curs
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $dataapunt;		
    
    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     */
    protected $tipus; // 'E' entrada o 'S' sortida
    
    /**
     * @ORM\Column(type="decimal", precision=6, scale=2, nullable=false)
     */
    protected $import;
    
    /**
     * @ORM\Column(type="string", length=3, nullable=false)
     *
     */
    protected $codi; // codi comptable
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     */
    protected $concepte; // p.e. "Pagament finestreta rebut XXX"
    
    /**
     * @ORM\OneToOne(targetEntity="Rebut")
     * @ORM\JoinColumn(name="rebut", referencedColumnName="id")
     */
    protected $rebut; // FK taula rebuts
    
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
    public function __construct($numapunt, $tipus = UtilsController::TIPUS_APUNT_ENTRADA, 
    							$dataapunt = null, $import = 0, $codi = UtilsController::CODI_COMPTABLE_INGRES_FINESTRETA, 
    							$concepte = '', $rebut = null)
    {
    	$this->id = 0;
    	$this->num = $numapunt;
    	if ($dataapunt == null) $this->dataapunt = new \DateTime('now');
    	else $this->dataapunt = $dataapunt;
    	$this->tipus = $tipus;
    	$this->import = $import;
    	$this->codi = $codi;
    	$this->concepte = $concepte;
    	$this->rebut = $rebut;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	
    }
    
    /**
     * Get num format
     *
     * @return String
     */
    public function getNumFormat()
    {
    	return str_pad($this->num, 6, '0', STR_PAD_LEFT) .'/'.$this->dataapunt->format('y');
    }
    
    /**
     * Està anul·lat l'apunt?
     *
     * @return boolean
     */
    public function anulat()
    {
    	return $this->databaixa != null;
    }
    
    /**
     * És sortida?
     *
     * @return boolean
     */
    public function esSortida()
    {
    	return $this->tipus == UtilsController::TIPUS_APUNT_SORTIDA;
    }
    
    /**
     * És entrada?
     *
     * @return boolean
     */
    public function esEntrada()
    {
    	return $this->tipus == UtilsController::TIPUS_APUNT_ENTRADA;
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
     * Set num
     *
     * @param integer $num
     * @return Apunt
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer 
     */
    public function getNum()
    {
        return $this->num;
    }

    
    /**
     * Set dataapunt
     *
     * @param \DateTime $dataapunt
     * @return Apunt
     */
    public function setDataapunt($dataapunt)
    {
    	$this->dataapunt = $dataapunt;
    
    	return $this;
    }
    
    /**
     * Get dataapunt
     *
     * @return \DateTime
     */
    public function getDataapunt()
    {
    	return $this->dataapunt;
    }
    
	/**
     * Set tipus
     *
     * @param string $tipus
     * @return Apunt
     */
    public function setTipus($tipus)
    {
    	$this->tipus = $tipus;
    
    	return $this;
    }
    
    /**
     * Get tipus
     *
     * @return string
     */
    public function getTipus()
    {
    	return $this->tipus;
    }
    
    /**
     * Set import
     *
     * @param decimal $import
     * @return Apunt
     */
    public function setImport($import)
    {
    	$this->import = $import;
    
    	return $this;
    }
    
    /**
     * Get import
     *
     * @return decimal
     */
    public function getImport()
    {
    	return $this->import;
    }
    
    /**
     * Set codi
     *
     * @param string $codi
     * @return Apunt
     */
    public function setCodi($codi)
    {
    	$this->codi = $codi;
    
    	return $this;
    }
    
    /**
     * Get codi
     *
     * @return string
     */
    public function getCodi()
    {
    	return $this->codi;
    }
    
    
    /**
     * Set concepte
     *
     * @param string $concepte
     * @return Apunt
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
    	return $this->concepte;
    }
    
    
    /**
     * Set rebut
     *
     * @param \Foment\GestioBundle\Entity\Rebut $rebut
     * @return Apunt
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
    
    
    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Apunt
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
     * @return Apunt
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
     * @return Apunt
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
}
