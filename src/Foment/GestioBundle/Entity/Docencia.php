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
     * @ORM\ManyToOne(targetEntity="FacturacioActivitat", inversedBy="docents")
     * @ORM\JoinColumn(name="facturacio", referencedColumnName="id")
     */
    protected $facturacio; // FK taula facturacioactivitats
    
    /**
     * @ORM\ManyToOne(targetEntity="Proveidor", inversedBy="docencies")
     * @ORM\JoinColumn(name="proveidor", referencedColumnName="id")
     */
    protected $proveidor; // FK taula proveidors

    
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
     * @ORM\OneToMany(targetEntity="Pagament", mappedBy="docencia")
     */
    protected $pagaments;
    
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
    public function __construct($facturacio, $proveidor, $totalhores, $preuhora, $import)
    {
    	$this->id = 0;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->databaixa = null;
    	
    	$this->facturacio = $facturacio;
    	if ($this->facturacio != null) $this->facturacio->addDocent($this);
    	$this->proveidor = $proveidor;
    	if ($this->proveidor != null) $this->proveidor->addDocencia($this);
    	$this->totalhores = $totalhores;
    	$this->preuhora = $preuhora;
    	$this->import = $import;
    	
    	$this->pagaments = new \Doctrine\Common\Collections\ArrayCollection();
    	
    }
    
    /**
     * És baixa? false
     *
     * @return boolean
     */
    public function esBaixa() { return $this->databaixa != null; }
    

    /**
     * Pagaments per mes / any
     *
     * @return array
     */
    public function getPagamentsMesAny($anypaga, $mespaga) {
    	$pagamentsMes = array();

    	foreach ($this->pagaments as $pagament) {
    		if (!$pagament->anulat()
    				&& $pagament->getDatapagament()->format('m') == $mespaga
    				&& $pagament->getDatapagament()->format('Y') == $anypaga) $pagamentsMes[] = $pagament;
    	}
    	 
    	return $pagamentsMes;
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
     * Set facturacio
     *
     * @param \Foment\GestioBundle\Entity\FacturacioActivitat $facturacio
     * @return Docencia
     */
    public function setFacturacio(\Foment\GestioBundle\Entity\FacturacioActivitat $facturacio = null)
    {
        $this->facturacio = $facturacio;

        return $this;
    }

    /**
     * Get facturacio
     *
     * @return \Foment\GestioBundle\Entity\FacturacioActivitat 
     */
    public function getFacturacio()
    {
        return $this->facturacio;
    }

    /**
     * Set proveidor
     *
     * @param \Foment\GestioBundle\Entity\Persona $proveidor
     * @return Docencia
     */
    public function setProveidor(\Foment\GestioBundle\Entity\Proveidor $proveidor = null)
    {
        $this->proveidor = $proveidor;

        return $this;
    }

    /**
     * Get proveidor
     *
     * @return \Foment\GestioBundle\Entity\Proveidor 
     */
    public function getProveidor()
    {
        return $this->proveidor;
    }
    
    /**
     * Add pagament
     *
     * @param \Foment\GestioBundle\Entity\Pagament $pagament
     * @return Docencia
     */
    public function addPagament(\Foment\GestioBundle\Entity\Pagament $pagament)
    {
    	$this->pagaments->add($pagament);
    
    	return $this;
    }
    
    /**
     * Remove pagament
     *
     * @param \Foment\GestioBundle\Entity\Pagament $pagament
     */
    public function removePagament(\Foment\GestioBundle\Entity\Pagament $pagament)
    {
    	$this->pagaments->removeElement($pagament);
    }
    
    /**
     * Get pagaments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPagaments()
    {
    	return $this->pagaments;
    }
    
}
