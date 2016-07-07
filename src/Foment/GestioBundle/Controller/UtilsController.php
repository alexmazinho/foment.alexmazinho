<?php

namespace Foment\GestioBundle\Controller;

//use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Foment\GestioBundle\Entity\AuxMunicipi;
use Foment\GestioBundle\Entity\Soci;
use Symfony\Component\HttpFoundation\JsonResponse;


class UtilsController extends BaseController
{
	const DEFAULT_PERPAGE = 15;
	const DEFAULT_PERPAGE_WITHFORM = 10;
	const MIN_INPUT_ACTIVITATS = 3;
	const MIN_INPUT_POBLACIONS = 2;
	const MIN_INPUT_NOMCOGNOMS = 3;
	const MIN_DATEPICKER_YEAR = 1899;
	const MIN_DATEPICKER_MONTH = 12;
	const MIN_DATEPICKER_DAY = 30;
	const DEFAULT_MIN_DATEPICKER_YEAR = 1950;
	const DEFAULT_MIN_DATEPICKER_MONTH = 01;
	const DEFAULT_MIN_DATEPICKER_DAY = 01;
	const REBUTS_MIN_DATEPICKER_YEAR = 2015;
	const REBUTS_MIN_DATEPICKER_MONTH = 01;
	const REBUTS_MIN_DATEPICKER_DAY = 01;
	
	const DIES_VENCIMENT_REBUT_DESDE_EMISSIO = 30;
	const PERCENT_DESCOMPTE_FAMILIAR = 0.25;
	const EDAT_ANYS_LIMIT_JUVENIL = 18;
	const HTTP_FORBIDDEN = 403;
	
	const PDF_MARGIN_LEFT_NARROW = 30;
	const PDF_MARGIN_RIGHT_NARROW = 30;
	
	const ID_FOMENT = 1;
	// Constants periodes de facturació
	const FOMENT = 'Foment';
	const SOCI_BAIXA = 'B';
	const SOCI_VIGENT = 'S';
	const NOSOCI = 'N';
	const TIPUS_SECCIO = 1;
	const TIPUS_ACTIVITAT = 2;
	const TIPUS_SECCIO_NO_SEMESTRAL = 3;
	const TITOL_REBUT_ACTIVITAT = 'cursos i tallers';
	const TITOL_REBUT_SECCIO = 'quotes seccions';
	const TITOL_REBUT_SECCIO_NO_SEMESTRAL = 'no semestrals';
	const TITOL_LIQ_DOCENT = 'Liquidació docència';
	const TITOL_LIQ_PROVEIDOR = 'Liquidació proveïdor';
	const PREFIX_REBUT_ACTIVITAT = 'C-';
	const PREFIX_REBUT_SECCIO = 'S-';
	const PREFIX_TITOL_SEMESTRE_1 = '1er Semestre ';  // Any ...
	const PREFIX_TITOL_SEMESTRE_2 = '2n Semestre ';  // Any ...
	const REBUTS_PENDENTS = 'Rebuts del semestre';
	const REBUTS_FINESTRETA = 'Finestreta semestre';
	const CONCEPTE_RECARREC_RETORNAT = 'Recarrec rebut retornat';
	const INDEX_FINESTRETA = 1;
	const INDEX_DOMICILIACIO = 2;
	const INDEX_FINES_RETORNAT = 3;
	const INDEX_ESTAT_PENDENT = 0;
	const INDEX_ESTAT_EMES = 1;
	const INDEX_ESTAT_FACTURAT = 2;
	const INDEX_ESTAT_RETORNAT = 3;
	const INDEX_ESTAT_COBRAT = 4;
	const INDEX_ESTAT_ANULAT = 5;
	const INDEX_ESTAT_EXEMPT = 6;
	const INDEX_CARREC_PRESIDENT = 1;
	const INDEX_CARREC_SOTSPRESIDENT = 2;
	const INDEX_CARREC_SECRETARI = 3;
	const INDEX_CARREC_TRESORER = 4;
	const INDEX_CARREC_VOCAL = 5;
	const INDEX_CARREC_ASSESSOR = 6;
	const INDEX_CARREC_SOTSSECRETARI = 7;
	const INDEX_CARREC_COMPTABLE = 8;
	const INDEX_CARREC_RRPP = 9;
	const INDEX_CARREC_REPRESENTANT = 10;
	
	const INDEX_EVENT_SESSIOCURS = 1;
	const INDEX_EVENT_TALLER = 2;
	const INDEX_EVENT_ANIVERSARI = 3;
	const INDEX_EVENT_CELEBRACIO = 4;
	const INDEX_EVENT_SOPAR = 5;
	const INDEX_EVENT_TEATRE = 6;
	const INDEX_EVENT_CAMPIONAT = 7;
	const INDEX_EVENT_ALTRES = 99;

	const PROG_SETMANAL = 'setmanal';
	const PROG_MENSUAL = 'mensual';
	const PROG_SESSIONS = 'sessio';
	
	const INDEX_DILLUNS = 1;
	const INDEX_DIMARTS = 2;
	const INDEX_DIMECRES = 3;
	const INDEX_DIJOUS = 4;
	const INDEX_DIVENDRES = 5;
	
	const DIA_DILLUNS = 'dilluns';
	const DIA_DIMARTS = 'dimarts';
	const DIA_DIMECRES = 'dimecres';
	const DIA_DIJOUS = 'dijous';
	const DIA_DIVENDRES = 'divendres';
	
	const INDEX_DIAMES_PRIMER = 1;
	const INDEX_DIAMES_SEGON= 2;
	const INDEX_DIAMES_TERCER= 3;
	const INDEX_DIAMES_QUART = 4;
	
	const DIA_INICI_SEMESTRE_1 = 1;
	const MES_INICI_SEMESTRE_1 = 1;
	const DIA_FINAL_SEMESTRE_1 = 31;
	const MES_FINAL_SEMESTRE_1 = 5;
	const DIA_INICI_SEMESTRE_2 = 1;
	const MES_INICI_SEMESTRE_2 = 6;
	const DIA_FINAL_SEMESTRE_2 = 31;
	const MES_FINAL_SEMESTRE_2 = 12;
	const PERCENT_FRA_GRAL_SEMESTRE_1 = 0.5;	// Percentatge 1er trimestre pagaments fraccionats quota general Foment
	const PERCENT_FRA_GRAL_SEMESTRE_2 = 0.5;	// Percentatge 2n trimestre pagaments fraccionats quota general Foment
	const PERCENT_FRA_SECCIONS_SEMESTRE_1 = 1;	// Percentatge 1er trimestre pagaments fraccionats quotes de les Seccions
	const PERCENT_FRA_SECCIONS_SEMESTRE_2 = 0;	// Percentatge 2n trimestre pagaments fraccionats quotes de les Seccions
	const CONCEPTE_REBUT_FOMENT_FAMILIAR = " familiar ";
	const CONCEPTE_REBUT_FOMENT_JUVENIL = " juvenil ";
	const CONCEPTE_REBUT_FOMENT_SENIOR = " sènior ";
	const CONCEPTE_REBUT_FOMENT_ANUAL = " anual ";
	const CONCEPTE_REBUT_FOMENT_SEMESTRAL = " semestral ";
	const CONCEPTE_REBUT_FOMENT_EXEMPT = " exempt ";
	const CONCEPTE_REBUT_FOMENT_FAMNOM = " ex. fam. nombrosa ";
	const CONCEPTE_REBUT_FOMENT_NO_EMES = " no emès ";
	const CONCEPTE_REBUT_FOMENT_PROP = " prop. ";
	const CONCEPTE_REBUT_ACTIVITAT_PREFIX = "Act. ";

	const ANY_INICI_CURSOS = 2014;
	const MES_INICI_CURS_SETEMBRE = 9;
	const DIA_MES_INICI_CURS_SETEMBRE = "01/09/";	//	
	const DIA_MES_FACTURA_CURS_OCTUBRE = "01/09/";	//	octubre, gener, abril. Millor rebut setembre
	const DIA_MES_FACTURA_CURS_GENER = "15/01/";	//	octubre, gener, abril
	const DIA_MES_FACTURA_CURS_ABRIL = "15/04/";	//	octubre, gener, abril
	const DIA_MES_FINAL_CURS_JUNY = "30/06/";	//
	const TEXT_FACTURACIO_GENERIC = "Trimestre curs";
	const TEXT_FACTURACIO_OCTUBRE = "1er Trimestre curs ";
	const TEXT_FACTURACIO_GENER = "2n Trimestre curs ";
	const TEXT_FACTURACIO_ABRIL = "3er Trimestre curs ";
	const RECARREC_REBUT_RETORNAT = 2.00;
	
	
	const ETIQUETES_FILES = 7;
	const ETIQUETES_COLUMNES = 3;
	const TAB_SECCIONS = 0;
	const TAB_ACTIVITATS = 1;
	//const TAB_REBUTS = 2;
	const TAB_CAIXA = 2;
	const TAB_AVALADORS = 3;
	const TAB_OBSERVACIONS = 4;
	
	const TAB_CURS_INFO = 0;
	const TAB_CURS_FACTURACIO = 3;
	
	// Fitxer domiciliacions
	const PATH_TO_FILES = '/../../../../fitxers/';
	const PATH_REL_TO_DOMICILIACIONS_FILES = 'domiciliacions/';
	const PATH_REL_TO_DECLARACIONS_FILES = 'declaracions/';
	const PATH_REL_TO_ESBORRATS_FILES = 'esborrats/';
	// Pedent de canviar fora document root	
	const PATH_TO_WEB_FILES = '/../../../../web/';
	const PATH_REL_TO_UPLOADS = 'uploads/';
	const NIF_FOMENT = "G08917635"; // 9
	const SUFIJO = "002";  // Ho diu el Toni
	const NOM_FOMENT = "FOMENT MARTINENC"; 
	const H_PRESENTADOR_REG = "51";  // 2
 	const H_PRESENTADOR_DADA = "80"; // 2
	const H_ORDENANT_REG = "53";  // 2
	const H_ORDENANT_DADA = "80"; // 2
	const H_ORDENANT_ENTITAT = "2100";
	const H_ORDENANT_OFICINA = "0961";
	const H_ORDENANT_DC = "75";
	const H_ORDENANT_CC = "0200009007";
	const H_ORDENANT_PROCEDIMENT = "01";
	const R_INDIVIDUAL_OBL_REG = "56";  // 2
	const R_INDIVIDUAL_OBL_DADA = "80"; // 2
	const R_INDIVIDUAL_OPT_REG = "56";  // 2
	const R_INDIVIDUAL_TOT_ORD = "58";  // 2
	const R_INDIVIDUAL_TOT_GEN = "59";  // 2
	const R_INDIVIDUAL_TOT_DATA = "80";  // 2
	// Fitxer declaració donacions Model 182
	const MODEL_DECLARACIO = '182';
	const REGISTRE_DECLARANT = '1';
	const REGISTRE_PERCEPTOR = '2';
	const TIPUS_SUPORT = 'T'; // 'T' transmissió o 'C' CD-Rom
	const NATURA_DECLARANT = '1'; // Preguntar
	const CLAU_DONATIU = 'A'; //  Preguntar
	const DONATIU_EN_ESPECIES = ' '; 
	const NATURA_DECLARAT = 'F'; // Persona física
	const DEDUCCIO_AUTO = '02500';  // 25%	
	
	protected static $select_per_page_options; // Veure getPerPageOptions()
	protected static $csv_header_persones; // Veure getCSVHeader_Persones()
	protected static $csv_header_seccions; // Veure getCSVHeader_Seccions()
	protected static $csv_header_activitats; // Veure getCSVHeader_Activitats()
	protected static $csv_header_rebuts; // Veure getCSVHeader_Rebuts()
	protected static $csv_header_infoseccions; // Veure getCSVHeader_InfoSeccions()
	protected static $csv_header_membresanual; // Veure getCSVHeader_membresanual()
	protected static $csv_header_membresfraccionat; // Veure getCSVHeader_membresfraccionat()
	protected static $tipuspagaments; // Veure getTipusPagament()
	protected static $estats; // Veure getEstats()
	protected static $carrecs; // Veure getCarrecsJunta()
	protected static $provincies; // Veure getCodiProvincia()
	protected static $comunitats; // Veure getCodiComunitat()
	protected static $tipusesdeveniment; // Veure getTipusEsdeveniment()
	protected static $tipusprogramacions; // Veure getTipusProgramacions()
	protected static $diessetmana; // Veure getDiesSetmana()
	protected static $diesdelmes; // Veure getDiesDelMes()
	protected static $tipusdesoci; // Veure getTipusDeSoci()
	protected static $motiusbaixa; // Veure getMotiusDeBaixa()
	
	
	/**
	 * Array possibles tipus de soci
	 */
	public static function getTipusDeSoci() {
		if (self::$tipusdesoci == null) {
			self::$tipusdesoci = array(
					1 => 'numerari',
					2 => 'propietari',
					3 => 'de mèrit',
					4 => 'honorari',
					5 => 'protector',
					6 => 'adherit'
			);
		}
		return self::$tipusdesoci;
	}
	 
	/**
	 * Obté tipus de soci
	 */
	public static function getTipusSoci($index) {
		$tipus = UtilsController::getTipusDeSoci();
		if (isset($tipus[$index])) return $tipus[$index];
	
		return '';
	}
	
	/**
	 * Array possibles motius de baixa
	 */
	public static function getMotiusDeBaixa() {
		if (self::$motiusbaixa == null) {
			self::$motiusbaixa = array(
					1 => 'defunció',
					2 => 'voluntària',
					3 => 'recuperació',
					4 => 'NS/NC'
			);
		}
		return self::$motiusbaixa;
	}
	
	/**
	 * Array possibles tipus de pagament
	 */
	public static function getTipusPagament($index) {
		if (self::$tipuspagaments == null) {
			self::$tipuspagaments = array(
					UtilsController::INDEX_FINESTRETA => 'finestreta',
					UtilsController::INDEX_DOMICILIACIO => 'domiciliacions',
					UtilsController::INDEX_FINES_RETORNAT => 'fines. retornat'
			);
		}
		if (isset(self::$tipuspagaments[$index])) return self::$tipuspagaments[$index];
		 
		return "";
	}
	
	/**
	 * Array provincies, per omplir model 182
	 */
	public static function getCodiProvincia($provincia) {
		if (self::$provincies == null) {
			self::$provincies = array(
					'ÁLAVA' => '01', 'ALBACETE' => '02', 'ALICANTE' => '03', 'ALMERÍA' => '04', 'ASTURIAS' => '33', 'ÁVILA' => '05',
					'BADAJOZ' => '06', 'BARCELONA' => '08', 'BURGOS' => '09', 'CÁCERES' => '10', 'CÁDIZ' => '11', 'CANTABRIA' => '39',
					'CASTELLÓN' => '12', 'CEUTA' => '51', 'CIUDAD REAL' => '13', 'CÓRDOBA' => '14', 'CORUÑA' => '15', 'CUENCA' => '16',
					'GIRONA' => '17', 'GRANADA' => '18', 'GUADALAJARA' => '19', 'GUIPÚZCOA' => '20', 'HUELVA' => '21', 'HUESCA' => '22',
					'ILLES BALEARS' => '07', 'JAÉN' => '23', 'LEÓN' => '24', 'LLEIDA' => '25', 'LUGO' => '27', 'MADRID' => '28', 
					'MÁLAGA' => '29', 'MELILLA' => '52', 'MURCIA' => '30', 'NAVARRA' => '31', 'OURENSE' => '32', 'PALENCIA' => '34',
					'PALMAS, LAS' => '35', 'PONTEVEDRA' => '36', 'LA RIOJA' => '26', 'SALAMANCA' => '37', 'S.C.TENERIFE' => '38',
					'SEGOVIA' => '40', 'SEVILLA' => '41', 'SORIA' => '42', 'TARRAGONA' => '43', 'TERUEL' => '44', 'TOLEDO' => '45',
					'VALENCIA' => '46', 'VALLADOLID' => '47', 'VIZCAYA' => '48', 'ZAMORA' => '49', 'ZARAGOZA' => '50', 'NO RESIDENTES' => '99'
			);
		}
		if (isset(self::$provincies[strtoupper($provincia)])) return self::$provincies[strtoupper($provincia)];
			
		return "08"; // Per defecte Barcelona
	}
	
	/**
	 * Array comunitats autònomes, per omplir model 182
	 */
	public static function getCodiComunitat($provincia) {
		if (self::$comunitats == null) {
			self::$comunitats = array(
					'ANDALUCÍA' => array('codi' => '01', 'provincies' => array('ALMERÍA', 'CÁDIZ', 'CÓRDOBA', 'GRANADA', 'HUELVA', 'JAÉN', 'MÁLAGA', 'SEVILLA' )),
					'ARAGÓN' => array('codi' => '02', 'provincies' => array('HUESCA', 'TERUEL', 'ZARAGOZA')),
					'PRINCIPADO DE ASTURIAS' => array('codi' => '03', 'provincies' => array()),
					'ILLES BALEARS' => array('codi' => '04', 'provincies' => array('ILLES BALEARS')),
					'CANARIAS' => array('codi' => '05', 'provincies' => array()),
					'CANTABRIA' => array('codi' => '06', 'provincies' => array('CANTABRIA')),
					'CASTILLA-LA MANCHA' => array('codi' => '07', 'provincies' => array('ALBACETE', 'CIUDAD REAL', 'GUADALAJARA', 'CUENCA', 'TOLEDO')),
					'CASTILLA Y LEÓN' => array('codi' => '08', 'provincies' => array('ÁVILA', 'BURGOS', 'LEÓN', 'PALENCIA', 'SALAMANCA', 'SEGOVIA', 'SORIA', 'VALLADOLID', 'ZAMORA')),
					'CATALUNYA' => array('codi' => '09', 'provincies' => array('BARCELONA', 'GIRONA', 'LLEIDA', 'TARRAGONA')),
					'EXTREMADURA' => array('codi' => '10', 'provincies' => array('BADAJOZ', 'CÁCERES')),
					'GALICIA' => array('codi' => '11', 'provincies' => array('CORUÑA', 'LUGO', 'OURENSE', 'PONTEVEDRA')),
					'MADRID' => array('codi' => '12', 'provincies' => array('MADRID')),
					'REGIÓN DE MURCIA' => array('codi' => '13', 'provincies' => array()),
					'LA RIOJA' => array('codi' => '16', 'provincies' => array('LA RIOJA')),
					'COMUNIDAD VALENCIANA' => array('codi' => '17', 'provincies' => array('ALICANTE', 'CASTELLÓN', 'VALENCIA'))
			);
		}
		
		$provincia = strtoupper($provincia);
		foreach (self::$comunitats as $comunitat) {
			if (in_array($provincia, $comunitat['provincies']) ) return $comunitat['codi'];
		}
			
		return str_repeat(" ",2);
	}
	
	
	/**
	 * Array possibles estats
	 */
	public static function getEstats($index) {
		/* Estats si el rebut existeix */
		
		if (self::$estats == null) {
			self::$estats = array(
					UtilsController::INDEX_ESTAT_PENDENT => 'Rebut no emès',	// Rebuts pendents de generar
					UtilsController::INDEX_ESTAT_EMES => 'Rebut pendent',	// Encara no s'han facturat els rebuts
					UtilsController::INDEX_ESTAT_FACTURAT => 'Rebut cobrat',  // S'ha afegit el rebut a una facturació per enviar al banc. Idem cobrat
					UtilsController::INDEX_ESTAT_RETORNAT => 'Rebut retornat',  // Rebut retornat
					UtilsController::INDEX_ESTAT_COBRAT => 'Rebut cobrat',	// S'ha confirmat el cobrament
					UtilsController::INDEX_ESTAT_ANULAT => 'Rebut anul·lat'   // Rebut anul·lat
			);
		}
		
		if (isset(self::$estats[$index])) return self::$estats[$index];
		
		return "";
	}
	
	/**
	 * Array possibles estats resumit cobrat o pendent
	 */
	public static function getEstatsResum($index) {
		/* Estats si el rebut pot no existir */
		if ($index == UtilsController::INDEX_ESTAT_PENDENT) return "No emès";
		if ($index == UtilsController::INDEX_ESTAT_EMES) return "Pendent";
		if ($index == UtilsController::INDEX_ESTAT_FACTURAT) return "Facturat";
		if ($index == UtilsController::INDEX_ESTAT_RETORNAT) return "Retornat";
		if ($index == UtilsController::INDEX_ESTAT_COBRAT) return "Cobrat";
		if ($index == UtilsController::INDEX_ESTAT_ANULAT) return "Anul·lat";
		if ($index == UtilsController::INDEX_ESTAT_EXEMPT) return "Exempt";
		
		return "Pendent";
	}
	
	/**
	 * Array possibles càrrecs de la junta
	 */
	
	public static function getArrayCarrecsJunta() {
		if (self::$carrecs == null) {
			self::$carrecs = array(
					UtilsController::INDEX_CARREC_PRESIDENT => 'President/a',
					UtilsController::INDEX_CARREC_SOTSPRESIDENT => 'Sots-president',
					UtilsController::INDEX_CARREC_SECRETARI => 'Secretari/a',
					UtilsController::INDEX_CARREC_TRESORER => 'Tresorer/a',
					UtilsController::INDEX_CARREC_VOCAL => 'Vocal',
					UtilsController::INDEX_CARREC_ASSESSOR => 'Assessor/a',
					UtilsController::INDEX_CARREC_SOTSSECRETARI => 'Sots-secretari/a',
					UtilsController::INDEX_CARREC_COMPTABLE => 'Comptable',
					UtilsController::INDEX_CARREC_RRPP => 'Relacions públiques',
					UtilsController::INDEX_CARREC_REPRESENTANT => 'Representant',
			);
		}
		return self::$carrecs;
	}
	
	public static function getCarrecJunta($index) {
		$carrecs = UtilsController::getArrayCarrecsJunta();
		if (isset($carrecs[$index])) return $carrecs[$index];
		
		return "";
	}
	
	
	/**
	 * Array possibles tipus d'esdeveniments
	 */
	public static function getTipusEsdeveniments() {
		if (self::$tipusesdeveniment == null) {
			self::$tipusesdeveniment = array(
					UtilsController::INDEX_EVENT_SESSIOCURS => 'Sessió curs',
					UtilsController::INDEX_EVENT_TALLER 	=> 'Taller',
					UtilsController::INDEX_EVENT_ANIVERSARI => 'Aniversari',
					UtilsController::INDEX_EVENT_CELEBRACIO => 'Celebració',
					UtilsController::INDEX_EVENT_SOPAR 		=> 'Sopar',
					UtilsController::INDEX_EVENT_TEATRE 	=> 'Teatre',
					UtilsController::INDEX_EVENT_CAMPIONAT 	=> 'Campionat',
					UtilsController::INDEX_EVENT_ALTRES 	=> 'Altres',
			);
		}
		return self::$tipusesdeveniment;
	}
	
	public static function getTipusEsdeveniment($index) {
		$events = UtilsController::getArrayCarrecsJunta();
		if (isset($events[$index])) return $events[$index];
		
		return "";
	}
	
	/**
	 * Array tipus programacions calendari cursos
	 */
	public static function getTipusProgramacions() {
		if (self::$tipusprogramacions == null) {
			self::$tipusprogramacions = array(
					UtilsController::PROG_SETMANAL => UtilsController::PROG_SETMANAL,
					UtilsController::PROG_MENSUAL => UtilsController::PROG_MENSUAL,
					UtilsController::PROG_SESSIONS => UtilsController::PROG_SESSIONS,
			);
		}
		return self::$tipusprogramacions;
	}
	
	/**
	 * Array dies de la setmana
	 */
	public static function getDiesSetmana() {
		if (self::$diessetmana == null) {
			self::$diessetmana = array(
					UtilsController::INDEX_DILLUNS => UtilsController::DIA_DILLUNS,
					UtilsController::INDEX_DIMARTS => UtilsController::DIA_DIMARTS,
					UtilsController::INDEX_DIMECRES => UtilsController::DIA_DIMECRES,
					UtilsController::INDEX_DIJOUS => UtilsController::DIA_DIJOUS,
					UtilsController::INDEX_DIVENDRES => UtilsController::DIA_DIVENDRES,
			);
		}
		return self::$diessetmana;
	}
	public static function getDiaSetmana($index) {
		$dies = UtilsController::getDiesSetmana();
		if (isset($dies[$index])) return $dies[$index];
	
		return "";
	}
	
	/**
	 * Array dies de la setmana
	 */
	public static function getDiesDelMes() {
		if (self::$diesdelmes == null) {
			self::$diesdelmes = array(
					UtilsController::INDEX_DIAMES_PRIMER => 'primer',
					UtilsController::INDEX_DIAMES_SEGON => 'segon',
					UtilsController::INDEX_DIAMES_TERCER => 'tercer',
					UtilsController::INDEX_DIAMES_QUART => 'quart',
			);
		}
		return self::$diesdelmes;
	}
	public static function getDiaDelMes($index) {
		$dies = UtilsController::getDiesDelMes();
		if (isset($dies[$index])) return $dies[$index];
	
		return "";
	}
	
	/**
	 * Obté servei
	 */
	public static function getServeis()
	{
		global $kernel;
	
		if ('AppCache' == get_class($kernel)) {
			$kernel = $kernel->getKernel();
		}
		 
		$serveis = $kernel->getContainer()->get('foment.serveis');
		return $serveis;
	}
	
	/**
	 * Obté cursos any anterior i posterior
	 */
	public static function getCursosCreables() {
	
		$anyInici = date('Y')-1;
		$anyFinal2 = date('y');
		 
		$cursosCreables = array();
		
		for ($i = 0; $i < 3; $i++) {
			$curs = $anyInici++.'-'.$anyFinal2++;
			$cursosCreables[$curs] = $curs;
		}
		
		return $cursosCreables;
	}
	
	/**
	 * Obté llista ordinals 1er, 2n, 3er, 4rt ...
	 */
	public static function getOrdinalNumbersSeq($max) {
	
		$locale = 'ca_ES';
		$nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
		$ordinals = array();
		for ($i = 0; $i < $max; $i++) $ordinals[] = $nf->format($i);
	
		return implode(",",$ordinals);
	}
	
	/**
	 * Total seccions actives
	 * 
	 * @param Request $request
	 * @return int
	 */	
	public function utiltotalseccionsAction(Request $request) {
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$total = $this->queryTotal('Seccio', true);
		
    	$response->setContent(json_encode( $total ));
		return $response;
	}
	/**
	 * Total socis actius
	 *
	 * @param Request $request
	 * @return int
	 */
	public function utiltotalsocisAction(Request $request) {
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$total = $this->queryTotal('Soci', true);
		
		$response->setContent(json_encode( $total ));
		return $response;
	}
	/**
	 * Total cursos iniciats i no finalitzats
	 *
	 * @param Request $request
	 * @return int
	 */
	public function utiltotalcursosAction(Request $request) {
		$response = new Response();
		$response->headers->set('Content-Type', 'application/json');
		$em = $this->getDoctrine()->getManager();
		
		$strQuery = 'SELECT COUNT(a.id) FROM Foment\GestioBundle\Entity\Activitat a ';
		$strQuery .= 'WHERE a.finalitzat = 0';
		
		$query = $em->createQuery($strQuery);
		
		$total = $query->getSingleScalarResult();
		
		$response->setContent(json_encode( $total ));
		return $response;
	}
	
	
	public function jsonpreuseccionsAction(Request $request) {
		//foment.dev/jsonpreuseccions?id[]=3&id[]=2....
		$response = new Response();
		
		$sociId = $request->query->get('id', 0);
		$strSeccionsSelected = $request->query->get('seccions', '');  // 1,2,3 ..
		
		$quotajuvenil = $request->query->get('quotajuvenil', 0) == 0?false:true;
		$familianombrosa = $request->query->get('familianombrosa', 0) == 0?false:true;
		$descomptefamilia = $request->query->get('descomptefamilia', 0) == 0?false:true;
		$pagfraccionat = $request->query->get('pagfraccionat', 1 == 1)?true:false;
		$percentexempt = $request->query->get('percentexempt', 0);
		$strDatanaixement = $request->query->get('datanaixement', '');
		
		$datanaixement = null;
		if ($strDatanaixement != '') $datanaixement = \DateTime::createFromFormat('d/m/Y', $strDatanaixement);
		
		$em = $this->getDoctrine()->getManager();
		$soci = $em->getRepository('FomentGestioBundle:Soci')->find($sociId);

		if ($soci != null) {
			if ($datanaixement != null) $soci->setDatanaixement($datanaixement);
			if ($quotajuvenil == false) $quotajuvenil = $soci->esJuvenil(); // No forçada la quota juvenil  
		} else {
			$aux = new Soci();
			if ($datanaixement != null) $aux->setDatanaixement($datanaixement);
			if ($quotajuvenil == false) $quotajuvenil = $aux->esJuvenil(); // No forçada la quota juvenil
		}
		
		//$import = $quota;
		$import = 0;
		$current = new \DateTime('now');
		$currentDay = $current->format('z');
		$currentYear = date('Y');
		foreach (explode(",", $strSeccionsSelected) as $secid)  {
			
			$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($secid);
						
			if ($seccio != null) {
				// Quota sencera. Pantalla soci assignació i anul·lació seccions
				$serveis = $this->get('foment.serveis');
				
				$diainici = $currentDay;
				if ($soci != null) {
					$membre = $soci->getMembreBySeccioId($secid);
					if ($membre != null) {
						if ($membre->getDatainscripcio()->format('Y') == $currentYear) $diainici = $membre->getDatainscripcio()->format('z');
						else $diainici = 0;
					}
				}
				
				$quota = $serveis->quotaSeccioAny($quotajuvenil, $familianombrosa, $descomptefamilia, $percentexempt, $seccio, $currentYear, $diainici);
				
				$import += $quota;
			}
		}
		
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode( number_format($import, 2, ',', '.') ));
		return $response;
	}
	
	public function jsonpersonesAction(Request $request) {
		//foment.dev/jsonpersones?nom=zubi&activitat=4&seccio=0  ==>
		$response = new Response();
	
		$em = $this->getDoctrine()->getManager();
		$query = null;
	
		$nom = $request->get('nom', '');
		
		if (strlen($nom) >= self::MIN_INPUT_NOMCOGNOMS) {  // 3 dígits mínim
			$activitatid = $request->get('activitat', 0);
			$seccioid = $request->get('seccio', 0);
			
			$ids = array(0);
			if ($activitatid > 0) {
				$strQuery = "SELECT p FROM Foment\GestioBundle\Entity\Participant p ";
				$strQuery .= " WHERE p.activitat = :activitat AND p.datacancelacio IS NULL  ";
				$query = $em->createQuery($strQuery)->setParameter('activitat', $activitatid);
				
				$result = $query->getResult();
				foreach ($result as $res) $ids[] = $res->getPersona()->getId();
				
				$strQuery = "SELECT p FROM Foment\GestioBundle\Entity\Persona p ";
				$strQuery .= " WHERE CONCAT(CONCAT(p.nom, ' '), p.cognoms) LIKE :value ";
				$strQuery .= " AND p.id NOT IN (:ids) ";
				$strQuery .= " ORDER BY p.cognoms, p.nom ";
			}
			
			if ($seccioid > 0) {
				$strQuery = "SELECT m FROM Foment\GestioBundle\Entity\Membre m ";
				$strQuery .= " WHERE m.seccio = :seccio AND m.datacancelacio IS NULL ";
				$query = $em->createQuery($strQuery)->setParameter('seccio', $seccioid);
				$result = $query->getResult();
				foreach ($result as $res) $ids[] = $res->getSoci()->getId();
				
				$strQuery = "SELECT s FROM Foment\GestioBundle\Entity\Soci s ";
				$strQuery .= " WHERE CONCAT(CONCAT(s.nom, ' '), s.cognoms) LIKE :value ";
				$strQuery .= " AND s.id NOT IN (:ids) AND s.databaixa IS NULL ";	// Socis mirar també no estiguin donats de baixa 
				$strQuery .= " ORDER BY s.cognoms, s.nom ";
			}
			
			if ($activitatid == 0 && $seccioid == 0) {
				$strQuery = "SELECT p FROM Foment\GestioBundle\Entity\Persona p ";
				$strQuery .= " WHERE CONCAT(CONCAT(p.nom, ' '), p.cognoms) LIKE :value ";
				$strQuery .= " AND p.id NOT IN (:ids) ";
				$strQuery .= " ORDER BY p.cognoms, p.nom ";
			}
			
			
			
			$query = $em->createQuery($strQuery)
						->setParameter('value', '%' . $nom . '%')
						->setParameter('ids', $ids); 
		}
	
		// Si retorna varis resultat => array ( array ('id' => ? , 'text' => ?), array ('id' => ? , 'text' => ?) ... )
		$search = array();
		if ($query != null) {
			$result = $query->getResult();
			foreach ($result as $p) {
				$text = '';
				if ($p->esSoci()) {
					if ($p->esSociVigent()) $text = $p->getNumSoci().'-'.$p->getNomCognoms();
					else $text = '(baixa) '.$p->getNomCognoms();
				} else {
					$text = '(no soci) '.$p->getNomCognoms();
				}
				$search[] = array("id" => $p->getId(), "text" => $text);
			}
		}
		
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($search));
	
		return $response;
	}
	
	public function jsonpersonaAction(Request $request) {
		//foment.dev/jsonperson?id=32
		$response = new Response();

		$id = $request->get('id', '');
		
		$em = $this->getDoctrine()->getManager();
		$persona = $em->getRepository('FomentGestioBundle:Persona')->find($id);
		
		// Si retorna un resultat => array ('id' => ? , 'text' => ? )
		$search = array("id" => $id, "text" => $persona->getNumSoci().'-'.$persona->getNomCognoms());
	
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($search));
		return $response;
	}

	public function jsonactivitatsAction(Request $request) {
		//foment.dev/jsonactivitats?action=byDesc&desc=aaa  ==> action byDesc For debug
		//foment.dev/jsonactivitats?action=byId&id[]=1&id[]=2 ==> action byId For debug
		$response = new Response();

		$em = $this->getDoctrine()->getManager();
		$query = null;
		
		$action = $request->get('action', '');
		switch($action) {
			case 'byDesc':  // Cerca a partir de les dades d'usuari
				$desc = $request->get('desc', '');
				if (strlen($desc) >= self::MIN_INPUT_ACTIVITATS) {  // 3 dígits mínim
					$excepcions = $request->get('excepcions', '');

					$ids = array(); 
					if ($excepcions != '') $ids = explode(',', $excepcions);
					$ids[] = 0; // No existeix la activitat 0, garanteix un vector amb dades
					
					$query = $em->createQuery('SELECT a
										FROM Foment\GestioBundle\Entity\Activitat a
										WHERE a.descripcio LIKE :value AND a.id NOT IN (:ids)
										AND a.databaixa IS NULL
										ORDER BY a.descripcio')
															->setParameter('value', '%' . $desc . '%')
															->setParameter('ids', $ids);
				}
				
				break;
			case 'byId':  // Llista carregada amb valors inicials
				$ids = $request->get('activitats', array());
				
				if (count($ids) > 0) {
					$query = $em->createQuery('SELECT a
										FROM Foment\GestioBundle\Entity\Activitat a
										WHERE a.id IN (?1) ORDER BY a.descripcio') ->setParameter(1, $ids);
				}
				
				break;
			default:
				$response->setStatusCode(Response::HTTP_BAD_REQUEST);
		}
		
		$search = array();
		if ($query != null) {
			$result = $query->getResult();
			foreach ($result as $activitat) {
				$search[] = array("id" => $activitat->getId(), "text" => $activitat->getDescripcio().' - '.$activitat->getCurs());
			}
		}
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode($search));
		
		return $response;
	}
	
	
	/*
	 * Consulta Ajax que retorna la taula d'activitats per a una persona
	* actualitzada amb la selecció de l'usuari
	*/
	public function jsonparticipacionsAction(Request $request)
	{
		if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
			$response = new JsonResponse();
			$response->setStatusCode(Response::HTTP_BAD_REQUEST);
			$response->setData(array('message' => 'accio no permesa'));
			return $response;
		}

		$stractivitats = $request->query->get('activitatsnoves', ''); // Id's noves activitats
		
		$activitatsids = array();
		if ($stractivitats != '') $activitatsids = explode(',',$stractivitats); // array ids activitats llista
	
		$em = $this->getDoctrine()->getManager();
		
		$files = '';
		foreach ($activitatsids as $actid)  {
			$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($actid);
			if ($activitat != null) {
				$files .= $this->renderView('FomentGestioBundle:Includes:filaactivitatpersona.html.twig', array('activitat' => $activitat, 'changed' => true ));
			}	
		}
	
		return new Response($files);
	}
	
	
    public function jsoncodibancAction(Request $request) {
    	//foment.dev/jsoncodibanc?codi=0019  ==> For debug
    	$banc = $this->getDoctrine()
    		->getRepository('FomentGestioBundle:AuxBanc')
    		->find($request->get('codi',''));
    	
    	$response = new Response();
    	$response->headers->set('Content-Type', 'application/json');
    	
    	if ($banc == null) {
    		$response->setContent(json_encode('codi d\'entitat bancària desconegut'));
    	}
    	else {
    		$response->setContent(json_encode($banc->getNom()));
    	}
    	
    	return $response;
    }
    
    public function jsonimportfacturacioAction(Request $request) { 
    	//foment.dev/jsonimportfacturacio?facturacio=10&deutor=10  ==> For debug
    	
    	 
    	$facturacio = $this->getDoctrine()->getRepository('FomentGestioBundle:Facturacio')->find($request->get('facturacio',0));
    	$deutor = $this->getDoctrine()->getRepository('FomentGestioBundle:Facturacio')->find($request->get('deutor',0));
    	
    	$response = new Response();
    	$response->headers->set('Content-Type', 'application/json');
    	 
    	if ($facturacio == null) return $response->setContent(json_encode(0));
    	
    	if ($deutor == null || !$deutor->esSoci()) return $response->setContent(json_encode($facturacio->getImportactivitatnosoci()));
    	
    	return $response->setContent(json_encode($facturacio->getImportactivitat()));
    }
    
    public function jsonpoblacionsAction(Request $request) {
    	$search = $this->consultaAjaxPoblacions($request->get('term'), $request->get('field'));
    	//$search = array();
    	//$search = array('Abrera', 'Agramunt', 'Agullana');
    	
    	$response = new Response();
    	
    	
    	$response->setContent(json_encode($search));
    	$response->headers->set('Content-Type', 'application/json');
    	//$response->setStatusCode(Response::HTTP_BAD_REQUEST);
    	return $response;
    }

    protected function consultaAjaxPoblacions($value, $field) {
    	// foment.dev/jsonpoblacions?term=abx&field=municipi  ==> For debug
    	// Cerques només per a >= 2 lletres
    	
    	$search = array();
    	if (strlen($value) >= self::MIN_INPUT_POBLACIONS) {
    		$em = $this->getDoctrine()->getManager();
    		$query = $em->createQuery(
    				"SELECT DISTINCT m.".$field."
					FROM Foment\GestioBundle\Entity\AuxMunicipi m
					WHERE m.".$field." LIKE :value ORDER BY m.".$field."")
    					->setParameter('value', '%' . $value . '%');
    		$result = $query->getResult();
    		
    		foreach ($result as $res) $search[] = array("id" => $res[$field], "text" => $res[$field]);
    	}
    	
    	return $search;
    }
    
    public function jsonselectoranysAction(Request $request) {
    	
    	// Selector anys per certificat hisenda
    	$anysSelectable = $this->getAnysSelectableToNow();
    	 
    	$form = $this->createFormBuilder()
	    		->add('selectoranys', 'choice', array(
	    			'required'  => true,
	    			'choices'   => $anysSelectable,
	    			'data'		=> date('Y')-1  ))
	    		->getForm();
    	
    	return $this->render('FomentGestioBundle:Includes:selectoranys.html.twig',
	    		array('form' => $form->createView()));
    }
    
    
    /**
     * Quotes soci secció per any. Veure  quotaSeccioAny
     */
    public static function quotaMembreSeccioAny($membre, $any)
    {
    	$diainici = 0;
    	if ($any == $membre->getDatainscripcio()->format('Y')) $diainici = $membre->getDatainscripcio()->format('z');     	// z 	The day of the year (starting from 0)
    	
    	return UtilsController::getServeis()->quotaSeccioAny($membre->getSoci()->esJuvenil(), $membre->getSoci()->getFamilianombrosa(), 
    											$membre->getSoci()->getDescomptefamilia(), 
    											$membre->getSoci()->getExempt(), $membre->getSeccio(), $any, $diainici);
    }
    
    
    /**
     * Quotes soci secció per periode. Veure quotaSeccioAny
     */
    public static function quotaMembreSeccioPeriode($membre, $periode)
    {
    	$socirebut = $membre->getSoci();
    	if ($membre->getSeccio()->getSemestral() == true && $socirebut->getSocirebut() != null) $socirebut = $socirebut->getSocirebut();
    	
    	$diainici = 0;
    	if ($periode->getAnyperiode() == $membre->getDatainscripcio()->format('Y')) $diainici = $membre->getDatainscripcio()->format('z');     	// z 	The day of the year (starting from 0)
    	
    	
    	$quotaany = UtilsController::getServeis()->quotaSeccioAny($membre->getSoci()->esJuvenil(), $membre->getSoci()->getFamilianombrosa(), 
    												$socirebut->getDescomptefamilia(), 
    												$membre->getSoci()->getExempt(), $membre->getSeccio(), 
    												$periode->getAnyperiode(), $diainici);
    	// Exemple. Sense fraccionar	General(100%) 80 + Secció(100%) 15 	=> 1er semestre
    	//								General(0%) 0 + Secció(0) 0 		=> 2n semestre
    	// Exemple. Fraccionat  		General(50%) 40 + Secció(100%) 15 	=> 1er semestre
    	//								General(50%) 40 + Secció(0) 0 		=> 2n semestre
    	if ($membre->getSeccio()->getFraccionat() == true) return ( $quotaany / 2 ); // Quota sempre repartida entre els dos semestres
    	
    	// Inscripcions del segon trimestre. quota proporcional integra
    	if ($periode->getSemestre() == 2 && $membre->getDatainscripcio()->format('Y-m-d') > $periode->getDatainici()->format('Y-m-d')) return $quotaany;
    		
    	// Obtenir percentatges del fraccionament segons el periode
    	$percentfraccionament =  $periode->getPercentfragmentseccions();  // Percentatge fraccionat 2n semestre 
    	if ($membre->getSeccio()->esGeneral()) $percentfraccionament = $periode->getPercentfragmentgeneral(); // Percentatge fraccionat 2n semestre 
    	
    	//if ($periode->getSemestre() == 2) $percentfraccionament = 1 - $percentfraccionament;
    	
    	// El fraccionament es mira per soci, independent del grupfamiliar
    	if ($membre->getSoci()->getPagamentfraccionat() == true) return ( $quotaany * $percentfraccionament );  
    	
    	if ($periode->getSemestre() == 2) return 0;
    	
    	// inscripció anterior a l'any del periode, quota íntegra (Les inscripcions data posterior no arriben aquí)
    	return $quotaany; // sense fraccionament
    	
    }
    
    /**
     * Concepte per als detalls dels rebuts de les quotes soci secció
     */
    public static function concepteMembreSeccioRebut($membre, $anydades)
    {
    	$concepte = '';
    	$socirebut = $membre->getSoci();
    	if ($socirebut->getSocirebut() != null) $socirebut = $socirebut->getSocirebut();
    	
    	if ($membre->getSeccio()->esGeneral()){
    		$diainici = 0;
    		if ($anydades == $membre->getDatainscripcio()->format('Y')) $diainici = $membre->getDatainscripcio()->format('z'); // Proporcional
    		
    		if ($membre->getSoci()->getExempt() > 0) return $concepte .= UtilsController::CONCEPTE_REBUT_FOMENT_EXEMPT.' '.number_format($membre->getSoci()->getExempt(), 0, ',', '.').'%';   
    		
    		if ($membre->getSoci()->esJuvenil()) $concepte .= UtilsController::CONCEPTE_REBUT_FOMENT_JUVENIL;
    		
    		if ($socirebut->getDescomptefamilia()) $concepte .= UtilsController::CONCEPTE_REBUT_FOMENT_FAMILIAR;
    		
    		if ($diainici > 0) {
    			if (UtilsController::getServeis()->facturacionsIniciadesAny($anydades, $diainici)) $concepte .= UtilsController::CONCEPTE_REBUT_FOMENT_PROP . (365 - $diainici);
    		}
    		
    		if ($socirebut->getPagamentfraccionat() == true) $concepte .= UtilsController::CONCEPTE_REBUT_FOMENT_SEMESTRAL;
    		else $concepte .= UtilsController::CONCEPTE_REBUT_FOMENT_ANUAL;
    	} else {
    		//if ($membre->getSeccio()->esTerranova() && !$membre->getSoci()->esJuvenil()) return $concepte .= UtilsController::CONCEPTE_REBUT_FOMENT_EXEMPT;
    		
    		if ($membre->getSoci()->esJuvenil()) $concepte .= UtilsController::CONCEPTE_REBUT_FOMENT_JUVENIL;
    		
    		if ($membre->getSeccio()->esTerranova() && $membre->getSoci()->esJuvenil() && $membre->getSoci()->getFamilianombrosa())
    			$concepte .= " " .UtilsController::CONCEPTE_REBUT_FOMENT_FAMNOM;
    	}
    	
    	return $concepte;
    }
    
    /**
     * Array amb els noms dels mesos en català
     */
    public static function getMonthLocale()
    {
	    $mesos = array();
	    
	    setlocale(LC_TIME, 'ca_ES', 'Catalan_Spain', 'Catalan');
	    for( $mes=1; $mes <= 12; $mes++ ) {
	    	//$mesText =  $currentAnyMes->format('F \d\e Y');
	    	//$mesText = date("F \de Y", $currentAnyMes->format('U'));
	    	$mesText = utf8_encode(strftime("%B", strtotime(sprintf('%02s', $mes)."/01/".date('Y'))));
	    	//$mesText = date('F',strtotime('01/'.$mes.'/'.$current));
	    	$mesos[$mes] = $mesText;
	    }
	    return $mesos;
    }
    
    /**
     * Concepte per als detalls dels rebuts de les participacions en activitats
     */
    public static function concepteParticipantRebut($participacio)
    {
    	return UtilsController::CONCEPTE_REBUT_ACTIVITAT_PREFIX . 
    			$participacio->getActivitat()->getDataactivitat()->format('Y').'. '
    			.$participacio->getActivitat()->getDescripcio();
    }
    
    /**
     * Array possibles tipus de soci
     */
    public static function getPerPageOptions() {
    	if (self::$select_per_page_options == null) {
    		self::$select_per_page_options = array(
    				'5' => '5 per pàgina', '10' => '10 per pàgina', '15' => '15 per pàgina', '999' => 'tots'
    		);
    	}
    	return self::$select_per_page_options;
    }
    
    /**
     * Array header export persones / socis
     */
    public static function getCSVHeader_Persones() {
    	if (self::$csv_header_persones == null) {
    		self::$csv_header_persones = array( '"id"', '"soci"', '"numero"', '"alta"', '"nom"', 
    											'"cognoms"', '"dni"', '"sexe"', '"mail"',
    											'"telèfon"', '"mòbil"','"adreça"','"poblacio"','"cp"','"provincia"', '"datanaixement"', 
    											'"vist i plau"', '"databaixa"' );
    	}
    	return self::$csv_header_persones;
    }
    
    /**
     * Array header export seccions
     */
    public static function getCSVHeader_Seccions() {
    	if (self::$csv_header_seccions == null) {
    		self::$csv_header_seccions = array( '"id"', '"nom"', '"any"', '"quota"', '"quota juvenil"', 
    											'"membres"' );
    	}
    	return self::$csv_header_seccions;
    }
    
    /**
     * Array header export activitats
     */
    public static function getCSVHeader_Activitats() {
    	if (self::$csv_header_activitats == null) {
    		self::$csv_header_activitats = array( '"id"', '"descripció"', '"curs"', '"quota soci"', '"quota no soci"',
    				   		'"participants"' ); 
        }
        return self::$csv_header_activitats;
    }
        
        
    /**
     * Array header export rebuts
     */
    public static function getCSVHeader_Rebuts() {
    	if (self::$csv_header_rebuts == null) {
   			self::$csv_header_rebuts = array( '"id"', '"num"', '"deutor"', '"import"', '"periode"', 
				'"facturacio"', '"tipuspagament"', '"tipusrebut"',
				 '"dataemissio"', '"dataretornat"','"datapagament"','"databaixa"', '"correccio"',
   				// Camps detall
   				 '"id detall"', '"num detall"', '"beneficiari"', '"concepte detall"', '"import detall"', 
   				 '"seccio"', '"activitat"', '"databaixa detall"' );
        	return self::$csv_header_rebuts;
        }
    }
    
    /**
     * Array header export rebuts
     */
    public static function getCSVHeader_InfoSeccions() {
    	if (self::$csv_header_infoseccions == null) {
   			self::$csv_header_infoseccions = array( '"id"', '"seccio"', '"total"', '"# total"', '"cobrats"',  '"# cobrats"', '"pendents"',  '"# pendents"',
   					'"anul·lats"', '"# anul·lats"', '"domiciliats"', '"# domiciliats"', '"retornats"','"# retornats"', 
   					'"ret. cobrats"', '"# ret. cobrats"', '"finestreta"', '"# finestreta"', '"fin. cobrats "', '"# fin. cobrats "' );
        	return self::$csv_header_infoseccions;
        }
    }
    
    
    /**
     * Array header export membres secció anual (no fraccionada)
     */
    public static function getCSVHeader_membresanual() {
    	if (self::$csv_header_membresanual == null) {
    		
    		$afegit = array( '"quota '.date('Y').'"', '"tipus"', '"Estat"' );
    		self::$csv_header_membresanual = array_merge(self::getCSVHeader_Persones(), $afegit); 

    		return self::$csv_header_membresanual;
    	}
    }
    
    /**
     * Array header export membres secció pagament fraccionat
     */
    public static function getCSVHeader_membresfraccionat() {
    	if (self::$csv_header_membresfraccionat == null) {
    
    		$afegit = array( '"quota '.date('Y').'"', '"tipus"', '"Semestre"', '"Import"', '"Estat"', '"Semestre"', '"Import"', '"Estat"' );
    		self::$csv_header_membresfraccionat = array_merge(self::getCSVHeader_Persones(), $afegit);
    
    		return self::$csv_header_membresfraccionat;
    	}
    }
    
    
    public static function formErrorsNotification($controller, $entity) {
    	$controller->get('session')->getFlashBag()->add('notice',	'Form not valid');
    	$errors = $controller->get('validator')->validate($entity);
     
	    foreach ($errors as $error) {
		    $controller->get('session')->getFlashBag()->add('notice',	$error->getMessage());
	    }
    }
    
    public static function format_phone($telephone) {
    	$strTelephone = $telephone."";
    	if (!is_numeric($telephone) || strlen($strTelephone) > 9) { return $telephone; }
    	
    	$strTelephone = substr($strTelephone, 0, 3) . '-' . substr($strTelephone, 3, 3) . '-' . substr($strTelephone, 6, 3);
    
    	return $strTelephone;
    }
    
    
    public static function uploadAndScale($file, $name, $maxwidth, $maxheight) {
    	/*
    	 *   Imagick
    	*   sudo apt-get install php-pear
    	*   apt-get install php5-dev
    	*   pear channel-update pear.php.net  ¿?
    	*   pear upgrade PEAR					¿?
    	*	 sudo apt-get install imagemagick libmagickwand-dev
    	*	 sudo pecl install imagick
    
    	configuration option "php_ini" is not set to php.ini location
    	You should add "extension=imagick.so" to php.ini
    
    	*   sudo apt-get install php5-imagick
    	*	 sudo service apache2 restart
    	*
    	*/
    
    	//http://jan.ucc.nau.edu/lrm22/pixels2bytes/calculator.htm
    
    	/* Format jpeg mida inferior a 35k */
    
    	$thumb = new \Imagick($file->getPathname());
    	//$thumb->readImage($file->getPathname());
    	$thumb->setImageFormat("jpeg");
    	$thumb->setImageCompressionQuality(85);
    	$thumb->setImageResolution(72,72);
    	//$thumb->resampleImage(72,72,\Imagick::FILTER_UNDEFINED,1);
    
    	// Inicialment escalar a una mida raonable
    	if($thumb->getImageWidth() > $maxwidth || $thumb->getImageHeight() > $maxheight) {
    		if($thumb->getImageWidth() > $maxwidth) $thumb->scaleImage($maxwidth, 0);
    		else $thumb->scaleImage(0, $maxheight);
    	}
    
    	//$i = 0;
    	/*while ($thumb->getImageLength() > 35840 and $i < 10 ) {  /// getImageLength no funciona
    	 $width = $image->getImageWidth();
    	$width = $width*0.8; // 80%
    	$thumb->scaleImage($width,0);
    	$i++;
    	}*/
    		
    	$nameAjustat = substr($name, 0, 33);
    	$nameAjustat = time() . "_". UtilsController::netejarNom($nameAjustat) . ".jpg";
    	$strPath = __DIR__.self::PATH_TO_FILES.self::PATH_REL_TO_UPLOADS.$nameAjustat;
    	$uploadReturn = $thumb->writeImage($strPath);
    	$thumb->clear();
    	$thumb->destroy();
    
    	if ($uploadReturn != true) {
    		throw new \Exception('No s\'ha pogut ajustar la foto');
    	}
    
    	return array('name' => $nameAjustat, 'path' => $strPath);
    }
    
    public static function netejarNom($string, $sense_espais = true)
    {
    	$string = trim($string);
    
    	$string = str_replace(
    			array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
    			array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
    			$string
    	);
    
    	$string = str_replace(
    			array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
    			array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
    			$string
    	);
    
    	$string = str_replace(
    			array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
    			array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
    			$string
    	);
    
    	$string = str_replace(
    			array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
    			array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
    			$string
    	);
    
    	$string = str_replace(
    			array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
    			array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
    			$string
    	);
    
    	$string = str_replace(
    			array('ñ', 'Ñ', 'ç', 'Ç'),
    			array('n', 'N', 'c', 'C',),
    			$string
    	);
    
    	if ($sense_espais == true) {
	    	//Esta parte se encarga de eliminar cualquier caracter extraño
	    	$string = str_replace(
	    			array("\\", "¨", "º", "-", "~",
	    					"#", "@", "|", "!", "\"",
	    					"·", "$", "%", "&", "/",
	    					"(", ")", "?", "'", "¡",
	    					"¿", "[", "^", "`", "]",
	    					"+", "}", "{", "¨", "´",
	    					">", "< ", ";", ",", ":",
	    					".", " "),
	    			"_",
	    			$string
	    	);
    	}
    
    
    	return $string;
    }
    
    
    /*!
     @function num2letras ()
    @abstract Dado un n?mero lo devuelve escrito.
    @param $num number - N?mero a convertir.
    @param $fem bool - Forma femenina (true) o no (false).
    @param $dec bool - Con decimales (true) o no (false).
    @result string - Devuelve el n?mero escrito en letra.
    
    NOOOOOOOOOOOOOOOOOOO CAL => 
    
    	$f = new \NumberFormatter("ca", \NumberFormatter::SPELLOUT);
    	$donacionsTxt = $f->format($donacions);
    
    */
    public static function num2letras($num, $fem = false, $dec = true) {
    	$matuni = array();
    	$matunisub = array();
    	$matdec = array();
    	$matsub = array();
    	$matmil = array();
    	$matuni[2]  = "dos";
    	$matuni[3]  = "tres";
    	$matuni[4]  = "cuatro";
    	$matuni[5]  = "cinco";
    	$matuni[6]  = "seis";
    	$matuni[7]  = "siete";
    	$matuni[8]  = "ocho";
    	$matuni[9]  = "nueve";
    	$matuni[10] = "diez";
    	$matuni[11] = "once";
    	$matuni[12] = "doce";
    	$matuni[13] = "trece";
    	$matuni[14] = "catorce";
    	$matuni[15] = "quince";
    	$matuni[16] = "dieciseis";
    	$matuni[17] = "diecisiete";
    	$matuni[18] = "dieciocho";
    	$matuni[19] = "diecinueve";
    	$matuni[20] = "veinte";
    	$matunisub[2] = "dos";
    	$matunisub[3] = "tres";
    	$matunisub[4] = "cuatro";
    	$matunisub[5] = "quin";
    	$matunisub[6] = "seis";
    	$matunisub[7] = "sete";
    	$matunisub[8] = "ocho";
    	$matunisub[9] = "nove";
    
    	
    	$matdec[2] = "veint";
    	$matdec[3] = "treinta";
    	$matdec[4] = "cuarenta";
    	$matdec[5] = "cincuenta";
    	$matdec[6] = "sesenta";
    	$matdec[7] = "setenta";
    	$matdec[8] = "ochenta";
    	$matdec[9] = "noventa";
    	$matsub[3]  = 'mill';
    	$matsub[5]  = 'bill';
    	$matsub[7]  = 'mill';
    	$matsub[9]  = 'trill';
    	$matsub[11] = 'mill';
    	$matsub[13] = 'bill';
    	$matsub[15] = 'mill';
    	$matmil[4]  = 'millones';
    	$matmil[6]  = 'billones';
    	$matmil[7]  = 'de billones';
    	$matmil[8]  = 'millones de billones';
    	$matmil[10] = 'trillones';
    	$matmil[11] = 'de trillones';
    	$matmil[12] = 'millones de trillones';
    	$matmil[13] = 'de trillones';
    	$matmil[14] = 'billones de trillones';
    	$matmil[15] = 'de billones de trillones';
    	$matmil[16] = 'millones de billones de trillones';
    	 
    	//Zi hack
    	$float=explode('.',$num);
    	$num=$float[0];
    
    	$num = trim((string)@$num);
    	if ($num[0] == '-') {
    		$neg = 'menos ';
    		$num = substr($num, 1);
    	}else
    		$neg = '';
    	while ($num[0] == '0') $num = substr($num, 1);
    	if ($num[0] < '1' or $num[0] > 9) $num = '0' . $num;
    	$zeros = true;
    	$punt = false;
    	$ent = '';
    	$fra = '';
    	for ($c = 0; $c < strlen($num); $c++) {
    		$n = $num[$c];
    		if (! (strpos(".,'''", $n) === false)) {
    			if ($punt) break;
    			else{
    				$punt = true;
    				continue;
    			}
    
    		}elseif (! (strpos('0123456789', $n) === false)) {
    			if ($punt) {
    				if ($n != '0') $zeros = false;
    				$fra .= $n;
    			}else
    
    				$ent .= $n;
    		}else
    
    			break;
    
    	}
    	$ent = '     ' . $ent;
    	if ($dec and $fra and ! $zeros) {
    		$fin = ' coma';
    		for ($n = 0; $n < strlen($fra); $n++) {
    			if (($s = $fra[$n]) == '0')
    				$fin .= ' cero';
    			elseif ($s == '1')
    			$fin .= $fem ? ' una' : ' un';
    			else
    				$fin .= ' ' . $matuni[$s];
    		}
    	}else
    		$fin = '';
    	if ((int)$ent === 0) return 'Cero ' . $fin;
    	$tex = '';
    	$sub = 0;
    	$mils = 0;
    	$neutro = false;
    	while ( ($num = substr($ent, -3)) != '   ') {
    		$ent = substr($ent, 0, -3);
    		if (++$sub < 3 and $fem) {
    			$matuni[1] = 'una';
    			$subcent = 'as';
    		}else{
    			$matuni[1] = $neutro ? 'un' : 'uno';
    			$subcent = 'os';
    		}
    		$t = '';
    		$n2 = substr($num, 1);
    		if ($n2 == '00') {
    		}elseif ($n2 < 21)
    		$t = ' ' . $matuni[(int)$n2];
    		elseif ($n2 < 30) {
    			$n3 = $num[2];
    			if ($n3 != 0) $t = 'i' . $matuni[$n3];
    			$n2 = $num[1];
    			$t = ' ' . $matdec[$n2] . $t;
    		}else{
    			$n3 = $num[2];
    			if ($n3 != 0) $t = ' y ' . $matuni[$n3];
    			$n2 = $num[1];
    			$t = ' ' . $matdec[$n2] . $t;
    		}
    		$n = $num[0];
    		if ($n == 1) {
    			$t = ' ciento' . $t;
    		}elseif ($n == 5){
    			$t = ' ' . $matunisub[$n] . 'ient' . $subcent . $t;
    		}elseif ($n != 0){
    			$t = ' ' . $matunisub[$n] . 'cient' . $subcent . $t;
    		}
    		if ($sub == 1) {
    		}elseif (! isset($matsub[$sub])) {
    			if ($num == 1) {
    				$t = ' mil';
    			}elseif ($num > 1){
    				$t .= ' mil';
    			}
    		}elseif ($num == 1) {
    			$t .= ' ' . $matsub[$sub] . '?n';
    		}elseif ($num > 1){
    			$t .= ' ' . $matsub[$sub] . 'ones';
    		}
    		if ($num == '000') $mils ++;
    		elseif ($mils != 0) {
    			if (isset($matmil[$sub])) $t .= ' ' . $matmil[$sub];
    			$mils = 0;
    		}
    		$neutro = true;
    		$tex = $t . $tex;
    	}
    	$tex = $neg . substr($tex, 1) . $fin;
    	//Zi hack --> return ucfirst($tex);
    	$end_num=ucfirst($tex).' pesos '.$float[1].'/100 M.N.';
    	return $end_num;
    }
}
