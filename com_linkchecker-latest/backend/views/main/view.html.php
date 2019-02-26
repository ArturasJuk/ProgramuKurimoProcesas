<?php
/*
 * @copyright  Copyright (C) 2016 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_COMPONENT_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'shared_functions.php');

class LinkCheckerViewMain extends JViewLegacy {
	function display($tmpl = null) {
		JToolbarHelper::title(JText::_('COM_LINKCHECKER'));

		if (JFactory::getUser()->authorise('core.admin', 'com_linkchecker')) {
			JToolbarHelper::preferences('com_linkchecker');
		}

		JHtml::_('jquery.framework');

		$doc = JFactory::getDocument();
		$params = JComponentHelper::getParams('com_linkchecker');

		$doc->addScript(JURI::root() . '/media/com_linkchecker/js/linkchecker-1.11.0.min.js', 'text/javascript');
		$doc->addScriptDeclaration("jQuery(document).ready(function() { riot.mount('*', { linkchecker: riot.observable() }); });");

		$doc->addStyleSheet(JURI::root() . '/media/com_linkchecker/css/wrapped.min.css?v=4'); // TODO use real version and make sure version is updated when needed
		$doc->addStyleSheet(JURI::root() . '/media/com_linkchecker/css/custom.css?v=1'); // TODO use real version and make sure version is updated when needed

		$this->onLocalhost = preg_match('/^https?:\/\/(?:localhost|127\.0\.0\.1)/i', JURI::root()) === 1; // TODO improve localhost detection
		$this->maxFetchers = (int) $params->get('max_fetchers', 10);
		$this->token = $params->get('token');

		$this->websiteURLs = array(JURI::root());

		$isMultilangSupportNecessary = isMultilangSupportNecessary();

		if ($isMultilangSupportNecessary && $this->token != '') {
			$this->websiteURLs = loadMultilangData(function ($language, $langCode, $defaultLangCode, $sefRewrite) {
				$websiteURL = JURI::root() . 'index.php/' . $language->sef . '/';

				if ($sefRewrite) {
					$websiteURL = JURI::root() . $language->sef . '/';
				}

				return $websiteURL;
			});
		}

		$this->showMultilangWarning = $isMultilangSupportNecessary && $this->token == '';

		if (JFactory::getApplication()->input->getInt('dev', 0) === 1) {
			$this->onLocalhost = false;
			$this->websiteURLs = array('https://www.marcobeierer.com/');
		} else if (JFactory::getApplication()->input->getInt('dev', 0) === 2) {
			$this->onLocalhost = false;
			$this->websiteURLs = array('https://www.marcobeierer.com/', 'https://www.marcobeierer.com/tools/link-checker');
		}

		$this->email = JFactory::getConfig()->get('mailfrom');

		parent::display();
	}

}
