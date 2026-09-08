<?php

return [
    'main' => [
        // -------------------- MODULE: Administer --------------------
        [
            'title'     => 'Administer',
            'icon'      => 'fa-solid fa-sliders',
            'icon_size' => 20,
            'children'  => [
                [
                    'title'      => 'States',
                    'route'      => 'states.index',
                    'permission' => 'state.list',
                    'icon'       => 'fa-solid fa-map-location-dot',
                ],
                // [
                //     'title'      => 'Cheque Format',
                //     'route'      => 'cheque.index',
                //     'permission' => 'cheque.list',
                //     'icon'       => 'fa-solid fa-money-check-alt'
                // ],
                [
                    'title'      => 'Email Templates',
                    'route'      => 'email-templates.index',
                    'permission' => 'email_template.list',
                    'icon'       => 'fa-solid fa-envelope-open-text',
                ],
                [
                    'title' => 'Weight Locations',
                    'route' => 'weight_location.index',
                    'permission' => 'weight_location.list',
                    'icon' => 'fas fa-weight-hanging'
                ],
                [
                    'title'      => 'Party Master',
                    'route'      => 'party-masters.index',
                    'permission' => 'party_master.list',
                    'icon'       => 'fa-solid fa-users',
                ],

            ],
        ],

        // -------------------- MODULE: Role Management --------------------
        [
            'title'     => 'Role Management',
            'icon'      => 'fa-solid fa-shield-halved',
            'icon_size' => 20,
            'children'  => [
                [
                    'title'      => 'Roles',
                    'route'      => 'roles.index',
                    'permission' => 'role.list',
                    'icon'       => 'fa-solid fa-user-shield',
                ],
                [
                    'title'      => 'Permissions',
                    'route'      => 'permissions.index',
                    'permission' => 'role.list',
                    'icon'       => 'fa-solid fa-key',
                ],
            ],
        ],

        // -------------------- MODULE: Users --------------------
        [
            'title'     => 'Users',
            'icon'      => 'fa-solid fa-users',
            'icon_size' => 20,
            'children'  => [
                [
                    'title'      => 'Users',
                    'route'      => 'users.index',
                    'permission' => 'user.list',
                    'icon'       => 'fa-solid fa-user',
                ],
                [
                    'title'      => 'Company Assign to User',
                    'route'      => 'company-users.index',
                    'permission' => 'user.list',
                    'icon'       => 'fa-solid fa-building-user',
                ],
                [
                    'title'      => 'Module Assign to Company',
                    'route'      => 'company-modules.index',
                    'permission' => 'user.list',
                    'icon'       => 'fa-solid fa-puzzle-piece',
                ],
            ],
        ],

        // -------------------- MODULE: Setup --------------------
        [
            'title'     => 'Setup',
            'icon'      => 'fa-solid fa-gear',
            'icon_size' => 20,
            'children'  => [

                [
                    'title'      => 'Database Backup',
                    'route'      => 'database-backup.index',
                    'permission' => 'database_backup.list',
                    'icon'       => 'fa-solid fa-database'
                ],
            ],
        ],
        [
            'title'     => 'Dairy Report',
            'icon'      => 'fa-solid fa-file-contract',
            'icon_size' => 20,
            'children'  => [
                [
                    'title'      => 'Dairy Out Standing ',
                    'route'      => 'dairy-outstanding.index',
                    'permission' => 'dairy_outstanding.list',
                    'icon'       => 'fa-solid fa-receipt',
                ],
            ],
        ],
    ],
];
