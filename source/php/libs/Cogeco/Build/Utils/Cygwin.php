<?php namespace Cogeco\Build\Utils;

/**
 * A class for Cygwin related functionality
 */
class Cygwin
{
	// Convert a Windows path to a cygwin path
	public static function cygPath($path)
	{
		// Only affect Windows paths
		if (preg_match('/^([A-Za-z]):/', $path, $matches) === 1) {
			$driveLetter = strtolower($matches[1]);
			$path = mb_substr(str_replace('\\', '/', $path), 2);
			$path = '/cygdrive/' . $driveLetter . $path;
		}
		return $path;
	}
}
