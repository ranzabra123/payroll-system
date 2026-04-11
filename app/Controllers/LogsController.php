<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use CodeIgniter\Controller;

class LogsController extends Controller
{
    private AuditLogModel $model;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->model = new AuditLogModel();
    }

    public function index()
    {
        $perPage = 50;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset  = ($page - 1) * $perPage;

        $filters = [
            'module'    => $this->request->getGet('module')    ?? '',
            'action'    => $this->request->getGet('action')    ?? '',
            'username'  => $this->request->getGet('username')  ?? '',
            'date_from' => $this->request->getGet('date_from') ?? '',
            'date_to'   => $this->request->getGet('date_to')   ?? '',
            'q'         => $this->request->getGet('q')         ?? '',
        ];

        // Strip empty strings so they are treated as "no filter"
        $activeFilters = array_filter($filters, fn($v) => $v !== '');

        $logs    = $this->model->getFiltered($activeFilters, $perPage, $offset);
        $total   = $this->model->countFiltered($activeFilters);
        $modules = $this->model->getDistinctModules();

        return view('logs/index', [
            'title'   => 'Audit Logs',
            'logs'    => $logs,
            'filters' => $filters,
            'modules' => $modules,
            'total'   => $total,
            'perPage' => $perPage,
            'page'    => $page,
            'pages'   => (int) ceil($total / $perPage),
        ]);
    }
}
