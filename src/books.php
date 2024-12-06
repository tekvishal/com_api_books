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

/**
 * Users plgAPI class
 *
 * @since  1.0.0
 */
class PlgAPIBooks extends ApiPlugin
{
	/**
	 * Constructor
	 *
	 * @param   string  &$subject  subject
	 * @param   string  $config    config
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());

		ApiResource::addIncludePath(dirname(__FILE__) . '/books');
		
		/*load language file for plugin frontend*/ 
		$lang = Factory::getLanguage(); 
		$lang->load('plg_api_books', JPATH_ADMINISTRATOR, '', true);

		$this->setResourceAccess('books', 'public', 'post');
		$this->setResourceAccess('config', 'public', 'get');
		$this->setResourceAccess('book', 'public', 'post');
	}
}
