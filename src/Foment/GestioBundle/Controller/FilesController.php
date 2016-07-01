<?php

namespace Foment\GestioBundle\Controller;


use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Foment\GestioBundle\Entity\Soci;
use Foment\GestioBundle\Entity\Persona;
use Foment\GestioBundle\Entity\Seccio;
use Foment\GestioBundle\Entity\Activitat;
use Foment\GestioBundle\Classes\TcpdfBridge;


define('CR', "\r");          // Carriage Return: Mac
define('LF', "\n");          // Line Feed: Unix
define('CRLF', "\r\n");      // Carriage Return and Line Feed: Windows
define('BR', '<br />' . LF); // HTML Break


class FilesController extends BaseController
{
	/**********************************  Export CSV ************************************/
	

    public function exportpersonesAction(Request $request) {
    	
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	} 
    	
    	$queryparams = $this->queryPersones($request);
    	 
    	$header = UtilsController::getCSVHeader_Persones();
    	$persones = $queryparams['query']->getResult();

    	$csvTxt = iconv("UTF-8", "ISO-8859-1//TRANSLIT",implode(";",$header).CRLF);
    	foreach ($persones as $persona) {
    		$csvTxt .= iconv("UTF-8", "ISO-8859-1//TRANSLIT",$persona->getCsvRow().CRLF);
    	}
    	$response = new Response($csvTxt);
    	
    	//$response = $this->render('FomentGestioBundle:CSV:template.csv.twig', array('headercsv' => $header, 'data' => $persones));
    	 
    	$filename = "export_persones_".date("Y_m_d_His").".csv";
    	  
    	//$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    	$response->headers->set('Content-Type', 'text/csv; charset=ISO-8859-1');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    	$response->headers->set('Content-Description', 'Submissions Export Persones');
    	
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    	 
    	
    	$response->prepare($request);
    	//$response->sendHeaders();
    	//$response->sendContent();
    	
    	return $response;
    }
    
    public function exportrebutsAction(Request $request) {
    	 
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$request->query->set('sort', 'r.id');
    	$request->query->set('direction', 'asc');
   	
    	$queryparams = $this->queryRebuts($request);
    
    	$header = UtilsController::getCSVHeader_Rebuts();
    	$rebuts = $queryparams['query']->getResult();
    
    	
    	$csvTxt = iconv("UTF-8", "ISO-8859-1//TRANSLIT",implode(";",$header).CRLF);
    	foreach ($rebuts as $rebut) {
    		$rowRebut = $rebut->getCsvRow();
    		foreach ($rebut->getDetallsSortedByNum(true) as $d) {
    			$csvTxt .= iconv("UTF-8", "ISO-8859-1//TRANSLIT",$rowRebut.";".$d->getCsvRow().CRLF);
    		}
    	}
    	$response = new Response($csvTxt);
    	
    	//$response = $this->render('FomentGestioBundle:CSV:template.csv.twig', array('headercsv' => $header, 'data' => $rebuts));
    
    	$filename = "export_rebuts_".date("Y_m_d_His").".csv";
    	 
    	//$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    	$response->headers->set('Content-Type', 'text/csv; charset=ISO-8859-1');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    	$response->headers->set('Content-Description', 'Submissions Export Rebuts');
    	 
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    
    	 
    	$response->prepare($request);
    	//$response->sendHeaders();
    	//$response->sendContent();
    	 
    	return $response;
    }
    
    public function exportinfoseccionsAction(Request $request) {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}

    	$current = $request->query->get('current', date('Y'));
    	$semestre = $request->query->get('semestre', 0);
    	
    	$header = UtilsController::getCSVHeader_InfoSeccions();
    	
    	$selectedPeriodes = $this->getPeriodesSeleccionats($current, $semestre);
    	
    	$infoseccions = $this->infoSeccionsQuotes($selectedPeriodes);

    	$csvTxt = iconv("UTF-8", "ISO-8859-1//TRANSLIT",implode(";",$header).CRLF);
    	$infoseccionsCSV = array();
    	foreach ($infoseccions as $k => $infoseccio) {
    		$info = $infoseccio['info'];
    		
    		$infoseccionsCSV = array( 'id' => $k, 'seccio' => $infoseccio['nom'], 
    			'total' => number_format($info['rebuts']['import'], 2, ',', ''), '# total' => $info['rebuts']['total'],
    			'cobrats' => number_format($info['cobrats']['import'], 2, ',', ''), '# cobrats' => $info['cobrats']['total'],
    			'pendents' => number_format($info['bpendents']['import'], 2, ',', ''), '# pendents' => $info['bpendents']['total'],
    			'anul·lats' => number_format($info['anulats']['import'], 2, ',', ''), '# anul·lats' => $info['anulats']['total'], 
				'domiciliats' => number_format($info['bfacturats']['import'], 2, ',', ''), '# domiciliats' => $info['bfacturats']['total'], 
    			'retornats' => number_format($info['retornats']['import'], 2, ',', ''),  '# retornats' => $info['retornats']['total'],  
    			'ret. cobrats' => number_format($info['rcobrats']['import'], 2, ',', ''), '# ret. cobrats' => $info['rcobrats']['total'], 
    			'finestreta' => number_format($info['finestreta']['import'], 2, ',', ''), '# finestreta' => $info['finestreta']['total'],
    			'fin. cobrats' => number_format($info['fcobrats']['import'], 2, ',', ''), '# fin. cobrats' => $info['fcobrats']['total']
    		);
    		$row = '"'.implode('";"', $infoseccionsCSV).'"';
    		
    		$csvTxt .= iconv("UTF-8", "ISO-8859-1//TRANSLIT", $row.CRLF);
    	}

    	$response = new Response($csvTxt);
    	
    	//$response = $this->render('FomentGestioBundle:CSV:templatearray.csv.twig', array('headercsv' => $header, 'data' => $infoseccionsCSV));
    	
    	$strPeriodes = array();
    	foreach ($selectedPeriodes as $periode) {
    		$strPeriodes[] = $periode->getTitol();
    	}
    	$nomFitxer = UtilsController::netejarNom(implode(", ", $strPeriodes), true);
    	
    	$filename = "export_".$nomFitxer."_".date("Y_m_d_His").".csv";
    	
    	//$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    	$response->headers->set('Content-Type', 'text/csv; charset=ISO-8859-1');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    	$response->headers->set('Content-Description', 'Submissions Export Rebuts');
    	
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    	
    	$response->prepare($request);
    	
    	return $response;
    }
    
    public function exportseccionsAction(Request $request) {
    	 
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}

    	$em = $this->getDoctrine()->getManager();
    	
    	$header = UtilsController::getCSVHeader_Seccions();
    	
    	$seccions = $em->getRepository('FomentGestioBundle:Seccio')->findBy(array('databaixa' => null), array('ordre' => 'asc'));
    	
    	$csvTxt = iconv("UTF-8", "ISO-8859-1//TRANSLIT",implode(";",$header).CRLF);
    	foreach ($seccions as $seccio) {
    		$csvTxt .= iconv("UTF-8", "ISO-8859-1//TRANSLIT",$seccio->getCsvRow().CRLF);
    	}
    	$response = new Response($csvTxt);
    	
    	$filename = "export_seccions_".date("Y_m_d_His").".csv";
    	 
    	//$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    	$response->headers->set('Content-Type', 'text/csv; charset=ISO-8859-1');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    	$response->headers->set('Content-Description', 'Submissions Export Persones');
    	 
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    
    	 
    	$response->prepare($request);
    	//$response->sendHeaders();
    	//$response->sendContent();
    	 
    	return $response;
    }
    
    
    public function exportmembresseccioAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$id = $request->query->get('id', 1); // Per defecte seccio 1: Foment
    	 
    	$em = $this->getDoctrine()->getManager();
    		
    	$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($id);
    		
    	$fraccionat = false;
    	$header = UtilsController::getCSVHeader_membresanual();
    	if ($seccio->getFraccionat() == true || $seccio->esGeneral() == true) {
    		$fraccionat = true;
    		$header = UtilsController::getCSVHeader_membresfraccionat();
    	}
    		
    	$membres = $seccio->getMembresSortedByCognom();
    		
    	$csvTxt = iconv("UTF-8", "ISO-8859-1//TRANSLIT",implode(";",$header).CRLF);

    	foreach ($membres as $m) {
    		$detalls = $m->getRebutDetallAny(date('Y'));
    		
    		$row = $m->getSoci()->getCsvRow().';"'.$m->getQuotaAny(date('Y')).'";"'.$m->getTextQuotaAny(date('Y')).'";';
    			
    		if ($fraccionat != true) {
    			$detall = isset($detalls[0])?$detalls[0]:null;
    			
    			$row .=  '"'.($detall != null?$detall->getEstat():'').'"';
    		} else {
    			$detall = isset($detalls[0])?$detalls[0]:null;
    			$semestre = 1;
    			$row .= '"'.$semestre.'";"'.($detall != null?$detall->getImport():'').'";"'.($detall != null?$detall->getEstat():'').'";';
    				
    			$semestre++;
    			$detall = isset($detalls[1])?$detalls[1]:null;
    			$row .= '"'.$semestre.'";"'.($detall != null?$detall->getImport():'').'";"'.($detall != null?$detall->getEstat():'').'"';
    		}
    			
    		$csvTxt .= iconv("UTF-8", "ISO-8859-1//TRANSLIT",$row.CRLF);
    	}
    		
    	$response = new Response($csvTxt);
    	
    	$filename = "export_membres_seccio_".UtilsController::netejarNom($seccio->getNom())."_".date("Y_m_d_His").".csv";
    	
    	//$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    	$response->headers->set('Content-Type', 'text/csv; charset=ISO-8859-1');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    	$response->headers->set('Content-Description', 'Export Membres secció '.$seccio->getNom());
    	
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    	
    	
    	$response->prepare($request);
    	//$response->sendHeaders();
    	//$response->sendContent();
    	
    	return $response;
    }
    
    public function exportactivitatsAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	//$queryparams = $this->queryTableSort($request, array( 'id' => 'a.id', 'direction' => 'asc'));
    
    	//$query = $this->queryActivitats($queryparams);
    	//$activitats = $query->getResult();
    	
    	$current = date('Y');
		if (date('n') < UtilsController::MES_INICI_CURS_SETEMBRE) $current--;
    	
    	$datainici =  \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_INICI_CURS_SETEMBRE. $current );
    	$datafinal =  \DateTime::createFromFormat('d/m/Y', UtilsController::DIA_MES_FINAL_CURS_JUNY. ($current + 1));
    	
    	$activitats = $this->queryActivitatsPeriode($datainici, $datafinal); // Any en curs
    
    	$header = UtilsController::getCSVHeader_Activitats();
    	
    	$csvTxt = iconv("UTF-8", "ISO-8859-1//TRANSLIT",implode(";",$header).CRLF);
    	foreach ($activitats as $activitat) {
    		$csvTxt .= iconv("UTF-8", "ISO-8859-1//TRANSLIT",$activitat->getCsvRow().CRLF);
    	}
    	$response = new Response($csvTxt);
    
    	$filename = "export_activitats_".date("Y_m_d_His").".csv";
    
    	//$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    	$response->headers->set('Content-Type', 'text/csv; charset=ISO-8859-1');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    	$response->headers->set('Content-Description', 'Submissions Export Persones');
    
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    
    
    	$response->prepare($request);
    	//$response->sendHeaders();
    	//$response->sendContent();
    
    	return $response;
    }
    
    public function exportparticipantsactivitatAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$id = $request->query->get('id', 0); 
    
    	$csvTxt = '';
    	$facturacions = array();
    	if ($id > 0) {
    		$em = $this->getDoctrine()->getManager();
    
    		$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($id);
    
    		$participants = $activitat->getParticipantsSortedByCognom(true);
    
    		$facturacions = $activitat->getFacturacionsSortedByDatafacturacio();

    		$header = UtilsController::getCSVHeader_Persones();
    		foreach ($facturacions as $f) {
    			$header[] = $f->getDescripcio();
    			$header[] = "Rebut";
    		}
    		$csvTxt = iconv("UTF-8", "ISO-8859-1//TRANSLIT",implode(";",$header).CRLF);
    		
    		foreach ($participants as $p) {
    			$row = $p->getPersona()->getCsvRow();
    			
    			foreach ($facturacions as $f) {
    				$info = $p->getInfoFacturacio($f);
    				
    				$row .= ';"'.$info['import'].'";"'.$info['estat'].'"';
    				
    			}
    			$csvTxt .= iconv("UTF-8", "ISO-8859-1//TRANSLIT",$row.CRLF);
    		}
    	}
    	 
    	$response = new Response($csvTxt);
    	
    	$filename = "export_particiants_activitat_".UtilsController::netejarNom($activitat->getDescripcio())."_".date("Y_m_d_His").".csv";
    	 
    	//$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    	$response->headers->set('Content-Type', 'text/csv; charset=ISO-8859-1');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    	$response->headers->set('Content-Description', 'Export Participants '.$activitat->getDescripcio().' '.$activitat->getCurs());
    	 
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    	 
    	 
    	$response->prepare($request);
    	//$response->sendHeaders();
    	//$response->sendContent();
    	 
    	return $response;
    }

    public function exportarmailsAction(Request $request) {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$queryparams = $this->queryPersones($request);
    	 
    	$queryparams['simail'] = true;
    	$queryparams['nomail'] = false;
    	
    	$persones = $queryparams['query']->getResult();
 
    	$mails = array();
    	$errors = array();
    	foreach ($persones as $persona) {
    		$email = $persona->getCorreu();
    		if ($email != '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    			$mails[] = $email;
    		} else {
    			if ($email != '') $errors[] = $persona->getId().'->'.$email;
    		}
    	}
    	$response = new Response(implode(';', $mails));
    	
    	//$response = $this->render('FomentGestioBundle:CSV:template.csv.twig', array('headercsv' => $header, 'data' => $persones));
    	 
    	$filename = "export_llistamails_".date("Y_m_d_His").".txt";
    	  
    	//$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    	$response->headers->set('Content-Type', 'text/plain; charset=ISO-8859-1');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
    	$response->headers->set('Content-Description', 'Export Llista Correus');
    	
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    	
    	return $response;
    }
    
    
    
    /**********************************  Fitxers especials ************************************/
    
    public function esborrarfitxerAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		//throw new AccessDeniedException();
    		$response = new Response("Accés denegat");
    		$response->setStatusCode(500);
    	}
    	
    	$file = $request->query->get('file', 0);
    	 
    	$file = urldecode($file);
    	
    	$fs = new Filesystem();
    	
    	// Fer còpia del fitxer per si fos necessari
    	$ruta_esborrats = __DIR__.UtilsController::PATH_TO_FILES.UtilsController::PATH_REL_TO_ESBORRATS_FILES;
    	
    	if ($fs->exists($ruta_esborrats) && $fs->exists(__DIR__.UtilsController::PATH_TO_FILES.$file)) {
    		
    		// Extreure el path relatiu per quedar-se amb el nom
    		$file_copia = $file;
    		$file_copia = str_replace(UtilsController::PATH_REL_TO_DOMICILIACIONS_FILES, "", $file_copia);
    		$file_copia = str_replace(UtilsController::PATH_REL_TO_DECLARACIONS_FILES, "", $file_copia);
    		$file_copia = $ruta_esborrats.$file_copia.'_deleted_'.date('YmdHiu');
    		
    		$fs->copy(__DIR__.UtilsController::PATH_TO_FILES.$file, $file_copia, true);

    		$fs->remove(__DIR__.UtilsController::PATH_TO_FILES.$file);
    		
    		return new Response("Ok");
    	}
    	
    	//throw new AccessDeniedException("No s'ha pogut esborrar el fitxer  ".$file);
    	$response = new Response("No s'ha pogut esborrar el fitxer  ".$file);
    	$response->setStatusCode(500);
    	return $response;
    }
    
    public function descarregarfitxerAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$file = $request->query->get('file', 0);
    
    	$fileAbs = __DIR__.UtilsController::PATH_TO_FILES.urldecode($file);
    	 
    	$file = str_replace(urlencode(UtilsController::PATH_REL_TO_DOMICILIACIONS_FILES), "", $file);
    	$file = str_replace(urlencode(UtilsController::PATH_REL_TO_DECLARACIONS_FILES), "", $file);
    	
    	$fs = new Filesystem();
    	 
    	if ($fs->exists($fileAbs)) {
    
    		$response = $this->downloadFile($fileAbs, $file, 'Comunicació de rebuts ');
    		
    		$response->prepare($request);
    		
    		return $response;
    	}
    	 
    	throw new AccessDeniedException("No s'ha pogut descarregar el fitxer  ".$file);
    }
    
    public function declaracioAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	$response = new Response("Ok");
    	
    	$exercici = $request->query->get('exercici', date('Y'));
    	
    	/*$telefon = $request->query->get('telefon', 0);
    	$nom = $request->query->get('nom', 0);
    	$justificant = $request->query->get('justificant', 0);
    	
    	if ($telefon == 0 || strlen($telefon . '') != 9 ) throw new \Exception("El número de telèfon (9 dígits) es obligatori");
    	
    	if ($justificant == 0 || strlen($justificant . '') != 13 ) throw new \Exception("El justificant de la declaració (13 dígits) es obligatori");
    	
    	if ($nom == "") throw new \Exception("El nom de la persona de contacte és obligatori");
    	$nom = substr($nom, 0, 40);
    	$nom = mb_strtoupper(UtilsController::netejarNom($nom, false), 'ISO-8859-1');
    	$nom = strlen($nom)==40?$nom:str_pad($nom, 40, " ", STR_PAD_RIGHT);*/
    	
    	$datainici = \DateTime::createFromFormat('Y-m-d', $exercici.'-01-01');
    	$datafinal = \DateTime::createFromFormat('Y-m-d', $exercici.'-12-31');
    	
    	$donacions = $this->consultaDonacionsPeriode($datainici, $datafinal);
    	
    	if (count($donacions) == 0) throw new NotFoundHttpException("Encara no hi ha cap rebut pagat per aquest exercici ".$exercici);
    	 
    	//$filename = date("Ymd_His") . "_model_182_donacions_exercici_".$exercici.".txt";
    	$filename = date("Ymd_His") . "_socis_model_182_donacions_exercici_".$exercici.".csv";
    	$ruta = __DIR__.UtilsController::PATH_TO_FILES.UtilsController::PATH_REL_TO_DECLARACIONS_FILES;
    	$fitxer = $ruta.'/'.$filename;
    	
    	//SOCI_COD	SOCI_NOMBR	SOCI_DNIS	SOCI_DIRS	SOCI_POBS	SOCI_CPS	SOCI_PROS	SOCI_IMPOR	SOCI_LIMPE	SOCI_LIMPC	SOCI_NOMB2
    	//32	JAUME GAY CODINA	36595924A	RIPOLLES, 31, bxs.	BARCELONA	08026	BARCELONA	256,00	DOSCIENTOS CINCUENTA Y SEIS	DUES-CENTS CINQUANTA-SIS	GAY CODINA JAUME
    	 
    	$fs = new Filesystem();
    	try {
    		if (!$fs->exists($ruta)) {
    			throw new NotFoundHttpException("No existeix el directori " .$ruta);
    		} else {
    			//$contents = $this->generarFitxerDonacions($exercici, $telefon, $nom, $justificant,  $donacions);
    			$contents = $this->generarFitxerDonacionsCSV($donacions);
    			
    			$fs->dumpFile($fitxer, implode(CRLF,$contents));
    			
    		}
    	} catch (IOException $e) {
    		throw new NotFoundHttpException("No es pot accedir al directori ".$ruta."  ". $e->getMessage());
    	}
   	
		$response = $this->downloadFile($fitxer, $filename, 'Declaració donacions. Model 182, exercici ' .$exercici);
    	
    	$response->prepare($request);
    	
    	return $response;
    }
    
    /**
     * Get fitxer donacions CSV per gestor: NIF (9 dígits ALFA, COGNOMS NOM majúsc. ISO, província 2 dígits, import sense punts milers
     *
     * @return array
     */
    protected function generarFitxerDonacionsCSV($donacions) {
    	 
    	$contents = array();
    	
    	$header = array('NIF', 'NOM', 'PROV.', 'IMPORT');
    	$contents[] = '"'.implode('";"',$header).'"'; // header 
    	
    	foreach ($donacions as $donacio) {
    		$row = array();
    		
    		$persona = $donacio['persona'];
    		$import = $donacio['importdonacio']; // Decimals
    			
    		$nif = str_replace('-', '', $persona->getDni());
    		$nom = $persona->getCognoms().' '.$persona->getNom();
    		
    		$row[] = strlen($nif) > 9?substr($nif, 0, 9):str_pad($nif, 9, "0", STR_PAD_LEFT);
    		//$row[] = mb_strtoupper($persona->getCognoms().' '.$persona->getNom(), 'ISO-8859-1');
    		$row[] = iconv("UTF-8", "ISO-8859-1//TRANSLIT", mb_strtoupper($nom) ); 
    		$row[] = UtilsController::getCodiProvincia($persona->getProvincia());
    		$row[] = number_format($import, 2, ',', '');
    		
    		$contents[] = '"'.implode('";"',$row).'"';
    	}
    	 
    	return $contents;
    }
    
    /**
     * Get fitxer donacions model 182 hisenda
     *
     * @return array
     */
    protected function generarFitxerDonacions($exercici, $telefon, $nom, $justificant,  $donacions) {
    	
    	$contents = array();
    	
    	/**
    	 * "existiendo un único registro del tipo 1 y tantos registros del tipo 2 como declarados tenga la declaración"
    	 * numéricos se presentarán alineados a la derecha y rellenos a ceros por la izquierda sin signos
    	 * los campos alfanuméricos y alfabéticos se presentarán alineados a la izquierda y 
    	 * rellenos de blancos por la derecha, en mayúsculas sin caracteres especiales, y sin vocales acentuadas
     	 * 
     	 * ISO-8859-1
		 *	“Ñ” tendrá el valor ASCII 209 (Hex. D1)
		 *  “Ç”(cedilla mayúscula) el valor ASCII 199 (Hex. C7).
     	 * 
    	 * Tipus  Model Exercici nif nom  Suport tlf Persona contate  Just. Declara  Complementaria  Sustitutiva  Anterior
    	 * 1      3     4        9    40   1      9        40             13 (Num.)     1 (Blanc)     1 (Blanc)     13 (Blanc)
    	 * 
    	 * Total  Import  Natura declarant  Camps titular patrimoni a blanc
    	 * 9       13+2         1 			  9 + 40 + 26 + 13 (SEGELL ELECTRONIC)
    	 *  
    	 * 1 -> Declarant (Foment)
    	 * 2 -> Perceptor (Socis)
    	 * 
    	 */
    	if (strlen($exercici) < 4) $exercici += 2000;
    	
    	$contents['recompte-debug-uni'] = str_repeat("1234567890",25);
    	$contents['recompte-debug-dec'] = "         1"."         2"."         3"."         4"."         5";
    	$contents['recompte-debug-dec'] .= "         6"."         7"."         8"."         9"."        10";
    	$contents['recompte-debug-dec'] .= "        11"."        12"."        13"."        14"."        15";
    	$contents['recompte-debug-dec'] .= "        16"."        17"."        18"."        19"."        20";
    	$contents['recompte-debug-dec'] .= "        21"."        22"."        23"."        24"."        25";
    	$contents['registre-declarant'] = UtilsController::REGISTRE_DECLARANT.UtilsController::MODEL_DECLARACIO.$exercici;
    	$contents['registre-declarant'] .= UtilsController::NIF_FOMENT.str_pad(UtilsController::NOM_FOMENT, 40, " ", STR_PAD_RIGHT);
    	$contents['registre-declarant'] .= UtilsController::TIPUS_SUPORT.$telefon.$nom.$justificant.str_repeat(" ",1+1+13);
    	
		$totalTemp9 = "TOTALTEMP"; // canviar al final 9 dígits
		$importTemp13_2 = "IMPORTTEMPTOTAL"; // canviar al final 15 dígits
    	
		$contents['registre-declarant'] .= $totalTemp9.$importTemp13_2.UtilsController::NATURA_DECLARANT.str_repeat(" ",9+40+28+13);
		
		
		/** Tipus  Model Exercici nif  nif  nif   nom  provincia   clau %deduccio  
		 *  1      3     4        9     9   9(B)  40   2           1       3+2    
		 *
		 *   Import  Especies  deduAuto  %deduAuto Natura revocacio  exercicirevoca  tipusBé   ident.Bé   (En blanc resta )
		 *    11+2         1 		2    	5       1        1            4            1         20        118 ?
		 */
		$total = 0;
		$sumaImport = 0;
		
		foreach ($donacions as $donacio) {
			$persona = $donacio['persona'];
			$import = $donacio['importdonacio'] * 100; // Decimals
			
			$reg = 'registre-declarat-'.$persona->getId();

			$nif = str_replace('-', '', $persona->getDni());
			
			$contents[$reg] = UtilsController::REGISTRE_PERCEPTOR.UtilsController::MODEL_DECLARACIO.$exercici.UtilsController::NIF_FOMENT;
			$contents[$reg] .= strlen($nif) > 9?substr($nif, 0, 9):str_pad($nif, 9, "0", STR_PAD_LEFT);
			
			$nom = $persona->getCognoms().' '.$persona->getNom();
			$nom = substr($nom, 0, 40);
			$nom = mb_strtoupper(UtilsController::netejarNom($nom, false), 'ISO-8859-1');
			
			$provincia = UtilsController::getCodiProvincia($persona->getProvincia());
			
			$contents[$reg] .= str_repeat(" ",9);
			$contents[$reg] .= (strlen($nom) > 40)?substr($nom, 0, 40):str_pad($nom, 40, " ", STR_PAD_RIGHT);
			
			$contents[$reg] .= $provincia.UtilsController::CLAU_DONATIU.str_repeat(" ",5); // Per defecte '08' BCN
			
			$contents[$reg] .= strlen($import.'') > 13?substr(($import.''), 0, 40):str_pad(($import.''), 13, "0", STR_PAD_LEFT).UtilsController::DONATIU_EN_ESPECIES;
			$contents[$reg] .= UtilsController::getCodiComunitat($provincia).UtilsController::DEDUCCIO_AUTO;
			$contents[$reg] .= UtilsController::NATURA_DECLARAT.str_repeat(" ",5).str_repeat(" ",1+4+1+20+118);
			
			$total++;
			$sumaImport += $import; 
		}
		
		// Substituir totals
		$total = strlen(($total.''))==9?($total.''):str_pad(($total.''), 9, "0", STR_PAD_LEFT);
		$sumaImport = strlen(($sumaImport.''))==15?($sumaImport.''):str_pad(($sumaImport.''), 15, "0", STR_PAD_LEFT);
		
		$contents['registre-declarant'] = str_replace($totalTemp9, $total, $contents['registre-declarant']);
		$contents['registre-declarant'] = str_replace($importTemp13_2, $sumaImport, $contents['registre-declarant']);
    	
    	
    	return $contents; 
    }
    
    /* Al crear la domiciliació els rebuts inclosos es marquen com pagats */
    public function domiciliacionsAction(Request $request) {
    	 
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		//throw new AccessDeniedException();
    		$response = new Response("Accés denegat");
    		$response->setStatusCode(500);
    		return $response;
    	}
    	 
    	$id = $request->query->get('facturacio', 0);
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$facturacio = $em->getRepository('FomentGestioBundle:Facturacio')->find($id);
    	
    	if ($facturacio == null) {
    		//throw new NotFoundHttpException("No s'ha trobat la facturació ".$id);
    		$response = new Response("No s'ha trobat la facturació ".$id);
    		$response->setStatusCode(500);
    		return $response;
    	}
    	
    	//$current = new \DateTime('now');
    	$filename = date("Ymd_His") . "_rebuts_".UtilsController::netejarNom($facturacio->getDescripcio()).".txt";
    	$ruta = __DIR__.UtilsController::PATH_TO_FILES.UtilsController::PATH_REL_TO_DOMICILIACIONS_FILES;
    	$fitxer = $ruta.'/'.$filename;

    	$fs = new Filesystem();
    	try {
    		if (!$fs->exists($ruta)) {
    			//throw new NotFoundHttpException("No existeix el directori " .$ruta);
    			$response = new Response("No existeix el directori " .$ruta);
    			$response->setStatusCode(500);
    			return $response;
    		} else {
    			$resultat = $facturacio->generarFitxerDomiciliacions();
    			$facturacio->setDatamodificacio(new \DateTime('now'));
    			
    			$contents = $resultat['contents'];
    			$errors = $resultat['errors'];
    			if (count($errors) > 0) {
    				// Facturació amb errors. Cal revisar els rebuts que no s'han enviat
    				// S'han tret de la facturació: falta el compte ....
    				//throw new Exception("Facturació generada amb errors ".PHP_EOL."  ". implode(PHP_EOL,$errors));
    				$response = new Response("Facturació amb errors ".PHP_EOL.".  ". implode(PHP_EOL,$errors));
    				$response->setStatusCode(500);
    				return $response;
    			} else {
    				$fs->dumpFile($fitxer, implode(CRLF,$contents));
    				 
    				$em->flush(); // Guardar canvis, rebuts trets de la facturació si escau
    			}
    		}
    	} catch (IOException $e) {
    		//throw new NotFoundHttpException("No es pot accedir al directori ".$ruta."  ". $e->getMessage());
    		$response = new Response("No es pot accedir al directori ".$ruta."  ". $e->getMessage()); 
    		$response->setStatusCode(500);
    		return $response;
    	}
    	
    	
    	/*$queryparams = $this->queryPersones($request);
    	  
    	$header = UtilsController::getCSVHeader_Persones();
    	$persones = $queryparams['query']->getResult();
    	$response = $this->render('FomentGestioBundle:CSV:template.csv.twig', array('headercsv' => $header, 'data' => $persones));*/
    	//$response = new Response($contents);

    	$response = $this->downloadFile($fitxer, $filename, 'Comunicació de rebuts ' .$facturacio->getDescripcio());
    	 
    	$response->prepare($request);
    	 
    	return $response;
    }
    
    private function downloadFile($fitxer, $path, $desc) {
    	$response = new BinaryFileResponse($fitxer);
    	 
    	//$response->setCharset('UTF-8');
    	 
    	//$response->headers->set('Content-Type', 'text/plain');
    	$response->headers->set('Content-Type', 'text/plain; charset=ISO-8859-1');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$path.'"');
    	$response->headers->set('Content-Description', $desc);
    	
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    	
    	return $response;
    }
    
    
    
    
    /**********************************  PDF's ************************************/
    
    public function pdfactivitatAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$id = $request->query->get('activitat', 0);
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($id);
    	
    	if ($activitat == null) throw new NotFoundHttpException("No s'ha trobat el curs o taller  ".$id);
    	
    	
    	// 2 pàgines 1era info resum activitat => calendari, professors ....
    	// 2a pàgina => llista alumnes (No mostrar si encara no hi ha alumnes)
    	
    	// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    	// $orientation, (string) $unit, (mixed) $format, (boolean) $unicode, (string) $encoding, (boolean) $diskcache, (boolean) $pdfa
    	$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    	
    	$pdf->setFontSubsetting(false);
    	
    	$title = 'Informació de l\'activitat, taller o curs ';
    	$title .= $activitat->getDescripcio().' en data ' . date('d/m/Y');
    	
    	//$pdf->init(array('header' => false, 'footer' => false, 'logo' => 'logo-foment-martinenc.jpg','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    	$pdf->init(array('header' => true, 'footer' => true,
    			'logo' => 'logo-fm1877-web.png','author' => 'Foment Martinenc',
    			'title' => '',
    			'string' => $title), true);
    	
    	$pdf->setPrintHeader(true);
    	$pdf->setPrintFooter(true);

    	// Add a page
    	$pdf->AddPage();
    	
    	//set margins
    	//$pdf->SetMargins(PDF_MARGIN_LEFT-1, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT-1);
    	
    	$innerWidth = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
    	 
    	//set auto page breaks
    	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    	
    	// set color for background
    	$pdf->SetFillColor(66,139,202); // Blau
    	// set color for text
    	$pdf->SetTextColor(255,255,255); // blanc
    	 
    	$pdf->SetFont('helvetica', 'B', 14);
    	
    	$strHeader = $activitat->getDescripcio();
    		
    	$pdf->MultiCell($innerWidth, 0, $strHeader,
    			array('LTRB' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100))), 'C', 1, 1, '', '', true, 1, false, true, 10, 'M', true);
    	 
    	$pdf->Ln();
    	 
    	$pdf->SetFillColor(255, 255, 255); // Blanc
    	
    	
    	
    	// Add a page. Participants
    	$pdf->AddPage();
    	
    	$pdf->SetFont('helvetica', 'B', 14);
    	$pdf->SetTextColor(50, 50, 50); // negre
    	//$pdf->SetTextColor(66,139,202); // blau
    	 
    	$pdf->MultiCell($innerWidth, 0, 'Llista de participants',
    			array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100))), 'L', 0, 1, '', '', true, 1, false, true, 8, 'M', true);
    	 
    	$pdf->Ln(4);
    	
    	// Treure membres de la Junta ¿?
    	$participants = $activitat->getParticipantsSortedByCognom(false);

    	$personesactivitat = array();
    	foreach ($participants as $participant)  $personesactivitat[] = $participant->getPersona();
    	 
    	//**************************************************************************
    	
    	$this->pdfTaulaPersones($pdf, $personesactivitat);
    	
    	//**************************************************************************
    	
    	
    	// Close and output PDF document
    	$nomFitxer = '';
    	$nomFitxer = 'informacio_activitat_'.UtilsController::netejarNom($activitat->getDescripcio()).'_'.date('Ymd_Hi').'.pdf';
    	 
    	if ($request->query->has('print') and $request->query->get('print') == true) {
    		// force print dialog
    		$js = 'print(true);';
    		// set javascript
    		$pdf->IncludeJS($js);
    		$response = new Response($pdf->Output($nomFitxer, "I")); // inline
    		$response->headers->set('Content-Disposition', 'attachment; filename="'.$nomFitxer.'"');
    		$response->headers->set('Pragma: public', true);
    		$response->headers->set('Content-Transfer-Encoding', 'binary');
    		$response->headers->set('Content-Type', 'application/pdf');
    		 
    	} else {
    		// Close and output PDF document
    		$response = new Response($pdf->Output($nomFitxer, "D")); // save as...
    		$response->headers->set('Content-Type', 'application/pdf');
    	}
    	 
    	return $response;
    }
    
    
    public function pdfsocisseccioAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		//throw new AccessDeniedException();
    		$response = new Response("Accés denegat");
    		$response->setStatusCode(500);
    		return $response;
    	}
    	
    	$id = $request->query->get('seccio', 0);
    	 
    	$sort = $request->query->get('sort', 'cognomsnom');
    	$direction = $request->query->get('direction', 'asc');
    	$queryparams = array('sort' => $sort, 'direction' => $direction);
    	
    	$em = $this->getDoctrine()->getManager();
    	 
    	$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($id);
    	 
    	if ($seccio == null) {
    		//throw new NotFoundHttpException("No s'ha trobat la secció ".$id);
    		$response = new Response("No s'ha trobat la secció ".$id);
    		$response->setStatusCode(500);
    		return $response;
    	}
    	 
    	
    	// Llista socis secció XXX en data XX/XX/XXXX
    	
    	// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    	// $orientation, (string) $unit, (mixed) $format, (boolean) $unicode, (string) $encoding, (boolean) $diskcache, (boolean) $pdfa
    	$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    	 
    	$pdf->setFontSubsetting(false);
    	 
    	//$pdf->init(array('header' => false, 'footer' => false, 'logo' => 'logo-foment-martinenc.jpg','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    	$pdf->init(array('header' => true, 'footer' => true,
    			'logo' => 'logo-fm1877-web.png','author' => 'Foment Martinenc',
    			'title' => '',
    			'string' => 'llistat socis secció de la secció '.$seccio->getNom().' en data ' . date('d/m/Y')), true);
    	 
    	$pdf->setPrintHeader(true);
    	$pdf->setPrintFooter(true);
    	 
    	// Add a page
    	$pdf->AddPage();
    	 
    	//set margins
    	//$pdf->SetMargins(PDF_MARGIN_LEFT-1, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT-1);
    	 
    	$innerWidth = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
    	
    	//set auto page breaks
    	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    	 
    	// set color for background
    	$pdf->SetFillColor(66,139,202); // Blau
    	// set color for text
    	$pdf->SetTextColor(255,255,255); // blanc 
    	
    	$pdf->SetFont('helvetica', 'B', 14);
    	$pdf->MultiCell($innerWidth, 0, 'SECCIÓ: '.$seccio->getNom(), 
    			array('LTRB' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100))), 'C', 1, 1, '', '', true, 1, false, true, 10, 'M', true);
    	
    	$pdf->Ln();
    	
    	$pdf->SetFillColor(255, 255, 255); // Blanc
    	    	
    	// Primer imprimir Junta
    	$membresjunta = $seccio->getMembresjunta();

    	if (count($membresjunta) > 0) {
    		$pdf->SetTextColor(66,139,202); // blau
    		
	    	$pdf->MultiCell($innerWidth, 0, 'Membres de la Junta', 
	    			array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(66,139,202))), 'R', 0, 1, '', '', true, 1, false, true, 10, 'M', true);
	    	
	    	$pdf->setY($pdf->getY() + 5);
	    	
	    	$this->pdfJuntaSeccio($pdf, $membresjunta);
	    	
	    	$pdf->setY($pdf->getY() - 5);
	    	
	    	$pdf->MultiCell($innerWidth, 0, '',
	    			array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(66,139,202))), 'L', 0, 1, '', '', true, 1, false, true, 10, 'M', true);
	    	
	    	$pdf->Ln();
    	}
    	
    	// Treure membres de la Junta ¿?
    	$membres = $seccio->getMembresSortedByCognom();
    	
    	if ($queryparams['sort'] == 'datainscripcio') $membres = $this->ordenarArrayObjectes($membres, $queryparams);
    	
    	$personesseccio = array(); 
    	foreach ($membres as $membre)  $personesseccio[] = $membre->getSoci();
    	
    	if ($queryparams['sort'] == 'num') $personesseccio = $this->ordenarArrayObjectes($personesseccio, $queryparams);
    	
    	//**************************************************************************
    	 
    	$this->pdfTaulaPersones($pdf, $personesseccio, $seccio->esGeneral());
    	 
    	//**************************************************************************
    	 
    	// Close and output PDF document
    	$nomFitxer = 'llistat_socis_seccio_'.UtilsController::netejarNom($seccio->getNom()).'_'.date('Ymd_Hi').'.pdf';
    	
    	if ($request->query->has('print') and $request->query->get('print') == true) {
    		// force print dialog
    		$js = 'print(true);';
    		// set javascript
    		$pdf->IncludeJS($js);
    		$response = new Response($pdf->Output($nomFitxer, "I")); // inline
    		$response->headers->set('Content-Disposition', 'attachment; filename="'.$nomFitxer.'"');
    		$response->headers->set('Pragma: public', true);
    		$response->headers->set('Content-Transfer-Encoding', 'binary');
    		$response->headers->set('Content-Type', 'application/pdf');
    		 
    	} else {
    		// Close and output PDF document
    		$response = new Response($pdf->Output($nomFitxer, "D")); // save as...
    		$response->headers->set('Content-Type', 'application/pdf');
    	}
    	
    	return $response;
    }
    
    public function pdfaltesbaixesseccioAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$id = $request->query->get('seccio', 0);
    	$desde = $request->query->get('desde', '');
    	$fins = $request->query->get('fins', '');
    	 
    	$em = $this->getDoctrine()->getManager();
    
    	//$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($id);
    
    	$seccions = $em->getRepository('FomentGestioBundle:Seccio')->findBy(array(),array('id' => 'ASC'));
    	
    	//if ($seccio == null) throw new NotFoundHttpException("No s'ha trobat la secció ".$id);
    
    	$strTitol = 'llistat altes i baixes socis secció';
    	
    	if ($desde != '') {
    		$dateDesde = \DateTime::createFromFormat('d/m/Y', $desde);
    		$strTitol .= ' des de '.$desde;
    	}
    	else  $dateDesde = \DateTime::createFromFormat('d/m/Y', '01/01/1900');
    	
    	if ($fins != '') {
    		$dateFins = \DateTime::createFromFormat('d/m/Y', $fins);
    		$strTitol .= ' fins '.$fins;
    	}
    	else  $dateFins = new \DateTime();
    	 
    	
    	
    	// Llista socis secció XXX en data XX/XX/XXXX
    	 
    	// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    	// $orientation, (string) $unit, (mixed) $format, (boolean) $unicode, (string) $encoding, (boolean) $diskcache, (boolean) $pdfa
    	$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    	$pdf->setFontSubsetting(false);
    
    	//$pdf->init(array('header' => false, 'footer' => false, 'logo' => 'logo-foment-martinenc.jpg','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    	$pdf->init(array('header' => true, 'footer' => true,
    			'logo' => 'logo-fm1877-web.png','author' => 'Foment Martinenc',
    			'title' => '',
    			'string' => $strTitol), true);
    
    	$pdf->setPrintHeader(true);
    	$pdf->setPrintFooter(true);
    
    	
    
    	//set margins
    	//$pdf->SetMargins(PDF_MARGIN_LEFT-1, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT-1);
    
    	$innerWidth = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
    	 
    	//set auto page breaks
    	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    	$seccioCurrent = null;
    	foreach ($seccions as $seccio)  {
    	
    		if ($seccio->getDatabaixa() == null && 
    				($id == 1  || $seccio->getId() == $id) ) {
    			
    			if ($seccio->getId() == $id) $seccioCurrent = $seccio;
    			// Add a page
    			$pdf->AddPage();
    					
		    	// set color for background
		    	$pdf->SetFillColor(66,139,202); // Blau
		    	// set color for text
		    	$pdf->SetTextColor(255,255,255); // blanc
		    	 
		    	$pdf->SetFont('helvetica', 'B', 14);
		    	$pdf->MultiCell($innerWidth, 0, 'SECCIÓ: '.$seccio->getNom(),
		    			array('LTRB' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100))), 'C', 1, 1, '', '', true, 1, false, true, 10, 'M', true);
		    	 
		    	$pdf->Ln();
		    	 
		    	$pdf->SetFillColor(255, 255, 255); // Blanc
		    	$pdf->SetTextColor(0, 0, 0); // Negre
		    	$pdf->SetFont('helvetica', 'B', 12);
		    	
		    	$strTitol = 'Noves inscripcions ';
		    	if ($desde != '') $strTitol .= ' des de '.$desde;
		    	if ($fins != '') $strTitol .= ' fins '.$fins;
		    	
		    	$pdf->MultiCell($innerWidth, 0, $strTitol,'', 'L', 1, 1, '', '', true, 1, false, true, 10, 'M', true);
		    	// Primer imprimir Junta
		    	$altes = $seccio->getAltesMembresPeriode($dateDesde, $dateFins);
		    
		    	//**************************************************************************
		    
		    	if (count ($altes) > 0) $this->pdfTaulaPersones($pdf, $altes, $seccio->esGeneral());
		    	else $pdf->MultiCell($innerWidth, 0, '--cap alta--','', 'C', 1, 1, '', '', true, 1, false, true, 10, 'M', true);
		    	
		    	//**************************************************************************
		
		    	$baixes = $seccio->getBaixesMembresPeriode($dateDesde, $dateFins);
		
		    	$pdf->SetFillColor(255, 255, 255); // Blanc
		    	$pdf->SetTextColor(0, 0, 0); // Negre
		    	$pdf->SetFont('helvetica', 'B', 12);
		    	
		    	$pdf->Ln();
		    	
		    	$strTitol = 'Baixes de la secció ';
		    	if ($desde != '') $strTitol .= ' des de '.$desde;
		    	if ($fins != '') $strTitol .= ' fins '.$fins;
		    	$pdf->MultiCell($innerWidth, 0, $strTitol,'', 'L', 1, 1, '', '', true, 1, false, true, 10, 'M', true);
		    	//**************************************************************************
		    	
		    	if (count ($altes) > 0) $this->pdfTaulaPersones($pdf, $baixes, $seccio->esGeneral());
		    	else $pdf->MultiCell($innerWidth, 0, '--cap baixa--','', 'C', 1, 1, '', '', true, 1, false, true, 10, 'M', true);
    	 
    		}
    	}	
    	
    	
    	//**************************************************************************
    	
    	// Close and output PDF document
    	$nomFitxer = 'llistat_altes_baixes_seccio_'.UtilsController::netejarNom( ($seccioCurrent!=null?$seccioCurrent->getNom():"") ).'_'.date('Ymd_Hi').'.pdf';
    	 
    	if ($request->query->has('print') and $request->query->get('print') == true) {
    		// force print dialog
    		$js = 'print(true);';
    		// set javascript
    		$pdf->IncludeJS($js);
    		$response = new Response($pdf->Output($nomFitxer, "I")); // inline
    		$response->headers->set('Content-Disposition', 'attachment; filename="'.$nomFitxer.'"');
    		$response->headers->set('Pragma: public', true);
    		$response->headers->set('Content-Transfer-Encoding', 'binary');
    		$response->headers->set('Content-Type', 'application/pdf');
    		 
    	} else {
    		// Close and output PDF document
    		$response = new Response($pdf->Output($nomFitxer, "D")); // save as...
    		$response->headers->set('Content-Type', 'application/pdf');
    	}
    	 
    	return $response;
    }
    
    private function pdfJuntaSeccio($pdf, $membresjunta) {
    	 
    	$w_carrec = 50;
    	$w_nom = 120;
    
    	$p_h = $pdf->getPageHeight() - PDF_MARGIN_BOTTOM;
    	$r_h = 8;
    	 
    	foreach ($membresjunta as $junta)  {
    
    		if ($pdf->getY() + $r_h > $p_h) {
    			$pdf->AddPage();
    		}

    		$pdf->SetFont('helvetica', 'B', 8);
    		$pdf->SetTextColor(0,0,0);

    		$carrec = UtilsController::getCarrecJunta($junta->getCarrec());
    		if ($junta->getArea() != '') $carrec .= '('.$junta->getArea().')';
    		// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    		$pdf->MultiCell($w_carrec, $r_h, $carrec, 0, 'L', 1, 0, '', '', true, 1, false, true, $r_h, 'M', true);
    		
    		$pdf->SetFont('helvetica', 'I', 10);
    		$pdf->SetTextColor(0,0,0);
    		$pdf->MultiCell($w_nom, $r_h, $junta->getSoci()->getNomCognoms(), 0, 'L', 0, 1, '', '', true, 1, false, true, $r_h, 'M', true);
    
    	}
    }    
    
    public function pdfpersonesAction(Request $request) {
    		
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    		
    	$queryparams = $this->queryPersones($request);
    
    	$persones = $queryparams['query']->getResult();
    	
    	// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    	// $orientation, (string) $unit, (mixed) $format, (boolean) $unicode, (string) $encoding, (boolean) $diskcache, (boolean) $pdfa
    	$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    	//$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);
    	
    	$pdf->setFontSubsetting(false);
    	
    	//$pdf->init(array('header' => false, 'footer' => false, 'logo' => 'logo-foment-martinenc.jpg','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    	$pdf->init(array('header' => true, 'footer' => true,
    			'logo' => 'logo-fm1877-web.png','author' => 'Foment Martinenc',
    			'title' => '',
    			'string' => 'llistat de dades personals'), true);
    	
    	$pdf->setPrintHeader(true);
    	$pdf->setPrintFooter(true);
    	
    	// Add a page
    	$pdf->AddPage();
    	
    	//set margins
    	//$pdf->SetMargins(PDF_MARGIN_LEFT-1, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT-1);
    	$innerWidth = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
    	
    	//set auto page breaks
    	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    	
    	// set color for background
    	$pdf->SetFillColor(255, 255, 255); // Blanc
    	// set color for text
    	$pdf->SetTextColor(0, 0, 0); // Negre
    	$pdf->SetFont('helvetica', '', 12);
    	
		//****************************** Capçalera taula ****************************
    	// set color for background
    	$pdf->SetFillColor(66,139,202); // Blau
    	// set color for text
    	$pdf->SetTextColor(255,255,255); // blanc
    	 
    	$pdf->SetFont('helvetica', 'B', 14);
    	$pdf->MultiCell($innerWidth, 0, 'LLISTAT DADES PERSONALS',
    			array('LTRB' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100))), 'C', 1, 1, '', '', true, 1, false, true, 10, 'M', true);
    	 
    	$pdf->Ln();
    	 
    	$pdf->SetFillColor(255, 255, 255); // Blanc
    	$pdf->SetTextColor(66,139,202); // blau
    	
    	$pdf->MultiCell($innerWidth, 0, 'Paràmetres del filtre i ordenació',
    			array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(66,139,202))), 'R', 0, 1, '', '', true, 1, false, true, 10, 'M', true);
    	
    	$pdf->setY($pdf->getY() + 5);
    	
    	$pdf->SetTextColor(100, 100, 100); // gris
    	$pdf->SetFont('helvetica', 'I', 9);
    	
    	///////////////// Ordenació
    	$ordenacio = '';
    	switch ($queryparams['sort']) {
    		case 's.id':
    			$ordenacio = 'Dades ordenades per número de soci';
    			break;
    		case 's.cognoms':
    			$ordenacio = 'Dades ordenades pels cognoms';
    			break;
    		case 's.datanaixement':
    			$ordenacio = 'Dades ordenades per edat';
    		case 's.dni':
    			$ordenacio = 'Dades ordenades pel número de document d\'identitat'; 
    	}
    	if ($ordenacio != '') {
    		if ($queryparams['direction'] != 'asc') $ordenacio .= ' descendentment';
    		else $ordenacio .= ' ascendentment';
    		
	   		$pdf->MultiCell($innerWidth, 0, $ordenacio, 0, 'L', 0, 1, '', '', true, 1, false, true, 8, 'M', true);
    	}
    	$pdf->SetFont('helvetica', '', 8);
    	$pdf->SetTextColor(50, 50, 50); // negre
		/////////////////// Filtre
        if (isset($queryparams['s']) && $queryparams['s'] == true) {
   			$filtre = '- Dades dels socis vigents ';
   			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    	} 
    	if (isset($queryparams['p']) && $queryparams['p'] == true) {
    		$filtre = '- Dades dels socis pendents de vist i plau ';
    		$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    	}
    	if (isset($queryparams['b']) && $queryparams['b'] == true) {
    		$filtre = '- Les dades inclouen també informació dels socis de baixa ';
    		$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    	}
    	if (isset($queryparams['nini']) && $queryparams['nini'] > 0) {
    		if (isset($queryparams['nfi']) && $queryparams['nfi'] > 0) {
	    		$filtre = '- Números de soci entre '.$queryparams['nini'] .' i ' .$queryparams['nfi'];
	    		$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		} else {
    			$filtre = '- Soci número '.$queryparams['nini'];
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		}
    	}
    	if (isset($queryparams['nom']) && $queryparams['nom'] != "") {
    		$filtre = '- El nom conté el text "'.$queryparams['nom'].'"';  
    		$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    	} 
    	if (isset($queryparams['cognoms']) && $queryparams['cognoms'] != "") {
    		$filtre = '- Els cognoms contenen el text "'.$queryparams['cognoms'].'"';
    		$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    	}
    	if (isset($queryparams['dni']) && $queryparams['dni'] != "") {
    		$filtre = '- El DNI conté els caràcters "'.$queryparams['dni'].'"';
    		$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    	}
    	if (isset($queryparams['simail']) && $queryparams['simail'] == true) {
    		if (!isset($queryparams['nomail']) || (isset($queryparams['nomail']) && $queryparams['nomail'] == false) ) {
    			$filtre = '- Només dades de persones amb adreça de correu ';
    			
    			if (isset($queryparams['mail']) && $queryparams['mail'] != "") {
    				$filtre .= ' que contingui el text "'.$queryparams['mail'].'"';
    			}
    			
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		}
    	}
    	if (isset($queryparams['nomail']) && $queryparams['nomail'] == true) {
    		if (!isset($queryparams['simail']) || (isset($queryparams['simail']) && $queryparams['simail'] == false) ) {
    			$filtre = '- Només dades de persones sense adreça de correu ';
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		}
    	}
    	if (isset($queryparams['exempt']) && $queryparams['exempt'] == true) {
    		$filtre = '- Només socis exempts de la quota general ';
    		$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    	}
    	if (isset($queryparams['h']) && $queryparams['h'] == true) {
    		if (!isset($queryparams['d']) || (isset($queryparams['d']) && $queryparams['d'] == false) ) {
    			$filtre = '- Filtre per sexe masculí ';
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		}
    	}
    	if (isset($queryparams['d']) && $queryparams['d'] == true) {
    		if (!isset($queryparams['h']) || (isset($queryparams['h']) && $queryparams['h'] == false) ) {
    			$filtre = '- Filtre per sexe femení ';
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		}
    	}
    	if (isset($queryparams['dini']) && $queryparams['dini'] != "") {
    		if (isset($queryparams['dfi']) && $queryparams['dfi'] != "") {
    			$filtre = '- Persones nascudes entre el  '.$queryparams['dini'] .' i el ' .$queryparams['dfi'];
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		} else {
    			$filtre = '- Persones nascudes el dia  '.$queryparams['dini'];
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		}
    	}

    	if (isset($queryparams['seccions']) && count($queryparams['seccions']) > 0) {
    		$seccions = array();
    		foreach ($queryparams['seccions'] as $seccio) {  // Seccions array objectes
    			$seccions[] = $seccio->getNom();
    		}
    		if (count($seccions) == 1) {
    			$filtre = '- Informació dels membres de la secció  '.$seccions[0];
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		} else {
    			$filtre = '- Informació dels membres de les seccions: '.implode(", ", $seccions);
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		}
    	}
   		if (isset($queryparams['activitats']) && count($queryparams['activitats']) > 0) {
   			$em = $this->getDoctrine()->getManager();
   			$activitats = array();
    		foreach ($queryparams['activitats'] as $activitatId) {  // activitats array id's
    			$activitat = $em->getRepository('FomentGestioBundle:Activitat')->find($activitatId); 
    			if ($activitat != null) $activitats[] = $activitat->getDescripcio();
    		}
    		if (count($activitats) == 1) {
    			$filtre = '- Informació dels participants en '.$activitats[0];
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		} else {
    			$filtre = '- Informació dels participants en els cursos i tallers: '.implode(", ", $activitats);
    			$pdf->MultiCell($innerWidth, 0, $filtre, 0, 'L', 0, 1, '', '', true, 1, false, true, 6, 'M', true);
    		}
    	}
    	
    	$pdf->SetTextColor(0, 0, 0); // negre
    	
    	$pdf->setY($pdf->getY() - 5);
    	
    	$pdf->MultiCell($innerWidth, 0, '',
    			array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(66,139,202))), 'L', 0, 1, '', '', true, 1, false, true, 10, 'M', true);
    	
    	$pdf->Ln();
    	$pdf->SetTextColor(0,0,0); // Negre
    	
    	
    	
    	
    	//**************************************************************************
    	
    	$this->pdfTaulaPersones($pdf, $persones, true);
    	
    	//**************************************************************************
    	
    	// Close and output PDF document
    	$nomFitxer = 'llistat_dades_personals_'.date('Ymd_Hi').'.pdf';
    
    	if ($request->query->has('print') and $request->query->get('print') == true) {
    		// force print dialog
    		$js = 'print(true);';
    		// set javascript
    		$pdf->IncludeJS($js);
    		$response = new Response($pdf->Output($nomFitxer, "I")); // inline
    		$response->headers->set('Content-Disposition', 'attachment; filename="'.$nomFitxer.'"');
    		$response->headers->set('Pragma: public', true);
    		$response->headers->set('Content-Transfer-Encoding', 'binary');
    		$response->headers->set('Content-Type', 'application/pdf');
    		 
    	} else {
    		// Close and output PDF document
    		$response = new Response($pdf->Output($nomFitxer, "D")); // save as...
    		$response->headers->set('Content-Type', 'application/pdf');
    	}
    
    	return $response;
    }
    
    private function pdfTaulaPersonesPrintHeader($pdf) {
    	$pdf->SetFont('helvetica', 'B', 10);
    	$pdf->SetFillColor(66,139,202); // blau
    	$pdf->SetTextColor(255,255,255); // Blanc
    	$pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255)));
    	
    	
    	// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    	$pdf->MultiCell(8, 16, '#',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', true, 1, false, true, 16, 'M', false);
    	$pdf->MultiCell(12, 16, '',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', false);
    	$pdf->MultiCell(22, 16, 'NÚM.',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', false);
    	$pdf->MultiCell(40, 16, 'NOM',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'L', 1, 0, '' ,'', false);
    	$pdf->MultiCell(25, 16, 'NASCUT',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '' ,'', false);
    	$pdf->MultiCell(15, 16, 'EDAT',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', false);
    	$pdf->MultiCell(43, 16, 'CONTACTE',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', false);
    	$pdf->MultiCell(15, 16, '', 0, 'C', 1, 1, '', '', true);
    	
    	$pdf->SetFont('helvetica', '', 10);
    	$pdf->SetFillColor(255,255,255); 
    	$pdf->SetTextColor(0,0,0); // Blanc
    	$pdf->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255)));
    }
    
    private function pdfTaulaPersonesSeccionsPrintHeader($pdf) {
    	$pdf->SetFont('helvetica', 'B', 9);
    	$pdf->SetFillColor(66,139,202); // blau
    	$pdf->SetTextColor(255,255,255); // Blanc
    	$pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255)));
    	 
    	 
    	// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    	$pdf->MultiCell(6, 16, '#',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', true, 1, false, true, 16, 'M', false);
    	$pdf->MultiCell(10, 16, '',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', false);
    	$pdf->MultiCell(18, 16, 'NÚM.',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', false);
    	$pdf->MultiCell(26, 16, 'SECCIONS',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', false);
    	$pdf->MultiCell(35, 16, 'NOM',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'L', 1, 0, '' ,'', false);
    	$pdf->MultiCell(20, 16, 'NASCUT',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '' ,'', false);
    	$pdf->MultiCell(13, 16, 'EDAT',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', false);
    	$pdf->MultiCell(39, 16, 'CONTACTE',
    			array('R' => array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255))), 'C', 1, 0, '', '', false);
    	$pdf->MultiCell(13, 16, '', 0, 'C', 1, 1, '', '', true);
    	 
    	$pdf->SetFont('helvetica', '', 9);
    	$pdf->SetFillColor(255,255,255);
    	$pdf->SetTextColor(0,0,0); // Blanc
    	$pdf->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255)));
    }
    
    private function pdfTaulaPersones($pdf, $persones, $mostrarSeccions = false, $mostrarInfoBaixes = true) {
    	
    	$font_size = 9;
    	$font_size_note = 8;
    	$w_seq = 8;
    	$w_soci = 12;
    	$w_num = 22;
    	$w_seccions = 0;
    	$w_nom = 40;
    	$w_nascut = 25;
    	$w_edat = 15;
    	$w_contacte = 43;
    	$w_foto = 15;  // Total 174

    	$p_h = $pdf->getPageHeight() - PDF_MARGIN_BOTTOM;
    	$r_foto = 20;
    	$r_nofoto = 12;
    	
    	if ($mostrarSeccions == true) {
    		$font_size--;
    		$font_size_note--;
    		$w_seq = 6;
    		$w_soci = 10;  
    		$w_num = 18;  
    		$w_seccions = 26;
    		$w_nom = 35;  
    		$w_nascut = 20;
    		$w_edat = 13; 
    		$w_contacte = 39;
    		$w_foto = 13;  
    		
    		$r_foto = 18;
    		$r_nofoto = 10;
    	}
    	
    	//$pdf->SetFont('dejavusans', '', $font_size);
    	$pdf->SetFont('helvetica', '', $font_size);
    	 
    	if (count($persones) > 1) {
    		$rowCount = '<p style="color:#357ebd; text-align: right"><b>total: '.count($persones).' registres</b></p>';
    		$pdf->writeHTML($rowCount, true, false, false, false, 'L');
    		$pdf->Ln('4');
    	}
    	 
    	
    	if ($mostrarSeccions == true) $this->pdfTaulaPersonesSeccionsPrintHeader($pdf);
    	else $this->pdfTaulaPersonesPrintHeader($pdf);
    	
    	$index = 1;
    	
    	foreach ($persones as $persona) {
    		
    		if ($pdf->getY() + $r_foto > $p_h) {
    			$pdf->AddPage();
		    	if ($mostrarSeccions == true) $this->pdfTaulaPersonesSeccionsPrintHeader($pdf);
		    	else $this->pdfTaulaPersonesPrintHeader($pdf);
    		}
    		
    		// Table rows
    		$r_h = $r_foto;
    		$fotoSrc = '';
    		try {
    			if ($persona->esSoci() && $persona->getFoto() != null && $persona->getFoto()->getWidth() > 0 && $persona->getFoto()->getHeight() > 0) {
    				
    				$ratioFoto = $persona->getFoto()->getWidth()/$persona->getFoto()->getHeight();
    				if ($ratioFoto > ($w_foto/$r_foto)) {
    					// foto més ample. cal reduir ample
    					
    					
    					$foto_w_scaled = $w_foto - 4;
    					$foto_h_scaled = ( $foto_w_scaled /$persona->getFoto()->getWidth()) * $persona->getFoto()->getHeight();
    				} else {
    					// foto més alta. Cal reduir alçada
    					$foto_h_scaled = $r_foto - 4;
    					$foto_w_scaled = ($persona->getFoto()->getWidth()/$persona->getFoto()->getHeight())*$foto_h_scaled;
    				}
    			
    				$fotoSrc = $persona->getFoto()->getWebPath();

    			} else $r_h = $r_nofoto;
    		} catch (\Exception $e) {
    			$r_h = $r_nofoto;
    		}
    		
    		$maxHeigthSubitem = ($fotoSrc == ''?$r_h-1:$r_h-5);
    		
    		$edat = $persona->getEdat();
    		
    		$contacte = trim($persona->getTelefons());
    		$tipussoci = $persona->esSociVigent()?UtilsController::getTipusSoci($persona->getTipus()):'';
    		
    		if ($persona->getCorreu() != null && $persona->getCorreu() != "") $contacte .= PHP_EOL.$persona->getCorreu();
    		
    		$pdf->SetTextColor(100,100,100);
    		$pdf->SetFont('helvetica', 'I', $font_size_note);
    		$pdf->MultiCell($w_seq, $r_h, $index,
    				array('L' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189)),
    						'B' => array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189))), 'C', 1, 0, '', '', true, 1, false, true, $r_h, 'M', false);
    		$pdf->SetTextColor(0,0,0);
    		$pdf->SetFont('helvetica', '', $font_size);
    		
    		$pdf->MultiCell($w_soci, $r_h, $persona->estatAmpliat(),
    				array('L' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189)),
    						'B' => array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189))), 'C', 1, 0, '', '', true, 1, false, true, $r_h, 'M', false);
    		
    		if ($tipussoci != "") {
    			$pdf->SetTextColor(100,100,100);
    			$pdf->SetFont('helvetica', 'I', $font_size_note);
    			//MultiCell ($w, $h,         $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
    			$pdf->MultiCell($w_num, 0, $tipussoci,0, 'C', 1, 0, '', '', true, 1, false, true, $maxHeigthSubitem, 'B', true);
    			 
    			// Reset position
    			$pdf->SetTextColor(0,0,0);
    			$pdf->SetFont('helvetica', '', $font_size);
    			$pdf->setX($pdf->getX() - $w_num);
    		}
    		
    		
    		$pdf->MultiCell($w_num, $r_h, $persona->getNumSoci(),
    				array('L' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189)),
    						'B' => array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189))), 'C', 1, 0, '', '', true, 1, false, true, $r_h, 'M', false);
    		
    		if ($mostrarSeccions == true) {
    			$pdf->SetFont('helvetica', 'I', $font_size_note);
    			
    			if (!$persona->esSociVigent() && $mostrarInfoBaixes = true)  $llistaSeccions = $persona->getLlistaSeccions();
    			else $llistaSeccions = $persona->getLlistaSeccions();
    			
    			$pdf->MultiCell($w_seccions, $r_h, $llistaSeccions, 
    				array('L' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189)),
    						'B' => array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189))), 'C', 1, 0, '', '', true, 1, false, true, $r_h, 'M', true);
    			$pdf->SetFont('helvetica', '', $font_size);
    			
    		}
    		
    		$pdf->MultiCell($w_nom, $r_h, $persona->getNomCognoms(),
    				array('L' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189)),
    						'B' => array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189))), 'L', 1, 0, '' ,'', true, 1, false, true, $r_h, 'M', false);
    				
    		$infoNaixement = ($persona->getDatanaixement()==null?'':$persona->getDatanaixement()->format('d/m/Y'));
    		if ($persona->getDatanaixement()!=null && $persona->getLlocnaixement()!=null) $infoNaixement .= PHP_EOL;
    		$infoNaixement .= ($persona->getLlocnaixement()==null || $persona->getLlocnaixement()==''?'':'a '.$persona->getLlocnaixement());
    		
			$pdf->MultiCell($w_nascut, $r_h, $infoNaixement,
					array('L' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189)),
							'B' => array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189))), 'C', 1, 0, '' ,'', true, 1, false, true, $r_h, 'M', true);
    				
    		if ($edat != "") {
	    		$pdf->SetTextColor(100,100,100); 
	    		$pdf->SetFont('helvetica', 'I', $font_size_note);
	    		$pdf->MultiCell($w_edat, 0, 'anys',0, 'C', 1, 0, '', '', true, 1, false, true, $maxHeigthSubitem, 'B', true);
	    		
	    		// Reset position
	    		$pdf->SetTextColor(0,0,0); 
	    		$pdf->SetFont('helvetica', '', $font_size);
	    		$pdf->setX($pdf->getX() - $w_edat); 
    		}
    		$pdf->MultiCell($w_edat, $r_h, $edat,
    				array('L' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189)),
    						'B' => array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189))), 'C', 1, 0, '', '', true, 1, false, true, $r_h, 'M', false);
    		
    		
    		$pdf->MultiCell($w_contacte, $r_h, $contacte,
    				array('L' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189)),
    						'B' => array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189))), 'C', 1, 0, '', '', true, 1, false, true, $r_h, 'M', true);
    		
    		$ant_y = $pdf->getY();
    		$ant_x = $pdf->getX();
    		    		
    		$pdf->MultiCell($w_foto, $r_h, '',
    				array('LR' => array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189)),
    						'B' => array('width' => 0.6, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189))), 'C', 1, 1, '', '', true, 1, false, true, $r_h, 'M', true);

    		$curr_y = $pdf->getY();
    		$curr_x = $pdf->getX();
    		
    		if ($fotoSrc != "") {
    			
    			$curr_x = $pdf->getX();
    			// Image($file,        $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300,
    			// $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)
    			$pdf->Image(K_PATH_IMAGES.$fotoSrc, $ant_x + 1, $ant_y + 1, $foto_w_scaled, $foto_h_scaled, '', '', 'B', true, 150, '', false, false, 
    					array('LTRB' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(100, 100, 100) )), false, false, false);
    			
    			$pdf->setX($curr_x); 
    			$pdf->setY($curr_y); 
    		}
    		
    		
    		$index++;
    	}
    	
    	$pdf->SetFont('helvetica', '', $font_size_note);
    	$pdf->SetFillColor(66,139,202); // blau
    	$pdf->SetTextColor(255,255,255); // Blanc
    	$pdf->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(52, 126, 189)));
    	 
    	// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    	$pdf->MultiCell(8 + 12 + 22 + 50 + 15 + 58 +15, 8, 'soci o sòcia, de baixa o no soci/a',0 , 'L', 1, 1, '', '', true);
    	
    	/*
    	
    	// soci / numero / tipus / nom / edat / foto / contacte
    	$html = '<table class="main-table" border="0" cellpadding="4" cellspacing="0"><thead>';
    	// Table Header
    	$html .= '<tr style="background-color:#428bca; color:#ffffff; border: 0.5px solid #428bca; font-weight:bold;" >';
    	$html .= '<th align="center" width="'.$w_seq.'" style="border: 0.5px solid #ffffff; border-left: 0.5px solid #428bca; border-top: 0.5px solid #428bca;">#</th>';
    	$html .= '<th align="left" width="'.$w_soci.'" style="border: 0.5px solid #ffffff; border-top: 0.5px solid #428bca;">&nbsp;</th>';
    	$html .= '<th align="center" width="'.$w_num.'" style="border: 0.5px solid #ffffff; border-top: 0.5px solid #428bca;">NÚM.</th>';
    	$html .= '<th align="left" width="'.$w_nom.'" style="border: 0.5px solid #ffffff; border-top: 0.5px solid #428bca;">NOM<span class="fa fa-icon-sort"></span></th>';
    	$html .= '<th align="center" width="'.$w_edat.'" style="border: 0.5px solid #ffffff; border-top: 0.5px solid #428bca; text-align:center;">EDAT</th>';
    	$html .= '<th align="center" width="'.$w_contacte.'" style="border: 0.5px solid #ffffff; border-top: 0.5px solid #428bca;">DADES DE CONTACTE</th>';
    	$html .= '<th align="center" width="'.$w_foto.'" style="border: 0.5px solid #ffffff; border-top: 0.5px solid #428bca;border-right: 0.5px solid #428bca;">&nbsp;</th></tr></thead>';
    	 
    	$index = 1;
    	$html .= '<tbody>';
    	
    	foreach ($persones as $persona) {
    		// Table rows
    		$foto = '&nbsp;';
    		try {
    			if ($persona->getFoto() != null && $persona->getFoto()->getWidth() > 0 && $persona->getFoto()->getHeight() > 0) {
    				$fotoSrc = $persona->getFoto()->getWebPath();
    				$foto = '<img width="30" style="border: 0.5px solid #428bca; margin-top:0;" src="'.$fotoSrc.'" >';
    			}
    		} catch (\Exception $e) {
    			
    		}
    	
    		$html .= '<tr  nobr="true">';
    		$html .= '<td align="right" width="'.$w_seq.'" style="border: 0.5px solid #357ebd; vertical-align: middle; "><span style="font-size: x-small; color:#555555;line-height:2em">'.$index.'</span></td>';
    		$html .= '<td align="center" width="'.$w_soci.'"  style="border: 0.5px solid #357ebd; vertical-align: middle; ">'.$persona->estatAmpliat().'</td>';
    		$html .= '<td align="center" width="'.$w_num.'" style="border: 0.5px solid #357ebd; vertical-align: middle; ">'.$persona->getNumSoci();
    		if ($persona->getTipus() > 0) $html .= '<br/><i><span style="font-size: x-small;color:#555555;">'.UtilsController::getTipusSoci($persona->getTipus()).'</span></i>';
    		$html .= '</td>';
    		$html .= '<td align="left" width="'.$w_nom.'" style="border: 0.5px solid #357ebd; vertical-align: middle; ">'.$persona->getNomCognoms().'</td>';
    		
    		$edat = $persona->getEdat();
    		if ($edat != "") $edat = '<div style="width: 100%">'.$edat.'</div><i><span style="font-size: x-small; color:#555555;">anys</span></i>'; 
    		
    		$html .= '<td align="center" width="'.$w_edat.'" style="border: 0.5px solid #357ebd; vertical-align: middle; ">'.$edat.'</td>';
    		$html .= '<td align="center" width="'.$w_contacte.'" style="border: 0.5px solid #357ebd; vertical-align: middle; "><span style="font-size: x-small; color:#555555;">'.trim($persona->getContacte()).'</span></td>';
    		$html .= '<td align="center" width="'.$w_foto.'" style="border: 0.5px solid #357ebd; vertical-align: middle; text-align:center; ">';
    		$html .= $foto.'</td></tr>';
    	
    		$index++;
    	}
    	 
    	$html .= '<tr><td colspan="7" align="left" style="background-color:#428bca; color:#ffffff; border: 0.5px solid #428bca;">';
    	$html .= '<i><span style="font-size: xx-small; color:#ffffff;">soci o sòcia, de baixa o no soci/a </span></i></td></tr>';
    	$html .= '<tbody></table>';
    	$pdf->writeHTML($html, true, false, false, false, 'L');
    	 
    	//  S-Soci, B-Soci de baixa, N-No soci
    	$pdf->SetFont('helvetica', '', 10);
    	$legend = '<p style="color:#357ebd"></p>';
    	 
    	$pdf->writeHTML($legend, true, false, false, false, 'L');
    	*/
    }
    
    
    public function certificatdonacioAction(Request $request) {
    	// http://www.foment.dev/certificatdonacio?id=xxx
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$id = $request->query->get('persona', 0);
    	$exercici = $request->query->get('exercici', 0);
    
    	$em = $this->getDoctrine()->getManager();
    	
    	$persona = $em->getRepository('FomentGestioBundle:Persona')->find($id);
    	 
    	
    	if ($persona != null) {
    
    		$datainici = \DateTime::createFromFormat('Y-m-d', $exercici.'-01-01');
    		$datafinal = \DateTime::createFromFormat('Y-m-d', $exercici.'-12-31');
    		
    		$donacions = $this->consultaDonacionsPeriode($datainici, $datafinal, $persona);
    		
    		$f = new \NumberFormatter("ca_ES.utf8", \NumberFormatter::SPELLOUT);
    		$donacionsFloor = floor($donacions);
    		$donacionsDec = floor(($donacions - $donacionsFloor)*100);
    		$donacionsTxt = $f->format($donacionsFloor);// . ($donacionsDec < 0.001)?'':' amb '. $f->format($donacionsDec*100);
    		$donacionsTxt .= ($donacionsDec == 0)?'':' amb '. $f->format($donacionsDec);
    		
    		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    		// $orientation, (string) $unit, (mixed) $format, (boolean) $unicode, (string) $encoding, (boolean) $diskcache, (boolean) $pdfa
    		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    		
    		
    		//$pdf->init(array('header' => false, 'footer' => false, 'logo' => 'logo-foment-martinenc.jpg','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    		$pdf->init(array('header' => true, 'footer' => true, 
    					'logo' => 'logo-fm1877-web.png','author' => 'Foment Martinenc', 
    					'title' => '',
    					'string' => 'Certificat',
    					'leftMargin' => UtilsController::PDF_MARGIN_LEFT_NARROW,
    					'rightMargin' => UtilsController::PDF_MARGIN_RIGHT_NARROW));
    		
    		$pdf->setPrintHeader(true);
    		$pdf->setPrintFooter(true);
    		
    		// Add a page
    		$pdf->AddPage();
    		
    		//set margins
    		//$pdf->SetMargins(PDF_MARGIN_LEFT-1, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT-1);
    		
    		
    		//set auto page breaks
    		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM - 10);
    		
    		// set color for background
    		$pdf->SetFillColor(255, 255, 255); // Blanc
    		// set color for text
    		$pdf->SetTextColor(0, 0, 0); // Negre
    		$pdf->SetFont('helvetica', '', 12);
    		 
    		$text = "Montserrat Alba Quintero, DNI, 46570232Q , Secretaria de la Junta Directiva de l’associació FOMENT MARTINENC,";
    		$text .=" domiciliada a Barcelona,  carrer Provença, 591, amb NIF G08917635, com a entitat inclosa dins les regulades en";
    		$text .=" l’article 16 de la Llei 49/2002, de 23 de desembre, de règim fiscal de les entitats sense finalitats lucratives";
    		$text .=" i dels incentius fiscals al mecenatge.\n";
    		
    		
    		//$pdf->Cell(0, 0, $text, 0, 0, 'L', 0, '', 1);
    		//	$pdf->MultiCell(0, 0, $text, 20, 100, 'L', 0, '', 1);
    		
    		// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    		$pdf->MultiCell(0, 0, $text, 0, 'J', 0, 1, UtilsController::PDF_MARGIN_LEFT_NARROW, PDF_MARGIN_TOP, true);
    		
    		$pdf->Ln(8);
    		
    		$pdf->SetFont('helvetica', 'B', 12);
    		$text = "CERTIFICO:";
    		$pdf->MultiCell(0, 0, $text, 0, 'J', 0, 1, '', '', true);
    		$pdf->Ln(8);
    		
    		$pdf->SetFont('helvetica', '', 12);
    		
    		$text = "Que amb la finalitat d’ajudar al compliment de les finalitats fundacionals establertes en els Estatuts, ";
    		$text .= mb_strtoupper(strtoupper($persona->getNomCognoms()), 'UTF-8') .", amb domicili fiscal a ".mb_strtoupper(strtoupper($persona->getPoblacio()), 'UTF-8');
    		$text .= ", ".mb_strtoupper($persona->getAdreca(), 'UTF-8').", amb NIF ".strtoupper($persona->getDNI()).", ha lliurat,";
    		$text .= " la quantitat de ".number_format($donacions, 2, ',', '.')." euros, (".mb_strtoupper($donacionsTxt, 'UTF-8')." EUROS), en concepte de donació pura i simple a la nostra entitat.\n";
    		

    		$pdf->MultiCell(0, 0, $text, 0, 'J', 0, 1, '', '', true);
    		$pdf->Ln(8);
    		
    		$text = "Que l’esmentada quantitat ha estat donada amb caràcter de donatiu irrevocable, i ha estat acceptada com a tal,";
    		$text .= " a l’empara del que estableixen els Estatuts.\n";
    		
    		$pdf->MultiCell(0, 0, $text, 0, 'J', 0, 1, '', '', true);
    		$pdf->Ln(8);
    		
    		setlocale(LC_TIME, "ca", "ca_ES.UTF-8", "ca_ES.utf8", "ca_ES", "Catalan_Spain", "Catalan");
    		setlocale(LC_TIME, "ca_ES.utf8");
    		$text = "I perquè així consti, i com a justificant del donatiu efectuat, als efectes de poder gaudir dels beneficis fiscals";
    		$text .= " establerts en l’article 19 de la Llei 49/2002 de 23 de desembre, de règim fiscal de les entitats sense fins";
    		$text .= " lucratius i dels incentius fiscals al mecenatge, lliuro la present certificació, a Barcelona, amb data ".strftime("%e de %B de %Y")."\n";
    		
    		$pdf->MultiCell(0, 0, $text, 0, 'J', 0, 1, '', '', true);
    		
    		$pdf->Ln(12);
    		
    		$text = "Vist-i-plau\nEL PRESIDENT";
    		$y = $pdf->getY();
    		$pdf->MultiCell(0, 0, $text, 0, 'L', 0, 1, $pdf->getX() + 20, $y, true);
    		
    		$text = "\nLA SECRETARIA";
    		$pdf->MultiCell(0, 0, $text, 0, 'L', 0, 1, $pdf->getX() + 90, $y, true);
    		
    		//$pdf->Ln(8);
    		
    		// Close and output PDF document
    		$nomFitxer = 'certificat_donacio_'.UtilsController::netejarNom($persona->getNomCognoms(), true).'_'.date('Ymd_Hi').'.pdf';
    
    		if ($request->query->has('print') and $request->query->get('print') == true) {
    			// force print dialog
    			$js = 'print(true);';
    			// set javascript
    			$pdf->IncludeJS($js);
    			$response = new Response($pdf->Output($nomFitxer, "I")); // inline
    			$response->headers->set('Content-Disposition', 'attachment; filename="'.$nomFitxer.'"');
    			$response->headers->set('Pragma: public', true);
    			$response->headers->set('Content-Transfer-Encoding', 'binary');
    			$response->headers->set('Content-Type', 'application/pdf');
    			 
    		} else {
    			// Close and output PDF document
    			$response = new Response($pdf->Output($nomFitxer, "D")); // save as...
    			$response->headers->set('Content-Type', 'application/pdf');
    		}
    
    		return $response;
    	}
    
    	throw new NotFoundHttpException("Persona no trobada");//ServiceUnavailableHttpException
    }
    
    public function rebutpdfAction(Request $request) {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$id = $request->query->get('id', 0);
    	 
    	$em = $this->getDoctrine()->getManager();
    	
    	$rebut = $em->getRepository('FomentGestioBundle:Rebut')->find($id);
    	
    	if ($rebut != null) {
    
    		$pdf = $this->imprimirrebuts(array($rebut));

    		// Close and output PDF document
    		//$nomFitxer = 'rebuts_socis_'.date('Ymd_Hi').'.pdf';
    		//if (count($rebuts) == 1) $nomFitxer = 'rebut_'.$rebut->getNum().'_'.date('Ymd_Hi').'.pdf';
    		$nomFitxer = 'rebut_'.$rebut->getNum().'_'.date('Ymd_Hi').'.pdf';
    		
    		if ($request->query->has('print') and $request->query->get('print') == true) {
    			// force print dialog
    			$js = 'print(true);';
    			// set javascript
    			$pdf->IncludeJS($js);
    			$response = new Response($pdf->Output($nomFitxer, "I")); // inline
    			$response->headers->set('Content-Disposition', 'attachment; filename="'.$nomFitxer.'"');
    			$response->headers->set('Pragma: public', true);
    			$response->headers->set('Content-Transfer-Encoding', 'binary');
    			$response->headers->set('Content-Type', 'application/pdf');
    			
    		} else {
    			// Close and output PDF document
    			$response = new Response($pdf->Output($nomFitxer, "D")); // save as...
    			$response->headers->set('Content-Type', 'application/pdf');
    		}
    		
    		return $response;
    	}
    	 
    	throw new NotFoundHttpException("Page not found");//ServiceUnavailableHttpException
    }
    
    public function pdfrebutsAction(Request $request) {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$queryparams = $this->queryRebuts($request);
    	
    	$rebuts = $queryparams['query']->getResult();
    	
    	$pdf = $this->imprimirrebuts($rebuts);
    
    	// Close and output PDF document
    	//$nomFitxer = 'rebuts_socis_'.date('Ymd_Hi').'.pdf';
    	//if (count($rebuts) == 1) $nomFitxer = 'rebut_'.$rebut->getNum().'_'.date('Ymd_Hi').'.pdf';
    	$nomFitxer = 'rebuts_'.date('Ymd_Hi').'.pdf';
    
    	if ($request->query->has('print') and $request->query->get('print') == true) {
    		// force print dialog
    		$js = 'print(true);';
    		// set javascript
    		$pdf->IncludeJS($js);
    		$response = new Response($pdf->Output($nomFitxer, "I")); // inline
    		$response->headers->set('Content-Disposition', 'attachment; filename="'.$nomFitxer.'"');
    		$response->headers->set('Pragma: public', true);
    		$response->headers->set('Content-Transfer-Encoding', 'binary');
    		$response->headers->set('Content-Type', 'application/pdf');
    			 
    	} else {
    		// Close and output PDF document
    		$response = new Response($pdf->Output($nomFitxer, "D")); // save as...
    		$response->headers->set('Content-Type', 'application/pdf');
    	}
    
    	return $response;
    	
    }
    
    private function imprimirrebuts($rebuts) {
    	// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    	$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    	 
    	//$pdf->init(array('header' => false, 'footer' => false, 'logo' => 'logo-foment-martinenc.jpg','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    	$pdf->init(array('header' => false, 'footer' => false, 'logo' => '','author' => 'Foment Martinenc', 'title' => 'Rebuts Socis/es - ' . date("Y")), false);
    	 
    	$marginRebuts = 20;
    	
    	//set margins
    	$pdf->SetMargins(PDF_MARGIN_LEFT, $marginRebuts, PDF_MARGIN_RIGHT);
    	//set auto page breaks
    	$pdf->SetAutoPageBreak(TRUE, 0);
    	
    	// Add a page
    	$pdf->AddPage();

    	$y = $pdf->getY();
    	$x = $pdf->getX();
    
    	// set color for background
    	$pdf->SetFillColor(255, 255, 255); // Blanc 
    	
    	$w_full = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
    	$h_page = $pdf->getPageHeight();
    	$h_middle = $h_page/2; 
    	
    	$styleSeparator = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 6, 'color' => array(100, 100, 100));
    	 
    	foreach ($rebuts as $rebut) {
    		if ($y = $pdf->getY() > $h_middle) {
    			$pdf->AddPage();
    		}
    		$y = $pdf->getY();
    		$x = $pdf->getX();
    	
    		$this->imprimirrebut($pdf, $x, $y, $w_full, $h_middle - 10, $rebut, false);
    	
    		$pdf->Line(5, $h_middle, $pdf->getPageWidth() - 5, $h_middle, $styleSeparator);
    	
    		$this->imprimirrebut($pdf, $x, $h_middle + PDF_MARGIN_TOP, $w_full, $h_page - 10, $rebut, true);
    	
    	}
    	 
    	// reset pointer to the last page
    	$pdf->lastPage();
    
    	return $pdf;
    }
    
    private function imprimirrebut($pdf, $x, $y, $w, $h, $rebut, $copia) {
    	$w_titol_foto = 20;
    	$w_titol_text = 84;
    	$marginRebuts = 20;
    	
    	// Total 630
    	$w_header_1 = 390; // Pixels
    	$w_header_2 = 100; // Pixels
    	$w_header_3 = 140; // Pixels
    	// Total 630 - 20
    	$w_concepte_1 = 213; // Pixels
    	$w_concepte_2 = 300; // Pixels
    	$w_concepte_3 = 103; // Pixels
    	 
    	$w_rebut = $pdf->pixelsToUnits($w_header_1+$w_header_2+$w_header_3); // 178 unitats
    	
    	$pdf->SetAlpha(1);
    	$pdf->SetTextColor(0, 0, 0); // Negre
    	 
    	// El rebut de cursos necessita 65 unitats, les seccions 65 mín + 5 per detall
    	$mida_minima = 65;
    	if ($rebut->esSeccio()) $mida_minima += (5 * $rebut->getNumDetallsActius());
    	
    	if ($y + $mida_minima > $pdf->getPageHeight() - $marginRebuts) {
    		$pdf->AddPage();
    		 
    		$y = $pdf->getY();
    		$x = $pdf->getX();
    	}
    	
    	$x_titol = $x;
    	$y_titol = $y;
    	
    	// Image ($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='',
    	// $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
    	$pdf->Image(K_PATH_IMAGES.'imatges/logo-foment-martinenc.png', $x_titol + 2, $y_titol + 3, $w_titol_foto, 0, 'png', '', 'M', true, 150, '',
    			false, false, 'LTRB', false, false, false);
    	 
    	$x_titol = $x + $w_titol_foto + 4;
    	
    	$y_titol++;
    	$pdf->SetFont('helvetica', 'B', 17.5);
    	$htmlTitle = '<p>FOMENT MARTINENC</p>';
    	$pdf->writeHTMLCell($w_titol_text, 0, $x_titol, $y_titol, $htmlTitle, '', 0, true, true, 'C', true);
    	$y_titol += 7.5;
    	 
    	$pdf->SetFont('helvetica', '', 11);
    	$htmlTitle = '<p>ATENEU CULTURAL i RECREATIU</p>';
    	$pdf->writeHTMLCell($w_titol_text, 0, $x_titol, $y_titol, $htmlTitle, '', 0, false, true, 'C', true);
    	$y_titol += 5;
    	 
    	$pdf->SetFont('helvetica', '', 7);
    	$htmlTitle = '<p>DECLARAT D\'UTILITAT PÚBLICA. FUNDAT L\'ANY 1877</p>';
    	$pdf->writeHTMLCell($w_titol_text, 0, $x_titol, $y_titol, $htmlTitle, '', 0, false, true, 'C', true);
    	$y_titol += 3.5;
    	 
    	$pdf->SetFont('helvetica', '', 6);
    	$pdf->setFontStretching(120);
    	//$pdf->setFontSpacing(0.2);
    	$htmlTitle = '<p>Provença, 591 - 08026 BARCELONA<br/>Tels. 93 455 70 95 - 93 435 73 76<br/>';
    	$htmlTitle .= '<a href="mailto:info@fomentmartinenc.org">info@fomentmartinenc.org</a> | ';
    	$htmlTitle .= '<a href="http://www.fomentmartinenc.org">http://www.fomentmartinenc.org</a></p>';
    	$pdf->writeHTMLCell($w_titol_text, 0, $x_titol, $y_titol, $htmlTitle, '', 0, false, true, 'C', true);
    	
   		$y_titol += 12;
   		$pdf->SetFont('helvetica', 'B', 10);
   		$pdf->SetTextColor(150, 150, 150);
   		if ($copia == true) {
   			$htmlTitle = 'CÒPIA FOMENT';
   		} else {
   			$htmlTitle = 'CÒPIA INTERESSAT';
   		}
   		$pdf->writeHTMLCell($w_titol_text, 0, $x + 14, $y_titol, $htmlTitle, '', 0, false, true, 'C', true);
   		$pdf->SetTextColor(0, 0, 0); // Negre
    	
    	// Capçalera
    	$pdf->SetFont('helvetica', '', 12);
    	$pdf->setX($x);
    	$pdf->setY($y);
    	
    	$html = '<table class="main-table" border="0" cellpadding="7" cellspacing="0" nobr="true">';
    	$html .= '<tr  nobr="true"><td rowspan="4" width="'.$w_header_1.'" align="center" style="border: 0.5px solid #999998; color:#05b5a8;"></td>';
    	//$html .= '<p><b>FOMENT MARTINENC</b><br/>ATENEU CULTURAL i RECREATIU<br/>DECLARAT D\'UTILITAT PÚBLICA. FUNDAT L\'ANY 1877</p>';
    	//$html .= '<p>Provença, 591 - 08026 BARCELONA<br/>Tels. 93 455 70 95 - 93 435 73 76</p></td>';
    	$html .= '<td align="center" colspan="2" width="'.($w_header_2+$w_header_3) .'" style="border: 0.5px solid #999998; color:#555555;"><b>'.$rebut->titolRebut().'</b></td></tr>';
    	$html .= '<tr><td align="left" width="'.$w_header_2.'" style="border: 0.5px solid #999998; color:#555555;">NIF</td><td width="'.$w_header_3.'" align="center" style="border: 0.5px solid #999998; color:#333333;"><b>G-08917635</b></td></tr>';
    	$html .= '<tr><td align="left" style="border: 0.5px solid #999998; color:#555555;">Nº rebut</td><td align="center" style="border: 0.5px solid #999998; color:#333333;"><b>'.$rebut->getNumFormat().'</b></td></tr>';
    	$html .= '<tr><td align="left" style="border: 0.5px solid #999998; color:#555555;">Data</td><td align="center" style="border: 0.5px solid #999998; color:#333333;"><b>'.$rebut->getDataemissio()->format('d/m/Y').'</b></td></tr>';
    	
    	$color = '#045B7C';
    	if ($rebut->esSeccio()) {
    		// Subtaula conceptes
    		$subTable =  '<table border="0" cellpadding="2" cellspacing="0" nobr="true"><tbody>';
    		 
    		foreach ($rebut->getDetalls() as $detall) {
    			if ($detall->getDatabaixa() == null) {
    				$subTable .= '<tr><td width="'.$w_concepte_1.'" align="left" style="color:'.$color.';"><span style="font-size: 11px;">'.$detall->getPersona()->getNomCognoms().'</span></td>';
    				$subTable .= '<td width="'.$w_concepte_2.'" align="left" style="color:'.$color.';"><span style="font-size: 10px;">'.$detall->getConcepte().'</span></td>';
    				$subTable .= '<td width="'.$w_concepte_3.'" align="right" style="color:'.$color.';"><span style="font-size: 11px;">'.number_format($detall->getImport(), 2, ',', '.').' €</span></td></tr>';
    			}
    		}
    		 
    		if ($rebut->esCorreccio() == true) {
    			$correccio = $rebut->getImport() - $rebut->getImportSenseCorreccio();
    			$subTable .= '<tr><td width="'.$w_concepte_1.'" align="left" style="color:'.$color.';"><span style="font-size: 11px;"></span></td>';
    			$subTable .= '<td width="'.$w_concepte_2.'" align="left" style="color:'.$color.';"><span style="font-size: 10px;">'.$rebut->getNouconcepte().'</span></td>';
    			$subTable .= '<td width="'.$w_concepte_3.'" align="right" style="color:'.$color.';"><span style="font-size: 11px;">'.number_format($correccio, 2, ',', '.').' €</span></td></tr>';
    		}
    		 
    		$subTable .= '<tr><td colspan="2" align="right" style="color:'.$color.'; border-top: 0.5px solid '.$color.';"><span style="font-size: xx-small;"><i>total</i></span></td>';
    		$subTable .= '<td align="right" style="color:'.$color.';border-top: 0.5px solid '.$color.';"><span style="font-size: xx-small;"><b>'.number_format($rebut->getImport(), 2, ',', '.').' €</b></span></td></tr>';
    		$subTable .= '</tbody></table>';
    		 
    	} else {
    		$concepte = $rebut->getConcepte();
    		if ($rebut->esCorreccio()) $concepte .= ' ('.$rebut->getNouconcepte().')';
    		$subTable =  '<p style="color:'.$color.';font-size: 16px;"><br/>'.$concepte.'</p>';
    	}
    	
    	$html .= '<tr style="background-color:#FEFEFE;"  nobr="true"><td colspan="3" align="left" style="border-top: 0.5px solid #999998;border-right: 0.5px solid #999998;border-left: 0.5px solid #999998;">'.$subTable.'</td></tr>';
    	
    	// Subtaula peu deutor
    	$subTable =  '<table border="0" cellpadding="2" cellspacing="0" nobr="true"><tbody>';
    	$subTable .=  '<tr><td width="'.$w_header_1.'" align="left" style="color:#333333;"><span style="font-size: small;">';
    	if ($rebut->getDeutor()->esSociVigent() == true) $subTable .=  '<b>Soci '.$rebut->getDeutor()->getNumSoci().'</b><br/>';
    	$subTable .=  '<b>'.$rebut->getDeutor()->getNomCognoms().'</b><br/>';
    	$subTable .=  $rebut->getDeutor()->getAdrecaCompleta().'</span></td></tr>';
    	$subTable .= '</tbody></table>';
    	
    	$html .= '<tr nobr="true"><td colspan="3" align="left" style="border-bottom: 0.5px solid #999998;border-right: 0.5px solid #999998;border-left: 0.5px solid #999998;">'.$subTable.'</td></tr>';
    	$html .= '</tbody></table>';
    	//	writeHTMLCell ($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
    	$pdf->writeHTMLCell($w_rebut, 0, $x, $y, $html, 0, 2, false, true, 'C', true);
    	//writeHTML ($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
    	//$pdf->writeHTML($html, true, false, false, false, '');
    	
    	$rebut_h = $pdf->getY() - $y;
    	
    	// Subtaula peu total import
    	$tableTotal =  '<table border="0" cellpadding="10" cellspacing="0" nobr="true"><tbody>';
    	/*$tableTotal .= '<tr style="background-color:'.$color.';color:white;"><td><span style="font-size: x-small;"><u>Import Rebut</u></span><br/>';*/
    	$tableTotal .= '<tr ><td style="color:'.$color.';border: 2px solid '.$color.';"><span style="font-size: x-small;"><u>Import Rebut</u></span><br/>';
    	$tableTotal .= '<b>'.number_format($rebut->getImport(), 2, ',', '.').' €</b></td></tr>';
    	$tableTotal .= '</tbody></table>';
    	
    	$w_totalTable = $pdf->pixelsToUnits($w_header_2 + $w_header_3);
    	$pdf->writeHTMLCell($w_totalTable-10, 0, PDF_MARGIN_LEFT + $w_rebut - $w_totalTable + 10, $pdf->getY() - 20, $tableTotal, 0, 2, false, true, 'C', true);
    	
    	
    	$y += $rebut_h + 10;
    	
    	$pdf->SetTextColor(236, 27, 35); // #ec1b23 red
    	$pdf->SetDrawColor(200);
    	if (!$rebut->cobrat() || $rebut->getDatabaixa() != null ) {
    		$x_offset = 0;
    		if (!$rebut->cobrat()) $strAigua = 'Pendent de pagament';
    		if ($rebut->getDatabaixa() != null) {
    			$strAigua = 'Rebut anul·lat';
    			$pdf->setFontStretching(150);
    			$x_offset = 20;
    		}
    		 
    		$pdf->SetFont('helvetica', '', 36);
    		 
    		//$pdf->SetTextColor(236, 27, 35); // #f57031 orange
    		$pdf->SetAlpha(0.3);
    		// Start Transformation
    		$pdf->StartTransform();
    		// Rotate -10 degrees
    		$pdf->Rotate(345, $pdf->getPageWidth()/2 , $pdf->getY());
    		$pdf->Text($x + $x_offset, $pdf->getY()-($rebut_h/2) -10 , $strAigua); // $y + (($pdf->getY()-$y)/2)
    		// Stop Transformation
    		$pdf->StopTransform();
    		 
    		$pdf->setFontStretching(100);
    	}
    	
    	if ($rebut->cobrat() && $rebut->getDatapagament() != null) {
    		$strAigua = 'Rebut cobrat '.PHP_EOL.'en data '.$rebut->getDatapagament()->format('d/m/Y');
    	
    		$pdf->SetFont('helvetica', '', 16);
    		 
    		//$pdf->SetTextColor(178, 219, 161); // #b2dba1 soft green
    		 
    		$pdf->MultiCell(0, 0, $strAigua, 0, 'C', 0, 1, PDF_MARGIN_LEFT - 10, $pdf->getY() - 15, true);
    		 
    	}
    	
    	
    }
    
    
    public function rebutproveidorAction(Request $request) {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$id = $request->query->get('id', 0);
    	 
    	$em = $this->getDoctrine()->getManager();
    
    	$pagament = $em->getRepository('FomentGestioBundle:Pagament')->find($id);
    
    	if ($pagament != null) {
    		 
    		$pdf = $this->imprimirliquidacions(array($pagament));
    		 
    		// Close and output PDF document
    		//$nomFitxer = 'rebuts_socis_'.date('Ymd_Hi').'.pdf';
    		//if (count($rebuts) == 1) $nomFitxer = 'rebut_'.$rebut->getNum().'_'.date('Ymd_Hi').'.pdf';
    		$nomFitxer = UtilsController::netejarNom($pagament->getConcepte(), true).'_'.date('Ymd_Hi').'.pdf';
    		 
    		if ($request->query->has('print') and $request->query->get('print') == true) {
    			// force print dialog
    			$js = 'print(true);';
    			// set javascript
    			$pdf->IncludeJS($js);
    			$response = new Response($pdf->Output($nomFitxer, "I")); // inline
    			$response->headers->set('Content-Disposition', 'attachment; filename="'.$nomFitxer.'"');
    			$response->headers->set('Pragma: public', true);
    			$response->headers->set('Content-Transfer-Encoding', 'binary');
    			$response->headers->set('Content-Type', 'application/pdf');
    
    		} else {
    			// Close and output PDF document
    			$response = new Response($pdf->Output($nomFitxer, "D")); // save as...
    			$response->headers->set('Content-Type', 'application/pdf');
    		}
    		 
    		return $response;
    	}
    	 
    	throw new NotFoundHttpException("Page not found");//ServiceUnavailableHttpException
    }
    
    private function imprimirliquidacions($pagaments) {
    	// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    	$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    	$pdf->init(array('header' => false, 'footer' => false, 'logo' => '','author' => 'Foment Martinenc', 'title' => 'Liquidació Proveïdors - ' . date("Y")), count($pagaments) > 1);
    
    	$marginPagaments = 20;
    	 
    	//set margins
    	$pdf->SetMargins(PDF_MARGIN_LEFT, $marginPagaments, PDF_MARGIN_RIGHT);
    	//set auto page breaks
    	$pdf->SetAutoPageBreak(TRUE, 0);
    	 
    	// Add a page
    	$pdf->AddPage();
    
    	$w_full = $pdf->getPageWidth() - PDF_MARGIN_LEFT - PDF_MARGIN_RIGHT;
    	$h_page = $pdf->getPageHeight();
    	$h_middle = $h_page/2; 
    	
    	// set color for background
    	$pdf->SetFillColor(255, 255, 255); // Blanc
    	
    	$styleSeparator = array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 6, 'color' => array(100, 100, 100));
    	
    	foreach ($pagaments as $liquidacio) {
    
    		if ($y = $pdf->getY() > $h_middle) {
    			$pdf->AddPage();
    		}
    		$y = $pdf->getY();
    		$x = $pdf->getX();
    		
    		$this->imprimirliquidacio($pdf, $x, $y, $w_full, $h_middle - 10, $liquidacio, false);
    		
    		$pdf->Line(5, $h_middle, $pdf->getPageWidth() - 5, $h_middle, $styleSeparator);
    		
    		$this->imprimirliquidacio($pdf, $x, $h_middle + PDF_MARGIN_TOP, $w_full, $h_page-10, $liquidacio, true);

    	}
    
	    // reset pointer to the last page
    	$pdf->lastPage();
    
       	return $pdf;
    }
    
    
    private function imprimirliquidacio($pdf, $x, $y, $w, $h, $liquidacio, $copia) { 
    	$pdf->SetAlpha(1);
    	$pdf->SetTextColor(0, 0, 0); // Negre
    	
    	$formatter = new \NumberFormatter("ca", \NumberFormatter::SPELLOUT);
    	
    	$w_titol_foto = 15;
   		$w_half = $w/2;
   		$w_third = $w/3;
   		$w_margin = 10;
   		
   		// Capçalera esquerra
   		$x_titol = $x + 2;
   		$y_titol = $y;
   		
   		$rectangle_h = $h - $y; //$h-($w_margin/2)
   		$pdf->MultiCell($w + (2*$w_margin), $rectangle_h + $w_margin, '',
   				array('LRBT' => array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(200, 200, 200))), 
   				'C', 1, 1, $x-$w_margin, $y-$w_margin, true, 1, false, true, $rectangle_h + $w_margin, 'M', false);  
   		
   		
   		/* Image ($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='',
   		   $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array()) */
   		$pdf->Image(K_PATH_IMAGES.'imatges/logo-foment-martinenc.png', $x_titol, $y_titol, $w_titol_foto, 0, 'png', '', 'M', true, 150, '',
    				false, false, 'LTRB', false, false, false); 
    		 
    	$x_titol += $w_titol_foto - 5;
    	$w_titol = $w_half -  $w_titol_foto;
    
    	$pdf->SetFont('helvetica', 'B', 15);
    	$htmlTitle = '<p>FOMENT MARTINENC</p>';
    	$pdf->writeHTMLCell($w_titol, 0, $x_titol, $y_titol, $htmlTitle, '', 0, true, true, 'C', true);
    	$y_titol += 7.5;
    		 
    	$pdf->SetFont('helvetica', '', 10);
    	$htmlTitle = '<p>ATENEU CULTURAL i RECREATIU</p>';
    	$pdf->writeHTMLCell($w_titol, 0, $x_titol, $y_titol, $htmlTitle, '', 0, false, true, 'C', true);
    	$y_titol += 5;
    		 
    	$pdf->SetFont('helvetica', '', 7);
    	$htmlTitle = '<p>DECLARAT D\'UTILITAT PÚBLICA. FUNDAT L\'ANY 1877</p>';
    	$pdf->writeHTMLCell($w_titol, 0, $x_titol, $y_titol, $htmlTitle, '', 0, false, true, 'C', true);
    	$y_titol += 3.5;
    		 
    	// Capçalera dreta
    	$pdf->SetTextColor(100, 100, 100);
    	$pdf->SetFont('helvetica', 'I', 13);
    	$x_titol = $x + $w_half;
    	$y_titol = $y + 2;
    
    	$htmlTitle = '<p>'.$liquidacio->titolLiquidacio().'</p>';
    	$pdf->writeHTMLCell($w_half, 0, $x_titol, $y_titol, $htmlTitle, 0, 2, false, true, 'C', true);
    
    	$y_titol += 7;
    
    	$pdf->SetFont('helvetica', '', 12);
    	if ($copia == true) $htmlTitle = 'CÒPIA FOMENT';
    	else $htmlTitle = 'CÒPIA INTERESSAT';
    		
   		$pdf->writeHTMLCell($w_half, 0, $x_titol, $y_titol, $htmlTitle, '', 0, false, true, 'C', true);
    
    	// Concepte
   		$pdf->SetTextColor(0, 0, 0); // Negre
   		$pdf->SetFont('helvetica', '', 11);
    
   		$x_concepte = $x;
   		$y_concepte = $y_titol + 15;
    
   		$proveidor = $liquidacio->getProveidor();
   		$nomGap = '_________________________________';
   		$dniGap = '________________';
    
   		$raoSocial = ($proveidor != null && $proveidor->getRaosocial() != null && $proveidor->getRaosocial() != ''? $proveidor->getRaosocial():$nomGap);
   		$cifProveidor = ($proveidor != null && $proveidor->getCif() != null && $proveidor->getCif() != ''? $proveidor->getCif():'________________');
   		$concepte = $liquidacio->getConcepte();
    
   		$text = '';
   		if ($liquidacio->esPagamentcurs()) {
   			$text .= $raoSocial. " amb DNI " .$cifProveidor;
   			$concepte = str_replace($raoSocial, "", $concepte);  // Treure nom profe del concepte
   		} else {
   			$text .= "En ".$nomGap. " amb DNI " .$dniGap. " com a representant de ". $raoSocial. " amb DNI " .$cifProveidor;
   		}
    
   		$text .= " he rebut de l’associació FOMENT MARTINENC,";
   		$text .=" domiciliada a Barcelona,  carrer Provença, 591, amb NIF G08917635, com a entitat inclosa dins les regulades en";
   		$text .=" l’article 16 de la Llei 49/2002, de 23 de desembre, de règim fiscal de les entitats sense finalitats lucratives";
   		$text .=" i dels incentius fiscals al mecenatge, ";
   		$text .=" la quantitat de ".$formatter->format($liquidacio->getImport()). " euros ";
   		$text .=" en concepte de " .$concepte .".\n";
    
   		$pdf->MultiCell($w_half * 2, 0, $text, 0, 'J', 0, 1, $x_concepte, $y_concepte, true);
   		//$pdf->writeHTMLCell($w_half * 2, 0, $x_concepte, $y_concepte, $html, '', 0, false, true, 'L', true);
   		$x_footer = $x;
   		$y_footer = $y_concepte + 35;
    
   		$pdf->SetFont('helvetica', '', 14);
   		// Subtaula peu total import
   		$html =  '<table border="0" cellpadding="10" cellspacing="0" nobr="true"><tbody>';
   		/*$tableTotal .= '<tr style="background-color:'.$color.';color:white;"><td><span style="font-size: x-small;"><u>Import Rebut</u></span><br/>';*/
   		$html .= '<tr ><td style="color:#045B7C;border: 0.1em solid #045B7C;"><span style="font-size: small;"><u>Import Liquidació</u></span><br/>';
   		$html .= '<b>'.number_format($liquidacio->getImport(), 2, ',', '.').' €</b></td></tr>';
   		$html .= '</tbody></table>';
    
   		$pdf->writeHTMLCell($w_third, 0, $x_footer, $y_footer, $html, 0, 2, false, true, 'C', true);
   		$x_footer += (2*$w_third);
    
   		$pdf->SetFont('helvetica', '', 11);
   		$pdf->SetTextColor(50, 50, 50);

   		// Subtaula peu total import
   		$dateCurrent = new \DateTime();
    	$html =  '<p>Barcelona, '.$dateCurrent->format('d \d\e F \d\e Y').'</p>';
    	$pdf->writeHTMLCell($w_half, 0, $x_footer, $y_footer, $html, 0, 2, false, true, 'L', true);
    
    	$y_footer += 8;
    	$html = '<p>Signatura</p>';
    	$pdf->writeHTMLCell($w_half, 0, $x_footer, $y_footer, $html, 0, 2, false, true, 'L', true);
    
    	$pdf->SetTextColor(100, 100, 100);
    	// Peu document. Dades contacte
    	$pdf->SetFont('helvetica', '', 8);
    	//$pdf->setFontStretching(120);
    	//$pdf->setFontSpacing(0.2);
    	$htmlTitle = '<p>Provença, 591 - 08026 BARCELONA. Tels 93 455 70 95 - 93 435 73 76 | ';
    	$htmlTitle .= '<a href="mailto:info@fomentmartinenc.org">info@fomentmartinenc.org</a> | ';
    	$htmlTitle .= '<a href="http://www.fomentmartinenc.org">http://www.fomentmartinenc.org</a></p>';
    	$pdf->writeHTMLCell($w_half * 2, 0, $x, $h - 7, $htmlTitle, '', 0, false, true, 'C', true);
    }
    
    
    
    public function imprimircarnetAction(Request $request) {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	
    	$id = $request->query->get('id', 0);
    	
    	if ($id > 0) {
    		$em = $this->getDoctrine()->getManager();
    		
    		$soci = $em->getRepository('FomentGestioBundle:Soci')->find($id);
    		
    		$response = $this->imprimircarnets(array($soci));
    		return $response;
    	}
    	
    	throw new NotFoundHttpException("Page not found");//ServiceUnavailableHttpException
    }
    
    public function imprimircarnetsAction(Request $request) {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	// Només socis
    	$request->query->set('s', true);
    	 
    	$queryparams = $this->queryPersones($request);
    	 
    	$socis = $queryparams['query']->getResult();
    
    	$response = $this->imprimircarnets($socis);
    	return $response;
    }
    
    public function imprimircarnets($socis) {
    	// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    	$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    	
    	//$pdf->init(array('header' => false, 'footer' => false, 'logo' => 'logo-foment-martinenc.jpg','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    	$pdf->init(array('header' => false, 'footer' => false, 'logo' => '','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    	
    	// Add a page
    	$pdf->AddPage();
    	
    	// Carnets pàgina 2 x 5  => total 10 carnets 
    	$margin = 2;
    	$padding = 1;
    	
    	//set margins
    	$pdf->SetMargins(PDF_MARGIN_LEFT - 10, PDF_MARGIN_TOP - 15, PDF_MARGIN_RIGHT - 10);
    	//set auto page breaks
    	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM - 15);
    	
    	$m = $pdf->getMargins();
    	
    	$w = $pdf->getPageWidth() - $m['left'] - $m['right'] - $m['padding_left'] - $m['padding_right'] - (3 * $margin);
    	$h = $pdf->getPageHeight() - $m['top'] - $m['bottom'] - $m['header'] - $m['footer'] - $m['padding_top'] - $m['padding_bottom'] - 6*$margin;
    	
    	$carnet_w = floor( $w / 2 );
    	$carnet_h = floor( $h / 5 );
    	
    	// get current vertical position
    	$pdf->setXY(PDF_MARGIN_LEFT - 10,PDF_MARGIN_TOP - 15);
    	$y_ini = $pdf->getY();
    	$x_ini = $pdf->getX();
    	 
    	$y = $y_ini - $carnet_h;
    	$x = $x_ini;

    	// set color for background
    	$pdf->SetFillColor(255, 255, 255); // Blanc
    	// set color for text
    	$pdf->SetTextColor(0, 0, 0); // Negre
    	
    	
    	foreach ($socis as $i => $s) {
	    	if ($i > 0 && $i % 10 == 0) {  // 10 x pàgina. Canvi de pàgina
	    		// Add a page
	    		$pdf->AddPage();
	    		
	    		$y = $y_ini - $carnet_h;
	    		$x = $x_ini;
	    	}
    		
    		if ($i % 2 == 0) {
    			$x = $x_ini;// Parell
    			$y +=  $carnet_h + $margin;  
    		}
    		else $x = $x_ini + $carnet_w + $margin;// Senar
    		
    		//	writeHTMLCell ($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
    		$pdf->writeHTMLCell($carnet_w, $carnet_h, $x, $y, '', 
    				array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 1, 'color' => array(100, 100, 100))),
    				0, false, true, 'C', true);
	    		
    		$this->drawCarnet($pdf, $x, $y, $carnet_w, $carnet_h, $padding, $s);
    	}
    	
    	// reset pointer to the last page
    	$pdf->lastPage();
    	 
    	// Close and output PDF document
    	$response = new Response($pdf->Output("graella_carnets.pdf", "D"));
    	$response->headers->set('Content-Type', 'application/pdf');
    	return $response;
    	//return new Response("hola");
    }
    
    private function drawCarnet($pdf, $x, $y, $w, $h, $padding, $soci) {
    	// $x i $y és la contonada esquerra superior
		$margin = 10;
    	$x += $padding;
    	$y += $padding;
    	$w -= (2*$padding); 
    	$h -= (2*$padding);
    	
    	$foto_w = floor( $w / 3.5 );
    	
    	$foto_h = floor( $h / 2 );
    	$info_w = $w - $foto_w - $margin;
    	
    	// Image ($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', 
    	// $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
    	$pdf->Image(K_PATH_IMAGES.'imatges/logo-foment-martinenc.png', $x + 5, $y, 0, ($foto_h*0.8), 'png', '', 'M', true, 150, '', 
    			false, false, 'LTRB', false, false, false);

    	
    	try {
    		if ($soci->getFoto() != null && $soci->getFoto()->getWidth() > 0 && $soci->getFoto()->getHeight() > 0) {
    			$fotoSrc = $soci->getFoto()->getWebPath();
    			
    			$ratioFoto = $soci->getFoto()->getWidth()/$soci->getFoto()->getHeight();
    			if ($ratioFoto > ($foto_w/$foto_h)) {
    				// foto més ample. cal reduir ample
    				$foto_w_scaled = $foto_w;
    				$foto_h_scaled = ($foto_w/$soci->getFoto()->getWidth()) * $soci->getFoto()->getHeight();
    			} else {
    				// foto més alta. Cal reduir alçada
    				$foto_h_scaled = $foto_h;
    				$foto_w_scaled = ($soci->getFoto()->getWidth()/$soci->getFoto()->getHeight())*$foto_h;
    			}
    			
    			$pdf->Image(K_PATH_IMAGES.$fotoSrc, $x+2, $y + 2 + ($foto_h*0.8), $foto_w_scaled, $foto_h_scaled, '', '', 'B', true, 150, '',
    				false, false, '1', false, false, false);
    		} else {
    			$pdf->Image(K_PATH_IMAGES.'imatges/icon-photo.blue.png', $x, $y + ($foto_h*0.8), $foto_w, ($foto_h*1.2), 'png', '', 'B', true, 150, '',
    					false, false, 'LTRB', false, false, false);
    		}
    	} catch (\Exception $e) {
    		
    		$pdf->Image(K_PATH_IMAGES.'imatges/icon-photo.blue.png', $x, $y + ($foto_h*0.8), $foto_w, ($foto_h*1.2), 'png', '', 'B', true, 150, '',
    				false, false, 'LTRB', false, false, false);
    	}
    	
    	//$pdf->writeHTMLCell($foto_w, 0, $x, $y, '', '', 0, false, true, 'C', true);
    	
    	$x += $foto_w + floor( $margin / 2 );;
    	
    	$pdf->SetFont('helvetica', 'B', 14);
    	$htmlTitle = "<p>FOMENT MARTINENC</p>";
    	$pdf->writeHTMLCell($info_w, 0, $x, $y, $htmlTitle, '', 0, false, true, 'C', true);
    	$y += 5.5;
    	
    	$pdf->SetFont('helvetica', '', 9);
    	$htmlTitle = "<p>ATENEU CULTURAL i RECREATIU</p>";
    	$pdf->writeHTMLCell($info_w, 0, $x, $y, $htmlTitle, '', 0, false, true, 'C', true);
    	$y += 3.5;
    	
    	$pdf->SetFont('helvetica', '', 6);
    	$htmlTitle = "<p>DECLARAT D'UTILITAT PÚBLICA</p>";
    	$pdf->writeHTMLCell($info_w, 0, $x, $y, $htmlTitle, '', 0, false, true, 'C', true);
    	$y += 2.5;
    	
    	$pdf->SetFont('helvetica', '', 4.5);
    	$pdf->setFontStretching(120);
    	//$pdf->setFontSpacing(0.2);
    	$htmlTitle = "<p><u>FUNDAT L'ANY 1877 PROVENÇA, 591 - 08026 BARCELONA</u></p>";
    	$pdf->writeHTMLCell($info_w, 0, $x, $y, $htmlTitle, '', 0, false, true, 'C', true);
    	$y += 4;
    	
    	$pdf->setFontSpacing(0.5);
    	//$pdf->setFontStretching(100);
    	$pdf->SetFont('helvetica', '', 13);
    	$htmlTitle = "<p>TÍTOL DE SOCI</p>";
    	$pdf->writeHTMLCell($info_w, 0, $x, $y, $htmlTitle, '', 0, false, true, 'C', true);
    	$y += 8;
    	
    	
    	$x -= 4;
    	$pdf->setFontSpacing(0);
    	$pdf->setFontStretching(90);
    	$pdf->SetFont('times', '', 9.5);
    	$htmlTitle = "<p>NOM:</p>";
    	$pdf->writeHTMLCell($info_w, 0, $x, $y, $htmlTitle, '', 0, false, true, 'L', true);

    	$pdf->setFontSpacing(0);
    	$pdf->setFontStretching(100);
    	$pdf->SetFont('times', 'I', 10.5);
    	//$htmlDadesSoci = $soci->getNomCognoms();
    	//$pdf->writeHTMLCell($info_w, 0, $x+13, $y, $htmlDadesSoci, '', 0, false, true, 'L', true);
    	//Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='M')
    	$pdf->SetXY($x + 14, $y - 0.3);
    	$pdf->Cell($info_w - 14, 0, $soci->getNomCognoms(), 0, 1, 'L', 0, '', 3);
    	$y += 5;

    	$pdf->setFontSpacing(0);
    	$pdf->setFontStretching(90);
    	$pdf->SetFont('times', '', 9.5);
    	$htmlTitle = "<p>ALTA:</p>";
    	$pdf->writeHTMLCell($info_w, 0, $x, $y, $htmlTitle, '', 0, false, true, 'L', true);
    	
    	$pdf->setFontSpacing(0);
    	$pdf->setFontStretching(100);
    	$pdf->SetFont('times', '', 10.5);
    	$htmlDadesSoci = "<i>".$soci->getDataalta()->format('d/m/Y')."</i>";
    	$pdf->writeHTMLCell($info_w, 0, $x+13, $y, $htmlDadesSoci, '', 0, false, true, 'L', true);
    	$y += 5;
    	 
    	$pdf->setFontSpacing(0);
    	$pdf->setFontStretching(90);
    	$pdf->SetFont('times', '', 9.5);
    	$htmlTitle = "<p>NÚM:</p>";
    	$pdf->writeHTMLCell($info_w, 0, $x, $y, $htmlTitle, '', 0, false, true, 'L', true);
    	
    	$pdf->setFontSpacing(0);
    	$pdf->setFontStretching(100);
    	$pdf->SetFont('times', '', 10.5);
    	$htmlDadesSoci = "<i>".$soci->getNumSoci()."</i>";
    	$pdf->writeHTMLCell($info_w, 0, $x+13, $y, $htmlDadesSoci, '', 0, false, true, 'L', true);
    	$y += 5;
    	
    	$pdf->SetFont('times', '', 5);
    	$htmlTitle = "<p>SECRETARIA GENERAL</p>";
    	$pdf->writeHTMLCell($info_w, 0, $x, $y, $htmlTitle, '', 0, false, true, 'R', true);
    	
    	// Reset
    	$pdf->setFontSpacing(0);
    	$pdf->setFontStretching(100);
    }
    
    
    public function pdfsociAction(Request $request) {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$id = $request->query->get('id', 0);
    	 
    	$em = $this->getDoctrine()->getManager();
    
    	$soci = $em->getRepository('FomentGestioBundle:Soci')->find($id);
    
    	if ($soci == null) throw new NotFoundHttpException("Page not found");//ServiceUnavailableHttpException
    	
    	$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    	 
    	
    	/*$pdf->init(array('header' => true, 'footer' => true,
    			'logo' => 'logo-fm1877-web.png','author' => 'Foment Martinenc',
    			'title' => '',
    			'string' => 'Certificat',
    			'leftMargin' => UtilsController::PDF_MARGIN_LEFT_NARROW,
    			'rightMargin' => UtilsController::PDF_MARGIN_RIGHT_NARROW));*/
    	$pdf->init(array('header' => true, 'footer' => true, 'logo' => 'logo-fm1877-web.png','author' => 'Foment Martinenc', 'title' => 'Fitxa personal', 'string' => 'Any '.date('Y')));
    	 
    	// Add a page
    	$pdf->AddPage();
    	 
    	// Values
    	$field_w = 30;
    	$table_w = 250;
    	$foto_w = 35;
    	$foto_h = 35;
    	$simple_gap = 6;
    	$double_gap = $simple_gap * 2;
    	
    	// get current vertical position
    	$y_ini = $pdf->getY();
    	$x_ini = $pdf->getX();
    	$x = $x_ini; 
    	$y = $y_ini;
    	
    	// set color for background
    	$pdf->SetFillColor(255, 255, 255); // Blanc
    	// set color for text
    	$pdf->SetTextColor(0, 0, 0); // Negre
    	 
    	
    	$pdf->SetFont('helvetica', '', 12);
    	$htmlTitle = "<h1>Soci: <b>".$soci->getNumSoci()."</b></h1>";
    	$pdf->writeHTMLCell( 50 , 0, ($pdf->getPageWidth()/2  - 25) , $y, $htmlTitle, 
    			array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => '')), 
    			0, false, true, 'C', true);
    	$y += $double_gap;
    	
    	$pdf->SetTextColor(4, 91, 124); // Negre
    	
    	$pdf->SetFont('helvetica', '', 11);
    	$htmlTitle = "<h2>Dades de contacte</h2>";
    	$pdf->writeHTMLCell(0, 0, $x, $y, $htmlTitle, 
    			array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(4, 91, 124))), 
    			0, false, true, 'L', true);
    	$y += $double_gap;

    	//$y_ini = $y;
    	
    	$pdf->SetTextColor(0, 0, 0); // Negre
    	
    	$pdf->SetFont('helvetica', '', 10);
    	$htmlText = "Nom :";
    	$pdf->writeHTMLCell($field_w, 0, $x, $y, $htmlText, '', 0, false, true, 'R', true);
    	$htmlText = "<b>".$soci->getNomCognoms()."</b>";
    	$pdf->writeHTMLCell(0, 0, $x + $field_w, $y, $htmlText, '', 0, false, true, 'L', true);
    	$y += $simple_gap;

    	$htmlText = "DNI :";
    	$pdf->writeHTMLCell($field_w, 0, $x, $y, $htmlText, '', 0, false, true, 'R', true);
    	$htmlText = "<b>".$soci->getDni()."</b>";
    	$pdf->writeHTMLCell(0, 0, $x + $field_w, $y, $htmlText, '', 0, false, true, 'L', true);
    	$y += $simple_gap;
    	
    	$htmlText = ($soci->getSexe()=='H'?'Nascut':'Nascuda').':';
    	$pdf->writeHTMLCell($field_w, 0, $x, $y, $htmlText, '', 0, false, true, 'R', true);
    	$htmlText = "<b>".$soci->getDataLlocNaixement()."</b>";
    	$pdf->writeHTMLCell(0, 0, $x + $field_w, $y, $htmlText, '', 0, false, true, 'L', true);
    	$y += $simple_gap;
    	 
    	$htmlText = "Adreça :";
    	$pdf->writeHTMLCell($field_w, 0, $x, $y, $htmlText, '', 0, false, true, 'R', true);
    	$htmlText = "<b>".$soci->getAdreca()."</b>";
    	$pdf->writeHTMLCell(0, 0, $x + $field_w, $y, $htmlText, '', 0, false, true, 'L', true);
    	$y += $simple_gap;
    	
    	/*$htmlText = "CP :";
    	$pdf->writeHTMLCell($field_w, 0, $x, $y, $htmlText, '', 0, false, true, 'R', true);
    	$htmlText = "<b>".$soci->getCp()."</b>";
    	$pdf->writeHTMLCell(0, 0, $x + $field_w, $y, $htmlText, '', 0, false, true, 'L', true);
    	$y += $simple_gap;*/
    	
    	$htmlText = "Població :";
    	$pdf->writeHTMLCell($field_w, 0, $x, $y, $htmlText, '', 0, false, true, 'R', true);
    	$htmlText = "<b>".$soci->getPoblacioCompleta()."</b>";
    	$pdf->writeHTMLCell(0, 0, $x + $field_w, $y, $htmlText, '', 0, false, true, 'L', true);
    	$y += $simple_gap;
    	
    	$htmlText = "Telèfon :";
    	$pdf->writeHTMLCell($field_w, 0, $x, $y, $htmlText, '', 0, false, true, 'R', true);
    	$htmlText = "<b>".$soci->getTelefons(false)."</b>";
    	$pdf->writeHTMLCell(0, 0, $x + $field_w, $y, $htmlText, '', 0, false, true, 'L', true);
    	$y += $simple_gap;
    	
    	$htmlText = "Mail :";
    	$pdf->writeHTMLCell($field_w, 0, $x, $y, $htmlText, '', 0, false, true, 'R', true);
    	$htmlText = "<b>".$soci->getCorreu()."</b>";
    	$pdf->writeHTMLCell(0, 0, $x + $field_w, $y, $htmlText, '', 0, false, true, 'L', true);
    	$y += $simple_gap;
    	
    	$y_inscripcio = $y + $simple_gap;
    	

    	$y = $y_ini + 2*$double_gap;
    	$x = $pdf->getPageWidth() - PDF_MARGIN_RIGHT  - $foto_w;
    	
    	/*$foto_w = floor( $w / 3.5 );
    	
    	$foto_h = floor( $h / 2 );
    	$info_w = $w - $foto_w - $margin;*/
    	
    	// Image ($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='',
    	// $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
    	/*$pdf->Image(K_PATH_IMAGES.'imatges/logo-foment-martinenc.png', $x + 5, $y, 0, ($foto_h*0.8), 'png', '', 'M', true, 150, '',
    			false, false, 'LTRB', false, false, false);*/
    	 
    	
    	try {
    		if ($soci->getFoto() != null && $soci->getFoto()->getWidth() > 0 && $soci->getFoto()->getHeight() > 0) {
    			$fotoSrc = $soci->getFoto()->getWebPath();
    	
    			$ratioFoto = $soci->getFoto()->getWidth()/$soci->getFoto()->getHeight();
    			if ($ratioFoto > ($foto_w/$foto_h)) {
    				// foto més ample. cal reduir ample
    				$foto_w_scaled = $foto_w;
    				$foto_h_scaled = ($foto_w/$soci->getFoto()->getWidth()) * $soci->getFoto()->getHeight();
    			} else {
    				// foto més alta. Cal reduir alçada
    				$foto_h_scaled = $foto_h;
    				$foto_w_scaled = ($soci->getFoto()->getWidth()/$soci->getFoto()->getHeight())*$foto_h;
    			}
    	
    			$pdf->Image(K_PATH_IMAGES.$fotoSrc, $x+2, $y + 2, $foto_w_scaled, $foto_h_scaled, '', '', 'B', true, 150, '',
    					false, false, '1', false, false, false);
    		} else {
    			$pdf->Image(K_PATH_IMAGES.'imatges/icon-photo.blue.png', $x, $y, $foto_w, ($foto_h*1.2), 'png', '', 'B', true, 150, '',
    					false, false, 'LTRB', false, false, false);
    		}
    	} catch (\Exception $e) {
    		 
    		$pdf->Image(K_PATH_IMAGES.'imatges/icon-photo.blue.png', $x, $y, $foto_w, ($foto_h*1.2), 'png', '', 'B', true, 150, '',
    				false, false, 'LTRB', false, false, false);
    	}
    	
    	
    	$x = $x_ini;
    	
    	if ($y + $simple_gap > $y_inscripcio) $y_inscripcio = $y + $simple_gap;
    	$y = $y_inscripcio;
    	
    	$pdf->SetTextColor(4, 91, 124); // Blau
    	 
    	$pdf->SetFont('helvetica', '', 11);
    	$htmlTitle = "<h2>Inscripcions</h2>";
    	$pdf->writeHTMLCell(0, 0, $x, $y, $htmlTitle,
    			array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(4, 91, 124))),
    			0, false, true, 'L', true);
    	$y += $double_gap;
    	
    	$pdf->SetTextColor(0, 0, 0); // Negre
    	$htmlText = "Soci des de :";
    	$pdf->writeHTMLCell($field_w, 0, $x, $y, $htmlText, '', 0, false, true, 'R', true);
    	$htmlText = "<b>".$soci->getDataalta()->format('d/m/Y')."</b>  (".$soci->getAntiguitatFormat().")";
    	$pdf->writeHTMLCell(0, 0, $x + $field_w, $y, $htmlText, '', 0, false, true, 'L', true);
    	$y += $simple_gap;
    	
    	
    	$htmlText = "Tipus de quota :";
    	$pdf->writeHTMLCell($field_w, 0, $x, $y, $htmlText, '', 0, false, true, 'R', true);
    	$aux = ($soci->esJuvenil()?UtilsController::CONCEPTE_REBUT_FOMENT_JUVENIL:UtilsController::CONCEPTE_REBUT_FOMENT_SENIOR);
    	$htmlText = "<b>".mb_convert_case(trim($aux), MB_CASE_UPPER, "UTF-8")."</b>";
    	$pdf->writeHTMLCell(0, 0, $x + $field_w, $y, $htmlText, '', 0, false, true, 'L', true);
    	$y += $double_gap;
    	
    	
    	$membrede = $soci->getMembreDeSortedById(true);
    	
    	if (count($membrede) > 0) {
	    	$htmlText =  '<table border="0" cellpadding="5" cellspacing="0" nobr="true"><tbody>';
	    	$htmlText .= '<tr><th style="color:#045B7C; border-bottom: 0.1em solid #045B7C;"><font size="13" color="#045B7C" weight="bold">Secció</font></th>';
	    	$htmlText .= '<th style="width:90px; color:#045B7C; border-bottom: 0.1em solid #045B7C;"><font size="13" color="#045B7C" weight="bold">Inscripció</font></th>';
	    	$htmlText .= '<th style="width:90px; color:#045B7C; border-bottom: 0.1em solid #045B7C; text-align: right;"><font size="13" color="#045B7C" weight="bold">Baixa</font></th></tr>';
	    	
	    	foreach ($membrede as $membre) {
				$seccio = $membre->getSeccio();
				
				$class="";
				if ($membre->getDatacancelacio() != null) $class = 'style="color:#999999;"';
				
				/*$tableTotal .= '<tr style="background-color:'.$color.';color:white;"><td style="color:#045B7C;border: 0.1em solid #045B7C;><span style="font-size: small;"><span style="font-size: x-small;"><u>Import Rebut</u></span><br/>';*/
				$htmlText .= '<tr '.$class.'><td>'.$seccio->getNom().'</td>';
				$htmlText .= '<td>'.$membre->getDatainscripcio()->format('d/m/Y').'</td>';
				$htmlText .= '<td  style="text-align: right;">'.($membre->getDatacancelacio() != null ?$membre->getDatacancelacio()->format('d/m/Y'):'').'</td></tr>';
	    	}
	    	
	    	$htmlText .= '</tbody></table>';
    	} else {
    		$htmlText = "no hi ha dades";
    	}
    	
    	$pdf->writeHTMLCell($table_w, 0, $x + 5, $y, $htmlText, '', 1, false, true, 'L', true);
    	$y = $pdf->getY() + $simple_gap;
    	
    	$pdf->SetTextColor(4, 91, 124); // Blau
    	
    	$pdf->SetFont('helvetica', '', 11);
    	$htmlTitle = "<h2>Cursos</h2>";
    	$pdf->writeHTMLCell(0, 0, $x, $y, $htmlTitle,
    			array('B' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(4, 91, 124))),
    			1, false, true, 'L', true);
    	$y += $double_gap;
    	 
    	
    	$participacions = $soci->getParticipacionsSortedById(true);
    	
    	$total = 0;
    	$pdf->SetTextColor(0, 0, 0); // Negre
    	$htmlText =  '<table border="0" cellpadding="5" cellspacing="0" nobr="true"><tbody>';
    	$htmlText .= '<tr><th style="color:#045B7C; border-bottom: 0.1em solid #045B7C;"><font size="13" color="#045B7C" weight="bold">Activitat</font></th>';
    	$htmlText .= '<th style="width:90px; color:#045B7C; border-bottom: 0.1em solid #045B7C;"><font size="13" color="#045B7C" weight="bold">Curs</font></th>';
    	$htmlText .= '<th style="width:90px; color:#045B7C; border-bottom: 0.1em solid #045B7C; text-align: right;"><font size="13" color="#045B7C" weight="bold">Baixa</font></th></tr>';
	    	 
    	foreach ($participacions as $participant) {
    		$activitat = $participant->getActivitat();

    		$class="";
	    	if ($activitat->getDatabaixa() != null) $class = 'style="color:#999999; font-size:11px;"';
		    			
	    	/*$tableTotal .= '<tr style="background-color:'.$color.';color:white;"><td style="color:#045B7C;border: 0.1em solid #045B7C;><span style="font-size: small;"><span style="font-size: x-small;"><u>Import Rebut</u></span><br/>';*/
	    	$htmlText .= '<tr><td '.$class.'>'.$activitat->getDescripcio().'</td>';
	    	$htmlText .= '<td '.$class.'>'.$activitat->getCurs().'</td>';
	    	$htmlText .= '<td style="color:#dddddd; text-align: right; font-size:11px;">'.($activitat->getDatabaixa() != null?$activitat->getDatabaixa()->format('d/m/Y'):'').'</td></tr>';
	    	$total++;
    	}
    	$htmlText .= '</tbody></table>';

    	if ($total == 0) $htmlText = "no hi ha dades"; 
    	
    	$pdf->writeHTMLCell($table_w, 0, $x + 5, $y, $htmlText, '', 1, false, true, 'L', true);
    	
    	
    	$pdf->SetTextColor(100, 100, 100); // Gris
    	$pdf->SetFont('helvetica', 'I', 7);
    	
    	$lopdTxt = "En Compliment de la Llei Orgànica 15/1999, de 13 de desembre, de protecció de dades de caràcter personal (LOPD), 
    			informem que les Dades Personals d'aquest document han de ser tractades de forma confidencial
    			i el responsable haurà d'adoptar les mesures oportunes que garanteixin la seguretat de les dades de caràcter personal 
    			i evitin la seva alteració, pèrdua, tractament o accés no autoritzat";
    	
    	$pdf->writeHTMLCell(0, 0, $x, $pdf->getPageHeight() - PDF_MARGIN_BOTTOM - 10, $lopdTxt, '', 1, false, true, 'J', true); 
    	
    	$pdf->SetFont('helvetica', '', 8);
    	
    	/*$pdf->SetFont('helvetica', 'I', 11);
    	$htmlTitle = "<p>Data: ".date('d/m/Y')."</p>";
    	$pdf->writeHTMLCell(0, 0, $x, $y, $htmlTitle, '', 0, false, true, 'R', true);*/
    	
    	// reset pointer to the last page
    	$pdf->lastPage();
    	
    	// Close and output PDF document
    	$response = new Response($pdf->Output("fitxa_soci_".$soci->getNum().".pdf", "D"));
    	$response->headers->set('Content-Type', 'application/pdf');

    	return $response;
    }
    
    public function imprimiretiquetesAction(Request $request) {
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    
    	$queryparams = $this->queryPersones($request);
    
    	$persones = $queryparams['query']->getResult();
    	$rows = $request->query->get('rows', UtilsController::ETIQUETES_FILES);
    	$cols = $request->query->get('rows', UtilsController::ETIQUETES_COLUMNES);
    	 
    	$response = $this->imprimiretiquetes($persones, $rows, $cols);
    	return $response;
    }
    
    public function imprimiretiquetes($persones, $rows, $cols) {
    	// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    	$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    	//$pdf->init(array('header' => false, 'footer' => false, 'logo' => 'logo-foment-martinenc.jpg','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    	$pdf->init(array('header' => false, 'footer' => false, 'logo' => '','author' => 'Foment Martinenc', 'title' => 'Graella etiquetes adreces - ' . date("Y")));
    
    	$pdf->setPrintHeader(false);
    	$pdf->setPrintFooter(false);
    	
    	//set margins
    	$pdf->SetMargins(0, 0, 0);
    	//set auto page breaks
    	$pdf->SetAutoPageBreak(TRUE, 0);
    
    	// Add a page
    	//$pdf->AddPage();
    	
    	// Etiquetes pàgina $rows x $cols  
    	$etiq_x_pag = $rows * $cols;
    	$padding = 10;
    	
    	$etiq_w = floor( $pdf->getPageWidth() / $cols );
    	$etiq_h = floor( $pdf->getPageHeight() / $rows );
    
    	// get current vertical position
    	$pdf->setXY(0,0, true);
    	$y_ini = $pdf->getY();
    	$x_ini = $pdf->getX();
    	$y = $y_ini-$etiq_h;
    	$x = $x_ini-$etiq_w;
    	
    	// set color for background
    	$pdf->SetFillColor(255, 255, 255); // Blanc
    	// set color for text
    	$pdf->SetTextColor(0, 0, 0); // Negre
    	// Font
    	$pdf->SetFont('helvetica', '', 11);
    
    	if ($rows >= 1 && $cols >= 1) {
	    	foreach ($persones as $i => $p) {
	    		if ($i % $etiq_x_pag == 0) {  // 10 x pàgina. Canvi de pàgina
	    			// Add a page
	    			$pdf->AddPage();
	    
	    			$y = $y_ini;
	    			$x = $x_ini;
	    		} else {
		    		if ($i % $cols == 0) {
		    			$x = $x_ini;
	    				$y += $etiq_h;
	    			}
		    		else $x += $etiq_w;
	    		}
	    		 
	    		//	writeHTMLCell ($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
	    		$pdf->writeHTMLCell($etiq_w, $etiq_h, $x, $y, /*$i.'('.$x.','.$y.')=>'.$etiq_w.'x'.$etiq_h*/ '',
	    				array('LTRB' => array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 1, 'color' => array(100, 100, 100))),
	    				0, false, true, 'C', true);
	    		$y_e = $y + $padding;
	    		$x_e = $x + $padding;
	    		
	    		//Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='', $stretch=0  [, $ignore_min_height=false, $calign='T', $valign='M'])
	    		if (strlen($p->getNomCognoms()) > 20) $pdf->SetFont('helvetica', '', 10);
	    		$pdf->setXY($x_e,$y_e, true);
	    		$pdf->Cell($etiq_w - (2*$padding), 0, ucfirst($p->getNomCognoms()), 0, 0, 'L', 0, '', 1); // 1 => horizontal scaling only if text is larger than cell width
	    		//$pdf->writeHTMLCell($etiq_w - (2*$padding), 0, $x_e, $y_e, $p->getNomCognoms(), '', 0, false, true, 'L', false);
	    		
	    		$pdf->SetFont('helvetica', '', 11);
	    		$y_e += 8;
	    		if (strlen($p->getAdreca()) > 20) $pdf->SetFont('helvetica', '', 10);
	    		$pdf->setXY($x_e,$y_e, true);
	    		$pdf->Cell($etiq_w - (2*$padding), 0, ucfirst($p->getAdreca()), 0, 0, 'L', 0, '', 1);
	    		//$pdf->writeHTMLCell($etiq_w - (2*$padding), 0, $x_e, $y_e, $p->getAdreca(), '', 0, false, true, 'L', false);
	    		
	    		$pdf->SetFont('helvetica', '', 11);
	    		$y_e += 5;
	    		if (strlen($p->getCp().' '.$p->getPoblacio()) > 20) $pdf->SetFont('helvetica', '', 10);
	    		$pdf->setXY($x_e,$y_e, true);
	    		$pdf->Cell($etiq_w - (2*$padding), 0, ucfirst($p->getCp().' '.$p->getPoblacio()), 0, 0, 'L', 0, '', 1);
	    		//$pdf->writeHTMLCell($etiq_w - (2*$padding), 0, $x_e, $y_e, $p->getCp().' '.$p->getPoblacio(), '', 0, false, true, 'L', false);
	    		 
	    		$pdf->SetFont('helvetica', '', 11);
	    		$y_e += 5;
	    		$pdf->setXY($x_e,$y_e, true);
	    		$pdf->Cell($etiq_w - (2*$padding), 0, strtoupper($p->getProvincia()), 0, 0, 'L', 0, '', 1);
	    		//$pdf->writeHTMLCell($etiq_w - (2*$padding), 0, $x_e, $y_e, $p->getProvincia(), '', 0, false, true, 'L', false);
	    	}
    	}
    
    	// reset pointer to the last page
    	$pdf->lastPage();
    	 
    	// Close and output PDF document
    	$response = new Response($pdf->Output("graella_etiquetes_adreces.pdf", "D"));
    	$response->headers->set('Content-Type', 'application/pdf');
    	return $response;
    	//return new Response("hola");
    }
}
