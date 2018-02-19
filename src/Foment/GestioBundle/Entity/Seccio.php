<?php 
// src/Foment/GestioBundle/Entity/Seccio.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity 
 * @ORM\Table(name="seccions")
 */

class Seccio
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=30, nullable=false)
     * @Assert\NotBlank(
     * 	message = "Cal indicar el nom."
     * )
     */
    protected $nom;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $ordre;
    
    /**
	 * @ORM\OneToMany(targetEntity="Quota", mappedBy="seccio")
	 */
	protected $quotes;
    
	/**
	 * @ORM\OneToMany(targetEntity="Membre", mappedBy="seccio")
	 */
	protected $membres;
	
	/**
	 * @ORM\Column(type="boolean", nullable=false)
	 */
	protected $semestral;

	/**
	 * @ORM\Column(type="integer", nullable=false)
	 */
	protected $facturacions;
	
	/**
	 * @ORM\Column(type="boolean", nullable=false)
	 */
	protected $fraccionat;
	
	/**
	 * @ORM\Column(type="boolean", nullable=false)
	 */
	protected $exemptfamilia; // quota 0 famílies nombroses
	
	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 */
	protected $dataentrada;
	
	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 */
	protected $datamodificacio;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $databaixa;

    /**
     * Constructor
     */
    public function __construct()
    {
    	$this->id = 0;
    	$this->semestral = true;
    	$this->facturacions = 2;
    	$this->order = 99;
    	$this->fraccionat = false;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->databaixa = null;
        $this->quotes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->membres = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * És la seccio general (Foment, id = 1)
     *
     * @return boolean
     */
    public function esGeneral() { return $this->id == 1; }
    
    
    /**
     * És la seccio Terranova (Foment, id = 14)
     *
     * @return boolean
     */
    public function esTerranova() { return $this->id == 14; }
    
    /**
     * Get info as string
     *
     * @return string
     */
    public function getInfo()
    {
		return $this->nom;
    }
    
    /**
     * Get info plus import as string
     *
     * @return string
     */
    public function getInfoPreu()
    {
    	$quota = $this->getQuotaAny(date('Y'));
    	$quotajuvenil = $this->getQuotaAny(date('Y'), true);
    	
    	if ($quota == 0 && $quotajuvenil == 0) return $this->nom;
    	
    	if ($quota == $quotajuvenil || $quotajuvenil == 0) return $this->nom . "    (" . number_format($quota, 2, ',', '.') ." €)";
    	
    	return $this->nom . "    (" . number_format($quota, 2, ',', '.') ." €, juvenil ".
    			number_format($quotajuvenil, 2, ',', '.')."€)";
    }
    
    /**
     * Returns membre no cancelats with Soci identified by $id or null
     *
     * @param integer $id
     * @return \Foment\GestioBundle\Entity\Membre
     */
    public function getMembreBySociId($id, $filtre = '') {
    	foreach ($this->membres as $membre)  {
    		if ($filtre == 'junta') {
    			if ($membre->getDatacancelacio() == null && $membre->esJunta() == true && 
    					$membre->getDatafins() == null && $membre->getSoci()->getId() == $id) return $membre;
    		} else {
    			if ($membre->getDatacancelacio() == null && $membre->getSoci()->getId() == $id) return $membre;
    		}
    	}
    	return null;
    }
    
    /**
     * Get id's dels membres no cancelats de la seccio
     *
     * @return string
     */
    /*public function getMembresIds($filtre = '')
    {
    	$membres_ids = array();
    	return $membres_ids; // Aquest mètode per la secció Foment fa 950 crides 
    	
    	foreach ($this->membres as $membre)  {
    		if ($membre->getDatacancelacio() == null) {
    			if (($filtre == 'junta' && $membre->esJunta() == true) ||
    				 $filtre != 'junta') {
	    			$membres_ids[] = $membre->getSoci()->getId();
    			}
    		}
    	}
    	 
    	//rsort($activitats_ids); // De major a menor
    	 
    	return $membres_ids;
    }*/
    
    
    /**
     * Get membres no cancelats o actius durant l'any
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMembresActius($any)
    {
    	// Cercar informació entre dates
    	$desde = \DateTime::createFromFormat('Y-m-d', $any."-01-01");
    	$fins = \DateTime::createFromFormat('Y-m-d', $any."-12-31");

    	$arr = array();
    	foreach ($this->membres as $membre) {
    		$soci = $membre->getSoci();
    		if (!isset($arr[$soci->getId()])) {
	    		$actiu = $membre->esMembreActiuPeriode($desde, $fins);
	    		if ($actiu) $arr[$soci->getId()] = $membre;
    		}
    	}

    	return $arr;
    }
    
    /**
     * Get total membres no cancelats o actius durant l'any
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTotalMembres($any)
    {
    	return count($this->getMembresActius($any));
    }
    
    
    /**
     * Get membres no cancelats o actius durant l'any sorted by cognom
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMembresSortedByCognom($any)
    {
    	$arr = $this->getMembresActius($any);
    
    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getSoci()->getCognoms() < $b->getSoci()->getCognoms())? -1:1;;
    	});
    	
    	return $arr;
    }
    
	/**
     * Get membres de la junta. Ordenats per càrrec
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMembresjunta()
    {
    	$junta = array();
    	foreach ($this->membres as $membre) {
    	    if ($membre->getDatacancelacio() == null && $membre->esJunta() && $membre->getDatafins() == null) $junta[] = $membre;
    	}
    	
    	usort($junta, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		if ($a->getCarrec() == $b->getCarrec()) return ($a->getArea() > $b->getArea())? -1:1;  // Àrea descendent, primer els que la tenen
    		
    		return ($a->getCarrec() < $b->getCarrec())? -1:1;
    	});
    	
        return $junta;
    }
    
    /**
     * Get alta membres entre dues dates
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAltesMembresPeriode($desde, $fins)
    {
		if ($desde == '' || $desde == null) $desde = \DateTime::createFromFormat('d/m/Y', '01/01/1900');
		if ($fins == '' || $fins == null) $fins = null;
	
		$iter = $this->membres->getIterator();
		
		$altes = array();

    	foreach ($iter as $membre)  {
    		// Mirar només darrera inscripció
    		$soci = $membre->getSoci();
    		if (!isset($altes[$soci->getId()])) {
    			// Altes i baixes dins el mateix periode només surten a baixes
    			$alta = $membre->esMembreAltaPeriode($desde, $fins);
    			
    			if ($alta) $altes[$soci->getId()] = $soci;
    		}
    	}
    	
    	if (count($altes) > 0) { 
	    	uasort($altes, function($a, $b) {
	    		if ($a === $b) {
	    			return 0;
	    		}
	    		if ($a->getCognoms() == $b->getCognoms()) return ($a->getNom() > $b->getNom())? -1:1;  
	    	
	    		return ($a->getCognoms() < $b->getCognoms())? -1:1;
	    	});
    	}
    	
    	return $altes;
    }

    /**
     * Get baixes membres entre dues dates
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBaixesMembresPeriode($desde, $fins)
    {
    	if ($desde == '' || $desde == null) $desde = \DateTime::createFromFormat('d/m/Y', '01/01/1900');
    	if ($fins == '' || $fins == null) $fins = null;
    
    	$iter = $this->membres->getIterator();
    	
    	
    	$baixes = array();
    	//$current = 0;
    	foreach ($iter as $membre)  {
    		// Mirar només darrera inscripció
    		$soci = $membre->getSoci();
    		if (!isset($baixes[$soci->getId()])) {
    			// Altes i baixes dins el mateix periode només surten a baixes
    			$baixa = $membre->esMembreBaixaPeriode($desde, $fins);
    			
    			if ($baixa) $baixes[$soci->getId()] = $soci;
    		}
    	}
    	
    	if (count($baixes) > 0) {
    		uasort($baixes, function($a, $b) {
    			if ($a === $b) {
    				return 0;
    			}
    			if ($a->getCognoms() == $b->getCognoms()) return ($a->getNom() > $b->getNom())? -1:1;
    	
    			return ($a->getCognoms() < $b->getCognoms())? -1:1;
    		});
    	}
    	 
    	return $baixes;
    }
    
    /**
     * Quotes existents per l'any indicat
     *
     * @return boolean
     */
    public function checkQuotesAny($anyquota)
    {
    	if ($anyquota == null) $anyquota = date('Y');
    	foreach ($this->quotes as $quota)  {
    		if ($quota->getAnyquota() == $anyquota) return true;
    	}
    	 
    	return false;
    }
    
    
    /**
     * Get info as decimal
     *
     * @return double
     */
    public function getQuotaAny($anyquota, $juvenil = false)
    {
    	if ($anyquota == null) $anyquota = date('Y');
    	foreach ($this->quotes as $quota)  {
			if ($quota->getAnyquota() == $anyquota) {
				if ($juvenil) return $quota->getImportjuvenil();
				else return $quota->getImport();
			}    		
    	}
    	
    	return 0;
    }
    
    /**
     * Set quota per year (normal o juvenil)
     * Si no existeix la crea
     * 
     * @return \Foment\GestioBundle\Entity\Quota 
     */
    public function setQuotaAny($anyquota, $import, $juvenil = false)
    {
    	if ($anyquota == null) $anyquota = date('Y');
    	foreach ($this->quotes as $quota)  {
    		if ($quota->getAnyquota() == $anyquota) {
    			if ($juvenil) $quota->setImportjuvenil($import);
    			else $quota->setImport($import);
    			$quota->setDatamodificacio(new \DateTime());
    			return $quota;
    		}
    	}

    	// No trobada, cal crear quota
    	$quota = new Quota($anyquota);
    	if ($juvenil) $quota->setImportjuvenil($import);
    	else $quota->setImport($import);
    	$quota->setSeccio($this);
    	$this->addQuote($quota);
    	
    	return $quota;
    }
    
    
    public function getInfoSoci()
    {
    	return '--';
    }
    
    /**
     * Add $membre en junta de $this. El membre ja existeix així que s'actualitzen els objectes
     *
     * @param \Foment\GestioBundle\Entity\Membre $membre
     * @return \Foment\GestioBundle\Entity\Junta
     */
    public function addMembreJunta(\Foment\GestioBundle\Entity\Membre $membre)
    {
    	$membrejunta = new Junta($membre);// Copiar tot
    	$membrejunta->setSoci($membre->getSoci());
    	$membrejunta->setSeccio($membre->getSeccio());
    	$membrejunta->getSoci()->addMembrede($membrejunta);
    	$this->addMembre($membrejunta);
    	
    	return $membrejunta;
    }
    
    
    /**
     * Add membre $this en $junta. El soci ja es membre així que s'actualitzen els objectes
     *
     * @param \Foment\GestioBundle\Entity\Soci $soci
     * @return array
     */
    /*public function addMembreJunta(\Foment\GestioBundle\Entity\Soci $soci)
    {
    	$membrejunta = new Junta();
    	$membrejunta->setSoci($soci);
    	$membrejunta->setSeccio($this);
    	
    	$soci->updateMembreJuntaBySeccio($this, $membrejunta);
    	$current = $this->updateMembredePerJuntaBySoci($soci, $membrejunta);
    	
    	return array('persist' => $membrejunta, 'remove' => $current);
    }*/
    
    /**
     * Add membre $this en $junta. El soci es un nou membre
     *
     * @param \Foment\GestioBundle\Entity\Soci $soci
     * @param $anydades 
     * @return \Foment\GestioBundle\Entity\Membre
     */
    public function addMembreSeccio(\Foment\GestioBundle\Entity\Soci $soci)
    {
    	$membre = new Membre();
    	$membre->setSoci($soci);
    	$membre->setSeccio($this);
    	 
    	$soci->addMembrede($membre);
    	$this->addMembre($membre);
    	 
    	return $membre;
    }
    
    /**
     * Change membre with $soci per membrejunta
     */
    /*private function updateMembredePerJuntaBySoci(\Foment\GestioBundle\Entity\Soci $soci, \Foment\GestioBundle\Entity\Junta $membrejunta)
    {
    	$current = $this->getMembreBySociId($soci->getId());
    	
    	// Copy $current data to $membrejunta. datainscripció, dataentrada ...
    	$membrejunta->setDatainscripcio($current->getDatainscripcio());
    	$membrejunta->setDataentrada($current->getDataentrada());
    	
    	$this->removeMembre($current);
    	
    	$this->addMembre($membrejunta);
    	
    	return $current;
    }*/
    
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
     * Set nom
     *
     * @param string $nom
     * @return Seccio
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set ordre
     *
     * @param integer $ordre
     * @return Seccio
     */
    public function setOrdre($ordre)
    {
    	$this->ordre = $ordre;
    
    	return $this;
    }
    
    /**
     * Get ordre
     *
     * @return integer
     */
    public function getOrdre()
    {
    	return $this->ordre;
    }
    
    
    
    /**
     * Set semestral
     *
     * @param boolean $semestral
     * @return Seccio
     */
    public function setSemestral($semestral)
    {
    	$this->semestral = $semestral;
    
    	return $this;
    }
    
    /**
     * Get semestral
     *
     * @return boolean
     */
    public function getSemestral()
    {
    	return $this->semestral;
    }
    
    /**
     * Set facturacions
     *
     * @param integer $facturacions
     * @return Seccio
     */
    public function setFacturacions($facturacions)
    {
    	$this->facturacions = $facturacions;
    
    	return $this;
    }
    
    /**
     * Get facturacions
     *
     * @return integer
     */
    public function getFacturacions()
    {
    	return $this->facturacions;
    }
    
    
    /**
     * Set fraccionat
     *
     * @param boolean $fraccionat
     * @return Seccio
     */
    public function setFraccionat($fraccionat)
    {
    	$this->fraccionat = $fraccionat;
    
    	return $this;
    }
    
    /**
     * Get fraccionat
     *
     * @return boolean
     */
    public function getFraccionat()
    {
    	return $this->fraccionat;
    }

    /**
     * Set exemptfamilia
     *
     * @param boolean $exemptfamilia
     * @return Seccio
     */
    public function setExemptfamilia($exemptfamilia)
    {
    	$this->exemptfamilia = $exemptfamilia;
    
    	return $this;
    }
    
    /**
     * Get exemptfamilia
     *
     * @return boolean
     */
    public function getExemptfamilia()
    {
    	return $this->exemptfamilia;
    }
    
    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Seccio
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
     * @return Seccio
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
     * @return Seccio
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
     * Add quotes
     *
     * @param \Foment\GestioBundle\Entity\Quota $quotes
     * @return Seccio
     */
    public function addQuote(\Foment\GestioBundle\Entity\Quota $quotes)
    {
    	$this->quotes->add($quotes);
    	//$this->quotes[] = $quotes;

        return $this;
    }

    /**
     * Remove quotes
     *
     * @param \Foment\GestioBundle\Entity\Quota $quotes
     */
    public function removeQuote(\Foment\GestioBundle\Entity\Quota $quotes)
    {
        $this->quotes->removeElement($quotes);
    }

    /**
     * Get quotes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getQuotes()
    {
        return $this->quotes;
    }

    /**
     * Add membres
     *
     * @param \Foment\GestioBundle\Entity\Membre $membres
     * @return Seccio
     */
    public function addMembre(\Foment\GestioBundle\Entity\Membre $membres)
    {
    	$this->membres->add($membres);
        //$this->membres[] = $membres;

        return $this;
    }

    
    /**
     * Remove membres
     *
     * @param \Foment\GestioBundle\Entity\Membre $membres
     */
    public function removeMembre(\Foment\GestioBundle\Entity\Membre $membres)
    {
        $this->membres->removeElement($membres);
    }

    /**
     * Get membres
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMembres()
    {
        return $this->membres;
    }
}
