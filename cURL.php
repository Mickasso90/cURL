<?php

namespace nicoswd\http\curl;

/**
 *    
 *      _/_/_/_/   _/   _/_/_/_/   _/_/_/_/_/   _/_/_/_/   _/  _/  _/           _/        _/_/_/_/   _/_/_/_/   _/_/_/_/_/
 *     _/    _/   _/   _/         _/      _/   _/         _/  _/  _/           _/        _/         _/    _/   _/  _/  _/
 *    _/    _/   _/   _/         _/      _/   _/_/_/_/   _/  _/  _/   _/_/_/_/_/        _/         _/    _/   _/  _/  _/
 *   _/    _/   _/   _/         _/      _/         _/   _/  _/  _/   _/      _/        _/         _/    _/   _/  _/  _/
 *  _/    _/   _/   _/_/_/_/   _/_/_/_/_/   _/_/_/_/   _/_/_/_/_/   _/_/_/_/_/   _/   _/_/_/_/   _/_/_/_/   _/  _/  _/
 *       _/                                                                                                        _/
 *      _/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *
 *	@Rev.   : 2.0
 *
 *	@Date   : 2nd March 2011
 *	@Author : Nicolas Oelgart
 *	@Email  : nico@nicoswd.com
 *	@Web    : www.nicoswd.com
 *
 */

use nicoswd\http\curl\Exceptions\cURLException;


class cURL
{
	
	/**
	 * Holds the cURL handle.
	 *
	**/
	protected $ch        = null;
	
	
	/**
	 * Holds a callback function to be executed on cURL::exec()
	 *
	**/
	protected $callback  = null;
	
	
	/**
	 * Outputs some debugging info on runtime. Only for testing purposes.
	 *
	**/
	const DEBUG          = false;
	
	
	
	/**
	 * Constructor, takes an URL and options as parameters. Example:
	 *
	 *	$ch = new cURL('http://.../', array(
	 *		CURLOPT_RETURNTRANSFER => true,
	 *		CURLOPT_FOLLOWLOCATION => true,
	 *		CURLOPT_HEADER         => false
	 *	));
	 *
	**/
	public function __construct($url = null, array $options = null)
	{
		if (!$this->ch = @curl_init($url))
			throw new cURLException('Could not initiate cURL');
		
		if ($options)
			$this->setopt_array($options);
	}
	
	
	/**
	 * Static constructor. Can be used like this:
	 *
	 *	$ch = cURL::init('http://.../')
	 *		-> setReturnTransfer(true)
	 *		-> setFollowLocation(true)
	 *		-> setHeader(false)
	 *		-> .... ;
	 *
	**/
	public static function init($url, array $options = array())
	{
		return new self($url, $options);
	}
	
	
	/**
	 * Sets a cURL option to the handle.
	 *
	 * Usage examples:
	 *
	 *	$ch->setopt(CURLOPT_URL, 'http://.../');
	 *	
	 * Or:
	 *	
	 *	$ch->setopt($ch->url, 'http://.../');
	**/
	public function setopt($option, $value)
	{
		return curl_setopt($this->ch, $option, $value);
	}
	
	
	/**
	 * Sets an array of options to the handle.
	 *
	 * Usage examples:
	 *
	 *	$ch->setopt_array(array(
	 *		CURLOPT_URL    => 'http://.../',
	 *		CURLOPT_HEADER => false
	 *	));
	**/
	public function setopt_array(array $options)
	{
		return curl_setopt_array($this->ch, $options);
	}
	
	
	/**
	 * Executes handle. First parameter does the same thing as CURLOPT_RETURNTRANSFER.
	 * Set to true to return the data, of false to output it directly.
	 *
	 * Second parameter is an optional callback function. Example:
	 *
	 *	$ch->exec(false, function($response, $curlObj)
	 *	{
	 *		// $response - will automatically be the response from the cURL::exec() call.
	 *		// $curlObj  - will automatically be the instance of the cURL object (eg: $ch).
	 *
	 *		if (preg_match('~(.....)~', $response, $match))
	 *		{
	 *			$curlObj->setURL($match[1]);
	 *		}
	 *	});
	 *
	**/
	public function exec($return = false, $callback = null)
	{
		if ($callback)
			$this->setCallback($callback);
		
		if ($this->callback !== null)
		{
			$this->setReturnTransfer(true);
			
			if (($response = @curl_exec($this->ch)) === false)
				throw new cURLException('Error executing cURL handle');
			
			$response = call_user_func($this->callback, $response, $this);
			$this->removeCallback();
			
			return ($return ? $response : $this);
		}
		
		if ($return)
			$this->setReturnTransfer(true);

		return curl_exec($this->ch);
	}
	
	
	/**
	 * Closes the current cURL handle.
	 *
	**/
	public function close()
	{
		return curl_close($this->ch);
	}
	
	
	/**
	 * Returns info of the current handle. For all available options, check www.php.net/curl_getinfo
	 *
	 * Usage example:
	 *
	 *	echo $ch->getinfo(CURLINFO_HTTP_CODE);
	**/
	public function getinfo($option = 0)
	{
		return curl_getinfo($this->ch, $option);
	}
	
	
	/**
	 * Returns the error message or '' (the empty string) if no error occurred.
	 *
	**/	
	public function error()
	{
		return curl_error($this->ch);
	}
	
	
	/**
	 * Returns the error number or 0 (zero) if no error occurred.
	 *
	**/
	public function errno()
	{
		return curl_errno($this->ch);
	}
	
	
	/**
	 * Returns the version of the cURL lib and components.
	 *
	**/	
	public function version($age = CURLVERSION_NOW)
	{
		return curl_version($this->ch, $age);
	}
	

	/**
	 * Sets a callback function.
	 *
	 * Examples:
	 *
	 *	$ch->setCallback(function($response, $curlObj)
	 *	{
	 *		echo strtoupper($response);
	 *	});
	 *
	 *	$ch->setCallback('name_of_callback_function');
	 *
	**/
	public function setCallback($function)
	{
		if (!is_callable($function))
			throw new cURLException('Invalid callback function supplied');
		
		$this->callback = $function;
		return $this;
	}
	
	
	/**
	 * Removes callback function.
	 *
	**/
	public function removeCallback()
	{
		$this->callback = null;
	}
	
	
	/**
	 * Just for fanciness. Allows you to do this:
	 *
	 *	$ch->setopt($ch->followlocation, true);
	 *	$ch->setopt($ch->returntransfer, true);
	 *
	 * It's however encouraged to use $ch->setFollowLocation(true) ... 
	 *
	**/	
	public function __get($constant)
	{
		$constant = 'CURLOPT_' . strtoupper($constant);
		
		if (!defined($constant))
			throw new cURLException("Undefined constant/property '{$constant}'");
		
		return constant($constant);
	}
	
	
	/**
	 * More fanciness. Allows what I suggested above. For example:
	 *
	 *	$ch->setReturntransfer(true);
	 *	$ch->setFollowLocation(false);
	 *	$ch->setURL('http://.../');
	 *	...
	 *
	 * Or even fancier:
	 *
	 *	$ch
	 *		-> setReturntransfer(true)
	 *		-> setFollowLocation(false)
	 *		-> setURL('http://.../')
	 *		-> exec();
	 *
	**/
	public function __call($function, array $args)
	{
		if (!array_key_exists(0, $args))
			throw new cURLException('Invalid parameters supplied');
		
		$function = strtoupper($function);
		
		if (substr($function, 0, 3) !== 'SET')
			throw new cURLException("Call to undefined method '{$function}'");
		
		$constant = 'CURLOPT_' . substr($function, 3);
		
		if (!defined($constant))
			throw new cURLException("Undefined constant '{$constant}'");
		
		if (static::DEBUG)
			echo "DEBUG: {$constant} set to ", (string) print_r($args[0], 1), "\n";
		
		$this->setopt(constant($constant), $args[0]);
		return $this;
	}
	

	/**
	 * Creates a copy of the current handle.
	 *
	**/
	public function copy_handle()
	{
		$instance = new self();
		$instance->ch = $this->ch;
		
		return $instance;		
	}
	
	
	/**
	 * Magic destructor. Closes all handles if eg instance is being unset().
	 *
	**/
	public function __destruct()
	{
		if (is_resource($this->ch))
			$this->close();
		
		$this->ch = null;
	}
	
}

?>