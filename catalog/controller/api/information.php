<?php

class ControllerApiInformation extends Controller {
    
    private $apiKey = "YOUR_SUPER_SECRET_API_KEY";
    private $debug = false;

    private function _checkApiKey() {
        if (!isset($this->request->get['apikey']) || $this->request->get['apikey'] !== $this->apiKey) {
            header('HTTP/1.1 401 Unauthorized');
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            $this->response->setOutput(json_encode([
                'success' => false,
                'error' => [
                    'code' => 401,
                    'message' => 'Unauthorized - API key missing or invalid'
                ]
            ]));
            return false;
        }
        return true;
    }
    
    public function _get() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('HTTP/1.1 405 Method Not Allowed');
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            $this->response->setOutput(json_encode([
                'success' => false,
                'error' => [
                    'code' => 405,
                    'message' => 'Method Not Allowed. Only GET is supported for this endpoint.'
                ]
            ]));
            return;
        }

        if (!$this->_checkApiKey()) {
            return;
        }

        $this->load->model('catalog/information');
        $json = array('success' => true);

        # -- $_GET params ------------------------------
                
        if (isset($this->request->get['id'])) {
            $information_id = (int)$this->request->get['id'];
        } else {
            $information_id = 0;
        }

        # -- End $_GET params --------------------------
		
		$information_info = $this->model_catalog_information->getInformation($information_id);
		
		if (!$information_info) {
            header('HTTP/1.1 404 Not Found');
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            $json = [
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Information not found with ID: ' . $information_id
                ]
            ];
        } else {
            $json['information'] = $information_info;
        }
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        if ($this->debug && isset($json['success']) && $json['success']) { // Only print if success, errors already handled
            echo '<pre>';
            print_r($json);
        } else {
            $this->response->setOutput(json_encode($json));
        }
    }
    
    function __call( $methodName, $arguments ) {
        //call_user_func(array($this, str_replace('.', '_', $methodName)), $arguments);
        call_user_func(array($this, "_$methodName"), $arguments);
    }
    
}

?>