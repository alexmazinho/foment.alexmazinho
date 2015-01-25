<?php 
// src/Foment/GestioBundle/Entity/ActivitatAnual.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
 * @ORM\Table(name="activitatsanuals")
 */
// When generetes inheritance entity comment extends ...
// http://docs.doctrine-project.org/en/latest/reference/faq.html#can-i-use-inheritance-with-doctrine-2
class ActivitatAnual extends Activitat 
{
	/**
	 * @ORM\Id
	 * @ORM\OneToOne(targetEntity="Activitat", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="id", referencedColumnName="id")
	 */
    protected $id;

    /**
     * @ORM\Column(type="date", nullable=false)
     * @Assert\NotBlank(
     * 	message = "Falta la data"
     * )
     * @Assert\DateTime(message="Data incorrecte.")
     */
    protected $datainici;
    
    
    /**
     * @ORM\Column(type="date", nullable=false)
     * @Assert\NotBlank(
     * 	message = "Falta la data"
     * )
     * @Assert\DateTime(message="Data incorrecte.")
     */
    protected $datafinal;
    
    
    /**
     * @ORM\OneToMany(targetEntity="Docencia", mappedBy="activitat")
     */
    protected $docents;

    /********************** programacions codificades text ****************************/ 
    /**
	 * @ORM\Column(type="text", nullable=true)
	 */
    protected $setmanal; // dl|dm|dx|dj|dv hora i durada  ==> (Amb constants UtilsController) 'diasemana+hh:ii+durada;diasemana+hh:ii+durada...
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $mensual; // Primer|segon|tercer|quart dl|dm|dx|dj|dv hora i durada 
    					// ==> (Amb constants UtilsController) 'diames+diasemana+hh:ii+durada;diames+diasemana+hh:ii+durada;...
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $persessions; // Dia, hora i durada => 'dd/mm/yyyy hh:ii+durada;dd/mm/yyyy hh:ii+durada;dd/mm/yyyy hh:ii+durada;...'  
    /********************** programacions codificades text ****************************/
    /**
     * @ORM\OneToMany(targetEntity="Sessio", mappedBy="activitat")
     */
    protected $calendari;
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	parent::__construct();
        $this->docents = new \Doctrine\Common\Collections\ArrayCollection();
        $this->calendari = new \Doctrine\Common\Collections\ArrayCollection();

        // Dates inici final per defecte curs escolar
        $this->datainici =  \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_INICI_CURS_SETEMBRE. date('Y') );
        $this->datafinal =  \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_FINAL_CURS_JUNY. (date('Y') +1));
        
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
    		return ($a->getPersona()->getCognoms() < $b->getPersona()->getCognoms())? -1:1;;
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
    		if ($docent->getPersona()->getId() == $professorId) $docent->setDatabaixa(new \DateTime());
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
    		//  diasemana+hh:ii+durada
    		$programaArray = explode('+',$progSetmanal);
    		if (count($programaArray) == 3) {
    			if ($formatcomu == true) {
    				$info[] = array('original' => $progSetmanal,  
    						'info' => 'Setmanalment cada '.UtilsController::getDiaSetmana($programaArray[0]),
    						'hora' => $programaArray[1], 'durada' => $programaArray[2].($programaArray[2]==1?' hora':' hores'));
    			} else {
    				$info[] = array('original' => $progSetmanal,
    						'diasetmana' => $programaArray[0],
    						'hora' => $programaArray[1], 'durada' => $programaArray[2]);
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
    					UtilsController::INDEX_DILLUNS => array('hora' => null, 'durada' => null),
						UtilsController::INDEX_DIMARTS => array('hora' => null, 'durada' => null),
						UtilsController::INDEX_DIMECRES => array('hora' => null, 'durada' => null),
						UtilsController::INDEX_DIJOUS => array('hora' => null, 'durada' => null),
						UtilsController::INDEX_DIVENDRES => array('hora' => null, 'durada' => null));


    	$setmanal = $this->getInfoSetmanal(false);
    	foreach ($setmanal as $dia) {
    		$setmanaCompleta[$dia['diasetmana']]['hora'] = \DateTime::createFromFormat('H:i', $dia['hora']);
    		$setmanaCompleta[$dia['diasetmana']]['durada'] = $dia['durada'];
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
    		//  diames+diasemana+hh:ii+durada
    		$programaArray = explode('+',$progMensual);
    		if (count($programaArray) == 4) {
    			if ($formatcomu == true) {
    				$info[] = array('original' => $progMensual,
    								'info' => ucfirst(UtilsController::getDiaDelMes($programaArray[0])).' '.UtilsController::getDiaSetmana($programaArray[1]).' del mes', 
    								'hora' => $programaArray[2], 'durada' => $programaArray[3].($programaArray[3]==1?' hora':' hores'));
    			} else {
    				$info[] = array('original' => $progMensual,
    								'diadelmes' => $programaArray[0], 'diasetmana' => $programaArray[1], 
    								'hora' => $programaArray[2], 'durada' => $programaArray[3] );
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
    		//  dd/mm/yyyy hh:ii+durada
    		$programaArray = explode('+',$progSessions);
    		if (count($programaArray) == 2) {
    			if ($formatcomu == true) {
    				$dataSessio = \DateTime::createFromFormat('d/m/Y H:i', $programaArray[0]);
    				$info[] = array('original' => $progSessions, 'info' => 'El dia '.$dataSessio->format('d/m/Y'), 
    								'hora' => $dataSessio->format('H:i'), 'durada' => $programaArray[1].($programaArray[1]==1?' hora':' hores'));
    			} else {
    				$info[] = array('original' => $progSessions, 'diahora' => $programaArray[0], 
    								'durada' => $programaArray[1]);
    			}
    			
    		}
    	}
    	return $info;
    }
    
    /**
     * Get 'anual' as string.
     *
     * @return string
     */
    public function getTipus()
    {
    	return parent::TIPUS_ANUAL;
    }
    
    /**
     * Get info del calendari de l'activitat as string
     *
     * @return string
     */
    public function getInfoCalendari()
    {
    	return 'activitat anual (calendari pendent)';
    }
    
    /**
     * Get data inicial
     *
     * @return \DateTime
     */
    public function getDataactivitat()
    {
    	return $this->datainici;
    }
    
    /**
     * Set id
     *
     * @param \Foment\GestioBundle\Entity\Activitat $id
     * @return ActivitatAnual
     */
    public function setId(\Foment\GestioBundle\Entity\Activitat $id)
    {
    	$this->id = $id;
    
    	return $this;
    }
    
    /**
     * Get id
     *
     * @return \Foment\GestioBundle\Entity\Activitat
     */
    public function getId()
    {
    	return $this->id;
    }
    
    /**
     * Set datainici
     *
     * @param \DateTime $datainici
     * @return ActivitatAnual
     */
    public function setDatainici($datainici)
    {
    	$this->datainici = $datainici;
    
    	return $this;
    }
    
    /**
     * Get datainici
     *
     * @return \DateTime
     */
    public function getDatainici()
    {
    	return $this->datainici;
    }
    
    /**
     * Set datafinal
     *
     * @param \DateTime $datafinal
     * @return ActivitatAnual
     */
    public function setDatafinal($datafinal)
    {
    	$this->datafinal = $datafinal;
    
    	return $this;
    }
    
    /**
     * Get datafinal
     *
     * @return \DateTime
     */
    public function getDatafinal()
    {
    	return $this->datafinal;
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
