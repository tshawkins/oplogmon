#!/usr/bin/php -q
<?php
/**
 * Created by JetBrains PhpStorm.
 * User: thawkins
 * Date: 2/23/11
 * Time: 12:37 PM
 * To change this template use File | Settings | File Templates.
 */


error_reporting(E_ALL);

$path = realpath(dirname(__FILE__) . '/..' );

// Define path to application directory
defined('APPLICATION_PATH')
        || define('APPLICATION_PATH', $path);

// Define application environment


// Ensure library/ is on include_path

$include_path   =  implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/library'),
    APPLICATION_PATH ,
    get_include_path()
));

set_include_path($include_path);


require_once "System/Daemon.php";
require_once 'Console/Getopt.php';
require_once 'library/parseoplog.php';


/**
 * Make a key-value array.
 * Since Console_Getopt does not provide such a method,
 * we implement it ourselves.
 *
 * @params array $params Array of parameters from Console_Getopt::getopt2()
 *
 * @return array key-value pair array
 */
function &condense_arguments($params)
{
    $new_params = array();
    foreach ($params[0] as $param) {
        $new_params[$param[0]] = $param[1];
    }
    return $new_params;
}

$cg = new Console_Getopt();
$args = $cg->readPHPArgv();
$script_name = $args[0];
$script_name = preg_replace("/\.php/i", "", $script_name);
array_shift($args);
$shortOpts = 's:nl:ih';
$longOpts  = array('source=', 'no-daemon', 'log=', 'init-d' , 'help');
$params = $cg->getopt2($args, $shortOpts, $longOpts);

if(!$params instanceof PEAR_Error){
    $params = condense_arguments($params);
} else {
    echo $params->message;
    exit(-1);
}

echo print_r($params,true);

// Setup
$options = array(
    'appName' => 'oplogmon',
    'appDir' => APPLICATION_PATH."/tasks",
    'appDescription' => 'Monitors MongoDB oplog for changes',
    'authorName' => 'Tim Hawkins',
    'authorEmail' => 'tim.hawkins@me.com',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '1024M',
    'appRunAsGID' => 1000,
    'appRunAsUID' => 1000,
);

System_Daemon::setOptions($options);

if(array_key_exists('h', $params) || array_key_exists('--help', $params)){
    System_Daemon::log(System_Daemon::LOG_WARNING, "oplogmon.php ['-h' | '--help'] ['-s sourcehost' | '--source=sourcehost'] ['-n' | '--no-daemon']\n");
    exit(0);
}



// With the runmode --write-initd, this program can automatically write a
// system startup file called: 'init.d'
// This will make sure your daemon will be started on reboot
if (!array_key_exists('i', $params) && !array_key_exists('--init-d', $params)) {
//    System_Daemon::info('not writing an init.d script this time');
} else {
    if (($initd_location = System_Daemon::writeAutoRun()) === false) {
        System_Daemon::notice('unable to write init.d script');
        exit(-1);
    } else {
        System_Daemon::info(
            'sucessfully written startup script: %s',
            $initd_location
        );
        exit(0);
    }
}

$source = null;

// setup the master to track
if (array_key_exists('s', $params) || array_key_exists('--source', $params)) {
    if(array_key_exists('s', $params)){
        $source = trim($params['s']);
    } else {
        $source = trim($params['--source']);
    }
} else {
    System_Daemon::info("you must specify a source with --source\n");
    exit(-1);
}

// This program can also be run in the forground with runmode --no-daemon
if (!array_key_exists('n', $params) && !array_key_exists('--no-daemon', $params)) {
    // Spawn Daemon
    System_Daemon::start();
} else {
    System_Daemon::notice("running in foreground\n");
}


// Run your code
// Here comes your own actual code

// This variable gives your own code the ability to breakdown the daemon:
$runningOkay = true;

// Create an instance of the Runtime interface to mongo

$hMonitor = parseOplog::getInstance($source);

// While checks on 3 things in this case:
// - That the Daemon Class hasn't reported it's dying
// - That your own code has been running Okay
// - That we're not executing more than 3 runs
while (!System_Daemon::isDying() && $runningOkay) {


    $runningOkay = true;


    $runningOkay = $hMonitor->pollOplog();



    if (!$runningOkay) {
        System_Daemon::err('oplogmon() produced an error, '.
            'so this will be my last run');
    }
}

// Shut down the daemon nicely
// This is ignored if the class is actually running in the foreground
System_Daemon::stop();


System_Daemon::log(System_Daemon::LOG_INFO, "Finished");



