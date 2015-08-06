<?php

require_once(_ELASTICSEARCH_CORE_DIR_.'AbstractFilter.php');

//@todo this class should be merged to ElasticSearchFilter and class name must be changed
class ReworkedElasticSearchFilter extends AbstractFilter
{
	const FILENAME = 'ElasticSearchFilter';

	public $id_category;
	public $all_category_products = array();
	public $price_filter = array();
	public $weight_filter = array();
	private $selected_filters;

	public function __construct()
	{
		parent::__construct(SearchService::ELASTICSEARCH_INSTANCE);
		$this->id_category = (int)Tools::getValue('id_category', Tools::getValue('id_elasticsearch_category'));
	}

	public function getFiltersProductsCountsAggregationQuery($enabled_filters)
	{
		$selected_filters = $this->getSelectedFilters();
		$id_currency = Context::getContext()->currency->id;
		$required_filters = array();

		foreach ($enabled_filters as $type => $enabled_filter)
		{
			switch ($type)
			{
				default:
					$required_filters[] = array(
						'aggregation_type' => 'terms',
						'field' => $type,
						'alias' => $type,
						'filter' => $this->getProductsQueryByFilters($selected_filters, $type)
					);
					break;
				case self::FILTER_TYPE_PRICE:
					if (!isset($price_query))
						$price_query = $this->getProductsQueryByFilters($selected_filters, $type);

					$required_filters[] = array(
						'aggregation_type' => 'min',
						'field' => 'price_min_'.$id_currency,
						'alias' => 'price_min_'.$id_currency,
						'filter' => $price_query
					);
					$required_filters[] = array(
						'aggregation_type' => 'max',
						'field' => 'price_max_'.$id_currency,
						'alias' => 'price_max_'.$id_currency,
						'filter' => $price_query
					);
					break;
				case self::FILTER_TYPE_WEIGHT:
					if (!isset($weight_query))
						$weight_query = $this->getProductsQueryByFilters($selected_filters, $type);

					$required_filters[] = array(
						'aggregation_type' => 'min',
						'field' => 'weight',
						'alias' => 'min_weight',
						'filter' => $weight_query
					);
					$required_filters[] = array(
						'aggregation_type' => 'max',
						'field' => 'weight',
						'alias' => 'max_weight',
						'filter' => $weight_query
					);
					break;
				case self::FILTER_TYPE_QUANTITY:
					break;
					if (!Configuration::get('PS_STOCK_MANAGEMENT'))
					{
						$required_filters[] = array(
							'aggregation_type' => 'value_count',
							'field' => 'quantity',
							'alias' => 'in_stock',
							'filter' => $this->getProductsQueryByFilters($selected_filters, $type)
						);
						break;
					}

					$quantity_query = $this->getProductsQueryByFilters($selected_filters, $type);

					$qty_filter = array(
						'aggregation_type' => 'terms',
						'field' => 'quantity',
						'alias' => 'in_stock',
						'filter' => $quantity_query
					);

					$should = array(
						array(
							'range' => array(
								'quantity' => array('gt' => 0)
							)
						),
						array(
							'term' => array(
								'out_of_stock' => AbstractFilter::PRODUCT_OOS_ALLOW_ORDERS
							)
						)
					);

					$global_oos_deny_orders = !Configuration::get('PS_ORDER_OUT_OF_STOCK');

					//if ordering out of stock products is allowed globally, include products with global oos value
					if (!$global_oos_deny_orders)
						$should[] = array(
							'term' => array(
								'out_of_stock' => AbstractFilter::PRODUCT_OOS_USE_GLOBAL
							)
						);

					$qty_filter['filter']['bool']['should'] = $should;

					$required_filters[] = $qty_filter;

					//Start building out of stock query

					//include products with quantity lower than 1
					$qty_filter = array(
						'aggregation_type' => 'terms',
						'field' => 'quantity',
						'alias' => 'out_of_stock',
						'filter' => $quantity_query
					);

					$should = array(
						array(
							'bool' => array(
								'must' => array(
									array(
										'range' => array(
											'quantity' => array('lt' => 1)
										)
									)
								)
							)
						)
					);

					//if global "deny out of stock orders" setting is enabled, include products that use global oos value
					if ($global_oos_deny_orders)
					{
						$should[0]['bool']['must'][] = array(
							'bool' => array(
								'should' => array(
									array(
										'term' => array(
											'out_of_stock' => AbstractFilter::PRODUCT_OOS_USE_GLOBAL
										)
									),
									array(
										'term' => array(
											'out_of_stock' => AbstractFilter::PRODUCT_OOS_DENY_ORDERS
										)
									)
								)
							)
						);
					}
					else
					{
						//include only products that deny orders if out of stock
						$should[0]['bool']['must'][] = array(
							'term' => array(
								'out_of_stock' => AbstractFilter::PRODUCT_OOS_DENY_ORDERS
							)
						);
					}

					$qty_filter['filter']['bool']['should'] = $should;

					$required_filters[] = $qty_filter;
					break;
				case self::FILTER_TYPE_MANUFACTURER:
					$required_filters[] = array(
						'aggregation_type' => 'terms',
						'field' => 'id_manufacturer',
						'alias' => 'id_manufacturer',
						'filter' => $this->getProductsQueryByFilters($selected_filters, $type)
					);
					break;
				case self::FILTER_TYPE_ATTRIBUTE_GROUP:
					foreach ($enabled_filter as $value)
						$required_filters[] = array(
							'aggregation_type' => 'terms',
							'field' => 'attribute_group_'.$value['id_value'],
							'alias' => 'attribute_group_'.$value['id_value'],
							'filter' => $this->getProductsQueryByFilters($selected_filters, $type)
						);
					break;
				case self::FILTER_TYPE_FEATURE:
					foreach ($enabled_filter as $value)
						$required_filters[] = array(
							'aggregation_type' => 'terms',
							'field' => 'feature_'.$value['id_value'],
							'alias' => 'feature_'.$value['id_value'],
							'filter' => $this->getProductsQueryByFilters($selected_filters, $type)
						);
					break;
			}
		}

		return AbstractFilter::$search_service->getAggregationQuery($required_filters);
	}

	/**
	 * @param $selected_filters array selected filters
	 * @param bool $count_only return only number of results?
	 * @return array|int array with products data | number of products
	 */
	public function getProductsBySelectedFilters($selected_filters, $count_only = false)
	{
		//building search query for selected filters
		$query = $this->getProductsQueryByFilters($selected_filters);

		if ($count_only)
			return AbstractFilter::$search_service->getDocumentsCount('products', $query);

		$page = (int)Tools::getValue('p');

		if ($page < 1)
			$page = 1;

		$pagination = (int)Tools::getValue('n');
		$start = ($page - 1) * $pagination;
		$order_by_values = array(0 => 'name', 1 => 'price', 6 => 'quantity', 7 => 'reference');
		$order_way_values = array(0 => 'asc', 1 => 'desc');

		$order_by = Tools::strtolower(
			Tools::getValue('orderby',
				isset($order_by_values[(int)Configuration::get('PS_PRODUCTS_ORDER_BY')]) ?
					$order_by_values[(int)Configuration::get('PS_PRODUCTS_ORDER_BY')] : null)
		);

		if ($order_by && !in_array($order_by, $order_by_values))
			$order_by = null;

		$order_way = Tools::strtolower(Tools::getValue('orderway',
			isset($order_way_values[(int)Configuration::get('PS_PRODUCTS_ORDER_WAY')]) ?
				$order_way_values[(int)Configuration::get('PS_PRODUCTS_ORDER_WAY')] : null)
		);

		if ($order_by == 'name')
			$order_by .= '_'.(int)Context::getContext()->language->id;

		$required_fields = array(
			'out_of_stock',
			'id_category_default',
			'link_rewrite_'.Context::getContext()->language->id,
			'default_category_link_rewrite_'.Context::getContext()->language->id,
			'link_'.Context::getContext()->language->id,
			'name_'.Context::getContext()->language->id,
			'description_short_'.Context::getContext()->language->id,
			'description_'.Context::getContext()->language->id,
			'ean13',
			'id_image',
			'customizable',
			'minimal_quantity',
			'available_for_order',
			'show_price',
			'price',
			'quantity',
			'id_combination_default'
		);

		$partial_fields = $this->getPartialFields($required_fields);

		$products = AbstractFilter::$search_service->search(
			'products',
			$query,
			$pagination ? $pagination : null,
			$start,
			$order_by,
			$order_way,
			null,
			false,
			$partial_fields
		);

		$products_data = array();

		$global_allow_oosp = (int)Configuration::get('PS_ORDER_OUT_OF_STOCK');

		foreach ($products as $product)
		{
			$allow_oosp = $this->extractProductField($product, 'out_of_stock');
			$allow_oosp =
				$allow_oosp == AbstractFilter::PRODUCT_OOS_ALLOW_ORDERS ||
				($allow_oosp == AbstractFilter::PRODUCT_OOS_USE_GLOBAL && $global_allow_oosp);

			$products_data[] = array(
				'id_product' => $product['_id'],
				'out_of_stock' => $this->extractProductField($product, 'out_of_stock'),
				'id_category_default' => $this->extractProductField($product, 'id_category_default'),
				'link_rewrite' => $this->extractProductField($product, 'link_rewrite_'.Context::getContext()->language->id),
				'name' => $this->extractProductField($product, 'name_'.Context::getContext()->language->id),
				'description_short' => $this->extractProductField($product, 'description_short_'.Context::getContext()->language->id),
				'ean13' => $this->extractProductField($product, 'ean13'),
				'id_image' => $this->extractProductField($product, 'id_image'),
				'customizable' => $this->extractProductField($product, 'customizable'),
				'minimal_quantity' => $this->extractProductField($product, 'minimal_quantity'),
				'available_for_order' => $this->extractProductField($product, 'available_for_order'),
				'show_price' => $this->extractProductField($product, 'show_price'),
				'quantity' => $this->extractProductField($product, 'quantity'),
				'id_product_attribute' =>  $this->extractProductField($product, 'id_combination_default'),
				'price' => $this->extractProductField($product, 'price'),
				'allow_oosp' => $allow_oosp,
				'link' => $this->extractProductField($product, 'link_'.Context::getContext()->language->id)
			);
		}

		return $products_data;
	}

	public function extractProductField($product, $field_name)
	{
		return isset($product['fields']['data'][0][$field_name]) ? $product['fields']['data'][0][$field_name] : null;
	}

	/**
	 * @param $selected_filters
	 * @param array|string $exclude - exclude these filters from query
	 * @return array
	 */
	public function getProductsQueryByFilters($selected_filters, $exclude = array())
	{
		//todo finish this method
		$query = array();
		$search_values = array();

		if (!is_array($exclude))
			$exclude = array($exclude);

		$price_counter = 0;
		$weight_counter = 0;

		foreach ($selected_filters as $key => $filter_values)
		{
			if (!count($filter_values))
				continue;

			preg_match('/^(.*[^_0-9])/', $key, $res);
			$key = $res[1];

			if (in_array($key, $exclude))
				continue;

			foreach ($filter_values as $value)
			{
				switch ($key)
				{
					case 'id_feature':
						$parts = explode('_', $value);

						if (count($parts) != 2)
							break;

						$search_values['id_feature'][] = array(
							'term' => array(
								'feature_'.$parts[0] => $parts[1]
							)
						);
						break;

					case 'id_attribute_group':
						$parts = explode('_', $value);

						if (count($parts) != 2)
							break;

						$search_values['id_attribute_group'][] = array(
							'term' => array(
								'attribute_group_'.$parts[0] => $parts[1]
							)
						);
						break;

					case 'category':
						//todo this is not done yet
						$search_values['categories'][] = array(
							'term' => array(
								'categories' => $value
							)
						);
						break;

					case 'quantity':
						//If in_stock was already processed it means that both "In stock" and "Not available" values are selected in filter
						//in this case we do not need to add quantity filter at all - need to remove previously set filter.
						if (isset($search_values['in_stock']))
						{
							unset($search_values['in_stock']);
							break;
						}

						//if stock management is disabled all products are available
						if (!Configuration::get('PS_STOCK_MANAGEMENT'))
							break;

						$global_oos_deny_orders = !Configuration::get('PS_ORDER_OUT_OF_STOCK');

						$search_values['in_stock'][] = array(
							'term' => array(
								'in_stock_when_global_oos_'.($global_oos_deny_orders ? 'deny' : 'allow').'_orders' => $value
							)
						);

						break;

					case 'manufacturer':
						$search_values['manufacturer'][] = array(
							'term' => array(
								'id_manufacturer' => $value
							)
						);
						break;

					case 'condition':
						$search_values['condition'][] = array(
							'match' => array(
								'condition' => $value
							)
						);
						break;

					case 'weight':
						if ($weight_counter == 0)
							$search_values['weight']['gte'] = $value;
						elseif ($weight_counter == 1)
							$search_values['weight']['lte'] = $value;

						$weight_counter++;
						break;

					case 'price':
						if ($price_counter == 0)
							$search_values['price']['gte'] = (int)$value;
						elseif ($price_counter == 1)
							$search_values['price']['lte'] = ceil($value);

						$price_counter++;
						break;
				}
			}
		}

		$query['bool']['must'] = $this->getQueryFromSearchValues($search_values);

		//completing categories query
		$query['bool']['must'][] = array(
			'term' => array(
				'categories' => $this->id_category
			)
		);

		return $query;
	}

	/**
	 * Returns formatted query to use in ElasticSearch requests
	 * @param $search_values array values that should be included in search query
	 * @return array
	 */
	public function getQueryFromSearchValues(array $search_values)
	{
		$query = array();

		foreach ($search_values as $key => $value)
		{
			if (in_array($key, array('condition', 'manufacturer', 'in_stock', 'id_feature', 'id_attribute_group')))
			{
				$query[] = array(
					'bool' => array(
						'should' => $value,
						'minimum_should_match' => 1
					)
				);
			}
			elseif ($key == 'weight')
			{
				$query[] = array(
					'range' => array(
						'weight' => $value
					)
				);
			}
			elseif ($key == 'price')
			{
				$query[] = array(
					'bool' => array(
						'should' => array(
							array(
								'bool' => array(
									'must' => array(
										array(
											'range' => array(
												'price_min_'.(int)Context::getContext()->currency->id => array(
													'gte' => $value['gte']
												)
											)
										),
										array(
											'range' => array(
												'price_max_'.(int)Context::getContext()->currency->id => array(
													'lte' => $value['lte']
												)
											)
										)
									)
								)
							),
							array(
								'bool' => array(
									'must' => array(
										array(
											'range' => array(
												'price_min_'.(int)Context::getContext()->currency->id => array(
													'lt' => $value['gte']
												)
											)
										),
										array(
											'range' => array(
												'price_max_'.(int)Context::getContext()->currency->id => array(
													'gt' => $value['gte']
												)
											)
										)
									)
								)
							),
							array(
								'bool' => array(
									'must' => array(
										array(
											'range' => array(
												'price_min_'.(int)Context::getContext()->currency->id => array(
													'lt' => $value['lte']
												)
											)
										),
										array(
											'range' => array(
												'price_max_'.(int)Context::getContext()->currency->id => array(
													'gt' => $value['lte']
												)
											)
										)
									)
								)
							)
						),
						'minimum_should_match' => 1
					)
				);
			}
		}

		return $query;
	}

	/**
	 * @param $id_category int category ID
	 * @return array enabled filters for given category
	 */
	public function getEnabledFiltersByCategory($id_category)
	{
		try
		{
			$filters = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
				SELECT `id_value`, `type`, `position`, `filter_type`, `filter_show_limit`
				FROM `'._DB_PREFIX_.'elasticsearch_category`
				WHERE `id_category` = "'.(int)$id_category.'"
					AND `id_shop` = "'.(int)Context::getContext()->shop->id.'"
				GROUP BY `type`, `id_value`
				ORDER BY `position` ASC'
			);

			$formatted_filters = array();

			foreach ($filters as $filter)
			{
				if (!isset($formatted_filters[$filter['type']]))
					$formatted_filters[$filter['type']] = array();

				$formatted_filters[$filter['type']][] = array(
					'id_value' => $filter['id_value'],
					'filter_type' => $filter['filter_type'],
					'filter_show_limit' => $filter['filter_show_limit']
				);
			}

			return $formatted_filters;
		} catch (Exception $e) {
			self::log('Unable to get filters from database', array('id_category' => $id_category));
			return array();
		}
	}

	/**
	 * @return array selected filters
	 */
	public function getSelectedFilters()
	{
		if ($this->selected_filters === null)
		{
			$id_category = $this->id_category;

			if ($id_category == Configuration::get('PS_HOME_CATEGORY') || !$id_category)
				return null;

			/* Analyze all the filters selected by the user and store them into a tab */
			$selected_filters = array('category' => array(), 'manufacturer' => array(), 'quantity' => array(), 'condition' => array());

			foreach ($_GET as $key => $value)
				if (Tools::strpos($key, 'elasticsearch_') === 0)
				{
					preg_match('/^(.*)_([0-9]+|new|used|refurbished|slider)$/', Tools::substr($key, 14, Tools::strlen($key) - 14), $res);
					if (isset($res[1]))
					{
						$tmp_tab = explode('_', $this->sanitizeValue($value));
						$value = $this->sanitizeValue($tmp_tab[0]);
						$id_key = false;

						if (isset($tmp_tab[1]))
							$id_key = $tmp_tab[1];

						if ($res[1] == 'condition' && in_array($value, array('new', 'used', 'refurbished')))
							$selected_filters['condition'][] = $value;
						else if ($res[1] == 'quantity' && (!$value || $value == 1))
							$selected_filters['quantity'][] = $value;
						else if (in_array($res[1], array('category', 'manufacturer')))
						{
							if (!isset($selected_filters[$res[1].($id_key ? '_'.$id_key : '')]))
								$selected_filters[$res[1].($id_key ? '_'.$id_key : '')] = array();

							$selected_filters[$res[1].($id_key ? '_'.$id_key : '')][] = (int)$value;
						}
						else if (in_array($res[1], array('id_attribute_group', 'id_feature')))
						{
							if (!isset($selected_filters[$res[1]]))
								$selected_filters[$res[1]] = array();
							$selected_filters[$res[1]][(int)$value] = $id_key.'_'.(int)$value;
						}
						else if ($res[1] == 'weight')
							$selected_filters[$res[1]] = $tmp_tab;
						else if ($res[1] == 'price')
							$selected_filters[$res[1]] = $tmp_tab;
					}
				}

			$this->selected_filters = $selected_filters;
		}

		return $this->selected_filters;
	}

	/**
	 * Formats partial fields in correct syntax to use in ElasticSearch calls
	 * @param array $required_fields
	 * @return array
	 */
	public function getPartialFields(array $required_fields)
	{
		$partial_fields = array();

		if ($required_fields)
		{
			$partial_fields['data'] = array('include' => array());

			foreach ($required_fields as $field)
				$partial_fields['data']['include'][] = $field;
		}

		return $partial_fields;
	}

	/**
	 * @param array $filter
	 * @return array price filter data to be used in template
	 */
	protected function getPriceFilter($filter)
	{
		if (isset($filter[0]))
			$filter = $filter[0];

		$currency = Context::getContext()->currency;

		$price_array = array(
			'type_lite' => 'price',
			'type' => 'price',
			'id_key' => 0,
			'name' => $this->getModuleInstance()->l('Price', self::FILENAME),
			'slider' => true,
			'max' => '0',
			'min' => null,
			'values' => array ('1' => 0),
			'unit' => $currency->sign,
			'format' => $currency->format,
			'filter_show_limit' => $filter['filter_show_limit'],
			'filter_type' => $filter['filter_type']
		);

		//getting min and max prices from aggregations
		$min_price = $this->getAggregation('price_min_'.$currency->id);
		$max_price = $this->getAggregation('price_max_'.$currency->id);

		$price_array['min'] = $min_price;
		$price_array['values'][0] = $price_array['min'];

		$price_array['max'] = $max_price;
		$price_array['values'][1] = $price_array['max'];

		if ($price_array['max'] != $price_array['min'] && $price_array['min'] !== null)
		{
			if ($filter['filter_type'] == AbstractFilter::FILTER_STYLE_LIST_OF_VALUES)
			{
				$price_array['list_of_values'] = array();
				$nbr_of_value = $filter['filter_show_limit'];

				if ($nbr_of_value < 2)
					$nbr_of_value = 4;

				$delta = ($price_array['max'] - $price_array['min']) / $nbr_of_value;

				for ($i = 0; $i < $nbr_of_value; $i++)
					$price_array['list_of_values'][] = array(
						(int)$price_array['min'] + $i * (int)$delta,
						(int)$price_array['min'] + ($i + 1) * (int)$delta
					);
			}

			$selected_filters = $this->getSelectedFilters();

			if ($selected_filters && isset($selected_filters['price'][0]) && isset($selected_filters['price'][1]))
			{
				$price_array['values'][0] = $selected_filters['price'][0];
				$price_array['values'][1] = $selected_filters['price'][1];
			}

			return $price_array;
		}

		return null;
	}

	/**
	 * @param array $filter
	 * @return array weight filter data to be used in template
	 */
	protected function getWeightFilter($filter)
	{
		if (isset($filter[0]))
			$filter = $filter[0];

		$weight_array = array(
			'type_lite' => 'weight',
			'type' => 'weight',
			'id_key' => 0,
			'name' => $this->getModuleInstance()->l('Weight', self::FILENAME),
			'slider' => true,
			'max' => '0',
			'min' => null,
			'values' => array ('1' => 0),
			'unit' => Configuration::get('PS_WEIGHT_UNIT'),
			'format' => 5, // Ex: xxxxx kg
			'filter_show_limit' => $filter['filter_show_limit'],
			'filter_type' => $filter['filter_type']
		);

		//getting min and max weight from aggregations
		$min_weight = $this->getAggregation('min_weight');
		$max_weight = $this->getAggregation('max_weight');

		$weight_array['min'] = floor($min_weight);
		$weight_array['values'][0] = $weight_array['min'];

		$weight_array['max'] = ceil($max_weight);
		$weight_array['values'][1] = $weight_array['max'];

		if ($weight_array['max'] != $weight_array['min'] && $weight_array['min'] !== null)
		{
			$selected_filters = $this->getSelectedFilters();

			if ($selected_filters
				&& isset($selected_filters['weight'])
				&& isset($selected_filters['weight'][0])
				&& isset($selected_filters['weight'][1]))
			{
				$weight_array['values'][0] = $selected_filters['weight'][0];
				$weight_array['values'][1] = $selected_filters['weight'][1];
			}

			return $weight_array;
		}

		return null;
	}

	/**
	 * @param $filter array available condition values - ID of condition => name of condition
	 * @return array product condition filter data to be used in template
	 */
	protected function getConditionFilter($filter)
	{
		if (isset($filter[0]))
			$filter = $filter[0];

		$condition_filter = array(
			'type_lite' => 'condition',
			'type' => 'condition',
			'id_key' => 0,
			'name' => $this->getModuleInstance()->l('Condition', self::FILENAME),
			'values' => array(),
			'filter_show_limit' => $filter['filter_show_limit'],
			'filter_type' => $filter['filter_type']
		);

		$aggregation = $this->getAggregation('condition');

		if (!$aggregation)
			return $condition_filter;

		$selected_filters = $this->getSelectedFilters();
		$condition_array = array();

		if (isset($aggregation['new']) && ($aggregation['new'] || !$this->hide_0_values))
		{
			$condition_array['new'] = array(
				'name' => $this->getModuleInstance()->l('New', self::FILENAME),
				'nbr' => $aggregation['new']
			);

			if (isset($selected_filters['condition']) && in_array('new', $selected_filters['condition']))
				$condition_array['new']['checked'] = true;
		}

		if (isset($aggregation['used']) && ($aggregation['used'] || !$this->hide_0_values))
		{
			$condition_array['used'] = array(
				'name' => $this->getModuleInstance()->l('Used', self::FILENAME),
				'nbr' => $aggregation['used'],
				'checked' => isset($selected_filters['condition']) && in_array('used', $selected_filters['condition'])
			);

			if (isset($selected_filters['condition']) && in_array('used', $selected_filters['condition']))
				$condition_array['used']['checked'] = true;
		}

		if (isset($aggregation['refurbished']) && ($aggregation['refurbished'] || !$this->hide_0_values))
		{
			$condition_array['refurbished'] = array(
				'name' => $this->getModuleInstance()->l('Refurbished', self::FILENAME),
				'nbr' => $aggregation['refurbished']
			);

			if (isset($selected_filters['condition']) && in_array('refurbished', $selected_filters['condition']))
				$condition_array['refurbished']['checked'] = true;
		}

		return array(
			'type_lite' => 'condition',
			'type' => 'condition',
			'id_key' => 0,
			'name' => $this->getModuleInstance()->l('Condition', self::FILENAME),
			'values' => $condition_array,
			'filter_show_limit' => $filter['filter_show_limit'],
			'filter_type' => $filter['filter_type']
		);
	}

	/**
	 * @param $filter array quantity filter data
	 * @return array product quantity filter data to be used in template
	 */
	protected function getQuantityFilter($filter)
	{
		if (isset($filter[0]))
			$filter = $filter[0];

		$out_of_stock = $this->getAggregation('out_of_stock');

		if (is_array($out_of_stock))
			$out_of_stock = reset($out_of_stock);

		$in_stock = $this->getAggregation('in_stock');

		if (is_array($in_stock))
			$in_stock = reset($in_stock);

		$quantity_array = array (
			0 => array(
				'name' => $this->getModuleInstance()->l('Not available', self::FILENAME),
				'nbr' => $out_of_stock
			),
			1 => array(
				'name' => $this->getModuleInstance()->l('In stock', self::FILENAME),
				'nbr' => $in_stock
			)
		);

		$selected_filters = $this->getSelectedFilters();

		//selecting filters where needed
		foreach (array_keys($quantity_array) as $key)
			if (isset($selected_filters['quantity']) && in_array($key, $selected_filters['quantity']))
				$quantity_array[$key]['checked'] = true;

		if ($quantity_array[0]['nbr'] || $quantity_array[1]['nbr'] || !$this->hide_0_values)
			return array(
				'type_lite' => 'quantity',
				'type' => 'quantity',
				'id_key' => 0,
				'name' => $this->getModuleInstance()->l('Availability', self::FILENAME),
				'values' => $quantity_array,
				'filter_show_limit' => $filter['filter_show_limit'],
				'filter_type' => $filter['filter_type']
			);

		return false;
	}

	/**
	 * @param $values array available manufacturer values - ID of manufacturer => name of manufacturer
	 * @return array product manufacturers filter data to be used in template
	 */
	protected function getManufacturerFilter($values)
	{
		// TODO: Implement getManufacturerFilter() method.
	}

	/**
	 * @param $values array available attributes groups values - ID of attribute group => name of attribute group
	 * @return array product attributes groups filter data to be used in template
	 */
	protected function getAttributeGroupFilter($values)
	{
		// TODO: Implement getAttributeGroupFilter() method.
	}

	/**
	 * @param $values array available features - IDs of features
	 * @return array product features filter data to be used in template
	 */
	protected function getFeatureFilter($values)
	{
		// TODO: Implement getFeatureFilter() method.
	}

	/**
	 * @param $values array available categories values - ID of category => name of category
	 * @return array categories filter data to be used in template
	 */
	protected function getCategoryFilter($values)
	{
		// TODO: Implement getCategoryFilter() method.
	}

	/**
	 * Returns count of products for each filter
	 * @return array
	 */
	public function getAggregations()
	{
		if ($this->filters_products_counts === null)
		{
			$query_all = array(
				'aggs' => $this->getFiltersProductsCountsAggregationQuery($this->enabled_filters)
			);
//			unset($query_all['aggs']['condition']['filter']['bool']['must'][1]);
			unset($query_all['aggs']['condition']['filter']['bool']['must'][2]);
			unset($query_all['aggs']['condition']['filter']['bool']['must'][3]);
			unset($query_all['aggs']['id_manufacturer']);
			unset($query_all['aggs']['min_weight']);
			unset($query_all['aggs']['max_weight']);
			unset($query_all['aggs']['price_min_1']);
			unset($query_all['aggs']['price_max_1']);
//			d($query_all);
			$result = AbstractFilter::$search_service->search(
				'products',
				$query_all,
				0,
				null,
				null,
				null,
				null,
				true
			);
//d($result);

			if (!isset($result['aggregations']))
				$this->filters_products_counts = array();
			else
			{
				$aggregations = array();

				foreach ($result['aggregations'] as $alias => $aggregation)
				{
					if (isset($aggregation[$alias]['buckets']) && $aggregation[$alias]['buckets'])
					{
						$aggregations[$alias] = array();

						foreach ($aggregation[$alias]['buckets'] as $bucket)
							$aggregations[$alias][$bucket['key']] = $bucket['doc_count'];
					}
					elseif (isset($aggregation[$alias]['value']))
						$aggregations[$alias] = $aggregation[$alias]['value'];
					elseif (isset($aggregation['doc_count']))
						$aggregations[$alias] = $aggregation['doc_count'];
					else
						$aggregations[$alias] = 0;
				}

				$this->filters_products_counts = $aggregations;
			}
		}

		return $this->filters_products_counts;
	}

	/**
	 * Gets aggregation value(s) by given name
	 * @param $name - aggregation name
	 * @return int|array
	 */
	public function getAggregation($name)
	{
		$aggregations = $this->getAggregations();

		if (!isset($aggregations[$name]))
			return 0;

		return $aggregations[$name];
	}
}