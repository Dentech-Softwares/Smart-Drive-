// Main JavaScript for Smart Drive Car Hire

function initMain() {
    
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            const icon = this.querySelector('i');
            if (navMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
        
        // Close menu when clicking a link
        navMenu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
                const icon = menuToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!menuToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('active');
                const icon = menuToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    
    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            const message = this.getAttribute('data-confirm');
            Swal.fire({
                title: 'Are you sure?',
                text: message || 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#8B0000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, proceed!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.getAttribute('href');
                }
            });
        });
    });
    
    // Auto-submit forms on change for filters
    document.querySelectorAll('.auto-submit').forEach(function(select) {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
    
    // Character counter for textareas
    document.querySelectorAll('textarea[maxlength]').forEach(function(textarea) {
        const max = textarea.getAttribute('maxlength');
        const counter = document.createElement('small');
        counter.className = 'text-muted';
        counter.style.float = 'right';
        textarea.parentNode.appendChild(counter);
        
        textarea.addEventListener('input', function() {
            counter.textContent = (max - this.value.length) + ' characters remaining';
        });
    });
    
    // Image preview
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function(input) {
        const previewId = input.getAttribute('data-preview');
        const preview = document.getElementById(previewId);
        
        if (preview) {
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    });
    
    // Loading state for buttons
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                const originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner spinner-sm"></span> Processing...';
                btn.disabled = true;
                btn.setAttribute('data-original-text', originalText);
            }
        });
    });
    
    // Sidebar toggle for mobile
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar && sidebarOverlay) {
        const menuToggleBtn = document.getElementById('sidebarToggle');
        if (menuToggleBtn) {
            menuToggleBtn.addEventListener('click', function() {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
            });
        }
        
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Initialize counters with animation
    const counters = document.querySelectorAll('.counter');
    if (counters.length > 0) {
        const animateCounter = function(counter) {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000;
            const step = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(function() {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                counter.textContent = Math.floor(current).toLocaleString();
            }, 16);
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(function(counter) {
            observer.observe(counter);
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMain);
} else {
    initMain();
}
