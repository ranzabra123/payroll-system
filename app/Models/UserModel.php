<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['username', 'password', 'full_name', 'role', 'branch_id', 'status', 'last_login'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'username'  => 'required|min_length[3]|max_length[100]|is_unique[users.username,id,{id}]',
        'full_name' => 'required|min_length[3]|max_length[150]',
        'role'      => 'required|in_list[admin,manager,staff]',
        'status'    => 'required|in_list[active,inactive]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    /**
     * Hash password before insert/update if it is being set.
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password']) && !empty($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_BCRYPT);
        } else {
            // Remove password key so it is not set to empty string
            unset($data['data']['password']);
        }
        return $data;
    }

    /**
     * Find active user by username (for login).
     */
    public function findActiveByUsername(string $username): ?array
    {
        return $this->where('username', $username)
                    ->where('status', 'active')
                    ->first();
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(int $id): void
    {
        $this->db->table('users')
                 ->where('id', $id)
                 ->update(['last_login' => date('Y-m-d H:i:s')]);
    }
}
