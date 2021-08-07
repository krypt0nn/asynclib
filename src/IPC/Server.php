<?php

namespace Asynclib\IPC;

use const Asynclib\IPC_AVAILABLE;

/**
 * IPC server
 * Provides you ability to
 * receive data through IPC protocol
 * 
 * Uses temp files if IPC is not available
 */
class Server
{
    protected $socket;
    protected $client = null;
    protected $inner = 0;

    /**
     * @param string $file - path to sock file
     */
    public function __construct (string $file)
    {
        if (IPC_AVAILABLE)
        {
            $this->socket = \socket_create (AF_UNIX, SOCK_STREAM, 0);

            \socket_bind ($this->socket, $file);
            \socket_listen ($this->socket);
            \socket_set_nonblock ($this->socket);
        }

        else $this->socket = $file;
    }

    /**
     * Read socket data
     * 
     * @return null|string - return null if client is not connected or
     *                       data is not available
     */
    public function listen (): ?string
    {
        if (IPC_AVAILABLE)
        {
            // This code is not working properly
            // and needs to be fixed
            
            if ($this->client !== null)
            {
                $data = socket_read ($this->client, 1024);

                if ($data !== false)
                {
                    while (($buffer = \socket_read ($this->client, 1024)) != '')
                        $data .= $buffer;

                    return strlen ($data) > 0 ? $data : null;
                }
            }

            if ($client = \socket_accept ($this->socket))
            {
                $this->client = $client;

                $data = '';

                while (($buffer = \socket_read ($client, 1024)) != '')
                    $data .= $buffer;

                    return strlen ($data) > 0 ? $data : null;
            }

            return null;
        }

        else
        {
            if (file_exists ($file = $this->socket .'.'. $this->inner))
            {
                $data = file_get_contents ($file);

                if (strlen ($data) == 0)
                    return null;

                unlink ($file);
                ++$this->inner;

                return $data;
            }

            return null;
        }
    }

    /**
     * Close socket connnection
     */
    public function close (): void
    {
        if (IPC_AVAILABLE)
        {
            \socket_close ($this->socket);

            if ($this->client !== null)
                \socket_close ($this->client);
        }
    }

    /**
     * Close socket connection if object destructs
     */
    public function __destruct ()
    {
        if (IPC_AVAILABLE)
        {
            \socket_close ($this->socket);

            if ($this->client !== null)
                \socket_close ($this->client);
        }
    }
}
