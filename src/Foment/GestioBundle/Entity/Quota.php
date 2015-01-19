<?php 
// src/Foment/GestioBundle/Entity/Quota.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity 
 * @ORM\Table(name="quotes")
 */

class Quota
{
	/**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Seccio", inversedBy="quotes")
     * @ORM\JoinColumn(name="seccio", referencedColumnName="id")
     */
    protected $seccio; // FK taula seccions
    
    /**
     * @ORM\Column(type="smallint", nullable=false)
     */
    protected $anyquota;
    
    /**
	 * @ORM\Column(type="decimal", precision=6, scale=2)
	 */
	protected $import;
    
	/**
	 * @ORM\Column(type="decimal", precision=6, scale=2)
	 */
	protected $importjuvenil;
	
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
		
		$a = func_get_args();
		$i = func_num_args();
		if (method_exists($this,$f='__construct'.$i)) {
			call_user_func_array(array($this,$f),$a);
		}
	}

	/**
	 * Constructor
	 *
	 * @param integer $anyquota
	 */
	public function __construct1($anyquota)
	{
		$this->anyquota = $anyquota;
	}
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
     * Set anyquota
     *
     * @param integer $anyquota
     * @return Quota
     */
    public function setAnyquota($anyquota)
    {
        $this->anyquota = $anyquota;

        return $this;
    }

    /**
     * Get anyquota
     *
     * @return integer 
     */
    public function getAnyquota()
    {
        return $this->anyquota;
    }

    /**
     * Set import
     *
     * @param string $import
     * @return Quota
     */
    public function setImport($import)
    {
        $this->import = $import;

        return $this;
    }

    /**
     * Get import
     *
     * @return string 
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * Set importjuvenil
     *
     * @param string $importjuvenil
     * @return Quota
     */
    public function setImportjuvenil($importjuvenil)
    {
        $this->importjuvenil = $importjuvenil;

        return $this;
    }

    /**
     * Get importjuvenil
     *
     * @return string 
     */
    public function getImportjuvenil()
    {
        return $this->importjuvenil;
    }

    /**
     * Set dataentrada
     *
     * @param \DateTime $dataentrada
     * @return Quota
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
     * @return Quota
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
     * Set seccio
     *
     * @param \Foment\GestioBundle\Entity\Seccio $seccio
     * @return Quota
     */
    public function setSeccio(\Foment\GestioBundle\Entity\Seccio $seccio = null)
    {
        $this->seccio = $seccio;

        return $this;
    }

    /**
     * Get seccio
     *
     * @return \Foment\GestioBundle\Entity\Seccio 
     */
    public function getSeccio()
    {
        return $this->seccio;
    }
}
