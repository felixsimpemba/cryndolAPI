<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class SettingsController extends Controller
{
    // === Business Profile & Branding ===

    public function getSettings(Request $request)
    {
        $user = $request->user();
        $business = Business::find($user->business_id);

        // Fetch System Settings
        $systemSettings = SystemSetting::all()->mapWithKeys(function ($item) {
            return [$item->key => $item->value];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'profile' => [
                    'id' => $business?->id,
                    'businessName' => $business?->name,
                    'email' => $business?->email,
                    'phone' => $business?->phone,
                    'address' => $business?->address,
                    'working_capital' => $business?->working_capital,
                ],
                'system' => $systemSettings,
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'businessName' => 'sometimes|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'working_capital' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $business = Business::updateOrCreate(
            ['id' => $user->business_id],
            [
                'name' => $request->businessName,
                'email' => $request->contact_email,
                'phone' => $request->contact_phone,
                'address' => $request->address,
                'working_capital' => $request->working_capital ?? 0,
            ]
        );

        // Link user to business if not already linked
        if (!$user->business_id) {
            $user->business_id = $business->id;
            $user->save();
        }

        return response()->json(['status' => 'success', 'data' => $business, 'message' => 'Profile updated']);
    }

    public function updateSystemSettings(Request $request)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            $parts = explode('.', $key);
            $group = count($parts) > 1 ? $parts[0] : 'general';

            $type = 'string';
            if (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_int($value)) {
                $type = 'integer';
            } elseif (is_array($value)) {
                $type = 'json';
            }

            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'group' => $group,
                    'type' => $type,
                ]
            );
        }

        return response()->json(['status' => 'success', 'message' => 'System settings updated']);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user->password = $request->password;
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Password updated successfully']);
    }

    public function updateNotifications(Request $request)
    {
        return response()->json(['status' => 'success', 'message' => 'Notification preferences updated']);
    }
}
