document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const listContainer = document.querySelector(".list-group");
  const allCompanies = Array.from(document.querySelectorAll(".company-item"));
  let visibleCompanies = [...allCompanies];
  let selectedIndex = -1;

  // Update highlight for selection
  function updateSelection() {
    visibleCompanies.forEach((item, index) => {
      if (index === selectedIndex) {
        item.classList.add("bg-primary-lt", "text-primary");
        item.scrollIntoView({ block: "nearest", behavior: "smooth" });
      } else {
        item.classList.remove("bg-primary-lt", "text-primary");
      }
    });
  }

  // Rebuild company list based on current visibleCompanies
  function renderList() {
    listContainer.innerHTML = "";
    if (visibleCompanies.length === 0) {
      listContainer.innerHTML = `
                <div class="list-group-item text-center text-muted py-4 border border-dashed rounded-4 bg-primary-lt">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search mb-2" width="36" height="36" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <circle cx="10" cy="10" r="7" />
                        <line x1="21" y1="21" x2="15" y2="15" />
                    </svg>
                    <p class="mb-0 fw-bold">No matching companies found.</p>
                    <small class="text-muted fw-bold">Try refining your search terms.</small>
                </div>`;
      selectedIndex = -1;
      return;
    }

    visibleCompanies.forEach((item) => listContainer.appendChild(item));

    // Re-attach click handler (lost after re-render)
    visibleCompanies.forEach((item, index) => {
      item.onclick = () => {
        selectedIndex = index;
        updateSelection();
        selectCompanyAjax(item.dataset.companyId);
      };
    });

    // Reset selection to first visible company
    selectedIndex = 0;
    updateSelection();
  }

  // Keyboard navigation (Up/Down/Enter) - Fixed to work everywhere
  document.addEventListener("keydown", function (e) {
    // Handle arrow keys even when input is focused
    if (e.key === "ArrowDown" || e.key === "ArrowUp") {
      if (visibleCompanies.length === 0) return;

      e.preventDefault();

      if (e.key === "ArrowDown") {
        selectedIndex = (selectedIndex + 1) % visibleCompanies.length;
      } else if (e.key === "ArrowUp") {
        selectedIndex =
          (selectedIndex - 1 + visibleCompanies.length) %
          visibleCompanies.length;
      }

      updateSelection();
      return;
    }

    // Handle Enter key
    if (e.key === "Enter") {
      e.preventDefault();
      if (selectedIndex >= 0 && visibleCompanies[selectedIndex]) {
        selectCompanyAjax(visibleCompanies[selectedIndex].dataset.companyId);
      }
      return;
    }

    // Handle / key to focus search (only when not already in input)
    if (e.key === "/" && document.activeElement !== searchInput) {
      e.preventDefault();
      searchInput.focus();
    }
  }, true);

  // Search: rebuild visibleCompanies and re-render
  searchInput.addEventListener("input", () => {
    const filter = searchInput.value.toUpperCase().trim();
    if (filter === "") {
      visibleCompanies = [...allCompanies];
    } else {
      visibleCompanies = allCompanies.filter((item) => {
        const name = item.dataset.companyName.toUpperCase();
        const code = item.dataset.companyCode.toUpperCase();
        return name.includes(filter) || code.includes(filter);
      });
    }
    renderList();
  });

  // Initial render
  renderList();

  function updatePlaceholder() {
    const input = document.getElementById("searchInput");
    const width = window.innerWidth;

    if (width < 576) {
      input.placeholder = "Search company";
    } else if (width < 768) {
      input.placeholder = "Search by Name or Code";
    } else {
      input.placeholder =
        "Search company by Name or Code (e.g., COMP0101, 101)";
    }
  }

  // Update on load
  updatePlaceholder();

  // Update on resize
  window.addEventListener("resize", updatePlaceholder);

  function selectCompanyAjax(companyId) {
    const messages = {
      success: "Wait, company data is being prepared...!",
      validationError:
        "There was a problem selecting the company. Please check the details.",
      serverError: "Oops! Something went wrong. Try again later.",
    };

    $.ajax({
      url: selectCompany,
      type: "POST",
      data: { company_id: companyId },
      beforeSend: function () {
        showLoader("Preparing company data...");
      },
      success: function (response) {
        if (response.success) {
          localStorage.removeItem('purchase_invoice_file_number');
          // redirect
          window.location.href = response.url || "/dashboard";
        } else {
          // error pop-up
          Swal.fire({
            icon: "error",
            title: response.message || messages.validationError,
            timer: 1500,
            showConfirmButton: false,
          });
        }
      },
      error: function (xhr) {
        let error = xhr.responseJSON;
        // error pop-up
        Swal.fire({
          icon: "error",
          title: error.message || messages.serverError,
          timer: 3000,
          showConfirmButton: false,
        });
        
      },
      complete: function () {
        hideLoader();
      },
    });
  }
});
