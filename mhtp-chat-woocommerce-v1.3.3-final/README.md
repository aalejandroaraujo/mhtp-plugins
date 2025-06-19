# MHTP Chat Interface - Version 3.1.0

This plugin provides a chat interface powered by OpenAI and includes
built-in support for embedding a [Typebot](https://typebot.io) conversation.
The separate `mhtp-typebot-chat` plugin is no longer required.

The embed uses `https://typebot.io/` by default. You can adjust the
base URL by hooking into the `mhtp_typebot_embed_base` filter if your
Typebot instance requires a different domain.

When embedding via Typebot the conversation transcript can't be exported,
so the **Descargar conversación** button is hidden until a storage
mechanism is implemented.

## Description
MHTP Chat Interface is a WordPress plugin that provides a chat interface for experts with WooCommerce integration. This plugin allows users to chat with experts who are set up as WooCommerce products.

**Note:** This version embeds a [Typebot](https://typebot.io) conversation instead of the previous custom chat UI while keeping the same page layout. When using Typebot the conversation transcript cannot be exported, so the "Descargar conversación" button is disabled.

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
1. Upload the plugin files to `/wp-content/plugins/mhtp-chat-woocommerce` or install through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' menu.
3. Use the shortcode `[mhtp_chat_interface]` (or `[mhtp_chat]`) on any page.


## Usage
The plugin provides two shortcodes:
- `[mhtp_chat_interface]` - The main shortcode
- `[mhtp_chat]` - An alias for backward compatibility

You can specify an expert ID directly:
- `[mhtp_chat_interface expert_id="123"]`

### AJAX Handlers
The plugin registers the actions `wp_ajax_mhtp_start_chat_session` and
`wp_ajax_nopriv_mhtp_start_chat_session` for starting chat sessions. If these
hooks are missing, every AJAX request will return an error and the front end
will display "Failed to start conversation".

The front-end script sends messages via `fetch` to the localized REST
endpoint. Ensure `mhtpChatConfig.rest_url` and `mhtpChatConfig.nonce` are
printed by `wp_localize_script`.

> **Note** Some security plugins or hosting providers may block requests to custom REST endpoints like `/mhtp-chat/message`. If message sending fails, check your host or plugin settings.

## Session Management
This plugin now properly handles session decrementation when users start a chat:

1. If the MHTP Test Sessions plugin is active, it first tries to use a test session
2. If no test sessions are available, it falls back to using WooCommerce sessions
3. If no sessions of either type are available, the user receives an error message

## Changelog

### 3.0.0
- Migrated the chat backend to use OpenAI instead of Botpress.


### 2.0.1
- Register AJAX handlers for `mhtp_start_chat_session` for logged in and guest
  users. The lack of these hooks previously caused chat initialization failures.
### 2.0.2
- Various improvements to the REST handlers and security checks.
### 2.0.0
- Initial chat backend implementation.

### 1.4.0
- Added programmatic API support and improved logging.

### 1.3.5
- Fixed 403 errors when sending messages by replacing the REST route permission
  callback with an anonymous function. A debug log entry now confirms the
  callback executes.

### 1.3.6
- Ensured the REST proxy always returns a valid JSON response by validating the
  assistant reply. Errors now produce a structured `{ "error": ... }` payload so
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

-- Exposed REST endpoint URL and nonce via `mhtpChatConfig` in the PHP registration routine.
-- Secure route with WP nonce permission callback.
-- `sendMessage()` now POSTs to the localized REST endpoint with fetch() and handles JSON reply.

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
