<?php

namespace Asynclib;

use Asynclib\IPC\{
    Client,
    Server
};

/**
 * TaskRunner class
 * Needs to specify your task in file
 * @see test folder
 */
class TaskRunner
{
    /**
     * Task callback with format function (array $options)
     * @see Task->run() method
     */
    protected $callback;

    /**
     * IPCs
     */
    protected Client $ipc_client;
    protected Server $ipc_server;

    /**
     * Array of events handlers
     */
    protected array $events = [];

    /**
     * @param callable $callback
     */
    public function __construct (callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Execute callback
     * 
     * @param string $taskId
     * @param string $options - base64 serialized array of options
     * 
     * @return void
     */
    public function execute (string $taskId, string $options): void
    {
        $this->ipc_client = new Client (sys_get_temp_dir () .'/'. $taskId .'_r.sock');
        $this->ipc_server = new Server (sys_get_temp_dir () .'/'. $taskId .'_w.sock');

        $this->ipc_client->send (serialize ([
            'action' => 'init',
            'pid' => getmypid ()
        ]));

        $result = call_user_func ($this->callback, unserialize (base64_decode ($options)), $this);

        $this->ipc_client->send (serialize ([
            'action' => 'finish',
            'output' => $result
        ]));
    }

    /**
     * Manage event handler
     * 
     * @param string $event - event name
     * @param callable|null $callback - event handler or null if event should be removed
     * 
     * Callback format: function ($data, TaskRunner $task)
     * 
     * @return self
     */
    public function on (string $event, ?callable $callback): self
    {
        if ($callback === null)
            unset ($this->events[$event]);

        else $this->events[$event] = $callback;

        return $this;
    }

    /**
     * Perform event execution
     * 
     * @param string $event - event name
     * @param mixed $data - event data
     * 
     * @return self
     */
    public function perform (string $event, $data): self
    {
        $this->ipc_client->send (serialize ([
            'action' => 'perform',
            'event'  => $event,
            'data'   => $data
        ]));

        return $this;
    }

    /**
     * Get and execute events from the main process
     * 
     * @return self
     */
    public function update (): self
    {
        while (($data = $this->ipc_server->listen ()) !== null)
        {
            $data = unserialize ($data);

            if (isset ($this->events[$data['event']]))
                call_user_func ($this->events[$data['event']], $data['data'], $this);
        }

        return $this;
    }
}
