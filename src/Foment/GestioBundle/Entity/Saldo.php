<?php 
// src/Foment/GestioBundle/Entity/Saldo.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="saldos")
 */
class Saldo
{
	
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datasaldo;		
    
    /**
     * @ORM\Column(type="decimal", precision=6, scale=2, nullable=false)
     */
    protected $import;
    
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
    public function __construct($datasaldo = null, $import = 0)
    {
    	$this->id = 0;
    	if ($datasaldo == null) $this->datasaldo = new \DateTime('now');
    	else $this->datasaldo = $datasaldo;
    	$this->import = $import;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    }
    
    /**
     * Està anul·lat el saldo?
     *
     * @return boolean
     */
    public function anulat()
    {
    	return $this->databaixa != null;
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
     * Set datasaldo
     *
     * @param \DateTime $datasaldo
     * @return Saldo
     */
    public function setDatasaldo($datasaldo)
    {
    	$this->datasaldo = $datasaldo;
    
    	return $this;
    }
    
    /**
     * Get datasaldo
     *
     * @return \DateTime
     */
    public function getDatasaldo()
    {
    	return $this->datasaldo;
    }
    
    /**
     * Set import
     *
     * @param decimal $import
     * @return Saldo
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
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Saldo
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
     * @return Saldo
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
     * @return Saldo
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
