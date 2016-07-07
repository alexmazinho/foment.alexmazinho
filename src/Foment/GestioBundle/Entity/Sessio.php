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
    protected $docencia; // FK taula facturacionsactivitats
    
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
     * Set facturacio
     *
     * @param \Foment\GestioBundle\Entity\FacturacioActivitat $facturacio
     * @return Sessio
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
