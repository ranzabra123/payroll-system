<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'module' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
            ],
            'record_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'old_values' => [
                'type' => 'JSON',
                'null' => true,
                'default' => null,
            ],
            'new_values' => [
                'type' => 'JSON',
                'null' => true,
                'default' => null,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
                'default'    => null,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['module', 'action']);
        $this->forge->createTable('audit_logs');
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_logs');
    }
}
