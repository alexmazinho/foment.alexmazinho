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
	public function __construct($datafacturacio, $tipuspagament, $desc, $activitat, $importactivitat, $importactivitatnosoci)
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
	 * Get docents actius i ordenats per cognom
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getDocentsOrdenats()
	{
		$actius = array();
		foreach ($this->docents as $docent) {
			if ($docent->getDatabaixa() == null) $actius[] = $docent;
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
	 * 					seq: 0,1,2,3..    // En cas de vàries facturacions noves amb id = 0
	 * 					docencies:  ( veure Docencia => getArrayDocencia()) 
	 * 				}]
	 *
	 * @return array
	 */
	public function getArrayDocencies()
	{
		$docencies = array( 'facturacio' => $this->id, 'seq' => 0, 'docencies' => array() );
		foreach ($this->getDocentsOrdenats() as $docent) {
			$docencies['docencies'][] = $docent->getARRAYDocencia();
		}
error_log( 'getarray => '.print_r ( $docencies, true ));			
		return $docencies;
	}
	
	/**
	 * Get import total docents actius
	 *
	 * @return int
	 */
	public function getImportDocents()
	{
		$actius = $this->getDocentsOrdenats();
		$total = 0;
		foreach ($actius as $docent) $total += $docent->getImport();
		 
		return $total;
	}
	


	/**
	 * Remove docencia professor by id
	 *
	 * @return FacturacioActivitat
	 */
	public function removeProfessorById($professorId)
	{
		foreach ($this->docents as $docent) {
			if ($docent->getProveidor()->getId() == $professorId) $docent->setDatabaixa(new \DateTime());
		}
	
		return $this;
	}

	
	/**
	 * Get mesos pagaments segons els calendaris de les docències. Format array('any' => yyyy, 'mes' => mm)
	 *
	 * @return array
	 */
	public function getMesosPagaments()
	{
		$mesos = array();
		foreach ($this->docents as $docent) {
			$mesos = array_merge($mesos, $docent->getMesosPagaments());
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
		
		foreach ($this->docents as $docent) $info .= $docent->getInfoCalendari();
		
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
		$actius = $this->getDocentsOrdenats();
		$total = 0;
		foreach ($actius as $docent) {
			if ($docent->getTotalhores() != null) $total += $docent->getTotalhores();
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
