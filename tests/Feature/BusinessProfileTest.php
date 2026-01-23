<?php

use App\Models\User;
use App\Models\BusinessProfile;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;

describe('Business Profiles', function () {

    test('authenticated user can create business profile', function () {
        $user = User::factory()->create();

        $response = actingAs($user)->postJson('/api/auth/business-profile', [
            'businessName' => 'My Consulting Business',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'businessProfile' => ['id', 'businessName', 'createdAt', 'updatedAt'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Business profile created successfully',
            ]);

        $this->assertDatabaseHas('business_profiles', [
            'user_id' => $user->id,
            'businessName' => 'My Consulting Business',
        ]);
    });

    test('creating business profile requires business name', function () {
        $user = User::factory()->create();

        $response = actingAs($user)->postJson('/api/auth/business-profile', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['businessName']);
    });

    test('authenticated user can update business profile', function () {
        $user = User::factory()->create();
        $businessProfile = BusinessProfile::factory()->create([
            'user_id' => $user->id,
            'businessName' => 'Old Business Name',
        ]);

        $response = actingAs($user)->putJson('/api/auth/business-profile', [
            'businessName' => 'New Business Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Business profile updated successfully',
                'data' => [
                    'businessProfile' => [
                        'businessName' => 'New Business Name',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('business_profiles', [
            'id' => $businessProfile->id,
            'businessName' => 'New Business Name',
        ]);
    });

    test('authenticated user can delete business profile', function () {
        $user = User::factory()->create();
        $businessProfile = BusinessProfile::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = actingAs($user)->deleteJson('/api/auth/business-profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Business profile deleted successfully',
            ]);

        $this->assertDatabaseMissing('business_profiles', [
            'id' => $businessProfile->id,
        ]);
    });

    test('unauthenticated user cannot create business profile', function () {
        $response = postJson('/api/auth/business-profile', [
            'businessName' => 'Test Business',
        ]);

        $response->assertStatus(401);
    });

    test('unauthenticated user cannot update business profile', function () {
        $response = putJson('/api/auth/business-profile', [
            'businessName' => 'Test Business',
        ]);

        $response->assertStatus(401);
    });

    test('unauthenticated user cannot delete business profile', function () {
        $response = deleteJson('/api/auth/business-profile');

        $response->assertStatus(401);
    });
});
