<?php

use App\Models\User;
use App\Models\Borrower;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;

describe('Customers', function () {

    test('authenticated user can list their customers', function () {
        $user = User::factory()->create();
        $customers = Borrower::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = actingAs($user)->getJson('/api/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'fullName', 'email', 'phoneNumber'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        expect($response->json('data.data'))->toHaveCount(3);
    });

    test('user only sees their own customers', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Borrower::factory()->count(2)->create(['user_id' => $user1->id]);
        Borrower::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = actingAs($user1)->getJson('/api/customers');

        $response->assertStatus(200);
        expect($response->json('data.data'))->toHaveCount(2);
    });

    test('authenticated user can create a customer', function () {
        $user = User::factory()->create();

        $response = actingAs($user)->postJson('/api/customers', [
            'fullName' => 'John Smith',
            'email' => 'john.smith@example.com',
            'phoneNumber' => '+1234567890',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Customer created',
                'data' => [
                    'fullName' => 'John Smith',
                    'email' => 'john.smith@example.com',
                ],
            ]);

        $this->assertDatabaseHas('borrowers', [
            'user_id' => $user->id,
            'fullName' => 'John Smith',
            'email' => 'john.smith@example.com',
        ]);
    });

    test('creating customer requires full name and email', function () {
        $user = User::factory()->create();

        $response = actingAs($user)->postJson('/api/customers', [
            'phoneNumber' => '+1234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fullName', 'email']);
    });

    test('creating customer with invalid email fails', function () {
        $user = User::factory()->create();

        $response = actingAs($user)->postJson('/api/customers', [
            'fullName' => 'John Smith',
            'email' => 'invalid-email',
            'phoneNumber' => '+1234567890',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    test('authenticated user can view a specific customer', function () {
        $user = User::factory()->create();
        $customer = Borrower::factory()->create([
            'user_id' => $user->id,
            'fullName' => 'Jane Doe',
        ]);

        $response = actingAs($user)->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $customer->id,
                    'fullName' => 'Jane Doe',
                ],
            ]);
    });

    test('user cannot view another users customer', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $customer = Borrower::factory()->create(['user_id' => $user2->id]);

        $response = actingAs($user1)->getJson("/api/customers/{$customer->id}");

        $response->assertStatus(404);
    });

    test('authenticated user can update their customer', function () {
        $user = User::factory()->create();
        $customer = Borrower::factory()->create([
            'user_id' => $user->id,
            'fullName' => 'Old Name',
        ]);

        $response = actingAs($user)->putJson("/api/customers/{$customer->id}", [
            'fullName' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer updated',
                'data' => [
                    'fullName' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('borrowers', [
            'id' => $customer->id,
            'fullName' => 'Updated Name',
        ]);
    });

    test('authenticated user can delete their customer', function () {
        $user = User::factory()->create();
        $customer = Borrower::factory()->create(['user_id' => $user->id]);

        $response = actingAs($user)->deleteJson("/api/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer deleted',
            ]);

        $this->assertDatabaseMissing('borrowers', [
            'id' => $customer->id,
        ]);
    });

    test('unauthenticated user cannot access customers', function () {
        $responses = [
            getJson('/api/customers'),
            postJson('/api/customers', ['fullName' => 'Test', 'email' => 'test@example.com']),
            getJson('/api/customers/1'),
            putJson('/api/customers/1', ['fullName' => 'Test']),
            deleteJson('/api/customers/1'),
        ];

        foreach ($responses as $response) {
            $response->assertStatus(401);
        }
    });

    test('customers list supports search', function () {
        $user = User::factory()->create();
        Borrower::factory()->create(['user_id' => $user->id, 'fullName' => 'John Smith', 'email' => 'john@example.com']);
        Borrower::factory()->create(['user_id' => $user->id, 'fullName' => 'Jane Doe', 'email' => 'jane@example.com']);
        Borrower::factory()->create(['user_id' => $user->id, 'fullName' => 'Bob Johnson', 'email' => 'bob@example.com']);

        $response = actingAs($user)->getJson('/api/customers?search=John');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        expect($data)->toHaveCount(2); // John Smith and Bob Johnson
    });
});
