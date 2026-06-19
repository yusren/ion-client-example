<?php

namespace Tests\Feature;

use Ptpn\IonClient\IonClient;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    private function expectedLoginUrl(): string
    {
        return app(IonClient::class)
            ->getLoginUrl(redirectUri: url('/auth/callback'));
    }

    public function test_the_application_redirects_to_ion_login_when_not_authenticated(): void
    {
        $response = $this->get('/');

        $response->assertRedirect($this->expectedLoginUrl());
    }

    public function test_the_application_redirects_to_ion_login_for_any_frontend_route_when_not_authenticated(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect($this->expectedLoginUrl());
    }

    public function test_the_application_returns_app_view_when_authenticated(): void
    {
        $this->withSession(['sso_session_id' => 'sso-session-id'])
            ->withCookie(config('ion-client.cookie.name'), 'sso-session-id');

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_application_returns_app_view_for_any_frontend_route_when_authenticated(): void
    {
        $this->withSession(['sso_session_id' => 'sso-session-id'])
            ->withCookie(config('ion-client.cookie.name'), 'sso-session-id');

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
    }
}
