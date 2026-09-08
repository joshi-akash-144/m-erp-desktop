<?php

use Spatie\Permission\Models\Permission;


return [
    'main' => [
        // -------------------- MODULE: Home --------------------
        [
            'title' => 'Home',
            'icon'  => 'home',
            'route' => 'dashboard',
            'icon_size' => 20,
            'permission' => null,
            'color' => '#8237bf',
            'bg' => '#f3e8faff',
        ],

        // -------------------- MODULE: Master --------------------
        [
            'title' => 'Master',
            'icon' => 'database',
            'icon_size' => 20,
            'color' => '#c55349ff',
            'bg' => '#fde9e9',
            'sections' => [
                'accounts_financial' => [
                    'title' => 'Accounts & Financial',
                    'icon' => 'fas fa-file-invoice-dollar',
                    'children' => [
                        ['title' => 'Accounts', 'route' => 'accounts.index', 'permission' => 'account.list', 'icon' => 'fas fa-user-circle'],
                        ['title' => 'Account Groups', 'route' => 'account-groups.index', 'permission' => 'account_group.list', 'icon' => 'fas fa-layer-group'],
                        ['title' => 'Bank Configuration', 'route' => 'bank-configurations.index', 'permission' => 'bank_configuration.list', 'icon' => 'fas fa-university'],
                        ['title' => 'Bill Sundries', 'route' => 'bill-sundries.index', 'permission' => 'bill_sundry.list', 'icon' => 'fas fa-receipt'],
                        ['title' => 'Tax Categories', 'route' => 'tax-categories.index', 'permission' => 'tax_category.list', 'icon' => 'fas fa-percentage'],
                        ['title' => 'TDS Categories', 'route' => 'tds-categories.index', 'permission' => 'tds_category.list', 'icon' => 'fas fa-calculator'],
                        ['title' => 'Payee Categories', 'route' => 'payee-categories.index', 'permission' => 'payee_category.list', 'icon' => 'fas fa-tags'],
                    ],
                ],
                'inventory_items' => [
                    'title' => 'Inventory & Items',
                    'icon' => 'fas fa-boxes',
                    'children' => [
                        ['title' => 'Items', 'route' => 'items.index', 'permission' => 'item.list', 'icon' => 'fas fa-box'],
                        ['title' => 'Item Groups', 'route' => 'item-groups.index', 'permission' => 'item_group.list', 'icon' => 'fas fa-boxes'],
                        ['title' => 'Godown Units', 'route' => 'godowns.index', 'permission' => 'godown.list', 'icon' => 'fas fa-warehouse'],
                        ['title' => 'Units', 'route' => 'units.index', 'permission' => 'unit.list', 'icon' => 'fas fa-balance-scale'],
                        ['title' => 'Unit Conversions', 'route' => 'unit-conversions.index', 'permission' => 'unit_conversion.list', 'icon' => 'fas fa-exchange-alt'],
                        ['title' => 'Elements', 'route' => 'elements.index', 'permission' => 'element.list', 'icon' => 'fas fa-atom'],
                        // ['title' => 'Godown Unit Location', 'route' => 'godown_unit_location.index','permission' =>'godown_unit_location.list', 'icon' => 'fas fa-exchange-alt'],
                        ['title' => 'Bags Rate', 'route' => 'bags-rates.index', 'permission' => 'bags_rate.list', 'icon' => 'fa-solid fa-indian-rupee-sign'],
                    ],
                ],
                'business_config' => [
                    'title' => 'Business Configuration',
                    'icon' => 'fas fa-cogs',
                    'children' => [
                        ['title' => 'Brokers', 'route' => 'brokers.index', 'permission' => 'broker.list', 'icon' => 'fas fa-user-tie'],
                        ['title' => 'Conditions', 'route' => 'conditions.index', 'permission' => 'condition.list', 'icon' => 'fas fa-clipboard-list'],
                        ['title' => 'Destinations', 'route' => 'destinations.index', 'permission' => 'destination.list', 'icon' => 'fas fa-map-marker-alt'],
                        ['title' => 'Purchase Types', 'route' => 'purchase-types.index', 'permission' => 'purchase_type.list', 'icon' => 'fas fa-shopping-bag'],
                        ['title' => 'Sale Types', 'route' => 'sale-types.index', 'permission' => 'sale_type.list', 'icon' => 'fas fa-store'],
                        ['title' => 'Dairy Parameter', 'route' => 'dairy-parameters.index', 'permission' => 'dairy_parameter.list', 'icon' => 'fas fa-flask'],
                        ['title' => 'Transporter', 'route' => 'transporters.index', 'permission' => 'transporter.list', 'icon' => 'fas fa-truck'],

                    ],
                ],
            ],
        ],

        [
            'title' => 'Transport Master',
            'icon' => 'database',
            'icon_size' => 20,
            'color' => '#c55349ff',
            'bg' => '#fde9e9',
            'sections' => [
                'accounts_financial' => [
                    'title' => 'Accounts & Financial',
                    'icon' => 'fas fa-file-invoice-dollar',
                    'children' => [
                        ['title' => 'Accounts', 'route' => 'accounts.index', 'permission' => 'account.list', 'icon' => 'fas fa-user-circle'],
                        ['title' => 'Account Groups', 'route' => 'account-groups.index', 'permission' => 'account_group.list', 'icon' => 'fas fa-layer-group'],
                        ['title' => 'Items', 'route' => 'items.index', 'permission' => 'item.list', 'icon' => 'fas fa-box'],
                        ['title' => 'Driver', 'route' => 'drivers.index', 'permission' => 'driver.list', 'icon' => 'fas fa-user-tie'],
                        ['title' => 'Transport Party', 'route' => 'transport_parties.index', 'permission' => 'transport_party.list', 'icon' => 'fa-solid fa-handshake'],
                        ['title' => 'TDS Categories', 'route' => 'tds-categories.index', 'permission' => 'tds_category.list', 'icon' => 'fas fa-calculator'],
                        ['title' => 'Payee Categories', 'route' => 'payee-categories.index', 'permission' => 'payee_category.list', 'icon' => 'fas fa-tags'],
                    ],
                ],
                'transport_details' => [
                    'title' => 'Transport Details',
                    'icon' => 'fas fa-truck',
                    'children' => [
                        ['title' => 'Vehicle', 'route' => 'vehicles.index', 'permission' => 'vehicle.list', 'icon' => 'fas fa-truck'],
                        ['title' => 'Vehicle Owner', 'route' => 'vehicle-owners.index', 'permission' => 'vehicle_owner.list', 'icon' => 'fas fa-user'],
                        ['title' => 'Zone', 'route' => 'zones.index', 'permission' => 'zone.list', 'icon' => 'fas fa-map-marker'],
                        // ['title' => 'Expense Type', 'route' => 'expense-types.index','permission' => 'expense_type.list','icon' => 'fas fa-exclamation-triangle'],
                        ['title' => 'Bags Rate', 'route' => 'bags-rates.index', 'permission' => 'bags_rate.list', 'icon' => 'fa-solid fa-indian-rupee-sign'],
                        // ['title' => 'Mandali', 'route' => 'mandalis.index','permission' => 'mandali.list','icon' => 'fas fa-user-tie'],
                        ['title' => 'Destinations', 'route' => 'destinations.index', 'permission' => 'destination.list', 'icon' => 'fas fa-map-marker-alt'],
                        ['title' => 'Contractors', 'route' => 'contractors.index', 'permission' => 'contractor.list', 'icon' => 'fas fa-file-contract'],
                    ],
                ],
            ],
        ],

        // -------------------- MODULE: Purchase --------------------
        [
            'title' => 'Purchase',
            'icon' => 'shopping-cart',
            'icon_size' => 20,
            'color' => '#066ed6',
            'bg' => '#e6f0fa',
            'sections' => [
                'purchase_transactions' => [
                    'title' => 'Purchase Transactions',
                    'icon' => 'fas fa-shopping-cart',
                    'children' => [
                        ['title' => 'Purchase Orders (PO)', 'route' => 'purchase-orders.create', 'permission' => 'purchase_order.create', 'icon' => 'fas fa-plus-circle'],
                        ['title' => 'Goods Receipt Notes (GRN)', 'route' => 'grns.create', 'permission' => 'grn.create', 'icon' => 'fas fa-truck-loading'],
                        ['title' => 'Purchase Invoice (PI)', 'route' => 'purchase-invoices.create', 'permission' => 'purchase_invoice.create', 'icon' => 'fas fa-file-invoice'],
                        ['title' => 'Debit Note (Purchase Return)', 'route' => 'debit-notes.create', 'permission' => 'debit_note.create', 'icon' => 'fas fa-undo-alt'],
                        ['title' => 'Mobile GRN', 'route' => 'mobile-grns.create', 'permission' => 'mobile_grn.create', 'icon' => 'fas fa-mobile'],
                    ],
                ],
                'purchase_reports' => [
                    'title' => 'Purchase Reports',
                    'icon' => 'fas fa-chart-line',
                    'children' => [
                        ['title' => 'Purchase Orders (PO) List', 'route' => 'purchase-orders.index', 'permission' => 'purchase_order.list', 'icon' => 'fas fa-list'],
                        ['title' => 'Goods Receipt Notes (GRN) List', 'route' => 'grns.index', 'permission' => 'grn.list', 'icon' => 'fas fa-clipboard-list'],
                        ['title' => 'Purchase Invoice List', 'route' => 'purchase-invoices.index', 'permission' => 'purchase_invoice.list', 'icon' => 'fas fa-file-alt'],
                        ['title' => 'Debit Note (DN) List', 'route' => 'debit-notes.index', 'permission' => 'debit_note.list', 'icon' => 'fas fa-undo'],
                        ['title' => 'Purchase Order Detail List', 'route' => 'purchase-orders.purchase_order_with_grn', 'permission' => 'purchase_order_with_grn.list', 'icon' => 'fas fa-file-medical-alt'],
                    ],
                ],
                'purchase_settings' => [
                    'title' => 'Module Settings',
                    'icon' => 'fa-solid fa-cog',
                    'children' => [
                        ['title' => 'PO Penalty', 'route' => 'penalty.index', 'permission' => 'penalty.list', 'icon' => 'fa-solid fa-gavel'],
                    ],
                ],
            ],
        ],

        // -------------------- MODULE: Sales --------------------
        [
            'title' => 'Sales',
            'icon' => 'receipt',
            'icon_size' => 20,
            'color' => '#794c33ff',
            'bg' => 'rgba(160, 99, 64, 0.14)',
            'sections' => [
                'sales_transactions' => [
                    'title' => 'Sales Transactions',
                    'icon' => 'fas fa-cash-register',
                    'children' => [
                        ['title' => 'Sales Orders (SO)', 'route' => 'sales-orders.create', 'permission' => 'sales_order.create', 'icon' => 'fas fa-plus-circle'],
                        ['title' => 'Sales Invoice (SI)', 'route' => 'sales-invoices.create', 'permission' => 'sales_invoice.create', 'icon' => 'fas fa-file-invoice'],
                        ['title' => 'Credit Note (Sale Return)', 'route' => 'credit-notes.create', 'permission' => 'credit_note.create', 'icon' => 'fas fa-rotate-left'],
                        // ['title' => 'Delivery Challan (DC)', 'route' => 'delivery-challans.create', 'icon' => 'fas fa-pen-to-square','permission' => 'delivery_challan.create'],
                        // ['title' => 'Delivery Challan Mobile (DCM)', 'route' => 'delivery-challans-mobile.create', 'icon' => 'fas fa-mobile-screen-button','permission' => 'delivery_challan_mobile.create'],
                        ['title' => 'Multi GRN', 'route' => 'multi-grns.create', 'icon' => 'fas fa-truck-loading', 'permission' => 'multi_grn.create'],
                    ],
                ],
                'sales_reports' => [
                    'title' => 'Sales Reports',
                    'icon' => 'fas fa-chart-bar',
                    'children' => [
                        ['title' => 'Sales Orders (SO) List', 'route' => 'sales-orders.index', 'permission' => 'sales_order.list', 'icon' => 'fas fa-list'],
                        ['title' => 'Sales Invoice (SI) List', 'route' => 'sales-invoices.index', 'permission' => 'sales_invoice.list', 'icon' => 'fas fa-file-alt'],
                        ['title' => 'Credit Note (CN) List', 'route' => 'credit-notes.index', 'permission' => 'credit_note.list', 'icon' => 'fas fa-file-circle-minus'],
                        ['title' => 'Sales Order Detail List', 'route' => 'sales-orders.sales_order_with_bill', 'permission' => 'sales_order.list', 'icon' => 'fas fa-file-medical-alt'],
                        // ['title' => 'Delivery Challan (DC) List', 'route' => 'delivery-challans.index', 'permission' => 'delivery_challan.list', 'icon' => 'fas fa-clipboard-list'],
                    ],
                ],
                'gst_portal' => [
                    'title' => 'GST Portal',
                    'icon' => 'fas fa-file-invoice',
                    'children' => [
                        // ['title' => 'E-Invoice & E-Way Bill', 'route' => 'gst-portal.index', 'permission' => 'e_invoice.manage', 'icon' => 'fas fa-file-invoice'],
                        ['title' => 'Generate E-WayBill / E-Invoice', 'route' => 'gst-portal.generate', 'permission' => 'e_invoice.manage', 'icon' => 'fas fa-truck-fast'],
                        ['title' => 'E-Invoice & EWB Errors', 'route' => 'gst-portal.error-logs', 'permission' => 'e_invoice.manage', 'icon' => 'fas fa-triangle-exclamation'],
                    ],
                ],
            ],
        ],

        [
            'title' => 'Billing',
            'icon' => 'receipt',
            'icon_size' => 20,
            'color' => '#794c33ff',
            'bg' => 'rgba(160, 99, 64, 0.14)',
            'sections' => [
                'billing' => [
                    'title' => 'Billing',
                    'icon' => 'fa-solid fa-minus-circle',
                    'children' => [
                        ['title' => 'Freight', 'route' => 'freight.create', 'permission' => 'freight.create', 'icon' => 'fa-solid fa-key'],
                        ['title' => 'Freight Invoice', 'route' => 'freight-invoice.create', 'permission' => 'freight_invoice.create', 'icon' => 'fa-solid fa-file-invoice'],
                        ['title' => 'Freight Invoice2', 'route' => 'freight-invoice2.create', 'permission' => 'freight_invoice2.create', 'icon' => 'fa-solid fa-file-invoice'],
                    ],
                ],
                'billing_reports' => [
                    'title' => 'Billing Reports',
                    'icon' => 'fa-solid fa-minus-circle',
                    'children' => [
                        ['title' => 'Freight List',                    'route' => 'freight.index',                            'permission' => 'freight.list',            'icon' => 'fa-solid fa-list'],
                        ['title' => 'Freight Invoice List', 'route' => 'freight-invoice.index', 'permission' => 'freight_invoice.list', 'icon' => 'fa-solid fa-list'],
                        ['title' => 'Freight Invoice2 List', 'route' => 'freight-invoice2.index', 'permission' => 'freight_invoice2.list', 'icon' => 'fa-solid fa-list'],
                    ],
                ],
            ],
        ],
        // -------------------- MODULE: Payment --------------------
        [
            'title' => 'Payment',
            'icon' => 'credit-card',
            'icon_size' => 20,
            'color' => '#33473cff',
            'bg' => 'rgba(107, 202, 153, 0.36)',
            'sections' => [
                'payment_transactions' => [
                    'title' => 'Payment Transactions',
                    'icon' => 'fas fa-money-bill-wave',
                    'children' => [
                        ['title' => 'Payment Payable', 'route' => 'payments.payable.create', 'permission' => 'payment_payable.list', 'icon' => 'fas fa-hand-holding-usd'],
                        ['title' => 'Payment Receivable', 'route' => 'payments.receivable.create', 'permission' => 'payment_receivable.create', 'icon' => 'fas fa-money-bill-transfer'],
                        ['title' => 'Payment Approve', 'route' => 'payments.approved.index', 'icon' => 'fa-solid fa-check-double', 'permission' => 'payment_approval.list'],
                        ['title' => 'Payment Hold', 'route' => 'payments.hold.index', 'icon' => 'fa-solid fa-circle-pause', 'permission' => 'payment_hold.list'],
                        ['title' => 'ONAC Payment Payable', 'route' => 'onac-payment.create', 'permission' => 'onac_payment.create', 'icon' => 'fas fa-money-check-alt'],
                    ],
                ],
                'bank_payments' => [
                    'title' => 'Bank Payments',
                    'icon' => 'fas fa-university',
                    'children' => [
                        ['title' => 'Online / RTGS', 'route' => 'payment-online-rtgs.index', 'permission' => 'payment_online_rtgs.list', 'icon' => 'fas fa-building-columns'],
                        ['title' => 'Payment Register', 'route' => 'payment-register.index', 'permission' => 'payment_register.list', 'icon' => 'fas fa-file-invoice-dollar'],
                        ['title' => 'Receipt Register', 'route' => 'receipt-register.index', 'permission' => 'receipt_register.list', 'icon' => 'fas fa-file-invoice-dollar'],
                        ['title' => 'Payment Advice', 'route' => 'payment-advice.index', 'permission' => 'payment_advice.list', 'icon' => 'fas fa-file-invoice-dollar'],
                    ],
                ],
            ],
        ],
        // -------------------- MODULE: Transport Reference Payment --------------------
        [
            'title' => 'Transport Ref. Payment',
            'icon' => 'credit-card',
            'icon_size' => 20,
            'color' => '#33473cff',
            'bg' => 'rgba(107, 202, 153, 0.36)',
            'sections' => [
                'payment_transactions' => [
                    'title' => 'Transport Ref. Payment',
                    'icon' => 'fas fa-money-bill-wave',
                    'children' => [
                        ['title' => 'Transport Ref. Payment', 'route' => 'transport-reference-payment.index', 'permission' => 'transport_ref_payment.create', 'icon' => 'fa-solid fa-truck-fast'],
                    ],
                ],
                'reports' => [
                    'title' => 'Reports',
                    'icon' => 'fas fa-money-bill-wave',
                    'children' => [
                        ['title' => 'Transport Payment Register',      'route' => 'transport-reference-payment.register',     'permission' => 'transport_ref_payment.list', 'icon' => 'fa-solid fa-file-invoice-dollar'],
                    ],
                ],
            ],
        ],

        // -------------------- MODULE: Voucher --------------------
        [
            'title' => 'Voucher',
            'icon' => 'file-text',
            'icon_size' => 20,
            'bg' => '#fcdcdcff',
            'color' => '#6E3A3D',
            'sections' => [
                'voucher_entry' => [
                    'title' => 'Voucher Entry',
                    'icon' => 'fa-solid fa-ticket',
                    'children' => [
                        ['title' => 'Journal Voucher', 'route' => 'journal-vouchers.create', 'permission' => 'journal_voucher.create', 'icon' => 'fas fa-book'],
                        ['title' => 'Payment Voucher', 'route' => 'payment-vouchers.create', 'permission' => 'payment_voucher.create', 'icon' => 'fas fa-book'],
                        ['title' => 'Receipt Voucher', 'route' => 'receipt-vouchers.create', 'permission' => 'receipt_voucher.create', 'icon' => 'fas fa-book'],
                        ['title' => 'Credit Note Voucher', 'route' => 'credit-note-vouchers.create', 'permission' => 'credit_note_voucher.create', 'icon' => 'fas fa-book'],
                        ['title' => 'Debit Note Voucher', 'route' => 'debit-note-vouchers.create', 'permission' => 'debit_note_voucher.create', 'icon' => 'fas fa-book'],
                    ],
                ],
                'voucher_reports' => [
                    'title' => 'Voucher Reports',
                    'icon' => 'fas fa-chart-bar',
                    'children' => [
                        ['title' => 'Journal Voucher Register', 'route' => 'journal-vouchers.index', 'permission' => 'journal_voucher.list', 'icon' => 'fas fa-file-invoice'],
                        ['title' => 'Payment Voucher Register', 'route' => 'payment-vouchers.index', 'permission' => 'payment_voucher.list', 'icon' => 'fas fa-clipboard-list'],
                        ['title' => 'Receipt Voucher Register', 'route' => 'receipt-vouchers.index', 'permission' => 'receipt_voucher.list', 'icon' => 'fas fa-file-alt'],
                        ['title' => 'Credit Note Voucher Register', 'route' => 'credit-note-vouchers.index', 'permission' => 'credit_note_voucher.list', 'icon' => 'fas fa-file-invoice'],
                        ['title' => 'Debit Note Voucher Register', 'route' => 'debit-note-vouchers.index', 'permission' => 'debit_note_voucher.list', 'icon' => 'fas fa-file-invoice'],
                    ],
                ],
            ],
        ],

        // -------------------- MODULE: FAS (Financial Accounting System) --------------------
        [
            'title' => 'FAS',
            'icon' => 'chart-pie',
            'icon_size' => 20,
            'bg' => '#e4a5a5a6',
            'color' => '#2e3141ff',
            'sections' => [
                'financial_reports' => [
                    'title' => 'Financial Reports',
                    'icon' => 'fas fa-chart-pie',
                    'children' => [
                        ['title' => 'Stock Status', 'route' => 'stock-status.index', 'permission' => 'stock_status.list', 'icon' => 'fas fa-cubes'],
                        ['title' => 'Trial Balance', 'route' => 'trial-balance.index', 'permission' => 'trial_balance.list', 'icon' => 'fas fa-balance-scale-right'],
                        ['title' => 'Trial Balance Opening', 'route' => 'trial-balance.opening-list', 'permission' => 'trial_balance.list', 'icon' => 'fas fa-folder-open'],
                        ['title' => 'Profit & Loss', 'route' => 'profit-loss.index', 'permission' => 'profit_loss.list', 'icon' => 'fas fa-chart-line'],
                    ],
                ],
                'gst_reports' => [
                    'title' => 'GST Reports',
                    'icon' => 'fas fa-file-invoice-dollar',
                    'children' => [
                        ['title' => 'GSTR-1 Report', 'route' => 'gstr1-report.index', 'permission' => 'gstr1_report.list', 'icon' => 'fas fa-file-invoice'],
                        ['title' => 'GSTR-2 Report', 'route' => 'gstr2-report.index', 'permission' => 'gstr2_report.list', 'icon' => 'fas fa-file-invoice'],
                        ['title' => 'GSTR-3B Report', 'route' => 'gstr3b-report.index', 'permission' => 'gstr3b_report.list', 'icon' => 'fas fa-file-invoice-dollar'],
                    ],
                ],
                'tds_reports' => [
                    'title' => 'TDS Reports',
                    'icon' => 'fas fa-percent',
                    'children' => [
                        ['title' => 'TDS Entries', 'route' => 'fas.tds-entries.index', 'permission' => 'tds_entry.list', 'icon' => 'fas fa-file-invoice-dollar'],
                    ],
                ],
                'audit_trail' => [
                    'title' => 'Audit Trail',
                    'icon' => 'fas fa-history',
                    'children' => [
                        ['title' => 'Audit Trail Logs', 'route' => 'fas.audit-trails.index', 'permission' => 'audit_trail.list', 'icon' => 'fas fa-list'],
                    ],
                ],
            ],
        ],

        // -------------------- MODULE: Ledger Reports --------------------
        [
            'title' => 'General Report',
            'icon' => 'chart-bar',
            'icon_size' => 20,
            'bg' => '#a0d9dbff',
            'color' => '#004D52',
            'sections' => [
                'reports' => [
                    'title' => 'Reports',
                    'icon' => 'fa-solid fa-file-lines',
                    'children' => [
                        ['title' => 'Ledger', 'route' => 'ledger-report.index', 'permission' => 'ledger_report.list', 'icon' => 'fas fa-book-open'],
                        ['title' => 'Daybook', 'route' => 'daybook-report.index', 'permission' => 'daybook_report.list', 'icon' => 'fas fa-book'],
                    ],
                ],
            ],
        ],

        // -------------------- MODULE: Analysis --------------------
        [
            'title' => 'Analysis',
            'icon' => 'flask',
            'icon_size' => 20,
            'color' => '#92495fff',
            'bg' => 'rgba(136, 78, 95, 0.32)',
            'sections' => [
                'analysis' => [
                    'title' => 'Analysis',
                    'icon' => 'fa-solid fa-microscope',
                    'children' => [
                        [
                            'title' => 'Dairy Analysis',
                            'route' => 'dairy-analysis.create',
                            'permission' => 'dairy_analysis.create',
                            'icon' => 'fa fa-flask'
                        ],
                        [
                            'title' => 'Godown Analysis',
                            'route' => 'godown-analysis.create',
                            'permission' => 'godown_analysis.create',
                            'icon' => 'fa fa-flask'
                        ],
                    ],
                ],
                'Analysis Reports' => [
                    'title' => 'Reports',
                    'icon' => 'fas fa-chart-bar',
                    'children' => [
                        [
                            'title' => 'Dairy Analysis List',
                            'route' => 'dairy-analysis.index',
                            'permission' => 'dairy_analysis.list',
                            'icon' => 'fas fa-list'
                        ],
                        [
                            'title' => 'Dairy Analysis Rebate Pending',
                            'route' => 'dairy-analysis.rebate-pending.index',
                            'permission' => 'dairy_analysis_report.rebate_pending_report',
                            'icon' => 'fa-solid fa-clock-rotate-left'
                        ],
                        [
                            'title' => 'Godown Analysis List',
                            'route' => 'godown-analysis.index',
                            'permission' => 'godown_analysis.list',
                            'icon' => 'fas fa-list'
                        ],
                        // [
                        //     'title' => 'Sales Purchase Analysis',
                        //     'route' => 'sales-purchase-analysis.index',
                        //     'permission' => 'sales_purchase_analysis.list',
                        //     'icon' => 'fas fa-balance-scale'
                        // ],
                    ],
                ],
            ],
        ],

        // -------------------- MODULE: Godown --------------------
        [
            'title' => 'Godown',
            'icon' => 'warehouse',
            'icon_size' => 20,
            'color' => '#6A4761',
            'bg' => 'rgba(106, 71, 97, 0.34)',
            'sections' => [
                'godown' => [
                    'title' => 'Godown',
                    'icon' => 'fa-solid fa-warehouse',
                    'children' => [
                        ['title' => 'Product In', 'route' => 'godown-module.product-in', 'permission' => 'godown_module.product_in', 'icon' => 'fa fa-door-open'],
                        ['title' => 'Product Out', 'route' => 'godown-module.product-out', 'permission' => 'godown_module.product_out', 'icon' => 'fa fa-door-closed'],
                        ['title' => 'Product In Manual', 'route' => 'godown-module.product-in-manual', 'permission' => 'godown_module.product_in_manual', 'icon' => 'fa fa-door-open'],
                        ['title' => 'Product Out Manual', 'route' => 'godown-module.product-out-manual', 'permission' => 'godown_module.product_out_manual', 'icon' => 'fa fa-door-closed'],
                        ['title' => 'Bag Challan Labour', 'route' => 'bag-challan-labour.create', 'permission' => 'bag_challan_labour.create', 'icon' => 'fa-solid fa-bag-shopping'],
                        // ['title' => 'Product In Self', 'route' => 'godown-module.product-in-self','permission' => 'self.product_in','icon' => 'fa fa-door-open'],
                        // ['title' => 'Product Out Self', 'route' => 'godown-module.product-out-self','permission' => 'self.product_out','icon' => 'fa fa-door-closed'],
                    ],
                ],
                'godown_reports' => [
                    'title' => 'Godown Reports',
                    'icon' => 'fa-solid fa-warehouse',
                    'children' => [
                        ['title' => 'Godown List', 'route' => 'godown-module.index', 'permission' => 'godown_module.list', 'icon' => 'fa fa-door-open'],
                        ['title' => 'Transporter List', 'route' => 'godown-module.transporter-list', 'permission' => 'godown_module.transporter_list', 'icon' => 'fa fa-truck'],
                        ['title' => 'Bag Challan Labour List', 'route' => 'bag-challan-labour.index', 'permission' => 'bag_challan_labour.list', 'icon' => 'fa-solid fa-bag-shopping'],
                        ['title' => 'Moisture List', 'route' => 'moisture.index', 'permission' => 'moisture.list', 'icon' => 'fa fa-water'],
                    ],
                ],
            ],
        ],
        // -------------------- MODULE: Driver Expenses --------------------
        [
            'title' => 'Expenses',
            'icon' => 'chart-pie',
            'icon_size' => 20,
            'bg' => '#e4a5a5a6',
            'color' => '#2e3141ff',
            'sections' => [
                'driver_expenses' => [
                    'title' => 'Expenses',
                    'icon' => 'fas fa-chart-pie',
                    'children' => [
                        ['title' => 'Driver Expenses', 'route' => 'driver-expense.create', 'permission' => 'driver_expense.create', 'icon' => 'fa-solid fa-money-bill-1'],
                        ['title' => 'Diesel Entry', 'route' => 'diesel.create', 'permission' => 'diesel.create', 'icon' => 'fa-solid fa-gas-pump'],
                        ['title' => 'Multi Expense Voucher', 'route' => 'multi-expense.create', 'permission' => 'multi_expense_voucher.create', 'icon' => 'fa-solid fa-file-invoice'],
                        ['title' => 'Salary Voucher', 'route' => 'salary-module.create', 'permission' => 'salary.create', 'icon' => 'fa-solid fa-money-bill'],
                    ],
                ],
                'register' => [
                    'title' => 'Register',
                    'icon' => 'fas fa-percent',
                    'children' => [
                        ['title' => 'Driver Expense Register', 'route' => 'driver-expense.index', 'permission' => 'driver_expense.index', 'icon' => 'fa-solid fa-money-bill-1'],
                        ['title' => 'Diesel Expense Register', 'route' => 'diesel.index', 'permission' => 'diesel.list', 'icon' => 'fa-solid fa-gas-pump'],
                        ['title' => 'Multi Expense Voucher Register', 'route' => 'multi-expense.index', 'permission' => 'multi_expense_voucher.list', 'icon' => 'fa-solid fa-file-invoice'],
                        ['title' => 'Salary Voucher Register', 'route' => 'salary-module.index', 'permission' => 'salary.list', 'icon' => 'fa-solid fa-money-bill'],
                    ],
                ],
                'reports' => [
                    'title' => 'Reports',
                    'icon' => 'fas fa-percent',
                    'children' => [
                        ['title' => 'All Expense Report', 'route' => 'expense-register.index', 'permission' => 'expense_register.list', 'icon' => 'fa-solid fa-receipt'],
                        ['title' => 'Vehicle Expenditure Report', 'route' => 'vehicle-expenditure.index', 'permission' => 'vehicle_expenditure.list', 'icon' => 'fa-solid fa-chart-bar'],
                        ['title' => 'Vehicle Income Report', 'route' => 'vehicle-income.index', 'permission' => 'vehicle_income_report.list', 'icon' => 'fa-solid fa-file-invoice-dollar'],
                    ],
                ],
            ],
        ],
        [
            'title' => 'Dairy File Import',
            'icon' => 'file-import',
            'icon_size' => 20,
            'color' => '#a12d2dff',
            'bg' => 'rgba(255, 198, 198, 0.45)',
            'sections' => [
                'dairy-file-import' => [
                    'title' => 'Dairy File Import',
                    'icon' => 'fa-solid fa-minus-circle',
                    'children' => [
                        ['title' => 'Dairy File Import',          'route' => 'dairy-file-import.index',                    'permission' => 'dairy_file_import.index',          'icon' => 'fa-solid fa-file-import'],
                        ['title' => 'Dairy File Register',        'route' => 'dairy-file-import.dairy-file.register',      'permission' => 'dairy_file_import.dairy_file_list', 'icon' => 'fa-solid fa-table-list'],
                        ['title' => 'Day To Day Import Register', 'route' => 'dairy-file-import.day-to-day.register',      'permission' => 'dairy_file_import.day_to_day_list', 'icon' => 'fa-solid fa-table-list'],
                    ],
                ],
            ],
        ],

        [
            'title' => 'Setup',
            'icon' => 'setup',
            'icon_size' => 20,
            'color' => '#a12d2dff',
            'bg' => 'rgba(255, 198, 198, 0.45)',
            'sections' => [
                'setup' => [
                    'title' => 'Setup',
                    'icon' => 'fa-solid fa-minus-circle',
                    'children' => [

                        ['title' => 'GST Credentials', 'route' => 'gst-credentials.index', 'permission' => 'gst_credential.list', 'icon' => 'fa-solid fa-key'],
                        ['title' => 'Mail Config', 'route' => 'mail-config.index', 'permission' => 'mail_config.list', 'icon' => 'fa-solid fa-envelope'],
                        ['title' => 'Bank Mail Config', 'route' => 'bank-mail-config.index', 'permission' => 'mail_config.list', 'icon' => 'fa-solid fa-building-columns'],
                        ['title' => 'Company', 'route' => 'companies.edit', 'permission' => 'companies.edit', 'icon' => 'fa-solid fa-building'],
                        ['title' => 'Cheque Format', 'route' => 'cheque.index', 'permission' => 'cheque.list', 'icon' => 'fa-solid fa-money-check-alt'],
                        ['title' => 'Mail Log', 'route' => 'mail-logs.index', 'permission' => 'mail_logs.list', 'icon' => 'fa-solid fa-envelope-open-text'],
                        [
                            'title'      => 'Login User Listing',
                            'route'      => 'login-users.index',
                            'permission' => 'login_user.list',
                            'icon'       => 'fa-solid fa-user-clock',
                        ],
                    ],
                ],
            ],
        ],
    ]
];
