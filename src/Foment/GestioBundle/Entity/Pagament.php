<?php 
// src/Foment/GestioBundle/Entity/Pagament.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity
 * @ORM\Table(name="pagaments")
 */
/* 
 * Els pagaments es poden associar directament a un proveïdor o a una docència (professors)
 * 
 */
class Pagament
{
	
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=30)
     */
    protected $num;		// num factura o Pagament associat al pagament
    
    /**
     * @ORM\ManyToOne(targetEntity="Proveidor", inversedBy="pagaments")
     * @ORM\JoinColumn(name="proveidor", referencedColumnName="id")
     * @Assert\NotBlank(
     * 	message = "Cal indicar a qui es realitza el pagament."
     * )
     */
    protected $proveidor; // FK taula Proveidor
    
    /**
     * @ORM\ManyToOne(targetEntity="Docencia", inversedBy="pagaments")
     * @ORM\JoinColumn(name="docencia", referencedColumnName="id")
     * @Assert\NotBlank(
     * 	message = "Cal indicar a qui es realitza el pagament."
     * )
     */
    protected $docencia; // FK taula Docencia
    
    /**
     * @ORM\Column(type="date", nullable=false)
     * @Assert\NotBlank(
     * 	message = "Manca la data de pagament."
     * )
     */
    protected $datapagament;	// S'informa quan es genera

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\NotBlank(
     * 	message = "Falta el concepte del pagament."
     * )
     */
    protected $concepte;	
    
    /**
     * @ORM\Column(type="decimal", precision=6, scale=2)
     * @Assert\NotBlank(
     * 	message = "Falta l'import."
     * )
     * @Assert\Type(type="numeric", message="Format incorrecte.")
	 * @Assert\GreaterThanOrEqual(value="0", message="Valor incorrecte.")
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
     * @ORM\Column(type="date", nullable=true)
     */
    protected $databaixa;
   
    /**
     * Constructor
     */
    public function __construct($num = '', $proveidor = null, $docencia = null, $datapagament = null, $concepte = '', $import = 0)
    {
    	$this->id = 0;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->databaixa = null;
    	
    	$this->num = $num;
    	$this->proveidor = $proveidor;
    	$this->docencia = $docencia;
    	$this->datapagament = $datapagament;
    	if ($this->datapagament == null) $this->datapagament = new \DateTime();
    	$this->concepte = $concepte;
    	$this->import = $import;
    	
    	if ($this->proveidor != null) $this->proveidor->addPagament($this);
    	if ($this->docencia != null) $this->docencia->addPagament($this);
    	
    } 
    
    /**
     * És pagamentcurs?
     *
     * @return boolean
     */
    public function esPagamentcurs()
    {
    	return $this->docencia != null;
    }
    
    /**
     * get titol rebut.
     *
     * @return boolean
     */
    public function titolLiquidacio()
    {
    	if ($this->esPagamentcurs()) return UtilsController::TITOL_LIQ_DOCENT;
    	return UtilsController::TITOL_LIQ_PROVEIDOR;
    }

    /**
     * Està anul·lat el pagament?
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
     * Set num
     *
     * @param string $num
     * @return Pagament
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return string 
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set datapagament
     *
     * @param \DateTime $datapagament
     * @return Pagament
     */
    public function setDatapagament($datapagament)
    {
        $this->datapagament = $datapagament;

        return $this;
    }

    /**
     * Get datapagament
     *
     * @return \DateTime 
     */
    public function getDatapagament()
    {
        return $this->datapagament;
    }

    /**
     * Set concepte
     *
     * @param string $concepte
     * @return Pagament
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
     * Set import
     *
     * @param string $import
     * @return Pagament
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
     * @return Pagament
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
     * @return Pagament
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
     * @param \Date $databaixa
     * @return Pagament
     */
    public function setDatabaixa($databaixa)
    {
        $this->databaixa = $databaixa;

        return $this;
    }

    /**
     * Get databaixa
     *
     * @return \Date
     */
    public function getDatabaixa()
    {
        return $this->databaixa;
    }

    /**
     * Set proveidor
     *
     * @param \Foment\GestioBundle\Entity\Proveidor $proveidor
     * @return Pagament
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
     * Set docencia
     *
     * @param \Foment\GestioBundle\Entity\Docencia $docencia
     * @return Pagament
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
}
