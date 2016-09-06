<?php 
// src/Foment/GestioBundle/Entity/Parametre.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity 
 * @ORM\Table(name="parametres",uniqueConstraints={@ORM\UniqueConstraint(name="clau_idx", columns={"clau"})})
 */

class Parametre
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=30, nullable=false)
     */
    protected $clau;	// Única

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $valor;
    
    /**
     * @ORM\Column(type="string", nullable=false)
     */
    protected $descripcio;
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	$this->id = 0;
    	$this->clau = "";
    	$this->descripcio = "";
    	$this->valor = "";
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
     * Set clau
     *
     * @param string $clau
     * @return Parametre
     */
    public function setClau($clau)
    {
        $this->clau = $clau;

        return $this;
    }

    /**
     * Get clau
     *
     * @return string 
     */
    public function getClau()
    {
        return $this->clau;
    }

    /**
     * Set valor
     *
     * @param string $valor
     * @return Parametre
     */
    public function setValor($valor)
    {
    	$this->valor = $valor;
    
    	return $this;
    }
    
    /**
     * Get valor
     *
     * @return string
     */
    public function getValor()
    {
    	return $this->valor;
    }

    /**
     * Set descripcio
     *
     * @param string $descripcio
     * @return Parametre
     */
    public function setDescripcio($descripcio)
    {
    	$this->descripcio = $descripcio;
    
    	return $this;
    }
    
    /**
     * Get descripcio
     *
     * @return string
     */
    public function getDescripcio()
    {
    	return $this->descripcio;
    }
}
