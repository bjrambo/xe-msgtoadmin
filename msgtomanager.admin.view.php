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
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->list_count = 30;
		$args->page_count = 10;
		$args->log_srl = Context::get('log_srl');
		/*
		 * HACK : not support search function in now
		$args->search_target = Context::get('search_targer');
		$args->search_keyword = Context::get('search_keyword');
		*/

		$output = executeQueryArray('msgtomanager.getMessageLog', $args);
		debugPrint($output);

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('message_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
	}

	function dispMsgtomanagerAdminConfig()
	{

	}

	function dispMsgtomanagerAdminLogView()
	{
		$args = new stdClass();
		$args->log_srl = Context::get('log_srl');

		$output = executeQuery('msgtomanager.getMessageLogView', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		debugPrint($output);

		// set a signiture by calling getEditor of the editor module
		$oEditorModel = getModel('editor');
		$option = new stdClass();
		$option->primary_key_name = 'log_srl';
		$option->content_key_name = 'content';
		$option->allow_fileupload = FALSE;
		$option->enable_autosave = FALSE;
		$option->enable_default_component = TRUE; // FALSE;
		$option->enable_component = FALSE;
		$option->resizable = FALSE;
		$option->disable_html = TRUE;
		$option->height = 300;
		$option->skin = $this->config->editor_skin;
		$option->colorset = $this->config->editor_colorset;
		$editor = $oEditorModel->getEditor(Context::get('logged_info')->member_srl, $option);
		Context::set('editor', $editor);

		Context::set('log_data', $output->data);
	}
}
/* End of file msgtomanager.admin.view.php */
/* Location: ./modules/msgtomanager/msgtomanager.admin.view.php */
