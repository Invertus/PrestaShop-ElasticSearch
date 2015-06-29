<?php

require_once(_ELASTICSEARCH_CORE_DIR_.'SearchService.php');
require_once(_ELASTICSEARCH_CORE_DIR_.'AbstractLogger.php');

abstract class AbstractFilter extends Brad\AbstractLogger
{
	/* Available filters types */
	const FILTER_TYPE_PRICE = 'price';
	const FILTER_TYPE_WEIGHT = 'weight';
	const FILTER_TYPE_CONDITION = 'condition';
	const FILTER_TYPE_QUANTITY = 'quantity';
	const FILTER_TYPE_MANUFACTURER = 'manufacturer';
	const FILTER_TYPE_ATTRIBUTE_GROUP = 'id_attribute_group';
	const FILTER_TYPE_FEATURE = 'id_feature';

	private static $search_service;
	private $filters_block;

	public function __construct($service_type)
	{
		self::$search_service = SearchService::getInstance($service_type);
	}

	/**
	 * @param $id_category int ID of category for which this filters block should be generated
	 * @param $extra_filters array extra filters to be added to filters block - optional
	 * @return string html of filters block
	 */
	public function generateFiltersBlock($id_category, array $extra_filters = array())
	{
		$enabled_filters = $this->getEnabledFiltersByCategory($id_category);
		$filters = array();

		foreach ($enabled_filters as $enabled_filter)
		{
			$filter = array();

			/* Getting filters by types */
			switch ($enabled_filter['type'])
			{
				case self::FILTER_TYPE_PRICE:
					$filter = $this->getPriceFilter();
					break;
				case self::FILTER_TYPE_WEIGHT:
					$filter = $this->getWeightFilter();
					break;
				case self::FILTER_TYPE_CONDITION:
					$filter = $this->getConditionFilter(array_unique($enabled_filter['values']));
					break;
				case self::FILTER_TYPE_QUANTITY:
					$filter = $this->getQuantityFilter(array_unique($enabled_filter['values']));
					break;
				case self::FILTER_TYPE_MANUFACTURER:
					$filter = $this->getManufacturerFilter(array_unique($enabled_filter['values']));
					break;
				case self::FILTER_TYPE_ATTRIBUTE_GROUP:
					$filter = $this->getAttributeGroupFilter(array_unique($enabled_filter['values']));
					break;
				case self::FILTER_TYPE_FEATURE:
					$filter = $this->getFeatureFilter(array_unique($enabled_filter['values']));
					break;
			}

			//Merging filters to one array
			if ($filter)
				$filters = array_merge($filters, $filter);
		}

		//adding extra filters
		if ($extra_filters)
			$filters = array_merge($filters, $extra_filters);

		$this->sortFilters($filters);

		Context::getContext()->smarty->assign(array(
			'filters' => $filters
		));

		return Context::getContext()->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'hook/column.tpl');
	}

	private function sortFilters(&$filters)
	{//todo

	}

	/**
	 * This method is called when filtering is processed (e.g. user selects a filter)
	 * @return array all variables that are needed to display filters page
	 */
	public function ajaxCall()
	{//todo finish this method
		$id_category = (int)Tools::getValue('id_elasticsearch_category');
		$category = new Category($id_category, (int)Context::getContext()->cookie->id_lang);
		$products_per_page_default = (int)Configuration::get('PS_PRODUCTS_PER_PAGE');
		$n_array = $products_per_page_default > 0 ?
			array($products_per_page_default, $products_per_page_default * 2, $products_per_page_default * 3, $products_per_page_default * 5) :
			array(10, 20, 50);

		$pagination_variables = $this->getPaginationVariables();

		Context::getContext()->smarty->assign(array_merge(
			array(
				'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
				'category' => $category,
				'n_array' => $n_array,
				'comparator_max_item' => (int)Configuration::get('PS_COMPARATOR_MAX_ITEM'),
				'products' => $this->getProductsBySelectedFilters($this->getSelectedFilters()),
				'products_per_page' => $products_per_page_default,
				'static_token' => Tools::getToken(false),
				'page_name' => 'category',
				'nArray' => $n_array,
				'compareProducts' => CompareProduct::getCompareProducts((int)Context::getContext()->cookie->id_compare),
			),
			$pagination_variables
		));

		return array(
			'filtersBlock' => utf8_encode($this->getFiltersBlock($id_category)),
			'productList' => utf8_encode(
				$pagination_variables['nb_products'] == 0 ?
					$this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'hook/elasticsearch-filter-no-products.tpl') :
					$this->context->smarty->fetch(_PS_THEME_DIR_.'product-list.tpl')
			),
			'pagination' => $this->context->smarty->fetch(_PS_THEME_DIR_.'pagination.tpl'),
			'categoryCount' => file_exists(_PS_THEME_DIR_.'category-count.tpl') ?
				$this->context->smarty->fetch(_PS_THEME_DIR_.'category-count.tpl') : '',
			'current_friendly_url' => $this->getCurrentFriendlyUrl(),
			'filters' => $this->getFiltersBlock($id_category),
			'nbRenderedProducts' => $pagination_variables['nb_products'],
			'nbAskedProducts' => $pagination_variables['n'],
			'pagination_bottom' => Context::getContext()->smarty->fetch(_PS_THEME_DIR_.'pagination.tpl')
		);
	}

	protected function getCurrentFriendlyUrl()
	{
		$friendly_url = $_SERVER['REQUEST_URI'];
		$friendly_url = str_replace(_ELASTICSEARCH_AJAX_URI_.'?', '#', $friendly_url);
		$friendly_url = explode('&token', $friendly_url);
		return str_replace('&submitElasticsearchFilter=1', '', $friendly_url[0]);
	}

	public function getFiltersBlock($id_category)
	{
		if ($this->filters_block === null)
			$this->filters_block = $this->generateFiltersBlock($id_category);

		return $this->filters_block;
	}

	protected function getPaginationVariables()
	{//todo
		return array(
			'nb_products' => '',
			'pages_nb' => '',
			'p' => '',
			'n' => '',
			'range' => '',
			'start' => '',
			'stop' => '',
			'paginationId' => 'bottom'
		);
	}

	/**
	 * @param $selected_filters array selected filters
	 * @param bool $count_only return only number of results?
	 * @return array|int array with products data | number of products
	 */
	abstract public function getProductsBySelectedFilters($selected_filters, $count_only = false);

	/**
	 * @param $id_category int category ID
	 * @return array enabled filters for given category
	 */
	abstract public function getEnabledFiltersByCategory($id_category);

	/**
	 * @return array selected filters
	 */
	abstract public function getSelectedFilters();

	/**
	 * @return array price filter data to be used in template
	 */
	abstract protected function getPriceFilter();

	/**
	 * @return array weight filter data to be used in template
	 */
	abstract protected function getWeightFilter();

	/**
	 * @param $values array available condition values - ID of condition => name of condition
	 * @return array product condition filter data to be used in template
	 */
	abstract protected function getConditionFilter($values);

	/**
	 * @param $values array available quantity values - ID of quantity type => name of quantity type
	 * @return array product quantity filter data to be used in template
	 */
	abstract protected function getQuantityFilter($values);

	/**
	 * @param $values array available manufacturer values - ID of manufacturer => name of manufacturer
	 * @return array product manufacturers filter data to be used in template
	 */
	abstract protected function getManufacturerFilter($values);

	/**
	 * @param $values array available attributes groups values - ID of attribute group => name of attribute group
	 * @return array product attributes groups filter data to be used in template
	 */
	abstract protected function getAttributeGroupFilter($values);

	/**
	 * @param $values array available features - IDs of features
	 * @return array product features filter data to be used in template
	 */
	abstract protected function getFeatureFilter($values);

	/**
	 * @param $values array available categories values - ID of category => name of category
	 * @return array categories filter data to be used in template
	 */
	abstract protected function getCategoryFilter($values);
}