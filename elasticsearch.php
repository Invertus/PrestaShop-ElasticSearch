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

require_once('elasticsearch.config.php');

class ElasticSearch extends Module
{
	const SETTINGS_CLASSNAME = 'AdminElasticSearchSettings';

	protected $config_form = false;
	public $errors = array();
	private $html = '';

	public function __construct()
	{
		$this->name = 'elasticsearch';
		$this->tab = 'front_office_features';
		$this->version = '1.0.0';
		$this->author = 'Invertus';
		$this->need_instance = 0;
		$this->bootstrap = version_compare(_PS_VERSION_, '1.6', '>');

		parent::__construct();

		$this->displayName = $this->l('Elastic Search');
		$this->description = $this->l('Extremely quick search on PrestaShop');
	}

	public function install()
	{
		if (!$this->installTab($this->l('Settings'), -1, self::SETTINGS_CLASSNAME))
		{
			$this->_errors[] = $this->l('Could not install module settings tab');
			return false;
		}

		return parent::install() &&
			$this->registerHooks() &&
			$this->setDefaultConfiguration();
	}

	private function setDefaultConfiguration()
	{
		$configuration = $this->getDefaultConfiguration();

		foreach ($configuration as $setting => $value)
			if (!Configuration::updateValue($setting, $value))
				return false;

		return true;
	}

	private function getDefaultConfiguration()
	{
		return array(
			'ELASTICSEARCH_SEARCH' => '1',
			'ELASTICSEARCH_SEARCH_COUNT' => '10',
			'ELASTICSEARCH_SEARCH_MIN' => '3',
			'ELASTICSEARCH_AJAX_SEARCH' => '1',
			'ELASTICSEARCH_HOST' => 'example.com:9200'
		);
	}

	private function installTab($name, $id_parent, $class_name)
	{
		if (!Tab::getIdFromClassName($class_name))
		{
			$module_tab = new Tab;

			foreach (Language::getLanguages(true) as $language)
				$module_tab->name[$language['id_lang']] = $name;

			$module_tab->class_name = $class_name;
			$module_tab->id_parent = $id_parent;
			$module_tab->module = $this->name;

			if (!$module_tab->add())
				return false;

			return $module_tab->id;
		}

		return true;
	}

	private function registerHooks()
	{
		return $this->registerHook('displayLeftColumn') &&
			$this->registerHook('displayTop') &&
			$this->registerHook('actionObjectProductAddAfter') &&
			$this->registerHook('actionObjectProductUpdateAfter') &&
			$this->registerHook('actionObjectProductDeleteAfter');
	}

	public function uninstall()
	{
		$result = parent::uninstall() && $this->uninstallTab(self::SETTINGS_CLASSNAME);

		if (!$result)
			return false;

		$search = $this->getElasticSearchServiceObject();

		if ($search->testElasticSearchServiceConnection())
			$search->deleteShopIndex();

		return $this->deleteConfiguration();
	}

	private function deleteConfiguration()
	{
		$configuration = $this->getDefaultConfiguration();

		foreach (array_keys($configuration) as $setting)
			if (!Configuration::deleteByName($setting))
				return false;

		return true;
	}

	private function uninstallTab($class_name)
	{
		if ($id_tab = (int)Tab::getIdFromClassName($class_name))
		{
			$tab = new Tab((int)$id_tab);

			return $tab->delete();
		}

		return true;
	}

	public function getContent()
	{
		$this->submitActions();
		$this->displayConfiguration();

		return $this->html;
	}

	private function submitActions()
	{
		if (Tools::isSubmit('startIndexing'))
			$this->indexProducts();
		elseif (Tools::isSubmit('continueIndexing'))
			$this->indexProducts(false);
	}

	private function indexProducts($delete_old = true)
	{
		$search = $this->getElasticSearchServiceObject();
		$search->indexAllProducts($delete_old);

		if (!$search->errors)
			$this->html .= $this->displayConfirmation($this->l('Products indexed successfully'));
		else
			$this->html .= $this->renderErrors($search->errors);
	}

	private function displayConfiguration()
	{
		require_once(_ELASTICSEARCH_CONTROLLERS_DIR_.'admin/AdminElasticSearchSettingsController.php');

		$controller = new AdminElasticSearchSettingsController();

		if (Tools::isSubmit('submitOptionsConfiguration'))
		{
			$controller->updateOptions();

			if (!empty($controller->errors))
				$this->html .= $this->displayError(implode('<br />', $controller->errors));
			else
				$this->html .= $this->displayConfirmation($this->l('Module settings saved successfully'));
		}

		$search = $this->getElasticSearchServiceObject();

		if (!$search->testElasticSearchServiceConnection())
			$this->html .= $this->displayError($this->l('Connection to elastic search service is unavailable'));

		$this->html .= $controller->renderOptions();
		$this->calculateIndexedProducts();

		if (Shop::getContext() != Shop::CONTEXT_SHOP)
			$this->context->smarty->assign('shop_restriction', true);

		$this->html .= $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'admin/form.tpl');
	}

	private function calculateIndexedProducts()
	{
		$indexed_products = 0;
		$search = $this->getElasticSearchServiceObject();
		$all_products = count($search->getAllProducts((int)$this->context->shop->id));

		if ($search->testElasticSearchServiceConnection())
		{
			$index = $search->index_prefix.(int)$this->context->shop->id;
			$type = 'products';
			$property = 'all';
			$query = $search->buildSearchQuery($property, '');
			$indexed_products = $search->search($index, $type, $query, null, null, null, null);
		}

		$this->context->smarty->assign(array(
			'indexed_products' => $indexed_products,
			'all_products' => $all_products
		));
	}

	public function submitSearchQuery()
	{
		$search_term = Tools::getValue('search_query');
		$type = 'products';
		$property = 'search_keywords_'.(int)$this->context->language->id;
		$search = $this->getElasticSearchServiceObject();
		$query = $search->buildSearchQuery($property, $search_term);
		$results_count = (int)Configuration::get('ELASTICSEARCH_SEARCH_COUNT');
		$index = $search->index_prefix.(int)$this->context->shop->id;
		$result = $search->search($index, $type, $query, $results_count);
		$search_result = array();

		if ($result)
			foreach ($result as $product)
			{
				$product_obj = new Product($product['_id'], null, (int)$this->context->language->id);

				if (!Validate::isLoadedObject($product_obj))
					continue;

				$category_obj = new Category((int)$product_obj->id_category_default, (int)$this->context->language->id);
				$search_result[] = array(
					'name' => $product['_source']['name_'.$this->context->language->id],
					'uri' => $this->context->link->getProductLink($product_obj, null, $product_obj->category),
					'category' => $category_obj->name
				);
			}

		$this->context->smarty->assign('links', $search_result);
		$results = $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'front/searchInputResultsList.tpl');

		return $results;
	}

	public function renderErrors($errors)
	{
		return $this->displayError(implode('<br />', $errors));
	}

	public function getElasticSearchServiceObject()
	{
		require_once(_ELASTICSEARCH_CLASSES_DIR_.'ElasticSearchService.php');

		return new ElasticSearchService();
	}

	public function hookDisplayTop()
	{
		$search = $this->getElasticSearchServiceObject();

		if (!$search->testElasticSearchServiceConnection())
			return '';

		$this->context->controller->addCSS(_ELASTICSEARCH_CSS_URI_.$this->name.'.css');

		if (Configuration::get('ELASTICSEARCH_SEARCH'))
			$this->context->controller->addJS(_ELASTICSEARCH_JS_URI_.$this->name.'.js');

		if (Configuration::get('ELASTICSEARCH_AJAX_SEARCH'))
			$this->context->controller->addJS(_ELASTICSEARCH_JS_URI_.'ajaxsearch.js');

		return $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'hook/top.tpl');
	}

	public function hookActionObjectProductAddAfter($params)
	{
		$product = new Product((int)$params['object']->id);

		if (!Validate::isLoadedObject($product))
			return true;

		return $this->reindexProduct((int)$params['object']->id);
	}

	public function hookActionObjectProductUpdateAfter($params)
	{
		return $this->reindexProduct((int)$params['object']->id);
	}

	public function hookActionObjectProductDeleteAfter($params)
	{
		$product = new Product((int)$params['object']->id, false, null, $this->context->shop->id);

		$search = $this->getElasticSearchServiceObject();

		if (!$search->testElasticSearchServiceConnection())
			return true;

		if (Validate::isLoadedObject($product))
			return $search->deleteDocumentById($search->index_prefix.$this->context->shop->id, (int)$params['object']->id);

		foreach (Shop::getShops(false, null, true) as $id_shop)
			if (!$search->deleteDocumentById($search->index_prefix.$id_shop, (int)$params['object']->id))
				return false;

		return true;
	}

	private function reindexProduct($id_product)
	{
		$search = $this->getElasticSearchServiceObject();

		if (!$search->testElasticSearchServiceConnection())
			return false;

		//Deleting the document first
		$search->deleteDocumentById($search->index_prefix.$this->context->shop->id, $id_product);

		//Generating a new body for deleted document
		$body = $search->generateSearchBodyByProduct($id_product);

		//creating a document with newly generated body
		return $search->createDocument($search->index_prefix.$this->context->shop->id, $body, (int)$id_product);
	}

	public function processAjaxSearch()
	{
		require_once(_PS_MODULE_DIR_.$this->name.'/controllers/front/elasticsearch.php');
		$controller = new ElasticSearchElasticSearchModuleFrontController();
		$controller->initContent();
		$this->context->smarty->assign('no_pagination', true);

		return $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'front/elasticsearch.tpl');
	}
}