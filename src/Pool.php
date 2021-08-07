<?php

namespace Asynclib;

/**
 * Pool class
 * Provides you ability to manage tasks groups
 * And add them "on done" callbacks
 */
class Pool
{
    /**
     * Array of tasks [task id => Task]
     */
    protected array $tasks = [];

    /**
     * Array of tasks outputs [task id => task output]
     */
    protected array $outputs = [];

    /**
     * [@param array $tasks = []] - list of tasks
     * 
     * You can put here both Task objects and tasks files pathes
     */
    public function __construct (array $tasks = [])
    {
        foreach ($tasks as $task)
        {
            if (is_string ($task) && file_exists ($task))
                $task = Task::create ($task);

            if (is_a ($task, Task::class))
                $this->tasks[$task->id ()] = $task;
        }
    }

    /**
     * Constructor shortcut
     * 
     * [@param array $tasks = []]
     * 
     * @return self
     */
    public function create (array $tasks = []): self
    {
        return new self ($tasks);
    }

    /**
     * Add new task to the pool
     * 
     * @param Task $task
     * 
     * @return self
     */
    public function add (Task $task): self
    {
        $this->tasks[] = $task;

        return $this;
    }

    /**
     * Get done task output
     * 
     * @param string $taskId
     * 
     * @throws \Exception - throws an exception when task output is not stored
     *                      like task id is incorrect or task is not finished
     */
    public function output (string $taskId)
    {
        if (!isset ($this->outputs[$taskId]))
            throw new \Exception ('No output for this task id is available');

        return $this->outputs[$taskId];
    }

    /**
     * Get done task output or null if it is not stored
     * 
     * @param string $taskId
     */
    public function outputOrNull (string $taskId)
    {
        return $this->outputs[$taskId] ?? null;
    }

    /**
     * Get amount of available (running or just initialized) tasks
     * Finished tasks will be automatically removed
     * 
     * @return int
     */
    public function size (): int
    {
        return sizeof ($this->tasks);
    }

    /**
     * Run tevery just initialized task in the pool
     * 
     * @return self
     */
    public function run (): self
    {
        foreach ($this->tasks as $task)
            if ($task->initialized ())
                $task->run ();
    }

    /**
     * Update tasks statuses in the pool
     * 
     * [@param callable $callback = null]
     * 
     * This callback will be used for every unfinished task
     * 
     * Callback looks like function (Task $task) and
     * if it returns a true (boolean) value - then
     * this task will be forcibly stopped and removed
     * 
     * @return self
     */
    public function update (callable $callback = null): self
    {
        foreach ($this->tasks as $id => &$task)
        {
            if ($task->update ())
            {
                $this->outputs[$id] = $task->output ();

                unset ($this->tasks[$id]);
            }

            elseif ($callback !== null && $callback ($task) === true)
            {
                $task->stop ();

                unset ($this->tasks[$id]);
            }
        }

        return $this;
    }

    /**
     * Update tasks statuses in the pool while at least one exist
     * 
     * Full alias of update function except this one will
     * update tasks untill there will be at least one
     * unfinished
     * 
     * [@param callable $callback = null]
     * 
     * @return null
     */
    public function updateWhileExist (callable $callback = null): self
    {
        while (sizeof ($this->tasks) > 0)
            $this->update ($callback);

        return $this;
    }
}
