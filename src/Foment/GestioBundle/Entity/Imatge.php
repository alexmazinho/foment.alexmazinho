<?php
// src/Foment/GestioBundle/Entity/Imatge.php
namespace Foment\GestioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Foment\GestioBundle\Controller\UtilsController;

/**
 * @ORM\Entity
 * @ORM\Table(name="imatges")
 * 
 * @author alex	
 *
 */
class Imatge {
	
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;	
	
	/**
	 * @ORM\Column(type="string", length=100, nullable=false)
	 */
	protected $path;

	/**
	 * @ORM\Column(type="string", length=100, nullable=true)
	 */
	protected $titol;
	
	/**
	 * @Assert\File(maxSize="2000000")
	 */
	protected $file;
	
	/**
	 * Constructor
	 */
	public function __construct($file)
	{
		$this->file = $file;
	}
	
	public function __toString() {
		return $this->path;
	}

	public function getWidth() {
		if (!file_exists($this->getAbsolutePath())) return 0;
		try {
			$image_info = getimagesize($this->getAbsolutePath());
		} catch (\Exception $e) {
			return 0; 
		}
		return $image_info[0];
	}
	
	public function getHeight() {
		if (!file_exists($this->getAbsolutePath())) return 0;
		try {
			$image_info = getimagesize($this->getAbsolutePath());
		} catch (\Exception $e) {
			return 0; 
		}
		return $image_info[1];
	}

	public function upload($name = null)
	{
		// the file property can be empty if the field is not required
		if (null === $this->getFile()) {
			return;
		}
	
		// use the original file name here but you should
		// sanitize it at least to avoid any security issues
	
		// move takes the target directory and then the
		// target filename to move to
		$extension = $this->getFile()->guessExtension(); 
		
		if ($extension == null or ($extension != "png" and $extension != "jpg" 
								and $extension != "jpeg" and $extension != "gif")) {
			return false;
		} 
		
		// 	set the path property to the filename where you've saved the file
		// Màxim 50 amb extensió. Mida de path  time 10 + _ + nom + .ext 5 => nom <= 50-16= 34
		
		if ($name == null) {
			$nameAjustat = $this->getFile()->getClientOriginalName();
		} else {
			$nameAjustat = $name;
		}
		$nameAjustat = substr($nameAjustat, 0, 33);
		$this->path = time() . "_". UtilsController::netejarNom($nameAjustat) . "." . $extension;
		
		$this->getFile()->move($this->getUploadRootDir(), $this->path);

		// clean up the file property as you won't need it anymore
		$this->file = null;
			
		return true;
	}

	public function getAbsolutePath()
	{
		return null === $this->path
		? null
		: $this->getUploadRootDir().$this->path;
	}
	
	public function getWebPath()
	{
		return null === $this->path
		? null
		: UtilsController::PATH_REL_TO_UPLOADS.$this->path;
	}
	
	protected function getUploadRootDir()
	{
		// the absolute directory path where uploaded
		// documents should be saved
		return __DIR__.UtilsController::PATH_TO_WEB_FILES.UtilsController::PATH_REL_TO_UPLOADS;
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
     * Set path
     *
     * @param string $path
     * @return EntityImatge
     */
    public function setPath($path)
    {
        $this->path = $path;
    
        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set titol
     *
     * @param string $titol
     * @return EntityImatge
     */
    public function setTitol($titol)
    {
        $this->titol = $titol;
    
        return $this;
    }

    /**
     * Get titol
     *
     * @return string 
     */
    public function getTitol()
    {
        return $this->titol;
    }
    
    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null)
    {
    	$this->file = $file;
    }
    
    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
    	return $this->file;
    }
}