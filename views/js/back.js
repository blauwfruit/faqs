$(document).ready(function() {
    $('#object_name').parent().addClass('ui-front');

    $.widget("custom.catcomplete", $.ui.autocomplete, {
        _create: function() {
            this._super();
            this.widget().menu("option", "items", "> :not(.ui-autocomplete-category)");
        },
        _renderMenu: function(ul, items) {
            var that = this,
                currentCategory = "";
            $.each(items, function(index, item) {
                var li;
                if (item.category != currentCategory) {
                    ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>");
                    currentCategory = item.category;
                }
                li = that._renderItemData(ul, item);
                if (item.category) {
                    li.attr("aria-label", item.category + " : " + item.label);
                }
            });
        }
    });

    $("#object_name").catcomplete({
        delay: 1000,
        minLength: 3,
        source: ajaxSearchUrl,
        select: function(event, ui) {

            $('[name=object_type]').val(ui.item.category);
            $('[name=object_id]').val(ui.item.data);

        }
    });
});