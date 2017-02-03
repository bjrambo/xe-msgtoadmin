<?php
/* Copyright (C) BJRambo <http://www.bjrambo.com> */
/**
 * @class  msgtomanagerView
 * @author BJRambo (qw5414@naver.com)
 * @brief View class of the msgtomanager modules
 */
class msgtomanagerView extends msgtomanager
{
	function init()
	{
		$oCommunicationModel = getModel('communication');

		$this->config = $oCommunicationModel->getConfig();
		$skin = $this->config->skin;

		Context::set('communication_config', $this->config);

		$config_parse = explode('|@|', $skin);

		if(count($config_parse) > 1)
		{
			$tpl_path = sprintf('./themes/%s/modules/communication/', $config_parse[0]);
		}
		else
		{
			$tpl_path = sprintf('%sskins/%s', './modules/msgtomanager/', 'default');
		}
		$this->setTemplatePath($tpl_path);

		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($this->config->layout_srl);
		if($layout_info)
		{
			$this->module_info->layout_srl = $this->config->layout_srl;
			$this->setLayoutPath($layout_info->path);
		}
	}

	function dispMsgtomanagerSendmessage()
	{
		if($this->config->enable_message == 'N')
		{
			return new Object(-1, 'msg_invalid_request');
		}

		$logged_info = Context::get('logged_info');

		if(!Context::get('is_logged'))
		{
			$logged_info->member_srl = 4;
		}
		// get receipient's information
		// check inalid request
		$receiver_srl = Context::get('receiver_srl');
		if(!$receiver_srl)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		// check receiver and sender are same
		if($logged_info->member_srl !== 4)
		{
			if($logged_info->member_srl == $receiver_srl)
			{
				return new Object(-1, 'msg_cannot_send_to_yourself');
			}
		}

		$oCommunicationModel = getModel('communication');
		$oMemberModel = getModel('member');

		// get message_srl of the original message if it is a reply
		$message_srl = Context::get('message_srl');
		if($message_srl)
		{
			$source_message = $oCommunicationModel->getSelectedMessage($message_srl);
			if($source_message->message_srl == $message_srl && $source_message->sender_srl == $receiver_srl)
			{
				if(strncasecmp('[re]', $source_message->title, 4) !== 0)
				{
					$source_message->title = '[re] ' . $source_message->title;
				}
				$source_message->content = "\r\n<br />\r\n<br /><div style=\"padding-left:5px; border-left:5px solid #DDDDDD;\">" . trim($source_message->content) . "</div>";
				Context::set('source_message', $source_message);
			}
		}

		$receiver_info = $oMemberModel->getMemberInfoByMemberSrl($receiver_srl);
		if(!$receiver_info)
		{
			return new Object(-1, 'msg_invalid_request');
		}

		Context::set('receiver_info', $receiver_info);

		// set a signiture by calling getEditor of the editor module
		$oEditorModel = getModel('editor');
		$option = new stdClass();
		$option->primary_key_name = 'receiver_srl';
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
		$editor = $oEditorModel->getEditor($logged_info->member_srl, $option);
		Context::set('editor', $editor);

		$this->setTemplateFile('send_message');
	}
}
/* End of file msgtomanager.view.php */
/* Location: ./modules/msgtomanager/msgtomanager.view.php */
