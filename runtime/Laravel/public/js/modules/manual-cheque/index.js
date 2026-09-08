const deleteUrl = (id) => `${manualChequeConfig.routes.base}/${id}`;

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

let filterParams = {};

// -----------------------------------------------------------------------
// Tabulator Table
// -----------------------------------------------------------------------
const table = new Tabulator('#manual_cheque_table', {
    height: "calc(90vh - 300px)",
    ajaxURL: manualChequeConfig.routes.index,
    ajaxParams: () => ({ ajax: true, ...filterParams }),
    ajaxConfig: {
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    },
    ajaxResponse(url, params, response) {
        return { data: response.data ?? [], last_page: response.last_page ?? 1 };
    },
    // layout: 'fitColumns',
    pagination: true,
    paginationMode: 'remote',
    paginationSize: 15,
    paginationSizeSelector: [15, 25, 50],
    responsiveLayout: 'collapse',
    placeholder: `
        <div class="text-center text-muted py-5">
        <i class="fa-solid fa-money-check-dollar fa-2x mb-2 opacity-25"></i>
        <br>No manual cheque entries found</div>`,
    columns: [
        {
            title: '#', 
            formatter: 'rownum', 
            width: 10,
            hozAlign: 'center', 
            headerHozAlign: 'center', 
            headerSort: false,
        },
        {
            title: 'Name', 
            field: 'name', 
            minWidth: 350,   
            headerHozAlign: 'center', 
            headerSort: false,         
        },

        {
            title: 'Amount', 
            field: 'amount', 
            minWidth: 150,
            hozAlign: 'right', 
            headerHozAlign: 'right',
            headerSort: false,
            bottomCalc: "sum",
            bottomCalcFormatter: function(cell) {
                return parseFloat(cell.getValue() || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            formatter: function(cell) {
                return parseFloat(cell.getValue() || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        },

        {
            title: 'Cheque No', 
            field: 'cheque_no', 
            minWidth: 100,
            headerSort: false,            
        },
         {
            title: 'Cheque Date', 
            field: 'cheque_date', 
            minWidth: 100,
            headerSort: false,  
            formatter: function(cell) {
                const value = cell.getValue();
                if (!value) return '';
                const date = new Date(value);
                return date.toLocaleDateString('en-GB'); // DD/MM/YYYY
            }          
        },
        {
            title: 'Cheque Format', 
            field: 'cheque_format_id', 
            minWidth: 150,
            headerSort: false,  
            formatter: (cell) => {
                const row = cell.getRow().getData();
                return row.cheque_format ? row.cheque_format.formate_name : '';
            },         
        },
        {
            title: 'Account Payee', 
            field: 'account_payee', 
            width: 130,
            hozAlign: 'center', 
            headerHozAlign: 'center', 
            headerSort: false,  
            formatter: (cell) => {
                const value = cell.getValue();
                if (value == 1) {
                    return `<span class="bg-success-lt px-3 py-1 rounded">Yes</span>`;
                } else {
                    return `<span class="bg-danger-lt px-3 py-1 rounded">No</span>`;
                }
            },         
        },
        {
            title: 'Narration',
            field: 'narration',
            minWidth: 300,
            headerSort: false,
        },
        {
            title: 'Second Narration',
            field: 'second_narration',
            minWidth: 300,
            headerSort: false,
        },
        {
            title: 'Action',
            field: 'id',
            width: 120,
            hozAlign: 'center',
            headerHozAlign: 'center',
            headerSort: false,
            formatter: (cell) => {
                const id = cell.getValue();
                return `
                    <button class="btn-icon-action edit" title="Print" onclick="printCheque(${id})">
                        <i class="fa-solid fa-print"></i>
                    </button>
                    <button class="btn-icon-action del" title="Delete" onclick="confirmDelete(${id})">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                `;
            },
        },
    ],
});

// -----------------------------------------------------------------------
// Filters
// -----------------------------------------------------------------------
document.getElementById('filter_apply').addEventListener('click', () => {
    filterParams = {
        search: document.getElementById('filter_search').value,
    };
    table.replaceData();
});

document.getElementById('filter_clear').addEventListener('click', () => {
    document.getElementById('filter_search').value = '';
    filterParams = {};
    table.replaceData();
});


// -----------------------------------------------------------------------
// Print
// -----------------------------------------------------------------------
function printCheque(id) {
    const printUrl = `${manualChequeConfig.routes.base}/${id}/print`;
    fetch(printUrl, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(json => {
        if (json.status && json.html) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(json.html);
            printWindow.document.close();
            printWindow.focus();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: json.message || 'Unable to print cheque.' });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while printing.' });
    });
}

// -----------------------------------------------------------------------
// Delete
// -----------------------------------------------------------------------
function confirmDelete(id) {
    Swal.fire({
        title: 'Delete this cheque entry?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        confirmButtonColor: '#d63939',
    }).then(res => {
        if (!res.isConfirmed) return;
        fetch(deleteUrl(id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(json => {
            if (json.status) {
                table.replaceData();
                Swal.fire({ icon: 'success', title: 'Deleted!', text: json.message, timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: json.message });
            }
        });
    });
}


