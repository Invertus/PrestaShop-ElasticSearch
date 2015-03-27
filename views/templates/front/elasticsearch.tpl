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
{capture name=path}
	{l s='Search' mod='elasticsearch'}
{/capture}

<h1 class="page-heading {if !isset($no_pagination)}product-listing{/if}">
	{l s='Search' mod='elasticsearch'}
	{if $search_value && $products}
        <span class="lighter">"{$search_value|escape:'htmlall':'UTF-8'}"</span>
        {if !isset($no_pagination)}
            <span class="heading-counter">{l s='%d results have been found.' sprintf=$products|count mod='elasticsearch'}</span>
        {/if}
    {/if}
    {if isset($no_pagination)}
        <a class="close-elasticsearch-results close" href="#">{l s='Return to the previous page' mod='elasticsearch'}</a>
    {/if}
</h1>


{if isset($warning_message) && $warning_message}
	<p class="alert alert-warning">
		{$warning_message|escape:'UTF-8'}
	</p>
{else}
    {if isset($no_pagination)}
        <div class="alert alert-info">{l s='%d results have been found.' sprintf=$products|count mod='elasticsearch'}</div>
    {/if}
{/if}

{if isset($products) && $products}
    <div class="content_sortPagiBar clearfix">
        {if !isset($no_pagination)}
            <div class="sortPagiBar clearfix">
                {include file=$smarty.const._PS_THEME_DIR_|cat:"product-sort.tpl"}
                {include file=$smarty.const._PS_THEME_DIR_|cat:"nbr-product-page.tpl"}
            </div>
            <div class="top-pagination-content clearfix">
                {include file=$smarty.const._PS_THEME_DIR_|cat:"product-compare.tpl"}
                {include file=$smarty.const._PS_THEME_DIR_|cat:"pagination.tpl"}
            </div>
        {/if}
    </div>
	{include file=$smarty.const._PS_THEME_DIR_|cat:"product-list.tpl" products=$products}
    <div class="content_sortPagiBar">
        <div class="bottom-pagination-content clearfix">
            {if !isset($no_pagination)}
                {include file=$smarty.const._PS_THEME_DIR_|cat:"product-compare.tpl" paginationId='bottom'}
                {include file=$smarty.const._PS_THEME_DIR_|cat:"pagination.tpl" paginationId='bottom'}
            {/if}
        </div>
    </div>
{/if}