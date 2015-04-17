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

require_once('elasticsearch.config.php');
require_once(_ELASTICSEARCH_CLASSES_DIR_.'ElasticSearchTemplate.php');

class ElasticSearch extends Module
{
	const SETTINGS_CLASSNAME = 'AdminElasticSearchSettings';
	const FILTER_CLASSNAME = 'AdminElasticSearchFilter';

	protected $config_form = false;
	public $errors = array();
	private $html = '';
	public $module_url;

	public function __construct()
	{
		$this->name = 'elasticsearch';
		$this->tab = 'front_office_features';
		$this->version = '1.1.0';
		$this->author = 'Invertus';
		$this->need_instance = 0;
		$this->bootstrap = version_compare(_PS_VERSION_, '1.6', '>');

		parent::__construct();

		$this->displayName = $this->l('BRAD');
		$this->description = $this->l('Elasticsearch® module for PrestaShop that makes PrestaShop search and filter significantly faster.');

		if (defined('_PS_ADMIN_DIR_'))
			$this->module_url = 'index.php?controller=AdminModules&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules');
	}

	public function install()
	{
		if (!$this->installTab($this->l('Settings'), -1, self::SETTINGS_CLASSNAME) ||
			!$this->installTab($this->l('Filter'), -1, self::FILTER_CLASSNAME))
		{
			$this->_errors[] = $this->l('Could not install module tab');
			return false;
		}

		return parent::install() &&
			$this->registerHooks() &&
			$this->setDefaultConfiguration() &&
			$this->installDatabaseTables();
	}

	private function installDatabaseTables()
	{
		return DB::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'elasticsearch_template` (
				`id_elasticsearch_template` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`name` varchar(64) NOT NULL,
				`filters` text,
				`n_categories` int(10) unsigned NOT NULL,
				`date_add` datetime NOT NULL,
			PRIMARY KEY (`id_elasticsearch_template`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8
		') && DB::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'elasticsearch_category` (
				`id_elasticsearch_category` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`id_shop` int(11) unsigned NOT NULL,
				`id_category` int(10) unsigned NOT NULL,
				`id_value` int(10) unsigned DEFAULT "0",
				`type` enum("category","id_feature","id_attribute_group","quantity","condition","manufacturer","weight","price") NOT NULL,
				`position` int(10) unsigned NOT NULL,
				`filter_type` int(10) unsigned NOT NULL DEFAULT "0",
				`filter_show_limit` int(10) unsigned NOT NULL DEFAULT "0",
				`date_add` varchar(20) NOT NULL,
			PRIMARY KEY (`id_elasticsearch_category`),
			KEY `id_category` (`id_category`,`type`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8
		') && DB::getInstance()->execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'elasticsearch_template_shop` (
				`id_elasticsearch_template` int(10) unsigned NOT NULL,
				`id_shop` int(11) unsigned NOT NULL,
			PRIMARY KEY (`id_elasticsearch_template`,`id_shop`),
			KEY `id_shop` (`id_shop`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8
		');
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
			'ELASTICSEARCH_HOST' => 'example.com:9200',
			'ELASTICSEARCH_SEARCH_DISPLAY' => '1',
			'ELASTICSEARCH_DISPLAY_FILTER' => '1',
			'ELASTICSEARCH_HIDE_0_VALUES' => '0',
			'ELASTICSEARCH_SHOW_QTIES' => '1',
			'ELASTICSEARCH_FULL_TREE' => '0',
			'ELASTICSEARCH_PRICE_USETAX' => '1',
			'ELASTICSEARCH_CATEGORY_DEPTH' => '1'
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
		return
			$this->registerHook('displayLeftColumn') &&
			$this->registerHook('displayTop') &&
			$this->registerHook('actionObjectProductAddAfter') &&
			$this->registerHook('actionObjectProductUpdateAfter') &&
			$this->registerHook('actionObjectProductDeleteAfter');
	}

	public function uninstall()
	{
		if (!parent::uninstall() || !$this->uninstallTab(self::SETTINGS_CLASSNAME))
			return false;

		$search = $this->getElasticSearchServiceObject();

		if ($search->testElasticSearchServiceConnection())
			$search->deleteShopIndex();

		return $this->deleteConfiguration() && $this->dropDatabaseTables();
	}

	private function dropDatabaseTables()
	{
		return DB::getInstance()->execute('
			DROP TABLE IF EXISTS
				`'._DB_PREFIX_.'elasticsearch_template`,
				`'._DB_PREFIX_.'elasticsearch_category`,
				`'._DB_PREFIX_.'elasticsearch_template_shop`
		');
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
		Module::$classInModule['AdminElasticSearchSettingscontroller'] = false;
		Module::$classInModule['AdminElasticSearchFiltercontroller'] = false;

		$this->submitActions();
		$this->displayNavigation();

		switch (Tools::getValue('menu'))
		{
			case 'filter':
				$this->displayFilterTemplatesList();
				$this->displayFilterSettings();
				break;
			case 'manage_filter_template':
				$this->context->controller->addCSS(_ELASTICSEARCH_CSS_URI_.'template_management.css');
				$this->context->controller->addJS(_ELASTICSEARCH_JS_URI_.'template_management.js');
				$this->context->controller->addjqueryPlugin('sortable');
				$this->displayFilterTemplateManagement();
				break;
			default:
				$this->displayConfiguration();
				break;
		}

		$this->displayIndexingBlock();

		return $this->html;
	}

	private function displayNavigation()
	{
		$this->context->smarty->assign('menutabs', $this->initNavigation());
		$this->html .= $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'admin/navigation.tpl');
	}

	private function initNavigation()
	{
		$menu_tabs = array(
			'search' => array(
				'short' => 'Search',
				'desc' => $this->l('Products search'),
				'href' => $this->module_url.'&menu=search',
				'active' => false,
				'imgclass' => 'icon-search'
			),
			'filter' => array(
				'short' => 'Filter',
				'desc' => $this->l('Products filter'),
				'href' => $this->module_url.'&menu=filter',
				'active' => false,
				'imgclass' => 'icon-filter'
			)
		);

		$available_pages = array_keys($menu_tabs);
		$current_page = Tools::getValue('menu', reset($available_pages));

		if (in_array($current_page, array_keys($menu_tabs)))
			$menu_tabs[$current_page]['active'] = true;

		if (Tools::getValue('menu') == 'manage_filter_template')
			$menu_tabs['filter']['active'] = true;

		return $menu_tabs;
	}

	private function submitActions()
	{
		if (Tools::isSubmit('startIndexing'))
			$this->indexProducts();
		elseif (Tools::isSubmit('continueIndexing'))
			$this->indexProducts(false);
		elseif (Tools::isSubmit('submitAddelasticsearch_template'))
			$this->saveFilterTemplate();
		elseif (Tools::isSubmit('deleteFilterTemplate'))
			$this->deleteFilterTemplate();
	}

	private function deleteFilterTemplate()
	{
		$id_elasticsearch_template = (int)Tools::getValue('id_elasticsearch_template');

		$elasticsearch_template = new ElasticSearchTemplate((int)$id_elasticsearch_template);

		if (Validate::isLoadedObject($elasticsearch_template))
		{
			$elasticsearch_template->delete();

			$this->buildLayeredCategories();
			$this->html .= $this->displayConfirmation($this->l('Filter template deleted, categories updated (reverted to default Filter template)'));
		}
		else
			$this->html .= $this->displayError($this->l('Filter template not found'));
	}

	private function saveFilterTemplate()
	{
		if (!Tools::getValue('elasticsearch_tpl_name'))
			$this->html .= $this->displayError($this->l('Filter template name required (cannot be empty)'));
		elseif (!Tools::getValue('categoryBox'))
			$this->html .= $this->displayError($this->l('You must select at least one category.'));
		else
		{
			if (Tools::getValue('id_elasticsearch_template'))
			{
				Db::getInstance()->execute('
					DELETE FROM `'._DB_PREFIX_.'elasticsearch_template`
					WHERE `id_elasticsearch_template` = "'.(int)Tools::getValue('id_elasticsearch_template').'"
				');

				$this->buildLayeredCategories();
			}

			if (Tools::getValue('scope') == 1)
			{
				Db::getInstance()->execute('TRUNCATE TABLE '._DB_PREFIX_.'elasticsearch_template');
				$categories = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
					SELECT id_category
					FROM '._DB_PREFIX_.'category'
				);

				foreach ($categories as $category)
					$_POST['categoryBox'][] = (int)$category['id_category'];
			}

			$id_elasticsearch_template = (int)Tools::getValue('id_elasticsearch_template');

			if (!$id_elasticsearch_template)
				$id_elasticsearch_template = (int)Db::getInstance()->Insert_ID();

			$shop_list = array();

			$assos = array();

			if ($selected_shops = Tools::getValue('checkBoxShopAsso_elasticsearch_template'))
			{
				foreach (array_keys($selected_shops) as $id_shop)
				{
					$assos[] = array('id_object' => (int)$id_elasticsearch_template, 'id_shop' => (int)$id_shop);
					$shop_list[] = (int)$id_shop;
				}
			}
			else
				$shop_list = array(Context::getContext()->shop->id);

			Db::getInstance()->execute('
				DELETE FROM '._DB_PREFIX_.'elasticsearch_template_shop
				WHERE `id_elasticsearch_template` = '.(int)$id_elasticsearch_template
			);

			if (count(Tools::getValue('categoryBox')))
			{
				/* Clean categoryBox before use */
				if (is_array(Tools::getValue('categoryBox')))
					foreach (Tools::getValue('categoryBox') as &$category_box_tmp)
						$category_box_tmp = (int)$category_box_tmp;

				$filter_values = array();

				foreach (Tools::getValue('categoryBox') as $idc)
					$filter_values['categories'][] = (int)$idc;

				$filter_values['shop_list'] = $shop_list;

				foreach (Tools::getValue('categoryBox') as $id_category_elasticsearch)
				{
					foreach ($_POST as $key => $value)
						if (Tools::substr($key, 0, 23) == 'elasticsearch_selection' && $value == 'on')
						{
							$type = 0;
							$limit = 0;

							if (Tools::getValue($key.'_filter_type'))
								$type = Tools::getValue($key.'_filter_type');
							if (Tools::getValue($key.'_filter_show_limit'))
								$limit = Tools::getValue($key.'_filter_show_limit');

							$filter_values[$key] = array(
								'filter_type' => (int)$type,
								'filter_show_limit' => (int)$limit
							);
						}
				}

				$values_to_insert = array(
					'name' => pSQL(Tools::getValue('elasticsearch_tpl_name')),
					'filters' => pSQL(serialize($filter_values)),
					'n_categories' => (int)count($filter_values['categories']),
					'date_add' => date('Y-m-d H:i:s'));

				if (Tools::getValue('id_elasticsearch_template'))
					$values_to_insert['id_elasticsearch_template'] = (int)Tools::getValue('id_elasticsearch_template');

				Db::getInstance()->autoExecute(_DB_PREFIX_.'elasticsearch_template', $values_to_insert, 'INSERT');
				$id_elasticsearch_template = (int)Db::getInstance()->Insert_ID();

				if (!empty($assos))
					foreach ($assos as $asso)
						Db::getInstance()->execute('
							INSERT INTO '._DB_PREFIX_.'elasticsearch_template_shop
								(`id_elasticsearch_template`, `id_shop`)
							VALUES
								('.$id_elasticsearch_template.', '.(int)$asso['id_shop'].')'
						);

				$this->buildLayeredCategories();
				$this->html .= $this->displayConfirmation($this->l('Your filter template saved successfully'));
			}
		}
	}

	public function buildLayeredCategories()
	{
		$res = Db::getInstance()->executeS('
			SELECT *
			FROM '._DB_PREFIX_.'elasticsearch_template
			ORDER BY `date_add` DESC
		');

		$categories = array();
		Db::getInstance()->execute('TRUNCATE '._DB_PREFIX_.'elasticsearch_category');

		if (!count($res))
			return true;

		$values = false;
		$sql_to_insert = '
			INSERT INTO '._DB_PREFIX_.'elasticsearch_category
				(`id_category`, `id_shop`, `id_value`, `type`, `position`, `filter_show_limit`, `filter_type`, `date_add`)
			VALUES ';

		foreach ($res as $filter_template)
		{
			$data = Tools::unSerialize($filter_template['filters']);

			foreach ($data['shop_list'] as $id_shop)
			{
				if (!isset($categories[$id_shop]))
					$categories[$id_shop] = array();

				foreach ($data['categories'] as $id_category)
				{
					$n = 0;

					if (!in_array($id_category, $categories[$id_shop]))
					{
						$categories[$id_shop][] = $id_category;

						foreach ($data as $key => $value)
							if (Tools::substr($key, 0, 24) == 'elasticsearch_selection_')
							{
								$values = true;
								$type = $value['filter_type'];
								$limit = $value['filter_show_limit'];
								$n++;

								if ($key == 'elasticsearch_selection_stock')
									$sql_to_insert .= '('.(int)$id_category.', '.(int)$id_shop.', NULL, "quantity", '.(int)$n.', '.(int)$limit.', '.
										(int)$type.', "'.date('Y-m-d H:i:s').'"),';
								else if ($key == 'elasticsearch_selection_subcategories')
									$sql_to_insert .= '('.(int)$id_category.', '.(int)$id_shop.', NULL, "category", '.(int)$n.', '.(int)$limit.', '.
										(int)$type.', "'.date('Y-m-d H:i:s').'"),';
								else if ($key == 'elasticsearch_selection_condition')
									$sql_to_insert .= '('.(int)$id_category.', '.(int)$id_shop.', NULL, "condition", '.(int)$n.', '.(int)$limit.', '.
										(int)$type.', "'.date('Y-m-d H:i:s').'"),';
								else if ($key == 'elasticsearch_selection_weight_slider')
									$sql_to_insert .= '('.(int)$id_category.', '.(int)$id_shop.', NULL, "weight", '.(int)$n.', '.(int)$limit.', '.
										(int)$type.', "'.date('Y-m-d H:i:s').'"),';
								else if ($key == 'elasticsearch_selection_price_slider')
									$sql_to_insert .= '('.(int)$id_category.', '.(int)$id_shop.', NULL, "price", '.(int)$n.', '.(int)$limit.', '.
										(int)$type.', "'.date('Y-m-d H:i:s').'"),';
								else if ($key == 'elasticsearch_selection_manufacturer')
									$sql_to_insert .= '('.(int)$id_category.', '.(int)$id_shop.', NULL, "manufacturer", '.(int)$n.', '.(int)$limit.', '.
										(int)$type.', "'.date('Y-m-d H:i:s').'"),';
								else if (Tools::substr($key, 0, 27) == 'elasticsearch_selection_ag_')
									$sql_to_insert .= '('.(int)$id_category.', '.(int)$id_shop.', '.(int)str_replace('elasticsearch_selection_ag_', '', $key).
										', "id_attribute_group", '.(int)$n.', '.(int)$limit.', '.(int)$type.', "'.date('Y-m-d H:i:s').'"),';
								else if (Tools::substr($key, 0, 29) == 'elasticsearch_selection_feat_')
									$sql_to_insert .= '('.(int)$id_category.', '.(int)$id_shop.', '.(int)str_replace('elasticsearch_selection_feat_', '', $key).
										', "id_feature", '.(int)$n.', '.(int)$limit.', '.(int)$type.', "'.date('Y-m-d H:i:s').'"),';
							}
					}
				}
			}
		}

		if ($values)
			DB::getInstance()->execute(rtrim($sql_to_insert, ','));

		return null;
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

	private function addTreeJs()
	{
		$admin_webpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
		$admin_webpath = preg_replace('/^'.preg_quote(DIRECTORY_SEPARATOR, '/').'/', '', $admin_webpath);
		$bo_theme = ((Validate::isLoadedObject($this->context->employee)
			&& $this->context->employee->bo_theme) ? $this->context->employee->bo_theme : 'default');

		if (!file_exists(_PS_BO_ALL_THEMES_DIR_.$bo_theme.DIRECTORY_SEPARATOR.'template'))
			$bo_theme = 'default';

		$js_path = __PS_BASE_URI__.$admin_webpath.'/themes/'.$bo_theme.'/js/tree.js';

		$this->context->controller->addJS($js_path);
	}

	private function displayFilterTemplateManagement()
	{
		$this->addTreeJs();

		require_once(_ELASTICSEARCH_CONTROLLERS_DIR_.'admin/AdminElasticSearchFilterController.php');

		$controller = new AdminElasticSearchFilterController();
		$this->displayConnectionError();
		$controller->initForm();
		$this->html .= $controller->renderForm();
	}

	private function displayFilterTemplatesList()
	{
		$this->context->smarty->assign(array(
			'current_url' => $this->module_url,
			'filters_templates' => ElasticSearchTemplate::getList()
		));

		$this->html .= $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'admin/filters_list.tpl');
	}

	private function displayFilterSettings()
	{
		require_once(_ELASTICSEARCH_CONTROLLERS_DIR_.'admin/AdminElasticSearchFilterController.php');

		$controller = new AdminElasticSearchFilterController();
		$controller->initOptionFields();

		if (Tools::isSubmit('submitOptionselasticsearch_template'))
		{
			$controller->updateOptions();

			if (!empty($controller->errors))
				$this->html .= $this->displayError(implode('<br />', $controller->errors));
			else
				$this->html .= $this->displayConfirmation($this->l('Filter settings saved successfully'));
		}

		$this->displayConnectionError();

		$controller->table = 'elasticsearch_template';
		$controller->class = 'ElasticSearchTemplate';
		$this->html .= $controller->renderOptions();
	}

	private function displayConnectionError()
	{
		$search = $this->getElasticSearchServiceObject();

		if (!$search->testElasticSearchServiceConnection())
			$this->html .= $this->displayError($this->l('Connection to elastic search service is unavailable'));
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
				$this->html .= $this->displayConfirmation($this->l('Search settings saved successfully'));
		}

		$this->displayConnectionError();

		$this->html .= $controller->renderOptions();
	}

	private function displayIndexingBlock()
	{
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
		require_once(_ELASTICSEARCH_CONTROLLERS_DIR_.'front/elasticsearch.php');

		$controller = new ElasticSearchElasticSearchModuleFrontController();
		$controller->initContent();
		$this->context->smarty->assign('no_pagination', true);

		return $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'front/elasticsearch.tpl');
	}

	public function hookDisplayTop()
	{
		if (!Configuration::get('ELASTICSEARCH_SEARCH_DISPLAY'))
			return '';

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

	public function hookDisplayLeftColumn()
	{
		if (!Configuration::get('ELASTICSEARCH_DISPLAY_FILTER'))
			return '';

		$search = $this->getElasticSearchServiceObject();

		if (!$search->testElasticSearchServiceConnection())
			return '';

		require_once(_ELASTICSEARCH_CLASSES_DIR_.'ElasticSearchFilter.php');

		$elasticsearch_filter = new ElasticSearchFilter();

		if (!$elasticsearch_filter->generateFiltersBlock($elasticsearch_filter->getSelectedFilters()))
			return '';

		$this->context->controller->addCSS(_ELASTICSEARCH_CSS_URI_.$this->name.'.css');
		$this->context->controller->addJS(_ELASTICSEARCH_JS_URI_.'filter.js');
		$this->context->controller->addJS(_PS_JS_DIR_.'jquery/jquery-ui-1.8.10.custom.min.js');
		$this->context->controller->addJQueryUI('ui.slider');
		$this->context->controller->addCSS(_PS_CSS_DIR_.'jquery-ui-1.8.10.custom.css');
		$this->context->controller->addCSS(_ELASTICSEARCH_CSS_URI_.'filter.css');
		$this->context->controller->addJQueryPlugin('scrollTo');

		return $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'hook/column.tpl');
	}
}