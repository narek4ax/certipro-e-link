var $ = jQuery.noConflict();

jQuery(document).ready(function ($) {
    startDate: '-3d'
    //});

    // Validation Start --


    $("#cplink_settings_form").validate({
        onfocusout: function (element) {
            this.element(element);
        },
        /*submitHandler: function (form) {
            //$(form).submit();
        }*/
    });
    $.validator.addMethod(
        "multiemails",
        function(value, element) {
            console.log('emails ',value);
            if (this.optional(element)) // return true on optional element
                return true;
            var emails = value.split(/[;,]+/); // split element by , and ;
            valid = true;
            for (var i in emails) {
                console.log('emails ',emails);
                console.log('i ',i);
                value = emails[i];
                valid = valid &&
                    jQuery.validator.methods.email.call(this, $.trim(value), element);
            }
            return valid;
        },

        "Your email address's must be in the format of name@domain.com"
    );


    $("#field_order_export_error_email_").rules("add", {
        //required: true,
        multiemails: true,
        /*messages: {
//            required: "Required input", 
            email: "Your email address must be in the format of name@domain.com"
        }*/
    });
    $("#field_error_email_addr_").rules("add", {
        //required: true,
        multiemails: true,
        /*messages: {
//            required: "Required input",
            email: "Your email address must be in the format of name@domain.com"
        }*/
    });
    $("#field_product_import_error_email_").rules("add", {
        //required: true,
        multiemails: true,
        /*messages: {
//            required: "Required input",
            email: "Your email address must be in the format of name@domain.com"
        }*/
    });
    $("#field_customer_import_error_email_").rules("add", {
        //required: true,
        multiemails: true,
        /*messages: {
//            required: "Required input",
            email: "Your email address must be in the format of name@domain.com"
        }*/
    });

    $("#field_division_no_").rules("add", {
        maxlength: 2,
        messages: {
            maxlength: jQuery.validator.format("Maximum Length: {0} characters.")
        }
    });
    $("#field_ar_division_no_").rules("add", {
        maxlength: 2,
        messages: {
            maxlength: jQuery.validator.format("Maximum Length: {0} characters.")
        }
    });
    $("#field_customer_no_").rules("add", {
        maxlength: 20,
        messages: {
            maxlength: jQuery.validator.format("Maximum Length: {0} characters.")
        }
    });
    // -- Validation End


    //$('#cplink_option_area .tabs_wrapper .tab a').click( open_cplink_global_tab );
    function open_cplink_global_tab() {
        var current_tab = '#' + $(this).attr('id') + '_tab';
        $('#cplink_option_area .tabs_wrapper .tab a.active').removeClass('active');
        $(this).addClass('active');
        $('#cplink_option_area .tabs_content_wrapper .tab_content.active').removeClass('active');
        $(current_tab).addClass('active');
        $('#cplink_option_area .tabs_content_wrapper .tab_content.active .i_cplink_section_tab').first().children('a').click();
        return false;
    }

    //$('#cplink_option_area .tabs_wrapper .tab').first().children('a').click();

    $('.i_cplink_section_tab a').click(open_cplink_section_tab);

    function open_cplink_section_tab() {
        var current_tab = '#' + $(this).attr('id') + '_option';
        $('.i_cplink_section_tab a.active').removeClass('active');
        $(this).addClass('active');
        $('.i_cplink_section_content.active').removeClass('active');
        $(current_tab).addClass('active');

        return false;
    }

    $('.i_cplink_section_tab').first().children('a').click();

    $('.i_color_picker').wpColorPicker({
        // a callback to fire whenever the color changes to a valid color
        change: function (event, ui) {
            var t_element = event.target;
            var t_color = ui.color.toString(); //console.log(t_color);
            t_element = $(t_element);

            switch (t_element.attr('id')) {
                case 'field_color_1_primary_':
                    $('#field_primary_color_, #field_primary_hover_color_, #field_header_footer_menu_text_color_, ' +
                        '#field_search_btn_color_, #field_woo_btn_color_, #field_woo_btn_hover_color_, ' +
                        '#field_woo_message_border_color_').val(t_color).change();
                    break;
                case 'field_color_2_primary_':
                    $('#field_secondary_color_').val(t_color).change();
                    break;
                case 'field_color_2_secondary_':
                    $('#field_secondary_hover_color_').val(t_color).change();
                    break;
            }
            //$(t_element).change();
        }
    });
    $('.i_datepicker').datepicker({dateFormat: 'dd-mm-yy'});
    /*
    * * Media / Uploading files
    */
    var file_frame, i_id, thiss, img_id = 1;

    $('.upload_image_button').on('click', function (event) {
        event.preventDefault();
        i_id = $(this).attr('id');

        if (file_frame) {
            file_frame.open();
            return;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: jQuery(this).data('uploader_title'),
            button: {
                text: jQuery(this).data('uploader_button_text'),
            },
            multiple: false // Set to true to allow multiple files to be selected
        });

        // When an image is selected, run a callback.
        file_frame.on('select', function () {
            attachments = file_frame.state().get('selection').toJSON();
            i_add_image(attachments);
        });

        file_frame.open();
    });

    function i_add_image(image) {
        image = image[0];
        $('#' + i_id).val(image.url);
        $('.i_preview_' + i_id).attr('src', image.url).removeClass('i_hidden');
        return false;
    }

    $('body').on('click', '.i_remove', get_open_del_window);

    function get_open_del_window() {
        var img_id = $(this).find('img').attr('id');
        if (img_id == '') {
            return false;
        }
        thiss = $(this);
        if (confirm("Delete this Image?")) {
            $(this).parents('.fb_image_li').remove();
        }
    }

    $('.i_add_featured_post').click(i_add_featured_post);

    function i_add_featured_post() {
        var featured_post = $(".featured_post_ex").first().clone();
        var featured_post_item = featured_post.children('select');
        featured_post_item.attr("id", featured_post_item.attr("id") + '_' + $(".featured_post_ex").length);
        featured_post_item.val('null');

        $("#featured_posts_list").append(featured_post);

        return false;
    }

    $("#featured_posts_list").sortable({
        stop: function (event, ui) {
            var i = 0;
        }
    });

    $('body').on('click', '.i_remove_feature_post', function () {
        if ($(".featured_post_ex").length > 1) {
            console.log($(".featured_post_ex").length);
            var remove_el = $(this).parents('.featured_post_ex');
            remove_el.hide(500, function () {
                remove_el.remove();
            });
        }
        return false;
    });


    /*$('#field_color_1_primary_').change( field_color_1_primary_changed );
    function field_color_1_primary_changed(){
        console.log( $( this ).val() );
    }*/
    function checkOptionsDependancies() {
        if (typeof dependencies_array !== 'undefined') {
            $.each(dependencies_array, function (key, val) {
                var countOfConditions = 0;
                var countOfConditionsMet = 0;
                $.each(val, function (key1, val1) {
                    if ($('#field_' + key1 + '_').val() == val1) {
                        countOfConditionsMet++;
                    }
                    countOfConditions++;
                });
                $('.' + key + '_div').addClass('hide_row');
                if (countOfConditionsMet == countOfConditions) {
                    $('.' + key + '_div').removeClass('hide_row');
                }
            });
        }
    }

    checkOptionsDependancies()

    $('#cplink_option_content select').on('change', function () {
        checkOptionsDependancies()
        /*var currentSelectId = $(this).attr('id');
        var value = $(this).val();
        if($('[data-depends_from="'+currentSelectId+'"]').length > 0){
            $('[data-depends_from="'+ currentSelectId +'"]').addClass('hide_row');
            $('[data-depends_from="'+ currentSelectId +'"][data-dependance_value="'+ value +'"]').removeClass('hide_row');
        }*/
    });


});

