<?php

use App\Models\User;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;

describe('Authentication', function () {

    test('user can register with valid personal information', function () {
        $response = postJson('/api/auth/register/personal', [
            'fullName' => 'John Doe',
            'email' => 'john@example.com',
            'phoneNumber' => '+1234567890',
            'password' => 'SecurePassword123!',
            'acceptTerms' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'fullName', 'email', 'phoneNumber', 'createdAt', 'updatedAt'],
                    'tokens' => ['accessToken', 'refreshToken', 'expiresIn'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Personal profile created successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'fullName' => 'John Doe',
        ]);
    });

    test('registration fails with invalid email', function () {
        $response = postJson('/api/auth/register/personal', [
            'fullName' => 'John Doe',
            'email' => 'invalid-email',
            'phoneNumber' => '+1234567890',
            'password' => 'SecurePassword123!',
            'acceptTerms' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('registration fails when terms are not accepted', function () {
        $response = postJson('/api/auth/register/personal', [
            'fullName' => 'John Doe',
            'email' => 'john@example.com',
            'phoneNumber' => '+1234567890',
            'password' => 'SecurePassword123!',
            'acceptTerms' => false,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['acceptTerms']);
    });

    test('user can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'fullName', 'email', 'phoneNumber', 'hasBusinessProfile'],
                    'tokens' => ['accessToken', 'refreshToken', 'expiresIn'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);
    });

    test('login fails with invalid credentials', function () {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    });

    test('login fails with missing email', function () {
        $response = postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('authenticated user can view their profile', function () {
        $user = User::factory()->create([
            'fullName' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = getJson('/api/auth/profile', [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'fullName', 'email', 'phoneNumber', 'hasBusinessProfile'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'jane@example.com',
                        'fullName' => 'Jane Doe',
                    ],
                ],
            ]);
    });

    test('unauthenticated user cannot view profile', function () {
        $response = getJson('/api/auth/profile');

        $response->assertStatus(401);
    });

    test('authenticated user can logout', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = postJson('/api/auth/logout', [], [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);

        // Verify token is deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    });

    test('refresh token creates new access token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = postJson('/api/auth/refresh', [
            'refreshToken' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'accessToken',
                    'expiresIn',
                ],
            ]);
    });
});
