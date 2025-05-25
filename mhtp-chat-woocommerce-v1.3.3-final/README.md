# MHTP Chat Interface - Version 1.4.0

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

## Installation
> Botpress integration now uses the official API. Configure the
> `MHTP_BOTPRESS_API_URL` constant with your Botpress Cloud endpoint
> (`https://api.botpress.cloud/v1/bots/<BOT_ID>/converse/`) and set
> `MHTP_BOTPRESS_API_KEY` to your Botpress personal access token.
1. Upload the plugin files to the `/wp-content/plugins/mhtp-chat-woocommerce` directory, or install the plugin through the WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the shortcode `[mhtp_chat_interface]` or `[mhtp_chat]` to display the chat interface on any page or post
4. Ensure the constants `MHTP_BOTPRESS_API_URL` and `MHTP_BOTPRESS_API_KEY` are set in `mhtp-chat-interface.php`.

## Usage
The plugin provides two shortcodes:
- `[mhtp_chat_interface]` - The main shortcode
- `[mhtp_chat]` - An alias for backward compatibility

You can specify an expert ID directly:
- `[mhtp_chat_interface expert_id="123"]`

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

### 1.4.0
- Switched to the Botpress Cloud programmatic API with support for API keys.
- Added new constants `MHTP_BOTPRESS_API_URL` and `MHTP_BOTPRESS_API_KEY`.
- REST proxy now logs any unexpected HTTP status codes and prints the full
  Botpress response for debugging.
- Requests are sent to `/converse/<WP user ID>` so each WordPress user has a
  unique conversation context.

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
