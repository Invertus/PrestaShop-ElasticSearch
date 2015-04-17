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

function checkForm()
{
	var is_category_selected = false;
	var is_filter_selected   = false;

	$('ul#categories-tree input[type=checkbox]').each(
		function()
		{
			if ($(this).prop('checked'))
			{
				is_category_selected = true;
				return false;
			}
		}
	);

	$('.filter_list_item input[type=checkbox]').each(
		function()
		{
			if ($(this).prop('checked'))
			{
				is_filter_selected = true;
				return false;
			}
		}
	);

	if (!is_category_selected)
	{
		alert(translations['no_selected_categories']);
		$('#categories-treeview input[type=checkbox]').first().focus();
		return false;
	}

	if (!is_filter_selected)
	{
		alert(translations['no_selected_filters']);
		$('#filter_list_item input[type=checkbox]').first().focus();
		return false;
	}

	return true;
}

$(document).ready(
	function()
	{
        initFilters();

		$('#ElasticSearchTemplate_form_submit_btn').click(function(){
			return checkForm();
		});

		$('.sortable').sortable(
		{
			forcePlaceholderSize: true
		});

		$('.filter_list_item input[type=checkbox]').click(
			function()
			{
				var current_selected_filters_count = parseInt($('#selected_filters').html());

				if ($(this).prop('checked'))
					$('#selected_filters').html(current_selected_filters_count + 1);
				else
					$('#selected_filters').html(current_selected_filters_count - 1);
			}
		);

		if (typeof filters !== 'undefined')
		{
			filters = jQuery.parseJSON(filters);
            $('section.filter_list').append('<ul class="list-unstyled2 sortable"></ul>');

			for (filter in filters)
			{
				$('#'+filter).attr('checked','checked');
				$('#selected_filters').html(parseInt($('#selected_filters').html())+1);
				$('select[name="'+filter+'_filter_type"]').val(filters[filter].filter_type);
				$('select[name="'+filter+'_filter_show_limit"]').val(filters[filter].filter_show_limit);

                $($('#'+filter).closest('li')).appendTo('ul.list-unstyled2');
			}

            $('section.filter_list ul.list-unstyled li').each(function(){
                $(this).appendTo('ul.list-unstyled2');
            });

            $('section.filter_list ul.list-unstyled').remove();
            $('section.filter_list ul.list-unstyled2').removeClass('list-unstyled2').addClass('list-unstyled');

            $('.sortable').sortable(
                {
                    forcePlaceholderSize: true
                });
		}
	}
);

function initFilters()
{
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

							$('#layered_' + $(event.target).data('type') + '_range').html(from + ' - ' + to);
						},
						stop: function () {
							reloadElasticsearchContent(true);
						}
					}, filter.unit, parseInt(filter.format));
			}
			else if(typeof filter.slider !== 'undefined' && parseInt(filter.filter_type) == 1)
			{
				$('#layered_' + filter.type + '_range_min').attr('limitValue', filter.min);
				$('#layered_' + filter.type + '_range_max').attr('limitValue', filter.max);
			}

			$('.layered_' + filter.type).show();
		}
	}
}