# MHTP Chat Interface - Version 2.0.1

## Description
MHTP Chat Interface is a WordPress plugin that provides a chat interface for experts with WooCommerce integration. This plugin allows users to chat with experts who are set up as WooCommerce products.

## New in Version 1.3.0
- **Unified Session Management**: Now properly decrements sessions when users start a chat, working with both test sessions (from MHTP Test Sessions plugin) and paid sessions (from WooCommerce)
- **Improved Session Tracking**: Added logging for session usage to help with reporting and analytics
- **Better Integration**: Seamless integration with the MHTP Test Sessions plugin
- **Fixed Expert List Rendering**: Restored the original plugin structure and data passing mechanism to ensure experts display properly

## Requirements
- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- MHTP Test Sessions plugin (optional, for test session management)

## Migrating from Legacy API
Previous versions of this plugin used the `/converse` endpoint from Botpress v12. That endpoint no longer works on Botpress Cloud and will return 404 errors. Version 2.0.0 now uses the Chat API. Ensure the Chat Integration is enabled on your bot and update your API key before upgrading.

## Installation
1. In Botpress Cloud, enable the **Chat Integration** for your bot and note the API key.
2. In that same Chat Integration screen set the **Webhook URL** to `https://YOURDOMAIN.com/wp-json/mhtp-chat/v1/webhook` (replace `YOURDOMAIN.com` with your domain).
3. *(Optional)* Define `MHTP_BOTPRESS_WEBHOOK_SECRET` in `wp-config.php` and configure Botpress to send `Authorization: Bearer <secret>` to this webhook.
4. Define the constant `MHTP_BOTPRESS_API_KEY` in your `wp-config.php` file with the key from step&nbsp;1.
5. Upload the plugin files to `/wp-content/plugins/mhtp-chat-woocommerce` or install through the WordPress plugins screen.
6. Activate the plugin through the 'Plugins' menu.
7. Use the shortcode `[mhtp_chat_interface]` (or `[mhtp_chat]`) on any page.

The plugin communicates with Botpress using the Chat API at `https://chat.botpress.cloud/v1`. A user and conversation are created automatically when a chat session starts.


## Usage
The plugin provides two shortcodes:
- `[mhtp_chat_interface]` - The main shortcode
- `[mhtp_chat]` - An alias for backward compatibility

You can specify an expert ID directly:
- `[mhtp_chat_interface expert_id="123"]`

### AJAX Handlers
The plugin registers the actions `wp_ajax_mhtp_start_chat_session` and
`wp_ajax_nopriv_mhtp_start_chat_session`. These handlers create the Botpress
conversation when the chat UI loads. If they are missing, every AJAX request
will return an empty response and the front end will display "Failed to prepare
chat user".

The front-end script sends messages via `fetch` to the localized REST
endpoint. Ensure `mhtpChatConfig.rest_url` and `mhtpChatConfig.nonce` are
printed by `wp_localize_script`.

> **Note** Some security plugins or hosting providers may block requests to custom REST endpoints like `/mhtp-chat/v1/message`. If message sending fails, check your host or plugin settings.

## Session Management
This plugin now properly handles session decrementation when users start a chat:

1. If the MHTP Test Sessions plugin is active, it first tries to use a test session
2. If no test sessions are available, it falls back to using WooCommerce sessions
3. If no sessions of either type are available, the user receives an error message

## Changelog


### 2.0.1
- Register AJAX handlers for `mhtp_start_chat_session` for logged in and guest
  users. The lack of these hooks previously caused chat initialization failures.
### 2.0.0
- Migrated to Botpress **Chat API** at `https://chat.botpress.cloud/v1`.
- Users and conversations are created automatically when a session begins.
- New optional webhook endpoint `/mhtp-chat/v1/webhook` for asynchronous events.
- API key is now read from `MHTP_BOTPRESS_API_KEY` defined in `wp-config.php`.

### 1.4.0
- Switched to the Botpress Cloud programmatic API with support for API keys (legacy approach).
- Added new constants `MHTP_BOTPRESS_API_URL` and `MHTP_BOTPRESS_API_KEY`.
- REST proxy now logs unexpected HTTP status codes with full Botpress response.
- Requests were sent to `/converse/<WP user ID>` for conversation context.

### 1.3.5
- Fixed 403 errors when sending messages by replacing the REST route permission
  callback with an anonymous function. A debug log entry now confirms the
  callback executes.

### 1.3.6
- Ensured the REST proxy always returns a valid JSON response by validating the
  Botpress reply. Errors now produce a structured `{ "error": ... }` payload so
  the frontend never receives an empty body.

### 1.3.4
- Added nonce header to REST fetch.

### 1.3.0
- Added unified session management for both test and paid sessions
- Integrated with MHTP Test Sessions plugin
- Added session usage logging for better tracking
- Fixed method name mismatch in session decrementation
- Fixed expert list rendering by restoring original data passing mechanism
- Restored original plugin structure for better compatibility

### 1.3.3
- Botpress URL now read from `MHTP_BOTPRESS_API_URL` constant pointing to your Botpress Cloud API.
- Expose REST endpoint URL and nonce via `mhtpChatConfig` in the PHP registration routine.
- Secure route with WP nonce permission callback.
- sendMessage() now POSTs to the localized REST endpoint with fetch() and handles JSON reply.

### 1.2.0
- Added PDF download functionality
- Enhanced chat interface styling

### 1.1.0
- Added expert selection screen
- Improved mobile responsiveness

### 1.0.0
- Initial release

## Credits
Developed by Araujo Innovations

## Usage example
```js
fetch(mhtpChatConfig.rest_url, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': mhtpChatConfig.nonce
  },
  body: JSON.stringify({ message: 'Hola desde staging' })
})
  .then(r => r.json())
  .then(d => console.log('Bot response:', d.text));
```

## Testing the Webhook
1. Confirm your Botpress Chat integration's **Webhook URL** is set to `https://YOURDOMAIN.com/wp-json/mhtp-chat/v1/webhook` and the plugin is active.
2. Open a page containing the `[mhtp_chat_interface]` shortcode and start a chat session.
3. Send a message to the bot and wait for the reply.
4. The webhook stores the most recent bot message in the user meta field `mhtp_last_bot_reply`.
5. You can verify this value using the WordPress admin or `get_user_meta( get_current_user_id(), 'mhtp_last_bot_reply', true )`.

## Security & Rate Limits
Keep your Botpress API key secret. Define `MHTP_BOTPRESS_API_KEY` in `wp-config.php` outside your web root. If you set `MHTP_BOTPRESS_WEBHOOK_SECRET`, Botpress must include `Authorization: Bearer <secret>` when calling the webhook. The Chat API enforces rate limits, so avoid unnecessary requests and handle errors gracefully.
