<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\TeamInvitation;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->business_id) {
             return response()->json(['status' => 'error', 'message' => 'No business associated with this user.'], 403);
        }

        $team = User::where('business_id', $user->business_id)->get()->map(function(User $member) {
            return [
                'id' => $member->id,
                'fullName' => $member->full_name,
                'email' => $member->email,
                'role' => $member->role,
                'permissions' => $member->permissions ?? $member->getDefaultPermissions(),
                'phone' => $member->phone,
                'status' => $member->status,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $team]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->business_id) {
             return response()->json(['status' => 'error', 'message' => 'No business associated with this user.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|in:ADMIN,LOAN_OFFICER,VIEWER', 
            'phoneNumber' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $invitationToken = Str::random(32);
        
        $newMember = User::create([
            'full_name' => $request->fullName,
            'email' => $request->email,
            'role' => $request->role,
            'phone' => $request->phoneNumber,
            'business_id' => $user->business_id,
            'password' => Hash::make(Str::random(16)),
            'permissions' => $request->permissions,
            'invitation_token' => $invitationToken,
        ]);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $inviteUrl = rtrim($frontendUrl, '/') . "/accept-invite?token=" . $invitationToken;

        Mail::to($newMember->email)->send(new TeamInvitation($newMember, $user, $inviteUrl));

        return response()->json(['status' => 'success', 'message' => 'Team member added and invitation sent.', 'data' => $newMember], 201);
    }

    public function update(Request $request, $id)
    {
        $currentUser = $request->user();
        $user = User::where('business_id', $currentUser->business_id)->findOrFail($id);

        if ($user->role === 'SUPER_ADMIN' && $currentUser->id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Cannot modify a super admin.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'sometimes|string|in:SUPER_ADMIN,ADMIN,LOAN_OFFICER,VIEWER',
            'status' => 'sometimes|string|in:ACTIVE,INACTIVE,SUSPENDED',
            'permissions' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user->update($validator->validated());

        return response()->json(['status' => 'success', 'message' => 'Member updated', 'data' => $user]);
    }

    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();
        
        if ($currentUser->id == $id) {
            return response()->json(['status' => 'error', 'message' => 'You cannot remove yourself.'], 400);
        }

        $user = User::where('business_id', $currentUser->business_id)->findOrFail($id);
        
        if ($user->role === 'SUPER_ADMIN') {
            return response()->json(['status' => 'error', 'message' => 'Cannot remove a super admin.'], 403);
        }

        $user->delete();

        return response()->json(['status' => 'success', 'message' => 'Member removed']);
    }
}
