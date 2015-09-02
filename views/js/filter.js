/**
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
 */

var ajaxQueries = [];
var ajaxLoaderOn = 0;
var sliderList = [];
var selected_show_all_button = false;

$(document).ready(function()
{
	cancelFilter();
	openCloseFilter();

    $(document).on('click', 'li.elasticsearch_list', function(){
        $('li.elasticsearch_list_selected').removeClass('elasticsearch_list_selected').addClass('elasticsearch_list');
        $(this).removeClass('elasticsearch_list').addClass('elasticsearch_list_selected');
    });

    $('form.showall button').live('click', function(){
        selected_show_all_button = true;
    });

	// Click on color
	$(document).on('click', '#elasticsearch_form input[type=button], #elasticsearch_form label.elasticsearch_color', function()
	{
        if ($(this).parent().find('input').hasClass('on'))
            $(this).parent().find('input').removeClass('on');
        else
            $(this).parent().find('input').addClass('on');

		if (!$('input[name='+$(this).attr('name')+'][type=hidden]').length)
			$('<input />').attr('type', 'hidden').attr('name', $(this).attr('name')).val($(this).data('rel')).appendTo('#elasticsearch_form');
		else
			$('input[name='+$(this).attr('name')+'][type=hidden]').remove();

		reloadElasticsearchContent();
	});

	$(document).on('click', '#elasticsearch_form input[type=checkbox], #elasticsearch_form input[type=radio]', function()
	{
		reloadElasticsearchContent();
	});

    $('#elasticsearch_form').on('change', '.select', function(){
        reloadElasticsearchContent();
    });

	// Changing content of an input text
	$(document).on('keyup', '#elasticsearch_form input.elasticsearch_input_range', function()
	{
		if ($(this).attr('timeout_id'))
			window.clearTimeout($(this).attr('timeout_id'));

		// IE Hack, setTimeout do not accept the third parameter
		var reference = this;

		$(this).attr('timeout_id', window.setTimeout(function(it) {
			if (!$(it).attr('id'))
				it = reference;

			var filter = $(it).attr('id').replace(/^elasticsearch_(.+)_range_.*$/, '$1');

			var value_min = parseInt($('#elasticsearch_'+filter+'_range_min').val());

			if (isNaN(value_min))
				value_min = 0;

			$('#elasticsearch_'+filter+'_range_min').val(value_min);

			var value_max = parseInt($('#elasticsearch_'+filter+'_range_max').val());
			if (isNaN(value_max))
				value_max = 0;
			$('#elasticsearch_'+filter+'_range_max').val(value_max);

			if (value_max < value_min) {
				$('#elasticsearch_'+filter+'_range_max').val($(it).val());
				$('#elasticsearch_'+filter+'_range_min').val($(it).val());
			}

			reloadElasticsearchContent();
		}, 500, this));
	});

	$(document).on('click', '#elasticsearch_block_left .radio', function()
	{
		var name = $(this).attr('name');

		$.each($(this).parent().parent().find('input[type=button]'), function (it, item)
        {
			if ($(item).hasClass('on') && $(item).attr('name') != name)
				$(item).click();
		});

		return true;
	});

	// Click on label
	$('#elasticsearch_form label:not(.elasticsearch_color) a').live('click', function(e) {
			e.preventDefault();

			var disable = $(this).parent().parent().find('input').attr('disabled');

			if (disable == '' || typeof(disable) == 'undefined' || disable == false)
                $(this).parent().parent().find('input').click();
	});

	elasticsearch_hidden_list = {};

	$('.hide-action').on('click', function()
	{
		elasticsearch_hidden_list[$(this).parent().find('ul').attr('id')] =
			typeof(elasticsearch_hidden_list[$(this).parent().find('ul').attr('id')]) == 'undefined' ||
			elasticsearch_hidden_list[$(this).parent().find('ul').attr('id')] == false;

		hideFilterValueAction(this);
	});

	$('.hide-action').each(function(){
		hideFilterValueAction(this);
	});

	$('.selectProductSort').unbind('change').bind('change', function(){
		$('.selectProductSort').val($(this).val());

		if($('#elasticsearch_form').length > 0)
			reloadElasticsearchContent();
	});

	$(document).off('change').on('change', 'select[name=n]', function()
	{
		$('select[name=n]').val($(this).val());
		reloadElasticsearchContent();
	});

	paginationButton(false);
	initLayered();
});

function initFilters()
{
    sliderList = [];

	if (typeof filters !== 'undefined')
	{
		for (key in filters)
		{
			if (filters.hasOwnProperty(key))
				var filter = filters[key];

			if (typeof filter.slider !== 'undefined' && parseInt(filter.filter_type) == 0)
			{
				var filterRange = parseInt(filter.max)-parseInt(filter.min);
				var step = filterRange / 100;

				if (step > 1)
					step = parseInt(step);

				addSlider(filter.type,
				{
					range: true,
					step: step,
					min: parseInt(filter.min),
					max: parseInt(filter.max),
					values: [filter.values[0], filter.values[1]],
					slide: function(event, ui) {
						stopAjaxQuery();

						if (parseInt($(event.target).data('format')) < 5)
						{
							from = formatCurrency(ui.values[0], parseInt($(event.target).data('format')),
								$(event.target).data('unit'));
							to = formatCurrency(ui.values[1], parseInt($(event.target).data('format')),
								$(event.target).data('unit'));
						}
						else
						{
							from = ui.values[0] + $(event.target).data('unit');
							to = ui.values[1] + $(event.target).data('unit');
						}

						$('#elasticsearch_' + $(event.target).data('type') + '_range').html(from + ' - ' + to);
					},
					stop: function () {
						reloadElasticsearchContent();
					}
				}, filter.unit, parseInt(filter.format));
			}
			else if(typeof filter.slider !== 'undefined' && parseInt(filter.filter_type) == 1)
			{
				$('#elasticsearch_' + filter.type + '_range_min').attr('limitValue', filter.min);
				$('#elasticsearch_' + filter.type + '_range_max').attr('limitValue', filter.max);
			}

			$('.elasticsearch_' + filter.type).show();
		}

		initUniform();
	}
}

function getSliderZeroValue(type, side)
{
    // side: min, max
    // type: weight, price

    var value = null;

    $(sliderList).each(function(){
        if (this.type == type)
            value =  this.data[side];
    });

    return value;
}

function initUniform()
{
	$('#elasticsearch_form input[type="checkbox"], #elasticsearch_form input[type="radio"], select.form-control').uniform();
}

function hideFilterValueAction(it)
{
	if (typeof(elasticsearch_hidden_list[$(it).parent().find('ul').attr('id')]) == 'undefined'
		|| elasticsearch_hidden_list[$(it).parent().find('ul').attr('id')] == false)
	{
		$(it).parent().find('.hiddable').hide();
		$(it).parent().find('.hide-action.less').hide();
		$(it).parent().find('.hide-action.more').show();
	}
	else
	{
		$(it).parent().find('.hiddable').show();
		$(it).parent().find('.hide-action.less').show();
		$(it).parent().find('.hide-action.more').hide();
	}
}

function addSlider(type, data, unit, format)
{
	sliderList.push({
		type: type,
		data: data,
		unit: unit,
		format: format
	});
}

function initSliders()
{
	$(sliderList).each(function(i, slider){
		$('#elasticsearch_'+slider['type']+'_slider').slider(slider['data']);

		var from = '';
		var to = '';
		switch (slider['format'])
		{
			case 1:
			case 2:
			case 3:
			case 4:
				from = formatCurrency($('#elasticsearch_'+slider['type']+'_slider').slider('values', 0), slider['format'], slider['unit']);
				to = formatCurrency($('#elasticsearch_'+slider['type']+'_slider').slider('values', 1), slider['format'], slider['unit']);
				break;
			case 5:
				from =  $('#elasticsearch_'+slider['type']+'_slider').slider('values', 0)+slider['unit'];
				to = $('#elasticsearch_'+slider['type']+'_slider').slider('values', 1)+slider['unit'];
				break;
		}
		$('#elasticsearch_'+slider['type']+'_range').html(from+' - '+to);
	});
}

function initLayered()
{
	initFilters();
	initSliders();
	initLocationChange();
	updateProductUrl();

    if (window.location.href.split('#').length == 2 && window.location.href.split('#')[1] != '')
    {
        var params = window.location.href.split('#')[1];
        reloadElasticsearchContent(params, true);
    }
}

function paginationButton(nbProductsIn, nbProductOut)
{
	if (typeof(current_friendly_url) === 'undefined')
		current_friendly_url = '#';

	$('div.pagination a').not(':hidden').each(function () {
		var page = $(this).attr('href').search(/(\?|&)p=/) == -1 ? 1 : parseInt($(this).attr('href').replace(/^.*(\?|&)p=(\d+).*$/, '$2'));
		var location = window.location.href.replace(/#.*$/, '');

		$(this).attr('href', location+current_friendly_url.replace(/\/page-(\d+)/, '')+'/page-'+page);
	});

	$('div.pagination li').not('.current, .disabled').each(function () {
		var nbPage = 0;

		if ($(this).hasClass('pagination_next'))
			nbPage = parseInt($('div.pagination li.current').children().children().html()) + 1;
		else if ($(this).hasClass('pagination_previous'))
			nbPage = parseInt($('div.pagination li.current').children().children().html()) - 1;

		$(this).children().children().on('click', function(e)
		{
			e.preventDefault();
            p = nbPage == 0 ? parseInt($(this).html()) + parseInt(nbPage) : nbPage;
			p = '&p='+ p;

			reloadElasticsearchContent(p);

			nbPage = 0;
		});
	});

	//product count refresh
	if (nbProductsIn!=false)
    {
		if(isNaN(nbProductsIn) == 0)
        {
			// add variables
			var productCountRow = $('.product-count').html();
			var nbPage = parseInt($('div.pagination li.current').children().children().html());
			var nb_products = nbProductsIn;

            var nbPerPage = $('#nb_item option:selected').length == 0 ? nb_products : parseInt($('#nb_item option:selected').val());

            if (isNaN(nbPage))
                nbPage = 1;

			nbPerPage * nbPage < nb_products ? productShowing = nbPerPage * nbPage : productShowing = (nbPerPage * nbPage - nb_products - nbPerPage * nbPage) * -1;
			nbPage == 1 ? productShowingStart = 1 : productShowingStart = nbPerPage * nbPage - nbPerPage + 1;

			//insert values into a .product-count
			productCountRow = $.trim(productCountRow);
			productCountRow = productCountRow.split(' ');
			productCountRow[1] = productShowingStart;
			productCountRow[3] = (nbProductOut != 'undefined') && (nbProductOut > productShowing) ? nbProductOut : productShowing;
			productCountRow[5] = nb_products;

			if (productCountRow[3] > productCountRow[5])
				productCountRow[3] = productCountRow[5];

			productCountRow = productCountRow.join(' ');

			$('.product-count').html(productCountRow).show();
		}
		else
			$('.product-count').hide();
	}
}

function cancelFilter()
{
	$(document).on('click', '#enabled_filters a', function(e)
	{
		if ($(this).data('rel').search(/_slider$/) > 0)
		{
			if ($('#'+$(this).data('rel')).length)
			{
				$('#'+$(this).data('rel')).slider('values' , 0, $('#'+$(this).data('rel')).slider('option' , 'min' ));
				$('#'+$(this).data('rel')).slider('values' , 1, $('#'+$(this).data('rel')).slider('option' , 'max' ));
				$('#'+$(this).data('rel')).slider('option', 'slide')(0,{values:[$('#'+$(this).data('rel')).slider( 'option' , 'min' ),
                    $('#'+$(this).data('rel')).slider( 'option' , 'max' )]});
			}
			else if($('#'+$(this).data('rel').replace(/_slider$/, '_range_min')).length)
			{
				$('#'+$(this).data('rel').replace(/_slider$/, '_range_min')).val($('#'+$(this).data('rel').replace(/_slider$/, '_range_min')).attr('limitValue'));
				$('#'+$(this).data('rel').replace(/_slider$/, '_range_max')).val($('#'+$(this).data('rel').replace(/_slider$/, '_range_max')).attr('limitValue'));
			}
		}
		else
		{
			if ($('option#'+$(this).data('rel')).length)
			{
				$('#'+$(this).data('rel')).parent().val('');
			}
			else
			{
				$('#'+$(this).data('rel')).attr('checked', false);
				$('.'+$(this).data('rel')).attr('checked', false);
				$('#elasticsearch_form input[type=hidden][name='+$(this).data('rel')+']').remove();
			}
		}
		reloadElasticsearchContent();
		e.preventDefault();
	});
}

function openCloseFilter()
{
	$(document).on('click', '#elasticsearch_form span.elasticsearch_close a', function(e)
	{
		if ($(this).html() == '&lt;')
		{
			$('#'+$(this).data('rel')).show();
			$(this).html('v');
			$(this).parent().removeClass('closed');
		}
		else
		{
			$('#'+$(this).data('rel')).hide();
			$(this).html('&lt;');
			$(this).parent().addClass('closed');
		}

		e.preventDefault();
	});
}

function stopAjaxQuery()
{
	if (typeof(ajaxQueries) == 'undefined')
		ajaxQueries = [];
	for(i = 0; i < ajaxQueries.length; i++)
		ajaxQueries[i].abort();

	ajaxQueries = [];
}

function reloadElasticsearchContent(params_plus, url_only)
{
	stopAjaxQuery();

	if (!ajaxLoaderOn)
	{
		$('.product_list').prepend($('#elasticsearch_ajax_loader').html());
		$('.product_list').css('opacity', '0.7');
		ajaxLoaderOn = 1;
	}

	var data = $('#elasticsearch_form').serialize();

	$(['price', 'weight']).each(function(it, sliderType)
	{
        var current_slider = $('#elasticsearch_'+sliderType+'_slider'),
            sliderStart = current_slider.slider('values', 0),
            sliderStop = current_slider.slider('values', 1);

        if (typeof(sliderStart) != 'number' || typeof(sliderStop) != 'number')
            return false;

        if (sliderStart != getSliderZeroValue(sliderType, 'min') || sliderStop != getSliderZeroValue(sliderType, 'max'))
            data += '&'+current_slider.attr('id')+'='+sliderStart+'_'+sliderStop;
	});

	$('#elasticsearch_form .select option').each( function () {
		if($(this).attr('id') && $(this).parent().val() == $(this).val())
		{
			data += '&'+$(this).attr('id') + '=' + $(this).val();
		}
	});

	if ($('.selectProductSort').length && $('.selectProductSort').val())
	{
		if ($('.selectProductSort').val().search(/orderby=/) > 0)
		{
			// Old ordering working
			var splitData = [
				$('.selectProductSort').val().match(/orderby=(\w*)/)[1],
				$('.selectProductSort').val().match(/orderway=(\w*)/)[1]
			];
		}
		else
		{
			// New working for default theme 1.4 and theme 1.5
			var splitData = $('.selectProductSort').val().split(':');
		}
		data += '&orderby='+splitData[0]+'&orderway='+splitData[1];
	}

    if ($('select#nb_item').length)
    {
        var selected_pagination = $('select#nb_item').val();

        if (selected_show_all_button)
        {
            selected_pagination = $('form.showall input[name="n"]').val();
            selected_show_all_button = false;
        }

        data += '&n=' + selected_pagination;
    }

	var slideUp = true;
	if (params_plus == undefined)
	{
		params_plus = '';
		slideUp = false;
	}

	// Get nb items per page
	var n = '';
	if (params_plus)
	{
		$('div.pagination select[name=n]').children().each(function(it, option) {
			if (option.selected)
				n = '&n=' + option.value;
		});
	}

    var ajax_call_data;

    if (typeof(url_only) != 'undefined' && url_only)
        ajax_call_data = params_plus+'&submitElasticsearchFilter=1&token='+static_token;
    else
        ajax_call_data = data+params_plus+n+'&submitElasticsearchFilter=1&token='+static_token;

	ajaxQuery = $.ajax(
	{
		type: 'GET',
		url: elasticsearch_ajax_uri,
		data: ajax_call_data,
		dataType: 'json',
		async: false,
		cache: false,
		success: function(result)
		{
            var $oldCenterColumn = $('#old_center_column');
            if ($oldCenterColumn.length > 0)
            {
                $('#center_column').remove();
                $oldCenterColumn.attr('id', 'center_column').show();
            }

			if (result.meta_description != '')
				$('meta[name="description"]').attr('content', result.meta_description);

			if (result.meta_keywords != '')
				$('meta[name="keywords"]').attr('content', result.meta_keywords);

			if (result.meta_title != '')
				$('title').html(result.meta_title);

			if (result.heading != '')
				$('h1.page-heading .cat-name').html(result.heading);

			$('#elasticsearch_block_left').replaceWith(utf8_decode(result.filtersBlock));
			$('.category-product-count, .heading-counter').html(result.categoryCount);

			if (result.nbRenderedProducts == result.nbAskedProducts)
				$('div.clearfix.selector1').hide();

			if (result.productList)
				$('.product_list').replaceWith(utf8_decode(result.productList));
			else
				$('.product_list').html('');

			$('.product_list').css('opacity', '1');
			if ($.browser.msie) // Fix bug with IE8 and aliasing
				$('.product_list').css('filter', '');

			if (result.pagination.search(/[^\s]/) >= 0) {
				var pagination = $('<div/>').html(result.pagination)
				var pagination_bottom = $('<div/>').html(result.pagination_bottom);

				if ($('<div/>').html(pagination).find('#pagination').length)
				{
					$('#pagination').show();
					$('#pagination').replaceWith(pagination.find('#pagination'));
				}
				else
				{
					$('#pagination').hide();
				}

				if ($('<div/>').html(pagination_bottom).find('#pagination_bottom').length)
				{
					$('#pagination_bottom').show();
					$('#pagination_bottom').replaceWith(pagination_bottom.find('#pagination_bottom'));
				}
				else
				{
					$('#pagination_bottom').hide();
				}
			}
			else
			{
				$('#pagination').hide();
				$('#pagination_bottom').hide();
			}

			paginationButton(result.nbRenderedProducts, result.nbAskedProducts);
			ajaxLoaderOn = 0;

			// On submiting nb items form, relaod with the good nb of items
			$('div.pagination form').on('submit', function(e)
			{
				e.preventDefault();
				val = $('div.pagination select[name=n]').val();

				$('div.pagination select[name=n]').children().each(function(it, option) {
					if (option.value == val)
						$(option).attr('selected', true);
					else
						$(option).removeAttr('selected');
				});

				// Reload products and pagination
				reloadElasticsearchContent();
			});
			if (typeof(ajaxCart) != "undefined")
				ajaxCart.overrideButtonsInThePage();

			if (typeof(reloadProductComparison) == 'function')
				reloadProductComparison();

			filters = result.filters;
			initFilters();
			initSliders();

			current_friendly_url = result.current_friendly_url;

			// Currente page url
			if (typeof(current_friendly_url) === 'undefined')
				current_friendly_url = '#';

			window.location.href = current_friendly_url;

			if (current_friendly_url != '#/show-all')
				$('div.clearfix.selector1').show();

			lockLocationChecking = true;

			if(slideUp)
				$.scrollTo('.product_list', 400);
			updateProductUrl();

			$('.hide-action').each(function() {
				hideFilterValueAction(this);
			});

			if (display instanceof Function) {
				var view = $.totalStorage('display');

				if (view && view != 'grid')
					display(view);
			}
		}
	});
	ajaxQueries.push(ajaxQuery);
}

function initLocationChange(func, time)
{
	if(!time) time = 500;
	var current_friendly_url = getUrlParams();
	setInterval(function()
	{
		if(getUrlParams() != current_friendly_url && !lockLocationChecking)
		{
			// Don't reload page if current_friendly_url and real url match
			if (current_friendly_url.replace(/^#(\/)?/, '') == getUrlParams().replace(/^#(\/)?/, ''))
				return;

			lockLocationChecking = true;
			//reloadElasticsearchContent('&selected_filters='+getUrlParams().replace(/^#/, ''));
            if (window.location.href.split('#').length == 2 && window.location.href.split('#')[1] != '')
            {
                var params = window.location.href.split('#')[1];
                reloadElasticsearchContent(params, true);
            }
		}
		else {
			lockLocationChecking = false;
			current_friendly_url = getUrlParams();
		}
	}, time);
}

function getUrlParams()
{
	if (typeof(current_friendly_url) === 'undefined')
		current_friendly_url = '#';

	var params = current_friendly_url;
	if(window.location.href.split('#').length == 2 && window.location.href.split('#')[1] != '')
		params = '#'+window.location.href.split('#')[1];
	return params;
}

function updateProductUrl()
{
	// Adding the filters to URL product
	if (typeof(param_product_url) != 'undefined' && param_product_url != '' && param_product_url !='#') {
		$.each($('ul.product_list li.ajax_block_product .product_img_link,'+
				'ul.product_list li.ajax_block_product h5 a,'+
				'ul.product_list li.ajax_block_product .product_desc a,'+
				'ul.product_list li.ajax_block_product .lnk_view'), function() {
			$(this).attr('href', $(this).attr('href') + param_product_url);
		});
	}
}

/**
 * Copy of the php function utf8_decode()
 */
function utf8_decode (utfstr) {
	var res = '';
	for (var i = 0; i < utfstr.length;) {
		var c = utfstr.charCodeAt(i);

		if (c < 128)
		{
			res += String.fromCharCode(c);
			i++;
		}
		else if((c > 191) && (c < 224))
		{
			var c1 = utfstr.charCodeAt(i+1);
			res += String.fromCharCode(((c & 31) << 6) | (c1 & 63));
			i += 2;
		}
		else
		{
			var c1 = utfstr.charCodeAt(i+1);
			var c2 = utfstr.charCodeAt(i+2);
			res += String.fromCharCode(((c & 15) << 12) | ((c1 & 63) << 6) | (c2 & 63));
			i += 3;
		}
	}
	return res;
}
