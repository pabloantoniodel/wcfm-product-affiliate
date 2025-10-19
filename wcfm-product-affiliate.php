<?php
/**
 * Plugin Name: WCFM Product Affiliate
 * Plugin URI: https://ciudadvirtual.app
 * Description: Sistema de afiliación de productos para WCFM Marketplace. Permite a los vendedores vender productos de otros sin clonarlos, con comisiones duales y tracking de origen.
 * Version: 1.0.0
 * Author: CiudadVirtual
 * Author URI: https://ciudadvirtual.app
 * Text Domain: wcfm-product-affiliate
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 9.0
 * WCFM requires at least: 6.0
 * WCFM Marketplace requires at least: 3.0
 * 
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Plugin constants
define('WCFM_AFFILIATE_VERSION', '1.0.0');
define('WCFM_AFFILIATE_PLUGIN_FILE', __FILE__);
define('WCFM_AFFILIATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCFM_AFFILIATE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WCFM_AFFILIATE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main WCFM Product Affiliate Class
 */
class WCFM_Product_Affiliate {
    
    /**
     * Single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Plugin modules
     */
    public $db;
    public $frontend;
    public $admin;
    public $commission;
    public $tracking;
    
    /**
     * Main Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        // Check dependencies
        add_action('plugins_loaded', array($this, 'check_dependencies'), 5);
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'), 10);
        
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Load textdomain
        add_action('init', array($this, 'load_textdomain'));
    }
    
    /**
     * Check plugin dependencies
     */
    public function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }
        
        if (!class_exists('WCFM')) {
            add_action('admin_notices', array($this, 'wcfm_missing_notice'));
            return false;
        }
        
        if (!class_exists('WCFMmp')) {
            add_action('admin_notices', array($this, 'wcfmmp_missing_notice'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        if (!$this->check_dependencies()) {
            return;
        }
        
        $this->includes();
        $this->init_classes();
        
        do_action('wcfm_affiliate_loaded');
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once WCFM_AFFILIATE_PLUGIN_DIR . 'includes/class-wcfm-affiliate-db.php';
        require_once WCFM_AFFILIATE_PLUGIN_DIR . 'includes/class-wcfm-affiliate-tracking.php';
        require_once WCFM_AFFILIATE_PLUGIN_DIR . 'includes/class-wcfm-affiliate-commission.php';
        
        // Frontend
        if (!is_admin() || defined('DOING_AJAX')) {
            require_once WCFM_AFFILIATE_PLUGIN_DIR . 'includes/class-wcfm-affiliate-frontend.php';
        }
        
        // Admin
        if (is_admin()) {
            require_once WCFM_AFFILIATE_PLUGIN_DIR . 'includes/class-wcfm-affiliate-admin.php';
        }
    }
    
    /**
     * Initialize classes
     */
    private function init_classes() {
        $this->db = new WCFM_Affiliate_DB();
        $this->tracking = new WCFM_Affiliate_Tracking();
        $this->commission = new WCFM_Affiliate_Commission();
        
        if (!is_admin() || defined('DOING_AJAX')) {
            $this->frontend = new WCFM_Affiliate_Frontend();
        }
        
        if (is_admin()) {
            $this->admin = new WCFM_Affiliate_Admin();
        }
    }
    
    /**
     * Activation hook
     */
    public function activate() {
        // Check dependencies
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Este plugin requiere WooCommerce. Por favor instala y activa WooCommerce primero.', 'wcfm-product-affiliate'));
        }
        
        if (!class_exists('WCFM')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Este plugin requiere WCFM Frontend Manager. Por favor instala y activa WCFM primero.', 'wcfm-product-affiliate'));
        }
        
        if (!class_exists('WCFMmp')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Este plugin requiere WCFM Marketplace. Por favor instala y activa WCFM Marketplace primero.', 'wcfm-product-affiliate'));
        }
        
        // Create tables
        require_once WCFM_AFFILIATE_PLUGIN_DIR . 'includes/class-wcfm-affiliate-db.php';
        WCFM_Affiliate_DB::create_tables();
        
        // Set default options
        $default_options = array(
            'enabled' => 'yes',
            'default_commission_rate' => 1,
            'min_commission_rate' => 1,
            'max_commission_rate' => 50,
            'allow_custom_rates' => 'yes',
            'tracking_method' => 'session',
            'disable_product_multivendor' => 'yes',
        );
        
        add_option('wcfm_affiliate_options', $default_options);
        
        // Set version
        update_option('wcfm_affiliate_version', WCFM_AFFILIATE_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Deactivation hook
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Load textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('wcfm-product-affiliate', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Admin notices
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p>';
        echo '<strong>' . __('WCFM Product Affiliate', 'wcfm-product-affiliate') . '</strong> ';
        echo __('requiere que WooCommerce esté instalado y activo.', 'wcfm-product-affiliate');
        echo '</p></div>';
    }
    
    public function wcfm_missing_notice() {
        echo '<div class="error"><p>';
        echo '<strong>' . __('WCFM Product Affiliate', 'wcfm-product-affiliate') . '</strong> ';
        echo __('requiere que WCFM Frontend Manager esté instalado y activo.', 'wcfm-product-affiliate');
        echo '</p></div>';
    }
    
    public function wcfmmp_missing_notice() {
        echo '<div class="error"><p>';
        echo '<strong>' . __('WCFM Product Affiliate', 'wcfm-product-affiliate') . '</strong> ';
        echo __('requiere que WCFM Marketplace esté instalado y activo.', 'wcfm-product-affiliate');
        echo '</p></div>';
    }
    
    /**
     * Get plugin options
     */
    public function get_option($key = '', $default = false) {
        $options = get_option('wcfm_affiliate_options', array());
        
        if (empty($key)) {
            return $options;
        }
        
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Update plugin option
     */
    public function update_option($key, $value) {
        $options = get_option('wcfm_affiliate_options', array());
        $options[$key] = $value;
        update_option('wcfm_affiliate_options', $options);
    }
}

/**
 * Returns the main instance of WCFM_Product_Affiliate
 */
function WCFM_Affiliate() {
    return WCFM_Product_Affiliate::instance();
}

// Initialize the plugin
WCFM_Affiliate();

