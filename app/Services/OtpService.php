<?php

namespace App\Services;

use App\Models\EmailVerification;
use App\Models\Party\Party;
use App\Mail\OtpVerificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class OtpService
{
    /**
     * OTP expiration time in minutes
     */
    const OTP_EXPIRATION_MINUTES = 10;

    /**
     * Maximum resend attempts per hour
     */
    const MAX_RESEND_PER_HOUR = 5;

    /**
     * Generate and send OTP for email verification
     */
    public function generateAndSendOtp(Party $party): array
    {
        try {
            // Check resend limit
            if ($this->hasExceededResendLimit($party)) {
                return [
                    'success' => false,
                    'message' => 'لقد تجاوزت الحد المسموح من إعادة الإرسال. يرجى المحاولة بعد ساعة.',
                ];
            }

            // Delete any existing active OTPs for this party
            $this->deleteActiveOtps($party);

            // Generate new OTP
            $otp = $this->generateOtp();

            // Create verification record
            $verification = EmailVerification::create([
                'party_id' => $party->id,
                'email' => $party->email,
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(self::OTP_EXPIRATION_MINUTES),
            ]);

            // Send OTP via email
            $this->sendOtpEmail($party, $otp);

            Log::info('OTP generated and sent', [
                'party_id' => $party->id,
                'email' => $party->email,
                'expires_at' => $verification->expires_at,
            ]);

            return [
                'success' => true,
                'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
                'expires_in_minutes' => self::OTP_EXPIRATION_MINUTES,
            ];
        } catch (Exception $e) {
            Log::error('Failed to generate and send OTP', [
                'party_id' => $party->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال رمز التحقق. يرجى المحاولة مرة أخرى.',
            ];
        }
    }

    /**
     * Verify OTP for a party
     */
    public function verifyOtp(Party $party, string $otp): array
    {
        try {
            // Find active OTP for this party
            $verification = EmailVerification::forPartyAndEmail($party->id, $party->email)
                ->active()
                ->where('otp', $otp)
                ->first();

            if (!$verification) {
                return [
                    'success' => false,
                    'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
                ];
            }

            // Mark as verified
            $verification->markAsVerified();

            // Update party's email_verified_at without triggering observers
            // Using updateQuietly to avoid foreign key constraint error with updated_by
            $party->updateQuietly([
                'email_verified_at' => Carbon::now(),
            ]);

            // Clean up old OTPs for this party
            $this->deleteOldOtps($party);

            Log::info('Email verified successfully', [
                'party_id' => $party->id,
                'email' => $party->email,
            ]);

            return [
                'success' => true,
                'message' => 'تم التحقق من البريد الإلكتروني بنجاح',
            ];
        } catch (Exception $e) {
            Log::error('Failed to verify OTP', [
                'party_id' => $party->id,
                'otp' => $otp,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء التحقق. يرجى المحاولة مرة أخرى.',
            ];
        }
    }

    /**
     * Generate a 6-digit OTP
     */
    private function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via email
     */
    private function sendOtpEmail(Party $party, string $otp): void
    {
        Mail::to($party->email)->send(new OtpVerificationMail($party, $otp));
    }

    /**
     * Delete active OTPs for a party
     */
    private function deleteActiveOtps(Party $party): void
    {
        EmailVerification::forPartyAndEmail($party->id, $party->email)
            ->active()
            ->delete();
    }

    /**
     * Delete old OTPs for a party (cleanup)
     */
    private function deleteOldOtps(Party $party): void
    {
        EmailVerification::forPartyAndEmail($party->id, $party->email)
            ->where('created_at', '<', Carbon::now()->subDays(1))
            ->delete();
    }

    /**
     * Check if party has exceeded resend limit
     */
    private function hasExceededResendLimit(Party $party): bool
    {
        $count = EmailVerification::forPartyAndEmail($party->id, $party->email)
            ->where('created_at', '>', Carbon::now()->subHour())
            ->count();

        return $count >= self::MAX_RESEND_PER_HOUR;
    }

    /**
     * Get remaining time until next resend is allowed (in seconds)
     */
    public function getResendCooldown(Party $party): int
    {
        $lastOtp = EmailVerification::forPartyAndEmail($party->id, $party->email)
            ->latest()
            ->first();

        if (!$lastOtp) {
            return 0;
        }

        $cooldownEnd = $lastOtp->created_at->addMinutes(1); // 1 minute cooldown
        $now = Carbon::now();

        return $cooldownEnd->isFuture() ? $cooldownEnd->diffInSeconds($now) : 0;
    }
}
