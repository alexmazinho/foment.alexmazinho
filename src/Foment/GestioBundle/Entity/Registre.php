<?php 
// src/Foment/GestioBundle/Entity/Registre.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity 
 * @ORM\Table(name="registre")
 */

class Registre
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    protected $usuari;	
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datahora;
    
    /**
     * @ORM\Column(type="text", nullable=false)
     */
    protected $peticio;
    
    /**
     * @ORM\Column(type="string", length=70, nullable=false)
     */
    protected $accio;
    
    /**
     * @ORM\Column(type="text", nullable=false)
     */
    protected $informacio;

    public function __construct($usuari, $peticio = '', $accio = '', $informacio = '') {
        
        $this->usuari = substr($usuari,0,50);
        $this->accio = substr($accio,0,70);
        $this->datahora = new \DateTime('now');
        $this->peticio = $peticio;
        $this->informacio = $informacio;
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
     * Set usuari
     *
     * @param string $usuari
     * @return Registre
     */
    public function setUsuari($usuari)
    {
        $this->usuari = $usuari;
        
        return $this;
    }
    
    /**
     * Get usuari
     *
     * @return string
     */
    public function getUsuari()
    {
        return $this->usuari;
    }
    
    
    /**
     * Set datahora
     *
     * @param \DateTime $datahora
     * @return Registre
     */
    public function setDatahora($datahora)
    {
        $this->datahora = $datahora;
        
        return $this;
    }
    
    /**
     * Get datahora
     *
     * @return \DateTime
     */
    public function getDatahora()
    {
        return $this->datahora;
    }
    
    /**
     * Set peticio
     *
     * @param string $peticio
     * @return Registre
     */
    public function setPeticio($peticio)
    {
        $this->peticio = $peticio;
        
        return $this;
    }
    
    /**
     * Get peticio
     *
     * @return string
     */
    public function getPeticio()
    {
        return $this->peticio;
    }
    
    /**
     * Set accio
     *
     * @param string $accio
     * @return Registre
     */
    public function setAccio($accio)
    {
        $this->accio = $accio;
        
        return $this;
    }
    
    /**
     * Get accio
     *
     * @return string
     */
    public function getAccio()
    {
        return $this->accio;
    }
    
    /**
     * Set informacio
     *
     * @param string $informacio
     * @return Registre
     */
    public function setInformacio($informacio)
    {
        $this->informacio = $informacio;

        return $this;
    }

    /**
     * Get informacio
     *
     * @return string 
     */
    public function getInformacio()
    {
        return $this->informacio;
    }
}
