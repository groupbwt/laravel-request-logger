<p align="right">
<a href="README.md">English description</a> | Описание на русском 
</p>

# Laravel 5 Request Logger

[![Latest Stable Version][ico-stable-version]][link-stable-packagist]
[![Latest Unstable Version][ico-unstable-version]][link-unstable-packagist]
[![License][ico-license]](LICENSE.md)

Этот пакет помогает легко и быстро настроить логирование запросов.

#### Этот пакет требует Laravel 5.6+
 
### Содержание

- [Установка](#Установка)
- [Настройка](#Настройка)
- [Лицензия](#Лицензия)

### Установка

Установите этот пакет с помощью composer используя следующую команду:

```bash
composer require bwt-team/laravel-request-logger
```

### Настройка

Laravel 5.6+ использует автоподлючение пакетов, по этому вам не надо подключать service provider вручную.

Если у вас отключено автоматическое подключение, вы можете подключить его самостоятельно, добавив следующие записи в массив `providers` в файле `config/app.php`:
 
```php
BwtTeam\LaravelRequestLogger\Providers\RequestLoggerServiceProvider::class
```

Этот service provider зарегистриует middleware, в котором происходит вызов самого логирования, и даст возможность выполнить все настройки по установке данного пакета.

Если вы хотите использовать facade - добавьте в массив `aliases` следующую запись:

```php
'RequestLogger' => \BwtTeam\LaravelRequestLogger\Facades\RequestLogger::class,
```

#### ВАЖНО! По умолчанию логирование в настройках выключено. Это сделано для того, чтобы после того, как вы установите данный пакет, у вас была возможность настроить всё необходимое, прежде чем приступите к логированию. Для активации логирования используйте env переменную "RL_ENABLE", либо же, после публикации конфигурационного файла, вы можете описать функцию, которая будет активировать логирование только в нужных вам запросах.

Пример функции, описывающей условия для логирования:

```php
'enabled' => function(\Illuminate\Http\Request $request) {
    return $request->isMethod('GET');
},
```

Для публикации конфигурационного файла выполните команду:

```bash
php artisan vendor:publish --provider="BwtTeam\LaravelRequestLogger\Providers\RequestLoggerServiceProvider" --tag=config
```

В настройках доступны опции: 
 - включить или отключить само логирование
 - имена полей, которые необходимо исключить из логов (например "password")
 - опции для приведения типов и оптимизирования логов
 - настройки хранилища логов
 
Хранилищем логов по умолчанию является "database".
Для работы этого хранилища необходимо выполнить миграцию по созданию таблицы для логов.
Для публикации этой миграции выполните команду:

```bash
php artisan request-logger:table
```

Имеется возможность за записи кастомных данных.
Для этого необходимо передать эти данные в метод "put" класса "BwtTeam\LaravelRequestLogger\RequestLoggerRepository".
Например:

```php
app(\BwtTeam\LaravelRequestLogger\RepositoryInterface::class)->put('test', ['some', 'data']);
```

Либо же воспользоваться фасадом.

```php
\RequestLogger::put('test', ['some', 'data']);
```

### Лицензия

Этот пакет использует лицензию [MIT](LICENSE.md).

[ico-stable-version]: https://poser.pugx.org/bwt-team/laravel-request-logger/v/stable?format=flat-square
[ico-unstable-version]: https://poser.pugx.org/bwt-team/laravel-request-logger/v/unstable?format=flat-square
[ico-license]: https://poser.pugx.org/bwt-team/laravel-request-logger/license?format=flat-square

[link-stable-packagist]: https://packagist.org/packages/bwt-team/laravel-request-logger
[link-unstable-packagist]: https://packagist.org/packages/bwt-team/laravel-request-logger#dev-develop