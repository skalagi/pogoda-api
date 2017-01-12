<?php

namespace SyntaxError\SocketBundle\Task;

use SyntaxError\SocketBundle\Server\Informer;
use SyntaxError\SocketBundle\Server\Server;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;

class Weather extends Server
{
    private $provider;

    private $sourceClientPassword;

    public function __construct(LoopInterface $loop, Informer $informer, array $config)
    {
        parent::__construct($loop, $informer, $config);
        $this->provider = new Provider();
        $this->sourceClientPassword = isset($config['source_client_password']) ? $config['source_client_password'] : null;

    }

    public function onOpen(ConnectionInterface $conn)
    {
        $client = parent::onOpen($conn);
        $conn->send($this->provider->getBasic());
        return $client;
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $client = parent::onMessage($conn, $msg);
        $parsed = json_decode($msg, JSON_OBJECT_AS_ARRAY);
        if($this->sourceClientPassword && isset($parsed['_source_client_password']) && password_verify($parsed['_source_client_password'], $this->sourceClientPassword)) {
            $basic = $this->provider->getBasic();
            $sentCount = 0;
            foreach($this->clients as $client) {
                /** @noinspection PhpUndefinedFieldInspection */
                if($client->resourceId != $conn->resourceId) {
                    $client->send($basic); $sentCount++;
                }
            }
            $conn->send(json_encode(['_status' => sprintf('Sent data to %s clients!', $sentCount)]));
        }
        return $client;
    }
}
