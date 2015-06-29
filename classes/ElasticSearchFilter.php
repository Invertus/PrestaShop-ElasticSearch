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

class ElasticSearchFilter extends ElasticSearchService
{
	const FILENAME = 'ElasticSearchFilter';

	private $context = null;
	private $page = 1;
	private $all_products;
	private $filtered_products;
	private $filtered_products_query_params;
	private $filter_query;

	public function __construct($module_name = 'elasticsearch')
	{
		$this->context = Context::getContext();

		if ((int)Tools::getValue('p'))
			$this->page = (int)Tools::getValue('p');

		parent::__construct($module_name);
	}

	public function generateFiltersBlock($selected_filters)
	{
		if ($filter_block = $this->getFilterBlock($selected_filters))
		{
			if ($filter_block['nbr_filterBlocks'] == 0)
				return false;

			$translate = array();
			$translate['price'] = $this->module_instance->l('price', self::FILENAME);
			$translate['weight'] = $this->module_instance->l('weight', self::FILENAME);

			$this->context->smarty->assign($filter_block);
			$this->context->smarty->assign(array(
				'hide_0_values' => Configuration::get('ELASTICSEARCH_HIDE_0_VALUES'),
				'blocklayeredSliderName' => $translate,
				'col_img_dir' => _PS_COL_IMG_DIR_
			));
			return $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'hook/column.tpl');
		}

		return false;
	}

	public function getSelectedFilters()
	{
		$home_category = Configuration::get('PS_HOME_CATEGORY');
		$id_category = (int)Tools::getValue('id_category', Tools::getValue('id_elasticsearch_category', $home_category));

		if ($id_category == $home_category)
			return null;

		/* Analyze all the filters selected by the user and store them into a tab */
		$selected_filters = array('category' => array(), 'manufacturer' => array(), 'quantity' => array(), 'condition' => array());

		foreach ($_GET as $key => $value)
			if (Tools::substr($key, 0, 14) == 'elasticsearch_')
			{
				preg_match('/^(.*)_([0-9]+|new|used|refurbished|slider)$/', Tools::substr($key, 14, Tools::strlen($key) - 14), $res);
				if (isset($res[1]))
				{
					$tmp_tab = explode('_', $this->filterVar($value));
					$value = $this->filterVar($tmp_tab[0]);
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

		return $selected_filters;
	}

	public function searchChildCategories($parent_category)
	{
		$filter_query = array(
			'and' => array(
				array(
					'range' => array(
						'nleft' => array(
							'gt' => $parent_category['_source']['nleft']
						)
					)
				),
				array(
					'range' => array(
						'nright' => array(
							'lt' => $parent_category['_source']['nright']
						)
					)
				),
			)
		);

		if (!Configuration::get('ELASTICSEARCH_FULL_TREE'))
			$filter_query['and'][1]['range']['level_depth']['lt'] = $parent_category['_source']['level_depth'] + 2;
		elseif ($depth = Configuration::get('ELASTICSEARCH_CATEGORY_DEPTH'))
			$filter_query['and'][1]['range']['level_depth']['lt'] = $parent_category['_source']['level_depth'] + 1 + (int)$depth;

		$n_cat = $this->getDocumentsCount('categories', array(), $filter_query);

		return $n_cat ? $this->search('categories', array(), $n_cat, 0, null, null, $filter_query) : array();
	}

	public function fixFilterQuery(&$filter_query, $operator = 'and')
	{
		if (count($filter_query) < 2)
		{
			if (isset($filter_query[0]))
				$filter_query = $filter_query[0];
		}
		else
			$filter_query = array($operator => $filter_query);
	}

	public function getPriceFilter($filter)
	{
		$currency = $this->context->currency;

		$price_array = array(
			'type_lite' => 'price',
			'type' => 'price',
			'id_key' => 0,
			'name' => $this->module_instance->l('Price', self::FILENAME),
			'slider' => true,
			'max' => '0',
			'min' => null,
			'values' => array ('1' => 0),
			'unit' => $currency->sign,
			'format' => $currency->format,
			'filter_show_limit' => $filter['filter_show_limit'],
			'filter_type' => $filter['filter_type']
		);

		if ($this->all_products)
			foreach ($this->all_products as $product)
			{
				if (is_null($price_array['min']))
				{
					$price_array['min'] = $product['_source']['price_min_'.$currency->id];
					$price_array['values'][0] = $product['_source']['price_min_'.$currency->id];
				}
				else if ($price_array['min'] > $product['_source']['price_min_'.$currency->id])
				{
					$price_array['min'] = $product['_source']['price_min_'.$currency->id];
					$price_array['values'][0] = $product['_source']['price_min_'.$currency->id];
				}

				if ($price_array['max'] < $product['_source']['price_max_'.$currency->id])
				{
					$price_array['max'] = $product['_source']['price_max_'.$currency->id];
					$price_array['values'][1] = $product['_source']['price_max_'.$currency->id];
				}
			}

		if ($price_array['max'] != $price_array['min'] && $price_array['min'] != null)
		{
			if ($filter['filter_type'] == 2)
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

			if (isset($selected_filters['price'][0]) && isset($selected_filters['price'][1]))
			{
				$price_array['values'][0] = $selected_filters['price'][0];
				$price_array['values'][1] = $selected_filters['price'][1];
			}

			return $price_array;
		}

		return null;
	}

	public function getWeightFilter($filter)
	{
		$weight_array = array(
			'type_lite' => 'weight',
			'type' => 'weight',
			'id_key' => 0,
			'name' => $this->module_instance->l('Weight', self::FILENAME),
			'slider' => true,
			'max' => '0',
			'min' => null,
			'values' => array ('1' => 0),
			'unit' => Configuration::get('PS_WEIGHT_UNIT'),
			'format' => 5, // Ex: xxxxx kg
			'filter_show_limit' => $filter['filter_show_limit'],
			'filter_type' => $filter['filter_type']
		);

		if ($this->all_products)
			foreach ($this->all_products as $product)
			{
				if (is_null($weight_array['min']))
				{
					$weight_array['min'] = $product['_source']['weight'];
					$weight_array['values'][0] = $product['_source']['weight'];
				}
				else if ($weight_array['min'] > $product['_source']['weight'])
				{
					$weight_array['min'] = $product['_source']['weight'];
					$weight_array['values'][0] = $product['_source']['weight'];
				}

				if ($weight_array['max'] < $product['_source']['weight'])
				{
					$weight_array['max'] = $product['_source']['weight'];
					$weight_array['values'][1] = $product['_source']['weight'];
				}
			}

		if ($weight_array['max'] != $weight_array['min'] && $weight_array['min'] != null)
		{
			if (isset($selected_filters['weight']) && isset($selected_filters['weight'][0])
				&& isset($selected_filters['weight'][1]))
			{
				$weight_array['values'][0] = $selected_filters['weight'][0];
				$weight_array['values'][1] = $selected_filters['weight'][1];
			}
			return $weight_array;
		}

		return null;
	}

	public function getConditionFilter($filter, $params, $filter_query, $selected_filters)
	{
		$query_new = $this->buildSearchQuery('bool_must', array_merge(
			$params,
			array(
				array(
					'term' => array(
						'condition' => 'new'
					)
				)
			)
		));

		$query_used = $this->buildSearchQuery('bool_must', array_merge(
			$params,
			array(
				array(
					'term' => array(
						'condition' => 'used'
					)
				)
			)
		));

		$query_refurbished = $this->buildSearchQuery('bool_must', array_merge(
			$params,
			array(
				array(
					'term' => array(
						'condition' => 'refurbished'
					)
				)
			)
		));

		//number of new products
		$nbr_new = $this->search('products', $query_new, null, null, null, null, $filter_query);

		//number of used products
		$nbr_used = $this->search('products', $query_used, null, null, null, null, $filter_query);

		//number of refurbished products
		$nbr_refurbished = $this->search('products', $query_refurbished, null, null, null, null, $filter_query);

		$condition_array = array(
			'new' => array(
				'name' => $this->module_instance->l('New', self::FILENAME),
				'nbr' => $nbr_new
			),
			'used' => array(
				'name' => $this->module_instance->l('Used', self::FILENAME),
				'nbr' => $nbr_used
			),
			'refurbished' => array(
				'name' => $this->module_instance->l('Refurbished', self::FILENAME),
				'nbr' => $nbr_refurbished
			)
		);

		if ($this->filtered_products)
			foreach ($this->filtered_products as $product)
				if (isset($selected_filters['condition']) && in_array($product['_source']['condition'], $selected_filters['condition']))
					$condition_array[$product['_source']['condition']]['checked'] = true;

		foreach (array_keys($condition_array) as $key)
			if (isset($selected_filters['condition']) && in_array($key, $selected_filters['condition']))
				$condition_array[$key]['checked'] = true;

		if ($nbr_new || $nbr_used || $nbr_refurbished || !Configuration::get('ELASTICSEARCH_HIDE_0_VALUES'))
			return array(
				'type_lite' => 'condition',
				'type' => 'condition',
				'id_key' => 0,
				'name' => $this->module_instance->l('Condition', self::FILENAME),
				'values' => $condition_array,
				'filter_show_limit' => $filter['filter_show_limit'],
				'filter_type' => $filter['filter_type']
			);

		return null;
	}

	public function initAllProducts()
	{
		if ($this->all_products === null)
		{
			$query_all = $this->buildSearchQuery('bool_must', array(
				'term' => array(
					'categories' => Tools::getValue('id_elasticsearch_category', Tools::getValue('id_category'))
				)
			));

			$this->all_products = $this->search('products', $query_all, null);
		}
	}

	public function initFilteredProducts($selected_filters)
	{
		if ($this->filtered_products === null)
		{
			$this->filtered_products_query_params = $this->getProductsQueryByFilters($selected_filters, $this->filter_query);
			$query = $this->buildSearchQuery('bool_must', $this->filtered_products_query_params);
			$this->fixFilterQuery($this->filter_query);
			$this->filtered_products = $this->search('products', $query, null, 0, null, null, $this->filter_query);
		}
	}

	public function getFilterBlock($selected_filters = array())
	{
		static $cache = null;

		if (is_array($cache))
			return $cache;

		$home_category = Configuration::get('PS_HOME_CATEGORY');
		$id_category = (int)Tools::getValue('id_category', Tools::getValue('id_elasticsearch_category', $home_category));

		if ($id_category == $home_category)
			return null;

		$filters = $this->getFiltersFromDb($id_category);

		// Remove all empty selected filters
		foreach ($selected_filters as $key => $value)
			switch ($key)
			{
				case 'price':
				case 'weight':
					if ($value[0] === '' && $value[1] === '')
						unset($selected_filters[$key]);
					break;
				default:
					if ($value == '')
						unset($selected_filters[$key]);
					break;
			}

		$this->initFilteredProducts($selected_filters);
		$this->initAllProducts();

		$current_category = $this->getDocumentById('categories', $id_category);
		$categories = $this->searchChildCategories($current_category);

		$filter_blocks = array();

		foreach ($filters as $filter)
			switch ($filter['type'])
			{
				case 'price':
					if ($this->showPriceFilter())
					{
						$price_filter = $this->getPriceFilter($filter);

						if ($price_filter)
							$filter_blocks[] = $price_filter;
					}
					break;
				case 'weight':
					$weight_filter = $this->getWeightFilter($filter);

					if ($weight_filter)
						$filter_blocks[] = $weight_filter;
					break;
				case 'condition':
					$condition_filter = $this->getConditionFilter($filter, $this->filtered_products_query_params, $this->filter_query, $selected_filters);

					if ($condition_filter)
						$filter_blocks[] = $condition_filter;
					break;

				case 'quantity':
					$quantity_array = array (
						0 => array('name' => $this->module_instance->l('Not available', self::FILENAME), 'nbr' => 0),
						1 => array('name' => $this->module_instance->l('In stock', self::FILENAME), 'nbr' => 0)
					);

					foreach (array_keys($quantity_array) as $key)
						if (isset($selected_filters['quantity']) && in_array($key, $selected_filters['quantity']))
							$quantity_array[$key]['checked'] = true;

					if ($this->filtered_products)
						foreach ($this->filtered_products as $product)
						{
							//If oosp move all not available quantity to available quantity
							if ((int)$product['_source']['quantity'] > 0 || Product::isAvailableWhenOutOfStock(StockAvailable::outOfStock($product['_id'])))
								$quantity_array[1]['nbr']++;
							else
								$quantity_array[0]['nbr']++;
						}

					if ($quantity_array[0]['nbr'] || $quantity_array[1]['nbr'] || !Configuration::get('ELASTICSEARCH_HIDE_0_VALUES'))
						$filter_blocks[] = array(
							'type_lite' => 'quantity',
							'type' => 'quantity',
							'id_key' => 0,
							'name' => $this->module_instance->l('Availability', self::FILENAME),
							'values' => $quantity_array,
							'filter_show_limit' => $filter['filter_show_limit'],
							'filter_type' => $filter['filter_type']
						);

					break;

				case 'manufacturer':
					if ($this->filtered_products)
					{
						$manufacturers_array = array();
						$manufacturers_nbr = array();
						foreach ($this->filtered_products as $manufacturer)
						{
							if (!isset($manufacturers_nbr[$manufacturer['_source']['id_manufacturer']]))
							{
								$query = $this->buildSearchQuery('bool_must', array_merge(
									$this->filtered_products_query_params,
									array(
										array(
											'term' => array(
												'id_manufacturer' => $manufacturer['_source']['id_manufacturer']
											)
										)
									)
								));
								$nbr = $this->search('products', $query, null, null, null, null, $this->filter_query);
								$manufacturers_nbr[$manufacturer['_source']['id_manufacturer']] = $nbr;
							}

							if (!isset($manufacturers_array[$manufacturer['_source']['id_manufacturer']]))
								$manufaturers_array[$manufacturer['_source']['id_manufacturer']] = array(
									'name' => $manufacturer['_source']['manufacturer_name'],
									'nbr' => $manufacturers_nbr[$manufacturer['_source']['id_manufacturer']]
								);

							if (isset($selected_filters['manufacturer']) && in_array((int)$manufacturer['_source']['id_manufacturer'], $selected_filters['manufacturer']))
								$manufaturers_array[$manufacturer['_source']['id_manufacturer']]['checked'] = true;
						}
						$filter_blocks[] = array(
							'type_lite' => 'manufacturer',
							'type' => 'manufacturer',
							'id_key' => 0,
							'name' => $this->module_instance->l('Manufacturer', self::FILENAME),
							'values' => $manufacturers_array,
							'filter_show_limit' => $filter['filter_show_limit'],
							'filter_type' => $filter['filter_type']
						);
					}
					break;

				case 'id_attribute_group':
					$attributes_array = array();
					if ($this->filtered_products)
					{
						foreach ($this->filtered_products as $product)
						{
							foreach ($product['_source'] as $attribute_group => $attributes)
							{
								if (Tools::strpos($attribute_group, 'attribute_group_') !== false)
								{
									$id_attribute_group = (int)str_replace('attribute_group_', '', $attribute_group);

									if ($filter['id_value'] != $id_attribute_group)
										continue;

									$group = new AttributeGroup($id_attribute_group);

									if (!isset($attributes_array[$id_attribute_group]))
										$attributes_array[$id_attribute_group] = array(
											'type_lite' => 'id_attribute_group',
											'type' => 'id_attribute_group',
											'id_key' => $id_attribute_group,
											'name' => $group->name[$this->context->language->id],
											'is_color_group' => (bool)$group->is_color_group,
											'values' => array(),
											'filter_show_limit' => $filter['filter_show_limit'],
											'filter_type' => $filter['filter_type']
										);

									if ($attributes)
										foreach ($attributes as $id_attribute)
										{
											$attribute = new Attribute($id_attribute);

											if (!isset($attributes_array[$id_attribute_group]['values'][$id_attribute]))
											{
												$query = $this->buildSearchQuery(
													'bool_must', array_merge(
														$this->filtered_products_query_params,
														array(
															array(
																'term' => array(
																	$attribute_group => $id_attribute
																)
															)
														)
													)
												);

												$nbr = $this->search(
													'products',
													$query,
													null,
													null,
													null,
													null,
													$this->filter_query
												);

												$attributes_array[$id_attribute_group]['values'][$id_attribute] = array(
													'color' => $attribute->color,
													'name' => $attribute->name[$this->context->language->id],
													'nbr' => (int)$nbr
												);
											}

											if (isset($selected_filters['id_attribute_group'][$id_attribute]))
												$attributes_array[$id_attribute_group]['values'][$id_attribute]['checked'] = true;
										}
								}
							}
						}

						$filter_blocks = array_merge($filter_blocks, $attributes_array);
					}
					break;
				case 'id_feature':
					$feature_array = array();
					if ($this->filtered_products)
					{
						foreach ($this->filtered_products as $product)
						{
							foreach ($product['_source'] as $feature => $id_feature_value)
							{
								if (Tools::strpos($feature, 'feature_') !== false)
								{
									$id_feature = (int)str_replace('feature_', '', $feature);

									if ($filter['id_value'] != $id_feature)
										continue;

									$feature_obj = new Feature($id_feature);

									if (!isset($feature_array[$id_feature]))
										$feature_array[$id_feature] = array(
											'type_lite' => 'id_feature',
											'type' => 'id_feature',
											'id_key' => (int)$id_feature,
											'values' => array(),
											'name' => $feature_obj->name[$this->context->language->id],
											'filter_show_limit' => $filter['filter_show_limit'],
											'filter_type' => $filter['filter_type']
										);

									if (!isset($feature_array[$id_feature]['values'][$id_feature_value]))
									{
										$query = $this->buildSearchQuery(
											'bool_must',
											array_merge(
												$this->filtered_products_query_params,
												array(
													array(
														'term' => array(
															$feature => $id_feature_value
														)
													)
												)
											)
										);

										$nbr = $this->search('products', $query, null, null, null, null, $this->filter_query);

										$feature_value_obj = new FeatureValue($id_feature_value);

										$feature_array[$id_feature]['values'][$id_feature_value] = array(
											'nbr' => (int)$nbr,
											'name' => $feature_value_obj->value[$this->context->language->id]
										);
									}

									if (isset($selected_filters['id_feature'][$id_feature_value]))
										$feature_array[$id_feature]['values'][$id_feature_value]['checked'] = true;
								}
							}
						}

						//Natural sort
						foreach ($feature_array as $key => $value)
						{
							$temp = array();
							foreach ($feature_array[$key]['values'] as $keyint => $valueint)
								$temp[$keyint] = $valueint['name'];

							natcasesort($temp);
							$temp2 = array();

							foreach (array_keys($temp) as $keytemp)
								$temp2[$keytemp] = $feature_array[$key]['values'][$keytemp];

							$feature_array[$key]['values'] = $temp2;
						}

						$filter_blocks = array_merge($filter_blocks, $feature_array);
					}
					break;

				case 'category':
					$tmp_array = array();
					if ($this->filtered_products)
					{
						$categories_with_products_count = 0;
						foreach ($categories as $category)
						{
							$category_params = $this->filtered_products_query_params;
							$this->changeQueryCategory($category_params, $category['_id']);

							$nbr_products = $this->search(
								'products',
								$this->buildSearchQuery('bool_must', $category_params),
								null,
								null
							);

							$tmp_array[$category['_id']] = array(
								'name' => $category['_source']['name_'.$this->context->language->id],
								'nbr' => (int)$nbr_products
							);

							if ((int)$nbr_products)
								$categories_with_products_count++;

							if (isset($selected_filters['category']) && in_array($category['_id'], $selected_filters['category']))
								$tmp_array[$category['_id']]['checked'] = true;
						}
						if ($categories_with_products_count || !Configuration::get('ELASTICSEARCH_HIDE_0_VALUES'))
							$filter_blocks[] = array(
								'type_lite' => 'category',
								'type' => 'category',
								'id_key' => 0,
								'name' => $this->module_instance->l('Categories', self::FILENAME),
								'values' => $tmp_array,
								'filter_show_limit' => $filter['filter_show_limit'],
								'filter_type' => $filter['filter_type']
							);
					}
					break;
			}

		$existing_filters = array();
		$count = count($filter_blocks);

		//removing duplicate filters
		for ($k = 0; $k < $count; $k++)
		{
			if (array_search($filter_blocks[$k]['type'].$filter_blocks[$k]['id_key'], $existing_filters) !== false)
			{
				unset($filter_blocks[$k]);
				continue;
			}

			if (isset($filter_blocks[$k]['values']))
				foreach ($filter_blocks[$k]['values'] as &$value)
					if (is_array($value))
					{
						$value['link'] = 'javascript:void(0);';
						$value['rel'] = '';
					}

			$existing_filters[] = $filter_blocks[$k]['type'].$filter_blocks[$k]['id_key'];
		}

		$n_filters = 0;
		if (isset($selected_filters['price']) && isset($price_array))
			if ($price_array['min'] == $selected_filters['price'][0] && $price_array['max'] == $selected_filters['price'][1])
				unset($selected_filters['price']);
		if (isset($selected_filters['weight']) && isset($weight_array))
			if ($weight_array['min'] == $selected_filters['weight'][0] && $weight_array['max'] == $selected_filters['weight'][1])
				unset($selected_filters['weight']);

		foreach ($selected_filters as $filters)
			$n_filters += count($filters);

		$cache = array(
			'elasticsearch_show_qties' => (int)Configuration::get('ELASTICSEARCH_SHOW_QTIES'),
			'id_elasticsearch_category' => (int)$id_category,
			'selected_filters' => $selected_filters,
			'n_filters' => (int)$n_filters,
			'nbr_filterBlocks' => count($filter_blocks),
			'filters' => $filter_blocks,
			'title_values' => array(),
			'meta_values' => array(),
			'current_friendly_url' => '',
			'param_product_url' => '',
			'no_follow' => false
		);

		return $cache;
	}

	/**
	 * Returns filters for category
	 * @return array - filters data
	 * @throws PrestaShopDatabaseException
	 */
	public function getFiltersFromDb($id_category)
	{
		try
		{
			return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT `id_elasticsearch_category`, `id_shop`, `id_category`, `id_value`, `type`, `position`, `filter_type`, `filter_show_limit`, `date_add`
			FROM '._DB_PREFIX_.'elasticsearch_category
			WHERE `id_category` = "'.(int)$id_category.'"
				AND `id_shop` = "'.(int)$this->context->shop->id.'"
			GROUP BY `type`, `id_value`
			ORDER BY `position` ASC
		');
		} catch (Exception $e) {
			self::log('Unable to get filters from database', array('id_category' => $id_category));
			return array();
		}
	}

	public function ajaxCall()
	{
		$selected_filters = $this->getSelectedFilters();
		$filter_block = $this->getFilterBlock($selected_filters);

		$this->getProducts($selected_filters, $products, $nb_products, $p, $n, $pages_nb, $start, $stop, $range);

		$n_array = array((int)Configuration::get('PS_PRODUCTS_PER_PAGE'), 10, 20, 50);
		$n_array = array_unique($n_array);
		asort($n_array);

		$this->context->controller->addColorsToProductList($products);
		$id_category = (int)Tools::getValue('id_elasticsearch_category', (int)Configuration::get('PS_HOME_CATEGORY'));
		$category = new Category((int)$id_category, (int)$this->context->cookie->id_lang);

		// Generate meta title and meta description
		$category_title = (empty($category->meta_title) ? $category->name : $category->meta_title);
		$category_metas = Meta::getMetaTags((int)$this->context->cookie->id_lang, 'category');
		$title = '';
		$keywords = '';

		if (is_array($filter_block['title_values']))
			foreach ($filter_block['title_values'] as $key => $val)
			{
				$title .= ' > '.$key.' '.implode('/', $val);
				$keywords .= $key.' '.implode('/', $val).', ';
			}

		$title = $category_title.$title;
		$meta_title = !empty($title) ? $title : $category_metas['meta_title'];
		$meta_description = $category_metas['meta_description'];

		$keywords = Tools::substr(Tools::strtolower($keywords), 0, 1000);

		if (!empty($keywords))
			$meta_keywords = rtrim($category_title.', '.$keywords.', '.$category_metas['meta_keywords'], ', ');

		$this->context->smarty->assign(
			array(
				'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
				'nb_products' => $nb_products,
				'category' => $category,
				'pages_nb' => (int)$pages_nb,
				'p' => (int)$p,
				'n' => (int)$n,
				'range' => (int)$range,
				'start' => (int)$start,
				'stop' => (int)$stop,
				'n_array' => ((int)Configuration::get('PS_PRODUCTS_PER_PAGE') != 10) ? array((int)Configuration::get('PS_PRODUCTS_PER_PAGE'), 10, 20, 50) :
					array(10, 20, 50),
				'comparator_max_item' => (int)Configuration::get('PS_COMPARATOR_MAX_ITEM'),
				'products' => $products,
				'products_per_page' => (int)Configuration::get('PS_PRODUCTS_PER_PAGE'),
				'static_token' => Tools::getToken(false),
				'page_name' => 'category',
				'nArray' => $n_array,
				'compareProducts' => CompareProduct::getCompareProducts((int)$this->context->cookie->id_compare)
			)
		);

		// Prevent bug with old template where category.tpl contain the title of the category and category-count.tpl do not exists
		$category_count = file_exists(_PS_THEME_DIR_.'category-count.tpl') ? $this->context->smarty->fetch(_PS_THEME_DIR_.'category-count.tpl') : '';
		$product_list = $nb_products == 0 ? $this->context->smarty->fetch(_ELASTICSEARCH_TEMPLATES_DIR_.'hook/elasticsearch-filter-no-products.tpl') :
			$this->context->smarty->fetch(_PS_THEME_DIR_.'product-list.tpl');

		$friendly_url = $_SERVER['REQUEST_URI'];
		$friendly_url = str_replace(_ELASTICSEARCH_AJAX_URI_.'?', '#', $friendly_url);
		$friendly_url = explode('&token', $friendly_url);
		$friendly_url = str_replace('&submitElasticsearchFilter=1', '', $friendly_url[0]);

		$vars = array(
			'filtersBlock' => utf8_encode($this->generateFiltersBlock($selected_filters)),
			'productList' => utf8_encode($product_list),
			'pagination' => $this->context->smarty->fetch(_PS_THEME_DIR_.'pagination.tpl'),
			'categoryCount' => $category_count,
			'meta_title' => $meta_title.' - '.Configuration::get('PS_SHOP_NAME'),
			'heading' => $meta_title,
			'meta_keywords' => isset($meta_keywords) ? $meta_keywords : null,
			'meta_description' => $meta_description,
			'current_friendly_url' => $friendly_url,
			'filters' => $filter_block['filters'],
			'nbRenderedProducts' => (int)$nb_products,
			'nbAskedProducts' => (int)$n
		);

		$vars = array_merge($vars, array(
			'pagination_bottom' => $this->context->smarty->assign('paginationId', 'bottom')->fetch(_PS_THEME_DIR_.'pagination.tpl')
		));

		return $vars;
	}

	private function getProductByFilters($selected_filters, $count_only = false)
	{
		//building search query for selected filters
		$query = $this->buildSearchQuery(
			'bool_must',
			$this->getProductsQueryByFilters($selected_filters, $filter)
		);

		$this->fixFilterQuery($filter);

		if ($count_only)
			return $this->search(
				'products',
				$query,
				null,
				null,
				null,
				null,
				$filter
			);

		$pagination = (int)Tools::getValue('n');
		$start = ($this->page - 1) * $pagination;
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

		$products = $this->search(
			'products',
			$query,
			$pagination ? $pagination : null,
			$start,
			$order_by,
			$order_way,
			$filter
		);

		$products_data = array();

		foreach ($products as $product)
			$products_data[] = array(
				'id_product' => $product['_id'],
				'out_of_stock' => $product['_source']['out_of_stock'],
				'id_category_default' => $product['_source']['id_category_default'],
				'link_rewrite' => $product['_source']['link_rewrite_'.$this->context->language->id],
				'name' => $product['_source']['name_'.$this->context->language->id],
				'description_short' => $product['_source']['description_short_'.$this->context->language->id],
				'ean13' => $product['_source']['ean13'],
				'id_image' => $product['_source']['id_image'],
				'customizable' => $product['_source']['customizable'],
				'minimal_quantity' => $product['_source']['minimal_quantity'],
				'available_for_order' => $product['_source']['available_for_order'],
				'show_price' => $product['_source']['show_price']
			);

		return $products_data;
	}

	public function getProductsQueryByFilters($selected_filters, &$filter_query = null)
	{
		$home_category = Configuration::get('PS_HOME_CATEGORY');
		$id_category = (int)Tools::getValue('id_category', Tools::getValue('id_elasticsearch_category', $home_category));

		if ($id_category == $home_category)
			return false;

		$query = array();

		$price_counter = 0;
		$weight_counter = 0;
		$price_query = array();
		$weight_query = array();
		$categories = array();

		foreach ($selected_filters as $key => $filter_values)
		{
			if (!count($filter_values))
				continue;

			preg_match('/^(.*[^_0-9])/', $key, $res);
			$key = $res[1];

			foreach ($filter_values as $value)
				switch ($key)
				{
					case 'id_feature':
						$parts = explode('_', $value);

						if (count($parts) != 2)
							break;

						$query[] = array('term' =>  array(
							'feature_'.$parts[0] => $parts[1]
						));
						break;

					case 'id_attribute_group':
						$parts = explode('_', $value);

						if (count($parts) != 2)
							break;

						$query[] = array('term' =>  array(
							'attribute_group_'.$parts[0] => $parts[1]
						));
						break;

					case 'category':
						$categories[] = array('term' => array('categories' => $value));
						break;

					case 'quantity':
						//values: 0 - not available, 1 - in stock
						if ($value == 0)
							$query[] = array('range' =>  array(
								'quantity' => array(
									'lte' => 0
								)
							));
						else if ($value == 1)
							$query[] = array('range' =>  array(
								'quantity' => array(
									'gte' => 1
								)
							));

						break;

					case 'manufacturer':
						$query[] = array('term' =>  array(
							'id_manufacturer' => $value
						));
						break;

					case 'condition':
						$query[] = array('term' =>  array(
							'condition' => $value
						));
						break;

					case 'weight':
						if ($weight_counter == 0)
							$weight_query['gte'] = $value;
						elseif ($weight_counter == 1)
							$weight_query['lte'] = $value;

						$weight_counter++;
						break;

					case 'price':
						if ($price_counter == 0)
							$price_query[0] = (int)$value;
						elseif ($price_counter == 1)
							$price_query[1] = ceil($value);

						$price_counter++;
						break;
				}
		}

		//completing price query
		if (isset($price_query[0]) && isset($price_query[1]))
		{
			$filter_query[] = array(
				'or' => array(
					array(
						'range' => array(
							'price_min_'.(int)$this->context->currency->id => array(
								'gte' => $price_query[0],
								'lte' => $price_query[1]
							)
						)
					),
					array(
						'range' => array(
							'price_min_'.(int)$this->context->currency->id => array(
								'lt' => $price_query[0]
							),
							'price_max_'.(int)$this->context->currency->id => array(
								'gt' => $price_query[0]
							)
						)
					),
					array(
						'range' => array(
							'price_min_'.(int)$this->context->currency->id => array(
								'lt' => $price_query[1]
							),
							'price_max_'.(int)$this->context->currency->id => array(
								'gt' => $price_query[1]
							)
						)
					)
				)
			);
		}

		//completing weight query
		if ($weight_query)
			$query[] = array('range' => array(
				'weight' => $weight_query
			));

		//completing categories query
		if ($categories)
			$filter_query[] = array('or' => $categories);
		else
			$query[] = array(
				'term' => array(
					'categories' => Tools::getValue('id_elasticsearch_category', Tools::getValue('id_category'))
				)
			);

		return $query;
	}

	public function changeQueryCategory(&$query, $new_category)
	{
		if (!$query)
			$query['term']['categories'] = $new_category;
		else
		{
			$length = count($query);

			for ($i = 0; $i < $length; $i++)
				foreach ($query[$i] as &$row)
					foreach (array_keys($row) as &$term)
						if ($term == 'categories')
							$row[$term] = $new_category;
		}
	}

	public function getProducts($selected_filters, &$products, &$nb_products, &$p, &$n, &$pages_nb, &$start, &$stop, &$range)
	{
		$products = $this->getProductByFilters($selected_filters);
		$products = Product::getProductsProperties((int)$this->context->cookie->id_lang, $products);
		$nb_products = $this->getProductByFilters($selected_filters, true);
		$range = 2; /* how many pages around page selected */

		$n = (int)Tools::getValue('n', Configuration::get('PS_PRODUCTS_PER_PAGE'));
		$p = $this->page;

		if ($n <= 0)
			$n = 1;

		if ($p < 0)
			$p = 0;

		if ($p > ($nb_products / $n))
			$p = ceil($nb_products / $n);

		$pages_nb = ceil($nb_products / (int)$n);
		$start = (int)$p - (int)$range;

		if ($start < 1)
			$start = 1;

		$stop = (int)$p + (int)$range;

		if ($stop > $pages_nb)
			$stop = (int)$pages_nb;

		foreach ($products as &$product)
		{
			if ($product['id_product_attribute'] && isset($product['product_attribute_minimal_quantity']))
				$product['minimal_quantity'] = $product['product_attribute_minimal_quantity'];
		}
	}

	private function showPriceFilter()
	{
		return Group::getCurrent()->show_prices;
	}

	private function filterVar($value)
	{
		if (version_compare(_PS_VERSION_, '1.6.0.7', '>=') === true)
			return Tools::purifyHTML($value);
		else
			return filter_var($value, FILTER_SANITIZE_STRING);
	}
}