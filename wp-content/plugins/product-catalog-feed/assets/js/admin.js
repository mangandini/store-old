jQuery(function($){
    function Wpwoof_getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return null;
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    var menutab = Wpwoof_getParameterByName('tab'); 
    var edittab = Wpwoof_getParameterByName('edit');

    if( menutab == null || menutab < 0 )
        menutab = 0;
    if( edittab == null ) {
        //toggle tab content
        $('.wpwoof-menu li').each(function(tabIndex, tabEl) {
            var $tab = $(this);
            $tab.on('click', function() {
                $('.wpwoof-settings-panel').hide();
                $('.wpwoof-menu li').removeClass('wpwoof-menu-selected');
                $tab.addClass('wpwoof-menu-selected');
                $('.wpwoof-settings-panel').eq(tabIndex).show();
            });
        });
    }

    $(document).on('click', '.wpwoof-open-popup', function(event) {
        event.preventDefault();
        $(this).parents('.wpwoof-open-popup-wrap').find('.wpwoof-popup-wrap').show();
    });

    $(document).on('click', '.wpwoof-popup-close, .wpwoof-popup-done', function(){
        $(this).parents('.wpwoof-popup-wrap').hide();	
    });

    $(document).on('submit', '#wpwoof-addfeed', function(e){
        var feed_name = $('input[name=feed_name]').val();
        var regexEmpty = /^\s+$/;
        var regexTitle = /^([\w+\-])$/;
        var regexLength = /^([\w+\-]){3,30}$/;
        if( feed_name == '' || regexEmpty.test(feed_name) ) {
            e.preventDefault();
            alert('The feed name must not be empty.');
            return false;
        } else if( regexTitle.test(feed_name) ) {
            e.preventDefault();	
            alert('The feed name must not contans special characters.');
            return false;
        } else if( regexTitle.test(feed_name) ) {
            e.preventDefault();	
            alert('The feed name must be atleast 3 character and not more than 30.');
            return false;
        }
    });

    $(document).on('click', '#wpwoof-hide-additional', function(){
        $('#wpwoof-additionalfield-wrap').toggleClass('wpwoof-additional-hide');
        if( $('#wpwoof-additionalfield-wrap').hasClass('wpwoof-additional-hide') ) {
            $(this).text('Show Additional Attributes');
        } else {
            $(this).text('Hide Additional Attributes');
        }
    });

    $(document).on('click', '#wpwoof-popup-categories li input.feed_category', function(e) {
        var cat_id = $(this).attr('id') || '';
        if( cat_id != 'feed_category_all' ) {
            var allchecked = true;
            $('#wpwoof-popup-categories li input.feed_category').each(function(index, el) {
                var cat_id = $(this).attr('id') || '';
                if( cat_id != 'feed_category_all' && $(this).prop('checked') == false )
                    allchecked = false;	
            });

            if( !allchecked ) {
                $('#feed_category_all').prop('checked', false);
            } else {
                $('#feed_category_all').prop('checked', true);
            }
        }
    });

    $(document).on('click', '#feed_category_all', function(e) {
        var tick = $(this).prop('checked');
        $('#wpwoof-popup-categories li input.feed_category').prop('checked', tick);
    });

    $(document).on('click', '#feed_check_all_additional', function(e) {
        var tick = $(this).prop('checked');
        $('input.wpwoof-field-additional').prop('checked', tick);
    });

    $(document).on('change', 'select.wpwoof_mapping_option', function(){
        if( $(this).val() == 'wpwoofdefa_use_custom_attribute' ) {
            if( !$(this).next('input' ).hasClass('wpwoof_mapping_attribute') ) {
                var name = $(this).attr('name');
                name = name.toString();
                name = name.replace('[value]', '[custom_attribute]');
                var html = '<input type="text" name="'+name+'" value="" class="wpwoof_mapping_attribute" />';
                $(this).after(html);
            }
        } else {
            if( $(this).next('input' ).hasClass('wpwoof_mapping_attribute') ) {
                $(this).next('input' ).remove();
            }
        }
    });
});
