<?php

namespace App\Services;

use App\Models\Party\Party;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class PartyPasswordResetService
{
    /**
     * Create a password reset token for the given email
     *
     * @param string $email
     * @return string|null
     */
    public function createResetToken(string $email): ?string
    {
        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        // Generate a new token
        $token = Str::random(64);

        // Store the hashed token in database
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => hash('sha256', $token),
            'created_at' => Carbon::now()
        ]);

        return $token;
    }

    /**
     * Validate if the token is valid and not expired
     *
     * @param string $email
     * @param string $token
     * @return bool
     */
    public function validateToken(string $email, string $token): bool
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', hash('sha256', $token))
            ->first();

        if (!$record) {
            return false;
        }

        // Check if token is not expired (60 minutes)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Reset the password for the given email using the token
     *
     * @param string $email
     * @param string $token
     * @param string $newPassword
     * @return bool
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        // Validate the token first
        if (!$this->validateToken($email, $token)) {
            return false;
        }

        // Find the party by email
        $party = Party::where('email', $email)->first();
        if (!$party) {
            return false;
        }

        // Update the password (will be automatically hashed by mutator)
        $party->password = $newPassword;
        $party->save();

        // Delete the used token
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        // Revoke all existing tokens for security
        $party->tokens()->delete();

        return true;
    }

    /**
     * Delete expired tokens (cleanup)
     *
     * @return int Number of deleted tokens
     */
    public function deleteExpiredTokens(): int
    {
        return DB::table('password_reset_tokens')
            ->where('created_at', '<', Carbon::now()->subHours(1))
            ->delete();
    }
}
