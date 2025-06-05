<?php
// tests/catalog/controller/api/CategoryApiTest.php

use PHPUnit\Framework\TestCase;

// Mock crucial OpenCart classes/dependencies if they are not part of a full OC bootstrap
// For unit tests, we want to isolate the controller.

if (!class_exists('Registry')) {
    class Registry {
        private $data = array();
        public function get($key) { return (isset($this->data[$key]) ? $this->data[$key] : null); }
        public function set($key, $value) { $this->data[$key] = $value; }
        public function has($key) { return isset($this->data[$key]); }
    }
}

if (!class_exists('Loader')) {
    class Loader {
        protected $registry;
        public function __construct($registry) { $this->registry = $registry; }
        public function model($model_path) {
            // Simplified model loading
            // Convert model path like 'catalog/category' to 'mock_model_catalog_category'
            $mock_model_key = 'mock_model_' . str_replace('/', '_', $model_path);
            $this->registry->set('model_' . str_replace('/', '_', $model_path), $this->registry->get($mock_model_key));
        }
        public function language($language){} // Mocked
    }
}

if (!class_exists('Request')) {
    class Request {
        public $get = array();
        public $post = array();
        public $server = array();
        public function __construct() {
            $this->server['REQUEST_METHOD'] = 'GET'; // Default
        }
    }
}

if (!class_exists('Response')) {
    class Response {
        private $output;
        private $headers = array();
        public function addHeader($header) { 
            // Store the full header string, including HTTP status line if present
            $this->headers[] = $header; 
            // Also, try to set HTTP response code if header is status line
            if (strpos($header, 'HTTP/') === 0) {
                $parts = explode(' ', $header);
                if (isset($parts[1])) {
                    // This is a simplification; real PHPUnit testing for headers is more involved
                    // or uses output buffering to capture header() calls.
                }
            }
        }
        public function getHeaders() { return $this->headers; }
        public function setOutput($output) { $this->output = $output; }
        public function getOutput() { return $this->output; }
    }
}
    
if (!class_exists('Url')) {
    class Url {
        public function link($route, $args = '', $secure = false) {
            $url = HTTP_SERVER . 'index.php?route=' . $route;
            if ($args) {
                $url .= '&' . http_build_query($args, '', '&');
            }
            return $url;
        }
    }
}


class CategoryApiTest extends TestCase
{
    private $controller;
    private $registry;
    private $request;
    private $response;
    private $orig_headers_sent; // To store original headers_sent status

    protected function setUp(): void
    {
        $this->registry = new Registry();
        
        $loader = new Loader($this->registry);
        $this->registry->set('load', $loader);

        $this->request = new Request();
        $this->registry->set('request', $this->request);

        $this->response = new Response();
        $this->registry->set('response', $this->response);
        
        // Mock Config
        $config = $this->getMockBuilder(stdClass::class)
                       ->addMethods(['get', 'set']) // Add set if it's ever used by controllers
                       ->getMock();
        $config->method('get')->will($this->returnValueMap([
            ['config_image_category_width', 80],
            ['config_image_category_height', 80],
            // Add other config values if the controller needs them
        ]));
        $this->registry->set('config', $config);
        
        $url = new Url();
        $this->registry->set('url', $url);

        // Controller needs to be instantiated after registry is populated
        $this->controller = new ControllerApiCategory($this->registry);
        
        // Mock headers_sent() to return false by default for tests
        // This requires a bit more advanced mocking if we can't use a library
        // For now, we assume it works or handle it by checking headers array in Response mock
    }

    public function testGetCategorySuccess()
    {
        // Prepare mocks
        $mockModelCatalogCategory = $this->getMockBuilder(stdClass::class)
                                         ->setMethods(['getCategory', 'getCategories', 'getTotalCategoriesByCategoryId']) 
                                         ->getMock();
        $mockModelCatalogCategory->method('getCategory')
                                 ->with($this->equalTo(123))
                                 ->willReturn([
                                     'category_id' => '123',
                                     'name' => 'Test Category',
                                     'description' => 'Test Description',
                                     'image' => 'catalog/test_image.jpg',
                                     'parent_id' => '0',
                                 ]);
        $this->registry->set('mock_model_catalog_category', $mockModelCatalogCategory); // Key matches Loader mock
        
        $mockModelToolImage = $this->getMockBuilder(stdClass::class)
                                   ->setMethods(['resize'])
                                   ->getMock();
        $mockModelToolImage->method('resize')->willReturn('http://localhost/image/cache/catalog/test_image-80x80.jpg');
        $this->registry->set('mock_model_tool_image', $mockModelToolImage); // Key matches Loader mock


        // Set up request
        $this->request->get['apikey'] = 'YOUR_SUPER_SECRET_API_KEY'; // Use the actual key from controller
        $this->request->get['id'] = '123';
        $this->request->server['REQUEST_METHOD'] = 'GET';
        
        // Call the method
        $this->controller->_get();

        // Assertions
        $output = json_decode($this->response->getOutput(), true);

        $this->assertTrue($output['success']);
        $this->assertEquals('123', $output['category']['id']);
        $this->assertEquals('Test Category', $output['category']['name']);
        
        // Check for Content-Type header
        $foundContentTypeHeader = false;
        foreach($this->response->getHeaders() as $header) {
            if (strtolower($header) === 'content-type: application/json') {
                $foundContentTypeHeader = true;
                break;
            }
        }
        $this->assertTrue($foundContentTypeHeader, "Content-Type: application/json header was not set.");
    }

    public function testGetCategoryNotFound()
    {
        $mockModelCatalogCategory = $this->getMockBuilder(stdClass::class)
                                         ->setMethods(['getCategory'])
                                         ->getMock();
        $mockModelCatalogCategory->method('getCategory')
                                 ->with($this->equalTo(999))
                                 ->willReturn(false); 
        $this->registry->set('mock_model_catalog_category', $mockModelCatalogCategory);

        $this->request->get['apikey'] = 'YOUR_SUPER_SECRET_API_KEY';
        $this->request->get['id'] = '999';
        $this->request->server['REQUEST_METHOD'] = 'GET';

        $this->controller->_get();
        
        $output = json_decode($this->response->getOutput(), true);
        $this->assertFalse($output['success']);
        $this->assertEquals(404, $output['error']['code']);
        $this->assertEquals('Category not found with ID: 999', $output['error']['message']);
        
        $foundHeader = false;
        foreach($this->response->getHeaders() as $header) {
            if (strpos($header, 'HTTP/1.1 404 Not Found') !== false) {
                $foundHeader = true;
                break;
            }
        }
        $this->assertTrue($foundHeader, "HTTP/1.1 404 Not Found header was not set.");
    }
    
    public function testGetCategoryInvalidApiKey()
    {
        $this->request->get['apikey'] = 'WRONG_KEY';
        $this->request->get['id'] = '123';
        $this->request->server['REQUEST_METHOD'] = 'GET';

        // No need to mock models as API key check should happen first
        $this->controller->_get(); 

        $output = json_decode($this->response->getOutput(), true);
        $this->assertFalse($output['success']);
        $this->assertEquals(401, $output['error']['code']);
        $this->assertEquals('Unauthorized - API key missing or invalid', $output['error']['message']);
        
        $foundHeader = false;
        foreach($this->response->getHeaders() as $header) {
            if (strpos($header, 'HTTP/1.1 401 Unauthorized') !== false) {
                $foundHeader = true;
                break;
            }
        }
        $this->assertTrue($foundHeader, "HTTP/1.1 401 Unauthorized header was not set.");
    }

    public function testGetCategoryWrongMethod()
    {
        $this->request->get['apikey'] = 'YOUR_SUPER_SECRET_API_KEY';
        $this->request->get['id'] = '123';
        $this->request->server['REQUEST_METHOD'] = 'POST'; // Wrong method

        $this->controller->_get();

        $output = json_decode($this->response->getOutput(), true);
        $this->assertFalse($output['success']);
        $this->assertEquals(405, $output['error']['code']);
        $this->assertStringContainsString('Method Not Allowed', $output['error']['message']);
        
        $foundHeader = false;
        foreach($this->response->getHeaders() as $header) {
            if (strpos($header, 'HTTP/1.1 405 Method Not Allowed') !== false) {
                $foundHeader = true;
                break;
            }
        }
        $this->assertTrue($foundHeader, "HTTP/1.1 405 Method Not Allowed header was not set.");
    }
}
?>
