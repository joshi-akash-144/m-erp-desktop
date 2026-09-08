// Shared Email Modal Logic for Dairy Analysis Register
let currentDairyAnalysisIds = [];

function openDairyAnalysisEmailModal(dairyAnalysisIds, $btn) {
    if (!dairyAnalysisIds || dairyAnalysisIds.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Warning",
            text: "Please select at least one record first",
            confirmButtonText: "OK",
        });
        return;
    }

    // --- SET YOUR LIMIT HERE ---
    const MAX_LIMIT = 20; 
    
    if (dairyAnalysisIds.length > MAX_LIMIT) {
        Swal.fire({
            icon: "warning",
            title: "Limit Exceeded",            
            text: `You can only select up to ${MAX_LIMIT} record at a time. You currently have ${dairyAnalysisIds.length} selected.`,
            confirmButtonText: "OK",
            closeOnClickOutside: false,
            closeOnEsc: false,
        });
        return;
    }

    currentDairyAnalysisIds = dairyAnalysisIds;
    const originalHtml = $btn.html();
    $btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

    // Open modal first so the loader inside the message field is visible
    $('#pa_email_to').val('');
    $('#pa_email_subject').val('');
    $('#pa_email_cc').empty().trigger('change');
    if (window.paEditorReady && hugerte.get('pa_email_message')) {
        hugerte.get('pa_email_message').setContent('');
    }
    $('#pa_editor_loader').css('display', 'flex');
    $('#paEmailModal').modal('show');

    $.ajax({
        url: emailPreviewUrl,
        method: 'POST',
        data: {
            dairy_analysis_ids: currentDairyAnalysisIds,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (!response.success) {
                $('#pa_editor_loader').hide();
                $('#paEmailModal').modal('hide');
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message || 'Error fetching data',
                    confirmButtonText: "OK",
                });
            } else {
                const data = response.data;
                $('#pa_email_to').val(data.to_email || '');
                $('#pa_email_subject').val(data.subject || '');
                
                // CC — rebuild options from accounts list
                const ccSelect = $('#pa_email_cc');
                ccSelect.empty();
                if (data.cc_accounts && data.cc_accounts.length) {
                    data.cc_accounts.forEach(function (acc) {
                        const label = acc.name + ' <' + acc.email + '>';
                        ccSelect.append(new Option(label, acc.email, false, false));
                    });
                }
                ccSelect.val(null).trigger('change');

                paSetEditorContent(data.body || '');
            }
        },
        error: function(xhr) {
            $('#pa_editor_loader').hide();
            $('#paEmailModal').modal('hide');
            Swal.fire({
                icon: "error",
                title: "Error",
                text: xhr.responseJSON?.message || 'Something went wrong',
                confirmButtonText: "OK",
            });
        },
        complete: function() {
            $btn.html(originalHtml).prop('disabled', false);
        }
    });
}

$(document).on("click", "#pa_email_send_btn", function(e) {
    e.preventDefault();

    if (!currentDairyAnalysisIds || currentDairyAnalysisIds.length === 0) {
        Swal.fire({ icon: "warning", title: "Warning", text: "No record selected.", confirmButtonText: "OK" });
        return;
    }

    const toEmail = $('#pa_email_to').val();
    const ccEmails = $('#pa_email_cc').val() || [];
    const subject = $('#pa_email_subject').val();
    
    let bodyHtml = '';
    if (window.paEditorReady && typeof hugerte !== 'undefined' && hugerte.get('pa_email_message')) {
        bodyHtml = hugerte.get('pa_email_message').getContent();
    } else {
        bodyHtml = $('#pa_email_message').val();
    }

    if (!toEmail) {
        Swal.fire({ icon: "warning", title: "Warning", text: "Recipient email is required.", confirmButtonText: "OK" });
        return;
    }

    if (!subject) {
        Swal.fire({ icon: "warning", title: "Warning", text: "Subject is required.", confirmButtonText: "OK" });
        return;
    }

    const $btn = $(this);
    const originalHtml = $btn.html();
    $btn.html('<i class="fa fa-spinner fa-spin me-1"></i> Sending...').prop('disabled', true);

    $.ajax({
        url: emailSendUrl,
        method: 'POST',
        data: {
            dairy_analysis_ids: currentDairyAnalysisIds,
            to_email: toEmail,
            cc_emails: ccEmails,
            subject: subject,
            body: bodyHtml,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#paEmailModal').modal('hide');
                if (typeof table !== 'undefined' && table.deselectRow) {
                    table.deselectRow();
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Sent!',
                    text: response.message || 'Email sent successfully.',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to send email.',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: xhr.responseJSON?.message || 'Something went wrong',
                confirmButtonText: 'OK'
            });
        },
        complete: function() {
            $btn.html(originalHtml).prop('disabled', false);
        }
    });
});

// HugeRTE for single-party compose modal
var paHugerteInitialized = false;
window.paEditorReady = false;
window.paEditorPendingContent = null;

function paSetEditorContent(html) {
    if (window.paEditorReady) {
        var ed = hugerte.get('pa_email_message');
        if (ed) ed.setContent(html);
        if (typeof hideLoader === "function") hideLoader();
        $('#pa_editor_loader').hide();
    } else {
        window.paEditorPendingContent = html;
    }
}

$(document).on('shown.bs.modal', '#paEmailModal', function() {
    if (!paHugerteInitialized && typeof hugerte !== 'undefined') {
        paHugerteInitialized = true;
        hugerte.init({
            selector: '#pa_email_message',
            base_url: typeof hugertePath !== 'undefined' ? hugertePath : '',
            height: 320,
            menubar: false,
            plugins: 'lists link table code',
            toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | code',
            branding: false,
            setup: function(editor) {
                editor.on('init', function() {
                    window.paEditorReady = true;
                    if (window.paEditorPendingContent !== null) {
                        editor.setContent(window.paEditorPendingContent);
                        window.paEditorPendingContent = null;
                        if (typeof hideLoader === "function") hideLoader();
                        $('#pa_editor_loader').hide();
                    }
                });
            }
        });
    }
});

$(document).on('hidden.bs.modal', '#paEmailModal', function() {
    if (window.paEditorReady && typeof hugerte !== 'undefined' && hugerte.get('pa_email_message')) {
        hugerte.get('pa_email_message').setContent('');
    }
    $('#pa_email_to').val('');
    $('#pa_email_cc').empty().trigger('change');
    $('#pa_email_subject').val('');
});


// Init Select2 for CC on page load
(function initPaEmailModal() {
  $('#pa_email_cc').select2({
    dropdownParent: $('#paEmailModal'),
    theme: 'bootstrap-5',
    placeholder: 'Please select',
    allowClear: true,
    tags: true,
    tokenSeparators: [','],
    createTag: function (params) {
      var term = $.trim(params.term);
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(term)) return null;
      return { id: term, text: term, newTag: true };
    },
  });
})();