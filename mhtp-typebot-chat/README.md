# MHTP Typebot Chat

This plugin provides a minimal shortcode wrapper around the
[Typebot](https://typebot.io) WordPress plugin. It replaces the previous
Botpress-based chat interface.

## Shortcode

Use `[mhtp_chat]` to embed the Typebot conversation. Optional parameters can
be passed and will be forwarded as URL parameters to Typebot:

```
[mhtp_chat expert_name="Lucia" topic="Anxiety" is_client="1"]
```

These values become available to your Typebot flow via the variables
`expertName`, `topic` and `isClient`.

The actual Typebot is embedded using:

```
[typebot typebot="especialista-5gzhab4" width="100%" height="600px"]
```

Any provided parameters are appended to the `url_params` attribute so they are
available inside Typebot.
