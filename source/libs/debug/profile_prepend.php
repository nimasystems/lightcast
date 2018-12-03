<?php
/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com
*/

/**
 * XHProf - Prepend file
 * @package FileCategory
 * @subpackage FileSubcategory
 * @changed $Id: profile_prepend.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */

// currently not supported
if (php_sapi_name() == 'cli') {
    return;
}

if (!function_exists('xhprof_enable')) {
    echo 'XHProf is not available on this system';
    exit(1);
}

/*
 * Enable xhprof
* Extra flags can be passed by defining PROFILER_XHPROF_EXTRA_FLAGS
*/
xhprof_enable(XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_CPU | (defined('PROFILER_XHPROF_EXTRA_FLAGS') ? PROFILER_XHPROF_EXTRA_FLAGS : null));

register_shutdown_function(function () {
    // by registering register_shutdown_function at the end of the file
    // I make sure that all execution data, including that of the earlier
    // registered register_shutdown_function, is collected.

    $xhprof_data = xhprof_disable();

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    if (!$xhprof_data) {
        return;
    }

    if (defined('PROFILER_CALLBACK')) {
        $callback_func = PROFILER_CALLBACK;
        $callback_func = strstr(':', $callback_func) ? explode(':', $callback_func) : $callback_func;
        $callback_func = is_array($callback_func) ? [$callback_func[0], $callback_func[1]] : $callback_func;

        call_user_func_array($callback_func, [$xhprof_data]);
    }
});