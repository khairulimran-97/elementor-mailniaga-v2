<?php
/**
 * Plugin Name: Elementor Forms MailNiaga v2
 * Description: Custom addon for Elementor Forms which adds new subscriber to MailNiaga v2 after form submission.
 * Plugin URI:  https://lamanweb.my/
 * Version:     1.0.0
 * Author:      Laman Web
 * Author URI:  https://lamanweb.my/
 * Text Domain: elementor-forms-mailniaga
 *
 * Elementor tested up to: 3.7.0
 * Elementor Pro tested up to: 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . 'includes/enqueue.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/api-calls.php';


register_activation_hook( dirname( __FILE__, 2 ) . '/elementor-forms-mailniaga.php', 'fetch_mailniaga_lists' );

/**
 * Add new subscriber to MailNiaga v2.
 *
 * @since 1.0.0
 * @param ElementorPro\Modules\Forms\Registrars\Form_Actions_Registrar $form_actions_registrar
 * @return void
 */

final class Mailniaga_Elements {

    const VERSION = '1.0.0';
    const MINIMUM_ELEMENTOR_VERSION = '2.5.0';
    const MINIMUM_PHP_VERSION = '5.4';

    private static $_instance = null;

    public static function instance() {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {

        add_action('init', [$this, 'i18n']);
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function i18n() {

        load_plugin_textdomain('elementor-forms-mailniaga');
    }

    public function init() {

        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return;
        }

        if (!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return;
        }

        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
            return;
        }

        if (!function_exists('elementor_pro_load_plugin')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return;
        }

        // Instead of require_once, trigger an action to include the plugin.php file
        add_action('elementor_pro/forms/actions/register', function ($form_actions_registrar) {
        include_once(__DIR__ . '/plugin.php');
        $form_actions_registrar->register(new MailNiaga_Action_After_Submit());
        });
    
    }

    public function admin_notice_missing_main_plugin() {

        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'elementor-forms-mailniaga'),
            '<strong>' . esc_html__('Elementor Forms MailNiaga v2', 'elementor-forms-mailniaga') . '</strong>',
            '<strong>' . esc_html__('Elementor Pro', 'elementor-forms-mailniaga') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    public function admin_notice_minimum_elementor_version() {

        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version "%3$s" or greater.', 'elementor-forms-mailniaga'),
            '<strong>' . esc_html__('Elementor Forms MailNiaga v2', 'elementor-forms-mailniaga') . '</strong>',
            '<strong>' . esc_html__('Elementor Pro', 'elementor-forms-mailniaga') . '</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    public function admin_notice_minimum_php_version() {

        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-forms-mailniaga'),
            '<strong>' . esc_html__('Elementor Forms MailNiaga v2', 'elementor-forms-mailniaga') . '</strong>',
            '<strong>' . esc_html__('PHP', 'elementor-forms-mailniaga') . '</strong>',
            self::MINIMUM_PHP_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

Mailniaga_Elements::instance();

//function add_new_mailniaga_form_action( $form_actions_registrar ) {
    //include_once( __DIR__ .  '/plugin.php' );
    //$form_actions_registrar->register( new MailNiaga_Action_After_Submit() );
//}
//add_action( 'elementor_pro/forms/actions/register', 'add_new_mailniaga_form_action' );

add_filter( 'plugin_action_links', 'add_action_plugin', 10, 5 );

function add_action_plugin( $actions, $plugin_file )
{
   static $plugin;

   if (!isset($plugin))
		$plugin = plugin_basename(__FILE__);
   if ($plugin == $plugin_file) {

      $settings = array('settings' => '<a href="admin.php?page=mailniaga-settings">' . __('Settings', 'General') . '</a>');
      $site_link = array('support' => '<a href="https://lamanweb.my/hubungi/" target="_blank">Support</a>');

      $actions = array_merge($site_link, $actions);
      $actions = array_merge($settings, $actions);
      
   }

   return $actions;
}
