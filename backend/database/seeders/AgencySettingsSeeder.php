<?php

namespace Database\Seeders;

use App\Models\ApplicationSetting;
use Illuminate\Database\Seeder;

/** Maintains the public identity of the Tanger agency without resetting data. */
class AgencySettingsSeeder extends Seeder
{
    /** Upserts the official Tanger contact and booking configuration. */
    public function run(): void
    {
        $settings = [
            'agency_name' => 'ASTRA Location Tanger',
            'agency_email' => 'contact@astra.ma',
            'agency_phone' => '+212 5 39 00 00 00',
            'agency_address' => 'Tanger, Maroc',
            'currency' => 'MAD',
            'timezone' => 'Africa/Casablanca',
            'booking_notice_hours' => '24',
        ];

        foreach ($settings as $key => $value) {
            ApplicationSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
