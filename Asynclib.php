<?php

/**
 * Asynclib
 * Copyright (C) 2021  Nikita Podvirnyy

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 * 
 * Contacts:
 *
 * Email: <suimin.tu.mu.ga.mi@gmail.com>
 * GitHub: https://github.com/KRypt0nn
 * VK:     https://vk.com/technomindlp
 */

namespace Asynclib;

/**
 * System OS
 * Actually we're interested in is it windows or not
 */
define ('Asynclib\\OS', strtoupper (substr (PHP_OS, 0, 3)) == 'WIN' ? 'Windows' : 'Linux');

/**
 * Is sockets extension loaded
 * If it is, the library will use
 * IPC communications except temp files
 * 
 * Now this feature is not working properly so
 * it is disabled by default
 */
const IPC_AVAILABLE = false;
// define ('Asynclib\\IPC_AVAILABLE', extension_loaded ('sockets'));

/**
 * Parallel process starter for windows
 * We have to use it because we can't run
 * Parallel applications from console
 */
const CHAIN_BINARY = __DIR__ .'/chain/chain.exe';

/**
 * Library classes autoloader
 */
spl_autoload_register (function (string $class)
{
    if (strlen ($class) > 8 && file_exists ($class = __DIR__ .'/src'. str_replace ('\\', '/', substr ($class, 8)) .'.php'))
        require $class;
});
