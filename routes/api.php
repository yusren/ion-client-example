<?php

use App\Http\Controllers\Auth\AuthWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Ptpn\IonClient\Facades\IonClient;

Route::post('/auth/webhook/logout', [AuthWebhookController::class, 'logout']);

Route::get('/me', function (Request $request) {
    $sessionId = $request->cookie(config('ion-client.cookie.name'));

    if (! $sessionId || ! Session::has('sso_session_id') || $sessionId !== Session::get('sso_session_id')) {
        return response()->json([
            'authenticated' => false,
            'message' => 'Not authenticated',
        ]);
    }

    $userData = json_decode(Session::get('user_data'), true);

    // Secure and sanitize user data before sending to frontend
    if (is_array($userData)) {
        // 1. Mask cellphone_number (e.g. 081234567890 -> 08*****7890)
        if (! empty($userData['cellphone_number'])) {
            $phone = $userData['cellphone_number'];
            $len = strlen($phone);
            if ($len > 6) {
                $userData['cellphone_number'] = substr($phone, 0, 2) . str_repeat('*', $len - 6) . substr($phone, -4);
            }
        }

        // 2. Mask username and nik_sap (e.g. 88888888 -> 88****88)
        foreach (['username', 'nik_sap'] as $key) {
            if (! empty($userData[$key])) {
                $val = $userData[$key];
                $len = strlen($val);
                if ($len > 4) {
                    $userData[$key] = substr($val, 0, 2) . str_repeat('*', $len - 4) . substr($val, -2);
                }
            }
        }

        // 3. Mask telegram_id
        if (! empty($userData['telegram_id'])) {
            $tg = $userData['telegram_id'];
            $len = strlen($tg);
            if ($len > 4) {
                $userData['telegram_id'] = substr($tg, 0, 2) . str_repeat('*', $len - 4) . substr($tg, -2);
            }
        }

        // 4. Hash database integer IDs to prevent direct enumeration
        $salt = config('app.key') ?: 'ion-default-salt-key';
        foreach (['company_id', 'unit_id', 'department_id', 'position_id'] as $idKey) {
            if (isset($userData[$idKey]) && ! empty($userData[$idKey])) {
                $prefix = str_replace('_id', '', $idKey);
                $userData[$idKey] = 'hash-' . $prefix . '-' . substr(hash('sha256', $userData[$idKey] . $salt), 0, 8);
            }
        }
    }

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
        } catch (Throwable $e) {
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
