// navbar.js - Navbar functionality
let navbarInitialized = false;

document.addEventListener('DOMContentLoaded', function() {
  // Wait a bit for navbar to be loaded if it's fetched
  setTimeout(initNavbar, 100);
});

function initNavbar() {
  // Prevent double initialization
  if (navbarInitialized) {
    return;
  }
  navbarInitialized = true;
  // Active page highlighting
  const currentPath = window.location.pathname;
  const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');
  
  navLinks.forEach(link => {
    const linkPath = link.getAttribute('href');
    if (linkPath === currentPath || (currentPath === '/' && linkPath === '/') || 
        (currentPath.includes(link.dataset.page) && link.dataset.page)) {
      link.classList.add('active');
    }
  });

  // Submit dropdown
  const submitBtn = document.getElementById('submitButton');
  const submitMenu = document.getElementById('submitMenu');
  const submitArrow = document.getElementById('submitArrow');
  
  if (submitBtn && submitMenu) {
    submitBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      e.preventDefault();
      const isHidden = submitMenu.classList.contains('hidden');
      
      if (isHidden) {
        submitMenu.classList.remove('hidden');
        setTimeout(() => submitMenu.classList.remove('opacity-0'), 10);
        if (submitArrow) submitArrow.style.transform = 'rotate(180deg)';
      } else {
        submitMenu.classList.add('opacity-0');
        setTimeout(() => submitMenu.classList.add('hidden'), 200);
        if (submitArrow) submitArrow.style.transform = 'rotate(0deg)';
      }
    });
  }

  // User dropdown
  const userBtn = document.getElementById('userButton');
  const userMenu = document.getElementById('userMenu');
  
  if (userBtn && userMenu) {
    userBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      const isHidden = userMenu.classList.contains('hidden');
      
      if (isHidden) {
        userMenu.classList.remove('hidden');
        setTimeout(() => userMenu.classList.remove('opacity-0'), 10);
      } else {
        userMenu.classList.add('opacity-0');
        setTimeout(() => userMenu.classList.add('hidden'), 200);
      }
    });
  }

  // Close dropdowns when clicking outside
  document.addEventListener('click', function(e) {
    // Don't close if clicking on the submit button or inside the submit menu
    const clickedSubmitButton = e.target.closest('#submitButton');
    const clickedInsideSubmitMenu = e.target.closest('#submitMenu');
    
    if (!clickedSubmitButton && !clickedInsideSubmitMenu && submitMenu && !submitMenu.classList.contains('hidden')) {
      submitMenu.classList.add('opacity-0');
      setTimeout(() => submitMenu.classList.add('hidden'), 200);
      if (submitArrow) submitArrow.style.transform = 'rotate(0deg)';
    }
    
    // Don't close if clicking on the user button or inside the user menu
    const clickedUserButton = e.target.closest('#userButton');
    const clickedInsideUserMenu = e.target.closest('#userMenu');
    
    if (!clickedUserButton && !clickedInsideUserMenu && userMenu && !userMenu.classList.contains('hidden')) {
      userMenu.classList.add('opacity-0');
      setTimeout(() => userMenu.classList.add('hidden'), 200);
    }
  });

  // Mobile menu toggle
  const menuToggle = document.getElementById('menuToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileBackdrop = document.getElementById('mobileBackdrop');
  
  if (menuToggle && mobileMenu && mobileBackdrop) {
    menuToggle.addEventListener('click', function() {
      const isOpen = !mobileMenu.classList.contains('translate-x-full');
      
      if (isOpen) {
        // Close menu
        mobileMenu.classList.add('translate-x-full');
        mobileBackdrop.classList.add('opacity-0', 'pointer-events-none');
        menuToggle.setAttribute('aria-expanded', 'false');
        
        // Reset hamburger
        const lines = menuToggle.querySelectorAll('.hamburger-line');
        lines[0].style.transform = 'rotate(0) translateY(0)';
        lines[1].style.opacity = '1';
        lines[2].style.transform = 'rotate(0) translateY(0)';
      } else {
        // Open menu
        mobileMenu.classList.remove('translate-x-full');
        mobileBackdrop.classList.remove('opacity-0', 'pointer-events-none');
        menuToggle.setAttribute('aria-expanded', 'true');
        
        // Animate to X
        const lines = menuToggle.querySelectorAll('.hamburger-line');
        lines[0].style.transform = 'rotate(45deg) translateY(8px)';
        lines[1].style.opacity = '0';
        lines[2].style.transform = 'rotate(-45deg) translateY(-8px)';
      }
    });

    // Close menu when clicking backdrop
    mobileBackdrop.addEventListener('click', function() {
      mobileMenu.classList.add('translate-x-full');
      mobileBackdrop.classList.add('opacity-0', 'pointer-events-none');
      menuToggle.setAttribute('aria-expanded', 'false');
      
      const lines = menuToggle.querySelectorAll('.hamburger-line');
      lines[0].style.transform = 'rotate(0) translateY(0)';
      lines[1].style.opacity = '1';
      lines[2].style.transform = 'rotate(0) translateY(0)';
    });
  }

  // Login overlay
  const loginBtns = document.querySelectorAll('#loginBtn, #mobileLoginBtn');
  const loginOverlay = document.getElementById('loginOverlay');
  const closeLogin = document.getElementById('closeLogin');
  
  loginBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      loginOverlay?.classList.remove('hidden');
      // Close mobile menu if open
      if (mobileMenu) mobileMenu.classList.add('translate-x-full');
      if (mobileBackdrop) mobileBackdrop.classList.add('opacity-0', 'pointer-events-none');
    });
  });

  closeLogin?.addEventListener('click', function() {
    loginOverlay?.classList.add('hidden');
  });

  loginOverlay?.addEventListener('click', function(e) {
    if (e.target === loginOverlay) {
      loginOverlay.classList.add('hidden');
    }
  });
}