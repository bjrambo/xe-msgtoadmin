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
		// Check login information
		if(!Context::get('is_logged'))
		{
			return new Object(-1, 'msg_not_logged');
		}

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
		$config = $oCommunicationModel->getConfig();

		if(!$oCommunicationModel->checkGrant($config->grant_send))
		{
			return new Object(-1, 'msg_not_permitted');
		}

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
			if(Context::get('is_popup') != 'Y')
			{
				global $lang;
				htmlHeader();
				alertScript($lang->success_sended);
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
			else
			{
				$this->setMessage('success_sended');
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('','act', 'dispCommunicationMessages', 'message_type', 'S', 'receiver_srl', $receiver_srl, 'message_srl', '');
				$this->setRedirectUrl($returnUrl);
			}
		}

		return $output;
	}
}
/* End of file msgtomanager.controller.php */
/* Location: ./modules/msgtomanager/msgtomanager.controller.php */
