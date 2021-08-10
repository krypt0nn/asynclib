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
$info = 'Hello, World!';

$runner = new TaskRunner (function (array $options, TaskRunner $task) use (&$info)
{
    for ($i = 0; $i < 5; ++$i)
    {
        $task->update();
        $task->perform ('cycleIteration', $info);

        sleep (1);
    }

    return $options['test_output'];
});

$runner->on('displayInfo', function ($data) use (&$info)
{
    $info = $data;
});

return $runner;
