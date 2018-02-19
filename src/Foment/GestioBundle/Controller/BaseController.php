<?php

namespace Foment\GestioBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Foment\GestioBundle\Entity\Rebut;
use Foment\GestioBundle\Entity\RebutDetall;
use Foment\GestioBundle\Entity\Saldo;



class BaseController extends Controller
{
	protected function consultaDonacionsPeriode($datainici, $datafinal, $persona = null) {
		$em = $this->getDoctrine()->getManager();
	
		// Rebuts pagats d'un any, ordenats per soci codi. Només quota foment i derrames seccions
		$strQuery = " SELECT p as persona, SUM(d.import) as importdonacio FROM Foment\GestioBundle\Entity\Persona p JOIN p.rebuts r JOIN r.detalls d ";
		$strQuery .= " WHERE r.databaixa IS NULL ";
		$strQuery .= " AND r.tipusrebut = :tipus ";
		$strQuery .= " AND d.databaixa IS NULL ";
		$strQuery .= " AND r.datapagament IS NOT NULL ";
		$strQuery .= " AND r.datapagament >= :datainici AND r.datapagament <= :datafinal ";
	
		// SELECT COUNT(DISTINCT r.deutor), SUM(d.import) FROM rebuts r INNER JOIN rebutsdetall d ON r.id = d.rebut INNER JOIN persones p ON p.id = r.deutor 
		// WHERE r.datapagament IS NOT NULL AND r.tipusrebut = 1 AND r.datapagament >= '2015-01-01' AND r.datapagament <= '2015-12-31' AND r.databaixa IS NULL AND d.databaixa IS NULL
		
		if ($persona != null) $strQuery .= " AND p.id = :personaid ";
	
		$strQuery .= " GROUP BY p.id ";
		$strQuery .= " ORDER BY p.id ";
		
		$query = $em->createQuery($strQuery);
	
		$query->setParameter('datainici', $datainici->format('Y-m-d'));
		$query->setParameter('datafinal', $datafinal->format('Y-m-d'));
		$query->setParameter('tipus', UtilsController::TIPUS_SECCIO);
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
    	$sort = $request->query->get('sort', 'nom'); // default 'nom'
    	$direction = $request->query->get('direction', 'asc');
    	$page = $request->query->get('page', 1);
    	
    	$nini = $request->query->get('nini', 0);
    	$nfi = $request->query->get('nfi', 0);

    	$s = array();
    	$vigents = true;
        if ($request->query->get('vigents', 1) == 0) $vigents = false;
        else $s[] = UtilsController::INDEX_CERCA_SOCIS;
        
        $baixes = false;
        if ($request->query->get('baixes', 0) == 1) {
            $baixes = true;
            $s[] = UtilsController::INDEX_CERCA_BAIXES;
        }
        
        $nosocis = false;
        if ($request->query->get('nosocis', 0) == 1) {
            $nosocis = true;
            $s[] = UtilsController::INDEX_CERCA_NOSOCIS;
        }
        
        
    	$nom = $request->query->get('nom', '');
    	$cognoms = $request->query->get('cognoms', '');
    	$dni = $request->query->get('dni', '');
    	$h = true;
    	if ($request->query->has('h') && $request->query->get('h') == 0) $h = false;
    	$d = true;
    	if ($request->query->has('d') && $request->query->get('d') == 0) $d = false;
    	$mail = $request->query->get('mail', '');
    	$nomail = false;
    	if ($request->query->has('nomail') && $request->query->get('nomail') == 1) $nomail = true;
    	
    	$newsletter = $request->query->get('newsletter', UtilsController::INDEX_DEFAULT_TOTS);
    	$dretsimatge = $request->query->get('dretsimatge', UtilsController::INDEX_DEFAULT_TOTS);
    	$lopd = $request->query->get('lopd', UtilsController::INDEX_DEFAULT_TOTS);
    	
    	$exempt = false;
    	if ($request->query->has('exempt') && $request->query->get('exempt') == 1) $exempt = true;
    	
    	
    	
    	$dini = $request->query->get('dini', '');
    	$dfi = $request->query->get('dfi', '');
    	 
    	$queryparams = array('sort' => $sort,'direction' => $direction, 'page' => $page, 'perpage' => UtilsController::DEFAULT_PERPAGE_WITHFORM,
    			'nom' => $nom, 'cognoms' => $cognoms, 'dni' => $dni,
    			'nomail' => $nomail, 'mail' => $mail, 'exempt' => $exempt,
    			'h' => $h, 'd' => $d, 's' =>  $s,
    	        'newsletter' => $newsletter, 'dretsimatge' => $dretsimatge, 'lopd' => $lopd
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
    	
    	$qParams = array();
    	$strQuery = "";
    	
    	// Condicions persona generals
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
    	
    	if ($nomail == true) $strQuery .= " AND s.correu IS NULL ";
    	else {
    	    if ($mail != "") {
    	        $strQuery .= " AND s.correu LIKE :mail ";
    	        $qParams['mail'] = "%".$mail."%";
    	    }
    	}
    	
    	if ($newsletter == UtilsController::INDEX_DEFAULT_SI) {
    	    $strQuery .= " AND s.newsletter = true AND s.correu IS NOT NULL AND s.correu <> '' ";
    	}
    	if ($newsletter == UtilsController::INDEX_DEFAULT_NO) {
    	    $strQuery .= " AND s.newsletter = false ";
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
    	
    	$queryparams['querynosocis'] = '';
    	if ($nosocis) {
    	    // Query persones no socies
   	        $prefix = "SELECT ".$strSelect." FROM Foment\GestioBundle\Entity\Persona s ";
   	        $query = $em->createQuery($prefix . $strJoinParticipacions." WHERE s NOT INSTANCE OF Foment\GestioBundle\Entity\Soci ".$strQuery);
    	    
    	    foreach ($qParams as $k => $p) {  // Add query parameters
    	        $query->setParameter($k, $p);
    	    }
    	    
    	    $queryparams['querynosocis'] = $query;
    	}
    	
    	$queryparams['query'] = '';
    	if ($vigents || $baixes) {
    	    // Query socis
    	    $prefix = "SELECT ".$strSelect." FROM Foment\GestioBundle\Entity\Soci s ";
    	    $strQuery = $prefix . $strJoinMembres. $strJoinParticipacions." WHERE s INSTANCE OF Foment\GestioBundle\Entity\Soci ".$strQuery; // Només socis
    	    
        	// Condicions específiques per a socis
        	if ($vigents && !$baixes) $strQuery .= " AND s.databaixa IS NULL ";
        	if (!$vigents && $baixes) $strQuery .= " AND s.databaixa IS NOT NULL ";
        	
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
        	        $strQuery .= " AND s.num = :num ";
        	        $qParams['num'] = $nini;
        	    }
        	    if ($nini == 0 && $nfi > 0)  {
        	        $strQuery .= " AND s.num = :num ";
        	        $qParams['num'] = $nfi;
        	    }
        	}
        	
        	if ($dretsimatge == UtilsController::INDEX_DEFAULT_SI) {
        	    $strQuery .= " AND s.dretsimatge = true ";
        	}
        	if ($dretsimatge == UtilsController::INDEX_DEFAULT_NO) {
        	    $strQuery .= " AND s.dretsimatge = false ";
        	}
        	if ($lopd == UtilsController::INDEX_DEFAULT_SI) {
        	    $strQuery .= " AND s.lopd = true ";
        	}
        	if ($lopd == UtilsController::INDEX_DEFAULT_NO) {
        	    $strQuery .= " AND s.lopd = false ";
        	}

        	$query = $em->createQuery($strQuery);
        	
        	foreach ($qParams as $k => $p) {  // Add query parameters
        	    $query->setParameter($k, $p);
        	}
        	
        	$queryparams['query'] = $query;
    	}
    	
    	return $queryparams;
    }
    
    protected function sortPersones($querysocis, $querynosocis, $sort, $direction) {
        $persones = array();
        if ($querynosocis != '') $persones = $querynosocis->getResult();
        if ($querysocis != '') $persones = array_merge($persones, $querysocis->getResult());
        
        // ORDENAR !!!!!!
        usort($persones, function ($a, $b) use ($sort, $direction) {
            if ($a === $b) return 0;
            
            $primer = $a;
            $segon = $b;
            if (strtolower($direction) == 'desc') {
                $primer = $b;
                $segon = $a;
            }
             
            switch ($sort) {
                case 'num':
                    return $primer->getNum() - $segon->getNum();
                    break;
                    
                case 'datanaixement':
                    
                    $dataprimer = $primer->getDatanaixement() != null?$primer->getDatanaixement():new \DateTime();
                    $datasegon = $segon->getDatanaixement() != null?$segon->getDatanaixement():new \DateTime();
                    return 1*$dataprimer->format('Ymd') - 1*$datasegon->format('Ymd');
                    break;
                
                case 'dni':
                    
                    return strcmp(mb_strtolower($primer->getDni(), 'UTF-8'),mb_strtolower($segon->getDni(), 'UTF-8'));
                    break;
                    
                case 'mail':
                    
                    return strcmp($primer->getCorreu(),$segon->getCorreu());
                    
                default:    // 'nom'
                    
                    return strcmp(mb_strtolower($primer->getCognoms().$primer->getNom(), 'UTF-8'),mb_strtolower($segon->getCognoms().$segon->getNom(), 'UTF-8'));
                    break;
            }
            
            return 0;
        });
        
        return $persones;
    }
    
    protected function queryRebuts(Request $request) {
    	// Opcions de filtre del formulari
    	$sort = $request->query->get('sort', 'r.id'); // default 'r.id desc'
    	$direction = $request->query->get('direction', 'desc');
    
    	$idRebut = $request->query->get('id', 0);
    	
    	$nini = $request->query->get('nini', 0);
    	$nfi = $request->query->get('nfi', 0);
    
    	$persona = $request->query->get('persona', '');
    
    	$dini = $request->query->get('dini', '');
    	$dfi = $request->query->get('dfi', '');
    
    	$cobrats = $request->query->get('cobrats', 0);
    	$tipus = $request->query->get('tipus', 0);
    
    	$facturacio = $request->query->get('facturacio', 0);
    	$page = $request->query->get('page', 1);
    
    	
    	$anulats = false;
    	if ($request->query->has('anulats') && $request->query->get('anulats') == 1) $anulats = true;
    	
    	$retornats = false;
    	if ($request->query->has('retornats') && $request->query->get('retornats') == 1) $retornats = true;
    
    	// Si no són tots
    	if ($tipus != 0 && $retornats == true) $tipus = UtilsController::INDEX_FINES_RETORNAT;
    	
    	
    	$queryparams = array('sort' => $sort,'direction' => $direction, 'page' => $page,
    			'anulats' => $anulats, 'retornats' =>  $retornats, 'cobrats' => $cobrats, 'tipus' => $tipus,
    			'persona' => $persona, 'facturacio' => $facturacio
    	);
    
    	if ($nini > 0)  $queryparams['nini'] = $nini;
    	if ($nfi > 0)  $queryparams['nfi'] = $nfi;
    	if ($dini != '')  $queryparams['dini'] = $dini;
    	if ($dfi != '')  $queryparams['dfi'] = $dfi;
    
    
    	$seccionsIds = $request->query->get('seccions', array());

    	$seccions = array();
    	foreach ($seccionsIds as $id) {  // Retrive selected seccions
    		$seccions[] = $this->getDoctrine()->getRepository('FomentGestioBundle:Seccio')->find($id);
    		//$queryparams['seccions['.$i.']'] = $id;
    	}
    	$queryparams['seccions'] = $seccions;
    
    	$activitatsIds = $request->query->get('activitats', array());
    	/*foreach ($activitatsIds as $i => $id) {  // Retrive selected seccions
    		$queryparams['activitats['.$i.']'] = $id;
    	}*/
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
    			$strQuery .= " AND m.seccio IN (:seccions) ";
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
    	
    	if ($idRebut > 0) {
    		$strQuery .= " AND r.id = :id ";
    		$qParams['id'] = $idRebut;
    	}
    
    	if ($nini > 0 && $nfi > 0) {
    		$strQuery .= " AND r.num BETWEEN :nini AND :nfi ";
    		$qParams['nini'] = $nini;
    		$qParams['nfi'] = $nfi;
    	} else {
    		// Només un
	    	if ($nini > 0) {
	    		$strQuery .= " AND r.num = :nini ";
	    		$qParams['nini'] = $nini;
	    	}
    		if ($nini == 0 && $nfi > 0)  {
    			$strQuery .= " AND r.num = :nfi ";
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
    	
    	$strQuery .= " ORDER BY " . $sort . " " . $direction;
    	
    	$query = $em->createQuery($strQuery);
    		
    	foreach ($qParams as $k => $p) {  // Add query parameters
    		$query->setParameter($k, $p);
    	}
    		
    	$queryparams['query'] = $query;
    	
    	return $queryparams;
    }
    
    protected function getCurrentSaldo() {
    	$em = $this->getDoctrine()->getManager();
    	 
    	// Consultar saldos descendents
    	$strQuery  = " SELECT s FROM Foment\GestioBundle\Entity\Saldo s ";
    	$strQuery .= " WHERE s.databaixa IS NULL ";
    	$strQuery .= " ORDER BY s.id DESC";
    	 
    	$query = $em->createQuery($strQuery);
    	$saldos = $query->getResult();
    	 
    	if (count($saldos) == 0) {
    		$saldo = new Saldo(); // Avui amb import 0
    		$em->persist($saldo);
    		return $saldo;
    	}
    	 
    	return $saldos[0];
    }
    
    protected function getSaldoConsolidat() {
    	$em = $this->getDoctrine()->getManager();
    
    	// Consultar saldos descendents
    	$strQuery  = " SELECT s FROM Foment\GestioBundle\Entity\Saldo s ";
    	$strQuery .= " WHERE s.databaixa IS NULL AND s.dataconsolidat IS NOT NULL ";
    	$strQuery .= " ORDER BY s.id DESC";
    
    	$query = $em->createQuery($strQuery);
    	$saldos = $query->getResult();
    
    	if (count($saldos) == 0) return null;
    	
    	return $saldos[0];
    }
    
    protected function getSaldoApunts($instant = null) {
		$em = $this->getDoctrine()->getManager();
    	
    	$saldoConsolidat = $this->getSaldoConsolidat();
    	
		if ($saldoConsolidat == null) return null;

		$dataconsolidat = $saldoConsolidat->getDataconsolidat();
		
    	// Des de la data del saldo fins l'últim apunt sumar entrades i restar sortides
    	$strQuery = " SELECT SUM(a.import) FROM Foment\GestioBundle\Entity\Apunt a ";
    	$strQuery .= " WHERE a.databaixa IS NULL ";
    	$strQuery .= " AND a.tipus = :entrada ";
    	$strQuery .= " AND a.dataapunt > :datasaldoconsolidat ";
    	if ($instant != null) $strQuery .= " AND a.dataapunt < :fins ";
    	
    	$query = $em->createQuery($strQuery);
    	$query->setParameter('entrada', UtilsController::TIPUS_APUNT_ENTRADA);
    	$query->setParameter('datasaldoconsolidat', $dataconsolidat->format('Y-m-d H:i:s'));
    	if ($instant != null) $query->setParameter('fins', $instant->format('Y-m-d H:i:s'));
    	
    	$entrades = $query->getSingleScalarResult();
    	if ($entrades == null) $entrades = 0;
    	
    	$query = $em->createQuery($strQuery);
    	$query->setParameter('entrada', UtilsController::TIPUS_APUNT_SORTIDA);
    	$query->setParameter('datasaldoconsolidat', $dataconsolidat->format('Y-m-d H:i:s'));
    	if ($instant != null) $query->setParameter('fins', $instant->format('Y-m-d H:i:s'));
    	
    	$sortides = $query->getSingleScalarResult();
    	if ($sortides == null) $sortides = 0;
    	
    	return $saldoConsolidat->getImportconsolidat() + $entrades - $sortides;
    }
    
    protected function queryApunts($max, $saldo = 0, $tipusconcepte = '', $concepte = '', $desde = null, $fins = null) {
   	
    	$em = $this->getDoctrine()->getManager();
    	$apuntsAsArray = array();
    	$saldos = true;
    	
    	// Si la consulta té filtres no cal informació de saldos. Sense filtres afegir saldos 
    	if ($tipusconcepte != '' || $concepte != '') $saldos = false;
   		 
   		// Opcions de filtre del formulari
   		$sort = 'a.dataapunt desc, a.id desc'; // apunts sempre ordenats per data des del darrer apunt
    		 
   		/* Query */
   		$qParams = array();
    		 
   		$strQuery = " SELECT a FROM Foment\GestioBundle\Entity\Apunt a INNER JOIN a.concepte c ";
   		$strQuery .= " WHERE a.databaixa IS NULL ";
    		 
   		if ($tipusconcepte != '') {
   			$strQuery .= " AND c.tipus = :tipusconcepte ";
   			$qParams['tipusconcepte'] = $tipusconcepte;
   		}
    		 
   		if ($concepte != "") {
   			$strQuery .= " AND (c.concepte LIKE :concepte OR c.codi LIKE :concepte) ";
   			$qParams['concepte'] = "%".$concepte."%";
   		}
   		
   		if ($desde != null) {
   			$strQuery .= " AND a.dataapunt >= :desde ";
   			$qParams['desde'] = $desde->format('Y-m-d').' 00:00:00';
   		}
    		
   		if ($fins != null) {
   			$strQuery .= " AND a.dataapunt <= :fins ";
   			$qParams['fins'] = $fins->format('Y-m-d').' 23:59:59';
   		}
   		
   		$strQuery .= " ORDER BY " . $sort;
    		 
   		$query = $em->createQuery($strQuery);
    		 
   		foreach ($qParams as $k => $p) {  // Add query parameters
   			$query->setParameter($k, $p);
   		}
    		 
   		if ($max > 0) $query->setMaxResults($max);
    		 
   		$apunts = $query->getResult();
    		
   		$apuntsAsArray = $this->getApuntsAsArray($apunts, $saldos, $saldo); // Sense informació de saldos
    		 
   		return array_reverse($apuntsAsArray);  // Ascendent per dataapunt  i dataentrada
    }
    
    protected function getCaixaParams($request) {
    	$page = $request->query->get('page', '');  // Última
    	$perpage = $request->query->get('perpage', UtilsController::DEFAULT_PERPAGE);  // Sempre mostra els 'perpage' primers
    	$tipusconcepte = $request->query->get('tipusconcepte', '');
    	$concepte = $request->query->get('filtre', '');
    	$action = $request->query->get('action', 'form');
    
    	return array( 'action' => $action, 'page' => $page, 'perpage' => $perpage,	'tipusconcepte' =>  $tipusconcepte, 'filtre' => $concepte);
    }
    
    private function getApuntsAsArray($apunts, $saldos = false, $saldo = 0) {
    	
    	$apuntsAsArray = array();
    	foreach ($apunts as $apunt) {
    		
    		$concepte = $apunt->getConcepte();
    		
    		$apuntsAsArray[] = array(
    				'id' 		=> $apunt->getId(),
    				'num'		=> $apunt->getNumFormat(),
    				'data'		=> $apunt->getDataapunt(),
    				'tipus'		=> $concepte->getTipus(),
    				'codi'		=> $concepte->getCodi(),
    				'concepte'	=> $concepte->getConcepte(),
    				'rebut'		=> $apunt->getRebut(),
    				'entrada'	=> ($apunt->esEntrada()?$apunt->getImport():''),
    				'sortida'	=> ($apunt->esSortida()?$apunt->getImport():''),
    				'saldo'		=> (!$saldos?'':$saldo)
    		);

    		if ($saldos) {
    			$factor = $apunt->esEntrada()? -1:1;
    			
    			$saldo += $factor * $apunt->getImport(); 
    		}

    		
    	}
    	
    	return $apuntsAsArray;
    }
    
    protected function queryApuntConcepteBySeccioActivitat($termId, $seccio = true, $excludeId = 0) {
    
    	$em = $this->getDoctrine()->getManager();
    	
    	
    	if ($termId == 0) return null;
    	
    	/* Query */
    	 
    	$strQuery = " SELECT c FROM Foment\GestioBundle\Entity\ApuntConcepte c ";
    	if ($seccio) $strQuery .= " WHERE c.databaixa IS NULL AND c.seccions LIKE :termid ";
    	else $strQuery .= " WHERE c.databaixa IS NULL AND c.activitats LIKE :termid ";
    	
    	if ($excludeId > 0) $strQuery .= " AND c.id <> :excludeid ";
    	 
    	$query = $em->createQuery($strQuery);
    	
    	$query->setParameter('termid', '%'.$termId.'%');
    	
    	if ($excludeId > 0) $query->setParameter('excludeid', $excludeId);
    	
		$query->setMaxResults(1);
    	 
    	$conceptes = $query->getResult();
    
    	if ($conceptes != null && count($conceptes) == 1) return $conceptes[0];
    	 
    	return null;
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
    	$strQuery = ' SELECT e.id, COUNT(DISTINCT m.soci) as membres, SUM( d.import) as sumaimports, COUNT( DISTINCT r.id) AS totalrebuts, ';
    	$strQuery .= ' COUNT( m.id) AS totalquotes, SUM( d.import) as sumapagats ';
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
    		$rebuts[ $r['id'] ] = array('membres' => $r['membres'], 'totalrebuts' => $r['totalrebuts'], 'totalquotes' => $r['totalquotes'], 'sumaimports' => $r['sumaimports'], 'sumapagats' => 0);
    	}
    	
    	// Total pagats 
    	$query = $em->createQuery($strQuery.' AND r.datapagament IS NOT NULL '.$strGroupBy);
    	$query->setParameter('inici', $inici);
    	$query->setParameter('final', $final);
    	
    	$rebutsArray = $query->getResult();
    	
    	foreach ($rebutsArray as $r) $rebuts[ $r['id'] ]['sumapagats'] = $r['sumapagats'];
    	
    	return $rebuts;
    }
    
    protected function queryGetMembresActiusPeriodeSeccio(\DateTime $datainici, \DateTime $datafinal, $seccio) {
    	// Ordenats per seccio, soci
    	// datainscripcio <= datafinalperiode
    	//				&&
    	// datacancelacio NULL o datacancelacio >= datainiciperiode
    
    	$em = $this->getDoctrine()->getManager();
    
    	// VEURE Membre.php => esMembreActiuPeriode
    	
    	// Només consultar actius al final del periode !!
    	
    	$strQuery = 'SELECT s FROM Foment\GestioBundle\Entity\Soci s JOIN s.membrede m';
    	$strQuery .= ' WHERE m.seccio = :seccio AND ';
    	$strQuery .= ' m.datainscripcio <= :datafinal AND ';
    	$strQuery .= ' (m.datacancelacio IS NULL OR ';
    	//$strQuery .= ' (m.datacancelacio IS NOT NULL AND m.datacancelacio >= :datainici) ) AND ';
    	$strQuery .= ' (m.datacancelacio IS NOT NULL AND m.datacancelacio > :datafinal) ) AND ';
    	$strQuery .= ' (s.databaixa IS NULL OR';
    	//$strQuery .= ' (s.databaixa IS NOT NULL AND s.databaixa >= :datainici) )';
    	$strQuery .= ' (s.databaixa IS NOT NULL AND s.databaixa > :datafinal) )';
    	$strQuery .= ' ORDER BY m.seccio, s.cognoms, s.nom ';
    	
    
    	$query = $em->createQuery($strQuery);
    
    	//$query->setParameter('datainici', $datainici->format('Y-m-d')); 
    	$query->setParameter('datafinal', $datafinal->format('Y-m-d'));
    	$query->setParameter('seccio', $seccio);
    
    	$result = $query->getResult();
    
    	return $result;
    }
    
    protected function queryGetTotalMembresActiusPeriodeSeccio(\DateTime $datainici, \DateTime $datafinal, $seccio) {
    	$membresactius = $this->queryGetMembresActiusPeriodeSeccio($datainici, $datafinal, $seccio);
    	 
    	return count($membresactius);
    }
    
    protected function queryBaixesMembresAny(\DateTime $datainici, \DateTime $datafinal, $seccio) {
    	$em = $this->getDoctrine()->getManager();
    
    	// VEURE Membre.php => esMembreBaixaPeriode
    	
    	$strQuery = 'SELECT s FROM Foment\GestioBundle\Entity\Soci s JOIN s.membrede m';
    	$strQuery .= ' WHERE m.seccio = :seccio AND ';
    	$strQuery .= ' ( ';
    	$strQuery .= '  (m.datacancelacio IS NULL AND s.databaixa IS NOT NULL AND s.databaixa >= :datainici AND s.databaixa >= :datafinal) ';
    	$strQuery .= '  OR ';
    	$strQuery .= '  (m.datacancelacio IS NOT NULL AND m.datacancelacio >= :datainici AND m.datacancelacio <= :datafinal) ';
    	$strQuery .= ' ) ';
    	$strQuery .= ' ORDER BY m.seccio, s.cognoms, s.nom ';
    	
    
    	$query = $em->createQuery($strQuery);
    	$query->setParameter('datainici', $datainici->format('Y-m-d')); 
    	$query->setParameter('datafinal', $datafinal->format('Y-m-d'));
    	$query->setParameter('seccio', $seccio);
    	 
    	$result = $query->getResult();
    
    	return $result;
    }
    
    protected function queryAltesMembresAny(\DateTime $datainici, \DateTime $datafinal, $seccio) {
    	$em = $this->getDoctrine()->getManager();
    
    	// VEURE Membre.php => esMembreAltaPeriode
    	 
    	$strQuery = 'SELECT s FROM Foment\GestioBundle\Entity\Soci s JOIN s.membrede m';
    	$strQuery .= ' WHERE m.seccio = :seccio AND ';
    	$strQuery .= '  (m.datainscripcio >= :datainici AND m.datainscripcio <= :datafinal) ';
    	$strQuery .= ' ORDER BY m.seccio, s.cognoms, s.nom ';
    	 
    	$query = $em->createQuery($strQuery);
    	$query->setParameter('datainici', $datainici->format('Y-m-d'));
    	$query->setParameter('datafinal', $datafinal->format('Y-m-d'));
    	$query->setParameter('seccio', $seccio);
    
    	$result = $query->getResult();
    
    	return $result;
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
    
    			$aux = array('id' => $id, 'nom' => $nom, 'ordre' => $s->getOrdre() );
    			$aux['import'] = (isset($quotes[$id])?$quotes[$id]['import']:0);
    			$aux['importjuvenil'] = (isset($quotes[$id])?$quotes[$id]['importjuvenil']:0);  
    			 
    			$aux['membres'] = $this->queryGetTotalMembresActiusPeriodeSeccio($ini , $fi, $id);  // count($s->getMembresActius($anydades)); MOLT LENT
    			 
    			//$aux['membres'] = (isset($totalsrebuts[$id])?$totalsrebuts[$id]['membres']:0);  // Millor que accedir als objectes fer-ho directament DQL. Més ràpid
    			$aux['rebuts'] = (isset($totalsrebuts[$id])?$totalsrebuts[$id]['totalrebuts']:0);
    			$aux['quotes'] = (isset($totalsrebuts[$id])?$totalsrebuts[$id]['totalquotes']:0);
    			$aux['sumaimports'] = (isset($totalsrebuts[$id])?$totalsrebuts[$id]['sumaimports']:0);
    			$aux['sumapagats'] = (isset($totalsrebuts[$id])?$totalsrebuts[$id]['sumapagats']:0);
    			 
    			$aux['baixesany'] = count($this->queryBaixesMembresAny($ini , $fi, $id));  // count($s->getAltesMembresPeriode($ini, $fi));  MOLT LENT
    			$aux['altesany'] = count($this->queryAltesMembresAny($ini , $fi, $id));  // count($s->getBaixesMembresPeriode($ini, $fi));   MOLT LENT
    			 
    			$query[] = $aux;
    		}
    	}
    	return $query;
    }
    
    protected function queryActivitats($queryparams) {
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = 'SELECT a FROM Foment\GestioBundle\Entity\Activitat a ';
    	$strQuery .= ' WHERE a.databaixa IS NULL ';
    
    	if ($queryparams['filtre'] != '') $strQuery .= ' AND a.descripcio LIKE :filtre ';
    	if (!isset($queryparams['finalitzats']) || $queryparams['finalitzats'] == false) $strQuery .= ' AND a.finalitzat = 0 ';
    
    	$strQuery .= ' GROUP BY a.id ORDER BY ' . $queryparams['sort'] . ' ' . $queryparams['direction'];
    
    	$query = $em->createQuery($strQuery);
    
    	if ($queryparams['filtre'] != '') $query->setParameter('filtre', '%'.$queryparams['filtre'].'%');
    
    	return $query;
    }
    
    protected function queryActivitatsEnCurs() {
    
    	$queryparams = array('sort' => 'a.descripcio', 'direction' => 'asc', 'filtre' => '', 'finalitzats' => false);
    	$query = $this->queryActivitats($queryparams);
    	
    	return $query->getResult();
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
    
    protected function rebutsCreatsAny($anyconsulta) {
    	$em = $this->getDoctrine()->getManager();
    	$facturacions = UtilsController::queryGetFacturacions($em, $anyconsulta);  // Ordenades per data facturacio DESC
    	 
    	foreach ($facturacions as $facturacio) if (count($facturacio->getRebutsActius()) > 0) return true;
    	 
    	return false; // Cap rebut creat aquest any
    }
    
    protected function infoSeccionsQuotes($facturacions) {
    	$em = $this->getDoctrine()->getManager();
    
    	$seccions = $em->getRepository('FomentGestioBundle:Seccio')
    		->findBy(array( 'databaixa' => null, 'semestral' => true ), array('ordre'=>'asc') );
    
    	$infoseccions = array();
    	foreach ($seccions as $seccio) {
    		$infoseccions[$seccio->getId()] = array('id' => $seccio->getId(), 
    												'domiciliada' => false,
    												'esEsborrable' => false,
    												'descripcio' => $seccio->getNom(),	
    												'infoRebuts' => Rebut::getArrayInfoRebuts());
    	}
    
    	$rebuts = array();
    	foreach ($facturacions as $facturacio) {
    		$rebuts = array_merge($rebuts, $facturacio->getRebuts()->getValues()); // Collection a Array
    	}

    	foreach ($rebuts as $rebut) {
    		$baixa = $rebut->anulat();
    		$cobrat = $rebut->cobrat();
    		
    		$seccionsRebut = array();
    		foreach ($rebut->getDetalls() as $d) {
    			$seccio = $d->getSeccio();
    			
    			if ($seccio != null) {
    				$increment = 1;  // Si un rebut té vàries quotes de la mateixa secció només incrementa una vegada el total de rebuts
    				if (isset($seccionsRebut[$seccio->getId()])) $increment = 0; 
    				
    				$seccionsRebut[$seccio->getId()] = true;
    				
    				//$baixa = ($rebut->getDatabaixa() != null || $d->getDatabaixa() != null);
    				$import = $d->getImport();
    				if ($baixa == false && $d->getDatabaixa() != null) {
    					// $import = 0; Detalls de baixa no contribueixen
    				} else {
    					
    					Rebut::addInforebutArray($infoseccions[$seccio->getId()]['infoRebuts'], $rebut->getTipuspagament(), $baixa, $cobrat, $import, $increment);
    				}
    			}
    		}
    	}
    
    	return $infoseccions;
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
    

    /**
     * Existeix facturació de l'any actual ? 
     */
    protected function queryGetFacturacioOberta($current) {
    	
    	$facturacio = null;
    	//$avui = new \DateTime();
    	$em = $this->getDoctrine()->getManager();
    	$facturacions = UtilsController::queryGetFacturacions($em, $current);  // Ordenades per data facturacio DESC
    	
    	// Mirar si cal crear una nova facturació. No hi ha cap per aquest any o la última està tancada (domiciliada)
    	//if (count($facturacions) == 0 || (count($facturacions) > 0 && $facturacions[0]->domiciliada())) {
    	if (count($facturacions) > 0) return $facturacions[0];
    	
    	return $facturacio;
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
    
    protected function queryIncidenciesSocisActiusSenseFoment() {
        // Socis actius que no són de la secció foment
        // Ordenats per nom soci
        
        $em = $this->getDoctrine()->getManager();
        
        $strQuery = 'SELECT s FROM Foment\GestioBundle\Entity\Soci s';
        $strQuery .= ' WHERE s.databaixa IS NULL ';
        $strQuery .= ' ORDER BY s.cognoms, s.nom ';
        
        $query = $em->createQuery($strQuery);
        
        $result = $query->getResult();
        
        $incidencies = array();
        foreach ($result as $soci) {
            $membre = $soci->getMembreBySeccioId(UtilsController::ID_FOMENT);
            if ($membre == null || $membre->baixa()) { // Pot tenir inscripcions cancel·lades però estar actiu
                $incidencies[] = array( 'soci'          => $soci->getId(),
                                        'nom'           => $soci->getNumNomCognoms(),
                                        'incidencia'    => 'Aquest soci no és membre de la secció foment'
                                );
            }
        }
        
        return $incidencies;
    }
    
    
    public function generarRebutActivitat($facturacio, $participacio, $numrebut) {
    	$em = $this->getDoctrine()->getManager();
    	
    	$existent = $participacio->getRebutParticipantFacturacio($facturacio, true);
    	if ($existent != null) return $existent;
    	
    	// Crear rebut per activitat
    	$import = 0;
    	if ($participacio->getPersona()->esSocivigent()) $import = $facturacio->getImportactivitat();
    	else $import = $facturacio->getImportactivitatnosoci();
    
    	$rebut = null;
    	if ($import > 0) {
    
    		$rebut = new Rebut($participacio->getPersona(), $facturacio->getDatafacturacio(), $numrebut, false);
    
    		$em->persist($rebut);
    
    		$rebutdetall = new RebutDetall($participacio, $rebut, $import);
    		$rebut->addDetall($rebutdetall);
    
    		$em->persist($rebutdetall);
    
    		$facturacio->addRebut($rebut);
    	}
    	return $rebut;
    }

    protected function generarRebutMembre($facturacio, $socipagarebut, $membre, &$numrebut, $anydades, $dataemissio, $fraccio) {
    	$em = $this->getDoctrine()->getManager();
    	$rebut = null;
    	$rebutdetall = null;
    	 
    	//$rebutexistent = $facturacio->getRebutPendentByPersonaDeutora($socipagarebut, $membre->getSeccio()->esGeneral(), $fraccio);
    	$rebutexistent = $socipagarebut->getRebutPendentFacturacio($facturacio, $membre->getSeccio()->esGeneral(), $fraccio);  // Rebuts facturacio soci pagador
    	
    	if ($rebutexistent == null) {
    		// Crear rebut nou
    		$rebut = new Rebut($socipagarebut, $dataemissio, $numrebut, true, false); // Semestral
    		$numrebut++;
    		$em->persist($rebut);
    		$facturacio->addRebut($rebut);
    	} else {
    		$rebut = $rebutexistent;
    	}
    
    	$rebutdetall = $this->generarRebutDetallMembre($membre, $rebut, $anydades, $fraccio);
    
    	if ($rebutdetall != null && $rebut->getImport() > 0) {
    	    $em->persist($rebutdetall);
    	    return $rebut;
    	}
    	
    	if ($rebutexistent == null) {
    		$rebut->detach();
    		$em->detach($rebut);
    		$numrebut--;
    	}
    	
    	return null;
    }
    
    
    /* Generar detall rebut per aquest membre  */
    protected function generarRebutDetallMembre($membre, $rebut, $any, $fraccio = 1) {
    	// Obtenir info soci: fraccionament, descompte, juvenil
    	$import = 0;
    	
    	$seccio = $membre->getSeccio();
    	$datainscripcio = $membre->getDatainscripcio();
    	$soci = $membre->getSoci();
    	$socirebut = $soci;
    	if ($seccio->getSemestral() && $socirebut->getSocirebut() != null) $socirebut = $socirebut->getSocirebut();	// El soci agrupa rebuts només quotes seccions semestrals
    	 
    	$diainici = 0;
    	if ($any == $datainscripcio->format('Y')) $diainici = $datainscripcio->format('z');     	// z 	The day of the year (starting from 0)
    	
    	/*$semestre = UtilsController::getSemestre($rebut->getDataemissio());
    	
    	$fraccionsemeses = $membre->getRebutDetallAny($any, true, false); // Amb baixes i sense ordre
    	$facturacions = $seccio->getFacturacions();  // 2 o 1
    	if ($seccio->esGeneral() && !$soci->getPagamentfraccionat()) $facturacions = 1;
    	 
    	$fraccionspendents = $facturacions - count($fraccionsemeses);
    	if ($fraccionspendents <= 0) return null;	// Rebuts emesos anteriorment*/
    	 
    	$quotaany = UtilsController::getServeis()->quotaSeccioAny($soci->esJuvenil(), $socirebut->getFamilianombrosa(),
    			$socirebut->getDescomptefamilia(),
    			$soci->getExempt(), $seccio,
    			$any, $diainici);
    	 
    	// Exemple. Sense fraccionar	General(100%) 80 + Secció(100%) 15 	=> 1er semestre
    	//								General(0%) 0 + Secció(0) 0 		=> 2n semestre
    	// Exemple. Fraccionat  		General(50%) 40 + Secció(100%) 15 	=> 1er semestre
    	//								General(50%) 40 + Secció(0) 0 		=> 2n semestre
    	
    	/*if ($seccio->getFraccionat() == true) {
    		
    		if ($fraccionspendents == 1 && $semestre != 2) $import = 0; // Encara no es poden genera la segona part dels rebuts fraccionats
	 		else $import = ( $quotaany / 2 ); // Quota sempre repartida entre els dos semestres
    	} else {  
    		if (!$seccio->esGeneral() || !$soci->getPagamentfraccionat()) { // El fraccionament es mira per soci, independent del grupfamiliar
    			$import = $quotaany;
    		} else {
		    	// General i soci fraccionat
    			if ($fraccionspendents == 1 && $semestre != 2) $percentfraccionament = 0; // Encara no es poden genera la segona part dels rebuts fraccionats
    			else {
			    	$percentfraccionament = ($fraccionspendents >= 1?UtilsController::PERCENT_FRA_GRAL_SEMESTRE_1:UtilsController::PERCENT_FRA_GRAL_SEMESTRE_2);  // 0.5 - 0.5
    			}
		    	 
		    	$import = ( $quotaany * $percentfraccionament );
    		}
    	}    	*/
    	
    	$import = $quotaany;
    	if ($seccio->esGeneral() && $socirebut->getPagamentfraccionat()) {
    		$percentfraccionament = ($fraccio == 1?UtilsController::PERCENT_FRA_GRAL_SEMESTRE_1:UtilsController::PERCENT_FRA_GRAL_SEMESTRE_2);  // 0.5 - 0.5
    		$import *= $percentfraccionament;
    	} 
    	
    	
    	if ($import <= 0) return null;
    	// Crear línia de rebut per quota de Secció segons periode
    	$rebutdetall = new RebutDetall($membre, $rebut, round($import, 2));
    	$rebut->addDetall($rebutdetall);
    	 
    	return $rebutdetall;
    }
    
    public function generarRebutSeccioNoSemestral($membre, $dataemissio, $numrebut) {
    	$em = $this->getDoctrine()->getManager();
    	
    	$anydades = ($dataemissio != null?$dataemissio->format('Y'):date('Y') );
    	
    	$import = $membre->getSeccio()->getQuotaAny($anydades, $membre->getSoci()->esJuvenil() );
    	
    	$rebut = null;
    	
    	if ($import <= 0) return null;  // => Per exemple petits somriures altres
    	
	    $rebut = new Rebut($membre->getSoci(), $dataemissio, $numrebut, true, true);
	    	
	    // Crear línia de rebut per quota de Secció segons periode
	    $rebutdetall = new RebutDetall($membre, $rebut, $import);
	    	 
	    if ($rebutdetall != null) {
	    	$rebut->addDetall($rebutdetall);
	    	$em->persist($rebut);
	    	$em->persist($rebutdetall);
	    }
    	
    	return $rebut;
    }
    
    
    protected function actualitzarSeccionsSoci($soci, $seccionsIds = array(), $notice = true) {
        
        $em = $this->getDoctrine()->getManager();
        
        $seccionsActualsIds = $soci->getSeccionsIds();
        foreach ($seccionsIds as $secid)  {
            if (!in_array($secid, $seccionsActualsIds)) {
                $seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($secid);
                // No pertany a la secció
                $this->inscriureMembre($seccio, $soci, date('Y'), $notice);
            } else {
                // Manté la secció
                $key = array_search($secid, $seccionsActualsIds);
                unset($seccionsActualsIds[$key]);
            }
        }
        foreach ($seccionsActualsIds as $secid)  {  // Per esborrar les que queden
            $seccio = $em->getRepository('FomentGestioBundle:Seccio')->find($secid);
            $this->esborrarMembre($seccio, $soci, date('Y'), $notice);
        }
    }
    
    protected function inscriureMembre($seccio, $noumembre, $anydades, $notice = true) {
        $em = $this->getDoctrine()->getManager();
        
        $membre = $seccio->getMembreBySociId($noumembre->getId());
        
        if ($membre != null) throw new \Exception('Aquest soci ja pertany a la Secció '.$seccio->getNom() );
        
        $membre = $seccio->addMembreSeccio($noumembre);
        
        $em->persist($membre);
        
        if ($anydades > date('Y')) {  // Inscripció futura, canviar data d'inscripció. No generar rebut, ja es generarà l'any vinent
            $membre->setDatainscripcio( \DateTime::createFromFormat('d/m/Y', '01/01/'.$anydades ) );
            
        } else {
            $numrebut = $this->getMaxRebutNumAnySeccio($anydades); // Max
            
            if ($seccio->getSemestral()) {
                // Mirar si cal crear una nova facturació. No hi ha cap per aquest any o la última està tancada (domiciliada)
                $facturacio = $this->queryGetFacturacioOberta($anydades);
                
                $strRebuts = "";
                if ($facturacio != null) {
                    
                    $socipagarebut = $noumembre->getSocirebut(); // Soci agrupa rebuts per pagar
                    
                    if ($socipagarebut == null) throw new \Exception('Cal indicar qui es farà càrrec dels rebuts '.($noumembre->getSexe()=='H'?'del soci ':'de la sòcia ').$noumembre->getNomCognoms() );
                    
                    $fraccio = 1;
                    if ($seccio->esGeneral() && $socipagarebut->getPagamentfraccionat()) {
                        $semestre = UtilsController::getSemestre($membre->getDatainscripcio());
                        if ($semestre == 2) $fraccio = 2; // Inscripció al segon semestre només proporcional 2n rebut
                    }
                    $dataemissio = $membre->getDatainscripcio();
                    $numrebutcurrent = $numrebut;
                    $rebut = $this->generarRebutMembre($facturacio, $socipagarebut, $membre, $numrebut, $anydades, $dataemissio, $fraccio);
                    
                    if ($rebut == null) throw new \Exception('No s\'ha pogut crear el rebut de Secció '.$seccio->getNom() );
                    
                    if ($numrebutcurrent == $numrebut) $strRebuts .= 'Quota afegida al rebut '. $rebut->getNumFormat() . '<br/>';
                    else  $strRebuts .= 'Nou rebut generat '. $rebut->getNumFormat() . '<br/>';
                    
                    if ($seccio->esGeneral() && $socipagarebut->getPagamentfraccionat() && $fraccio = 1) {
                        // Generar fracció 2n semestre
                        $fraccio = 2;
                        $dataemissio = UtilsController::getDataIniciEmissioSemestre2($anydades);
                        
                        $numrebutcurrent = $numrebut;
                        $rebut = $this->generarRebutMembre($facturacio, $socipagarebut, $membre, $numrebut, $anydades, $dataemissio, $fraccio);
                        
                        if ($rebut == null) throw new \Exception('No s\'ha pogut crear la fracció del rebut de Secció '.$seccio->getNom() );
                        
                        if ($numrebutcurrent == $numrebut) $strRebuts .= 'Quota fraccionada afegida al rebut '. $rebut->getNumFormat() . '<br/>';
                        else  $strRebuts .= 'Nou rebut fraccionat generat '. $rebut->getNumFormat() . '<br/>';
                    }
                }
                if ($notice) {
                    $this->get('session')->getFlashBag()->add('notice',	($noumembre->getSexe()=='H'?'En ':'Na ').$noumembre->getNomCognoms().' s\'ha inscrit correctament a la secció '.$seccio->getNom());
                    if ($strRebuts != "") {
                        $this->get('session')->getFlashBag()->add('notice',	$strRebuts);
                    }
                }
                
            } else {
                // Les seccions no semestrals sempre les paguen els propis socis per finestreta
                //$soci  = $noumembre->getSoci();
                
                // Crear tants rebuts com facturacions mensualment
                $dataemissio = clone $membre->getDatainscripcio();
                
                for($facturacio = 0; $facturacio < $seccio->getFacturacions(); $facturacio++) {
                    if ($this->generarRebutSeccioNoSemestral($membre, $dataemissio, $numrebut) != null) {
                        
                        $dataemissio = clone $dataemissio; // Totes les facturacions de cop, incrementar un mes
                        $dataemissio->add(new \DateInterval('P1M'));
                        
                        $numrebut++;
                    }
                }
            }
        }
    }
    
    protected function esborrarMembre($seccio, $esborrarmembre, $anydades, $notice = true) {
        $membre = $seccio->getMembreBySociId($esborrarmembre->getId());
        
        if ($membre == null) throw new \Exception('Aquest soci no pertany a la secció');
        
        
        if ($anydades > date('Y')) {  // Cancel·lació futura, canviar data de cancel·lació
            $membre->setDatacancelacio( \DateTime::createFromFormat('d/m/Y', '31/12/'.($anydades - 1) ) );
        } else {
            $membre->setDatacancelacio(new \DateTime());
        }
        $membre->setDatamodificacio(new \DateTime());
        
        $strRebuts = "";
        
        // Anul·lar rebuts vigents o futurs no cobrats
        $detallsrebuts = $membre->getRebutDetallTots();// Rebuts actius
        foreach ( $detallsrebuts as $detall ) {
            $rebut = $detall->getRebut();
            $iniciPeriodeActual =  \DateTime::createFromFormat('d/m/Y', '01/01/'. date('Y') );
            
            if ($seccio->getSemestral() == true) {
                if ($rebut != null && $rebut->getDataemissio() >= $iniciPeriodeActual) {
                    if ($rebut != null && $rebut->esEsborrable()) {
                        $detall->baixa();
                        $strRebuts = 'Quota del soci '.number_format($detall->getImport(),'2','.',',');
                        $strRebuts .= ' esborrada del rebut '. $rebut->getNumFormat() .'<br/>';
                    }
                    if ($rebut != null && !$rebut->esEsborrable()) {
                        $strRebuts = 'La quota del soci '.number_format($detall->getImport(),'2','.',',');
                        $strRebuts .= ' està inclosa al rebut '. $rebut->getNumFormat() . ' i no s\'ha esborrat. ';
                        $strRebuts .= UtilsController::getEstats($rebut->getEstat()) .'<br/>';
                    }
                }
            } else {
                if ($rebut != null) {
                    $detall->baixa();
                    $strRebuts .= ' Rebut '. $rebut->getNumFormat() .' anul·lat<br/>';
                }
            }
        }

        if ($notice) {
            if (count($esborrarmembre->getMembreDeSortedById( false )) == 0) {
                $quotaDelStr = ($esborrarmembre->getSexe()=='H'?'El soci ':'La sòcia ').$esborrarmembre->getNumSoci().'-'.$esborrarmembre->getNomCognoms() .' no pertany a cap secció';
                $this->get('session')->getFlashBag()->add('error',	$quotaDelStr );
            }
            
            $this->get('session')->getFlashBag()->add('notice',	($esborrarmembre->getSexe()=='H'?'En ':'Na ').$esborrarmembre->getNomCognoms().' ha estat baixa de la secció '.
                $membre->getSeccio()->getNom().' en data '. $membre->getDatacancelacio()->format('d/m/Y'));
            if ($strRebuts != "") {
                $this->get('session')->getFlashBag()->add('notice',	$strRebuts);
            }
        }
    }
    
    protected function getMorosos($queryparams) {
    	$em = $this->getDoctrine()->getManager();

    	if ($queryparams['anydades'] == '' || $queryparams['anydades'] < 2000) $anydades = date('Y');
    	else $anydades = $queryparams['anydades']; 
    	$ini = $anydades."-01-01";
    	$fi = $anydades."-12-31";
    	 
    	$strQuery = "SELECT r FROM Foment\GestioBundle\Entity\Rebut r JOIN r.detalls d ";
    	$strQuery .= " WHERE r.databaixa IS NULL AND d.databaixa IS NULL ";
    	$strQuery .= " AND r.dataemissio >= :ini AND r.dataemissio <= :fi ";
    	$strQuery .= " AND r.datapagament IS NULL ";
    
    	$query = $em->createQuery($strQuery)
    	->setParameter('ini', $ini)
    	->setParameter('fi', $fi);
    	 
    	$rebutsPendents = $query->getResult();
    	 
    	$morosos = array();
    	 
    	foreach ($rebutsPendents as $rebut) {
    		$socipagament = $rebut->getDeutor();
    
    		$socinom = $socipagament->getNomCognoms();
    
    		if ($queryparams['filtre'] == '' || ($queryparams['filtre'] != '' && stripos($socinom, $queryparams['filtre']) !== false)) {
    
    			if ($queryparams['tipus'] == UtilsController::OPTION_TOTS ||
    				($queryparams['tipus'] == UtilsController::TIPUS_SECCIO && $rebut->esSeccio()) ||
    				($queryparams['tipus'] == UtilsController::TIPUS_ACTIVITAT && $rebut->esActivitat())) {
    						 
    				if (isset($morosos[$socipagament->getId()])) {
    							 
    					$morosos[$socipagament->getId()]['rebuts'][] = $rebut;
    						 
    					$morosos[$socipagament->getId()]['deute'] += $rebut->getImport();
    							 
    					$minEmissioCurrent = $morosos[$socipagament->getId()]['mindataemissio'];
    					if ($rebut->getDataemissio()->format('Y-m-d') < $minEmissioCurrent->format('Y-m-d')) $morosos[$socipagament->getId()]['mindataemissio'] = $rebut->getDataemissio();
    							 
   					} else {
   						$morosos[$socipagament->getId()] = array('soci' => $socipagament, 'rebuts' => array( $rebut ),
							    								'deute' => $rebut->getImport(),
							    								'mindataemissio' => $rebut->getDataemissio() );
    				}
    			}
    		}
    	}
    	 
    	// sort 'soci.id' 'soci.numsoci' 'soci.nomcognoms' 'mindataemissio' 'deute'
    	 
    	uasort($morosos, function ($a, $b) use ($queryparams) {
    		if ($a === $b) return 0;
    
    		if (strtolower($queryparams['direction']) == 'asc') {
    			$primer = $a;
    			$segon = $b;
    		} else {
    			$primer = $b;
    			$segon = $a;
    		}
    
    		switch ($queryparams['sort']) {
    			case 'numsoci':
    					
    				return $primer['soci']->getNum() - $segon['soci']->getNum();
    				break;
    			case 'nomcognoms':
    					
    				return strcmp($primer['soci']->getCognoms().$primer['soci']->getNom(),$segon['soci']->getCognoms().$segon['soci']->getNom());
    				break;
    			case 'mindataemissio':
    					
    				return strcmp($primer['mindataemissio']->format('Y-m-d'),$segon['mindataemissio']->format('Y-m-d'));
    				break;
    			case 'deute':
    					
    				return floor(($primer['deute']*100) - ($segon['deute']*100));
    				break;
    			case 'deutecursos':
    					
    				return floor(($primer['deutecursos']*100) - ($segon['deutecursos']*100));
    				break;
    		}
    
    		return 0;
    	});
    		 
    	return $morosos;
    }
    
    
    public function getMaxRebutNumAnySeccio($any) {
    	return max($this->getMaxRebutNumAny($any, UtilsController::TIPUS_SECCIO), $this->getMaxRebutNumAny($any, UtilsController::TIPUS_SECCIO_NO_SEMESTRAL));
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
    	 
    	return $result + 1;
    }
    
    public function getMaxPagamentNumAny($any) {
    	if ($any < 2000) $any = date('Y');
    	$ini = $any."-01-01";
    	$fi = $any."-12-31";
    
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = "SELECT MAX(p.num) FROM Foment\GestioBundle\Entity\Pagament p
				 WHERE p.datapagament >= :ini AND p.datapagament <= :fi";
    
    	$query = $em->createQuery($strQuery)
	    	->setParameter('ini', $ini)
    		->setParameter('fi', $fi);
    	$result = $query->getSingleScalarResult();
    
    	if ($result == null) $result = 1;
    
    	return $result + 1;
    }
    
    public function getMaxApuntNumAny($any) {
    	if ($any < 2000) $any = date('Y');
    	$ini = $any."-01-01";
    	$fi = $any."-12-31";
    
    	$em = $this->getDoctrine()->getManager();
    
    	$strQuery = "SELECT MAX(a.num) FROM Foment\GestioBundle\Entity\Apunt a
				 WHERE a.dataapunt >= :ini AND a.dataapunt <= :fi";
    
    	$query = $em->createQuery($strQuery)
    	->setParameter('ini', $ini)
    	->setParameter('fi', $fi);
    	$result = $query->getSingleScalarResult();
    
    	if ($result == null) $result = 1;
    
    	return $result + 1;
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
    
    protected function queryProveidors($filtre = '', $sort = ' p.raosocial ASC ') {
    	$em = $this->getDoctrine()->getManager();
    	
    	$strQuery = 'SELECT p FROM Foment\GestioBundle\Entity\Proveidor p WHERE 1 = 1 ';
    	if ($filtre != "") $strQuery .= ' AND p.raosocial LIKE :filtre ';
    	$strQuery .= " ORDER BY " . $sort;
    	
    	$query = $em->createQuery($strQuery);
    	if ($filtre != "") $query->setParameter('filtre', '%'.$filtre.'%');
    	 
    	return $query;
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
    
    
    
    /** Obtenir anys camp Select */
    protected function getAnysSelectable() {
    	
    	$anysSelectable = $this->getAnysSelectableToNow();
    	if (!in_array( (date('Y')+1) , $anysSelectable)) $anysSelectable[(date('Y')+1)] = (date('Y')+1);    	
    	
	    return $anysSelectable;
    }
    
    /** Obtenir anys camp Select des de 2014 fins actual */
    protected function getAnysSelectableToNow() {
    
    	$anysSelectable = array();
    
    	// Inici any 2014
    	for ($any = UtilsController::ANY_INICI_APP; $any <= date('Y'); $any++) {
    		$anysSelectable[$any] = $any;
    	}
    
    	return $anysSelectable;
    }
    
    
    /** Obtenir cursos camp Select fins curs proper */
    protected function getCursosSelectable() {
    	 
   		$anyInici = UtilsController::ANY_INICI_APP;
    	$anyFinal = date('Y');
    	
    	$cursosSelectable = array();
    	 
    	for($i = $anyInici; $i <= $anyFinal; $i++)  {
    		$cursosSelectable[$i] = $i.'-'.($i+1);    		
    	}
    	 
    	return $cursosSelectable;
    }
    
    
    
    
    
    protected function buildAndSendMail($subject, $from, $tomails, $innerbody, $bccmails = array(), $width = 600) {
    	
    	if ($this->get('kernel')->getEnvironment() != 'prod') {
    		$tomails = array( $this->container->getParameter('fomentgestio.emails.test') );  // Entorns de test
    	}
    
    	$message = \Swift_Message::newInstance()
	    	->setSubject($subject)
	    	->setFrom($from)
	    	->setBcc($bccmails)
	    	->setTo($tomails);
    
    	// Afegir signatura
    	$logosrc = $message->embed(\Swift_Image::fromPath('imatges/logo-foment-mail.png'));
    	
    	$footer = "";
    	$footer .= "<div style=\"text-align:left\"><img src=".$logosrc." alt=\"Foment Martinenc\" width=\"86\" height=\"96\" /></div>";
    	$footer .= "<font color=\"#888888\"><div><span style=\"text-align:left;font-size:x-small\">";
    	$footer .= "Foment Martinenc - Provença 591-593 - 08026 Barcelona - ";
    	$footer .= "<a href=\"tel:93.435.73.76\" target=\"_blank\" value=\"934357376\">93.435.73.76</a>";
    	$footer .= "- <a href=\"http://www.fomentmartinenc.org\" target=\"_blank\">www.fomentmartinenc.org</a>";
    	$footer .= "</span><br></div></font>";
    	
    	$body = "<html style='font-family: Helvetica,Arial,sans-serif;'><head></head><body>";
    	$body .= "<table align='left' border='0' cellpadding='0' cellspacing='0' width='".$width."' style='border-collapse: collapse;'>";
    	$body .= "<tr><td style='padding: 10px 0 10px 0;'>".$innerbody."</td></tr>";
    	$body .= "<tr><td style='padding: 10px 0 10px 0;'>".$footer."</td></tr></table></body></html>";
    	
    	$message->setBody($body, 'text/html');
    
    	$this->get('mailer')->send($message);
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
    protected function cmpdatanaixementasc($a, $b) {
    	if ($a->getDatanaixement() == null) return -1;
    	if ($b->getDatanaixement() == null) return 1;
    	return $this->cmpgeneriques($a->getDatanaixement()->format('Ymd'), $b->getDatanaixement()->format('Ymd'), true);   
   	}
    protected function cmpdatanaixementdesc($a, $b) {
    	if ($a->getDatanaixement() == null) return 1;
    	if ($b->getDatanaixement() == null) return -1;
    	return $this->cmpgeneriques($a->getDatanaixement()->format('Ymd'), $b->getDatanaixement()->format('Ymd'), false);   
    }
    
    
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
	    		/*if ($queryparams['sort'] == $v && $queryparams['direction'] == 'asc') return ( $a[$k] < $b[$k] )? -1:1;
	    		if ($queryparams['sort'] == $v && $queryparams['direction'] == 'desc') return ( $a[$k] > $b[$k] )? -1:1;*/
	    		if ($queryparams['sort'] == $v && $queryparams['direction'] == 'asc') return  strcmp($a[$k], $b[$k]);
	    		if ($queryparams['sort'] == $v && $queryparams['direction'] == 'desc') return strcmp($b[$k], $a[$k]);
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
