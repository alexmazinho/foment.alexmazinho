<?php 
// src/Foment/GestioBundle/Entity/Compte.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="comptes")
 */

// Sense estratégia @ORM\GeneratedValue(strategy="AUTO")
// Cada soci té màxim un compte bé està associat al compte d'un altre soci
class Compte
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\OneToMany(targetEntity="Soci", mappedBy="compte")
     */
    protected $soci; // Un dels socis del compte és el titular
    
    /**
     * @ORM\Column(type="string", length=80, nullable=false)
     */
    protected $titular;
    
    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $banc; 

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $agencia;
    
    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $dc;

    /**
     * @ORM\Column(type="bigint", nullable=true)
	 */
    protected $numcompte;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     */
    protected $iban; // ESXXBBBBOOOODDNNNNNNNNNN
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $dataentrada;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datamodificacio;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = 0;
        $this->dataentrada = new \DateTime();
        $this->datamodificacio = new \DateTime();
    }

    /**
     * Get compte format 4+4+2+10 = 20
     *
     * @return integer
     */
    public function getCompte20() {
    	$compte = "";
    	if ($this->iban != null && $this->iban != "") {
    		$compte = substr($this->iban, 4); // Treure els 4 primers
    	} else {
    		if (!(trim($this->banc) == "" || trim($this->agencia) == "" || trim($this->dc) == "" || trim($this->numcompte) == "")) { 
		    	/*$compte .= strlen($this->banc)==4?$this->banc:str_pad($this->banc, 4, "0", STR_PAD_LEFT);
		    	$compte .= strlen($this->agencia)==4?$this->agencia:str_pad($this->agencia, 4, "0", STR_PAD_LEFT);
		    	$compte .= strlen($this->dc)==2?$this->dc:str_pad($this->dc, 2, "0", STR_PAD_LEFT);
		    	$compte .= strlen($this->numcompte)==10?$this->numcompte:str_pad($this->numcompte, 10, "0", STR_PAD_LEFT);*/
		    	$compte .= str_pad($this->banc, 4, "0", STR_PAD_LEFT);
		    	$compte .= str_pad($this->agencia, 4, "0", STR_PAD_LEFT);
		    	$compte .= str_pad($this->dc, 2, "0", STR_PAD_LEFT);
		    	$compte .= str_pad($this->numcompte, 10, "0", STR_PAD_LEFT);
		    	 
    		}
    	}
		if ( !is_numeric($compte) || strlen($compte) != 20 ) {
			return "";
		}
    	
    	return $compte;
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
     * Set id
     *
     * @param integer $id
     * @return Compte
     */
    public function setId($id)
    {
    	$this->id = $id;
    
    	return $this;
    }
    
    /**
     * Set soci
     *
     * @param \Foment\GestioBundle\Entity\Soci $soci
     * @return Compte
     */
    public function setSoci(\Foment\GestioBundle\Entity\Soci $soci = null)
    {
    	$this->soci = $soci;
    
    	return $this;
    }
    
    /**
     * Get soci
     *
     * @return \Foment\GestioBundle\Entity\Soci
     */
    public function getSoci()
    {
    	return $this->soci;
    }
    
    /**
     * Set titular
     *
     * @param string $titular
     * @return Compte
     */
    public function setTitular($titular)
    {
    	$this->titular = $titular;
    
    	return $this;
    }
    
    /**
     * Get titular
     *
     * @return string
     */
    public function getTitular()
    {
    	return $this->titular;
    }
    
    /**
     * Set agencia
     *
     * @param integer $agencia
     * @return Compte
     */
    public function setAgencia($agencia)
    {
        $this->agencia = $agencia;

        return $this;
    }

    /**
     * Get agencia
     *
     * @return integer 
     */
    public function getAgencia()
    {
        return $this->agencia;
    }

    /**
     * Set dc
     *
     * @param integer $dc
     * @return Compte
     */
    public function setDc($dc)
    {
        $this->dc = $dc;

        return $this;
    }

    /**
     * Get dc
     *
     * @return integer 
     */
    public function getDc()
    {
        return $this->dc;
    }

    /**
     * Set numcompte
     *
     * @param integer $numcompte
     * @return Compte
     */
    public function setNumcompte($numcompte)
    {
        $this->numcompte = $numcompte;

        return $this;
    }

    /**
     * Get numcompte
     *
     * @return integer 
     */
    public function getNumcompte()
    {
        return $this->numcompte;
    }

    /**
     * Set banc
     *
     * @param integer $banc
     * @return Compte
     */
    public function setBanc($banc)
    {
        $this->banc = $banc;

        return $this;
    }

    /**
     * Get banc
     *
     * @return integer 
     */
    public function getBanc()
    {
        return $this->banc;
    }

    /**
     * Set iban
     *
     * @param integer $iban
     * @return Compte
     */
    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * Get iban
     *
     * @return integer 
     */
    public function getIban()
    {
        return $this->iban;
    }
    
    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Persona
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
     * @return Persona
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
}
