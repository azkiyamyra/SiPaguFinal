// ===========================================
// CUSTOM JAVASCRIPT FOR SIPAGU - UPLOAD PAGES
// ===========================================

// Global object for upload functions
const UploadManager = {
    // Initialize all upload page functionality
    init: function() {
        this.initFileUpload();
        this.initFormValidation();
        this.initTemplateDownloads();
        this.initModeToggle();
        this.initFormReset();
        this.initAutoFill();
        
        // Check for URL parameters to show alerts
        this.checkUrlParams();
    },
    
    // Initialize file upload with drag & drop
    initFileUpload: function() {
        const dropArea  = document.querySelector('.upload-drop-area');
        const fileInput = document.querySelector('.upload-file-input');
        const fileInfo  = document.querySelector('.file-info');
        
        if (!dropArea || !fileInput) return;
        
        // Click on drop area triggers file input
        // FIX: Tambahkan e.stopPropagation() agar event click tidak bubble
        // ke document listener milik sidebar (adjustMenuHeight) secara tidak perlu.
        dropArea.addEventListener('click', (e) => {
            e.stopPropagation();
            if (e.target !== fileInput) {
                fileInput.click();
            }
        });
        
        // File input change
        fileInput.addEventListener('change', (e) => {
            this.handleFileSelect(e);
        });
        
        // Drag & Drop events
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.classList.add('dragover');
        });
        
        dropArea.addEventListener('dragleave', () => {
            dropArea.classList.remove('dragover');
        });
        
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                this.handleFileSelect(e);
            }
        });
    },
    
    // Handle file selection
    handleFileSelect: function(event) {
        const file = event.target.files?.[0] || event.dataTransfer?.files?.[0];
        
        if (!file) return;
        
        // Validate file type
        const allowedTypes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.spreadsheet'
        ];
        
        const fileExt  = file.name.split('.').pop().toLowerCase();
        const allowedExts = ['xls', 'xlsx', 'ods'];
        
        if (!allowedExts.includes(fileExt) && !allowedTypes.includes(file.type)) {
            this.showAlert('danger', 'File harus dalam format Excel (.xls, .xlsx)');
            return;
        }
        
        // Validate file size (10MB max)
        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            this.showAlert('danger', 'File terlalu besar. Maksimal 10MB.');
            return;
        }
        
        // Display file info
        const fileName   = document.querySelector('.file-name');
        const fileSize   = document.querySelector('.file-size');
        const progressBar = document.querySelector('.progress-fill');
        const fileInfo   = document.querySelector('.file-info');
        
        if (fileName)    fileName.textContent = file.name;
        if (fileSize)    fileSize.textContent = this.formatFileSize(file.size);
        if (progressBar) progressBar.style.width = '0%';
        if (fileInfo)    fileInfo.classList.add('active');
        
        // Simulate upload progress
        this.simulateUploadProgress(progressBar);
    },
    
    // Simulate upload progress (in production, this would be real)
    simulateUploadProgress: function(progressBar) {
        if (!progressBar) return;
        
        let width = 0;
        const interval = setInterval(() => {
            width += Math.random() * 15;
            if (width >= 100) {
                width = 100;
                clearInterval(interval);
                
                // Show success message
                setTimeout(() => {
                    this.showAlert('success', 'File berhasil diupload! Silakan lanjutkan proses.');
                }, 300);
            }
            progressBar.style.width = width + '%';
        }, 100);
    },
    
    // Format file size for display
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k     = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i     = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    // Initialize form validation
    initFormValidation: function() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    this.showAlert('warning', 'Harap periksa kembali data yang dimasukkan.');
                }
            });
            
            // Real-time validation
            const inputs = form.querySelectorAll('input[data-validate], select[data-validate], textarea[data-validate]');
            inputs.forEach(input => {
                input.addEventListener('blur', () => {
                    this.validateField(input);
                });
                
                input.addEventListener('input', () => {
                    this.clearFieldError(input);
                });
            });
        });
    },
    
    // Validate single field
    validateField: function(field) {
        const value     = field.value.trim();
        const fieldType = field.getAttribute('data-validate');
        
        let isValid      = true;
        let errorMessage = '';
        
        // Required validation
        if (field.hasAttribute('required') && !value) {
            isValid      = false;
            errorMessage = 'Field ini wajib diisi.';
        }
        
        // Type-specific validation
        if (value && isValid) {
            switch(fieldType) {
                case 'number':
                    if (isNaN(value) || value < 0) {
                        isValid      = false;
                        errorMessage = 'Harus berupa angka positif.';
                    }
                    break;
                    
                case 'email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        isValid      = false;
                        errorMessage = 'Format email tidak valid.';
                    }
                    break;
                    
                case 'phone':
                    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,15}$/;
                    if (!phoneRegex.test(value.replace(/\s/g, ''))) {
                        isValid      = false;
                        errorMessage = 'Format nomor telepon tidak valid.';
                    }
                    break;
                    
                case 'npp':
                    if (!/^\d{4}\.\d{2}\.\d{4}\.\d{3}$/.test(value)) {
                        isValid      = false;
                        errorMessage = 'Format NPP tidak valid (contoh: 0686.11.1995.071)';
                    }
                    break;
                    
                case 'nik':
                    if (!/^\d{16}$/.test(value)) {
                        isValid      = false;
                        errorMessage = 'NIK harus 16 digit angka.';
                    }
                    break;
            }
        }
        
        // Show/hide error
        if (!isValid) {
            this.showFieldError(field, errorMessage);
        } else {
            this.clearFieldError(field);
        }
        
        return isValid;
    },
    
    // Show field error
    showFieldError: function(field, message) {
        this.clearFieldError(field);
        
        const errorDiv           = document.createElement('div');
        errorDiv.className       = 'field-error';
        errorDiv.style.color     = '#e53e3e';
        errorDiv.style.fontSize  = '0.875rem';
        errorDiv.style.marginTop = '4px';
        errorDiv.textContent     = message;
        
        field.parentNode.appendChild(errorDiv);
        field.style.borderColor = '#e53e3e';
    },
    
    // Clear field error
    clearFieldError: function(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        field.style.borderColor = '';
    },
    
    // Validate entire form
    validateForm: function(form) {
        let isValid = true;
        const fields = form.querySelectorAll('[data-validate]');
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
                
                // Scroll to first error
                if (!isValid) {
                    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    field.focus();
                }
            }
        });
        
        return isValid;
    },
    
    // Initialize template downloads
    initTemplateDownloads: function() {
        const downloadBtns = document.querySelectorAll('.template-download-btn');
        
        downloadBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                
                const templateType = btn.getAttribute('data-template');
                const fileName     = `template_${templateType}.xlsx`;
                
                // Show loading
                const originalText = btn.innerHTML;
                btn.innerHTML      = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan...';
                btn.disabled       = true;
                
                // Simulate download (in production, this would trigger actual download)
                setTimeout(() => {
                    this.showAlert('success', `Template ${fileName} berhasil diunduh.`);
                    
                    // Reset button
                    btn.innerHTML = originalText;
                    btn.disabled  = false;
                }, 1500);
            });
        });
    },
    
    // Initialize mode toggle (upload vs manual)
    initModeToggle: function() {
        const modeBtns       = document.querySelectorAll('.mode-btn');
        const uploadSection  = document.getElementById('uploadSection');
        const manualSection  = document.getElementById('manualSection');
        
        if (!modeBtns.length || !uploadSection || !manualSection) return;
        
        modeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Update active button
                modeBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Show/hide sections
                if (btn.getAttribute('data-mode') === 'upload') {
                    uploadSection.classList.remove('hidden');
                    manualSection.classList.add('hidden');
                } else {
                    uploadSection.classList.add('hidden');
                    manualSection.classList.remove('hidden');
                }
            });
        });
    },
    
    // Initialize form reset
    initFormReset: function() {
        const resetBtns = document.querySelectorAll('.btn-reset');
        
        resetBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const formId = btn.getAttribute('data-form');
                const form   = document.getElementById(formId);
                
                if (form) {
                    form.reset();
                    
                    // Clear file inputs
                    const fileInputs = form.querySelectorAll('input[type="file"]');
                    fileInputs.forEach(input => {
                        input.value = '';
                    });
                    
                    // Hide file info
                    const fileInfo = document.querySelector('.file-info');
                    if (fileInfo) {
                        fileInfo.classList.remove('active');
                    }
                    
                    // Clear validation errors
                    const errors = form.querySelectorAll('.field-error');
                    errors.forEach(error => error.remove());
                    
                    this.showAlert('info', 'Form telah direset.');
                }
            });
        });
    },
    
    // Initialize auto-fill from filename
    initAutoFill: function() {
        // This would auto-fill form fields based on filename patterns
        // Example: filename "user_data_20241.xlsx" would auto-fill semester field
        console.log('Auto-fill initialized');
    },
    
    // Show alert message
    showAlert: function(type, message) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert:not(.persistent)');
        existingAlerts.forEach(alert => {
            alert.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        });
        
        // Create new alert
        const alertDiv   = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        
        const icons = {
            'success': 'check-circle',
            'danger' : 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info'   : 'info-circle'
        };
        
        const icon = icons[type] || 'info-circle';
        
        alertDiv.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${icon} mr-3"></i>
                <div>${message}</div>
            </div>
        `;
        
        // Insert at the beginning of upload container
        const uploadContainer = document.querySelector('.upload-container');
        if (uploadContainer) {
            uploadContainer.insertBefore(alertDiv, uploadContainer.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => alertDiv.remove(), 300);
                }
            }, 5000);
        }
    },
    
    // Check URL parameters for messages
    checkUrlParams: function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('success')) {
            const message = decodeURIComponent(urlParams.get('success'));
            this.showAlert('success', message);
        }
        
        if (urlParams.has('error')) {
            const message = decodeURIComponent(urlParams.get('error'));
            this.showAlert('danger', message);
        }
        
        if (urlParams.has('warning')) {
            const message = decodeURIComponent(urlParams.get('warning'));
            this.showAlert('warning', message);
        }
    }
};

// ===========================================
// MOBILE MENU TOGGLE FIX
// ===========================================

const MobileMenuManager = {
    isMenuOpen: false,
    
    init: function() {
        this.initMobileMenu();
    },
    
    initMobileMenu: function() {
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const navLinks      = document.getElementById('navLinks');
        const menuOverlay   = document.getElementById('menuOverlay');
        
        // FIX BUG 2: Jika elemen tidak ditemukan (misalnya di halaman admin
        // yang tidak pakai hamburger frontend), keluar dengan aman.
        // Ini mencegah MobileMenuManager crash di halaman admin.
        if (!hamburgerMenu || !navLinks || !menuOverlay) return;
        
        // Event listener untuk hamburger menu
        hamburgerMenu.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleMenu();
        });
        
        // Event listener untuk overlay
        menuOverlay.addEventListener('click', (e) => {
            e.stopPropagation();
            this.closeMenu();
        });
        
        // Event listener untuk link di dalam menu
        const menuLinks = navLinks.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                this.closeMenu();
            });
        });
        
        // FIX BUG 1 RESIZE: Saat resize ke desktop dan menu terbuka,
        // closeMenu() harus ikut memanggil BodyScrollManager.unlock().
        // Sebelumnya unlock tidak terjamin jika BodyScrollManager belum ready
        // saat openMenu() dipanggil — sekarang closeMenu() sudah handle fallback.
        window.addEventListener('resize', () => {
            if (window.innerWidth > 767 && this.isMenuOpen) {
                this.closeMenu();
            }
        });
    },
    
    toggleMenu: function() {
        if (this.isMenuOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    },
    
    openMenu: function() {
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const navLinks      = document.getElementById('navLinks');
        const menuOverlay   = document.getElementById('menuOverlay');
        
        hamburgerMenu.classList.add('active');
        navLinks.classList.add('active');
        menuOverlay.classList.add('active');
        document.body.classList.add('menu-active');

        // FIX BUG 1: Guard robust — BodyScrollManager mungkin belum ada
        // jika sidebar_admin.php belum selesai di-parse saat custom.js jalan.
        // Gunakan polling singkat sebagai fallback aman.
        if (window.BodyScrollManager) {
            window.BodyScrollManager.lock();
        } else {
            // Fallback: langsung set overflow, akan di-override BodyScrollManager nanti
            document.body.style.overflow = 'hidden';
        }
        
        this.isMenuOpen = true;
    },
    
    closeMenu: function() {
        const hamburgerMenu = document.getElementById('hamburgerMenu');
        const navLinks      = document.getElementById('navLinks');
        const menuOverlay   = document.getElementById('menuOverlay');

        // Guard null — elemen mungkin tidak ada di halaman admin
        if (hamburgerMenu) hamburgerMenu.classList.remove('active');
        if (navLinks)      navLinks.classList.remove('active');
        if (menuOverlay)   menuOverlay.classList.remove('active');
        document.body.classList.remove('menu-active');

        // FIX BUG 1: Gunakan BodyScrollManager jika tersedia,
        // fallback ke reset manual jika belum diinisialisasi
        if (window.BodyScrollManager) {
            window.BodyScrollManager.unlock();
        } else {
            document.body.style.overflow = '';
        }
        
        this.isMenuOpen = false;
    }
};

// ===========================================
// NAVBAR SCROLL EFFECT
// ===========================================

const NavbarScrollManager = {
    init: function() {
        this.initNavbarScroll();
    },
    
    initNavbarScroll: function() {
        const navbar = document.getElementById('navbar');
        
        if (!navbar) return;
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Initialize on load
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        }
    }
};

// Inisialisasi semua fungsi saat DOM loaded
document.addEventListener('DOMContentLoaded', function() {
    // Upload Manager untuk halaman upload
    if (document.querySelector('.upload-page')) {
        UploadManager.init();
    }
    
    // Mobile Menu Manager untuk semua halaman
    MobileMenuManager.init();
    
    // Navbar Scroll Manager untuk semua halaman
    NavbarScrollManager.init();
    
    // Back to Top Button
    const backToTopButton = document.getElementById('backToTop');
    
    if (backToTopButton) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'block';
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.style.display = 'none';
                backToTopButton.classList.remove('visible');
            }
        });
        
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Animasi fade-in
    const fadeElements = document.querySelectorAll('.fade-in');
    
    const checkFade = () => {
        fadeElements.forEach(element => {
            const elementTop     = element.getBoundingClientRect().top;
            const elementVisible = 150;
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('visible');
            }
        });
    };
    
    window.addEventListener('scroll', checkFade);
    checkFade(); // Check on load
});