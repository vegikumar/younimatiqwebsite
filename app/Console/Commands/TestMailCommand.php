<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify SMTP settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Attempting to send a test email to {$email} using current SMTP settings...");
        
        try {
            Mail::raw('This is a test email from Younimatiq to verify SMTP settings are working correctly.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Test Email from Younimatiq');
            });
            
            $this->info("✅ Test email sent successfully to {$email}!");
            $this->info("If you still don't see it, check your spam folder.");
        } catch (\Exception $e) {
            $this->error("❌ Failed to send email!");
            $this->error($e->getMessage());
        }
    }
}
