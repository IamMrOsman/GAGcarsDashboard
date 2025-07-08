<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
			[
                'name' => 'Ghana',
                'iso3' => 'GHA',
                'iso2' => 'GH',
                'numeric_code' => '288',
                'phone_code' => '233',
                'capital' => 'Accra',
                'currency' => 'GHS',
                'currency_name' => 'Ghanaian cedi',
                'currency_symbol' => 'GH₵',
                'tld' => '.gh',
                'native' => 'Ghana',
                'region' => 'Africa',
                'subregion' => 'Western Africa',
                'latitude' => '8.00000000',
                'longitude' => '-2.00000000',
                'emoji' => '🇬🇭',
                'emojiU' => 'U+1F1EC U+1F1ED',
            ],
            [
                'name' => 'Nigeria',
                'iso3' => 'NGA',
                'iso2' => 'NG',
                'numeric_code' => '566',
                'phone_code' => '234',
                'capital' => 'Abuja',
                'currency' => 'NGN',
                'currency_name' => 'Nigerian naira',
                'currency_symbol' => '₦',
                'tld' => '.ng',
                'native' => 'Nigeria',
                'region' => 'Africa',
                'subregion' => 'Western Africa',
                'latitude' => '10.00000000',
                'longitude' => '8.00000000',
                'emoji' => '🇳🇬',
                'emojiU' => 'U+1F1F3 U+1F1EC',
            ],
		];

		foreach ($countries as $country) {
			Country::firstOrCreate($country);
		}
    }
}
