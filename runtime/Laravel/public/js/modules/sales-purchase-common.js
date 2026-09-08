function getRowGstRate(row) {
    let cgst = parseFloat(row.find(".cgst_rate").val()) || 0;
    let sgst = parseFloat(row.find(".sgst_rate").val()) || 0;
    let igst = parseFloat(row.find(".igst_rate").val()) || 0;
    return cgst + sgst + igst;
}

function getRateFromInclusive(inclusiveRate, gstRate) {
    if (!gstRate) return inclusiveRate;
    return inclusiveRate / (1 + (gstRate / 100));
}

function getInclusiveFromRate(rate, gstRate) {
    if (!gstRate) return rate;
    return rate * (1 + (gstRate / 100));
}

function qtyBlur(row) {
    let qty = parseFloat(row.find(".quantity").val()) || 0;
    let rate = parseFloat(row.find(".rate").val()) || 0;
    let amount = qty * rate;
    
    let qtyDec = typeof DECIMALS !== 'undefined' ? DECIMALS.QTY : 3;
    let amtDec = typeof DECIMALS !== 'undefined' ? DECIMALS.AMOUNT : 2;
    
    if (qty > 0) row.find(".quantity").val(qty.toFixed(qtyDec));
    row.find(".amount").val(amount.toFixed(amtDec));
}

function rateBlur(row) {
    let qty = parseFloat(row.find(".quantity").val()) || 0;
    let rate = parseFloat(row.find(".rate").val()) || 0;
    let gstRate = getRowGstRate(row);

    let rateDec = typeof DECIMALS !== 'undefined' ? DECIMALS.RATE : 2;
    let incDec = typeof DECIMALS !== 'undefined' ? DECIMALS.INCLUSIVE_RATE : 2;
    let amtDec = typeof DECIMALS !== 'undefined' ? DECIMALS.AMOUNT : 2;

    // Step 1: Force rate to its exact rounded value as seen in the input
    rate = parseFloat(rate.toFixed(rateDec));
    
    // Step 2: Calculate inclusive rate
    let rawInclusiveRate = getInclusiveFromRate(rate, gstRate);
    let inclusiveRate = parseFloat(rawInclusiveRate.toFixed(incDec));
    
    // Step 3: Calculate Amount strictly using the rounded rate
    let amount = qty * rate;
    
    if (rate > 0) row.find(".rate").val(rate.toFixed(rateDec));
    row.find(".inclusive_rate").val(inclusiveRate.toFixed(incDec));
    row.find(".amount").val(amount.toFixed(amtDec));
}

function inclusiveBlur(row) {
    let qty = parseFloat(row.find(".quantity").val()) || 0;
    let inclusiveRate = parseFloat(row.find(".inclusive_rate").val()) || 0;
    let gstRate = getRowGstRate(row);
    
    let rateDec = typeof DECIMALS !== 'undefined' ? DECIMALS.RATE : 2;
    let incDec = typeof DECIMALS !== 'undefined' ? DECIMALS.INCLUSIVE_RATE : 2;
    let amtDec = typeof DECIMALS !== 'undefined' ? DECIMALS.AMOUNT : 2;

    // Step 1: Force inclusiveRate strictly to its rounded form
    inclusiveRate = parseFloat(inclusiveRate.toFixed(incDec));
    
    // Step 2: Extract rate, but critically, ROUND IT to match UI precision
    let rawRate = getRateFromInclusive(inclusiveRate, gstRate);
    let rate = parseFloat(rawRate.toFixed(rateDec));
    
    // Step 3: Calculate Amount strictly using the rounded Rate
    let amount = qty * rate;
    
    row.find(".rate").val(rate.toFixed(rateDec));
    if (inclusiveRate > 0) row.find(".inclusive_rate").val(inclusiveRate.toFixed(incDec));
    row.find(".amount").val(amount.toFixed(amtDec));
}

function calculateTotal() {
    let totalAmount = 0;
    
    // Sum up all item amounts
    $(".item-row").each(function () {
        let amount = parseFloat($(this).find(".amount").val()) || 0;
        totalAmount += amount;
    });
    
    // Update the total amount field
    let totalDec = typeof DECIMALS !== 'undefined' ? DECIMALS.AMOUNT : 2;
    $("#total_amount").text(formatCurrency(totalAmount, totalDec));
}

function calculateTotalQty() {
    let totalQty = 0;
    
    // Sum up all item quantities
    $(".item-row").each(function () {
        let qty = parseFloat($(this).find(".quantity").val()) || 0;
        totalQty += qty;
    });
    
    // Update the total quantity field
    let qtyDec = typeof DECIMALS !== 'undefined' ? DECIMALS.QTY : 3;
    $("#total_qty").text(formatIndianNumber(totalQty, qtyDec));
}