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

class ElasticSearchElasticSearchModuleFrontController extends FrontController
{
    const FILENAME = 'elasticsearch';

    private $module_instance;

    public function __construct()
    {
        $this->module_instance = Module::getInstanceByName('elasticsearch');

        $search = $this->module_instance->getSearchServiceObject();

        if (!$search->testSearchServiceConnection())
            Controller::getController('PageNotFoundController')->run();

        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        if ($query = Tools::getValue('elasticsearch_query'))
            Tools::redirect($this->context->link->getModuleLink('elasticsearch', 'elasticsearch', array('id_elasticsearch' => 1)).
                '&'.http_build_query(array('search' => $query)));

        $products = $this->getSearchProducts();
        $nbrProducts = $this->getSearchProducts(true);

        $this->context->smarty->assign(array(
            'products' => $products,
            'allow_oosp' => (int)Configuration::get('PS_ORDER_OUT_OF_STOCK'),
            'page_name' => 'best-sales',
            'request' => $this->context->link->getModuleLink('elasticsearch', 'elasticsearch', array('id_elasticsearch' => 1)).'&'.
                http_build_query(array('search' => Tools::getValue('search'))),
            'current_url' => $this->context->link->getModuleLink('elasticsearch', 'elasticsearch', array('id_elasticsearch' => 1)).'&'.
                http_build_query(array('search' => Tools::getValue('search'))),
            'nbr_products' => $nbrProducts,
        ));

        $this->productSort();
        $this->pagination($nbrProducts);
    }

    public function setMedia()
    {
        parent::setMedia();

        $this->addCSS(_THEME_CSS_DIR_.'product_list.css');
    }

    public function getSearchProducts($no_filter = false)
    {
        $search_value = Tools::getValue('search');

        $order_by_values = array(0 => 'name', 1 => 'price', 6 => 'quantity', 7 => 'reference');
        $order_way_values = array(0 => 'asc', 1 => 'desc');
        $order_by = Tools::strtolower(
            Tools::getValue('orderby',
            isset($order_by_values[(int)Configuration::get('PS_PRODUCTS_ORDER_BY')]) ?
                $order_by_values[(int)Configuration::get('PS_PRODUCTS_ORDER_BY')] : null)
        );
        $order_way = Tools::strtolower(Tools::getValue('orderway',
            isset($order_way_values[(int)Configuration::get('PS_PRODUCTS_ORDER_WAY')]) ?
                $order_way_values[(int)Configuration::get('PS_PRODUCTS_ORDER_WAY')] : null)
        );

        if ($order_by == 'name')
            $order_by .= '_'.(int)Context::getContext()->language->id;

        $this->context->smarty->assign('search_value', $search_value);

        if (!$search_value)
        {
            $this->context->smarty->assign('warning_message', $this->module_instance->l('Please enter a search keyword', self::FILENAME));
            return $no_filter ? 0 : array();
        }

        $default_products_per_page = max(1, (int)Configuration::get('PS_PRODUCTS_PER_PAGE'));
        $n_array = array($default_products_per_page, $default_products_per_page * 2, $default_products_per_page * 5);
        $this->n = $default_products_per_page;

        if (isset($this->context->cookie->nb_item_per_page) && in_array($this->context->cookie->nb_item_per_page, $n_array))
            $this->n = (int)$this->context->cookie->nb_item_per_page;

        if ((int)Tools::getValue('n')) {
            $this->n = (int)Tools::getValue('n');
        }

        $this->p = (int)Tools::getValue('p', 1);

        if ($this->p < 1)
            $this->p = 1;

        if ($this->n < 1)
            $this->n = 1;

        if (!is_numeric($this->p) || $this->p < 1)
            Tools::redirect($this->context->link->getPaginationLink(false, false, $this->n, false, 1, false));

        $type = 'products';
        $property = 'search_keywords_'.(int)$this->context->language->id;

        $search = $this->module_instance->getSearchServiceObject();
        $query = $search->buildSearchQuery($property, $search_value);

        $from = $no_filter ? null : ((int)$this->p - 1) * (int)$this->n;
        $pagination = $no_filter ? null : (int)$this->n;

        $result = $search->search($type, $query, $pagination, $from, $order_by, $order_way);

        if ($no_filter)
            return $result ? $result : 0;

        $search_result = array();

        if (isset($result))
            $global_allow_oosp = (int)Configuration::get('PS_ORDER_OUT_OF_STOCK');

            foreach ($result as $product)  {

                $row = array();
                $row['id_product'] = $product['_id'];
                $row['id_image'] = $product['_source']['id_image'];
                $row['out_of_stock'] = $product['_source']['out_of_stock'];
                $row['id_category_default'] = $product['_source']['id_category_default'];
                $row['ean13'] = $product['_source']['ean13'];
                $row['link_rewrite'] = $product['_source']['link_rewrite_'.$this->context->language->id];

                $allow_oosp = $row['out_of_stock'] == AbstractFilter::PRODUCT_OOS_ALLOW_ORDERS ||
                    $row['out_of_stock'] == AbstractFilter::PRODUCT_OOS_USE_GLOBAL && $global_allow_oosp;

                $row['allow_oosp'] = $allow_oosp;

                $product_properties = Product::getProductProperties($this->context->language->id, $row);

                foreach ($product['_source'] as $key => $value) {
                    if (!array_key_exists($key, $product_properties)) {
                        $product_properties[$key] = $value;
                    }
                }

                $product_properties['name'] = $product['_source']['name_'.$this->context->language->id];
                $product_properties['description_short'] =
                    $product['_source']['description_short_'.$this->context->language->id];

                $search_result[] = $product_properties;
            }

        $this->addColorsToProductList($search_result);

        if (empty($search_result))
            $this->context->smarty->assign('warning_message',
                sprintf($this->module_instance->l('No results were found for your search "%s"', self::FILENAME), $search_value));

        return $search_result;
    }

    public function displayContent()
    {
        parent::displayContent();

        $template_filename = _ELASTICSEARCH_TEMPLATES_DIR_.'front/elasticsearch.tpl';

        if (file_exists($template_filename))
            $this->context->smarty->display($template_filename);
    }
}