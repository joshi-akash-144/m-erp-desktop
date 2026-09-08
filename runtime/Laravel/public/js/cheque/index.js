let table; 
let currentPermissions = {};
let savedColWidths = {};

const WIDTH_KEY = "cheque_col_widths";

$(document).ready(function() {       
    table = new Tabulator("#cheque_table", {
        layout: "fitColumns",
        dataTree: true,
        height: "550px",
        placeholder: `
        <div class="text-center py-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" 
                viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
                class="icon text-muted mb-3">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                <path d="M10 12l4 4m0 -4l-4 4" />
            </svg>
            <h3 class="text-muted">No data found</h3>
            <p class="text-muted">Try adjusting your filters or search criteria</p>
        </div>
    `,
        pagination: true,
        paginationMode: "remote",
        paginationSize: 50,
        paginationSizeSelector: [10, 20, 50, 100, 300, true],
        paginationDataSent: {
            page: "page",
            size: "size",
        },
        paginationDataReceived: {
            last_page: "last_page",
            data: "data",
            total: "total",
        },
        ajaxURL: chequeListUrl,
        ajaxURLGenerator: (url, config, params) => {
            const qp = new URLSearchParams({
                page: params.page,
                size: params.size,
            });
            return `${url}?${qp}`;
        },
        ajaxResponse(url, params, response) {
            if (response.permissions) currentPermissions = response.permissions;           
            window.recordsInCurrentPage = response.data ? response.data.length : 0;
            return response;
        },
        columns: getTableColumns(savedColWidths),
    });



    // Delete Cheque
    $(document).on("click", ".delete", function (event) {
      const chequeId = $(this).data("id");
      const url = chequeDeleteUrl.replace(":id", chequeId);

      Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        allowOutsideClick: false, 
        allowEscapeKey: false,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "No, cancel!",
        reverseButtons: false,
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: url,
            type: "DELETE",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
              showLoader("Deleting Cheque Format...");
            },
            success: function (response) {
              showToast("success", response.message || "Cheque format deleted successfully!");
              if (table) table.replaceData();
            },
            error: function (xhr) {
              const message = xhr.responseJSON ? xhr.responseJSON.message : 'Oops! Something went wrong. Try again later.';
              Swal.fire({
                title: 'Error',
                text: message,
                icon: 'error'
              });
              console.error(xhr.responseText);
            },
            complete: function () {
              hideLoader();
            },
          });
        }
      });
    });
});

function getTableColumns(savedColWidths) {
    return [
        {
          title: "No",
          field: "no",
          width: savedColWidths.no ?? 130,
          headerSort: false,
          hozAlign: "center",
          headerHozAlign: "center",
          sorter: "number",
          resizable: true,
        },
        {
          title: "Name",
          field: "formate_name",
          width: savedColWidths.formate_name ?? 250,
          headerSort: false,
          hozAlign: "left",
          headerHozAlign: "left",
          sorter: "string",
          resizable: true,
        },
        {
          title: "Actions",
          field: "actions",
          width: 160,
          hozAlign: "center",
          headerHozAlign: "center",
          headerSort: false,
          frozen: true,
          formatter: (cell) => {
            const rowData = cell.getData();
            const canEdit = currentPermissions.update || false;
            const canDelete = currentPermissions.delete || false;
    
            const editIcon = icons.edit;
            const deleteIcon = icons.delete;
    
            let actions = '<div class="d-flex gap-2 justify-content-center">';
    
            if (canEdit) {
              const editUrl = chequeEditUrl.replace(':id', rowData.id);
              actions += `
                <a 
                  href="${editUrl}"
                  class="erp-btn-icon edit"
                  title="Edit Record">
                  ${editIcon}
                </a>`;
            }
    
            if (canDelete && !rowData.is_default) {
              actions += `
                <span 
                  class="erp-btn-icon delete"
                  data-id="${rowData.id}"
                  title="Delete Record">
                  ${deleteIcon}
                </span>`;
            }
    
            actions += "</div>";
            return actions || '<span class="text-muted">No actions</span>';
          },
        },
    ];
}
