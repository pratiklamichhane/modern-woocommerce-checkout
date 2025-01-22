<?php
/**
 * Plugin Name: Modern WooCommerce Checkout
 * Description: Modern and clean checkout page for WooCommerce
 * Version: 1.0.0
 * Author: Pratik Lamichhane
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

defined('ABSPATH') || exit;

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

class Modern_WooCommerce_Checkout {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_filter('woocommerce_locate_template', array($this, 'override_checkout_template'), 999, 3);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('woocommerce_checkout_fields', array($this, 'modify_checkout_fields'));
        add_action('init', array($this, 'setup_plugin'));
    }

    public function setup_plugin() {
        $this->create_plugin_files();
    }

    public function create_plugin_files() {
        $dirs = array(
            'templates',
            'assets/css',
            'assets/js'
        );

        foreach ($dirs as $dir) {
            wp_mkdir_p(plugin_dir_path(__FILE__) . $dir);
        }

        // Create template file
        $template_file = plugin_dir_path(__FILE__) . 'templates/checkout.php';
        if (!file_exists($template_file)) {
            file_put_contents($template_file, $this->get_checkout_template());
        }

        // Create CSS file
        $css_file = plugin_dir_path(__FILE__) . 'assets/css/checkout-style.css';
        if (!file_exists($css_file)) {
            file_put_contents($css_file, $this->get_checkout_styles());
        }

        // Create JS file
        $js_file = plugin_dir_path(__FILE__) . 'assets/js/checkout-script.js';
        if (!file_exists($js_file)) {
            file_put_contents($js_file, $this->get_checkout_scripts());
        }
    }

    public function enqueue_scripts() {
        if (!is_checkout()) {
            return;
        }

        wp_enqueue_style(
            'modern-checkout-style',
            plugins_url('assets/css/checkout-style.css', __FILE__),
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/checkout-style.css')
        );

        wp_enqueue_script(
            'modern-checkout-script',
            plugins_url('assets/js/checkout-script.js', __FILE__),
            array('jquery', 'wc-checkout'),
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/checkout-script.js'),
            true
        );
    }

    public function override_checkout_template($template, $template_name, $template_path) {
        if ($template_name !== 'checkout/form-checkout.php') {
            return $template;
        }

        $plugin_template = plugin_dir_path(__FILE__) . 'templates/checkout.php';
        
        return file_exists($plugin_template) ? $plugin_template : $template;
    }

    public function modify_checkout_fields($fields) {
        // Customize billing fields
        if (isset($fields['billing'])) {
            $fields['billing']['billing_first_name']['class'] = array('form-row-first');
            $fields['billing']['billing_first_name']['placeholder'] = 'First name';
            
            $fields['billing']['billing_last_name']['class'] = array('form-row-last');
            $fields['billing']['billing_last_name']['placeholder'] = 'Last name';
            
            $fields['billing']['billing_company']['placeholder'] = 'Company name (optional)';
            $fields['billing']['billing_address_1']['placeholder'] = 'Street address';
            $fields['billing']['billing_address_2']['placeholder'] = 'Apartment, suite, unit, etc. (optional)';
            $fields['billing']['billing_city']['placeholder'] = 'Town / City';
            $fields['billing']['billing_postcode']['placeholder'] = 'Postcode / ZIP';
            $fields['billing']['billing_phone']['placeholder'] = 'Phone';
        }

        return $fields;
    }

    private function get_checkout_template() {
        return '<?php
defined("ABSPATH") || exit;

// Hook before checkout form
do_action("woocommerce_before_checkout_form", $checkout);

// Check cart has contents
if (!WC()->cart->is_empty()) : ?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

    <div class="modern-checkout-container">
        <div class="checkout-main">
            <!-- Checkout Header -->
            <div class="checkout-header">
                <h1>Information</h1>
                <?php if (WC()->cart && !WC()->cart->is_empty()) : ?>
                    <div class="user-info">
                        <?php 
                        $current_user = wp_get_current_user();
                        if ($current_user->exists()) {
                            echo "Welcome back, " . esc_html($current_user->user_email);
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Checkout Fields -->
            <div class="checkout-fields">
                <h2>Shipping address</h2>
                <?php do_action("woocommerce_checkout_billing"); ?>
                <?php do_action("woocommerce_checkout_shipping"); ?>
            </div>
        </div>

        <div class="checkout-sidebar">
            <!-- Order Summary -->
            <div class="order-summary">
                <h2>Order Summary</h2>
                <?php do_action("woocommerce_checkout_before_order_review"); ?>
                <div id="order_review" class="woocommerce-checkout-review-order">
                    <?php do_action("woocommerce_checkout_order_review"); ?>
                </div>
            </div>

            <!-- Promo Code -->
            <?php if (wc_coupons_enabled()) : ?>
            <div class="promo-code">
                <div class="coupon">
                    <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="Enter Promo Code" />
                    <button type="button" class="button" name="apply_coupon" value="Apply">Apply</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</form>

<?php else : ?>
    <div class="woocommerce-error">
        <?php esc_html_e("Your cart is empty.", "woocommerce"); ?>
        <a href="<?php echo esc_url(get_permalink(wc_get_page_id("shop"))); ?>" class="button">
            <?php esc_html_e("Return to shop", "woocommerce"); ?>
        </a>
    </div>
<?php endif;

do_action("woocommerce_after_checkout_form", $checkout); ?>';
    }

    private function get_checkout_styles() {
        return '
.modern-checkout-container {
    display: flex;
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.checkout-main {
    flex: 1.5;
}

.checkout-sidebar {
    flex: 1;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
}

.checkout-header {
    margin-bottom: 2rem;
}

.checkout-header h1 {
    font-size: 24px;
    margin-bottom: 0.5rem;
}

.user-info {
    color: #666;
    font-size: 14px;
}

.checkout-fields h2 {
    font-size: 18px;
    margin-bottom: 1.5rem;
}

/* Form Fields */
.form-row {
    margin-bottom: 1rem;
}

.form-row label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-row input,
.form-row select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

/* Promo Code Section */
.promo-code {
    margin-top: 2rem;
}

.coupon {
    display: flex;
    gap: 0.5rem;
}

.coupon input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.coupon button {
    padding: 0.75rem 1.5rem;
    background: #666;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    white-space: nowrap;
}

.coupon button:hover {
    background: #555;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modern-checkout-container {
        flex-direction: column;
        padding: 1rem;
    }

    .checkout-sidebar {
        margin-top: 2rem;
    }
}

/* Error Handling */
.woocommerce-error {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

/* Success Messages */
.woocommerce-message {
    background: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

/* Required Field Indicators */
.required {
    color: #dc3545;
}';
    }

    private function get_checkout_scripts() {
        return '
jQuery(function($) {
    // Handle coupon code application
    $(".coupon button").on("click", function(e) {
        e.preventDefault();
        var coupon_code = $("#coupon_code").val();
        
        if (coupon_code) {
            $(".woocommerce-error, .woocommerce-message").remove();
            
            $.ajax({
                type: "POST",
                url: wc_checkout_params.ajax_url,
                data: {
                    action: "woocommerce_apply_coupon",
                    security: wc_checkout_params.apply_coupon_nonce,
                    coupon_code: coupon_code
                },
                success: function(response) {
                    $(".woocommerce-checkout").before(response);
                    $(document.body).trigger("update_checkout");
                    $("#coupon_code").val("");
                }
            });
        }
    });
});';
    }
}

// Initialize the plugin
function initialize_modern_checkout() {
    return Modern_WooCommerce_Checkout::get_instance();
}

add_action('plugins_loaded', 'initialize_modern_checkout');

// Activation hook
register_activation_hook(__FILE__, function() {
    $plugin = Modern_WooCommerce_Checkout::get_instance();
    $plugin->setup_plugin();
    
    // Flush rewrite rules
    flush_rewrite_rules();
});