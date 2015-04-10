<?php 
// src/Foment/GestioBundle/Entity/RebutCorreccio.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="rebutscorreccions")
 */

// When generetes inheritance entity comment extends ...
// http://docs.doctrine-project.org/en/latest/reference/faq.html#can-i-use-inheritance-with-doctrine-2
class RebutCorreccio extends Rebut 
{
	/**
	 * @ORM\Id
	 * @ORM\OneToOne(targetEntity="Rebut", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="id", referencedColumnName="id")
	 */
	protected $id;
	
    /**
     * @ORM\Column(type="decimal", precision=6, scale=2)
     */
    protected $importcorreccio;
    
    /**
     * @ORM\Column(type="text", nullable=false)
     */
    protected $nouconcepte; 

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $dataentradac;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datamodificacioc;
    
    /**
     * Constructor
     */
    public function __construct($rebut, $importcorreccio, $nouconcepte, $seccio)
    {
    	//$this->id = 0;
    	
    	
    	$this->importcorreccio = $importcorreccio;
    	$this->nouconcepte = $nouconcepte;

    	$this->dataentradac = new \DateTime();
    	$this->datamodificacioc = new \DateTime();
    	
    	//parent::__construct($rebut->getDeutor(), $rebut->getDataemissio(),  $rebut->getNum(), null, $seccio );
    	
    	$this->setId($rebut);
    	
    	$this->detalls = $rebut->getDetalls();
    	
    	parent::setDataentrada($rebut->getDataentrada());
    	parent::setDatamodificacio($rebut->getDatamodificacio());
    	
    }
    
    /**
     * es correcció true.
     *
     * @return boolean
     */
    public function esCorreccio()
    {
    	return true;
    }
    
    /**
     * Get import amb la correcció corresponent. Sobreescrit
     *
     * @return double
     */
    public function getImport()
    {
    	return $this->importcorreccio;
    }
    
    /**
     * Get import sense la correcció corresponent. Sobreescrit
     *
     * @return double
     */
    public function getImportSenseCorreccio()
    {
    	return parent::getImport();
    }
    
    /**
     * Set importcorreccio
     *
     * @param double $importcorreccio
     * @return RebutCorreccio
     */
    public function setImportcorreccio($importcorreccio)
    {
        $this->importcorreccio = $importcorreccio;

        return $this;
    }

    /**
     * Get importcorreccio
     *
     * @return double 
     */
    public function getImportcorreccio()
    {
        return $this->importcorreccio;
    }
   
    /**
     * Set nouconcepte
     *
     * @param string $nouconcepte
     * @return RebutCorreccio
     */
    public function setNouconcepte($nouconcepte)
    {
        $this->nouconcepte = $nouconcepte;

        return $this;
    }

    /**
     * Get nouconcepte
     *
     * @return string 
     */
    public function getNouconcepte()
    {
        return $this->nouconcepte;
    }

    /**
     * Set id
     *
     * @param \Foment\GestioBundle\Entity\Rebut $id
     * @return RebutCorreccio
     */
    public function setId(\Foment\GestioBundle\Entity\Rebut $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return \Foment\GestioBundle\Entity\Rebut 
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set dataentradac
     *
     * @param \DateTime $dataentradac
     * @return Rebut
     */
    public function setDataentradac($dataentradac)
    {
    	$this->dataentradac = $dataentradac;
    
    	return $this;
    }
    
    /**
     * Get dataentradac
     *
     * @return \DateTime
     */
    public function getDataentradac()
    {
    	return $this->dataentradac;
    }
    
    /**
     * Set datamodificacioc
     *
     * @param \DateTime $datamodificacioc
     * @return Rebut
     */
    public function setDatamodificacioc($datamodificacioc)
    {
    	$this->datamodificacioc = $datamodificacioc;
    
    	return $this;
    }
    
    /**
     * Get datamodificacioc
     *
     * @return \DateTime
     */
    public function getDatamodificacioc()
    {
    	return $this->datamodificacioc;
    }
}
