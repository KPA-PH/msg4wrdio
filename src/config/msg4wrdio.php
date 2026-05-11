<?php

return [

    'live' => env('MSG4wrdIO_DOMAIN', 'https://api.msg4wrd.io'),

    'sandbox' => env('MSG4wrdIO_DEVELOPER', 'https://staging.msg4wrd.io'),

    'token' => env('MSG4wrdIO_TOKEN', ''),

    'country' => env('MSG4wrdIO_DEFAULT_COUNTRY', 'PH'),

    /*
     * When true, the package registers /msg4wrd, /msg4wrd/send and
     * /msg4wrd/send-with-options routes. These endpoints are unauthenticated
     * and can trigger SMS sends billed to your token, so they are disabled
     * by default. Enable only for local testing.
     */
    'dev_mode' => env('MSG4wrdIO_DEV_MODE', false),
    'expose_demo_routes' => env('MSG4wrdIO_EXPOSE_DEMO_ROUTES', false),

];
