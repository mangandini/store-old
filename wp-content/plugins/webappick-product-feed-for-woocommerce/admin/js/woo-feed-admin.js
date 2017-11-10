(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     */
    $(function () {
        

        // Category Mapping (Auto Field Populate)
        $(".treegrid-parent").on('change keyup', function () {
            var val = $(this).val();
            var parent = $(this).attr('classval');

            $(".treegrid-parent-" + parent).val(val);
        });

        // Generate Feed Add Table Row
        $(document).on('click', '#wf_newRow', function () {
            $("#table-1 tbody tr:first").clone().find('input').val('').end().find("select:not('.wfnoempty')").val('').end().insertAfter("#table-1 tbody tr:last");

            $('.outputType').each(function (index, element) {
                //do stuff to each individually.
                $(this).attr('name', "output_type[" + index + "][]"); //sets the val to the index of the element, which, you know, is useless
            });
        });

        // XML Feed Wrapper
        $(document).on('change', '#feedType', function () {
            var type = $(this).val();
            var provider = $("#provider").val();
            console.log(type);
            console.log(provider);
            if (type == 'xml') {
                $(".itemWrapper").show();
                $(".wf_csvtxt").hide();
            } else if (type == 'csv' || type == 'txt') {
                $(".wf_csvtxt").show();
                $(".itemWrapper").hide();
            } else if (type == '') {
                $(".wf_csvtxt").hide();
                $(".itemWrapper").hide();
            }

            if (provider == 'google' || provider == 'facebook' && type != "") {
                $(".itemWrapper").hide();
            } else {
                //$(".itemWrapper").hide();
            }
        });
        

        // Tooltip only Text
        $('.wfmasterTooltip').hover(function () {
            // Hover over code
            var title = $(this).attr('wftitle');
            $(this).data('tipText', title).removeAttr('wftitle');
            $('<p class="wftooltip"></p>')
                .text(title)
                .appendTo('body')
                .fadeIn('slow');
        }, function () {
            // Hover out code
            $(this).attr('wftitle', $(this).data('tipText'));
            $('.wftooltip').remove();
        }).mousemove(function (e) {
            var mousex = e.pageX + 20; //Get X coordinates
            var mousey = e.pageY + 10; //Get Y coordinates
            $('.wftooltip')
                .css({top: mousey, left: mousex})
        });

        // Dynamic Attribute Add New Condition
        $(document).on('click', '#wf_newCon', function () {
            $("#table-1 tbody tr:first").show().clone().find('input').val('').end().insertAfter("#table-1 tbody tr:last");
            $(".fsrow:gt(5)").prop('disabled', false);
            $(".daRow:eq(0)").hide();

        });


        // Add New Condition for Filter
        $(document).on('click', '#wf_newFilter', function () {
            $("#table-filter tbody tr:eq(0)").show().clone().find('input').val('').end().find('select').val('').end().insertAfter("#table-filter tbody tr:last");
            $(".fsrow:gt(2)").prop('disabled', false);
            $(".daRow:eq(0)").hide();
        });

        // Attribute type selection
        $(document).on('change', '.attr_type', function () {
            var type = $(this).val();
            $(this).closest('tr').find('.wf_attr').prop('required',false);
            $(this).closest('tr').find('.wf_default').prop('required',false);
            if (type == 'pattern') {
                $(this).closest('tr').find('.wf_attr').hide();
                $(this).closest('tr').find('.wf_attr').val('');
                $(this).closest('tr').find('.wf_default').show();
                $(this).closest('tr').find('.wf_default').prop('required',true);
            } else {
                $(this).closest('tr').find('.wf_attr').prop('required',true);
                $(this).closest('tr').find('.wf_attr').show();
                $(this).closest('tr').find('.wf_default').hide();
                $(this).closest('tr').find('.wf_default').val('');
            }
        });

        // Attribute type selection for dynamic attribute
        $(document).on('change', '.dType', function () {
            var type = $(this).val();
            if (type == 'pattern') {
                $(this).closest('tr').find('.value_attribute').hide();
                $(this).closest('tr').find('.value_pattern').show();
            } else if (type == 'attribute') {
                $(this).closest('tr').find('.value_attribute').show();
                $(this).closest('tr').find('.value_pattern').hide();
            } else if (type == 'remove') {
                $(this).closest('tr').find('.value_attribute').hide();
                $(this).closest('tr').find('.value_pattern').hide();
            }
        });

        // Generate Feed Table Row Delete
        $(document).on('click', '.delRow', function (event) {
            $(this).closest('tr').remove();
        });

        //Expand output type
        $(document).on('click', '.expandType', function () {
            $('.outputType').each(function (index, element) {
                //do stuff to each individually.
                $(this).attr('name', "output_type[" + index + "][]");
            });
            $(this).closest('tr').find('.outputType').attr('multiple', 'multiple');
            $(this).closest('tr').find('.contractType').show();
            $(this).hide();
            console.log('clicked');
        });

        //Contract output type
        $(document).on('click', '.contractType', function () {
            $('.outputType').each(function (index, element) {
                //do stuff to each individually.
                $(this).attr('name', "output_type[" + index + "][]");
            });
            $(this).closest('tr').find('.outputType').removeAttr('multiple');
            $(this).closest('tr').find('.expandType').show();
            $(this).hide();
        });

        // Generate Feed Form Submit
        $(".generateFeed").validate();
        $(document).on('submit', '#generateFeed', function (event) {
            $(".makeFeedResponse").html("<b style='color: darkblue;'><i class='dashicons dashicons-sos wpf_sos'></i> Processing...</b>");
            //event.preventDefault();
            // Feed Generating form validation
            $(this).validate();
            var this2 = this;
            if ($(this).valid()) {

            }
        });
        // Update Feed Form Submit
        $(".updatefeed").validate();
        $(document).on('submit', '#updatefeed', function (event) {
            $(".makeFeedResponse").html("<b style='color: darkblue;'><i class='dashicons dashicons-sos wpf_sos'></i> Processing...</b>");
            //event.preventDefault();
            // Feed Generating form validation
            $(this).validate();
            var this2 = this;
            if ($(this).valid()) {

            }
        });
        // Get Merchant View
        $("#provider").on('change', function (event) {
            event.preventDefault();
            $("#providerPage").html("<h3>Loading...</h3>");
            var merchant = $(this).val();
            var this2 = this;                  //use in callback
            $('#feedType').trigger('change');
            $.post(wpf_ajax_obj.wpf_ajax_url, {     //POST request
                _ajax_nonce: wpf_ajax_obj.nonce, //nonce
                action: "get_feed_merchant",        //action
                merchant: merchant              //data
            }, function (data) {                //callback
                //console.log(data);          //insert server response
                $("#providerPage").html(data);

                // Generate Feed Table row shorting
                $('.sorted_table').sortablesd({
                    containerSelector: 'table',
                    itemPath: '> tbody',
                    itemSelector: 'tr',
                    placeholder: '<tr class="placeholder"/>',
                    // set $item relative to cursor position
                    onDragStart: function ($item, container, _super, event) {
                        $item.css({
                            height: $item.outerHeight(),
                            width: $item.outerWidth()
                        });
                        $item.addClass(container.group.options.draggedClass);
                        $("body").addClass(container.group.options.bodyClass);
                    },
                    onDrag: function ($item, position, _super, event) {
                        $item.css(position)
                    },
                    onMousedown: function ($item, _super, event) {
                        console.log(event);
                        if (!event.target.nodeName.match(/^(input|select|textarea|option)$/i) && event.target.classList[0] != 'delRow' && event.target.classList[2] != 'expandType' && event.target.classList[0] != 'delRow' && event.target.classList[2] != 'expandType' && event.target.classList[2] != 'contractType') {
                            event.preventDefault();
                            return true
                        }
                    }
                });
            });
        });

        // Initialize Table Sorting
        $('.sorted_table').sortablesd({
            containerSelector: 'table',
            itemPath: '> tbody',
            itemSelector: 'tr',
            placeholder: '<tr class="placeholder"/>',
            // set $item relative to cursor position
            onDragStart: function ($item, container, _super, event) {
                $item.css({
                    height: $item.outerHeight(),
                    width: $item.outerWidth()
                });
                $item.addClass(container.group.options.draggedClass);
                $("body").addClass(container.group.options.bodyClass);
            },
            onDrag: function ($item, position, _super, event) {
                $item.css(position)
            },
            onMousedown: function ($item, _super, event) {
                console.log(event);
                if (!event.target.nodeName.match(/^(input|select|textarea|option)$/i) && event.target.classList[0] != 'delRow' && event.target.classList[2] != 'expandType' && event.target.classList[2] != 'contractType') {
                    event.preventDefault();
                    return true
                }
            }
        });


    });

    /** When the window is loaded: */

    $(window).load(function () {

    });
    /**
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

})(jQuery);


