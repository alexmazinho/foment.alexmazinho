<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Doctrine\ORM\Query\ResultSetMappingBuilder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Foment\GestioBundle\Entity\Rebut;
use Foment\GestioBundle\Entity\RebutDetall;
/*use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;




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
*/

class BaseController extends Controller
{
	protected function consultaDonacionsPeriode($datainici, $datafinal, $persona = null) {
		$em = $this->getDoctrine()->getManager();
	
		// Rebuts pagats d'un any, ordenats per soci codi
		$strQuery = " SELECT p as persona, SUM(d.import) as importdonacio FROM Foment\GestioBundle\Entity\Persona p JOIN p.rebuts r JOIN r.detalls d ";
		$strQuery .= " WHERE r.databaixa IS NULL ";
		$strQuery .= " AND d.databaixa IS NULL ";
		$strQuery .= " AND r.datapagament IS NOT NULL ";
		$strQuery .= " AND r.datapagament >= :datainici AND r.datapagament <= :datafinal ";
	
		if ($persona != null) $strQuery .= " AND p.id = :personaid ";
	
		$strQuery .= " GROUP BY p.id ";
		$strQuery .= " ORDER BY p.id ";
		
		$query = $em->createQuery($strQuery);
	
		$query->setParameter('datainici', $datainici->format('Y-m-d'));
		$query->setParameter('datafinal', $datafinal->format('Y-m-d'));
		if ($persona != null) $query->setParameter('personaid', $persona->getId());
	
		$donacions = $query->getResult();
		
		if ($persona != null) {
			if ($donacions == null || !isset($donacions[0]) || !isset($donacions[0]['importdonacio']) ) return 0;
			else return $donacions[0]['importdonacio'];
		} 
	
		return $donacions;
	}
		
    protected function queryPersones(Request $request, $selectFieldsReturnArray = '') {
    	// Opcions de filtre del formulari
    	$sort = $request->query->get('sort', 's.cognoms'); // default 's.cognoms'
    	$direction = $request->query->get('direction', 'asc');
    	
    	$nini = $request->query->get('nini', 0);
    	$nfi = $request->query->get('nfi', 0);
    	 
    	$nom = $request->query->get('nom', '');
    	$cognoms = $request->query->get('cognoms', '');
    	$dni = $request->query->get('dni', '');
    	$h = true;
    	if ($request->query->has('h') && $request->query->get('h') == 0) $h = false;
    	$d = true;
    	if ($request->query->has('d') && $request->query->get('d') == 0) $d = false;
    	$s = true;
    	if ($request->query->has('s') && $request->query->get('s') == 0) $s = false;
    	$p = false;
    	if ($request->query->has('p') && $request->query->get('p') == 1) $p = true;
    	$b = false;
    	if ($request->query->has('b') && $request->query->get('b') == 1) $b = true;
    	$simail = false;
    	if ($request->query->has('simail') && $request->query->get('simail') == 1) $simail = true;
    	$nomail = false;
    	if ($request->query->has('nomail') && $request->query->get('nomail') == 1) $nomail = true;
    	$exempt = false;
    	if ($request->query->has('exempt') && $request->query->get('exempt') == 1) $exempt = true;
    	
    	$mail = $request->query->get('mail', '');
    	
    	$dini = $request->query->get('dini', '');
    	$dfi = $request->query->get('dfi', '');
    	 
    	$queryparams = array('sort' => $sort,'direction' => $direction,
    			'nom' => $nom, 'cognoms' => $cognoms, 'dni' => $dni,
    			'simail' => $simail, 'nomail' => $nomail, 'mail' => $mail, 'exempt' => $exempt,
    			'h' => $h, 'd' => $d, 's' =>  $s, 'p'  =>  $p, 'b'  =>  $b
    	);

    	if ($nini > 0)  $queryparams['nini'] = $nini;
    	if ($nfi > 0)  $queryparams['nfi'] = $nfi;
    	if ($dini != '')  $queryparams['dini'] = $dini;
    	if ($dfi != '')  $queryparams['dfi'] = $dfi;
    	 
    	$seccionsIds = $request->query->get('seccions', array());
    	$seccions = array();
    	foreach ($seccionsIds as $i => $id) {  // Retrive selected seccions
    		$seccions[] = $this->getDoctrine()->getRepository('FomentGestioBundle:Seccio')->find($id);
    		$queryparams['seccions['.$i.']'] = $id;
    	}
    	$queryparams['seccions'] = $seccions;
    	 
    	$activitatsIds = $request->query->get('activitats', array());
    	foreach ($activitatsIds as $i => $id) {  // Retrive selected seccions
    		$queryparams['activitats['.$i.']'] = $id;
    	}
    	$queryparams['activitats'] = $activitatsIds;
    	
    	/* Query */
    	$em = $this->getDoctrine()->getManager();
    	
    	$strJoinMembres = "";
    	$strJoinParticipacions = "";
    	if (count($seccions) > 0) $strJoinMembres = " JOIN s.membrede m ";
    	if (count($activitatsIds) > 0) $strJoinParticipacions = " JOIN s.participacions p ";
    	
    	
    	$strSelect = 's';
    	if ($selectFieldsReturnArray != '') $strSelect = $selectFieldsReturnArray;
    	
    	if ($s == true)  { // Només socis
	    	$prefix = "SELECT ".$strSelect." FROM Foment\GestioBundle\Entity\Soci s ";
	    	$strQuery = $prefix . $strJoinMembres. $strJoinParticipacions.  
	    		" WHERE s INSTANCE OF Foment\GestioBundle\Entity\Soci AND s.databaixa IS NULL ";
    	} else {
    		if ($b == true) {  // Persones i socis de baixa
    			$prefix = "SELECT ".$strSelect." FROM Foment\GestioBundle\Entity\Persona s ";
    			$strQuery = $prefix.$strJoinMembres. $strJoinParticipacions. 
    				"	WHERE (NOT EXISTS (SELECT o1.id FROM Foment\GestioBundle\Entity\Soci o1 WHERE o1.id = s.id AND o1.databaixa IS NULL))  ";
    		} else {  // Persones sense socis de baixa 
    			$prefix = "SELECT ".$strSelect." FROM Foment\GestioBundle\Entity\Persona s ";
    			$strQuery = $prefix.$strJoinMembres. $strJoinParticipacions. 
    				"	WHERE (NOT EXISTS (SELECT o1.id FROM Foment\GestioBundle\Entity\Soci o1 WHERE o1.id = s.id))  ";
    		}
    		
    	}
	    	
	    	
    	/*if ($b == true) {
    		$prefix = "SELECT s FROM Foment\GestioBundle\Entity\Persona s ";
    		$strQuery = $prefix.$strJoinMembres. $strJoinParticipacions. "	WHERE (NOT EXISTS (SELECT o1.id FROM Foment\GestioBundle\Entity\Soci o1 WHERE o1.id = s.id) OR
    								EXISTS (SELECT o.id FROM Foment\GestioBundle\Entity\Soci o WHERE o.id = s.id AND o.databaixa IS NULL))  ";
    	} else {
	    	if ($s == false)  {
	    		$prefix = "SELECT s FROM Foment\GestioBundle\Entity\Persona s ";
	    		$strQuery = $prefix . $strJoinMembres. $strJoinParticipacions.  " WHERE s INSTANCE OF Foment\GestioBundle\Entity\Soci AND s.databaixa IS NULL ";
	    		
	    	}
    	}*/
    	    	
    	$qParams = array();
    	    	
    	if ($s == true) { // Seccions només socis 
	    	if (count($seccions) > 0) { // Seccions filtrades
	    		$strQuery .= " AND m.seccio IN (:seccions) ";
	    		$qParams['seccions'] = $seccions;
	    	}
	    	
	    	// Número només socis
	    	if ($nini > 0 && $nfi > 0) {
	    		$strQuery .= " AND s.num BETWEEN :nini AND :nfi ";
	    		$qParams['nini'] = $nini;
	    		$qParams['nfi'] = $nfi;
	    	} else {
	    		// Només un
	    		if ($nini > 0) {
	    			$strQuery .= " AND s.num >= :nini ";
	    			$qParams['nini'] = $nini;
	    		}
	    		if ($nfi > 0)  {
	    			$strQuery .= " AND s.num <= :nfi ";
	    			$qParams['nfi'] = $nfi;
	    		}
	    	}
    	}
    	
    	if (count($activitatsIds) > 0) {
    		$strQuery .= " AND p.activitat IN (:activitats) ";
    		$qParams['activitats'] = $activitatsIds;
    	}
    	
    	if ($nom != "") {
    		$strQuery .= " AND s.nom LIKE :nom ";
    		$qParams['nom'] = "%".$nom."%";
    	}
    	if ($cognoms != "") {
    		$strQuery .= " AND s.cognoms LIKE :cognoms ";
    		$qParams['cognoms'] = "%".$cognoms."%";
    	}
    	if ($dni != "") {
    		$strQuery .= " AND s.dni LIKE :dni ";
    		$qParams['dni'] = "%".$dni."%";
    	}
    	
        if ($simail == false) {
        	if ($nomail == true) $strQuery .= " AND s.correu IS NULL ";
        } else {
        	// $simail == true
        	if ($mail != "") {
        		$strQuery .= " AND s.correu LIKE :mail ";
        		$qParams['mail'] = "%".$mail."%";
			}        	
			if ($nomail == false) $strQuery .= " AND s.correu IS NOT NULL ";
        }
    	
    	    	
    	if ($h == false && $d == true) $strQuery .= " AND s.sexe = 'D' ";
    	if ($h == true && $d == false) $strQuery .= " AND s.sexe = 'H' ";
    	
    	
    	if ($dini != '' || $dini != '') {
    		// Alguna data indicada
    		if ($dini != '') {
    			$diniISO = \DateTime::createFromFormat('d/m/Y', $dini);
    			$strQuery .= " AND s.datanaixement >= :dini ";
    			$qParams['dini'] = $diniISO->format('Y-m-d');
    		}
    		 
    		if ($dfi != '') {
    			$dfiISO = \DateTime::createFromFormat('d/m/Y', $dfi);
    			 
    			$strQuery .= " AND s.datanaixement <= :dfi ";
    			$qParams['dfi'] = $dfiISO->format('Y-m-d');
    		}
    	}
    	
    	if ($s == true) {  // Només socis
    		
    		if ($p == true) $strQuery .= " AND s.vistiplau = FALSE ";
    	}
    	
    	$strQuery .= " ORDER BY " . $sort . " " . $direction;
    	
    	$query = $em->createQuery($strQuery);
    	
    	foreach ($qParams as $k => $p) {  // Add query parameters
    		$query->setParameter($k, $p);
    	}
    	
    	$queryparams['query'] = $query;
    	
    	return $queryparams;
    }
    
    protected function queryRebuts(Request $request) {
    	// Opcions de filtre del formulari
    	$sort = $request->query->get('sort', 'r.id'); // default 'r.id desc'
    	$direction = $request->query->get('direction', 'desc');
    
    	$nini = $request->query->get('nini', 0);
    	$nfi = $request->query->get('nfi', 0);
    
    	$persona = $request->query->get('persona', '');
    
    	$dini = $request->query->get('dini', '');
    	$dfi = $request->query->get('dfi', '');
    
    	$cobrats = $request->query->get('cobrats', 0);
    	$tipus = $request->query->get('tipus', 0);
    
    	$facturacio = $request->query->get('facturacio', 0);
    	$periode = $request->query->get('periode', 0);
    	$page = $request->query->get('page', 1);
    
    
    	$anulats = false;
    	if ($request->query->has('anulats') && $request->query->get('anulats') == 1) $anulats = true;
    	$retornats = false;
    	if ($request->query->has('retornats') && $request->query->get('retornats') == 1) $retornats = true;
    
    	$queryparams = array('sort' => $sort,'direction' => $direction, 'page' => $page,
    			'anulats' => $anulats, 'retornats' =>  $retornats, 'cobrats' => $cobrats, 'tipus' => $tipus,
    			'persona' => $persona, 'facturacio' => $facturacio, 'periode' => $periode
    	);
    
    	if ($nini > 0)  $queryparams['nini'] = $nini;
    	if ($nfi > 0)  $queryparams['nfi'] = $nfi;
    	if ($dini != '')  $queryparams['dini'] = $dini;
    	if ($dfi != '')  $queryparams['dfi'] = $dfi;
    
    
    	$seccionsIds = $request->query->get('seccions', array());
    	$seccions = array();
    	foreach ($seccionsIds as $i => $id) {  // Retrive selected seccions
    		$seccions[] = $this->getDoctrine()->getRepository('FomentGestioBundle:Seccio')->find($id);
    		$queryparams['seccions['.$i.']'] = $id;
    	}
    	$queryparams['seccions'] = $seccions;
    
    	$activitatsIds = $request->query->get('activitats', array());
    	foreach ($activitatsIds as $i => $id) {  // Retrive selected seccions
    		$queryparams['activitats['.$i.']'] = $id;
    	}
    	$queryparams['activitats'] = $activitatsIds;
    
    
    
    	/* Query */
    	$qParams = array();
    
    	$em = $this->getDoctrine()->getManager();
    		
    	$strQuery = " SELECT r FROM Foment\GestioBundle\Entity\Rebut r ";
    
    	if ($persona != '') {
    		$strQuery .= "  JOIN r.detalls d LEFT JOIN d.quotaseccio m LEFT JOIN d.activitat a ";
    		$strQuery .= " WHERE (m.soci = :persona OR a.persona = :persona) ";
    			
    		$qParams['persona'] = $persona;
    		if (count($seccions) > 0) { // Seccions filtrades
    			$strQuery .= " AND d.quotaseccio IN (:seccions) ";
    			$qParams['seccions'] = $seccionsIds;
    		}
    		if (count($activitatsIds) > 0) {
    			$strQuery .= " AND d.activitat IN (:activitats) ";
    			$qParams['activitats'] = $activitatsIds;
    		}
    			
    	} else {
    		if (count($seccions) > 0 || count($activitatsIds) > 0) { // Seccions o activitats filtrades, cal fer JOIN
    			$strQuery .= "  JOIN r.detalls d ";
    
    			if (count($seccions) > 0) $strQuery .= " LEFT JOIN d.quotaseccio m ";
    				
    			if (count($activitatsIds) > 0) $strQuery .= " LEFT JOIN d.activitat a ";
    		}
    		$strQuery .= "  WHERE 1 = 1 ";
    			
    		if (count($seccions) > 0) {
    			$strQuery .= " AND m.seccio IN (:seccions) ";
    			$qParams['seccions'] = $seccionsIds;
    		}
    		if (count($activitatsIds) > 0) {
    			$strQuery .= " AND a.activitat IN (:activitats) ";
    			$qParams['activitats'] = $activitatsIds;
    		}
    			
    	}
    
    	if ($nini > 0 && $nfi > 0) {
    		$strQuery .= " AND r.num BETWEEN :nini AND :nfi ";
    		$qParams['nini'] = $nini;
    		$qParams['nfi'] = $nfi;
    	} else {
    		// Només un
	    	if ($nini > 0) {
	    		$strQuery .= " AND r.num >= :nini ";
	    		$qParams['nini'] = $nini;
	    	}
    		if ($nfi > 0)  {
    			$strQuery .= " AND r.num <= :nfi ";
    			$qParams['nfi'] = $nfi;
    		}
	   	}
    
    	if ($cobrats > 0) {
    		if ($cobrats == 1) $strQuery .= " AND r.datapagament IS NOT NULL ";
    		if ($cobrats == 2) $strQuery .= " AND r.datapagament IS NULL ";
    	}
    
    	if ($tipus > 0) {
    		$strQuery .= " AND r.tipuspagament = :tipus ";
    		$qParams['tipus'] = $tipus;
    	}
    
    	if ($anulats) $strQuery .= " AND r.databaixa IS NOT NULL ";
    	if ($retornats) $strQuery .= " AND r.dataretornat IS NOT NULL ";
    
    	if ($dini != '' || $dfi != '') {
    		// Alguna data indicada
    		if ($dini != '') {
    			$diniISO = \DateTime::createFromFormat('d/m/Y', $dini);
    			$strQuery .= " AND r.dataemissio >= :dini ";
    			$qParams['dini'] = $diniISO->format('Y-m-d');
    		}
    
    		if ($dfi != '') {
    			$dfiISO = \DateTime::createFromFormat('d/m/Y', $dfi);
    
    			$strQuery .= " AND r.dataemissio <= :dfi ";
    			$qParams['dfi'] = $dfiISO->format('Y-m-d');
    		}
    	}
    
    	if ($facturacio > 0) {
    		$strQuery .= " AND r.facturacio = :facturacio ";
    		$qParams['facturacio'] = $facturacio;
    	}
    	
    	if ($periode > 0) {
    		$strQuery .= " AND r.periodenf = :periode ";
    		$qParams['periode'] = $periode;
    	}
    
    	$strQuery .= " ORDER BY " . $sort . " " . $direction;
    		
    	$query = $em->createQuery($strQuery);
    		
    	foreach ($qParams as $k => $p) {  // Add query parameters
    		$query->setParameter($k, $p);
    	}
    		
    	$queryparams['query'] = $query;
    
    	return $queryparams;
    }
    
    protected function queryTableSort(Request $request, $defaults = array( 'id' => 'a.id', 'direction' => 'asc')) {
    	if ($request->getMethod() == 'POST') $params = $request->request;
    	else $params = $request->query;
    	 
    	$perpage = UtilsController::DEFAULT_PERPAGE;
    	if (isset($defaults['perpage'])) $perpage = $defaults['perpage'];
    	
    	$queryparams = array(
    		'page' => $params->get('page', 1),
    		'sort' => $params->get('sort', $defaults['id']), // default 'a.id'
    		'direction' => $params->get('direction', $defaults['direction']),
    		'perpage' => $params->get('perpage', $perpage),
    		'filtre' => $params->get('filtre', '')
    	);
    	 
    	return $queryparams;
    }
    
    
    protected function queryQuotes($anyquota) {
    	$em = $this->getDoctrine()->getManager();
    	
    	 
    	// Important!! Han d'estar donades d'alta totes les quotes per cada seccio / any
    	$strQuery = 'SELECT q FROM Foment\GestioBundle\Entity\Quota q ';
    	$strQuery .= 'WHERE q.anyquota = :anyquota ORDER BY q.seccio';
    	
    	$query = $em->createQuery($strQuery);
    	$query->setParameter('anyquota', $anyquota);
    	
    	$quotesArray = $query->getResult();
    	
    	/* Fer array d'index per secció */
    	$quotes = array( 'anyquotes' => $anyquota);
    	foreach ($quotesArray as $q) {
    		$quotes[ $q->getSeccio()->getId() ] = array('import' => $q->getImport(), 'importjuvenil' => $q->getImportjuvenil());
    	}
    	return $quotes;
    }
    
    /*
     * Obté totals rebuts vàlids per secció (sense tenir en compte rebuts donats de baixa o retornats)
    */
    protected function queryGetRebutsPerSeccioAny($arraySeccions, $anyconsulta) {
    	$em = $this->getDoctrine()->getManager();
    
    	$inici = $anyconsulta.'-01-01';
    	$final = $anyconsulta.'-12-31';

    	/*
Quotes + rebuts
SELECT s.id, s.nom, s.databaixa, q.import, q.importjuvenil, COUNT( DISTINCT m.soci), SUM( d.import), COUNT( DISTINCT d.rebut) 
FROM seccions s LEFT JOIN quotes q ON s.id = q.seccio LEFT JOIN membres m ON s.id = m.seccio
LEFT JOIN socis o ON m.soci = o.id LEFT JOIN rebutsdetall d ON d.quotaseccio = m.id
WHERE (q.anyquota = 2015 OR  q.anyquota IS NULL)
AND d.databaixa IS NULL
AND (m.datainscripcio IS NULL OR m.datainscripcio <= '2015-12-31')
AND (m.datacancelacio IS NULL OR m.datacancelacio >= '2015-01-01')
GROUP BY s.id, s.nom, s.databaixa, q.import, q.importjuvenil


Per parts, rebuts
SELECT s.id, s.nom, s.databaixa, COUNT( DISTINCT m.soci), SUM( d.import), COUNT( DISTINCT d.rebut) 
FROM seccions s LEFT JOIN membres m ON s.id = m.seccio
LEFT JOIN socis o ON m.soci = o.id LEFT JOIN rebutsdetall d ON d.quotaseccio = m.id
WHERE d.databaixa IS NULL
AND (m.datainscripcio IS NULL OR m.datainscripcio <= '2015-12-31')
AND (m.datacancelacio IS NULL OR m.datacancelacio >= '2015-01-01')
GROUP BY s.id, s.nom, s.databaixa

Per parts, rebuts sense taula Seccions, només id
SELECT m.seccio, COUNT( DISTINCT m.soci), SUM( d.import), COUNT( DISTINCT d.rebut) 
FROM membres m LEFT JOIN socis o ON m.soci = o.id LEFT JOIN rebutsdetall d ON d.quotaseccio = m.id
LEFT JOIN rebuts r ON d.rebut = r.id
WHERE d.databaixa IS NULL AND r.databaixa IS NULL
AND r.dataemissio >= '2015-01-01'  AND r.dataemissio  <= '2015-12-31'
AND (m.datainscripcio IS NULL OR m.datainscripcio <= '2015-12-31')
AND (m.datacancelacio IS NULL OR m.datacancelacio >= '2015-01-01')
GROUP BY m.seccio

Comptadors exempt, familiar, juvenil
SELECT s.id, s.nom, s.databaixa, COUNT( m.soci), SUM(o.exempt), SUM( o.descomptefamilia ), 
SUM( IF ( YEAR (p.datanaixement) + 18 >= YEAR(CURDATE()),1,0) ) 
FROM seccions s LEFT JOIN membres m ON s.id = m.seccio 
LEFT JOIN socis o ON m.soci = o.id LEFT JOIN persones p ON p.id = o.id
WHERE  m.datainscripcio <= '2015-12-31' 
AND (m.datacancelacio IS NULL OR m.datacancelacio >= '2015-01-01')
GROUP BY s.id, s.nom, s.databaixa
    	 */
    	
    	// Total membres, rebuts i imports 
    	$strQuery = ' SELECT e.id, COUNT(DISTINCT m.soci) as membres, SUM( d.import) as sumaimports, COUNT(DISTINCT d.rebut) AS totalrebuts, ';
    	$strQuery .= ' SUM( d.import) as sumapagats, COUNT(DISTINCT d.rebut) AS totalretornats ';
    	$strQuery .= ' FROM Foment\GestioBundle\Entity\Seccio e JOIN e.membres m JOIN m.detallsrebuts d JOIN d.rebut r ';
    	$strQuery .= ' WHERE d.databaixa IS NULL AND r.databaixa IS NULL ';
    	$strQuery .= ' AND r.dataemissio >= :inici AND r.dataemissio  <= :final ';
    	$strQuery .= ' AND (m.datainscripcio IS NULL OR m.datainscripcio <= :final)';
    	$strQuery .= ' AND (m.datacancelacio IS NULL OR m.datacancelacio >= :inici)';
    	$strGroupBy = ' GROUP BY e.id';
    	
    	$query = $em->createQuery($strQuery.$strGroupBy);
    	$query->setParameter('inici', $inici);
    	$query->setParameter('final', $final);
    
    	$rebutsArray = $query->getResult();
    
    	$rebuts = array();
    	foreach ($rebutsArray as $r) {
    		$rebuts[ $r['id'] ] = array('membres' => $r['membres'], 'totalrebuts' => $r['totalrebuts'], 'sumaimports' => $r['sumaimports'], 'sumapagats' => 0);
    	}
    	
    	// Total pagats 
    	$query = $em->createQuery($strQuery.' AND r.datapagament IS NOT NULL '.$strGroupBy);
    	$query->setParameter('inici', $inici);
    	$query->setParameter('final', $final);
    	
    	$rebutsArray = $query->getResult();
    	
    	foreach ($rebutsArray as $r) $rebuts[ $r['id'] ]['sumapagats'] = $r['sumapagats'];
    	
    	return $rebuts;
    }
    
    
    protected function queryBaixesMembresAny($seccioid, $anyconsulta) {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = 'SELECT COUNT(m.id) FROM Foment\GestioBundle\Entity\Membre m JOIN m.soci s ';
    	$strQuery .= ' WHERE m.seccio = :sid ';
    	$strQuery .= ' AND m.datacancelacio IS NOT NULL AND m.datacancelacio >= :datainiciany AND m.datainscripcio <= :datafinalany ';
    	
    
    	$query = $em->createQuery($strQuery);
    	$query->setParameter('sid', $seccioid);
    	$query->setParameter('datainiciany', $anyconsulta.'-01-01');
    	$query->setParameter('datafinalany', $anyconsulta.'-12-31');
    	 
    	$result = $query->getSingleScalarResult();
    
    	return $result;
    }
    
    /*
     * Totes les seccions amb les quotes de l'any en curs i el nombre de membres ordenades per id
     * Filtre per nom i diferents ordenacions segons paràmetres
    */
    protected function querySeccions($queryparams) {
    	
    	/*********************************************************************************************
    	*************************   No funciona. No la faig servir ***********************************
    	**********************************************************************************************
    	**********************************************************************************************/
    	
    	
    	$em = $this->getDoctrine()->getManager();
    
    	
    	// Important!! Han d'estar donades d'alta totes les quotes per cada seccio / any 
    	$strQuery = 'SELECT s, q.import, q.importjuvenil, COUNT(m.id) AS membres ';
    	$strQuery .= 'FROM Foment\GestioBundle\Entity\Seccio s LEFT JOIN s.membres m LEFT JOIN s.quotes q ';
    	$strQuery .= 'WHERE s.databaixa IS NULL AND m.datacancelacio IS NULL AND q.anyquota = '.date('Y'). ' ';
   	
    	if ($queryparams['filtre'] != '') $strQuery .= ' AND s.nom LIKE :filtre ';
    	
    	//$strQuery .= 'GROUP BY s, q.anyquota, q.import, q.importjuvenil ';
    	$strQuery .= 'GROUP BY s, q.import, q.importjuvenil ';
    	
    	$strQuery .= 'ORDER BY ' . $queryparams['sort'] . ' ' . $queryparams['direction']; 
    	
    	$query = $em->createQuery($strQuery);
    	
    	if ($queryparams['filtre'] != '') $query->setParameter('filtre', '%'.$queryparams['filtre'].'%');
    	
    	return $query;
    }
    

    
    protected function queryActivitats($queryparams) {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = 'SELECT a FROM Foment\GestioBundle\Entity\Activitat a ';
    	$strQuery .= ' WHERE a.databaixa IS NULL ';
    
    	if ($queryparams['filtre'] != '') $strQuery .= ' AND a.descripcio LIKE :filtre ';
    
    	$strQuery .= ' GROUP BY a.id ORDER BY ' . $queryparams['sort'] . ' ' . $queryparams['direction'];
    
    	$query = $em->createQuery($strQuery);
    
    	if ($queryparams['filtre'] != '') $query->setParameter('filtre', '%'.$queryparams['filtre'].'%');
    
    	return $query;
    }
    
    protected function queryActivitatsPeriode($datainici, $datafinal) {
    	$em = $this->getDoctrine()->getManager();
    
    	/*$strQuery = 'SELECT a FROM Foment\GestioBundle\Entity\Activitat a ';
    	$strQuery .= ' WHERE a.databaixa IS NULL AND ';
    	$strQuery .= ' (( a INSTANCE OF Foment\GestioBundle\Entity\ActivitatPuntual AND a.dataactivitat >= :datainici ';
    	$strQuery .= ' AND a.dataactivitat <= :datafinal ) OR ';
    	$strQuery .= ' ( a INSTANCE OF Foment\GestioBundle\Entity\ActivitatAnual AND a.datainici <= :datafinal ';
    	$strQuery .= ' AND a.datafinal >= :datainici )) ';
    	$strQuery .= ' ORDER BY a.descripcio ';*/
    	
    	$strQuery = 'SELECT a FROM Foment\GestioBundle\Entity\ActivitatAnual a ';
    	$strQuery .= ' WHERE a.databaixa IS NULL ';
    	//$strQuery .= ' AND a INSTANCE OF Foment\GestioBundle\Entity\ActivitatPuntual ';
    	//$strQuery .= ' AND a.dataactivitat <= :datafinal ) OR ';
    	$strQuery .= ' AND a.datainici <= :datafinal ';
    	$strQuery .= ' AND a.datafinal >= :datainici ';
    	$strQuery .= ' ORDER BY a.descripcio ';
    	
    	$query = $em->createQuery($strQuery);
    	
    	$query->setParameter('datainici', $datainici->format('Y-m-d'));
    	$query->setParameter('datafinal', $datafinal->format('Y-m-d'));
    	
    	$result1 = $query->getResult();
    	
    	
    	$strQuery = 'SELECT a FROM Foment\GestioBundle\Entity\ActivitatPuntual a ';
    	$strQuery .= ' WHERE a.databaixa IS NULL ';
    	$strQuery .= ' AND a.dataactivitat >= :datainici ';
    	$strQuery .= ' AND a.dataactivitat <= :datafinal ';
    	$strQuery .= ' ORDER BY a.descripcio ';
    	
    	$query = $em->createQuery($strQuery);
    	 
    	$query->setParameter('datainici', $datainici->format('Y-m-d'));
    	$query->setParameter('datafinal', $datafinal->format('Y-m-d'));
    	 
    	$result2 = $query->getResult();
    	 
    	return array_merge($result1, $result2);
    }
    
    /*
     * mètode genèric que a partir d'una sentència DQL pot construir query amb filtre sobre el nom i cognoms de persones 
     * i ordenada segons valors de $queryparams
     * El mètode retorna $query per si cal afegir algun altre paràmetre
     * 
     * SQL --> DQL no gestiona bé els espais
     */
    private function queryGenericaFiltreOrdreCognomsSQL($queryparams, $strSQL) {
    	$em = $this->getDoctrine()->getManager();
    	
    	
    	$qb = $em->createQueryBuilder();
    	
    	$qb->select('a')
    		->from('Participant', 'a')
    		
    		->where('u.id = ?1')
    		->orderBy('u.name', 'ASC');
    	
    	if ($queryparams['filtre'] != '') $strSQL .= " AND CONCAT(p.nom, CONCAT(' ', p.cognoms)) LIKE :filtre "; // Important ús ' ' i " "
    	
    	$strSQL .= " ORDER BY " . $queryparams['sort'] . " " . $queryparams['direction'];
    	
    	$query = $em->createQuery($strSQL);
    	
    	if ($queryparams['filtre'] != '') {
    		//$query->setParameter('espai', $qb->expr()->literal(' '));
    		//echo "***************".preg_replace('/ /', '%', $queryparams['filtre']);
    		$query->setParameter('filtre', '%'.preg_replace('/ /', "%", $queryparams['filtre'])."%");
    	}
    	return $query;
    }
    
    protected function getPeriodeData($data) {
    	// obtenir període per una data concreta o null si no existeix
    	if ($data == null) return null;
    	
    	$mesdata = $data->format('n');	// Mes en format sense zeros esquerra
    	$periodes = $this->queryGetPeriodesAny($data->format('Y'));
    	
    	foreach ($periodes as $periode) {
    		if ($periode->getMesinici() <= $mesdata && $periode->getMesfinal() >= $mesdata) return $periode;
    	}

    	return null;
    }
    
    protected function queryGetPeriodesAny($anyconsulta) {
    
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = 'SELECT p FROM Foment\GestioBundle\Entity\Periode p ';
    	$strQuery .= ' WHERE p.anyperiode = :anyconsulta ';
    
    	$query = $em->createQuery($strQuery);
    
    	$query->setParameter('anyconsulta', $anyconsulta);
    
    	$result = $query->getResult();
    
    	return $result;
    }
    
    protected function queryGetPeriodesPendents($anyconsulta) {
    
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = 'SELECT p FROM Foment\GestioBundle\Entity\Periode p ';
    	$strQuery .= ' WHERE p.anyperiode >= :anyconsulta ';
    
    	$query = $em->createQuery($strQuery);
    
    	$query->setParameter('anyconsulta', $anyconsulta);
    
    	$result = $query->getResult();
    
    	return $result;
    }
    
    protected function rebutsCreatsAny($anyconsulta) {
    	$periodes = $this->queryGetPeriodesAny($anyconsulta);
    	 
    	
    	foreach ($periodes as $periode) if ($periode->rebutsCreats()) return true;
    	
    	return false; // Cap rebut creat per cap dels periodes de l'any
    }
    
    protected function queryTotalCandidats($queryparams, $activitatno) {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = 'SELECT COUNT(DISTINCT p.id) FROM Foment\GestioBundle\Entity\Persona p ';
    	$strQuery .= ' WHERE NOT EXISTS ( SELECT pa.id FROM Foment\GestioBundle\Entity\Participant pa ';
    	$strQuery .= ' WHERE pa.activitat = :excepcio AND pa.datacancelacio IS NULL AND pa.persona = p.id ) ';
    
    	$query = $em->createQuery($strQuery);
    
    	$query->setParameter('excepcio', $activitatno);
    	
    	$result = $query->getSingleScalarResult();

    	return $result;
    }
    
    
    protected function queryTotal($strEntity, $databaixa = true) {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = 'SELECT COUNT(x.id) FROM Foment\GestioBundle\Entity\\'. $strEntity .' x ';
    	if ($databaixa) $strQuery .= 'WHERE x.databaixa IS NULL';
    
    	$total = $em->createQuery($strQuery)->getSingleScalarResult();
    
    	return $total;
    }
    
    protected function queryGetMembresActiusPeriodeAgrupats(\DateTime $datainici, \DateTime $datafinal) {
    	// Ordenats per soci rebut, num compte i seccio
    	// datainscripcio <= datafinalperiode
    	//				&&
    	// datacancelacio NULL o datacancelacio >= datainiciperiode
    
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = 'SELECT m FROM Foment\GestioBundle\Entity\Membre m JOIN m.soci s';
    	$strQuery .= ' WHERE m.datainscripcio <= :datafinal AND ';
    	$strQuery .= ' (m.datacancelacio IS NULL OR m.datacancelacio >= :datafinal) ';
    	$strQuery .= ' AND s.databaixa IS NULL ';
    	$strQuery .= ' ORDER BY s.socirebut, s.compte DESC, m.seccio, s.cognoms ';
    
    	$query = $em->createQuery($strQuery);
    
    	//$query->setParameter('datainici', $datainici->format('Y-m-d')); No fa falta
    	$query->setParameter('datafinal', $datafinal->format('Y-m-d'));
    
    	$result = $query->getResult();
    
    	return $result;
    }
    
    protected function queryGetMembresActiusPeriodeSeccio(\DateTime $datainici, \DateTime $datafinal, $seccio) {
    	// Ordenats per seccio, soci
    	// datainscripcio <= datafinalperiode
    	//				&&
    	// datacancelacio NULL o datacancelacio >= datainiciperiode
    
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = 'SELECT m FROM Foment\GestioBundle\Entity\Membre m JOIN m.soci s';
    	$strQuery .= ' WHERE m.datainscripcio <= :datafinal AND ';
    	$strQuery .= ' (m.datacancelacio IS NULL OR m.datacancelacio >= :datafinal) ';
    	$strQuery .= ' AND s.databaixa IS NULL ';
    	$strQuery .= ' AND m.seccio = :seccio ';
    	$strQuery .= ' ORDER BY m.seccio, s.cognoms, s.nom ';
    	/*$strQuery .= ' ORDER BY m.seccio, s.socirebut, s.compte DESC, s.cognoms ';*/
    	
    	
    	/*$strQuery = 'SELECT m FROM Foment\GestioBundle\Entity\Membre m ';
    	$strQuery .= ' WHERE m.datainscripcio <= :datafinal AND ';
    	$strQuery .= ' (m.datacancelacio IS NULL OR m.datacancelacio >= :datainici) ';
    	$strQuery .= ' ORDER BY m.seccio, m.soci';*/
    
    	$query = $em->createQuery($strQuery);
    
    	//$query->setParameter('datainici', $datainici->format('Y-m-d')); No fa falta
    	$query->setParameter('datafinal', $datafinal->format('Y-m-d'));
    	$query->setParameter('seccio', $seccio);
    
    	$result = $query->getResult();
    
    	return $result;
    }
    
    protected function queryGetTotalMembresActiusPeriodeSeccio(\DateTime $datainici, \DateTime $datafinal, $seccio) {
    	$membresactius = $this->queryGetMembresActiusPeriodeSeccio($datainici, $datafinal, $seccio); 
    	
    	return count($membresactius);
    }
    
    public function getMaxRebutNumAnySeccio($any) {
    	return $this->getMaxRebutNumAny($any, UtilsController::TIPUS_SECCIO);
    }
    
    public function getMaxRebutNumAnyActivitat($any) {
    	return $this->getMaxRebutNumAny($any, UtilsController::TIPUS_ACTIVITAT);
    }
    
    private function getMaxRebutNumAny($any, $tipus) {
    	// Tipus 1 - seccions 2 - activitats
    	// SELECT MAX(r.num) FROM rebuts r  WHERE r.dataemissio >= '2014-01-01' AND r.dataemissio <= '2014-12-31'
    	if ($any < 2000) $any = date('Y');
    	$ini = $any."-01-01";
    	$fi = $any."-12-31";
    
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = "SELECT MAX(r.num) FROM Foment\GestioBundle\Entity\Rebut r
				 WHERE r.dataemissio >= :ini AND r.dataemissio <= :fi AND 
    				r.tipusrebut = :tipus";
    	 
    	$query = $em->createQuery($strQuery)
    	->setParameter('tipus', $tipus)
    	->setParameter('ini', $ini)
    	->setParameter('fi', $fi);
    	$result = $query->getSingleScalarResult();
    
    	if ($result == null) $result = 1;
    	 
    	return $result;
    }
    
    public function getMaxNumSoci() {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = "SELECT MAX(s.num) FROM Foment\GestioBundle\Entity\Soci s";
    
    	$query = $em->createQuery($strQuery);
    	$result = $query->getSingleScalarResult();
    
    	if ($result == null) $result = 1;
    
    	return $result+1;
    }
    
    public function getMaxFacturacio() {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = "SELECT MAX(f.id) FROM Foment\GestioBundle\Entity\Facturacio f";
    	 
    	$query = $em->createQuery($strQuery);
    	$result = $query->getSingleScalarResult();
    
    	if ($result == null) $result = 1;
    	
    	return $result;
    }

    
    
    /* Generar detall rebut per aquest membre  */
    protected function generarRebutDetallMembre($membre, $rebut, $periode) {
    	// Obtenir info soci: fraccionament, descompte, juvenil
    	$import = UtilsController::quotaMembreSeccioPeriode($membre, $periode);
    	
    	if ($import <= 0) return null;
    	// Crear línia de rebut per quota de Secció segons periode
    	$rebutdetall = new RebutDetall($membre, $rebut, $import);
    	$rebut->addDetall($rebutdetall);
    	
    	return $rebutdetall;
    }
    
    protected function queryMembres($queryparams, $id) {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = 'SELECT m, s FROM Foment\GestioBundle\Entity\Membre m JOIN m.soci s ';
    	$strQuery .= ' WHERE m.datacancelacio IS NULL AND m.seccio = :id ';
    
    	if ($queryparams['filtre'] != '') $strQuery .= ' AND s.cognoms LIKE :filtre ';
    
    	$strQuery .= ' ORDER BY ' . $queryparams['sort'] . ' ' . $queryparams['direction'];
    
    	$query = $em->createQuery($strQuery);
    
    	$query->setParameter('id', $id);
    	if ($queryparams['filtre'] != "") $query->setParameter('filtre', '%'.$queryparams['filtre'].'%');
    
    	return $query;
    }
    
    protected function queryParticipants($queryparams, $id) {
    	$strQuery = "SELECT a, p FROM Foment\GestioBundle\Entity\Participant a JOIN a.persona p ";
    	$strQuery .= " WHERE a.datacancelacio IS NULL AND a.activitat = :id ";
    	 
    	$query = $this->queryGenericaFiltreOrdreCognomsSQL($queryparams, $strQuery);
    	$query->setParameter('id', $id);
    
    	return $query;
    }
    
    
    protected function querySocis($queryparams, $secciono) {
    	if ($secciono == 0) $strQuery = 'SELECT p FROM Foment\GestioBundle\Entity\Soci p WHERE p.databaixa IS NULL';
    	else {
    		$strQuery = 'SELECT p FROM Foment\GestioBundle\Entity\Soci p ';
    		$strQuery .= ' WHERE NOT EXISTS ( SELECT m.id FROM Foment\GestioBundle\Entity\Membre m ';
    		$strQuery .= ' WHERE m.seccio = :excepcio AND m.datacancelacio IS NULL ';
    		$strQuery .= ' AND m.soci = p.id ) AND p.databaixa IS NULL ';
    	}
    	$query = $this->queryPersonesSocis($queryparams, $strQuery, $secciono);
    	 
    	return $query;
    }
    
    protected function queryCandidats($queryparams, $activitatno) {
    	if ($activitatno == 0) $strQuery = 'SELECT p FROM Foment\GestioBundle\Entity\Persona p ';
    	else {
    		$strQuery = ' SELECT p FROM Foment\GestioBundle\Entity\Persona p ';
    		$strQuery .= ' WHERE NOT EXISTS ( SELECT pa.id FROM Foment\GestioBundle\Entity\Participant pa ';
    		$strQuery .= ' WHERE pa.activitat = :excepcio AND pa.datacancelacio IS NULL AND pa.persona = p.id ) ';
    	}
    	 
    	$query = $this->queryPersonesSocis($queryparams, $strQuery, $activitatno);
    
    	return $query;
    }
    
    /*
     * Mètode genèric que agrupa les parts comunes del mètodes particulars de Soci i Persona
    */
    private function queryPersonesSocis($queryparams, $strQuery, $excepcioid) {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery .= " ORDER BY " . $queryparams['sort'] . " " . $queryparams['direction'];
    	$query = $em->createQuery($strQuery);
    	if ($excepcioid != 0) $query->setParameter('excepcio', $excepcioid);
        
    	return $query->getResult();
    }
    
    
    protected function filtrarArrayNomCognoms($arrayEntitats, $queryparams) {  // Han d'implementar getNom() i getCognoms()
    	$query = array();
    	foreach ($arrayEntitats as $e) {
    		 
    		$nomcognoms = $e->getNom().' '.$e->getCognoms();
    		 
    		if	($this->filtreTaulaCompleix($queryparams['filtre'], $nomcognoms)) {
    			$query[] = $e;
    		}
    	}
    	return $query;
    }
    
    protected function filtrarArraySeccions($arraySeccions, $queryparams, $anydades) {

    	$quotes = $queryparams['quotes'];
    	
    	$totalsrebuts = $this->queryGetRebutsPerSeccioAny($arraySeccions, $anydades);  // array totals rebuts
    	
    	$ini = \DateTime::createFromFormat('Y-m-d', $anydades."-01-01"); 
    	$fi = \DateTime::createFromFormat('Y-m-d', $anydades."-12-31");
    	
    	$query = array();
    	foreach ($arraySeccions as $s) {
    	
    		$nom = $s->getNom();
    	
    		if	($this->filtreTaulaCompleix($queryparams['filtre'], $nom)) {
    			
    			$id = $s->getId();
    			 
    			$aux = array('id' => $id, 'nom' => $nom );
    			$aux['import'] = (isset($quotes[$id])?$quotes[$id]['import']:0);
    			$aux['importjuvenil'] = (isset($quotes[$id])?$quotes[$id]['importjuvenil']:0);
    			$aux['membres'] = $this->queryGetTotalMembresActiusPeriodeSeccio($ini , $fi, $id); 
    			//$aux['membres'] = (isset($totalsrebuts[$id])?$totalsrebuts[$id]['membres']:0);  // Millor que accedir als objectes fer-ho directament DQL. Més ràpid
    			$aux['rebuts'] = (isset($totalsrebuts[$id])?$totalsrebuts[$id]['totalrebuts']:0);
    			$aux['sumaimports'] = (isset($totalsrebuts[$id])?$totalsrebuts[$id]['sumaimports']:0);
    			$aux['sumapagats'] = (isset($totalsrebuts[$id])?$totalsrebuts[$id]['sumapagats']:0);
    			$aux['baixesany'] = $this->queryBaixesMembresAny($id, $anydades);
    			    			 
    			$query[] = $aux;
    		}
    	}
    	return $query;
    } 
    
    /** Obtenir anys camp Select */
    protected function getAnysSelectable() {
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	$strQuery = 'SELECT p FROM Foment\GestioBundle\Entity\Periode p ORDER BY p.anyperiode DESC';
	    
    	$query = $em->createQuery($strQuery);
	    
    	$periodes = $query->getResult();
	    	
	    $anysSelectable = array();
	    
	    foreach ($periodes as $periode) {
	    	if (!in_array($periode->getAnyperiode(), $anysSelectable)) $anysSelectable[$periode->getAnyperiode()] = $periode->getAnyperiode();
	    }
	    
	    // Han d'estar l'any actual i el pròxim com a mínim
	    if (!in_array( date('Y') , $anysSelectable)) $anysSelectable[date('Y')] = date('Y');
	    if (!in_array( (date('Y')+1) , $anysSelectable)) $anysSelectable[(date('Y')+1)] = (date('Y')+1);
	    
	    return $anysSelectable;
    }
    
    
    /** Obtenir anys camp Select des de 2014 fins actual */
    protected function getAnysSelectableToNow() {
    	 
    	$anysSelectable = array();
    	 
    	// Inici any 2014
    	for ($any = 2014; $any <= date('Y'); $any++) {
    		$anysSelectable[$any] = $any;
    	}
    	 
    	return $anysSelectable;
    }
    
    /****** Funcions compare usort objectes :  usort($array, 'cmp-...'); *****/
    protected function cmpidasc($a, $b) {
    	if ($a === $b) { return 0; } 
    	return ( $a->getId() < $b->getId() )? -1:1;
    	return 0;
    }
    protected function cmpiddesc($a, $b) { 
    	if ($a === $b) { return 0; } 
    	return ( $a->getId() > $b->getId() )? -1:1;
    	return 0; 
   	}
    protected function cmpcognomsnomasc($a, $b) { return $this->cmpgeneriques(strtolower($a->getCognoms().$a->getNom()), strtolower($b->getCognoms().$b->getNom()), true);   }
    protected function cmpcognomsnomdesc($a, $b) { 	return $this->cmpgeneriques(strtolower($a->getCognoms().$a->getNom()), strtolower($b->getCognoms().$b->getNom()), false);   }
	protected function cmpdatainscripcioasc($a, $b) { return $this->cmpgeneriques($a->getDatainscripcio()->format('Ymd'), $b->getDatainscripcio()->format('Ymd'), true);   }
    protected function cmpdatainscripciodesc($a, $b) { 	return $this->cmpgeneriques($a->getDatainscripcio()->format('Ymd'), $b->getDatainscripcio()->format('Ymd'), false);   }
    
    // Compare generic strings
    protected function cmpgeneriques($a, $b, $asc) { 
    	if ($a === $b) { return 0; } 
    	if ($asc) return ( $a < $b )? -1:1;
    	if (!$asc) return ( $a > $b )? -1:1;
    	return 0;
    }
    
    protected function ordenarArrayObjectes($array, $queryparams) {
    	$comparefunc = 'cmp'.$queryparams['sort'].$queryparams['direction'];
    	
    	if (! method_exists($this,$comparefunc)) $comparefunc = 'cmpid'.$queryparams['direction']; // Default id
    	usort($array, array($this, $comparefunc) ); 
    	
    	return $array;
    }
    
    
    protected function ordenarArrayClausVariables($array, $queryparams, $keys) {
    // Ordenar l'array segons els paràmetres
    	usort($array, function($a, $b ) use ($queryparams, $keys) {
	    	if ($a === $b) {
	    		return 0;
	    	}
	    	
	    	foreach ($keys as $k => $v) {
	    		if ($queryparams['sort'] == $v && $queryparams['direction'] == 'asc') return ( $a[$k] < $b[$k] )? -1:1;
	    		if ($queryparams['sort'] == $v && $queryparams['direction'] == 'desc') return ( $a[$k] > $b[$k] )? -1:1;
	    	}
	    	
	    	return 0;
	    
	    });
	    
	    return $array;
    }
    
    protected function filtreTaulaCompleix($filtre, $dada) {
    	// Aplicar filtre  o sense filtre.
    	// Sense tenie en compte caràcters amb accents, majúscules, especials, etc... per exemple à á i ä  són igual que a
    	if ($filtre == '') return true;
    	
    	$filtre = UtilsController::netejarNom($filtre);
    	$dada = UtilsController::netejarNom($dada);
    	
    	if (strpos( strtolower($dada), strtolower($filtre)) !== false) return true;
    	
    	return false;
    }
}
