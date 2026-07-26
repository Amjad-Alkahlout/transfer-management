<?php

return [
    'page' => [
        'title' => 'User Management',
        'description' => 'Add, edit, and manage system users and their permissions.',
    ],
    'table' => [
        'title' => 'Users',
        'description' => 'List of all users registered in the system.',
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'status' => 'Status',
        'actions' => 'Actions',
    ],
    'buttons' => [
        'add' => 'Add User',
        'edit' => 'Edit',
        'update' => 'Save Changes',
        'set_password' => 'Set Password',
        'save_password' => 'Save Password',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'delete' => 'Delete',
        'confirm_delete' => 'Yes, delete permanently',
    ],
    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'role' => 'Role',
        'status' => 'Status',
        'new_password' => 'New Password',
        'new_password_confirmation' => 'Confirm New Password',
    ],
    'placeholders' => [
        'select_role' => 'Select role',
    ],
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'labels' => [
        'you' => 'You',
    ],
    'forms' => [
        'add' => [
            'title' => 'Add New User',
            'description' => 'Create a new user account for the system.',
        ],
        'edit' => [
            'title' => 'Edit User',
            'description' => 'Update the name, email, role, and active status.',
        ],
        'password' => [
            'title' => 'Set New Password',
            'description' => 'Set a new password for :user.',
        ],
    ],
    'modals' => [
        'delete' => [
            'title' => 'Confirm User Deletion',
            'message' => 'Are you sure you want to permanently delete :user? This action cannot be undone.',
        ],
    ],
    'messages' => [
        'created' => 'User added successfully.',
        'updated' => 'User updated successfully.',
        'activated' => 'User activated successfully.',
        'deactivated' => 'User deactivated successfully.',
        'deleted' => 'User permanently deleted.',
        'password_updated' => 'Password updated successfully.',
    ],
    'errors' => [
        'cannot_modify_self' => 'You cannot edit or delete your own account from here.',
        'last_admin' => 'Cannot deactivate or delete the last active admin account.',
        'cannot_delete_has_history' => 'This user cannot be deleted because they are linked to existing records. You can deactivate the account instead.',
    ],
];
