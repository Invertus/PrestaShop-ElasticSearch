{**
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