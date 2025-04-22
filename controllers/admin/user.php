<?php

namespace Admin;

class User extends AdminController {

	public function index($f3) {
		$users = $this->Model->Users->fetchAll();
		$f3->set('users',$users);
	}

	/** Handles the export of user data to a CSV file. It fetches all users from 
	 * the Users model and writes them to a CSV file. The method first write the column headings to the CSV file using the fputcsv function.
	 * 
	 */
	public function export($f3) {
		$users = $this->Model->Users->fetchAll();
		$fp = fopen('export.csv', 'w');

		//Add headings
		$headings = ['ID','Username','Display Name','Email','Level','Created'];
		fputcsv($fp,$headings);

		//Add users
		foreach($users as $user) {			
			$fields = [$user->id,$user->username,$user->displayname,$user->email,$user->level,$user->created];
			fputcsv($fp,$fields);
		}
		fclose($fp);

		//Output file
		if(file_exists("export.csv")) {
			header("Content-type: text/csv");
			header("Content-Disposition: attachment; filename=file.csv");
			header("Pragma: no-cache");
			header("Expires: 0");
			echo file_get_contents('export.csv');
			unlink('export.csv');
			exit();
		} else {
			\StatusMessage::add('Export of users failed','danger');
		}
	}

	public function edit($f3) {    
		$id = $f3->get('PARAMS.3');
		$u = $this->Model->Users->fetch($id);
	
		if ($this->request->is('post')) {
			//Sanitize inputs
			$username = h($this->request->data['username'] ?? '');
			$email = h($this->request->data['email'] ?? '');
			$displayname = h($this->request->data['displayname'] ?? '');
			$bio = h($this->request->data['bio'] ?? '');
			$password = h($this->request->data['password'] ?? '');
			$level = h($this->request->data['level'] ?? '');

	
			//Update user object only if there is a change
			$updated = false;
			if ($u->username !== $username) {
				$u->username = $username;
				$updated = true;
			}
			if ($u->email !== $email) {
				$u->email = $email;
				$updated = true;
			}
			if ($u->displayname !== $displayname) {
				$u->displayname = $displayname;
				$updated = true;
			}
			if ($u->bio !== $bio) {
				$u->bio = $bio;
				$updated = true;
			}
			if (is_numeric($level) && ($level == 1 || $level == 2) && $u->level !== $level) {
				$u->level = $level;
				$updated = true;
			}
			if (!empty($password)) {
				$u->setPassword($password); //Hash the password securely
				$updated = true;
			}
	
			if ($updated) {
				$u->save();
				\StatusMessage::add('User updated successfully', 'success');
			} else {
				\StatusMessage::add('No changes detected', 'info');
			}
			return $f3->reroute('/admin/user');
		}
	
		//Sanitize user data before passing it to the view
		$u->username = h($u->username);
		$u->email = h($u->email);
		$u->displayname = h($u->displayname);
		$u->bio = h($u->bio);
		$u->level = h($u->level);
	
		$_POST = $u->cast();
		$f3->set('u', $u);
	}
	

	public function delete($f3) {
		$id = $f3->get('PARAMS.3');
		$u = $this->Model->Users->fetch($id);

		//Checks if the user is trying to delete themsevles. If so, it adds a status message and redirects to the user admin page.
		if($id == $this->Auth->user('id')) {
			\StatusMessage::add('You cannot remove yourself','danger');
			return $f3->reroute('/admin/user');
		}

		//Remove all posts and comments
		$posts = $this->Model->Posts->fetchAll(array('user_id' => $id));
		foreach($posts as $post) {
			$post_categories = $this->Model->Post_Categories->fetchAll(array('post_id' => $post->id));
			foreach($post_categories as $cat) {
				$cat->erase();
			}
			$post->erase();
		}
		$comments = $this->Model->Comments->fetchAll(array('user_id' => $id));
		foreach($comments as $comment) {
			$comment->erase();
		}
		$u->erase();

		\StatusMessage::add('User has been removed','success');
		return $f3->reroute('/admin/user');
	}


}

?>
