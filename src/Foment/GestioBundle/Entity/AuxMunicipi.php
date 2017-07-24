<?php
// src/Foment/GestioBundle/Entity/AuxMunicipi.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="auxmunicipis")
 * 
 * @author alex
 *
 */
class AuxMunicipi {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=50)
	 */
	protected $municipi;

	/**
	 * @ORM\Column(type="string", length=30)
	 */
	protected $comarca;
	
	/**
	 * @ORM\Column(type="string", length=20)
	 */
	protected $provincia;
	
	/**
	 * @ORM\Column(type="string", length=5)
	 */
	protected $cp;

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
     * Set municipi
     *
     * @param string $municipi
     */
    public function setMunicipi($municipi)
    {
        $this->municipi = $municipi;
    }

    /**
     * Get municipi
     *
     * @return string 
     */
    public function getMunicipi()
    {
        return $this->municipi;
    }

    /**
     * Set comarca
     *
     * @param string $comarca
     */
    public function setComarca($comarca)
    {
        $this->comarca = $comarca;
    }

    /**
     * Get comarca
     *
     * @return string 
     */
    public function getComarca()
    {
        return $this->comarca;
    }

    /**
     * Set provincia
     *
     * @param string $provincia
     */
    public function setProvincia($provincia)
    {
        $this->provincia = $provincia;
    }

    /**
     * Get provincia
     *
     * @return string 
     */
    public function getProvincia()
    {
        return $this->provincia;
    }

    /**
     * Set cp
     *
     * @param string $cp
     */
    public function setCp($cp)
    {
        $this->cp = $cp;
    }

    /**
     * Get cp
     *
     * @return string 
     */
    public function getCp()
    {
        return $this->cp;
    }
}