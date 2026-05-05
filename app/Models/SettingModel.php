<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table         = 'settings';
    protected $primaryKey    = 'key_name';
    protected $allowedFields = ['key_name', 'value'];
    protected $useTimestamps = false;
    protected $returnType    = 'array';

    /**
     * Simpan atau update nilai setting berdasarkan key.
     */
    public function upsert(string $key, $value): void
    {
        $exists = $this->find($key);
        if ($exists) {
            $this->update($key, ['value' => $value]);
        } else {
            $this->insert(['key_name' => $key, 'value' => $value]);
        }
    }

    /**
     * Ambil satu nilai setting berdasarkan key.
     */
    public function getValue(string $key, $default = null)
    {
        $row = $this->find($key);
        return $row ? $row['value'] : $default;
    }

    /**
     * Ambil semua settings sebagai associative array [key => value].
     */
    public function getAllAssoc(): array
    {
        $rows = $this->findAll();
        return array_column($rows, 'value', 'key_name');
    }
}
