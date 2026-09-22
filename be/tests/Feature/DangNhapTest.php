<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use App\Models\VaiTro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class DangNhapTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $status = 'hoat_dong'): NguoiDung
    {
        $role = VaiTro::query()->firstOrCreate(['ten_vai_tro' => 'sinh_vien']);

        return NguoiDung::query()->create([
            'ho_ten' => 'Nguyễn Văn An', 'email' => 'an@example.com',
            'password' => 'password', 'vai_tro_id' => $role->id, 'trang_thai' => $status,
        ]);
    }

    public function test_login_returns_a_usable_expiring_token_and_hides_password(): void
    {
        $user = $this->user();
        $response = $this->postJson('/api/dang-nhap', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('nguoi_dung.id', $user->id)
            ->assertJsonPath('nguoi_dung.vai_tro.ten_vai_tro', 'sinh_vien')
            ->assertJsonMissingPath('nguoi_dung.password');

        $plainText = $response->json('access_token');
        $token = PersonalAccessToken::findToken($plainText);
        $this->assertTrue($token->tokenable->is($user));
        $this->assertNotEquals($plainText, $token->token);
        $this->assertTrue($token->expires_at->isFuture());

        Route::get('/api/test-auth', fn () => ['id' => auth()->id()])->middleware('auth:sanctum');
        $this->withToken($plainText)->getJson('/api/test-auth')->assertOk()->assertJsonPath('id', $user->id);
    }

    public function test_expired_token_cannot_authenticate(): void
    {
        $user = $this->user();
        $this->travelTo(now()->startOfSecond());
        $response = $this->postJson('/api/dang-nhap', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $token = $response->json('access_token');
        $this->assertTrue(PersonalAccessToken::findToken($token)->expires_at->equalTo(now()->addDay()));

        Route::get('/api/test-expired-token', fn () => ['id' => auth()->id()])->middleware('auth:sanctum');
        $this->travel(25)->hours();
        $this->withToken($token)->getJson('/api/test-expired-token')->assertUnauthorized();
        $this->travelBack();
    }

    public function test_invalid_credentials_do_not_issue_tokens(): void
    {
        $this->user();
        foreach (['an@example.com', 'missing@example.com'] as $email) {
            $this->postJson('/api/dang-nhap', ['email' => $email, 'password' => 'wrong-password'])
                ->assertUnauthorized()->assertExactJson(['message' => 'Email hoặc mật khẩu không chính xác.']);
        }
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_and_locked_users_cannot_login(): void
    {
        $user = $this->user('bi_khoa');
        foreach (['bi_khoa', 'khong_hoat_dong'] as $status) {
            $user->update(['trang_thai' => $status]);
            $this->postJson('/api/dang-nhap', ['email' => $user->email, 'password' => 'password'])->assertForbidden();
        }
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_validates_input(): void
    {
        $this->postJson('/api/dang-nhap', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->postJson('/api/dang-nhap', ['email' => 'invalid', 'password' => ['bad']])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_routes_require_authentication(): void
    {
        $this->getJson('/api/check-token')->assertUnauthorized();
        $this->getJson('/api/thong-tin')->assertUnauthorized();
        $this->postJson('/api/dang-xuat')->assertUnauthorized();
        $this->withToken('invalid-token')->getJson('/api/check-token')->assertUnauthorized();
    }

    public function test_token_can_fetch_profile_and_is_revoked_on_logout(): void
    {
        $user = $this->user();
        $token = $this->postJson('/api/dang-nhap', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->json('access_token');
        $otherToken = $user->createToken('other-device')->plainTextToken;

        $this->withToken($token)->getJson('/api/check-token')->assertOk()->assertJsonPath('status', true);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/thong-tin')->assertOk()
            ->assertJsonPath('nguoi_dung.id', $user->id)->assertJsonMissingPath('nguoi_dung.password');
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/dang-xuat')->assertOk();
        $this->assertNull(PersonalAccessToken::findToken($token));
        $this->assertNotNull(PersonalAccessToken::findToken($otherToken));
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/check-token')->assertUnauthorized();
    }

    public function test_locked_account_cannot_use_profile_or_check_token(): void
    {
        $user = $this->user();
        $token = $user->createToken('test')->plainTextToken;
        $user->update(['trang_thai' => 'bi_khoa']);
        $this->withToken($token)->getJson('/api/check-token')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/thong-tin')->assertForbidden();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/dang-nhap', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertUnauthorized();
        }
        $this->postJson('/api/dang-nhap', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertStatus(429);
    }
}
