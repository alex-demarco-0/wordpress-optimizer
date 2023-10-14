<?php

/**
 * Plugin Name: Wordpress Optimizer
 * Plugin URI:  https://wordpress.org/
 * Description: Allows disabling commonly useless WordPress features to improve website performance.
 * Version:     0.1
 * Author:      Alessandro De Marco
 * Author URI:  https://github.com/alex-demarco-0
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * 
 * Copyright © 2023 Alessandro De Marco
 */

register_activation_hook(__FILE__, array('Wordpress_Optimizer', 'activate'));
add_action('init', array('Wordpress_Optimizer', 'instance'), 1);

class Wordpress_Optimizer {

    protected $tweaks;

    protected static $instance = null;

    public static function instance()
    {
        if (!(static::$instance instanceof static)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function __construct()
    {
        $tweaks = get_option('wordpress_optimizer_tweaks', array());
        $this->tweaks = !empty($tweaks) ? array_flip($tweaks) : array();
        $this->apply_tweaks();
        add_action('admin_menu', array($this, 'submenu_page'));
        add_action('admin_init', array($this, 'tweaks_init'));
    }

    public function activate()
    {
        add_option('wordpress_optimizer_tweaks', array('apply_all'));
    }

    /* admin (submenu under 'tools' menu) */
    public function submenu_page()
    {
        add_submenu_page(
            'tools.php',
            'WordPress Optimizer',
            'WordPress Optimizer',
            'manage_options',
            'wordpress-optimizer',
            array($this, 'tweaks_page_html')
        );
    }

    public function tweaks_page_html()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                '<h1>' . __('You need a higher level of permission.') . '</h1>' .
                '<p>' . __('Sorry, you are not allowed to manage these options.') . '</p>',
                403
            );
        }
        if (isset($_GET['settings-updated'])) {
            add_settings_error('wordpress_optimizer_messages', 'wordpress_optimizer_tweaks_updated', __('Impostazioni salvate ⚡', 'wordpress_optimizer'), 'updated');
        }
        ?>
        <div class="wordpress-optimizer-tweaks">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p>
                <?php
                $description = get_plugin_data(__FILE__)['Description'];
                echo substr($description, 0, strpos($description, "<cite>"));
                ?>
            </p>
            <?php settings_errors('wordpress_optimizer_messages'); ?>
            <form action="options.php" method="post">
                <?php
                settings_fields('wordpress_optimizer_tweaks');
                do_settings_sections('wordpress_optimizer_tweaks');
                submit_button('Save');
                ?>
            </form>
        </div>
        <?php
    }

    public function tweaks_init()
    {
        register_setting('wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks');

        add_settings_section('wordpress_optimizer_tweaks_section', '', '', 'wordpress_optimizer_tweaks');

        add_settings_field('wordpress_optimizer_apply_all_field', 'Apply all', array($this, 'apply_all_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_xmlrpc_field', 'Disable XML-RPC', array($this, 'xmlrpc_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_pingback_field', 'Disable X-Pingback HTTP header', array($this, 'pingback_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_self_pingbacks_field', 'Disable Self Pingbacks', array($this, 'self_pingbacks_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_headers_field', 'Remove unnecessary header fields', array($this, 'headers_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_oembed_field', 'Remove oEmbed discovery links', array($this, 'oembed_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_shortlinks_field', 'Disable shortlinks headers', array($this, 'shortlinks_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_rest_field', 'Disable REST APIs', array($this, 'rest_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_emojicons_field', 'Disable Emojicons', array($this, 'emojicons_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_update_checks_field', 'Disable update checks', array($this, 'update_checks_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_amp_field', 'Disable useless AMP features ', array($this, 'amp_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_translation_service_field', 'Disable external translation service calls', array($this, 'translation_service_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_yoast_head_field', 'Remove Yoast SEO garbage', array($this, 'yoast_head_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_admin_bar_field', 'Show admin toolbar in admin only', array($this, 'admin_bar_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_admin_favicon_field', 'Include favicon in admin pages', array($this, 'admin_favicon_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
        add_settings_field('wordpress_optimizer_action_scheduler_default_runner_field', "Disable Action Scheduler's default (WP Cron) queue runner", array($this, 'action_scheduler_default_runner_field_callback'), 'wordpress_optimizer_tweaks', 'wordpress_optimizer_tweaks_section');
    }

    public function apply_all_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="apply_all" <?php echo array_key_exists('apply_all', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function xmlrpc_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="xmlrpc" <?php echo array_key_exists('xmlrpc', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function pingback_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="pingback" <?php echo array_key_exists('pingback', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function self_pingbacks_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="self_pingbacks" <?php echo array_key_exists('self_pingbacks', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function headers_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="headers" <?php echo array_key_exists('headers', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function oembed_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="oembed" <?php echo array_key_exists('oembed', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function shortlinks_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="shortlinks" <?php echo array_key_exists('shortlinks', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function rest_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="rest" <?php echo array_key_exists('rest', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function emojicons_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="emojicons" <?php echo array_key_exists('emojicons', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function update_checks_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="update_checks" <?php echo array_key_exists('update_checks', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function amp_field_callback()
    {
        $plugin_active = is_plugin_active('amp/amp.php');
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="amp" <?php echo array_key_exists('amp', $this->tweaks) ? 'checked="checked"' : ''; ?><?php echo !$plugin_active ? ' disabled="disabled"' : ''; ?>><?php
    }

    public function translation_service_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="translation_service" <?php echo array_key_exists('translation_service', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function yoast_head_field_callback()
    {
        $plugin_active = is_plugin_active('wordpress-seo/wp-seo.php');
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="yoast_head" <?php echo array_key_exists('yoast_head', $this->tweaks) ? 'checked="checked"' : ''; ?><?php echo !$plugin_active ? ' disabled="disabled"' : ''; ?>><?php
    }
    
    public function admin_bar_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="admin_bar" <?php echo array_key_exists('admin_bar', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function admin_favicon_field_callback()
    {
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="admin_favicon" <?php echo array_key_exists('admin_favicon', $this->tweaks) ? 'checked="checked"' : ''; ?>><?php
    }

    public function action_scheduler_default_runner_field_callback()
    {
        $plugin_active = class_exists('ActionScheduler');
        ?><input type="checkbox" name="wordpress_optimizer_tweaks[]" value="as_default_runner" <?php echo array_key_exists('as_default_runner', $this->tweaks) ? 'checked="checked"' : ''; ?><?php echo !$plugin_active ? ' disabled="disabled"' : ''; ?>><?php
    }

    /* tweak functions */
    protected function apply_tweaks()
    {    
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $apply_all = array_key_exists('apply_all', $this->tweaks);
        if ($apply_all || array_key_exists('xmlrpc', $this->tweaks)) {
            add_filter('xmlrpc_enabled', '__return_false'); 
        }
        if ($apply_all || array_key_exists('pingback', $this->tweaks)) {
            add_action('wp_headers', array($this, 'disable_pingback'), 11, 1);
        }
        if ($apply_all || array_key_exists('self_pingback', $this->tweaks)) {
            add_action('pre_ping', array($this, 'disable_self_pingbacks'));
        }
        if ($apply_all || array_key_exists('headers', $this->tweaks)) {
            $this->remove_headers();
        }
        if ($apply_all || array_key_exists('oembed', $this->tweaks)) {
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
        }
        if ($apply_all || array_key_exists('shortlinks', $this->tweaks)) {
            $this->disable_shortlinks();
        }
        if ($apply_all || array_key_exists('rest', $this->tweaks)) {
            $this->disable_rest();
        }
        if ($apply_all || array_key_exists('emojicons', $this->tweaks)) {
            $this->disable_emojicons();
        }
        if ($apply_all || array_key_exists('update_checks', $this->tweaks)) {
            remove_action('init', 'wp_schedule_update_checks');
        }
        if (is_plugin_active('amp/amp.php') && ($apply_all || array_key_exists('amp', $this->tweaks))) {
            $this->remove_amp_useless_stuff();
        }
        if ($apply_all || array_key_exists('translation_service', $this->tweaks)) {
            add_filter('async_update_translation', '__return_false', 1);
        }
        if (is_plugin_active('wordpress-seo/wp-seo.php') && ($apply_all || array_key_exists('yoast_head', $this->tweaks))) {
            add_action('template_redirect', array($this, 'remove_yoast_head'), 9999);
        }
        if ($apply_all || array_key_exists('admin_bar', $this->tweaks)) {
            add_filter('show_admin_bar', array($this, 'show_admin_bar_selectively'));
        }
        if ($apply_all || array_key_exists('admin_favicon', $this->tweaks)) {
            add_filter('admin_head', array($this, 'include_admin_favicon'));
        }
        if ($apply_all || array_key_exists('as_default_runner', $this->tweaks)) {
            // ActionScheduler_QueueRunner::init() is attached to 'init' with priority 1, so we need to run after that
            add_action('init', array($this, 'disable_action_scheduler_default_runner'), 99);
        }
    }

    /** Disables X-Pingback HTTP Header. */
    public function disable_pingback($headers)
    {
        if (isset($headers['X-Pingback'])) {
            // Drop X-Pingback
            unset($headers['X-Pingback']);
        }
        return $headers;
    }

    /** Disables self-pingbacks. */
    function disable_self_pingbacks($links)
    {
        foreach ($links as $l => $link) {
            if (0 === strpos($link, get_option('home'))) {
                unset($links[$l]);
            }
        }
        return $links;
    }

    /** Removes unnecessary headers. */
    public function remove_headers()
    {
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', '_ak_framework_meta_tags');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_action('wp_head', '_ak_framework_meta_tags');
        add_action('wp_print_styles', array($this, 'deregister_styles'), 100);
    }
    public function deregister_styles()
    {
        wp_dequeue_style('category-posts');
    }

    /** Disables shortlinks in \<head\> and in HTTP headers. */
    public function disable_shortlinks()
    {
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('template_redirect', 'wp_shortlink_header');
    }

    /** Disables REST APIs. */
    public function disable_rest()
    {
        // Filters for WP-API version 1.x
        add_filter('json_enabled', '__return_false');
        add_filter('json_jsonp_enabled', '__return_false');
        // Filters for WP-API version 2.x
        add_filter('rest_enabled', '__return_false');
        add_filter('rest_jsonp_enabled', '__return_false');
        // Remove REST API lines from HTML Header
        remove_action('wp_head', 'rest_output_link_wp_head', 10 );
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);
    }

    /** Disables Emojicons. */
    public function disable_emojicons()
    {
        // all actions related to emojis
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        // filter to remove TinyMCE emojis
        add_filter('tiny_mce_plugins', array($this, 'disable_emojicons_tinymce'));
        // Remove dns prefetch
        add_filter('emoji_svg_url', '__return_false');
    }
    public function disable_emojicons_tinymce($plugins)
    {
        if (is_array($plugins)) {
            return array_diff($plugins, array('wpemoji'));
        } else {
            return array();
        }
    }

    /** Disables AMP useless stuff (if AMP plugin installed). */
    public function remove_amp_useless_stuff()
    {
        remove_action('admin_menu', "AMP_Validated_URL_Post_Type::add_admin_menu_new_invalid_url_count", 10);
        remove_filter('dashboard_glance_items', "AMP_Validated_URL_Post_Type::filter_dashboard_glance_items", 10);
        add_action('init', array($this, 'avoid_loading_amp_story_templates'), 99);
        // AMP customizer check query that runs on every post save
        add_filter('amp_customizer_is_enabled', '__return_false');
    }
    public function avoid_loading_amp_story_templates()
    {
        remove_action('wp_loaded', 'amp_story_templates');
    }

    /** Removes Yoast's boasting from the head (if Yoast SEO plugin installed). */
    public function remove_yoast_head()
    {
        if (!class_exists('WPSEO_Frontend')) {
            return;
        }
        $instance = WPSEO_Frontend::get_instance();
        // make sure future version of the plugin does not break the site
        if (!method_exists($instance, 'debug_mark')) {
            return ;
        }
        // remove boast
        remove_action('wpseo_head', array($instance, 'debug_mark'), 2);
    }

    /** Show admin toolbar only when *stb* parameter is set and non-false. */
    public function show_admin_bar_selectively($admin_bar)
    {
        return (is_user_logged_in() && isset($_GET['stb']) && $_GET['stb']);
    }

    /** Include favicon in admin pages. */
    public function include_admin_favicon()
    {
        ?><link rel="icon" type="image/x-icon" href="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/favicon.ico'); ?>"><?php
    }

    /** Disables Action Scheduler's default queue runner, by removing it from the 'action_scheduler_run_queue' hook. */
    public function disable_action_scheduler_default_runner()
    {
        if (class_exists('ActionScheduler')) {
            remove_action('action_scheduler_run_queue', array(ActionScheduler::runner(), 'run'));
        }
    }

}
