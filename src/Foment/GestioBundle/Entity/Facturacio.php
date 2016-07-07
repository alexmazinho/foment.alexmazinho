<?php 
// src/Foment/GestioBundle/Entity/Facturacio.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity 
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="tipus", type="string", length=1)
 * @ORM\DiscriminatorMap({"A" = "FacturacioActivitat", "S" = "FacturacioSeccio"}) 
 * @ORM\Table(name="facturacions")
 */

/*
 * Facturació. Agrupació de rebuts i centralitzen l'enviament de domiciliacions.
 * Una facturació correspon a un període concret i agrupen múltiples rebuts. 
 * Quan es generen facturacions es comprova els rebuts que cal domiciliar i encara no estan associats a cap facturació
 * i depenent de la persona (Domicilia o no) es crea la facturació corresponents i s'associen els rebuts
 * 
 * Si les facturacions s'associen a un periode són de seccions que facturen per semestres
 * La resta són d'activitats o seccions que nofacturen per semestres
 */
class Facturacio
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     *
     */
    protected $descripcio; // p.e. "Facturació $id Banc" o "Facturació $id Finestreta" 
    
    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $tipuspagament;  // Idem rebut. Tots els rebuts seran del mateix tipus
    
    /**
     * @ORM\OneToMany(targetEntity="Rebut", mappedBy="facturacio")
     */
    protected $rebuts;
    
    /**
     * @ORM\Column(type="date", nullable=false)
     */
    protected $datafacturacio;
    
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
	public function __construct($datafacturacio, $tipuspagament, $desc = '')
	{
		$this->id = 0;
		$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->descripcio = $desc;
    	$this->tipuspagament = $tipuspagament;
    	
    	if ($datafacturacio == null) $this->datafacturacio = new \DateTime();
    	else $this->datafacturacio = $datafacturacio;
    	
    	$this->rebuts = new \Doctrine\Common\Collections\ArrayCollection();
	}
    
    public function __clone() {
    	$this->id = null;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	 
    	$this->rebuts = new \Doctrine\Common\Collections\ArrayCollection(); // Init rebuts
    }
    
    /**
     * És baixa? false
     *
     * @return boolean
     */
    public function esBaixa() { return $this->databaixa != null; }
    
    /**
     * es esborrable?. Només si cap rebut pagat
     *
     * @return boolean
     */
    public function esEsborrable()
    {
    	foreach ($this->rebuts as $rebut) {
   			if (!$rebut->esEsborrable() || $rebut->cobrat()) return false;
    	}
    	return true;
    }
    
    /**
     * baixa de la facturació i els rebuts associats
     *
     */
    public function baixa()
    {
    	if ($this->esEsborrable()) { 

			$this->databaixa = new \DateTime();
			$this->datamodificacio = new \DateTime();
			
	    	foreach ($this->rebuts as $rebut) $rebut->baixa();
    	}
    }
    
    
    /**
     * Get total rebuts vigents
     *
     * @return int
     */
    public function getTotalrebuts()
    {
    	$total = 0;
    	foreach ($this->rebuts as $rebut) {
    		if (!$rebut->anulat())  $total++;
    	}
    	return $total;
    }
    
    /**
     * Get info rebuts generats as array
     *
     * @return array
     */
    public function getInforebuts()
    {
    	$info = Rebut::getArrayInfoRebuts();
    	 
    	foreach ($this->rebuts as $rebut) $rebut->addInfoRebut($info);
    	 
    	return $info;
    }
    
    /**
     * Do nothing. Per sobreescriure
     *
     * @return Facturacio
     */
    public function removeProfessorById($professorId)
    {
    	return $this;
    }
    
    
    /**
     * Get descripcio amb tipus de pagament
     * Per sobreescriure
     *
     * @return string
     */
    public function getDescripcioCompleta()
    {
    	return '';
    }
    
    /**
     * Get docents actius i ordenats. Per defecte array buit
     * Per sobreescriure
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocentsOrdenats()
    {
    	return array();
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
     * Set descripcio
     *
     * @param string $descripcio
     * @return Facturacio
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

    /**
     * Set tipuspagament
     *
     * @param integer $tipuspagament
     * @return Facturacio
     */
    public function setTipuspagament($tipuspagament)
    {
    	$this->tipuspagament = $tipuspagament;
    
    	return $this;
    }
    
    /**
     * Get tipuspagament
     *
     * @return integer
     */
    public function getTipuspagament()
    {
    	return $this->tipuspagament;
    }

    /**
     * Set datafacturacio
     *
     * @param \DateTime $datafacturacio
     * @return Facturacio
     */
    public function setDatafacturacio($datafacturacio)
    {
    	$this->datafacturacio = $datafacturacio;
    
    	return $this;
    }
    
    /**
     * Get datafacturacio
     *
     * @return \DateTime
     */
    public function getDatafacturacio()
    {
    	return $this->datafacturacio;
    }
    
    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Facturacio
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
     * @return Facturacio
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
     * @return Rebut
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
     * Add rebuts
     *
     * @param \Foment\GestioBundle\Entity\Rebut $rebut
     * @return Facturacio
     */
    public function addRebut(\Foment\GestioBundle\Entity\Rebut $rebut)
    {
    	$this->rebuts->add($rebut);
    	$rebut->setFacturacio($this);
        return $this;
    }

    /**
     * Remove rebuts
     *
     * @param \Foment\GestioBundle\Entity\Rebut $rebut
     */
    public function removeRebut(\Foment\GestioBundle\Entity\Rebut $rebut)
    {
        $this->rebuts->removeElement($rebut);
        $rebut->setFacturacio(null);
    }

    /**
     * Get rebuts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRebuts()
    {
        return $this->rebuts;
    }
}
