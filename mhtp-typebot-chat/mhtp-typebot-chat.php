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
    $defaults = array(
        'typebot'     => 'especialista-5gzhab4',
        'width'       => '100%',
        'height'      => '600px',
        'expert_name' => '',
        'topic'       => '',
        'is_client'   => '',
    );

    $atts = shortcode_atts( $defaults, $atts, 'mhtp_chat' );

    $params = array_filter( $atts );
    unset( $params['typebot'], $params['width'], $params['height'] );

    $query = http_build_query( $params );

    $shortcode = sprintf(
        '[typebot typebot="%s" width="%s" height="%s"',
        esc_attr( $atts['typebot'] ),
        esc_attr( $atts['width'] ),
        esc_attr( $atts['height'] )
    );

    if ( $query ) {
        $shortcode .= ' url_params="' . esc_attr( $query ) . '"';
    }

    $shortcode .= ']';

    return do_shortcode( $shortcode );
}

add_shortcode( 'mhtp_chat', 'mhtp_typebot_chat_shortcode' );
add_shortcode( 'mhtp_chat_interface', 'mhtp_typebot_chat_shortcode' );