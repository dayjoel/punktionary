// navbar.js - Navbar functionality
let navbarInitialized = false;
let currentUser = null;

// Try to initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  // Wait for navbar to be loaded if it's fetched
  waitForNavbarAndInit();
});

// Wait for navbar elements to exist before initializing
function waitForNavbarAndInit() {
  const checkNavbar = setInterval(() => {
    // Check if navbar elements exist
    if (document.getElementById('desktopLoginBtn')) {
      clearInterval(checkNavbar);
      initNavbar();
    }
  }, 50); // Check every 50ms

  // Timeout after 5 seconds to prevent infinite waiting
  setTimeout(() => clearInterval(checkNavbar), 5000);
}

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

  // Login overlay - setup event listeners
  setupLoginModal();

  // Logout handlers
  const desktopLogoutBtn = document.getElementById('desktopLogoutBtn');
  const mobileLogoutBtn = document.getElementById('mobileLogoutBtn');

  desktopLogoutBtn?.addEventListener('click', function() {
    window.location.href = '/auth/logout.php';
  });

  mobileLogoutBtn?.addEventListener('click', function() {
    window.location.href = '/auth/logout.php';
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

  // Update user profile picture and name
  const userProfilePic = document.getElementById('userProfilePic');
  const userFirstName = document.getElementById('userFirstName');

  if (userProfilePic) {
    userProfilePic.src = user.profile_picture_url || '/images/default-avatar.svg';
  }

  if (userFirstName) {
    // Get first name from display_name (first word) or email
    let firstName = 'User';
    if (user.display_name) {
      firstName = user.display_name.split(' ')[0];
    } else if (user.email) {
      firstName = user.email.split('@')[0];
    }
    userFirstName.textContent = firstName;
  }

  // Setup user dropdown toggle (after elements are visible)
  setupUserDropdown();

  // Show logged-in state (mobile)
  document.getElementById('mobileLoginBtn')?.classList.add('hidden');
  document.getElementById('mobileProfileLink')?.classList.remove('hidden');
  document.getElementById('mobileSubmissionsLink')?.classList.remove('hidden');
  document.getElementById('mobileLogoutBtn')?.classList.remove('hidden');

  // Show admin panel link if user is admin or god (account_type >= 1)
  if (user.account_type >= 1) {
    document.getElementById('adminPanelLink')?.classList.remove('hidden');
    document.getElementById('mobileAdminPanelLink')?.classList.remove('hidden');

    // Load and display pending edits count
    loadPendingEditsCount();
  }
}

// Load pending edits count for admin badge
async function loadPendingEditsCount() {
  try {
    const response = await fetch('/api/get_pending_edits.php?status=pending');
    const data = await response.json();

    if (data.success && data.counts.pending > 0) {
      const count = data.counts.pending;
      document.getElementById('adminBadge').textContent = count;
      document.getElementById('mobileAdminBadge').textContent = count;
    }
  } catch (error) {
    console.error('Failed to load pending edits count:', error);
  }
}

// Setup user dropdown event listeners
let dropdownListenersAdded = false;
function setupUserDropdown() {
  if (dropdownListenersAdded) return; // Prevent multiple listeners

  const userMenuToggle = document.getElementById('userMenuToggle');
  const userDropdown = document.getElementById('userDropdown');

  if (userMenuToggle && userDropdown) {
    userMenuToggle.addEventListener('click', function(e) {
      e.stopPropagation();
      userDropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      if (userDropdown && !userDropdown.contains(e.target) && !userMenuToggle.contains(e.target)) {
        userDropdown.classList.add('hidden');
      }
    });

    dropdownListenersAdded = true;
  }
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

// Setup login modal event listeners
function setupLoginModal() {
  const loginBtns = document.querySelectorAll('#desktopLoginBtn, #mobileLoginBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileBackdrop = document.getElementById('mobileBackdrop');

  loginBtns.forEach(btn => {
    if (btn) {
      btn.addEventListener('click', function() {
        // Wait a bit for modal to be loaded if needed
        setTimeout(() => {
          const loginOverlay = document.getElementById('loginOverlay');
          if (loginOverlay) {
            loginOverlay.classList.remove('hidden');
          }
          // Close mobile menu if open
          if (mobileMenu) mobileMenu.classList.add('translate-x-full');
          if (mobileBackdrop) mobileBackdrop.classList.add('opacity-0', 'pointer-events-none');
        }, 50);
      });
    }
  });
}

// Initialize login modal controls when modal is loaded
function initLoginModal() {
  const loginOverlay = document.getElementById('loginOverlay');
  const closeLogin = document.getElementById('closeLogin');

  closeLogin?.addEventListener('click', function() {
    if (loginOverlay) {
      loginOverlay.classList.add('hidden');
    }
  });

  loginOverlay?.addEventListener('click', function(e) {
    if (e.target === loginOverlay) {
      loginOverlay.classList.add('hidden');
    }
  });
}