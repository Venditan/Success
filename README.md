# Success #

Absence of Success (AoS) monitoring client for PHP

**WARNING - this library is in pre-alpha**

## Example Usage ##

```php
\Venditan\Success::expect('Regular job')->every('hour')->sms('07000000000');
```

Or you can use named recipients or groups, like this

```php
\Venditan\Success::expect('Regular job')->every('hour')->email('tom,support');
```
