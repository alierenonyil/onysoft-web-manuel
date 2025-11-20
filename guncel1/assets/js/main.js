// Frontend JavaScript

$(document).ready(function() {
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Add to cart with AJAX (optional enhancement)
    $('.add-to-cart-form').on('submit', function(e) {
        const btn = $(this).find('button[type="submit"]');
        const originalHtml = btn.html();
        btn.html('<span class="loading"></span>').prop('disabled', true);

        // Allow normal form submission after showing loading
        setTimeout(function() {
            // Form will submit normally
        }, 300);
    });

    // Quantity input validation
    $('input[type="number"]').on('change', function() {
        const min = parseInt($(this).attr('min')) || 1;
        const max = parseInt($(this).attr('max')) || 999;
        let value = parseInt($(this).val());

        if (value < min) value = min;
        if (value > max) value = max;

        $(this).val(value);
    });

    // Product image zoom
    $('.product-images img').on('mousemove', function(e) {
        const offset = $(this).offset();
        const x = e.pageX - offset.left;
        const y = e.pageY - offset.top;

        $(this).css('transform-origin', x + 'px ' + y + 'px');
    });

    // Thumbnail click
    $('.thumbnail-image').on('click', function() {
        const newSrc = $(this).attr('src');
        $('#mainProductImage').attr('src', newSrc);

        $('.thumbnail-image').removeClass('border-primary');
        $(this).addClass('border-primary');
    });

    // Newsletter form
    $('#newsletterForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const btn = form.find('button[type="submit"]');
        const email = form.find('input[name="email"]').val();

        btn.prop('disabled', true).html('Gönderiliyor...');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                alert('Bültene başarıyla kaydoldunuz!');
                form[0].reset();
            },
            error: function() {
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            },
            complete: function() {
                btn.prop('disabled', false).html('Kayıt');
            }
        });
    });

    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(e) {
        const target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });

    // Price range filter (if implemented)
    $('#priceRange').on('change', function() {
        const value = $(this).val();
        $('#priceValue').text(value + ' ₺');
    });
});

// Get CSRF token
function getCsrfToken() {
    return $('meta[name="csrf-token"]').attr('content');
}

// AJAX setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': getCsrfToken()
    }
});

// Format price
function formatPrice(price) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(price);
}

// Show loading overlay
function showLoading() {
    $('body').append('<div class="loading-overlay"><div class="spinner-border" role="status"></div></div>');
}

function hideLoading() {
    $('.loading-overlay').remove();
}
