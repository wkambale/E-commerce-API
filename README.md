# E-commerce API

## Introduction

This repository provides comprehensive documentation for the E-commerce API, built for OpenCart. This API allows developers to interact with core e-commerce functionalities such as managing products, categories, customers, and more.

## Table of Contents

1.  [Installation](#installation)
2.  [Configuration](#configuration)
3.  [Authentication](#authentication)
4.  [API Endpoints](#api-endpoints)
    *   [Products](#products)
        *   [Get Product (`product/get`)](#get-product-productget)
        *   [List Products (`product/list`)](#list-products-productlist)
        *   [Count Products (`product/count`)](#count-products-productcount)
        *   [Add Product (`product/add`)](#add-product-productadd)
    *   [Categories](#categories)
        *   [Get Category (`category/get`)](#get-category-categoryget)
        *   [List Categories (`category/list`)](#list-categories-categorylist)
        *   [Count Categories (`category/count`)](#count-categories-categorycount)
    *   [Customers](#customers)
        *   [Get Customer (`customer/get`)](#get-customer-customerget)
        *   [List Customers (`customer/list`)](#list-customers-customerlist)
        *   [Add Customer (`customer/add`)](#add-customer-customeradd)
    *   [Store](#store)
        *   [Get Store Information (`store/get`)](#get-store-information-storeget)
    *   [Information](#information)
        *   [Get Information Page (`information/get`)](#get-information-page-informationget)
5.  [Error Handling](#error-handling)
6.  [Features & Roadmap](#features--roadmap)
7.  [Contributing](#contributing)
8.  [License](#license)

## Installation

1.  **Copy Controllers**: Place the API controller files (e.g., `product.php`, `category.php`, etc.) into your OpenCart installation directory at `catalog/controller/api/`.
2.  **Enable API Access**: Ensure that API access is enabled in your OpenCart settings (if applicable, depending on your OpenCart version and any specific API management extensions).
3.  **Permissions**: Verify file permissions if you encounter any issues.

## Configuration

### API Key

The API currently uses a hardcoded API key for authentication. This key is defined as a private property within each controller class (e.g., `private $apiKey = "YOUR_SUPER_SECRET_API_KEY";` in `catalog/controller/api/category.php`).

**To change the API key:** You must manually edit each API controller file and update the `$apiKey` property.

*Future Improvement*: Store the API key in the OpenCart database or a configuration file for easier management.

## Authentication

All API requests must be authenticated using an API key. The API key is passed as a query parameter named `apikey` in the request URL.

Example: `https://yourstore.com/index.php?route=api/product/list&apikey=YOUR_SUPER_SECRET_API_KEY`

If the API key is missing or invalid, the API will respond with a `401 Unauthorized` error.

## API Endpoints

The base URL for all API endpoints is: `https://yourstore.com/index.php?route=api/`

Replace `https://yourstore.com/` with the actual URL of your OpenCart store.

---

### Products

#### Get Product (`product/get`)

*   **Description**: Retrieves details for a specific product.
*   **Endpoint Path**: `product/get`
*   **HTTP Method**: `GET`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `id` (query, integer, required): The ID of the product to retrieve.
*   **Example Request (cURL)**:
    ```bash
    curl -X GET "https://yourstore.com/index.php?route=api/product/get&apikey=YOUR_SUPER_SECRET_API_KEY&id=42"
    ```
*   **Example Success Response (JSON - Status 200 OK)**:
    ```json
    {
        "success": true,
        "product": {
            "id": "42",
            "name": "Test Product",
            "description": "This is a test product description.",
            "model": "TEST01",
            "price": "100.00"
        }
    }
    ```
*   **Example Error Responses (JSON)**:
    *   `401 Unauthorized`:
        ```json
        {
            "success": false,
            "error": {
                "code": 401,
                "message": "Unauthorized - API key missing or invalid"
            }
        }
        ```
    *   `404 Not Found`:
        ```json
        {
            "success": false,
            "error": {
                "code": 404,
                "message": "Product not found with ID: 999"
            }
        }
        ```
    *   `405 Method Not Allowed`:
        ```json
        {
            "success": false,
            "error": {
                "code": 405,
                "message": "Method Not Allowed. Only GET is supported for this endpoint."
            }
        }
        ```

#### List Products (`product/list`)

*   **Description**: Retrieves a list of products, optionally filtered by category.
*   **Endpoint Path**: `product/list`
*   **HTTP Method**: `GET`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `category` (query, integer, optional): The category ID to filter products by. If not provided, lists all products.
*   **Example Request (cURL)**:
    ```bash
    curl -X GET "https://yourstore.com/index.php?route=api/product/list&apikey=YOUR_SUPER_SECRET_API_KEY&category=20"
    ```
*   **Example Success Response (JSON - Status 200 OK)**:
    ```json
    {
        "success": true,
        "products": [
            {
                "id": "42",
                "name": "Product Alpha",
                "price": "99.9900",
                "thumb": "https://yourstore.com/image/cache/catalog/demo/product_alpha-100x100.jpg"
            },
            {
                "id": "43",
                "name": "Product Beta",
                "price": "120.5000",
                "thumb": "https://yourstore.com/image/cache/catalog/demo/product_beta-100x100.jpg"
            },
        ]
    }
    ```
*   **Example Error Responses**: See `product/get`.

#### Count Products (`product/count`)

*   **Description**: Counts products, optionally filtered by category.
*   **Endpoint Path**: `product/count`
*   **HTTP Method**: `GET`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `category` (query, integer, optional): The category ID to filter by. If not provided, counts all products.
*   **Example Request (cURL)**:
    ```bash
    curl -X GET "https://yourstore.com/index.php?route=api/product/count&apikey=YOUR_SUPER_SECRET_API_KEY&category=20"
    ```
*   **Example Success Response (JSON - Status 200 OK)**:
    ```json
    {
        "success": true,
        "data": {
            "count": 15
        }
    }
    ```
*   **Example Error Responses**: See `product/get`.

#### Add Product (`product/add`)

*   **Description**: Adds a new product to the store.
*   **Endpoint Path**: `product/add`
*   **HTTP Method**: `POST`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `model` (POST body, string, required): Product model.
    *   `name` (POST body, string, required): Product name.
    *   `price` (POST body, float, optional, default: 0.0): Product price.
    *   `quantity` (POST body, integer, optional, default: 0): Product quantity.
    *   `status` (POST body, integer, optional, default: 0): Product status (0=Disabled, 1=Enabled).
*   **Example Request (cURL)**:
    ```bash
    curl -X POST "https://yourstore.com/index.php?route=api/product/add&apikey=YOUR_SUPER_SECRET_API_KEY" \
    -d "model=NEWPROD01" \
    -d "name=My New Awesome Product" \
    -d "price=49.99" \
    -d "quantity=100" \
    -d "status=1"
    ```
*   **Example Success Response (JSON - Status 201 Created)**:
    ```json
    {
        "success": true,
        "product_id": 51,
        "message": "Product added successfully"
    }
    ```
*   **Example Error Responses (JSON)**:
    *   `400 Bad Request` (Missing required fields):
        ```json
        {
            "success": false,
            "error": {
                "code": 400,
                "message": "Missing required fields: model and/or name"
            }
        }
        ```
    *   `401 Unauthorized`: See `product/get`.
    *   `405 Method Not Allowed`: (If not POST)
        ```json
        {
            "success": false,
            "error": {
                "code": 405,
                "message": "Method Not Allowed. Only POST is supported for this endpoint."
            }
        }
        ```
    *   `500 Internal Server Error`:
        ```json
        {
            "success": false,
            "error": {
                "code": 500,
                "message": "Failed to add product due to a server error."
            }
        }
        ```

---

### Categories

#### Get Category (`category/get`)

*   **Description**: Retrieves details for a specific category.
*   **Endpoint Path**: `category/get`
*   **HTTP Method**: `GET`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `id` (query, integer, required): The ID of the category to retrieve.
*   **Example Request (cURL)**:
    ```bash
    curl -X GET "https://yourstore.com/index.php?route=api/category/get&apikey=YOUR_SUPER_SECRET_API_KEY&id=20"
    ```
*   **Example Success Response (JSON - Status 200 OK)**:
    ```json
    {
        "success": true,
        "category": {
            "id": "20",
            "name": "Electronics",
            "description": "All kinds of electronics.",
            "href": "https://yourstore.com/index.php?route=product/category&category_id=20",
            "image": "catalog/demo/electronics.jpg"
        }
    }
    ```
*   **Example Error Responses**: Similar to `product/get` (401, 404, 405). Replace "Product" with "Category" in messages.

#### List Categories (`category/list`)

*   **Description**: Retrieves a list of categories, optionally filtered by parent and level.
*   **Endpoint Path**: `category/list`
*   **HTTP Method**: `GET`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `parent` (query, integer, optional, default: 0): Parent category ID to retrieve subcategories.
    *   `level` (query, integer, optional, default: 1): How many levels of subcategories to retrieve.
*   **Example Request (cURL)**:
    ```bash
    curl -X GET "https://yourstore.com/index.php?route=api/category/list&apikey=YOUR_SUPER_SECRET_API_KEY&parent=20&level=2"
    ```
*   **Example Success Response (JSON - Status 200 OK)**:
    ```json
    {
        "success": true,
        "categories": [
            {
                "category_id": "25",
                "parent_id": "20",
                "name": "Laptops",
                "image": "https://yourstore.com/image/cache/catalog/demo/laptops-100x100.jpg",
                "href": "https://yourstore.com/index.php?route=product/category&category_id=25",
                "categories": [ ]
            },
        ]
    }
    ```
*   **Example Error Responses**: Similar to `product/get` (401, 405).

#### Count Categories (`category/count`)

*   **Description**: Counts categories, optionally filtered by parent ID.
*   **Endpoint Path**: `category/count`
*   **HTTP Method**: `GET`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `parent` (query, integer, optional, default: 0): Parent category ID to count its direct subcategories.
*   **Example Request (cURL)**:
    ```bash
    curl -X GET "https://yourstore.com/index.php?route=api/category/count&apikey=YOUR_SUPER_SECRET_API_KEY&parent=20"
    ```
*   **Example Success Response (JSON - Status 200 OK)**:
    ```json
    {
        "success": true,
        "data": {
            "count": 5
        }
    }
    ```
*   **Example Error Responses**: Similar to `product/get` (401, 405).

---

### Customers

#### Get Customer (`customer/get`)

*   **Description**: Retrieves details for a specific customer by ID, email, or token.
*   **Endpoint Path**: `customer/get`
*   **HTTP Method**: `GET`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `id` (query, integer, optional): Customer ID.
    *   `email` (query, string, optional): Customer email.
    *   `token` (query, string, optional): Customer token (used for password reset, etc.).
    *   *Note: At least one of `id`, `email`, or `token` must be provided.*
*   **Example Request (cURL)**:
    ```bash
    curl -X GET "https://yourstore.com/index.php?route=api/customer/get&apikey=YOUR_SUPER_SECRET_API_KEY&id=1"
    ```
*   **Example Success Response (JSON - Status 200 OK)**:
    ```json
    {
        "success": true,
        "customer": {
            "customer_id": "1",
            "store_id": "0",
            "firstname": "John",
            "lastname": "Doe",
            "email": "john.doe@example.com",
            "telephone": "1234567890",
            "fax": ""
        }
    }
    ```
*   **Example Error Responses**: Similar to `product/get` (401, 404, 405). Replace "Product" with "Customer" in messages. For 404: `"message": "Customer not found with the provided criteria."`

#### List Customers (`customer/list`)

*   **Description**: Retrieves a list of all customers. (Filtering by category is currently non-functional despite the parameter).
*   **Endpoint Path**: `customer/list`
*   **HTTP Method**: `GET`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `category` (query, integer, optional): Currently unused parameter.
*   **Example Request (cURL)**:
    ```bash
    curl -X GET "https://yourstore.com/index.php?route=api/customer/list&apikey=YOUR_SUPER_SECRET_API_KEY"
    ```
*   **Example Success Response (JSON - Status 200 OK)**:
    ```json
    {
        "success": true,
        "customers": [
            {
                "id": "1",
                "firstname": "John",
                "lastname": "Doe",
                "email": "john.doe@example.com"
            },
            {
                "id": "2",
                "firstname": "Jane",
                "lastname": "Smith",
                "email": "jane.smith@example.com"
            }
        ]
    }
    ```
*   **Example Error Responses**: Similar to `product/get` (401, 405).

#### Add Customer (`customer/add`)

*   **Description**: Adds a new customer.
*   **Endpoint Path**: `customer/add`
*   **HTTP Method**: `POST`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `firstname` (POST body, string, required): Customer's first name.
    *   `lastname` (POST body, string, required): Customer's last name.
    *   `email` (POST body, string, required): Customer's email address.
    *   `password` (POST body, string, required): Customer's password.
*   **Example Request (cURL)**:
    ```bash
    curl -X POST "https://yourstore.com/index.php?route=api/customer/add&apikey=YOUR_SUPER_SECRET_API_KEY" \
    -d "firstname=Test" \
    -d "lastname=User" \
    -d "email=test.user@example.com" \
    -d "password=securepassword123"
    ```
*   **Example Success Response (JSON - Status 201 Created)**:
    ```json
    {
        "success": true,
        "customer_id": 12,
        "message": "Customer added successfully"
    }
    ```
*   **Example Error Responses (JSON)**:
    *   `400 Bad Request` (Missing required fields):
        ```json
        {
            "success": false,
            "error": {
                "code": 400,
                "message": "Missing required fields: firstname, lastname, email, password."
            }
        }
        ```
    *   `400 Bad Request` (Invalid email format):
        ```json
        {
            "success": false,
            "error": {
                "code": 400,
                "message": "Invalid email format."
            }
        }
        ```
    *   `401 Unauthorized`: See `product/get`.
    *   `405 Method Not Allowed`: (If not POST).
    *   `500 Internal Server Error`:
        ```json
        {
            "success": false,
            "error": {
                "code": 500,
                "message": "Failed to add customer or retrieve confirmation due to a server error."
            }
        }
        ```

---

### Store

#### Get Store Information (`store/get`)

*   **Description**: Retrieves store configuration settings.
*   **Endpoint Path**: `store/get`
*   **HTTP Method**: `GET`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `store` (query, integer, optional, default: 0): The ID of the store configuration to retrieve.
*   **Example Request (cURL)**:
    ```bash
    curl -X GET "https://yourstore.com/index.php?route=api/store/get&apikey=YOUR_SUPER_SECRET_API_KEY&store=0"
    ```
*   **Example Success Response (JSON - Status 200 OK)**:
    ```json
    {
        "success": true,
        "store": {
            "config_name": "Your Store Name",
            "config_owner": "Store Owner",
            "config_address": "123 Store Street"
        }
    }
    ```
*   **Example Error Responses**:
    *   `401 Unauthorized`: See `product/get`.
    *   `404 Not Found`:
        ```json
        {
            "success": false,
            "error": {
                "code": 404,
                "message": "Store configuration not found for ID: 1"
            }
        }
        ```
    *   `405 Method Not Allowed`: See `product/get`.

---

### Information

#### Get Information Page (`information/get`)

*   **Description**: Retrieves the content of an information page.
*   **Endpoint Path**: `information/get`
*   **HTTP Method**: `GET`
*   **Parameters**:
    *   `apikey` (query, string, required): Your API key.
    *   `id` (query, integer, required): The ID of the information page.
*   **Example Request (cURL)**:
    ```bash
    curl -X GET "https://yourstore.com/index.php?route=api/information/get&apikey=YOUR_SUPER_SECRET_API_KEY&id=4"
    ```
*   **Example Success Response (JSON - Status 200 OK)**:
    ```json
    {
        "success": true,
        "information": {
            "information_id": "4",
            "title": "About Us",
            "description": "<p>This is the about us page content.</p>"
        }
    }
    ```
*   **Example Error Responses**: Similar to `product/get` (401, 404, 405). Replace "Product" with "Information" in messages. For 404: `"message": "Information not found with ID: 999"`.

---

## Error Handling

The API uses a standardized JSON format for error responses:

```json
{
    "success": false,
    "error": {
        "code": <HTTP_STATUS_CODE>,
        "message": "Descriptive error message"
    }
}
```

Common HTTP Status Codes Used:

*   `200 OK`: Request successful.
*   `201 Created`: Resource successfully created (e.g., `customer/add`, `product/add`).
*   `400 Bad Request`: The request was malformed, such as missing required parameters or invalid data.
*   `401 Unauthorized`: Authentication failed (missing or invalid API key).
*   `404 Not Found`: The requested resource could not be found.
*   `405 Method Not Allowed`: The HTTP method used is not supported for the endpoint.
*   `500 Internal Server Error`: An unexpected error occurred on the server.

## Features & Roadmap

### Currently Implemented Features

*   **Products**:
    *   Get Product Details (`product/get`)
    *   List Products (`product/list`)
    *   Count Products (`product/count`)
    *   Add Product (`product/add`)
*   **Categories**:
    *   Get Category Details (`category/get`)
    *   List Categories (`category/list`)
    *   Count Categories (`category/count`)
*   **Customers**:
    *   Get Customer Details (`customer/get`)
    *   List Customers (`customer/list`)
    *   Add Customer (`customer/add`)
*   **Store**:
    *   Get Store Information (`store/get`)
*   **Information Pages**:
    *   Get Information Page Details (`information/get`)

### Future Enhancements (Roadmap)

*   **Products**:
    *   Update Product (`product/update`)
    *   Delete Product (`product/delete`)
    *   Get Latest Products (`product/latest`)
    *   Get Popular Products (`product/popular`)
    *   Get Bestseller Products (`product/bestseller`)
*   **Orders**:
    *   Get Order Details
    *   List Orders (with filtering)
    *   Update Order Status
    *   (Further order management features)
*   **Authentication**:
    *   More robust API key management (database/config file).
    *   Support for API key in HTTP headers.
    *   OAuth2 for more secure and granular access control.
*   **Input Handling**:
    *   Support for JSON request bodies for POST/PUT requests.
*   **Advanced Filtering & Sorting**: For all list endpoints.
*   **Pagination**: For all list endpoints.
*   **Webhooks**: For event-driven notifications.
*   **Documentation**: Automated generation of API documentation.

## Contributing

Contributions to this API project are welcome. Please follow standard coding practices and submit pull requests for review. (Further details to be added).

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.