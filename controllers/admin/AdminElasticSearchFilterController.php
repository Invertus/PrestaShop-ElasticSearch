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

class AdminElasticSearchFilterController extends ModuleAdminController
{
	public function __construct()
	{
		$this->meta_title = $this->l('Elastic Search filter settings');

		parent::__construct();

		$this->token = Tools::getAdminTokenLite('AdminModules');
		$this->table = 'elasticsearch_template';
		$this->class = 'ElasticSearchTemplate';

		self::$currentIndex = 'index.php?controller=AdminModules&configure='.$this->module->name.'&menu=filter';
	}

	public function updateOptions()
	{
		$this->table = 'Configuration';
		$this->class = 'Configuration';

		$this->processUpdateOptions();
	}

	public function initOptionFields()
	{
		$this->fields_options = array(
			'main_settings' => array(
				'title' => $this->l('Elastic Search filter settings'),
				'fields' => array(
					'ELASTICSEARCH_DISPLAY_FILTER' => array(
						'type' => 'bool',
						'title' => $this->l('Display products filter in page column')
					),
					'ELASTICSEARCH_HIDE_0_VALUES' => array(
						'type' => 'bool',
						'title' => $this->l('Hide filter values when no product is matching')
					),
					'ELASTICSEARCH_SHOW_QTIES' => array(
						'type' => 'bool',
						'title' => $this->l('Show the number of matching products')
					),
					'ELASTICSEARCH_FULL_TREE' => array(
						'type' => 'bool',
						'title' => $this->l('Show products from subcategories')
					),
					'ELASTICSEARCH_CATEGORY_DEPTH' => array(
						'title' => $this->l('Category filter depth'),
						'size' => 3,
						'cast' => 'intval',
						'validation' => 'isInt',
						'type' => 'text',
						'desc' => $this->l('0 for no limits')
					),
					'ELASTICSEARCH_PRICE_USETAX' => array(
						'type' => 'bool',
						'title' => $this->l('Use tax to filter price')
					)
				),
				'submit' => array('title' => $this->l('Save'))
			)
		);
	}

	public function initForm()
	{
		$categories = Category::getRootCategories((int)$this->context->language->id);
		$id_root = (int)$categories[0]['id_category'];

		$root_category = Shop::getContext() == Shop::CONTEXT_SHOP && Tools::isSubmit('id_shop') ? new Category((int)$this->context->shop->id_category) :
			new Category((int)$id_root);

		$this->object = new ElasticSearchTemplate();

		$this->fields_form = array(
			'legend' => array(
				'title' => $this->l('Manage filter template'),
				'icon' => 'icon-cogs'
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Template name'),
					'name' => 'elasticsearch_tpl_name',
					'required' => true,
					'desc' => $this->l('Only as a reminder')
				),
				array(
					'type' => 'categories',
					'name' => 'categoryBox',
					'label' => $this->l(' Categories used for this template'),
					'required' => true,
					'tree' => array(
						'id' => 'categories-tree',
						'selected_categories' => $this->getSelectedCategories(),
						'root_category' => $root_category->id,
						'use_search' => false,
						'use_checkbox' => true
					),
				)
			),
			'submit' => array(
				'title' => $this->l('Save'),
			)
		);

		if (Shop::isFeatureActive())
		{
			$this->fields_form['input'][] = array(
				'type' => 'shop',
				'label' => $this->l('Shop association'),
				'name' => 'checkBoxShopAsso',
				'required' => true
			);
		}

		$this->fields_form['input'][] = array(
			'type' => 'free',
			'label' => $this->l('Template settings'),
			'required' => true,
			'name' => 'templateSettingsManagement'
		);

		$this->fields_value['templateSettingsManagement'] = $this->displayFilterTemplateManagemetList();
	}

	private function getSelectedCategories()
	{
		$elasticsearch_template = new ElasticSearchTemplate((int)Tools::getValue('id_elasticsearch_template'));

		if (!Validate::isLoadedObject($elasticsearch_template))
		{
			$this->fields_value['elasticsearch_tpl_name'] = sprintf($this->l('My template %s'), date('Y-m-d'));
			$this->context->smarty->assign('elasticsearch_selected_shops', '');
			return array();
		}

		$this->object = $elasticsearch_template;
		$this->identifier = 'id_elasticsearch_template';
		$filters = unserialize($elasticsearch_template->filters);
		$this->fields_value['elasticsearch_tpl_name'] = $elasticsearch_template->name;
		$return = $filters['categories'];
		$elasticsearch_selected_shops = '';

		foreach ($filters['shop_list'] as $id_shop)
			$elasticsearch_selected_shops .= $id_shop.', ';

		$elasticsearch_selected_shops = Tools::substr($elasticsearch_selected_shops, 0, -2);

		$this->context->smarty->assign('elasticsearch_selected_shops', $elasticsearch_selected_shops);

		unset($filters['categories']);
		unset($filters['shop_list']);

		$this->context->smarty->assign('filters', Tools::jsonEncode($filters));

		return $return;
	}

	private function displayFilterTemplateManagemetList()
	{
		$attribute_groups = ElasticSearchTemplate::getAttributes();
		$features = ElasticSearchTemplate::getFeatures();
		$module_instance = Module::getInstanceByName('elasticsearch');

		$this->context->smarty->assign(array(
			'current_url' => $module_instance->module_url,
			'id_elasticsearch_template' => 0,
			'attribute_groups' => $attribute_groups,
			'features' => $features,
			'total_filters' => 6 + count($attribute_groups) + count($features)
		));

		return $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'admin/templates_management_list.tpl');
	}
}