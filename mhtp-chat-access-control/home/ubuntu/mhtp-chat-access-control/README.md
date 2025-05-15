# MHTP Chat Access Control Plugin

This plugin provides functionality to manage and consume user chat session credits for the Mental Health Triage Platform.

## Features

*   **Session Consumption**: A PHP function `mhtp_consume_session( $wp_user_id )` that decrements a user's available chat sessions.
*   **REST API Endpoint**: A `GET` endpoint `wp-json/mhtp/v1/consume/{user_id}` to trigger session consumption.
*   **Logging**: Session consumption attempts (both successful and failed) are logged using `error_log()`.
*   **Unit Test**: A PHP script (`mhtp-session-test.php`) is included to test the session consumption logic.

## Database Query for Session Consumption

The core of the session consumption logic lies in the `mhtp_consume_session` function. To ensure that a session credit is correctly and safely decremented, especially in concurrent scenarios, an atomic database operation is used.

The specific SQL query executed via `$wpdb->query()` is:

```sql
UPDATE {$wpdb->prefix}mhtp_user_sessions 
SET sessions_remaining = sessions_remaining - 1 
WHERE user_id = %d AND sessions_remaining > 0;
```

**Explanation of Atomicity:**

1.  `UPDATE ... SET sessions_remaining = sessions_remaining - 1`: This part attempts to decrement the `sessions_remaining` count by 1.
2.  `WHERE user_id = %d AND sessions_remaining > 0`: This is the crucial part for atomicity and correctness.
    *   It ensures that the update only happens for the specified `user_id`.
    *   More importantly, `sessions_remaining > 0` ensures that the decrement only occurs if the user has sessions available *at the moment the database processes the update*. If `sessions_remaining` is already 0 (or less, though the column is unsigned), the `WHERE` clause will not match, and no rows will be updated.

This single `UPDATE` statement is an atomic operation at the database level. This means the database guarantees that the check (`sessions_remaining > 0`) and the update (`sessions_remaining = sessions_remaining - 1`) happen as a single, indivisible operation. This prevents race conditions where, for example, two simultaneous requests might both read `sessions_remaining = 1`, both decide to decrement, and incorrectly result in `sessions_remaining = -1` or two sessions being consumed when only one was available.

The `$wpdb->query()` method in WordPress, when used for `UPDATE` statements like this, returns the number of rows affected. The function `mhtp_consume_session` checks if exactly one row was updated. If so, it means a session was successfully consumed. Otherwise (0 rows updated), it means the user either had no sessions remaining or the user ID was not found, and the function returns `false`.

## Unit Test (`mhtp-session-test.php`)

This script provides a way to test the functionality:
1.  It creates a temporary WordPress user.
2.  It inserts a record into the `mhtp_user_sessions` table for this user, giving them 1 session.
3.  It then simulates calls to the session consumption logic:
    *   The first call should succeed (return `ok: true`) and decrement `sessions_remaining` to 0.
    *   The second call should fail (return `ok: false`) as no sessions are left.
4.  It also tests the permission callback by simulating a call as an administrator for the test user.
5.  The script cleans up by deleting the test user and their session data.

**To run the test:**
*   Place `mhtp-session-test.php` in the root directory of your WordPress installation.
*   Ensure the `mhtp-chat-access-control` plugin is activated.
*   Access the script via your browser (e.g., `https://your-site.com/mhtp-session-test.php`) or run `wp eval-file mhtp-session-test.php` via WP-CLI.

**Note**: The test script is designed for development environments as it creates and deletes user data.
