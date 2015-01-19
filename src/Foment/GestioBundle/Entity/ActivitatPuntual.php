<?php 
// src/Foment/GestioBundle/Entity/ActivitatPuntual.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity 
 * @ORM\Table(name="activitatspuntuals")
 */

// When generetes inheritance entity comment extends ...
// http://docs.doctrine-project.org/en/latest/reference/faq.html#can-i-use-inheritance-with-doctrine-2
class ActivitatPuntual extends Activitat
{
	/**
	 * @ORM\Id
	 * @ORM\OneToOne(targetEntity="Activitat", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="id", referencedColumnName="id")
	 */
    protected $id;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\NotBlank(
     * 	message = "Falta la data"
     * )
     * @Assert\DateTime(message="Data incorrecte.")
     */
    protected $dataactivitat;


    /**
     * Constructor
     */
    public function __construct()
    {
    	parent::__construct();
    }
    
    /**
     * es anual?.
     *
     * @return boolean
     */
    public function esAnual()
    {
    	return false;
    }
    
    
    /**
     * Get 'puntual' as string.
     *
     * @return string
     */
    public function getTipus()
    {
    	return parent::TIPUS_PUNTUAL;
    }
    
    /**
     * Get info del calendari de l'activitat as string
     *
     * @return string
     */
    public function getInfoCalendari()
    {
    	return 'El dia ' .$this->dataactivitat->format('d/m/Y') . ' a les ' . $this->dataactivitat->format('H:i');
    }
    /**
     * Set dataactivitat
     *
     * @param \DateTime $dataactivitat
     * @return ActivitatPuntual
     */
    public function setDataactivitat($dataactivitat)
    {
        $this->dataactivitat = $dataactivitat;

        return $this;
    }

    /**
     * Get dataactivitat
     *
     * @return \DateTime 
     */
    public function getDataactivitat()
    {
        return $this->dataactivitat;
    }

    /**
     * Set id
     *
     * @param \Foment\GestioBundle\Entity\Activitat $id
     * @return ActivitatPuntual
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
}
