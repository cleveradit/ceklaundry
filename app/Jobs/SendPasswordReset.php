<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\User;
use App\Services\OutboundGuard;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendPasswordReset implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(private int $userId, private string $recipient, private string $token, private string $expiresAt, public ?int $businessId) {}

    public function handle(OutboundGuard $guard): void
    {
        try {
            $user = User::query()->find($this->userId);
            if (! $user || $user->business_id !== $this->businessId || $user->email !== $this->recipient || now()->greaterThanOrEqualTo($this->expiresAt)) {
                return;
            }
            $business = $this->businessId ? Business::query()->find($this->businessId) : null;
            if ($this->businessId && ! $business) {
                return;
            }
            $row = DB::table('password_reset_tokens')->where('email', $user->email)->first();
            if (! $row || ! $guard->allows($business, $row->created_at) || ! Hash::check($this->token, $row->token)) {
                return;
            }
            $url = route('password.reset', ['token' => $this->token, 'email' => $this->recipient]);
            // No tenant mailer state: security mail always uses the global transport.
            if (! $guard->allows($business, $row->created_at)) {
                return;
            }
            Mail::raw("Permintaan reset password CekLaundry.\n\nBuka tautan berikut dalam 60 menit:\n$url\n\nJika Anda tidak meminta reset, abaikan email ini.", fn ($message) => $message->to($this->recipient)->subject('Reset password CekLaundry'));
        } catch (Throwable) {
            $this->fail(new RuntimeException('Pengiriman email keamanan tidak dapat dipastikan. Minta reset baru; jangan ulangi job ini.'));
        }
    }
}
