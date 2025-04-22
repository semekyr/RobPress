<?php

	#[AllowDynamicProperties]
	class AuthHelper {

		/** Construct a new Auth helper */
		public function __construct($controller) {
			$this->controller = $controller;
		}

		/** Get the IP address of the user */
		public function getIPAddress(){
			return $_SERVER['REMOTE_ADDR'];
		}

		/** Track the user's login attempts */
		private function tracklogin($ip, $success) {
			$db = $this->controller->db;
			$settings = $this->controller->Model->Settings;
			$debug = $settings->getSetting('debug');

			//Do not track login attempts in debug mode
			if ($debug ==`1`) return;

			
		
			//Check if the IP address is already in the database
			$results = $db->connection->exec(
				"SELECT attempts, last_attempt FROM `login_attempts` WHERE ip_address = :ip_address",
				[':ip_address' => $ip]
			);
		
			//If the IP address is in the database, update the record
			if (!empty($results)) {
				$attempts = $results[0]['attempts'];
				$lastAttempt = strtotime($results[0]['last_attempt']);
				$now = time();
				$diff = $now - $lastAttempt;
		
		
				//If the last attempt was more than 10 minutes ago, reset the attempts
				if ($success) {
					$db->connection->exec(
						"UPDATE `login_attempts` SET attempts = 0, last_attempt = NOW() WHERE ip_address = :ip_address",
						[':ip_address' => $ip]
					);
					
				} else {
					if ($diff < 600) {
						$attempts++;
					} else {
						$attempts = 1;
					}
		
					$db->connection->exec(
						"UPDATE `login_attempts` SET attempts = :attempts, last_attempt = NOW() WHERE ip_address = :ip_address",
						[':attempts' => $attempts, ':ip_address' => $ip]
					);
					
		
					//Block the IP if there are too many failed attempts (max 5)
					if ($attempts >= 5) {
						$db->connection->exec(
							"INSERT INTO `blocked_ips` (ip_address, blocked_at) VALUES (:ip_address, NOW())",
							[':ip_address' => $ip]
						);
						
					}
				}
			} else {
				//If the IP address is not in the database, create a new record
				if (!$success) {
					$db->connection->exec(
						"INSERT INTO `login_attempts` (ip_address, attempts, last_attempt) VALUES (:ip_address, 1, NOW())",
						[':ip_address' => $ip]
					);
					
				}
			}
		}

		/** Check if the user is blocked */
		private function isBlocked($ip){
			$db = $this->controller->db;

			$results = $db->connection->exec("SELECT * FROM `blocked_ips` WHERE ip_address = :ip_address",
			[':ip_address' => $ip]);

			if (!empty($results)) {
				$blockedAtTime = strtotime($results[0]['blocked_at']);
				$now = time();

				if ($now - $blockedAtTime > 600) {
					//If the IP address has been blocked for more than 10 minutes, remove it from the blocked_ips table
					$db->connection->exec("DELETE FROM `blocked_ips` WHERE ip_address = :ip_address",
					[':ip_address' => $ip]);
					return false;
				}
				return true;
			}
			return false;
		}


		/** Attempt to resume a previously logged in session if one exists */
		public function resume() {
			$f3 = Base::instance();
			$db = $this->controller->db;
		
			//Cleanup expired cookies from the database
			$db->connection->exec("DELETE FROM cookies WHERE expires_at < NOW()");

			//Cleanup cookies that belong to deleted users from the database
			$db->connection->exec("DELETE FROM cookies WHERE user_id NOT IN (SELECT id FROM users)");
		
			
			//Ignore if already running session
			if ($f3->exists('SESSION.user.id')) return;
		
			//Log user back in from cookie
			if ($f3->exists('COOKIE.RobPress_User')) {
				$cookieValue = $f3->get('COOKIE.RobPress_User');

				//Get the cookie from the database
				$results = $db->connection->exec(
					"SELECT * FROM `cookies` WHERE `cookie_name` = 'RobPress_User' AND `cookie_value` = :cookie_value AND `expires_at` > NOW()",
					[':cookie_value' => $cookieValue]
				);

				//If the cookie is found, decrypt and deserialize the cookie value
				if (!empty($results)) {
					list($encruptedvalue, $iv) = explode('::', base64_decode($results[0]['cookie_value']), 2);

					$decryptedcalue = openssl_decrypt(
						$encruptedvalue,
						'aes-256-cbc',
						base64_decode(SECURE_KEY),
						0,
						$iv
					);
					$user = unserialize($decryptedcalue);
					if ($user){
						$this->forceLogin($user);
					}
				}
			}
		
		
			//Retrieve the previous URL from the cookie and set it in the session
			if (isset($_COOKIE['previousUrl'])) {
				$f3->set('SESSION.previousUrl', $_COOKIE['previousUrl']);
				// Optionally, clear the cookie after setting it in the session
				setcookie('previousUrl', '', time() - 3600, '/');
			}
		}
			

		/** Perform any checks before starting login */
		public function checkLogin($username,$password,$request,$debug) {

			
			//DO NOT check login when in debug mode
			if($debug == 1) { return true; }

			return true;	
		}

		/** Look up user by username and password and log them in */
		public function login($username, $password) {
			$f3 = Base::instance();						
			$db = $this->controller->db;

			//Sanitize the input
			$username = h($username);
    		$password = h($password);


			//Get the client's IP address
			$ip = $this->getIPAddress();


			//Check if this user's IP address is blocked
			if ($this->isBlocked($ip)) {
				StatusMessage::add('You have been blocked for 1 hour due to too many login attempts. Please try again later.','error');
				return false;
			}


			//Update plain text passwords to hashed passwords (only for passwords that are not hashed)
			$this->updatePlainTextPasswords();
		
		
		
			//Fetch user from the database
			$results = $db->connection->exec("SELECT * FROM `users` WHERE `username`= :user", [':user' => $username]);
			
			//Check if the user exists
			if (!empty($results)) {
				$user = $results[0];
		
				//Check if the password matches
				if (password_verify($password, $user['password'])) {
					$this->tracklogin($ip, true);
					$this->setupSession($user);
					return $this->forceLogin($user);
					StatusMessage::add('Logged in successfully','success');
				} else {
					$this->tracklogin($ip, false);
				}
			} else {
				$this->tracklogin($ip, false);
			}
		
			return false; 
		}

		/** Log user out of system */
		public function logout() {
			$f3=Base::instance();	
			$db = $this->controller->db;	
			
			//Kill the cookie
			if ($f3->exists('COOKIE.RobPress_User')) {
				$cookieValue = $f3->get('COOKIE.RobPress_User');
				$db->connection->exec("DELETE FROM `cookies` WHERE `cookie_name` = 'RobPress_User' AND `cookie_value` = :cookie_value", [':cookie_value' => $cookieValue]);
			}	

			//Kill the cookie in the browser
			setcookie('RobPress_User', '', time() - 3600, '/');

			//Kill the session
			session_destroy();

			
		}

		/** Set up the session for the current user */
		public function setupSession($user) {
			$f3=Base::instance();
			$db = $this->controller->db;

			
			//Retrieve the previous URL from the session
			$previousUrl = $f3->get('SESSION.previousUrl');

			//Remove previous session
			session_destroy();

			//Setup new session with more secure hash
			session_id(hash('sha256', $user['id']. uniqid()));

			//Encrypt user data before storing in the cookie
			$encryption_key = base64_decode(SECURE_KEY);
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
			$encruptedvalue = openssl_encrypt(serialize($user), 'aes-256-cbc', $encryption_key, 0, $iv);
			$cookieValue = base64_encode($encruptedvalue . '::' . $iv);


			//Store the cookie in the database
			$db->connection->exec("INSERT INTO `cookies` (`user_id`, `cookie_name`, `cookie_value`, `expires_at`) VALUES (:user_id, 'RobPress_User', :cookie_value, DATE_ADD(NOW(), INTERVAL 30 DAY))", [':user_id' => $user['id'], ':cookie_value' => $cookieValue]);
			//Setup cookie for storing user details and for relogging in
			setcookie('RobPress_User', $cookieValue, time() + 3600 * 24 * 30, '/', '', true, true);


			//Store the previous URL in a cookie
			 if (!empty($previousUrl)) {
				$encryptedurl = base64_encode(openssl_encrypt($previousUrl, 'aes-256-cbc', SECURE_KEY, 0, base64_decode(SECURE_IV)));
				setcookie('previousUrl', $encryptedurl, time() + 3600 * 24 * 30, '/');
			}

			//And begin!
			new Session();

			//Retrieve the previous URL from the cookie and set in the new session
			if (isset($_COOKIE['previousUrl'])) {
				$decryptedurl = openssl_decrypt(base64_decode($_COOKIE['previousUrl']), 'aes-256-cbc', SECURE_KEY, 0, base64_decode(SECURE_IV));
				$f3->set('SESSION.previousUrl', $decryptedurl);
				// Optionally, clear the cookie after setting it in the session
				setcookie('previousUrl', '', time() - 3600, '/');
			}
		}

		/** Not used anywhere in the code, for debugging only */
		public function specialLogin($username) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$f3 = Base::instance();
			$user = $this->controller->Model->Users->fetch(array('username' => $username));
			$array = $user->cast();
			return $this->forceLogin($array);
		}

		/** Not used anywhere in the code, for debugging only */
		public function debugLogin($username,$password='admin',$admin=true) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$user = $this->controller->Model->Users->fetch(array('username' => $username));

			//Create a new user if the user does not exist
			if(!$user) {
				$user = $this->controller->Model->Users;
				$user->username = $user->displayname = $username;
				$user->email = "$username@robpress.org";
				$user->setPassword($password);
				$user->created = mydate();
				$user->bio = '';
				if($admin) {
					$user->level = 2;
				} else {
					$user->level = 1;
				}
				$user->save();
			}

			//Update user password
			$user->setPassword($password);

			//Move user up to administrator
			if($admin && $user->level < 2) {
				$user->level = 2;
				$user->save();
			}

			//Log in as new user
			return $this->forceLogin($user);			
		}

		/** Force a user to log in and set up their details */
		public function forceLogin($user) {
			//YOU ARE NOT ALLOWED TO CHANGE THIS FUNCTION
			$f3=Base::instance();					

			if(is_object($user)) { $user = $user->cast(); }

			$f3->set('SESSION.user',$user);
			return $user;
		}

		/** Get information about the current user */
		public function user($element=null) {
			$f3=Base::instance();
			if(!$f3->exists('SESSION.user')) { return false; }
			if(empty($element)) { return $f3->get('SESSION.user'); }
			else { return $f3->get('SESSION.user.'.$element); }
		}

		/** Check if the password is hashed and if not, hash it */
		public function updatePlainTextPasswords() {
				$db = $this->controller->db;
				$results = $db->connection->exec("SELECT * FROM `users`");
				foreach ($results as $user) {
					if (!password_get_info($user['password'])['algo']) {
						$hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
						$db->connection->exec("UPDATE `users` SET `password` = :password WHERE `id` = :id", [':password' => $hashedPassword, ':id' => $user['id']]);
					}
				}
		}





		
	}

?>
