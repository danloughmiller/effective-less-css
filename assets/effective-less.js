var editor = ace.edit("effective_less_css_editor");
var textarea = jQuery('textarea[name="effective_less_css_textarea"]');

editor.setTheme("ace/theme/tomorrow_night_eighties");
editor.session.setMode("ace/mode/less");
editor.setFontSize("16px");
editor.getSession().setValue(textarea.val());
editor.getSession().on('change', function(){
    textarea.val(editor.getSession().getValue());
});


if (jQuery('#effective_less_editor_form').length>0){
    jQuery(window).bind('keydown', function(event) {
        if (event.ctrlKey || event.metaKey) {
            switch (String.fromCharCode(event.which).toLowerCase()) {
            case 's':
                event.preventDefault();
                textarea.val(editor.getSession().getValue());
                jQuery('#effective_less_editor_form').submit();
                break;
            }
        }
    });
}

jQuery(document).ready( function () {
    jQuery('#effective_less_editor_form').on('submit', function(e) {
        e.preventDefault();
        elc_save_ajax();
    });
});


function elc_save_ajax()
{
    jQuery('.elc_notices').html('');
    jQuery.ajax({
        type: 'post',
        dataType: 'html',
        url : elessjs.ajax_url,
        data: {
            'action': 'elc_save_less',
            'content': textarea.val()
        },
        success: function(response)
        {
            jQuery('.elc_notices').html(response);
        }
    });
}