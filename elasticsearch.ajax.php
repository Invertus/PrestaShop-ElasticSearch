<?php
/**
 * 2015 Invertus, UAB
 *
 * NOTICE OF LICENSE
 *
 * This file is proprietary and can not be copied and/or distributed
 * without the express permission of INVERTUS, UAB
 *
 *  @author    INVERTUS, UAB www.invertus.eu <help@invertus.eu>
 *  @copyright 2015 INVERTUS, UAB
 *  @license   --
 *  International Registered Trademark & Property of INVERTUS, UAB
 */

include_once(dirname(__FILE__).'/../../config/config.inc.php');
include_once(dirname(__FILE__).'/../../init.php');

if (Tools::getValue('token') != Tools::getToken(false))
	exit;

if (Tools::isSubmit('submitElasticsearchSearch'))
{
	$module_instance = Module::getInstanceByName('elasticsearch');
	$result = $module_instance->submitSearchQuery();

	die(Tools::jsonEncode($result));
}

if (Tools::isSubmit('submitElasticsearchAjaxSearch'))
{
	$module_instance = Module::getInstanceByName('elasticsearch');
	$result = $module_instance->processAjaxSearch();

	die($result);
}

if (Tools::isSubmit('submitElasticsearchFilter'))
{
	$module_instance = Module::getInstanceByName('elasticsearch');

	require_once(_ELASTICSEARCH_CLASSES_DIR_.'ElasticSearchService.php');
	require_once(_ELASTICSEARCH_CLASSES_DIR_.'ElasticSearchFilter.php');

	$filter = new ElasticSearchFilter();
	$result = $filter->ajaxCall();

	die(Tools::jsonEncode($result));
}