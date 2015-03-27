{**
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
*}
<div class="col-lg-4">
	<br />
	<form class="elasticsearch_search" method="post" action="{$link->getModuleLink('elasticsearch', 'elasticsearch')|escape:'htmlall':'UTF-8'}">
		<input class="col-lg-12" type="text" value="" placeholder="{l s='Search...' mod='elasticsearch'}" name="elasticsearch_query" autocomplete="off">
		<button class="btn btn-default button-search" name="submit_elasticsearch_query" type="submit"></button>
		<div class="elasticsearch_search_results"></div>
	</form>
</div>

<script>
	var elasticsearch_ajax_uri = '{$smarty.const._ELASTICSEARCH_AJAX_URI_|escape:'htmlall':'UTF-8'}';
	var elasticsearch_min_words_count = '{Configuration::get('ELASTICSEARCH_SEARCH_MIN')|intval}';
</script>