<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mprice
 * Date: 5/29/14
 * Time: 11:25 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Cogeco\Build\Utils;

class StringUtils
{
	public function replaceWithinDelimiters($start, $end, $new, $source) {
		return preg_replace('#('.preg_quote($start).')(.*)('.preg_quote($end).')#si', '$1'.$new.'$3', $source);
	}
}