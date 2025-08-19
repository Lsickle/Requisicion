<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;

class TestEmailCommand extends Command
{
    protected $signature = 'email:test';
    protected $description = 'Test email configuration';

    public function handle()
    {
        try {
            Mail::to('pardomoyasegio@gmail.com')->send(new TestEmail());
            $this->info('Email sent successfully!');
        } catch (\Exception $e) {
            $this->error('Error sending email: ' . $e->getMessage());
        }
    }
}