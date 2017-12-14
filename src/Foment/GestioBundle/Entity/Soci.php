<?php 
// src/Foment/GestioBundle/Entity/Soci.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
 * @ORM\Table(name="socis",uniqueConstraints={@ORM\UniqueConstraint(name="numsoci_idx", columns={"num"})})
 */

// When generetes inheritance entity comment extends ...
// http://docs.doctrine-project.org/en/latest/reference/faq.html#can-i-use-inheritance-with-doctrine-2
class Soci extends Persona
{
	const MAX_AVALADORS = 2;
	
	/**
	 * @ORM\Id
     * @ORM\OneToOne(targetEntity="Persona", cascade={"persist", "remove"}) 
     * @ORM\JoinColumn(name="id", referencedColumnName="id") 
     */
    protected $id;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(
     * 		message = "Cal indicar el número de soci."
     * )
     * @Assert\Type(type="numeric", message="Número de soci incorrecte.")
     * @Assert\GreaterThan(value="0", message="Número de soci incorrecte.")
     */
    protected $num;
    
    /**
     * @ORM\Column(type="smallint", nullable=false) 
     * @Assert\NotBlank(
     * 		message = "Cal indicar el tipus de soci."
     * )
     */
    protected $tipus;  
    
    /**
     * @ORM\ManyToOne(targetEntity="Compte", inversedBy="soci", cascade={"persist"})
	 * @ORM\JoinColumn(name="compte", referencedColumnName="id")
     * 
     **/
	protected $compte; // FK taula comptes, si NULL pagament per finestreta i $this == $socirebut

	/**
	 * @ORM\ManyToOne(targetEntity="Soci", inversedBy="socisacarrec")
	 * @ORM\JoinColumn(name="socirebut", referencedColumnName="id")
	 **/
	protected $socirebut; // self-referencing owning side
	
	/**
	 * @ORM\OneToMany(targetEntity="Soci", mappedBy="socirebut")
	 */
	protected $socisacarrec; // self-referencing
	
	/**
	 * @ORM\Column(type="boolean", nullable=false)
	 */
	protected $vistiplau;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $datavistiplau;
	
	/**
	 * @ORM\Column(type="date", nullable=false)
	 */
	protected $dataalta;
	
	/**
	 * @ORM\Column(type="smallint", nullable=false)
	 * @Assert\NotBlank(
	 * 		message = "Cal indicar el tipus de pagament."
	 * )
	 */
	protected $tipuspagament;
	
	/**
	 * @ORM\ManyToMany(targetEntity="Soci", mappedBy="avaladors")
	 */
	protected $avalats;  // Socis als quals avala
	
	/**
	 * @ORM\ManyToMany(targetEntity="Soci", inversedBy="avalats", cascade={"remove", "persist"})
	 * @ORM\JoinTable(name="avals",
	 *      joinColumns={@ORM\JoinColumn(name="soci", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="avalador", referencedColumnName="id")}
	 *      )
	 */
    protected $avaladors;   // Socis que l'avalen
    
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $descomptefamilia;
    
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $pagamentfraccionat;
    
    /**
	 * @ORM\Column(type="integer", nullable=false)
	 * @Assert\NotBlank(
     * 		message = "Cal indicar el percentatge exempt."
     * )
     * @Assert\Type(type="integer", message="Percentatge incorrecte.")
     * @Assert\GreaterThanOrEqual(value="0", message="Percentatge incorrecte. Ha de ser major a 0")
     * @Assert\LessThanOrEqual(value="100", message="Número de soci incorrecte. Ha de ser menor a 100 ")
	 */
    protected $exempt;
    
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $quotajuvenil;  // Adults que paguen quota juvenil. P.e. disminuits. Menors de 16 desactivat (Per evitar es propagui)
    
    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $familianombrosa;  // És familia nombrosa Terra-Nova
    
    /**
     * @ORM\Column(type="boolean")
     */
    protected $dretsimatge;
    
    /**
     * @ORM\Column(type="boolean")
     */
    protected $lopd;    // LOPD revisada?
    
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $databaixa;

     /**
     * @ORM\Column(type="smallint", nullable=true) 
     */
    protected $motiu;
    
    /**
     * @ORM\OneToMany(targetEntity="Membre", mappedBy="soci")
     */
    protected $membrede;
    
    /**
	 * @ORM\OneToOne(targetEntity="Imatge")
	 * @ORM\JoinColumn(name="foto", referencedColumnName="id")
	 */
	protected $foto;
	
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->avalats = new \Doctrine\Common\Collections\ArrayCollection();
        $this->avaladors = new \Doctrine\Common\Collections\ArrayCollection();
        $this->membrede = new \Doctrine\Common\Collections\ArrayCollection();
        $this->socisacarrec = new \Doctrine\Common\Collections\ArrayCollection();
        // Valors per defecte
        $this->num = 0;
        $this->vistiplau = true;
        $this->descomptefamilia = false;
        $this->familianombrosa = false;
        $this->pagamentfraccionat = false;
        $this->dretsimatge = true;
        $this->lopd = false;
        $this->tipus = 1; // Numerari
        $this->dataalta = new \DateTime('today');
        $this->socirebut = $this; // Inicialment  el propi soci a càrrec dels rebuts
        //$this->socirebut = null;
        $this->tipuspagament = UtilsController::INDEX_DOMICILIACIO; // Numerari
        $this->compte = null;
        $this->exempt = 0;
        $this->quotajuvenil = false;
        
        // Hack per permetre múltiples constructors
        $a = func_get_args();
        $i = func_num_args();
        
        if ($i == 1) {
        	if (is_array($a[0]) and method_exists($this,$f='__constructArraySoci')) {
        		call_user_func_array(array($this,$f),$a);
        	}
        	if ($a[0] instanceof Persona and method_exists($this,$f='__constructPersonaSoci')) {
        		call_user_func_array(array($this,$f),$a);
        	}
        } else {
        	parent::__construct();
        }
    }
    
    /**
     * Constructor
     *
     * @param array $data
     */
    public function __constructArraySoci($data)
    {
    	parent::__construct($data);
    }
    
    
    /**
     * Constructor from Persona
     * 
     * @param \Foment\GestioBundle\Entity\Persona $persona
     */
    public function __constructPersonaSoci($persona)
    {
    	parent::__construct($persona);
    	//$this->id = $persona;
    }
    
    /**
     * És soci? 
     *
     * @return boolean
     */
    public function esSoci() { return true; }
    
    /**
     * És soci vigent?
     *
     * @return boolean
     */
    public function esSociVigent() { return !$this->esBaixa(); }
    
    
    /**
     * És baixa? 
     *
     * @return boolean
     */
    public function esBaixa() { return $this->databaixa != null; }
    
    /**
     * Estat: S-Soci, B-Soci de baixa, N-No soci
     *
     * @return boolean
     */
    public function estat() { 
    	if ($this->databaixa == null) return UtilsController::SOCI_VIGENT;
    	else return UtilsController::SOCI_BAIXA; 
    }
    
    /**
     * Get Numero de soci Format 9.999
     *
     * @return string
     */
    public function getNumSoci()
    {
    	if ($this->num == null) return '';
    	return number_format($this->num, 0, ' ', '.'); 
    }
    
    /**
     * Get num + nom + cognoms
     *
     * @return string
     */
    public function getNumNomCognoms()
    {
    	return $this->getNumSoci()."-".parent::getNomCognoms();
    }
    
    /**
     * Get temps soci
     *
     * @return \DateInterval
     */
    public function getAntiguitat()
    {
    	$max = new \DateTime('today');
    	if ($this->databaixa != null) $max = $this->databaixa;
    	
    	$antiguitat = $max->diff($this->dataalta);
    	return $antiguitat;
    }
    
    /**
     * Get temps soci format
     *
     * @return string
     */
    public function getAntiguitatFormat()
    {
    	
    	$datediff = $this->getAntiguitat();
    	
    	$antiguitatStr = '';
    	$anys = $datediff->format('%y');
    	if ($anys == 1) $antiguitatStr .= '1 any ';
    	if ($anys > 1) $antiguitatStr .= $anys .' anys ';
    	
    	$mesos = $datediff->format('%m');
    	if ($mesos == 1) $antiguitatStr .= '1 mes ';
    	if ($mesos > 1) $antiguitatStr .= $mesos .' mesos ';
    	
    	$dies = $datediff->format('%d');
    	if ($dies == 1) $antiguitatStr .= '1 dia ';
    	if ($dies > 1) $antiguitatStr .= $dies .' dies ';

    	if ($antiguitatStr == '' && $dies == 0) $antiguitatStr = 'avui';
    	
    	return $antiguitatStr;
    }
    
    /**
     * Soci paga per finestreta?
     *
     * @return boolean
     */
    // Sobreescriptura
    public function esPagamentFinestreta()
    {
    	return $this->getTipuspagament() == UtilsController::INDEX_FINESTRETA;
    }
    
    /**
     * Soci és el deudor dels rebuts del grup?
     *
     * @return boolean
     */
    // Sobreescriptura
    public function esDeudorDelGrup()
    {
    	//return $this === $this->getSocirebut();
    	return $this->socirebut == null ||  $this === $this->getSocirebut();
    }
     
    /**
     * Get rebuts on el soci aparegui, no només per ser el deutor. Per defecte no inclou anul·lacions
     *
     * @return array
     */
    // sobreescriptura
    public function getRebutsPersona($baixa = false)
    {
    	return array_merge(parent::getRebutsPersona(), $this->getRebutsSeccions($baixa));
    }
    
    /**
     * Get rebuts de seccions on el soci aparegui. Per defecte no inclou anul·lacions
     *
     * @return array
     */
    public function getRebutsSeccions($baixa = false)
    {
        $rebuts = array();
        foreach ($this->membrede as $membre)  {
            if ($baixa == true || ($baixa == false && $membre->getDatacancelacio() == null)) {
                $rebuts = array_merge($rebuts, $membre->getRebutsMembre($baixa));
            }
        }
        return $rebuts;
    }
    
    /**
     * Get rebut pendent de cobrar del soci a una facturació. En cas de que sigui per la secció Foment intenta cercar rebut semestre correcte
     * 
     * @param \Foment\GestioBundle\Entity\Facturacio $facturacio
     * @param boolean $general
     * @param integer $fraccio
     *
     * @return array
     */
    public function getRebutPendentFacturacio(\Foment\GestioBundle\Entity\Facturacio $facturacio, $general, $fraccio)
    {
        $candidat = null;
        $dataemissio2 = UtilsController::getDataIniciEmissioSemestre2($facturacio->getDatafacturacio()->format('Y'));
        foreach ($this->getRebutsPersona() as $rebut)  {
            if ($rebut->getFacturacio()->getId() == $facturacio->getId() && !$rebut->cobrat() && !$rebut->anulat()) {
                
                if (!$general) return $rebut;  // Seccions no generals només 1 fracció
                
                $candidat = $rebut;
                if ($fraccio == 1) {
                    if ($rebut->getDataemissio()->format('Y-m-d') < $dataemissio2->format('Y-m-d')) return $rebut;
                } else {
                    if ($rebut->getDataemissio()->format('Y-m-d') >= $dataemissio2->format('Y-m-d')) return $rebut;
                }
            }
            
        }
        return $candidat;
    }
    
    
    /**
     * Get quota del soci per la data $current (totes les seccions actives per aquesta data)
     * Si semestre == 0 => calcula quota tot l'any
     *
     * @return boolean
     */
    public function getQuotaAnual($current = null)
    {
        if ($current == null) $current = new \DateTime('today');
        $total = 0;
    	//$arr = array();
    	foreach ($this->membrede as $membre) {
    	    if ($membre->esMembreActiuPeriode($current, $current) && !$membre->esMembreBaixaPeriode($current, $current) ) {
    	        $quota = $membre->getQuotaAny($current->format('Y'));
    			$total += $quota;
    		}
    	}
    	return $total;
    }
    
    /**
     * Get infoSoci, sobreescrita
     *
     * @return string
     */
    public function getInfoSoci()
    {
    	if ($this->databaixa != null) return parent::getInfoSoci();
    	
    	if ($this->vistiplau == false) return 'pendent de vist i plau';
    	
    	return $this->getAntiguitatFormat();
    }
    
    /**
     * És juvenil? (compleix 18 anys o menys l'any en curs)
     *
     * @return boolean
     */
    public function esJuvenil() { 
    	if ($this->quotajuvenil == true) return true;
    	
    	if ($this->datanaixement == null) return false;
    	$anyLimit = $this->datanaixement->format('Y') + UtilsController::EDAT_ANYS_LIMIT_JUVENIL;
    	return ($anyLimit >= date('Y'));
    }
    
    
    /**
     * Get csvRow, sobreescrita
     *
     * @return string
     */
    public function getCsvRow($cc = false)
    {
    	// Veure UtilsController::getCSVHeader_Persones();
    	$seccions = $this->getLlistaSeccions(', ');
    	if ($seccions != '') {
    		$seccions = substr($seccions, 0, -2);
    		$seccions = str_replace(";", ",", $seccions);
    	}
    	
    	$row = '';
    	$row .= '"'.$this->id.'";"Si";"'.$this->num.'";"'.$this->dataalta->format('Y-m-d').'";"'.$seccions.'";'.$this->getCsvRowCommon();
    	if ($cc) {
    		if ($this->compte != null) {
    			$row .= ';"'.$this->compte->getCompteFormat().'"';
    			$row .= ';"'.$this->compte->getTitular().'";';
    		} else {
    			$row .= ';"";"";';
    		}
    	}
    	$row .= '"'.($this->vistiplau == true?'Si':'No').'";';
    	$row .= '"'.($this->databaixa == null?'':$this->databaixa->format('Y-m-d')).'"';
    	
    	//return htmlentities($row, ENT_NOQUOTES, "UTF-8");
    	return $row;
    }
    
    
    /**
     * Get infoBaixa
     *
     * @return string
     */
    public function getInfoBaixa()
    {
    	if ($this->databaixa == null) return ''; 
    	
    	return $this->databaixa->format('Y') .'-'. self::$motiusbaixa[$this->motiu];
    }
    
    
    /**
     * Get noms membres del grup moròs. Per a persona no socia només ell mateix
     *
     * @return String
     */
    public function getDeuteGrup()
    {
    	$grup = '';
    	foreach ($this->socisacarrec as $acarrec) {
    		if (!$acarrec->esBaixa()) $grup .= $acarrec->getNomCognoms().', ';
    	}
    	
    	return $grup;
    }
    
    /**
     * Returns membre with Seccio identified by $id or null
     *
     * @param integer $id
     * @return \Foment\GestioBundle\Entity\Membre
     */
    public function getMembreBySeccioId($id, $baixa = false) {
    	foreach ($this->membrede as $membre)  {
    	    if ($membre->getSeccio()->getId() == $id && ($baixa || !$membre->baixa())) return $membre;
    	}
    	return null;
    }
    
    /**
     * Change membre with $seccio per membrejunta
     */
    /*public function updateMembreJuntaBySeccio(\Foment\GestioBundle\Entity\Seccio $seccio, \Foment\GestioBundle\Entity\Junta $membrejunta)
    {
    	$current = $this->getMembreBySeccioId($seccio->getId());
    	 
    	$this->removeMembrede($current); 
    	 
    	$this->addMembrede($membrejunta);
    }*/
    
    
    /**
     * Get inscripcions sorted by seccio id
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMembreDeSortedById($cancelats = true)
    {
    	$arr = array();
    	foreach ($this->membrede as $membre) {
    		if ($cancelats == true || 
    			($cancelats == false && $membre->getDatacancelacio() == null)) $arr[] = $membre;
    	}
    
    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getSeccio()->getId() < $b->getSeccio()->getId())? -1:1;
    	});
    		 
    	return $arr;
    }
    
    
    /**
     * Get llistaSeccions no cancelades
     *
     * @return string
     */
    public function getLlistaSeccions($separator = PHP_EOL)
    {
    	$list = '';
    	
    	$membres = $this->getMembreDeSortedById( false );
    	
    	foreach ($membres as $m) {
    	    $list .=  $m->getSeccio()->getNom(). $separator;
    	}
    	
    	return $list;
    }
    
    /**
     * Get id's de les seccions no cancelades on participa la persona
     *
     * @return string
     */
    public function getSeccionsIds()
    {
    	$seccions_ids = array();
    	foreach ($this->membrede as $membre)  {
    		if ($membre->getDatacancelacio() == null)
    			$seccions_ids[] = $membre->getSeccio()->getId();
    	}
    	 
    	return $seccions_ids;
    }
    
    /**
     * Set id
     *
     * @param \Foment\GestioBundle\Entity\Persona $id
     * @return Soci
     */
    public function setId(\Foment\GestioBundle\Entity\Persona $id)
    {
    	$this->id = $id;
    
    	return $this;
    }
    
    /**
     * Get id
     *
     * @return \Foment\GestioBundle\Entity\Persona
     */
    public function getId()
    {
    	//parent::getId();
    	return $this->id;
    }
    
    
    /**
     * Set num
     *
     * @param integer $num
     * @return Soci
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
     * Set tipus
     *
     * @param integer $tipus
     * @return Soci
     */
    public function setTipus($tipus)
    {
        $this->tipus = $tipus;

        return $this;
    }

    /**
     * Get tipus
     *
     * @return integer
     */
    public function getTipus()
    {
    	return $this->tipus;
    }
    
    /**
     * Set tipuspagament
     *
     * @param integer $tipuspagament
     * @return Soci
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
     * Set dataalta
     *
     * @param \DateTime $dataalta
     * @return Soci
     */
    public function setDataalta($dataalta)
    {
        $this->dataalta = $dataalta;

        return $this;
    }

    /**
     * Get dataalta
     *
     * @return \DateTime 
     */
    public function getDataalta()
    {
        return $this->dataalta;
    }

    /**
     * Set databaixa
     *
     * @param \DateTime $databaixa
     * @return Soci
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
     * Set motiu
     *
     * @param integer $motiu
     * @return Soci
     */
    public function setMotiu($motiu)
    {
        $this->motiu = $motiu;

        return $this;
    }

    /**
     * Get motiu
     *
     * @return integer 
     */
    public function getMotiu()
    {
        return $this->motiu;
    }

    /**
     * Set compte
     *
     * @param \Foment\GestioBundle\Entity\Compte $compte
     * @return Soci
     */
    public function setCompte(\Foment\GestioBundle\Entity\Compte $compte = null)
    {
        $this->compte = $compte;

        return $this;
    }

    /**
     * Get compte
     *
     * @return \Foment\GestioBundle\Entity\Compte 
     */
    public function getCompte()
    {
        return $this->compte;
    }

    /**
     * Set socirebut
     *
     * @param \Foment\GestioBundle\Entity\Soci $socirebut
     * @return Soci
     */
    public function setSocirebut(\Foment\GestioBundle\Entity\Soci $socirebut = null)
    {
    	$this->socirebut = $socirebut;
    
    	return $this;
    }
    
    /**
     * Get socirebut
     *
     * @return \Foment\GestioBundle\Entity\Soci
     */
    public function getSocirebut()
    {
    	return $this->socirebut;
    }
    
    
    /**
     * Add avalats
     *
     * @param \Foment\GestioBundle\Entity\Soci $avalats
     * @return Soci
     */
    public function addAvalat(\Foment\GestioBundle\Entity\Soci $avalats)
    {
    	$this->avalats->add($avalats);
    	//$this->avalats[] = $avalats;

        return $this;
    }

    /**
     * Remove avalats
     *
     * @param \Foment\GestioBundle\Entity\Soci $avalats
     */
    public function removeAvalat(\Foment\GestioBundle\Entity\Soci $avalats)
    {
        $this->avalats->removeElement($avalats);
    }

    /**
     * Get avalats
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAvalats()
    {
        return $this->avalats;
    }

    /**
     * Add avaladors
     *
     * @param \Foment\GestioBundle\Entity\Soci $avaladors
     * @return Soci
     */
    public function addAvalador(\Foment\GestioBundle\Entity\Soci $avaladors)
    {
    	$this->avaladors->add($avaladors);
    	//$this->avaladors[] = $avaladors;

        return $this;
    }

    
    /**
     * Get altres socis a càrrec
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSocisDepenents()
    {
    	$depenents = array();
    	foreach ($this->socisacarrec as $depenent) {
    		if ($depenent->getId() != $this->id && !$depenent->esBaixa()) $depenents[] = $depenent;
    	}
    	return $depenents;
    }
    
    
    /**
     * Remove avaladors
     *
     * @param \Foment\GestioBundle\Entity\Soci $avaladors
     */
    public function removeAvalador(\Foment\GestioBundle\Entity\Soci $avaladors)
    {
        $this->avaladors->removeElement($avaladors);
    }

    /**
     * Get avaladors
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAvaladors()
    {
        return $this->avaladors;
    }

    /**
     * Add membrede
     *
     * @param \Foment\GestioBundle\Entity\Membre $membrede
     * @return Soci
     */
    public function addMembrede(\Foment\GestioBundle\Entity\Membre $membrede)
    {
    	$this->membrede->add($membrede);
    	//$this->membrede[] = $membrede;

        return $this;
    }

    /**
     * Remove membrede
     *
     * @param \Foment\GestioBundle\Entity\Membre $membrede
     */
    public function removeMembrede(\Foment\GestioBundle\Entity\Membre $membrede)
    {
        $this->membrede->removeElement($membrede);
    }

    /**
     * Get membrede
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMembrede()
    {
        return $this->membrede;
    }
    
    /**
     * Add socisacarrec
     *
     * @param \Foment\GestioBundle\Entity\Soci $socisacarrec
     * @return Soci
     */
    public function addSocisacarrec(\Foment\GestioBundle\Entity\Soci $sociacarrec)
    {
    	$this->socisacarrec->add($sociacarrec);
    	//$this->socisacarrec[] = $socisacarrec;
    
    	return $this;
    }
    
    /**
     * Remove socisacarrec
     *
     * @param \Foment\GestioBundle\Entity\Membre $socisacarrec
     */
    public function removeSocisacarrec(\Foment\GestioBundle\Entity\Soci $sociacarrec)
    {
    	$this->socisacarrec->removeElement($sociacarrec);
    }
    
    /**
     * Get socisacarrec
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSocisacarrec()
    {
    	return $this->socisacarrec;
    }
    
    
    
    /**
     * Set dretsimatge
     *
     * @param boolean $dretsimatge
     * @return Soci
     */
    public function setDretsimatge($dretsimatge)
    {
        $this->dretsimatge = $dretsimatge;
        
        return $this;
    }
    
    /**
     * Get dretsimatge
     *
     * @return boolean
     */
    public function getDretsimatge()
    {
        return $this->dretsimatge;
    }
    
    /**
     * Set lopd
     *
     * @param boolean $lopd
     * @return Soci
     */
    public function setLopd($lopd)
    {
        $this->lopd = $lopd;
        
        return $this;
    }
    
    /**
     * Get lopd
     *
     * @return boolean
     */
    public function getLopd()
    {
        return $this->lopd;
    }
    
    /**
     * Set foto
     *
     * @param \Foment\GestioBundle\Entity\Imatge $imatge
     * @return Imatge
     */
    public function setFoto(\Foment\GestioBundle\Entity\Imatge $foto = null)
    {
    	$this->foto = $foto;
    }
    
    /**
     * Get foto
     *
     * @return \Foment\GestioBundle\Entity\Imatge
     */
    public function getFoto()
    {
    	return $this->foto;
    }
    
   

    /**
     * Set vistiplau
     *
     * @param boolean $vistiplau
     * @return Soci
     */
    public function setVistiplau($vistiplau)
    {
        $this->vistiplau = $vistiplau;

        return $this;
    }

    /**
     * Get vistiplau
     *
     * @return boolean 
     */
    public function getVistiplau()
    {
        return $this->vistiplau;
    }

    /**
     * Set datavistiplau
     *
     * @param \DateTime $datavistiplau
     * @return Soci
     */
    public function setDatavistiplau($datavistiplau)
    {
        $this->datavistiplau = $datavistiplau;

        return $this;
    }

    /**
     * Get datavistiplau
     *
     * @return \DateTime 
     */
    public function getDatavistiplau()
    {
        return $this->datavistiplau;
    }
    
    /**
     * Set descomptefamilia
     *
     * @param boolean $descomptefamilia
     * @return Soci
     */
    public function setDescomptefamilia($descomptefamilia)
    {
        $this->descomptefamilia = $descomptefamilia;

        return $this;
    }

    /**
     * Get descomptefamilia
     *
     * @return boolean 
     */
    public function getDescomptefamilia()
    {
        return $this->descomptefamilia;
    }

    /**
     * Set pagamentfraccionat
     *
     * @param boolean $pagamentfraccionat
     * @return Soci
     */
    public function setPagamentfraccionat($pagamentfraccionat)
    {
    	$this->pagamentfraccionat = $pagamentfraccionat;

        return $this;
    }

    /**
     * Get pagamentfraccionat
     *
     * @return boolean 
     */
    public function getPagamentfraccionat()
    {
        return $this->pagamentfraccionat;
    }
    
    /**
     * Set exempt
     *
     * @param integer $exempt
     * @return Soci
     */
    public function setExempt($exempt)
    {
    	$this->exempt = $exempt;
    
    	return $this;
    }
    
    /**
     * Get exempt
     *
     * @return integer
     */
    public function getExempt()
    {
    	return $this->exempt;
    }
    
    
    /**
     * Set quotajuvenil
     *
     * @param boolean $quotajuvenil
     * @return Soci
     */
    public function setQuotajuvenil($quotajuvenil)
    {
    	$this->quotajuvenil = $quotajuvenil;
    
    	return $this;
    }
    
    /**
     * Get quotajuvenil
     *
     * @return boolean
     */
    public function getQuotajuvenil()
    {
    	return $this->quotajuvenil;
    }
    
    /**
     * Set familianombrosa
     *
     * @param boolean $familianombrosa
     * @return Soci
     */
    public function setFamilianombrosa($familianombrosa)
    {
    	$this->familianombrosa = $familianombrosa;
    
    	return $this;
    }
    
    /**
     * Get familianombrosa
     *
     * @return boolean
     */
    public function getFamilianombrosa()
    {
    	return $this->familianombrosa;
    }
}
