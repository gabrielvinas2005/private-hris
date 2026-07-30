<?php

namespace App\Console\Commands;

use App\Notifications\EmailUserAccountNotification;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class UserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:password-encrypt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'encrypts hrms migrated users account.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = User::where('is_encrypted', false)->get();

        if ($users->isEmpty()) {
            $this->info("Nothing to encrypt.");
            return false;
        }

        $proceed_to_update = $this->ask("This will encrypt " . count($users) . " password from migrations. do you want to continue? (Yes/No)");

        if (strtolower($proceed_to_update) != 'yes' && strtolower($proceed_to_update) != 'no') {
            $this->error("Invalid reponse. Please only enter Yes or No.");
            return false;
        }

        if (strtolower($proceed_to_update) === 'no') {
            return false;
        }

        $bar = $this->output->createProgressBar(count($users));
        $bar->start();

        foreach ($users as $user) {
            $password_temp = $user->employee_no . '_P@$sw0rd';
            $password = Hash::make($user->employee_no . '_P@$sw0rd');
            $user_account = User::where('employee_no', $user->employee_no)->get();

            // Send Email Notification
            try {
                Notification::send($user_account, new EmailUserAccountNotification($user_account, $user->email, $user->name, $password_temp));
            } catch (\Throwable $th) {
                Log::info($th);
                continue;
            }

            DB::table('users')->where('id', $user->id)
                ->update([
                    'password' => $password,
                    'is_encrypted' => true,
                ]);

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        $users = $user->select('name', 'email', 'is_encrypted')->orderBy('created_at', 'desc')->limit(count($users))->get()->toArray();
        $this->table(['name', 'email', 'is_encrypted'], $users);

        $this->alert("Successfully encrypted " . count($users) . " user passwords.");
        return true;
    }
}
