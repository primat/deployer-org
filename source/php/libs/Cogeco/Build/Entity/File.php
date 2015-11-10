<?php namespace Cogeco\Build\Entity;

use \Cogeco\Build\Entity;
use \Cogeco\Build\Entity\Dir;

/**
 * This entity class represents a File
 */
class File extends Entity implements IDirectoryFile
{
	/** @var Dir $dir The directory object corresponding to this File */
	public $dir;

	/** @var string $name The name of the file */
	public $name = '';


	/**
	 * @param string|Dir $dir
	 * @param string $fileName
	 * @param Host $host
	 */
	public function __construct($dir, $fileName, Host $host = NULL)
	{
		if (is_string($dir)) {
			$this->dir = new Dir($dir, $host);
		}
		else {
			$this->dir = $dir;
            if ($host !== NULL) {
                trigger_error(
                    'Second argument should not be used providing a Dir object as first argument in ' . __METHOD__);
            }
		}
		$this->name = $fileName;
	}

	/**
	 * Get the Dir object of this File
	 * @return Dir|string
	 */
	public function getDir()
	{
		return $this->dir;
	}

	/**
	 * Get the host where this file is located
	 * @return Host
	 */
	public function getHost()
	{
		return $this->dir->getHost();
	}

	/**
	 * Get the full file path
	 * @return string
	 */
	public function getPath()
	{
		return $this->dir->getPath() . $this->name;
	}

	/**
	 * Get the directory separator for this File
	 * @return string
	 */
	public function getSeparator()
	{
		return $this->dir->getSeparator();
	}

	/**
	 * Test if the file located on a remote server
	 * @return string
	 */
	public function isRemote()
	{
		return $this->dir->isRemote();
	}
}
