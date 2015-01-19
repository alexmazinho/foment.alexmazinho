<?php 
// src/Foment/GestioBundle/Entity/Periode.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
 * @ORM\Table(name="periodes")
 */

/*
 * Periodes de facturació. Agrupen facturacions de rebuts. 
 * Els periodes sempre es troben dins un mateix any
 * Tots els períodes d'un any han d'incloure tots els dies i no es poden encavalcar
 * Així la suma dels percentatges ha de quadrar al 100% cada any 
 */
class Periode
{
	
	protected static $estats; // Veure getEstats()
	
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=80, nullable=false)
     *
     */
    protected $titol; // p.e. "2n Semestre 2014"
    
    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $anyperiode;
    
    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $semestre;  // p.e '1' o '2' 
    
    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $diainici;
    
    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $mesinici;
    
    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $diafinal;
    
    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $mesfinal;

    /**
     * @ORM\Column(type="decimal", precision=3, scale=2)
     */
    protected $percentfragmentgeneral;
   
    /**
     * @ORM\Column(type="decimal", precision=3, scale=2)
     */
    protected $percentfragmentseccions;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $datarebuts;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dataemissio;
  
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dataconsolidacio;
    
    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $estat;
    
    /**
     * @ORM\OneToMany(targetEntity="Facturacio", mappedBy="periode")
     */
    protected $facturacions;
    
	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 */
	protected $dataentrada;
	
	/**
	 * @ORM\Column(type="datetime", nullable=false)
	 */
	protected $datamodificacio;
	
	/**
	 * @ORM\OneToMany(targetEntity="Rebut", mappedBy="periodenf")
	 */
	protected $rebutsnofacturats;
	
	/**
	 * Array possibles estats del període
	 */
	public static function getEstats() {
		if (self::$estats == null) {
			self::$estats = array(
					'0' => 'pendent',		// Estat inicial, el periode no existeix o no s'ha generat els rebuts
					'1' => 'rebuts creats',	// Encara no s'han imprés els rebuts
					'2' => 'rebuts facturats', // S'ha afegit els rebuts a una facturació 
					'3' => 'consolidats'	// Període tancat, tots els rebuts validats (cobrats o perduts)
			);
		}
		return self::$estats;
	}
    /**
     * Constructor. 
     */
    public function __construct($anyperiode, $semestre = 1)
    {
    	if (! is_numeric($anyperiode) || $anyperiode < 2010 ) $anyperiode = date('Y');
    	
    	
    	$this->id = 0;
    	$this->estat = 0;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	
    	// Inicialitzar amb valors genèrics
    	$this->anyperiode = $anyperiode;
    	$this->semestre = $semestre;
    	if ($semestre == 1) {
    		$this->titol = UtilsController::PREFIX_TITOL_SEMESTRE_1 . $anyperiode;
    		$this->diainici = UtilsController::DIA_INICI_SEMESTRE_1; 
    		$this->mesinici = UtilsController::MES_INICI_SEMESTRE_1;
    		$this->diafinal = UtilsController::DIA_FINAL_SEMESTRE_1;
    		$this->mesfinal = UtilsController::MES_FINAL_SEMESTRE_1;
    		$this->percentfragmentgeneral = UtilsController::PERCENT_FRA_GRAL_SEMESTRE_1;
    		$this->percentfragmentseccions = UtilsController::PERCENT_FRA_SECCIONS_SEMESTRE_1;
    	}
    	if ($semestre == 2) {
    		$this->titol = UtilsController::PREFIX_TITOL_SEMESTRE_2 . $anyperiode;
    		$this->diainici = UtilsController::DIA_INICI_SEMESTRE_2; 
    		$this->mesinici = UtilsController::MES_INICI_SEMESTRE_2;
    		$this->diafinal = UtilsController::DIA_FINAL_SEMESTRE_2;
    		$this->mesfinal = UtilsController::MES_FINAL_SEMESTRE_2;
    		$this->percentfragmentgeneral = UtilsController::PERCENT_FRA_GRAL_SEMESTRE_2;
    		$this->percentfragmentseccions = UtilsController::PERCENT_FRA_SECCIONS_SEMESTRE_2;
    	}
    	
        $this->facturacions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->rebutsnofacturats = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get datainici
     *
     * @return string
     */
    public function getDatainici()
    {
    	return \DateTime::createFromFormat('d/m/Y', $this->diainici.'/'.$this->mesinici.'/'.$this->anyperiode); 
    }
    
    /**
     * Get datafinal
     *
     * @return string
     */
    public function getDatafinal()
    {
    	return \DateTime::createFromFormat('d/m/Y', $this->diafinal.'/'.$this->mesfinal.'/'.$this->anyperiode);  
    }
    
    /**
     * Get estat periode as string
     *
     * @return string
     */
    public function getEstatPeriode()
    {
    	$aux = self::getEstats();
    	 
    	return $aux[$this->estat];
    }
    
    /**
     * Get info estat periode amb date as string
     *
     * @return string
     */
    public function getEstatPeriodeExtended()
    {
		$aux = self::getEstats();
    	
    	$desc = $aux[$this->estat];
    	
    	switch ($this->estat) {
    		case 0:  // pendent
    			$desc = ' des del '.($this->dataentrada!=null?$this->dataentrada->format('j/n/Y'):'??');
    			break;
    		case 1:  // rebuts creats
    			$desc = ' generats el '.($this->datarebuts!=null?$this->datarebuts->format('j/n/Y'):'??');
    			break; 
    		case 2:  // rebuts emessos
    			$desc = ' facturats el '.($this->dataemissio!=null?$this->dataemissio->format('j/n/Y'):'??');
    			break;
    		case 3:  // periode consolidat
    			$desc = ' consolidats des del '.($this->dataconsolidacio!=null?$this->dataconsolidacio->format('j/n/Y'):'??');
    			break;
    	}
    	
    	return $desc;
    }
    
    /**
     * Returns true si hi ha rebuts pendents per facturar. Algun que no sigui finestreta
     *
     * @return boolean
     */
    public function pendents() {
    	foreach ($this->rebutsnofacturats as $rebut)  {
    		if ($rebut->esFacturable()) return true;
    	}
    	return false;
    }
    
    /**
     * Returns rebut pendent from persona
     *
     * @param \Foment\GestioBundle\Entity\Persona $persona
     * @return \Foment\GestioBundle\Entity\Rebut
     */
    public function getRebutPendentByPersonaDeutora($persona) {
    	foreach ($this->rebutsnofacturats as $rebut)  {
    		if ($rebut->getDeutor() == $persona && $rebut->esModificable()) return $rebut;
    	}
    	return null;
    }
    
    /**
     * Afegeix els rebuts pendents que cal domiciliar a la facturació
     *
     * @param \Foment\GestioBundle\Entity\Facturacio $facturacio
     */
    public function facturarPendents($facturacio) {
    	
    	$facturats = array();
    	foreach ($this->rebutsnofacturats as $rebut)  {
    		if ($rebut->esFacturable()) {
    			$facturacio->addRebut($rebut);
    			$rebut->setFacturacio($facturacio);
    			$rebut->setPeriodenf(null);
    			$facturats[] = $rebut; // guardar per treure de la collecció
    		}
    	}
    	foreach ($facturats as $rebut) $this->rebutsnofacturats->removeElement($rebut);
    }

    /**
     * Get info rebuts generats as array
     *
     * @return array
     */
    public function getInfoRebuts()
    {
    	$info = array(0 => Rebut::getArrayInfoRebuts()); // Primer registre els totals, pendents i finestreta inicial 
    	 
    	foreach ($this->rebutsnofacturats as $rebut) $rebut->addInfoRebut($info[0]);
    	
    	$claus = array_keys($info[0]);
    	foreach ($this->facturacions as $facturacio) {
    			$id = $facturacio->getId();
    			$info[$id] = $facturacio->getInforebuts(); // La resta de registres les facturacions
    			
    			foreach ($claus as $clau) {
    				$info[0][$clau]['total'] += $info[$id][$clau]['total'];
    				$info[0][$clau]['import'] += $info[$id][$clau]['import'];
    			}
    	}
    	
    	return $info;
    }
        
    /**
     * Es  pot esborrar el període?
     *
     * @return boolean
     */
    public function esborrable()
    {
    	if ( count($this->facturacions) > 0 ) return false;
    	return $this->estat <= 1; // Rebuts ni facturats ni periode consolidat 
    }
    
    /**
     * Es  pot facturar al període? Afegir rebuts per exemple? Si no esta consolidat
     *
     * @return boolean
     */
    public function facturable()
    {
    	return $this->estat < 3; // Periode no consolidat, encara es poden afegir rebuts
    }
    
    
    /**
     * Estan generats els rebuts del període?
     *
     * @return boolean
     */
    public function rebutsCreats()
    {
    	if ( count($this->facturacions) > 0 ) return true;
    	if ( count($this->rebutsnofacturats) > 0 ) return true;
    	
    	return false; // Rebuts ni facturats ni pendents
    }
    
    /**
     * Get periode description as string
     *
     * @return string
     */
    public function getDescripcioPeriode()
    {
    	$desc = ' Del '.$this->getDatainici()->format('j/n/y').' al '.$this->getDatafinal()->format('j/n/Y').' ';
    	$desc .= ' fraccionament: general '.$this->percentfragmentgeneral*100 . '%  seccions ' . $this->percentfragmentseccions*100 . '%'; 
    	return $desc; 
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
     * Set titol
     *
     * @param string $titol
     * @return Periode
     */
    public function setTitol($titol)
    {
        $this->titol = $titol;

        return $this;
    }

    /**
     * Get titol
     *
     * @return string 
     */
    public function getTitol()
    {
        return $this->titol;
    }

    /**
     * Set anyperiode
     *
     * @param integer $anyperiode
     * @return Periode
     */
    public function setAnyperiode($anyperiode)
    {
        $this->anyperiode = $anyperiode;

        return $this;
    }

    /**
     * Get anyperiode
     *
     * @return integer
     */
    public function getAnyperiode()
    {
    	return $this->anyperiode;
    }
    
    /**
     * Get semestre
     *
     * @return integer 
     */
    public function getSemestre()
    {
        return $this->semestre;
    }

    
    /**
     * Set semestre
     *
     * @param integer $semestre
     * @return Periode
     */
    public function setSemestre($semestre)
    {
    	$this->semestre = $semestre;
    
    	return $this;
    }
    
    
    /**
     * Set diainici
     *
     * @param integer $diainici
     * @return Periode
     */
    public function setDiainici($diainici)
    {
        $this->diainici = $diainici;

        return $this;
    }

    /**
     * Get diainici
     *
     * @return integer 
     */
    public function getDiainici()
    {
        return $this->diainici;
    }

    /**
     * Set mesinici
     *
     * @param integer $mesinici
     * @return Periode
     */
    public function setMesinici($mesinici)
    {
        $this->mesinici = $mesinici;

        return $this;
    }

    /**
     * Get mesinici
     *
     * @return integer 
     */
    public function getMesinici()
    {
        return $this->mesinici;
    }

    /**
     * Set diafinal
     *
     * @param integer $diafinal
     * @return Periode
     */
    public function setDiafinal($diafinal)
    {
        $this->diafinal = $diafinal;

        return $this;
    }

    /**
     * Get diafinal
     *
     * @return integer 
     */
    public function getDiafinal()
    {
        return $this->diafinal;
    }

    /**
     * Set mesfinal
     *
     * @param integer $mesfinal
     * @return Periode
     */
    public function setMesfinal($mesfinal)
    {
        $this->mesfinal = $mesfinal;

        return $this;
    }

    /**
     * Get mesfinal
     *
     * @return integer 
     */
    public function getMesfinal()
    {
        return $this->mesfinal;
    }

    /**
     * Set diaemissio
     *
     * @param integer $diaemissio
     * @return Periode
     */
    public function setDiaemissio($diaemissio)
    {
        $this->diaemissio = $diaemissio;

        return $this;
    }

    /**
     * Get diaemissio
     *
     * @return integer 
     */
    public function getDiaemissio()
    {
        return $this->diaemissio;
    }

    /**
     * Set mesemissio
     *
     * @param integer $mesemissio
     * @return Periode
     */
    public function setMesemissio($mesemissio)
    {
        $this->mesemissio = $mesemissio;

        return $this;
    }

    /**
     * Get mesemissio
     *
     * @return integer 
     */
    public function getMesemissio()
    {
        return $this->mesemissio;
    }

    /**
     * Set percentfragmentgeneral
     *
     * @param string $percentfragmentgeneral
     * @return Periode
     */
    public function setPercentfragmentgeneral($percentfragmentgeneral)
    {
        $this->percentfragmentgeneral = $percentfragmentgeneral;

        return $this;
    }

    /**
     * Get percentfragmentgeneral
     *
     * @return string 
     */
    public function getPercentfragmentgeneral()
    {
        return $this->percentfragmentgeneral;
    }

    /**
     * Set percentfragmentseccions
     *
     * @param string $percentfragmentseccions
     * @return Periode
     */
    public function setPercentfragmentseccions($percentfragmentseccions)
    {
        $this->percentfragmentseccions = $percentfragmentseccions;

        return $this;
    }

    /**
     * Get percentfragmentseccions
     *
     * @return string 
     */
    public function getPercentfragmentseccions()
    {
        return $this->percentfragmentseccions;
    }

    /**
     * Set dataemissio
     *
     * @param \DateTime $dataemissio
     * @return Periode
     */
    public function setDataemissio($dataemissio)
    {
        $this->dataemissio = $dataemissio;

        return $this;
    }

    /**
     * Get dataemissio
     *
     * @return \DateTime 
     */
    public function getDataemissio()
    {
        return $this->dataemissio;
    }

    /**
     * Set dataconsolidacio
     *
     * @param \DateTime $dataconsolidacio
     * @return Periode
     */
    public function setDataconsolidacio($dataconsolidacio)
    {
    	$this->dataconsolidacio = $dataconsolidacio;
    
    	return $this;
    }
    
    /**
     * Get dataconsolidacio
     *
     * @return \DateTime
     */
    public function getDataconsolidacio()
    {
    	return $this->dataconsolidacio;
    }
    
    
    /**
     * Set datarebuts
     *
     * @param \DateTime $datarebuts
     * @return Periode
     */
    public function setDatarebuts($datarebuts)
    {
        $this->datarebuts = $datarebuts;

        return $this;
    }

    /**
     * Get datarebuts
     *
     * @return \DateTime 
     */
    public function getDatarebuts()
    {
        return $this->datarebuts;
    }

    /**
     * Set estat
     *
     * @param integer $estat
     * @return Periode
     */
    public function setEstat($estat)
    {
        $this->estat = $estat;

        return $this;
    }

    /**
     * Get estat
     *
     * @return integer 
     */
    public function getEstat()
    {
        return $this->estat;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Periode
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
     * @return Periode
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
     * Get facturacions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFacturacions()
    {
    	return $this->facturacions;
    }
    
    /**
     * Add rebut
     *
     * @param \Foment\GestioBundle\Entity\Rebut $rebut
     * @return Facturacio
     */
    public function addRebutnofacturat(\Foment\GestioBundle\Entity\Rebut $rebut)
    {
    	$this->rebutsnofacturats->add($rebut);
    	//$this->rebuts[] = $rebuts;
    
    	return $this;
    }
    
    /**
     * Remove rebut
     *
     * @param \Foment\GestioBundle\Entity\Rebut $rebut
     */
    public function removeRebutnofacturat(\Foment\GestioBundle\Entity\Rebut $rebut)
    {
    	$this->rebutsnofacturats->removeElement($rebut);
    }
    
    /**
     * Get rebuts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRebutsnofacturats()
    {
    	return $this->rebutsnofacturats;
    }
}
