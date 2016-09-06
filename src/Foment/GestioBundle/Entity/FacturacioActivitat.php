<?php 
// src/Foment/GestioBundle/Entity/FacturacioActivitat.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
 * @ORM\Table(name="facturacionsactivitats")
 */

/*
 * FacturacióActivitat. Agrupació de rebuts del cursos i tallers 
 */
// http://docs.doctrine-project.org/en/latest/reference/faq.html#can-i-use-inheritance-with-doctrine-2
class FacturacioActivitat extends Facturacio 
{
	/**
	 * @ORM\Id
	 * @ORM\OneToOne(targetEntity="Facturacio", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="id", referencedColumnName="id")
	 */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Activitat", inversedBy="facturacions")
     * @ORM\JoinColumn(name="activitat", referencedColumnName="id")
     */
    protected $activitat; // FK taula activitats
    
    /**
     * @ORM\Column(type="decimal", precision=6, scale=2, nullable=true)
     */
    protected $importactivitat; // Parcial o total  de l'activitat
    
    /**
     * @ORM\Column(type="decimal", precision=6, scale=2, nullable=true)
     */
    protected $importactivitatnosoci; // Parcial o total  de l'activitat
    
    /**
     * @ORM\OneToMany(targetEntity="Docencia", mappedBy="facturacio")
     */
    protected $docents;
    

	/**
	 * Constructor
	 */
	public function __construct($datafacturacio = null, $desc = '', $activitat = null, $importactivitat = 0, $importactivitatnosoci = 0)
	{
		parent::__construct($datafacturacio, UtilsController::INDEX_FINESTRETA, $desc); // Sempre finestreta
		 
    	$this->activitat = $activitat;
    	if ($activitat != null) $activitat->addFacturacions($this);
    	$this->importactivitat = $importactivitat;
    	$this->importactivitatnosoci = $importactivitatnosoci;
    	
    	$this->docents = new \Doctrine\Common\Collections\ArrayCollection();
    	
	}
    
	public function __clone() {
		parent::__clone();
	
		$docents = $this->getDocents(); // Clone docents
		 
		$this->docents = new \Doctrine\Common\Collections\ArrayCollection();
		 
		if ($docents != null) {
			foreach ($docents as $docent_iter) {
				if (!$docent_iter->esBaixa()) {
					$clonedocent = clone $docent_iter;
	
					$this->addDocent($clonedocent);
					$clonedocent->setActivitat($this);
				}
			}
		}
	}
	
	/**
	 * es esborrable?. Només si cap rebut pagat, ni cap pagament a docent
	 *
	 * @return boolean
	 */
	public function esEsborrable()
	{
		foreach ($this->docents as $docencia) {
			if (!$docencia->esEsborrable()) return false;
		}
		return parent::esEsborrable();  
	}
	
	/**
	 * baixa de la facturació, els rebuts associats i les docències i sessions associades
	 *
	 */
	public function baixa()
	{
		if ($this->esEsborrable()) {
			parent::baixa();  // Rebuts

			// Baixa docències
			foreach ($this->docents as $docencia) {
				if (!$docencia->esBaixa()) $docencia->baixa();
			}		
		}
	}
	
	/**
	 * Get previsió ingressos
	 *
	 * @return decimal
	 */
	public function getPrevisioIngressos()
	{
		return ( $this->importactivitat * $this->getTotalRebutsPerDeutor(true)) + ($this->importactivitatnosoci * $this->getTotalRebutsPerDeutor(false) );
	
	}
	
	/**
	 * Get previsió costos
	 *
	 * @return decimal
	 */
	public function getPrevisioCostos()
	{
		$cost = 0;
		
		foreach ($this->getDocenciesOrdenades() as $docencia) {
			$cost += $docencia->getImport();
		}
		return $cost;
	
	}
	
	/**
	 * Get mínim nombre alumnes necessaris a preu de soci per cubrir despeses
	 *
	 * @return decimal
	 */
	public function getMinimAlumnes()
	{
		if ( abs($this->importactivitat - $this->importactivitatnosoci) < 0.01 ) return 0;
		
		return ceil ( $this->getPrevisioCostos() / min( 1*$this->importactivitat, 1*$this->importactivitatnosoci ) );
	}
	
	/**
	 * Remove docencia by id
	 *
	 * @return Docencia|null si no trobat
	 */
	public function getDocenciaByDocentId($Id)
	{
		foreach ($this->docents as $docencia) {
			if ($docencia->getProveidor()->getId() == $Id) return $docencia;
		}
		return null;
	}
	
	/**
	 * Get docents actius ids
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getDocentsIds()
	{
		$ids = array();
		foreach ($this->docents as $docencia) {
			if (!$docencia->esBaixa()) $ids[] = $docencia->getProveidor()->getId();
		}
	
		return $ids;
	}
	
	/**
	 * Get docencies actives i ordenades per nom proveïdor
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getDocenciesOrdenades()
	{
		$actius = array();
		foreach ($this->docents as $docencia) {
			if (!$docencia->esBaixa()) $actius[] = $docencia;
		}
		 
		usort($actius, function($a, $b) {
			if ($a === $b) {
				return 0;
			}
			return ($a->getProveidor()->getRaosocial() < $b->getProveidor()->getRaosocial())? -1:1;;
		});
			 
		return $actius;
	}
	
	/**
	 * Get Array docencies
	 *
	 * docencies = [ {  facturacio : id,
	 * 					docencies:  ( veure Docencia => getArrayDocencia()) 
	 * 				}]
	 *
	 * @return array
	 */
	public function getArrayDocencies()
	{
		$docencies = array( 'facturacio' => $this->id, 'docencies' => array() );
		foreach ($this->getDocenciesOrdenades() as $docencia) {
			$docencies['docencies'][] = $docencia->getARRAYDocencia();
		}
		return $docencies;
	}
	
	/**
	 * Get import total docents actius
	 *
	 * @return int
	 */
	public function getImportDocents()
	{
		$actius = $this->getDocenciesOrdenades();
		$total = 0;
		foreach ($actius as $docencia) $total += $docencia->getImport();
		 
		return $total;
	}
	


	/**
	 * Get mesos pagaments segons els calendaris de les docències. Format array('any' => yyyy, 'mes' => mm)
	 *
	 * @return array
	 */
	public function getMesosPagaments()
	{
		$mesos = array();
		
		foreach ($this->getDocenciesOrdenades() as $docencia) {
			$mesos = array_merge($mesos, $docencia->getMesosPagaments());
		}
		return $mesos;
	}
	
	
	/**
	 * Get info del calendari de l'activitat as string
	 *
	 * @return string
	 */
	public function getInfoCalendari()
	{
		$info = '';
		
		foreach ($this->docents as $docencia) $info .= $docencia->getInfoCalendari();
		
		if ($info == '') return '(calendari pendent)<br/>';
		
		return $info;
	}
	
	
	/**
	 * Get hores total docents actius
	 *
	 * @return int
	 */
	public function getHoresDocents()
	{
		$actius = $this->getDocenciesOrdenades();
		$total = 0;
		foreach ($actius as $docencia) {
			if ($docencia->getTotalhores() != null) $total += $docencia->getTotalhores();
		}
		return $total;
	}
	
   /**
     * Get descripcio amb tipus de pagament
     *
     * @return string
     */
    public function getDescripcioCompleta()
    {
    	return $this->activitat->getDescripcio().' '.$this->descripcio; 
    }
    
    
    /**
     * Set id
     *
     * @param \Foment\GestioBundle\Entity\Facturacio $id
     * @return FacturacioActivitat
     */
    public function setId(\Foment\GestioBundle\Entity\Facturacio $id)
    {
    	$this->id = $id;
    
    	return $this;
    }
    
    /**
     * Get id
     *
     * @return \Foment\GestioBundle\Entity\Facturacio
     */
    public function getId()
    {
    	return $this->id;
    }
    
	/**
     * Set activitat
     *
     * @param \Foment\GestioBundle\Entity\Activitat $activitat
     * @return Facturacio
     */
    public function setActivitat(\Foment\GestioBundle\Entity\Activitat $activitat = null)
    {
    	$this->activitat = $activitat;
    
    	return $this;
    }
    
    /**
     * Get activitat
     *
     * @return \Foment\GestioBundle\Entity\Activitat
     */
    public function getActivitat()
    {
    	return $this->activitat;
    }
    
    /**
     * Set importactivitat
     *
     * @param string $importactivitat
     * @return Facturacio
     */
    public function setImportactivitat($importactivitat)
    {
    	$this->importactivitat = $importactivitat;
    
    	return $this;
    }
    
    /**
     * Get importactivitat
     *
     * @return string
     */
    public function getImportactivitat()
    {
    	return $this->importactivitat;
    }
    
    /**
     * Set importactivitatnosoci
     *
     * @param string $importactivitatnosoci
     * @return Facturacio
     */
    public function setImportactivitatnosoci($importactivitatnosoci)
    {
    	$this->importactivitatnosoci = $importactivitatnosoci;
    
    	return $this;
    }
    
    /**
     * Get importactivitatnosoci
     *
     * @return string
     */
    public function getImportactivitatnosoci()
    {
    	return $this->importactivitatnosoci;
    }
    
    /**
     * Add docent
     *
     * @param \Foment\GestioBundle\Entity\Docencia $docent
     * @return FacturacioActivitat
     */
    public function addDocent(\Foment\GestioBundle\Entity\Docencia $docent)
    {
    	$this->docents->add($docent);
    	//$this->docents[] = $docent;
    
    	return $this;
    }
    
    /**
     * Remove docent
     *
     * @param \Foment\GestioBundle\Entity\Docencia $docent
     */
    public function removeDocent(\Foment\GestioBundle\Entity\Docencia $docent)
    {
    	$this->docents->removeElement($docent);
    }
    
    /**
     * Get docents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocents()
    {
    	return $this->docents;
    }
}
