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

<script type="text/javascript">
    $(document).ready(function(){
        $('#elasticsearch_form').submit(function(){
            $(this).find('input[type=submit]').attr('onclick', 'return false;');
        });
    });
</script>

<form action="" method="post" id="elasticsearch_form">
	<div class="panel">
		<h3>
			<i class="icon icon-cogs"></i>
			{l s='Elastic Search actions' mod='elasticsearch'}
		</h3>
		{if isset($shop_restriction) && $shop_restriction}
			<p class="alert alert-warning">
				{l s='Elastic Search inexing is disabled when all shops or group of shops are selected' mod='elasticsearch'}
			</p>
		{else}
			<p class="alert alert-info">
				<b>{$indexed_products|intval} / {$all_products|intval}</b> {l s='indexed products' mod='elasticsearch'}
			</p>
			<p>
				<input class="btn btn-default" type="submit" name="startIndexing" value="{l s='Reindex all products' mod='elasticsearch'}" />
				&nbsp;
				<input class="btn btn-default" type="submit" name="continueIndexing" value="{l s='Reindex missing products' mod='elasticsearch'}" />
			</p>
		{/if}
	</div>
</form>