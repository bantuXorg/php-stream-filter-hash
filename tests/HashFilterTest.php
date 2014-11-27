<?php

/*
* (c) Andreas Fischer <af@bantuX.org>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace bantu\StreamFilter\Hash;

class HashFilterTest extends \PHPUnit_Framework_TestCase
{
    protected $filters;

    public function setUp()
    {
        $this->filter = stream_get_filters();
        parent::setUp();
    }

    public function tearDown()
    {
        foreach (array_diff($this->filter, stream_get_filters()) as $name) {
            stream_filter_remove($name);
        }
        parent::tearDown();
    }

    public function testRegisterWithDefault()
    {
        $this->assertNotContains(
            'hash',
            stream_get_filters(),
            'Failed asserting that a filter called hash does not already exist.'
        );
        $this->assertTrue(
            HashFilter::register(),
            'Failed asserting that HashFilter could be successfully registered.'
        );
        $this->assertContains(
            'hash',
            stream_get_filters(),
            'Failed asserting that register() registered a filter called hash.'
        );
        $this->assertFalse(
            HashFilter::register(),
            'Failed asserting that HashFilter could not be registered a 2nd time.'
        );
    }

    public function testFilterWithOutputStream()
    {
        $phrase = 'The quick brown fox jumps over the lazy dog.';
        $hash = fopen('php://memory', 'r+');
        $out = fopen('php://memory', 'r+');
        $in = fopen('php://memory', 'r+');
        fwrite($in, $phrase);
        rewind($in);

        HashFilter::appendToWriteStream($out, array(
            'algo' => 'sha256',
            'out' => $hash,
        ));

        stream_copy_to_stream($in, $out);
        fclose($in);

        rewind($out);
        $this->assertSame(
            $phrase,
            stream_get_contents($out),
            'Failed asserting that filter does pass all data through.'
        );
        fclose($out);

        rewind($hash);
        $this->assertSame(
            'ef537f25c895bfa782526529a9b63d97aa631564d5d789c2b765448c8635fb6c',
            stream_get_contents($hash),
            'Failed asserting that filter calculated expected hash.'
        );
        fclose($hash);
    }

    public function testFilterWithCallback()
    {
        $out = fopen('php://memory', 'r+');
        $in = fopen('php://memory', 'r+');
        fwrite($in, 'The quick brown fox jumps over the lazy dog');
        rewind($in);

        $hashFromCallback = null;
        $callback = function ($hash) use (&$hashFromCallback) {
            $hashFromCallback = $hash;
        };

        HashFilter::appendToWriteStream($out, array(
            'algo' => 'md5',
            'callback' => $callback,
        ));

        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        $this->assertSame(
            '9e107d9d372bb6826bd81d3542a419d6',
            $hashFromCallback,
            'Failed asserting that filter calculated expected checksum.'
        );
    }

    /**
    * @expectedException \Exception
    */
    public function testAppendToWriteStreamWithoutMandatory()
    {
        HashFilter::appendToWriteStream(
            fopen('php://memory', 'r+'),
            array()
        );
    }

    public function testAppendToWriteStreamTwice()
    {
        $stream = fopen('php://memory', 'r+');
        $params = array(
            'algo' => 'md5',
            'callback' => function ($hash) {
            },
        );
        $ressource1 = HashFilter::appendToWriteStream($stream, $params);
        $this->assertInternalType('resource', $ressource1);
        $ressource2 = HashFilter::appendToWriteStream($stream, $params);
        $this->assertInternalType('resource', $ressource2);
        $this->assertNotSame(
            $ressource1,
            $ressource2,
            'Failed asserting that the filter could be appended twice ' .
            'without causing any namespace issues or similar errors.'
        );
    }
}
