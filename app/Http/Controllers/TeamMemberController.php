<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        // List users belonging to the same business (assuming users are linked by business_id potentially or just listing all for now if single tenant logic isn't fully strict per row)
        // Since we don't have a strict multi-tenant scope yet, we might assume authorized user can see others?
        // Actually, user has `business_id`. Let's use that if available, or just list all if super admin?
        // For now, let's assume we list all users (simple team) or users with same business_id.

        // $user = $request->user();
        // $team = User::where('business_id', $user->business_id)->get(); 

        // Fallback for current simple setup: return all users except current? or just all
        $team = User::all();

        return response()->json(['status' => 'success', 'data' => $team]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string',
            'phoneNumber' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Generate random password they can reset later
        $password = Str::random(10);
        $data['password'] = Hash::make($password);
        $data['phoneNumber'] = $data['phoneNumber'] ?? '';

        $user = User::create($data);

        // Ideally send email invitation here with password

        return response()->json(['status' => 'success', 'message' => 'Team member added', 'data' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'role' => 'required|string',
            'status' => 'nullable|string|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $user->update($validator->validated());

        return response()->json(['status' => 'success', 'message' => 'Member updated', 'data' => $user]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['status' => 'success', 'message' => 'Member removed']);
    }
}
