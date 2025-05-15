<?php
/**
 * Unit test for MHTP session consumption.
 *
 * How to run:
 * 1. Place this file in the root of your WordPress installation.
 * 2. Ensure the mhtp-chat-access-control plugin is activated.
 * 3. Access this file directly via your browser (e.g., https://your-wp-site.com/mhtp-session-test.php)
 *    OR run it via WP-CLI: wp eval-file mhtp-session-test.php
 *
 * IMPORTANT: This script creates and deletes a user. Use on a development environment.
 */

// Load WordPress environment
if ( ! defined( 'ABSPATH' ) ) {
    // Attempt to load wp-load.php from common relative paths
    $wp_load_paths = [
        __DIR__ . '/wp-load.php',
        __DIR__ . '/../wp-load.php',
        __DIR__ . '/../../wp-load.php',
        __DIR__ . '/../../../wp-load.php',
    ];
    $wp_load_path_found = false;
    foreach ( $wp_load_paths as $path ) {
        if ( file_exists( $path ) ) {
            define( 'WP_USE_THEMES', false );
            require_once( $path );
            $wp_load_path_found = true;
            break;
        }
    }
    if ( ! $wp_load_path_found ) {
        die( "WordPress environment could not be loaded. Ensure wp-load.php is accessible. Searched paths: " . implode(', ', $wp_load_paths) );
    }
}

global $wpdb;

// Test User Credentials
$test_username = 'tester999_mhtp';
$test_password = 'password123_mhtp';
$test_email    = 'tester999_mhtp@example.com';
$user_id       = 0;

function mhtp_test_cleanup_user( $user_id_to_delete ) {
    global $wpdb;
    if ( $user_id_to_delete > 0 ) {
        // Remove from custom table
        $wpdb->delete( $wpdb->prefix . 'mhtp_user_sessions', [ 'user_id' => $user_id_to_delete ], [ '%d' ] );
        // Delete WordPress user
        require_once( ABSPATH . 'wp-admin/includes/user.php' );
        wp_delete_user( $user_id_to_delete );
        echo "<p>Cleaned up test user ID: {$user_id_to_delete}</p>";
    }
}

echo "<h1>MHTP Session Consumption Test</h1>";

// 1. Create a temporary user
$user_id = username_exists( $test_username );
if ( ! $user_id ) {
    $user_id = wp_create_user( $test_username, $test_password, $test_email );
    if ( is_wp_error( $user_id ) ) {
        die( "<p>Error creating test user: " . $user_id->get_error_message() . "</p>" );
    }
    echo "<p>Test user '{$test_username}' created with ID: {$user_id}</p>";
} else {
    echo "<p>Test user '{$test_username}' already exists with ID: {$user_id}. Reusing.</p>";
    // Ensure no previous test data interferes
    $wpdb->delete( $wpdb->prefix . 'mhtp_user_sessions', [ 'user_id' => $user_id ], [ '%d' ] );
}

// Set up cleanup function to run at the end or on error
register_shutdown_function( 'mhtp_test_cleanup_user', $user_id );

// 2. Insert a row into mhtp_user_sessions for the test user
$table_name = $wpdb->prefix . 'mhtp_user_sessions';
$inserted = $wpdb->insert(
    $table_name,
    [ 'user_id' => $user_id, 'sessions_remaining' => 1 ],
    [ '%d', '%d' ]
);

if ( ! $inserted ) {
    die( "<p>Error inserting session data for user ID {$user_id}: " . $wpdb->last_error . "</p>" );
}
echo "<p>Inserted 1 session for user ID: {$user_id}</p>";

// 3. Simulate calling the REST API endpoint (by directly calling the handler function for simplicity in this script)
// To properly test the REST API, we'd typically use wp_remote_get or similar,
// but that requires the site to be accessible via HTTP and proper authentication handling.
// For a self-contained script, calling the handler and permission callback directly is more straightforward.

// Impersonate the test user for the first call
wp_set_current_user( $user_id );
echo "<p>Current user set to test user ID: " . get_current_user_id() . "</p>";

// --- First Call --- 
echo "<h2>First call to consume session...</h2>";
$request1 = new WP_REST_Request( 'GET', "/mhtp/v1/consume/{$user_id}" );
$request1->set_url_params( [ 'user_id' => $user_id ] );

// Check permissions
if ( ! mhtp_can_consume( $request1 ) ) {
    die( "<p>Permission denied for first call. User: " . get_current_user_id() . ", Target: {$user_id}</p>" );
}
echo "<p>Permissions OK for first call.</p>";

$response1 = mhtp_consume_session_handler( $request1 );
$data1 = $response1->get_data();

echo "<p>Response 1: " . json_encode( $data1 ) . "</p>";
if ( isset( $data1['ok'] ) && $data1['ok'] === true ) {
    echo "<p style='color:green;'>SUCCESS: First call returned { ok: true } as expected.</p>";
} else {
    echo "<p style='color:red;'>FAILURE: First call did NOT return { ok: true }.</p>";
}

// Verify sessions_remaining in DB
$sessions_after_first_call = $wpdb->get_var( $wpdb->prepare( "SELECT sessions_remaining FROM {$table_name} WHERE user_id = %d", $user_id ) );
echo "<p>Sessions remaining in DB after first call: {$sessions_after_first_call}</p>";
if ( $sessions_after_first_call == 0 ) {
    echo "<p style='color:green;'>SUCCESS: sessions_remaining is 0 in DB after first call.</p>";
} else {
    echo "<p style='color:red;'>FAILURE: sessions_remaining is NOT 0 in DB after first call. Value: {$sessions_after_first_call}</p>";
}


// --- Second Call --- 
echo "<h2>Second call to consume session...</h2>";
// User is still the test user
$request2 = new WP_REST_Request( 'GET', "/mhtp/v1/consume/{$user_id}" );
$request2->set_url_params( [ 'user_id' => $user_id ] );

// Check permissions
if ( ! mhtp_can_consume( $request2 ) ) {
    // This shouldn't happen if the first one passed with the same user
    die( "<p>Permission denied for second call. User: " . get_current_user_id() . ", Target: {$user_id}</p>" );
}
echo "<p>Permissions OK for second call.</p>";

$response2 = mhtp_consume_session_handler( $request2 );
$data2 = $response2->get_data();

echo "<p>Response 2: " . json_encode( $data2 ) . "</p>";
if ( isset( $data2['ok'] ) && $data2['ok'] === false ) {
    echo "<p style='color:green;'>SUCCESS: Second call returned { ok: false } as expected.</p>";
} else {
    echo "<p style='color:red;'>FAILURE: Second call did NOT return { ok: false }.</p>";
}

// Verify sessions_remaining in DB (should still be 0)
$sessions_after_second_call = $wpdb->get_var( $wpdb->prepare( "SELECT sessions_remaining FROM {$table_name} WHERE user_id = %d", $user_id ) );
echo "<p>Sessions remaining in DB after second call: {$sessions_after_second_call}</p>";
if ( $sessions_after_second_call == 0 ) {
    echo "<p style='color:green;'>SUCCESS: sessions_remaining is still 0 in DB after second call.</p>";
} else {
    echo "<p style='color:red;'>FAILURE: sessions_remaining is NOT 0 in DB after second call. Value: {$sessions_after_second_call}</p>";
}

// --- Test admin calling the endpoint ---
echo "<h2>Admin call to consume session for another user (who has no sessions)...</h2>";
// Create a dummy admin user (or use an existing one if ID 1 is admin)
$admin_user_id = 1; // Assuming user ID 1 is an admin. For robust test, create one.
wp_set_current_user( $admin_user_id );
echo "<p>Current user set to admin ID: " . get_current_user_id() . "</p>";

$target_user_for_admin_test_id = $user_id; // Test against the same user who now has 0 sessions

$request_admin = new WP_REST_Request( 'GET', "/mhtp/v1/consume/{$target_user_for_admin_test_id}" );
$request_admin->set_url_params( [ 'user_id' => $target_user_for_admin_test_id ] );

if ( ! mhtp_can_consume( $request_admin ) ) {
    die( "<p>Permission denied for admin call. Admin User: " . get_current_user_id() . ", Target: {$target_user_for_admin_test_id}</p>" );
}
echo "<p>Permissions OK for admin call.</p>";

$response_admin = mhtp_consume_session_handler( $request_admin );
$data_admin = $response_admin->get_data();

echo "<p>Response for admin call: " . json_encode( $data_admin ) . "</p>";
if ( isset( $data_admin['ok'] ) && $data_admin['ok'] === false ) {
    echo "<p style='color:green;'>SUCCESS: Admin call for user with 0 sessions returned { ok: false } as expected.</p>";
} else {
    echo "<p style='color:red;'>FAILURE: Admin call for user with 0 sessions did NOT return { ok: false }.</p>";
}

// Restore original user
wp_set_current_user( 0 );

echo "<p>Test completed. User will be cleaned up.</p>";

// Cleanup is handled by register_shutdown_function

?>
