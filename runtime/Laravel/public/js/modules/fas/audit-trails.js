$(document).ready(function () {
    let summaryTable, detailTable;

    function initSummaryTable() {
        summaryTable = new Tabulator("#summary_output", {
            layout: "fitColumns",
            height: "65vh",
            progressiveLoad: "scroll",
            paginationMode: "remote",
            paginationSize: 50,
            ajaxURL: AUDIT_SUMMARY_URL,
            ajaxURLGenerator: function(url, config, params) {
                var page = params.page || 1;
                var size = params.size || 50;
                var formParams = $('#audit_summary_filter_form').serializeArray().reduce(function(obj, item) {
                    if (item.value) obj[item.name] = item.value;
                    return obj;
                }, {});
                var qp = new URLSearchParams($.extend({}, formParams, { page: page, size: size }));
                return url + "?" + qp.toString();
            },
            paginationDataSent: { page: "page", size: "size" },
            paginationDataReceived: { last_page: "last_page", data: "data" },
            columnDefaults: { headerSort: false },
            columns: [
                { title: "User", field: "user_name", width: 150 },
                { title: "Date", field: "date", width: 100 },
                { title: "Time", field: "time", width: 100 },
                { title: "Action", field: "action", width: 100 },
                { title: "Voucher Type", field: "voucher_type", width: 120 },
                { title: "Voucher Serial", field: "voucher_no", width: 100 },
                { title: "Ref. No", field: "reference_number", width: 100 },
                { title: "Version", field: "version", width: 80 },
                { title: "Account", field: "account_name" },
                { title: "Debit", field: "debit", width: 120, hozAlign: "right", formatter: "money" },
                { title: "Credit", field: "credit", width: 120, hozAlign: "right", formatter: "money" },
                { title: "Org. Value", field: "org_value", width: 120, hozAlign: "right", formatter: function(cell) { return cell.getValue() !== null ? parseFloat(cell.getValue()).toFixed(2) : ''; } },
                { title: "Final Value", field: "final_value", width: 120, hozAlign: "right", formatter: function(cell) { return cell.getValue() !== null ? parseFloat(cell.getValue()).toFixed(2) : ''; } },
            ],
            rowFormatter: function(row) {
                var data = row.getData();
                if(data._is_first) {
                    row.getElement().style.backgroundColor = "#f1f8ff";
                    row.getElement().style.borderTop = "2px solid #ccc";
                }
            }
        });
    }

    function initDetailTable() {
        detailTable = new Tabulator("#detail_output", {
            layout: "fitColumns",
            height: "65vh",
            progressiveLoad: "scroll",
            paginationMode: "remote",
            paginationSize: 50,
            ajaxURL: AUDIT_DETAIL_URL,
            ajaxURLGenerator: function(url, config, params) {
                var page = params.page || 1;
                var size = params.size || 50;
                var formParams = $('#audit_detail_filter_form').serializeArray().reduce(function(obj, item) {
                    if (item.value) obj[item.name] = item.value;
                    return obj;
                }, {});
                var qp = new URLSearchParams($.extend({}, formParams, { page: page, size: size }));
                return url + "?" + qp.toString();
            },
            paginationDataSent: { page: "page", size: "size" },
            paginationDataReceived: { last_page: "last_page", data: "data" },
            columnDefaults: { headerSort: false },
            columns: [
                { title: "User", field: "user_name", width: 150 },
                { title: "Date", field: "date", width: 100 },
                { title: "Time", field: "time", width: 100 },
                { title: "Action", field: "action", width: 100 },
                { title: "Version", field: "version", width: 80 },
                { title: "Voucher Type", field: "voucher_type", width: 120 },
                { title: "Voucher Serial", field: "voucher_no", width: 100 },
                { title: "Ref. No", field: "reference_number", width: 100 },
                { title: "Org. Value", field: "org_value", width: 120, hozAlign: "right", formatter: "money" },
                { title: "Final Value", field: "final_value", width: 120, hozAlign: "right", formatter: "money" },
                {
                    title: "View", 
                    field: "id", 
                    width: 70, 
                    hozAlign: "center", 
                    formatter: function() {
                        return '<button class="btn btn-sm btn-info py-0 px-2 view-version"><i class="fa-solid fa-eye"></i></button>';
                    },
                    cellClick: function(e, cell) {
                        viewVersion(cell.getValue());
                    }
                }
            ],
        });
    }

    function viewVersion(id) {
        if (typeof showLoader === 'function') {
            showLoader("Loading version comparison...");
        }

        $.ajax({
            url: AUDIT_SHOW_URL + '/' + id,
            type: 'GET',
            success: function(response) {
                if (typeof hideLoader === 'function') {
                    hideLoader(0);
                }
                
                if(response.success) {
                    var data = response;
                    
                    // Destroy old instance if exists
                    if (window.comparisonTabulator) {
                        window.comparisonTabulator.destroy();
                    }

                    var columns = [
                        { title: "Field", field: "field", frozen: true, headerSort: false, width: 250 }
                    ];

                    data.versions.forEach(function(v) {
                        columns.push({
                            title: "Version " + v,
                            field: "v_" + v,
                            headerSort: false,
                            minWidth: 200,
                            formatter: function(cell) {
                                var valObj = cell.getValue();
                                if (!valObj) return '<span class="text-muted opacity-50">—</span>';
                                
                                var cellValue = valObj.value ? valObj.value : '<span class="text-muted opacity-50">—</span>';
                                
                                if (valObj.changed) {
                                    cell.getElement().classList.add('cell-changed');
                                    return `
                                        <span class="change-label">Changed</span>
                                        <div class="version-val">${cellValue}</div>
                                    `;
                                } else {
                                    return `
                                        <div class="version-val">${cellValue}</div>
                                    `;
                                }
                            }
                        });
                    });

                    var tableData = data.rows.map(function(row) {
                        var rowData = { field: row.field };
                        data.versions.forEach(function(v) {
                            rowData["v_" + v] = row.values[v];
                        });
                        return rowData;
                    });

                    window.comparisonTabulator = new Tabulator("#version_comparison_tabulator", {
                        data: tableData,
                        layout: "fitColumns",
                        columns: columns,
                        height: "65vh",
                    });

                    $('#versionComparisonModal').modal('show');
                    
                    // Redraw tabulator after modal is shown to fix column widths
                    $('#versionComparisonModal').on('shown.bs.modal', function () {
                        if (window.comparisonTabulator) {
                            window.comparisonTabulator.redraw();
                        }
                    });
                }
            },
            error: function(xhr) {
                if (typeof hideLoader === 'function') {
                    hideLoader(0);
                }
                console.error("Error fetching version comparison:", xhr);
            }
        });
    }

    // Initialize both tables
    initSummaryTable();
    initDetailTable();

    // Summary Form Submit
    $('#audit_summary_filter_form').on('submit', function (e) {
        e.preventDefault();
        summaryTable.setData();
    });

    $('#summary_filter_clear').on('click', function() {
        $('#audit_summary_filter_form')[0].reset();
        summaryTable.setData();
    });

    // Detail Form Submit
    $('#audit_detail_filter_form').on('submit', function (e) {
        e.preventDefault();
        detailTable.setData();
    });

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        // Redraw tables when tab becomes visible to fix layout issues
        if(e.target.getAttribute('href') === '#tab-summary') {
            summaryTable.redraw();
        } else {
            detailTable.redraw();
        }
    });
});
