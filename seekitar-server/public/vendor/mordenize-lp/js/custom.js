(function () {
    'use strict';

    // =================================
    // Tooltip
    // =================================
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // =================================
    // Smooth scroll untuk .scroll-link
    // =================================
    document.querySelectorAll('.scroll-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                var top = target.getBoundingClientRect().top + window.pageYOffset - 160;
                window.scrollTo({ top: top, behavior: 'smooth' });
            }
        });
    });

    // =================================
    // Fixed header
    // =================================
    function toggleFixedHeader() {
        var header = document.querySelector('header');
        if (header) {
            header.classList.toggle('fixed-header', window.pageYOffset >= 60);
        }
    }
    window.addEventListener('scroll', toggleFixedHeader, { passive: true });
    toggleFixedHeader();

    // =================================
    // AOS
    // =================================
    if (window.AOS) {
        AOS.init({
            once: true,
        });
    }

    // =================================
    // Production Slider
    // =================================
    var production = document.querySelector('.production-slider .owl-carousel');
    if (production && window.$.fn && window.$.fn.owlCarousel) {
        window.$(production).owlCarousel({
            nav: false,
            dots: true,
            loop: true,
            items: 1,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true
        });
    }

    // =================================
    // Review Slider
    // =================================
    var review = document.querySelector('.review-slider .owl-carousel');
    if (review && window.$.fn && window.$.fn.owlCarousel) {
        window.$(review).owlCarousel({
            loop: true,
            margin: 0,
            dots: true,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: { items: 1 },
                768: { items: 2 },
                1200: { items: 3 }
            }
        });
    }
})();
