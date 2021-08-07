<?php

/**
 * Require Asynclib
 */
require dirname (__DIR__) .'/Asynclib.php';

use Asynclib\Task;

/**
 * Creating a new task from task.php file
 */
$task = Task::create (__DIR__ .'/task.php');

/**
 * Add cycleIteration event handler
 */
$task->on ('cycleIteration', function ($data, Task $task)
{
    echo 'Cycle iteration event: '. $data . PHP_EOL;
});

/**
 * Run this task with "test_output" option
 */
$task->run ([
    'test_output' => 'Hello, World!'
]);

/**
 * Displaying task id
 */
echo 'Initialized task with id: '. $task->id () . PHP_EOL;
echo '                 and pid: '. $task->pid () . PHP_EOL . PHP_EOL;

/**
 * Waiting before task finish
 * with a one second delay
 * 
 * You also can use this variant:
 * 
 * while (!$task->done ())
 * {
 *     echo 'Task is not finished yet' . PHP_EOL;
 * 
 *     sleep (1);
 * }
 */
$task->wait (delay: 1000, callback: function ()
{
    echo 'Task is not finished yet' . PHP_EOL;
});

/**
 * Displaying task output (Hello, World!)
 */
echo 'Task finished with output: '. $task->output ();
