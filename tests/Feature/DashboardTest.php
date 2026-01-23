<?php

use App\Models\User;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\BusinessProfile;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

describe('Dashboard', function () {

    test('authenticated user can view dashboard summary', function () {
        $user = User::factory()->create();
        $business = BusinessProfile::factory()->create(['user_id' => $user->id]);

        // Create test data
        $borrower1 = Borrower::factory()->create(['user_id' => $user->id]);
        $borrower2 = Borrower::factory()->create(['user_id' => $user->id]);

        Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower1->id, 'status' => 'ACTIVE']);
        Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower2->id, 'status' => 'ACTIVE']);
        Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower1->id, 'status' => 'PENDING']);

        $response = actingAs($user)->getJson("/api/dashboard/summary/{$business->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'totals' => ['loans', 'customers', 'activeLoans', 'pendingLoans'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    });

    test('dashboard totals are accurate', function () {
        $user = User::factory()->create();
        $business = BusinessProfile::factory()->create(['user_id' => $user->id]);

        $borrower1 = Borrower::factory()->create(['user_id' => $user->id]);
        $borrower2 = Borrower::factory()->create(['user_id' => $user->id]);
        $borrower3 = Borrower::factory()->create(['user_id' => $user->id]);

        Loan::factory()->count(2)->create(['user_id' => $user->id, 'borrower_id' => $borrower1->id, 'status' => 'ACTIVE']);
        Loan::factory()->count(3)->create(['user_id' => $user->id, 'borrower_id' => $borrower2->id, 'status' => 'PENDING']);
        Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower3->id, 'status' => 'PAID']);

        $response = actingAs($user)->getJson("/api/dashboard/summary/{$business->id}");

        $response->assertStatus(200);

        $totals = $response->json('data.totals');
        expect($totals['loans'])->toBe(6); // Total loans
        expect($totals['customers'])->toBe(3); // Total borrowers
        expect($totals['activeLoans'])->toBe(2); // Active loans
        expect($totals['pendingLoans'])->toBe(3); // Pending loans
    });

    test('dashboard shows recent payments', function () {
        $user = User::factory()->create();
        $business = BusinessProfile::factory()->create(['user_id' => $user->id]);
        $borrower = Borrower::factory()->create(['user_id' => $user->id]);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'borrower_id' => $borrower->id]);

        LoanPayment::factory()->create([
            'loan_id' => $loan->id,
            'amountPaid' => 250,
            'paidDate' => '2025-10-10',
        ]);

        $response = actingAs($user)->getJson("/api/dashboard/summary/{$business->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'recentPayments' => [
                        '*' => ['loanId', 'amount', 'date'],
                    ],
                ],
            ]);
    });

    test('user can only view their own business dashboard', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $business2 = BusinessProfile::factory()->create(['user_id' => $user2->id]);

        $response = actingAs($user1)->getJson("/api/dashboard/summary/{$business2->id}");

        $response->assertStatus(403);
    });

    test('unauthenticated user cannot access dashboard', function () {
        $response = getJson('/api/dashboard/summary/1');

        $response->assertStatus(401);
    });
});
