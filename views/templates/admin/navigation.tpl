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
<nav class="navbar navbar-default" role="navigation">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle pull-left" data-toggle="collapse" data-target="#navbar-collapse">
			<span class="sr-only"></span>
			<span class="icon-bar"></span>
		</button>
	</div>
	<div class="collapse navbar-collapse" id="navbar-collapse">
		<ul class="nav navbar-nav">
			{if isset($menutabs)}
				{foreach from=$menutabs item=tab}
					<li class="{if $tab.active}active{/if}">
						<a id="{$tab.short|escape:'htmlall':'UTF-8'}" href="{$tab.href|escape:'htmlall':'UTF-8'}">
							<span class="{$tab.imgclass|escape:'htmlall':'UTF-8'}"></span>
							{$tab.desc|escape:'htmlall':'UTF-8'}
						</a>
					</li>
				{/foreach}
			{/if}
		</ul>
	</div>
</nav>