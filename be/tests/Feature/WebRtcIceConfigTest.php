<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebRtcIceConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_ice_config_requires_authentication_and_uses_stun_when_turn_is_not_configured(): void
    {
        $this->getJson('/api/webrtc/ice-config')->assertUnauthorized();
        $user = $this->user();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/webrtc/ice-config')
            ->assertOk()
            ->assertJsonPath('turnConfigured', false)
            ->assertJsonPath('iceServers.0.urls.0', 'stun:stun.l.google.com:19302')
            ->assertJsonMissingPath('iceServers.1.credential');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_turn_credentials_are_short_lived_and_generated_per_authenticated_account(): void
    {
        $user = $this->user();
        config([
            'services.turn.urls' => ['turn:turn.example.test:3478?transport=udp'],
            'services.turn.shared_secret' => 'staging-only-shared-secret',
            'services.turn.ttl_minutes' => 10,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/webrtc/ice-config')->assertOk()
            ->assertJsonPath('turnConfigured', true);
        $turn = $response->json('iceServers.1');
        $this->assertSame('turn:turn.example.test:3478?transport=udp', $turn['urls'][0]);
        $this->assertSame('password', $turn['credentialType']);
        $this->assertSame((string) $user->id, substr($turn['username'], strpos($turn['username'], ':') + 1));
        $this->assertSame(
            base64_encode(hash_hmac('sha1', $turn['username'], 'staging-only-shared-secret', true)),
            $turn['credential']
        );
        $expiry = (int) strstr($turn['username'], ':', true);
        $this->assertGreaterThanOrEqual(now()->addMinutes(9)->timestamp, $expiry);
        $this->assertLessThanOrEqual(now()->addMinutes(10)->timestamp, $expiry);
    }

    private function user(): NguoiDung
    {
        $roleId = DB::table('vai_tro')->insertGetId([
            'ten_vai_tro' => 'sinh_vien', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return NguoiDung::query()->create([
            'ho_ten' => 'ICE Test Student', 'email' => uniqid('ice').'@example.test',
            'password' => 'password', 'vai_tro_id' => $roleId, 'trang_thai' => 'hoat_dong',
        ]);
    }
}
