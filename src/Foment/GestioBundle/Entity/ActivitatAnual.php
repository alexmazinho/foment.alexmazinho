<?php 
// src/Foment/GestioBundle/Entity/ActivitatAnual.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
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
     * @ORM\Column(type="date", nullable=false)
     * @Assert\NotBlank(
     * 	message = "Falta la data"
     * )
     * @Assert\DateTime(message="Data incorrecte.")
     */
    protected $datainici;
    
    
    /**
     * @ORM\Column(type="date", nullable=false)
     * @Assert\NotBlank(
     * 	message = "Falta la data"
     * )
     * @Assert\DateTime(message="Data incorrecte.")
     */
    protected $datafinal;
    
    
    /**
     * @ORM\OneToMany(targetEntity="Docencia", mappedBy="activitat")
     */
    protected $docents;

    /**
     * @ORM\OneToMany(targetEntity="Sessio", mappedBy="activitat")
     */
    protected $calendari;
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	parent::__construct();
        $this->docents = new \Doctrine\Common\Collections\ArrayCollection();
        $this->calendari = new \Doctrine\Common\Collections\ArrayCollection();

        // Dates inici final per defecte curs escolar
        $this->datainici =  \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_INICI_CURS_SETEMBRE. date('Y') );
        $this->datafinal =  \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_FINAL_CURS_JUNY. (date('Y') +1));
        
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
     * Get info del calendari de l'activitat as string
     *
     * @return string
     */
    public function getInfoCalendari()
    {
    	return 'activitat anual (calendari pendent)';
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
     * Add docents
     *
     * @param \Foment\GestioBundle\Entity\Docencia $docents
     * @return ActivitatAnual
     */
    public function addDocent(\Foment\GestioBundle\Entity\Docencia $docents)
    {
        $this->docents[] = $docents;

        return $this;
    }

    /**
     * Remove docents
     *
     * @param \Foment\GestioBundle\Entity\Docencia $docents
     */
    public function removeDocent(\Foment\GestioBundle\Entity\Docencia $docents)
    {
        $this->docents->removeElement($docents);
    }

    /**
     * Get docents
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDocents()
    {
        return $this->docents;
    }

    /**
     * Add calendari
     *
     * @param \Foment\GestioBundle\Entity\Sessio $calendari
     * @return ActivitatAnual
     */
    public function addCalendari(\Foment\GestioBundle\Entity\Sessio $calendari)
    {
        $this->calendari[] = $calendari;

        return $this;
    }

    /**
     * Remove calendari
     *
     * @param \Foment\GestioBundle\Entity\Sessio $calendari
     */
    public function removeCalendari(\Foment\GestioBundle\Entity\Sessio $calendari)
    {
        $this->calendari->removeElement($calendari);
    }

    /**
     * Get calendari
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCalendari()
    {
        return $this->calendari;
    }
}
