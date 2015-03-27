/*
 * 2007-2015 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2015 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

var elasticsearch_queries = [];
$(document).ready(function()
{
	var $input = $('.elasticsearch_search input');

	$input.on('input propertychange paste', function(){
		if($(this).val().length >= elasticsearch_min_words_count)
		{
			stopInstantSearchQueries();
			elasticsearch_query = $.ajax({
				url: elasticsearch_ajax_uri,
				data: {
                    token: static_token,
					submitElasticsearchAjaxSearch: 1,
					id_lang: id_lang,
					search: $(this).val()
				},
				dataType: 'html',
				type: 'POST',
				headers: { "cache-control": "no-cache" },
				async: true,
				cache: false,
				success: function(data){
					if ($input.val().length > 0) {
						tryToCloseInstantSearch();
						$('#center_column').attr('id', 'old_center_column');
						$('#old_center_column').after('<div id="center_column" class="' + $('#old_center_column').attr('class') + '">' + data + '</div>').hide();
						// Button override
						ajaxCart.overrideButtonsInThePage();
						$('.close-elasticsearch-results').on('click', function() {
							$input.val('');
							return tryToCloseInstantSearch();
						});
						return false;
					}
					else
						tryToCloseInstantSearch();
				}
			});
			elasticsearch_queries.push(elasticsearch_query);
		}
		else
			tryToCloseInstantSearch();
	});
});

function tryToCloseInstantSearch()
{
	var $oldCenterColumn = $('#old_center_column');
	if ($oldCenterColumn.length > 0)
	{
		$('#center_column').remove();
		$oldCenterColumn.attr('id', 'center_column').show();
		return false;
	}
}

function stopInstantSearchQueries()
{
	for(var i=0; i<elasticsearch_queries.length; i++)
		elasticsearch_queries[i].abort();
	elasticsearch_queries = [];
}