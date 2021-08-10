<?php

namespace Asynclib\IPC;

use const Asynclib\IPC_AVAILABLE;

/**
 * IPC client
 * Provides you ability to send data
 * through IPC protocol
 * 
 * Uses temp files if IPC is not available
 */
class Client
{
    protected $socket;
    protected int $inner = 0;

    /**
     * @param string $file - path to sock file
     */
    public function __construct (string $file)
    {
        if (IPC_AVAILABLE)
        {
            $this->socket = socket_create (AF_UNIX, SOCK_STREAM, 0);

            socket_connect ($this->socket, $file);
        }

        else $this->socket = $file;
    }

    /**
     * Send data
     * 
     * @param string $data
     * 
     * @return self
     */
    public function send (string $data)
    {
        if (IPC_AVAILABLE)
            socket_write ($this->socket, $data, strlen ($data));

        else file_put_contents ($this->socket .'.'. $this->inner++, $data);

        return $this;
    }

    /**
     * Close socket
     */
    public function close (): void
    {
        if (IPC_AVAILABLE)
            socket_close ($this->socket);
    }

    /**
     * Close socket when object destructs
     */
    public function __destruct ()
    {
        if (IPC_AVAILABLE)
            socket_close ($this->socket);
    }
}
