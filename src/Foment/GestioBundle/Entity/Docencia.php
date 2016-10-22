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
    protected $setmanal; // dl|dm|dx|dj|dv hora i hh:ii  ==> (Amb constants UtilsController) 'hh:ii+hh:ii+diasemana;hh:ii+hh:ii+diasemana...
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $mensual; // Primer|segon|tercer|quart dl|dm|dx|dj|dv hora i hora final
    // ==> (Amb constants UtilsController) 'hh:ii+hh:ii+diames+diasemana;hh:ii+hh:ii+diames+diasemana;...
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $persessions; // Dia, hora i hh:ii => 'hh:ii+hh:ii+dd/mm/yyyy;hh:ii+hh:ii+dd/mm/yyyy;hh:ii+hh:ii+dd/mm/yyyy;...'
    /********************** programacions codificades text ****************************/
    
    /**
     * @ORM\OneToMany(targetEntity="Sessio", mappedBy="docencia", cascade={"persist", "remove"} )
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
    public function __construct($facturacio, $proveidor, $datadesde, $totalhores, $preuhora)
    {
    	$this->id = 0;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->datadesde = $datadesde;
    	$this->databaixa = null;
    	$this->setmanal = null;
    	$this->mensual = null;
    	$this->persessions = null;
    	
    	
    	$this->facturacio = $facturacio;
    	if ($this->facturacio != null) $this->facturacio->addDocent($this);
    	$this->proveidor = $proveidor;
    	if ($this->proveidor != null) $this->proveidor->addDocencia($this);
    	$this->totalhores = $totalhores;
    	$this->preuhora = $preuhora;
    	
    	$this->pagaments = new \Doctrine\Common\Collections\ArrayCollection();
    	$this->calendari = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
	public function __clone() {
    	$this->id = null;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	
    	$this->pagaments = new \Doctrine\Common\Collections\ArrayCollection(); // Init pagaments
    	$this->calendari = new \Doctrine\Common\Collections\ArrayCollection(); // Init calendari
    }
    
    /**
     * És baixa? false
     *
     * @return boolean
     */
    public function esBaixa() { return $this->databaixa != null; }
    
    /**
     * es esborrable?. Només si cap rebut pagat, ni cap pagament a docent
     *
     * @return boolean
     */
    public function esEsborrable()
    {
    	foreach ($this->pagaments as $pagament) {
    		if (!$pagament->anulat()) return false;
    	}
		return true;    	
    }
    
    /**
     * baixa de la facturació, els rebuts associats i les docències i sessions associades
     *
     */
    public function baixa()
    {
    	if ($this->esEsborrable()) {
	    	$this->databaixa = new \DateTime();
			$this->datamodificacio = new \DateTime();
	    
	    	// Baixa sessions
			$this->initCalendari();
    	}
    }
    
    /**
     * Get sessions actives del calendari de l'activitat 
     *
     * @return string
     */
    public function getSessionsActives()
    {
    	$sessions = array();
    	foreach ($this->calendari as $sessio) {
    		if (!$sessio->esBaixa()) $sessions[] = $sessio;
    	}
    	return $sessions;
    }
    
    /**
     * Get sessions del calendari de l'activitat as string
     *
     * @return string
     */
    public function getSessionsCalendari()
    {
    	$info = '';
    	$sessions = $this->getSessionsActives();
    	foreach ($sessions as $sessio) {
    		$data = $sessio->getHorari()->getDatahora();
    		$info[] = 'El dia ' .$data->format('d/m/Y') . ' a les ' . $data->format('H:i');
    	}
    	return implode('\n', $info);
    }
    
    /**
     * Crear sessions segons planificació des de la 'datadesde' un 'totalhores' de sessions
     *
     */
    public function crearCalendari( $festius )
    {
    	//$this->totalhores = $totalhores;
    	//$this->datadesde = $preuhora;
    	$interval = new \DateInterval('P1D'); // 1 dia
    	$datainicial = clone $this->datadesde;
    	
    	$progs = array_merge($this->getInfoSetmanal(), $this->getInfoMensual(), $this->getInfoPersessions());
    	
    	$descSessio = $this->facturacio->getActivitat()->getDescripcio().' ('.$this->facturacio->getId().')';
    	$total = 0;
    	
    	for ($i = 0; $i < 365; $i++) {	// max. 365 dies
    		
    		$esFestiu = $this->validarFestiu($datainicial, $festius);
    		
    		if (!$esFestiu) {
	    		$sessions = array(); // Sessions per $datainicial segons totes les programacions
		    	foreach ($progs as $info) {	// Validar si la data compleix
		    		
		    		$dades = explode('+',$info['dades']);
		    		$candidata = null;
		    		
		    		switch ($info['tipus']) {
		    			case UtilsController::PROG_SETMANAL:		//  hh:ii+hh:ii+diasemana
		    				if ($datainicial->format('N') == $dades[2]) { // Dia de la setmana 1-dilluns ... 7-diumenge
		    					$candidata = clone $datainicial;
		    				}
		    				break;
		    			case UtilsController::PROG_MENSUAL:			//  hh:ii+hh:ii+diames+diasemana
		    				
		    				$ord = UtilsController::getOrdinalAng($dades[2]);
		    				$weekday = UtilsController::getDiaSetmanaAng($dades[3]); 
		    				
		    				$candidata = clone $datainicial;
		    				$candidata->modify($ord.' '.$weekday.' of this month');  //'first mon of this month'
		    				
		    				if ($datainicial->format('d/m/Y') != $candidata->format('d/m/Y')) $candidata = null;
		    				
		    				break;
		    			case UtilsController::PROG_SESSIONS:		//  hh:ii+hh:ii+dd/mm/yyyy
		    				if ($datainicial->format('d/m/Y') == $dades[2]) {
		    					$candidata = clone $datainicial;
		    				}
		    				
		    				break;
		    		}
		    		if ($candidata != null) $sessions[] = $this->validarNovaSessio($sessions, $candidata, $dades[0], $dades[1], $descSessio);
		    	}
	    		
		    	foreach ($sessions as $nova) {
		    		$this->addCalendari($nova);
		    		$total++;
		    		if ($total >= $this->totalhores) return;
		    	}
    		}
    		$datainicial->add($interval);
    	}
    }
    
    private function validarFestiu($data, $festius) {
    	$esFestiu = false;
    	foreach ($festius as $festiu) {
    		//$festiu = trim($festiu);
    		$dia = $data->format('d') * 1;
    		$mes = $data->format('m') * 1;
    		 
    		$arrFestiu = explode("/", $festiu);
    	
    		if (count($arrFestiu) == 2) {
    			$diaFestiu = $arrFestiu[0]*1;
    			$mesFestiu = $arrFestiu[1]*1;
    	
    			if ($dia == $diaFestiu && $mes == $mesFestiu) return true;
    		}
    	}
    	 
    	return false;
    }
    
    private function validarNovaSessio($sessions, $candidata, $hinici, $hfin, $descripcio) {
    	
    	if ($candidata == null || $candidata == '') throw new \Exception('Data de la sessió incorrecte '.$candidata);
    	if ($hinici == '') throw new \Exception('Hora inici de la sessió incorrecte '.$hinici);
    	if ($hfin == '') throw new \Exception('Hora final de la sessió incorrecte '.$hfin);
    	if ($descripcio == '') throw new \Exception('Falta la descripció de la sessió');
    	
    	$hora = explode(':',$hinici);
    	if (count($hora) != 2 || !is_numeric($hora[0]) || !is_numeric($hora[1])) throw new \Exception('Hora inici incorrecte '.$hinici);
    	
    	$candidata->setTime($hora[0], $hora[1]);

    	$minutsInici = 60 * $hora[0] + $hora[1];
    	
    	$hora = explode(':',$hfin);
    	if (count($hora) != 2 || !is_numeric($hora[0]) || !is_numeric($hora[1])) throw new \Exception('Hora final incorrecte '.$hfin);
    	 
    	$minutsFinal = 60 * $hora[0] + $hora[1];
    	$durada = $minutsFinal - $minutsInici;
    	if ($durada <= 0) throw new \Exception('Durada de la sessió incorrecte '.$durada);
    	
    	// Overlapping
    	foreach ($sessions as $sessio) {
    		
    		$datadesde = \DateTime::createFromFormat('d/m/Y', $docenciaArray['datadesde']);
    		
    		if ($hfinal >= $sessio->getHorari()->getDatahora()->forma('H:i') &&
    			$hini <= $sessio->getHorari()->getDatahorafinal()->forma('H:i')) throw new \Exception('Les sessions encavalquen per al dia '.$sessio->getHorari()->getDatahora()->forma('d/m/Y'));
    		
    		$this->addCalendari($nova);
    	}
    	
    	$sessio = new Sessio($this, $candidata, $durada, UtilsController::EVENT_SESSIO, $descripcio);
    	
    	return $sessio;
    }
    
    
    /**
     * Baixa sessions
     *
     */
    public function initCalendari()
    {
    	foreach ($this->calendari as $sessio) {
    		if (!$sessio->esBaixa()) $sessio->baixa();
    	}
    }
    
    
    public function setArrayDocencia( $horari )
    {
    	$setmanalArray = array();
    	$mensualArray = array();
    	$sessionsArray = array();
    	$errors = array();
    	
    	foreach ($horari as $k => $info) {
    		
    		if ($info['hora'] >= $info['final'])  $errors[] = 'Programació '.($k + 1).' interval incorrecte: '.$info['hora'].' a '.$info['final']; 
    		else {
	    		switch ($info['tipus']) {
	    			case UtilsController::PROG_SETMANAL: 
	    				$setmanalArray[] = $info['dades'];
	    				break;
	    			case UtilsController::PROG_MENSUAL:  
	    				$mensualArray[] = $info['dades'];
	    				break;
	    			case UtilsController::PROG_SESSIONS:  
	    				$sessionsArray[] = $info['dades'];
	    				break;
	    		}
    		}
    	}
    	
    	if (count($errors) > 0) return $errors;
    	
    	$this->setmanal = count($setmanalArray) > 0?implode(';', $setmanalArray):null; 
    	$this->mensual = count($mensualArray) > 0?implode(';', $mensualArray):null;
    	$this->persessions = count($sessionsArray) > 0?implode(';', $sessionsArray):null;
    	
    	return array();
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
     *						dades: 'hh:ii+hh:ii+diasemana;...' | 'hh:ii+hh:ii+setmanames+diames' | 'hh:ii+hh:ii+dd/mm/yyyy'
     *						hora:  hora inici
	 *						final: hora final
	 *						info:  desc
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
     * Import pagaments
     *
     * @return decimal
     */
    public function getImportPagaments() {
    	
    	$total = 0;
    
    	foreach ($this->pagaments as $pagament) {
    		if (!$pagament->anulat()) $total += $pagament->getImport();
    	}
    
    	return $total;
    }
    
    /**
     * Pagaments per mes / any
     *
     * @return array
     */
    public function getPagamentsMesAny($anypaga, $mespaga) {
    	$pagamentsMes = array();

    	foreach ($this->pagaments as $pagament) {
    		if (!$pagament->anulat() && $pagament->esDelMesAny($anypaga,$mespaga)) $pagamentsMes[] = $pagament;
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
    		if (!$sessio->esBaixa()) {
	    		$data = $sessio->getHorari()->getDatahora();
	    			
	    		if (!isset($mesos[$data->format('Y')."-".$data->format('m')])) {
	    			$mesos[$data->format('Y')."-".$data->format('m')] = array('any' => $data->format('Y'), 'mes' => $data->format('m'));
	    		}
    		}
    	}
    	return $mesos;
    }
    
    
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
     * Get sessions d'un mes i any concrets
     *
     * @return string
     */
    public function getSessionsMensuals( $anyImp, $mesImp )
    {
		$sessions = array();
    	foreach ($this->calendari as $sessio) {
    		if (!$sessio->esBaixa()) {
    			$data = $sessio->getHorari()->getDatahora();
    		
    			if ($data->format('Y') == $anyImp && $data->format('m') == $mesImp) $sessions[] = $sessio;
    		}
    	}
    	return $sessions;
    }
    
    /**
     * Get import sessions d'un mes i any concrets
     *
     * @return string
     */
    public function getImportSessionsMensuals( $anyImp, $mesImp )
    {
    	
    	$cost = 0;
    	$sessions = $this->getSessionsMensuals( $anyImp, $mesImp );
	    	
    	return $this->preuhora * count($sessions);
    }
    
    /**
     * Get info del calendari de l'activitat as string
     *
     * @return string
     */
    public function getInfoCalendari($separator = '<br/>')
    {
    	$progs = array_merge($this->getInfoSetmanal(), $this->getInfoMensual(), $this->getInfoPersessions());
    		
    	$info = '';
    		
    	foreach ($progs as $prog) {
    		$info .= $prog['info'].' a les '.$prog['hora'].' fins les '. $prog['final'].$separator;
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
    		//  hh:ii+hh:ii+diasemana
    		$programaArray = explode('+',$progSetmanal);
    		if (count($programaArray) == 3) {
    			if ($formatcomu == true) {
    				$info[] = array(
    						'tipus' => UtilsController::PROG_SETMANAL,
    						'dades' => $progSetmanal,
    						'hora' => $programaArray[0], 
    						'final' => $programaArray[1],
    						'info' => UtilsController::getDiaSetmana($programaArray[2])
    				);
    			} else {
    				$info[] = array(
    						'tipus' => UtilsController::PROG_SETMANAL,
    						'dades' => $progSetmanal,
    						'hora' => $programaArray[0], 
    						'final' => $programaArray[1],
    						'diasetmana' => $programaArray[2]
    				);
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
    		//  hh:ii+hh:ii+diames+diasemana
    		$programaArray = explode('+',$progMensual);
    		if (count($programaArray) == 4) {
    			if ($formatcomu == true) {
    				$info[] = array(
    						'tipus' => UtilsController::PROG_MENSUAL,
    						'dades' => $progMensual,
    						'hora' => $programaArray[0], 
    						'final' => $programaArray[1],
    						'info' => ucfirst(UtilsController::getDiaDelMes($programaArray[2])).' '.UtilsController::getDiaSetmana($programaArray[3]),
    				);
    			} else {
    				$info[] = array(
    						'tipus' => UtilsController::PROG_MENSUAL,
    						'dades' => $progMensual,
    						'hora' => $programaArray[0], 
    						'final' => $programaArray[1],
    						'diadelmes' => $programaArray[2],
    						'diasetmana' => $programaArray[3],
    						
    				);
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
    		//  hh:ii+hh:ii+dd/mm/yyyy
    		$programaArray = explode('+',$progSessions);
    		if (count($programaArray) == 3) {
    			if ($formatcomu == true) {
    				$info[] = array(
    						'tipus' => UtilsController::PROG_SESSIONS,
    						'dades' => $progSessions, 
    						'hora' => $programaArray[0], 
    						'final' => $programaArray[1],
    						'info' => $programaArray[2],
    				);
    			} else {
    				$info[] = array(
    						'tipus' => UtilsController::PROG_SESSIONS,
    						'dades' => $progSessions, 
    						'diahora' => $programaArray[2].' '.$programaArray[0],
    						'final' => $programaArray[1]
    				);
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
