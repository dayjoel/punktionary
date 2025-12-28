// submit.js - Handle form type switching and submissions

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
  } else if (type === 'resource') {
    resourceForm.classList.remove('hidden');
  }

  // Hide messages when switching
  hideMessages();
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

  try {
    const formData = new FormData(form);
    
    // Process albums field for bands (convert comma-separated to JSON array)
    if (type === 'band' && formData.get('albums')) {
      const albumsStr = formData.get('albums');
      if (albumsStr.trim()) {
        const albumsArray = albumsStr.split(',').map(a => a.trim()).filter(a => a);
        formData.set('albums', JSON.stringify(albumsArray));
      }
    }

    // Process photo_references field for bands (convert comma-separated to JSON array)
    if (type === 'band' && formData.get('photo_references')) {
      const photosStr = formData.get('photo_references');
      if (photosStr.trim()) {
        const photosArray = photosStr.split(',').map(p => p.trim()).filter(p => p);
        formData.set('photo_references', JSON.stringify(photosArray));
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