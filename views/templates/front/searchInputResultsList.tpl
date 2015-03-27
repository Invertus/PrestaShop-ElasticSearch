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
{if $links}
	<ul>
		{section name=link loop=$links}
			<li>
				<a href="{$links[link].uri|escape:'htmlall':'UTF-8'}">
					{$links[link].category|escape:'htmlall':'UTF-8'} {$smarty.const._ELASTICSEARCH_CATEGORY_SEPARATOR_|escape:'htmlall':'UTF-8'} {$links[link].name|escape:'htmlall':'UTF-8'}
				</a>
			</li>
		{/section}
	</ul>
{/if}