<?php 
// src/Foment/GestioBundle/Entity/Docencia.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity 
 * @ORM\Table(name="docencies")
 */

class Docencia
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="ActivitatAnual", inversedBy="docents")
     * @ORM\JoinColumn(name="activitat", referencedColumnName="id")
     */
    protected $activitat; // FK taula activitatsanuals
    
    /**
     * @ORM\ManyToOne(targetEntity="Persona", inversedBy="docencies")
     * @ORM\JoinColumn(name="persona", referencedColumnName="id")
     */
    protected $persona; // FK taula persones

    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $totalhores;
    
    /**
     * @ORM\Column(type="decimal", precision=6, scale=2, nullable=true)
     */
    protected $preuhora;
    
    /**
     * @ORM\Column(type="decimal", precision=6, scale=2, nullable=true)
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
    public function __construct($activitat, $persona, $totalhores, $preuhora, $import)
    {
    	$this->id = 0;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->databaixa = null;
    	
    	$this->activitat = $activitat;
    	if ($this->activitat != null) $this->activitat->addDocent($this);
    	$this->persona = $persona;
    	if ($this->persona != null) $this->persona->addDocencia($this);
    	$this->totalhores = $totalhores;
    	$this->preuhora = $preuhora;
    	$this->import = $import;
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
     * Set totalhores
     *
     * @param integer $totalhores
     * @return Docencia
     */
    public function setTotalhores($totalhores)
    {
        $this->totalhores = $totalhores;

        return $this;
    }

    /**
     * Get totalhores
     *
     * @return integer 
     */
    public function getTotalhores()
    {
        return $this->totalhores;
    }

    /**
     * Set preuhora
     *
     * @param string $preuhora
     * @return Docencia
     */
    public function setPreuhora($preuhora)
    {
        $this->preuhora = $preuhora;

        return $this;
    }

    /**
     * Get preuhora
     *
     * @return string 
     */
    public function getPreuhora()
    {
        return $this->preuhora;
    }

    /**
     * Set import
     *
     * @param string $import
     * @return Docencia
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
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Docencia
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
     * @return Docencia
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
     * @return Docencia
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
     * Set activitat
     *
     * @param \Foment\GestioBundle\Entity\ActivitatAnual $activitat
     * @return Docencia
     */
    public function setActivitat(\Foment\GestioBundle\Entity\ActivitatAnual $activitat = null)
    {
        $this->activitat = $activitat;

        return $this;
    }

    /**
     * Get activitat
     *
     * @return \Foment\GestioBundle\Entity\ActivitatAnual 
     */
    public function getActivitat()
    {
        return $this->activitat;
    }

    /**
     * Set persona
     *
     * @param \Foment\GestioBundle\Entity\Persona $persona
     * @return Docencia
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
}
