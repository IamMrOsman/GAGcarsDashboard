<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
		User::create([
			'name' => 'Cyberlord',
			'email' => '1kwakubonsam@gmail.com',
			'password' => Hash::make('@password99'),
			'email_verified_at' => now(),
		]);

		User::create([
			'name' => 'Lancer',
			'email' => 'mcjohnsonlyndon@gmail.com',
			'password' => Hash::make('Omae wo korosu!'),
			'email_verified_at' => now(),
		]);

		$this->call([
			CountrySeeder::class,
			ItemFieldSeeder::class,
			BrandSeeder::class,
			MarketerRoleSeeder::class,
		]);
	}
}
