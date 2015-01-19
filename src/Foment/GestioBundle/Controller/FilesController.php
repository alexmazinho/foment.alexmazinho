<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Foment\GestioBundle\Classes\CSVWriter;
use Foment\GestioBundle\Entity\Soci;
use Foment\GestioBundle\Entity\Persona;
use Foment\GestioBundle\Entity\Seccio;
use Foment\GestioBundle\Form\FormSoci;
use Foment\GestioBundle\Form\FormPersona;
use Foment\GestioBundle\Form\FormSeccio;
use Foment\GestioBundle\Form\FormActivitat;
use Foment\GestioBundle\Entity\AuxMunicipi;
use Foment\GestioBundle\Classes\TcpdfBridge;


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

    	$response = $this->render('FomentGestioBundle:CSV:template.csv.twig', array('headercsv' => $header, 'data' => $persones));
    	 
    	$filename = "export_persones_".date("Y_m_d_His").".csv";
    	  
    	$response->headers->set('Content-Type', 'text/csv');
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
    
    public function exportseccionsAction(Request $request) {
    	 
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}

    	$queryparams = $this->queryTableSort($request, 's.id');
    	 
    	$query = $this->querySeccions($queryparams);
    	
    	$seccions = $query->getResult();
   
    	$seccionsCsv = array();
    	foreach ($seccions as $sec) {
    		$row = '';
    		$row .= '"'.$sec['0']->getId().'";"'.$sec['0']->getNom().'";"'.date('Y').'";"'.$sec['import'].'";"';
    		$row .= $sec['importjuvenil'].'";"'.$sec['membres'].'";'.PHP_EOL;
    		
    		$seccionsCsv[]['csvRow'] = $row;
    	}
    	
    	$header = UtilsController::getCSVHeader_Seccions();
    
    	$response = $this->render('FomentGestioBundle:CSV:template.csv.twig', array('headercsv' => $header, 'data' => $seccionsCsv));
    
    	$filename = "export_seccions_".date("Y_m_d_His").".csv";
    	 
    	$response->headers->set('Content-Type', 'text/csv');
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
    	
    	$id = $request->query->get('id', 0); // Per defecte seccio 1: Foment
    	 
    	$socis = array();
    	if ($id > 0) {
    		$em = $this->getDoctrine()->getManager();
    		
    		$seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($id);
    		
    		$membres = $seccio->getMembresSortedByCognom();
    		
    		foreach ($membres as $m) {
    			$socis[] = $m->getSoci();
    		}
    	}
    	
    	$header = UtilsController::getCSVHeader_Persones();
    	 
    	$response = $this->render('FomentGestioBundle:CSV:template.csv.twig', array('headercsv' => $header, 'data' => $socis ));
    	
    	$filename = "export_membres_seccio_".UtilsController::netejarNom($seccio->getNom())."_".date("Y_m_d_His").".csv";
    	
    	$response->headers->set('Content-Type', 'text/csv');
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
    
    /**********************************  Fitxers especials ************************************/
    
    public function esborrarfitxerAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
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
    	
    	throw new AccessDeniedException("No s'ha pogut esborrar el fitxer  ".$file);
    }
    
    public function descarregarfitxerAction(Request $request) {
    
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$file = $request->query->get('file', 0);
    
    	$fileAbs = __DIR__.UtilsController::PATH_TO_FILES.urldecode($file);
    	 
    	$fs = new Filesystem();
    	 
    	if ($fs->exists($fileAbs)) {
    
    		$response = $this->downloadFile($fileAbs, 'Comunicació de rebuts ');
    		
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
    	
    	$telefon = $request->query->get('telefon', 0);
    	$nom = $request->query->get('nom', 0);
    	$justificant = $request->query->get('justificant', 0);
    	
    	if ($telefon == 0 || strlen($telefon . '') != 9 ) throw new \Exception("El número de telèfon (9 dígits) es obligatori");
    	
    	if ($justificant == 0 || strlen($justificant . '') != 13 ) throw new \Exception("El justificant de la declaració (13 dígits) es obligatori");
    	
    	if ($nom == "") throw new \Exception("El nom de la persona de contacte és obligatori");
    	$nom = substr($nom, 0, 40);
    	$nom = mb_strtoupper(UtilsController::netejarNom($nom, false), 'ISO-8859-1');
    	$nom = strlen($nom)==40?$nom:str_pad($nom, 40, " ", STR_PAD_RIGHT);
    	
    	$datainici = \DateTime::createFromFormat('Y-m-d', $exercici.'-01-01');
    	$datafinal = \DateTime::createFromFormat('Y-m-d', $exercici.'-12-31');
    	
    	$donacions = $this->consultaDonacionsPeriode($datainici, $datafinal);
    	
    	if (count($donacions) == 0) throw new NotFoundHttpException("Encara no hi ha cap rebut pagat per aquest exercici ".$exercici);
    	 
    	$filename = date("Ymd_His") . "_model_182_donacions_exercici_".$exercici.".txt";
    	$ruta = __DIR__.UtilsController::PATH_TO_FILES.UtilsController::PATH_REL_TO_DECLARACIONS_FILES;
    	$fitxer = $ruta.'/'.$filename;
    	
    	//SOCI_COD	SOCI_NOMBR	SOCI_DNIS	SOCI_DIRS	SOCI_POBS	SOCI_CPS	SOCI_PROS	SOCI_IMPOR	SOCI_LIMPE	SOCI_LIMPC	SOCI_NOMB2
    	//32	JAUME GAY CODINA	36595924A	RIPOLLES, 31, bxs.	BARCELONA	08026	BARCELONA	256,00	DOSCIENTOS CINCUENTA Y SEIS	DUES-CENTS CINQUANTA-SIS	GAY CODINA JAUME
    	 
    	$fs = new Filesystem();
    	try {
    		if (!$fs->exists($ruta)) {
    			throw new NotFoundHttpException("No existeix el directori " .$ruta);
    		} else {
    			$contents = $this->generarFitxerDonacions($exercici, $telefon, $nom, $justificant,  $donacions);
    			
    			
    			$fs->dumpFile($fitxer, implode(PHP_EOL,$contents));
    			
    		}
    	} catch (IOException $e) {
    		throw new NotFoundHttpException("No es pot accedir al directori ".$ruta."  ". $e->getMessage());
    	}
   	
		$response = $this->downloadFile($fitxer, 'Declaració donacions. Model 182, exercici ' .$exercici);
    	
    	$response->prepare($request);
    	
    	return $response;
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

			$contents[$reg] = UtilsController::REGISTRE_PERCEPTOR.UtilsController::MODEL_DECLARACIO.$exercici.UtilsController::NIF_FOMENT;
			$contents[$reg] .= strlen($persona->getDni())==9?$persona->getDni():str_pad($persona->getDni(), 9, "0", STR_PAD_LEFT);
			
			$nom = $persona->getCognoms().' '.$persona->getNom();
			$nom = substr($nom, 0, 40);
			$nom = mb_strtoupper(UtilsController::netejarNom($nom, false), 'ISO-8859-1');
			
			$contents[$reg] .= str_repeat(" ",9);
			$contents[$reg] .= (strlen($nom)==40)?$nom:str_pad($nom, 40, " ", STR_PAD_RIGHT);
			$contents[$reg] .= UtilsController::getCodiProvincia($persona->getProvincia()).UtilsController::CLAU_DONATIU.str_repeat(" ",5);
			
			$contents[$reg] .= strlen($import.'')==13?($import.''):str_pad(($import.''), 13, "0", STR_PAD_LEFT).UtilsController::DONATIU_EN_ESPECIES;
			$contents[$reg] .= UtilsController::getCodiComunitat($persona->getProvincia())."00000".UtilsController::NATURA_DECLARAT.str_repeat(" ",5).str_repeat(" ",1+4+1+20+118);
			
			$total++;
			$sumaImport += $import; 
		}
		
		// Substituir totals
		$total = strlen(($total.''))==9?($total.''):str_pad(($total.''), 9, "0", STR_PAD_LEFT);
		$sumaImport = strlen(($sumaImport.''))==15?($sumaImport.''):str_pad(($sumaImport.''), 15, "0", STR_PAD_LEFT);
		
		$contents['registre-declarant'] = str_replace("TOTALTEMP", $total, $contents['registre-declarant']);
		$contents['registre-declarant'] = str_replace("IMPORTTEMPTOTAL", $sumaImport, $contents['registre-declarant']);
    	
    	
    	return $contents; 
    }
    
    public function domiciliacionsAction(Request $request) {
    	 
    	if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
    		throw new AccessDeniedException();
    	}
    	 
    	$id = $request->query->get('facturacio', 0);
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$facturacio = $em->getRepository('FomentGestioBundle:Facturacio')->find($id);
    	
    	if ($facturacio == null) throw new NotFoundHttpException("No s'ha trobat la facturació ".$id); 
    	
    	//$current = new \DateTime('now');
    	$filename = date("Ymd_His") . "_rebuts_".UtilsController::netejarNom($facturacio->getDescripcio()).".txt";
    	$ruta = __DIR__.UtilsController::PATH_TO_FILES.UtilsController::PATH_REL_TO_DOMICILIACIONS_FILES;
    	$fitxer = $ruta.'/'.$filename;

    	$fs = new Filesystem();
    	try {
    		if (!$fs->exists($ruta)) {
    			throw new NotFoundHttpException("No existeix el directori " .$ruta);
    		} else {
    			$resultat = $facturacio->generarFitxerDomiciliacions();
    			$facturacio->setDatamodificacio(new \DateTime('now'));
    			
    			$contents = $resultat['contents'];
    			$errors = $resultat['errors'];
    			$fs->dumpFile($fitxer, implode(PHP_EOL,$contents));
    			
    			$em->flush(); // Guardar canvis, rebuts trets de la facturació si escau
    			
    			if (count($errors) > 0) {
    				// Facturació amb errors. Cal revisar els rebuts que no s'han enviat
    				// S'han tret de la facturació: falta el compte ....
    				throw new AccessDeniedHttpException("Facturació generada amb errors ".PHP_EOL."  ". implode(PHP_EOL,$errors));
    			}
    		}
    	} catch (IOException $e) {
    		throw new NotFoundHttpException("No es pot accedir al directori ".$ruta."  ". $e->getMessage());
    	}
    	
    	
    	/*$queryparams = $this->queryPersones($request);
    	  
    	$header = UtilsController::getCSVHeader_Persones();
    	$persones = $queryparams['query']->getResult();
    	$response = $this->render('FomentGestioBundle:CSV:template.csv.twig', array('headercsv' => $header, 'data' => $persones));*/
    	//$response = new Response($contents);

    	$response = $this->downloadFile($fitxer, 'Comunicació de rebuts ' .$facturacio->getDescripcio());
    	 
    	$response->prepare($request);
    	 
    	return $response;
    }
    
    private function downloadFile($fitxer, $desc) {
    	$response = new BinaryFileResponse($fitxer);
    	 
    	$response->setCharset('UTF-8');
    	 
    	$response->headers->set('Content-Type', 'text/plain');
    	$response->headers->set('Content-Disposition', 'attachment; filename="'.$fitxer.'"');
    	$response->headers->set('Content-Description', $desc);
    	
    	$response->headers->set('Content-Transfer-Encoding', 'binary');
    	$response->headers->set('Pragma', 'no-cache');
    	$response->headers->set('Expires', '0');
    	
    	return $response;
    }
    
    
    /**********************************  PDF's ************************************/
    
    
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
    		
    		$donacions = 1231.15;
    		
    		$f = new \NumberFormatter("ca_ES.utf8", \NumberFormatter::SPELLOUT);
    		$donacionsFloor = floor($donacions);
    		$donacionsDec = floor(($donacions - $donacionsFloor)*100);
    		$donacionsTxt = $f->format($donacionsFloor);// . ($donacionsDec < 0.001)?'':' amb '. $f->format($donacionsDec*100);
    		$donacionsTxt .= ($donacionsDec == 0)?'':' amb '. $f->format($donacionsDec);
    		
    		// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    		// $orientation, (string) $unit, (mixed) $format, (boolean) $unicode, (string) $encoding, (boolean) $diskcache, (boolean) $pdfa
    		$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', true);
    		
    		
    		//$pdf->init(array('header' => false, 'footer' => false, 'logo' => 'logo-foment-martinenc.jpg','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    		$pdf->init(array('header' => true, 'footer' => true, 
    					'logo' => 'logo-fm1877-web.png','author' => 'Foment Martinenc', 
    					'title' => '',
    					'string' => 'Certificat'));
    		
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
    		$pdf->MultiCell(0, 0, $text, 0, 'J', 0, 1, PDF_MARGIN_LEFT_NARROW, PDF_MARGIN_TOP, true);
    		
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
    	 
    	if ($id > 0) {
    		$em = $this->getDoctrine()->getManager();
    
    		$rebut = $em->getRepository('FomentGestioBundle:Rebut')->find($id);
    
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
    
    public function imprimirrebuts($rebuts) {
    	// Configuració 	/vendor/tcpdf/config/tcpdf_config.php
    	$pdf = new TcpdfBridge('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    	 
    	//$pdf->init(array('header' => false, 'footer' => false, 'logo' => 'logo-foment-martinenc.jpg','author' => 'Foment Martinenc', 'title' => 'Graella Carnets Socis/es - ' . date("Y")));
    	$pdf->init(array('header' => false, 'footer' => false, 'logo' => '','author' => 'Foment Martinenc', 'title' => 'Rebuts Socis/es - ' . date("Y")));
    	 
    	// Add a page
    	$pdf->AddPage();
    	 
    	//set margins
    	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP - 10, PDF_MARGIN_RIGHT);
    	//set auto page breaks
    	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM - 10);

    	$y_ini = $pdf->getY();
    	$x_ini = $pdf->getX();
    
    	$y = $y_ini;
    	$x = $x_ini;
    
    	// set color for background
    	$pdf->SetFillColor(255, 255, 255); // Blanc 
    	// set color for text
    	$pdf->SetTextColor(0, 0, 0); // Negre
    	 
    	
    	$styleSeparator = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 6, 'color' => array(200, 200, 200));
    	
    	$pdf->Line(5, $pdf->getY() - 5, $pdf->getPageWidth()-5, $pdf->getY() - 5, $styleSeparator);
    	
    	foreach ($rebuts as $rebut) {
    		
    		$x_titol = $x;
    		$y_titol = $y;
    		$w_titol_foto = 20;
    		$w_titol_text = 84;
    		
    		// Total 630
    		$w_header_1 = 390; // Pixels
    		$w_header_2 = 100; // Pixels
    		$w_header_3 = 140; // Pixels
    		// Total 630 - 20
    		$w_concepte_1 = 213; // Pixels
    		$w_concepte_2 = 300; // Pixels
    		$w_concepte_3 = 103; // Pixels
    		
    		// Image ($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='',
    		// $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array())
    		$pdf->Image('imatges/logo-foment-martinenc.png', $x_titol + 2, $y_titol + 3, $w_titol_foto, 0, 'png', '', 'M', true, 150, '',
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
    		
    		// Capçalera
    		$pdf->SetFont('helvetica', '', 12);
    		$pdf->setX($x);
    		$pdf->setY($y);
    		
    		$html = '<table class="main-table" border="0" cellpadding="7" cellspacing="0" nobr="true">';
    		$html .= '<tr><td rowspan="3" width="'.$w_header_1.'" align="center" style="border: 0.5px solid #999998; color:#05b5a8;"></td>';
    		//$html .= '<p><b>FOMENT MARTINENC</b><br/>ATENEU CULTURAL i RECREATIU<br/>DECLARAT D\'UTILITAT PÚBLICA. FUNDAT L\'ANY 1877</p>';
    		//$html .= '<p>Provença, 591 - 08026 BARCELONA<br/>Tels. 93 455 70 95 - 93 435 73 76</p></td>';
    		$html .= '<td align="left" width="'.$w_header_2.'" style="border: 0.5px solid #999998; color:#555555;">NIF</td><td width="'.$w_header_3.'" align="center" style="border: 0.5px solid #999998; color:#333333;"><b>G-08917635</b></td></tr>';
    		$html .= '<tr><td align="left" style="border: 0.5px solid #999998; color:#555555;">Nº rebut</td><td align="center" style="border: 0.5px solid #999998; color:#333333;"><b>'.$rebut->getNumFormat().'</b></td></tr>';
    		$html .= '<tr><td align="left" style="border: 0.5px solid #999998; color:#555555;">Data</td><td align="center" style="border: 0.5px solid #999998; color:#333333;"><b>'.$rebut->getDataemissio()->format('d/m/Y').'</b></td></tr>';
    		
    		// Subtaula conceptes
    		$subTable =  '<table border="0" cellpadding="2" cellspacing="0" nobr="true"><tbody>';
    		    		
    		foreach ($rebut->getDetalls() as $detall) {
    			if ($detall->getDatabaixa() == null) {
    				$subTable .= '<tr><td width="'.$w_concepte_1.'" align="left" style="color:#045B7C;"><span style="font-size: 9px;">'.$detall->getPersona()->getNomCognoms().'</span></td>';
    				$subTable .= '<td width="'.$w_concepte_2.'" align="left" style="color:#045B7C;"><span style="font-size: 8px;">'.$detall->getConcepte().'</span></td>';
    				$subTable .= '<td width="'.$w_concepte_3.'" align="right" style="color:#045B7C;"><span style="font-size: 9px;">'.number_format($detall->getImport(), 2, ',', '.').' €</span></td></tr>';
    			}	
    		}
    		$subTable .= '<tr><td colspan="2" align="right" style="color:#045B7C; border-top: 0.5px solid #045B7C;"><span style="font-size: xx-small;"><i>total</i></span></td>';
    		$subTable .= '<td align="right" style="color:#045B7C;border-top: 0.5px solid #045B7C;"><span style="font-size: xx-small;"><b>'.number_format($rebut->getImport(), 2, ',', '.').' €</b></span></td></tr>';
    		$subTable .= '</tbody></table>';
    		
    		$html .= '<tr style="background-color:#FEFEFE;"><td colspan="3" align="center" style="border-top: 0.5px solid #999998;border-right: 0.5px solid #999998;border-left: 0.5px solid #999998;">'.$subTable.'</td></tr>';
    		
    		// Subtaula peu deutor
    		$subTable =  '<table border="0" cellpadding="5" cellspacing="0" nobr="true"><tbody>';
    		$subTable .=  '<tr><td width="'.$w_header_1.'" align="left" style="color:#333333;"><span style="font-size: small;">';
    		$subTable .=  '<b>'.$rebut->getDeutor()->getNomCognoms().'</b><br/>';
    		$subTable .=  $rebut->getDeutor()->getAdrecaCompleta().'</span></td><td width="'.$w_header_2.'"></td>';
    		
    		// Subtaula import
    		$subTableImp =  '<table border="0" cellpadding="10" cellspacing="0" nobr="true"><tbody>';
    		$subTableImp .= '<tr style="background-color:#1991c0;color:white;"><td><span style="font-size: x-small;"><u>Import Rebut</u></span><br/>';
    		$subTableImp .= '<b>'.number_format($rebut->getImport(), 2, ',', '.').' €</b></td></tr>';
    		$subTableImp .= '</tbody></table>';
    		
    		// Subtaula peu total
    		$subTable .= '<td width="'.($w_header_3 - 14).'" align="center" style="color:#333333;">'.$subTableImp.'</td></tr>';
    		$subTable .= '</tbody></table>';
    		
    		$html .= '<tr><td colspan="3" align="center" style="border-bottom: 0.5px solid #999998;border-right: 0.5px solid #999998;border-left: 0.5px solid #999998;">'.$subTable.'</td></tr>';
    		
    		
    		$html .= '</tbody></table>';
    		//	writeHTMLCell ($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true)
    		$pdf->writeHTMLCell(0, 0, $x, $y, $html, 0, 2, false, true, 'C', true);
    		//writeHTML ($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
    		//$pdf->writeHTML($html, true, false, false, false, '');
    		
    		$pdf->Line(5, $pdf->getY() + 5, $pdf->getPageWidth() - 5, $pdf->getY() + 5, $styleSeparator);
    		
    		if (!$rebut->cobrat() || $rebut->getDatabaixa() != null ) {
    			$x_offset = 0;
    			if (!$rebut->cobrat()) $strAigua = 'Pendent de pagament';
    			if ($rebut->getDatabaixa() != null) {
    				$strAigua = 'Rebut anul·lat';
    				$pdf->setFontStretching(150);
    				$x_offset = 20;
    			}
    			
	    		$pdf->SetFont('helvetica', '', 36);
	    		$pdf->SetDrawColor(200);
	    		$pdf->SetTextColor(236, 27, 35); // #f57031 orange
	    		
	    		$pdf->SetAlpha(0.3);
	    		// Start Transformation
	    		$pdf->StartTransform();
	    		// Rotate -10 degrees 
	    		$pdf->Rotate(345, $pdf->getPageWidth()/2 , $pdf->getY());
	    		$pdf->Text($x + $x_offset, $y + (($pdf->getY()-$y)/2) -10 , $strAigua); // $y + (($pdf->getY()-$y)/2)
	    		// Stop Transformation
	    		$pdf->StopTransform();
	    		$pdf->SetAlpha(1);
	    		$pdf->setFontStretching(100);
    		}
    		
    		
    	}
    	 
    	// reset pointer to the last page
    	$pdf->lastPage();
    
    	return $pdf;
    	//return new Response("hola");
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
    	$pdf->Image('imatges/logo-foment-martinenc.png', $x + 5, $y, 0, ($foto_h*0.8), 'png', '', 'M', true, 150, '', 
    			false, false, 'LTRB', false, false, false);

    	$pdf->Image('imatges/icon-photo.blue.png', $x, $y + ($foto_h*0.8), $foto_w, ($foto_h*1.2), 'png', '', 'B', true, 150, '',
    			false, false, 'LTRB', false, false, false);
    	
    	$pdf->writeHTMLCell($foto_w, 0, $x, $y, '', '', 0, false, true, 'C', true);
    	
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
    	$pdf->SetFont('times', '', 10.5);
    	$htmlDadesSoci = "<i>".$soci->getNom()." ".$soci->getCognoms()."</i>";
    	$pdf->writeHTMLCell($info_w, 0, $x+13, $y, $htmlDadesSoci, '', 0, false, true, 'L', true);
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
