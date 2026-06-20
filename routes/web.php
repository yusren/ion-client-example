<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Ptpn\IonClient\IonClient;

Route::get('/auth/login', function () {
    return redirect(
        app(IonClient::class)->getLoginUrl(redirectUri: url('/auth/callback'))
    );
});

Route::get('/auth/callback', function (Request $request) {
    return app(IonClient::class)->callback($request);
});

$appRoute = function (Request $request) {
    $sessionId = $request->cookie(config('ion-client.cookie.name'));
    $hasSession = Session::has('sso_session_id');

    if (! $sessionId || ! $hasSession) {
        Log::debug('Fallback route redirecting to ION login', [
            'url'          => $request->fullUrl(),
            'has_cookie'   => !empty($sessionId),
            'has_session'  => $hasSession,
            'cookie_value' => $sessionId,
        ]);

        return redirect()->away(
            app(IonClient::class)->getLoginUrl(redirectUri: url('/auth/callback'))
        );
    }

    return view('app');
};

Route::get('/', $appRoute);

Route::get('/{any?}', $appRoute)->where('any', '^(?!api|auth/callback).*$');
