<?php 
// src/Foment/GestioBundle/Entity/Saldo.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

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
     * @ORM\Column(type="decimal", precision=6, scale=2, nullable=true)
     */
    protected $importconsolidat;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dataconsolidat;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datasaldo;		
    
    /**
     * @ORM\Column(type="text", nullable=false)
     */
    protected $desglossament;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $databaixa;
    
    
    /**
     * Constructor
     */
    public function __construct($datasaldo = null, $desglossament = '')
    {
    	$this->id = 0;
    	if ($datasaldo == null) $this->datasaldo = new \DateTime('now');
    	else $this->datasaldo = $datasaldo;
    	
    	if ($desglossament == '') $this->desglossament = UtilsController::JSON_DESGLOSSAMENT;
    	else $this->desglossament = $desglossament; 
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
     * Està consolidat el saldo?
     *
     * @return boolean
     */
    public function consolidat()
    {
    	return $this->dataconsolidat != null;
    }
    
    /**
     * Get import
     *
     * @return double
     */
    public function getImport()
    {
    	return UtilsController::calcularDesglossament($this->desglossament);
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
     * Set importconsolidat
     *
     * @param double $importconsolidat
     * @return Saldo
     */
    public function setImportconsolidat($importconsolidat)
    {
    	$this->importconsolidat = $importconsolidat;
    
    	return $this;
    }
    
    /**
     * Get importconsolidat
     *
     * @return double
     */
    public function getImportconsolidat()
    {
    	return $this->importconsolidat;
    }
    
    /**
     * Set dataconsolidat
     *
     * @param \DateTime $dataconsolidat
     * @return Saldo
     */
    public function setDataconsolidat($dataconsolidat)
    {
    	$this->dataconsolidat = $dataconsolidat;
    
    	return $this;
    }
    
    /**
     * Get dataconsolidat
     *
     * @return \DateTime
     */
    public function getDataconsolidat()
    {
    	return $this->dataconsolidat;
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
     * Set desglossament
     *
     * @param string $desglossament
     * @return Saldo
     */
    public function setDesglossament($desglossament)
    {
    	$this->desglossament = $desglossament;
    
    	return $this;
    }
    
    /**
     * Get desglossament
     *
     * @return string
     */
    public function getDesglossament()
    {
    	return $this->desglossament;
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
