<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
$bId = $user->business_id;

echo "User Working Capital: {$user->working_capital}\n";
echo "Active Loans Count: " . App\Models\Loan::where('status', 'active')->count() . "\n";
echo "Active Loans Principal: " . App\Models\Loan::where('status', 'active')->sum('principal') . "\n";
echo "Loan Payments for Active Loans: " . App\Models\LoanPayment::whereHas('loan', function($q){$q->where('status', 'active');})->count() . "\n";
echo "Sum Amount Scheduled: " . App\Models\LoanPayment::whereHas('loan', function($q){$q->where('status', 'active');})->sum('amountScheduled') . "\n";
