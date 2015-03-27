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
<form action="" method="post">
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