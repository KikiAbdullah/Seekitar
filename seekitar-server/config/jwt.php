<?php

/*
|--------------------------------------------------------------------------
| JWT Authentication Configuration
|--------------------------------------------------------------------------
|
| tymon/jwt-auth package configuration.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Secret
    |--------------------------------------------------------------------------
    |
    | JWT signing key. Generated once via `php artisan jwt:secret` and
    | stored in .env as JWT_SECRET. NEVER commit the real value to VCS.
    |
    */

    'secret' => env('JWT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Keys
    |--------------------------------------------------------------------------
    |
    | The algorithm you are using, will determine whether your tokens are
    | signed with a random string (provided in config) or using the
    | following public & private keys.
    |
    | Supported: "HS256", "HS384", "HS512", "RS256", "RS384", "RS512"
    |
    */

    'keys' => [
        'public'  => env('JWT_PUBLIC_KEY'),
        'private' => env('JWT_PRIVATE_KEY'),
        'passphrase' => env('JWT_PASSPHRASE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT time to live (TTL)
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that the token will be valid
    | for. Defaults to 30 days (43200 minutes) — same as old Sanctum 30-day TTL.
    |
    */

    'ttl' => env('JWT_TTL', 43200),

    /*
    |--------------------------------------------------------------------------
    | Refresh time to live
    |--------------------------------------------------------------------------
    |
    | Specify the length of time (in minutes) that a token can be refreshed
    | within. I.E. The user can refresh their token within a 2 week window
    | of the original token being created until they must re-authenticate.
    | Defaults to 2 weeks.
    |
    */

    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | JWT hashing algorithm
    |--------------------------------------------------------------------------
    |
    | Specify the hashing algorithm that will be used to sign the token.
    |
    | Use HS256 unless you have a specific reason (e.g., RS256 for microservices
    | that need to verify tokens without sharing a secret).
    |
    */

    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    */

    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti',
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent Claims
    |--------------------------------------------------------------------------
    |
    | Specify the claim keys to be persisted when refreshing a token.
    | `sub` and `iat` will automatically be persisted, so no need to include.
    |
    */

    'persistent_claims' => [
        // 'foo', 'bar'
    ],

    /*
    |--------------------------------------------------------------------------
    | Lock Subject
    |--------------------------------------------------------------------------
    |
    | This will determine whether a `prv` claim is automatically added to
    | the token. The purpose is to ensure that if you have multiple
    | authentication models (e.g. App\User & App\Admin), a token for one
    | model cannot be used to authenticate as another.
    |
    */

    'lock_subject' => true,

    /*
    |--------------------------------------------------------------------------
    | Leeway
    |--------------------------------------------------------------------------
    |
    | This property gives the jwt timestamp claims some "leeway".
    | Meaning that if you have any unavoidable slight clock skew between
    | your issuing and validating server, the claims will still be considered
    | valid. In seconds.
    |
    */

    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    |
    | Invalidate tokens by adding them to a blacklist until they expire.
    | Requires the `jwt.blacklist` cache driver (typically your app cache).
    |
    */

    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Grace Period
    |--------------------------------------------------------------------------
    |
    | When multiple concurrent requests are made with the same JWT,
    | it is possible that some of them fail due to token regeneration
    | on every request. Set grace period in seconds to prevent this.
    |
    */

    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 30),

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Decouple token building from the User model lookup for performance
    | in high-traffic scenarios.
    |
    */

    'decay_custom_provider_claims' => false,
    'custom_provider_claims_ttl' => 1440,

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Authentication provider: 'users' from config/auth.php.
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],

        'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,

        'auth' => Tymon\JWTAuth\Providers\Auth\Illuminate::class,

        'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,
    ],

];
