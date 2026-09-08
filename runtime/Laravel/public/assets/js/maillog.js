/**
 * Mail Logs — maillog.js
 * Tabulator-based listing
 */

document.addEventListener('DOMContentLoaded', function () {
    var table = new Tabulator("#mail-logs-table", {
        layout: "fitDataStretch",
        // responsiveLayout: "collapse",
        ajaxURL: mailLogsDataUrl,
        ajaxConfig: 'GET',
        ajaxParams: function() {
            return {
                company_id: window.COMPANY_ID || null,
                financial_year_id: window.FINANCIAL_YEAR_ID || null
            };
        },
        ajaxResponse: function(url, params, response) {
            return response.data || response; // Support both {data: [...]} and [...]
        },
        pagination: "local",
        paginationSize: 50,
        paginationSizeSelector: [20, 50, 100, 500],
        placeholder: "No email logs found.",
        height: "60vh",
        columnCalcs: 'both',
        columns: [
            { title: "ID", field: "id", width: 70, hozAlign: 'center', sorter: "number", headerSort: true },
            { title: "Sent At", field: "sent_at", width: 190, headerSort: true },
            { title: "Sender (User)", field: "user", width: 140, headerSort: true },
            { title: "Recipient", field: "recipient", minWidth: 220, headerSort: true },
            { title: "Subject", field: "subject", minWidth: 250, headerSort: true },
            { title: "Status", field: "status", width: 120, hozAlign: 'center', headerSort: true, formatter: function(cell) {
                var value = cell.getValue();
                if(value === 'sent') {
                    return "<span class='badge bg-success-lt text-uppercase px-2'>Sent</span>";
                } else if(value === 'failed') {
                    return "<span class='badge bg-danger-lt text-uppercase px-2'>Failed</span>";
                }
                return "<span class='badge bg-secondary-lt text-uppercase px-2'>" + value + "</span>";
            }},
            { title: "Error Message", field: "error_message", minWidth: 300, headerSort: false, formatter: function(cell) {
                var msg = cell.getValue();
                return msg ? '<span class="text-danger small">' + msg + '</span>' : '—';
            }}
        ]
    });
});
