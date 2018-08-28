<?php

/**
 * @package     External_Login
 * @subpackage  Component
 * @author      Christophe Demko <chdemko@gmail.com>
 * @author      Ioannis Barounis <contact@johnbarounis.com>
 * @author      Alexandre Gandois <alexandre.gandois@etudiant.univ-lr.fr>
 * @copyright   Copyright (C) 2008-2017 Christophe Demko, Ioannis Barounis, Alexandre Gandois. All rights reserved.
 * @license     GNU General Public License, version 2. http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.chdemko.com
 */

// No direct access to this file
defined('_JEXEC') or die;

// Import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Login Model of External Login component
 *
 * @package     External_Login
 * @subpackage  Component
 *
 * @since       2.0.0
 */
class ExternalloginModelLogin extends JModelList
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string|null  $ordering   Column for ordering
	 * @param   string|null  $direction  Direction of ordering
	 *
	 * @return  void
	 *
	 * @note  Calling getState in this method will result in recursion.
	 *
	 * @see  JModelList::populateState
	 *
	 * @since  2.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Adjust the context to support modal layouts.
		if ($layout = JFactory::getApplication()->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

		$redirect = JFactory::getApplication()->input->get('redirect');
		$this->setState('server.redirect', $redirect);

		// List state information.
		parent::populateState('a.ordering', 'asc');
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  object  A JDatabaseQuery object to retrieve the data set.
	 *
	 * @see  JModelList::getListQuery
	 *
	 * @since  2.0.0
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		$query->select('a.*');

		// From the externallogin_servers table
		$query->from($db->quoteName('#__externallogin_servers') . ' as a');

		// Join over the users for the enabled plugins.
		$query->join('LEFT', '#__extensions AS e ON ' .
			$db->quoteName('e.type') . '=' . $db->quote('plugin') . ' AND ' .
			$query->concatenate(array($db->quoteName('e.folder'), $db->quoteName('e.element')), '.') . '=' . $db->quoteName('a.plugin')
		);
		$query->where('e.enabled = 1');

		// Filter by published state
		$query->where('a.published = 1');

		// Add the list ordering clause.
		$orderCol = $this->state->get('list.ordering');
		$orderDirn = $this->state->get('list.direction');
		$query->order($db->escape($orderCol . ' ' . $orderDirn));

		return $query;
	}

	/**
	 * Method to get a list of servers.
	 *
	 * @return  array  A list of servers.
	 *
	 * @since  2.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$app = JFactory::getApplication();
		$menu = $app->getMenu()->getActive();

		if ($menu)
		{
			$params = $menu->params;
		}
		else
		{
			$params = new JRegistry;
		}

		foreach ($items as $i => $item)
		{
			$item->params = new JRegistry($item->params);
			$redirect = $this->getState(
				'server.redirect',
				$params->get(
					'redirect',
					$item->params->get(
						'redirect',
						JComponentHelper::getParams('com_externallogin')->get('redirect')
					)
				)
			);
			$url = 'index.php?option=com_externallogin&view=server&server=' . $item->id;

			if (!empty($redirect))
			{
				$url .= '&redirect=' . $redirect;
			}

			$item->url = $url;
		}

		return $items;
	}
}
