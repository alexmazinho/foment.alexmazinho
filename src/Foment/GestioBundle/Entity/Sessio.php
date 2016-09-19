<?php 
// src/Foment/GestioBundle/Entity/Sessio.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity 
 * @ORM\Table(name="sessions")
 */

/* Una sessió del calendari d'una (facturació d'una) activitat anual 
 * Cada sessió està associada a un esdeveniment que conté la temporització */
class Sessio
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Docencia", inversedBy="calendari")
     * @ORM\JoinColumn(name="docencia", referencedColumnName="id")
     */
    protected $docencia; // FK taula docencies
    
    /**
     * @ORM\OneToOne(targetEntity="Esdeveniment", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="horari", referencedColumnName="id")
     */
    protected $horari; // FK taula esdeveniments

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
    public function __construct($docencia, $datahora, $durada, $tipus, $descripcio)
    {
    	
    	$this->id = 0;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->databaixa = null;
    	
    	$this->docencia = $docencia;
    	
    	$this->horari = new Esdeveniment($datahora, $durada, $tipus, $descripcio);
    }
    
    /**
     * És baixa? false
     *
     * @return boolean
     */
    public function esBaixa() { return $this->databaixa != null; }
    
    /**
     * baixa de la sessió i del esdeveniment corresponent
     *
     */
    public function baixa()
    {
    	$this->databaixa = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	
    	$this->horari->baixa();
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
     * Set docencia
     *
     * @param \Foment\GestioBundle\Entity\Docencia $docencia
     * @return Sessio
     */
    public function setDocencia(\Foment\GestioBundle\Entity\Docencia $docencia = null)
    {
        $this->docencia = $docencia;

        return $this;
    }

    /**
     * Get docencia
     *
     * @return \Foment\GestioBundle\Entity\Docencia 
     */
    public function getDocencia()
    {
        return $this->docencia;
    }

    /**
     * Set horari
     *
     * @param \Foment\GestioBundle\Entity\Esdeveniment $horari
     * @return Sessio
     */
    public function setHorari(\Foment\GestioBundle\Entity\Esdeveniment $horari = null)
    {
        $this->horari = $horari;

        return $this;
    }

    /**
     * Get horari
     *
     * @return \Foment\GestioBundle\Entity\Esdeveniment 
     */
    public function getHorari()
    {
        return $this->horari;
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
