<?php
/**
 * Kunena Component
 *
 * @package     Kunena.Site
 * @subpackage  Controllers
 *
 * @copyright   (C) 2008 - 2017 Kunena Team. All rights reserved.
 * @license     https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link        https://www.kunena.org
 **/
defined('_JEXEC') or die();

require_once KPATH_ADMIN . '/controllers/categories.php';

/**
 * Kunena Category Controller
 *
 * @since  2.0
 */
class KunenaControllerCategory extends KunenaAdminControllerCategories
{
	/**
	 * @param   array $config
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->baseurl  = 'index.php?option=com_kunena&view=category&layout=manage';
		$this->baseurl2 = 'index.php?option=com_kunena&view=category';
	}

	/**
	 * @throws Exception
	 */
	function jump()
	{
		$catid = JFactory::getApplication()->input->getInt('catid', 0);

		if (!$catid)
		{
			$this->setRedirect(KunenaRoute::_('index.php?option=com_kunena&view=category&layout=list', false));
		}
		else
		{
			$this->setRedirect(KunenaRoute::_("index.php?option=com_kunena&view=category&catid={$catid}", false));
		}
	}

	/**
	 * @throws Exception
	 */
	function markread()
	{
		if (!JSession::checkToken('request'))
		{
			$this->app->enqueueMessage(JText::_('COM_KUNENA_ERROR_TOKEN'), 'error');
			$this->setRedirectBack();

			return;
		}

		$catid    = JFactory::getApplication()->input->getInt('catid', 0);
		$children = JFactory::getApplication()->input->getBool('children', 0);

		if (!$catid)
		{
			// All categories
			$session = KunenaFactory::getSession();
			$session->markAllCategoriesRead();

			if (!$session->save())
			{
				$this->app->enqueueMessage(JText::_('COM_KUNENA_ERROR_SESSION_SAVE_FAILED'), 'error');
			}
			else
			{
				$this->app->enqueueMessage(JText::_('COM_KUNENA_GEN_ALL_MARKED'));
			}
		}
		else
		{
			// One category
			$category = KunenaForumCategoryHelper::get($catid);

			if (!$category->authorise('read'))
			{
				$this->app->enqueueMessage($category->getError(), 'error');
				$this->setRedirectBack();

				return;
			}

			$session = KunenaFactory::getSession();

			if ($session->userid)
			{
				$categories = array($category->id => $category);

				if ($children)
				{
					// Include all levels of child categories.
					$categories += $category->getChildren(-1);
				}

				// Mark all unread topics in selected categories as read.
				KunenaForumCategoryUserHelper::markRead(array_keys($categories));

				if (count($categories) > 1)
				{
					$this->app->enqueueMessage(JText::_('COM_KUNENA_GEN_ALL_MARKED'));
				}
				else
				{
					$this->app->enqueueMessage(JText::_('COM_KUNENA_GEN_FORUM_MARKED'));
				}
			}
		}

		$this->setRedirectBack();
	}

	/**
	 * @throws Exception
	 */
	function subscribe()
	{
		if (!JSession::checkToken('get'))
		{
			$this->app->enqueueMessage(JText::_('COM_KUNENA_ERROR_TOKEN'), 'error');
			$this->setRedirectBack();

			return;
		}

		$category = KunenaForumCategoryHelper::get(JFactory::getApplication()->input->getInt('catid', 0));

		if (!$category->authorise('read'))
		{
			$this->app->enqueueMessage($category->getError(), 'error');
			$this->setRedirectBack();

			return;
		}

		if ($this->me->exists())
		{
			$success = $category->subscribe(1);

			if ($success)
			{
				$this->app->enqueueMessage(JText::_('COM_KUNENA_GEN_CATEGORY_SUBCRIBED'));
			}
		}

		$this->setRedirectBack();
	}

	/**
	 * @throws Exception
	 */
	function unsubscribe()
	{
		if (!JSession::checkToken('request'))
		{
			$this->app->enqueueMessage(JText::_('COM_KUNENA_ERROR_TOKEN'), 'error');
			$this->setRedirectBack();

			return;
		}

		$catid  = JFactory::getApplication()->input->getInt('catid', 0);
		$catids = $catid
			? array($catid)
			: array_keys(JFactory::getApplication()->input->get('categories', array(), 'post', 'array'));
		Joomla\Utilities\ArrayHelper::toInteger($catids);

		$categories = KunenaForumCategoryHelper::getCategories($catids);

		foreach ($categories as $category)
		{
			if (!$category->authorise('read'))
			{
				$this->app->enqueueMessage($category->getError(), 'error');
				continue;
			}

			if ($this->me->exists())
			{
				$success = $category->subscribe(0);

				if ($success)
				{
					$this->app->enqueueMessage(JText::sprintf('COM_KUNENA_GEN_CATEGORY_NAME_UNSUBCRIBED', $category->name));
				}
			}
		}

		$this->setRedirectBack();
	}
}
