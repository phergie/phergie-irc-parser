# phergie/phergie-irc-parser

A PHP-based parser for messages conforming to the IRC protocol as described in [RFC 1459](https://tools.ietf.org/html/rfc1459) and [RFC 2812](https://tools.ietf.org/html/rfc2812).

[![Build Status](https://secure.travis-ci.org/phergie/phergie-irc-parser.png?branch=master)](http://travis-ci.org/phergie/phergie-irc-parser)

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "phergie/phergie-irc-parser": "~1"
    }
}
```

## Design goals

* Minimal dependencies: PHP 5.4.2+ with the core PCRE extension
* Can extract messages from a real-time data stream
* Simple easy-to-understand API

## Usage

```php
<?php
$stream = ":Angel PRIVMSG Wiz :Hello are you receiving this message ?\r\n"
        . "PRIVMSG Angel :yes I'm receiving it !receiving it !'u>(768u+1n) .br\r\n";
$parser = new Phergie\Irc\Parser();

// Get one message without modifying $stream
// or null if no complete message is found
$message = $parser->parse($stream);

// Get one message and remove it from $stream
// or null if no complete message is found
$message = $parser->consume($stream);

// Get all messages without modifying $stream
// or an empty array if no complete messages are found
$messages = $parser->parseAll($stream);

// Get all messages and remove them from $stream
// or an empty array if no complete messages are found
$messages = $parser->consumeAll($stream);

/*
One parsed message looks like this:
array(
    'prefix' => ':Angel',
    'nick' => 'Angel',
    'command' => 'PRIVMSG',
    'params' => array(
        'receivers' => 'Wiz',
        'text' => 'Hello are you receiving this message ?',
        'all' => 'Wiz :Hello are you receiving this message ?',
    ),
    'targets' => array('Wiz'),
    'message' => ":Angel PRIVMSG Wiz :Hello are you receiving this message ?\r\n",
)
*/
```

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
./vendor/bin/phpunit Phergie/Irc/ParserTest.php
```

## License

Released under the BSD License. See `LICENSE`.

## Community

Check out #phergie on irc.freenode.net or e-mail us at [team@phergie.org](mailto:team@phergie.org).

## Related Projects

* [phergie/phergie-irc-client-react](http://github.com/phergie/phergie-irc-client-react)
