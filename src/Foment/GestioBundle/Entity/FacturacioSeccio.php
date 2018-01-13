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
	 * Get fitxer domiciliacions per la Caixa format SEPA, actualitza Facturació si escau
	 * 
	 * Cuaderno 19-44: Adeudos Directos SEPA - Esquema Básico
     *
     * @return array
	 * 
	 */
	
	public function generarFitxerDomiciliacionsSEPA($datafins)
	{
	    $contents = array();
	    //$errors = array();
	    //$rebutsPerTreure = array();
	    $current = new \DateTime();
	    
	    // 0. La longitud de los registros del fichero es de 600 caracteres.
	    // 1. Los campos pueden ser obligatorios (OB) u opcionales (OP) y los campos definidos como “Libre” irán a espacios en blanco.
	    // 2. Los campos numéricos irán ajustados a la derecha y completados con ceros a la izquierda, cuando sea necesario.
	    // 3. Los campos alfanuméricos irán ajustados a la izquierda y completados con espacios en blanco a la derecha cuando sea necesario.
	    // 4. dentro de cada bloque de acreedor, en orden ascendente  Código de registro + Referencia del adeudo + Número de dato
	    // 5. Codificació bàsica UNIFI (ISO20022)     Ñ,ñ → N,n   i     Ç,ç → C,c
	    
	    $idAcreedor = str_pad(UtilsController::H_IDACREEDOR, 35, " ", STR_PAD_RIGHT);
	    $nomAcreedor = str_pad(UtilsController::NOM_FOMENT, 70, " ", STR_PAD_RIGHT);
	    $idFichero = str_pad('PRE'.$current->format('YmdHisu').'FINS'.$datafins->format('Ymd'), 35, " ", STR_PAD_RIGHT);
	    $datavenciment = clone $current;
	    $datavenciment->add(new \DateInterval('P4D')); // + 4 dies hàbils
	    
	    // Cabecera de Presentador inicial
	    // len ob/op   val                     desc
	    // 2   ob      01                      Código de registro
	    // 5   ob      19143                   versió quadern
	    // 3   ob      001                     Núm dato
	    // 35  ob      ESDDSSSSNNNNNNNNN       Id Presentador NIF y Sufijo en formato ESDDSSSSNNNNNNNNN  (Id acreedor)
	    // 70  ob      abscd...                Nombre del Presentador 
	    // 8   ob      AAAAMMDD                Fecha creación fichero
	    // 35  ob      PREAAAAMMDDHHMMSSmmmmm  Identificación del fichero Formato  + Referencia identificativa asignada por el presentador
	    // 4   ob      2100                    Entidad receptora
	    // 4   ob      0961                    Oficina gestora del cliente Acreedor NNNN
	    // 434 ob                              Libre (espais en blanc)
	    $contents['header-presentador']  = '01'.UtilsController::H_VERSIOQUADERN.'001'.$idAcreedor.$nomAcreedor;
	    $contents['header-presentador'] .= $current->format('Ymd').$idFichero;
	    $contents['header-presentador'] .= UtilsController::H_ORDENANT_ENTITAT.UtilsController::H_ORDENANT_OFICINA.str_repeat(" ",434);
	    
	    // Cabecera de Acreedor y Fecha de Cobro: un único registro obligatorio
	    // len ob/op   val                     desc
	    // 2   ob      02                      Código de registro
	    // 5   ob      19143                   versió quadern
	    // 3   ob      002                     Núm dato
	    // 35  ob      ESDDSSSSNNNNNNNNN       AT-02   Identificador del Acreedor NIF y Sufijo en formato ESDDSSSSNNNNNNNNN
	    // 8   ob      AAAAMMDD                AT-11   Fecha de cobro/vencimiento
	    // 70  ob      abscd...                AT-03   Nombre del Acreedor
	    // 50  op      Carrer de Provença, 591 Dirección acreedor 1    tipo de vía, nombre de la vía, número y piso del domicilio del acreedor
	    // 50  op      08026 Barcelona         Dirección acreedor 2    código postal y el  nombre de la localidad
	    // 40  op      Barcelona               Dirección acreedor 3    nombre de la provincia del domicilio
	    // 2   op      ES                      País acreedor           
	    // 34  ob      (<--24-->   )           AT-04   Cuenta del Acreedor IBAN
	    // 301 ob                              Libre (espais en blanc)
	    $contents['header-acreedor']  = '02'.UtilsController::H_VERSIOQUADERN.'002'.$idAcreedor.$datavenciment->format('Ymd').$nomAcreedor;
	    $contents['header-acreedor'] .= str_pad(UtilsController::H_ADDRFOMENT1, 50, " ", STR_PAD_RIGHT);
	    $contents['header-acreedor'] .= str_pad(UtilsController::H_ADDRFOMENT2, 50, " ", STR_PAD_RIGHT);
	    $contents['header-acreedor'] .= str_pad(UtilsController::H_ADDRFOMENT3, 40, " ", STR_PAD_RIGHT);
	    $contents['header-acreedor'] .= UtilsController::H_ADDRPAIS.str_pad(UtilsController::H_IBANFOMENT, 34, " ", STR_PAD_RIGHT).str_repeat(" ",301);
	    
	    // Individuales Obligatorio  (un registre por adeudo)
	    // 2   ob      03                      Código de registro
	    // 5   ob      19143                   versió quadern
	    // 3   ob      003                     Núm dato
	    // 35  ob      (num rebut)             AT-10   Referencia del adeudo   (asignada por el cliente a cada recibo)
	    // 35  ob      (id client)             AT-01   Referencia única del Mandato    (Ref. única de la orden de domiciliación o mandato) => Num soci, s'ha de mantenir igual 
	    //                                             mentre es vagin fent domiciliacions RCUR
	    // 4   ob      RCUR                    AT-21 Secuencia del adeudo Tipo de adeudo  
	    // 4   op      ESDD  ¿?                objeto del adeudo       códigos    ISO 20022        
	    // 11  ob      00000099900             AT-06   Importe del adeudo (2 decimals ajustat dreta)
	    // 8   ob      AAAAMMDD                AT-25   Fecha de firma del Mandato  31.10.2009
	    // 11  op      NOTPROVIDED             AT-13   Entidad del deudor BIC
	    // 70  ob      abscd...                AT-14   Nombre del deudor
	    // 50  op      Carrer XYZ, 123         Dirección deudor 1    tipo de vía, nombre de la vía, número y piso del domicilio del acreedor
	    // 50  op      08XXX Barcelona         Dirección deudor 2    código postal y el  nombre de la localidad
	    // 40  op      Barcelona               Dirección deudor 3    nombre de la provincia del domicilio
	    // 2   op      ES                      País deudor
	    // 1   op      2                       Tipo de Identificación   1 – Organización 2 – Persona
	    // 36  op      J+num soci+NIF o NIE    Identificador deudor
	    // 35  op                              Identificación del deudor otro
	    // 1   ob      A                       Identificador de la cuenta del deudor  A = IBAN
	    // 34  ob      (<--24-->   )           AT-07   IBAN deutor
	    // 4   op      ESDD  ¿?                Propósito del adeudo    códigos externos    ISO 20022   ESDD SEPA Core Direct Debit
	    // 140 op                              AT-22   Concepto
	    // 19  ob                              Libre (espais en blanc)
	    //
	    //
	    // Individuales Opcionales (màxim 3 registres por adeudo)    
	    //     AT-10   Referencia del adeudo 
	    //     AT-01   Referencia única del Mandato    (Ref. única de la orden de domiciliación o mandato)
	    //     Referencia única del Mandato original   (només si canvia)  
	    //     Nombre del acreedor original            (només si canvia)
	    //     Identificador del acreedor original     (només si canvia)
	    //     Cuenta del deudor original
	    //     Entidad del deudor original
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
	                $import = $rebut->getImport();
	                $import = $import*100; // Decimals
	                $deutor = $rebut->getDeutor();
	                $rebutNum = $rebut->getNumFormat();
	                $deutorNumNom = $deutor->getNumNomCognoms();
	                $compte = $deutor->getCompte();
	                
	                if ($import <= 0) {
	                    throw new \Exception('El rebut '.$rebutNum.' a càrrec del soci '.$deutorNumNom .
	                        ' té un import incorrecte');
	                }
	                if ($compte == null) {
	                    throw new \Exception('El soci '.$deutorNumNom.' a càrrec del rebut '. $rebutNum.
	                        ' no té cap compte corrent associat');
	                }
	                if ($compte->getIban() == null || $compte->getIban() == "") {
	                    throw new \Exception('El soci '.$deutorNumNom.' a càrrec del rebut '. $rebutNum.
	                        ' no té indicat l\'IBAN ');
	                }
	                if ($compte->getCompte20() == "") {
	                    throw new \Exception('El soci '.$deutorNumNom.' a càrrec del rebut '. $rebutNum.
	                        ' té un compte corrent associat erroni '.$compte->getCompte20());
	                }
	                
	                $totalDomiciliacions++;
	                $totalRegistres++;
	                $sumaImport += $rebut->getImport();
	                
	                $refAdeudo = str_pad($rebut->getNumFormat(), 35, " ", STR_PAD_RIGHT);
	                $refMandato = str_pad($rebut->getDeutor()->getNum(), 35, " ", STR_PAD_RIGHT);
	                
	                $idDeutor = str_pad('J'.$deutor->getNumSoci().$deutor->getDni(), 36, " ", STR_PAD_RIGHT);
	                
	                $titular = mb_strtoupper(UtilsController::netejarNom($compte->getTitular(), false), 'ASCII');  // Ñ -> 165
	                $titular = substr($titular, 0, 70);
	                $titular = str_pad($titular, 70, " ", STR_PAD_RIGHT);
	                
	                $liniesConceptes = $rebut->getConceptesArraySepa(80, 60);
	                if (count($liniesConceptes) != 2) {
	                    throw new \Exception('No s\'ha pogut generar correctament el concepte pel soci '.$deutorNumNom.' a càrrec del rebut '. $rebutNum);
	                }
	                
	                $concepteLinia1 = str_pad($liniesConceptes[0], 80, " ", STR_PAD_RIGHT);
	                $concepteLinia2 = str_pad($liniesConceptes[1], 60, " ", STR_PAD_RIGHT);
	                
	                $contents['individual-obligatori-'.$rebut->getNum()]  = '03'.UtilsController::H_VERSIOQUADERN.'003'.$refAdeudo.$refMandato.'RCUR'.'    ';
	                $contents['individual-obligatori-'.$rebut->getNum()] .= str_pad($import, 11, "0", STR_PAD_LEFT).UtilsController::H_FIRMAMANDATO;
	                $contents['individual-obligatori-'.$rebut->getNum()] .= UtilsController::H_BICDEUTOR.$titular;
	                $contents['individual-obligatori-'.$rebut->getNum()] .= str_repeat(" ",50).str_repeat(" ",50).str_repeat(" ",40).'  '.'2'.$idDeutor.str_repeat(" ",35);
	                $contents['individual-obligatori-'.$rebut->getNum()] .= 'A'.str_pad($compte->getIban(), 34, " ", STR_PAD_RIGHT).'    ';
	                $contents['individual-obligatori-'.$rebut->getNum()] .= $concepteLinia1.$concepteLinia2.str_repeat(" ",19);

	                $rebut->setDatapagament($current);
	            }
	    }
	    if ($totalDomiciliacions == 0) {
	        throw new \Exception('No hi ha cap rebut pendent de domicialiar fins la data indicada '.$datafins->format('d/m/Y'));
	    }
	    
	    // Total por Acreedor y Fecha de Cobro
	    // 2   ob      04                      Código de registro
	    // 35  ob      ESDDSSSSNNNNNNNNN       AT-02   Identificador del Acreedor NIF y Sufijo en formato ESDDSSSSNNNNNNNNN
	    // 8   ob      AAAAMMDD                AT-11   Fecha de cobro/vencimiento
	    // 17  ob      00000099900             Total de importes Sumatorio de los importes del campo 8 de todos los registros con número de dato = 003
	    // 8   ob      00009999                Número de adeudos, registros individuales obligatorios, número de dato = 003
	    // 10  ob      0000099999              Total de registros que contenga el bloque del acreedor, incluidos el de cabecera y el propio de totales
	    // 520 ob                              Libre (espais en blanc)
	    $sumaImport = $sumaImport*100; // Decimals
	    $totalRegistres++;
	    
	    $contents['total-acreedordata']  = '04'.$idAcreedor.$datavenciment->format('Ymd').str_pad($sumaImport, 17, "0", STR_PAD_LEFT);
	    $contents['total-acreedordata'] .= str_pad($totalDomiciliacions, 8, "0", STR_PAD_LEFT);
	    $contents['total-acreedordata'] .= str_pad($totalRegistres, 10, "0", STR_PAD_LEFT).str_repeat(" ",520);
	    
	    // Total del Acreedor
	    // 2   ob      05                      Código de registro
	    // 35  ob      ESDDSSSSNNNNNNNNN       AT-02   Identificador del Acreedor NIF y Sufijo en formato ESDDSSSSNNNNNNNNN
	    // 17  ob      ######99900             Total de importes Sumatorio de los importes del campo 4 de todos los registros con código de registro = 04
	    // 8   ob      ######99999             Número de adeudos, registros individuales obligatorios, número de dato = 003
	    // 10  ob      ######99999             Total de registros que contenga el bloque del acreedor, incluidos el de cabecera y el propio de totales.
	    // 528 ob                              Libre (espais en blanc)
	    $totalRegistres += 2;
	    
	    $contents['total-acreedor']  = '05'.$idAcreedor.str_pad($sumaImport, 17, "0", STR_PAD_LEFT);
	    $contents['total-acreedor'] .= str_pad($totalDomiciliacions, 8, "0", STR_PAD_LEFT);
	    $contents['total-acreedor'] .= str_pad($totalRegistres, 10, "0", STR_PAD_LEFT).str_repeat(" ",528);
	    
	    
	    
	    // Total fichero / general
	    // 2   ob      99                      Código de registro
	    // 17  ob      ######99900             Total de importes Sumatorio de los importes del campo 4 de todos los registros con código de registro = 05
	    // 8   ob      ######99999             Número de adeudos, registros individuales obligatorios, número de dato = 003
	    // 10  ob      ######99999             Total de registros que contenga el bloque del acreedor, incluidos el de cabecera y el propio de totales.
	    // 563 ob                              Libre (espais en blanc)
	    
	    $totalRegistres++;
	    
	    $contents['total-general']  = '99'.str_pad($sumaImport, 17, "0", STR_PAD_LEFT);
	    $contents['total-general'] .= str_pad($totalDomiciliacions, 8, "0", STR_PAD_LEFT);
	    $contents['total-general'] .= str_pad($totalRegistres, 10, "0", STR_PAD_LEFT).str_repeat(" ",563);
	    
	    return $contents;
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
    	//$errors = array();
    	//$rebutsPerTreure = array();
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
     * (Obsolet) Returns rebut pendent from persona. Si existeix el rebut però està de baixa, es torna a fer donar d'alta per 
     *
     * @param \Foment\GestioBundle\Entity\Persona $persona
     * @param boolean $general
     * @param integer $fraccio
     * 
     * @return \Foment\GestioBundle\Entity\Rebut
     */
    public function getRebutPendentByPersonaDeutora($persona, $general = false, $fraccio = 1) {
        $candidat = null;
        
    	$dataemissio2 = UtilsController::getDataIniciEmissioSemestre2($this->datafacturacio->format('Y'));
   	
    	foreach ($this->rebuts as $rebut) {
    		if ($rebut->getDeutor() == $persona && !$rebut->cobrat() && !$rebut->anulat()) {
                if (!$general) return $rebut;  // Seccions no generals només 1 fracció
    			
                $candidat = $rebut;
    			if ($fraccio == 1) {
    				if ($rebut->getDataemissio()->format('Y-m-d') < $dataemissio2->format('Y-m-d')) return $rebut;
    			} else {
    				if ($rebut->getDataemissio()->format('Y-m-d') >= $dataemissio2->format('Y-m-d')) return $rebut;
    			}
    		}
    	}
        return $candidat;  // Millor candidat. Rebut pendent encara que no coincideixin les dates
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
