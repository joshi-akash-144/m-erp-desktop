const notyf = new Notyf();

const showToast = (type = "success", message = "", duration = 3000) => {
  const notyf = new Notyf({
    duration: duration,
    position: { x: "center", y: "top" },
    dismissible: true,
    ripple: true,
  });

  switch (type) {
    case "success":
      notyf.success(message);
      break;

    case "error":
      notyf.error(message);
      break;

    case "info":
      notyf.open({
        type: "info",
        message: message,
        background: "#228be6",
      });
      break;

    case "warning":
      notyf.open({
        type: "warning",
        message: message,
        background: "#f59f00",
      });
      break;

    default:
      notyf.open({
        type: type,
        message: message,
      });
  }
};

// Show loader with optional message
window.showLoader = function (message = "Saving data, please wait...") {
  document.body.classList.add("loading");
  const loaderText = document.querySelector(".loader-text");
  const loaderContainer = document.querySelector(".loader-content");
  if (loaderText) loaderText.textContent = message;
  if (loaderContainer) loaderContainer.style.display = "flex";
};

// Hide loader with optional delay (default 300ms)
window.hideLoader = function (delay = 300) {
  setTimeout(() => {
    document.body.classList.remove("loading");
    const loaderContainer = document.querySelector(".loader-content");
    if (loaderContainer) loaderContainer.style.display = "none";
  }, delay);
};

$(document).ready(function () {
  document.body.addEventListener("click", function (e) {
    if (e.target.closest(".exit-company-btn")) {
      e.preventDefault();
      showExitCompanyModal();
    }
  });

  document.addEventListener("keydown", function (e) {
    if (e.ctrlKey && (e.key === "m" || e.key === "M")) {
      e.preventDefault();

      const btn = document.querySelector(".exit-company-btn");

      if (btn) btn.click();
    }
  });

  // Enter Event
  $("body").on("keydown", "input, select", function (e) {
    if (e.key === "Enter") {
      var self = $(this),
        form = self.parents("form:eq(0)"),
        focusable,
        next;
      // console.log(form);

      focusable = form
        .find("input,a,select,button")
        .filter(":input:not([readonly])")
        .filter(":input:not([disabled])")
        .filter(":input:not([type=hidden])")
        .filter(":not(.non-selectable)")
        .filter(function () {
          return $(this).closest(".d-none").length === 0;
        });
      // .filter(":not(button.skip-btn)");

      next = focusable.eq(focusable.index(this) + 1);
      if (next.length) {
        next.focus();
      } else {
        form.submit();
      }
      return false;
    }
  });

  // select text on focus
  $(document).on("focus", ".form-control", function () {
    let type = $(this).attr("type");
    if (["text", "number", "email"].includes(type) && $(this).val() !== "") {
      this.select();
    }
  });

});

// ===========================================
// GLOBAL AJAX SETUP FOR ERP
// ===========================================
$.ajaxSetup({
  headers: {
    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
  },
  beforeSend: function () {
    // showLoader();
    // console.log("add loader hear");
    // TODO add loader hear
  },
  complete: function () {
    // hideLoader();
    // TODO hide loader hear
  },
  error: function (xhr) {
    // TODO hide loader hear
    handleAjaxError(xhr);
  },
});

// ===========================================
// ERROR HANDLER
// ===========================================
function handleAjaxError(xhr) {
  let message = "Something went wrong!";

  if (xhr.status === 0 || xhr.statusText === "abort") {
    return; // Aborted/cancelled request (e.g. Select2 debounce) — ignore silently
  } else if (xhr.status === 404) {
    message = "Requested page not found (404).";
  } else if (xhr.status === 500) {
    message = xhr.responseJSON?.errors || xhr.responseJSON?.message || "Internal Server Error (500).";
  } else if (xhr.status === 422) {
    let errors = xhr.responseJSON.errors;
    $.each(errors, function (key, val) {
      message = val[0];
      return false;
    });
  } else if (xhr.responseJSON && xhr.responseJSON.message) {
    message = xhr.responseJSON.message;
  }

  Swal.fire({
    icon: "error",
    title: "Error",
    text: message,
  });
}

function showExitCompanyModal() {
  const messages = {
    success: "Returning to company selection...",
    serverError: "Oops! Something went wrong. Try again later.",
  };

  Swal.fire({
    title: "Exit Current Company",
    text: "Are you sure you want to exit the current company and return to the company selection screen?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, Exit",
    cancelButtonText: "No, Stay",
  }).then((result) => {
    if (!result.isConfirmed) {
      //   showToast("info", "You chose to stay in the current company.", 3000);
      return;
    }

    showLoader("Exiting current company...");

    $.ajax({
      url: "/company-selection/exit",
      method: "GET",
      success: function (response) {
        if (response.success) {
          showToast(
            "success",
            "You are being redirected to the company selection screen...",
          );

          if (window.backNav) {
            window.backNav.clearHistory();
          }

          // Clear cached bill sundry orders and file number when exiting company
          localStorage.removeItem('purchase_invoice_file_number');
          Object.keys(localStorage).forEach(function(key) {
            if (key.startsWith('purchase_inv_sundry_order_')) {
              localStorage.removeItem(key);
            }
          });

          setTimeout(() => {
            window.location.href = response.url || "/company-selection";
          }, 100);
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: response.message || messages.serverError,
          });
        }
      },
      error: function (xhr) {
        console.error("Error exiting company:", xhr);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: messages.serverError,
        });
      },
    });
  });
}

function moveFocusToNextField(currentField) {
    var inputs = $(
        ":input:visible:not([readonly]):not([disabled]):not(:hidden)"
    );
    var currentIndex = inputs.index(currentField);
    var nextIndex = currentIndex + 1;


    while (nextIndex < inputs.length) {
        var nextField = inputs[nextIndex];
        if (

          !$(nextField).prop("readonly") &&
          $(nextField).is(":visible") &&
          !$(nextField).prop(":disabled") &&
          !$(nextField).is(":hidden") &&
          !$(nextField).is(".non-selectable") &&
          !$(nextField).hasClass("select2-selection__clear")
        ) {
            nextField.focus();
            break;
        }
        nextIndex++;
    }
}


function printReport(route, data = {}) {
  showLoader("Please wait... Generating print preview...");

  $.ajax({
    url: route,
    method: "GET",
    data: data,
    success: function (response) {
      hideLoader();

      // Normalize response handling whether it's wrapped in a data object or not
      const isSuccess = response.success || (response.data && response.data.success);
      const html = response.html || (response.data && response.data.html) ||
        (response.data && response.data.data && response.data.data.html);

      if (isSuccess && html) {
        // 🪟 Step 5: Open a new window for print preview
        const w = window.open("", "_blank");

        if (!w) {
          // Handle popup-blocker issue
          showToast("error", "Popup blocked! Please allow popups for this site.");
          return;
        }

         // 🖨️ Step 7: Trigger browser print dialog (Small delay to allow CSS loading)
         w.onload = function () {
        w.focus();
        w.print();
        // w.close(); // optional
      };
      
        // 📝 Step 6: Write the HTML content into the new window
        w.document.write(html);
        w.document.close();

       
      } else {
        // ⚠️ Step 8: Handle invalid or failed response
        const message = response.message || (response.data && response.data.message) || "Unable to generate print preview.";
        showToast("warning", message);
      }
    },
    error: function (xhr) {
      // ❌ Step 9: Handle network/server errors gracefully
      hideLoader();

      let errorMessage = "Something went wrong while generating the report.";

      if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
      } else if (xhr.status === 0) {
        errorMessage = "No response received from server. Check your connection.";
      } else if (xhr.status >= 500) {
        errorMessage = "Server error occurred while generating the report.";
      }

      console.error("Print Report Error:", xhr);
      showToast("error", errorMessage);
    }
  });
}

function downloadExcel(route, data = {}) {
  showLoader("Please wait... Generating Excel report...");

  $.ajax({
    url: route,
    method: "GET",
    data: data,
    success: function (response) {
      hideLoader();

      // Normalize response handling whether it's wrapped in a data object or not
      const isSuccess = response.success || (response.data && response.data.success);
      const payload = response.data || response;

      if (isSuccess && payload) {
        // Extract URL and filename properly
        const file_url = payload.file_url || (payload.data && payload.data.file_url);
        const file_name = payload.file_name || (payload.data && payload.data.file_name);

        if (file_url) {
          // ✅ Create a temporary link and trigger download
          const link = document.createElement("a");
          link.href = file_url;
          link.download = file_name || "report.xlsx";
          document.body.appendChild(link);
          link.click();
          link.remove();

          showToast("success", "Excel file downloaded successfully!");
        } else {
          showToast("error", "File URL not found in response.");
        }
      } else {
        const message = response.message || (response.data && response.data.message) || "Failed to export Excel.";
        showToast("error", message);
      }
    },
    error: function (xhr) {
      hideLoader();

      let errorMessage = "Something went wrong while downloading the Excel file.";

      if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
      } else if (xhr.status === 0) {
        errorMessage = "No response received from server. Check your connection.";
      } else if (xhr.status >= 500) {
        errorMessage = "Server error occurred while generating the report.";
      }

      console.error("Excel Download Error:", xhr);
      showToast("error", errorMessage);
    }
  });
}

// ===========================================
// LOCAL STORAGE BACK BUTTON LOGIC
// ===========================================
$(document).ready(function () {
  let currentUrl = window.location.pathname + window.location.search;
  let navHistory = JSON.parse(localStorage.getItem("erp_nav_history")) || [];

  // Don't track AJAX responses, only track true page loads.
  // Also avoid duplicating the same URL twice in a row (e.g. refreshes)
  if (currentUrl !== "/back" && navHistory[navHistory.length - 1] !== currentUrl) {
    navHistory.push(currentUrl);
    
    // Keep max 20 pages in history to save space
    if (navHistory.length > 20) navHistory.shift(); 
    localStorage.setItem("erp_nav_history", JSON.stringify(navHistory));
  }

  // Intercept any click on the back button
  $(document).on("click", ".back-btn", function (e) {
    e.preventDefault(); // Stop it from hitting the server-side /back route

    let history = JSON.parse(localStorage.getItem("erp_nav_history")) || [];
    
    // 1. Remove the current page we are on
    history.pop();
    
    // 2. Get the actual previous page to navigate to
    let previousUrl = history.pop();
    
    if (previousUrl) {
      // Save the updated history and redirect!
      localStorage.setItem("erp_nav_history", JSON.stringify(history));
      window.location.href = previousUrl;
    } else {
      // Fallback if no history exists
      window.location.href = "/dashboard";
    }
  });
});


