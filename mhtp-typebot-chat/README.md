# MHTP Typebot Chat
```
[mhtp_chat expert_name="Lucia" topic="Anxiety" is_client="1"]
```

These values become available to your Typebot flow via the variables
`expertName`, `topic` and `isClient`.

The shortcode renders an `<iframe>` pointing to your Typebot with any
parameters appended to the query string. If the legacy plugin
`mhtp-chat-woocommerce-v1.3.3-final` is active, deactivate it to avoid
shortcode conflicts.
=======
The actual Typebot is embedded using:

```
[typebot typebot="especialista-5gzhab4" width="100%" height="600px"]
```

Any provided parameters are appended to the `url_params` attribute so they are
available inside Typebot.
