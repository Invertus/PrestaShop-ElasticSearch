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