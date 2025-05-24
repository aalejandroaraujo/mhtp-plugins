# MHTP Chat Transcript

Registers a REST endpoint to save chat transcripts when the user has given consent.

## Endpoint

`POST /wp-json/chatlog/v1/save`

The request must be authenticated using Basic Auth or a logged-in session cookie. If the `consent` field in the JSON payload is `true`, the transcript data is stored in the `wp_mhtp_chat_logs` table and the response is `{"stored": true}`. When `consent` is `false`, the endpoint returns `204 No Content` and nothing is saved.

### Sample cURL

```bash
curl -X POST -u username:password \
  -H "Content-Type: application/json" \
  -d '{
    "consent": true,
    "convo_id": "abc123",
    "summary": "Chat summary",
    "transcript": "Full transcript text...",
    "sentiment": "positive"
  }' \
  https://example.com/wp-json/chatlog/v1/save
```
