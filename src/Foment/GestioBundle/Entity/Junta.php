<?php 
// src/Foment/GestioBundle/Entity/Junta.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity 
 * @ORM\Table(name="junta")
 */

// When generetes inheritance entity comment extends ...
// http://docs.doctrine-project.org/en/latest/reference/faq.html#can-i-use-inheritance-with-doctrine-2
class Junta extends Membre
{
	/**
	 * @ORM\Id
	 * @ORM\OneToOne(targetEntity="Membre", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(name="id", referencedColumnName="id")
	 */
    protected $id;

    /**
     * @ORM\Column(type="smallint", nullable=false) 
     */
    protected $carrec;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    protected $area;
    
    /**
     * @ORM\Column(type="date", nullable=false)
     */
    protected $datadesde;
    
    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $datafins;
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
    	// Valors per defecte
    	$this->carrec = 5;
    	$this->datadesde = new \DateTime('today');
    	$this->datafins = null;
    	// Hack per permetre múltiples constructors
    	$a = func_get_args();
    	$i = func_num_args();
    
    	if ($i == 1) {
    		if ($a[0] instanceof Membre and method_exists($this,$f='__constructMembreJunta')) {
    			call_user_func_array(array($this,$f),$a);
    		}
    	} else {
    		parent::__construct();
    	}
    }
    
    /**
     * Constructor from Membre
     *
     * @param \Foment\GestioBundle\Entity\Membre $membre
     */
    public function __constructMembreJunta($membre)
    {
    	parent::__construct($membre);
    }
    
    /**
     * És junta? true
     *
     * @return boolean
     */
    public function esJunta() { return true; }
    
    /**
     * Get carrec junta as string
     *
     * @return string
     */
    public function getCarrecjunta()
    {
    	return UtilsController::getCarrecJunta($this->carrec);
    }
    
    /**
     * Get info as string
     *
     * @return string
     */
    public function getInfo()
    {
    	if ($this->datacancelacio != null) return parent::getInfo();
    	
    	return $this->getCarrecjunta();
    }
    
    /**
     * Set carrec
     *
     * @param integer $carrec
     * @return Junta
     */
    public function setCarrec($carrec)
    {
        $this->carrec = $carrec;

        return $this;
    }

    /**
     * Get carrec
     *
     * @return integer 
     */
    public function getCarrec()
    {
        return $this->carrec;
    }

    /**
     * Set area
     *
     * @param string $area
     * @return Junta
     */
    public function setArea($area)
    {
    	$this->area = $area;
    
    	return $this;
    }
    
    /**
     * Get area
     *
     * @return string
     */
    public function getArea()
    {
    	return $this->area;
    }
    
    /**
     * Set datadesde
     *
     * @param \DateTime $datadesde
     * @return Junta
     */
    public function setDatadesde($datadesde)
    {
        $this->datadesde = $datadesde;

        return $this;
    }

    /**
     * Get datadesde
     *
     * @return \DateTime 
     */
    public function getDatadesde()
    {
        return $this->datadesde;
    }

    /**
     * Set id
     *
     * @param \Foment\GestioBundle\Entity\Membre $id
     * @return Junta
     */
    public function setId(\Foment\GestioBundle\Entity\Membre $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return \Foment\GestioBundle\Entity\Membre 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set datafins
     *
     * @param \DateTime $datafins
     * @return Junta
     */
    public function setDatafins($datafins)
    {
        $this->datafins = $datafins;

        return $this;
    }

    /**
     * Get datafins
     *
     * @return \DateTime 
     */
    public function getDatafins()
    {
        return $this->datafins;
    }
}
