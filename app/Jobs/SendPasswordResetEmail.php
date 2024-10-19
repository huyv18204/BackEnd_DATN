<?php

namespace App\Jobs;

use App\Mail\VerifyPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $otp;
    public $email;
    public $minutes;

    public function __construct($otp, $email, $minutes)
    {
        $this->otp = $otp;
        $this->email = $email;
        $this->minutes = $minutes;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new VerifyPassword($this->otp, $this->email, $this->minutes));
    }
}
