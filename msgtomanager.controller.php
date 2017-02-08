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
		$obj = Context::getRequestVars();

		if(!$obj->email)
		{
			return new Object(-1, '이메일 값은 필수 입력항목입니다.');
		}

		$obj->title = trim($obj->title);
		if(!$obj->title)
		{
			return new Object(-1, 'msg_title_is_null');
		}

		$obj->content = trim($obj->content);
		if(!$obj->content)
		{
			return new Object(-1, 'msg_content_is_null');
		}

		if($obj->send_mail != 'Y')
		{
			$obj->send_mail = 'N';
		}

		// Check if there is a member to receive a message
		$oMemberModel = getModel('member');
		$oCommunicationModel = getModel('communication');

		$receiver_member_info = $oMemberModel->getMemberInfoByMemberSrl($obj->receiver_srl);
		if($receiver_member_info->member_srl != $obj->receiver_srl)
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
		$output = $this->sendMessage($logged_info->member_srl, $obj);

		if(!$output->toBool())
		{
			return $output;
		}

		// send an e-mail
		if($obj->send_mail == 'Y')
		{
			$view_url = Context::getRequestUri();
			$content = sprintf("%s<br /><br />From : <a href=\"%s\" target=\"_blank\">%s</a>", $obj->content, $view_url, $view_url);
			$oMail = new Mail();
			$oMail->setTitle(htmlspecialchars($obj->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
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

		//return $output;

	}

	/**
	 * @param int $sender_srl
	 * @param object $obj
	 * @param bool $sender_log
	 * @return object
	 */
	function sendMessage($sender_srl, $obj, $sender_log = true)
	{
		// Encode the title and content.
		$title = htmlspecialchars($obj->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
		$content = removeHackTag($obj->content);
		$title = utf8_mbencode($title);
		$content = utf8_mbencode($content);

		$message_srl = getNextSequence();
		$related_srl = getNextSequence();

		$admin_args = new stdClass();
		$admin_args->is_admin = 'Y';
		$output = executeQueryArray('member.getMemberList', $admin_args);
		$adminMembers = $output->data;

		if(count($adminMembers) > 0)
		{
			$oDB = DB::getInstance();
			$oDB->begin();

			foreach($adminMembers as $admin)
			{
				// messages to save in the sendor's message box
				$sender_args = new stdClass();
				$sender_args->sender_srl = $sender_srl;
				$sender_args->receiver_srl = $admin->member_srl;
				$sender_args->message_type = 'S';
				$sender_args->title = $title;
				$sender_args->content = $content;
				$sender_args->readed = 'N';
				$sender_args->regdate = date("YmdHis");
				$sender_args->message_srl = $message_srl;
				$sender_args->related_srl = $related_srl;
				$sender_args->list_order = $sender_args->message_srl * -1;
				$sender_args->sender_log = $sender_log;
				$sender_args->nick_name = 'nick';
				$sender_args->email = 'email';

				$trigger_output = ModuleHandler::triggerCall('msgtomanager.sendMessage', 'before', $sender_args);
				if(!$trigger_output->toBool())
				{
					return $trigger_output;
				}

				$isSend = $this->sendLogInsert($sender_args);
				if(!$isSend->toBool())
				{
					return $isSend;
				}

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
				ModuleHandler::triggerCall('msgtomanager.sendMessage', 'after', $sender_args);



				// create a flag that message is sent (in file format)
				$this->updateFlagFile($admin->member_srl);
			}
			$oDB->commit();
		}

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
