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
                'name' => 'name',
                'label' => 'Name',
                'type' => 'string',
				'required' => true,
				'unique' => false,
				'nullable' => false,
				'options' => null,
			],
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
				'name' => 'slug',
				'label' => 'Slug',
				'type' => 'string',
				'required' => true,
				'unique' => true,
				'nullable' => false,
				'options' => null,
			],
			[
				'name' => 'description',
				'label' => 'Description',
				'type' => 'string',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => null,
			],
			[
				'name' => 'images',
				'label' => 'Images',
				'type' => 'json',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => null,
			],
			[
				'name' => 'location',
				'label' => 'Location',
				'type' => 'json',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => null,
			],
			[
				'name' => 'serial_number',
				'label' => 'Serial Number',
				'type' => 'string',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => null,
			],
			[
				'name' => 'condition',
				'label' => 'Condition',
				'type' => 'enum',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => [
					'new' => 'Brand New',
					'used' => 'Used',
					'refurbished' => 'Refurbished',
				],
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
				'name' => 'features',
				'label' => 'Features',
				'type' => 'json',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => [
					'air_conditioning' => 'Air Conditioning',
					'dashboard_camera' => 'Dashboard Camera',
					'power_windows' => 'Power Windows',
					'power_locks' => 'Power Locks',
					'power_mirrors' => 'Power Mirrors',
					'power_steering' => 'Power Steering',
					'power_seats' => 'Power Seats',
					'cruise_control' => 'Cruise Control',
					'bluetooth' => 'Bluetooth',
					'navigation' => 'Navigation',
					'backup_camera' => 'Backup Camera',
					'parking_sensors' => 'Parking Sensors',
					'sunroof' => 'Sunroof',
					'leather_seats' => 'Leather Seats',
					'heated_seats' => 'Heated Seats',
					'ventilated_seats' => 'Ventilated Seats',
				],
			],
			[
				'name' => 'status',
				'label' => 'Status',
				'type' => 'enum',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => [
					'0' => 'Inactive',
					'1' => 'Active',
					'sold' => 'Sold',
					'reserved' => 'Reserved',
				],
			],
			[
				'name' => 'price',
				'label' => 'Price',
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
			],
			[
				'name' => 'warranty',
				'label' => 'Warranty',
				'type' => 'string',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => null,
			],
			[
				'name' => 'warranty_expiration',
				'label' => 'Warranty Expiration',
				'type' => 'date',
				'required' => false,
				'unique' => false,
				'nullable' => true,
				'options' => null,
			],
		];

		foreach ($fields as $field) {
			ItemField::updateOrCreate(
				['name' => $field['name']],
				$field
			);
		}
    }
}
