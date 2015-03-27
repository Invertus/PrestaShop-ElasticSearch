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

if (!defined('_PS_VERSION_'))
	exit;

if (!defined('_ELASTICSEARCH_DIR_'))
	define('_ELASTICSEARCH_DIR_', dirname(__FILE__).'/');

if (!defined('_ELASTICSEARCH_URI_'))
	define('_ELASTICSEARCH_URI_', _MODULE_DIR_.'elasticsearch/');

if (!defined('_ELASTICSEARCH_CLASSES_DIR_'))
	define('_ELASTICSEARCH_CLASSES_DIR_', _ELASTICSEARCH_DIR_.'classes/');

if (!defined('_ELASTICSEARCH_CONTROLLERS_DIR_'))
	define('_ELASTICSEARCH_CONTROLLERS_DIR_', _ELASTICSEARCH_DIR_.'controllers/');

if (!defined('_ELASTICSEARCH_CSS_URI_'))
	define('_ELASTICSEARCH_CSS_URI_', _ELASTICSEARCH_URI_.'views/css/');

if (!defined('_ELASTICSEARCH_JS_URI_'))
	define('_ELASTICSEARCH_JS_URI_', _ELASTICSEARCH_URI_.'views/js/');

if (!defined('_ELASTICSEARCH_IMG_URI_'))
	define('_ELASTICSEARCH_IMG_URI_', _ELASTICSEARCH_URI_.'views/img/');

if (!defined('_ELASTICSEARCH_TEMPLATES_DIR_'))
	define('_ELASTICSEARCH_TEMPLATES_DIR_', _ELASTICSEARCH_DIR_.'views/templates/');

if (!defined('_ELASTICSEARCH_AJAX_URI_'))
	define('_ELASTICSEARCH_AJAX_URI_', _ELASTICSEARCH_URI_.'elasticsearch.ajax.php');

if (!defined('_ELASTICSEARCH_CATEGORY_SEPARATOR_'))
	define('_ELASTICSEARCH_CATEGORY_SEPARATOR_', '>');