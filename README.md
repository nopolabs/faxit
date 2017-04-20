faxit
=====

Fax your congressional delegation (or whoever).

Inspired by [resistbot](resistbot.io).

You will need a [twilio](https://www.twilio.com) account.

Edit app/config/private.yml to set twilio parameters and fax choices.

## quick start

```
git clone https://github.com/nopolabs/faxit.git
composer install
vi app/config/private.yml
```

### using built-in webserver

You can use [php's built-in webserver](http://symfony.com/doc/current/setup/built_in_web_server.html):

```
php bin/console server:start
```

By default it runs on port 8000.

You may need to expose the webserver through a service like ngrok:

```
ngrok http 8000
```

### using apache2 or nginx

