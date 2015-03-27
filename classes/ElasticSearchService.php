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

class ElasticSearchService
{
	const FILENAME = 'ElasticSearchService.php';

	public $module_instance = null;
	public $errors = array();
	public $client = null;
	public $index_prefix = '';

	private $host = null;

	public function __construct($module_name = 'elasticsearch')
	{
		$this->initIndexPrefix();
		$this->module_instance = Module::getInstanceByName($module_name);
		$this->host = Configuration::get('ELASTICSEARCH_HOST');

		if (Tools::strpos($this->host, 'http://') === false && Tools::strpos($this->host, 'https://') === false)
			$this->host = 'http://'.$this->host;

		$this->initClient();
	}

	private function initIndexPrefix()
	{
		if (!($prefix = Configuration::get('ELASTICSEARCH_INDEX_PREFIX')))
		{
			$prefix = Tools::strtolower(Tools::passwdGen().'_');
			Configuration::updateValue('ELASTICSEARCH_INDEX_PREFIX', $prefix);
		}

		$this->index_prefix = $prefix;
	}

	private function initClient()
	{
		if (!$this->client)
		{
			if (!$this->host)
			{
				$this->errors[] = $this->module_instance->l('Service host must be entered in order to use elastic search', self::FILENAME);
				return false;
			}

			require_once(_PS_MODULE_DIR_.'elasticsearch/vendor/autoload.php');

			$params = array();
			$params['hosts'] = array(
				$this->host         				// Domain + Port
			);

			$this->client = new Elasticsearch\Client($params);
		}
	}

	public function testElasticSearchServiceConnection()
	{
		if (!$this->client || !$this->host)
			return false;

		$response = Tools::jsonDecode(Tools::file_get_contents($this->host));

		if (!$response)
			return false;

		return isset($response->status) && $response->status = '200';
	}

	public function generateSearchBodyByProduct($id_product)
	{
		$product_obj = new Product($id_product, true);

		$body = array();
		$body['reference'] = $product_obj->reference;

		foreach ($product_obj->name as $id_lang => $name)
		{
			$body['name_'.$id_lang] = $name;
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
			$body['search_keywords_'.$lang['id_lang']] = implode(' ', array_filter($body['search_keywords_'.$lang['id_lang']]));

		$body['quantity'] = $product_obj->quantity;
		$body['price'] = $product_obj->price;

		return $body;
	}

	public function createDocument($index, $body, $id = null, $type = 'products')
	{
		$params = array();

		if ($id)
			$params['id'] = $id;

		$params['index'] = $index;
		$params['type'] = $type;
		$params['body'] = $body;

		return $this->client->index($params);
	}

	public function indexAllProducts($delete_old = true)
	{
		if ($delete_old)
			$this->deleteShopIndex();

		if (!$this->createIndexForCurrentShop())
			return false;

		$id_shop = (int)Context::getContext()->shop->id;
		$shop_products = $this->getAllProducts($id_shop);

		if (!$shop_products)
			return true;

		foreach ($shop_products as $product)
		{
			if ($this->documentExists($this->index_prefix.$id_shop, (int)$product['id_product']))
				continue;

			$result = $this->createDocument(
				$this->index_prefix.$id_shop,
				$this->generateSearchBodyByProduct((int)$product['id_product']),
				$product['id_product']
			);

			if (!isset($result['created']) || $result['created'] !== true)
				$this->errors[] = sprintf($this->module_instance->l('Unable to index product #%d'), $product['id_product']);
		}

		return $this->errors ? false : true;
	}

	public function getAllProducts($id_shop)
	{
		$products = Db::getInstance()->executeS('
			SELECT `id_product`
			FROM `'._DB_PREFIX_.'product_shop`
			WHERE `active` = 1
				AND `id_shop` = "'.(int)$id_shop.'"
				AND `visibility` IN ("both", "search")'
		);

		return $products ? $products : array();
	}

	public function searchByType($type, $index = null)
	{
		if ($index === null)
			$index = $this->index_prefix.Context::getContext()->shop->id;

		$params = array();
		$params['index'] = $index;
		$params['type'] = $type;

		return $this->client->search($params);
	}

	public function buildSearchQuery($type, $term)
	{
		$term = Tools::strtolower($term);
		$type = pSQL($type);

		switch ($type)
		{
			case 'all':
				return array (
					'match_all' => array()
				);
			default:
			case 'products':
				return array (
					'wildcard' => array(
						$type => '*'.pSQL($term).'*'
					)
				);
		}
	}

	public function deleteDocumentById($index_name, $id, $type = 'products')
	{
		if (!$this->documentExists($index_name, $id, $type))
			return true;

		$params = array(
			'index' => $index_name,
			'type' => $type,
			'id' => $id
		);

		return $this->client->delete($params);
	}

	public function documentExists($index, $id, $type = 'products')
	{
		$params = array(
			'index' => $index,
			'type' => $type,
			'id' => $id
		);

		return (bool)$this->client->exists($params);
	}

	public function search($index, $type, array $query, $pagination = 50, $from = 0, $order_by = null, $order_way = null)
	{
		if (!$this->indexExists($index))
			return array();

		$params = array(
			'index' => $index,
			'type' => $type,
			'body' => array(
				'query' => $query
			)
		);

		if ($pagination !== null)
			$params['size'] = $pagination;               // how many results *per shard* you want back

		if ($from !== null)
			$params['from'] = $from;

		if ($pagination === null && $from === null)
		{
			$params['search_type'] = 'count';
			return $this->client->search($params)['hits']['total'];
		}

		if ($order_by && $order_way)
			$params['sort'] = array($order_by.':'.$order_way);

		return $this->client->search($params)['hits']['hits'];   // Execute the search
	}

	private function createIndexForCurrentShop()
	{
		if (!$this->createIndex($this->index_prefix.(int)Context::getContext()->shop->id))
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

	private function createIndex($index_name)
	{
		if ($this->indexExists($index_name))
			return true;

		if (!$index_name)
			return false;

		$index_params = array();

		$index_params['index'] = $index_name;
		$index_params['body']['settings']['number_of_shards'] = 1;
		$index_params['body']['settings']['number_of_replicas'] = 1;

		return $this->client->indices()->create($index_params);
	}

	public function deleteShopIndex()
	{
		$delete_params = array();

		if (Shop::getContext() == Shop::CONTEXT_SHOP)
		{
			$index_name = $this->index_prefix.(int)Context::getContext()->shop->id;

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