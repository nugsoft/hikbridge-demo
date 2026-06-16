<?php

return [

  /*
     | Base URL of the HikBridge API, e.g. https://yourserver.com/api
     */
  'base_url' => env('HIKBRIDGE_BASE_URL', 'https://devicebridge.blendsnpearls.com/api'),

  /*
     | Per-organization API key (hbk_...). Sent as Authorization: Bearer on every request.
     */
  'api_key' => env('HIKBRIDGE_API_KEY'),

  /*
     | HTTP timeout in seconds.
     */
  'timeout' => (int) env('HIKBRIDGE_TIMEOUT', 30),

  /*
     | Automatic retry on transient failures (5xx, connection errors).
     | Set 'times' to 0 to disable retries.
     */
  'retry' => [
    'times' => 3,
    'sleep' => 100, // milliseconds between retries
  ],

  /*
     | Webhook signing secret (whsec_...) returned when you create a webhook.
     | Set this to verify incoming webhook requests from HikBridge.
     */
  'webhook_secret' => env('HIKBRIDGE_WEBHOOK_SECRET'),

];
