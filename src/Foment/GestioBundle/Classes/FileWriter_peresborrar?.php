<?php
namespace Foment\GestioBundle\Classes;


class FileWriter {
	protected $_handle = null; //file handle
    protected $_init = false; //have we initialized 
    protected $_output = null; //path to output
    protected $_layout = array(); //our definition
    protected $_filename = ''; //filename of our File
	protected $_separador = ';'; //CSV separador
	
	/**
	 * Initialize CSV, open file and get it ready for reading
	 * @throws Exception
	 */
	protected function _init(){
		ini_set('auto_detect_line_endings', 1);
		$this->_init = true;
		$this->_handle = fopen($this->_output, "w+");
		if(!$this->_handle){
			throw new \Exception('Could not open file for write: ' . $this->_output);
		}
	}	 	
	 
	/**
	 * Set what our outputted CSV should be
	 * @param string $filename
	 * @param string $working_dir
	 */
	public function setOutput($filename, $working_dir){
		return $this->_output = $this->_createWorkingFile($filename, $working_dir);
	}
	
	
	/**
	 * Create a working CSV File for us to parse, modify, edit, add to etc
	 *
	 * @param type $filename
	 * @param type $working_dir
	 * @return boolean
	 */
	protected function _createWorkingFile($filename, $working_dir){
		$contents = ''; //empty file
		//$timestamp = time();
	
		if(!is_dir($working_dir)){
			$result = mkdir($working_dir, 0755, true);
			if(!$result){
				throw new \Exception();
			}
		}
	
		$handle = fopen($working_dir . $filename, "w+");
		if($handle){
			$this->_filename = $filename;
			fwrite($handle, $contents);
			fclose($handle);
			return $working_dir . $filename;
		}
		//log the error
		return false;
	}
	 
	
	/**
	 * Write row to File
	 * @param type $row
	 */
	public function writeRow($row = array()){
		if(!$this->_init){
			$this->_init();
		}
	
		$line = array();
		foreach($this->_layout as $key){
			if(isset($row[$key])){
				$line[$key] = $row[$key];
			} else {
				$line[$key] = NULL;
			}
		}
	
		$result = fputcsv($this->_handle, $line ,',', '"');
		if($result === FALSE){
			throw new \Exception('Failed to write row ('.var_export($row, true).') for File '.$this->_filename);
		}
	}
	
	/**
	 * Clean up our file streams, and anything that we don't need
	 */
	public function clean(){
		if($this->_handle){
			fclose($this->_handle);
		}
		$this->_init = false;
	}
	
}
