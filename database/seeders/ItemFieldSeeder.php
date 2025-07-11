<?php

namespace Database\Seeders;

use App\Models\ItemField;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ItemFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
			[
				'name' => 'year',
				'label' => 'Year',
				'type' => 'year',
				'required' => true,
				'unique' => false,
				'nullable' => false,
				'options' => null,
			],
			[
				'name' => 'steer_position',
				'label' => 'Steer Position',
				'type' => 'enum',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => [
					'left' => 'Left',
					'right' => 'Right',
				],
			],
			[
				'name' => 'engine_capacity',
				'label' => 'Engine Capacity',
				'type' => 'string',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => null,
			],
			[
				'name' => 'transmission',
				'label' => 'Transmission',
				'type' => 'enum',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => [
					'manual' => 'Manual',
					'automatic' => 'Automatic',
					'semi_automatic' => 'Semi-Automatic',
				],
			],
			[
				'name' => 'color',
				'label' => 'Color',
				'type' => 'json',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => null,
			],
			[
				'name' => 'build_type',
				'label' => 'Build Type',
				'type' => 'enum',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => [
					'sedan' => 'Sedan',
					'hatchback' => 'Hatchback',
					'suv' => 'SUV',
					'mpv' => 'MPV',
					'coupe' => 'Coupe',
					'convertible' => 'Convertible',
					'pickup' => 'Pickup',
					'van' => 'Van',
					'wagon' => 'Wagon',
					'other' => 'Other',
				],
			],
			[
				'name' => 'number_of_passengers',
				'label' => 'Number of Passengers',
				'type' => 'number',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => null,
			],
			[
				'name' => 'mileage',
				'label' => 'Mileage',
				'type' => 'number',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => null,
			]
		];

		foreach ($fields as $field) {
			ItemField::updateOrCreate(
				['name' => $field['name']],
				$field
			);
		}
    }
}
