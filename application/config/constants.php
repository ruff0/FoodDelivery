<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE')  OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE')   OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE')  OR define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ')                           OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE')                     OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE')       OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE')  OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE')                   OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE')              OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT')            OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT')       OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS')        OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR')          OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG')         OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE')   OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS')  OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE')       OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN')      OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code


/* =============  RESOURCE RETURN CODE ============== */
defined('RESULT_SUCCESS')        OR define('RESULT_SUCCESS', 0); // no errors
defined('RESULT_ERROR')        OR define('RESULT_ERROR', 4000); 
defined('RESULT_ERROR_RESOURCE_NOT_FOUND')        OR define('RESULT_ERROR_RESOURCE_NOT_FOUND', 4001); 
defined('RESULT_ERROR_ID_REQUIRED')        OR define('RESULT_ERROR_ID_REQUIRED', 4002); 
defined('RESULT_ERROR_PARAMS_REQUIRED')        OR define('RESULT_ERROR_PARAMS_REQUIRED', 40030); 
defined('RESULT_ERROR_PARAMS_INVALID')        OR define('RESULT_ERROR_PARAMS_INVALID', 4003); 
defined('RESULT_ERROR_ACCESS_TOKEN_INVALID')        OR define('RESULT_ERROR_ACCESS_TOKEN_INVALID', 4004); 
defined('RESULT_ERROR_ACCESS_TOKEN_EXPIRED')        OR define('RESULT_ERROR_ACCESS_TOKEN_EXPIRED', 4005); 
defined('RESULT_ERROR_ACCESS_TOKEN_REQUIRED')        OR define('RESULT_ERROR_ACCESS_TOKEN_REQUIRED', 4006); 
defined('RESULT_ERROR_USER_INVALID')        OR define('RESULT_ERROR_USER_INVALID', 4007); 
defined('RESULT_ERROR_TOTAL_INVALID')        OR define('RESULT_ERROR_TOTAL_INVALID', 4008); 

/* ============= STATUS CONSTANTS ===================== */

defined('ORDER_STATUS_UNDER_PROCESS')       OR define('ORDER_STATUS_UNDER_PROCESS', 1); 
defined('ORDER_STATUS_ACCEPTED')            OR define('ORDER_STATUS_ACCEPTED', 2); 
defined('ORDER_STATUS_WAITING_PAYMENT')     OR define('ORDER_STATUS_WAITING_PAYMENT', 2); 
defined('ORDER_STATUS_COMPLETED')           OR define('ORDER_STATUS_COMPLETED', 3); 
defined('ORDER_STATUS_CANCELED')            OR define('ORDER_STATUS_CANCELED', -1); 

defined('CART_STATUS_ACTIVE')       OR define('CART_STATUS_ACTIVE', 1); 
defined('CART_STATUS_CANCELED')     OR define('CART_STATUS_CANCELED', -1); 

/* ============== SERVICE CONSTANTS ==============*/
defined('SERVICE_DELIVERY')     OR define('SERVICE_DELIVERY', 1); 
defined('SERVICE_CATERING')     OR define('SERVICE_CATERING', 2); 
defined('SERVICE_RESERVATION')  OR define('SERVICE_RESERVATION', 3); 
defined('SERVICE_PICKUP')       OR define('SERVICE_PICKUP', 4); 

/* =========== TIME CONSTANTS ============= */
defined('CART_TIMEOUT')     OR define('CART_TIMEOUT', 15 * 60); 

defined('ITEM_PRICE_TYPE_BY_VARIATION')     OR define('ITEM_PRICE_TYPE_BY_VARIATION', 1); 
defined('ITEM_PRICE_TYPE_BY_MAIN')          OR define('ITEM_PRICE_TYPE_BY_MAIN', 2); 