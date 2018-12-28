# Atlas ORM package for Berlioz Framework

This package is intended to provide **Atlas** in **Berlioz Framework**.

> Atlas is a database framework for PHP to help you work with your persistence model, while providing a path to refactor towards a richer domain model as needed.
> 
> [Official website of Atlas](http://atlasphp.io/)

For more information, and use of Berlioz Framework, go to website and online documentation :
https://getberlioz.com

## Installation

### Composer

You can install **Atlas Package** with [Composer](https://getcomposer.org/), it's the recommended installation.

```bash
$ composer require berlioz/atlas-package
```

### Dependencies

* **PHP** >= 7.1
* Packages:
  * **berlioz/core**
  * **atlas/orm**
  * **atlas/cli**


## Usage

Package add a service named `atlas`, who correspond to the `\Atlas\Orm\Atlas` class.

See [**Atlas ORM** documentation](http://atlasphp.io/) for more information.


## Configuration

Default configuration:
```json
{
  "atlas": {
    "pdo": {
      "connection_locator": {
        "default": {
          "dsn": null,
          "username": null,
          "password": null
        },
        "read": {},
        "write": {}
      }
    },
    "orm": {
      "atlas": {
         "transaction_class": "Atlas\\Orm\\Transaction\\AutoTransact",
         "log_queries": "%berlioz.debug%"
      }
    }
  }
}
```