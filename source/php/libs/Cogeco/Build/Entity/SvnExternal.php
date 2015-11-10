<?php namespace Cogeco\Build\Entity;

use \Cogeco\Build\Exception;

/**
 *
 */
class SvnExternal
{
	public $basePath;
	public $relPath;
	public $url;
	public $revision;

	/**
	 * Constructor
	 * @param $basePath
	 * @param $relPath
	 * @param $url
	 * @param string $revision
	 */
	public function __construct($basePath, $relPath, $url, $revision = '')
	{
		$this->basePath = $basePath;
		$this->relPath = $relPath;
		$this->url = $url;
		$this->revision = $revision;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->basePath . '/' . $this->relPath;
	}
}
