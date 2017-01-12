<?php

namespace SyntaxError\SocketBundle\Task;

use SyntaxError\ApiBundle\Tools\Jsoner;

class Provider
{
    private $live;

    public function __construct()
    {
        $kernel = new \AppKernel('prod', false);
        $kernel->boot();
        $this->live = $kernel->getContainer()->get('syntax_error_api.live');
    }

    public function getBasic()
    {
        $data = [];
        foreach(get_class_methods($this->live) as $method) {
            if($method != '__construct') {
                $key = strtolower( str_replace('create', '', $method) );
                $data[$key] = call_user_func( [$this->live, $method] );
            }
        }
        $jsoner = new Jsoner();
        return $jsoner->createJson($data)->getJsonString();
    }
}
