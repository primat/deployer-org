<?php
if ( ! function_exists('q'))
{
	/**
	 * @param $mixedvars
	 */
	function q($mixedvars)
	{
		echo '<div style="text-align:left;"><pre>';
		if (is_array($mixedvars) || is_object($mixedvars)) {
			print_r($mixedvars);
		}
		else {
			echo (string)$mixedvars;
		}
		echo '</pre></div>';
	}
}

if ( ! function_exists('a'))
{
	/**
	 * @param $mixedvars
	 */
	function x($mixedvars)
	{
		x($mixedvars);
		exit;
	}
}


if ( ! function_exists('t'))
{
	/**
	 * @param $mixedvars
	 */
	function t($mixedvars)
	{
		echo '<textarea>';
		if (is_array($mixedvars) || is_object($mixedvars)) {
			print_r($mixedvars);
		} else if (is_string($mixedvars) && strpos($mixedvars, '<?xml') === 0) {
			try {
				$simpleXMLElement = new SimpleXMLElement($mixedvars);
				$dom = dom_import_simplexml($simpleXMLElement)->ownerDocument;
				$dom->formatOutput = true;
				echo $dom->saveXML();
			}
			catch (Exception $e) {
				echo $mixedvars;
			}
		} else if($mixedvars instanceof SimpleXMLElement) {
			$dom = dom_import_simplexml($mixedvars)->ownerDocument;
			$dom->formatOutput = true;
			echo $dom->saveXML();
		} else {
			echo (string)$mixedvars;
		}
		echo '</textarea>';
	}
}

if ( ! function_exists('b'))
{
	/**
	 * @param $mixedvars
	 * @return string
	 */
	function b($mixedvars)
	{
		ob_start();
		print_r($mixedvars);
		$result = '<pre>' . ob_get_contents() . '</pre>';
		ob_end_clean();
		return $result;
	}
}
