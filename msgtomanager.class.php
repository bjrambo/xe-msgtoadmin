<?php
/* Copyright (C) BJRambo <http://www.bjrambo.com> */
/**
 * @class  msgtomanager
 * @author BJRambo (qw5414@naver.com)
 * @brief high class of the msgtomanager module
 */
class msgtomanager extends ModuleObject
{
	private $triggers = array(

	);
	private $delete_triggers = array(

	);

	function moduleInstall()
	{
		return new Object();
	}

	function checkUpdate()
	{
		return false;
	}

	function moduleUpdate()
	{
		return new Object();
	}

	function recompileCache()
	{
		return new Object();
	}

	function moduleUninstall()
	{
		return new Object();
	}
}
/* End of file msgtomanager.class.php */
/* Location: ./modules/msgtomanager/msgtomanager.class.php */
