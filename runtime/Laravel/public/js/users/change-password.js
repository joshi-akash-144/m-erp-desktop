$(document).ready(function () {
  // Auto-dismiss flash alerts after 4 seconds
  setTimeout(function () {
    $(".alert-success, .alert-danger").fadeOut("slow");
  }, 4000);
});
