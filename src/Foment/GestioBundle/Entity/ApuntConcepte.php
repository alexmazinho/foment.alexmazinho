<?php 
// src/Foment/GestioBundle/Entity/ApuntConcepte.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity
 * @ORM\Table(name="apuntconceptes")
 */
class ApuntConcepte
{
	
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
   	/**
     * @ORM\Column(type="string", length=10, nullable=false)
     */
    protected $tipus; // veure UtilsController::getTipusConceptesApunts()
    
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     *
     */
    protected $concepte; 
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $databaixa;
   
    /**
     * Constructor
     */
    public function __construct($tipus = '', $concepte = '')
    {
    	$this->id = 0;
    	$this->tipus = $tipus;
    	$this->concepte = $concepte;
    }
    
    /**
     * Get tipu i concepte
     *
     * @return string
     */
    public function getConcepteLlarg()
    {
    	return $this->tipus.' - '.$this->concepte;
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
     * Set tipus
     *
     * @param string $tipus
     * @return ApuntConcepte
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
     * Set concepte
     *
     * @param string $concepte
     * @return ApuntConcepte
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
     * Set databaixa
     *
     * @param \DateTime $databaixa
     * @return ApuntConcepte
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
