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
    protected $importoriginal;
    
    /**
     * @ORM\Column(type="text", nullable=false)
     */
    protected $motiu; 


    /**
     * Set importoriginal
     *
     * @param string $importoriginal
     * @return RebutCorreccio
     */
    public function setImportoriginal($importoriginal)
    {
        $this->importoriginal = $importoriginal;

        return $this;
    }

    /**
     * Get importoriginal
     *
     * @return string 
     */
    public function getImportoriginal()
    {
        return $this->importoriginal;
    }

    /**
     * Set motiu
     *
     * @param string $motiu
     * @return RebutCorreccio
     */
    public function setMotiu($motiu)
    {
        $this->motiu = $motiu;

        return $this;
    }

    /**
     * Get motiu
     *
     * @return string 
     */
    public function getMotiu()
    {
        return $this->motiu;
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
}
