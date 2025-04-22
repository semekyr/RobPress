<?php

namespace Admin;
/** The code fetches the count of users, posts and comments from their respective models. 
* It then retrieves the total number of records using fetchCount. 
* Makes them available to view using set. 
*/
class Admin extends AdminController {
	public function index($f3) {
		$users = $this->Model->Users->fetchCount();
		$posts = $this->Model->Posts->fetchCount();
		$comments = $this->Model->Comments->fetchCount();
		$f3->set('users',$users);
		$f3->set('posts',$posts);
		$f3->set('comments',$comments);
	}

}

?>
