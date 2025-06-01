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
            'expert_name' => '',
            'topic'       => '',
            'is_client'   => '',
        ),
        $atts,
        'mhtp_chat'
    );

    $params = array_filter(
        array(
            'expertName' => $atts['expert_name'],
            'topic'      => $atts['topic'],
            'isClient'   => $atts['is_client'],
        )
    );

    $query = $params ? '?' . http_build_query( $params ) : '';

    $src = esc_url( 'https://embed.typebot.io/especialista-5gzhab4' . $query );

    return sprintf(
        '<iframe src="%s" width="100%%" height="600px" style="border:none;" allow="camera; microphone; clipboard-read; clipboard-write"></iframe>',
        $src
    );
}
add_shortcode( 'mhtp_chat', 'mhtp_typebot_chat_shortcode' );
