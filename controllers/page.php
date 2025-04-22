<?php

class Page extends Controller {
	/** This method reconstructs the page variable. It removes the .html extension, replaces
	 * underscores with spaces and capitalizes the first letter of each word.
	 * If the page does not exist, it will display an error message.
	 * If the page name is blank, it will display an error message.
	 */
	function display($f3) {
		$pagename = ($f3->get('PARAMS.3'));
		if (empty($pagename)) {
			\StatusMessage::add('Page name cannot be blank.', 'danger');
			return $f3->reroute('/');
		}
		$page = urldecode($pagename);
		$page = $this->Model->Pages->fetch($pagename);
		if (empty($page) || $page === false) {
			\StatusMessage::add('Page does not exist.', 'danger');
			return $f3->reroute('/');
		}
		$pagetitle = ucfirst(str_replace("_"," ",str_replace(".html","",$pagename)));
		$f3->set('pagetitle',$pagetitle);
		$f3->set('page',$page);
	}

}

?>
