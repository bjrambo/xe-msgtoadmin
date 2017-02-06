<?php
/* Copyright (C) BJRambo <http://www.bjrambo.com> */
/**
 * Class msgtomanagerController
 * @author BJRambo (qw5414@naver.com)
 * @brief Controller class for msgtomanager modules
 */
class msgtomanagerController extends msgtomanager
{

	function procMsgtomanagerSendMessage()
	{
		$logged_info = Context::get('logged_info');

		// Check variables
		$receiver_srl = Context::get('receiver_srl');
		if(!$receiver_srl)
		{
			return new Object(-1, 'msg_not_exists_member');
		}

		$title = trim(Context::get('title'));
		if(!$title)
		{
			return new Object(-1, 'msg_title_is_null');
		}

		$content = trim(Context::get('content'));
		if(!$content)
		{
			return new Object(-1, 'msg_content_is_null');
		}

		$send_mail = Context::get('send_mail');
		if($send_mail != 'Y')
		{
			$send_mail = 'N';
		}

		// Check if there is a member to receive a message
		$oMemberModel = getModel('member');
		$oCommunicationModel = getModel('communication');

		$receiver_member_info = $oMemberModel->getMemberInfoByMemberSrl($receiver_srl);
		if($receiver_member_info->member_srl != $receiver_srl)
		{
			return new Object(-1, 'msg_not_exists_member');
		}

		// check whether to allow to receive the message(pass if a top-administrator)
		if($logged_info->is_admin != 'Y')
		{
			if($receiver_member_info->allow_message == 'F')
			{
				if(!$oCommunicationModel->isFriend($receiver_member_info->member_srl))
				{
					return new object(-1, 'msg_allow_message_to_friend');
				}
			}
			else if($receiver_member_info->allow_message == 'N')
			{
				return new object(-1, 'msg_disallow_message');
			}
		}

		// send a message
		$output = $this->sendMessage($logged_info->member_srl, $receiver_srl, $title, $content);

		if(!$output->toBool())
		{
			return $output;
		}

		// send an e-mail
		if($send_mail == 'Y')
		{
			$view_url = Context::getRequestUri();
			$content = sprintf("%s<br /><br />From : <a href=\"%s\" target=\"_blank\">%s</a>", $content, $view_url, $view_url);
			$oMail = new Mail();
			$oMail->setTitle(htmlspecialchars($title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
			$oMail->setContent(utf8_mbencode(removeHackTag($content)));
			$oMail->setSender($logged_info->nick_name, $logged_info->email_address);
			$oMail->setReceiptor($receiver_member_info->nick_name, $receiver_member_info->email_address);
			$oMail->send();
		}

		if(!in_array(Context::getRequestMethod(), array('XMLRPC', 'JSON')))
		{
			$this->setMessage('success_sended');
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('','act', 'dispMsgtomanagerSendmessage', 'receiver_srl', $receiver_srl);
			$this->setRedirectUrl($returnUrl);
		}

		return $output;
	}

	function sendMessage($sender_srl, $receiver_srl, $title, $content, $sender_log = TRUE)
	{
		$logged_info = Context::get('logged_info');

		// Encode the title and content.
		$title = htmlspecialchars($title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		$content = removeHackTag($content);
		$title = utf8_mbencode($title);
		$content = utf8_mbencode($content);

		$message_srl = getNextSequence();
		$related_srl = getNextSequence();

		// messages to save in the sendor's message box
		$sender_args = new stdClass();
		$sender_args->sender_srl = $sender_srl;
		$sender_args->receiver_srl = $receiver_srl;
		$sender_args->message_type = 'S';
		$sender_args->title = $title;
		$sender_args->content = $content;
		$sender_args->readed = 'N';
		$sender_args->regdate = date("YmdHis");
		$sender_args->message_srl = $message_srl;
		$sender_args->related_srl = $related_srl;
		$sender_args->list_order = $sender_args->message_srl * -1;


		// Call a trigger (before)
		$trigger_obj = new stdClass();
		$trigger_obj->sender_srl = $sender_srl;
		$trigger_obj->receiver_srl = $receiver_srl;
		$trigger_obj->message_srl = $message_srl;
		$trigger_obj->related_srl = $related_srl;
		$trigger_obj->title = $title;
		$trigger_obj->content = $content;
		$trigger_obj->sender_log = $sender_log;
		$trigger_obj->nick_name = $logged_info->nick_name;
		$trigger_obj->email = $logged_info->email_address;

		$trigger_output = ModuleHandler::triggerCall('communication.sendMessage', 'before', $trigger_obj);
		if(!$trigger_output->toBool())
		{
			return $trigger_output;
		}

		$isSend = $this->sendLogInsert($trigger_obj);
		if(!$isSend->toBool())
		{
			return $isSend;
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		// messages to save in the sendor's message box
		if($sender_srl && $sender_log)
		{
			$output = executeQuery('communication.sendMessage', $sender_args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		// Call a trigger (after)
		ModuleHandler::triggerCall('communication.sendMessage', 'after', $trigger_obj);

		$oDB->commit();

		// create a flag that message is sent (in file format)
		$this->updateFlagFile($receiver_srl);

		return new Object(0, 'success_sended');
	}

	/**
	 * Update flag file
	 * @param int $member_srl
	 * @return void
	 */
	function updateFlagFile($member_srl)
	{
		$flag_path = _XE_PATH_ . 'files/member_extra_info/new_message_flags/' . getNumberingPath($member_srl);
		$flag_file = $flag_path . $member_srl;
		$new_message_count = getModel('communication')->getNewMessageCount($member_srl);
		if($new_message_count > 0)
		{
			FileHandler::writeFile($flag_file, $new_message_count);
		}
		else
		{
			FileHandler::removeFile($flag_file);
		}
	}

	function sendLogInsert($obj)
	{
		$args = new stdClass();
		$args->log_srl = getNextSequence();
		$args->sender_srl = $obj->sender_srl;
		$args->receiver_srl = $obj->receiver_srl;
		$args->message_srl = $obj->message_srl;
		$args->related_srl = $obj->related_srl;
		$args->title = $obj->title;
		$args->content = $obj->content;
		$args->nick_name = $obj->nick_name;
		$args->email = $obj->email;

		$output = executeQuery('msgtomanager.insertLog', $args);

		return $output;
	}
}
/* End of file msgtomanager.controller.php */
/* Location: ./modules/msgtomanager/msgtomanager.controller.php */
