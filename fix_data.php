<?php
use App\Models\User;
use App\Models\Business;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$user = User::orderBy('id')->first();

if ($user) {
    if (!$user->business_id) {
        $business = Business::create([
            'business_name' => 'My Default Business',
            'email' => $user->email,
            'is_active' => true,
            'registration_number' => 'REG-' . time(),
        ]);
        
        $user->update([
            'business_id' => $business->id,
            'role' => 'super_admin'
        ]);
        
        echo "Created business and linked to user.\n";
    } else {
        // Just make sure user is super_admin if they are the first user
        if ($user->role !== 'super_admin') {
            $user->update(['role' => 'super_admin']);
            echo "Updated user role to super_admin.\n";
        }
    }

    $b_id = $user->business_id;
    echo "Using business_id: {$b_id}\n";

    $tables = [
        'blms_borrowers', 'blms_loans', 'blms_loan_templates', 'blms_transactions', 
        'blms_loan_payments', 'blms_disbursements', 'blms_documents', 'blms_collaterals', 
        'blms_approval_logs', 'blms_loan_schedules'
    ];
    
    foreach($tables as $table) {
        if (Schema::hasTable($table) && Schema::hasColumn($table, 'business_id')) {
            $updated = DB::table($table)->whereNull('business_id')->update(['business_id' => $b_id]);
            echo "Updated $updated rows in $table\n";
        }
    }
} else {
    echo "No users found.\n";
}
