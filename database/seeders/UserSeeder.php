<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'username' => 'dhedhepratiwi',
                'password' => 'dhedhepratiwi',
                'role' => 'master',
                'trial_ends_at' => null,
                'active' => true,
            ],
            [
                'username' => 'User1',
                'password' => 'User1',
                'role' => 'trial',
                'trial_ends_at' => now()->addDays(7)->toDateString(),
                'active' => true,
            ],
            [
                'username' => 'User2',
                'password' => 'User2',
                'role' => 'trial',
                'trial_ends_at' => now()->addDays(7)->toDateString(),
                'active' => true,
            ],
            [
                'username' => 'User3',
                'password' => 'User3',
                'role' => 'trial',
                'trial_ends_at' => now()->addDays(7)->toDateString(),
                'active' => true,
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'name' => $u['username'],
                    'email' => null,
                    'password' => Hash::make($u['password']),
                    'role' => $u['role'],
                    'trial_ends_at' => $u['trial_ends_at'],
                    'active' => $u['active'],
                    'remember_token' => Str::random(10),
                ]
            );
        }
    }
}
