<?php 
// src/Foment/GestioBundle/Entity/Esdeveniment.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity 
 * @ORM\Table(name="esdeveniments")
 */


/* Un esdeveniment és quelcom que succeeix en una data/hora concreta
 */
class Esdeveniment
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
    protected $tipus;
    
    /**
     * @ORM\Column(type="string", length=70, nullable=false)
     */
    protected $descripcio;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datahora;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $durada;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $dataentrada;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datamodificacio;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $databaixa;

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
     * @param integer $tipus
     * @return Esdeveniment
     */
    public function setTipus($tipus)
    {
        $this->tipus = $tipus;

        return $this;
    }

    /**
     * Get tipus
     *
     * @return integer 
     */
    public function getTipus()
    {
        return $this->tipus;
    }

    /**
     * Set descripcio
     *
     * @param string $descripcio
     * @return Esdeveniment
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
     * Set datahora
     *
     * @param \DateTime $datahora
     * @return Esdeveniment
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
     * Set durada
     *
     * @param integer $durada
     * @return Esdeveniment
     */
    public function setDurada($durada)
    {
        $this->durada = $durada;

        return $this;
    }

    /**
     * Get durada
     *
     * @return integer 
     */
    public function getDurada()
    {
        return $this->durada;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Esdeveniment
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
     * @return Esdeveniment
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
     * @return Esdeveniment
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
