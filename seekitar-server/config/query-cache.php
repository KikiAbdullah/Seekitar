<?php

return [
    'ttl' => env('QUERY_CACHE_TTL', 0),

    'max_collection' => (int) env('MAX_COLLECTION_SIZE', 500),
];
