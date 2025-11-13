<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OvertimeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'SettingKey' => 'overtime_enabled',
                'SettingValue' => 'true',
            ],
            [
                'SettingKey' => 'overtime_check_time',
                'SettingValue' => '15:30',
            ],
            [
                'SettingKey' => 'overtime_prompt_interval',
                'SettingValue' => '15',
            ],
            [
                'SettingKey' => 'overtime_prompt_timeout',
                'SettingValue' => '10',
            ],
            [
                'SettingKey' => 'overtime_working_days',
                'SettingValue' => 'Mon,Tue,Wed,Thu,Fri',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('Settings')->updateOrInsert(
                ['SettingKey' => $setting['SettingKey']],
                ['SettingValue' => $setting['SettingValue']]
            );
        }
    }
}
