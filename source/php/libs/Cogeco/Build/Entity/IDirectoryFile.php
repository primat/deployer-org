<?php

namespace Cogeco\Build\Entity;


interface IDirectoryFile {

	public function getHost();
	public function getPath();
	public function getSeparator();
	public function isRemote();
}