<?php
/**
 * Copyright (c) 2015 Invertus, JSC
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

if (!defined('_PS_VERSION_'))
    exit;

if (!defined('_ELASTICSEARCH_DIR_'))
    define('_ELASTICSEARCH_DIR_', dirname(__FILE__).'/');

if (!defined('_ELASTICSEARCH_URI_'))
    define('_ELASTICSEARCH_URI_', _MODULE_DIR_.'elasticsearch/');

if (!defined('_ELASTICSEARCH_CLASSES_DIR_'))
    define('_ELASTICSEARCH_CLASSES_DIR_', _ELASTICSEARCH_DIR_.'classes/');

if (!defined('_ELASTICSEARCH_CORE_DIR_'))
    define('_ELASTICSEARCH_CORE_DIR_', _ELASTICSEARCH_CLASSES_DIR_.'core/');

if (!defined('_ELASTICSEARCH_VENDOR_DIR_'))
    define('_ELASTICSEARCH_VENDOR_DIR_', _ELASTICSEARCH_DIR_.'vendor/');

if (!defined('_ELASTICSEARCH_CONTROLLERS_DIR_'))
    define('_ELASTICSEARCH_CONTROLLERS_DIR_', _ELASTICSEARCH_DIR_.'controllers/');

if (!defined('_ELASTICSEARCH_CSS_URI_'))
    define('_ELASTICSEARCH_CSS_URI_', _ELASTICSEARCH_URI_.'views/css/');

if (!defined('_ELASTICSEARCH_JS_URI_'))
    define('_ELASTICSEARCH_JS_URI_', _ELASTICSEARCH_URI_.'views/js/');

if (!defined('_ELASTICSEARCH_TEMPLATES_DIR_'))
    define('_ELASTICSEARCH_TEMPLATES_DIR_', _ELASTICSEARCH_DIR_.'views/templates/');

if (!defined('_ELASTICSEARCH_AJAX_URI_'))
    define('_ELASTICSEARCH_AJAX_URI_', _ELASTICSEARCH_URI_.'elasticsearch.ajax.php');

if (!defined('_ELASTICSEARCH_CATEGORY_SEPARATOR_'))
    define('_ELASTICSEARCH_CATEGORY_SEPARATOR_', '>');