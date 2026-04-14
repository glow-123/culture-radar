/* Culture Radar — Script principal
   Gloria AMINI | InnovaDigital Agency | 2025
   Aucun emoji dans ce fichier
*/

document.addEventListener('DOMContentLoaded', function() {

  // ── NAVIGATION ACTIVE ──
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-link').forEach(link => {
    const href = link.getAttribute('href');
    if (href && href.includes(currentPage)) {
      link.classList.add('active');
    }
  });

  // ── FILTRES EVENEMENTS ──
  const filterChips = document.querySelectorAll('.filter-chip');
  filterChips.forEach(chip => {
    chip.addEventListener('click', function(e) {
      // Ne pas empecher la navigation si c'est un lien
      if (this.tagName === 'A') return;
      
      // Sinon gerer l'etat actif
      const parent = this.closest('.filters-bar, .cats-nav');
      if (parent) {
        parent.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
      }
    });
  });

  // ── ANIMATION AU SCROLL ──
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.feature-card, .step, .avantage-card, .tarif-card').forEach(el => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      observer.observe(el);
    });
  }

  // ── HEADER SCROLL ──
  const header = document.querySelector('.site-header');
  if (header) {
    window.addEventListener('scroll', function() {
      if (window.scrollY > 60) {
        header.style.boxShadow = '0 4px 30px rgba(0,0,0,0.4)';
      } else {
        header.style.boxShadow = '0 2px 20px rgba(0,0,0,0.3)';
      }
    }, { passive: true });
  }

  // ── MENU MOBILE ──
  // Le menu nav est cache sur mobile avec CSS
  // Sur mobile on pourrait ajouter un burger menu ici

  // ── ANCRES SMOOTH SCROLL ──
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ── ACCESSIBILITE : Focus visible ──
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Tab') {
      document.body.classList.add('keyboard-navigation');
    }
  });
  document.addEventListener('mousedown', function() {
    document.body.classList.remove('keyboard-navigation');
  });

});
