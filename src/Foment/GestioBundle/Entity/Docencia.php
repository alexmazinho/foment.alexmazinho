<?php 
// src/Foment/GestioBundle/Entity/Docencia.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
 * @ORM\Table(name="docencies")
 */

class Docencia
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="FacturacioActivitat", inversedBy="docents")
     * @ORM\JoinColumn(name="facturacio", referencedColumnName="id")
     */
    protected $facturacio; // FK taula facturacioactivitats
    
    /**
     * @ORM\ManyToOne(targetEntity="Proveidor", inversedBy="docencies")
     * @ORM\JoinColumn(name="proveidor", referencedColumnName="id")
     */
    protected $proveidor; // FK taula proveidors
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $totalhores;
    
    /**
     * @ORM\Column(type="decimal", precision=6, scale=2, nullable=true)
     */
    protected $preuhora;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datadesde;
    
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
     * @ORM\OneToMany(targetEntity="Sessio", mappedBy="docencia")
     */
    protected $calendari;
    
    /**
     * @ORM\OneToMany(targetEntity="Pagament", mappedBy="docencia")
     */
    protected $pagaments;
    
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
    public function __construct($facturacio, $proveidor, $totalhores, $preuhora, $import)
    {
    	$this->id = 0;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->datadesde = new \DateTime();
    	$this->databaixa = null;
    	
    	$this->facturacio = $facturacio;
    	if ($this->facturacio != null) $this->facturacio->addDocent($this);
    	$this->proveidor = $proveidor;
    	if ($this->proveidor != null) $this->proveidor->addDocencia($this);
    	$this->totalhores = $totalhores;
    	$this->preuhora = $preuhora;
    	$this->import = $import;
    	
    	$this->pagaments = new \Doctrine\Common\Collections\ArrayCollection();
    	$this->calendari = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * És baixa? false
     *
     * @return boolean
     */
    public function esBaixa() { return $this->databaixa != null; }
    
    
    /**
     * Get import
     *
     * @return string
     */
    public function getImport()
    {
    	return $this->preuhora * $this->totalhores;
    }

    /**
     * Get Array docencies
     *
     *		docencies: [{
     *			docent: id,
     *			docentnom: nom,
     *			datadesde: data,
     *			sessions: num
     *			preusessio: import,
     *			horari: [ { tipus: 'setmanal' | 'mensual'| 'sessio'
     *						dades: 'diasemana+hh:ii+hh:ii;...' | 'setmanames+diames+hh:ii+hh:ii' | 'dd/mm/yyyy hh:ii+hh:ii'
     *						info:  desc
	 *						hora:  hora inici
	 *						final: hora final
     *					  }
     *					]
     * 		}]
     *
     * @return array
     */
    public function getArrayDocencia()
    {
    	$horari = $this->getInfoSetmanal(true);
    	$horari = array_merge($horari, $this->getInfoMensual(true));
    	$horari = array_merge($horari, $this->getInfoPersessions(true));
    	$docencia = array(
    		'docent' => $this->getProveidor()->getId(),
    		'docentnom' => $this->getProveidor()->getRaosocial(),
    		'datadesde' => $this->datadesde->format('d/m/Y'),
    		'sessions' => $this->getTotalhores(),
    		'preusessio' => $this->getPreuhora(),
    		'horari'	=> $horari
    	);
    	
    	return $docencia;
    }
    
    /**
     * Pagaments per mes / any
     *
     * @return array
     */
    public function getPagamentsMesAny($anypaga, $mespaga) {
    	$pagamentsMes = array();

    	foreach ($this->pagaments as $pagament) {
    		if (!$pagament->anulat()
    				&& $pagament->getDatapagament()->format('m') == $mespaga
    				&& $pagament->getDatapagament()->format('Y') == $anypaga) $pagamentsMes[] = $pagament;
    	}
    	 
    	return $pagamentsMes;
    }
    
    /**
     * Get mesos pagaments segons el calendari establert. Format array('any' => yyyy, 'mes' => mm)
     *
     * @return array
     */
    public function getMesosPagaments()
    {
    	$mesos = array();
    	foreach ($this->calendari as $sessio) {
    		$data = $sessio->getHorari()->getDatahora();
    			
    		if (!isset($mesos[$data->format('Y')."-".$data->format('m')])) {
    			$mesos[$data->format('Y')."-".$data->format('m')] = array('any' => $data->format('Y'), 'mes' => $data->format('m'));
    		}
    	}
    	return $mesos;
    }
    
    /**
     * Get sessions del calendari de l'activitat as string
     *
     * @return string
     */
    public function getSessionsCalendari()
    {
    	$info = '';
    	foreach ($this->calendari as $sessio) {
    		$data = $sessio->getHorari()->getDatahora();
    		$info[] = 'El dia ' .$data->format('d/m/Y') . ' a les ' . $data->format('H:i');
    	}
    	return implode('\n', $info);
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
    		
    	foreach ($progs as $prog) {
    		$info .= $prog['info'].'<br/>a les '.$prog['hora'].' fins les '. $prog['final'].'<br/>';
    	}
    		
    	return $info;
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
    				$info[] = array(
    						'tipus' => UtilsController::PROG_SETMANAL,
    						'dades' => $progSetmanal,
    						'info' => UtilsController::getDiaSetmana($programaArray[0]),
    						'hora' => $programaArray[1], 
    						'final' => $programaArray[2]);
    			} else {
    				$info[] = array(
    						'tipus' => UtilsController::PROG_SETMANAL,
    						'dades' => $progSetmanal,
    						'diasetmana' => $programaArray[0],
    						'hora' => $programaArray[1], 
    						'final' => $programaArray[2]);
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
    				$info[] = array(
    						'tipus' => UtilsController::PROG_MENSUAL,
    						'dades' => $progMensual,
    						'info' => ucfirst(UtilsController::getDiaDelMes($programaArray[0])).' '.UtilsController::getDiaSetmana($programaArray[1]),
    						'hora' => $programaArray[2], 
    						'final' => $programaArray[3] );
    			} else {
    				$info[] = array(
    						'tipus' => UtilsController::PROG_MENSUAL,
    						'dades' => $progMensual,
    						'diadelmes' => $programaArray[0], 
    						'diasetmana' => $programaArray[1],
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
    				$info[] = array(
    						'tipus' => UtilsController::PROG_SESSIONS,
    						'dades' => $progSessions, 
    						'info' => $dataSessio->format('d/m/Y'),
    						'hora' => $dataSessio->format('H:i'), 
    						'final' => $horaFinal->format('H:i') );
    			} else {
    				$info[] = array(
    						'tipus' => UtilsController::PROG_SESSIONS,
    						'dades' => $progSessions, 
    						'diahora' => $programaArray[0],
    						'final' => $programaArray[1]);
    			}
    				
    		}
    	}
    	return $info;
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
     * Set totalhores
     *
     * @param integer $totalhores
     * @return Docencia
     */
    public function setTotalhores($totalhores)
    {
        $this->totalhores = $totalhores;

        return $this;
    }

    /**
     * Get totalhores
     *
     * @return integer 
     */
    public function getTotalhores()
    {
        return $this->totalhores;
    }

    /**
     * Set preuhora
     *
     * @param string $preuhora
     * @return Docencia
     */
    public function setPreuhora($preuhora)
    {
        $this->preuhora = $preuhora;

        return $this;
    }

    /**
     * Get preuhora
     *
     * @return string 
     */
    public function getPreuhora()
    {
        return $this->preuhora;
    }

    /**
     * Set datadesde
     *
     * @param \DateTime $datadesde
     * @return Docencia
     */
    public function setDatadesde($datadesde)
    {
    	$this->datadesde = $datadesde;
    
    	return $this;
    }
    
    /**
     * Get datadesde
     *
     * @return \DateTime
     */
    public function getDatadesde()
    {
    	return $this->datadesde;
    }
    
    /**
     * Set setmanal
     *
     * @param string $setmanal
     * @return FacturacioActivitat
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
     * @return FacturacioActivitat
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
     * @return FacturacioActivitat
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
     * Add calendari
     *
     * @param \Foment\GestioBundle\Entity\Sessio $calendari
     * @return Docencia
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
    
    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Docencia
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
     * @return Docencia
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
     * @return Docencia
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
     * Set facturacio
     *
     * @param \Foment\GestioBundle\Entity\FacturacioActivitat $facturacio
     * @return Docencia
     */
    public function setFacturacio(\Foment\GestioBundle\Entity\FacturacioActivitat $facturacio = null)
    {
        $this->facturacio = $facturacio;

        return $this;
    }

    /**
     * Get facturacio
     *
     * @return \Foment\GestioBundle\Entity\FacturacioActivitat 
     */
    public function getFacturacio()
    {
        return $this->facturacio;
    }

    /**
     * Set proveidor
     *
     * @param \Foment\GestioBundle\Entity\Persona $proveidor
     * @return Docencia
     */
    public function setProveidor(\Foment\GestioBundle\Entity\Proveidor $proveidor = null)
    {
        $this->proveidor = $proveidor;

        return $this;
    }

    /**
     * Get proveidor
     *
     * @return \Foment\GestioBundle\Entity\Proveidor 
     */
    public function getProveidor()
    {
        return $this->proveidor;
    }
    
    /**
     * Add pagament
     *
     * @param \Foment\GestioBundle\Entity\Pagament $pagament
     * @return Docencia
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
    
}
