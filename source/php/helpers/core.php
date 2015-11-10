<?php
if ( ! function_exists('getArrayValue'))
{
	/**
	 * @param $key
	 * @param array $array
	 * @param null $default
	 * @return null
	 */
	function getArrayValue($key, array $array, $default = NULL)
	{
		if (isset($array[$key])) {
			return $array[$key];
		}
		return $default;
	}
}
