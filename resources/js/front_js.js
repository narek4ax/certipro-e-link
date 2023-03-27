jQuery(document).ready(function ($) {
    /*$('.sage-orders-table > tbody > tr').each(function(index, el){
        $('.woocommerce-orders-table').append( $(el) );
    });

    $('.cplink_toggle_btn').click( cplink_toggle_el );
    function cplink_toggle_el(e){
        e.preventDefault();
        let cplink_el = $(this).data('cplink_el');
        let cplink_toggle_el = $('.cplink_toggle_el[data-cplink_el='+cplink_el+']');
        if( cplink_toggle_el.hasClass('cplink_hidden') ){
            $(this).text( $(this).data('hide_txt') );
            cplink_toggle_el.removeClass('cplink_hidden').slideDown(300);
        } else {
            $(this).text( $(this).data('view_txt') );
            cplink_toggle_el.addClass('cplink_hidden').slideUp(300);
        }

        return false;
    }*/

    if( $('.scroll_to_me').length ){
        $('html,body').animate({'scrollTop': ($('.scroll_to_me').offset().top - 50)}, 500);
    }

    $('.cplink_print_this').click(function(){
        console.log('Start Print fn');
        let print_el = $( $(this).data('print_el') );
        if( print_el.length ) {
            print_el.printThis({
                debug: true,
                importCSS: true,
                importStyle: true,
                removeScripts: true,
                copyTagClasses: true,
                printDelay: 1333,
                loadCSS: "/wp-content/plugins/certipro-e-link/resources/style/print-this.css",
                //header: "<h1>Look at all of my kitties!</h1>"
            });
        }
        return false;
    });

   /* var sage_order_table = $('#sage-orders-table').DataTable({
        colReorder: false,
        ordering: false
    });
    $('.sage_order_search_button').click(function (event) {
        sage_order_table.search('').columns().search('').draw();
        event.preventDefault();
        //sage_order_table.search($("#web_order_number").val()).draw();
        if ($("#sales_order_number").val() !== "") {
            sage_order_table.column(0).search($("#sales_order_number").val()).draw();
        }
        if ($("#web_order_number").val() !== '') {
            sage_order_table.column(1).search($("#web_order_number").val()).draw();
        }
        if ($("#po_number").val() !== '') {
            sage_order_table.column(3).search($("#po_number").val()).draw();

        }
        console.log($("#po_number").val());
        if ($("#sales_order_number").val() == "" && $("#web_order_number").val() == '' && $("#po_number").val() == '') {
            sage_order_table.search('').columns().search('').draw();

        }
    });*/
    $('.sage-invoices-table,.sage-orders-table,.sage-table').basictable({
        breakpoint: 767
    });

    $('.cp_choose_form_type').on('click',function(){
        $('.cp_choose_form_type').removeClass('active');
        $(this).addClass('active');
        $(this).parents('form').addClass('show_fields');

        $('#reg_cp_customer_no').parent().addClass('hidden_row');
        $('#reg_billing_postcode').parent().addClass('hidden_row');
        $('#reg_cp_customer_no').attr('disabled','disabled');
        $('#reg_has_account').attr('disabled','disabled');
        $('#reg_billing_postcode').attr('disabled','disabled');
        if($(this).hasClass('have_account')){
            $('#reg_cp_customer_no').parent().removeClass('hidden_row');
            $('#reg_billing_postcode').parent().removeClass('hidden_row');

            $('#reg_cp_customer_no').removeAttr('disabled');
            $('#reg_has_account').removeAttr('disabled');
            $('#reg_billing_postcode').removeAttr('disabled');
        }
    });
});
