# Atlas ORM package for Berlioz Framework

[![Latest Version](https://img.shields.io/packagist/v/berlioz/atlas-package.svg?style=flat-square)](https://github.com/BerliozFramework/AtlasPackage/releases)
[![Software license](https://img.shields.io/github/license/BerliozFramework/AtlasPackage.svg?style=flat-square)](https://github.com/BerliozFramework/AtlasPackage/blob/2.x/LICENSE)
[![Build Status](https://img.shields.io/github/workflow/status/BerliozFramework/AtlasPackage/Tests/2.x.svg?style=flat-square)](https://github.com/BerliozFramework/AtlasPackage/actions/workflows/tests.yml?query=branch%3A2.x)
[![Quality Grade](https://img.shields.io/codacy/grade/35cc79cf05c8460793c569e5b2fe1bbe/2.x.svg?style=flat-square)](https://www.codacy.com/manual/BerliozFramework/AtlasPackage)
[![Total Downloads](https://img.shields.io/packagist/dt/berlioz/atlas-package.svg?style=flat-square)](https://packagist.org/packages/berlioz/atlas-package)

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