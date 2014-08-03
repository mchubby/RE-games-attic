<?php

// include file.

// General, string/file manipulation


function break_filename($filename)
{
	if ( ($pos = strrpos($filename, '.')) !== FALSE )
	{
		return array(substr($filename, 0, $pos), substr($filename, $pos + 1));
	}
	return array($filename, '');
}

function is_absolute_directory($path)
{
	if ( DIRECTORY_SEPARATOR == '/' )
	{
		return substr($path, 0, 1) === DIRECTORY_SEPARATOR;
	}
	return substr($path, 0, 2) == '\\\\' OR substr($path, 1, 1) == ':';
}

function expand_argspec($argspec_arr, $base_dir = '')
{
	$res = array();
	foreach ( $argspec_arr as $argspec )
	{
		if ( strlen($base_dir) > 0 && !is_absolute_directory($argspec) )
		{
			$argspec = $base_dir. DIRECTORY_SEPARATOR. $argspec;
		}
		foreach ( glob($argspec) as $val )
		{
			$res[] = $val;
		}
	}
	return $res;
}

function usage($syntax)
{
	fprintf(STDERR, "Usage: %s\r\n", $syntax);
}


function array_get_inverse($array)
{
	$res = array();
	foreach ( $array as $k => $val )
	{
		if ( !isset($res[$val]) )
		{
			$res[$val] = array();
		}
		$res[$val][] = $k;
	}
	return $res;
}



function in_array_ci($needle, &$array, &$match)
{
	foreach ( $array as $key => &$value )
	{
		if ( strcasecmp($needle, $value) == 0 )
		{
			$match = $key;
			return TRUE;
		}
	}
	$match = NULL;
	return FALSE;
}


/**
* PHP_Beautifier_Common and PHP_Beautifier_Interface
*
* PHP version 5
*
* LICENSE: This source file is subject to version 3.0 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_0.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
* @category   PHP
* @package PHP_Beautifier
* @author Claudio Bustos <cdx@users.sourceforge.com>
* @copyright  2004-2006 Claudio Bustos
* @link     http://pear.php.net/package/PHP_Beautifier
* @link     http://beautifyphp.sourceforge.net
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    CVS: $Id:$
*/

class PHP_Beautifier_Common {
    /**
    * Normalize reference to directories
    * @param  string path to directory
    * @param  directory separator to enforce
    * @return string normalized path to directory
    */
    public static function normalizeDir($sDir, $sep = DIRECTORY_SEPARATOR)
    {
        $sDir = PHP_Beautifier_Common::normalizePath($sDir, $sep);
        if (substr($sDir, -1) != $sep) {
            $sDir .= $sep;
        }
        return $sDir;
    }
    public static function normalizePath($sPath, $sep = DIRECTORY_SEPARATOR)
    {
        $sPath = str_replace('/', $sep, $sPath);
        $sPath = str_replace('\\', $sep, $sPath);
        return $sPath;
    }
}

function strip_trailing_0($str)
{
	$pos = strpos($str, chr(0));
	return ($pos !== FALSE) ? substr($str, 0, $pos) : $str;
}

function pad_str_0($str, $padlen)
{
	if ( strlen($str) < $padlen )
	{
		$str .= str_repeat(chr(0), $padlen - strlen($str));
	}
	return $str;
}


function as_i32le($long)
{
	$last = ord(substr($long, 3, 1));
	if ( ($last & 0x80) == 0 )
	{
		return current(unpack("V", $long));
	}

	// complement
	$number = ~$last & 0xFF;
	for ( $i = 2; $i >= 0; --$i )
	{
		$number <<= 8;
		$number |= (~ord(substr($long, $i, 1))) & 0xff;
	}
	return -$number - 1;
}

function to_i32le($phpnum)
{
	if ( $phpnum >= 0 )
	{
		return pack('V', $phpnum);
	}

	$result = '';
	for ( $i = 0; $i < 4; ++$i )
	{
		$result .= chr($phpnum & 0xFF);
		$phpnum >>= 8;
	}
	return $result;
}


function read_0_wstr($blob, $start)
{
	if ( $start + 2 > strlen($blob) )
	{
		return FALSE;
	}
	$result = '';
	$offset = $start;
	$cmp = chr(0). chr(0);
	while ( $offset + 2 <= strlen($blob)
		&& $cmp != ($char01 = substr($blob, $offset, 2)) )
	{
		$result .= $char01;
		$offset += 2;
	}
	return $result;
}

function read_0_str($blob, $start)
{
	if ( $start + 1 > strlen($blob) )
	{
		return FALSE;
	}
	$result = '';
	$offset = $start;
	$cmp = chr(0);
	while ( $offset + 1 <= strlen($blob)
		&& $cmp != ($char01 = substr($blob, $offset, 1)) )
	{
		$result .= $char01;
		$offset += 1;
	}
	return $result;
}

