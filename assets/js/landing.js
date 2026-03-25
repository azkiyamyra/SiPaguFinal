/**
 * Landing Page JavaScript
 * File: assets/js/landing.js
 */

"use strict";

document.addEventListener('DOMContentLoaded', function() {
    
    // ========== NAVBAR SCROLL EFFECT ==========
    const navbar = document.getElementById('navbar');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // ========== MOBILE MENU TOGGLE ==========
    const hamburgerMenu = document.getElementById('hamburgerMenu');
    const navLinks = document.getElementById('navLinks');
    const menuOverlay = document.getElementById('menuOverlay');

    if (hamburgerMenu && navLinks && menuOverlay) {
        hamburgerMenu.addEventListener('click', function() {
            hamburgerMenu.classList.toggle('active');
            navLinks.classList.toggle('active');
            menuOverlay.classList.toggle('active');
            document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
        });

        // Close menu when clicking overlay
        menuOverlay.addEventListener('click', function() {
            hamburgerMenu.classList.remove('active');
            navLinks.classList.remove('active');
            menuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        // Close menu when clicking nav link
        const navLinkItems = navLinks.querySelectorAll('.nav-link');
        navLinkItems.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 767) {
                    hamburgerMenu.classList.remove('active');
                    navLinks.classList.remove('active');
                    menuOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    }

    // ========== SMOOTH SCROLL FOR ANCHOR LINKS ==========
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Skip if href is just "#"
            if (href === '#') {
                e.preventDefault();
                return;
            }
            
            const target = document.querySelector(href);
            
            if (target) {
                e.preventDefault();
                const offsetTop = target.offsetTop - 80; // 80px untuk navbar height
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // ========== ACTIVE NAVIGATION HIGHLIGHT ==========
    const sections = document.querySelectorAll('section[id]');
    const navLinksArray = document.querySelectorAll('.nav-link[href^="#"]');

    function highlightNavigation() {
        const scrollY = window.pageYOffset;

        sections.forEach(function(section) {
            const sectionHeight = section.offsetHeight;
            const sectionTop = section.offsetTop - 100;
            const sectionId = section.getAttribute('id');
            
            if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                navLinksArray.forEach(function(link) {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === '#' + sectionId) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }

    window.addEventListener('scroll', highlightNavigation);

    // ========== FADE IN ANIMATION ON SCROLL ==========
    const fadeElements = document.querySelectorAll('.fade-in');
    
    function checkFadeIn() {
        fadeElements.forEach(function(element) {
            const elementTop = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementTop < windowHeight - 100) {
                element.classList.add('visible');
            }
        });
    }

    // Check on load
    checkFadeIn();
    
    // Check on scroll
    window.addEventListener('scroll', checkFadeIn);

    // ========== BACK TO TOP BUTTON ==========
    const backToTopBtn = document.getElementById('backToTop');
    
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        });

        backToTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // ========== FORM VALIDATION (untuk login page) ==========
    const loginForm = document.querySelector('form[action="login_aksi.php"]');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const nppInput = this.querySelector('input[name="npp_user"]');
            const passwordInput = this.querySelector('input[name="password_user"]');
            
            let isValid = true;

            // Validate NPP
            if (!nppInput.value.trim()) {
                isValid = false;
                showError(nppInput, 'NPP harus diisi');
            } else {
                removeError(nppInput);
            }

            // Validate Password
            if (!passwordInput.value.trim()) {
                isValid = false;
                showError(passwordInput, 'Password harus diisi');
            } else {
                removeError(passwordInput);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ========== PASSWORD TOGGLE (sudah ada di HTML, tapi kita pastikan) ==========
    const passwordToggle = document.getElementById('togglePassword');
    
    if (passwordToggle) {
        passwordToggle.addEventListener('click', function() {
            const passwordInput = document.querySelector('input[name="password_user"]');
            const eyeIcon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    }

    // ========== HELPER FUNCTIONS ==========
    function showError(input, message) {
        removeError(input);
        
        const inputGroup = input.closest('.input-group') || input.parentElement;
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.cssText = 'color: #fc544b; font-size: 0.875rem; margin-top: 5px;';
        errorDiv.textContent = message;
        
        inputGroup.parentElement.appendChild(errorDiv);
        input.style.borderColor = '#fc544b';
    }

    function removeError(input) {
        const inputGroup = input.closest('.input-group') || input.parentElement;
        const errorMessage = inputGroup.parentElement.querySelector('.error-message');
        
        if (errorMessage) {
            errorMessage.remove();
        }
        
        input.style.borderColor = '';
    }

    // ========== LOADING BUTTON STATE ==========
    const loginButton = document.getElementById('loginButton');
    
    if (loginButton && loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Hanya jalankan jika validasi berhasil
            if (this.checkValidity()) {
                loginButton.disabled = true;
                loginButton.innerHTML = '<span class="loading"></span> Memproses...';
            }
        });
    }

    // ========== CONSOLE LOG (untuk debugging) ==========
    console.log('Landing page JavaScript loaded successfully!');
    console.log('Current page:', window.location.pathname);
});

// ========== UTILITY FUNCTIONS ==========

// Debounce function untuk optimasi scroll events
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}