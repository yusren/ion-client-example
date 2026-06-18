<?php

namespace App\Http\Controllers\Auth;

use App\Events\Auth\CheckAuthEvent;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Log;
use ReflectionProperty;
use RuntimeException;
use Throwable;

class AuthWebhookController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected SessionManager $sessionManager,
        protected Container $container,
    ) {}

    /**
     * Handle a back-channel logout webhook.
     */
    public function logout(Request $request): JsonResponse
    {
        // 1. Authenticate the caller (ION SSO server) using X-Client-ID & X-Client-Secret or Basic Auth
        $clientId = $request->header('X-Client-ID');
        $clientSecret = $request->header('X-Client-Secret');

        if ($clientId !== config('ion-client.client_id') || $clientSecret !== config('ion-client.client_secret')) {
            // Check HTTP Basic Auth as a fallback
            $basicUser = $request->getUser();
            $basicPassword = $request->getPassword();
            if ($basicUser !== config('ion-client.client_id') || $basicPassword !== config('ion-client.client_secret')) {
                Log::warning('Webhook logout unauthorized access attempt.');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        $content = $request->getContent();

        if ($content === '') {
            $payload = [];
        } else {
            $payload = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Invalid JSON'], 400);
            }
        }

        if (! is_array($payload)) {
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        $logoutToken = $payload['logout_token'] ?? null;

        if (empty($logoutToken) || ! is_string($logoutToken)) {
            return response()->json(['error' => 'Missing token'], 400);
        }

        // 2. Validate token format to prevent path traversal and arbitrary file deletion
        if (! preg_match('/^[a-zA-Z0-9\-_]{20,256}$/', $logoutToken)) {
            Log::warning('Webhook logout attempted with invalid token format.');
            return response()->json(['error' => 'Invalid token format'], 400);
        }

        Log::info('Menerima sinyal Logout untuk Session: '.$logoutToken);

        // 3. Make webhook idempotent: if session doesn't exist or is already inactive, return 200 OK
        try {
            $session = $this->getFreshSession($logoutToken);
            $session->start();
            $data = $session->all();
        } catch (Throwable $e) {
            Log::info('Session sudah tidak ada atau tidak valid saat logout webhook: '.$e->getMessage());
            return response()->json(['status' => 'ok', 'message' => 'Session already inactive or not found']);
        }

        if (($data['status'] ?? null) !== 'active') {
            Log::info('Session tidak aktif.');
            return response()->json(['status' => 'ok', 'message' => 'Session already inactive']);
        }

        try {
            $session->getHandler()->destroy($logoutToken);
        } catch (Throwable $e) {
            Log::error('Gagal menghapus session: '.$e->getMessage());
        }

        try {
            broadcast(new CheckAuthEvent($logoutToken));
        } catch (Throwable) {
            // Ignore if no connections exist or broadcaster is unavailable.
        }

        Log::info('Session lokal berhasil dihancurkan.');

        return response()->json(['status' => 'ok']);
    }

    /**
     * Build a fresh session store for the given session ID.
     *
     * A fresh SessionManager is used so that reading/destroying the target
     * session does not interfere with the request's own session lifecycle.
     */
    protected function getFreshSession(string $sessionId): Store
    {
        $freshManager = new SessionManager($this->container);
        $session = $freshManager->driver();

        if ($session->getHandler()->read($sessionId) === '') {
            throw new RuntimeException('Session not found in store');
        }

        // 4. Set session ID directly instead of using reflection
        $session->setId($sessionId);

        return $session;
    }
}
