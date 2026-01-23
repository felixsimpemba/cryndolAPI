<?php

use App\Models\User;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanPayment;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;

describe('Loans', function () {

    test('authenticated user can list their loans', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        Loan::factory()->count(3)->create([
            'user_id' => $user->id,
            'borrower_id' => $borrower->id,
        ]);

        $response = actingAs($user)->getJson('/api/loans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'principal', 'interestRate', 'termMonths', 'startDate', 'status'],
                    ],
                ],
            ]);

        expect($response->json('data.data'))->toHaveCount(3);
    });

    test('user only sees their own loans', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $borrower1 = Borrower::factory()->create(['user_id' => $user1->id]);
        $borrower2 = Borrower::factory()->create(['user_id' => $user2->id]);

        Loan::factory()->count(2)->create(['user_id' => $user1->id, 'borrower_id' => $borrower1->id]);
        Loan::factory()->count(3)->create(['user_id' => $user2->id, 'borrower_id' => $borrower2->id]);

        $response = actingAs($user1)->getJson('/api/loans');

        $response->assertStatus(200);
        expect($response->json('data.data'))->toHaveCount(2);
    });

    test('loans can be filtered by status', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);

        Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower->id, 'status' => 'ACTIVE']);
        Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower->id, 'status' => 'PENDING']);
        Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower->id, 'status' => 'ACTIVE']);

        $response = actingAs($user)->getJson('/api/loans?status=ACTIVE');

        $response->assertStatus(200);
        expect($response->json('data.data'))->toHaveCount(2);
    });

    test('authenticated user can create a loan', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);

        $response = actingAs($user)->postJson('/api/loans', [
            'borrower_id' => $borrower->id,
            'principal' => 10000,
            'interestRate' => 12,
            'termMonths' => 12,
            'startDate' => '2025-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Loan created',
            ]);

        $this->assertDatabaseHas('loans', [
            'user_id' => $user->id,
            'borrower_id' => $borrower->id,
            'principal' => 10000,
        ]);
    });

    test('creating loan requires all fields', function () {
        $user = User::factory()->create();

        $response = actingAs($user)->postJson('/api/loans', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['borrower_id', 'principal', 'interestRate', 'termMonths', 'startDate']);
    });

    test('creating loan with negative principal fails', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);

        $response = actingAs($user)->postJson('/api/loans', [
            'borrower_id' => $borrower->id,
            'principal' => -1000,
            'interestRate' => 12,
            'termMonths' => 12,
            'startDate' => '2025-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['principal']);
    });

    test('creating loan with invalid interest rate fails', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);

        $response = actingAs($user)->postJson('/api/loans', [
            'borrower_id' => $borrower->id,
            'principal' => 10000,
            'interestRate' => 150, // Over 100%
            'termMonths' => 12,
            'startDate' => '2025-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['interestRate']);
    });

    test('user cannot create loan for another users borrower', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $borrower2 = Borrower::factory()->create(['user_id' => $user2->id]);

        $response = actingAs($user1)->postJson('/api/loans', [
            'borrower_id' => $borrower2->id,
            'principal' => 10000,
            'interestRate' => 12,
            'termMonths' => 12,
            'startDate' => '2025-01-01',
        ]);

        $response->assertStatus(404);
    });

    test('authenticated user can view loan details with aggregates', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'borrower_id' => $borrower->id,
            'principal' => 10000,
            'interestRate' => 12,
        ]);

        // Add some payments
        LoanPayment::factory()->create(['loan_id' => $loan->id, 'amountPaid' => 1000]);
        LoanPayment::factory()->create(['loan_id' => $loan->id, 'amountPaid' => 500]);

        $response = actingAs($user)->getJson("/api/loans/{$loan->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'loan',
                    'aggregates' => ['totalPaid', 'totalDue', 'balance'],
                ],
            ]);

        $aggregates = $response->json('data.aggregates');
        expect($aggregates['totalPaid'])->toBe(1500.0);
        expect($aggregates['totalDue'])->toBe(11200.0); // 10000 + 12% = 11200
        expect($aggregates['balance'])->toBe(9700.0); // 11200 - 1500
    });

    test('authenticated user can update loan', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'borrower_id' => $borrower->id,
            'principal' => 10000,
        ]);

        $response = actingAs($user)->putJson("/api/loans/{$loan->id}", [
            'principal' => 12000,
            'interestRate' => 15,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Loan updated',
            ]);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'principal' => 12000,
            'interestRate' => 15,
        ]);
    });

    test('authenticated user can delete loan', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower->id]);

        $response = actingAs($user)->deleteJson("/api/loans/{$loan->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Loan deleted',
            ]);

        $this->assertDatabaseMissing('loans', [
            'id' => $loan->id,
        ]);
    });

    test('authenticated user can change loan status', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'borrower_id' => $borrower->id,
            'status' => 'PENDING',
        ]);

        $response = actingAs($user)->postJson("/api/loans/{$loan->id}/status", [
            'status' => 'APPROVED',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Status changed',
            ]);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'APPROVED',
        ]);
    });

    test('changing status to invalid value fails', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower->id]);

        $response = actingAs($user)->postJson("/api/loans/{$loan->id}/status", [
            'status' => 'INVALID_STATUS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });

    test('cannot mark loan as PAID without full payment', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'borrower_id' => $borrower->id,
            'principal' => 10000,
            'interestRate' => 12,
        ]);

        // Add partial payment
        LoanPayment::factory()->create(['loan_id' => $loan->id, 'amountPaid' => 1000]);

        $response = actingAs($user)->postJson("/api/loans/{$loan->id}/status", [
            'status' => 'PAID',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot mark as PAID: outstanding balance remains']);
    });

    test('authenticated user can add payment to loan', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower->id]);

        $response = actingAs($user)->postJson("/api/loans/{$loan->id}/payments", [
            'paidDate' => '2025-01-15',
            'amountPaid' => 500,
            'notes' => 'First payment',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Payment recorded',
            ]);

        $this->assertDatabaseHas('loan_payments', [
            'loan_id' => $loan->id,
            'amountPaid' => 500,
        ]);
    });

    test('adding payment updates loan totalPaid', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'borrower_id' => $borrower->id,
            'totalPaid' => 0,
        ]);

        actingAs($user)->postJson("/api/loans/{$loan->id}/payments", [
            'paidDate' => '2025-01-15',
            'amountPaid' => 500,
        ]);

        $loan->refresh();
        expect($loan->totalPaid)->toBe('500.00');
    });

    test('payment requires date and amount', function () {
        $user = User::factory()->create();
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower->id]);

        $response = actingAs($user)->postJson("/api/loans/{$loan->id}/payments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['paidDate', 'amountPaid']);
    });

    test('unauthenticated user cannot access loans', function () {
        $responses = [
            getJson('/api/loans'),
            postJson('/api/loans', ['borrower_id' => 1, 'principal' => 10000]),
            getJson('/api/loans/1'),
            putJson('/api/loans/1', ['principal' => 12000]),
            deleteJson('/api/loans/1'),
            postJson('/api/loans/1/status', ['status' => 'APPROVED']),
            postJson('/api/loans/1/payments', ['paidDate' => '2025-01-01', 'amountPaid' => 500]),
        ];

        foreach ($responses as $response) {
            $response->assertStatus(401);
        }
    });
});
