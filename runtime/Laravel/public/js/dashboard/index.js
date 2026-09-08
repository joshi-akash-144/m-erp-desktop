document.addEventListener("DOMContentLoaded", function () {
    const initTabulators = () => {
        // 8. Recent Invoices Tabulator (AJAX)
        if (document.getElementById('recent-invoices-table')) {
            new Tabulator("#recent-invoices-table", {
                ajaxURL: recentSalesInvoice,
                layout: "fitColumns",
                responsiveLayout: "collapse",
                placeholder: "No recent invoices found",
                columnDefaults: {
                    vertAlign: "middle",
                },
                columns: [
                    {
                        title: "No",
                        formatter: "rownum",
                        hozAlign: "center",
                        widthGrow: 1,
                        headerSort: false,
                    },
                    {
                        title: "Date",
                        field: "invoice_date",
                        hozAlign: "center",
                        widthGrow: 1,
                        headerSort: false,
                        formatter: (cell) => {
                            return new Date(cell.getValue()).toLocaleDateString('en-IN');
                        }
                    },
                    {
                        title: "Customer",
                        field: "customer_name",
                        headerSort: false,
                        widthGrow: 5,
                    },
                    {
                        title: "Amount",
                        field: "grand_total",
                        widthGrow: 2,
                        headerHozAlign: "right",
                        headerSort: false,
                        hozAlign: "right",
                        formatter: (cell) => {
                            return new Intl.NumberFormat('en-IN', {
                                style: 'currency',
                                currency: 'INR',
                                maximumFractionDigits: 0
                            }).format(cell.getValue());
                        }
                    },
                    {
                        title: "Status",
                        field: "status",
                        widthGrow: 1,
                        headerHozAlign: "right",
                        headerSort: false,
                        hozAlign: "center",
                        formatter: (cell) => {
                            let value = cell.getValue();
                            return value === 'Paid'
                                ? `<i class="fa-regular fa-circle-check text-success me-1"></i>${value}`
                                : `<i class="fa-regular fa-circle-xmark text-danger me-1"></i>${value}`;
                        }
                    },
                ],
            });
        }

        // 9. Top Products Tabulator (AJAX)
        if (document.getElementById('top-products-table')) {
            new Tabulator("#top-products-table", {
                ajaxURL: topProductsRoute,
                layout: "fitColumns",
                responsiveLayout: "collapse",
                placeholder: "No top products found",
                columnDefaults: {
                    vertAlign: "middle",
                },
                columns: [
                    {
                        title: "No",
                        formatter: "rownum",
                        hozAlign: "center",
                        widthGrow: 1,
                        headerSort: false,

                    },
                    {
                        title: "Item",
                        field: "item_name",
                        sorter: "string",
                        headerSort: false,
                        widthGrow: 2,
                    },
                    {
                        title: "Qty",
                        field: "total_qty",
                        widthGrow: 1,
                        headerSort: false,
                        hozAlign: "right",
                        formatter: (cell) => {
                            const data = cell.getData();
                            return `${new Intl.NumberFormat('en-IN').format(data.total_qty)} ${data.unit}`;
                        }
                    },
                    {
                        title: "Revenue",
                        field: "total_amount",
                        headerSort: false,
                        widthGrow: 1,
                        hozAlign: "right",
                        formatter: (cell) => {
                            return new Intl.NumberFormat('en-IN', {
                                style: 'currency',
                                currency: 'INR',
                                maximumFractionDigits: 0
                            }).format(cell.getValue());
                        }
                    },
                ],
            });
        }
        // 10. Location-Wise GRN (AJAX)
        if (document.getElementById('location-grn-table')) {
            window.locationGrnTable = new Tabulator("#location-grn-table", {
                ajaxURL: locationGrnRoute + "?days=" + encodeURIComponent(window.dashboardSettings.initialDays),
                layout: "fitColumns",
                responsiveLayout: "collapse",
                placeholder: "No data available",
                columnDefaults: {vertAlign: "middle",},
                ajaxLoader: true,
                ajaxLoaderLoading: `<div class="d-flex flex-column align-items-center justify-content-center py-5 text-center" style="min-height: 250px;">
                    <div class="spinner-border text-primary mb-3" style="width: 2.75rem; height: 2.75rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-primary fw-bold fs-6">Loading records, please wait...</div>
                </div>`,
                ajaxLoaderError: `<div class="d-flex flex-column align-items-center justify-content-center py-5 text-center" style="min-height: 250px;">
                    <i class="fas fa-exclamation-circle text-danger fa-2x mb-2"></i>
                    <div class="text-danger fw-bold">Failed to load data</div>
                </div>`,
                columns: [
                    {
                        title: "No",
                        formatter: "rownum",
                        hozAlign: "center",
                        headerHozAlign: "center", 
                        width: 50,
                        headerSort: false,
                    },
                    {
                        title: "Location",
                        field: "location_name",
                        hozAlign: "left",
                        headerHozAlign: "left", 
                        sorter: "string",
                        headerSort: false,
                        // width:258,
                        formatter: (cell) => {
                            var destId = cell.getRow().getData().destination_id;
                            var val = cell.getValue();
                            return `<a href="#" class="text-primary fw-bold text-decoration-none" onclick="event.preventDefault(); window.openLocationGrnDetailsModal(${destId})">${val}</a>`;
                        }
                    },
                    {
                        title: "Total GRNs",
                        field: "total_grns",
                        // width: 100,
                        headerSort: false,
                        hozAlign: "center",
                        headerHozAlign: "center",
                        formatter: (cell) => {
                            return `<span class="badge bg-primary-lt text-primary fs-15 fw-bold px-3">${cell.getValue()}</span>`;
                        }
                    },
                ],
            });
        }
    };

    initTabulators();

    let locationGrnDetailsTable = null;
    window.openLocationGrnDetailsModal = function (destinationId) {
        if (!locationGrnDetailsRoute) return;
        const url = `${locationGrnDetailsRoute}?destination_id=${destinationId}`;

        const modalElement = document.getElementById('locationGrnDetailsModal');

        const openModalCb = () => {
            if (typeof bootstrap !== 'undefined') {
                let bsModal = bootstrap.Modal.getInstance(modalElement);
                if (!bsModal) bsModal = new bootstrap.Modal(modalElement, { backdrop: "static", keyboard: false });
                
                modalElement.addEventListener('shown.bs.modal', function () {
                    if (locationGrnDetailsTable) locationGrnDetailsTable.redraw(true);
                }, { once: true });
                
                bsModal.show();
            } else {
                $(modalElement).on('shown.bs.modal', function () {
                    if (locationGrnDetailsTable) locationGrnDetailsTable.redraw(true);
                });
                $(modalElement).modal({ backdrop: 'static', keyboard: false });
                $(modalElement).modal('show');
            }
        };

        if (!locationGrnDetailsTable) {
            openModalCb();
            locationGrnDetailsTable = new Tabulator("#location-grn-details-table", {
                ajaxURL: url,
                // layout: "fitColumns",
                // responsiveLayout: "collapse",
                placeholder: "No details found",
                height: "70vh",
                // columnDefaults: { vertAlign: "middle" },
                ajaxLoader: true,
                ajaxLoaderLoading: `<div class="d-flex flex-column align-items-center justify-content-center py-5 text-center" style="min-height: 250px;">
                    <div class="spinner-border text-primary mb-3" style="width: 2.75rem; height: 2.75rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-primary fw-bold fs-6">Loading details, please wait...</div>
                </div>`,
                ajaxLoaderError: `<div class="d-flex flex-column align-items-center justify-content-center py-5 text-center" style="min-height: 250px;">
                    <i class="fas fa-exclamation-circle text-danger fa-2x mb-2"></i>
                    <div class="text-danger fw-bold">Failed to load details</div>
                </div>`,
                columns: [
                    { 
                        title: "No", 
                        formatter: "rownum", 
                        hozAlign: "center",
                        headerHozAlign: "center", 
                        width: 80, 
                        headerSort: false 
                    },
                    { 
                        title: "GRN Number", 
                        field: "grn_number", 
                        width: 150, 
                        hozAlign: "center",
                        headerHozAlign: "center", 
                        headerSort: false 
                    },
                    { 
                        title: "Date", 
                        field: "date", 
                        width: 120, 
                        hozAlign: "center",
                        headerHozAlign: "center", 
                        headerSort: false 
                    },
                    { 
                        title: "Supplier", 
                        field: "party_name", 
                        width: 400, 
                        hozAlign: "left",
                        headerHozAlign: "left",
                        headerSort: false 
                    },
                    { 
                        title: "Destination", 
                        field: "destination_id", 
                        width: 200, 
                        hozAlign: "left",
                        headerHozAlign: "left",
                        headerSort: false,
                        formatter: "html"
                    },
                    { 
                        title: "Item", 
                        field: "item_name", 
                        width: 300, 
                        hozAlign: "left",
                        headerHozAlign: "left",
                        headerSort: false,
                        formatter: "html"
                    },                    
                    { 
                        title: "Party Quantity", 
                        field: "party_quantity", 
                        hozAlign: "right",
                        headerHozAlign: "right", 
                        width: 200, 
                        headerSort: false,
                        formatter: "html"
                    },                     
                    { 
                        title: "Qty",
                        field: "quantity", 
                        hozAlign: "right",
                        headerHozAlign: "right", 
                        width: 200, 
                        headerSort: false,
                        formatter: "html"
                    }
                ],
                ajaxResponse: function(url, params, response) {
                    return response;
                }
            });
        } else {
            openModalCb();
            locationGrnDetailsTable.clearData();
            locationGrnDetailsTable.setData(url).catch(() => {                
            });
        }
    };

    let godownTable = null;

    // Helper: show "-" for null/empty values
    const nullFmt = (cell) => {
        const val = cell.getValue();
        return (val !== null && val !== undefined && val !== '') ? val : '-';
    };

    // Helper: truncate with tooltip, show "-" for null/empty
    const truncFmt = (cell) => {
        const raw = cell.getValue();
        const val = (raw !== null && raw !== undefined && raw !== '') ? raw : '-';
        return `<div class="text-truncate w-100" title="${String(val).replace(/"/g, '&quot;')}">${val}</div>`;
    };

    window.openGodownDetails = function (status, title) {
        document.getElementById('godownDetailsModalTitle').innerText = 'Godown Details - ' + title;

        // Always read the current data-days from the godown dropdown attribute
        // This attribute is kept in sync by chart.js on every filter change.
        const godownDropdown = document.querySelector('#godown-dropdown');
        const days = godownDropdown ? (godownDropdown.getAttribute('data-days') || 'today') : 'today';

        const url = `${godownDetailsRoute}?days=${encodeURIComponent(days)}&status=${encodeURIComponent(status)}`;

        if (!godownTable) {
            godownTable = new Tabulator("#godown-details-table", {
                ajaxURL: url,
                layout: "fitColumns",
                responsiveLayout: "collapse",
                placeholder: "No details found",
                height: "100%",
                columnDefaults: { vertAlign: "middle" },
                columns: [
                    {
                        title: "No",
                        formatter: "rownum",
                        headerHozAlign: "center",
                        hozAlign: "center",
                        widthGrow: 0.5,
                        headerSort: false
                    },
                    {
                        title: "Party Name",
                        field: "party_name",
                        widthGrow: 4, 
                        headerSort: false,
                        formatter: truncFmt 
                    },
                    { 
                        title: "Party Destination", 
                        field: "party_destination", 
                        widthGrow: 2, 
                        headerSort: false,
                        formatter: truncFmt 
                    },
                    { 
                        title: "Godown", 
                        field: "godown", 
                        widthGrow: 2, 
                        headerSort: false,
                        formatter: truncFmt 
                    },
                    { 
                        title: "Rate", 
                        field: "rate", 
                        widthGrow: 1, 
                        headerHozAlign: "right", 
                        hozAlign: "right", 
                        headerSort: false,
                        formatter: nullFmt 
                    },
                    { 
                        title: "Item", 
                        field: "item", 
                        widthGrow: 2, 
                        headerHozAlign: "left", 
                        hozAlign: "left", 
                        headerSort: false,
                        formatter: truncFmt 
                    },
                    { 
                        title: "Date In", 
                        field: "date_in", 
                        widthGrow: 1, 
                        headerHozAlign: "center", 
                        hozAlign: "center", 
                        headerSort: false,
                        formatter: nullFmt 
                    },
                    { 
                        title: "Time In", 
                        field: "time_in", 
                        widthGrow: 1, 
                        headerHozAlign: "center", 
                        hozAlign: "center", 
                        headerSort: false,
                        formatter: nullFmt 
                    },
                    { 
                        title: "Date Out", 
                        field: "date_out", 
                        widthGrow: 1, 
                        headerHozAlign: "center", 
                        hozAlign: "center", 
                        headerSort: false,
                        formatter: nullFmt 
                    },
                    { 
                        title: "Time Out", 
                        field: "time_out", 
                        widthGrow: 1, 
                        headerHozAlign: "center", 
                        hozAlign: "center", 
                        headerSort: false,
                        formatter: nullFmt 
                    },

                ],
            });
        } else {
            // Refresh table data with the current filter (days) + status
            godownTable.setData(url);
        }

        const modalElement = document.getElementById('godownDetailsModal');
        if (!modalElement) return;

        if (typeof bootstrap !== 'undefined') {
            const bsModal = new bootstrap.Modal(modalElement, {
                backdrop: "static",
                keyboard: false,
            });

            modalElement.addEventListener('shown.bs.modal', function () {
                if (godownTable) godownTable.redraw(true);
            }, { once: true });

            bsModal.show();
        } else {
            $(modalElement).on('shown.bs.modal', function () {
                if (godownTable) godownTable.redraw(true);
            });
            $(modalElement).modal('show');
        }
    };

    let creditorTable = null;
    let debtorTable = null;

    window.openCreditorModal = function () {
        const url = "/dashboard/creditors";

        showLoader("Fetching Creditors Data...");
        
        if (!creditorTable) {
            creditorTable = new Tabulator("#creditor-table", {
                ajaxURL: url,
                layout: "fitColumns",
                height: "70vh",
                responsiveLayout: "collapse",
                placeholder: "No creditors found",
                columnDefaults: { vertAlign: "middle" },
                columns: [
                    { title: "No", formatter: "rownum", headerHozAlign: "center", hozAlign: "center", widthGrow: 1, headerSort: false },
                    { title: "Account Name", field: "account_name", widthGrow: 4, headerSort: false, formatter: truncFmt },
                    {
                        title: "Closing Balance", field: "closing_balance", widthGrow: 2, headerHozAlign: "right", hozAlign: "right", headerSort: false, 
                        bottomCalc: "sum",
                        bottomCalcFormatter: (cell) => {
                            let val = cell.getValue();
                            let suffix = val > 0 ? ' Dr' : (val < 0 ? ' Cr' : '');
                            return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(Math.abs(val)) + suffix;
                        },
                        formatter: (cell) => {
                            let val = cell.getValue();
                            let suffix = val > 0 ? ' Dr' : (val < 0 ? ' Cr' : '');
                            return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(Math.abs(val)) + suffix;
                        }
                    }
                ],
                ajaxResponse: function(url, params, response) {
                    if (typeof hideLoader === 'function') hideLoader();
                    return response;
                },
                ajaxError: function(xhr, textStatus, errorThrown) {
                    if (typeof hideLoader === 'function') hideLoader();
                }
            });
        } else {
            creditorTable.setData(url).then(() => {
                if (typeof hideLoader === 'function') hideLoader();
            }).catch(() => {
                if (typeof hideLoader === 'function') hideLoader();
            });
        }

        const modalElement = document.getElementById('creditorModal');
        if (typeof bootstrap !== 'undefined') {
            let bsModal = bootstrap.Modal.getInstance(modalElement);
            if (!bsModal) bsModal = new bootstrap.Modal(modalElement, { backdrop: "static", keyboard: true });
            bsModal.show();
        } else {
            $(modalElement).modal({ backdrop: 'static', keyboard: true });
            $(modalElement).modal('show');
        }
    };

    window.openDebtorModal = function () {
        const url = "/dashboard/debtors";

        showLoader("Fetching Debtors Data...");
        
        if (!debtorTable) {
            debtorTable = new Tabulator("#debtor-table", {
                ajaxURL: url,
                layout: "fitColumns",
                height: "70vh",
                responsiveLayout: "collapse",
                placeholder: "No debtors found",
                columnDefaults: { vertAlign: "middle" },
                columns: [
                    { title: "No", formatter: "rownum", headerHozAlign: "center", hozAlign: "center", widthGrow: 1, headerSort: false },
                    { title: "Account Name", field: "account_name", widthGrow: 4, headerSort: false, formatter: truncFmt },
                    {
                        title: "Closing Balance", field: "closing_balance", widthGrow: 2, headerHozAlign: "right", hozAlign: "right", headerSort: false, 
                        bottomCalc: "sum",
                        bottomCalcFormatter: (cell) => {
                            let val = cell.getValue();
                            let suffix = val > 0 ? ' Dr' : (val < 0 ? ' Cr' : '');
                            return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(Math.abs(val)) + suffix;
                        },
                        formatter: (cell) => {
                            let val = cell.getValue();
                            let suffix = val > 0 ? ' Dr' : (val < 0 ? ' Cr' : '');
                            return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(Math.abs(val)) + suffix;
                        }
                    }
                ],
                ajaxResponse: function(url, params, response) {
                    if (typeof hideLoader === 'function') hideLoader();
                    return response;
                },
                ajaxError: function(xhr, textStatus, errorThrown) {
                    if (typeof hideLoader === 'function') hideLoader();
                }
            });
        } else {
            debtorTable.setData(url).then(() => {
                if (typeof hideLoader === 'function') hideLoader();
            }).catch(() => {
                if (typeof hideLoader === 'function') hideLoader();
            });
        }

        const modalElement = document.getElementById('debtorModal');
        if (typeof bootstrap !== 'undefined') {
            let bsModal = bootstrap.Modal.getInstance(modalElement);
            if (!bsModal) bsModal = new bootstrap.Modal(modalElement, { backdrop: "static", keyboard: true });
            bsModal.show();
        } else {
            $(modalElement).modal({ backdrop: 'static', keyboard: true });
            $(modalElement).modal('show');
        }
    };

    window.printTabulator = function (tableId, title) {
        let table = tableId === 'creditor-table' ? creditorTable : debtorTable;
        if (!table) return;

        let data = table.getData("active");
        
        let html = `
        <html>
        <head>
            <title>${title}</title>
            <style>
                @media print {
                    @page { margin: 15mm; }
                    .page-break { page-break-after: always; }
                    body { padding: 0; }
                }
                body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
                h2 { text-align: center; margin-bottom: 20px; font-size: 18px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; }
                th, td { border: 1px solid #333; padding: 4px 8px; text-align: left; }
                th { background-color: #f8f9fa; }
                .text-right { text-align: right !important; }
                .text-center { text-align: center !important; }
                .fw-bold { font-weight: bold; }
                .account-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            </style>
        </head>
        <body>
        `;

        let cumulativeTotal = 0;
        let todayStr = new Intl.DateTimeFormat('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date());

        let chunks = [];
        let currentStart = 0;
        let pageIndex = 0;
        while (currentStart < data.length) {
            let limit = (pageIndex === 0) ? 25 : 28;
            chunks.push({
                items: data.slice(currentStart, currentStart + limit),
                startIndex: currentStart
            });
            currentStart += limit;
            pageIndex++;
        }
        
        const totalPages = chunks.length;

        if (data.length === 0) {
            html += `<h2>${title}</h2><h4 style="text-align:center; margin-top:-15px; font-weight:normal;">As on Date: ${todayStr}</h4><table><tr><td class="text-center">No data available to print.</td></tr></table>`;
        } else {
            for (let i = 0; i < totalPages; i++) {
                let chunkInfo = chunks[i];
                let chunk = chunkInfo.items;
                let start = chunkInfo.startIndex;
                
                let isFirstPage = (i === 0);
                let isLastPage = (i === totalPages - 1);

                if (i > 0) {
                    html += `<div class="page-break"></div>`;
                }

                html += `<h2>${title} <small style="font-size:12px; font-weight:normal;">(Page ${i + 1} of ${totalPages})</small></h2>`;
                html += `<h4 style="text-align:center; margin-top:-15px; margin-bottom:15px; font-weight:normal;">As on Date: ${todayStr}</h4>`;
                html += `<table>
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 10%">No</th>
                            <th>Account Name</th>
                            <th class="text-right" style="width: 25%">Closing Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                `;

                if (!isFirstPage) {
                    let suffix = cumulativeTotal > 0 ? ' Dr' : (cumulativeTotal < 0 ? ' Cr' : '');
                    let formattedBF = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(Math.abs(cumulativeTotal)) + suffix;
                    html += `
                        <tr>
                            <td colspan="2" class="text-right fw-bold">B/F (Brought Forward):</td>
                            <td class="text-right fw-bold">${formattedBF}</td>
                        </tr>
                    `;
                }

                let pageTotal = 0;
                chunk.forEach((row, index) => {
                    let val = parseFloat(row.closing_balance) || 0;
                    pageTotal += val;
                    cumulativeTotal += val;
                    
                    let suffix = val > 0 ? ' Dr' : (val < 0 ? ' Cr' : '');
                    let formattedVal = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(Math.abs(val)) + suffix;
                    
                    html += `
                        <tr>
                            <td class="text-center">${start + index + 1}</td>
                            <td class="account-name">${row.account_name}</td>
                            <td class="text-right">${formattedVal}</td>
                        </tr>
                    `;
                });

                let totalSuffix = cumulativeTotal > 0 ? ' Dr' : (cumulativeTotal < 0 ? ' Cr' : '');
                let formattedCumulative = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(Math.abs(cumulativeTotal)) + totalSuffix;

                let pageSuffix = pageTotal > 0 ? ' Dr' : (pageTotal < 0 ? ' Cr' : '');
                let formattedPageTotal = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(Math.abs(pageTotal)) + pageSuffix;

                html += `
                    <tr>
                        <td colspan="2" class="text-right fw-bold">Page Total:</td>
                        <td class="text-right fw-bold">${formattedPageTotal}</td>
                    </tr>
                `;

                if (!isLastPage) {
                    html += `
                        <tr>
                            <td colspan="2" class="text-right fw-bold">C/F (Carried Forward):</td>
                            <td class="text-right fw-bold">${formattedCumulative}</td>
                        </tr>
                    `;
                } else {
                    html += `
                        <tr>
                            <td colspan="2" class="text-right fw-bold">Grand Total:</td>
                            <td class="text-right fw-bold">${formattedCumulative}</td>
                        </tr>
                    `;
                }

                html += `
                    </tbody>
                </table>
                `;
            }
        }

        html += `
            <script>
                window.onload = function() { 
                    window.onafterprint = function() { window.close(); };
                    setTimeout(function() { window.print(); }, 200); 
                }
            </script>
        </body>
        </html>
        `;

        let printWin = window.open('', '_blank');
        printWin.document.open();
        printWin.document.write(html);
        printWin.document.close();
    };

    // Attach search event listeners
    const creditorSearch = document.getElementById('creditor-search');
    if (creditorSearch) {
        creditorSearch.addEventListener('input', function(e) {
            if (creditorTable) {
                creditorTable.setFilter("account_name", "like", e.target.value);
            }
        });
    }

    const debtorSearch = document.getElementById('debtor-search');
    if (debtorSearch) {
        debtorSearch.addEventListener('input', function(e) {
            if (debtorTable) {
                debtorTable.setFilter("account_name", "like", e.target.value);
            }
        });
    }

    const locationGrnDetailsSearch = document.getElementById('location-grn-details-search');
    if (locationGrnDetailsSearch) {
        locationGrnDetailsSearch.addEventListener('input', function(e) {
            if (locationGrnDetailsTable) {
                locationGrnDetailsTable.setFilter(function(data, filterParams) {
                    var val = filterParams.value.toLowerCase();
                    return String(data.grn_number || '').toLowerCase().includes(val) || 
                           String(data.party_name || '').toLowerCase().includes(val) || 
                           String(data.item_name || '').toLowerCase().includes(val);
                }, {value: e.target.value});
            }
        });
    }

    // Modal shown event listeners to redraw Tabulator and autofocus search
    const credModal = document.getElementById('creditorModal');
    if (credModal) {
        credModal.addEventListener('shown.bs.modal', function () {
            if (creditorTable) creditorTable.redraw(true);
            if (creditorSearch) creditorSearch.focus();
        });
    }

    const debModal = document.getElementById('debtorModal');
    if (debModal) {
        debModal.addEventListener('shown.bs.modal', function () {
            if (debtorTable) debtorTable.redraw(true);
            if (debtorSearch) debtorSearch.focus();
        });
    }

    const locGrnDetailsModal = document.getElementById('locationGrnDetailsModal');
    if (locGrnDetailsModal) {
        locGrnDetailsModal.addEventListener('shown.bs.modal', function () {
            // redraw is already handled in openLocationGrnDetailsModal, but autofocus:
            if (locationGrnDetailsSearch) locationGrnDetailsSearch.focus();
        });
    }

});