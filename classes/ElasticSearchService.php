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

class ElasticSearchService extends SearchService
{
	const INSTANCE_TYPE = self::ELASTICSEARCH_INSTANCE;
	const FILENAME = 'ElasticSearchService';

	public $module_instance = null;

	private $host = null;

	public function __construct($module_name = 'elasticsearch')
	{
		$this->initIndex();
		$this->module_instance = Module::getInstanceByName($module_name);
		$this->host = Configuration::get('ELASTICSEARCH_HOST');

		if (Tools::strpos($this->host, 'http://') === false && Tools::strpos($this->host, 'https://') === false)
			$this->host = 'http://'.$this->host;

		$this->initClient();
	}

	protected function initIndexPrefix()
	{
		if ($this->index_prefix)
			return;

		if (!($prefix = Configuration::get('ELASTICSEARCH_INDEX_PREFIX')))
		{
			$prefix = Tools::passwdGen().'_';
			Configuration::updateValue('ELASTICSEARCH_INDEX_PREFIX', $prefix);
		}

		$this->index_prefix = $prefix;
	}

	public function getDocumentById($type, $id)
	{
		$params = array(
			'index' => $this->index,
			'type' => $type,
			'id' => $id
		);

		return $this->client->get($params);
	}

	protected function initClient()
	{
		if (!$this->host)
		{
			$this->errors[] = $this->module_instance->l('Service host must be entered in order to use elastic search', self::FILENAME);
			return false;
		}

		$params = array();
		$params['hosts'] = array(
			$this->host         				// Domain + Port
		);

		$this->client = new Elasticsearch\Client($params);
	}

	public function testSearchServiceConnection()
	{
		if (!$this->client || !$this->host)
			return false;

		$response = Tools::jsonDecode(Tools::file_get_contents($this->host));

		if (!$response)
			return false;

		return isset($response->status) && $response->status = '200';
	}

	private function generateFilterBodyByProduct($id_product)
	{
		$product_obj = new Product($id_product, true);
		$attributes = Product::getAttributesInformationsByProduct($id_product);
		$features = $product_obj->getFeatures();

		$body = array();
		$body['categories'] = $product_obj->getCategories();
		$body['condition'] = $product_obj->condition;
		$body['id_manufacturer'] = $product_obj->id_manufacturer;
		$body['manufacturer_name'] = $product_obj->manufacturer_name;
		$body['weight'] = $product_obj->weight;
		$body['out_of_stock'] = $product_obj->out_of_stock;
		$body['id_category_default'] = $product_obj->id_category_default;
		$body['ean13'] = $product_obj->ean13;
		$body['available_for_order'] = $product_obj->available_for_order;
		$body['customizable'] = $product_obj->customizable;
		$body['minimal_quantity'] = $product_obj->minimal_quantity;
		$body['show_price'] = $product_obj->show_price;

		$cover = Product::getCover($product_obj->id);
		$body['id_image'] = isset($cover['id_image']) ? $cover['id_image'] : $cover;

		if ($attributes)
			foreach ($attributes as $attribute)
				$body['attribute_group_'.$attribute['id_attribute_group']][] = $attribute['id_attribute'];

		if ($features)
			foreach ($features as $feature)
				$body['feature_'.$feature['id_feature']] = $feature['id_feature_value'];

		return array_merge($body, $this->getProductPricesForIndexing($product_obj->id));
	}

	private function generateSearchKeywordsBodyByProduct($id_product)
	{
		$product_obj = new Product($id_product, true);

		$body = array();
		$body['reference'] = $product_obj->reference;

		foreach ($product_obj->name as $id_lang => $name)
		{
			$body['name_'.$id_lang] = $name;
			$body['link_rewrite_'.$id_lang] = $product_obj->link_rewrite[$id_lang];
			$body['description_short_'.$id_lang] = $product_obj->description_short[$id_lang];
			$body['search_keywords_'.$id_lang][] = $product_obj->reference;
			$body['search_keywords_'.$id_lang][] = $name;
			$body['search_keywords_'.$id_lang][] = strip_tags($product_obj->description[$id_lang]);
			$body['search_keywords_'.$id_lang][] = strip_tags($product_obj->description_short[$id_lang]);
			$body['search_keywords_'.$id_lang][] = $product_obj->manufacturer_name;
		}

		$category = new Category($product_obj->id_category_default);

		foreach ($category->name as $id_lang => $category_name)
			$body['search_keywords_'.$id_lang][] = $category_name;

		foreach (Language::getLanguages() as $lang)
			$body['search_keywords_'.$lang['id_lang']] = Tools::strtolower(implode(' ', array_filter($body['search_keywords_'.$lang['id_lang']])));

		$body['quantity'] = $product_obj->quantity;
		$body['price'] = $product_obj->price;

		return $body;
	}

	public function generateSearchBodyByProduct($id_product)
	{
		return array_merge($this->generateSearchKeywordsBodyByProduct($id_product), $this->generateFilterBodyByProduct($id_product));
	}

	public function generateSearchBodyByCategory($id_category)
	{
		$category = new Category($id_category);

		$body = array();

		foreach ($category->name as $id_lang => $name)
			$body['name_'.$id_lang] = $name;

		$body['id_parent'] = $category->id_parent;
		$body['level_depth'] = $category->level_depth;
		$body['nleft'] = $category->nleft;
		$body['nright'] = $category->nright;
		$body['is_root_category'] = $category->is_root_category;

		return $body;
	}

	public function createDocument($body, $id = null, $type = 'products')
	{
		try
		{
			$params = array();

			if ($id)
				$params['id'] = $id;

			$params['index'] = $this->index;
			$params['type'] = $type;
			$params['body'] = $body;

			return $this->client->index($params);
		} catch (Exception $e) {
			self::log('Unable to create document', array_merge(array('Message' => $e->getMessage()), get_defined_vars()));
			return false;
		}
	}

	public static function getProductPricesForIndexing($id_product)
	{
		static $groups = null;

		if (is_null($groups))
		{
			$groups = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT id_group FROM `'._DB_PREFIX_.'group_reduction`');
			if (!$groups)
				$groups = array();
		}

		$id_shop = (int)Context::getContext()->shop->id;

		static $currency_list = null;

		if (is_null($currency_list))
			$currency_list = Currency::getCurrencies(false, 1, new Shop($id_shop));

		$min_price = array();
		$max_price = array();

		if (Configuration::get('ELASTICSEARCH_PRICE_USETAX'))
			$max_tax_rate = Db::getInstance()->getValue('
				SELECT max(t.rate) max_rate
				FROM `'._DB_PREFIX_.'product_shop` p
				LEFT JOIN `'._DB_PREFIX_.'tax_rules_group` trg ON (trg.id_tax_rules_group = p.id_tax_rules_group AND p.id_shop = '.(int)$id_shop.')
				LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON (tr.id_tax_rules_group = trg.id_tax_rules_group)
				LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.id_tax = tr.id_tax AND t.active = 1)
				WHERE id_product = '.(int)$id_product.'
				GROUP BY id_product');
		else
			$max_tax_rate = 0;

		$product_min_prices = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT id_shop, id_currency, id_country, id_group, from_quantity
			FROM `'._DB_PREFIX_.'specific_price`
			WHERE id_product = '.(int)$id_product
		);

		// Get min price
		foreach ($currency_list as $currency)
		{
			$price = Product::priceCalculation($id_shop, (int)$id_product, null, null, null, null,
				$currency['id_currency'], null, null, false, 6, false, true, true,
				$specific_price_output, true);

			if (!isset($max_price[$currency['id_currency']]))
				$max_price[$currency['id_currency']] = 0;
			if (!isset($min_price[$currency['id_currency']]))
				$min_price[$currency['id_currency']] = null;
			if ($price > $max_price[$currency['id_currency']])
				$max_price[$currency['id_currency']] = $price;
			if ($price == 0)
				continue;
			if (is_null($min_price[$currency['id_currency']]) || $price < $min_price[$currency['id_currency']])
				$min_price[$currency['id_currency']] = $price;
		}

		foreach ($product_min_prices as $specific_price)
			foreach ($currency_list as $currency)
			{
				if ($specific_price['id_currency'] && $specific_price['id_currency'] != $currency['id_currency'])
					continue;
				$price = Product::priceCalculation((($specific_price['id_shop'] == 0) ? null : (int)$specific_price['id_shop']), (int)$id_product,
					null, (($specific_price['id_country'] == 0) ? null : $specific_price['id_country']), null, null,
					$currency['id_currency'], (($specific_price['id_group'] == 0) ? null : $specific_price['id_group']),
					$specific_price['from_quantity'], false, 6, false, true, true, $specific_price_output, true);

				if (!isset($max_price[$currency['id_currency']]))
					$max_price[$currency['id_currency']] = 0;
				if (!isset($min_price[$currency['id_currency']]))
					$min_price[$currency['id_currency']] = null;
				if ($price > $max_price[$currency['id_currency']])
					$max_price[$currency['id_currency']] = $price;
				if ($price == 0)
					continue;
				if (is_null($min_price[$currency['id_currency']]) || $price < $min_price[$currency['id_currency']])
					$min_price[$currency['id_currency']] = $price;
			}

		foreach ($groups as $group)
			foreach ($currency_list as $currency)
			{
				$price = Product::priceCalculation(null, (int)$id_product, null, null, null, null, (int)$currency['id_currency'], (int)$group['id_group'],
					null, false, 6, false, true, true, $specific_price_output, true);

				if (!isset($max_price[$currency['id_currency']]))
					$max_price[$currency['id_currency']] = 0;
				if (!isset($min_price[$currency['id_currency']]))
					$min_price[$currency['id_currency']] = null;
				if ($price > $max_price[$currency['id_currency']])
					$max_price[$currency['id_currency']] = $price;
				if ($price == 0)
					continue;
				if (is_null($min_price[$currency['id_currency']]) || $price < $min_price[$currency['id_currency']])
					$min_price[$currency['id_currency']] = $price;
			}

		$values = array();
		foreach ($currency_list as $currency)
		{
			$values['price_min_'.(int)$currency['id_currency']] = (int)$min_price[$currency['id_currency']];
			$values['price_max_'.(int)$currency['id_currency']] = (int)Tools::ps_round($max_price[$currency['id_currency']] * (100 + $max_tax_rate) / 100, 0);
		}

		return $values;
	}

	public function indexAllProducts($delete_old = true)
	{
		if ($delete_old)
			$this->deleteShopIndex();

		if (!$this->createIndexForCurrentShop())
			return false;

		$id_shop = (int)Context::getContext()->shop->id;
		$shop_products = $this->module_instance->getAllProducts($id_shop);

		if (!$shop_products)
			return true;

		foreach ($shop_products as $product)
		{
			if ($this->documentExists($id_shop, (int)$product['id_product']))
				continue;

			$result = $this->createDocument(
				$this->generateSearchBodyByProduct((int)$product['id_product']),
				$product['id_product']
			);

			if (!isset($result['created']) || $result['created'] !== true)
				$this->errors[] = sprintf($this->module_instance->l('Unable to index product #%d'), $product['id_product']);
		}

		//indexing categories if products indexing succeeded
		return $this->errors ? false : $this->indexAllCategories();
	}

	public function indexAllCategories()
	{
		$id_shop = (int)Context::getContext()->shop->id;
		$shop_categories = $this->module_instance->getAllCategories($id_shop);

		if (!$shop_categories)
			return true;

		foreach ($shop_categories as $category)
		{
			if ($this->documentExists($id_shop, (int)$category['id_category'], 'categories'))
				continue;

			$result = $this->createDocument(
				$this->generateSearchBodyByCategory((int)$category['id_category']),
				$category['id_category'],
				'categories'
			);

			if (!isset($result['created']) || $result['created'] !== true)
				$this->errors[] = sprintf($this->module_instance->l('Unable to index category #%d'), $category['id_category']);
		}

		return $this->errors ? false : true;
	}

	public function buildSearchQuery($type, $term = '')
	{
		$type = pSQL($type);

		switch ($type)
		{
			case 'all':
				return array (
					'match_all' => array()
				);
			default:
			case 'products':
				$term = Tools::strtolower($term);
				return array (
					'wildcard' => array(
						$type => '*'.pSQL($term).'*'
					)
				);
			case 'strict_search':
				return array(
					'term' => $term
				);
			case 'range':
				return array(
					'range' => $term
				);
			case 'bool_must':
				return array(
					'bool' => array(
						'must' => $term
					)
				);
			case 'bool_should':
				return array(
					'bool' => array(
						'should' => $term
					)
				);
			case 'filter_or':
				return array(
					'or' => $term
				);
		}
	}

	public function deleteDocumentById($id_shop, $id, $type = 'products')
	{
		if (!$this->documentExists($id_shop, $id, $type))
			return true;

		$params = array(
			'index' => $this->index_prefix.$id_shop,
			'type' => $type,
			'id' => $id
		);

		return $this->client->delete($params);
	}

	public function documentExists($id_shop, $id, $type = 'products')
	{
		$params = array(
			'index' => $this->index_prefix.$id_shop,
			'type' => $type,
			'id' => $id
		);

		return (bool)$this->client->exists($params);
	}

	public function search($type, array $query, $pagination = 50, $from = 0, $order_by = null, $order_way = null, $filter = null)
	{
		$params = array(
			'index' => $this->index,
			'body' => array()
		);

		if ($query)
			$params['body']['query'] = $query;

		if ($type !== null)
			$params['type'] = $type;

		if ($filter !== null)
			$params['body']['filter'] = $filter;

		if ($pagination !== null)
			$params['size'] = $pagination;               // how many results *per shard* you want back

		if ($from !== null)
			$params['from'] = $from;

		try
		{
			if ($pagination === null && $from === null)
			{
				$params['search_type'] = 'count';
				return (int)$this->client->search($params)['hits']['total'];
			}

			if ($order_by && $order_way)
				$params['sort'] = array($order_by.':'.$order_way);

			return $this->client->search($params)['hits']['hits'];   // Execute the search
		} catch (Exception $e) {
			self::log('Search failed', array('Message' => $e->getMessage(), 'index' => $this->index, 'params' => $params));
			return array();
		}
	}

	public function getDocumentsCount($type, array $query, $filter = null)
	{
		return $this->search($type, $query, null, null, null, null, $filter);
	}

	private function createIndexForCurrentShop()
	{
		if (!$this->createIndex($this->index))
		{
			$this->errors[] = $this->module_instance->l('Unable to create search index', self::FILENAME);
			return false;
		}

		return true;
	}

	public function indexExists($index_name)
	{
		$params = array(
			'index' => $index_name
		);

		return $this->client->indices()->exists($params);
	}

	protected function createIndex($index_name)
	{
		if ($this->indexExists($index_name))
			return true;

		if (!$index_name)
			return false;

		$index_params = array();

		$index_params['index'] = $index_name;
		$index_params['body']['settings']['number_of_shards'] = 1;
		$index_params['body']['settings']['number_of_replicas'] = 1;
		$index_params['body']['mappings']['products']['properties']['weight'] = array(
			'type' => 'double'
		);
		$index_params['body']['mappings']['categories']['properties']['nleft'] = array(
			'type' => 'long'
		);
		$index_params['body']['mappings']['categories']['properties']['nright'] = array(
			'type' => 'long'
		);

		return $this->client->indices()->create($index_params);
	}

	public function deleteShopIndex()
	{
		$delete_params = array();

		if (Shop::getContext() == Shop::CONTEXT_SHOP)
		{
			$index_name = $this->index;

			if (!$this->indexExists($index_name))
				return true;

			$delete_params['index'] = $index_name;
			$this->client->indices()->delete($delete_params);
			Configuration::deleteFromContext('ELASTICSEARCH_INDEX_PREFIX');
		}
		elseif (Shop::getContext() == Shop::CONTEXT_ALL)
		{
			$index_name = $this->index_prefix.'*';

			if (!$this->indexExists($index_name))
				return true;

			$delete_params['index'] = $index_name;
			$this->client->indices()->delete($delete_params);

			Configuration::deleteByName('ELASTICSEARCH_INDEX_PREFIX');
		}
		elseif (Shop::getContext() == Shop::CONTEXT_GROUP)
		{
			$id_shop_group = Context::getContext()->shop->id_shop_group;
			foreach (Shop::getShops(false, $id_shop_group, true) as $id_shop)
			{
				$index_name = $this->index_prefix.(int)$id_shop;

				if (!$this->indexExists($index_name))
					return true;

				$delete_params['index'] = $index_name;
				$this->client->indices()->delete($delete_params);
				$id = Configuration::getIdByName('ELASTICSEARCH_INDEX_PREFIX', $id_shop_group, $id_shop);
				$configuration = new Configuration($id);
				$configuration->delete();
			}
		}
	}
}