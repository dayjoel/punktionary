// navbar.js
async function loadNavbar() {
  const response = await fetch('/navbar.html');
  const navbarHTML = await response.text();
  document.body.insertAdjacentHTML('afterbegin', navbarHTML);

  // Element references
  const submitButton = document.getElementById('submitButton');
  const submitMenu = document.getElementById('submitMenu');
  const userButton = document.getElementById('userButton');
  const userMenu = document.getElementById('userMenu');
  const loginBtn = document.getElementById('loginBtn');
  const loginOverlay = document.getElementById('loginOverlay');
  const loginCancel = document.getElementById('loginCancel');
  const menuToggle = document.getElementById('menuToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileBackdrop = document.getElementById('mobileBackdrop');
  const mobileLoginBtn = document.getElementById('mobileLoginBtn');
  const bars = menuToggle?.querySelectorAll('span');

  let submitOpen = false, userOpen = false, mobileOpen = false;

  // ----- Dropdown Toggles -----
  function toggleSubmit() {
    if (!submitMenu) return;
    submitMenu.classList.toggle('hidden');
    submitMenu.classList.toggle('opacity-0');
    submitOpen = !submitOpen;
  }

  function toggleUser() {
    if (!userMenu) return;
    userMenu.classList.toggle('hidden');
    userMenu.classList.toggle('opacity-0');
    userOpen = !userOpen;
  }

  submitButton?.addEventListener('click', e => { e.stopPropagation(); toggleSubmit(); });
  userButton?.addEventListener('click', e => { e.stopPropagation(); toggleUser(); });

  // Close dropdowns on outside click
  document.addEventListener('click', e => {
    if (submitOpen && !submitButton.contains(e.target) && !submitMenu.contains(e.target)) toggleSubmit();
    if (userOpen && !userButton.contains(e.target) && !userMenu.contains(e.target)) toggleUser();
  });

  // ----- Login Overlay -----
  loginBtn?.addEventListener('click', () => {
    loginOverlay.classList.remove('hidden');
    loginOverlay.classList.add('flex');
    if (userOpen) toggleUser();
  });

  mobileLoginBtn?.addEventListener('click', () => {
    loginOverlay.classList.remove('hidden');
    loginOverlay.classList.add('flex');
    if (mobileOpen) {
      mobileOpen = false;
      closeMobile();
    }
  });

  loginCancel?.addEventListener('click', () => {
    loginOverlay.classList.add('hidden');
    loginOverlay.classList.remove('flex');
  });

  loginOverlay?.addEventListener('click', e => {
    if (e.target === loginOverlay) {
      loginOverlay.classList.add('hidden');
      loginOverlay.classList.remove('flex');
    }
  });

  // ----- Mobile Menu Toggle -----
  function openMobile() {
    mobileMenu.classList.remove('translate-x-full');
    mobileMenu.classList.add('translate-x-0');
    mobileBackdrop.classList.remove('opacity-0', 'pointer-events-none');
    mobileBackdrop.classList.add('opacity-100');
    menuToggle?.setAttribute('aria-expanded', 'true');

    if (bars && bars.length === 3) {
      bars[0].classList.add('rotate-45', 'translate-y-1.5');
      bars[1].classList.add('opacity-0');
      bars[2].classList.add('-rotate-45', '-translate-y-1.5');
    }
  }

  function closeMobile() {
    mobileMenu.classList.add('translate-x-full');
    mobileMenu.classList.remove('translate-x-0');
    mobileBackdrop.classList.add('opacity-0', 'pointer-events-none');
    mobileBackdrop.classList.remove('opacity-100');
    menuToggle?.setAttribute('aria-expanded', 'false');

    if (bars && bars.length === 3) {
      bars[0].classList.remove('rotate-45', 'translate-y-1.5');
      bars[1].classList.remove('opacity-0');
      bars[2].classList.remove('-rotate-45', '-translate-y-1.5');
    }
  }

  menuToggle?.addEventListener('click', e => {
    e.stopPropagation();
    mobileOpen = !mobileOpen;
    mobileOpen ? openMobile() : closeMobile();
  });

  mobileBackdrop?.addEventListener('click', () => {
    if (mobileOpen) {
      mobileOpen = false;
      closeMobile();
    }
  });

  mobileMenu?.querySelectorAll('a, button')?.forEach(el => {
    el.addEventListener('click', () => {
      if (mobileOpen) {
        mobileOpen = false;
        closeMobile();
      }
    });
  });

  // ----- Close on Escape -----
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (submitOpen) toggleSubmit();
      if (userOpen) toggleUser();
      if (mobileOpen) { mobileOpen = false; closeMobile(); }
      if (!loginOverlay.classList.contains('hidden')) {
        loginOverlay.classList.add('hidden');
        loginOverlay.classList.remove('flex');
      }
    }
  });

  // ----- Google Sign-In -----
  const googleClientId = '468094396453-vbt8dbmg2a8qrp0ahmv48qfj6srcp2dq.apps.googleusercontent.com'; // replace with your client ID
  google.accounts.id.initialize({
    client_id: googleClientId,
    callback: handleGoogleCredentialResponse
  });

  // Render desktop Google button if exists
  if (document.getElementById('googleSignInBtn')) {
    google.accounts.id.renderButton(
      document.getElementById('googleSignInBtn'),
      { theme: 'filled_blue', size: 'large', width: '100%' }
    );
  }

  // Render mobile Google button
  if (document.getElementById('mobileGoogleSignInBtn')) {
    google.accounts.id.renderButton(
      document.getElementById('mobileGoogleSignInBtn'),
      { theme: 'filled_blue', size: 'large', width: '100%' }
    );
  }

  function handleGoogleCredentialResponse(response) {
    fetch('/api/auth/google', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ credential: response.credential })
    })
    .then(res => res.json())
    .then(data => {
      console.log('Login success:', data);
      if (loginOverlay) {
        loginOverlay.classList.add('hidden');
        loginOverlay.classList.remove('flex');
      }
      if (mobileOpen) { mobileOpen = false; closeMobile(); }
      alert(`Welcome, ${data.name || 'user'}!`);
    })
    .catch(err => console.error('Login failed', err));
  }
}

loadNavbar();
