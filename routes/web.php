<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Ptpn\IonClient\IonClient;

Route::get('/auth/callback', function (Request $request) {
    return app(IonClient::class)->callback($request);
});

$appRoute = function (Request $request) {
    $sessionId = $request->cookie(config('ion-client.cookie.name'));

    if (! $sessionId || ! Session::has('sso_session_id')) {
        // Set return_url cookie to redirect back to target page after successful login
        $cookie = cookie('return_url', $request->fullUrl(), 15);

        return redirect()->away(sprintf(
            'https://ion.palmco.id/auth/login?client_key=%s&client_identifier=%s&redirect_uri=%s',
            urlencode(config('ion-client.client_id')),
            urlencode(config('ion-client.client_secret')),
            url('/auth/callback')
        ))->withCookie($cookie);
    }

    return view('app');
};

Route::get('/', $appRoute);

Route::get('/{any?}', $appRoute)->where('any', '^(?!api|auth/callback).*$');
