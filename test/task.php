<?php

/**
 * Require Asynclib
 */
require dirname (__DIR__) .'/Asynclib.php';

use Asynclib\TaskRunner;

/**
 * Creating a TaskRunner which will return
 * us a "test_output" options value
 * after 5 seconds and before it
 * perform cycleIteration event
 * every second
 */
return new TaskRunner (function (array $options, TaskRunner $task)
{
    for ($i = 0; $i < 5; ++$i)
    {
        $task->perform ('cycleIteration', $i + 1);

        sleep (1);
    }

    return $options['test_output'];
});
