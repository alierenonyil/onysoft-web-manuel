// Admin Panel JavaScript

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Confirm delete
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Bu kaydı silmek istediğinizden emin misiniz?')) {
            e.preventDefault();
            return false;
        }
    });

    // Sidebar toggle for mobile
    $('#sidebarToggle').on('click', function() {
        $('.sidebar').toggleClass('show');
    });

    // Table row click
    $('.clickable-row').on('click', function() {
        window.location = $(this).data('href');
    });

    // Search input auto-submit delay
    let searchTimeout;
    $('.search-input').on('keyup', function() {
        clearTimeout(searchTimeout);
        const form = $(this).closest('form');
        searchTimeout = setTimeout(function() {
            form.submit();
        }, 500);
    });
});

// Image preview before upload
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#' + previewId).attr('src', e.target.result).show();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Format number with thousand separator
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Get CSRF token from meta tag
function getCsrfToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

// AJAX setup for CSRF token
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': getCsrfToken()
    }
});
