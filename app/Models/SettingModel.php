<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['setting_key', 'setting_value'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /** Get a single setting value by key. */
    public function getValue(string $key, ?string $default = null): ?string
    {
        $row = $this->where('setting_key', $key)->first();
        return $row ? $row['setting_value'] : $default;
    }

    /** Insert or update a setting. */
    public function setValue(string $key, ?string $value): void
    {
        $existing = $this->where('setting_key', $key)->first();
        if ($existing) {
            $this->update($existing['id'], ['setting_value' => $value]);
        } else {
            $this->insert(['setting_key' => $key, 'setting_value' => $value]);
        }
    }

    /** Get all settings as an associative array keyed by setting_key. */
    public function getAllKeyed(): array
    {
        $rows = $this->findAll();
        $out  = [];
        foreach ($rows as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
        }
        return $out;
    }
}
