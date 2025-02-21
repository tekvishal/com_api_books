<?php
/**
 * @package     API
 * @subpackage  plg_api_users
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2022 Techjoomla, Tekdi Technologies Pvt. Ltd. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\UserHelper;
use Empvaibhav\Component\Employee\Administrator\Model\BooksModel;
// Load com_users language file
$language = Factory::getLanguage();
$language->load('com_books');
$language->load('com_books', JPATH_SITE, 'en-GB', true);
$language->load('com_books', JPATH_ADMINISTRATOR, 'en-GB', true);

if (JVERSION < '4.0.0')
{
	require_once JPATH_ROOT . '/administrator/components/com_employee/models/books.php';
}
else
{
	require_once JPATH_ROOT . '/administrator/components/com_employee/src/Model/BooksModel.php';
}

/**
 * User Api.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_api
 *
 * @since       1.1
 */
class BooksApiResourceBooks extends ApiResource
{
	/**
	 * Function delete for user record.
	 *
	 *@deprecated  2.0 use UsersApiResourceUser delete instead
	 *
	 * @return void
	 */
	public function delete()
	{
		$app              = Factory::getApplication();
		$db 			  = Factory::getDbo();
		$data             = array();
		$eobj             = new stdClass;
		$eobj->status     = false;
		$eobj->id         = 0;

		// Get values paased by put in the url
		$data = $app->input->getArray(array());

		// Check username or user_id to edit the details of user
		if (isset($data['username']) || isset($data['user_id']))
		{
			if (!$data['user_id'] && $data['username'])
			{
				// Get user_id with the help of username
				$query = $db->getQuery(true);
				$query->select('id');
				$query->from('#__users');
				$query->where($db->quoteName('username') . '=' . $db->quote($data['username']));
				$db->setQuery($query);
				$user_id = $db->loadResult();
				$data['user_id'] = $user_id;
			}

			$user = User::getInstance($data['user_id']);

			if ($user->id && $user->delete())
			{
					$eobj->status     = true;
					$eobj->id         = $data['user_id'];
					$eobj->code       = '200';
					$eobj->message    = Text::_('COM_USERS_USERS_N_ITEMS_DELETED_1');
					$this->plugin->setResponse($eobj);

					return;
			}
			else
			{
					$eobj->code = '400';
					$eobj->message = Text::_('COM_USERS_USER_NOT_FOUND');
					$this->plugin->setResponse($eobj);

					return;
			}
		}
		else
		{
			// Not given username or user_id to edit the details of user
			$eobj->code = '400';
			$eobj->message = Text::_('PLG_API_USERS_REQUIRED_DATA_EMPTY_MESSAGE');
			$this->plugin->setResponse($eobj);

			return;
		}
	}

	/**
		* Function for edit user record.
		*
		* @deprecated  2.0 use UsersApiResourceUser post instead
		* @return void
		*/
	public function put()
	{
		$app              = Factory::getApplication();
		$db 			  = Factory::getDbo();
		$data             = array();
		$eobj             = new stdClass;
		$eobj->status     = false;
		$eobj->id         = 0;

		// Get values paased by put in the url
		$data = $app->input->getArray(array());

		// Check username or user_id to edit the details of user
		if (isset($data['username']) || isset($data['user_id']))
		{
			if (!$data['user_id'] && isset($data['username']))
			{
				// Get user_id with the help of username
				$query = $db->getQuery(true);
				$query->select('id');
				$query->from('#__users');
				$query->where($db->quoteName('username') . '=' . $db->quote($data['username']));
				$db->setQuery($query);
				$user_id = $db->loadResult();
				$data['user_id'] = $user_id;
			}

			// Given username or user_id not exist
				if (!$data['user_id'])
				{
					$eobj->code = '400';
					$eobj->message = Text::_('COM_USERS_USER_NOT_FOUND');
					$this->plugin->setResponse($eobj);

					return;
				}

				$user = Factory::getUser($data['user_id']);

				// Bind the data.
				if (!$user->bind($data))
				{
					// User deatils are not updated
					$message = $user->getError();
					$eobj->code = '400';
					$eobj->message = $message;
					$this->plugin->setResponse($eobj);

					return;
				}

				// Save the user data
				if (!$user->save())
				{
					// User deatils are not updated
					$message = $user->getError();
					$eobj->code = '400';
					$eobj->message = $message;
					$this->plugin->setResponse($eobj);

					return;
				}
				else
				{
					// Updated records updated successsfully
					$eobj->status     = true;
					$eobj->id         = $data['user_id'];
					$eobj->code       = '200';
					$eobj->message    = Text::_('PLG_API_USERS_ACCOUNT_EDITED_SUCCESSFULLY_MESSAGE');
					$this->plugin->setResponse($eobj);

					return;
				}
		}
		else
		{
			// Not given username or user_id to edit the details of user
			$eobj->code = '400';
			$eobj->message = Text::_('PLG_API_USERS_REQUIRED_DATA_EMPTY_MESSAGE');
			$this->plugin->setResponse($eobj);

			return;
		}
	}

	/**
		* Function for edit user record.
		*
		* @return void
		*/
	public function post()
	{
		$error_messages = array();
		$fieldname      = array();
		$response       = null;
		$validated      = true;
		$userid         = null;
		$data           = array();

		$app              = Factory::getApplication();
		$data['username'] = $app->input->get('username', '', 'STRING');
		$data['password'] = $app->input->get('password', '', 'STRING');
		$data['name']     = $app->input->get('name', '', 'STRING');
		$data['email']    = $app->input->get('email', '', 'STRING');
		$data['enabled']    = $app->input->get('enabled', 1, 'INT');

		$data['activation']    = $app->input->get('activation', 0, 'INT');
		$data['app']    = $app->input->get('app_name', 'Easysocial App', 'STRING');
		$data['profile_id']    = $app->input->get('profile_id', 1, 'INT');

		$eobj = new stdClass();

		if( $data['username']=='' ||  $data['password'] =='' || $data['name'] == '' || $data['email']== '')
		{
			$eobj->status = false;
			$eobj->id = 0;
			$eobj->code = '403';
			$eobj->message = Text::_( 'PLG_API_USERS_REQUIRED_DATA_EMPTY_MESSAGE' );

			$this->plugin->setResponse($eobj);
			return;

		}

		$user = clone Factory::getUser();
		$user->set('username', $data['username']);
		$user->set('password', $data['password']);
		$user->set('name', $data['name']);
		$user->set('email', $data['email']);
		$user->set('block', $data['enabled']);
		$user->set('activation', $data['activation']);

		// Password encryption
		$salt           = UserHelper::genRandomPassword(32);
		$crypt          = UserHelper::getCryptedPassword($user->password, $salt);
		$user->password = "$crypt:$salt";

		// User group/type
		$user->set('id', '');
		$user->set('usertype', 'Registered');

		$userConfig       = ComponentHelper::getParams('com_users');

		// Default to Registered.
		$defaultUserGroup = $userConfig->get('new_usertype', 2);
		$user->set('groups', array($defaultUserGroup));

		$date = Factory::getDate();
		$user->set('registerDate', $date->toSql());

		// True on success, false otherwise
		if (!$user->save())
		{
			//$message = "not created because of " . $user->getError();
			$message = $user->getError();

			$eobj->status = false;
			$eobj->id = 0;
			$eobj->code = '403';
			$eobj->message = $message;
			$this->plugin->setResponse($eobj);
			return;
		}
		else
		{

			/*
			// Auto registration
			if( $data['activation'] == 0)
			{
				$emailSubject = 'Email Subject for registration successfully';
				$emailBody = 'Email body for registration successfully';
				$return = Factory::getMailer()->sendMail('sender email', 'sender name', $user->email, $emailSubject, $emailBody);

			}
			else if( $data['activation'] == 1)
			{
				$emailSubject = 'Email Subject for activate the account';
				$emailBody = 'Email body for for activate the account';
				$user_activation_url = JURI::base().JRoute::_('index.php?option=com_users&task=registration.activate&token=' . $user->activation, false);  // Append this URL in your email body
				$return = Factory::getMailer()->sendMail('sender email', 'sender name', $user->email, $emailSubject, $emailBody);

			}
			*/
			/* Update profile type */

			$mail_sent = $this->sendRegisterEmail($data);

			$easysocial = JPATH_ADMINISTRATOR .'/components/com_easysocial/easysocial.php';
			//eb version
			if( File::exists( $easysocial ) && ComponentHelper::isEnabled('com_easysocial', true))
			{
				$profiles = FD::model( 'profiles' );
				$all_profiles = $profiles->getAllProfiles();

				foreach ($all_profiles as $key) {
					if($key->id == $data['profile_id']){
						$profiles->updateUserProfile($user->id,$data['profile_id']);
					}
				}
				$pobj = $this->createEsprofile($user->id);
				// Assign badge for the person.
				$badge = FD::badges();
				$badge->log( 'com_easysocial' , 'registration.create' , $user->id , Text::_( 'COM_EASYSOCIAL_REGISTRATION_BADGE_REGISTERED' ) );
				//$message = "created of username-" . $user->username .",send mail of details please check";
				$message = Text::_('PLG_API_USERS_ACCOUNT_CREATED_SUCCESSFULLY_MESSAGE');
			}
			else
			$message = Text::_('PLG_API_USERS_ACCOUNT_CREATED_SUCCESSFULLY_MESSAGE');

		}
		$userid = $user->id;

		// Result message
		//$result = array('user id ' => $userid, 'message' => $message);
		//$result = ($userid) ? $result : $message;
		$eobj->status = true;
		$eobj->id = $userid;
		$eobj->code = '200';
		$eobj->message = $message;

		$this->plugin->setResponse($eobj);
		return;
	}

	/**
	 * Function get for users record.
	 *
	 * @deprecated  2.0 This will be move in the post method of UsersApiResourceUsers  with support for filters
	 *
	 * @return void
	 */
	public function get()
	{

		$input = Factory::getApplication()->input;
		$res = new stdClass();

		$model = new BooksModel;

		$app = Factory::getApplication('administrator');

		$app->setUserState("com_employee.books.default.filter.search", $input->get('search'));
		$app->setUserState("com_employee.books.default.filter.active", '0');
		$app->setUserState("com_employee.books.default.filter.state", '0');

		$res->result = $model->getItems();
		$res->mymessage = "Happy to help you get all books";

		foreach ($res->result as $k => $v)
		{
			unset($res->result[$k]->password);
		}

		$this->plugin->setResponse($res);
	}

	/**
	 * Function create easysocial profile.
	 *
	 * @deprecated  2.0 This will be move in the Easysocial API
	 *
	 * @return user obj
	 */
	public function createEsprofile($log_user)
	{
		$obj = new stdClass();

		if (ComponentHelper::isEnabled('com_easysocial', true))
		{
			$app    = Factory::getApplication();

			$epost = $app->input->get('fields', '', 'ARRAY');

			require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';

			// Get all published fields apps that are available in the current form to perform validations
			$fieldsModel = FD::model('Fields');

			// Get current user.
			$my = FD::user($log_user);

			// Only fetch relevant fields for this user.
			$options = array( 'profile_id' => $my->getProfile()->id, 'data' => true, 'dataId' => $my->id, 'dataType' => SOCIAL_TYPE_USER, 'visible' => SOCIAL_PROFILES_VIEW_EDIT, 'group' => SOCIAL_FIELDS_GROUP_USER );

			$fields = $fieldsModel->getCustomFields($options);

			$epost = $this->create_field_arr($fields,$epost);

			// Load json library.
			$json = FD::json();

			// Initialize default registry
			$registry = FD::registry();

			// Get disallowed keys so we wont get wrong values.
			$disallowed = array( FD::token() , 'option' , 'task' , 'controller' );

			// Process $_POST vars
			foreach ($epost as $key => $value) {

				if (!in_array($key, $disallowed)) {

					if (is_array($value) && $key != 'es-fields-11') {
						$value  = $json->encode( $value );
					}

					$registry->set($key, $value);
				}
			}

			// Convert the values into an array.
			$data = $registry->toArray();

			// Perform field validations here. Validation should only trigger apps that are loaded on the form
			// @trigger onRegisterValidate
			$fieldsLib	= FD::fields();

			// Get the general field trigger handler
			$handler = $fieldsLib->getHandler();

			// Build arguments to be passed to the field apps.
			$args = array( $data , &$my );

			// Ensure that there is no errors.
			// @trigger onEditValidate
			//$errors = $fieldsLib->trigger( 'onEditValidate' , SOCIAL_FIELDS_GROUP_USER , $fields , $args, array( $handler, 'validate' ) );

			// Bind the my object with appropriate data.
			$my->bind($data);

			// Save the user object.
			$sval = $my->save();

			// Reconstruct args
			$args 		= array(&$data, &$my);

			// @trigger onEditAfterSave
			$fieldsLib->trigger( 'onRegisterAfterSave' , SOCIAL_FIELDS_GROUP_USER , $fields , $args );

			// Bind custom fields for the user.
			$my->bindCustomFields($data);

			// Reconstruct args
			$args = array(&$data, &$my);

			// @trigger onEditAfterSaveFields
			$fieldsLib->trigger( 'onEditAfterSaveFields' , SOCIAL_FIELDS_GROUP_USER , $fields , $args );

			if($sval)
			{
				$obj->success = 1;
				$obj->message = Text::_('PLG_API_USERS_PROFILE_CREATED_SUCCESSFULLY_MESSAGE');
			}
			else
			{
				$obj->success = 0;
				$obj->message = Text::_( 'PLG_API_USERS_UNABLE_CREATE_PROFILE_MESSAGE' );
			}

		}
		else
		{
			$obj->success = 0;
			$obj->message = Text::_( 'PLG_API_USERS_EASYSOCIAL_NOT_INSTALL_MESSAGE');
		}

		return $obj;

	}

	/**
	 * create field array as per easysocial
	 *
	 * @deprecated  2.0 This will be move in the Easysocial API
	 *
	 * @return object
	 */
	public function create_field_arr($fields,$post)
	{
		$fld_data = array();
		$app = Factory::getApplication();

		require_once JPATH_SITE.'/plugins/api/easysocial/libraries/uploadHelper.php';
		//for upload photo
		 if(!empty($_FILES['avatar']['name']))
			{
				$upload_obj = new EasySocialApiUploadHelper();

				$phto_obj = $upload_obj->ajax_avatar($_FILES['avatar']);
				$avtar_pth = $phto_obj['temp_path'];
				$avtar_scr = $phto_obj['temp_uri'];
				$avtar_typ = 'upload';
				$avatar_file_name = $_FILES['avatar']['name'];
			}

		foreach($fields as $field)
		{
			$fobj = new stdClass();
			$fullname = $app->input->get('name', '', 'STRING');
			$fld_data['first_name'] = $app->input->get('name', '', 'STRING');


			$fobj->first = $fld_data['first_name'];
			$fobj->middle = '';
			$fobj->last = '';
			$fobj->name = $fullname;
			switch($field->unique_key)
			{
				case 'HEADER': break;

				case 'JOOMLA_FULLNAME':	$fld_data['es-fields-'.$field->id] = $fobj;
								break;
				case 'JOOMLA_USERNAME':	$fld_data['es-fields-'.$field->id] = $app->input->get('username', '', 'STRING');
								break;
				case 'JOOMLA_PASSWORD':	$fld_data['es-fields-'.$field->id] = $app->input->get('password', '', 'STRING');
								break;
				case 'JOOMLA_EMAIL':	$fld_data['es-fields-'.$field->id] = $app->input->get('email', '', 'STRING');
								break;
				case 'AVATAR':	if(isset($avtar_scr)){
									$fld_data['es-fields-'.$field->id] = Array
										(
											'source' =>$avtar_scr,
											'path' =>$avtar_pth,
											'data' => '',
											'type' => $avtar_typ,
											'name' => $avatar_file_name
										);
									}
								break;
			}
		}
		return $fld_data;
	}

	/**
	 * send registration mail
	 *
	 * @deprecated  2.0 This will be move in the Easysocial API
	 *
	 * @return object
	 */
	public function sendRegisterEmail($base_dt)
	{
		$config = Factory::getConfig();
		$params = ComponentHelper::getParams('com_users');
		$sendpassword = $params->get('sendpassword', 1);

		$lang = Factory::getLanguage();
		$lang->load('com_users', JPATH_SITE, '', true);

		$data['fromname'] = $config->get('fromname');
		$data['mailfrom'] = $config->get('mailfrom');
		$data['sitename'] = $config->get('sitename');
		$data['siteurl'] = Uri::root();
		$data['activation'] = $base_dt['activation'];

		// Handle account activation/confirmation emails.
		if ($data['activation'] == 0)
		{
			// Set the link to confirm the user email.
			$uri = Uri::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base . Route::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			$emailSubject = Text::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$base_dt['name'],
				$data['sitename']
			);

			if ($sendpassword)
			{
				$emailBody = Text::sprintf(
					'Hello %s,\n\nThank you for registering at %s. Your account is created and activated.
					\nYou can login to %s using the following username and password:\n\nUsername: %s\nPassword: %s',
					$base_dt['name'],
					$data['sitename'],
					$base_dt['app'],
					$base_dt['username'],
					$base_dt['password']
				);
			}

		}
		elseif ($data['activation'] == 1)
		{
			// Set the link to activate the user account.
			$uri = Uri::getInstance();
			$base = $uri->toString(array('scheme', 'user', 'pass', 'host', 'port'));
			$data['activate'] = $base . Route::_('index.php?option=com_users&task=registration.activate&token=' . $data['activation'], false);

			$emailSubject = Text::sprintf(
				'COM_USERS_EMAIL_ACCOUNT_DETAILS',
				$base_dt['name'],
				$data['sitename']
			);

			if ($sendpassword)
			{
				$emailBody = Text::sprintf(
					'COM_USERS_EMAIL_REGISTERED_WITH_ADMIN_ACTIVATION_BODY',
					$base_dt['name'],
					$data['sitename'],
					$data['activate'],
					$base_dt['app'],
					$base_dt['username'],
					$base_dt['password']
				);
			}
		}
		// Send the registration email.
		$return = Factory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $base_dt['email'], $emailSubject, $emailBody);
		return $return;

	}
}
