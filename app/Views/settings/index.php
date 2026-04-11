<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold"><i class="fa fa-sliders me-2 text-primary"></i>Control Panel</h5>
    <span class="badge bg-danger"><i class="fa fa-shield-halved me-1"></i>Administrator Only</span>
</div>

<!-- Tab Nav -->
<ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="tab-general" data-bs-toggle="tab" href="#general" role="tab">
            <i class="fa fa-building me-1"></i>General
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="tab-logo" data-bs-toggle="tab" href="#logo" role="tab">
            <i class="fa fa-image me-1"></i>Logo
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="tab-departments" data-bs-toggle="tab" href="#departments" role="tab">
            <i class="fa fa-sitemap me-1"></i>Departments
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="tab-branches" data-bs-toggle="tab" href="#branches" role="tab">
            <i class="fa fa-store me-1"></i>Branches
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="tab-colors" data-bs-toggle="tab" href="#colors" role="tab">
            <i class="fa fa-palette me-1"></i>Theme & Colors
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="tab-permissions" data-bs-toggle="tab" href="#permissions" role="tab">
            <i class="fa fa-lock me-1"></i>Permissions
        </a>
    </li>
</ul>

<div class="tab-content" id="settingsTabContent">

    <!-- ===================== GENERAL ===================== -->
    <div class="tab-pane fade show active" id="general" role="tabpanel">
        <div class="card">
            <div class="card-header fw-semibold"><i class="fa fa-building me-2"></i>Company / System Settings</div>
            <div class="card-body">
                <form action="<?= site_url('settings/general') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">System / Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" class="form-control"
                                   value="<?= esc($cfg['company_name'] ?? 'PayrollPH') ?>"
                                   placeholder="e.g. PayrollPH" required/>
                            <div class="form-text">Displayed in the sidebar, page titles, and payslips.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tagline / Sub-heading</label>
                            <input type="text" name="company_tagline" class="form-control"
                                   value="<?= esc($cfg['company_tagline'] ?? 'Management System') ?>"
                                   placeholder="e.g. Management System"/>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-floppy-disk me-1"></i>Save General Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================== LOGO ===================== -->
    <div class="tab-pane fade" id="logo" role="tabpanel">
        <div class="card">
            <div class="card-header fw-semibold"><i class="fa fa-image me-2"></i>Company Logo</div>
            <div class="card-body">
                <!-- Current logo -->
                <?php $logoUrl = setting_logo_url(); ?>
                <div class="mb-4">
                    <label class="form-label fw-medium">Current Logo</label>
                    <div class="border rounded p-3 d-inline-flex align-items-center gap-3"
                         style="background:#f8fafc;min-width:250px;">
                        <?php if ($logoUrl): ?>
                            <img src="<?= esc($logoUrl) ?>" alt="Logo" style="max-height:80px;max-width:200px;object-fit:contain;"/>
                            <div>
                                <div class="text-muted small mb-1">Logo is set</div>
                                <a href="<?= site_url('settings/logo/remove') ?>"
                                   class="btn btn-sm btn-outline-danger confirm-delete"
                                   data-confirm="Remove the current logo?">
                                    <i class="fa fa-trash me-1"></i>Remove
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-muted">
                                <i class="fa fa-image fa-2x mb-2 d-block"></i>
                                No logo uploaded yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upload form -->
                <form action="<?= site_url('settings/logo') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label class="form-label fw-medium">Upload New Logo</label>
                    <div class="d-flex gap-2 align-items-start flex-wrap">
                        <div>
                            <input type="file" name="logo" id="logoInput" class="form-control" accept="image/*"
                                   onchange="previewLogo(this)" required/>
                            <div class="form-text">JPG, PNG, GIF, WEBP or SVG — max 2 MB.</div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-0">
                            <i class="fa fa-upload me-1"></i>Upload Logo
                        </button>
                    </div>
                    <!-- Preview -->
                    <div id="logoPreviewWrap" class="mt-3 d-none">
                        <label class="form-label">Preview:</label>
                        <div class="border rounded p-2 d-inline-block">
                            <img id="logoPreview" src="" alt="Preview" style="max-height:80px;max-width:200px;object-fit:contain;"/>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================== DEPARTMENTS ===================== -->
    <div class="tab-pane fade" id="departments" role="tabpanel">
        <div class="row g-3">
            <!-- Add form -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header fw-semibold"><i class="fa fa-plus me-2"></i>Add Department</div>
                    <div class="card-body">
                        <form action="<?= site_url('settings/department/add') ?>" method="POST">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Department Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                       placeholder="e.g. Finance" required/>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Description</label>
                                <textarea name="description" class="form-control" rows="2"
                                          placeholder="Optional description…"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Working Days / Month <span class="text-danger">*</span></label>
                                <input type="number" name="working_days" class="form-control"
                                       value="26" min="1" max="31" required/>
                                <div class="form-text">Used for absent deduction calculation per cutoff.</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa fa-plus me-1"></i>Add Department
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Departments list -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header fw-semibold">
                        <i class="fa fa-sitemap me-2"></i>All Departments
                        <span class="badge bg-secondary ms-2"><?= count($departments) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($departments)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fa fa-sitemap fa-2x mb-2 d-block opacity-50"></i>
                            No departments yet. Add one!
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th class="text-center">Work Days/Mo</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($departments as $i => $dept): ?>
                                <!-- View row -->
                                <tr id="dept-view-<?= $dept['id'] ?>">
                                    <td class="text-muted small"><?= $i + 1 ?></td>
                                    <td class="fw-semibold"><?= esc($dept['name']) ?></td>
                                    <td class="text-muted small"><?= esc($dept['description'] ?: '—') ?></td>
                                    <td class="text-center">
                                        <span class="badge" style="background:#dbeafe;color:#1e40af;">
                                            <?= (int)($dept['working_days'] ?? 26) ?> days
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($dept['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="editDeptRow(<?= $dept['id'] ?>)" title="Edit">
                                                <i class="fa fa-pen"></i>
                                            </button>
                                            <a href="<?= site_url('settings/department/toggle/' . $dept['id']) ?>"
                                               class="btn btn-sm btn-outline-secondary" title="Toggle Active">
                                                <i class="fa fa-toggle-<?= $dept['is_active'] ? 'on' : 'off' ?>"></i>
                                            </a>
                                            <a href="<?= site_url('settings/department/delete/' . $dept['id']) ?>"
                                               class="btn btn-sm btn-outline-danger confirm-delete"
                                               data-confirm="Delete department '<?= esc($dept['name']) ?>'? This cannot be undone.">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Inline edit row -->
                                <tr id="dept-edit-<?= $dept['id'] ?>" class="d-none table-warning">
                                    <td class="text-muted small"><?= $i + 1 ?></td>
                                    <td colspan="4">
                                        <form action="<?= site_url('settings/department/edit/' . $dept['id']) ?>"
                                              method="POST" class="d-flex gap-2 align-items-start flex-wrap">
                                            <?= csrf_field() ?>
                                            <div class="flex-grow-1" style="min-width:140px;">
                                                <input type="text" name="name" class="form-control form-control-sm"
                                                       value="<?= esc($dept['name']) ?>"
                                                       placeholder="Department name" required/>
                                            </div>
                                            <div class="flex-grow-1" style="min-width:180px;">
                                                <input type="text" name="description" class="form-control form-control-sm"
                                                       value="<?= esc($dept['description']) ?>"
                                                       placeholder="Description (optional)"/>
                                            </div>
                                            <div style="width:90px;">
                                                <input type="number" name="working_days" class="form-control form-control-sm"
                                                       value="<?= (int)($dept['working_days'] ?? 26) ?>"
                                                       min="1" max="31" title="Working days/month" required/>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fa fa-check me-1"></i>Save
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    onclick="cancelDeptEdit(<?= $dept['id'] ?>)">
                                                <i class="fa fa-xmark"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== BRANCHES ===================== -->
    <div class="tab-pane fade" id="branches" role="tabpanel">
        <div class="row g-4">
            <!-- Add Branch Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header fw-semibold"><i class="fa fa-plus me-2"></i>Add Branch</div>
                    <div class="card-body">
                        <form action="<?= site_url('settings/branch/add') ?>" method="POST">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required maxlength="150" placeholder="e.g. Main Store">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" maxlength="255" placeholder="e.g. Zarraga, Iloilo">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fa fa-plus me-1"></i>Add Branch
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Branches Table -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header fw-semibold"><i class="fa fa-store me-2"></i>Branch List</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Branch Name</th>
                                        <th>Address</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($branches)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-3">No branches yet.</td></tr>
                                <?php else: ?>
                                <?php foreach ($branches as $i => $branch): ?>
                                    <!-- View Row -->
                                    <tr id="branch-view-<?= $branch['id'] ?>">
                                        <td><?= $i + 1 ?></td>
                                        <td><?= esc($branch['name']) ?></td>
                                        <td><?= esc($branch['address'] ?? '—') ?></td>
                                        <td>
                                            <?php if ($branch['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editBranchRow(<?= $branch['id'] ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <a href="<?= site_url('settings/branch/toggle/' . $branch['id']) ?>" class="btn btn-sm btn-outline-warning" title="Toggle">
                                                <i class="fa fa-toggle-<?= $branch['is_active'] ? 'on' : 'off' ?>"></i>
                                            </a>
                                            <a href="<?= site_url('settings/branch/delete/' . $branch['id']) ?>" class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Delete this branch?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <!-- Edit Row -->
                                    <tr id="branch-edit-<?= $branch['id'] ?>" class="d-none table-warning">
                                        <td><?= $i + 1 ?></td>
                                        <form action="<?= site_url('settings/branch/edit/' . $branch['id']) ?>" method="POST">
                                            <?= csrf_field() ?>
                                            <td><input type="text" name="name" class="form-control form-control-sm" value="<?= esc($branch['name']) ?>" required maxlength="150"></td>
                                            <td><input type="text" name="address" class="form-control form-control-sm" value="<?= esc($branch['address'] ?? '') ?>" maxlength="255"></td>
                                            <td></td>
                                            <td class="text-end">
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i></button>
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelBranchEdit(<?= $branch['id'] ?>)"><i class="fa fa-times"></i></button>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== COLORS ===================== -->
    <div class="tab-pane fade" id="colors" role="tabpanel">
        <div class="card">
            <div class="card-header fw-semibold"><i class="fa fa-palette me-2"></i>Theme & Color Settings</div>
            <div class="card-body">
                <form action="<?= site_url('settings/colors') ?>" method="POST" id="colorForm">
                    <?= csrf_field() ?>
                    <div class="row g-4">

                        <div class="col-md-3 col-6">
                            <label class="form-label fw-medium">Sidebar Background</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="sidebar_bg" id="sidebar_bg"
                                       class="form-control form-control-color"
                                       value="<?= esc($cfg['sidebar_bg'] ?? '#1e293b') ?>"
                                       oninput="updatePreview()"/>
                                <input type="text" id="sidebar_bg_hex" class="form-control form-control-sm"
                                       value="<?= esc($cfg['sidebar_bg'] ?? '#1e293b') ?>"
                                       oninput="syncColor('sidebar_bg', this.value)"
                                       maxlength="7" style="width:90px;"/>
                            </div>
                        </div>

                        <div class="col-md-3 col-6">
                            <label class="form-label fw-medium">Sidebar Text</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="sidebar_text" id="sidebar_text"
                                       class="form-control form-control-color"
                                       value="<?= esc($cfg['sidebar_text'] ?? '#94a3b8') ?>"
                                       oninput="updatePreview()"/>
                                <input type="text" id="sidebar_text_hex" class="form-control form-control-sm"
                                       value="<?= esc($cfg['sidebar_text'] ?? '#94a3b8') ?>"
                                       oninput="syncColor('sidebar_text', this.value)"
                                       maxlength="7" style="width:90px;"/>
                            </div>
                        </div>

                        <div class="col-md-3 col-6">
                            <label class="form-label fw-medium">Accent / Link Color</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="accent_color" id="accent_color"
                                       class="form-control form-control-color"
                                       value="<?= esc($cfg['accent_color'] ?? '#2563eb') ?>"
                                       oninput="updatePreview()"/>
                                <input type="text" id="accent_color_hex" class="form-control form-control-sm"
                                       value="<?= esc($cfg['accent_color'] ?? '#2563eb') ?>"
                                       oninput="syncColor('accent_color', this.value)"
                                       maxlength="7" style="width:90px;"/>
                            </div>
                        </div>

                        <div class="col-md-3 col-6">
                            <label class="form-label fw-medium">Topbar Background</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="topbar_bg" id="topbar_bg"
                                       class="form-control form-control-color"
                                       value="<?= esc($cfg['topbar_bg'] ?? '#ffffff') ?>"
                                       oninput="updatePreview()"/>
                                <input type="text" id="topbar_bg_hex" class="form-control form-control-sm"
                                       value="<?= esc($cfg['topbar_bg'] ?? '#ffffff') ?>"
                                       oninput="syncColor('topbar_bg', this.value)"
                                       maxlength="7" style="width:90px;"/>
                            </div>
                        </div>
                    </div>

                    <!-- Live preview sidebar -->
                    <div class="mt-4">
                        <label class="form-label fw-medium">Live Preview</label>
                        <div id="previewPanel" class="rounded overflow-hidden border d-flex" style="height:160px;max-width:500px;">
                            <div id="previewSidebar" class="p-3 flex-shrink-0" style="width:150px;background:#1e293b;">
                                <div id="previewBrand" style="color:#fff;font-weight:700;font-size:.85rem;margin-bottom:8px;">
                                    <?= esc($cfg['company_name'] ?? 'PayrollPH') ?>
                                </div>
                                <?php foreach (['Dashboard','Employees','Payroll'] as $item): ?>
                                <div id="previewLink" class="py-1 px-2 rounded mb-1 small" style="color:#94a3b8;">
                                    <?= $item ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div id="previewTopbar" class="px-3 d-flex align-items-center" style="height:48px;background:#fff;border-bottom:1px solid #e2e8f0;">
                                    <span class="small text-muted">Dashboard</span>
                                </div>
                                <div class="p-3" style="background:#f8fafc;">
                                    <div class="rounded mb-2" style="height:12px;width:80%;background:#e2e8f0;"></div>
                                    <div class="rounded mb-2" style="height:12px;width:60%;background:#e2e8f0;"></div>
                                    <div class="rounded" style="height:28px;width:100px;background:#2563eb;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-floppy-disk me-1"></i>Save Colors
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetColors()">
                            <i class="fa fa-rotate-left me-1"></i>Reset Defaults
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===================== PERMISSIONS ===================== -->
    <div class="tab-pane fade" id="permissions" role="tabpanel">
        <div class="card">
            <div class="card-header fw-semibold"><i class="fa fa-lock me-2"></i>Role Permissions</div>
            <div class="card-body">
                <p class="text-muted small mb-3">Set which modules each role can access. Admins always have full access.</p>

                <!-- Role selector -->
                <div class="mb-3 d-flex gap-2 align-items-center">
                    <label class="fw-medium me-2">Select Role:</label>
                    <button type="button" class="btn btn-sm btn-outline-primary perm-role-btn active" data-role="manager" onclick="showRolePerms('manager', this)">
                        <i class="fa fa-user-tie me-1"></i>Manager
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary perm-role-btn" data-role="staff" onclick="showRolePerms('staff', this)">
                        <i class="fa fa-user me-1"></i>Staff
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary perm-role-btn" data-role="employee" onclick="showRolePerms('employee', this)">
                        <i class="fa fa-id-card me-1"></i>Employee
                    </button>
                </div>

                <?php foreach (['manager' => $managerPerms, 'staff' => $staffPerms, 'employee' => $employeePerms] as $role => $rolePerms): ?>
                <div id="perms-<?= $role ?>" class="perm-panel" <?= $role !== 'manager' ? 'style="display:none"' : '' ?>>
                    <form action="<?= site_url('settings/permissions') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="role" value="<?= $role ?>">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:200px">Module</th>
                                        <th class="text-center">View</th>
                                        <th class="text-center">Add</th>
                                        <th class="text-center">Edit</th>
                                        <th class="text-center">Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($permModules as $slug => $label): ?>
                                <?php $p = $rolePerms[$slug] ?? ['can_view'=>0,'can_add'=>0,'can_edit'=>0,'can_delete'=>0]; ?>
                                <tr>
                                    <td class="fw-medium"><?= esc($label) ?></td>
                                    <?php foreach (['view','add','edit','delete'] as $action): ?>
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center">
                                            <input class="form-check-input" type="checkbox"
                                                   name="perms[<?= $slug ?>][can_<?= $action ?>]"
                                                   value="1"
                                                   <?= ! empty($p['can_' . $action]) ? 'checked' : '' ?>
                                                   onchange="syncViewCheckbox(this, '<?= $role ?>', '<?= $slug ?>', '<?= $action ?>')">
                                        </div>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa fa-floppy-disk me-1"></i>Save <?= ucfirst($role) ?> Permissions
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="checkAll('<?= $role ?>')">
                                <i class="fa fa-check-double me-1"></i>Check All
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="uncheckAll('<?= $role ?>')">
                                <i class="fa fa-xmark me-1"></i>Uncheck All
                            </button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

</div><!-- /.tab-content -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// ---- Department inline edit ----
function editDeptRow(id) {
    document.getElementById('dept-view-' + id).classList.add('d-none');
    document.getElementById('dept-edit-' + id).classList.remove('d-none');
}
function cancelDeptEdit(id) {
    document.getElementById('dept-edit-' + id).classList.add('d-none');
    document.getElementById('dept-view-' + id).classList.remove('d-none');
}
// ---- Branch inline edit ----
function editBranchRow(id) {
    document.getElementById('branch-view-' + id).classList.add('d-none');
    document.getElementById('branch-edit-' + id).classList.remove('d-none');
}
function cancelBranchEdit(id) {
    document.getElementById('branch-edit-' + id).classList.add('d-none');
    document.getElementById('branch-view-' + id).classList.remove('d-none');
}
document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector('[href="' + hash + '"]');
        if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
    }
});

// ---- Logo preview ----
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logoPreview').src = e.target.result;
            document.getElementById('logoPreviewWrap').classList.remove('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ---- Color sync between picker & hex text ----
function syncColor(id, val) {
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
        document.getElementById(id).value = val;
        updatePreview();
    }
}

document.querySelectorAll('input[type="color"]').forEach(function(el) {
    el.addEventListener('input', function() {
        document.getElementById(el.id + '_hex').value = el.value;
    });
});

// ---- Live preview update ----
function updatePreview() {
    const sidebarBg   = document.getElementById('sidebar_bg').value;
    const sidebarText = document.getElementById('sidebar_text').value;
    const accentColor = document.getElementById('accent_color').value;
    const topbarBg    = document.getElementById('topbar_bg').value;

    document.getElementById('previewSidebar').style.background = sidebarBg;
    document.querySelectorAll('#previewLink').forEach(el => el.style.color = sidebarText);
    document.getElementById('previewTopbar').style.background = topbarBg;
    // Update the accent button in preview
    const btn = document.querySelector('#previewPanel .rounded[style*="background:#2563eb"]');
    if (btn) btn.style.background = accentColor;
}

// ---- Reset to defaults ----
function resetColors() {
    const defaults = {
        sidebar_bg:   '#1e293b',
        sidebar_text: '#94a3b8',
        accent_color: '#2563eb',
        topbar_bg:    '#ffffff',
    };
    Object.entries(defaults).forEach(function([id, val]) {
        document.getElementById(id).value = val;
        const hexEl = document.getElementById(id + '_hex');
        if (hexEl) hexEl.value = val;
    });
    updatePreview();
}

// ---- Permissions tab ----
function showRolePerms(role, btn) {
    document.querySelectorAll('.perm-panel').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.perm-role-btn').forEach(el => {
        el.classList.remove('active', 'btn-primary', 'btn-secondary');
        el.classList.add('btn-outline-secondary');
    });
    document.getElementById('perms-' + role).style.display = '';
    btn.classList.remove('btn-outline-primary', 'btn-outline-secondary');
    btn.classList.add('btn-primary', 'active');
}

function checkAll(role) {
    document.querySelectorAll('#perms-' + role + ' input[type="checkbox"]').forEach(cb => cb.checked = true);
}

function uncheckAll(role) {
    document.querySelectorAll('#perms-' + role + ' input[type="checkbox"]').forEach(cb => cb.checked = false);
}

// If add/edit/delete is checked, auto-check view; if view unchecked, uncheck all
function syncViewCheckbox(el, role, module, action) {
    if (action !== 'view' && el.checked) {
        const viewCb = document.querySelector('#perms-' + role + ' input[name="perms[' + module + '][can_view]"]');
        if (viewCb) viewCb.checked = true;
    }
    if (action === 'view' && ! el.checked) {
        ['add','edit','delete'].forEach(a => {
            const cb = document.querySelector('#perms-' + role + ' input[name="perms[' + module + '][can_' + a + ']"]');
            if (cb) cb.checked = false;
        });
    }
}
</script>
<?= $this->endSection() ?>
