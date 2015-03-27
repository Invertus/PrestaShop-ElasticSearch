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

var sending_request = false;

$(document).ready(function(){
    $('.elasticsearch_search input[type="text"]').on('input propertychange paste', function(){
        elasticSearchSearch();
    });

    $('.elasticsearch_search').on('focusout', function(){
        setTimeout(function() {
            clearSearch();
        }, 100);
    });
});

function elasticSearchSearch()
{
    if ($('.elasticsearch_search input[type="text"]').val().length < elasticsearch_min_words_count)
        return $('.elasticsearch_search .elasticsearch_search_results').html('');

    if (sending_request)
        return;

    sending_request = true;

    setTimeout(function(){
        var search_query = $('.elasticsearch_search input[type="text"]').val();

        if (search_query.length < elasticsearch_min_words_count) {
            sending_request = false;
            return $('.elasticsearch_search .elasticsearch_search_results').html('');
        }

        $.ajax({
            type: "POST",
            async: true,
            url: elasticsearch_ajax_uri,
            dataType: "json",
            global: false,
            data:'ajax=true&submitElasticsearchSearch=1&search_query='+encodeURIComponent(search_query)+'&token='+encodeURIComponent(static_token),
            success: function(resp)
            {
                $('.elasticsearch_search .elasticsearch_search_results').html(resp);
                sending_request = false;
            }
        });
    }, 1000);
}

function clearSearch()
{
    $('.elasticsearch_search .elasticsearch_search_results').html('');
}