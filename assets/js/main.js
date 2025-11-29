/**
 * Culture Radar - Main JavaScript
 * Handles interactions, animations, and dynamic content
 */

// DOM Content Loaded Event
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    initializeScrollAnimations();
    initializeSearch();
    initializeFilters();
    initializeCookieBanner();
    initializeStats();
    initializeMobileMenu();
    initializeFloatingElements();
    
    // Show scroll reveal elements on page load
    setTimeout(() => {
        const elements = document.querySelectorAll('.scroll-reveal');
        elements.forEach((el, index) => {
            setTimeout(() => {
                el.classList.add('revealed');
            }, index * 100);
        });
    }, 500);
}

/**
 * Scroll animations using Intersection Observer
 */
function initializeScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all scroll reveal elements
    document.querySelectorAll('.scroll-reveal').forEach(el => {
        observer.observe(el);
    });
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchForm = document.querySelector('.search-form');
    const searchInput = document.querySelector('.search-input');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleSearch(searchInput.value);
        });
    }
    
    // Add search suggestions (placeholder for future enhancement)
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            const query = e.target.value;
            if (query.length > 2) {
                // Future: Show search suggestions
                console.log('Search suggestions for:', query);
            }
        }, 300));
    }
}

/**
 * Handle search functionality
 */
function handleSearch(query) {
    if (!query.trim()) return;
    
    // Show loading state
    const searchButton = document.querySelector('.search-button');
    const originalContent = searchButton.innerHTML;
    searchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Simulate search (replace with actual search logic)
    setTimeout(() => {
        searchButton.innerHTML = originalContent;
        // Future: Redirect to search results or show results
        console.log('Searching for:', query);
        window.location.href = `/search.php?q=${encodeURIComponent(query)}`;
    }, 1000);
}

/**
 * Initialize filter functionality
 */
function initializeFilters() {
    const filterChips = document.querySelectorAll('.filter-chip');
    
    filterChips.forEach(chip => {
        chip.addEventListener('click', function() {
            // Remove active class from all chips
            filterChips.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked chip
            this.classList.add('active');
            
            // Get filter value
            const filterValue = this.dataset.filter;
            handleFilter(filterValue);
        });
    });
}

/**
 * Handle filter functionality
 */
function handleFilter(filter) {
    console.log('Filtering by:', filter);
    
    // Add visual feedback
    const activeChip = document.querySelector('.filter-chip.active');
    if (activeChip) {
        activeChip.style.transform = 'translateY(-2px) scale(1.05)';
        setTimeout(() => {
            activeChip.style.transform = '';
        }, 200);
    }
    
    // Future: Apply actual filtering logic
    // This could update the demo events or redirect to filtered results
}

/**
 * Initialize cookie banner
 */
function initializeCookieBanner() {
    const cookieBanner = document.getElementById('cookie-banner');
    
    if (!cookieBanner) return;
    
    // Check if user has already made a choice
    const cookieConsent = localStorage.getItem('cookieConsent');
    
    if (!cookieConsent) {
        // Show banner after a delay
        setTimeout(() => {
            cookieBanner.style.display = 'block';
            cookieBanner.style.animation = 'fade-in-up 0.5s ease-out';
        }, 2000);
    } else {
        cookieBanner.style.display = 'none';
    }
}

/**
 * Accept all cookies
 */
function acceptAllCookies() {
    localStorage.setItem('cookieConsent', 'accepted');
    hideCookieBanner();
    
    // Initialize analytics or other tracking
    console.log('Cookies accepted - initializing tracking');
}

/**
 * Reject cookies
 */
function rejectCookies() {
    localStorage.setItem('cookieConsent', 'rejected');
    hideCookieBanner();
    
    console.log('Cookies rejected - no tracking initialized');
}

/**
 * Hide cookie banner
 */
function hideCookieBanner() {
    const cookieBanner = document.getElementById('cookie-banner');
    if (cookieBanner) {
        cookieBanner.classList.add('hidden');
        setTimeout(() => {
            cookieBanner.style.display = 'none';
        }, 300);
    }
}

/**
 * Initialize statistics counter animation
 */
function initializeStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    const animateCounter = (element) => {
        const target = parseInt(element.dataset.count);
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current).toLocaleString();
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target.toLocaleString();
            }
        };
        
        updateCounter();
    };
    
    // Use Intersection Observer to trigger animation when stats come into view
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statNumber = entry.target.querySelector('.stat-number');
                if (statNumber && !statNumber.classList.contains('animated')) {
                    statNumber.classList.add('animated');
                    animateCounter(statNumber);
                }
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('.stat-card').forEach(card => {
        statsObserver.observe(card);
    });
}

/**
 * Initialize mobile menu
 */
function initializeMobileMenu() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            this.classList.toggle('active');
            
            // Animate hamburger menu
            const spans = this.querySelectorAll('span');
            spans.forEach((span, index) => {
                span.style.transform = this.classList.contains('active') 
                    ? `rotate(${index === 1 ? 0 : index === 0 ? 45 : -45}deg) translate(${index === 0 ? '6px, 6px' : index === 2 ? '6px, -6px' : '0, 0'})`
                    : 'none';
                span.style.opacity = this.classList.contains('active') && index === 1 ? '0' : '1';
            });
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileToggle.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('active');
                mobileToggle.classList.remove('active');
                
                const spans = mobileToggle.querySelectorAll('span');
                spans.forEach(span => {
                    span.style.transform = 'none';
                    span.style.opacity = '1';
                });
            }
        });
    }
}

/**
 * Initialize floating background elements
 */
function initializeFloatingElements() {
    const floatingShapes = document.querySelector('.floating-shapes');
    
    if (floatingShapes) {
        // Create additional floating elements
        for (let i = 0; i < 8; i++) {
            const element = document.createElement('div');
            element.style.cssText = `
                position: absolute;
                width: ${Math.random() * 60 + 20}px;
                height: ${Math.random() * 60 + 20}px;
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                top: ${Math.random() * 100}%;
                left: ${Math.random() * 100}%;
                animation: float ${Math.random() * 20 + 15}s infinite ease-in-out;
                animation-delay: ${Math.random() * 20}s;
                backdrop-filter: blur(10px);
            `;
            floatingShapes.appendChild(element);
        }
    }
}

/**
 * Smooth scroll for anchor links
 */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        
        if (target) {
            const headerOffset = 80;
            const elementPosition = target.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
            
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    });
});

/**
 * Header scroll effect
 */
window.addEventListener('scroll', debounce(function() {
    const header = document.querySelector('.header');
    const scrolled = window.scrollY > 50;
    
    if (header) {
        header.style.background = scrolled 
            ? 'rgba(10, 10, 15, 0.95)' 
            : 'rgba(10, 10, 15, 0.9)';
        header.style.backdropFilter = scrolled ? 'blur(30px)' : 'blur(20px)';
    }
}, 10));

/**
 * Form validation helpers
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePassword(password) {
    const requirements = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        digit: /\d/.test(password)
    };
    return Object.values(requirements).every(req => req);
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--warning)' : 'var(--primary)'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: var(--shadow-xl);
        z-index: 1002;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 400px;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after delay
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 5000);
}

/**
 * Debounce utility function
 */
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        
        if (callNow) func.apply(context, args);
    };
}

/**
 * Throttle utility function
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

/**
 * API helper functions
 */
const API = {
    baseURL: '/api/',
    
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'API request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            showNotification(error.message || 'Une erreur est survenue', 'error');
            throw error;
        }
    },
    
    get(endpoint) {
        return this.request(endpoint);
    },
    
    post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },
    
    put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },
    
    delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    }
};

/**
 * Local storage helpers
 */
const Storage = {
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error('Storage error:', error);
        }
    },
    
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('Storage error:', error);
            return defaultValue;
        }
    },
    
    remove(key) {
        try {
            localStorage.removeItem(key);
        } catch (error) {
            console.error('Storage error:', error);
        }
    },
    
    clear() {
        try {
            localStorage.clear();
        } catch (error) {
            console.error('Storage error:', error);
        }
    }
};

/**
 * Performance monitoring
 */
function measurePerformance() {
    if ('performance' in window) {
        window.addEventListener('load', () => {
            const timing = performance.timing;
            const loadTime = timing.loadEventEnd - timing.navigationStart;
            console.log(`Page load time: ${loadTime}ms`);
            
            // Send to analytics if needed
            if (Storage.get('cookieConsent') === 'accepted') {
                // Future: Send performance data to analytics
            }
        });
    }
}

/**
 * Accessibility helpers
 */
function announceToScreenReader(message) {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = message;
    
    document.body.appendChild(announcement);
    
    setTimeout(() => {
        document.body.removeChild(announcement);
    }, 1000);
}

/**
 * Keyboard navigation helpers
 */
document.addEventListener('keydown', function(e) {
    // Escape key closes mobile menu
    if (e.key === 'Escape') {
        const mobileMenu = document.querySelector('.nav-links.active');
        const mobileToggle = document.querySelector('.mobile-menu-toggle.active');
        
        if (mobileMenu && mobileToggle) {
            mobileMenu.classList.remove('active');
            mobileToggle.classList.remove('active');
            
            const spans = mobileToggle.querySelectorAll('span');
            spans.forEach(span => {
                span.style.transform = 'none';
                span.style.opacity = '1';
            });
        }
    }
    
    // Enter key on search
    if (e.key === 'Enter' && e.target.classList.contains('search-input')) {
        e.preventDefault();
        handleSearch(e.target.value);
    }
});

/**
 * Error handling
 */
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
    
    // Show user-friendly error message
    if (!document.querySelector('.notification-error')) {
        showNotification('Une erreur inattendue s\'est produite. Veuillez rafraîchir la page.', 'error');
    }
});

/**
 * Initialize performance monitoring
 */
measurePerformance();

/**
 * Make functions globally available for inline event handlers
 */
window.acceptAllCookies = acceptAllCookies;
window.rejectCookies = rejectCookies;
window.showNotification = showNotification;
window.API = API;
window.Storage = Storage;