<?php

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Contracts\WhatsAppGateway;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshesDatabase, WithFaker;

    private string $phone = '6281234567890';
    private string $otpCode;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_request_otp_with_valid_phone(): void
    {
        $this->mock(WhatsAppGateway::class)
            ->shouldReceive('sendOtp')
            ->once()
            ->andReturnNull();

        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '081234567890',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message'])
            ->assertJsonPath('success', true);
    }

    public function test_request_otp_with_invalid_phone(): void
    {
        $response = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_verify_otp_creates_user_and_returns_token(): void
    {
        $this->setupOtpInCache();

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '081234567890',
            'otp'   => $this->otpCode,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'expires_in', 'is_new_user', 'user'],
            ]);

        $this->assertDatabaseHas('users', ['phone' => $this->phone]);
    }

    public function test_verify_otp_with_invalid_code(): void
    {
        $this->setupOtpInCache();

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '081234567890',
            'otp'   => '000000',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['otp']]);
    }

    public function test_verify_otp_returns_existing_user_on_second_login(): void
    {
        $user = User::factory()->create(['phone' => $this->phone]);

        $this->setupOtpInCache();

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '081234567890',
            'otp'   => $this->otpCode,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_new_user', false)
            ->assertJsonPath('data.user.phone', $this->phone);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user']]);
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Regresi K3: blokir user harus memutus akses JWT yang MASIH HIDUP.
     *
     * JWT stateless tidak bisa dicabut server-side, jadi middleware
     * `user.active` (EnsureUserNotBlocked) wajib menolak 423 di tiap request
     * selama status user Diblokir.
     */
    public function test_blocked_user_rejected_despite_valid_jwt(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Diblokir]);

        // JWT asli & masih berlaku (subjek = user diblokir).
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(423)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Akun Anda diblokir. Hubungi dukungan Seekitar.');
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Berhasil keluar.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    private function setupOtpInCache(): void
    {
        $this->otpCode = '123456';
        $normalized = \App\Support\PhoneNumber::normalize($this->phone) ?? $this->phone;
        Cache::put("otp:{$normalized}", Hash::make($this->otpCode), 300);
        Cache::put("otp:{$normalized}:attempts", 0, 300);

        $this->mock(WhatsAppGateway::class)
            ->shouldReceive('sendOtp')
            ->zeroOrMoreTimes()
            ->andReturnNull();
    }
}
