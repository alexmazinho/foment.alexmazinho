<?php 
// src/Foment/GestioBundle/Entity/Sessio.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity 
 * @ORM\Table(name="sessions")
 */

/* Una sessió del calendari d'una activitat anual 
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
     * @ORM\ManyToOne(targetEntity="ActivitatAnual", inversedBy="calendari")
     * @ORM\JoinColumn(name="activitat", referencedColumnName="id")
     */
    protected $activitat; // FK taula activitatsanuals
    
    
    /**
     * @ORM\OneToOne(targetEntity="Esdeveniment")
     * @ORM\JoinColumn(name="horari", referencedColumnName="id")
     */
    protected $horari; // FK taula esdeveniments


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
     * Set activitat
     *
     * @param \Foment\GestioBundle\Entity\ActivitatAnual $activitat
     * @return Sessio
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
}
