<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table         = 'audit_logs';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id', 'username', 'module', 'action', 'record_id',
        'old_values', 'new_values', 'summary', 'ip_address', 'url',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Log an action.
     *
     * @param string      $module     e.g. 'Employees'
     * @param string      $action     e.g. 'create', 'update', 'delete'
     * @param int|null    $recordId   Primary key of the affected record
     * @param array|null  $oldValues  Data before change
     * @param array|null  $newValues  Data after change
     * @param string|null $summary    Human-readable one-line description
     */
    public function logAction(
        string $module,
        string $action,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $summary = null
    ): void {
        $session  = session();
        $request  = service('request');
        $userId   = $session->get('user_id');
        $username = $session->get('username') ?? $session->get('full_name') ?? 'system';
        $url      = (string) $request->getUri();

        // Auto-build summary if not provided
        if ($summary === null) {
            $summary = $this->buildSummary($module, $action, $recordId, $oldValues, $newValues);
        }

        $this->insert([
            'user_id'    => $userId,
            'username'   => $username,
            'module'     => $module,
            'action'     => $action,
            'record_id'  => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'summary'    => $summary,
            'ip_address' => $request->getIPAddress(),
            'url'        => $url,
        ]);
    }

    /**
     * Auto-generate a human-readable summary string.
     */
    private function buildSummary(string $module, string $action, ?int $id, ?array $old, ?array $new): string
    {
        $verb = match (strtolower($action)) {
            'create', 'add', 'store'            => 'Created',
            'update', 'edit'                    => 'Updated',
            'delete', 'destroy', 'remove'       => 'Deleted',
            'login'                             => 'Logged in',
            'logout'                            => 'Logged out',
            'finalize'                          => 'Finalized',
            'recalculate'                       => 'Recalculated',
            'complete', 'mark_complete'         => 'Marked complete',
            'upload', 'logo_upload'             => 'Uploaded',
            'delete-by-date', 'delete_by_date'  => 'Deleted all records for date',
            default                             => ucfirst($action),
        };

        $label = $module . ($id ? " #$id" : '');
        $parts = ["$verb $label"];

        // Show key identifying fields from new or old data
        $nameFields = ['name', 'full_name', 'username', 'title', 'description', 'date', 'payroll_month', 'type'];
        $record = $new ?? $old ?? [];
        foreach ($nameFields as $f) {
            if (! empty($record[$f])) {
                $parts[] = '"' . $record[$f] . '"';
                break;
            }
        }

        // Show changed fields for updates
        if ($old && $new) {
            $changed = [];
            foreach ($new as $k => $v) {
                if (array_key_exists($k, $old) && (string)$old[$k] !== (string)$v
                    && ! in_array($k, ['password', 'updated_at', 'created_at'])) {
                    $changed[] = "$k: \"" . $old[$k] . "\" → \"$v\"";
                }
            }
            if ($changed) {
                $parts[] = implode(', ', array_slice($changed, 0, 4));
            }
        }

        return implode(' — ', $parts);
    }

    /**
     * Get recent audit logs with user name, filterable.
     */
    public function getFiltered(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $builder = $this->db->table('audit_logs al')
            ->select('al.*, u.full_name AS user_full_name')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->orderBy('al.created_at', 'DESC');

        if (! empty($filters['module'])) {
            $builder->where('al.module', $filters['module']);
        }
        if (! empty($filters['action'])) {
            $builder->where('al.action', $filters['action']);
        }
        if (! empty($filters['username'])) {
            $builder->like('al.username', $filters['username']);
        }
        if (! empty($filters['date_from'])) {
            $builder->where('DATE(al.created_at) >=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $builder->where('DATE(al.created_at) <=', $filters['date_to']);
        }
        if (! empty($filters['q'])) {
            $builder->groupStart()
                        ->like('al.summary', $filters['q'])
                        ->orLike('al.username', $filters['q'])
                        ->orLike('al.module', $filters['q'])
                    ->groupEnd();
        }

        return $builder->limit($limit, $offset)->get()->getResultArray();
    }

    public function countFiltered(array $filters = []): int
    {
        $builder = $this->db->table('audit_logs al');

        if (! empty($filters['module'])) {
            $builder->where('al.module', $filters['module']);
        }
        if (! empty($filters['action'])) {
            $builder->where('al.action', $filters['action']);
        }
        if (! empty($filters['username'])) {
            $builder->like('al.username', $filters['username']);
        }
        if (! empty($filters['date_from'])) {
            $builder->where('DATE(al.created_at) >=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $builder->where('DATE(al.created_at) <=', $filters['date_to']);
        }
        if (! empty($filters['q'])) {
            $builder->groupStart()
                        ->like('al.summary', $filters['q'])
                        ->orLike('al.username', $filters['q'])
                        ->orLike('al.module', $filters['q'])
                    ->groupEnd();
        }

        return (int) $builder->countAllResults();
    }

    public function getDistinctModules(): array
    {
        return $this->db->table('audit_logs')
            ->distinct()->select('module')
            ->orderBy('module')->get()->getResultArray();
    }

    /**
     * @deprecated Use getFiltered()
     */
    public function getRecent(int $limit = 50): array
    {
        return $this->getFiltered([], $limit);
    }
}
