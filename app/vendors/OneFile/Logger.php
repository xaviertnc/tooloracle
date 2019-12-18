<?php namespace OneFile;

/**
 * @author C. Moller <xavier.tnc@gmail.com> - 2012
 *
 * @update C. Moller - 7 June 2014 - Complete Rewrite
 *   - Changed from fully static to regular class with magic methods.
 *   - Significantly simplified
 *
 * @update C. Moller - 18 December 2019 - Hack and slash...
 *   - Reduce complexity
 *   - Drop everything I never use!
 *   - Leave only essentials
 */
class Logger
{

	/**
	 *
	 * @var string
	 */
	protected $path;

	/**
	 *
	 * @var string
	 */
	protected $filename;

	/**
	 * Roll with your own message formatter ;-)
	 *
	 * @var closure
	 */
	protected $formatter;

	/**
	 *
	 * @var boolean
	 */
	public $enabled = true;

	/**
	 *
	 * @var string
	 */
	public static $shortDateFormat = 'Y-m-d';

	/**
	 *
	 * @var string
	 */
	public static $longDateFormat = 'd M Y H:i:s';

	/**
	 *
	 * @var octal
	 */
	public static $newFolderPermissions = 0755;


	/**
	 *
	 * @param string $logPath
	 * @param string $filename
	 * @param closure $formatter  fn($message, $messageType, $loggerInstance){..}
	 */
	public function __construct($logPath = null, $filename = null, $formatter = null)
	{
		$this->path = $logPath ?: __DIR__;
		$this->filename = $filename ?: date(Logger::$shortDateFormat) . '.log';
		$this->formatter = $formatter;
		if ( ! is_dir($this->path))
		{
			$oldumask = umask(0);
			mkdir($this->path, Logger::$newFolderPermissions, true);
			umask($oldumask);
		}
	}


	/**
	 *
	 * @param string $message
	 * @param string $type
	 * @return string
	 */
	public function format($message, $type = null)
	{
		if ($this->formatter) {	return $this->formatter($message, $type, $this);	}
		$typePrefix = $type ? '[' . str_pad(ucfirst($type), 5) . "]:\t" : '';
		return $typePrefix . date(Logger::$longDateFormat) . ' - ' . $message . PHP_EOL;
	}


	/**
	 * No error checking or supressing errors. Keep it fast.
	 * Make sure your folder exists and permissions are set correctly or expect fatal errors!
	 *
	 * @param string $message
	 * @param string $type
	 * @param string $filename
	 */
	public function write($message = '', $type = null)
	{
		$logFile = $this->path . '/' . $this->filename;
		$formattedMessage = $this->format($message, $type);
		file_put_contents($logFile, $formattedMessage, FILE_APPEND | LOCK_EX);
	}


	/**
	 *
	 * @param type $name
	 * @param type $arguments
	 */
	public function __call($name, $arguments)
	{
		if ( ! $this->enabled) { return; }
		switch(count($arguments))
		{
			case 1:
				$this->write($arguments[0], $name); // e.g. $log->debug('message');
				break;
			default:
				$this->write('', $name);
		}
	}

}
