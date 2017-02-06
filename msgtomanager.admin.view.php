<?php
/* Copyright (C) BJRambo <http://www.bjrambo.com> */
/**
 * @class  msgtomanagerAdminView
 * @author BJRambo (qw5414@naver.com)
 * @brief admin view class for msgtomanager modules
 */
class msgtomanagerAdminView extends msgtomanager
{
	function init()
	{
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispMsgtomanagerAdmin', '', $this->act)));
	}

	function dispMsgtomanagerAdminLogList()
	{

	}

	function dispMsgtomanagerAdminConfig()
	{

	}

	function dispMsgtomanagerAdminLogView()
	{

	}
}
/* End of file msgtomanager.admin.view.php */
/* Location: ./modules/msgtomanager/msgtomanager.admin.view.php */
