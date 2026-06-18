<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Ptpn\IonClient\Facades\IonClient;

Route::get('/me', function (Request $request) {
    $sessionId = $request->cookie(config('ion-client.cookie.name'));

    if (! $sessionId || ! Session::has('sso_session_id')) {
        return response()->json([
            'authenticated' => false,
            'message' => 'Not authenticated',
        ]);
    }

    $userData = json_decode(Session::get('user_data'), true);

    return response()->json([
        'authenticated' => true,
        'session_id' => Session::get('sso_session_id'),
        'user' => $userData,
    ]);
})->name('api.me');

Route::post('/logout', function (Request $request) {
    $sessionId = Session::get('sso_session_id');

    if ($sessionId) {
        try {
            IonClient::logout($sessionId);
        } catch (\Throwable $e) {
            // Continue to clear local session even if ION call fails
        }
    }

    Session::flush();
    Session::invalidate();

    $cookieConfig = config('ion-client.cookie');
    $cookie = cookie(
        $cookieConfig['name'],
        null,
        -1,
        '/',
        $cookieConfig['domain'],
        $cookieConfig['secure'],
        $cookieConfig['http_only'],
        false,
        $cookieConfig['same_site']
    );

    return response()->json([
        'success' => true,
        'message' => 'Logged out successfully',
    ])->withCookie($cookie);
})->name('api.logout');
