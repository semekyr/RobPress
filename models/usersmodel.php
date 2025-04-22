<?php

class UsersModel extends GenericModel {

	/** Update the password for a user account 
	 * Hashes the password before storing it in the database
	*/
	public function setPassword($password) {
		$this->password = password_hash($password, PASSWORD_DEFAULT);
	}		

}

?>
