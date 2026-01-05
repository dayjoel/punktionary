// submit.js - Handle form type switching and submissions

let autocompleteSetup = false;
let autocompleteTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
  initSubmitPage();
  checkAndShowAuthNotice();
});

// Check authentication status and show notice if not logged in
async function checkAndShowAuthNotice() {
  try {
    const response = await fetch('/auth/check_auth.php');
    const data = await response.json();

    if (!data.authenticated) {
      // User not logged in - show the auth notice
      const authNotice = document.getElementById('authNotice');
      if (authNotice) {
        authNotice.classList.remove('hidden');
      }
    }
  } catch (error) {
    console.error('Auth check failed:', error);
  }
}

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

  // "Ask a punk" checkbox for venue address
  const askAPunkCheckbox = document.getElementById('askAPunk');
  const addressFields = document.getElementById('addressFields');

  if (askAPunkCheckbox && addressFields) {
    askAPunkCheckbox.addEventListener('change', function() {
      if (this.checked) {
        // Hide and disable address fields
        addressFields.style.display = 'none';
        document.getElementById('street_address').disabled = true;
        document.getElementById('city').disabled = true;
        document.getElementById('postal_code').disabled = true;
        document.getElementById('state').disabled = true;
      } else {
        // Show and enable address fields
        addressFields.style.display = 'block';
        document.getElementById('street_address').disabled = false;
        document.getElementById('city').disabled = false;
        document.getElementById('postal_code').disabled = false;
        document.getElementById('state').disabled = false;
      }
    });
  }

  // Resource type "Other" toggle for custom field
  const resourceTypeRadios = document.querySelectorAll('input[name="resource_type"]');
  const customResourceTypeField = document.getElementById('customResourceTypeField');
  const customResourceTypeInput = document.getElementById('customResourceType');

  if (resourceTypeRadios.length > 0 && customResourceTypeField) {
    resourceTypeRadios.forEach(radio => {
      radio.addEventListener('change', function() {
        if (this.value === 'Other' && this.checked) {
          customResourceTypeField.classList.remove('hidden');
          customResourceTypeInput.required = true;
        } else {
          customResourceTypeField.classList.add('hidden');
          customResourceTypeInput.required = false;
          customResourceTypeInput.value = '';
        }
      });
    });
  }

  // Switch to band form
  selectBand.addEventListener('click', async function() {
    if (await checkAuthenticationBeforeForm()) {
      showForm('band');
      updateActiveButton(this, typeButtons);
    }
  });

  // Switch to venue form
  selectVenue.addEventListener('click', async function() {
    if (await checkAuthenticationBeforeForm()) {
      showForm('venue');
      updateActiveButton(this, typeButtons);
    }
  });

  // Switch to resource form
  selectResource.addEventListener('click', async function() {
    if (await checkAuthenticationBeforeForm()) {
      showForm('resource');
      updateActiveButton(this, typeButtons);
    }
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

// Check if user is authenticated before showing form
async function checkAuthenticationBeforeForm() {
  try {
    const response = await fetch('/auth/check_auth.php');
    const data = await response.json();

    if (data.authenticated) {
      return true; // User is logged in, allow form access
    } else {
      // User not logged in - show login modal
      const loginOverlay = document.getElementById('loginOverlay');
      if (loginOverlay) {
        loginOverlay.classList.remove('hidden');
      } else {
        // Fallback: redirect to login if modal not available
        alert('Please sign in to submit content.');
      }
      return false;
    }
  } catch (error) {
    console.error('Auth check failed:', error);
    alert('Please sign in to submit content.');
    return false;
  }
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

    // Process resource type (use custom type if "Other" is selected)
    if (type === 'resource') {
      const resourceType = formData.get('resource_type');
      if (resourceType === 'Other') {
        const customType = formData.get('custom_resource_type');
        if (customType && customType.trim()) {
          formData.set('resource_type', customType.trim());
        }
      }
      // Remove the custom field from formData
      formData.delete('custom_resource_type');
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

// Venue URL Scraper
document.addEventListener('DOMContentLoaded', function() {
  const fetchButton = document.getElementById('fetchVenueInfo');
  if (fetchButton) {
    fetchButton.addEventListener('click', async function() {
      const urlInput = document.getElementById('venueUrlInput');
      const statusDiv = document.getElementById('fetchStatus');
      const fetchBtnText = document.querySelector('.fetch-btn-text');
      const fetchBtnLoading = document.querySelector('.fetch-btn-loading');

      const url = urlInput.value.trim();

      if (!url) {
        showFetchStatus('Please enter a URL', 'error');
        return;
      }

      // Validate URL format
      try {
        new URL(url);
      } catch (e) {
        showFetchStatus('Please enter a valid URL', 'error');
        return;
      }

      // Show loading state
      fetchButton.disabled = true;
      fetchBtnText.classList.add('hidden');
      fetchBtnLoading.classList.remove('hidden');
      statusDiv.classList.add('hidden');

      try {
        const response = await fetch('/api/scrape_venue_url.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ url })
        });

        const result = await response.json();

        if (result.success && result.data) {
          // Populate form fields with extracted data
          const data = result.data;

          if (data.name) {
            document.getElementById('venueName').value = data.name;
          }
          if (data.street_address) {
            document.getElementById('street_address').value = data.street_address;
          }
          if (data.city) {
            document.getElementById('city').value = data.city;
          }
          if (data.state) {
            // Match state to select options (supports both full name and abbreviation)
            const stateSelect = document.getElementById('state');
            const stateUpper = data.state.toUpperCase();
            for (let option of stateSelect.options) {
              if (option.value === stateUpper || option.text.includes(stateUpper)) {
                stateSelect.value = option.value;
                break;
              }
            }
          }
          if (data.postal_code) {
            document.getElementById('postal_code').value = data.postal_code;
          }
          if (data.website) {
            const websiteInput = document.querySelector('#venueForm input[name="link_website"]');
            if (websiteInput) {
              websiteInput.value = data.website;
            }
          }
          if (data.phone) {
            const phoneInput = document.querySelector('#venueForm input[name="phone"]');
            if (phoneInput) {
              phoneInput.value = data.phone;
            }
          }
          if (data.description) {
            const descInput = document.querySelector('#venueForm textarea[name="description"]');
            if (descInput) {
              descInput.value = data.description;
            }
          }

          // Populate social media links
          if (data.social_links) {
            if (data.social_links.facebook) {
              const fbInput = document.querySelector('#venueForm input[name="social_facebook"]');
              if (fbInput) fbInput.value = data.social_links.facebook;
            }
            if (data.social_links.instagram) {
              const igInput = document.querySelector('#venueForm input[name="social_instagram"]');
              if (igInput) igInput.value = data.social_links.instagram;
            }
            if (data.social_links.twitter) {
              const twInput = document.querySelector('#venueForm input[name="social_twitter"]');
              if (twInput) twInput.value = data.social_links.twitter;
            }
            if (data.social_links.youtube) {
              const ytInput = document.querySelector('#venueForm input[name="social_youtube"]');
              if (ytInput) ytInput.value = data.social_links.youtube;
            }
          }

          showFetchStatus('Information extracted successfully! Please review and edit as needed.', 'success');
        } else {
          showFetchStatus(result.error || 'Could not extract venue information from this URL', 'error');
        }

      } catch (error) {
        console.error('Fetch error:', error);
        showFetchStatus('Network error. Please try again.', 'error');
      } finally {
        // Reset button state
        fetchButton.disabled = false;
        fetchBtnText.classList.remove('hidden');
        fetchBtnLoading.classList.add('hidden');
      }
    });
  }

  function showFetchStatus(message, type) {
    const statusDiv = document.getElementById('fetchStatus');
    statusDiv.textContent = message;
    statusDiv.classList.remove('hidden', 'text-green-400', 'text-red-400');

    if (type === 'success') {
      statusDiv.classList.add('text-green-400');
    } else {
      statusDiv.classList.add('text-red-400');
    }
  }
});