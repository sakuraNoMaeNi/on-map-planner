<?php
namespace omp;

// ===============
// error reporting
// ===============
error_reporting(E_ALL | E_STRICT ); # all php errors and deprication warnings
ini_set('log_errors', 'On'); # Log errors to the server's error log in stead of printing them.
ini_set('html_errors', 'Off'); # Do not use HTML formatting in error messages.

define( 'IS_XML_HTTP_REQUEST',
isset($_SERVER, $_SERVER['HTTP_X_REQUESTED_WITH'])
&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');


// =================================================
// Setup the automatic class loader & inclusion path
// =================================================
define( 'BASE_PATH', dirname(__DIR__) ); # Our base path based on the location of this init file
define( 'PRIVATE_PATH', BASE_PATH . '/private' ); # Our private path
$_SERVER['DOCUMENT_ROOT'] = BASE_PATH . '/public'; # Set/overwrite document root
set_include_path( implode( PATH_SEPARATOR, [
	get_include_path(),       // From php.ini
	BASE_PATH,                // base path
	PRIVATE_PATH,   // private path in case you'd ever move this init file
	$_SERVER['DOCUMENT_ROOT'] // public path
] ) ); # Set include paths

require_once( PRIVATE_PATH . '/vendor/autoload.php' ); # Global composer autoloader


spl_autoload_register(function ($class)
{ // force load from selected folders based on namespace, leave options open for other paths to use frameworks as library
	$class   = str_replace('\\', DIRECTORY_SEPARATOR, $class); # Transform namespace to path and get top namespace which should be the project's name
	$project = strtok($class, DIRECTORY_SEPARATOR); # Project specific source
	switch($project)
	{
		# Bruneau
		case 'omp':
			$src = sprintf('%s/../%s.php', $_SERVER['DOCUMENT_ROOT'], $class);
		break;
	}
	if( $src = realpath($src) )
	{ // Load class from source
		include $src; # always absolute, so no include_path required
		return true;
	}
	return false;
});


// ==============================
// Setup some basic errorhandling
// ==============================

$ouch = new \Ouch\Reporter;
$ouch->on();

/**
 * Log messages for a catch, there is no reason to throw an error.
 * If you want to get an error, use an ErrorException.
 */
set_exception_handler(function ($e)
{
	// Prevent Xdebug from truncating the exception information.
	ini_set('xdebug.var_display_max_data', -1);
	ini_set('xdebug.var_display_max_depth', -1);

	$dumpChars    = ['\t', '\n'];
	$replaceChars = [ '	',  '
		'];
	$logger = Config::getLogger();
	$logger->setSuppressStackTrace(true);
	$logMsg = 'Uncaught Exeption of type ' . get_class($e) . '
	[ErrorCode] ' . $e->getCode() . '
	[ErrorMessage] ' .  $e->getMessage() . '
	[Location] ' .  $e->getFile() . ' : ' .  $e->getLine() . '
	[URL] ' . Url::currentUrl() . '
	[Trace] ' .  str_replace( $dumpChars, $replaceChars, $logger->getVarDump( $e->getTrace() ) ) . '
	( [TraceAsString] ' .  $e->getTraceAsString() .
	( property_exists( $e, 'xdebug_message' ) ? '
	[XDebug_message] ' . $e->xdebug_message : '' );
	$logger->fatal($logMsg);
	$logger->resetSuppressStackTrace();
});


/**
 * Custom error handler to log our own errors/warnings/notices.
 * @param int $errorLevel The PHP error level.
 * @param string $error The PHP error message.
 * @param string $file The file in which the error was triggered.
 * @param int $line The line on which the error was triggered.
 * @param array $context The variables in scope when the error was triggered.
 */
function jmbErrorHandler($errorLevel, $error, $file, $line, $context)
{
	$fullpath = (IS_PRODUCTION) ? false : BASE_PATH;
	$logged   = \bruneau\logger\Logger::log($error, $errorLevel, $file, $line, $context, $fullpath);
	// If our own logger didn't work, or the error level is set to
	// be reported (see error_reporting()), return false,
	// and PHP's internal error handler will take over.
	if (!$logged || (ini_get('error_reporting') & $errorLevel))
	{
		return false;
	}
	return $logged;
}
