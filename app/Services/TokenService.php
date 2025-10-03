<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;

class TokenService
{
    /**
     * Create access and refresh tokens for a user
     */
    public function createTokens(User $user): array
    {
        // Create access token (expires in 1 hour)
        $accessToken = $user->createToken('access_token', ['*'], now()->addHour());
        
        // Create refresh token (expires in 30 days)
        $refreshToken = $user->createToken('refresh_token', ['refresh'], now()->addDays(30));

        return [
            'accessToken' => $accessToken->plainTextToken,
            'refreshToken' => $refreshToken->plainTextToken,
            'expiresIn' => 3600 // 1 hour in seconds
        ];
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        // Find the refresh token
        $token = PersonalAccessToken::findToken($refreshToken);
        
        if (!$token || !$token->can('refresh')) {
            throw new \Exception('Invalid refresh token');
        }

        // Check if refresh token is expired
        if ($token->expires_at && $token->expires_at->isPast()) {
            throw new \Exception('Refresh token has expired');
        }

        $user = $token->tokenable;

        // Create new access token
        $newAccessToken = $user->createToken('access_token', ['*'], now()->addHour());

        return [
            'accessToken' => $newAccessToken->plainTextToken,
            'expiresIn' => 3600
        ];
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Revoke specific token
     */
    public function revokeToken(string $token): bool
    {
        $personalAccessToken = PersonalAccessToken::findToken($token);
        
        if ($personalAccessToken) {
            $personalAccessToken->delete();
            return true;
        }

        return false;
    }

    /**
     * Authenticate user with credentials
     */
    public function authenticateUser(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }
}
