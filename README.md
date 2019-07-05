# FetcherGRIDMI
Simple fetcher of data from database for PHP
# What properties does the structure contain?
|PROPERTY|TYPE|DESCRIPTION|OPTIONAL|
|:------:|:--:|:----------|:------:|
|query|string|Contains a SQL query string to the database.|false
|id|string|Contains the current selection identifier for the child selections.|true
|exclude|array|Array of strings to exclude selection properties.|true
|types|object|An object with properties and values to be cast to a specific type.|true
|properties|object|An object with child selections for the current selection.|true

# How to use
You need to pass two arguments or three to a static method.

The first argument is a descriptor.
The second argument is the JSON object (JSON Schema Fetch).
The third argument is optional, this parameter allows you to set the primary variable in a static structure.

# For example
``````

// Init descriptor and schema
$mysqli = new mysqli('localhost', 'root', '123456', 'test');
$schema = json_decode(file_get_contents('schema.json'));

// Send query to database
$result = FetcherGRIDMI::onSelect($mysqli, $schema, array('name' => 'GRIDMI'));

``````

This is example $result will return array with data.

# Structure JSON Schema
``````
{
	"id": "customer",
	"query": "SELECT * FROM `users` WHERE `id` > (customerId);",
	"exclude": [
		"password",
		"session"
	],
	"types": {
		"id": "integer",
		"name": "string",
		"latitude": "float",
		"longitude": "float"
	},
	"properties": {
		"orders": {
			"id": "order",
			"query": "SELECT * FROM `orders` WHERE `customer` = (customer.id);",
			"types": {
				"id": "integer"
			},
			"properties": {
				"items": {
					"query": "SELECT * FROM `items` WHERE `order` = (order.id);"
				}
			}
		}
	}
}
``````
This example will return array with data from database. For example dump returned data.
``````
Array(
	0 => Array(
		"id" => 1,
		"name" => "Name",
		"latitude" => 0.00,
		"longitude" => 0.00,
		"orders" => Array(
			0 => Array(
				"id" => 1,
				"customer" => 1,
				"items" => Array(
					0 => Array(
						"id" => "1",
						"order" => "1"
					)
				)
			)
		)
	)
)
``````
# What type can I convert data from the database?
Any scalar type can be cast to a specific type. Register is not important.
* String
* Numeric
* Double
* Float
* Integer

# License
Free use with modification rights.

`Without` the right to change the name of the class!
