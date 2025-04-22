<?php

class File {
	/** Extracts the elements of the array parameter into individual variables using the extract function. 
	 * This allows the method to access file details, such as file name.
	 * What is chmod 0666? Allows read and write access to the file.
	 */
	public static function Upload($array,$local=false) {
		$f3 = Base::instance();
		extract($array);
		$directory = getcwd() . '/uploads';
		$destination = $directory . '/' . $name;
		$name = basename($name);
		$webdest = '/uploads/' . $name;

		//Validate the file 
		if (!self::isValidFile($name, $tmp_name)) {
			StatusMessage::add('Invalid file type','danger');
			return false;
		}

		//Check the file size 
		$maxsize = 69 * 1024 * 1024; // 69MB
		if ($size > $maxsize) {
			StatusMessage::add('File size exceeds maximum limit of 69MB','danger');
			return false;
		}
		//Generate a new name for the file
		$newName = self::generateNewName($name);
		$destination = $directory . '/' . $newName;
		$webdest = '/uploads/' . $newName;


		//Local files get moved
		if($local) {
			if (copy($tmp_name,$destination)) {
				chmod($destination,0644);
				return $webdest;
			} else {
				return false;
			}
		//POSTed files are done with move_uploaded_file
		} else {
			if (move_uploaded_file($tmp_name,$destination)) {
				chmod($destination,0644);
				return $webdest;
			} else {
				return false;
			}
		}
	}

	/** Generate a new name for the file */
	private static function generateNewName($name) {
		$tmp = pathinfo ($name);
		$extension = strtolower($tmp['extension']);
		$filename = uniqid() . '.' . $extension;
		return $filename;
	}

	public static function isValidFile($name, $tmp_name) {

		//Allowed file extensions
		$allowedExtensions = array('png', 'jpg', 'jpeg', 'gif', 'webp');
		//Allowed MIME types
		$allowedMimes = array('image/png', 'image/jpeg', 'image/gif', 'image/webp');

		//Check the file extension
		$fileInfo = pathinfo($name);
		$extension = strtolower($fileInfo['extension']);
	
		//Check the MIME type
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $tmp_name);
		finfo_close($finfo);

		//If the extension or MIME type is not allowed, return false
		if (!in_array($extension, $allowedExtensions) || !in_array($mime, $allowedMimes)) {
			return false;
		}

		//If getimagesize fails, the file is not an image
		if (!@getimagesize($tmp_name)) {
			return false;
		}
	
		return true;
	}
	

}

?>
