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

class AdminElasticSearchSettingsController extends ModuleAdminController
{
	public function __construct()
	{
		$this->meta_title = $this->l('Elastic Search module settings');

		parent::__construct();

		$this->token = Tools::getAdminTokenLite('AdminModules');
		$this->table = 'Configuration';
		$this->class = 'Configuration';

		self::$currentIndex = 'index.php?controller=AdminModules&configure='.$this->module->name;

		$this->initOptionFields();
	}

	public function updateOptions()
	{
		$this->processUpdateOptions();
	}

	private function initOptionFields()
	{
		$this->fields_options = array(
			'main_settings' => array(
				'title' => $this->l('Elastic Search settings'),
				'fields' => array(
					'ELASTICSEARCH_SEARCH_DISPLAY' => array(
						'type' => 'bool',
						'title' => $this->l('Display search block'),
						'hint' => $this->l('Search block in page top')
					),
					'ELASTICSEARCH_SEARCH' => array(
						'type' => 'bool',
						'title' => $this->l('Instant search'),
						'hint' => $this->l('Instant search block under search input')
					),
					'ELASTICSEARCH_AJAX_SEARCH' => array(
						'type' => 'bool',
						'title' => $this->l('Ajax search'),
						'hint' => $this->l('Search results will appear in the page immediately as the user types in search block')
					),
					'ELASTICSEARCH_SEARCH_COUNT' => array(
						'title' => $this->l('Instant search results count'),
						'size' => 3,
						'cast' => 'intval',
						'validation' => 'isInt',
						'type' => 'text',
						'hint' => $this->l('Number of records in search results list under search input'),
						'suffix' => $this->l('results')
					),
					'ELASTICSEARCH_SEARCH_MIN' => array(
						'title' => $this->l('Min. word length'),
						'size' => 3,
						'cast' => 'intval',
						'validation' => 'isUnsignedInt',
						'type' => 'text',
						'hint' => $this->l('Number of symbols typed into search input when instant search starts working'),
						'suffix' => $this->l('symbols')
					),
					'ELASTICSEARCH_HOST' => array(
						'title' => $this->l('Service host'),
						'size' => 3,
						'validation' => 'isUrl',
						'type' => 'text',
						'hint' => $this->l('URL:PORT'),
						'required' => true
					)

				),
				'submit' => array('title' => $this->l('Save'))
			)
		);
	}
}