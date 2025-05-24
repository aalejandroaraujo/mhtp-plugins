<?php
/**
 * Plugin Name: MHTP Anonymous Checkout
 * Description: Makes WooCommerce checkout more anonymous for mental health services
 * Version: 1.0
 * Author: Manus
 */

// Remove unnecessary checkout fields
function mhtp_remove_checkout_fields($fields) {
    // Make most fields optional
    $fields['billing']['billing_first_name']['required'] = false;
    $fields['billing']['billing_last_name']['required'] = false;
    $fields['billing']['billing_company']['required'] = false;
    $fields['billing']['billing_address_1']['required'] = false;
    $fields['billing']['billing_address_2']['required'] = false;
    $fields['billing']['billing_city']['required'] = false;
    $fields['billing']['billing_postcode']['required'] = false;
    $fields['billing']['billing_country']['required'] = false;
    $fields['billing']['billing_state']['required'] = false;
    $fields['billing']['billing_phone']['required'] = false;
    
    // Only keep email as required
    // $fields['billing']['billing_email']['required'] = true;
    
    // Change field labels to be more privacy-focused
    $fields['billing']['billing_first_name']['label'] = 'Name (or pseudonym)';
    $fields['billing']['billing_last_name']['label'] = 'Last name (optional)';
    
    // Add placeholder text explaining privacy
    $fields['billing']['billing_first_name']['placeholder'] = 'You can use a pseudonym';
    $fields['billing']['billing_last_name']['placeholder'] = 'Optional';
    $fields['billing']['billing_address_1']['placeholder'] = 'Optional for digital products';
    $fields['billing']['billing_city']['placeholder'] = 'Optional for digital products';
    $fields['billing']['billing_postcode']['placeholder'] = 'Optional for digital products';
    
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'mhtp_remove_checkout_fields');

// Add notice about anonymous checkout
function mhtp_anonymous_checkout_notice() {
    if (is_checkout()) {
        wc_print_notice(
            'For your privacy: Most fields are optional. You can use a pseudonym instead of your real name. Only your email is required to receive session information.',
            'notice'
        );
    }
}
add_action('woocommerce_before_checkout_form', 'mhtp_anonymous_checkout_notice');

// Hide shipping fields completely for virtual products
function mhtp_hide_shipping_fields_for_virtual($fields) {
    $only_virtual = true;
    
    // Check if all products in cart are virtual
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (!$cart_item['data']->is_virtual()) {
            $only_virtual = false;
            break;
        }
    }
    
    // If only virtual products, remove shipping fields
    if ($only_virtual) {
        unset($fields['shipping']);
    }
    
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'mhtp_hide_shipping_fields_for_virtual', 20);

// Add privacy message to checkout
function mhtp_privacy_message() {
    echo '<div class="mhtp-privacy-message" style="margin-bottom: 20px; padding: 15px; background-color: #f8f8f8; border-left: 3px solid #2271b1;">';
    echo '<h3 style="margin-top: 0;">Your Privacy Matters</h3>';
    echo '<p>We respect your privacy and understand the sensitive nature of mental health services. Your information is kept confidential and will never be shared with third parties.</p>';
    echo '<p>You can use a pseudonym instead of your real name if you prefer. Only your email is required to receive your session information.</p>';
    echo '</div>';
}
add_action('woocommerce_checkout_before_customer_details', 'mhtp_privacy_message');
