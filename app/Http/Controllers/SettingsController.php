<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
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
        $businessProfile = BusinessProfile::where('user_id', $user->id)->first();

        // Fetch System Settings (optionally filter by user permission or public)
        $systemSettings = SystemSetting::all()->mapWithKeys(function ($item) {
            return [$item->key => $item->value];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'profile' => $businessProfile,
                'system' => $systemSettings,
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'businessName' => 'sometimes|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'currency_code' => 'nullable|string|size:3',
            'locale' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $profile = BusinessProfile::updateOrCreate(
            ['user_id' => $user->id],
            $validator->validated()
        );

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', 'public');
            $profile->logo_url = $path;
            $profile->save();
        }

        return response()->json(['status' => 'success', 'data' => $profile, 'message' => 'Profile updated']);
    }

    // === System Settings (Admin) ===

    public function updateSystemSettings(Request $request)
    {
        // Add Check for Admin Role here later

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

    // === Security ===

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

        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Password updated successfully']);
    }

    public function updateNotifications(Request $request)
    {
        // Placeholder for user preference update
        return response()->json(['status' => 'success', 'message' => 'Notification preferences updated']);
    }
}
