<?php 
// src/Foment/GestioBundle/Entity/FacturacioSeccio.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
 * @ORM\Table(name="facturacionsseccions")
 */

/*
 * FacturacióSeccions. Agrupació de rebuts i centralitzen l'enviament de domiciliacions de les quotes de les seccions.
 */
class FacturacioSeccio extends Facturacio 
{
	/**
	 * @ORM\Id
	 * @ORM\OneToOne(targetEntity="Facturacio", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="id", referencedColumnName="id")
	 */
    protected $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $datadomiciliada;
    
	/**
	 * Constructor
	 */
	public function __construct($datafacturacio, $desc)
	{
		parent::__construct($datafacturacio, $desc);
	}
	
	/**
	 * Domiciliada ?  Data últim enviament domiciliacions
	 *
	 * @return boolean
	 */
	public function domiciliada()
	{
		return $this->getDatadomiciliada() != null;
	}
	
	/**
	 * es esborrable?. No domiciliada o si cap rebut pagat
	 *
	 * @return boolean
	 */
	public function esEsborrable()
	{
		if ($this->domiciliada()) return false;
		
		return parent::esEsborrable();
	}
	
    /**
     * Get fitxer domiciliacions per la Caixa, actualitza Facturació si escau
     * DAt 
     *
     * @return array
     */
    public function generarFitxerDomiciliacions($datafins)
    {
    	$contents = array();
    	$errors = array();
    	$rebutsPerTreure = array();
		$current = new \DateTime();
    	
		// NIF + ANY (2) + SEMESTRE (1)
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
   			if ($rebut->getTipuspagament() == UtilsController::INDEX_DOMICILIACIO &&
   				!$rebut->cobrat() &&
   				!$rebut->retornat() &&
   				!$rebut->esBaixa() &&
   				$rebut->getDataemissio()->format('Y-m-d') <= $datafins->format('Y-m-d')) {
		    		// Rebuts de la facturació emesos fins la datafins encara no enviats al banc
    					
    					
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
	    		
    		}
    	}
    	if ($totalDomiciliacions == 0) {
    		throw new \Exception('No hi ha cap rebut pendent de domicialiar fins la data indicada '.$datafins->format('d/m/Y'));
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
    	}*/
    	return $contents;
    }
    
    
    /**
     * Returns rebut pendent from persona. Si existeix el rebut però està de baixa, es torna a fer donar d'alta per 
     *
     * @param \Foment\GestioBundle\Entity\Persona $persona
     * @return \Foment\GestioBundle\Entity\Rebut
     */
    public function getRebutPendentByPersonaDeutora($persona, $fraccio = 1) {
    	$dataemissio2 = UtilsController::getDataIniciEmissioSemestre2($this->datafacturacio->format('Y'));
    	
    	foreach ($this->rebuts as $rebut) {
    		if ($rebut->getDeutor() == $persona && !$rebut->cobrat() && !$rebut->anulat()) {
    			if ($fraccio == 1) {
    				if ($rebut->getDataemissio()->format('Y-m-d') < $dataemissio2->format('Y-m-d')) return $rebut;
    			} else {
    				if ($rebut->getDataemissio()->format('Y-m-d') >= $dataemissio2->format('Y-m-d')) return $rebut;
    			}
    		}
    	}
    }
    
    /**
     * Set id
     *
     * @param \Foment\GestioBundle\Entity\Facturacio $id
     * @return FacturacioSeccio
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
     * Set datadomiciliada
     *
     * @param \DateTime $datadomiciliada
     * @return Facturacio
     */
    public function setDatadomiciliada($datadomiciliada)
    {
    	$this->datadomiciliada = $datadomiciliada;
    
    	return $this;
    }
    
    /**
     * Get datadomiciliada
     *
     * @return \DateTime
     */
    public function getDatadomiciliada()
    {
    	return $this->datadomiciliada;
    }
}
