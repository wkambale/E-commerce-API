Cart Module APIs
================

Functions
-------

Product Management Functions

* `product.get`: Return full product information (by ID).
* `product.list`: Return products list with limited information (ID, Name, Price, Thumbnail). Set optional parameter category_id to get products list into the specified category.
* `product.count`: Return store products count. Set optional parameter category_id to get products count into the specified category.

Category Management Functions

* `category.get`: Return full Category information (by ID).
* `category.list`: Get categories list with limited information (ID, Name, Description, Href, Image).
* `category.count`: Return shopping cart categories count.

Customer Management Functions

* `customer.get`: Return full Customer information (by ID, Email or Token).
* `customer.add`: Add new customer to the store. Return ID.

Store Management Functions

* `store.get`: Return store config parameters.

Information Management Functions

* `information.get`: Return information from page by ID

Roadmap
-------

Product Management Functions
	
* `product.add`: Add new product to the shopping cart.
* `product.update`: Update price and stock for a specific product
* `product.delete`: Remove specified product from the shopping cart (by ID)
* `product.latest`
* `product.popular`
* `product.bestseller`

Customer Management Functions

* `customer.list`: Get customers list with limited information.

Cart Management Functions

Order Management Functions

* `order.get`
* `order.list`
* `order.count`
* `order.add`
* `order.update`

License
-------

[MIT License](https://github.com/WesleyKambale/cart-chat-module/blob/main/LICENSE)
