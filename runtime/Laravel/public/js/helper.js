// date helper

// date dmy to ymd

function formatDateToYMD(dateStr) {
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        const [day, month, year] = parts;
        if (day.length === 2 && month.length === 2 && year.length === 4) {
            return `${year}-${month}-${day}`;
        }
    }
    return dateStr; 
}

// date ymd to dmy  (date get from database)                
function formatDateToDMY(dateStr) {
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        const [year, month, day] = parts;
        if (day.length === 2 && month.length === 2 && year.length === 4) {
            return `${day}-${month}-${year}`;
        }
    }
    return dateStr; // fallback if format is not correct
}

// check valid date
function isValidDateDMY(dateStr) {
    if (typeof dateStr !== 'string') return false;
    const trimmed = dateStr.trim();
    // Regex to match DD-MM-YYYY
    const regex = /^(\d{2})-(\d{2})-(\d{4})$/;
    const match = trimmed.match(regex);
  
    if (!match) return false; // format not matched
  
    const day = parseInt(match[1], 10);
    const month = parseInt(match[2], 10);
    const year = parseInt(match[3], 10);
  
    // Check valid ranges
    if (month < 1 || month > 12) return false;
  
    const daysInMonth = new Date(year, month, 0).getDate(); // last day of month
    if (day < 1 || day > daysInMonth) return false;
  
    return true;
}



// check within financial year
function isWithinFY(dateInput, financialYearStart, financialYearEnd) {
    if (!dateInput || !financialYearStart || !financialYearEnd) return false;
    const date = new Date(dateInput);
    return date >= new Date(financialYearStart) && date <= new Date(financialYearEnd);
}


// calculate due date 
function calculateDueDate(baseDateStr, daysToAdd) {
    // baseDateStr in format DD-MM-YYYY
    const [day, month, year] = baseDateStr.split('-').map(Number);
    const baseDate = new Date(year, month - 1, day); // JS months are 0-based

    baseDate.setDate(baseDate.getDate() + Number(daysToAdd));

    // Format result as DD-MM-YYYY
    const dd = String(baseDate.getDate()).padStart(2, '0');
    const mm = String(baseDate.getMonth() + 1).padStart(2, '0');
    const yyyy = baseDate.getFullYear();

    return `${dd}-${mm}-${yyyy}`;
}

// auto date input with validation
class DateInput {
    constructor(selector, financialYearStartDate, financialYearEndDate) {
        this.selector = selector;
        this.financialYearStartDate = new Date(financialYearStartDate);
        this.financialYearEndDate = new Date(financialYearEndDate);

        this.init();
        this.addBlurValidation();
    }

    init() {
        document.addEventListener("keyup", (e) => {
            if (e.target.matches(this.selector)) {
                this.handleKeyup(e.target, e);
            }
        });
    }

    addBlurValidation() {
        document.addEventListener("blur", (e) => {
            if (e.target.matches(this.selector)) {
                this.handleBlur(e.target);
            }
        }, true); // use capture so blur works properly
    }

    handleKeyup(inputElement, e) {
        // Skip navigation/modifier keys — they must never trigger date reformatting
        const skipKeys = ['Enter', 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight',
                          'Tab', 'Escape', 'Shift', 'Control', 'Alt', 'Meta'];
        if (skipKeys.includes(e.key)) return;

        if (inputElement.dataset.blockDateKeyup === "1") {
            inputElement.dataset.blockDateKeyup = "0";
            return;
        }
        const isEditable = inputElement.isContentEditable;
        let input = isEditable ? inputElement.textContent : inputElement.value;
        let currentDate = new Date();

        let currentYear = currentDate.getFullYear().toString();
        let financialStartYear = this.financialYearStartDate.getFullYear().toString();
        let financialEndYear = this.financialYearEndDate.getFullYear().toString();

        let year = (currentYear === financialStartYear || currentYear === financialEndYear)
            ? currentYear
            : financialEndYear;

        if (input) {
            let values = input.split("-").map(v => v.replace(/\D/g, ""));
            if (values[1]) values[1] = this.checkValue(values[1], 12);
            if (values[0]) values[0] = this.checkValue(values[0], 31);

            if (values[0]?.length === 2 && values[1]?.length === 2 && !values[2]) {
                values[2] = year;
            }

            let output = values.map((v, i) => v.length === 2 && i < 2 ? v + "-" : v);
            const formatted = output.join("").substr(0, 10);

            if (isEditable) {
                inputElement.textContent = formatted;
                // Place cursor at end using collapse — avoids removeAllRanges which can blur the cell
                const sel = window.getSelection();
                const textNode = inputElement.firstChild;
                if (textNode) {
                    sel.collapse(textNode, textNode.length);
                }
            } else {
                inputElement.value = formatted;
            }
            inputElement.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    handleBlur(inputElement) {
        const isEditable = inputElement.isContentEditable;
        let input = isEditable ? inputElement.textContent : inputElement.value;
        if (!input) return;

        let parts = input.split("-");
        if (parts.length !== 3) {
            if (isEditable) inputElement.textContent = "";
            else inputElement.value = "";
            return;
        }

        let day = parseInt(parts[0], 10);
        let month = parseInt(parts[1], 10);
        let year = parseInt(parts[2], 10);

        let date = new Date(year, month - 1, day);
        if (
            date.getFullYear() !== year ||
            date.getMonth() !== (month - 1) ||
            date.getDate() !== day
        ) {
            if (isEditable) inputElement.textContent = "";
            else inputElement.value = "";
            showToast('error', 'Invalid date! Please enter a valid date');
        }
    }

    checkValue(str, max) {
        if (str.charAt(0) !== "0" || str === "00") {
            let num = parseInt(str);
            if (isNaN(num) || num <= 0 || num > max) num = 1;
            str = num > parseInt(max.toString().charAt(0)) && num.toString().length === 1
                ? "0" + num
                : num.toString();
        }
        return str;
    }
}

// number helper

function formatNumber(number, decimals = 2) {
    if (isNaN(number)) return "0.00";
    return parseFloat(number).toFixed(decimals);
}

function formatQty(number, decimals = 3) {
    return formatNumber(number, decimals);
}

function formatCurrency(number, decimals = 2) {
    return "₹" + formatIndianNumber(number, decimals);
}

function formatIndianNumber(number, decimals = 2) {
  if (isNaN(number) || number === null) return "";

  let isNegative = Number(number) < 0;
  number = Math.abs(Number(number));

  let [intPart, decPart] = number.toFixed(decimals).split(".");

  if (intPart.length > 3) {
    const lastThree = intPart.slice(-3);
    const otherNumbers = intPart.slice(0, -3);
    intPart =
      otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + "," + lastThree;
  }

  let formatted = decPart ? intPart + "." + decPart : intPart;

  return isNegative ? "-" + formatted : formatted;
} 

    
//String helper

function upperCase(str) {
    if (str === null || str === undefined) return "";
    return String(str).toUpperCase();
}

// vehicle validation
// function initVehicleRegValidation(classSelector = ".txtRegNo") {
//     const inputs = document.querySelectorAll(classSelector);

//     if (!inputs.length) {
//         console.warn(`No elements found for selector: ${classSelector}`);
//         return;
//     }

//     inputs.forEach((input) => {
//         // Create an error message element
//         let errorEl = document.createElement("div");
//         errorEl.classList.add("text-danger", "small", "mt-1", "d-none");
//         input.insertAdjacentElement("afterend", errorEl);

//         function showError(message) {
//             input.classList.add("is-invalid");
//             errorEl.textContent = message;
//             errorEl.classList.remove("d-none");
//         }

//         function clearError() {
//             input.classList.remove("is-invalid");
//             errorEl.textContent = "";
//             errorEl.classList.add("d-none");
//         }

//         input.addEventListener("input", (e) => {
//             clearError();
//             let value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
//             input.value = value;

//             const rules = [
//                 {
//                     condition: value.length >= 1 && !/[A-Z]/.test(value[0]),
//                     message: "1st character must be an alphabet.",
//                 },
//                 {
//                     condition: value.length >= 2 && !/[A-Z]/.test(value[1]),
//                     message: "2nd character must be an alphabet.",
//                 },
//                 {
//                     condition:
//                         value.length >= 4 &&
//                         (!/[0-9]/.test(value[2]) || !/[0-9]/.test(value[3])),
//                     message: "3rd and 4th characters must be numbers.",
//                 },
//                 {
//                     condition: value.length >= 6,
//                     message:
//                         "5th and 6th characters must be 2 alphabets or 1 alphabet & 1 number.",
//                     validate: () => {
//                         const mid = value.substring(4, 6);
//                         const alphaCount = (mid.match(/[A-Z]/g) || []).length;
//                         const numCount = (mid.match(/[0-9]/g) || []).length;
//                         return alphaCount + numCount === 2 && alphaCount >= 1;
//                     },
//                 },
//                 {
//                     condition: value.length >= 9,
//                     message: "Last characters (from 7th onwards) must be numbers.",
//                     validate: () => /^[0-9]{3,4}$/.test(value.substring(6)),
//                 },
//             ];

//             for (const rule of rules) {
//                 if (rule.condition && (!rule.validate || !rule.validate())) {
//                     showError(`${value} - ${rule.message}`);
//                     e.stopPropagation();
//                     break;
//                 }
//             }
//         });

//         input.addEventListener("blur", () => {
//             const value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
//             if (value.length > 0 && (value.length < 9 || value.length > 10)) {
//                 showError(`${value} - Vehicle number must be 9 or 10 characters long.`);
//             }
//         });

//         input.addEventListener("focus", clearError);
//     });
// }

function initVehicleRegValidation(classSelector = ".txtRegNo") {
    const inputs = document.querySelectorAll(classSelector);

    if (!inputs.length) {
        console.warn(`No elements found for selector: ${classSelector}`);
        return;
    }

    // NIC e-Way Vehicle Number Regex
    const vehicleRegex = /^[A-Z]{2}[0-9]{1,2}[A-Z]{0,3}[0-9]{1,4}$/;

    inputs.forEach((input) => {
        let errorEl = document.createElement("div");
        errorEl.classList.add("text-danger", "small", "mt-1", "d-none");
        input.insertAdjacentElement("afterend", errorEl);

        function showError(message) {
            input.classList.add("is-invalid");
            errorEl.textContent = message;
            errorEl.classList.remove("d-none");
        }

        function clearError() {
            input.classList.remove("is-invalid");
            errorEl.textContent = "";
            errorEl.classList.add("d-none");
        }

        // Format input
        input.addEventListener("input", () => {
            clearError();

            // Remove spaces/special chars and convert to uppercase
            let value = input.value
                .toUpperCase()
                .replace(/[\s-]+/g, "")
                .replace(/[^A-Z0-9]/g, "");

            input.value = value;
        });

        // Validate on blur
        input.addEventListener("input", () => {
            clearError();

            const value = input.value
                .toUpperCase()
                .replace(/[\s-]+/g, "")
                .replace(/[^A-Z0-9]/g, "");

            input.value = value;

            if (value.length === 0) return;

            if (!vehicleRegex.test(value)) {
                showError(
                    "Enter a valid vehicle number (e.g. GJ01AB1234)."
                );
            }
        });

        input.addEventListener("focus", clearError);
    });
}

function convertKgToTon(kg) {
    return kg / 1000;
}

// show status badge
function renderStatusBadge(element, status, mapping) {
    const $el = $(element);
    if (!$el.length) return;

    const defaultClasses = 'bg-secondary text-secondary-fg';

    $el.removeClass(function(index, className) {
        return (className.match(/(^|\s)(bg-|text-|badge)\S+/g) || []).join(' ');
    });
    
    const safeStatus = status || 'unknown';
    const statusClasses = mapping[safeStatus] || mapping['default'] || defaultClasses;

    $el.addClass(`badge ${statusClasses}`).text(upperCase(safeStatus));
}

function formatValue(val) {
    if (val === null || val === undefined) return "0";
    if (val >= 10000000) return (val / 10000000).toFixed(2) + ' Cr';
    if (val >= 100000) return (val / 100000).toFixed(2) + ' L';
    if (val >= 1000) return (val / 1000).toFixed(1) + ' K';
    return Number(val).toLocaleString();
}

// Mobile Number
function initMobileNumberValidation(selector = ".txtMobile") {
    const PREFIX = "+91 ";

    $(selector).on("focus", function () {
        let input = $(this);
        if (!input.val().startsWith(PREFIX)) {
            input.val(PREFIX);
        }
        let len = input.val().length;
        this.setSelectionRange(len, len);
    });

    $(selector).on("keydown", function (e) {
        let cursorPos = $(this).prop("selectionStart");
        if ((e.key === "Backspace" && cursorPos <= PREFIX.length) ||
            (e.key === "Delete" && cursorPos < PREFIX.length)) {
            e.preventDefault();
        }
    });

    $(selector).on("input", function () {
        let input = $(this);
        let value = input.val();

        // ✅ Slice off "+91 " first — treat it as static decoration
        let userPart = value.startsWith(PREFIX)
            ? value.slice(PREFIX.length)   // removes exactly "+91 "
            : value.replace(/^\+?91\s*/, ""); // fallback cleanup

        // Extract only digits from what the user typed
        let digits = userPart.replace(/\D/g, "").substring(0, 10);

        // Rebuild: +91 XXXXX XXXXX
        let formatted = PREFIX;
        if (digits.length > 0) formatted += digits.substring(0, 5);
        if (digits.length > 5) formatted += " " + digits.substring(5, 10);

        input.val(formatted);
        input.prop("selectionStart", formatted.length)
             .prop("selectionEnd", formatted.length);
    });
}

function calculateDay(startDate, EndDate) {
    // convert Date dmy to ymd
    const start = formatDateToYMD(startDate);
    const end = formatDateToYMD(EndDate);
    const start_date = new Date(start);
    const end_date = new Date(end);
    const timeDiff = Math.abs(end_date.getTime() - start_date.getTime());
    const diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // +1 to include both start and end dates
    return diffDays;
}


function lockSelect2(selector) {
    $(selector).data('locked', true);
    $(selector).next('.select2-container').addClass('select2-locked');
    $(selector).addClass('non-selectable');
    $(selector).on('select2:opening.lock', function (e) {
        e.preventDefault();
    });
}

function unlockSelect2(selector) {
    $(selector).data('locked', false);
    $(selector).next('.select2-container').removeClass('select2-locked');
    $(selector).removeClass('non-selectable');
    $(selector).off('select2:opening.lock');
}

/**
 * Calculate basic net weight (Gross - Tare).
 */
function calculateNetWeight(grossSelector, tareSelector, netSelector, decimals) {
    const gross = parseFloat($(grossSelector).val()) || 0;
    const tare  = parseFloat($(tareSelector).val())  || 0;
    const decimalPlaces = (typeof decimals !== 'undefined') ? decimals : ((typeof DECIMALS !== 'undefined' && DECIMALS.WEIGHT_KG) ? DECIMALS.WEIGHT_KG : 3);

    // Tare must not exceed gross
    if (tare > gross) {
        $(netSelector).val((0).toFixed(decimalPlaces));
        return 0;
    }

    const net = gross - tare;
    $(netSelector).val(net.toFixed(decimalPlaces));
    return net;
}

/**
 * Calculate net weight without bags.
 */
function calculateNetWeightWithoutBags(grossSelector, tareSelector, bagCountSelector, bagTypeSelector, netWithoutBagsSelector, decimals) {
    const gross    = parseFloat($(grossSelector).val()) || 0;
    const tare     = parseFloat($(tareSelector).val())  || 0;
    const bagCount = parseFloat($(bagCountSelector).val())    || 0;
    const bagType  = $(bagTypeSelector).val();
    const net      = gross - tare;
    
    const decimalPlaces = (typeof decimals !== 'undefined') ? decimals : ((typeof DECIMALS !== 'undefined' && DECIMALS.WEIGHT_KG) ? DECIMALS.WEIGHT_KG : 3);

    // Always reset first so stale values don't linger
    let netWithoutBags = net;

    if (bagCount > 0 && net > 0) {
        let bagWeight = 0;

        if (bagType === BAG_GUNNY) {
            bagWeight = bagCount * BAG_GUNNY_WEIGHT;
        } else if (bagType === BAG_PLASTIC) {
            bagWeight = bagCount * BAG_PLASTIC_WEIGHT;
        }

        netWithoutBags = net - bagWeight;
        if (netWithoutBags < 0) netWithoutBags = 0;
    }

    $(netWithoutBagsSelector).val(netWithoutBags.toFixed(decimalPlaces));
    return netWithoutBags;
}

function currentDate() {
    let today = new Date();
    let year = today.getFullYear();
    let month = String(today.getMonth() + 1).padStart(2, '0');
    let day = String(today.getDate()).padStart(2, '0');

    return `${day}-${month}-${year}`;
}

function getDayFromDate(dmyDate) {

    let parts = dmyDate.split("-");
    let ymdDate = `${parts[2]}-${parts[1]}-${parts[0]}`;

    let dateObj = new Date(ymdDate);

    let days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

    return days[dateObj.getDay()];
}

function toArray(value) {
    if (Array.isArray(value)) {
        return value;
    }
    return value ? [value] : [];
}

function amountDirection(value) {

    let amount = parseFloat(value);

    if (isNaN(amount)) return "0.00";

    let formatted = formatIndianNumber(Math.abs(amount).toFixed(2));

    return amount >= 0
        ? `${formatted} ${DIRECTION.DEBIT}`
        : `${formatted} ${DIRECTION.CREDIT}`;
}

/**
 * Format and set time_in / time_in_display fields using a Laravel database ISO timestamp (created_at).
 */
function formatAndSetTimeFields(createdAtStr, timeInSelector = '#time_in', timeInDisplaySelector = 'input[name="time_in_display"]') {
    if (!createdAtStr) return;
    const createdAt = new Date(createdAtStr);
    const time24 = createdAt.getHours().toString().padStart(2, '0') + ':' + createdAt.getMinutes().toString().padStart(2, '0');
    const time12 = (createdAt.getHours() % 12 || 12).toString().padStart(2, '0') + ':' + createdAt.getMinutes().toString().padStart(2, '0') + ' ' + (createdAt.getHours() >= 12 ? 'PM' : 'AM');
    
    $(timeInSelector).val(time24);
    $(timeInDisplaySelector).val(time12);
}


/*--------------------------------------------------------------
| Validation helpers
--------------------------------------------------------------*/
function markInvalid(selector, message) {
    var $el = $(selector);
    $el.addClass("is-invalid");
    // show feedback only when a message is provided and no sibling exists yet
    // if (message && !$el.next(".invalid-feedback").length) {
    //     $('<div class="invalid-feedback"></div>').text(message).insertAfter($el);
    // }
}

function dynamicFilter(selectors) {
    let debounceTimeout;
    $(document).on("input change", selectors, function (e) {        
        clearTimeout(debounceTimeout);        
        if (e.type === "input") {
            debounceTimeout = setTimeout(applyFilter, 300);
        } else if (e.type === "change") {
            applyFilter();
        }
    });
}