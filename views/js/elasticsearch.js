/**
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