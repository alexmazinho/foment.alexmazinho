<?php 
// src/Foment/GestioBundle/Entity/ActivitatAnual.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
 * @ORM\Table(name="activitatsanuals")
 */
// When generetes inheritance entity comment extends ...
// http://docs.doctrine-project.org/en/latest/reference/faq.html#can-i-use-inheritance-with-doctrine-2
class ActivitatAnual extends Activitat 
{
	/**
	 * @ORM\Id
	 * @ORM\OneToOne(targetEntity="Activitat", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="id", referencedColumnName="id")
	 */
    protected $id;

    /**
     * @ORM\Column(type="string", length=7, nullable=false)
     */
    protected $curs;   // Format 2015-16
    
    
    /**
     * @ORM\Column(type="date", nullable=false)
     */
    protected $datainici;
    
    
    /**
     * @ORM\Column(type="date", nullable=false)
     */
    protected $datafinal;
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	parent::__construct();
        $this->curs = date('Y').'-'.(date('y')+1);
        
        // Dates inici final per defecte curs escolar
        $this->datainici =  \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_INICI_CURS_SETEMBRE. date('Y') );
        $this->datafinal =  \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_FINAL_CURS_JUNY. (date('Y') +1));
    }

    /**
     * Get 'anual' as string.
     *
     * @return string
     */
    public function getTipus()
    {
    	return parent::TIPUS_ANUAL;
    }
    
    /**
     * Get data inicial
     *
     * @return \DateTime
     */
    public function getDataactivitat()
    {
    	return $this->datainici;
    }
    
    /**
     * Set id
     *
     * @param \Foment\GestioBundle\Entity\Activitat $id
     * @return ActivitatAnual
     */
    public function setId(\Foment\GestioBundle\Entity\Activitat $id)
    {
    	$this->id = $id;
    
    	return $this;
    }
    
    /**
     * Get id
     *
     * @return \Foment\GestioBundle\Entity\Activitat
     */
    public function getId()
    {
    	return $this->id;
    }
    
    /**
     * Set curs
     *
     * @param string $curs
     * @return ActivitatAnual
     */
    public function setCurs($curs)
    {
    	$this->curs = $curs;
    
    	return $this;
    }
    
    /**
     * Get curs
     *
     * @return string
     */
    public function getCurs()
    {
    	return $this->curs;
    }
    
    /**
     * Set datainici
     *
     * @param \DateTime $datainici
     * @return ActivitatAnual
     */
    public function setDatainici($datainici)
    {
    	$this->datainici = $datainici;
    
    	return $this;
    }
    
    /**
     * Get datainici
     *
     * @return \DateTime
     */
    public function getDatainici()
    {
    	return $this->datainici;
    }
    
    /**
     * Set datafinal
     *
     * @param \DateTime $datafinal
     * @return ActivitatAnual
     */
    public function setDatafinal($datafinal)
    {
    	$this->datafinal = $datafinal;
    
    	return $this;
    }
    
    /**
     * Get datafinal
     *
     * @return \DateTime
     */
    public function getDatafinal()
    {
    	return $this->datafinal;
    }
}
