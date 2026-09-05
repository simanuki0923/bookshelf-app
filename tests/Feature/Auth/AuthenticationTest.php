<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録画面を表示できる
     */
    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /**
     * 新規会員登録できる
     */
    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect('/');

        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);

        $user = User::where(
            'email',
            'test@example.com'
        )->firstOrFail();

        $this->assertTrue(
            Hash::check('password', $user->password)
        );
    }

    /**
     * メールアドレス重複時は登録できない
     */
    public function test_users_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->from('/register')
            ->post('/register', [
                'name' => 'テスト太郎',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * 7文字のパスワードでは会員登録できない
     */
    public function test_users_cannot_register_with_password_shorter_than_eight_characters(): void
    {
        $response = $this
            ->from('/register')
            ->post('/register', [
                'name' => 'テストユーザー',
                'email' => 'short-password@example.com',
                'password' => '1234567',
                'password_confirmation' => '1234567',
            ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'password',
            ]);

        $this->assertDatabaseMissing(
            'users',
            [
                'email' => 'short-password@example.com',
            ]
        );
    }

    /**
     * 8文字のパスワードで会員登録できる
     */
    public function test_users_can_register_with_eight_character_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'eight-password@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertRedirect('/');

        $this->assertDatabaseHas(
            'users',
            [
                'name' => 'テストユーザー',
                'email' => 'eight-password@example.com',
            ]
        );
    }

    /**
     * ログイン画面を表示できる
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * メールアドレス未入力ではログインできない
     */
    public function test_users_cannot_login_without_email(): void
    {
        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => 'password',
            ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'メールアドレスを入力してください。',
            ]);

        $this->assertGuest();
    }

    /**
     * メールアドレス形式でない場合はログインできない
     */
    public function test_users_cannot_login_with_invalid_email_format(): void
    {
        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => 'invalid-email',
                'password' => 'password',
            ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'メールアドレスの形式で入力してください。',
            ]);

        $this->assertGuest();
    }

    /**
     * パスワード未入力ではログインできない
     */
    public function test_users_cannot_login_without_password(): void
    {
        $response = $this
            ->from('/login')
            ->post('/login', [
                'email' => 'test@example.com',
                'password' => '',
            ]);

        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'password' => 'パスワードを入力してください。',
            ]);

        $this->assertGuest();
    }

    /**
     * 正しい情報でログインできる
     */
    public function test_users_can_authenticate(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        $response->assertRedirect('/');
    }

    /**
     * パスワードが違う場合はログインできない
     */
    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    /**
     * ログアウトできる
     */
    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/logout');

        $this->assertGuest();

        $response->assertRedirect('/login');
    }

    /**
     * ログイン済みではログイン・登録画面を表示しない
     */
    public function test_authenticated_users_are_redirected_from_guest_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/');

        $this->actingAs($user)
            ->get('/register')
            ->assertRedirect('/');
    }

    /**
     * 要件外のFortify機能ルートは登録されない
     */
    public function test_unrequired_fortify_feature_routes_are_not_registered(): void
    {
        // Basic認証で必要なルート
        $this->assertTrue(Route::has('register'));
        $this->assertTrue(Route::has('login'));
        $this->assertTrue(Route::has('logout'));

        // パスワードリセット
        $this->assertFalse(Route::has('password.request'));
        $this->assertFalse(Route::has('password.email'));
        $this->assertFalse(Route::has('password.reset'));

        // メール認証
        $this->assertFalse(Route::has('verification.notice'));
        $this->assertFalse(Route::has('verification.verify'));
        $this->assertFalse(Route::has('verification.send'));

        // プロフィール・パスワード更新
        $this->assertFalse(Route::has('user-profile-information.update'));
        $this->assertFalse(Route::has('user-password.update'));

        // 二要素認証
        $this->assertFalse(Route::has('two-factor.enable'));
        $this->assertFalse(Route::has('two-factor.disable'));
        $this->assertFalse(Route::has('two-factor.confirm'));
    }
}
