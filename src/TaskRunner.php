<?php

namespace Asynclib;

use Asynclib\IPC\Client;

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

    protected Client $ipc;

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
        $this->ipc = new Client (sys_get_temp_dir () .'/'. $taskId .'.sock');

        $this->ipc->send (serialize ([
            'action' => 'init',
            'pid' => getmypid ()
        ]));

        $result = call_user_func ($this->callback, unserialize (base64_decode ($options)), $this);

        $this->ipc->send (serialize ([
            'action' => 'finish',
            'output' => $result
        ]));

        // file_put_contents (sys_get_temp_dir () .'/'. $taskId .'.pid', getmypid ());
        // file_put_contents (sys_get_temp_dir () .'/'. $taskId, serialize (call_user_func ($this->callback, unserialize (base64_decode ($options)), $this)));
    }

    public function perform (string $event, $data): self
    {
        $this->ipc->send (serialize ([
            'action' => 'perform',
            'event'  => $event,
            'data'   => $data
        ]));

        return $this;
    }
}
