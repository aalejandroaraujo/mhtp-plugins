# MHTP Typebot Chat

This plugin embeds a [Typebot](https://typebot.io) conversation using a
simple shortcode. It replaces the previous Botpress-based chat interface
and uses a plain `<iframe>` so the official Typebot plugin is not
required.

## Shortcode

Use `[mhtp_chat]` to embed the Typebot conversation. Optional parameters
are forwarded as URL parameters to Typebot:

```
[mhtp_chat expert_name="Lucia" topic="Anxiety" is_client="1"]
```

These values become available to your Typebot flow via the variables
`expertName`, `topic` and `isClient`.

The shortcode renders an `<iframe>` pointing to your Typebot with any
parameters appended to the query string. If the legacy plugin
`mhtp-chat-woocommerce-v1.3.3-final` is active, deactivate it to avoid
shortcode conflicts.
