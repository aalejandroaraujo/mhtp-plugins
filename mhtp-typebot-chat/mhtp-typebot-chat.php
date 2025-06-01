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
    $atts = shortcode_atts( array(
        'expert_name' => '',
        'topic'       => '',
        'is_client'   => '',
    ), $atts, 'mhtp_chat' );

    $params = array_filter( array(
        'expertName' => $atts['expert_name'],
        'topic'      => $atts['topic'],
        'isClient'   => $atts['is_client'],
    ) );

    $query = http_build_query( $params );

    $shortcode = '[typebot typebot="especialista-5gzhab4" width="100%" height="600px"';
    if ( $query ) {
        $shortcode .= ' url_params="' . esc_attr( $query ) . '"';
    }
    $shortcode .= ']';

    return do_shortcode( $shortcode );
}
add_shortcode( 'mhtp_chat', 'mhtp_typebot_chat_shortcode' );
