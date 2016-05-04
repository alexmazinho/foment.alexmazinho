<?php 
// src/Foment/GestioBundle/Entity/Facturacio.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
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
     * @ORM\ManyToOne(targetEntity="Periode", inversedBy="facturacions")
     * @ORM\JoinColumn(name="periode", referencedColumnName="id")
     */
    protected $periode; // FK taula periodes    
    
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
	public function __construct()
	{
		$this->id = 0;
		$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
		 
		// Hack per permetre múltiples constructors
		$a = func_get_args();
		$i = func_num_args();
		
		if ($i == 5 && $a[0] instanceof Periode && method_exists($this,$f='__constructPeriodes')) {
			call_user_func_array(array($this,$f),$a);
		}
		if ($i == 6 && $a[0] instanceof Activitat && method_exists($this,$f='__constructActivitats')) {
			call_user_func_array(array($this,$f),$a);
		}
	}
    
    /**
     * Constructor Periodes. 
     */
    public function __constructPeriodes($periode, $num, $tipuspagament, $desc, $datafacturacio)
    {
    	$this->tipuspagament = $tipuspagament;
    	
    	if ($datafacturacio == null) $this->datafacturacio = new \DateTime();
    	else $this->datafacturacio = $datafacturacio;

    	$this->periode = $periode;
    	if ($periode != null) $periode->addFacturacions($this);
    	
    	$this->descripcio = $num.'-'.$desc;
    	$this->activitat = null;
    	$this->importactivitat = null;
    	$this->importactivitatnosoci = null;
    	$this->rebuts = new \Doctrine\Common\Collections\ArrayCollection();
    	 
    }
    
    /**
     * Constructor Activitats.
     */
    public function __constructActivitats($activitat, $num, $desc, $importactivitat, $importactivitatnosoci, $datafacturacio)
    {
    	$this->tipuspagament = UtilsController::INDEX_FINESTRETA;
    	
    	if ($datafacturacio == null) $this->datafacturacio = new \DateTime();
    	else $this->datafacturacio = $datafacturacio;
    	
    	$this->activitat = $activitat;
    	if ($activitat != null) $activitat->addFacturacions($this);
    	$this->importactivitat = $importactivitat;
    	$this->importactivitatnosoci = $importactivitatnosoci;
    	$this->descripcio = $desc;
    	
    	$this->periode = null;
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
    		if ($this->databaixa != null) { // No tenir en compte de baixa
    			if (!$rebut->esEsborrable()) return false;
    		} 
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
     * Get periode description as string
     *
     * @return string
     */
   /* public function getDescription()
    {
    	$desc = $this->titol . ' ('.$this->getDatainici()->format('j/n/y').' - '.$this->getDatafinal()->format('j/n/Y').')';
    	$desc .= PHP_EOL . 'fraccionament: general '.$this->percentfragmentgeneral*100 . '%, seccions ' . $this->percentfragmentseccions*100 . '%';
    	return $desc;
    }*/
    
    /**
     * Get fitxer domiciliacions per la Caixa, actualitza Facturació si escau
     * DAt 
     *
     * @return array
     */
    public function generarFitxerDomiciliacions()
    {
    	$contents = array();
    	$errors = array();
    	$rebutsPerTreure = array();
		$current = new \DateTime();
    	
		// NIF + ANY (2) + SEMESTRE (1)
		//$ident_ordenant = UtilsController::NIF_FOMENT.substr($this->periode->getAnyperiode(), -2, 2).$this->periode->getSemestre();
		$ident_ordenant = UtilsController::NIF_FOMENT.UtilsController::SUFIJO;
		
    	// Capçalera presentador
    	// A1 A2 B1  B2 B3    C  D     E          F     G
    	// 2  2  9+3 6  6(L)  40 20(L) 4+4+12(L)  40(L) 14(L)
    	$contents['header-presentador'] = UtilsController::H_PRESENTADOR_REG.UtilsController::H_PRESENTADOR_DADA.$ident_ordenant;
    	$contents['header-presentador'] .= $current->format('dmy').str_repeat(" ",6);
    	$contents['header-presentador'] .= str_pad(UtilsController::NOM_FOMENT, 40, " ", STR_PAD_RIGHT).str_repeat(" ",20);
    	$contents['header-presentador'] .= UtilsController::H_ORDENANT_ENTITAT.UtilsController::H_ORDENANT_OFICINA;
    	$contents['header-presentador'] .= str_repeat(" ",12).str_repeat(" ",40).str_repeat(" ",14);
    	
    	// Capçalera ordenant
    	// A1 A2 B1  B2 B3 C   D        E             F     G
    	// 2  2  9+3 6  6  40 4+4+2+10  8(L)+2+10(L)  40(L) 14(L)
    	$contents['header-ordenant'] = UtilsController::H_ORDENANT_REG.UtilsController::H_ORDENANT_DADA.$ident_ordenant;
    	$contents['header-ordenant'] .= $current->format('dmy').$current->add(new \DateInterval('P1D'))->format('dmy');
    	$contents['header-ordenant'] .= str_pad(UtilsController::NOM_FOMENT, 40, " ", STR_PAD_RIGHT);
    	$contents['header-ordenant'] .= UtilsController::H_ORDENANT_ENTITAT.UtilsController::H_ORDENANT_OFICINA;
    	$contents['header-ordenant'] .= UtilsController::H_ORDENANT_DC.UtilsController::H_ORDENANT_CC.str_repeat(" ",8).UtilsController::H_ORDENANT_PROCEDIMENT;
    	$contents['header-ordenant'] .= str_repeat(" ",10).str_repeat(" ",40).str_repeat(" ",14);
    	
    	$totalDomiciliacions = 0;
    	$totalRegistres = 1; // Capçalera ordenant
    	$sumaImport = 0;
    	foreach ($this->rebuts as $rebut) {
    		try {
    		$compte = $rebut->getDeutor()->getCompte();
    		$rebutNum = $rebut->getNum();
    		$import = $rebut->getImport();
    		$import = $import*100; // Decimals

    			//$deutor = str_replace("Ñ",chr(165),$deutor);
    			$deutor = mb_strtoupper(UtilsController::netejarNom($rebut->getDeutor()->getNomCognoms(), false), 'ASCII');  // Ñ -> 165
    		
    			if ($import <= 0) {
    				throw new \Exception('El rebut '.$rebutNum.' a càrrec del soci '.$deutor .
    						' té un import incorrecte');
    			}
    			
	    		if ($compte == null) {
	    			throw new \Exception('El soci '.$deutor.' a càrrec del rebut '. $rebutNum.
	    						' no té cap compte corrent associat');
	    		}

	    		$titular = mb_strtoupper(UtilsController::netejarNom($compte->getTitular(), false), 'ASCII');  // Ñ -> 165
	    		
	    		if ($compte->getCompte20() == "") {
	    			throw new \Exception('El soci '.$deutor.' a càrrec del rebut '. $rebutNum.
	    						' té un compte corrent associat erroni '.$compte->getCompte20());
	    		} 
	    		
	    		$titular = substr($titular, 0, 40);
	    		$titular = strlen($titular)==40?$titular:str_pad($titular, 40, " ", STR_PAD_RIGHT);
	    		
	    		$rebutNum = substr($rebutNum, 0, 6);
	    		$rebutNum = strlen($rebutNum)==6?$rebutNum:str_pad($rebutNum, 6, "0", STR_PAD_LEFT);
	    		
	    		
	    		// Registre individual obligatori
	    		// A1 A2 B1  B2 C   D         E    F1  F2  G   H
	    		// 2  2  9+3 12 40  4+4+2+10  10   6   10  40  8(L)
	    		// B2 => número de soci
	    		// F => devoluciones : num rebut (6) + fecha (00 + yyyymmdd) emissió del rebut
	    		$reg = 'individual-obligatori-'.$rebutNum;
	    		$contents[$reg] = UtilsController::R_INDIVIDUAL_OBL_REG.UtilsController::R_INDIVIDUAL_OBL_DADA.$ident_ordenant;
	    		$contents[$reg] .= str_pad($rebut->getDeutor()->getNum(), 12, " ", STR_PAD_LEFT);
	    		$contents[$reg] .= $titular;
	    		$contents[$reg] .= $compte->getCompte20();
	    		$contents[$reg] .= str_pad($import, 10, "0", STR_PAD_LEFT);
	    		$contents[$reg] .= $rebutNum.str_pad($rebut->getDataemissio()->format('Ymd'), 10, "0", STR_PAD_LEFT);
	    		
	    		$totalDomiciliacions++;
	    		$totalRegistres++;
	    		$sumaImport += $import;
	    		 
	    		
	    		// Els conceptes s'imprimeixen de la següent manera, en total 16
	    		/*
	    		 *   linia 1: Concepte 1 obligatori : NUM-NOM SOCI 					Concepte 2 opcional (1er del 1er registre opcional)
	    		 *   linia 2: Concepte 3 opcional (2er del 1er registre opcional) 	Concepte 4 opcional (3er del 1er registre opcional)
	    		 *   linia 3: Concepte 5 opcional (1er del 2n registre opcional)    Concepte 6 opcional (2n del 2n registre opcional)
	    		 *   linia 4: Concepte 7 opcional (3er del 2n registre opcional)	Concepte 8 opcional (1er del 3er registre opcional) 
	    		 *   linia 5: Concepte 9 opcional (2n del 3er registre opcional) 	Concepte 10 opcional (3er del 3er registre opcional) 
	    		 *   linia 6: Concepte 11 opcional (1er del 4rt registre opcional) 	Concepte 12 opcional (2n del 4rt registre opcional) 
	    		 *   linia 7: Concepte 13 opcional (3n del 4rt registre opcional)   Concepte 14 opcional (1er del 5é registre opcional)
	    		 *   linia 8: Concepte 15 opcional (2n del 5é registre opcional)	Concepte 16 opcional (3er del 5é registre opcional)
	    		 *  
	    		 */
	    		
	    		$concepte = substr($rebut->getDeutor()->getNum().'-'.$deutor, 0, 40);
	    		
	    		$contents[$reg] .= $concepte.str_repeat(" ",8); // 1er concepte
	    		
	    		// Opcionals
	    		$conceptesOpcionals = $rebut->getConceptesArray(40);
	    		$totalConceptes = count($conceptesOpcionals);
	    		
	    		if ($totalConceptes > 8) {
	    			unset($contents[$reg]);
	    			throw new \Exception('El rebut '.$rebutNum.' a càrrec del soci '.$rebut->getDeutor()->getNomCognoms() .
	    					' té masses conceptes i no es pot afegir al fitxer');
	    		}
	    		// Registre individual opcional (primer) ==> Aquest sempre es mostrarà
	    		// A1 A2 B1  B2 C   D   E  F
	    		// 2  2  9+3 12 40  40  40 14(L)
	    		// C, D, E => conceptes	: concepte 2 + REBUT NUM. : XXXXX + concepte 4
	    		$registre = 1;
	    		if (isset($conceptesOpcionals[2])) { // 3 o més
	    			$reg = 'individual-opcional-'.$rebutNum.'-'.$registre;
	    			$contents[$reg] = UtilsController::R_INDIVIDUAL_OPT_REG;
	    			$contents[$reg] .= (UtilsController::R_INDIVIDUAL_OBL_DADA+$registre).$ident_ordenant; // 80 + 1, 80 + 2...
	    			$contents[$reg] .= str_pad($rebut->getDeutor()->getNum(), 12, " ", STR_PAD_LEFT);	    			
	    			
	    			$contents[$reg] .= $conceptesOpcionals[2]; // Concepte 2
	    			$contents[$reg] .= str_pad('REBUT NUM. : '.$rebutNum, 40, " ", STR_PAD_RIGHT);
	    			$contents[$reg] .= isset($conceptesOpcionals[4])?$conceptesOpcionals[4]:str_repeat(" ",40); // Concepte 4
	    			$contents[$reg] .= str_repeat(" ",14);
	    			$totalRegistres++;
	    		}
	    		 
	    		
	    		// Registre individual opcional (segon)
	    		// A1 A2 B1  B2 C   D   E  F
	    		// 2  2  9+3 12 40  40  40 14(L)
	    		// C, D, E => conceptes  :  blanc + concepte 6 + blanc  
	    		$registre = 2;
	    		if (isset($conceptesOpcionals[6])) { // 3 o més 
	    			$reg = 'individual-opcional-'.$rebutNum.'-'.$registre;
	    			$contents[$reg] = UtilsController::R_INDIVIDUAL_OPT_REG;
	    			$contents[$reg] .= (UtilsController::R_INDIVIDUAL_OBL_DADA+$registre).$ident_ordenant; // 80 + 1, 80 + 2...
	    			$contents[$reg] .= str_pad($rebut->getDeutor()->getNum(), 12, " ", STR_PAD_LEFT);

	    			$contents[$reg] .= str_repeat(" ",40); 
	    			$contents[$reg] .= $conceptesOpcionals[6]; // Concepte 6
	    			$contents[$reg] .= str_repeat(" ",40); 
	    			$contents[$reg] .= str_repeat(" ",14);
	    			$totalRegistres++;
	    		}
	    		
	    		// Registre individual opcional (tercer)
	    		// A1 A2 B1  B2 C   D   E  F
	    		// 2  2  9+3 12 40  40  40 14(L)
	    		// C, D, E => conceptes :  concepte 8 + blanc + concepte 10
	    		$registre = 3;
	    		if (isset($conceptesOpcionals[8])) { // 4 o més
	    			$reg = 'individual-opcional-'.$rebutNum.'-'.$registre;
	    			$contents[$reg] = UtilsController::R_INDIVIDUAL_OPT_REG;
	    			$contents[$reg] .= (UtilsController::R_INDIVIDUAL_OBL_DADA+$registre).$ident_ordenant; // 80 + 1, 80 + 2...
	    			$contents[$reg] .= str_pad($rebut->getDeutor()->getNum(), 12, " ", STR_PAD_LEFT);
	    			
	    			$contents[$reg] .= $conceptesOpcionals[8]; // Concepte 8
	    			$contents[$reg] .= str_repeat(" ",40); 
	    			$contents[$reg] .= isset($conceptesOpcionals[10])?$conceptesOpcionals[10]:str_repeat(" ",40); // Concepte 10
	    			$contents[$reg] .= str_repeat(" ",14);
	    			$totalRegistres++;
	    		}
	    		
	    		
	    		// Registre individual opcional (quart)
	    		// A1 A2 B1  B2 C   D   E  F
	    		// 2  2  9+3 12 40  40  40 14(L)
	    		// C, D, E => conceptes :  blanc + concepte 12 + blanc
	    		$registre = 4;
	    		if (isset($conceptesOpcionals[12])) { // 6 o més
	    			$reg = 'individual-opcional-'.$rebutNum.'-'.$registre;
	    			$contents[$reg] = UtilsController::R_INDIVIDUAL_OPT_REG;
	    			$contents[$reg] .= (UtilsController::R_INDIVIDUAL_OBL_DADA+$registre).$ident_ordenant; // 80 + 1, 80 + 2...
	    			$contents[$reg] .= str_pad($rebut->getDeutor()->getNum(), 12, " ", STR_PAD_LEFT);
	    			
	    			$contents[$reg] .= str_repeat(" ",40);
	    			$contents[$reg] .= $conceptesOpcionals[12]; // Concepte 12
	    			$contents[$reg] .= str_repeat(" ",40);
	    			$contents[$reg] .= str_repeat(" ",14);
	    			$totalRegistres++;
	    		}
	    			   
	    		// Registre individual opcional (cinqué)
	    		// A1 A2 B1  B2 C   D   E  F
	    		// 2  2  9+3 12 40  40  40 14(L)
	    		// C, D, E => conceptes : concepte 14 + blanc + concepte 16
	    		$registre = 5;
	    		if (isset($conceptesOpcionals[14])) { // 7 o 8
	    			$reg = 'individual-opcional-'.$rebutNum.'-'.$registre;
	    			$contents[$reg] = UtilsController::R_INDIVIDUAL_OPT_REG;
	    			$contents[$reg] .= (UtilsController::R_INDIVIDUAL_OBL_DADA+$registre).$ident_ordenant; // 80 + 1, 80 + 2...
	    			$contents[$reg] .= str_pad($rebut->getDeutor()->getNum(), 12, " ", STR_PAD_LEFT);
	    			
	    			$contents[$reg] .= $conceptesOpcionals[14]; // Concepte 14
	    			$contents[$reg] .= str_repeat(" ",40);
	    			$contents[$reg] .= isset($conceptesOpcionals[16])?$conceptesOpcionals[16]:str_repeat(" ",40); // Concepte 16
	    			$contents[$reg] .= str_repeat(" ",14);
	    			$totalRegistres++;
	    		}	   
			
	    		
	    		$rebut->setDatapagament($current); 
    		} catch (\Exception $e) {
    			// Treure el rebut de la facturació
				//$rebutsPerTreure[] = $rebut;
    			$errors[] = $e->getMessage();
    			//return array('contents' => $contents, 'errors' => $errors);
    		}
    	}

    	$totalRegistres++;
    	// Total ordenant
    	// A1 A2 B1  B2    C     D      E1  E2   F1  F2  F3    G
    	// 2  2  9+3 12(L) 40(L) 20(L)  10  6(L) 10  10  20(L) 18(L)
    	// E1 -> suma total imports
    	// F1 -> total domiciliacions (regs obligatoris) per ordenant
    	// F2 -> total registres ordenant (inclouent cap i aquest total)
    	// C, D, E => conceptes : concepte 14 + blanc + concepte 16
    	$reg = 'total-ordenante';
    	$contents[$reg] = UtilsController::R_INDIVIDUAL_TOT_ORD.UtilsController::R_INDIVIDUAL_TOT_DATA.$ident_ordenant;
    	$contents[$reg] .= str_repeat(" ",12 + 40 + 20);
    	$contents[$reg] .= str_pad($sumaImport, 10, "0", STR_PAD_LEFT);
    	$contents[$reg] .= str_repeat(" ",6);
    	$contents[$reg] .= str_pad($totalDomiciliacions, 10, "0", STR_PAD_LEFT); // F1 total domiciliacions
    	$contents[$reg] .= str_pad($totalRegistres, 10, "0", STR_PAD_LEFT); // F2 total registres ordenant
    	$contents[$reg] .= str_repeat(" ",20 + 18);
    	 
    	 
    	// Total general
    	// A1 A2 B1  B2    C     D1 D2     E1  E2   F1  F2  F3    G
    	// 2  2  9+3 12(L) 40(L) 4  16(L)  10  6(L) 10  10  20(L) 18(L)
    	// D1 -> nombre ordenants => 1
    	// E1 -> suma total imports
    	// F1 -> total domiciliacions (regs obligatoris)
    	// F2 -> total registres
    	// C, D, E => conceptes : concepte 14 + blanc + concepte 16
    	$reg = 'total-general';
    	$contents[$reg] = UtilsController::R_INDIVIDUAL_TOT_GEN.UtilsController::R_INDIVIDUAL_TOT_DATA.$ident_ordenant;
    	$contents[$reg] .= str_repeat(" ",12 + 40);
    	$contents[$reg] .= str_pad(1, 4, "0", STR_PAD_LEFT); // Total ordenants 1
    	$contents[$reg] .= str_repeat(" ",16);
    	$contents[$reg] .= str_pad($sumaImport, 10, "0", STR_PAD_LEFT);
    	$contents[$reg] .= str_repeat(" ",6);
    	$contents[$reg] .= str_pad($totalDomiciliacions, 10, "0", STR_PAD_LEFT); // F1 total domiciliacions
    	$contents[$reg] .= str_pad($totalRegistres + 2, 10, "0", STR_PAD_LEFT); // F2 total registres ordenant  + Cap i cua generals
    	$contents[$reg] .= str_repeat(" ",20 + 18);
    	
    	
    	// Treure de la facturació tots els rebuts que han donat problemes 
    	/*foreach ($rebutsPerTreure as $rebutesborrar) {
    		$this->removeRebut($rebutesborrar);
    		$rebutesborrar->setFacturacio(null);
    		$rebutesborrar->setDatapagament(null);
    		$rebutesborrar->setPeriodenf($this->periode);
    		$this->periode->addRebutnofacturat($rebutesborrar);
    	}*/
    	return array('contents' => $contents, 'errors' => $errors);
    }
    
    /**
     * Get descripcio amb tipus de pagament
     *
     * @return string
     */
    public function getDescripcioCompleta()
    {
    	if ($this->activitat != null) return $this->activitat->getDescripcio().' '.$this->activitat->getCurs().' '.$this->descripcio; 
    	return ucfirst(UtilsController::getTipusPagament($this->tipuspagament).' '.$this->descripcio);
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
     * Set periode
     *
     * @param \Foment\GestioBundle\Entity\Periode $periode
     * @return Facturacio
     */
    public function setPeriode(\Foment\GestioBundle\Entity\Periode $periode = null)
    {
    	$this->periode = $periode;
    
    	return $this;
    }
    
    /**
     * Get periode
     *
     * @return \Foment\GestioBundle\Entity\Periode
     */
    public function getPeriode()
    {
    	return $this->periode;
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
