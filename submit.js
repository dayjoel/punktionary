// submit.js - Handle form type switching and submissions

let autocomplete = null;

// Google Places API callback
function initAutocomplete() {
  // This is called when Google Maps API loads
  // Autocomplete will be initialized when venue form is shown
}

document.addEventListener('DOMContentLoaded', function() {
  initSubmitPage();
});

function initSubmitPage() {
  // Form type selector buttons
  const selectBand = document.getElementById('selectBand');
  const selectVenue = document.getElementById('selectVenue');
  const selectResource = document.getElementById('selectResource');
  
  const bandForm = document.getElementById('bandForm');
  const venueForm = document.getElementById('venueForm');
  const resourceForm = document.getElementById('resourceForm');
  
  const typeButtons = document.querySelectorAll('.submit-type-btn');

  // Switch to band form
  selectBand.addEventListener('click', function() {
    showForm('band');
    updateActiveButton(this, typeButtons);
  });

  // Switch to venue form
  selectVenue.addEventListener('click', function() {
    showForm('venue');
    updateActiveButton(this, typeButtons);
  });

  // Switch to resource form
  selectResource.addEventListener('click', function() {
    showForm('resource');
    updateActiveButton(this, typeButtons);
  });

  // Form submissions
  bandForm.addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm('band', bandForm);
  });

  venueForm.addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm('venue', venueForm);
  });

  resourceForm.addEventListener('submit', function(e) {
    e.preventDefault();
    submitForm('resource', resourceForm);
  });

  // Submit another button
  const submitAnother = document.getElementById('submitAnother');
  submitAnother.addEventListener('click', function() {
    hideMessages();
    // Reset current form
    document.querySelector('.submit-form:not(.hidden)').reset();
  });
}

function showForm(type) {
  const bandForm = document.getElementById('bandForm');
  const venueForm = document.getElementById('venueForm');
  const resourceForm = document.getElementById('resourceForm');

  // Hide all forms
  bandForm.classList.add('hidden');
  venueForm.classList.add('hidden');
  resourceForm.classList.add('hidden');

  // Show selected form
  if (type === 'band') {
    bandForm.classList.remove('hidden');
  } else if (type === 'venue') {
    venueForm.classList.remove('hidden');
    // Initialize Google Places Autocomplete for venue address
    setupVenueAddressAutocomplete();
  } else if (type === 'resource') {
    resourceForm.classList.remove('hidden');
  }

  // Hide messages when switching
  hideMessages();
}

function setupVenueAddressAutocomplete() {
  // Only initialize once and if Google Maps is loaded
  if (autocomplete || typeof google === 'undefined') {
    return;
  }

  const addressInput = document.querySelector('#venueForm input[name="street_address"]');
  if (!addressInput) {
    return;
  }

  // Create autocomplete instance restricted to US addresses
  autocomplete = new google.maps.places.Autocomplete(addressInput, {
    types: ['address'],
    componentRestrictions: { country: 'us' }
  });

  // Listen for place selection
  autocomplete.addListener('place_changed', function() {
    const place = autocomplete.getPlace();

    if (!place.address_components) {
      return;
    }

    // Extract address components
    let streetNumber = '';
    let route = '';
    let city = '';
    let state = '';
    let postalCode = '';

    place.address_components.forEach(component => {
      const types = component.types;

      if (types.includes('street_number')) {
        streetNumber = component.long_name;
      }
      if (types.includes('route')) {
        route = component.long_name;
      }
      if (types.includes('locality')) {
        city = component.long_name;
      }
      if (types.includes('administrative_area_level_1')) {
        state = component.short_name; // Two-letter abbreviation
      }
      if (types.includes('postal_code')) {
        postalCode = component.long_name;
      }
    });

    // Populate form fields
    const venueForm = document.getElementById('venueForm');

    // Set street address
    const fullAddress = `${streetNumber} ${route}`.trim();
    addressInput.value = fullAddress;

    // Set city
    const cityInput = venueForm.querySelector('input[name="city"]');
    if (cityInput && city) {
      cityInput.value = city;
    }

    // Set state (select dropdown)
    const stateSelect = venueForm.querySelector('select[name="state"]');
    if (stateSelect && state) {
      stateSelect.value = state;
    }

    // Set postal code
    const postalInput = venueForm.querySelector('input[name="postal_code"]');
    if (postalInput && postalCode) {
      postalInput.value = postalCode;
    }
  });
}

function updateActiveButton(activeBtn, allButtons) {
  allButtons.forEach(btn => {
    if (btn === activeBtn) {
      btn.classList.remove('punk-button-outline');
      btn.classList.add('punk-button');
    } else {
      btn.classList.remove('punk-button');
      btn.classList.add('punk-button-outline');
    }
  });
}

async function submitForm(type, form) {
  const successMessage = document.getElementById('successMessage');
  const errorMessage = document.getElementById('errorMessage');
  const errorText = document.getElementById('errorText');

  // Hide previous messages
  hideMessages();

  // Check authentication first
  try {
    const authResponse = await fetch('/auth/check_auth.php');
    const authData = await authResponse.json();

    if (!authData.authenticated) {
      errorText.textContent = 'You must be logged in to submit content. Please log in and try again.';
      errorMessage.classList.remove('hidden');
      errorMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });

      // Open login modal
      const loginOverlay = document.getElementById('loginOverlay');
      if (loginOverlay) {
        loginOverlay.classList.remove('hidden');
      }
      return;
    }
  } catch (error) {
    console.error('Auth check failed:', error);
    errorText.textContent = 'Unable to verify authentication. Please try again.';
    errorMessage.classList.remove('hidden');
    return;
  }

  try {
    const formData = new FormData(form);
    
    // Process albums field for bands (convert comma-separated to JSON array)
    if (type === 'band') {
      const albumsStr = formData.get('albums');
      if (albumsStr && albumsStr.trim()) {
        const albumsArray = albumsStr.split(',').map(a => a.trim()).filter(a => a);
        if (albumsArray.length > 0) {
          formData.set('albums', JSON.stringify(albumsArray));
        } else {
          formData.delete('albums'); // Remove empty field
        }
      } else {
        formData.delete('albums'); // Remove empty field
      }
    }

    // Process photo_references field for bands (convert comma-separated to JSON array)
    if (type === 'band') {
      const photosStr = formData.get('photo_references');
      if (photosStr && photosStr.trim()) {
        const photosArray = photosStr.split(',').map(p => p.trim()).filter(p => p);
        if (photosArray.length > 0) {
          formData.set('photo_references', JSON.stringify(photosArray));
        } else {
          formData.delete('photo_references'); // Remove empty field
        }
      } else {
        formData.delete('photo_references'); // Remove empty field
      }
    }

    // Process link fields (convert to JSON object)
    const links = {};
    const linkFields = ['website', 'bandcamp', 'instagram', 'facebook', 'x'];
    
    linkFields.forEach(platform => {
      const fieldName = `link_${platform}`;
      const url = formData.get(fieldName);
      if (url && url.trim()) {
        // Capitalize platform name properly
        const platformName = platform === 'x' ? 'X' : 
                            platform.charAt(0).toUpperCase() + platform.slice(1);
        links[platformName] = url.trim();
      }
      // Remove individual link fields from formData
      formData.delete(fieldName);
    });

    // Add links as JSON if any exist
    if (Object.keys(links).length > 0) {
      formData.set('links', JSON.stringify(links));
    }

    // Determine endpoint
    const endpoint = `submit_${type}.php`;
    
    const response = await fetch(endpoint, {
      method: 'POST',
      body: formData
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // Show success message
      successMessage.classList.remove('hidden');
      form.reset();
      
      // Scroll to success message
      successMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
      // Show error message
      errorText.textContent = data.error || 'An error occurred. Please try again.';
      errorMessage.classList.remove('hidden');
      
      // Scroll to error message
      errorMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

  } catch (error) {
    console.error('Submission error:', error);
    errorText.textContent = 'Network error. Please check your connection and try again.';
    errorMessage.classList.remove('hidden');
    errorMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function hideMessages() {
  document.getElementById('successMessage').classList.add('hidden');
  document.getElementById('errorMessage').classList.add('hidden');
}