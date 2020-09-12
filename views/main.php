<div class="wrap">
    <h1>Effective LessCSS</h1>
    <div class="elc_notices"></div>
    <form id="effective_less_editor_form" action="themes.php?page=effective-less-css" method="post">
        <input type="hidden" name="effective_less_css_save" value="1" />
        <div id="effective_less_css_editor"></div>
        <textarea name="effective_less_css_textarea"><?=(EffectiveLessCSSPlugin::instance())->getContent()?></textarea>
        <br />
        <button type="submit" class="button button-primary">Update</button>
    </form>
</div>