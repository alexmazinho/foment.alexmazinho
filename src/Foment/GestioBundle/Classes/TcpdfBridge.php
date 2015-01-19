<?php
namespace Foment\GestioBundle\Classes;

if (!defined('K_PATH_IMAGES')) {
	define ('K_PATH_IMAGES', __DIR__.'/../../../../web/imatges/');
}

define ('PDF_MARGIN_LEFT_NARROW', 30);
define ('PDF_MARGIN_RIGHT_NARROW', 30);

require_once (__DIR__.'/../../../../vendor/tcpdf/tcpdf.php');

/**
* TCPDF Bridge 
*/
class TcpdfBridge extends \TCPDF {
	protected $pagenum;
	
    public function init($params, $pagenum = false, $rightheader = "")
    {
    	// set document information
    	$this->SetCreator(PDF_CREATOR);
    	$this->SetAuthor($params['author']);
    	$this->SetTitle($params['title']);
    	$this->SetSubject($params['title']);
    	
    	$this->pagenum = $pagenum;
    	//$this->SetKeywords('TCPDF, PDF, example, test, guide');

    	
    	if ($params['header'] == false) $this->setPrintHeader(false);
    	else {
    		$this->setPrintHeader(true);
    		
    		// set default header data
    		$this->SetHeaderData($params['logo'], 10, $params['title'], $params['string']);
    		 
    		// set header and footer fonts
    		$this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    		
    		$this->SetHeaderMargin(PDF_MARGIN_HEADER);
    	}
    	
    	if ($params['footer'] == false) $this->setPrintFooter(false);
    	else {
    		$this->setPrintFooter(true);
    		
    		$this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    		
    		$this->SetFooterMargin(PDF_MARGIN_FOOTER);
    	}
    	
    	// set default monospaced font
    	$this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    	
    	//set margins
    	$this->SetMargins(PDF_MARGIN_LEFT_NARROW, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT_NARROW);
    	
    	//set auto page breaks
    	$this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    	
    	//set image scale factor
    	$this->setImageScale(PDF_IMAGE_SCALE_RATIO);
    	
        // set default font subsetting mode
        $this->setFontSubsetting(true);
        
        //Document Encryption / Security. http://www.tcpdf.org/examples/example_016.phps
        $this->SetProtection(array('modify', 'copy'), '', null, 2, null); // permissions , userpass, ownerpass, mode, pubkeys
    }
    
    public function Header() {
    	$image_file = K_PATH_IMAGES.$this->header_logo;
    	$this->Image($image_file, PDF_MARGIN_LEFT_NARROW, PDF_MARGIN_HEADER, $this->header_logo_width, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    	// Set font
    	$this->SetFont('helvetica', '', 11);
    	
    	//$this->SetLineWidth(0.1);
    	// Title
    	$this->writeHTMLCell('', '', $this->getX()+2, PDF_MARGIN_HEADER, $this->header_title, 0, 0, 0, true, 'L', true);
    	$this->SetTextColor(150, 150, 150); // Negre
    	$this->SetFont('helvetica', 'I', 10);
    	$this->writeHTMLCell(0, 0,  PDF_MARGIN_LEFT_NARROW, PDF_MARGIN_HEADER+7, $this->header_string, 'B', 0, 0, true, 'R', true);
    	
    	$this->SetTextColor(0, 0, 0); // Negre
    }
    
    
    public function Footer() {
    	// Position at 15 mm from bottom
    	$this->SetY(-15);
    	// Set font
    	$this->SetFont('helvetica', 'I', 8);
    	// Page number
    	$footer = 'Foment Martinenc - NIF: G08917635<br/>';
    	$footer .= 'Carrer de Provença, 591 - 08026 Barcelona. ';
    	$footer .= 'Tel: 934 55 70 95<br/>';
    	$footer .= '<a href="http://www.fomentmartinenc.org">www.fomentmartinenc.org</a> | info@fomentmartinenc.org';
    	
    	$this->writeHTMLCell('', '', '', '', $footer, 0, 0, 0, true, 'C', true);
    	if ($this->pagenum == true) $this->Cell(0, 10, 'Pàgina '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}