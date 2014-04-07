<?php
namespace Clip;

class FFmpeg extends Clip
{
	/**
    *
    */
    public function __construct($binary = null)
    {
        if (empty($binary) OR !$this->binary($binary)) {
            trigger_error("You must set the binary of FFmpeg",E_USER_ERROR);
        }
    }
    /**
    *
    */
    public function __call( $function , $args )
	{
	    if (array_key_exists($function, $this->quick_functions)) {
	        if (!is_callable(array($this,$this->quick_functions[$function]))) {
	            return false;
	        }
	        return call_user_func_array(array(
	            $this,
	            $this->quick_functions[$function]
	        ),$args);
	    }else{
	        trigger_error("Method $function dont exists",E_USER_ERROR);
	    }
	}
	/**
    *
    */
    public function call( $method , $args = array() )
	{
		if( method_exists( $this , $method ) )
		{
			return call_user_func_array( array( $this , $method )  , 
				( is_array( $args ) ) ? $args : array( $args )
			);
		}else{
		    trigger_error("method doesnt exist",E_USER_ERROR);
		}
		return $this;
	}
	/**
	*
	*/
	protected $quick_functions		=	array(
		'b'			=>	'bitrate',
		'r'			=>	'frameRate',
		'fs'		=>	'fileSizeLimit',
		'f'			=>	'forceFormat',
		'force'		=>	'forceFormat',
		'i'			=>	'input',
		's'			=>	'size',
		'ar'		=>	'audioSamplingFrequency',
		'ab'		=>	'audioBitrate',
		'acodec'	=>	'audioCodec',
		'vcodec'	=>	'videoCodec',
		'std'		=>	'redirection',
		'number'	=>	'videoFrames',
		'vframes'	=>	'videoFrames',
		'y'			=>	'overwrite',
		'log'		=>	'loglevel',
	);
	/**
	*
	*/
	private $fixForceFormat = array(
		"ogv"	=>	'ogg',
		"jpeg"	=>	'mjpeg',
		"jpg"	=>	'mjpeg',
		"flash"	=>	"flv",
	);
	/**
	*
	*/
	public function output( $output = null , $forceFormat = null )
	{
		$this->forceFormat( $forceFormat );
		$options = array();
		$this->setEmptyKey( $output);
		return $this;
	}
	/**
	* @param	string	$forceFormat	Force format output
	* @return	object	Return self
	* @access	public
	*/
	public function forceFormat($forceFormat)
	{
		if( !empty( $forceFormat ) )
		{
			$forceFormat = strtolower( $forceFormat );
			if( array_key_exists( $forceFormat , $this->fixForceFormat ) )
			{
				$forceFormat = $this->fixForceFormat[ $forceFormat ];
			}
			$this->set('f',$forceFormat,false);
		}
		return $this;
	}
	/**
	* @param	string	$file	input file path
	* @return	object	Return self
	* @access	public
	* @version	1.2	Fix by @propertunist
	*/
	public function input ($file)
	{
		if (file_exists($file) AND is_file($file)) {
			$this->set('i', '"'.$file.'"', false);
		} else {
			if (strstr($file, '%') !== false) {
				$this->set('i', '"'.$file.'"', false);
			} else {
				trigger_error ("File $file doesn't exist", E_USER_ERROR);
			}
		}
		return $this;
	}
	/**
	* @param	string	$size
	* @param	string	$start
	* @param	string	$videoFrames
	* @return	object	Return self
	* @access	public
	* @version	1.2	Fix by @propertunist
	*/
	public function thumb ($size, $start, $videoFrames = 1)
	{
		//$input = false;
	        if (!is_numeric( $videoFrames ) OR $videoFrames <= 0) {
	        	$videoFrames = 1;
	        }
	        $this->audioDisable ();
	        $this->size ($size);
	        $this->position ($start);
	        $this->videoFrames ($videoFrames);
	        $this->frameRate (1);
	        return $this;
	}
	/**
	* @return	object	Return self
	* @access	public
	*/
	public function clear()
	{
		$this->command = array();
		return $this;
	}
	/**
	* @param	string	$transpose	http://ffmpeg.org/ffmpeg.html#transpose
	* @return	object	Return self
	* @access	public
	*/
	public function transpose( $transpose = 0 )
	{
		if( is_numeric( $transpose )  )
		{
			$this->command['vf']['transpose'] = strval($transpose);
		}
		return $this;
	}
	/**
	* @return	object	Return self
	* @access	public
	*/
	public function vflip()
	{
		$this->command['vf']['vflip'] = null;
		return $this;
	}
	/**
	* @return	object	Return self
	* @access	public
	*/
	public function hflip()
	{
		$this->command['vf']['hflip'] = null;
		return $this;
	}
	/**
	* @return	object	Return self
	* @param	$flip	v OR h
	* @access	public
	*/
	public function flip( $flip )
	{
		if( !empty( $flip ) )
		{
			$flip = strtolower( $flip );
			if( $flip == 'v' )
			{
				return $this->vflip();
			}
			else if( $flip == 'h' )
			{
				$this->hflip();
			}
		}
		return false;
	}
	/**
	* @param	string	$aspect	sample aspect ratio
	* @return	object	Return self
	* @access	public
	*/
	public function aspect( $aspect )
	{
		$this->set('aspect',$aspect,false);
	}
	/**
	* @param	string	$b	set bitrate (in bits/s)
	* @return	object	Return self
	* @access	public
	*/
	public function bitrate( $b )
	{
		return $this->set('b',$b,false);
	}
	/**
	* @param	string	$r	Set frame rate (Hz value, fraction or abbreviation).
	* @return	object	Return self
	* @access	public
	*/
	public function frameRate( $r )
	{
		if( !empty( $r ) AND preg_match( '/^([0-9]+\/[0-9]+)$/' , $r ) XOR is_numeric( $r ) )
		{
			$this->set('r',$r,false);
		}
		return $this;
	}
	/**
	* @param	string	$s	Set frame size.
	* @return	object	Return self
	* @access	public
	*/
	public function size( $s )
	{
		if( !empty( $s ) AND preg_match( '/^([0-9]+x[0-9]+)$/' , $s ) )
		{
			$this->set('s',$s,false);
		}
		return $this;
	}
	/**
	* When used as an input option (before "input"), seeks in this input file to position. When used as an output option (before an output filename), decodes but discards input until the timestamps reach position. This is slower, but more accurate.
	*
	* @param	string	$s	position may be either in seconds or in hh:mm:ss[.xxx] form.
	* @return	object	Return self
	* @access	public
	*/
	public function position( $ss )
	{
		return $this->set('ss',$ss,false);
	}
	/**
	* @param	string	$t	Stop writing the output after its duration reaches duration. duration may be a number in seconds, or in hh:mm:ss[.xxx] form.
	* @return	object	Return self
	* @access	public
	*/
	public function duration( $t )
	{
		return $this->set('t',$t,false);
	}
	/**
	* Set the input time offset in seconds. [-]hh:mm:ss[.xxx] syntax is also supported. The offset is added to the timestamps of the input files.
	*
	* @param	string	$t	Specifying a positive offset means that the corresponding streams are delayed by offset seconds.
	* @return	object	Return self
	* @access	public
	*/
	public function itsoffset( $itsoffset )
	{
		return $this->set('itsoffset',$itsoffset,false);
	}
	/**
	*	
	*/
	public function audioSamplingFrequency( $ar )
	{
		return $this->set('ar',$ar,false);
	}
	/**
	*	
	*/
	public function audioBitrate( $ab )
	{
		return $this->set('ab', $ab , false );
	}
	/**
	*	
	*/
	public function audioCodec( $acodec = 'copy' )
	{
		return $this->set('acodec',$acodec,false);
	}
	/**
	*	
	*/
	public function audioChannels( $ac )
	{
		$this->set('ac',$ac,false);
	}
	/**
	*	
	*/
	public function audioQuality( $aq )
	{
		return $this->set('aq', $a , false );
	}
	/**
	*	
	*/
	public function audioDisable()
	{
		return $this->set('an',null,false);
	}
	/**
	* @param	string	$number
	* @return	object	Return self
	* @access	public
	*/
	public function videoFrames( $number )
	{
		return $this->set( 'vframes' , $number );
	}
	/**
	*	@param string	$vcodec
	*	@return object Self
	*/
	public function videoCodec( $vcodec = 'copy' )
	{
		return $this->set('vcodec' , $vcodec );
	}
	/**
	*	@return object Self
	*/
	public function videoDisable()
	{
		return $this->set('vn',null,false);
	}
	/**
	*	@return object Self
	*/
	public function overwrite()
	{
		return $this->set('y',null,false);
	}
	/**
	*	@param string	$fs
	*	@return object Self
	*/
	public function fileSizeLimit( $fs )
	{
		return $this->set('fs' , $fs , false );
	}
	/**
	*	@param string	$progress
	*	@return object Self
	*/
	public function progress( $progress )
	{
		return $this->set('progress',$progress);
	}
	/**
	*	@param integer	$pass
	*	@return object Self
	*/
	public function pass( $pass )
	{
		if( is_numeric( $pass ) )
		{
			$pass = intval( $pass );
			if( $pass == 1 OR $pass == 2 )
			{
				$this->command['pass'] = $pass;
			}
		}
		return $this;
	}
	/**
	*	@return object Self
	*	@access	public
	*/
	public function grayScale( )
	{
		return $this->set('pix_fmt','gray');
	}
	/**
	* @param	string	$level
	* @return	object	Return self
	* @access	public
	*/
	public function loglevel( $level = "verbose" )
	{
		$level = strtolower( $level );
		if( in_array( $level , array("quiet","panic","fatal","error","warning","info","verbose","debug") ) )
		{
			return $this->set('loglevel',$level );
		}else{
			trigger_error(  "The option does not valid in loglevel" );
		}
	}
}