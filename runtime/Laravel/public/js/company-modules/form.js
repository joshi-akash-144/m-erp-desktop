$(document).ready(function () {

    // ── Select2 ──────────────────────────────────────────────
    $(".select2-company").select2({
        theme: "bootstrap-5",
        allowClear: true,
        placeholder: "Select Company…",
        width: "100%",
    });

    // ── Module card toggle ────────────────────────────────────
    const grid = document.getElementById("moduleGrid");

    grid.addEventListener("click", function (e) {
        const card = e.target.closest(".module-card");
        if (!card) return;
        const checkbox = card.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        card.classList.toggle("selected", checkbox.checked);
        updateSelectedCount();
    });

    document.getElementById("selectAll").addEventListener("click", function () {
        grid.querySelectorAll(".module-card").forEach(function (card) {
            card.querySelector("input").checked = true;
            card.classList.add("selected");
        });
        updateSelectedCount();
    });

    document.getElementById("deselectAll").addEventListener("click", function () {
        grid.querySelectorAll(".module-card").forEach(function (card) {
            card.querySelector("input").checked = false;
            card.classList.remove("selected");
        });
        updateSelectedCount();
    });

    function updateSelectedCount() {
        const count = grid.querySelectorAll('input[type="checkbox"]:checked').length;
        document.getElementById("selected-count").textContent = count + " selected";
    }

    // ── Load existing modules when company is chosen ──────────
    $("#company_id").on("change", function () {
        const companyId = $(this).val();
        if (!companyId) {
            grid.querySelectorAll(".module-card").forEach(function (card) {
                card.querySelector("input").checked = false;
                card.classList.remove("selected");
            });
            updateSelectedCount();
            return;
        }

        $.ajax({
            url: getCompanyModulesUrl,
            method: "GET",
            data: { filter_company_id: companyId, size: 200 },
            success: function (res) {
                const assignedIds = (res.data || []).map(function (r) { return String(r.module_id); });

                grid.querySelectorAll(".module-card").forEach(function (card) {
                    const moduleId = String(card.dataset.moduleId);
                    const checkbox = card.querySelector("input");
                    const isAssigned = assignedIds.includes(moduleId);
                    checkbox.checked = isAssigned;
                    card.classList.toggle("selected", isAssigned);
                });

                updateSelectedCount();
            },
            error: function () {
                showToast("error", "Failed to load existing module assignments.");
            },
        });
    });

    // ── Form submission ───────────────────────────────────────
    $("#companyModuleForm").on("submit", function (e) {
        e.preventDefault();

        const companyId = $("#company_id").val();
        if (!companyId) {
            document.getElementById("company_id_error").textContent = "Please select a company";
            document.getElementById("company_id_error").style.display = "block";
            return;
        }
        document.getElementById("company_id_error").style.display = "none";

        const checkedModules = grid.querySelectorAll('input[type="checkbox"]:checked');
        if (checkedModules.length === 0) {
            document.getElementById("module_ids_error").textContent = "Please select at least one module";
            return;
        }
        document.getElementById("module_ids_error").textContent = "";

        const formData = new FormData(this);
        showLoader("Saving assignment…");

        $.ajax({
            url: storeCompanyModuleUrl,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                hideLoader();
                if (res.success) {
                    showToast("success", res.message);
                    setTimeout(function () {
                        window.location.href = companyModulesIndexUrl;
                    }, 1200);
                } else {
                    showToast("error", res.message || "Failed to save.");
                }
            },
            error: function (xhr) {
                hideLoader();
                if (typeof handleAjaxError === "function") {
                    handleAjaxError(xhr);
                } else {
                    showToast("error", "Something went wrong!");
                }
            },
        });
    });
});
