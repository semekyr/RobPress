<?php

use Web\Google\StaticMap;

#[AllowDynamicProperties]
class PagesModel {

	/** Create a new page */
	public function create($pagename) {
		if (empty($pagename)) {
			return false;
		}
		$pagedir = getcwd() . "/pages/";
		touch($pagedir . $pagename . ".html");
		return true;
	}

	/** Load the contents of a page */
	public function delete($pagename) {
		if (empty($pagename)) {
			return false;
		}
		$pagedir = getcwd() . "/pages/";
		$file = $pagedir . $pagename;
		if(!file_exists($file)) {
			$file .= ".html";
		}
		if(!file_exists($file)) { 
			return false; 
		}
		unlink($file);
		return true;
	}

	/** Get all available pages */
	public function fetchAll() {
		$pagedir = getcwd() . "/pages/";
		$pages = array();
		if ($handle = opendir($pagedir)) {
			while (false !== ($file = readdir($handle))) {
				if (!preg_match('![.]html!sim',$file)) continue;
				$title = ucfirst(str_ireplace("_"," ",str_ireplace(".html","",$file)));
				$pages[$title] = $file;
			}
			closedir($handle);
		}
		return $pages;
	}

	/** Load the contents of a page */
	public function fetch($pagename) {
		if (empty($pagename)) {
			return false;
		}
		//Sanitize the page name to prevent any directory traversal attacks
		$pagename = basename ($pagename);
		$pagedir = getcwd() . "/pages/";
		$file = $pagedir . $pagename;
		if(!file_exists($file)) {
			$file .= ".html";
		}
		if(!file_exists($file)) { 
			return false; 
		}
		return file_get_contents($file);
	}

	/** Save contents of the page based on title and content field to file */
	public function save() {
		if (empty($this->title)) {
			return false;
		}
		if (!isset($this->content)) {
			return false;
		}
		$pagedir = getcwd() . "/pages/";
		$file = $pagedir . $this->title;
		if(!file_exists($file)) {
			$file .= ".html";
		}
		if(!file_exists($file)) { 
			return false; 
		}
		if(!isset($this->content)) { return false; } 
		return file_put_contents($file,$this->content);
	}

}

?>
