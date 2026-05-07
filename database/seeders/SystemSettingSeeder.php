<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            ['key' => 'theme', 'value' => 'system', 'type' => 'string'],
            ['key' => 'allowed_extensions', 'value' => 'jpg,jpeg,png,gif,webp,pdf,txt,zip,doc,docx,xls,xlsx,ppt,pptx,csv,mp3,mp4,exe', 'type' => 'string'],
            ['key' => 'max_upload_size_mb', 'value' => '10', 'type' => 'integer'],
            ['key' => 'storage_limit_gb', 'value' => '10', 'type' => 'integer'],
        ];

        foreach ($defaults as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }
    }
}
