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
 *	@Rev.   : 1.3
 *
 *	@Date   : 13th October 2010
 *	@Author : Nicolas Oelgart
 *	@Email  : nico@nicoswd.com
 *	@Web    : www.nicoswd.com
 *
 */


class cURL
{
	
	protected $ch        = null;
	
	protected $mh        = null;
	
	protected $callback  = null;
	
	const DEBUG          = true;
	
	
	
	public function __construct($url = null, array $options = null)
	{
		if (!$this->ch = @curl_init($url))
			throw new cURL_Exception('Could not initiate cURL');
		
		if ($options)
			$this->setopt_array($options);
	}
	
	
	public static function init($url, array $options = array())
	{
		return new self($url, $options);
	}
	
	
	public function setopt($option, $value)
	{
		return curl_setopt($this->ch, $option, $value);
	}
	
	
	public function setopt_array(array $options)
	{
		return curl_setopt_array($this->ch, $options);
	}
	
	
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
			$this->callback = null;
			
			return ($return ? $response : $this);
		}
		
		if ($return)
			$this->setReturnTransfer(true);

		return curl_exec($this->ch);
	}
	
	
	public function close()
	{
		return curl_close($this->ch);
	}
	
	
	public function getinfo($option = 0)
	{
		return curl_getinfo($this->ch, $option);
	}
	
	
	public function error()
	{
		return curl_error($this->ch);
	}
	
	
	public function errno()
	{
		return curl_errno($this->ch);
	}
	
	
	public function copy_handle()
	{
		return curl_copy_handle($this->ch);
	}
	
	
	public function version($age = CURLVERSION_NOW)
	{
		return curl_version($this->ch, $age);
	}
	
	
	public function setCallback($function)
	{
		if (!is_callable($function))
			throw new cURL_Exception('Invalid callback function supplied');
		
		$this->callback = $function;
		return $this;
	}
	
	
	public function removeCallback()
	{
		$this->callback = null;
	}
	
	
	public function __get($constant)
	{
		$constant = 'CURLOPT_' . strtoupper($constant);
		
		if (!defined($constant))
			throw new cURL_Exception("Undefined constant/property '{$constant}'");
		
		return constant($constant);
	}
	

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
			echo "DEBUG: {$constant} set to " . (string) print_r($args[0], 1) . "\n";		
		
		$this->setopt(constant($constant), $args[0]);
		return $this;
	}
	

	public function multi_init()
	{
		$this->mh = curl_multi_init();
	}
	
	
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
	
	
	public function multi_exec(&$running = null)
	{
		return curl_multi_exec($this->mh, $running);
	}
	
	
	public function multi_remove_handle(cURL $curl)
	{
		return curl_multi_remove_handle($this->mh, $curl->ch);
	}
	
	
	public function multi_info_read(&$queue = null)
	{
		return curl_multi_info_read($this->mh, $queue);
	}
	
	
	public function multi_getcontent()
	{
		return curl_multi_getcontent($this->ch);
	}
	
	
	public function multi_close()
	{
		return curl_multi_close($this->mh);
	}
	
	
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