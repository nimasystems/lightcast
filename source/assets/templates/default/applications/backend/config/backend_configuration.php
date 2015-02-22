<?php

class BackendConfiguration extends lcWebManagementConfiguration
{
	public function getApplicationName()
	{
		return 'backend';
	}

	public function getProjectName()
	{
		throw new lcConfigException('Set a proper project name');
	}
}

?>