<?php

class ControllerApiCategory extends Controller {
    
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
    
    private function getCategoriesTree($parent = 0, $level = 1) {
        $this->load->model('catalog/category');
        $this->load->model('tool/image');
                
        $result = array();

        $categories = $this->model_catalog_category->getCategories($parent);

        if ($categories && $level > 0) {
            $level--;

            foreach ($categories as $category) {

                if ($category['image']) {
                    $image = $this->model_tool_image->resize($category['image'], $this->config->get('config_image_category_width'), $this->config->get('config_image_category_height'));
                } else {
                    $image = false;
                }

                $result[] = array(
                    'category_id' => $category['category_id'],
                    'parent_id' => $category['parent_id'],
                    'name' => $category['name'],
                    'image' => $image,
                    'href' => $this->url->link('product/category', 'category_id=' . $category['category_id']),
                    'categories' => $this->getCategoriesTree($category['category_id'], $level)
                );
            }

            return $result;
        }
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

        $this->load->model('catalog/category');
        $this->load->model('tool/image');

        $json = array('success' => true);

        # -- $_GET params ------------------------------
                
        if (isset($this->request->get['id'])) {
            $category_id = (int)$this->request->get['id'];
        } else {
            $category_id = 0;
        }

        # -- End $_GET params --------------------------

        $category_info = $this->model_catalog_category->getCategory($category_id);
                
        if (!$category_info) {
            header('HTTP/1.1 404 Not Found');
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            $this->response->setOutput(json_encode([
                'success' => false,
                'error' => [
                    'code' => 404,
                    'message' => 'Category not found with ID: ' . $category_id
                ]
            ]));
            return;
        }
        
        $json['category'] = array(
            'id' => $category_info['category_id'],
            'name' => $category_info['name'],
            'description' => $category_info['description'],
            'href' => $this->url->link('product/category', 'category_id=' . $category_info['category_id']),
            'image' => $category_info['image'] // Assuming image path is okay as is, or needs resize like in getCategoriesTree
        );

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        if ($this->debug) {
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

        $this->load->model('catalog/category');
        $json = array('success' => true);

        # -- $_GET params ------------------------------
                
        if (isset($this->request->get['parent'])) {
            $parent = (int)$this->request->get['parent'];
        } else {
            $parent = 0;
        }

        if (isset($this->request->get['level'])) {
            $level = (int)$this->request->get['level'];
        } else {
            $level = 1;
        }

        # -- End $_GET params --------------------------
        
        $json['categories'] = $this->getCategoriesTree($parent, $level);

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        if ($this->debug) {
            echo '<pre>';
            print_r($json);
        } else {
            $this->response->setOutput(json_encode($json));
        }
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

        $this->load->model('catalog/category');
        // $this->load->model('tool/image'); // Not used in _count
        $json = array('success' => true);
        
        # -- $_GET params ------------------------------
        if (isset($this->request->get['parent'])) {
            $parent_id = (int)$this->request->get['parent'];
        } else {
            $parent_id = 0;
        }
        # -- End $_GET params --------------------------
        
        $total_categories = $this->model_catalog_category->getTotalCategoriesByCategoryId($parent_id);
        
        $json['data'] = array('count' => $total_categories);
        
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
?>
