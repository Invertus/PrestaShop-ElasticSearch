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

require_once(_ELASTICSEARCH_CORE_DIR_.'AbstractLogger.php');

abstract class SearchService extends Brad\AbstractLogger
{
    /* Other search services constants should be added here */
    const ELASTICSEARCH_INSTANCE = 1;
    const ELASTICSEARCH_SERVICE_CLASS_NAME = 'ElasticSearchService';

    public $errors = array();

    /* Array containing all active instances of search services */
    protected static $instance = array();

    public $client; // Search service client

    public $index_prefix;
    public $index; // Full index name (prefix + id_shop)

    /**
     * Returns search instance
     * @param int $type - which instance to use
     * @return null|object
     */
    public static function getInstance($type)
    {
        if (!isset(self::$instance[$type]))
        {
            $class = self::getClass($type);

            if (!$class)
            {
                self::log('Search service class is missing', array('class' => $class));
                return null;
            }

            if (!class_exists($class.'.php'))
                require_once(_ELASTICSEARCH_CLASSES_DIR_.$class.'.php');

            self::$instance[$type] = new $class();
        }

        return self::$instance[$type];
    }

    /**
     * Returns class name of search service if it exists
     * @param int $type Instance type
     * @return bool|string class name or false
     */
    public static function getClass($type)
    {
        switch ($type)
        {
            case self::ELASTICSEARCH_INSTANCE:
                if (!file_exists(_ELASTICSEARCH_CLASSES_DIR_.self::ELASTICSEARCH_SERVICE_CLASS_NAME.'.php'))
                    return false;
                return self::ELASTICSEARCH_SERVICE_CLASS_NAME;
        }

        return false;
    }

    /**
     * Initialize index name which can be accessed as $this->index
     */
    protected function initIndex()
    {
        $this->initIndexPrefix();

        if (!$this->index)
            $this->index = $this->index_prefix.Context::getContext()->shop->id;
    }

    abstract protected function initClient();

    abstract public function testSearchServiceConnection();

    abstract protected function initIndexPrefix();

    abstract public function getDocumentById($type, $id);

    abstract public function createDocument($body, $id, $type);

    abstract public function indexAllProducts($delete_old);

    abstract public function indexAllCategories();

    abstract public function buildSearchQuery($type, $term);

    abstract public function deleteDocumentById($id_shop, $id, $type);

    abstract public function documentExists($id_shop, $id, $type);

    abstract public function search($type, array $query, $pagination, $from, $order_by, $order_way, $filter);

    abstract public function getDocumentsCount($type, array $query, $filter = null);

    abstract public function indexExists($index_name);

    abstract protected function createIndex($index_name);

    abstract public function deleteShopIndex();
}