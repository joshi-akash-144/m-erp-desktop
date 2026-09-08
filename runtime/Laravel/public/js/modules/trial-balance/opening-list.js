// Opening Trial Balance — Tabulator module
/* global otbIndexUrl, otbPrintUrl, otbExcelUrl, otbSelectGroupUrl, otbGroupWiseUrl, otbGroupPrintUrl, otbGroupExcelUrl */
let otbTable;
let otbViewMode = "group";

let gwTable;

function fmtDate(iso) {
  if (!iso) return "";
  const p = iso.split("-");
  return p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : iso;
}

$(function () {
  otbLoadData();

  $("#otb_print_btn").on("click", (e) => {
    e.preventDefault();
    otbPrint();
  });
  $("#otb_excel_btn").on("click", (e) => {
    e.preventDefault();
    otbExport();
  });

  // View mode toggle
  $("#otb_filter_group").on("click", function () {
    if (otbViewMode === "group") return;
    otbViewMode = "group";
    $("#otb_filter_group")
      .removeClass("btn-outline-primary")
      .addClass("btn-primary");
    $("#otb_filter_account")
      .removeClass("btn-primary")
      .addClass("btn-outline-primary");
    $("#otb_search").val("");
    $("#otb_search_clear").hide();
    if (otbTable) {
      otbTable.destroy();
      otbTable = null;
    }
    otbLoadData();
  });

  $("#otb_filter_account").on("click", function () {
    if (otbViewMode === "account") return;
    otbViewMode = "account";
    $("#otb_filter_account")
      .removeClass("btn-outline-primary")
      .addClass("btn-primary");
    $("#otb_filter_group")
      .removeClass("btn-primary")
      .addClass("btn-outline-primary");
    $("#otb_search").val("");
    $("#otb_search_clear").hide();
    if (otbTable) {
      otbTable.destroy();
      otbTable = null;
    }
    otbLoadData();
  });

  $("#gw_print_btn").on("click", (e) => {
    e.preventDefault();
    gwPrint();
  });
  $("#gw_excel_btn").on("click", (e) => {
    e.preventDefault();
    gwExport();
  });

  // Main table search
  $("#otb_search").on("input", function () {
    const q = $(this).val().trim();
    $("#otb_search_clear").toggle(q.length > 0);
    if (!otbTable) return;
    if (!q) {
      otbTable.clearFilter();
      return;
    }
    if (otbViewMode === "account") {
      otbTable.setFilter(
        function (data, p) {
          const s = p.q;
          return (
            (data.account_name || "").toLowerCase().includes(s) ||
            (data.account_group_name || "").toLowerCase().includes(s)
          );
        },
        { q: q.toLowerCase() },
      );
    } else {
      otbTable.setFilter("account_group_name", "like", q);
    }
  });

  $("#otb_search_clear").on("click", function () {
    $("#otb_search").val("").trigger("input");
  });

  $("#gw_search").on("input", function () {
    if (!gwTable) return;
    const q = $(this).val().toLowerCase().trim();
    q ? gwTable.setFilter("account_name", "like", q) : gwTable.clearFilter();
  });

  $("#group_wise_modal").on("hidden.bs.modal", function () {
    $("#gw_search").val("");
    if (gwTable) gwTable.clearFilter();
  });
});

/* ------------------------------------------------------------------ */
/* LOAD                                                                 */
/* ------------------------------------------------------------------ */
function otbLoadData() {
  showLoader("Loading opening trial balance…");

  $.ajax({
    url: otbIndexUrl,
    method: "GET",
    data: { view_type: otbViewMode },
    success(res) {
      hideLoader();
      const payload = res.data || {};
      const rows = payload.data || [];
      const gt = payload.grand_total || {};
      otbRenderDiff((gt.total_debit || 0) - (gt.total_credit || 0));
      if (otbViewMode === "account") {
        otbBuildAccountTable(rows);
      } else {
        otbBuildGroupTable(rows);
      }
    },
    error(xhr) {
      hideLoader();
      Swal.fire({
        icon: "error",
        title: "Error",
        text: xhr.responseJSON?.message || "Failed to load data.",
      });
    },
  });
}

/* ------------------------------------------------------------------ */
/* GROUP WISE TABLE                                                      */
/* ------------------------------------------------------------------ */
function otbBuildGroupTable(rows) {
  if (otbTable) {
    otbTable.setData(rows);
    return;
  }

  otbTable = new Tabulator("#opening_trial_balance_table", {
    data: rows,
    height: "500px",
    layout: "fitColumns",
    placeholder: `
            <div class="text-center py-5">
                <i class="fa-solid fa-scale-balanced fa-3x text-muted mb-3"></i>
                <h3 class="text-muted">No opening balance data found</h3>
            </div>`,
    rowFormatter(row) {
      row.getElement().style.cursor = "pointer";
    },
    columns: [
      {
        title: "Sr No.",
        formatter: "rownum",
        width: 70,
        hozAlign: "center",
        headerHozAlign: "center",
        headerSort: false,
      },
      {
        title: "Account Group",
        field: "account_group_name",
        minWidth: 300,
        headerSort: false,
        formatter: (cell) => {
          cell.getElement().setAttribute("title", cell.getValue() || "");
          return `<span class="text-primary fw-semibold">${cell.getValue() || ""}</span>`;
        },
      },
      {
        title: "Debit (₹)",
        field: "debit",
        width: 180,
        hozAlign: "right",
        headerHozAlign: "right",
        headerSort: false,
        formatter: (cell) => {
          const b = cell.getRow().getData().balance;
          return formatIndianNumber(b >= 0 ? b : 0);
        },
        bottomCalc: (_v, data) =>
          data.reduce((s, r) => s + (r.balance >= 0 ? r.balance : 0), 0),
        bottomCalcFormatter: (cell) =>
          `<strong>${formatIndianNumber(cell.getValue() || 0)}</strong>`,
      },
      {
        title: "Credit (₹)",
        field: "credit",
        width: 180,
        hozAlign: "right",
        headerHozAlign: "right",
        headerSort: false,
        formatter: (cell) => {
          const b = cell.getRow().getData().balance;
          return formatIndianNumber(b < 0 ? Math.abs(b) : 0);
        },
        bottomCalc: (_v, data) =>
          data.reduce(
            (s, r) => s + (r.balance < 0 ? Math.abs(r.balance) : 0),
            0,
          ),
        bottomCalcFormatter: (cell) =>
          `<strong>${formatIndianNumber(cell.getValue() || 0)}</strong>`,
      },
    ],
  });

  otbTable.on("rowClick", function (_e, row) {
    const data = row.getData();
    otbOpenGroupWise(data.account_group_id, data.account_group_name);
  });
}

/* ------------------------------------------------------------------ */
/* ACCOUNT WISE TABLE                                                    */
/* ------------------------------------------------------------------ */
function otbBuildAccountTable(rows) {
  if (otbTable) {
    otbTable.setData(rows);
    return;
  }

  otbTable = new Tabulator("#opening_trial_balance_table", {
    data: rows,
    height: "500px",
    layout: "fitColumns",
    placeholder: `
            <div class="text-center py-5">
                <i class="fa-solid fa-scale-balanced fa-3x text-muted mb-3"></i>
                <h3 class="text-muted">No opening balance data found</h3>
            </div>`,
    columns: [
      {
        title: "Sr No.",
        formatter: "rownum",
        width: 70,
        hozAlign: "center",
        headerHozAlign: "center",
        headerSort: false,
      },
      {
        title: "Account Name",
        field: "account_name",
        minWidth: 240,
        headerSort: true,
        formatter: (cell) => {
          cell.getElement().setAttribute("title", cell.getValue() || "");
          return `<span class="fw-semibold">${cell.getValue() || ""}</span>`;
        },
      },
      {
        title: "Account Group",
        field: "account_group_name",
        minWidth: 180,
        headerSort: true,
        formatter: (cell) => {
          cell.getElement().setAttribute("title", cell.getValue() || "");
          return `<span class="text-muted">${cell.getValue() || ""}</span>`;
        },
      },
      {
        title: "Debit (₹)",
        field: "debit",
        width: 160,
        hozAlign: "right",
        headerHozAlign: "right",
        headerSort: false,
        formatter: (cell) => {
          const b = cell.getRow().getData().balance;
          return formatIndianNumber(b >= 0 ? b : 0);
        },
        bottomCalc: (_v, data) =>
          data.reduce((s, r) => s + (r.balance >= 0 ? r.balance : 0), 0),
        bottomCalcFormatter: (cell) =>
          `<strong>${formatIndianNumber(cell.getValue() || 0)}</strong>`,
      },
      {
        title: "Credit (₹)",
        field: "credit",
        width: 160,
        hozAlign: "right",
        headerHozAlign: "right",
        headerSort: false,
        formatter: (cell) => {
          const b = cell.getRow().getData().balance;
          return formatIndianNumber(b < 0 ? Math.abs(b) : 0);
        },
        bottomCalc: (_v, data) =>
          data.reduce(
            (s, r) => s + (r.balance < 0 ? Math.abs(r.balance) : 0),
            0,
          ),
        bottomCalcFormatter: (cell) =>
          `<strong>${formatIndianNumber(cell.getValue() || 0)}</strong>`,
      },
    ],
  });
  // No rowClick — account level has no drill-down
}

/* ------------------------------------------------------------------ */
/* DIFFERENCE BAR                                                        */
/* ------------------------------------------------------------------ */
function otbRenderDiff(diff) {
  if (diff === 0) {
    $("#otb_diff_bar").hide();
    return;
  }
  const abs = Math.abs(diff);
  const isDr = diff > 0;
  $("#otb_diff_amount").text(formatIndianNumber(abs));
  $("#otb_diff_label")
    .text(isDr ? "Dr" : "Cr")
    .removeClass("text-primary text-danger")
    .addClass(isDr ? "text-primary" : "text-danger");
  $("#otb_diff_bar").show();
}

/* ------------------------------------------------------------------ */
/* PRINT / EXPORT                                                        */
/* ------------------------------------------------------------------ */
function otbPrint() {
    printReport(otbPrintUrl, { format: "print", orientation: "portrait", view_type: otbViewMode })
    return
}

function otbExport() {
    downloadExcel(otbExcelUrl, { view_type: otbViewMode })
}

/* ------------------------------------------------------------------ */
/* GROUP WISE MODAL                                                      */
/* ------------------------------------------------------------------ */
function otbOpenGroupWise(groupId, groupName) {
  showLoader("Loading group data…");

  $.ajax({
    url: otbSelectGroupUrl,
    method: "POST",
    headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
    data: { group_id: groupId },
    success() {
      gwLoadData(groupName);
    },
    error(xhr) {
      hideLoader();
      Swal.fire({
        icon: "error",
        title: "Error",
        text: xhr.responseJSON?.message || "Failed to select group.",
      });
    },
  });
}

function gwLoadData(groupName) {
  $.ajax({
    url: otbGroupWiseUrl,
    method: "GET",
    success(res) {
      hideLoader();
      if (!res.success) {
        Swal.fire({
          icon: "error",
          title: "No Data",
          text: res.message || "No accounts found in this group.",
        });
        return;
      }

      const data = res.data || {};
      const rows = data.data || [];
      const totals = data.grand_total || { total_debit: 0, total_credit: 0 };

      $("#gw_modal_title").text(
        groupName || data.group_name || "Group Wise Trial Balance",
      );

      const dr = data.date_range || {};
      if (dr.start_date) {
        $("#gw_date_text").text(`As on: ${fmtDate(dr.start_date)}`);
        $("#gw_date_badge").show();
      } else {
        $("#gw_date_badge").hide();
      }

      gwBuildTable(rows);

      const netBalance = (totals.total_debit || 0) - (totals.total_credit || 0);
      const isDr = netBalance >= 0;
      $("#gw_footer_group_name").text(groupName || data.group_name || "—");
      $("#gw_total_debit").text(formatIndianNumber(totals.total_debit || 0));
      $("#gw_total_credit").text(formatIndianNumber(totals.total_credit || 0));
      $("#gw_net_balance")
        .text(
          `${formatIndianNumber(Math.abs(netBalance))} ${isDr ? "Dr" : "Cr"}`,
        )
        .removeClass("text-primary text-danger")
        .addClass(isDr ? "text-primary" : "text-danger");
      $("#gw_totals_bar").css("display", "block");

      $("#group_wise_modal").modal("show");
    },
    error(xhr) {
      hideLoader();
      Swal.fire({
        icon: "error",
        title: "Error",
        text: xhr.responseJSON?.message || "Failed to load group data.",
      });
    },
  });
}

function gwBuildTable(rows) {
  gwTable = new Tabulator("#group_wise_trial_balance_table", {
    data: rows,
    layout: "fitColumns",
    height: "400px",
    placeholder: `
            <div class="text-center py-4">
                <i class="fa-solid fa-folder-open fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No accounts found in this group</p>
            </div>`,
    columns: [
      {
        title: "Sr No.",
        formatter: "rownum",
        width: 70,
        hozAlign: "center",
        headerHozAlign: "center",
        headerSort: false,
      },
      {
        title: "Account Name",
        field: "account_name",
        minWidth: 280,
        headerSort: false,
        formatter: (cell) => {
          cell.getElement().setAttribute("title", cell.getValue() || "");
          return cell.getValue() || "";
        },
      },
      {
        title: "Debit (₹)",
        field: "debit",
        width: 160,
        hozAlign: "right",
        headerHozAlign: "right",
        headerSort: false,
        formatter: (cell) => {
          const b = cell.getRow().getData().balance;
          return formatIndianNumber(b >= 0 ? b : 0);
        },
        bottomCalc: (_v, data) =>
          data.reduce((s, r) => s + (r.balance >= 0 ? r.balance : 0), 0),
        bottomCalcFormatter: (cell) => formatIndianNumber(cell.getValue() || 0),
      },
      {
        title: "Credit (₹)",
        field: "credit",
        width: 160,
        hozAlign: "right",
        headerHozAlign: "right",
        headerSort: false,
        formatter: (cell) => {
          const b = cell.getRow().getData().balance;
          return formatIndianNumber(b < 0 ? Math.abs(b) : 0);
        },
        bottomCalc: (_v, data) =>
          data.reduce(
            (s, r) => s + (r.balance < 0 ? Math.abs(r.balance) : 0),
            0,
          ),
        bottomCalcFormatter: (cell) => formatIndianNumber(cell.getValue() || 0),
      },
    ],
  });
}

/* ------------------------------------------------------------------ */
/* GROUP WISE PRINT / EXPORT                                            */
/* ------------------------------------------------------------------ */
function gwPrint() {
     printReport(otbGroupPrintUrl, { format: "print", orientation: "portrait" })
}

function gwExport() {
    downloadExcel(otbGroupExcelUrl)
}
