jQuery(document).ready( function( $ ) {
    $('.cplink_toggle_btn').click( cplink_toggle_el );
    function cplink_toggle_el(e){
        e.preventDefault();
        let cplink_el = $(this).data('cplink_el');
        let cplink_toggle_el = $('.cplink_toggle_el[data-cplink_el='+cplink_el+']');
        if( cplink_toggle_el.hasClass('cplink_hidden') ){
            $(this).text( $(this).data('hide_txt') );
            cplink_toggle_el.removeClass('cplink_hidden').slideDown(0);
        } else {
            $(this).text( $(this).data('view_txt') );
            cplink_toggle_el.addClass('cplink_hidden').slideUp(0);
        }

        return false;
    }
});
