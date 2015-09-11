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
//todo refactor class name
//	require_once(_ELASTICSEARCH_CLASSES_DIR_.'ElasticSearchService.php');
    require_once(_ELASTICSEARCH_CLASSES_DIR_.'ReworkedElasticSearchFilter.php');

    $filter = new ReworkedElasticSearchFilter();
    $result = $filter->ajaxCall();

    die(Tools::jsonEncode($result));
}