<?php
namespace BO_System\Core;

/**
 * Lớp Permissions quản lý việc kiểm tra quyền truy cập của người dùng.
 */
final class Permissions
{
    // Quyền truy cập chung
    const ADMIN_ACCESS = 'admin.access';

    // Quyền cho module Settings
    const SETTINGS_VIEW = 'settings.view';
    const SETTINGS_UPDATE_PROFILE = 'settings.update-profile';
    const SETTINGS_CHANGE_PASSWORD = 'settings.change-password';
    
    // Quyền quản trị hệ thống phân quyền
    const RBAC_MANAGE_PERMISSIONS = 'rbac.manage.permissions'; // Quyền để vào trang đồng bộ
    const RBAC_MANAGE_ROLES = 'rbac.manage.roles';       // Quyền để vào trang quản lý vai trò
    const RBAC_MANAGE_USERS = 'rbac.manage.users';       // Quyền để vào trang quản lý người dùng
    
    // Quyền quản trị users
    const USERS_VIEW = 'users.view';       // Quyền xem danh sách người dùng
    const USERS_CREATE = 'users.create';     // Quyền tạo người dùng mới
    const USERS_EDIT = 'users.edit';       // Quyền sửa thông tin người dùng
    const USERS_DELETE = 'users.delete';     // Quyền xóa người dùng
    const USERS_MANAGE_ROLES = 'users.manage.roles'; // Quyền gán vai trò & quyền cá nhân

    // Quyền quản lý Logs hệ thống
    const SYSTEM_LOGS_VIEW = 'system.logs.view';
    const SYSTEM_LOGS_DELETE = 'system.logs.delete';
}