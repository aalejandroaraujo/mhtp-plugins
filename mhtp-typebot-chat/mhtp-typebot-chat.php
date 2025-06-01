<?php
/**
 * Plugin Name: MHTP Typebot Chat
 * Description: Simple chat embedding using Typebot.
 * Version: 0.1.0
 * Author: MHTP Team
 * Text Domain: mhtp-typebot-chat
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode to embed a Typebot conversation.
 *
 * Usage: [mhtp_chat expert_name="" topic="" is_client=""]
 */
function mhtp_typebot_chat_shortcode( $atts ) {
}
add_shortcode( 'mhtp_chat', 'mhtp_typebot_chat_shortcode' );
