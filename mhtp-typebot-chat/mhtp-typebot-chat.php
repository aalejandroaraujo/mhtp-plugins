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
    $atts = shortcode_atts(
        array(
            'typebot' => 'especialista-5gzhab4',
            'width'   => '100%',
            'height'  => '600px',
        ),
        $atts,
        'mhtp_chat'
    );

    $params = array();
    foreach ( $atts as $key => $value ) {
        if ( in_array( $key, array( 'typebot', 'width', 'height' ), true ) ) {
            continue;
        }
        if ( '' === $value ) {
            continue;
        }
        $camel = preg_replace_callback( '/_([a-z])/', function ( $m ) { return strtoupper( $m[1] ); }, $key );
        $params[ $camel ] = $value;
    }

    $query   = http_build_query( $params );
    $shortcode = '[typebot typebot="' . esc_attr( $atts['typebot'] ) . '" width="' . esc_attr( $atts['width'] ) . '" height="' . esc_attr( $atts['height'] ) . '"';
    if ( $query ) {
        $shortcode .= ' url_params="' . esc_attr( $query ) . '"';
    }
    $shortcode .= ']';

    return do_shortcode( $shortcode );
}
add_shortcode( 'mhtp_chat', 'mhtp_typebot_chat_shortcode' );
add_shortcode( 'mhtp_chat_interface', 'mhtp_typebot_chat_shortcode' );