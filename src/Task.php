<?php

namespace Asynclib;

use Asynclib\IPC\{
    Client,
    Server
};

/**
 * Task class
 * Provides you ability to create asynchronous task
 */
class Task
{
    /**
     * File that provides a TaskRunner object
     */
    protected string $file;

    /**
     * Task IPCs
     */
    protected Client $ipc_client;
    protected Server $ipc_server;

    /**
     * Task events handlers
     */
    protected array $events = [];
    protected $finishEvent = null;

    /**
     * Task state
     * 0 - just initialized
     * 1 - running
     * 2 - finished
     */
    protected int $state = 0;
    
    protected string $taskid;
    protected int $taskpid = -1;
    protected $output;

    /**
     * Create new task
     * 
     * @param string $file - path to file that provides a TaskRunner object
     * 
     * @throws \Exception - throws exception when file is not reachable
     */
    public function __construct (string $file)
    {
        $this->file = $file;

        if (!is_readable ($file))
            throw new \Exception ('File is not readable');

        $taskid = uniqid ();
        $tmpfile = sys_get_temp_dir () .'/'. $taskid;

        $this->taskid  = $taskid;

        $this->ipc_client = new Client ($tmpfile .'_w.sock');
        $this->ipc_server = new Server ($tmpfile .'_r.sock');
    }

    /**
     * Constructor shortcut
     * 
     * @param string $file
     * 
     * @return self
     */
    public static function create (string $file): self
    {
        return new self ($file);
    }

    /**
     * Run this task
     * 
     * [@param array $options = []]
     * Any options that will be sent to the TaskRunner callback
     * 
     * @return self
     * 
     * @throws \Exception - throws exception when task is already running
     */
    public function run (array $options = []): self
    {
        if ($this->running ())
            throw new \Exception ('Task is already running');
        
        $this->state = 1;

        switch (OS)
        {
            case 'Windows':
                /**
                 * chain.exe php [base64: -r "(require \"<task_file.php>\")->execute(\"<task_id>\",\"<base64 serialized array of options>\");"]
                 */
                exec ('"'. CHAIN_BINARY .'" "'. PHP_BINARY .'" '. base64_encode ('-r "(require \"'. addslashes ($this->file) .'\")->execute(\"'. $this->taskid .'\",\"'. base64_encode (serialize ($options)) .'\");"'));

                break;

            case 'Linux':
                /**
                 * nohup php -r "(require \"<task_file.php>\")->execute(\"<task_id>\",\"<base64 serialized array of options>\");" > /dev/null 2>&1 &
                 */
                exec ('nohup "'. PHP_BINARY .'" -r "(require \"'. addslashes ($this->file) .'\")->execute(\"'. $this->taskid .'\",\"'. base64_encode (serialize ($options)) .'\");" > /dev/null 2>&1 &');

                break;
        }

        while (true)
        {
            while (($data = $this->ipc_server->listen ()) === null);

            $data = unserialize ($data);

            if ($data['action'] == 'init')
            {
                $this->taskpid = $data['pid'];

                break;
            }
        }

        return $this;
    }

    /**
     * Manage task event handler
     * 
     * @param string $event - event name
     * @param callable|null $callback - event handler or null if event should be removed
     * 
     * Callback format: function ($data, Task $task)
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
     * Manage task finished event handler
     * 
     * @param callable|null $callback
     * 
     * @return self
     */
    public function onFinished (?callable $callback): self
    {
        $this->finishEvent = $callback;

        return $this;
    }

    /**
     * Perform task event
     * 
     * @param string $event - event name
     * @param mixed $data - event data
     * 
     * @return self
     * 
     * @throws \Exception - throws exception when task is not initialized or already finished
     */
    public function perform (string $event, $data): self
    {
        switch ($this->state)
        {
            case 0:
                throw new \Exception ('Task is not initialized');

                break;

            case 2:
                throw new \Exception ('Task is finished');

                break;
        }

        $this->ipc_client->send (serialize ([
            'event' => $event,
            'data'  => $data
        ]));

        return $this;
    }

    /**
     * Get task id
     * 
     * @return string
     */
    public function id (): string
    {
        return $this->taskid;
    }

    /**
     * Get task process id
     * 
     * @return int
     * 
     * @throws \Exception - throws exception when task is not initialized or already finished
     */
    public function pid (): int
    {
        switch ($this->state)
        {
            case 0:
                throw new \Exception ('Task is not initialized');

                break;

            case 1:
                return $this->taskpid;

                break;

            case 2:
                throw new \Exception ('Task is finished');

                break;
        }
    }

    /**
     * Update task state
     * 
     * @return bool|null
     * 
     * Returns null if tash is not initialized
     * Returns true if task is finished and false if
     * task is still running
     */
    public function update (): ?bool
    {
        if ($this->state == 0)
            return null;
        
        while (($data = $this->ipc_server->listen ()) !== null)
        {
            $data = unserialize ($data);

            switch ($data['action'])
            {
                case 'perform':
                    if (isset ($this->events[$data['event']]))
                        $this->events[$data['event']] ($data['data'], $this);

                    else throw new \Exception ('Tried to perform undefined event '. $data['event']);

                    break;

                case 'finish':
                    $this->state = 2;
                    $this->output = $data['output'];

                    if ($this->finishEvent !== null)
                        call_user_func ($this->finishEvent, $data['output'], $this);

                    break;
            }
        }

        return $this->state == 2;
    }

    /**
     * Get task state
     * 
     * @return int
     */
    public function state (): int
    {
        return $this->state;
    }

    /**
     * Checks if task is initialized (state = 0)
     * 
     * @return bool
     */
    public function initialized (): bool
    {
        return $this->state == 0;
    }

    /**
     * Checks if task is running (state = 1)
     * 
     * @return bool
     */
    public function running (): bool
    {
        return $this->state == 1;
    }

    /**
     * Checks if task is finished (state = 2)
     * 
     * @return bool
     */
    public function done (): bool
    {
        return $this->state == 2;
    }

    /**
     * Wait untill task finish or time is up
     * 
     * [@param int $milliseconds = null] - maximal amount of milliseconds
     *                                     to wait untill task will finish
     * 
     * [@param int $delay = 100] - amount of milliseconds to wait
     *                             between task states checks
     * 
     * [@param callable $callback = null] - callback which will be called
     *                                      in every task checking iteration
     * Format: function (Task $task)
     * 
     * If this callback will return true (boolean) value - then
     * waiter will be stopped
     * 
     * @return self
     */
    public function wait (?int $milliseconds = null, int $delay = 100, callable $callback = null): self
    {
        if ($this->state == 0)
            throw new \Exception ('Task is not started');
        
        if (!$this->running ())
            return $this;
        
        if ($milliseconds !== null)
            $stop_counter = (int)($milliseconds / $delay);

        while ($this->update () === false)
        {
            if ($milliseconds !== null && $stop_counter-- == 0)
                break;

            if ($callback !== null && $callback ($this) === true)
                break;

            usleep ($delay * 1000);
        }

        return $this;
    }

    /**
     * Get task output
     * 
     * @throws \Exception - throws exception when task is not completed or started
     */
    public function output ()
    {
        switch ($this->state)
        {
            case 0:
                throw new \Exception ('Task is not initialized');

                break;

            case 1:
                throw new \Exception ('Task is not completed');

                break;

            case 2:
                return $this->output;

                break;
        }
    }

    /**
     * Forcibly stop task execution
     * 
     * @return self
     */
    public function stop (): self
    {
        switch (OS)
        {
            case 'Windows':
                exec ('taskkill /f /t /pid '. $this->pid ());

                break;

            case 'Linux':
                exec ('kill -9 '. $this->pid ());

                break;
        }

        $this->state = 0;

        return $this;
    }

    /**
     * Task destructor
     * 
     * Stops running process if it is and removes temp files
     */
    public function __destruct ()
    {
        $this->ipc_client->close ();
        $this->ipc_server->close ();

        if ($this->state == 0)
            return;
        
        if ($this->state == 1)
            $this->stop ();
    }
}
