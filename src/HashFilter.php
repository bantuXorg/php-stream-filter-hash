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
    protected $hash_resource;

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
            hash_update($this->hash_resource, $bucket->data);
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }

    public function onCreate()
    {
        if (!isset($this->params['algo']) ||
            !isset($this->params['callback']) && !isset($this->params['out']) ||
            isset($this->params['callback']) && !is_callable($this->params['callback']) ||
            isset($this->params['out']) && !is_resource($this->params['out'])
        ) {
            return false;
        }
        $this->hash_resource = hash_init($this->params['algo']);
        return true;
    }

    public function onClose()
    {
        if (is_resource($this->hash_resource)) {
            $result = hash_final($this->hash_resource);
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
}
