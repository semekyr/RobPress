<?php

	namespace Admin;

	class Category extends AdminController {


		/** It calculates the number of posts for each category using fetchCount.
		 * The counts are then stored in the counts array, with the category ID as the key. 
		 * It then sets the categories and counts variables to be available to the view.
		 */
		public function index($f3) {
			$categories = $this->Model->Categories->fetchAll();
			$counts = array();
			foreach($categories as $category) {
				$counts[$category->id] = $this->Model->Post_Categories->fetchCount(array('category_id' => $category->id));
			}
			$f3->set('categories',$categories);
			$f3->set('counts',$counts);
		}

		public function add($f3) {
			if ($this->request->is('post')) {
				require_once 'vendor/autoload.php';
		
				//Configure HTMLPurifier
				$config = \HTMLPurifier_Config::createDefault();
				$config->set('HTML.Allowed', ''); // Disallow all HTML tags
				$config->set('Core.EscapeInvalidTags', true);
				$purifier = new \HTMLPurifier($config);
		
				//Sanitize title input
				$category = $this->Model->Categories;
				$category->title = $purifier->purify($this->request->data['title']);
		
				//Check for errors
				if (empty($category->title)) {
					\StatusMessage::add('Category title cannot be blank', 'danger');
					return $f3->reroute('/admin/category');
				}
		
				//Save sanitized category
				$category->save();
		
				\StatusMessage::add('Category added successfully', 'success');
				return $f3->reroute('/admin/category');
			}
		}
		
		/** It manages the deletion of a category. It retrieves the category ID and fetches the corresponding category from the Categories and deletes it.
		 * It removes any associations between the category and posts by fetching and deleting all related 
		 * records from the Post_Categories model. */
		public function delete($f3) {
			$categoryid = $f3->get('PARAMS.3');
			$category = $this->Model->Categories->fetchById($categoryid);
			$category->erase();

			//Delete links		
			$links = $this->Model->Post_Categories->fetchAll(array('category_id' => $categoryid));
			foreach($links as $link) { $link->erase(); } 
	
			\StatusMessage::add('Category deleted successfully','success');
			return $f3->reroute('/admin/category');
		}

		/** Can change the title of the category.
		 * It retrieves the category ID from the URL parameters and fetches the corresponding category from the Categories model.
		 * If the request is a POST, it updates the category title and saves it to the database. 
		 * It then adds a status message and redirects to the category admin page.
		 */
		public function edit($f3) {
			$categoryid = $f3->get('PARAMS.3');
			$category = $this->Model->Categories->fetchById($categoryid);
		
			//Ensure the category exists
			if (!$category) {
				\StatusMessage::add('Category not found', 'danger');
				return $f3->reroute('/admin/category');
			}
		
			if ($this->request->is('post')) {
				require_once 'vendor/autoload.php';
		
				//Configure HTMLPurifier
				$config = \HTMLPurifier_Config::createDefault();
				$config->set('HTML.Allowed', ''); // Disallow all HTML tags
				$config->set('Core.EscapeInvalidTags', true); // Escape or strip invalid tags
				$purifier = new \HTMLPurifier($config);
		
				// Sanitize title input
				$category->title = $purifier->purify($this->request->data['title']);
		
				//Validate input
				if (empty($category->title)) {
					\StatusMessage::add('Category title cannot be blank', 'danger');
					return $f3->reroute('/admin/category');
				}
		
				//Save sanitized category
				$category->save();
				\StatusMessage::add('Category updated successfully', 'success');
				return $f3->reroute('/admin/category');
			}
		
			$f3->set('category', $category);
		}
		


	}

?>
