<?php

class Contact extends Controller {

	/** This function checks by checking if the request method is POST 
	 * If it is, it extracts the data from the request. The method constructs the From header for the email using the extracted data.  
	 * It then sends the email using the mail function. 
	 * After sending the email, it adds a status message and redirects to the home page.
	 */


	public function index($f3) {
		if ($this->request->is('post')) {
			extract($this->request->data);
			
            $subject = $this->request->data['subject'] ?? null;
            $message = $this->request->data['message'] ?? null;

			//Get the email from the currently logged in user 
			$user = $this->Auth->user();
			$email = $user['email'] ?? null;


			//Check if all fields are filled out
			if (empty($subject) || empty($message) || empty($email)) {
				StatusMessage::add('All fields need to be filled out.', 'danger');
				return $f3->reroute('/contact');
			}

			// Check if email format is valid
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                StatusMessage::add('Invalid email address.', 'danger');
                return $f3->reroute('/contact');
            }

			//Sanitize input 
			$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
			$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
			$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
			
			$from = "From: $email";
			$to = 'root@localhost'; 

			
			//Check if the email is being sent to the same email address
			if ($to === $email) {
				StatusMessage::add('You cannot send an email to yourself.', 'danger');
				return $f3->reroute('/contact');
			}

			mail($to,$subject,$message,$from);

			StatusMessage::add('Thank you for contacting us');
			return $f3->reroute('/');
		}
	}
	

}

?>
