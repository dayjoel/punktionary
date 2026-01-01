// submit.js - Handle form type switching and submissions

let autocompleteSetup = false;
let autocompleteTimeout = null;

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

  // Image source toggle for band form
  const imageSourceRadios = document.querySelectorAll('input[name="image_source"]');
  const uploadSection = document.getElementById('upload-section');
  const urlSection = document.getElementById('url-section');

  imageSourceRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      if (this.value === 'upload') {
        uploadSection.classList.remove('hidden');
        urlSection.classList.add('hidden');
        document.getElementById('logoUrlInput').value = '';
      } else {
        uploadSection.classList.add('hidden');
        urlSection.classList.remove('hidden');
        document.getElementById('logoFileInput').value = '';
      }
    });
  });

  // Genre checkbox limit (max 3)
  const genreCheckboxes = document.querySelectorAll('.genre-checkbox');
  const genreCount = document.getElementById('genreCount');

  genreCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      const checkedBoxes = document.querySelectorAll('.genre-checkbox:checked');
      const count = checkedBoxes.length;

      // Update count display
      genreCount.textContent = `${count} of 3 genres selected`;

      // If 3 are selected, disable unchecked boxes
      if (count >= 3) {
        genreCheckboxes.forEach(box => {
          if (!box.checked) {
            box.disabled = true;
            box.parentElement.classList.add('opacity-50', 'cursor-not-allowed');
          }
        });
      } else {
        // Re-enable all boxes
        genreCheckboxes.forEach(box => {
          box.disabled = false;
          box.parentElement.classList.remove('opacity-50', 'cursor-not-allowed');
        });
      }
    });
  });

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
  if (autocompleteSetup) {
    return;
  }
  autocompleteSetup = true;

  const addressInput = document.querySelector('#venueForm input[name="street_address"]');
  if (!addressInput) {
    return;
  }

  // Create autocomplete dropdown container
  const dropdown = document.createElement('div');
  dropdown.id = 'address-autocomplete-dropdown';
  dropdown.className = 'hidden absolute z-50 bg-black border-2 border-pink-500 mt-1 max-h-60 overflow-y-auto';
  dropdown.style.width = '100%';
  dropdown.style.left = '0';
  dropdown.style.top = '100%';

  // Ensure parent has relative positioning
  const parentDiv = addressInput.parentElement;
  parentDiv.style.position = 'relative';
  parentDiv.appendChild(dropdown);

  // Listen for input changes
  addressInput.addEventListener('input', function() {
    clearTimeout(autocompleteTimeout);
    const input = this.value.trim();

    if (input.length < 3) {
      dropdown.classList.add('hidden');
      return;
    }

    // Debounce requests
    autocompleteTimeout = setTimeout(async () => {
      try {
        const formData = new FormData();
        formData.append('input', input);

        const response = await fetch('/api/autocomplete_address.php', {
          method: 'POST',
          body: formData
        });

        if (!response.ok) {
          console.error('Autocomplete API error:', response.status, response.statusText);
          const errorData = await response.json();
          console.error('Error details:', errorData);
          dropdown.classList.add('hidden');
          return;
        }

        const data = await response.json();

        if (data.predictions && data.predictions.length > 0) {
          displayPredictions(data.predictions, dropdown, addressInput);
        } else {
          dropdown.classList.add('hidden');
        }
      } catch (error) {
        console.error('Autocomplete error:', error);
        dropdown.classList.add('hidden');
      }
    }, 300);
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    if (!addressInput.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });
}

function displayPredictions(predictions, dropdown, addressInput) {
  dropdown.innerHTML = '';
  dropdown.classList.remove('hidden');

  predictions.forEach(prediction => {
    const item = document.createElement('div');
    item.className = 'px-4 py-3 cursor-pointer hover:bg-pink-500 hover:text-black transition-colors border-b border-pink-500/30 last:border-b-0';
    item.textContent = prediction.description;
    item.dataset.placeId = prediction.place_id;

    item.addEventListener('click', async function() {
      const selectedDescription = prediction.description;
      addressInput.value = selectedDescription;
      dropdown.classList.add('hidden');

      // Get place details to populate other fields
      await getPlaceDetails(prediction.place_id, selectedDescription);
    });

    dropdown.appendChild(item);
  });
}

async function getPlaceDetails(placeId, selectedDescription) {
  try {
    const formData = new FormData();
    formData.append('place_id', placeId);

    const response = await fetch('/api/get_place_details.php', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();

    console.log('=== FULL PLACES API RESPONSE ===');
    console.log(JSON.stringify(data, null, 2));
    console.log('=== END RESPONSE ===');

    if (data.result && data.result.address_components) {
      console.log('Address components found:', data.result.address_components);
      populateAddressFields(data.result.address_components, selectedDescription);
    } else {
      console.error('No address components in response:', data);
    }
  } catch (error) {
    console.error('Place details error:', error);
  }
}

function populateAddressFields(addressComponents, selectedDescription) {
  const venueForm = document.getElementById('venueForm');
  let streetNumber = '';
  let route = '';
  let city = '';
  let state = '';
  let postalCode = '';

  console.log('Processing address components:', addressComponents);

  addressComponents.forEach(component => {
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
      state = component.short_name;
    }
    if (types.includes('postal_code')) {
      postalCode = component.long_name;
    }
  });

  console.log('Extracted components:', { streetNumber, route, city, state, postalCode });

  // Set street address - try to extract from selected description if Google doesn't provide street_number
  const addressInput = venueForm.querySelector('input[name="street_address"]');
  if (addressInput) {
    if (streetNumber && route) {
      // Google provided both - use them
      addressInput.value = `${streetNumber} ${route}`;
    } else if (!streetNumber && route && selectedDescription) {
      // Google only provided route, try to extract street number from description
      // Example: "10325 West Marginal Way, Tukwila, WA" -> extract "10325 West Marginal Way"
      const parts = selectedDescription.split(',');
      if (parts.length > 0) {
        addressInput.value = parts[0].trim();
      }
    } else if (!streetNumber && route) {
      // No description, just use route
      addressInput.value = route;
    }
    // If neither, leave the user's input as-is
  }

  // Set city
  const cityInput = venueForm.querySelector('input[name="city"]');
  if (cityInput && city) {
    cityInput.value = city;
  }

  // Set state
  const stateSelect = venueForm.querySelector('select[name="state"]');
  if (stateSelect && state) {
    stateSelect.value = state;
  }

  // Set postal code - only update if Google provided one
  const postalInput = venueForm.querySelector('input[name="postal_code"]');
  if (postalInput && postalCode) {
    postalInput.value = postalCode;
  }
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

    // Process genre checkboxes for bands (convert to comma-separated string)
    if (type === 'band') {
      const selectedGenres = Array.from(document.querySelectorAll('.genre-checkbox:checked'))
        .map(cb => cb.value);

      if (selectedGenres.length > 0) {
        formData.set('genre', selectedGenres.join(', '));
      } else {
        formData.delete('genre');
      }

      // Remove the individual genre checkbox values from formData
      formData.delete('genres');
    }

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