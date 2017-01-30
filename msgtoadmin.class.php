<?php
/* Copyright (C) BJRambo <http://www.bjrambo.com> */
/**
 * @class  msgtoadmin
 * @author BJRambo (qw5414@naver.com)
 * @brief high class of the msgtoadmin module
 */
class msgtoadmin extends ModuleObject
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
		return new Object();
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
/* End of file msgtoadmin.class.php */
/* Location: ./modules/msgtoadmin/msgtoadmin.class.php */
