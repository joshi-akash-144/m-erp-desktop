// Shared Email Modal Logic for Godown Analysis
let currentGodownAnalysisIds = [];

function openEmailModal(godownAnalysisIds, $btn) {
    if (!godownAnalysisIds || godownAnalysisIds.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Warning",
            text: "Please select at least one record first",
            confirmButtonText: "OK",
        });
        return;
    }

    const MAX_LIMIT = 10; 
    
    if (godownAnalysisIds.length > MAX_LIMIT) {
        Swal.fire({
            icon: "warning",
            title: "Limit Exceeded",            
            text: `You can only select up to ${MAX_LIMIT} record at a time. You currently have ${godownAnalysisIds.length} selected.`,
            confirmButtonText: "OK",
            closeOnClickOutside: false,
            closeOnEsc: false,
        });
        return;
    }

    currentGodownAnalysisIds = godownAnalysisIds;
    const originalHtml = $btn.html();
    $btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

    // Open modal first so the loader inside the message field is visible
    $('#email_to').val('');
    $('#email_subject').val('');
    $('#email_cc').empty().trigger('change');
    if (window.EditorReady && typeof hugerte !== 'undefined' && hugerte.get('email_message')) {
        hugerte.get('email_message').setContent('');
    }
    $('#editor_loader').css('display', 'flex');
    $('#EmailModal').modal('show');

    $.ajax({
        url: emailPreviewUrl,
        method: 'POST',
        data: {
            godown_analysis_ids: currentGodownAnalysisIds,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (!response.success) {
                $('#editor_loader').hide();
                $('#EmailModal').modal('hide');
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message || 'Error fetching data',
                    confirmButtonText: "OK",
                });
            } else {
                const data = response.data;
                $('#email_to').val(data.to_email || '');
                $('#email_subject').val(data.subject || '');
                
                // CC — rebuild options from accounts list
                const ccSelect = $('#email_cc');
                ccSelect.empty();
                if (data.cc_accounts && data.cc_accounts.length) {
                    data.cc_accounts.forEach(function (acc) {
                        const label = acc.name + ' <' + acc.email + '>';
                        ccSelect.append(new Option(label, acc.email, false, false));
                    });
                }
                ccSelect.val(null).trigger('change');

                setEditorContent(data.body || '');
            }
        },
        error: function(xhr) {
            $('#editor_loader').hide();
            $('#EmailModal').modal('hide');
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

$(document).on("click", "#email_send_btn", function(e) {
    e.preventDefault();

    if (!currentGodownAnalysisIds || currentGodownAnalysisIds.length === 0) {
        Swal.fire({ icon: "warning", title: "Warning", text: "No record selected.", confirmButtonText: "OK" });
        return;
    }

    const toEmail = $('#email_to').val();
    const ccEmails = $('#email_cc').val() || [];
    const subject = $('#email_subject').val();
    
    let bodyHtml = '';
    if (window.EditorReady && typeof hugerte !== 'undefined' && hugerte.get('email_message')) {
        bodyHtml = hugerte.get('email_message').getContent();
    } else {
        bodyHtml = $('#email_message').val();
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
            godown_analysis_ids: currentGodownAnalysisIds,
            to_email: toEmail,
            cc_emails: ccEmails,
            subject: subject,
            body: bodyHtml,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#EmailModal').modal('hide');
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
var hugerteInitialized = false;
window.EditorReady = false;
window.EditorPendingContent = null;

function setEditorContent(html) {
    if (window.EditorReady) {
        var ed = hugerte.get('email_message');
        if (ed) ed.setContent(html);
        $('#editor_loader').hide();
    } else {
        window.EditorPendingContent = html;
    }
}

$(document).on('shown.bs.modal', '#EmailModal', function() {
    if (!hugerteInitialized && typeof hugerte !== 'undefined') {
        hugerteInitialized = true;
        hugerte.init({
            selector: '#email_message',
            base_url: typeof hugertePath !== 'undefined' ? hugertePath : '',
            height: 320,
            menubar: false,
            plugins: 'lists link table code',
            toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | code',
            branding: false,
            setup: function(editor) {
                editor.on('init', function() {
                    window.EditorReady = true;
                    if (window.EditorPendingContent !== null) {
                        editor.setContent(window.EditorPendingContent);
                        window.EditorPendingContent = null;
                        $('#editor_loader').hide();
                    }
                });
            }
        });
    }
});

$(document).on('hidden.bs.modal', '#EmailModal', function() {
    if (window.EditorReady && typeof hugerte !== 'undefined' && hugerte.get('email_message')) {
        hugerte.get('email_message').setContent('');
    }
    $('#email_to').val('');
    $('#email_cc').empty().trigger('change');
    $('#email_subject').val('');
});

// Init Select2 for CC on page load
(function initEmailModal() {
  $('#email_cc').select2({
    dropdownParent: $('#EmailModal'),
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