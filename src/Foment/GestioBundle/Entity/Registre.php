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
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datahora;
    
    /**
     * @ORM\Column(type="string", length=70, nullable=false)
     */
    protected $accio;
    
    /**
     * @ORM\Column(type="text", nullable=false)
     */
    protected $informació;

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
     * Set informació
     *
     * @param string $informació
     * @return Registre
     */
    public function setInformació($informació)
    {
        $this->informació = $informació;

        return $this;
    }

    /**
     * Get informació
     *
     * @return string 
     */
    public function getInformació()
    {
        return $this->informació;
    }
}
