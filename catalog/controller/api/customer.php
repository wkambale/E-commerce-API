<?php

class ControllerApiCustomer extends Controller {
    
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

        $this->load->model('account/customer');
        $json = array('success' => true);

        # -- $_GET params ------------------------------
        $customer_id = 0;
        $email = '';
        $token = '';
                
        if (isset($this->request->get['id'])) {
            $customer_id = (int)$this->request->get['id'];
            $customer = $this->model_account_customer->getCustomer($customer_id);
        } else if (isset($this->request->get['email'])) {
            $email = filter_var($this->request->get['email'], FILTER_SANITIZE_EMAIL);
            $customer = $this->model_account_customer->getCustomerByEmail($email);
        } else if (isset($this->request->get['token'])) {
            $token = htmlspecialchars($this->request->get['token'], ENT_QUOTES, 'UTF-8');
            $customer = $this->model_account_customer->getCustomerByToken($token);
        } else {
            $customer = $this->model_account_customer->getCustomer($customer_id); // Default case if no valid param
        }

        # -- End $_GET params --------------------------

        if ($customer) {
            $json['customer'] = array(
                'customer_id' => $customer['customer_id'],
                'store_id' => $customer['store_id'],
                'firstname' => $customer['firstname'],
                'lastname' => $customer['lastname'],
                'email' => $customer['email'],
                'telephone' => $customer['telephone'],
                'fax' => $customer['fax'],
                // Do not include password and salt in the response for security reasons
            );
        } else {
            header('HTTP/1.1 404 Not Found');
            $json = [
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Customer not found with the provided criteria.'
                ]
            ];
        }

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        if ($this->debug && $json['success']) { // Only print if success, errors already handled
            echo '<pre>';
            print_r($json);
        } else {
            $this->response->setOutput(json_encode($json));
        }
    }
    
    public function _list() {
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

        $this->load->model('sale/customer');
        $json = array('success' => true, 'customers' => array());
        
        # -- $_GET params ------------------------------
        if (isset($this->request->get['category'])) { // Assuming 'category' refers to category_id for filtering, though model used is sale/customer
            $category_id = (int)$this->request->get['category'];
        } else {
            $category_id = 0;
        }
        # -- End $_GET params --------------------------
        
        // The model_sale_customer->getCustomers() does not seem to use category_id.
        // If filtering by category_id was intended, the model or data array would need adjustment.
        // For now, $category_id is sanitized but not directly used by this model call.
        $customers = $this->model_sale_customer->getCustomers(array(
            //'filter_name'        => ''
        ));
        
        foreach ($customers as $customer) {
            $json['customers'][] = array(
                'id' => $customer['customer_id'],
                'firstname' => $customer['firstname'],
                'lastname' => $customer['lastname'],
                'email' => $customer['email'],
            );
        }
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        $this->response->setOutput(json_encode($json));
    }
    
    public function _add() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            $this->response->setOutput(json_encode([
                'success' => false,
                'error' => [
                    'code' => 405,
                    'message' => 'Method Not Allowed. Only POST is supported for this endpoint.'
                ]
            ]));
            return;
        }

        if (!$this->_checkApiKey()) {
            return;
        }

        $this->load->model('account/customer');
        $json_response = array(); // Use a dedicated variable for the final response
        $data = array();
        
        # -- $_POST params for customer data ------------------------------
        // Check for required fields first
        if (empty($this->request->post['firstname']) || empty($this->request->post['lastname']) || empty($this->request->post['email']) || empty($this->request->post['password'])) {
            header('HTTP/1.1 400 Bad Request');
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            $json_response = [
                'success' => false, 
                'error' => [
                    'code' => 400,
                    'message' => 'Missing required fields: firstname, lastname, email, password.'
                ]
            ];
            $this->response->setOutput(json_encode($json_response));
            return;
        }

        // All required fields are present, now sanitize and collect them
        $data['firstname'] = htmlspecialchars($this->request->post['firstname'], ENT_QUOTES, 'UTF-8');
        $data['lastname'] = htmlspecialchars($this->request->post['lastname'], ENT_QUOTES, 'UTF-8');
        $data['email'] = filter_var($this->request->post['email'], FILTER_SANITIZE_EMAIL);
        $data['password'] = $this->request->post['password']; // Password taken as is for hashing by the model
        
        // Validate email format after sanitizing
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            header('HTTP/1.1 400 Bad Request');
             if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            $json_response = [
                'success' => false,
                'error' => [
                    'code' => 400,
                    'message' => 'Invalid email format.'
                ]
            ];
            $this->response->setOutput(json_encode($json_response));
            return;
        }


        $data['telephone'] = '';
        $data['fax'] = '';
        $data['company'] = '';
        $data['company_id'] = '';
        $data['tax_id'] = '';
        $data['address_1'] = '';
        $data['address_2'] = '';
        $data['city'] = '';
        $data['postcode'] = '';
        $data['country_id'] = '';
        $data['zone_id'] = '';
        # -- End $_POST params --------------------------
        
        // The addCustomer method in OpenCart doesn't directly return the new customer_id or success status in a clear way for API.
        // It triggers events but the direct return is void. We add, then fetch.
        $this->model_account_customer->addCustomer($data);
        $new_customer_info = $this->model_account_customer->getCustomerByEmail($data['email']);

        if ($new_customer_info && isset($new_customer_info['customer_id'])) {
            header('HTTP/1.1 201 Created');
            $json_response = [
                'success' => true,
                'customer_id' => $new_customer_info['customer_id'],
                'message' => 'Customer added successfully'
            ];
        } else {
            // This case might indicate an issue with addCustomer or immediate retrieval
            header('HTTP/1.1 500 Internal Server Error');
            $json_response = [
                'success' => false,
                'error' => [
                    'code' => 500,
                    'message' => 'Failed to add customer or retrieve confirmation due to a server error.'
                ]
            ];
        }
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        $this->response->setOutput(json_encode($json_response));
    }
    
    function __call( $methodName, $arguments ) {
        //call_user_func(array($this, str_replace('.', '_', $methodName)), $arguments);
        call_user_func(array($this, "_$methodName"), $arguments);
    }
    
}

?>