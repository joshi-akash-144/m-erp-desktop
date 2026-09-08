// import { defineConfig } from "vite";
// import laravel from "laravel-vite-plugin";
// import path from "path";

// export default defineConfig({
//   plugins: [
//     laravel({
//       input: [
//         // Core CSS & JS
//         "resources/css/core/app.css",
//         "resources/js/core/app.js",
//         "resources/js/core/company-selection-init.js",
//         "resources/js/core/company-init.js",
//         "resources/js/core/modal-loader.js",

//         //Theme & layout CSS & JS
//         "resources/css/core/layout.css",
//         "resources/css/core/theme.css",
//         "resources/js/core/theme.js",

//         //custom CSS
//         "resources/css/core/custom.css",
//         "resources/css/modules/index.css",
//         // "resources/css/core/select2_view.css",

//         //Start Master
//         //Account Group
//         "resources/js/modules/masters/account-group/index.js",
//         "resources/js/modules/masters/account-group/modal.js",
//         //Unit
//         "resources/js/modules/masters/unit/index.js",
//         "resources/js/modules/masters/unit/modal.js",
//         // Bank Configuration
//         "resources/js/modules/masters/bank-configuration/index.js",
//         "resources/js/modules/masters/bank-configuration/modal.js",
//         // Destination
//         "resources/js/modules/masters/destination/index.js",
//         "resources/js/modules/masters/destination/modal.js",

//         // Godown
//         "resources/js/modules/masters/godown/index.js",
//         "resources/js/modules/masters/godown/modal.js",

//         // tax Category
//         "resources/js/modules/masters/tax-category/index.js",
//         "resources/js/modules/masters/tax-category/modal.js",

//         // Payee Category
//         "resources/js/modules/masters/payee-category/index.js",
//         "resources/js/modules/masters/payee-category/modal.js",

//         // Tds Category
//         "resources/js/modules/masters/tds-category/index.js",
//         "resources/js/modules/masters/tds-category/modal.js",

//         // Broker
//         "resources/js/modules/masters/broker/index.js",
//         "resources/js/modules/masters/broker/modal.js",

//         // condition
//         "resources/js/modules/masters/condition/index.js",
//         "resources/js/modules/masters/condition/modal.js",

//         //Dairy Parameter
//         "resources/js/modules/masters/dairy-parameter/index.js",
//         "resources/js/modules/masters/dairy-parameter/modal.js",

//         // Element
//         "resources/js/modules/masters/element/index.js",
//         "resources/js/modules/masters/element/modal.js",

//         // Item
//         "resources/js/modules/masters/item/index.js",
//         "resources/js/modules/masters/item/modal.js",

//         // Item Group
//         "resources/js/modules/masters/item-group/index.js",
//         "resources/js/modules/masters/item-group/modal.js",

//         // Purchase Type
//         "resources/js/modules/masters/purchase-type/index.js",
//         "resources/js/modules/masters/purchase-type/modal.js",

//         // Sale Type
//         "resources/js/modules/masters/sale-type/index.js",
//         "resources/js/modules/masters/sale-type/modal.js",

//         // Unit Conversion
//         "resources/js/modules/masters/unit-conversion/index.js",
//         "resources/js/modules/masters/unit-conversion/modal.js",

//         //Account
//         "resources/js/modules/masters/account/index.js",
//         "resources/js/modules/masters/account/modal.js",

//         //Bill Sundry
//         "resources/js/modules/masters/bill-sundry/index.js",
//         "resources/js/modules/masters/bill-sundry/modal.js",

//         //End Master

//         //User
//         "resources/js/modules/company-selection/user/index.js",
//         "resources/js/modules/company-selection/user/form.js",
//         "resources/js/modules/company-selection/profile/change-password.js",

//         // Role Management
//         "resources/js/modules/company-selection/roles/index.js",
//         "resources/js/modules/company-selection/roles/form.js",

//         // States
//         "resources/js/modules/company-selection/state/index.js",
//         "resources/js/modules/company-selection/state/form.js",

//         // Module CSS & JS
//         // "resources/js/modules/print-report.js",
//         // "resources/js/modules/export-report.js",
//         "resources/css/modules/auth/login.css",
//         "resources/js/modules/auth/login.js",

//         "resources/js/modules/company/index.js",

//         //Ledger
//         "resources/js/modules/ledger/index.js",

//         //Payment Payable 
//         "resources/js/modules/payment-payable/index.js",

//         //Purchase Order 
//         'resources/js/modules/purchase-order/index.js',
//         'resources/js/modules/purchase-order/create.js',
//         'resources/js/modules/purchase-order/purchase-order-detail-with-grn.js',

//         //Grn
//         'resources/js/modules/grn/index.js',
//         'resources/js/modules/grn/create.js',

//         //Purchase Invoice
//         'resources/js/modules/purchase-invoice/index.js',
//         'resources/js/modules/purchase-invoice/create.js',

//         //Sales Invoice
//         'resources/js/modules/sales-invoice/index.js',
//         'resources/js/modules/sales-invoice/create.js',

//         //Sales Order
//         'resources/js/modules/sales-order/index.js',
//         'resources/js/modules/sales-order/create.js',

//         //Journal
//         'resources/js/modules/journal-voucher/index.js',
//         'resources/js/modules/journal-voucher/create.js',

//         //Stock
//         'resources/js/modules/stock/index.js',

//         //All View Modal Css
//         'resources/css/core/view.css',
//       ],
//       refresh: true,
//     }),
//   ],
//   resolve: {
//     alias: {
//       "@": path.resolve(__dirname, "resources/js"),
//       "@css": path.resolve(__dirname, "resources/css"),
//       "@core": path.resolve(__dirname, "resources/js/core"),
//       "@utils": path.resolve(__dirname, "resources/js/utils"),
//       "@modules": path.resolve(__dirname, "resources/js/modules"),
//       "@constants": path.resolve(__dirname, "resources/js/constants"),
//       "@services": path.resolve(__dirname, "resources/js/services"),
//       "@shortcuts": path.resolve(__dirname, "resources/js/shortcuts"),
//     },
//   },
//   server: {
//     watch: {
//       usePolling: true,
//       interval: 500,
//       ignored: [
//         "**/public/**",
//         "**/storage/**",
//         "**/bootstrap/**",
//         "**/vendor/**",
//       ],
//     },
//     // host: true,
//     port: 5173,
//   },

//   build: {
//     cssCodeSplit: true,
//     // target: 'es2015',
//     // minify: 'esbuild',
//     // // ✅ Preserve class names and function names
//     // esbuild: {
//     //     keepNames: true,
//     // },
//   },

//   // // ✅ Optimize dependencies
//   // optimizeDeps: {
//   //     include: ['just-validate'],
//   //     esbuildOptions: {
//   //         keepNames: true,
//   //     },
//   // },
// });
