<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AuthControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use DatabaseMigrations;
    protected AuthController $authController;

    public function setUp(): void
    {
        parent::setUp();
        $this->authController = $this->app->make(AuthController::class);
    }


    public function test_register(): void
    {
        $request = Request::create(
            '/api/auth/register',
            'POST',
            [
                'name' => 'Test',
                'email' => 'test123@example.com',
                'password' => 'testing123',
                'password_confirm' => 'testing123'
            ]
        );

        $response = $this->authController->register($request);
        $this->assertSame(201, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertNotEmpty($responseData['token']);
        $this->assertNotEmpty($responseData['user']);
        $this->assertSame('Test', $responseData['user']['name']);
        $this->assertSame('test123@example.com', $responseData['user']['email']);
    }

    public function test_missing_field_name(): void
    {
        $this->expectException(ValidationException::class);
        $request = Request::create(
            'api/auth/register',
            'POST',
            [
                'name' => '',
                'email' => 'test123@example.com',
                'password' => 'testing123',
                'password_confirm' => 'testing123'
            ]
        );
        $response = $this->authController->register($request);
    }

    public function test_missing_field_email(): void
    {
        $this->expectException(ValidationException::class);
        $request = Request::create(
            'api/auth/register',
            'POST',
            [
                'name' => 'Test',
                'email' => '',
                'password' => 'testing123',
                'password_confirm' => 'testing123'
            ]
        );
        $response = $this->authController->register($request);
    }

    public function test_missing_field_password(): void
    {
        $this->expectException(ValidationException::class);
        $request = Request::create(
            'api/auth/register',
            'POST',
            [
                'name' => 'Test',
                'email' => 'test123@example.com',
                'password' => '',
                'password_confirm' => 'testing123'
            ]
        );
        $response = $this->authController->register($request);
    }

    public function test_missing_field_password_confirm(): void
    {
        $this->expectException(ValidationException::class);
        $request = Request::create(
            'api/auth/register',
            'POST',
            [
                'name' => 'Test',
                'email' => 'test123@example.com',
                'password' => 'testing123',
                'password_confirm' => ''
            ]
        );
        $response = $this->authController->register($request);
    }

    public function test_login(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
        $user->save();
        $request = Request::create(
            'api/auth/login',
            'POST',
            [
                'email' => $user->email,
                'password' => $unhashedPassword
            ]
        );
        $response = $this->authController->login($request);

        $this->assertSame(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertNotEmpty($responseData['token']);
        $this->assertNotEmpty($responseData['user']);
        $this->assertSame($responseData['user']['id'], $user->id);
        $this->assertSame($responseData['user']['name'], $user->name);
        $this->assertSame($responseData['user']['email'], $user->email);
    }

    public function test_login_missing_email(): void
    {
        $this->expectException(ValidationException::class);
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
        $user->save();
        $request = Request::create(
            'api/auth/login',
            'POST',
            [
                'email' => '',
                'password' => $unhashedPassword
            ]
        );
        $response = $this->authController->login($request);
    }

    public function test_login_missing_password(): void
    {
        $unhashedPassword = 'testing123';
        $hashedPassword = Hash::make($unhashedPassword);
        $user = User::factory()->create([
            'password' => $hashedPassword
        ]);
        $user->save();

        $this->expectException(ValidationException::class);
        $request = Request::create(
            'api/auth/login',
            'POST',
            [
                'email' => $user->email,
                'password' => $hashedPassword
            ]
        );
        $response = $this->authController->login($request);
    }

    public function test_login_nonexistent_user(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
        $user->save();

        $this->expectException(ValidationException::class);
        $request = Request::create(
            'api/auth/login',
            'POST',
            [
                'email' => 'doesntexist@gmail.com',
                'password' => 'doesntexist'
            ]
        );
        $response = $this->authController->login($request);
    }

    public function test_logout(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
        $token = $user->createToken('api')->plainTextToken;
        $user->save();
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('api/auth/logout')
            ->assertOk()
            ->assertJson([
                'ok' => true
            ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me(): void
    {
        $unhashedPassword = 'testing123';
        $user = User::factory()->create([
            'password' => Hash::make($unhashedPassword)
        ]);
        $user->save();
        Sanctum::actingAs($user);

        $this->getJson('api/me')
            ->assertOk()
            ->assertJson([
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]);
    }
}
