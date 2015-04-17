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
<section class="filter_panel">
	<header class="clearfix">
		<span class="label-tooltip pull-left" data-toggle="tooltip" title="" data-original-title="{l s='You can drag and drop filters to adjust position' mod='elasticsearch'}"><b>{l s='Filters:' mod='elasticsearch'}</b>&nbsp;</span>
		<span class="badge pull-left" id="selected_filters">0</span>
		<span class="badge pull-right">{l s='Total filters: %s'|sprintf:$total_filters mod='elasticsearch'}</span>
	</header>
	<section class="filter_list">
		<ul class="list-unstyled sortable">
			<li class="filter_list_item" draggable="true">
				<div class="col-lg-2">
					<label class="switch-light prestashop-switch fixed-width-lg">
						<input name="elasticsearch_selection_subcategories" id="elasticsearch_selection_subcategories" type="checkbox" />
						<span>
							<span>{l s='Yes' mod='elasticsearch'}</span>
							<span>{l s='No' mod='elasticsearch'}</span>
						</span>
						<a class="slide-button btn"></a>
					</label>
				</div>
				<div class="col-lg-4">
					<h4>{l s='Sub-categories filter' mod='elasticsearch'}</h4>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter result limit:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_subcategories_filter_show_limit">
							<option value="0">{l s='No limit' mod='elasticsearch'}</option>
							<option value="4">4</option>
							<option value="5">5</option>
							<option value="10">10</option>
							<option value="20">20</option>
						</select>
					</div>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter style:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_subcategories_filter_type">
							<option value="0">{l s='Checkbox' mod='elasticsearch'}</option>
							<option value="1">{l s='Radio button' mod='elasticsearch'}</option>
							<option value="2">{l s='Drop-down list' mod='elasticsearch'}</option>
						</select>
					</div>
				</div>
			</li>
			<li class="filter_list_item" draggable="true">
				<div class="col-lg-2">
					<label class="switch-light prestashop-switch fixed-width-lg">
						<input name="elasticsearch_selection_stock" id="elasticsearch_selection_stock" type="checkbox" />
						<span>
							<span>{l s='Yes' mod='elasticsearch'}</span>
							<span>{l s='No' mod='elasticsearch'}</span>
						</span>
						<a class="slide-button btn"></a>
					</label>
				</div>
				<div class="col-lg-4">
					<span class="module_name">{l s='Product stock filter' mod='elasticsearch'}</span>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter result limit:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_stock_filter_show_limit">
							<option value="0">{l s='No limit' mod='elasticsearch'}</option>
							<option value="4">4</option>
							<option value="5">5</option>
							<option value="10">10</option>
							<option value="20">20</option>
						</select>
					</div>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter style:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_stock_filter_type">
							<option value="0">{l s='Checkbox' mod='elasticsearch'}</option>
							<option value="1">{l s='Radio button' mod='elasticsearch'}</option>
							<option value="2">{l s='Drop-down list' mod='elasticsearch'}</option>
						</select>
					</div>
				</div>
			</li>
			<li class="filter_list_item" draggable="true">
				<div class="col-lg-2">
					<label class="switch-light prestashop-switch fixed-width-lg">
						<input name="elasticsearch_selection_condition" id="elasticsearch_selection_condition" type="checkbox" />
						<span>
							<span>{l s='Yes' mod='elasticsearch'}</span>
							<span>{l s='No' mod='elasticsearch'}</span>
						</span>
						<a class="slide-button btn"></a>
					</label>
				</div>
				<div class="col-lg-4">
					<span class="module_name">{l s='Product condition filter' mod='elasticsearch'}</span>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter result limit:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_condition_filter_show_limit">
							<option value="0">{l s='No limit' mod='elasticsearch'}</option>
							<option value="4">4</option>
							<option value="5">5</option>
							<option value="10">10</option>
							<option value="20">20</option>
						</select>
					</div>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter style:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_condition_filter_type">
							<option value="0">{l s='Checkbox' mod='elasticsearch'}</option>
							<option value="1">{l s='Radio button' mod='elasticsearch'}</option>
							<option value="2">{l s='Drop-down list' mod='elasticsearch'}</option>
						</select>
					</div>
				</div>
			</li>
			<li class="filter_list_item" draggable="true">
				<div class="col-lg-2">
					<label class="switch-light prestashop-switch fixed-width-lg">
						<input name="elasticsearch_selection_manufacturer" id="elasticsearch_selection_manufacturer" type="checkbox" />
						<span>
							<span>{l s='Yes' mod='elasticsearch'}</span>
							<span>{l s='No' mod='elasticsearch'}</span>
						</span>
						<a class="slide-button btn"></a>
					</label>
				</div>
				<div class="col-lg-4">
					<span class="module_name">{l s='Product manufacturer filter' mod='elasticsearch'}</span>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter result limit:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_manufacturer_filter_show_limit">
							<option value="0">{l s='No limit' mod='elasticsearch'}</option>
							<option value="4">4</option>
							<option value="5">5</option>
							<option value="10">10</option>
							<option value="20">20</option>
						</select>
					</div>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter style:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_manufacturer_filter_type">
							<option value="0">{l s='Checkbox' mod='elasticsearch'}</option>
							<option value="1">{l s='Radio button' mod='elasticsearch'}</option>
							<option value="2">{l s='Drop-down list' mod='elasticsearch'}</option>
						</select>
					</div>
				</div>
			</li>
			<li class="filter_list_item" draggable="true">
				<div class="col-lg-2">
					<label class="switch-light prestashop-switch fixed-width-lg">
						<input name="elasticsearch_selection_weight_slider" id="elasticsearch_selection_weight_slider" type="checkbox" />
						<span>
							<span>{l s='Yes' mod='elasticsearch'}</span>
							<span>{l s='No' mod='elasticsearch'}</span>
						</span>
						<a class="slide-button btn"></a>
					</label>
				</div>
				<div class="col-lg-4">
					<span class="module_name">{l s='Product weight filter (slider)' mod='elasticsearch'}</span>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter result limit:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_weight_slider_filter_show_limit">
							<option value="0">{l s='No limit' mod='elasticsearch'}</option>
							<option value="4">4</option>
							<option value="5">5</option>
							<option value="10">10</option>
							<option value="20">20</option>
						</select>
					</div>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter style:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_weight_slider_filter_type">
							<option value="0">{l s='Slider' mod='elasticsearch'}</option>
							<option value="1">{l s='Inputs area' mod='elasticsearch'}</option>
							<option value="2">{l s='List of values' mod='elasticsearch'}</option>
						</select>
					</div>
				</div>
			</li>
			<li class="filter_list_item" draggable="true">
				<div class="col-lg-2">
					<label class="switch-light prestashop-switch fixed-width-lg">
						<input name="elasticsearch_selection_price_slider" id="elasticsearch_selection_price_slider" type="checkbox" />
						<span>
							<span>{l s='Yes' mod='elasticsearch'}</span>
							<span>{l s='No' mod='elasticsearch'}</span>
						</span>
						<a class="slide-button btn"></a>
					</label>
				</div>
				<div class="col-lg-4">
					<span class="module_name">{l s='Product price filter (slider)' mod='elasticsearch'}</span>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter result limit:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_price_slider_filter_show_limit">
							<option value="0">{l s='No limit' mod='elasticsearch'}</option>
							<option value="4">4</option>
							<option value="5">5</option>
							<option value="10">10</option>
							<option value="20">20</option>
						</select>
					</div>
				</div>
				<div class="col-lg-3 pull-right">
					<label class="control-label col-lg-6">{l s='Filter style:' mod='elasticsearch'}</label>
					<div class="col-lg-6">
						<select name="elasticsearch_selection_price_slider_filter_type">
							<option value="0">{l s='Slider' mod='elasticsearch'}</option>
							<option value="1">{l s='Inputs area' mod='elasticsearch'}</option>
							<option value="2">{l s='List of values' mod='elasticsearch'}</option>
						</select>
					</div>
				</div>
			</li>
			{if $attribute_groups|count > 0}
				{foreach $attribute_groups as $attribute_group}
				<li class="filter_list_item" draggable="true">
					<div class="col-lg-2">
						<label class="switch-light prestashop-switch fixed-width-lg">
							<input name="elasticsearch_selection_ag_{$attribute_group['id_attribute_group']|intval}" id="elasticsearch_selection_ag_{$attribute_group['id_attribute_group']|intval}" type="checkbox" />
							<span>
								<span>{l s='Yes' mod='elasticsearch'}</span>
								<span>{l s='No' mod='elasticsearch'}</span>
							</span>
							<a class="slide-button btn"></a>
						</label>
					</div>
					<div class="col-lg-4">
						<span class="module_name">
						{if $attribute_group['n'] > 1}
							{l s='Attribute group: %1$s (%2$d attributes)'|sprintf:$attribute_group['name']:$attribute_group['n'] mod='elasticsearch'}
						{else}
							{l s='Attribute group: %1$s (%2$d attribute)'|sprintf:$attribute_group['name']:$attribute_group['n'] mod='elasticsearch'}
						{/if}
						{if $attribute_group['is_color_group']}
							<img src="../img/admin/color_swatch.png" alt="" title="{l s='This group will allow user to select a color' mod='elasticsearch'}" />
						{/if}
						</span>
					</div>
					<div class="col-lg-3 pull-right">
						<label class="control-label col-lg-6">{l s='Filter result limit:' mod='elasticsearch'}</label>
						<div class="col-lg-6">
							<select name="elasticsearch_selection_ag_{$attribute_group['id_attribute_group']|intval}_filter_show_limit">
								<option value="0">{l s='No limit' mod='elasticsearch'}</option>
								<option value="4">4</option>
								<option value="5">5</option>
								<option value="10">10</option>
								<option value="20">20</option>
							</select>
						</div>
					</div>
					<div class="col-lg-3 pull-right">
						<label class="control-label col-lg-6">{l s='Filter style:' mod='elasticsearch'}</label>
						<div class="col-lg-6">
							<select name="elasticsearch_selection_ag_{$attribute_group['id_attribute_group']|intval}_filter_type">
								<option value="0">{l s='Checkbox' mod='elasticsearch'}</option>
								<option value="1">{l s='Radio button' mod='elasticsearch'}</option>
								<option value="2">{l s='Drop-down list' mod='elasticsearch'}</option>
							</select>
						</div>
					</div>
				</li>
				{/foreach}
			{/if}

			{if $features|count > 0}
				{foreach $features as $feature}
				<li class="filter_list_item" draggable="true">
					<div class="col-lg-2">
						<label class="switch-light prestashop-switch fixed-width-lg">
							<input name="elasticsearch_selection_feat_{$feature['id_feature']|intval}" id="elasticsearch_selection_feat_{$feature['id_feature']|intval}" type="checkbox" />
							<span>
								<span>{l s='Yes' mod='elasticsearch'}</span>
								<span>{l s='No' mod='elasticsearch'}</span>
							</span>
							<a class="slide-button btn"></a>
						</label>
					</div>
					<div class="col-lg-4">
						<span class="module_name">
					{if $feature['n'] > 1}{l s='Feature: %1$s (%2$d values)'|sprintf:$feature['name']:$feature['n'] mod='elasticsearch'}{else}{l s='Feature: %1$s (%2$d value)'|sprintf:$feature['name']:$feature['n'] mod='elasticsearch'}{/if}
						</span>
					</div>
					<div class="col-lg-3 pull-right">
						<label class="control-label col-lg-6">{l s='Filter result limit:' mod='elasticsearch'}</label>
						<div class="col-lg-6">
							<select name="elasticsearch_selection_feat_{$feature['id_feature']|intval}_filter_show_limit">
								<option value="0">{l s='No limit' mod='elasticsearch'}</option>
								<option value="4">4</option>
								<option value="5">5</option>
								<option value="10">10</option>
								<option value="20">20</option>
							</select>
						</div>
					</div>
					<div class="col-lg-3 pull-right">
						<label class="control-label col-lg-6">{l s='Filter style:' mod='elasticsearch'}</label>
						<div class="col-lg-6">
							<select name="elasticsearch_selection_feat_{$feature['id_feature']|intval}_filter_type">
								<option value="0">{l s='Checkbox' mod='elasticsearch'}</option>
								<option value="1">{l s='Radio button' mod='elasticsearch'}</option>
								<option value="2">{l s='Drop-down list' mod='elasticsearch'}</option>
							</select>
						</div>
					</div>
				</li>
				{/foreach}
			{/if}
		</ul>
	</section>
</section>

<script type="text/javascript">
	var translations = new Array();
	{if isset($filters)}var filters = '{$filters}';{/if}
	translations['no_selected_categories'] = '{l s='You must select at least one category'|addslashes mod='elasticsearch'}';
	translations['no_selected_filters'] = '{l s='You must select at least one filter'|addslashes mod='elasticsearch'}';

	var elasticsearch_selected_shops = [{$elasticsearch_selected_shops|escape:'htmlall':'UTF-8'}];

	for (var i = 0; i < elasticsearch_selected_shops.length; i++)
	{
		$('#shop-tree input[name="checkBoxShopAsso_elasticsearch_template['+elasticsearch_selected_shops[i]+']"]').attr('checked', 'checked');
		$('#shop-tree input[name="checkBoxShopAsso_elasticsearch_template['+elasticsearch_selected_shops[i]+']"]').parent().addClass('tree-selected');
	}

</script>