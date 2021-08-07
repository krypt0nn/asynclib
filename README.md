<h1 align="center">🚀 Asynclib</h1>

**Asynclib** is a library that gives you ability to create asynchronous tasks in PHP 7.4+

## Installation

```
composer require krypt0nn/asynclib
```

## About asynchronous programming

<p align="center"><img src="https://i.ibb.co/PGn9DX0/Untitled-Diagram.png"></p>

\* yellows is a tasks ran asynchronously

This diagram shows the difference between synchronous and asynchronous programs. First ones executing every task in the main process so if you have a task like get a huge file from a website - the program will be paused (lagged) until this task will be not finished. It also means that other tasks in your program will be executed only after this one

Asynchronous programs execute tasks parallel. In Asynclib, every new task runs in another process which means that they will not affect the main program and stop its execution. We can run long high weight tasks async and monitor their states from the main process which will give us a valuable performance boost

## Basic usage

> For practical example you can look at [test](/test) directory
> 
> Also, keep in mind that this library was developed only for desktop usage. I didn't test it on websites and don't know will it works there or not

Every task should be written in its file. This file should return the `TaskRunner` object. For example:

```php
<?php

require 'vendor/autoload.php';

use Asynclib\TaskRunner;

return new TaskRunner (function (array $options, TaskRunner $task)
{
    // your task code goes here
});
```

Callable inside `TaskRunner` constructor takes 2 arguments: `array $options` and `TaskRunner $task`. The first is an array of parameters you give this task in `Task->run` method when the second is the current `TaskRunner` object

To run this task in your main code you should use `Task` class:

```php
<?php

require 'vendor/autoload.php';

use Asynclib\Task;

// Create new task from file
// and run it

$task = Task::create ('path/to/task/file.php')->run ([
    // here you can write some options
    // that will be available in TaskRunner
    // also you can run this method
    // without any arguments
]);
```

After that you `TaskRunner` will begin its work in another process so it will not affect your code in the current process, stop it, or something like that

`Task` object, which is now stored in our `$task` variable, has a lot of useful methods. But the major is `update`. This method will synchronize task state and process performed events

```php
/**
 * Task state
 * null  - task is not initialized
 * false - task is still running
 * true  - task is done and you can get its output
 */
$state = $task->update ();
```

We can wait until task will be completed using another major method `wait`

```php
// PHP 8 syntax
$task->wait (milliseconds: 5000);
```

This method has 3 arguments: `?int $milliseconds = null`, `int $delay = 100` and `callable $callback = null`. First is the amount of milliseconds after which this function will stop ints work. If you write here a null value - then this method will wait until the task will not be finished. The second argument defines delay between task state updates (`update` method calls). And the last argument is a callable that takes current `Task` object and runs after each task update. If this callable returns `true` (boolean value) - then the wait method will be stopped

```php
$task->wait (delay: 1000, callable: function ()
{
    echo 'Task is not finished yet' . PHP_EOL;
});
```

Another useful part of `Task` objects is that you can give them an events. For example:

```php
$task->on ('some_event', function ($data, Task $task)
{
    echo 'Hey! I got a message from task: '. $data . PHP_EOL;
});
```

And perform this event from `TaskRunner` callable:

```php
<?php

require 'vendor/autoload.php';

use Asynclib\TaskRunner;

return new TaskRunner (function (array $options, TaskRunner $task)
{
    for ($i = 0; $i < 5; ++$i)
    {
        // Performing event "some_event"
        // in main process
        $task->perform ('some_event', 'Now I am at position '. ($i + 1));

        sleep (1);
    }
});
```

Event `some_event` will be performed when method `update` will be called

`$data` argument can be anything PHP can serialize with function `serialize`: string, number, boolean, array, object...

Task also has a special event which will be called when it will be done

```php
$task->onFinished (function ($output, Task $task)
{
    echo 'Hey! Task with id '. $task->id() .
        ' seems to be finished with output: '. $output . PHP_EOL;
});
```

To forcibly interrupt task execution you can call method stop

```php
$task->stop ();
```

It also will set the task state as 0 (just initialized)

Other available methods:

| Name | Type | Description |
| - | - | - |
| output | sting | Get task output if it is done |
| id | string | Get task id |
| pid | int | Get id of the process which executing this task |
| state | int | Get task state. 0 - just initialized, 1 - running, 2 - done |
| initialized | bool | Check if the task is just initialized |
| running | bool | Check if the task is running |
| done | bool | Check if the task is done |

## Tasks pools

You can unite some tasks into one pool and run them together

```php
<?php

require 'vendor/autoload.php';

use Asynclib\Pool;

// Create tasks pool
$pool = Pool::create ([
    'path/to_some_task.php',
    'path/to_some_another_task.php',

    Task::create ('or_the_task/itself.php')->onFinished (function ($output)
    {
        echo 'Task finished with output: '. $output . PHP_EOL;
    })
]);

// Run pool execution
$pool->run ();

// Wait untill all tasks in this pool will be finished
$pool->updateWhileExist ();
```

Method `update` will update states of all the tasks in this pool and remove the ones which are already finished. It also can take a callback which will be used for every unfinished task. `updateWhileExist` is something similar to `wait` in `Task`, but works like `update`: takes a callable and runs it for every unfinished task until there will be unfinished tasks

You can get tasks outputs with method `output`

```php
$task_output = $pool->output ('task_id');
```

This method will throw an exception if provided task id does not exist or this task is not finished yet

Also, you can use `outputOrNull` method if you don't want to work with exceptions. Null value will be returned when the task is not finished or is not exists

Method `add` can add a new task to the pool

```php
$pool->add (Task::create ('path_to_task.php'));
```

## Known issues and todos

- [ ] This library technically support IPC protocol, but it is not working properly

<br>

Author: [Nikita Podvirnyy](https://vk.com/technomindlp)
