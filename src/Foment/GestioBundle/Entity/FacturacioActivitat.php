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
    
    /********************** programacions codificades text ****************************/
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $setmanal; // dl|dm|dx|dj|dv hora i hh:ii  ==> (Amb constants UtilsController) 'diasemana+hh:ii+hh:ii;diasemana+hh:ii+hh:ii...
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $mensual; // Primer|segon|tercer|quart dl|dm|dx|dj|dv hora i hora final
    // ==> (Amb constants UtilsController) 'diames+diasemana+hh:ii+hh:ii;diames+diasemana+hh:ii+hh:ii;...
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $persessions; // Dia, hora i hh:ii => 'dd/mm/yyyy hh:ii+hh:ii;dd/mm/yyyy hh:ii+hh:ii;dd/mm/yyyy hh:ii+hh:ii;...'
    /********************** programacions codificades text ****************************/
    /**
     * @ORM\OneToMany(targetEntity="Sessio", mappedBy="activitat")
     */
    protected $calendari;
    
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
    	$this->calendari = new \Doctrine\Common\Collections\ArrayCollection();
	}
    
	public function __clone() {
		parent::__clone();
	
		$this->calendari = new \Doctrine\Common\Collections\ArrayCollection(); // Init calendari
		 
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
	public function getDocentsActius()
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
	 * Get import total docents actius
	 *
	 * @return int
	 */
	public function getImportDocents()
	{
		$actius = $this->getDocentsActius();
		$total = 0;
		foreach ($actius as $docent) $total += $docent->getImport();
		 
		return $total;
	}
	


	/**
	 * Remove docencia professor by id
	 *
	 * @return ActivitatAnual
	 */
	public function removeProfessorById($professorId)
	{
		foreach ($this->docents as $docent) {
			if ($docent->getProveidor()->getId() == $professorId) $docent->setDatabaixa(new \DateTime());
		}
	
		return $this;
	}
	
	/**
	 * Get hores total docents actius
	 *
	 * @return int
	 */
	public function getHoresDocents()
	{
		$actius = $this->getDocentsActius();
		$total = 0;
		foreach ($actius as $docent) {
			if ($docent->getTotalhores() != null) $total += $docent->getTotalhores();
		}
		return $total;
	}
	
	/**
	 * Get info programacio setmanal
	 *
	 * @return string
	 */
	public function getInfoSetmanal($formatcomu = true)
	{
		$setmanalArray = array();
		if ($this->setmanal != '') $setmanalArray = explode(';',$this->setmanal); // array pogramacions
	
		$info = array();
		foreach ($setmanalArray as $progSetmanal) {
			//  diasemana+hh:ii+hh:ii
			$programaArray = explode('+',$progSetmanal);
			if (count($programaArray) == 3) {
				if ($formatcomu == true) {
					$info[] = array('original' => $progSetmanal,
							'info' => 'Setmanalment cada '.UtilsController::getDiaSetmana($programaArray[0]),
							'hora' => $programaArray[1], 'final' => $programaArray[2]);
				} else {
					$info[] = array('original' => $progSetmanal,
							'diasetmana' => $programaArray[0],
							'hora' => $programaArray[1], 'final' => $programaArray[2]);
				}
			}
		}
		return $info;
	}
	
	/**
	 * Get dies programacio setmanal
	 *
	 * @return array
	 */
	public function getDiesSetmanal()
	{
		$setmanal = $this->getInfoSetmanal(false);
		$dies = array();
		foreach ($setmanal as $dia) {
			$dies[] = $dia['diasetmana'];
		}
		return $dies;
	}
	
	/**
	 * Get data dies programacio setmanal
	 *
	 * @return array
	 */
	public function getDadesDiesSetmanal()
	{
		$setmanaCompleta = array(
				UtilsController::INDEX_DILLUNS => array('hora' => null, 'final' => null),
				UtilsController::INDEX_DIMARTS => array('hora' => null, 'final' => null),
				UtilsController::INDEX_DIMECRES => array('hora' => null, 'final' => null),
				UtilsController::INDEX_DIJOUS => array('hora' => null, 'final' => null),
				UtilsController::INDEX_DIVENDRES => array('hora' => null, 'final' => null));
	
	
		$setmanal = $this->getInfoSetmanal(false);
		foreach ($setmanal as $dia) {
			$setmanaCompleta[$dia['diasetmana']]['hora'] = \DateTime::createFromFormat('H:i', $dia['hora']);
			$setmanaCompleta[$dia['diasetmana']]['final'] = \DateTime::createFromFormat('H:i', $dia['final']);
		}
		return $setmanaCompleta;
	}
	
	/**
	 * Get info programacio mensual
	 *
	 * @return string
	 */
	public function getInfoMensual($formatcomu = true)
	{
		$mensualArray = array();
		if ($this->mensual != '') $mensualArray = explode(';',$this->mensual); // array pogramacions
	
		$info = array();
		foreach ($mensualArray as $progMensual) {
			//  diames+diasemana+hh:ii+hh:ii
			$programaArray = explode('+',$progMensual);
			if (count($programaArray) == 4) {
				if ($formatcomu == true) {
					$info[] = array('original' => $progMensual,
							'info' => ucfirst(UtilsController::getDiaDelMes($programaArray[0])).' '.UtilsController::getDiaSetmana($programaArray[1]).' del mes',
							'hora' => $programaArray[2], 'final' => $programaArray[3] );
				} else {
					$info[] = array('original' => $progMensual,
							'diadelmes' => $programaArray[0], 'diasetmana' => $programaArray[1],
							'hora' => $programaArray[2], 'final' => $programaArray[3] );
				}
			}
		}
		return $info;
	}
	
	/**
	 * Get info programacio per sessions
	 *
	 * @return string
	 */
	public function getInfoPersessions($formatcomu = true)
	{
		$persessionsArray = array();
		if ($this->persessions != '') $persessionsArray = explode(';',$this->persessions); // array pogramacions
	
		$info = array();
		foreach ($persessionsArray as $progSessions) {
			//  dd/mm/yyyy hh:ii+hh:ii
			$programaArray = explode('+',$progSessions);
			if (count($programaArray) == 2) {
				if ($formatcomu == true) {
					$dataSessio = \DateTime::createFromFormat('d/m/Y H:i', $programaArray[0]);
					$horaFinal = \DateTime::createFromFormat('H:i', $programaArray[1]);
					$info[] = array('original' => $progSessions, 'info' => 'El dia '.$dataSessio->format('d/m/Y'),
							'hora' => $dataSessio->format('H:i'), 'final' => $horaFinal->format('H:i') );
				} else {
					$info[] = array('original' => $progSessions, 'diahora' => $programaArray[0],
							'final' => $programaArray[1]);
				}
				 
			}
		}
		return $info;
	}
	
	/**
	 * Get info del calendari de l'activitat as string
	 *
	 * @return string
	 */
	public function getInfoCalendari()
	{
		$progs = array_merge($this->getInfoSetmanal(), $this->getInfoMensual(), $this->getInfoPersessions());
		 
		$info = '';
		 
		if (count($progs) == 0) return '(calendari pendent)';
		 
		foreach ($progs as $prog) {
			$info .= $prog['info'].'<br/>a les '.$prog['hora'].' fins les '. $prog['final'].'<br/>';
		}
		 
		return $info;
	}
	
    /**
     * Get descripcio amb tipus de pagament
     *
     * @return string
     */
    public function getDescripcioCompleta()
    {
    	return $this->activitat->getDescripcio().' '.$this->activitat->getCurs().' '.$this->descripcio; 
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
     * Set setmanal
     *
     * @param string $setmanal
     * @return ActivitatAnual
     */
    public function setSetmanal($setmanal)
    {
    	$this->setmanal = $setmanal;
    
    	return $this;
    }
    
    /**
     * Get setmanal
     *
     * @return string
     */
    public function getSetmanal()
    {
    	return $this->setmanal;
    }
    
    /**
     * Set mensual
     *
     * @param string $mensual
     * @return ActivitatAnual
     */
    public function setMensual($mensual)
    {
    	$this->mensual = $mensual;
    
    	return $this;
    }
    
    /**
     * Get mensual
     *
     * @return string
     */
    public function getMensual()
    {
    	return $this->mensual;
    }
    
    /**
     * Set persessions
     *
     * @param string $persessions
     * @return ActivitatAnual
     */
    public function setPersessions($persessions)
    {
    	$this->persessions = $persessions;
    
    	return $this;
    }
    
    /**
     * Get persessions
     *
     * @return string
     */
    public function getPersessions()
    {
    	return $this->persessions;
    }
    
    /**
     * Add docent
     *
     * @param \Foment\GestioBundle\Entity\Docencia $docent
     * @return ActivitatAnual
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
    
    /**
     * Add calendari
     *
     * @param \Foment\GestioBundle\Entity\Sessio $calendari
     * @return ActivitatAnual
     */
    public function addCalendari(\Foment\GestioBundle\Entity\Sessio $calendari)
    {
    	$this->calendari[] = $calendari;
    
    	return $this;
    }
    
    /**
     * Remove calendari
     *
     * @param \Foment\GestioBundle\Entity\Sessio $calendari
     */
    public function removeCalendari(\Foment\GestioBundle\Entity\Sessio $calendari)
    {
    	$this->calendari->removeElement($calendari);
    }
    
    /**
     * Get calendari
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCalendari()
    {
    	return $this->calendari;
    }
}
