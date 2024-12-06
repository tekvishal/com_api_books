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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Joomla\CMS\Access\Access;
use Joomla\CMS\User\UserHelper;
use Empvaibhav\Component\Employee\Administrator\Model\BookItemModel;
/**
 * User Api.
 * Creates a new user, updates an existing user and gets data of an user
 *
 * @package  Com.Api
 *
 * @since    2.0
 */
class BooksApiResourceBookauthor extends ApiResource
{
	/**
	 * Function to create and edit user record.
	 *
	 * @return object|void User details on success. raise error on failure.
	 *
	 * @since   2.0
	 */
	public function post()
	{
		$app = Factory::getApplication();
		$userIdentifier = $app->input->get('id', 0, 'String');
		$formData = $app->input->getArray();
		// echo "<pre>"; print_r($formData);die;
		// $params = ComponentHelper::getParams("com_employee");
		$response = new stdClass;

		$xidentifier = $app->input->server->get('HTTP_X_IDENTIFIER');
		$fidentifier = $app->input->server->get('HTTP_FORCECREATE');

		// if ($formData['username'] == '' || $formData['name'] == '' || $formData['email'] == '')
		// {
		// 	ApiError::raiseError(400, Text::_('PLG_API_USERS_REQUIRED_DATA_EMPTY_MESSAGE'));

		// 	return;
		// }

		// Get current logged in user.
		// $my = Factory::getUser();

		// Check if $userIdentifier is not set
		if (empty($userIdentifier))
		{
			// if ($formData['password'] == '')
			// {
			// 	ApiError::raiseError(400, Text::_('PLG_API_USERS_REQUIRED_DATA_EMPTY_MESSAGE'));

			// 	return;
			// }

			

			// Get a blank user object
			$book = new BookItemModel;
			
			// Create new user.
			$response = $this->storeBook($book, $formData, 1);
			// die("hi");
			$this->plugin->setResponse($response);

			return;
		}
		else
		{
			// Get a user object
			// $book = $this->retriveBook($xidentifier, $userIdentifier);
			$passedUserGroups = array();

			// If user is already present then update it according to access.
			if (!empty($user->id))
			{
				$iAmSuperAdmin	= $my->authorise('core.admin');

				// Check if regular user is tring to update himself.
				if ($my->id == $user->id || $iAmSuperAdmin)
				{
					// If present then update or else dont include.
					if (!empty($formData['password']))
					{
						$formData['password2'] = $formData['password'];
					}

					// Add newly added groups and keep the old one as it is.
					if (!empty($formData['groups']))
					{
						$passedUserGroups['groups'] = array_unique(array_merge($user->groups, $formData['groups']));
					}

					$response = $this->storeUser($user, $passedUserGroups);
					$this->plugin->setResponse($response);

					return;
				}
				else
				{
					ApiError::raiseError(400, Text::_('JERROR_ALERTNOAUTHOR'));

					return;
				}
			}
			else
			{
				if ($fidentifier)
				{
					$user = new User;

					if ($formData['password'] == '')
					{
						ApiError::raiseError(400, Text::_('PLG_API_USERS_REQUIRED_DATA_EMPTY_MESSAGE'));

						return;
					}

					// Set default group if nothing is passed for group.
					if (empty($formData['groups']))
					{
						$formData['groups'] = array($params->get("new_usertype", 2));
					}

					// Create new user.
					$response = $this->storeUser($user, $formData, 1);
					$this->plugin->setResponse($response);

					return;
				}
				else
				{
					ApiError::raiseError(400, Text::_('PLG_API_USERS_USER_ABSENT_MESSAGE'));

					return;
				}
			}
		}
	}

	/**
	 * Function get for user record.
	 *
	 * @return object|void User details on success otherwise raise error
	 *
	 * @since   2.0
	 */
	public function get()
	{
		
		$input = Factory::getApplication()->input;
		$id = $input->get('id', 0, 'int');
		$xidentifier	= $input->server->get('HTTP_X_IDENTIFIER', '', 'String');

		/*
		 * If we have an id try to fetch the user
		 * @TODO write user field mapping logic here
		 */
		if ($id)
		{
			// Get a user object
			$book = $this->retriveBook("id", $id);
			// echo "<pre>"; print_r($book);die;

			if (! $book->id)
			{
				ApiError::raiseError(400, Text::_('PLG_API_USERS_USER_NOT_FOUND_MESSAGE'));

				return;
			}

			$this->plugin->setResponse($book);
		}
		else
		{
			$user = Factory::getUser();

			if ($user->guest)
			{
				ApiError::raiseError(400, Text::_('JERROR_ALERTNOAUTHOR'));
			}

			$this->plugin->setResponse($user);
		}
	}

	/**
	 * Function to return userid if a user exists depending on email
	 *
	 * @param   string  $email  The email to search on.
	 *
	 * @return  integer  The user id or 0 if not found.
	 *
	 * @since   2.0
	 */
	private function getBookId($title)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('id'))
			->from($db->quoteName('#__book'))
			->where($db->quoteName('title') . ' = ' . $db->quote($title));
		$db->setQuery($query, 0, 1);

		return $db->loadResult();
	}

	/**
	 * Funtion for bind and save data and return response.
	 *
	 * @param   Object   $user      The user object.
	 * @param   Array    $formData  Array of user data to be added or updated.
	 * @param   Boolean  $isNew     Flag to differentiate the update of create action.
	 *
	 * @return  object|void  $response  the response object created on after user saving. void and raise error
	 *
	 * @since   2.0
	 */
	private function storeBook($book, $formData, $isNew = 0)
	{
		$response = new stdClass;
		$book = new stdClass;

		$book->booktitle = $formData['title'];
		$book->bookauthor = "NA";
		$book->bookpublisher = $formData['publisher'];
		$book->bookprice= $formData['price'];

		// Insert the object into the user profile table.
		$db = JFactory::getDbo();
		$result = $db->insertObject('#__books', $book);
		if($result){
			$bookid = $db->insertid();
			foreach ($formData['author'] as $key => $value) {
				
				$author = new stdClass;
				
				$author->book_id = $bookid;
				$author->book_author = $value;
				$author->status = 1;
				$result = $db->insertObject('#__book_authors', $author);
				
			}
			$response->id = $bookid;
			$response->message = Text::_('PLG_API_BOOKS_CREATED_SUCCESSFULLY_MESSAGE');
		}
		else {
			$response->message = Text::_('PLG_API_BOOKS_CREATE_FAIL_MESSAGE');
		}
		
		// echo "<pre>"; print_r($db->insertid());die;

		


		return $response;
	}

	/**
	 * Function delete is used to delete the respective user record.
	 *
	 * @return void
	 *
	 * @since   2.0
	 */
	public function delete()
	{
		$app = Factory::getApplication();
		$userIdentifier = $app->input->get('id', 0, 'STRING');
		$xidentifier = $app->input->server->get('HTTP_X_IDENTIFIER', '', 'String');

		$loggedUser = Factory::getUser();

		// Check if I am a Super Admin
		$iAmSuperAdmin = $loggedUser->authorise('core.admin');

		$userToDelete = $this->retriveUser($xidentifier, $userIdentifier);

		if (!$userToDelete->id)
		{
			ApiError::raiseError(400, Text::_('PLG_API_USERS_USER_NOT_FOUND_MESSAGE'));

			return;
		}

		if ($loggedUser->id == $userToDelete->id)
		{
			ApiError::raiseError(400, Text::_('COM_USERS_USERS_ERROR_CANNOT_DELETE_SELF'));

			return;
		}

		// Access checks.
		$allow = $loggedUser->authorise('core.delete', 'com_users');

		// Don't allow non-super-admin to delete a super admin
		$allow = (!$iAmSuperAdmin && Access::check($userToDelete->id, 'core.admin')) ? false : $allow;

		if ($allow)
		{
			if (!$userToDelete->delete())
			{
				ApiError::raiseError(400, $userToDelete->getError());

				return;
			}
		}
		else
		{
			ApiError::raiseError(403, Text::_('JERROR_CORE_DELETE_NOT_PERMITTED'));

			return;
		}

		$response = new stdClass;
		$response->message = Text::_('PLG_API_USERS_USER_DELETE_MESSAGE');
		$this->plugin->setResponse($response);

		return;
	}

	/**
	 * Function retriveUser for get user details depending upon the identifier.
	 *
	 * @param   string  $xidentifier     Flag to differentiate the column value.
	 *
	 * @param   string  $id  bookid
	 *
	 * @return  object  $book  Book object if book exist otherwise std class.
	 *
	 * @since   2.0
	 */
	private function retriveBook($xidentifier, $id)
	{
		$book = new stdClass;

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('a.id', 'a.booktitle', 'a.bookpublisher','a.bookprice', 'b.book_author')))
			->from($db->quoteName('#__books','a'))
			->join('INNER', $db->quoteName('#__book_authors', 'b') . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('b.book_id'))
			->where($db->quoteName("a.".$xidentifier) . ' = ' . $db->quote($id));
		$db->setQuery($query);

		return $db->loadObjectList();
		
	}
}
