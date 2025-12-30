// navbar.js - Navbar functionality
let navbarInitialized = false;
let currentUser = null;

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

  // No dropdowns needed anymore - removed submit dropdown and user dropdown code

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
  const loginBtns = document.querySelectorAll('#desktopLoginBtn, #mobileLoginBtn');
  const loginOverlay = document.getElementById('loginOverlay');
  const closeLogin = document.getElementById('closeLogin');

  loginBtns.forEach(btn => {
    if (btn) {
      btn.addEventListener('click', function() {
        if (loginOverlay) {
          loginOverlay.style.display = 'flex';
        }
        // Close mobile menu if open
        if (mobileMenu) mobileMenu.classList.add('translate-x-full');
        if (mobileBackdrop) mobileBackdrop.classList.add('opacity-0', 'pointer-events-none');
      });
    }
  });

  closeLogin?.addEventListener('click', function() {
    if (loginOverlay) {
      loginOverlay.style.display = 'none';
    }
  });

  loginOverlay?.addEventListener('click', function(e) {
    if (e.target === loginOverlay) {
      loginOverlay.style.display = 'none';
    }
  });

  // Logout handlers
  const desktopLogoutBtn = document.getElementById('desktopLogoutBtn');
  const mobileLogoutBtn = document.getElementById('mobileLogoutBtn');

  desktopLogoutBtn?.addEventListener('click', async function() {
    try {
      await fetch('/auth/logout.php');
      window.location.href = '/';
    } catch (error) {
      console.error('Logout failed:', error);
    }
  });

  mobileLogoutBtn?.addEventListener('click', async function() {
    try {
      await fetch('/auth/logout.php');
      window.location.href = '/';
    } catch (error) {
      console.error('Logout failed:', error);
    }
  });

  // Check authentication status on page load
  checkAuthStatus();
}

// Check if user is authenticated
async function checkAuthStatus() {
  try {
    const response = await fetch('/auth/check_auth.php');
    const data = await response.json();

    if (data.authenticated) {
      currentUser = data.user;
      updateNavbarForLoggedIn(data.user);
    } else {
      currentUser = null;
      updateNavbarForLoggedOut();
    }
  } catch (error) {
    console.error('Auth check failed:', error);
    updateNavbarForLoggedOut();
  }
}

// Update navbar to show logged-in state
function updateNavbarForLoggedIn(user) {
  // Show logged-in state (desktop)
  const desktopLoginBtn = document.getElementById('desktopLoginBtn');
  const desktopUserSection = document.getElementById('desktopUserSection');

  if (desktopLoginBtn) desktopLoginBtn.classList.add('hidden');
  if (desktopUserSection) desktopUserSection.classList.remove('hidden');

  // Show logged-in state (mobile)
  document.getElementById('mobileLoginBtn')?.classList.add('hidden');
  document.getElementById('mobileProfileLink')?.classList.remove('hidden');
  document.getElementById('mobileSubmissionsLink')?.classList.remove('hidden');
  document.getElementById('mobileLogoutBtn')?.classList.remove('hidden');
}

// Update navbar to show logged-out state
function updateNavbarForLoggedOut() {
  // Show logged-out state (desktop)
  const desktopLoginBtn = document.getElementById('desktopLoginBtn');
  const desktopUserSection = document.getElementById('desktopUserSection');

  if (desktopLoginBtn) desktopLoginBtn.classList.remove('hidden');
  if (desktopUserSection) desktopUserSection.classList.add('hidden');

  // Show logged-out state (mobile)
  document.getElementById('mobileLoginBtn')?.classList.remove('hidden');
  document.getElementById('mobileProfileLink')?.classList.add('hidden');
  document.getElementById('mobileSubmissionsLink')?.classList.add('hidden');
  document.getElementById('mobileLogoutBtn')?.classList.add('hidden');
}