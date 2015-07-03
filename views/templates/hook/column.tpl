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
<script>
	var elasticsearch_ajax_uri = '{$smarty.const._ELASTICSEARCH_AJAX_URI_|escape:'htmlall':'UTF-8'}';
</script>

{if $nbr_filterBlocks != 0}
	<div id="elasticsearch_block_left" class="block">
		<p class="title_block">
			{l s='Products filter' mod='elasticsearch'}
		</p>
		<div class="block_content">
			<form action="#" id="elasticsearch_form">
				<div>
					{if isset($selected_filters) && $n_filters > 0}
						<div id="enabled_filters">
							<span class="elasticsearch_subtitle" style="float: none;">
								{l s='Enabled filters:' mod='elasticsearch'}
							</span>
							<ul>
								{foreach from=$selected_filters key=filter_type item=filter_values}
									{foreach from=$filter_values key=filter_key item=filter_value name=f_values}
										{foreach from=$filters item=filter}
											{if $filter.type == $filter_type && isset($filter.values)}
												{if isset($filter.slider)}
													{if $smarty.foreach.f_values.first}
														<li>
															<a href="#" data-rel="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_slider" title="{l s='Cancel' mod='elasticsearch'}"></a>
															{if $filter.format == 1}
																{l s='%1$s: %2$s - %3$s'|sprintf:$filter.name:{displayPrice price=$filter.values[0]}:{displayPrice price=$filter.values[1]}|escape:'html':'UTF-8' mod='elasticsearch'}
															{else}
																{l s='%1$s: %2$s %4$s - %3$s %4$s'|sprintf:$filter.name:$filter.values[0]:$filter.values[1]:$filter.unit|escape:'html':'UTF-8' mod='elasticsearch'}
															{/if}
														</li>
													{/if}
												{else}
													{foreach from=$filter.values key=id_value item=value}
														{if $id_value == $filter_key && !is_numeric($filter_value) && ($filter.type eq 'id_attribute_group' || $filter.type eq 'id_feature') || $id_value == $filter_value && $filter.type neq 'id_attribute_group' && $filter.type neq 'id_feature'}
															<li>
																<a href="#" data-rel="elasticsearch_{$filter.type_lite|escape:'htmlall':'UTF-8'}_{$id_value|escape:'htmlall':'UTF-8'}" title="{l s='Cancel' mod='elasticsearch'}"><i class="icon-remove"></i></a>
																{l s='%1$s: %2$s' mod='elasticsearch' sprintf=[$filter.name, $value.name]}
															</li>
														{/if}
													{/foreach}
												{/if}
											{/if}
										{/foreach}
									{/foreach}
								{/foreach}
							</ul>
						</div>
					{/if}
					{foreach from=$filters item=filter}
					{if isset($filter.values)}
					{if isset($filter.slider)}
					<div class="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}" style="display: none;">
						{else}
						<div class="elasticsearch_filter">
							{/if}
							<div class="elasticsearch_subtitle_heading">
								<span class="elasticsearch_subtitle">{$filter.name|escape:'html':'UTF-8'}</span>
							</div>
							<ul id="ul_elasticsearch_{$filter.type}_{$filter.id_key}" class="col-lg-12 elasticsearch_filter_ul{if isset($filter.is_color_group) && $filter.is_color_group} color-group{/if}">
								{if !isset($filter.slider)}
									{if $filter.filter_type == 0}
										{foreach from=$filter.values key=id_value item=value name=fe}
											{if $value.nbr || !$hide_0_values}
												<li class="nomargin {if $smarty.foreach.fe.index >= $filter.filter_show_limit}hiddable{/if} col-lg-12">
													{if isset($filter.is_color_group) && $filter.is_color_group}
														<input class="color-option {if isset($value.checked) && $value.checked}on{/if} {if !$value.nbr}disable{/if}" type="button" name="elasticsearch_{$filter.type_lite}_{$id_value}" data-rel="{$id_value}_{$filter.id_key}" id="elasticsearch_id_attribute_group_{$id_value}" {if !$value.nbr}disabled="disabled"{/if} style="background: {if isset($value.color)}{if file_exists($smarty.const._PS_ROOT_DIR_|cat:"/img/co/$id_value.jpg")}url(img/co/{$id_value}.jpg){else}{$value.color}{/if}{else}#CCC{/if};" />
														{if isset($value.checked) && $value.checked}<input type="hidden" name="elasticsearch_{$filter.type_lite}_{$id_value}" value="{$id_value}" />{/if}
													{else}
														<input type="checkbox" class="checkbox" name="elasticsearch_{$filter.type_lite}_{$id_value}" id="elasticsearch_{$filter.type_lite}{if $id_value || $filter.type == 'quantity'}_{$id_value}{/if}" value="{$id_value}{if $filter.id_key}_{$filter.id_key}{/if}"{if isset($value.checked)} checked="checked"{/if}{if !$value.nbr} disabled="disabled"{/if} />
													{/if}
													<label for="elasticsearch_{$filter.type_lite}_{$id_value}"{if !$value.nbr} class="disabled"{else}{if isset($filter.is_color_group) && $filter.is_color_group} name="elasticsearch_{$filter.type_lite}_{$id_value}" class="elasticsearch_color" data-rel="{$id_value}_{$filter.id_key}"{/if}{/if}>
														{if !$value.nbr}
															{$value.name|escape:'html':'UTF-8'}{if $elasticsearch_show_qties}<span> ({$value.nbr})</span>{/if}
														{else}
															<a href="{$value.link}"{if $value.rel|trim != ''} data-rel="{$value.rel}"{/if}>{$value.name|escape:'html':'UTF-8'}{if $elasticsearch_show_qties}<span> ({$value.nbr})</span>{/if}</a>
														{/if}
													</label>
												</li>
											{/if}
										{/foreach}
									{else}
										{if $filter.filter_type == 1}
											{foreach from=$filter.values key=id_value item=value name=fe}
												{if $value.nbr || !$hide_0_values}
													<li class="nomargin {if $smarty.foreach.fe.index >= $filter.filter_show_limit}hiddable{/if}">
														{if isset($filter.is_color_group) && $filter.is_color_group}
															<input class="radio color-option {if isset($value.checked) && $value.checked}on{/if} {if !$value.nbr}disable{/if}" type="button" name="elasticsearch_{$filter.type_lite}_{$id_value}" data-rel="{$id_value}_{$filter.id_key}" id="elasticsearch_id_attribute_group_{$id_value}" {if !$value.nbr}disabled="disabled"{/if} style="background: {if isset($value.color)}{if file_exists($smarty.const._PS_ROOT_DIR_|cat:"/img/co/$id_value.jpg")}url(img/co/{$id_value}.jpg){else}{$value.color}{/if}{else}#CCC{/if};"/>
															{if isset($value.checked) && $value.checked}<input type="hidden" name="elasticsearch_{$filter.type_lite}_{$id_value}" value="{$id_value}" />{/if}
														{else}
															<input type="radio" class="radio elasticsearch_{$filter.type_lite}_{$id_value}" name="elasticsearch_{$filter.type_lite}{if $filter.id_key}_{$filter.id_key}{else}_1{/if}" id="elasticsearch_{$filter.type_lite}{if $id_value || $filter.type == 'quantity'}_{$id_value}{if $filter.id_key}_{$filter.id_key}{/if}{/if}" value="{$id_value}{if $filter.id_key}_{$filter.id_key}{/if}"{if isset($value.checked)} checked="checked"{/if}{if !$value.nbr} disabled="disabled"{/if} />
														{/if}
														<label for="elasticsearch_{$filter.type_lite}{if $id_value || $filter.type == 'quantity'}_{$id_value}{if $filter.id_key}_{$filter.id_key}{/if}{/if}"{if !$value.nbr} class="disabled"{else}{if isset($filter.is_color_group) && $filter.is_color_group} name="elasticsearch_{$filter.type_lite}_{$id_value}" class="elasticsearch_color" data-rel="{$id_value}_{$filter.id_key}"{/if}{/if}>
															{if !$value.nbr}
																{$value.name|escape:'html':'UTF-8'}{if $elasticsearch_show_qties}<span> ({$value.nbr})</span>{/if}
															{else}
																<a href="{$value.link}"{if $value.rel|trim != ''} data-rel="{$value.rel}"{/if}>{$value.name|escape:'html':'UTF-8'}{if $elasticsearch_show_qties}<span> ({$value.nbr})</span>{/if}</a>
															{/if}
														</label>
													</li>
												{/if}
											{/foreach}
										{else}
											<select class="select form-control" {if $filter.filter_show_limit > 1}multiple="multiple" size="{$filter.filter_show_limit}"{/if}>
												<option value="">{l s='No filters' mod='elasticsearch'}</option>
												{foreach from=$filter.values key=id_value item=value}
													{if $value.nbr || !$hide_0_values}
														<option style="color: {if isset($value.color)}{$value.color}{/if}" id="elasticsearch_{$filter.type_lite}{if $id_value || $filter.type == 'quantity'}_{$id_value}{/if}" value="{$id_value}_{$filter.id_key}" {if isset($value.checked) && $value.checked}selected="selected"{/if} {if !$value.nbr}disabled="disabled"{/if}>
															{$value.name|escape:'html':'UTF-8'}{if $elasticsearch_show_qties}<span> ({$value.nbr})</span>{/if}
														</option>
													{/if}
												{/foreach}
											</select>
										{/if}
									{/if}
								{else}
									{if $filter.filter_type == 0}
										<label for="{$filter.type|escape:'htmlall':'UTF-8'}">
											{l s='Range:' mod='elasticsearch'}
										</label>
										<span id="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_range"></span>
										<div class="elasticsearch_slider_container">
											<div class="elasticsearch_slider" id="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_slider" data-type="{$filter.type|escape:'htmlall':'UTF-8'}" data-format="{$filter.format|escape:'htmlall':'UTF-8'}" data-unit="{$filter.unit|escape:'htmlall':'UTF-8'}"></div>
										</div>
									{else}
										{if $filter.filter_type == 1}
											<li class="nomargin row">
												<div class="col-xs-6 col-sm-12 col-lg-6 first-item">
													{l s='From' mod='elasticsearch'}
													<input class="elasticsearch_{$filter.type}_range elasticsearch_input_range_min elasticsearch_input_range form-control grey" id="elasticsearch_{$filter.type}_range_min" type="text" value="{$filter.values[0]|intval}"/>
	                                    	<span class="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_range_unit">
	                                    		{$filter.unit|escape:'htmlall':'UTF-8'}
	                                    	</span>
												</div>
												<div class="col-xs-6 col-sm-12 col-lg-6">
													{l s='to' mod='elasticsearch'}
													<input class="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_range elasticsearch_input_range_max elasticsearch_input_range form-control grey" id="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_range_max" type="text" value="{ceil($filter.values[1])|intval}"/>
	                                    	<span class="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_range_unit">
	                                    		{$filter.unit|escape:'htmlall':'UTF-8'}
	                                    	</span>
												</div>
										<span class="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_format" style="display:none;">
											{$filter.format|escape:'htmlall':'UTF-8'}
										</span>
											</li>
										{else}
											{foreach from=$filter.list_of_values  item=values}
												<li class="nomargin {if $filter.values[1] == $values[1] && $filter.values[0] == $values[0]}elasticsearch_list_selected{/if} elasticsearch_list" onclick="$('#elasticsearch_{$filter.type}_range_min').val({$values[0]});$('#elasticsearch_{$filter.type}_range_max').val({$values[1]});reloadElasticsearchContent();">
													- {l s='From' mod='elasticsearch'} {$values[0]|escape:'htmlall':'UTF-8'} {$filter.unit|escape:'htmlall':'UTF-8'} {l s='to' mod='elasticsearch'} {$values[1]|escape:'htmlall':'UTF-8'} {$filter.unit|escape:'htmlall':'UTF-8'}
												</li>
											{/foreach}
											<li style="display: none;">
												<input class="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_range" id="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_range_min" type="hidden" value="{$filter.values[0]|escape:'htmlall':'UTF-8'}"/>
												<input class="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_range" id="elasticsearch_{$filter.type|escape:'htmlall':'UTF-8'}_range_max" type="hidden" value="{$filter.values[1]|escape:'htmlall':'UTF-8'}"/>
											</li>
										{/if}
									{/if}
								{/if}
								{if count($filter.values) > $filter.filter_show_limit && $filter.filter_show_limit > 0 && $filter.filter_type != 2}
									<span class="hide-action more">{l s='Show more' mod='elasticsearch'}</span>
									<span class="hide-action less">{l s='Show less' mod='elasticsearch'}</span>
								{/if}
							</ul>
						</div>
						{/if}
						{/foreach}
					</div>
					<input type="hidden" name="id_elasticsearch_category" value="{$id_elasticsearch_category|escape:'htmlall':'UTF-8'}" />
					{foreach from=$filters item=filter}
						{if $filter.type_lite == 'id_attribute_group' && isset($filter.is_color_group) && $filter.is_color_group && $filter.filter_type != 2}
							{foreach from=$filter.values key=id_value item=value}
								{if isset($value.checked)}
									<input type="hidden" name="elasticsearch_id_attribute_group_{$id_value|escape:'htmlall':'UTF-8'}" value="{$id_value|escape:'htmlall':'UTF-8'}_{$filter.id_key|escape:'htmlall':'UTF-8'}" />
								{/if}
							{/foreach}
						{/if}
					{/foreach}
			</form>
		</div>
		<div id="elasticsearch_ajax_loader" style="display: none;">
			<p>
				<img src="{$img_ps_dir|escape:'htmlall':'UTF-8'}loader.gif" alt="" />
				<br />{l s='Loading...' mod='elasticsearch'}
			</p>
		</div>
	</div>
{else}
	<div id="elasticsearch_block_left" class="block">
		<div class="block_content">
			<form action="#" id="elasticsearch_form">
				<input type="hidden" name="id_elasticsearch_category" value="{$id_elasticsearch_category|intval}" />
			</form>
		</div>
		<div style="display: none;">
			<p>
				<img src="{$img_ps_dir|escape:'htmlall':'UTF-8'}loader.gif" alt="" />
				<br />{l s='Loading...' mod='elasticsearch'}
			</p>
		</div>
	</div>
{/if}

{if $nbr_filterBlocks != 0}
	{strip}
		{addJsDef param_product_url='#'}
		{addJsDef blocklayeredSliderName=$elasticsearchSliderName}

		{if isset($filters) && $filters|@count}
			{addJsDef filters=$filters}
		{/if}
	{/strip}
{/if}