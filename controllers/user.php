<?php
class User extends Controller {

	/** Retrieves user's details based on their user ID provided in the URL parameters. 
	 * It fetches the user's details, all articles and comments associated with the user.
	 * Send them to be used in the view.
	 */
	
	public function view($f3) {
		$userid = $f3->get('PARAMS.3');
		$u = $this->Model->Users->fetch($userid);
	
		//Check if the user exists
		if (!$u) {
			\StatusMessage::add('User not found', 'danger');
			return $f3->reroute('/');
		}
	
		$articles = $this->Model->Posts->fetchAll(array('user_id' => $userid));
		$comments = $this->Model->Comments->fetchAll(array('user_id' => $userid));
	
		$f3->set('u', $u);
		$f3->set('articles', $articles);
		$f3->set('comments', $comments);
	}

	/** Handles the registration of new users.
	 * If the request is a POST it retrieves the username and password from the request data.
	 * If the fields are not all filled out, it adds a status message and redirects to the registration page.
	 * It then checks if the username already exists in the database.
	 * If the check passes, it creates a new user, saves them to the database. 
	 * Upon successful registration, it adds a status message and redirects to the login page.
	 */
	public function add($f3) {
		if ($this->request->is('post')) {
			require_once 'vendor/autoload.php';
	
			// Configure HTMLPurifier
			$config = \HTMLPurifier_Config::createDefault();
			$config->set('HTML.Allowed', 'b,strong,i,em,a[href|target],p,ul,ol,li,img[src|alt|width|height]');
			$config->set('URI.DisableExternalResources', true);
			$config->set('URI.DisableResources', true);
			$purifier = new \HTMLPurifier($config);
	
			// Sanitize inputs
			$username = $purifier->purify($this->request->data['username'] ?? '');
			$email = $purifier->purify($this->request->data['email'] ?? '');
			$displayname = $purifier->purify($this->request->data['displayname'] ?? '');
			$password = $this->request->data['password'] ?? '';
			$password2 = $this->request->data['password2'] ?? '';
	
			// Validate inputs
			if (empty($username) || empty($password) || empty($password2) || empty($email)) {
				StatusMessage::add('All fields need to be filled out', 'danger');
				return $f3->reroute('/user/add');
			}
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				StatusMessage::add('Invalid email address', 'danger');
				return $f3->reroute('/user/add');
			}
	
			// Check for existing user
			$check = $this->Model->Users->fetch(array('username' => $username));
			if (!empty($check)) {
				StatusMessage::add('User already exists', 'danger');
			} else if ($password !== $password2) {
				StatusMessage::add('Passwords must match', 'danger');
			} else {
				// Create new user
				$user = $this->Model->Users;
				$user->username = $username;
				$user->email = $email;
				$user->created = mydate();
				$user->bio = '';
				$user->level = 1;
				$user->setPassword($password);
				$user->displayname = !empty($displayname) ? $displayname : $username;
	
				// Save user
				$user->save();
	
				StatusMessage::add('Registration complete', 'success');
				return $f3->reroute('/user/login');
			}
		}
	}
	
	

	/**
	 * Handles the login of users. Checks if the request is a POST and retrives the username and password from the request data. 
	 * It then verifies the login using the Auth helper. 
	 * If the login is successufl, call the afterLogin method to handle the rest. 
	 */
	public function login($f3) {
		/** YOU MAY NOT CHANGE THIS FUNCTION - Make any changes in Auth->checkLogin, Auth->login and afterLogin() (AuthHelper.php) */
		if ($this->request->is('post')) {

			//Check for debug mode
			$settings = $this->Model->Settings;
			$debug = $settings->getSetting('debug');
			
			//Either allow log in with checked and approved login, or debug mode login
			list($username,$password) = array($this->request->data['username'],$this->request->data['password']);
			if (
				($this->Auth->checkLogin($username,$password,$this->request,$debug) && ($this->Auth->login($username,$password))) ||
				($debug && $this->Auth->debugLogin($username))) {

					$this->afterLogin($f3);

			} else {
				StatusMessage::add('Invalid username or password','danger');
			}
		}		
	}

	/* Handle after logging in */
	private function afterLogin($f3) {
				StatusMessage::add('Logged in successfully','success');

				// Retrieve the previous URL from the cookie
				if (isset($_COOKIE['previousUrl']) && isset($_GET['from'])) {
					$encryptedurl = $_COOKIE['previousUrl'];
					$previousUrl = openssl_decrypt(base64_decode($encryptedurl), 'aes-256-cbc', SECURE_KEY, 0, base64_decode(SECURE_IV));
					// Optionally, clear the cookie after using it
					setcookie('previousUrl', '', time() - 3600, '/');
					$f3->reroute($previousUrl);
				} else {
					$f3->reroute('/');
				}
	}

	public function logout($f3) {
		$this->Auth->logout();
		StatusMessage::add('Logged out successfully','success');
		$f3->reroute('/');	
	}

	public function profile($f3) {    
		$id = $this->Auth->user('id');
		$u = $this->Model->Users->fetch($id);
		$oldpass = $u->password;
	
		if ($this->request->is('post')) {
			//Sanitize inputs
			$username = h($this->request->data['username'] ?? '');
			$email = h($this->request->data['email'] ?? '');
			$password = h($this->request->data['password'] ?? '');
			$displayname = h($this->request->data['displayname'] ?? '');
			$bio = h($this->request->data['bio'] ?? '');
	
			//Update user object
			$u->username = $username;
			$u->email = $email;
			$u->displayname = $displayname;
			$u->bio = $bio;
	
			if (!empty($password)) { 
				$u->setPassword($password); //Hash the password securely
			} else {
				$u->password = $oldpass; //Retain old password if none is provided
			}
	
			//Handle avatar upload
			if (isset($_FILES['avatar']) && isset($_FILES['avatar']['tmp_name']) && !empty($_FILES['avatar']['tmp_name'])) {
				$url = File::Upload($_FILES['avatar']);
				$u->avatar = h($url); // Sanitize uploaded avatar URL
			} else if (isset($this->request->data['reset'])) {
				$u->avatar = '';
			}
	
			$u->save();
			\StatusMessage::add('Profile updated successfully', 'success');
			return $f3->reroute('/user/profile');
		}            
	
		//Sanitize output for the view
		$u->username = h($u->username);
		$u->email = h($u->email);
		$u->displayname = h($u->displayname);
		$u->bio = h($u->bio);
		$u->avatar = h($u->avatar);
	
		$_POST = $u->cast();
		$f3->set('u', $u);
	}


}
?>
