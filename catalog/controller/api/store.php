<?php

class ControllerApiStore extends Controller {
    
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

        $this->load->model('setting/setting');
        $json = array('success' => true);

        # -- $_GET params ------------------------------
        // Sanitize store_id, though getSetting('config') without a second param typically gets default store config
        if (isset($this->request->get['store'])) {
            $store_id = (int)$this->request->get['store'];
        } else {
            $store_id = 0; // Default store ID
        }

        # -- End $_GET params --------------------------
        
        // If the intention was to get settings for a specific store_id,
        // it should be $this->model_setting_setting->getSetting('config', $store_id);
        // For now, sticking to the original logic of fetching default config.
        $store_settings = $this->model_setting_setting->getSetting('config', $store_id);

        if (empty($store_settings)) {
            header('HTTP/1.1 404 Not Found');
            // If store_id was non-zero and no settings found, or even for store 0 if it's unconfigured
            $json = [ // Re-initialize json for error case
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Store configuration not found for ID: ' . $store_id
                ]
            ];
        } else {
            $json['store'] = $store_settings;
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