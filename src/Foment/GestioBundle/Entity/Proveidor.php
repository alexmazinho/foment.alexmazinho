<?php 
// src/Foment/GestioBundle/Entity/Proveidor.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity
 * @ORM\Table(name="proveidors")
 */
class Proveidor
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=70, nullable=false)
     */
    protected $raosocial;  

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    protected $cif;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $telffix;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $telfmobil;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $correu; 
    
    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $adreca;  
    
    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    protected $cp;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $poblacio;
    
    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $provincia;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $observacions;
    
    /**
     * @ORM\OneToMany(targetEntity="Pagament", mappedBy="proveidor")
     */
    protected $pagaments;
    
    /**
     * @ORM\OneToMany(targetEntity="Docencia", mappedBy="proveidor")
     */
    protected $docencies;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $dataentrada;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datamodificacio;   

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $databaixa;
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	$this->id = 0;
    	$this->databaixa = null;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
        $this->docencies = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pagaments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->telffix = null;
        $this->telfmobil  = null;
        $this->correu = null;
        $this->adreca = null;
        $this->cp = null;
        $this->poblacio = null;
        $this->provincia = null;
        $this->observacions = null;
    }

    /**
     * toString
     */
    public function __toString()
    {
    	
    	return $this->id . " " . $this->getRaosocial();
    }   
     
    /**
     * És baixa? false
     *
     * @return boolean
     */
    public function esBaixa() { return $this->databaixa != null; }
    
    
    /**
     * Get contacte
     *
     * @return string
     */
    public function getContacte()
    {
    	$mailHref = '';
    	if ($this->correu != null && $this->correu != "") $mailHref = '<a href="mailto:'.$this->correu.'">'.$this->correu.'</a>';
    	if ($this->telffix == null && $this->telfmobil == null) return '--';
    	if ($this->telffix == null) return UtilsController::format_phone($this->telfmobil).'<br/>'.$mailHref;
    	if ($this->telfmobil == null) return UtilsController::format_phone($this->telffix).'<br/>'.$mailHref;
    	 
    	return UtilsController::format_phone($this->telffix) .' - '. UtilsController::format_phone($this->telfmobil).'<br/>'.$mailHref;
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
     * Set raosocial
     *
     * @param string $raosocial
     * @return Proveidor
     */
    public function setRaosocial($raosocial)
    {
        $this->raosocial = $raosocial;

        return $this;
    }

    /**
     * Get raosocial
     *
     * @return string 
     */
    public function getRaosocial()
    {
        return $this->raosocial;
    }

    /**
     * Set cif
     *
     * @param string $cif
     * @return Proveidor
     */
    public function setCif($cif)
    {
        $this->cif = $cif;

        return $this;
    }

    /**
     * Get cif
     *
     * @return string 
     */
    public function getCif()
    {
        return $this->cif;
    }

    /**
     * Set telffix
     *
     * @param integer $telffix
     * @return Proveidor
     */
    public function setTelffix($telffix)
    {
        $this->telffix = $telffix;

        return $this;
    }

    /**
     * Get telffix
     *
     * @return integer 
     */
    public function getTelffix()
    {
        return $this->telffix;
    }

    /**
     * Set telfmobil
     *
     * @param integer $telfmobil
     * @return Proveidor
     */
    public function setTelfmobil($telfmobil)
    {
        $this->telfmobil = $telfmobil;

        return $this;
    }

    /**
     * Get telfmobil
     *
     * @return integer 
     */
    public function getTelfmobil()
    {
        return $this->telfmobil;
    }

    

    
    /**
     * Set correu
     *
     * @param string $correu
     * @return Proveidor
     */
    public function setCorreu($correu)
    {
        $this->correu = $correu;

        return $this;
    }

    /**
     * Get correu
     *
     * @return string 
     */
    public function getCorreu()
    {
        return $this->correu;
    }

    /**
     * Set adreca
     *
     * @param string $adreca
     * @return Proveidor
     */
    public function setAdreca($adreca)
    {
        $this->adreca = $adreca;

        return $this;
    }

    /**
     * Get adreca
     *
     * @return string 
     */
    public function getAdreca()
    {
        return $this->adreca;
    }

    /**
     * Set cp
     *
     * @param string $cp
     * @return Proveidor
     */
    public function setCp($cp)
    {
        $this->cp = $cp;

        return $this;
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
    
    /**
     * Set poblacio
     *
     * @param string $poblacio
     * @return Proveidor
     */
    public function setPoblacio($poblacio)
    {
        $this->poblacio = $poblacio;

        return $this;
    }

    /**
     * Get poblacio
     *
     * @return string 
     */
    public function getPoblacio()
    {
        return $this->poblacio;
    }

    /**
     * Set provincia
     *
     * @param string $provincia
     * @return Proveidor
     */
    public function setProvincia($provincia)
    {
        $this->provincia = $provincia;

        return $this;
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
     * Set observacions
     *
     * @param string $observacions
     * @return Soci
     */
    public function setObservacions($observacions)
    {
    	$this->observacions = $observacions;
    
    	return $this;
    }
    
    /**
     * Get observacions
     *
     * @return string
     */
    public function getObservacions()
    {
    	return $this->observacions;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Proveidor
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
     * @return Proveidor
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

    /**
     * Set databaixa
     *
     * @param \DateTime $databaixa
     * @return Proveidor
     */
    public function setDatabaixa($databaixa)
    {
    	$this->databaixa = $databaixa;
    
    	return $this;
    }
    
    /**
     * Get databaixa
     *
     * @return \DateTime
     */
    public function getDatabaixa()
    {
    	return $this->databaixa;
    }
    
    /**
     * Add pagament
     *
     * @param \Foment\GestioBundle\Entity\Pagament $pagament
     * @return Proveidor
     */
    public function addPagament(\Foment\GestioBundle\Entity\Pagament $pagament)
    {
    	$this->pagaments->add($pagament);
    
    	return $this;
    }
    
    /**
     * Remove pagament
     *
     * @param \Foment\GestioBundle\Entity\Pagament $pagament
     */
    public function removePagament(\Foment\GestioBundle\Entity\Pagament $pagament)
    {
    	$this->pagaments->removeElement($pagament);
    }
    
    /**
     * Get pagaments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPagaments()
    {
    	return $this->pagaments;
    }
    
    /**
     * Add $docencia
     *
     * @param \Foment\GestioBundle\Entity\Docencia $docencia
     * @return Proveidor
     */
    public function addDocencia(\Foment\GestioBundle\Entity\Docencia $docencia)
    {
    	$this->docencies->add($docencia);
        //$this->docencies[] = $docencies;

        return $this;
    }

    /**
     * Remove $docencia
     *
     * @param \Foment\GestioBundle\Entity\Docencia $docencia
     */
    public function removeDocencia(\Foment\GestioBundle\Entity\Docencia $docencia)
    {
        $this->docencies->removeElement($docencia);
    }

    /**
     * Get docencies
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDocencies()
    {
        return $this->docencies;
    }
    
    
    
}
