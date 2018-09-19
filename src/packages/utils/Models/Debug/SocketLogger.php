<?php
/**
 * Created by PhpStorm.
 * User: artur.kmera
 * Date: 17.09.2018
 * Time: 11:02
 */

namespace Arrow\Utils\Models\Debug;


class SocketLogger
{
    private $address = "127.0.0.1";
    private $port = "1337";

    public function __construct()
    {

    }

    public function log($msg)
    {
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($sock, $this->address, $this->port);

        if (!is_string($msg)) {
            $msg = print_r($msg, 1);
        }
        socket_write($sock, $msg, strlen($msg));
        socket_close($sock);
    }


}