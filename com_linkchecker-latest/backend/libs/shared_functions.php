<?php
/*
 * @copyright  Copyright (C) 2016 Marco Beierer. All rights reserved.
 * @license    http://www.gnu.org/licenses/agpl-3.0.html GNU/AGPL
 */
defined('_JEXEC') or die('Restricted access');

function isLanguageFilterEnabled() {
	return JPluginHelper::isEnabled('system', 'languagefilter');
}

function doRemoveDefaultPrefix() {
	if (isLanguageFilterEnabled()) {
		$languageFilterPlugin = JPluginHelper::getPlugin('system', 'languagefilter');
		$languageFilterParams = new JRegistry($languageFilterPlugin->params);

		return $languageFilterParams->get('remove_default_prefix', 0) == '1';
	} else {
		return false;
	}
}

function isMultilangSupportNecessary() {
	$sef = JFactory::getConfig()->get('sef', 0);
	return isLanguageFilterEnabled() && $sef == '1' && !doRemoveDefaultPrefix();
}

function loadMultilangData($prepareElementCallback) {
	$languages = JLanguageHelper::getLanguages();
	$app = JApplication::getInstance('site');
	$menu = $app->getMenu();
	$config = JFactory::getConfig();

	$sef = $config->get('sef', 0);
	$sefRewrite = $config->get('sef_rewrite', 0);

	$defaultLangCode = JFactory::getLanguage()->getDefault();

	$websites = array();
	//$websites['*'] = $menu->getDefault('*'); // TODO add?

	$languageFilterEnabled = JPluginHelper::isEnabled('system', 'languagefilter');
	if (!$languageFilterEnabled || $sef != '1') { // TODO check also if sef is enabled
		return $websites;
	}

	$oldLanguageFilterValue = $app->setLanguageFilter(true); // necessary that $menu->getDefault() works

	foreach ($languages as $language) {
		$langCode = $language->lang_code;
		$default = $menu->getDefault($langCode);

		if ($default && $default->language == $langCode) {
			$websites[$langCode] = $prepareElementCallback($language, $langCode, $defaultLangCode, $sefRewrite);
		}
	}

	$app->setLanguageFilter($oldLanguageFilterValue);

	return $websites;
}

?>
