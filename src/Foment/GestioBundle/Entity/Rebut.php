<?php 
// src/Foment/GestioBundle/Entity/Rebut.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="rol", type="string", length=1)
 * @ORM\DiscriminatorMap({"R" = "Rebut", "X" = "RebutCorreccio"}) 
 * @ORM\Table(name="rebuts")
 */
class Rebut
{
	
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $num;		// Reset anual MAX $num de l'any en curs
    
    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $total;  // p.e. 3 : total de detalls
    
    /**
     * @ORM\ManyToOne(targetEntity="Persona", inversedBy="rebuts")
     * @ORM\JoinColumn(name="deutor", referencedColumnName="id")
     */
    protected $deutor; // FK taula Persona
    
    /**
     * @ORM\ManyToOne(targetEntity="Facturacio", inversedBy="rebuts")
     * @ORM\JoinColumn(name="facturacio", referencedColumnName="id")
     */
    protected $facturacio; // FK taula facturacions
    
    /**
     * @ORM\ManyToOne(targetEntity="Periode", inversedBy="rebutsnofacturats")
     * @ORM\JoinColumn(name="periodenf", referencedColumnName="id")
     */
    protected $periodenf; // FK taula periodes
    
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $dataemissio;		// S'informa quan es genera
    
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $datavenciment;	// A partir del moment que s'emet el rebut veure UtilsController::DIES_VENCIMENT_REBUT_DESDE_EMISSIO
    
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $dataretornat;
   
    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $tipuspagament; // Finestreta o Banc. Veure UtilsController
    
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $datapagament;
    
    /**
     * @ORM\OneToMany(targetEntity="RebutDetall", mappedBy="rebut")
     */
    protected $detalls;
    
    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $tipusrebut; // Quota o activitat. Veure UtilsController
    
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
    public function __construct($deutor, $dataemissio, $numrebut, $seccio = true, $periode = null)
    {
    	$this->id = 0;
    	$this->num = $numrebut;
    	$this->deutor = $deutor;
    	if ($this->deutor != null) $this->deutor->addRebut($this);
    	$this->dataemissio = $dataemissio;
    	
    	$this->facturacio = null;  // Es creen en un periode pendents de facturar
    	
    	if ($seccio == true) {
    		$this->periodenf = $periode;
    		if ($this->periodenf != null) {
    			$this->tipusrebut = UtilsController::TIPUS_SECCIO;
    			// Seccions normals
    			$this->periodenf->addRebutnofacturat($this); // Afegir rebut al període (només semestrals)
	    		// Només cal mirar domiciliacions per a socis vigents i rebuts de quotes. La resta es paga per finestreta
	    		if ($this->deutor != null && $deutor->esSociVigent()) $this->tipuspagament = $deutor->getTipusPagament();
	    		else  $this->tipuspagament = UtilsController::INDEX_FINESTRETA; // Finestreta
    		} else {
    			$this->tipusrebut = UtilsController::TIPUS_SECCIO_NO_SEMESTRAL;
    			// Seccions altres, sempre finestreta
    			$this->tipuspagament = UtilsController::INDEX_FINESTRETA; // Finestreta
    		}
    	} else {
    		$this->tipusrebut = UtilsController::TIPUS_ACTIVITAT;
    		$this->tipuspagament = UtilsController::INDEX_FINESTRETA; // Finestreta
    		$this->periodenf = null;
    	}
    	    	
    	$this->total = 0;
    	
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	$this->detalls = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Get csvRow, qualsevol Entitat que s'exporti a CSV ha d'implementar aquest mètode
     * Delimiter ;
     * Quotation ""
     *
     * @return string
     */
    public function getCsvRow()
    {
    	/*array( '"id"', '"num"', '"deutor"', '"import"', '"concepte"', '"periode"',
    			'"facturacio"', '"tipuspagament"', '"tipusrebut"',
    			'"dataemissio"', '"dataretornat"','"datapagament"','"databaixa"', '"correccio"' );*/
    	
    	$fields = array();
    	$fields[] = $this->id;
    	$fields[] = $this->getNumFormat();
    	if ($this->deutor != null) $fields[] = $this->deutor->getNomCognoms();
    	else $fields[] = '';
    
    	$fields[] = number_format($this->getImport(), 2, ',', '.');
    	//$fields[] = $this->getConcepte();
    	
    	
    
    	// Periode i facturació
   		if ($this->facturacio != null) {
   			if ($this->facturacio->getPeriode() != null) $fields[] = $this->facturacio->getPeriode()->getTitol();
   			else $fields[] = '';
   			
   			$fields[] = $this->facturacio->getDescripcio();
   		} else {
   			if ($this->periodenf != null) $fields[] = $this->periodenf->getTitol();
   			else $fields[] = '';
   			
   			$fields[] = '';
   		}
    
    	$fields[] = $this->getTexttipuspagament();
    	if ($this->esActivitat()) $fields[] = UtilsController::TITOL_REBUT_ACTIVITAT;
    	else {
    		if ($this->tipusrebut == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL) $fields[] = UtilsController::TITOL_REBUT_SECCIO_NO_SEMESTRAL; 
    		else $fields[] = UtilsController::TITOL_REBUT_SECCIO;
    	}
    
    	if ($this->dataemissio != null) $fields[] = $this->dataemissio->format('Y-m-d');
    	else $fields[] = '';
    	if ($this->dataretornat != null) $fields[] = $this->dataretornat->format('Y-m-d');
    	else $fields[] = '';
    	if ($this->datapagament != null) $fields[] = $this->datapagament->format('Y-m-d');
    	else $fields[] = '';
    	if ($this->databaixa != null) $fields[] = $this->databaixa->format('Y-m-d');
    	else $fields[] = '';
    
    	if ($this->esCorreccio() == false) $fields[] = '';
    	else $fields[] = 'correccio, nou concepte : '.$this->getNouconcepte().', import previ '.number_format($this->getImportcorreccio(), 2, ',', '.');
    	$row = '"'.implode('";"', $fields).'"';
    	
    	return $row;
    }
    
    
    /**
     * Rollback creació rebut, detectat import 0.
     * Abans de fer detach($rebut), cal treure'l del deutor i dels rebuts 
     * no facturats del periode
     *
     */
    public function detach()
    {
    	$this->deutor->removeRebut($this);
    	if ($this->periodenf != null) $this->periodenf->removeRebutnofacturat($this);
    }
    
    /**
     * es activitat?.
     *
     * @return boolean
     */
    public function esActivitat()
    {
    	return $this->tipusrebut == UtilsController::TIPUS_ACTIVITAT;
    }

    /**
     * es seccio?.
     *
     * @return boolean
     */
    public function esSeccio()
    {
    	return $this->tipusrebut == UtilsController::TIPUS_SECCIO  || 
    			$this->tipusrebut == UtilsController::TIPUS_SECCIO_NO_SEMESTRAL;
    }
    
    /**
     * get titol rebut.
     *
     * @return boolean
     */
    public function titolRebut()
    {
    	if ($this->esActivitat()) return UtilsController::TITOL_REBUT_ACTIVITAT;
    	return UtilsController::TITOL_REBUT_SECCIO;
    }
    
    /**
     * get prefix rebut.
     *
     * @return boolean
     */
    public function prefixRebut()
    {
    	if ($this->esActivitat()) return UtilsController::PREFIX_REBUT_ACTIVITAT;
    	return UtilsController::PREFIX_REBUT_SECCIO;
    }
    
    /**
     * es correcció fals?.
     *
     * @return boolean
     */
    public function esCorreccio()
    {
    	return false;
    }
    
    /**
     * Get id Activitat si escau o 0
     *
     * @return int
     */
    public function getActivitatId()
    {
    	if ($this->facturacio == null || $this->facturacio->getActivitat() == null) return 0;
    	 
    	return $this->facturacio->getActivitat()->getId();
    	
    }

    /**
     * Get estat as integer
     *
     * @return integer
     */
    public function getEstat()
    {
    	if ($this->anulat()) return UtilsController::INDEX_ESTAT_ANULAT;
    	if ($this->cobrat()) return UtilsController::INDEX_ESTAT_COBRAT;
    	if ($this->enDomiciliacio()) return UtilsController::INDEX_ESTAT_FACTURAT;
    	if ($this->retornat()) return UtilsController::INDEX_ESTAT_RETORNAT;
    	//if ($this->facturacio != null && $this->tipuspagament == UtilsController::INDEX_DOMICILIACIO) return UtilsController::INDEX_ESTAT_FACTURAT;
    	return UtilsController::INDEX_ESTAT_EMES;
    }
    
    /**
     * Get info as string
     *
     * @return String
     */
    public function getInfo()
    {
    	$info = 'rebut número '.$this->getNumFormat().' en data '.$this->getDataemissio()->format('d/m/Y');
    	$info .= ' per un import de ' . number_format($this->getImport(), 2, ',', '.');
    	if ($this->cobrat()) $info .= '. Cobrat el dia '.$this->getDatapagament()->format('d/m/Y');
    	return $info;
    }

    /**
     * Get num format
     *
     * @return String
     */
    public function getNumFormat()
    {
    	return $this->prefixRebut().str_pad($this->num, 6, '0', STR_PAD_LEFT) .'/'.$this->dataemissio->format('y');
    }
    
    /**
     * Get next num detall
     *
     * @return int
     */
    public function getNextNumDetall()
    {
    	return count($this->detalls) + 1;
    }
    
    /**
     * Get num detall actius (no baixa)
     *
     * @return int
     */
    public function getNumDetallsActius()
    {
    	$total = 0;
    	foreach ($this->detalls as $d) {
    		if ($d->getDatabaixa() == null) $total++;
    	}
    	return $total;
    }
    
    /**
     * Get import total rebut
     *
     * @return double 
     */
    public function getImport()
    {
    	$import = 0;
    	foreach ($this->detalls as $d) {
    		if ($d->getDatabaixa() == null) $import += $d->getImport();
    	}
    	return $import;
    }
    
    /**
     * Get import sense la correcció corresponent. Sobreescrit
     *
     * @return double
     */
    public function getImportSenseCorreccio()
    {
    	return $this->getImport();
    }
    
    /**
     * Get import total rebut amb baixes
     *
     * @return double
     */
    public function getImportBaixes()
    {
    	$import = 0;
    	foreach ($this->detalls as $d) $import += $d->getImport();
    	
    	return $import;
    }
    
    public function getConcepte()
    {
    	if ($this->esActivitat() && $this->facturacio != null) return $this->facturacio->getDescripcioCompleta();
    	$concepte = '';
    	foreach ($this->detalls as $d) {
    		if ($d->getDatabaixa() == null) $concepte .= $d->getConcepte().', ';
    	}
    	if ($concepte != '') $concepte = substr($concepte, 0, -2);  // treure últim ', '
    	return $concepte;
    }
    
    /**
     * Retorna conceptes detalls actius
     * Cada concepte (element array) mida $len, si $len > 0
     * Tenen les claus tal com surt a la documentació, claus parelles (columna dreta extracte banc) 
     *
     * @return double
     */
    public function getConceptesArray($len)
    {
    	
    	$conceptes = array();
    	foreach ($this->getDetallsSortedByNum() as $d) {
    		$con = $d->getConcepte();
    		if (!isset($conceptes[$con])) {
    			$conceptes[$con] = array('total' => 1, 'import' => $d->getImport());
    		} else {
    			$conceptes[$con]['total']++;
    			$conceptes[$con]['import'] += $d->getImport();
    		}
    	}
    	
    	$i = 2;  // Conceptes 2,4,6,8,10, 12,14, 16
    	$conceptesClauFinal = array();
    	foreach ($conceptes as $con => $data) {
    		$importFormat = number_format($data['import'], 2, ',', '.');

    		if ($data['total'] > 1) $str = $con.' X'.$data['total'];
    		else $str = $con;
    		
    		if ($len > 0) {
	    		$maxLenCon = $len - strlen($importFormat);
	    		$str = substr($str, 0, $maxLenCon);  // p.e. Foment General anual x 2
	    		$str = str_pad($str, $maxLenCon, " ", STR_PAD_RIGHT) . $importFormat;   // p.e. Foment General anual x 2        80,00
    		} else {
    			$str .= ' '.$importFormat; 
    		}
	    		
    		$conceptesClauFinal[$i] = mb_strtoupper(UtilsController::netejarNom($str, false), 'ASCII');  // Ñ -> 165;
    		$i += 2;
    	}
    	
    	return $conceptesClauFinal;
    }
    
    /**
     * Get nouconcepte per sobreescritura
     *
     * @return string
     */
    public function getNouconcepte()
    {
    	return '';
    }
    
    /**
     * Get importcorreccio per sobreescritura
     *
     * @return double
     */
    public function getImportcorreccio()
    {
    	return 0;
    }
    
    /**
     * Get seccions detalls si es de secció. Per activitat array buit
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSeccions()
    {
    	$seccions = array();
    	if ($this->esActivitat()) return $seccions;
    	
    	foreach ($this->detalls as $d) {
    		if ($d->getDatabaixa() == null && $d->getQuotaseccio() != null) {
    			$membre = $d->getQuotaseccio();
    			$seccions[] = $membre->getSeccio();
    		}
    	}
    	return $seccions;
    }
    
    
    
    /**
     * Get detalls no cancelats sorted by num
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDetallsSortedByNum($baixes = false)
    {
    	$arr = array();
    	foreach ($this->detalls as $d) {
    		//if ($baixes == true || $d->getDatabaixa() == null) $arr[$d->getSeccio()->getId()] = $d;
    		if ($baixes == true || $d->getDatabaixa() == null) $arr[] = $d;
    	}
    	error_log(count($arr));
    
    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getNumdetall() < $b->getNumdetall())? -1:1;;
    	});
    		 
    	return $arr;
    }
    
    /**
     * Es pot facturar el rebut?
     *
     * @return boolean
     */
    public function esFacturable()
    {
    	if ($this->tipuspagament == UtilsController::INDEX_DOMICILIACIO &&
    		$this->databaixa == null &&
    		$this->datapagament == null ) {
    		return true;	
    	}
    	return false;
    }
    
    /**
     * Get datafacturacio
     *
     * @return \String
     */
    public function getDescripcioFacturacio()
    {
    	if ($this->facturacio != null) return $this->facturacio->getDescripcioCompleta();
    	
    	if ($this->tipuspagament == UtilsController::INDEX_DOMICILIACIO) return UtilsController::getEstats($this->getEstat()); // Pendent propera domiciliació
    	
    	return "";
    }
    
    
    /**
     * Està facturat el rebut i encara no s'ha rebut resposta?
     *
     * @return boolean
     */
    public function enDomiciliacio()
    {
    	if ($this->tipuspagament != UtilsController::INDEX_DOMICILIACIO) return false;
    	return $this->periodenf == null && $this->facturacio != null && !$this->retornat();
    }
    
    /**
     * Es finestreta ?
     *
     * @return boolean
     */
    public function esFinestreta()
    {
    	return $this->tipuspagament == UtilsController::INDEX_FINESTRETA;
    }
    
    /**
     * Està retornat i encara no cobrat el rebut?
     *
     * @return boolean
     */
    public function retornat()
    {
    	// En domiciliació es marquen com pagats directament
    	// return $this->dataretornat != null && $this->datapagament == null;
    	return $this->dataretornat != null;
    }
    
    /**
     * Està cobrat el rebut?
     *
     * @return boolean
     */
    public function cobrat()
    {
    	return $this->datapagament != null;
    }
    
    /**
     * Està anul·lat el rebut?
     *
     * @return boolean
     */
    public function anulat()
    {
    	return $this->databaixa != null;
    }
    
    /**
     * Es pot esborrar el rebut? 
     *
     * @return boolean
     */
    public function esEsborrable()
    {
    	if ($this->databaixa != null) return false; // Donat de baixa ja no pot canviar
    	//if ($this->datapagament != null) return false; // No donar de baixa si està pagat
    	if ($this->enDomiciliacio()) return false; // Pendent resposta del banc
    	//if ($this->tipuspagament == UtilsController::INDEX_FINESTRETA) return $this->datapagament == null; // no pagat
    	return true;
    }
    
    /**
     * Es pot modificar el rebut? Afegir nous conceptes, treure'n...
     *
     * @return boolean
     */
    public function esModificable()
    {
    	return true;
    	//return $this->esEsborrable();
    }
    
    /**
     * Baixa del rebut
     *
     */
    public function baixa() {
    	$this->setDatamodificacio(new \DateTime());
    	$this->setDatabaixa(new \DateTime());
    	 
    	foreach ($this->detalls as $d) {
    		if ($d->getDatabaixa() == null) {
    			$d->setDatamodificacio(new \DateTime());
    			$d->setDatabaixa(new \DateTime());
    		}
    	}
    		
    }
    
    /**
     * És el rebut encara vigent
     *
     * @return String
     */
    public function esVigent()
    {
    	if ($this->databaixa != null || $this->dataretornat != null) return false; // No vigent
    	
    	if ($this->datavenciment == null) return true;
    	
    	$current = new \DateTime(); 
    	if ($this->datavenciment < $current) return false;  // Ha vençut
    	
    	return true;
    }

    /**
     * Get texttipuspagament
     *
     * @return integer
     */
    public function getTexttipuspagament()
    {
    	$tipus = UtilsController::getTipusPagament($this->tipuspagament); 
    	
    	return $tipus;
    }
    
    /**
     * Array buit info  rebuts
     */
    public static function getArrayInfoRebuts() {
    	return array(	'rebuts' => array ('total' => 0, 'import' => 0, 'correccio' => 0),		// total
    					'cobrats' => array ('total' => 0, 'import' => 0, 'correccio' => 0),		// total cobrats
    					'anulats' => array ('total' => 0, 'import' => 0, 'correccio' => 0),
    					/************************ BANC ********************************/  
    					'bpendents' => array ('total' => 0, 'import' => 0, 'correccio' => 0),  // Banc però no facturats encara    										  
    					'bfacturats' => array ('total' => 0, 'import' => 0, 'correccio' => 0),// Són el total, mentre no es rebi notificació banc
	    				'bcobrats' => array ('total' => 0, 'import' => 0, 'correccio' => 0), 

    					/************************ RETORNATS **************************/
    					'retornats' => array ('total' => 0, 'import' => 0, 'correccio' => 0), 	// Retornats pendents pagament finestreta
    					'rcobrats' => array ('total' => 0, 'import' => 0, 'correccio' => 0),

    					/************************* FINESTRETA *************************/
    					'finestretaanulats' => array ('total' => 0, 'import' => 0, 'correccio' => 0),  
    					'finestreta' => array ('total' => 0, 'import' => 0, 'correccio' => 0),  	// Finestreta inicials (no retornats)
    					'fcobrats' => array ('total' => 0, 'import' => 0, 'correccio' => 0)
    	);
    }
    
    /**
     * Adds info rebut to an Array
     *
     */
    public function addInforebut(&$info)
    {
    	if ($this->esSeccio()) {
    		$import = $this->getImportSenseCorreccio();
    		$correccio = 0;
    		
    		if ($this->getDatabaixa() != null) {
    			$import = $this->getImportBaixes();
    		}
    		if ($this->esCorreccio()) {
    			$correccio = $this->getImport() - $import;
    		}
    		
    		$this->addInforebutArray($info, $this->getDatabaixa() != null, $import, $correccio);
    	}
    }
    
    /**
     * Adds info rebut to an Array
     *
     */
    public function addInforebutArray(&$info, $baixa = false, $import = 0, $correccio = 0) {
    
	    if ($baixa == false) {
	    	$info['rebuts']['total']++;
	    	$info['rebuts']['import'] += $import;
	    	$info['rebuts']['correccio'] += $correccio;
	    	if ($this->tipuspagament != UtilsController::INDEX_DOMICILIACIO) {  // Rebut marcat finestreta o retornat
	    
		    	if ($this->facturacio != null && $this->getDataretornat() != null){ // Retornats alguna facturació
		    		$info['retornats']['total']++;
		    		$info['retornats']['import'] += $import;
		    		$info['retornats']['correccio'] += $correccio;
		    		if ($this->getDatapagament() != null) {
		    			$info['rcobrats']['total']++;
		    			$info['rcobrats']['import'] += $import;
		    			$info['rcobrats']['correccio'] += $correccio;
		    			$info['cobrats']['total']++;
		    			$info['cobrats']['import'] += $import;
		    			$info['cobrats']['correccio'] += $correccio;
		    		}
		    		 
		    	} else {
		    		$info['finestreta']['total']++;
		    		$info['finestreta']['import'] += $import;
		    		$info['finestreta']['correccio'] += $correccio;
		    		if ($this->getDatapagament() != null) {
		    			$info['fcobrats']['total']++;
		    			$info['fcobrats']['import'] += $import;
		    			$info['fcobrats']['correccio'] += $correccio;
		    			$info['cobrats']['total']++;
		    			$info['cobrats']['import'] += $import;
		    			$info['cobrats']['correccio'] += $correccio;
		    		}
		    	}
	    
	    	} else {  // Rebut marcat domiciliació
	    
	    		if ($this->facturacio == null){ // Pendents, encara  a cap facturació
	    			if ($import != 0) { // Incidència Gospel 2015 setembre
		    			$info['bpendents']['total']++;
		    			$info['bpendents']['import'] += $import;
		    			$info['bpendents']['correccio'] += $correccio;
	    			}
	    		} else {  // Tenen facturació
	    			$info['bfacturats']['total']++;
	    			$info['bfacturats']['import'] += $import;
	    			$info['bfacturats']['correccio'] += $correccio;
	    			if ($this->getDatapagament() != null) {
	    				$info['bcobrats']['total']++;
	    				$info['bcobrats']['import'] += $import;
	    				$info['bcobrats']['correccio'] += $correccio;
	    				$info['cobrats']['total']++;
	    				$info['cobrats']['import'] += $import;
	    				$info['cobrats']['correccio'] += $correccio;
	    			}
	    		}
	    	}
	    
	    } else {
	    	$info['anulats']['total']++;
	    	$info['anulats']['import'] += $import;
	    	$info['anulats']['correccio'] += $correccio;
	    	 
	    	$info['rebuts']['total']++;
	    	$info['rebuts']['import'] += $import;
	    	$info['rebuts']['correccio'] += $correccio;
	    	
	    	if ($this->facturacio == null) {
	    	//if ($this->tipuspagament != UtilsController::INDEX_DOMICILIACIO) {
	    		$info['finestretaanulats']['total']++;
	    		$info['finestretaanulats']['import'] += $import;
	    		$info['finestretaanulats']['correccio'] += $correccio;
	    	}
	    	
	    }
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
     * Set num
     *
     * @param integer $num
     * @return Rebut
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer 
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set total
     *
     * @param integer $total
     * @return Rebut
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return integer 
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set dataemissio
     *
     * @param \DateTime $dataemissio
     * @return Rebut
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
     * Set datavenciment
     *
     * @param \DateTime $datavenciment
     * @return Rebut
     */
    public function setDatavenciment($datavenciment)
    {
        $this->datavenciment = $datavenciment;

        return $this;
    }

    /**
     * Get datavenciment
     *
     * @return \DateTime 
     */
    public function getDatavenciment()
    {
        return $this->datavenciment;
    }

    /**
     * Set dataretornat
     *
     * @param \DateTime $dataretornat
     * @return Rebut
     */
    public function setDataretornat($dataretornat)
    {
        $this->dataretornat = $dataretornat;

        return $this;
    }

    /**
     * Get dataretornat
     *
     * @return \DateTime 
     */
    public function getDataretornat()
    {
        return $this->dataretornat;
    }

    /**
     * Set tipuspagament
     *
     * @param integer $tipuspagament
     * @return Rebut
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
     * Set tipusrebut
     *
     * @param integer $tipusrebut
     * @return Rebut
     */
    public function setTipusrebut($tipusrebut)
    {
    	$this->tipusrebut = $tipusrebut;
    
    	return $this;
    }
    
    /**
     * Get tipusrebut
     *
     * @return integer
     */
    public function getTipusrebut()
    {
    	return $this->tipusrebut;
    }
    
    /**
     * Set datapagament
     *
     * @param \DateTime $datapagament
     * @return Rebut
     */
    public function setDatapagament($datapagament)
    {
        $this->datapagament = $datapagament;

        return $this;
    }

    /**
     * Get datapagament
     *
     * @return \DateTime 
     */
    public function getDatapagament()
    {
        return $this->datapagament;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Rebut
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
     * @return Rebut
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
     * Set deutor
     *
     * @param \Foment\GestioBundle\Entity\Persona $deutor
     * @return Rebut
     */
    public function setDeutor(\Foment\GestioBundle\Entity\Persona $deutor = null)
    {
    	$this->deutor = $deutor;
    
    	return $this;
    }
    
    /**
     * Get deutor
     *
     * @return \Foment\GestioBundle\Entity\Persona
     */
    public function getDeutor()
    {
    	return $this->deutor;
    }
    
    /**
     * Set facturacio
     *
     * @param \Foment\GestioBundle\Entity\Facturacio $facturacio
     * @return Rebut
     */
    public function setFacturacio(\Foment\GestioBundle\Entity\Facturacio $facturacio = null)
    {
        $this->facturacio = $facturacio;

        return $this;
    }

    /**
     * Get facturacio
     *
     * @return \Foment\GestioBundle\Entity\Facturacio 
     */
    public function getFacturacio()
    {
        return $this->facturacio;
    }

    /**
     * Set periodenf
     *
     * @param \Foment\GestioBundle\Entity\Periode $periodenf
     * @return Rebut
     */
    public function setPeriodenf(\Foment\GestioBundle\Entity\Periode $periodenf = null)
    {
    	$this->periodenf = $periodenf;
    
    	return $this;
    }
    
    /**
     * Get periodenf
     *
     * @return \Foment\GestioBundle\Entity\Periode
     */
    public function getPeriodenf()
    {
    	return $this->periodenf;
    }
    
    /**
     * Add detalls
     *
     * @param \Foment\GestioBundle\Entity\RebutDetall $detalls
     * @return Rebut
     */
    public function addDetall(\Foment\GestioBundle\Entity\RebutDetall $detalls)
    {
    	$this->detalls->add($detalls);
   
    	return $this;
    }
    
    /**
     * Remove detalls
     *
     * @param \Foment\GestioBundle\Entity\RebutDetall $detalls
     */
    public function removeDetall(\Foment\GestioBundle\Entity\RebutDetall $detalls)
    {
    	$this->detalls->removeElement($detalls);
    }
    
    /**
     * Get detalls
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDetalls()
    {
    	return $this->detalls;
    }
}
