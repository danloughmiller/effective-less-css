<?php
/*
Plugin Name: Effective LessCSS
Plugin URI: 
Description: 
Version: 1.0.8
Author: Daniel Loughmiller / Effect Web Agency
Author URI: 
Text Domain: 
Domain Path: 
*/

require_once('vendor/autoload.php');
require_once('lib/singleton.class.php');
require_once('lib/plugin.class.php');

define('EFFECTIVE_LESS_CSS_URL', plugins_url('', __FILE__));


class EffectiveLessCSSPlugin extends EffectiveLessCSS\EffectivePlugin
{
    const field_prefix = 'effective_less_css_';

    var $alert_compile_error = false;
    var $alert_updated = false;

    function __construct()
    {
        parent::__construct();

        add_action( 'admin_notices', array(&$this,'admin_notices'));
        add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
    }

    function onAdminMenu()
    {
        add_theme_page('Effective LessCSS', 'Effective LessCSS', 'manage_options', 'effective-less-css', array(&$this, 'pageMain'));
    }

    function pageMain()
    {
        include('views/main.php');
    }

    public function onEnqueueScripts()
    {
        $timestamp = get_option(self::field_prefix.'main_style_timestamp');
        $deps = array();
        if (get_option('stylesheet')=='astra-child')
            $deps[] = 'astra-child-theme-css';
        

        if (!empty($timestamp))
            wp_enqueue_style('effective_less_css', $this->getCSSUrl(), $deps, $timestamp, 'all');

    }

    function onEnqueueAdminScripts()
    {
        wp_enqueue_script( 'ace', EFFECTIVE_LESS_CSS_URL . '/vendor/ajaxorg/src-min/ace.js', array(), 1.0);
        wp_enqueue_script( 'effective-less-js', EFFECTIVE_LESS_CSS_URL . '/assets/effective-less.js', array('ace'), 1.0, true);
        wp_enqueue_style( 'effective-less-css', EFFECTIVE_LESS_CSS_URL . '/assets/effective-less.css');
    }

    function getContent($field='main_style')
    {
        $field = self::field_prefix.$field;
        $content = get_option($field);

        if (empty($content))
            return '';

        return $content;
    }

    function setContent($content, $field='main_style')
    {
        $field = self::field_prefix.$field;
        update_option($field, $content);

        //Only updates the timestamp if it wrote the file
        $result = $this->compileLess($content);
        if ($result) {
            update_option($field.'_timestamp', date('U'));
        }        
    }

    public function onInit()
    {
        if (!empty($_REQUEST['effective_less_css_save']) && current_user_can('manage_options')) {
            $this->setContent(stripslashes($_REQUEST['effective_less_css_textarea']));
            $this->alert_updated=true;
        }
    }

    function admin_notices()
    {
        if ($this->alert_updated)
        {
            $class = 'notice notice-success';
            $message = __( 'LessCSS Updated', 'sample-text-domain' );
        
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
        }

        if ($this->alert_compile_error !== false) {
            $class = 'notice notice-error';
            $message = __( 'LessCSS Compile Error: ' . $this->alert_compile_error, 'sample-text-domain' );
        
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
        }
    }

    function getCSSFile()
    {
        $upload = wp_get_upload_dir();
        $basedir = $upload['basedir'];
        $file = $basedir . '/effective_less_css.css';

        return $file;
    }

    function getCSSUrl()
    {
        $upload = wp_get_upload_dir();
        $basedir = $upload['baseurl'];
        $file = $basedir . '/effective_less_css.css';

        return $file;
    }

    function compileLess($content, $file=false)
    {
        if ($file===false)
            $file = $this->getCSSFile();

        $less = new lessc();
        try {
            $compiled = $less->compile($content);
            file_put_contents($file, $this->minify_css($compiled));
        } catch (exception $e) {
            $this->alert_compile_error = $e->getMessage();
            return false;
        }
        
        return true;
    }

    function minify_css($input) {
        if(trim($input) === "") return $input;
        return preg_replace(
            array(
                // Remove comment(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
                // Remove unused white-space(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
                // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
                // Replace `:0 0 0 0` with `:0`
                '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
                // Replace `background-position:0` with `background-position:0 0`
                '#(background-position):0(?=[;\}])#si',
                // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
                '#(?<=[\s:,\-])0+\.(\d+)#s',
                // Minify string value
                '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
                '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
                // Minify HEX color code
                '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
                // Replace `(border|outline):none` with `(border|outline):0`
                '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
                // Remove empty selector(s)
                '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
            ),
            array(
                '$1',
                '$1$2$3$4$5$6$7',
                '$1',
                ':0',
                '$1:0 0',
                '.$1',
                '$1$3',
                '$1$2$4$5',
                '$1$2$3',
                '$1:0',
                '$1$2'
            ),
        $input);
    }

    function plugins_loaded()
    {
        $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
            'https://github.com/danloughmiller/effective-less-css/',
            __FILE__,
            'effective-less-css'
        );
        $myUpdateChecker->getVcsApi()->enableReleaseAssets();
    }
}
new EffectiveLessCSSPlugin();