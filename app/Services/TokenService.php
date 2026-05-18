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
    public function createTokens(User $user, bool $remember = false): array
    {
        // Create access token (expires in 1 hour)
        $accessToken = $user->createToken('access_token', ['*'], now()->addHour());

        // Create refresh token
        // If remember is true: 30 days, else: 24 hours (Absolute Timeout)
        $refreshExpiresAt = $remember ? now()->addDays(30) : now()->addHours(24);
        $refreshToken = $user->createToken('refresh_token', ['refresh'], $refreshExpiresAt);

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

        // Implement Refresh Token Rotation
        // Delete the old refresh token
        $token->delete();

        // Create new access token
        $newAccessToken = $user->createToken('access_token', ['*'], now()->addHour());

        // Create a NEW refresh token with the same expiration logic (or inherited from old token)
        // For simplicity and security, we'll give it another 24h or 30d if the user is still active
        // But better is to check if the old token had a long expiration
        $isLongLived = $token->expires_at && $token->expires_at->diffInDays(now()) > 1;
        $refreshExpiresAt = $isLongLived ? now()->addDays(30) : now()->addHours(24);
        $newRefreshToken = $user->createToken('refresh_token', ['refresh'], $refreshExpiresAt);

        return [
            'accessToken' => $newAccessToken->plainTextToken,
            'refreshToken' => $newRefreshToken->plainTextToken,
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