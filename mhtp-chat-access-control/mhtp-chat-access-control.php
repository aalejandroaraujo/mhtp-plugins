<?php
/**
 * Plugin Name: MHTP Chat Access Control
 * Description: Manages access to chat sessions and consumes session credits.
 * Version: 1.0
 * Author: Manus
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Consumes a session credit for the given user ID.
 *
 * @param int $wp_user_id The ID of the WordPress user.
 * @return bool True if a session was successfully consumed, false otherwise.
 */
function mhtp_consume_session( $wp_user_id ): bool {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mhtp_user_sessions';
    $wp_user_id = intval( $wp_user_id );

    // Atomically decrement sessions_remaining if it's greater than 0
    // The $wpdb->update method returns the number of rows updated.
    $updated_rows = $wpdb->query(
        $wpdb->prepare(
            $updated_rows = $wpdb->query( $wpdb->prepare(
                "UPDATE {$table_name}
                SET sessions_remaining = sessions_remaining - 1
                WHERE user_id = %d AND sessions_remaining > 0",
            $wp_user_id
            ));

            $wp_user_id
        )
    );

    if ( $updated_rows === 1 ) {
        // Log the consumption
        error_log( "MHTP: Session consumed for user_id: " . $wp_user_id );
        return true;
    } else {
        // Log the attempt if user had no sessions or did not exist, or if an error occurred.
        // We can refine logging later if needed to distinguish these cases.
        $current_sessions = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT sessions_remaining FROM {$table_name} WHERE user_id = %d",
                $wp_user_id
            )
        );
        if (is_null($current_sessions)) {
             error_log( "MHTP: Attempted session consumption for non-existent user_id or user not in mhtp_user_sessions: " . $wp_user_id );
        } else if ($current_sessions == 0) {
             error_log( "MHTP: Attempted session consumption for user_id: " . $wp_user_id . " but sessions_remaining was 0." );
        } else {
            // This case should ideally not be reached if $updated_rows was not 1. Could indicate an unexpected DB state or error.
            error_log( "MHTP: Session consumption failed for user_id: " . $wp_user_id . ". Rows updated: " . $updated_rows . ". Current sessions: " . $current_sessions);
        }
        return false;
    }
}



/**
 * Permission callback for the consume session REST route.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return bool True if the current user can consume a session for the target user, false otherwise.
 */
function mhtp_can_consume( WP_REST_Request $request ): bool {
    $target_user_id = (int) $request->get_param( 'user_id' );
    // Check if the current user is an administrator or if the current user is the target user.
    return current_user_can( 'manage_options' ) || get_current_user_id() === $target_user_id;
}

/**
 * Handles the REST API request to consume a session.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response The REST response.
 */
function mhtp_consume_session_handler( WP_REST_Request $request ): WP_REST_Response {
    $user_id = (int) $request->get_param( 'user_id' );
    $consumed = mhtp_consume_session( $user_id );
    return new WP_REST_Response( [ 'ok' => $consumed ], 200 );
}

/**
 * Registers the REST API routes.
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'mhtp/v1', '/consume/(?P<user_id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'mhtp_consume_session_handler',
        'permission_callback' => 'mhtp_can_consume',
        'args'                => [
            'user_id' => [
                'validate_callback' => function( $param, $request, $key ) {
                    return is_numeric( $param );
                },
                'required' => true,
            ],
        ],
    ] );
} );
