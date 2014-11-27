<?php

/*
* (c) Andreas Fischer <af@bantuX.org>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace bantu\StreamFilter\Hash;

class HashFilter extends \php_user_filter
{
    /** @var resource */
    protected $hashResource;

    /**
    * Called when applying the filter.
    * See http://php.net/manual/en/php-user-filter.filter.php
    *
    * @param resource $in
    * @param resource $out
    * @param int &$consumed
    * @param bool $closing
    *
    * @return int  Always returns PSFS_PASS_ON.
    */
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
            hash_update($this->hashResource, $bucket->data);
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }

    /**
    * Called when the filter is created.
    * Verifies that mandatory parameters are specified.
    * Also see http://php.net/manual/en/php-user-filter.oncreate.php
    *
    * @return bool  False if mandatory $params are missing.
    *               True otherwise.
    */
    public function onCreate()
    {
        if (!isset($this->params['algo']) ||
            !isset($this->params['callback']) && !isset($this->params['out']) ||
            isset($this->params['callback']) && !is_callable($this->params['callback']) ||
            isset($this->params['out']) && !is_resource($this->params['out'])
        ) {
            return false;
        }
        $this->hashResource = hash_init($this->params['algo']);
        return true;
    }

    /**
    * Called when closing the filter. Finalises the hash and either writes the
    * a hexadecimal string into the output stream or passes it to the callback.
    * Also see http://php.net/manual/en/php-user-filter.onclose.php
    *
    * @return null
    */
    public function onClose()
    {
        if (is_resource($this->hashResource)) {
            $result = hash_final($this->hashResource);
            if (isset($this->params['callback'])) {
                $this->params['callback']($result);
            } elseif (is_resource($this->params['out'])) {
                fwrite($this->params['out'], $result);
            }
        }
    }

    /**
    * Convenience wrapper around stream_filter_register(), mostly for
    * autoloading. Additionally takes care of specifying the class name.
    *
    * @param string $filtername  The filter name to be registered, default 'hash'.
    * @return bool               True if the filter was successfully registred.
    *                            Otherwise false (e.g. when filter already exists).
    */
    public static function register($filtername = 'hash')
    {
        return stream_filter_register($filtername, get_called_class());
    }

    /**
    * Convenience wrapper around stream_filter_append(). Appends an instance
    * of this stream filter as a write filter to a given stream.
    *
    * @param resource $stream  The write stream to append this filter to.
    * @param array $param      Parameters of this stream filter, e.g. 'algo'.
    * @return resource         A resource that can be passed to the function
    *                          stream_filter_remove() to remove the filter from
    *                          the stream.
    */
    public static function appendToWriteStream($stream, array $params)
    {
        $filtername = 'bantu-StreamFilter-Hash-HashFilter';
        static::register($filtername);
        return stream_filter_append(
            $stream,
            $filtername,
            STREAM_FILTER_WRITE,
            $params
        );
    }
}
