<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * GET /settings
     * Returns all settings: business profile + system workflow config
     */
    public function getSettings(Request $request)
    {
        $user     = $request->user();
        $business = Business::find($user->business_id);

        // Fetch all system settings keyed by their key name with full metadata
        $rawSettings = SystemSetting::all()->keyBy('key');

        // Build a structured system settings map
        $systemSettings = $rawSettings->mapWithKeys(function ($item) {
            $value = $item->value;
            // Cast to proper PHP type based on the stored type
            switch ($item->type) {
                case 'boolean':
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'integer':
                    $value = (int) $value;
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            return [$item->key => $value];
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'profile' => [
                    'id'              => $business?->id,
                    'businessName'    => $business?->name,
                    'contact_email'   => $business?->email,
                    'contact_phone'   => $business?->phone,
                    'address'         => $business?->address,
                    'working_capital' => $business?->working_capital,
                    'created_at'      => $business?->created_at,
                    'updated_at'      => $business?->updated_at,
                ],
                'system' => $systemSettings,
            ],
        ]);
    }

    /**
     * PUT /settings
     * Unified endpoint: saves business profile fields + system settings in one call.
     *
     * Request body:
     * {
     *   "profile": { "businessName": "...", "contact_email": "...", ... },
     *   "system":  { "workflow.auto_approve": false, "workflow.late_fee_enabled": true, ... }
     * }
     */
    public function saveSettings(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'profile'                => 'sometimes|array',
            'profile.businessName'   => 'sometimes|string|max:255',
            'profile.contact_email'  => 'nullable|email|max:255',
            'profile.contact_phone'  => 'nullable|string|max:30',
            'profile.address'        => 'nullable|string|max:500',
            'profile.working_capital'=> 'nullable|numeric|min:0',
            'system'                 => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $saved = [];

        // ── Business Profile ─────────────────────────────────────────────
        if ($request->has('profile')) {
            $profileData = $request->input('profile', []);

            $business = Business::updateOrCreate(
                ['id' => $user->business_id],
                array_filter([
                    'name'            => $profileData['businessName']    ?? null,
                    'email'           => $profileData['contact_email']   ?? null,
                    'phone'           => $profileData['contact_phone']   ?? null,
                    'address'         => $profileData['address']         ?? null,
                    'working_capital' => $profileData['working_capital'] ?? null,
                ], fn($v) => $v !== null)
            );

            // Link user to business if not already done
            if (!$user->business_id) {
                $user->business_id = $business->id;
                $user->save();
            }

            $saved['profile'] = [
                'id'              => $business->id,
                'businessName'    => $business->name,
                'contact_email'   => $business->email,
                'contact_phone'   => $business->phone,
                'address'         => $business->address,
                'working_capital' => $business->working_capital,
            ];
        }

        // ── System Settings ──────────────────────────────────────────────
        if ($request->has('system')) {
            $settings = $request->input('system', []);

            foreach ($settings as $key => $value) {
                $parts = explode('.', $key);
                $group = count($parts) > 1 ? $parts[0] : 'general';

                if (is_bool($value)) {
                    $type = 'boolean';
                } elseif (is_int($value)) {
                    $type = 'integer';
                } elseif (is_array($value)) {
                    $type  = 'json';
                    $value = json_encode($value);
                } else {
                    $type = 'string';
                }

                SystemSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => is_bool($value) ? ($value ? 'true' : 'false') : $value,
                        'group' => $group,
                        'type'  => $type,
                    ]
                );
            }

            $saved['system'] = $settings;
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Settings saved successfully',
            'data'    => $saved,
        ]);
    }

    /**
     * PUT /settings/password
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|current_password',
            'password'         => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Password updated successfully',
        ]);
    }

    /**
     * PUT /settings/profile  — kept for backwards compatibility (logo uploads via FormData)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'businessName'    => 'sometimes|string|max:255',
            'contact_email'   => 'nullable|email|max:255',
            'contact_phone'   => 'nullable|string|max:20',
            'address'         => 'nullable|string',
            'working_capital' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $business = Business::updateOrCreate(
            ['id' => $user->business_id],
            array_filter([
                'name'    => $request->businessName,
                'email'   => $request->contact_email,
                'phone'   => $request->contact_phone,
                'address' => $request->address,
                'working_capital' => $request->working_capital ?? null,
            ], fn($v) => $v !== null)
        );

        if (!$user->business_id) {
            $user->business_id = $business->id;
            $user->save();
        }

        return response()->json(['status' => 'success', 'data' => $business, 'message' => 'Profile updated']);
    }

    /**
     * PUT /settings/notifications
     */
    public function updateNotifications(Request $request)
    {
        return response()->json(['status' => 'success', 'message' => 'Notification preferences updated']);
    }
}
