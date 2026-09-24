<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AccountControlService;
use App\Services\AuditLogService;
use App\Services\CategoryAdminService;
use App\Services\NotificationService;
use App\Services\AuthGuard;

class AdminControlController extends Controller
{
    public function categories()
    {
        $this->requireAdmin();
        return $this->view('admin/categories/index', ['categories' => (new CategoryAdminService())->all()]);
    }

    public function saveCategory()
    {
        $admin = $this->requireAdmin();
        csrf_verify();
        try {
            $id = (new CategoryAdminService())->save($_POST);
            AuditLogService::log('product_category_saved', 'category', $id, ['new_value' => ['name' => trim((string) ($_POST['name'] ?? ''))]], (int) $admin['id'], '/ani-mart/public/admin/categories/save');
            Session::flash('success', 'Category saved successfully.');
        } catch (\InvalidArgumentException $e) { Session::flash('error', $e->getMessage()); }
        return $this->redirect('/ani-mart/public/admin/categories');
    }

    public function setCategoryStatus()
    {
        $admin = $this->requireAdmin();
        csrf_verify();
        try {
            $active = ($_POST['status'] ?? '') === 'active';
            $result = (new CategoryAdminService())->setActive((int) ($_POST['id'] ?? 0), $active);
            AuditLogService::log('product_category_status_changed', 'category', (int) ($_POST['id'] ?? 0), ['old_value' => ['status' => $result['old_status']], 'new_value' => ['status' => $result['new_status']], 'category_name' => $result['name']], (int) $admin['id'], '/ani-mart/public/admin/categories/status');
            Session::flash('success', 'Category ' . ($active ? 'restored.' : 'archived. Existing products keep their category.'));
        } catch (\InvalidArgumentException $e) { Session::flash('error', $e->getMessage()); }
        return $this->redirect('/ani-mart/public/admin/categories');
    }

    public function users()
    {
        $this->requireAdmin();
        $search = trim((string) ($_GET['search'] ?? ''));
        $roleFilter = strtolower((string) ($_GET['role'] ?? ''));
        $statusFilter = strtolower((string) ($_GET['status'] ?? ''));
        $users = (new AccountControlService())->users($search, $roleFilter, $statusFilter);
        return $this->view('admin/users/index', compact('users', 'search', 'roleFilter', 'statusFilter'));
    }

    public function setUserStatus()
    {
        $admin = $this->requireAdmin();
        csrf_verify();
        try {
            $status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'suspended';
            $reason = trim((string) ($_POST['reason'] ?? ''));
            $userId = (int) ($_POST['user_id'] ?? 0);
            $result = (new AccountControlService())->setStatus($userId, $status, $reason, (int) $admin['id']);
            AuditLogService::log('user_account_status_changed', 'user', $userId, ['old_value' => ['status' => $result['old_status']], 'new_value' => ['status' => $status, 'reason' => $reason]], (int) $admin['id'], '/ani-mart/public/admin/users/status');
            NotificationService::send($userId, $status === 'suspended' ? 'Account Suspended' : 'Account Restored', $status === 'suspended' ? 'Your Ani-Mart account was suspended. Reason: ' . $reason : 'Your Ani-Mart account access has been restored.', 'system', $status === 'suspended' ? 'bi-person-lock' : 'bi-person-check', $status === 'suspended' ? 'danger' : 'success');
            Session::flash('success', 'User account ' . ($status === 'active' ? 'restored.' : 'suspended.'));
        } catch (\InvalidArgumentException $e) { Session::flash('error', $e->getMessage()); }
        return $this->redirect('/ani-mart/public/admin/users');
    }

    private function requireAdmin(): array
    {
        return AuthGuard::admin();
    }
}
