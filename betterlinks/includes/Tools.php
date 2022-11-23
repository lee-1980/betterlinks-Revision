<?php
namespace BetterLinks;

class Tools
{
	public function __construct()
	{
		$this->init_import();
		$this->init_export();
	}

	public function init_export()
	{
		new Tools\Export();
	}

	public function init_import()
	{
		new Tools\Import();
	}
}
