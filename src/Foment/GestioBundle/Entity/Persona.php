<?php 
// src/Foment/GestioBundle/Entity/Persona.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="rol", type="string", length=1)
 * @ORM\DiscriminatorMap({"P" = "Persona", "S" = "Soci"}) 
 * @ORM\Table(name="persones")
 */
class Persona
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=30, nullable=false)
     * @Assert\NotBlank(
     * 	message = "Cal indicar el nom."
     * )
     */
    protected $nom;

    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @Assert\NotBlank(
     * 	message = "Cal indicar els cognoms."
     * )
     */
    protected $cognoms;
    
    /**
     * @ORM\Column(type="string", length=1, nullable=false)
     * @Assert\NotBlank(
     * 	message = "Cal indicar el sexe."
     * )
     * 
     * 'H' o 'D' 
     */
    protected $sexe;
    
    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Type(
     * 		type="\DateTime",
     *  	message = "Format incorrecte."
     * )
     */
    protected $datanaixement;
    
    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $llocnaixement;
    
    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    protected $dni;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type(type="integer", message="Format incorrecte.")
     * @Assert\Length(
     *      min = "9",
     *      max = "9",
     *      exactMessage = "Format incorrecte."
     * )
     */
    protected $telffix;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type(type="integer", message="Format incorrecte.")
     * @Assert\Length(
     *      min = "9",
     *      max = "9",
     *      exactMessage = "Format incorrecte."
     * )
     */
    protected $telfmobil;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $notacontacte;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Assert\Email(
     *     message = "Adreça de correu incorrecte."
     * )
     */
    protected $correu;
    
    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $adreca;  
    
    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    protected $cp;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $poblacio;
    
    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $provincia;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $observacions;
    
    /**
     * @ORM\OneToMany(targetEntity="Rebut", mappedBy="deutor")
     */
    protected $rebuts;
    
    /**
     * @ORM\OneToMany(targetEntity="Participant", mappedBy="persona")
     */
    protected $participacions;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $dataentrada;
    
    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $datamodificacio;
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	$this->id = 0;
    	$this->dataentrada = new \DateTime();
    	$this->datamodificacio = new \DateTime();
    	//$this->vistiplau = false;
        $this->participacions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->rebuts = new \Doctrine\Common\Collections\ArrayCollection();
        //$this->sexe = "H";
        
        // Hack per permetre múltiples constructors
        $a = func_get_args();
        $i = func_num_args();
        if (method_exists($this,$f='__construct'.$i)) {
        	call_user_func_array(array($this,$f),$a);
        }
        
        if ($i == 1) {
        	if (is_array($a[0]) and method_exists($this,$f='__constructArray')) {
        		call_user_func_array(array($this,$f),$a);
        	}
        	if ($a[0] instanceof Persona and method_exists($this,$f='__constructPersona')) {
        		call_user_func_array(array($this,$f),$a);
        	}
        }
    }

    
    /**
     * Constructor
     * 
     * @param array $data
     */
    public function __constructArray($data)
    {
    	if (isset($data['nom']) && $data['nom'] != '') $this->setNom($data['nom']);
    	if (isset($data['cognoms']) && $data['cognoms'] != '') $this->setCognoms($data['cognoms']);
    	if (isset($data['dni']) && $data['dni'] != '') $this->setDni($data['dni']);
    	if (isset($data['sexe']) && $data['sexe'] != '') $this->setSexe($data['sexe']);
    	if (isset($data['telffix']) && $data['telffix'] != '') $this->setTelffix($data['telffix']);
    	if (isset($data['telfmobil']) && $data['telfmobil'] != '') $this->setTelfmobil($data['telfmobil']);
    	
    	if (isset($data['correu']) && $data['correu'] != '') $this->setCorreu($data['correu']);
    	if (isset($data['datanaixement']) && $data['datanaixement'] != '')
    		$this->setDatanaixement(\DateTime::createFromFormat('d/m/Y', $data['datanaixement']));
    	if (isset($data['llocnaixement']) && $data['llocnaixement'] != '') $this->setLlocnaixement($data['llocnaixement']);    	 
    	 
    	if (isset($data['poblacio']) && $data['poblacio'] != '') $this->setPoblacio($data['poblacio']);
    	if (isset($data['provincia']) && $data['provincia'] != '') $this->setProvincia($data['provincia']);
    	if (isset($data['cp']) && $data['cp'] != '') $this->setCp($data['cp']);
    	if (isset($data['adreca']) && $data['adreca'] != '') $this->setAdreca($data['adreca']);
    	
    }
    
    /**
     * Constructor from Persona
     *
     * @param \Foment\GestioBundle\Entity\Persona $persona
     */
    public function __constructPersona($persona)
    {
    	//$this->id = $persona->getId();
    	
    	$this->setNom($persona->getNom());
    	$this->setCognoms($persona->getCognoms());
    	$this->setDni($persona->getDni());
    	$this->setSexe($persona->getSexe());
    	$this->setTelffix($persona->getTelffix());
    	$this->setTelfmobil($persona->getTelfmobil());
    
    	$this->setCorreu($persona->getCorreu());
    	$this->setDatanaixement($persona->getDatanaixement());
    	$this->setLlocnaixement($persona->getLlocnaixement());
    	 
    	$this->setPoblacio($persona->getPoblacio());
    	$this->setProvincia($persona->getProvincia());
    	$this->setCp($persona->getCp());
    	$this->setAdreca($persona->getAdreca());
    }
    
    /**
     * toString
     */
    public function __toString()
    {
    	
    	return $this->id . " " . $this->getNomCognoms();
    }   
     
    /**
     * És soci? false
     *
     * @return boolean
     */
    public function esSoci() { return false; }
    
    /**
     * És soci vigent? false
     *
     * @return boolean
     */
    public function esSociVigent() { return false; }
    
    /**
     * És baixa? false
     *
     * @return boolean
     */
    public function esBaixa() { return false; }
    
    
    
    /**
     * Estat: S-Soci, B-Soci de baixa, N-No soci
     *
     * @return boolean
     */
    public function estat() { return UtilsController::NOSOCI; }
    
    /**
     * Estat: Soci, Baixa, No soci
     *
     * @return boolean
     */
    public function estatAmpliat() {
    	 
    	switch ($this->estat()) {
    		case UtilsController::SOCI_VIGENT:
    			if ($this->sexe == 'H') return 'soci';
    			else return 'sòcia';
    			break;
    		case UtilsController::SOCI_BAIXA:
    			return 'baixa';
    			break;
    		case UtilsController::NOSOCI:
    			if ($this->sexe == 'H') return 'no soci';
    			else return 'no sòcia';
    			break;
    	}
    	return 'desconegut';
    }
    
    /**
     * Get temps soci => 0
     *
     * @return \DateInterval
     */
    public function getAntiguitat()
    {
    	return 0;
    }    
    /**
     * Return "--"
     *
     * @return string
     */
    public function getNumSoci()
    {
    	return "--";
    }
    
    /**
     * Return ""
     *
     * @return string
     */
    public function getTipus()
    {
    	return 0;
    }
    /**
     * Persona sempre paga per finestreta
     *
     * @return boolean
     */
    public function esPagamentFinestreta()
    {
    	return true;
    }
    
    /**
     * Persona sempre és la deudora del seu propi grup
     *
     * @return boolean
     */
    public function esDeudorDelGrup()
    {
    	return true;
    }
    
    /**
     * Get infoSoci
     *
     * @return string
     */
    public function getInfoSoci()
    {
    	return '--';
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
    	$row = '';
    	$row .= '"'.$this->id.'";"No";"";"";"'.$this->getCsvRowCommon().'";"";"";"";""'.PHP_EOL;

    	return $row;
    }
    
    protected function getCsvRowCommon() {
    	$datan = '';
    	if ($this->datanaixement != null) $datan = $this->datanaixement->format('Y-m-d');
    	$mail = ($this->correu != null?$this->correu:'');
    	$telf = ($this->telffix != null?$this->telffix:'');
    	$mob = ($this->telfmobil != null?$this->telfmobil:'');
    	$adreca = ($this->adreca != null?$this->adreca:'');
    	$poblacio = ($this->poblacio != null?$this->poblacio:'');
    	$provincia = ($this->provincia != null?$this->provincia:'');
    	$cp = ($this->cp != null?$this->cp:'');
    	
    	$res = $this->nom.'";"'.$this->cognoms.'";"'.$this->dni.'";"'.$this->sexe.'";"';
    	$res .= $mail.'";"'.$telf.'";"'.$mob.'";"'.$adreca.'";"'.$poblacio.'";"'.$cp.'";"'.$provincia.'";"'.$datan;
    	
    	return $res;
    }
    
    /**
     * Get infoBaixa
     *
     * @return string
     */
    public function getInfoBaixa()
    {
    	return '';
    }
    
    /**
     * Get nom + cognoms
     *
     * @return string
     */
    public function getNomCognoms()
    {
    	return $this->nom . " " . $this->cognoms;
    }
    
    /**
     * Get prefix nom amb espai
     *
     * @return string
     */
    public function getPrefixNom($inicialmajuscula = false)
    {
    	$prefix = ($this->sexe == 'H'? 'en ':'na ');
    	
    	return $inicialmajuscula?ucfirst($prefix):$prefix;
    }
    
    /**
     * Get foto null per defecte
     *
     * @return \Foment\GestioBundle\Entity\Imatge
     */
    public function getFoto()
    {
    	return null;
    }
    
    
    /**
     * Get edat anys
     *
     * @return int
     */
    public function getEdat()
    {
    	if ($this->datanaixement == null) return "";
    	$current = new \DateTime();
    	$interval = $current->diff($this->datanaixement);
    	return $interval->y;
    }
    
    /**
     * Get adreça
     *
     * @return string
     */
    public function getAdrecaCompleta()
    {
    	$strA = $this->adreca.'<br/>';
    	$strA .= $this->cp.' '.$this->poblacio.'<br/>';
    	if ($this->provincia != "" && $this->provincia != null) $strA .= $this->provincia;
    	return $strA;
    }
    
    /**
     * Get llistaSeccions
     *
     * @return string
     */
    public function getLlistaSeccions()
    {
    	return '--';
    }
    
    
    /**
     * Get telefons
     *
     * @return string
     */
    public function getTelefons()
    {
    	if ($this->telffix == null && $this->telfmobil == null) return '--';
    	if ($this->telffix == null) return UtilsController::format_phone($this->telfmobil);
    	if ($this->telfmobil == null) return UtilsController::format_phone($this->telffix);
    	
    	return UtilsController::format_phone($this->telffix) . PHP_EOL . UtilsController::format_phone($this->telfmobil);
    }
    
    /**
     * Get contacte
     *
     * @return string
     */
    public function getContacte()
    {
    	$mailHref = '';
    	if ($this->correu != null && $this->correu != "") $mailHref = '<a href="mailto:'.$this->correu.'">'.$this->correu.'</a>';
    	if ($this->telffix == null && $this->telfmobil == null) return '--';
    	if ($this->telffix == null) return UtilsController::format_phone($this->telfmobil).'<br/>'.$mailHref;
    	if ($this->telfmobil == null) return UtilsController::format_phone($this->telffix).'<br/>'.$mailHref;
    	 
    	return UtilsController::format_phone($this->telffix) .' - '. UtilsController::format_phone($this->telfmobil).'<br/>'.$mailHref;
    }
    
    /**
     * Get deute, suma imports rebuts emesos encara no cobrats
     *
     * @return double
     */
    public function getDeute()
    {
		$deute = 0;
    	foreach ($this->rebuts as $rebut)  {
    		if (!$rebut->anulat() && !$rebut->cobrat()) {
    			$deute += $rebut->getImport();
    		}
    	}
    	return $deute;
    }
    
    /**
     * Get total rebuts emesos encara no cobrats
     *
     * @return double
     */
    public function getDeuteNum()
    {
    	$deute = 0;
    	foreach ($this->rebuts as $rebut)  {
    		if (!$rebut->anulat() && !$rebut->cobrat()) {
    			$deute++;
    		}
    	}
    	return $deute;
    }

    /**
     * Get noms membres del grup moròs. Per a persona no socia només ell mateix
     *
     * @return String
     */
    public function getDeuteGrup()
    {
    	return $this->getNomCognoms();
    }
    
    /**
     * Get detalls rebuts on la persona aparegui, no només per ser el deutor
     *
     * @return array
     */
    public function getRebutDetalls()
    {
    	$detalls = array();
    	foreach ($this->participacions as $participacio)  {
    		if ($participacio->getDatacancelacio() == null) {
    			$detalls = array_merge($detalls, $participacio->getRebutsDetallsVigents()); 
    		}
    	}
    	return $detalls;
    }
    
    
    /**
     * Get id's de les activitats no cancelades on participa la persona
     *
     * @return string
     */
    public function getActivitatsIds()
    {
    	$activitats_ids = array();
    	foreach ($this->participacions as $participacio)  {
    		if ($participacio->getDatacancelacio() == null)
    			$activitats_ids[] = $participacio->getActivitat()->getId();
    	}
    	
    	//rsort($activitats_ids); // De major a menor
    	
    	return $activitats_ids; 
    }
    
    /**
     * Returns participació with Activitat identified by $id or null
     *
     * @param integer $id
     * @return \Foment\GestioBundle\Entity\Participant
     */
    public function getParticipacioByActivitatId($id) {
    	foreach ($this->participacions as $participacio)  {
    		if ($participacio->getActivitat()->getId() == $id) return $participacio;
    	}
    	return null;
    }
    
    
    /**
     * Returns rebut facturacio or null
     *
     * @param \Foment\GestioBundle\Entity\Facturacio $facturacio
     * @return \Foment\GestioBundle\Entity\Participant
     */
    public function getRebutFacturacio($facturacio) {
    	foreach ($this->rebuts as $rebut)  {
    		if (!$rebut->anulat() && $rebut->getId() != 0 && $rebut->getFacturacio() == $facturacio) return $rebut; 
    	}
    	return null;
    }
    
    /**
     * Get participacions no cancelades sorted by activitat id desc
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParticipacionsSortedByIdDesc()
    {
    	$arr = array();
    	foreach ($this->participacions as $participacio) {
    		if ($participacio->getDatacancelacio() == null) $arr[] = $participacio;
    	}
    	 
    	usort($arr, function($a, $b) {
    		if ($a === $b) {
    			return 0;
    		}
    		return ($a->getActivitat()->getId() < $b->getActivitat()->getId())? -1:1;;
    	});
    	return $arr;
    }
    
    /**
     * Add participació $this en $activitat
     *
     * @param \Foment\GestioBundle\Entity\Activitat $activitat
     * @return \Foment\GestioBundle\Entity\Participant
     */
    /*public function addActivitat(\Foment\GestioBundle\Entity\Activitat $activitat)
    {
    	$participant = new Participant();
    	$participant->setActivitat($activitat);
    	$participant->setPersona($this);
    	$activitat->addParticipant($participant);
    	 
    	$this->addParticipacio($participant);
    	return $participant;
    }*/
    
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
     * Set nom
     *
     * @param string $nom
     * @return Persona
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set cognoms
     *
     * @param string $cognoms
     * @return Persona
     */
    public function setCognoms($cognoms)
    {
        $this->cognoms = $cognoms;

        return $this;
    }

    /**
     * Get cognoms
     *
     * @return string 
     */
    public function getCognoms()
    {
        return $this->cognoms;
    }

    /**
     * Set sexe
     *
     * @param string $sexe
     * @return Persona
     */
    public function setSexe($sexe)
    {
        $this->sexe = $sexe;

        return $this;
    }

    /**
     * Get sexe
     *
     * @return string 
     */
    public function getSexe()
    {
        return $this->sexe;
    }

    /**
     * Set datanaixement
     *
     * @param \DateTime $datanaixement
     * @return Persona
     */
    public function setDatanaixement($datanaixement)
    {
        $this->datanaixement = $datanaixement;

        return $this;
    }

    /**
     * Get datanaixement
     *
     * @return \DateTime 
     */
    public function getDatanaixement()
    {
        return $this->datanaixement;
    }

    /**
     * Set llocnaixement
     *
     * @param string $llocnaixement
     * @return Persona
     */
    public function setLlocnaixement($llocnaixement)
    {
        $this->llocnaixement = $llocnaixement;

        return $this;
    }

    /**
     * Get llocnaixement
     *
     * @return string 
     */
    public function getLlocnaixement()
    {
        return $this->llocnaixement;
    }

    /**
     * Set dni
     *
     * @param string $dni
     * @return Persona
     */
    public function setDni($dni)
    {
        $this->dni = $dni;

        return $this;
    }

    /**
     * Get dni
     *
     * @return string 
     */
    public function getDni()
    {
        return $this->dni;
    }

    /**
     * Set telffix
     *
     * @param integer $telffix
     * @return Persona
     */
    public function setTelffix($telffix)
    {
        $this->telffix = $telffix;

        return $this;
    }

    /**
     * Get telffix
     *
     * @return integer 
     */
    public function getTelffix()
    {
        return $this->telffix;
    }

    /**
     * Set telfmobil
     *
     * @param integer $telfmobil
     * @return Persona
     */
    public function setTelfmobil($telfmobil)
    {
        $this->telfmobil = $telfmobil;

        return $this;
    }

    /**
     * Get telfmobil
     *
     * @return integer 
     */
    public function getTelfmobil()
    {
        return $this->telfmobil;
    }

    /**
     * Set notacontacte
     *
     * @param string $notacontacte
     * @return Soci
     */
    public function setNotacontacte($notacontacte)
    {
    	$this->notacontacte = $notacontacte;
    
    	return $this;
    }
    
    /**
     * Get notacontacte
     *
     * @return string
     */
    public function getNotacontacte()
    {
    	return $this->notacontacte;
    }
    
    /**
     * Set correu
     *
     * @param string $correu
     * @return Persona
     */
    public function setCorreu($correu)
    {
        $this->correu = $correu;

        return $this;
    }

    /**
     * Get correu
     *
     * @return string 
     */
    public function getCorreu()
    {
        return $this->correu;
    }

    /**
     * Set adreca
     *
     * @param string $adreca
     * @return Persona
     */
    public function setAdreca($adreca)
    {
        $this->adreca = $adreca;

        return $this;
    }

    /**
     * Get adreca
     *
     * @return string 
     */
    public function getAdreca()
    {
        return $this->adreca;
    }

    /**
     * Set cp
     *
     * @param string $cp
     * @return Persona
     */
    public function setCp($cp)
    {
        $this->cp = $cp;

        return $this;
    }

    /**
     * Get cp
     *
     * @return string 
     */
    public function getCp()
    {
        return $this->cp;
    }
    
    /**
     * Set poblacio
     *
     * @param string $poblacio
     * @return Persona
     */
    public function setPoblacio($poblacio)
    {
        $this->poblacio = $poblacio;

        return $this;
    }

    /**
     * Get poblacio
     *
     * @return string 
     */
    public function getPoblacio()
    {
        return $this->poblacio;
    }

    /**
     * Set provincia
     *
     * @param string $provincia
     * @return Persona
     */
    public function setProvincia($provincia)
    {
        $this->provincia = $provincia;

        return $this;
    }

    /**
     * Get provincia
     *
     * @return string 
     */
    public function getProvincia()
    {
        return $this->provincia;
    }
    
    /**
     * Set observacions
     *
     * @param string $observacions
     * @return Soci
     */
    public function setObservacions($observacions)
    {
    	$this->observacions = $observacions;
    
    	return $this;
    }
    
    /**
     * Get observacions
     *
     * @return string
     */
    public function getObservacions()
    {
    	return $this->observacions;
    }
    
    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Persona
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
     * @return Persona
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
     * Add participacio
     *
     * @param \Foment\GestioBundle\Entity\Participant $participacio
     * @return Persona
     */
    public function addParticipacio(\Foment\GestioBundle\Entity\Participant $participacio)
    {
    	$this->participacions->add($participacio);
        //$this->participacions[] = $participacio;

        return $this;
    }

    /**
     * Remove participacio
     *
     * @param \Foment\GestioBundle\Entity\Participant $participacio
     */
    public function removeParticipacio(\Foment\GestioBundle\Entity\Participant $participacio)
    {
        $this->participacions->removeElement($participacio);
    }

    /**
     * Get participacions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getParticipacions()
    {
        return $this->participacions;
    }

    
    /**
     * Add rebut
     *
     * @param \Foment\GestioBundle\Entity\Rebut $rebut
     * @return Persona
     */
    public function addRebut(\Foment\GestioBundle\Entity\Rebut $rebut)
    {
    	$this->rebuts->add($rebut);
    
    	return $this;
    }
    
    /**
     * Remove rebut
     *
     * @param \Foment\GestioBundle\Entity\Rebut $rebut
     */
    public function removeRebut(\Foment\GestioBundle\Entity\Rebut $rebut)
    {
    	$this->rebuts->removeElement($rebut);
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
