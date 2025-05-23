<?php

#[AllowDynamicProperties]
class Controller {

	protected $layout = 'default';
	protected $template;

	public function __construct() {
		$f3=Base::instance();
		$this->f3 = $f3;

		// Connect to the database
		$this->db = new Database();
		$this->Model = new Model($this);

		//Load helpers
		$helpers = array('Auth');
		foreach($helpers as $helper) {
			$helperclass = $helper . "Helper";
			$this->$helper = new $helperclass($this);
		}
	}


	/** This method is executed before routing to a specific controller action. */
	public function beforeRoute($f3) {
		$this->request = new Request();
		//$this->db = new Database();


		//Check user
		$this->Auth->resume();

		//Load settings
		$settings = Settings::getSettings();
		$settings['base'] = $f3->get('BASE');
		
		//Append debug mode to title
		if($settings['debug'] == 1) { $settings['name'] .= ' (Debug Mode)'; }

		$settings['path'] = $f3->get('PATH');
		$this->Settings = $settings;
		$f3->set('site',$settings);

        //Enable backwards compatability
        if($f3->get('PARAMS.*')) { $f3->set('PARAMS.3',$f3->get('PARAMS.*')); }

		//Get the API key from the database (if there is one set)
		$apikey = $this->db->connection->exec('SELECT value FROM settings WHERE setting = "api_key"');
		if ($apikey) {
    		$f3->set('site.apikey', $apikey[0]['value']);
		} else {
    		$f3->set('site.apikey', '');
		}	

		//Extract request data
		extract($this->request->data);

		//Process before route code
		if(isset($beforeCode)) {
			Settings::process($beforeCode);
		}
	}

	/** This method is exectuted after routing to a specific controller action.  */
	public function afterRoute($f3) {	
		//Set page options
		$f3->set('title',isset($this->title) ? $this->title : get_class($this));

		//Prepare default menu	
		$f3->set('menu',$this->defaultMenu());

		//Setup user
		$f3->set('user',$this->Auth->user());

		//Check for admin
		$admin = false;
		if(stripos($f3->get('PARAMS.0'),'admin') !== false) { $admin = true; }

		//Identify action
		$controller = get_class($this);
		if($f3->exists('PARAMS.action')) {
			$action = $f3->get('PARAMS.action');	
		} else {
			$action = 'index';
		}

		//Handle admin actions
		if ($admin) {
			$controller = str_ireplace("Admin\\","",$controller);
			$action = "admin_$action";
		}

		//Handle errors
		if ($controller == 'Error') {
			$action = $f3->get('ERROR.code');
		}

		//Handle custom view
		if(isset($this->action)) {
			$action = $this->action;
		}

		//Extract request data
		extract($this->request->data);

		//Generate content
		$template = "$controller/$action.htm";
		if(isset($this->template)) {
			if(preg_match('!/!',$this->template)) {
				$template = $this->template . ".htm";
			} else {
				$template = "$controller/" . $this->template . ".htm";
			}
		}
		$content = View::instance()->render($template);
		$f3->set('content',$content);

		//Process before route code
		if(isset($afterCode)) {
			Settings::process($afterCode);
		}

		//Set previous URL for rerouting after login
        if ($controller != 'Error' && ($this->request->is('post')) && $f3->get('PATH') != '/user/login') {
            $previousUrl = $f3->get('PATH');
			$encryptedurl = base64_encode(openssl_encrypt($previousUrl, 'aes-256-cbc', SECURE_KEY, 0, base64_decode(SECURE_IV)));
            setcookie('previousUrl', $encryptedurl, time() + 3600 * 24 * 30, '/');
        }

		//Render template
		echo View::instance()->render($this->layout . '.htm');
	}

	public function defaultMenu() {
		$menu = array(
			array('label' => 'Search', 'link' => 'blog/search'),
			array('label' => 'Contact', 'link' => 'contact'),
		);

		//Load pages
		$pages = $this->Model->Pages->fetchAll();
		foreach($pages as $pagetitle=>$page) {
			$pagename = str_ireplace(".html","",$page);
			$menu[] = array('label' => $pagetitle, 'link' => 'page/display/' . $pagename);
		}

		//Add admin menu items
		if ($this->Auth->user('level') > 1) {
			$menu[] = array('label' => 'Admin', 'link' => 'admin');
		}

		return $menu;
	}

}

?>
