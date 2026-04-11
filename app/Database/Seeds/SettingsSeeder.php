<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['setting_key' => 'company_name',    'setting_value' => 'PayrollPH'],
            ['setting_key' => 'company_tagline',  'setting_value' => 'Management System'],
            ['setting_key' => 'logo_path',        'setting_value' => null],
            ['setting_key' => 'sidebar_bg',       'setting_value' => '#1e293b'],
            ['setting_key' => 'sidebar_text',     'setting_value' => '#94a3b8'],
            ['setting_key' => 'accent_color',     'setting_value' => '#2563eb'],
            ['setting_key' => 'topbar_bg',        'setting_value' => '#ffffff'],
        ];

        foreach ($defaults as $row) {
            $exists = $this->db->table('settings')
                ->where('setting_key', $row['setting_key'])
                ->get()->getRowArray();
            if (! $exists) {
                $this->db->table('settings')->insert(array_merge($row, [
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));
            }
        }
    }
}
