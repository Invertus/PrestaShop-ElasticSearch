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
<div class="panel">
	<h3>
		<i class="icon-cogs"></i>
		{l s='Filters templates' mod='elasticsearch'}<span class="badge">{$filters_templates|count}</span>
	</h3>
	{if $filters_templates|count > 0}
		<div class="row">
			<table class="table">
				<thead>
				<tr>
					<th class="fixed-width-xs center"><span class="title_box">{l s='ID' mod='elasticsearch'}</span></th>
					<th><span class="title_box text-left">{l s='Name' mod='elasticsearch'}</span></th>
					<th class="fixed-width-sm center"><span class="title_box">{l s='Categories' mod='elasticsearch'}</span></th>
					<th class="fixed-width-lg"><span class="title_box">{l s='Created on' mod='elasticsearch'}</span></th>
					<th class="fixed-width-sm"><span class="title_box text-right">{l s='Actions' mod='elasticsearch'}</span></th>
				</tr>
				</thead>
				<tbody>
				{foreach $filters_templates as $template}
					<tr>
						<td class="center">{$template['id_elasticsearch_template']|intval}</td>
						<td class="text-left">{$template['name']|escape:'htmlall':'UTF-8'}</td>
						<td class="center">{$template['n_categories']|intval}</td>
						<td>{Tools::displayDate($template['date_add']|escape:'htmlall':'UTF-8', null, true)|escape:'htmlall':'UTF-8'}</td>
						<td>
							{if empty($limit_warning)}
								<div class="btn-group-action">
									<div class="btn-group pull-right">
										<a href="{$current_url}&menu=manage_filter_template&id_elasticsearch_template={$template['id_elasticsearch_template']|intval}" class="btn btn-default">
											<i class="icon-pencil"></i> {l s='Edit' mod='elasticsearch'}
										</a>
										<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
											<span class="caret"></span>&nbsp;
										</button>
										<ul class="dropdown-menu">
											<li>
												<a href="{$current_url}&menu=filter&deleteFilterTemplate=1&id_elasticsearch_template={$template['id_elasticsearch_template']|intval}"
												   onclick="return confirm('{l s='Do you really want to delete this filter template' mod='elasticsearch'}');">
													<i class="icon-trash"></i> {l s='Delete' mod='elasticsearch'}
												</a>
											</li>
										</ul>
									</div>
								</div>
							{/if}
						</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
			<div class="clearfix">&nbsp;</div>
		</div>
	{else}
		<div class="row alert alert-warning">
			{l s='No filter template found.' mod='elasticsearch'}
		</div>
	{/if}
	{if empty($limit_warning)}
		<div class="panel-footer">
			<a class="btn btn-default pull-right" href="{$current_url|escape:'htmlall':'UTF-8'}&menu=manage_filter_template">
				<i class="process-icon-plus"></i>
				{l s='Add new template' mod='elasticsearch'}
			</a>
		</div>
	{/if}
</div>