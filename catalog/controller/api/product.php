<?php
class ControllerApiProduct extends Controller {
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

        $this->load->model('catalog/product');
        $json_response = array();

        // Input Collection & Basic Validation
        $model = isset($this->request->post['model']) ? $this->request->post['model'] : null;
        $name = isset($this->request->post['name']) ? $this->request->post['name'] : null;

        if (empty($model) || empty($name)) {
            header('HTTP/1.1 400 Bad Request');
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            $json_response = [
                'success' => false,
                'error' => [
                    'code' => 400,
                    'message' => 'Missing required fields: model and/or name'
                ]
            ];
            $this->response->setOutput(json_encode($json_response));
            return;
        }

        // Input Sanitization
        $sanitized_model = htmlspecialchars($model, ENT_QUOTES, 'UTF-8');
        $sanitized_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $sanitized_price = isset($this->request->post['price']) ? (float)$this->request->post['price'] : 0.0;
        $sanitized_quantity = isset($this->request->post['quantity']) ? (int)$this->request->post['quantity'] : 0;
        $sanitized_status = isset($this->request->post['status']) ? (int)$this->request->post['status'] : 0;

        // Data Preparation for OpenCart Model
        $data = array();
        $data['model'] = $sanitized_model;
        $data['product_description'] = array(
            1 => array( // Assuming language_id 1
                'name' => $sanitized_name,
                'description' => '', // Default empty description
                'meta_title' => $sanitized_name, // Default meta_title to name
                'meta_description' => '',
                'meta_keyword' => '',
                'tag' => ''
            )
        );
        $data['price'] = $sanitized_price;
        $data['quantity'] = $sanitized_quantity;
        $data['status'] = $sanitized_status;

        $data['product_store'] = array(0); // Associate with default store
        $this->load->model('localisation/stock_status');
        $stock_statuses = $this->model_localisation_stock_status->getStockStatuses();
        $data['stock_status_id'] = ($sanitized_quantity > 0) ? 7 : 5; // 7 for 'In Stock', 5 for 'Out of Stock'
        
        $found_stock_status = false;
        foreach ($stock_statuses as $ss) {
            if ($ss['stock_status_id'] == $data['stock_status_id']) {
                $found_stock_status = true;
                break;
            }
        }
        if (!$found_stock_status) {
            $data['stock_status_id'] = !empty($stock_statuses) ? $stock_statuses[0]['stock_status_id'] : 0;
        }

        $data['manufacturer_id'] = 0;
        $data['shipping'] = 1; 
        $data['tax_class_id'] = 0;
        $data['date_available'] = date('Y-m-d');
        $data['weight'] = '';
        $data['weight_class_id'] = $this->config->get('config_weight_class_id') ? $this->config->get('config_weight_class_id') : 1;
        $data['length'] = '';
        $data['width'] = '';
        $data['height'] = '';
        $data['length_class_id'] = $this->config->get('config_length_class_id') ? $this->config->get('config_length_class_id') : 1;
        $data['subtract'] = 1;
        $data['minimum'] = 1;
        $data['sort_order'] = 1;
        $data['sku'] = '';
        $data['upc'] = '';
        $data['ean'] = '';
        $data['jan'] = '';
        $data['isbn'] = '';
        $data['mpn'] = '';
        $data['location'] = '';
        $data['points'] = 0;
        $data['product_layout'] = array();

        // Call Model
        $product_id = $this->model_catalog_product->addProduct($data);

        if ($product_id) {
            header('HTTP/1.1 201 Created');
            $json_response = [
                'success' => true,
                'product_id' => $product_id,
                'message' => 'Product added successfully'
            ];
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            $json_response = [
                'success' => false,
                'error' => [
                    'code' => 500,
                    'message' => 'Failed to add product due to a server error.'
                ]
            ];
        }
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        $this->response->setOutput(json_encode($json_response));
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

        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        $json = array('success' => true);

        # -- $_GET params ------------------------------
                
        if (isset($this->request->get['id'])) {
            $product_id = (int)$this->request->get['id'];
        } else {
            $product_id = 0;
        }

        # -- End $_GET params --------------------------

        $product = $this->model_catalog_product->getProduct($product_id);

        if (!$product) {
            header('HTTP/1.1 404 Not Found');
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            $json = [ // Re-initialize json for error case
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Product not found with ID: ' . $product_id
                ]
            ];
            $this->response->setOutput(json_encode($json));
            return;
        }

        // If product is found, continue to build the success response
        // The $json = array('success' => true); was already initialized

        # product image
        if ($product['image']) {
            $image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_popup_width'), $this->config->get('config_image_popup_height'));
        } else {
            $image = '';
        }

        #additional images
        $additional_images = $this->model_catalog_product->getProductImages($product['product_id']);
        $images = array();

        foreach ($additional_images as $additional_image) {
            $images[] = $this->model_tool_image->resize($additional_image, $this->config->get('config_image_additional_width'), $this->config->get('config_image_additional_height'));
        }

        #specal
        if ((float)$product['special']) {
            $special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')));
        } else {
            $special = false;
        }

        #discounts
        $discounts = array();
        $data_discounts = $this->model_catalog_product->getProductDiscounts($product['product_id']);

        foreach ($data_discounts as $discount) {
            $discounts[] = array(
                'quantity' => $discount['quantity'],
                'price' => $this->currency->format($this->tax->calculate($discount['price'], $product['tax_class_id'], $this->config->get('config_tax')))
            );
        }

        // options
        $options = array();

        foreach ($this->model_catalog_product->getProductOptions($product['product_id']) as $option) {
            if ($option['type'] == 'select' || $option['type'] == 'radio' || $option['type'] == 'checkbox' || $option['type'] == 'image') {
                $option_value_data = array();
                                
                foreach ($option['option_value'] as $option_value) {
                    if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
                        if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
                            $price = $this->currency->format($this->tax->calculate($option_value['price'], $product['tax_class_id'], $this->config->get('config_tax')));
                        } else {
                            $price = false;
                        }
                                                
                        $option_value_data[] = array(
                            'product_option_value_id' => $option_value['product_option_value_id'],
                            'option_value_id' => $option_value['option_value_id'],
                            'name' => $option_value['name'],
                            'image' => $this->model_tool_image->resize($option_value['image'], 50, 50),
                            'price' => $price,
                            'price_prefix' => $option_value['price_prefix']
                        );
                    }
                }
                                
                $options[] = array(
                    'product_option_id' => $option['product_option_id'],
                    'option_id' => $option['option_id'],
                    'name' => $option['name'],
                    'type' => $option['type'],
                    'option_value' => $option_value_data,
                    'required' => $option['required']
                );                                        
            } elseif ($option['type'] == 'text' || $option['type'] == 'textarea' || $option['type'] == 'file' || $option['type'] == 'date' || $option['type'] == 'datetime' || $option['type'] == 'time') {
                $options[] = array(
                    'product_option_id' => $option['product_option_id'],
                    'option_id' => $option['option_id'],
                    'name' => $option['name'],
                    'type' => $option['type'],
                    'option_value' => $option['option_value'],
                    'required' => $option['required']
                );                                                
            }
        }

        #minimum
        if ($product['minimum']) {
            $minimum = $product['minimum'];
        } else {
            $minimum = 1;
        }

        $json['product'] = array(
            'id' => $product['product_id'],
            'name' => $product['name'],
            'description' => html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'),
            'meta_description' => $product['meta_description'],
            'meta_keyword' => $product['meta_keyword'],
            'tag' => $product['tag'],
            'model' => $product['model'],
            'sku' => $product['sku'],
            'upc' => $product['upc'],
            'ean' => $product['ean'],
            'jan' => $product['jan'],
            'isbn' => $product['isbn'],
            'mpn' => $product['mpn'],
            'location' => $product['location'],
            'quantity' => $product['quantity'],
            'stock_status' => $product['stock_status'],
            'image' => $image,
            'images' => $images,
            'manufacturer_id' => $product['manufacturer_id'],
            'manufacturer' => $product['manufacturer'],
            // $product['price'];
            'price' => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
            // $product['special'];
            'special' => $special,
            'reward' => $product['reward'],
            'points' => $product['points'],
            'tax_class_id' => $product['tax_class_id'],
            'date_available' => $product['date_available'],
            'weight' => $product['weight'],
            'weight_class_id' => $product['weight_class_id'],
            'length' => $product['length'],
            'width' => $product['width'],
            'height' => $product['height'],
            'length_class_id' => $product['length_class_id'],
            'subtract' => $product['subtract'],
            'rating' => (int)$product['rating'],
            'reviews' => (int)$product['reviews'],
            'minimum' => $minimum,
            'sort_order' => $product['sort_order'],
            'status' => $product['status'],
            'date_added' => $product['date_added'],
            'date_modified' => $product['date_modified'],
            'viewed' => $product['viewed'],
            'discounts' => $discounts,
            'options' => $options,
            'attribute_groups' => $this->model_catalog_product->getProductAttributes($product['product_id'])
        );


        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        if ($this->debug) { // Success is implied if we reach here
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

        $this->load->model('catalog/product');
        $this->load->model('tool/image');
        $json = array('success' => true, 'products' => array());
        
        // -- $_GET params ------------------------------
        if (isset($this->request->get['category'])) {
            $category_id = (int)$this->request->get['category'];
        } else {
            $category_id = 0;
        }
        # -- End $_GET params --------------------------

        $products = $this->model_catalog_product->getProducts(array(
            'filter_category_id'        => $category_id
        ));
        
        foreach ($products as $product) {

            if ($product['image']) {
                $image = $this->model_tool_image->resize($product['image'], $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'));
            } else {
                $image = false;
            }

            $json['products'][] = array(
                'id' => $product['product_id'],
                'name' => $product['name'],
                'price' => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))),
                'thumb' => $image,
            );
        }
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        $this->response->setOutput(json_encode($json));
    }
    
    public function _count() {
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

        $this->load->model('catalog/product');
        // $this->load->model('tool/image'); // Not used in _count
        $json = array('success' => true);
        
        // -- $_GET params ------------------------------
        if (isset($this->request->get['category'])) {
            $category_id = (int)$this->request->get['category'];
        } else {
            $category_id = 0;
        }
        // -- End $_GET params --------------------------
        
        $total_products = $this->model_catalog_product->getTotalProducts(array(
            'filter_category_id' => $category_id
        ));
        
        $json['data'] = array('count' => $total_products);
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        $this->response->setOutput(json_encode($json));
    }
    
    function __call( $methodName, $arguments ) {
        //call_user_func(array($this, str_replace('.', '_', $methodName)), $arguments);
        call_user_func(array($this, "_$methodName"), $arguments);
    }
 }
