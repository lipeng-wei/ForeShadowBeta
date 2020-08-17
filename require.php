<?php

ini_set('display_errors', false);
//error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
error_reporting(E_ERROR);
//error_reporting(E_ALL);


define('APP_PATH', dirname(__FILE__). '/');
define('CONF_PATH', APP_PATH. 'conf/');
define('LOG_PATH', APP_PATH. 'log/');
define('LIB_PATH', APP_PATH. 'lib/');
define('TMP_PATH', APP_PATH. 'tmp/');
define('DATA_PATH', APP_PATH. 'data/');
define('MODEL_PATH', APP_PATH. 'model/');
define('SCRIPT_PATH', APP_PATH. 'script/');
define('OUTPUT_PATH', APP_PATH. 'output/');


require_once(LIB_PATH . "Util.php");
require_once(LIB_PATH . "Logic.php");
require_once(LIB_PATH . "Helper.php");
require_once(LIB_PATH . "Container.php");
require_once(LIB_PATH . "TmpFile.php");
require_once(LIB_PATH . "TdxUtil.php");

Container::register('__request_uniqid__', uniqid());


require_once(LIB_PATH . "Config.php");
define('CONFIG_FILE', CONF_PATH. 'app.ini');
if (Config::load(CONFIG_FILE) === false ) {
    exit(0);
}

require_once(LIB_PATH . "Log.php");
$level = Config::get('Log.Level');
Log::setLogLevel($level);


register_shutdown_function(function () {
    $last_error = error_get_last();
    if ($last_error['type'] === E_ERROR) {
        Log::easyError('errno:'.$last_error['type'], 'error:'.$last_error['message'],
            " file:".$last_error['file'],  "line:".$last_error['line']);
    }
    return false;
});

set_exception_handler(function ($exception) {
        Log::easyError("exception occured, but not catched", $exception->getCode(),
            $exception->getMessage(), strval($exception));
        return false;
    }
);

require_once(MODEL_PATH . "StockData.php");
require_once(MODEL_PATH . "Refer.php");
require_once(MODEL_PATH . "Concept.php");






