<?php
// tests/bootstrap.php

// Define a minimal Controller class if not available
if (!class_exists('Controller')) {
    class Controller {
        protected $registry;
        public function __construct($registry) {
            $this->registry = $registry;
        }
        public function __get($key) {
            return $this->registry->get($key);
        }
    }
}

// Define a minimal Model class
if (!class_exists('Model')) {
    class Model {
        protected $registry;
        public function __construct($registry) {
            $this->registry = $registry;
        }
        public function __get($key) {
            return $this->registry->get($key);
        }
    }
}

// Autoload controller being tested (adjust path as necessary if test execution changes CWD)
// This is a simplified autoloader. A proper PSR-4 autoloader would be better.
spl_autoload_register(function ($class_name) {
    // Try to load API controllers
    $api_controller_filename = lcfirst(str_replace('ControllerApi', '', $class_name));
    $file = dirname(__DIR__) . '/catalog/controller/api/' . $api_controller_filename . '.php';
    if (strpos($class_name, 'ControllerApi') === 0 && file_exists($file)) {
        require_once $file;
        return true;
    }
    // Try to load models (very basic)
    $model_filename = lcfirst(str_replace('ModelCatalog', '', $class_name));
    $model_file_catalog = dirname(__DIR__) . '/catalog/model/catalog/' . $model_filename . '.php';
     if (strpos($class_name, 'ModelCatalog') === 0 && file_exists($model_file_catalog)) {
        require_once $model_file_catalog;
        return true;
    }
    return false;
});

// Manually include the specific controller file for the test,
// as OpenCart's autoloader/structure is complex.
// This path assumes the phpunit command is run from the repository root.
// For CategoryApiTest, we need ControllerApiCategory
if (file_exists('catalog/controller/api/category.php')) {
    require_once 'catalog/controller/api/category.php';
}
?>
