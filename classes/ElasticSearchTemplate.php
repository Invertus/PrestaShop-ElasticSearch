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

class ElasticSearchTemplate extends ObjectModel
{
	public $id;

	public $id_elasticsearch_template = 0;

	public $name = '';

	public $filters = '';

	public $n_categories = '';

	public $date_add;

	public static $definition = array(
		'table' => 'elasticsearch_template',
		'primary' => 'id_elasticsearch_template',
		'fields' => array(
			'name' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32),
			'filters' => array('type' => self::TYPE_STRING, 'validate' => 'isAnything'),
			'n_categories' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
			'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'copy_post' => false)
		),
	);

	public static function getAttributes()
	{
		return DB::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT ag.id_attribute_group, ag.is_color_group, agl.name, COUNT(DISTINCT(a.id_attribute)) n
			FROM '._DB_PREFIX_.'attribute_group ag
			LEFT JOIN '._DB_PREFIX_.'attribute_group_lang agl ON (agl.id_attribute_group = ag.id_attribute_group)
			LEFT JOIN '._DB_PREFIX_.'attribute a ON (a.id_attribute_group = ag.id_attribute_group)
			WHERE agl.id_lang = '.(int)Context::getContext()->language->id.'
			GROUP BY ag.id_attribute_group'
		);
	}

	public static function getFeatures()
	{
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT fl.id_feature, fl.name, COUNT(DISTINCT(fv.id_feature_value)) n
			FROM '._DB_PREFIX_.'feature_lang fl
			LEFT JOIN '._DB_PREFIX_.'feature_value fv ON (fv.id_feature = fl.id_feature)
			WHERE (fv.custom IS NULL OR fv.custom = 0) AND fl.id_lang = '.(int)Context::getContext()->language->id.'
			GROUP BY fl.id_feature'
		);
	}

	public static function getList()
	{
		return DB::getInstance()->executeS('
			SELECT *
			FROM '._DB_PREFIX_.'elasticsearch_template
			ORDER BY date_add DESC
		');
	}
}