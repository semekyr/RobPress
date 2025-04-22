<?php

class Errorer extends Controller {

	public function __construct() {
		parent::__construct();
	}
	/** The first step loads the application settings.
	 * The settings are then updated with the base URL and the current path.
	 * The updated settings are then stored and also set in the framework instance under the 'site' key.
	 * This makes the settings available to the view. 
	 */
	public function errorer($f3) {

		//Load settings
		$settings = Settings::getSettings();
		$settings['base'] = $f3->get('BASE');
		$settings['path'] = $f3->get('PATH');
		$this->Settings = $settings;
		$f3->set('site',$settings);
		$f3->set('title','Fatal Error');

		//Display error
		echo View::instance()->render('Errorer/errorer.htm');
	}

}

?>
