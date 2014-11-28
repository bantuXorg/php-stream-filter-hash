[![Travis Build Status](https://travis-ci.org/bantuXorg/php-stream-filter-hash.svg?branch=master)](https://travis-ci.org/bantuXorg/php-stream-filter-hash)
[![Scrutinizer Build Status](https://scrutinizer-ci.com/g/bantuXorg/php-stream-filter-hash/badges/build.png?b=master)](https://scrutinizer-ci.com/g/bantuXorg/php-stream-filter-hash/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/bantuXorg/php-stream-filter-hash/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/bantuXorg/php-stream-filter-hash/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bantuXorg/php-stream-filter-hash/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bantuXorg/php-stream-filter-hash/?branch=master)

## Installation

Through [composer](http://getcomposer.org):

```bash
$ composer require bantu/stream-filter-hash:~1.0
```

## Usage

Use `HashFilter::appendToWriteStream($stream, $params)` to calculate a checksum
of everything written to `$stream`. The `$params` argument has to be an array
specifying the hash algorithm to use via the `algo` array key (e.g. `md5` or
`sha256`). Furthermore `$params` either needs to be passed a callback via the
`callback` array key or an output stream using the `stream` array key.

### Example using Stream

Create an `output.txt.sha256` file containing a checksum of everything you
write to `output.txt`.

```php
require 'vendor/autoload.php';
$dest = fopen('output.txt', 'w+b');
$hash = fopen('output.txt.md5', 'w+b');
\bantu\StreamFilter\Hash\HashFilter::appendToWriteStream($dest, array(
    'algo' => 'sha256', 'stream' => $hash,
));
fwrite($dest, 'The quick brown fox jumps over the lazy dog');
fclose($dest);
fclose($hash);
```
