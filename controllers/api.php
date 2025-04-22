<?php

class Api extends Controller {
    /** This function is responsible for handling API requests and returning
     * JSON responses. It begins by extracting the parameters from the URL. It
     * then checks for the authentication token and fails if it is not present.
     * This verifies if the token parameter is set and if its is, if it matches the API key stored in the 
     * framework's configuration (site.apikey). 
     * If the token is missing or incorrect, the method returns a JSON response with a 403 error. 
     * If the authentication succeeds, the method gives API access. 
     * If the id parameter is not set, it fetches all records using fetchAll (genericmodel) and stores them in an array.
     * If the array is empty, it returns a 404 error.
     */
    public function display($f3) {
        extract($f3->get('PARAMS'));
        extract($f3->get('GET'));

        // Fetch API key from settings 
        $apikey = $this->Model->Settings->getSetting('api_key');

        // Check for authentication token and fail without
        if (empty($token) || $token !== $apikey) {
            echo json_encode(array('error' => '403')); die();
        }

        // Provide API access
        if (!isset($id)) {
            $results = array();
            $result = $this->Model->$model->fetchAll();
            foreach ($result as $r) {
                $results[] = $r->cast();
            }
        } else {
            $result = $this->Model->$model->fetch($id);
            $results = $result->cast();
        }

        // File not found
        if (empty($results)) { 
            echo json_encode(array('error' => '404')); die();
        }
        
        echo json_encode($results);
        exit();
    }
}

?>