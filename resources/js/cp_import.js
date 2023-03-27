jQuery(document).ready(function ($) {
    var action_in_process = false;
    $('.cplink_close_response').click(cplink_close_response);
    function cplink_close_response() {
        $('.cplink_response_txt').html('');
        $(this).parents('.cplink_response_wrapper').hide();
    }
    function show_msg_response(r_message = '', r_type = 'loading', is_append = false) {
        if (is_append === undefined) {
            is_append = false;
        }
        if( !is_append ){
            $('.cplink_response_txt').html(r_message);
        } else {
            $('.cplink_response_txt').append('<div class="cplink_resp_'+r_type+'">'+r_message+'</div>');
        }

        $('.cplink_response_txt').parent()
            .removeClass('cplink_resp_loading cplink_resp_success cplink_resp_error')
            .addClass('cplink_resp_' + r_type)
            .parents('.cplink_response_wrapper')
            .showInlineBlock();

        let result_icon = $('.cplink_response .result_icon');
        if( result_icon.attr('data-'+r_type) ){
            result_icon.children('i').attr('class', 'dashicons '+result_icon.attr('data-'+r_type));
        } else {
            result_icon.children('i').attr('class', 'dashicons ')
        }
    }

    var current_source_action = '';
    var current_promise = 0;
    var promises_el = [];

    $('.cp_do_import').click(cp_do_import);
    function cp_do_import(e){
        e.preventDefault();

        if( action_in_process )
            return false;

        current_source_action = $(this).parents('.i_import_type_wrapper').find('.cp_import_source').val();
        cp_request_import(this);

        return false;
    }
    $('.bulk_import_submit').click( cp_do_bulk_import );
    function cp_do_bulk_import(e){
        e.preventDefault();

        if( action_in_process )
            return false;

        //var ajax_completed = 1;

        current_source_action = $(this).parents('.bulk_import_div').find('.bulk_source_action').val();
        current_promise = 0;
        promises_el = $('.i_check_item:checked');
        if( promises_el.length ) {
            cp_loop_import(current_promise);
        } else {
            show_msg_response('Please check a least 1 import module item bellow!', 'error');
        }

        return false;
    }

    function cp_loop_import(i){
        $.when( cp_request_import( promises_el[i] ) ).done(function(){
            current_promise++;
            if( current_promise < promises_el.length )
                cp_loop_import( current_promise );
        });
    }


    function cp_request_import(el){

        action_in_process = true;

        //ajax_completed = 0;
        let import_tr = $(el).parents('tr.i_import_type_wrapper');
        if( !import_tr.length )
            show_msg_response( 'Import Module not selected!', 'error');

        let cplink_import_type = $(el).val();
        let import_sec = import_tr.attr('data-import_desc');
        let ajax_action = 'cplink_import';
        if( $('.cp_import_area').length && $('.cp_import_area').attr('data-import_area') == 'cpbp' )
            ajax_action = 'cpbp_import';

        show_msg_response('<b>'+import_sec+'</b> Module '+cplink_infos.importMessage, 'loading', true);

        let formData = {
            'action': ajax_action,
            'cplink_import_type': cplink_import_type,
            'cplink_import_source': current_source_action
        };

        return $.ajax({
            type: 'POST',
            url: cplink_infos.ajax_url,
            data: formData,
            success: function (data) {
                console.log(data);
                data = JSON.parse(data);
                last_import_data = data.last_import_data;

                if (data.status == 1) {
                    show_msg_response(data.html, 'success', true);
                } else {
                    let err_txt = data.html;
                    if( !err_txt )
                        err_txt = last_import_data.run_result_txt;
                    show_msg_response(err_txt, 'error', true);
                }

                import_tr.find('.import_date').text( last_import_data.date );
                import_tr.find('.import_run_result').text( last_import_data.run_result_txt );
                import_tr.find('.import_status').text( last_import_data.status_txt );
                import_tr.fadeOut(500, function() {
                    import_tr.fadeIn(500);
                });

                action_in_process = false;
            },
            error: function (xhr, ajaxOptions, thrownError) {
                show_msg_response('Error '+xhr.status+': ' + thrownError, 'error', true);
                action_in_process = false;
            }
            //dataType: dataType,
            //async:false
        });
    }

});

jQuery.fn.showInlineBlock = function () {
    return this.css('display', 'inline-flex');
};