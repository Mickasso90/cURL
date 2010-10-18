<?php

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
 *	@Rev.   : 1.5
 *
 *	@Date   : 13th October 2010
 *	@Author : Nicolas Oelgart
 *	@Email  : nico@nicoswd.com
 *	@Web    : www.nicoswd.com
 *
 */


class cURL
{
	
	/**
	 * Holds the cURL handle.
	 *
	**/
	protected $ch        = null;
	
	
	/**
	 * Holds the multi handle.
	 *
	**/
	protected $mh        = null;
	
	
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
	 * Contructor, takes an URL and options as parameters. Example:
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
			throw new cURL_Exception('Could not initiate cURL');
		
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
				throw new cURL_Exception('Error executing cURL handle');
			
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
	 * Creates a copy of the current handle. I think returning $this is probably the best way of doing this...?
	 *
	**/
	public function copy_handle()
	{
		return $this; // curl_copy_handle($this->ch);
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
			throw new cURL_Exception('Invalid callback function supplied');
		
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
			throw new cURL_Exception("Undefined constant/property '{$constant}'");
		
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
			throw new cURL_Exception('Invalid parameters supplied');
		
		$function = strtoupper($function);
		
		if (substr($function, 0, 3) !== 'SET')
			throw new cURL_Exception("Call to undefined method '{$function}'");
		
		$constant = 'CURLOPT_' . substr_replace($function, '', 0, 3);
		
		if (!defined($constant))
			throw new cURL_Exception("Undefined constant '{$constant}'");
		
		if (self::DEBUG)
			echo "DEBUG: {$constant} set to ", (string) print_r($args[0], 1), "\n";
		
		$this->setopt(constant($constant), $args[0]);
		return $this;
	}
	
	
	/**
	 * Initiates a multi handle. Not sure if I like this, but oh well...
	 *
	**/
	public function multi_init()
	{
		$this->mh = curl_multi_init();
	}
	
	
	/**
	 * Adds a new handle to the multi handler. Takes an unlimited amount of parameters, all must be instances of cURL.
	 *
	**/	
	public function multi_add_handle(cURL $curl /* [, cURL $curl_1 [, cURL $... ]] */)
	{
		foreach (func_get_args() AS $num => $curl)
		{
			if (!($curl instanceof cURL))
				throw new cURL_Exception(sprintf('Argument %d must be an instance of cURL', ++$num));
			
			if (($error = @curl_multi_add_handle($this->mh, $curl->ch)) !== CURLM_OK)
				return $error;
		}
		
		return true;
	}
	
	
	/**
	 * Executes all pending handles.
	 *
	**/	
	public function multi_exec(&$running = null)
	{
		return curl_multi_exec($this->mh, $running);
	}
	
	
	/**
	 * Removes a handle from the multi stack.
	 *
	**/
	public function multi_remove_handle(cURL $curl)
	{
		return curl_multi_remove_handle($this->mh, $curl->ch);
	}
	
	
	/**
	 * Get information about the current transfers.
	 *
	**/
	public function multi_info_read(&$queue = null)
	{
		return curl_multi_info_read($this->mh, $queue);
	}
	
	
	/**
	 * Return the content of a cURL handle if CURLOPT_RETURNTRANSFER is set.
	 *
	**/	
	public function multi_getcontent()
	{
		return curl_multi_getcontent($this->ch);
	}
	
	
	/**
	 * Close a set of cURL handles.
	 *
	**/
	public function multi_close()
	{
		return curl_multi_close($this->mh);
	}


	/**
	 * Hmmmm...
	 *
	**/
	public function __clone()
	{		
	}
	
	
	/**
	 * Magic destructor. Closes all handles if eg instance is being unset().
	 *
	**/
	public function __destruct()
	{
		if (is_resource($this->ch))
			$this->close();
		
		if (is_resource($this->mh))
			$this->multi_close();
		
		$this->ch = null;
		$this->mh = null;
	}
	
}

?>