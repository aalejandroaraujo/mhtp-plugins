<?php
/**
 * Plugin Name: MHTP Chat Transcript
 * Description: Stores chat transcripts via REST API.
 * Version: 1.0.0
 * Author: Araujo Innovations
 * Text Domain: mhtp-chat-transcript
 */

// Abort if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Create database table on activation.
 */
function mhtp_chat_transcript_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mhtp_chat_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        wp_user_id bigint(20) unsigned NOT NULL,
        convo_id varchar(255) NOT NULL,
        summary text NOT NULL,
        transcript longtext NOT NULL,
        sentiment varchar(20) NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'mhtp_chat_transcript_activate' );

/**
 * Permission callback for the REST route.
 */
function mhtp_chatlog_permission() {
    if ( is_user_logged_in() ) {
        return true;
    }

    if ( isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ) {
        $user = wp_authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
        if ( ! is_wp_error( $user ) ) {
            wp_set_current_user( $user->ID );
            return true;
        }
    }

    return false;
}

/**
 * Handle POST /chatlog/v1/save
 */
function mhtp_chatlog_save( WP_REST_Request $request ) {
    $params = $request->get_json_params();
    if ( empty( $params['consent'] ) ) {
        return new WP_REST_Response( null, 204 );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'mhtp_chat_logs';

    $wpdb->insert(
        $table,
        array(
            'wp_user_id' => get_current_user_id(),
            'convo_id'   => isset( $params['convo_id'] ) ? sanitize_text_field( $params['convo_id'] ) : '',
            'summary'    => isset( $params['summary'] ) ? wp_kses_post( $params['summary'] ) : '',
            'transcript' => isset( $params['transcript'] ) ? wp_kses_post( $params['transcript'] ) : '',
            'sentiment'  => isset( $params['sentiment'] ) ? sanitize_text_field( $params['sentiment'] ) : '',
        ),
        array( '%d', '%s', '%s', '%s', '%s' )
    );

    return array( 'stored' => true );
}

/**
 * Register REST route.
 */
function mhtp_chat_transcript_rest() {
    register_rest_route(
        'chatlog/v1',
        '/save',
        array(
            'methods'             => 'POST',
            'callback'            => 'mhtp_chatlog_save',
            'permission_callback' => 'mhtp_chatlog_permission',
        )
    );
}
add_action( 'rest_api_init', 'mhtp_chat_transcript_rest' );
