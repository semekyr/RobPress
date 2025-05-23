<?php

	namespace Admin;

	class Blog extends AdminController {

		public function index($f3) {
			$posts = $this->Model->Posts->fetchAll();
			$blogs = $this->Model->map($posts,'user_id','Users');
			$blogs = $this->Model->map($posts,array('post_id','Post_Categories','category_id'),'Categories',false,$blogs);
			$f3->set('blogs',$blogs);
		}

		public function delete($f3) {
			$postid = $f3->get('PARAMS.3');

			//Check if post ID is provided
			if (empty($postid)) {
				\StatusMessage::add('Post ID not specified','danger');
				return $f3->reroute('/admin/blog');
			}
			$post = $this->Model->Posts->fetchById($postid);

			//Check if post exists
			if (!$post) {
				\StatusMessage::add('Post does not exist','danger');
				return $f3->reroute('/admin/blog');
			}
			$post->erase();

			//Remove from categories
			$cats = $this->Model->Post_Categories->fetchAll(array('post_id' => $postid));
			foreach($cats as $cat) {
				$cat->erase();
			}	

			\StatusMessage::add('Post deleted successfully','success');
			return $f3->reroute('/admin/blog');
		}

	public function add($f3) {
		if ($this->request->is('post')) {
			$post = $this->Model->Posts;
			extract($this->request->data);

			//Sanitize inputs
			require_once 'vendor/autoload.php';
			$config = \HTMLPurifier_Config::createDefault();
			$config->set('HTML.Allowed', 'b,strong,i,em,a[href|target],p,ul,ol,li,img[src|alt|width|height]');
			$config->set('URI.DisableExternalResources', true);
			$config->set('URI.DisableResources', true);
			$purifier = new \HTMLPurifier($config);

			$post->title = $purifier->purify($title);
			$post->content = $purifier->purify($content);
			$post->summary = $purifier->purify($summary);
			$post->user_id = $this->Auth->user('id');
			$post->created = $post->modified = mydate();

			//Check for errors
			$errors = false;
			if (empty($post->title)) {
				$errors = 'You did not specify a title';
			}

			if ($errors) {
				\StatusMessage::add($errors, 'danger');
			} else {
				//Handle publish date
				if (!isset($Publish)) {
					$post->published = null; //Draft
				} else {
					$post->published = !empty($this->request->data['published'])
						? mydate($this->request->data['published']) //Use provided date
						: mydate(); //Default to current date
				}

				//Save post
				$post->save();
				$postid = $post->id;

				//Assign categories
				$link = $this->Model->Post_Categories;
				if (!isset($categories)) {
					$categories = array();
				}
				foreach ($categories as $category) {
					$link->reset();
					$link->category_id = $category;
					$link->post_id = $postid;
					$link->save();
				}

				\StatusMessage::add('Post added successfully', 'success');
				return $f3->reroute('/admin/blog');
			}
		}

		$categories = $this->Model->Categories->fetchList();
		$f3->set('categories', $categories);
	}

		public function edit($f3) {
			$postid = $f3->get('PARAMS.3');
			if (empty($postid)) {
				\StatusMessage::add('Post ID not specified', 'danger');
				return $f3->reroute('/admin/blog');
			}
		
			$post = $this->Model->Posts->fetchById($postid);
			if (!$post) {
				\StatusMessage::add('Post does not exist', 'danger');
				return $f3->reroute('/admin/blog');
			}
		
			$blog = $this->Model->map($post, array('post_id', 'Post_Categories', 'category_id'), 'Categories', false);
		
			if ($this->request->is('post')) {
				require_once 'vendor/autoload.php';
		
				//Configure HTMLPurifier
				$config = \HTMLPurifier_Config::createDefault();
				$config->set('HTML.Allowed', 'b,strong,i,em,a[href|target],p,ul,ol,li,img[src|alt|width|height]'); // Allow safe tags
				$config->set('URI.DisableExternalResources', true);
				$config->set('URI.DisableResources', true);
				$purifier = new \HTMLPurifier($config);
		
				//Extract and sanitize input
				extract($this->request->data);
				$post->title = $purifier->purify($title);
				$post->content = $purifier->purify($content);
				$post->summary = $purifier->purify($summary);
				$post->modified = mydate();
				$post->user_id = $this->Auth->user('id');
		
				//Determine whether to publish or draft
				if (!isset($Publish)) {
					$post->published = null;
				} else {
					$post->published = mydate($published);
				}
		
				//Save changes
				$post->save();
		
				$link = $this->Model->Post_Categories;
		
				//Remove previous categories
				$old = $link->fetchAll(array('post_id' => $postid));
				foreach ($old as $oldcategory) {
					$oldcategory->erase();
				}
		
				//Assign new categories
				if (!isset($categories)) {
					$categories = array();
				}
		
				//Sanitize categories using HTMLPurifier
				$categories = array_map(function ($category) use ($purifier) {
					return $purifier->purify($category);
				}, $categories);
		
				foreach ($categories as $category) {
					$link->reset();
					$link->category_id = $category;
					$link->post_id = $postid;
					$link->save();
				}
		
				\StatusMessage::add('Post updated successfully', 'success');
				return $f3->reroute('/admin/blog');
			}
		
			$_POST = $post->cast();
			foreach ($blog['Categories'] as $cat) {
				if (!$cat) continue;
				$_POST['categories'][] = $cat->id;
			}
		
			$categories = $this->Model->Categories->fetchList();
			$f3->set('categories', $categories);
			$f3->set('post', $post);
		}
		

	}

?>
