<p align="right">
English description | <a href="README_RU.md">Russian description</a>
</p>

# Laravel 5 Request Logger

[![Latest Stable Version][ico-stable-version]][link-stable-packagist]
[![Latest Unstable Version][ico-unstable-version]][link-unstable-packagist]
[![License][ico-license]](LICENSE.md)

This package provides quick and easy setup for the requests logging.

#### This package requires Laravel 5.6+
 
### Contents
- [Installation](#installation)
- [Set up](#set-up)
- [License](#license)

### Installation

This package can be installed via composer with this command:

```bash
composer require bwt-team/laravel-request-logger
```

### Set up

Laravel 5.6+ uses package auto-discovery, so there's no need to register the service provider manually.
If auto-discovery is disabled the service provider can be registered by adding these records into `providers` array in `config/app.php` file:

```php
BwtTeam\LaravelRequestLogger\Providers\RequestLoggerServiceProvider::class
```

This service provider will register the middleware that calls the logging itself and will allow configuring all the settings required for the package installation.
To use a facade add this record to the `aliases` array:

```php
'RequestLogger' => \BwtTeam\LaravelRequestLogger\Facades\RequestLogger::class,
```

#### IMPORTANT! By default, logging is disabled in the settings. This allows to configure the package after the installation and enable the logging once everything is ready. To activate the logging use an environment variable "RL_ENABLE". Alternatively, once the config file has been published, it is possible to describe a function that will activate the logging only for the requests of interest. 

Here's an example of the function that describes the logging conditions:

```php
'enabled' => function(\Illuminate\Http\Request $request) {
    return $request->isMethod('GET');
},
```

In order to publish the config file execute next command:

```bash
php artisan vendor:publish --provider="BwtTeam\LaravelRequestLogger\Providers\RequestLoggerServiceProvider" --tag=config
```

Next options are available in the settings: 
 - enable/disable logging;
 - titles of the fields that should be excluded from the logs (for example, "password");
 - options for type casting and logs optimization;
 - logs storage settings.
 
The default log storage is "database".
For this storage to work correctly it is necessary to execute a migration to create a table for logs.
In order to publish this migration execute this command:

```bash
php artisan request-logger:table
```

It is possible to write custom data.
To do this, the data should be passed to "put" method of "BwtTeam\LaravelRequestLogger\RequestLoggerRepository" class.
For example:

```php
app(\BwtTeam\LaravelRequestLogger\RepositoryInterface::class)->put('test', ['some', 'data']);
```

Alternatively, a facade could be used:

```php
\RequestLogger::put('test', ['some', 'data']);
```

### License

This package uses [MIT](LICENSE.md).

[ico-stable-version]: https://poser.pugx.org/bwt-team/laravel-request-logger/v/stable?format=flat-square
[ico-unstable-version]: https://poser.pugx.org/bwt-team/laravel-request-logger/v/unstable?format=flat-square
[ico-license]: https://poser.pugx.org/bwt-team/laravel-request-logger/license?format=flat-square

[link-stable-packagist]: https://packagist.org/packages/bwt-team/laravel-request-logger
[link-unstable-packagist]: https://packagist.org/packages/bwt-team/laravel-request-logger#dev-develop