function closingBalance(ids, route, callback) {

    // Ensure IDs are always array
    ids = toArray(ids);

    $.ajax({
        url: route,
        type: "GET",
        data: { account_ids: ids },

        success: function (response) {
            if (typeof callback === "function") {
                callback(response);
            }
        },
        error: function (xhr) {
            console.error("Opening Balance Error:", xhr);
            if (typeof callback === "function") {
                callback(null);
            }
        }
    });
}