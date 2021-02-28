<?php

namespace Hi\Http\Tests\Message;

use Hi\Http\Message\Uri;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase
{
    public function testGet()
    {
        $scheme = 'https';
        $host = 'www.baidu.com';
        $path = '/s';
        $query = 'ie=utf-8&f=8&rsv_bp=1&rsv_idx=1&tn=baidu&fenlei=256&rsv_pq=c28cc1ec000045bd&rqlang=cn&rsv_enter=1&rsv_dl=tb&rsv_sug3=10&rsv_sug1=8&rsv_sug7=101&rsv_sug2=0&rsv_sug4=9930';
        $fragment = 'test';

        $uriString = "{$scheme}://{$host}{$path}?{$query}#{$fragment}";

        $uri = new Uri($uriString);

        $this->assertEquals($uriString, (string) $uri);
        $this->assertEquals($scheme,    $uri->getScheme());
        $this->assertEquals($host,      $uri->getHost());
        $this->assertEquals($path,      $uri->getPath());
        $this->assertEquals($query,     $uri->getQuery());
        $this->assertEquals(null,       $uri->getPort());
        $this->assertEquals($fragment, $uri->getFragment());
    }

    public function testGetAuth()
    {
        $scheme = 'https';
        $userInfo = 'robin:123456';
        $host = 'www.baidu.com';
        $path = '/s';
        $uriString = "{$scheme}://{$userInfo}@{$host}{$path}";

        $uri = new Uri($uriString);
        $this->assertEquals($uriString, (string) $uri);
    }

    public function testHttpUri()
    {
        $uri = new Uri('http://www.baidu.com/');
        $this->assertEquals('http', $uri->getScheme());
    }

    public function testWith()
    {
        $uri = new Uri('https://www.baidu.com/');

        $scheme = 'http';
        $host = 'www.xiaoe-tech.com';
        $path = '/about/company';
        $query = 'boo=far&bnb=eee';
        $user = 'example';
        $pass = 'pass';
        $port = 80;
        $userInfo = $user . ':' . $pass;

        $uri = $uri
            ->withScheme($scheme)
            ->withHost($host)
            ->withPath($path)
            ->withPort($port)
            ->withQuery($query)
            ->withUserInfo($user, $pass)
        ;

        $this->assertEquals($scheme, $uri->getScheme());
        $this->assertEquals($userInfo, $uri->getUserInfo());
        $this->assertEquals($host, $uri->getHost());
        $this->assertEquals($path, $uri->getPath());
        $this->assertEquals($query, $uri->getQuery());

        $uriString = "{$scheme}://{$userInfo}@{$host}{$path}?{$query}";
        $this->assertEquals($uriString, (string) $uri);
    }
}
