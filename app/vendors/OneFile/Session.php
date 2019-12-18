<?php namespace OneFile;

/**
 * OneFile/Session Class
 *
 * @author C. Moller <xavier.tnc@gmail.com> - 30 May 2014
 *
 * Licensed under the MIT license. Please see LICENSE for more information.
 *
 * @update C. Moller - 9 June 2014: Added session scope support
 *
 */
class Session
{

	protected $scope;


	public function id($id = null)
	{
		if ($id)
		{
			return session_id($id);
		}

		return session_id();
	}


	public function started()
	{
		return ! (
      php_sapi_name() == 'cli'
      or (defined('PHP_SESSION_ACTIVE') and session_status() !== PHP_SESSION_ACTIVE)
      or ( ! session_id())
    );
	}


	public function start($scope = null, $id = null)
	{
		$this->scope = $scope;

		$started = $this->started();

		if ($id)
		{
			if ($started)
			{
				session_write_close();
				$started = false;
			}

			$this->id($id);
		}

		if ( ! $started)
		{
			// Throws an error if we try to re-start a started session!
			session_start();
		}

		//NB: You can't and shouldn't use session->has($scope) or session->put($scope)
		//    to detect or set the $scope array!
    if ($scope and empty($_SESSION[$scope]))
    {
      $_SESSION[$scope] = array();
    }

	}


  public function getScope()
  {
    return $this->scope;
  }


  public function changeScope($newScope)
  {
    $this->scope = $newScope;
  }


	public function has($key)
	{
		return $this->scope ? isset($_SESSION[$this->scope][$key]) : isset($_SESSION[$key]);
	}


	/**
	 * This method adds the convenience of not having to check if a key exists before retrieving
	 * and also returns a default value if not set!
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key = null, $default = null)
	{
		if (is_null($key))
		{
			return $this->scope ? $_SESSION[$this->scope] : $_SESSION;
		}

		if ($this->has($key))
		{
			return $this->scope ? $_SESSION[$this->scope][$key] : $_SESSION[$key];
		}
		else
		{
			return $default;
		}
	}


	public function all()
	{
		return $this->get();
	}


	public function put($key, $value)
	{
		if ($this->scope)
		{
			$_SESSION[$this->scope][$key] = $value;
		}
		else
		{
			$_SESSION[$key] = $value;
		}
	}


	public function flash($key, $value)
	{
		if ($this->scope)
		{
			$_SESSION[$this->scope]['__FLASH__'][$key] = $value;
		}
		else
		{
			$_SESSION['__FLASH__'][$key] = $value;
		}
	}


	public function forget($key)
	{
		if ($this->scope)
		{
			unset($_SESSION[$this->scope][$key]);
		}
		else
		{
			unset($_SESSION[$key]);
		}
	}


	public function destroy()
	{
		session_destroy();
	}


	public function clear($destory_current = false)
	{
		if ($destory_current)
		{
			$this->destroy();
			$this->start($this->scope);
		}
		else
		{
			if ($this->scope)
			{
				//NB: Don't use session->put() to set the $scope array!
				$_SESSION[$this->scope] = array();
			}
			else
			{
				$_SESSION = array();
			}
		}
	}


	public function changeId($delete_old_session = false)
	{
		session_regenerate_id($delete_old_session);
	}


	public function replace(array $new_session_array)
	{
		if ($this->scope)
		{
			//NB: Don't use session->put() to set the $scope array!
			$_SESSION[$this->scope] = $new_session_array;
		}
		else
		{
			$_SESSION = $new_session_array;
		}
	}

}
