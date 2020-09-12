<?php
namespace EffectiveLessCSS;

class EffectivePlugin extends Singleton
{
    const version = '1.0.1';

    function __construct()
    {
        add_action('init', array(&$this, 'onInit'));
        add_action('wp', array(&$this, 'onWP'));
        add_action('wp_enqueue_scripts', array(&$this, 'onEnqueueScripts'));
        add_action('admin_enqueue_scripts', array(&$this, 'onEnqueueAdminScripts'));
        add_action('admin_menu', array(&$this, 'onAdminMenu'));
    }

    

    public function onInit()
    {
        
    }

    public function onAdminMenu()
    {
        
    }

    public function onWP()
    {
        
    }

    public function onEnqueueScripts()
    {

    }

    public function onEnqueueAdminScripts()
    {

    }
}