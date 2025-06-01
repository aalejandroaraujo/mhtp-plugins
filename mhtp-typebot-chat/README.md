# MHTP Typebot Chat

This plugin provides a minimal shortcode wrapper around the
[Typebot](https://typebot.io) WordPress plugin. It replaces the previous
Botpress-based chat interface.

## Shortcode

Use `[mhtp_chat]` (or its alias `[mhtp_chat_interface]`) to embed the
Typebot conversation. Optional parameters can be passed and will be forwarded
as URL parameters to Typebot:

```
[mhtp_chat expert_name="Lucia" topic="Anxiety" is_client="1"]
```

These values become available to your Typebot flow via the variables
`expertName`, `topic` and `isClient`.

The actual Typebot is embedded using the `[typebot]` shortcode. You can
override the `typebot`, `width`, and `height` attributes if needed:

```
[mhtp_chat typebot="otro-bot" width="80%" height="500px" expert_name="Lucia"]
```

Parameters other than `typebot`, `width` and `height` are passed to Typebot as
URL variables.

If you still have the legacy `mhtp-chat-woocommerce-v1.3.3-final` plugin
active, deactivate it to avoid shortcode conflicts.
