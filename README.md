# metabase-php-api
Simple Metabase API wrapper written in PHP.

## Installation

Install with composer:

```composer log
composer require lujunsan/metabase-php-api
```
## Usage

Create a Metabase Client with:
```php
/*
 * url: Full URL of the Metabase application (https://metabase.unicorn.com)
 * username: Username of the account that will make the API calls
 * password: Password of username above
*/

$client = new MetabaseClient($url, $username, $password);
```

Retrieve data from a question with:
```php
/*
 * questionId: Id of the question (query) that will be executed and retrieved
 * format: Optional, defaults as json
 * parameters: Optional, defaults empty, any extra parameters that will be sent in the API call
*/

$client->getQuestion($questionId, $format, $parameters);
```
