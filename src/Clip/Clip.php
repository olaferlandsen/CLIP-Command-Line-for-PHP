<?php
namespace Clip;
/**
* Clip - Command line interpreter for PHP 5!
* 
* @package		Clip
* @version		1.0 2014-04-07  Lautaro
* @license		http://opensource.org/licenses/gpl-license.php  GNU Public License
* @author		Olaf Erlandsen <info@webdevfreelance.com>
*/
class Clip
{
    /**
	*	
	*/
	protected   $arg_separator  =   ",";
	/**
	*	
	*/
	protected   $arg_equal      =   "=";
	/**
	*	
	*/
	protected   $arg_init       =   "-";
	/**
	*	
	*/
	protected   $arg_large      =   "--";
	/**
	*	
	*/
	protected   $function       =   "exec";
	/**
	*	
	*/
	protected   $redirection;
	/**
	*	
	*/
	protected   $binary;
	/**
	*	
	*/
	protected   $command        =   array();
	/**
	*	
	*/
	protected   $output         =   null;
	/**
	*	
	*/
	protected   $result         =   false;
	/**
	*	
	*/
	protected   $status         =   false;
	/**
	*	
	*/
	protected   $quick_functions=   array();
	/**
	*	
	*/
	protected   $quick_options  =   array();
	/**
	*	
	*/
	const       PLUGINS_PATH        = "plugins/";
	/**
	*	
	*/
	const       PLUGINS_NAME_FORMAT = "plugin.%s.php";
	/**
	*	
	*/
	public function __construct ($binary = null)
	{
	    $this->binary($binary);
	}
	/**
	*
	*/
	public function redirection ($redirection = "2<&1")
	{
		if (!empty($redirection)) {
			$this->redirection = $redirection;
			return true;
		}
		return false;
	}
	/**
	*
	*/
	public function execute( $callback = null )
	{
	    $command = $this->getCommand();
	    if (empty($this->function)) {
	        trigger_error("Empty function", E_USER_ERROR);
        } elseif (is_array($this->function) and !is_callable($this->function)) {
            trigger_error("Function dont callable", E_USER_ERROR);
        } elseif (!function_exists($this->function)) {
            trigger_error("Function dont exists", E_USER_ERROR);
        }
        
		if($this->function=="exec"){
	        $this->result = exec($command, $this->output, $this->status);
	    } elseif($this->function=="shell_exec") {
	        $this->output = shell_exec ($command);
	    } elseif($this->function=="passthru") {
            $this->output = passthru ($command, $this->status);
	    } elseif($this->function=="system") {
            $this->output =  system ($command, $this->status);
	    } else {
	        $this->output = call_user_func(
	            $this->function,
	            $command,
	            $this->result
	        );
	    }
	    // callback
	    if (!empty($callback)) {
	        if (is_callable($callback)) {
	            return call_user_func($callback,$this->getResult());
	        }
	    }
	    return true;
	}
	/**
	*
	*/
	public function binary( $binary )
	{
		if (!empty($binary)) {
			$this->binary = $binary;
			return true;
		} else {
		    trigger_error("Empty binary",E_USER_ERROR);
		}
		return false;
	}
	/**
	*
	*/
	public function set ($key, $value = null, $append = false )
	{
	    // replace "-" from key
		$key = preg_replace('/^\-+/', '', $key);
		// validate key
		if (!empty($key)) {
		    // check if exists key in quick_options and use if exists
			if (array_key_exists($key, $this->quick_options)) {
				$key = $this->quick_options[$key];
			}
			// check append
			if($append === false) {
			    $this->command[$key] = $value;
			} else {
			    
				if (!array_key_exists($key, $this->command)) {
					$this->command[$key] = array($value);
				} elseif (!is_array($this->command[$key])) {
					$this->command[$key] = array(
					    $this->command[$key],
					    $value
					);
				} else {
				    if (is_array($value)) {
				        foreach ($value AS $k => $v) {
				            $this->command[$key][$k] = $v;
				        }
				    }else{
				        $this->command[$key][] = $value;
				    }
				}
			}
			return true;
		}
		return false;
	}
	/**
	*
	*/
	public function setEmptyKey( $value )
	{
	    $this->command[] = $value;
	}
	/**
	*
	*/
	public function _unset( $key )
	{
		if (array_key_exists($key, $this->command)) {
			unset( $this->command[ $key ] ) ;
		}
		return $this;
	}
	/**
	*
	*/
	public function getCommand ()
	{
	    $command_to_string = array();
		foreach ($this->command AS $option => $values) {
			if(is_array($values)) {
				$items = array();
				foreach ($values AS $item => $val ){
					if (!is_null($val)) {
						if (is_array($val)) {
							$val = join($this->arg_separator, $val);
						}
						$val = strval($val);
						if (!is_string($item)) {
							$items[] = $val;
						} else {
							$items[] = $item.$this->arg_equal. $val;
						}
					} else {
						$items[] = $item;
					}
				}
				if(!is_integer($option)){
			        if( strlen($option) > 1 ){
			            $option = $this->arg_large . $option. " " ;
			        }else{
			            $option = $this->arg_small.$option." ";
			        }
			    }else{
			        $option = null;
			    }
			    $command_to_string[] = $option. join($this->arg_separator, $items);
			} else {
			    if(!is_integer($option)){
			        if( strlen($option) > 1 ){
			            $option = $this->arg_large . $option." ";
			        }else{
			            $option = $this->arg_small.$option." ";
			        }
			    }else{
			        $option = null;
			    }
			    $command_to_string[] = $option.strval($values);
			}
		}
		
		$command = $this->binary. " ";
		$command.= join(" ",$command_to_string);
		if (!empty($this->redirection)) {
		    $command.= " ".(string)$this->redirection;
	    }
	    return $command;
	}
	/**
	*
	*
	*/
	public static function plugin ($name)
	{
	    // only need the name
	    $name = preg_replace("/^(Clip\x5c\x5c)/i", "", $name);
	    // define plugin real name
	    $classname = "Clip\\".$name;
	    // define plugin file
	    $file = \Clip\Clip::PLUGINS_PATH;
	    $file.= sprintf(\Clip\Clip::PLUGINS_NAME_FORMAT, strtolower($name));
	    
	    // check class
	    if (!class_exists($classname)) {
	        if (!file_exists($file) XOR !is_file($file)) {
	            trigger_error("Plugin file dont exists", E_USER_ERROR);
	        }
	        if (!include_once($file)) {
	            trigger_error("Plugin file error [$file]", E_USER_ERROR);
	        }
	    }
	    // recheck class
	    if (!class_exists($classname)) {
	        trigger_error("Plugin $classname dont exists", E_USER_ERROR);
	    }
	    // is subclass
	    elseif(!is_subclass_of($classname, __NAMESPACE__."\\Clip")) {
	        trigger_error("Plugin $classname dont is subclass of Clip",E_USER_ERROR);
	    }
	    // get params
	    $params = array_slice(func_get_args(),1);
	    
	    // instace with ReflectionClass; see more in
	    // http://us2.php.net/manual/en/class.reflectionclass.php
	    return call_user_func_array(
	        array(new \ReflectionClass($classname), 'newInstance'),
	        $params
        );
	}
	/**
	*
	*/
	public function getResult()
	{
	    if (!is_array($this->output)) {
	        $this->output = preg_split("/\n+/",$this->output);
	    }
	    return (object)array(
	        "output"  =>  $this->output,
	        "result"  =>  $this->result,
	        "status"  =>  (boolean)$this->status,
	        "command" =>  $this->getCommand(),
	    );
	}
	/**
	*
	*/
	public function setFunction ($function)
	{
	    $this->function = $function;
	}
}