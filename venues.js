// venues.js - Handle venue filtering, pagination, and display

let currentPage = 1;
let currentPerPage = 12;
let currentFilters = {};
let currentSort = 'name_asc';
let currentView = 'grid'; // 'grid' or 'list'

document.addEventListener('DOMContentLoaded', function() {
  initVenues();
});

function initVenues() {
  // Load featured venues by default
  loadVenues({ featured: true });

  // Filter form submission
  const filterForm = document.getElementById('filterForm');
  filterForm.addEventListener('submit', function(e) {
    e.preventDefault();
    currentPage = 1;
    applyFilters();
  });

  // Reset button
  const resetBtn = document.getElementById('resetBtn');
  resetBtn.addEventListener('click', function() {
    filterForm.reset();
    currentFilters = {};
    currentPage = 1;
    loadVenues({ featured: true });
  });

  // Clear filters button
  const clearFiltersBtn = document.getElementById('clearFiltersBtn');
  if (clearFiltersBtn) {
    clearFiltersBtn.addEventListener('click', function() {
      filterForm.reset();
      currentFilters = {};
      currentPage = 1;
      loadVenues({ featured: true });
    });
  }

  // Sort selector
  const sortSelect = document.getElementById('sortSelect');
  sortSelect.addEventListener('change', function() {
    currentSort = this.value;
    currentPage = 1;
    loadVenues(currentFilters);
  });

  // Per page selector
  const perPageSelect = document.getElementById('perPageSelect');
  perPageSelect.addEventListener('change', function() {
    currentPerPage = parseInt(this.value);
    currentPage = 1;
    loadVenues(currentFilters);
  });

  // View toggle buttons
  const gridViewBtn = document.getElementById('gridViewBtn');
  const listViewBtn = document.getElementById('listViewBtn');

  gridViewBtn.addEventListener('click', function() {
    currentView = 'grid';
    updateViewButtons();
    displayVenues(window.currentVenues || []);
  });

  listViewBtn.addEventListener('click', function() {
    currentView = 'list';
    updateViewButtons();
    displayVenues(window.currentVenues || []);
  });
}

function updateViewButtons() {
  const gridViewBtn = document.getElementById('gridViewBtn');
  const listViewBtn = document.getElementById('listViewBtn');

  if (currentView === 'grid') {
    gridViewBtn.className = 'px-3 py-2 bg-pink-500 text-black font-bold transition-colors';
    listViewBtn.className = 'px-3 py-2 text-pink-500 hover:bg-pink-500 hover:text-black font-bold transition-colors';
  } else {
    gridViewBtn.className = 'px-3 py-2 text-pink-500 hover:bg-pink-500 hover:text-black font-bold transition-colors';
    listViewBtn.className = 'px-3 py-2 bg-pink-500 text-black font-bold transition-colors';
  }
}

function applyFilters() {
  const form = document.getElementById('filterForm');
  const formData = new FormData(form);
  
  currentFilters = {};
  for (let [key, value] of formData.entries()) {
    if (value.trim() !== '') {
      currentFilters[key] = value.trim();
    }
  }
  
  loadVenues(currentFilters);
}

async function loadVenues(filters = {}) {
  const venuesGrid = document.getElementById('venuesGrid');
  const loadingState = document.getElementById('loadingState');
  const emptyState = document.getElementById('emptyState');
  const paginationContainer = document.getElementById('paginationContainer');
  const resultsCount = document.getElementById('resultsCount');

  // Show loading state
  loadingState.classList.remove('hidden');
  venuesGrid.innerHTML = '';
  emptyState.classList.add('hidden');
  paginationContainer.classList.add('hidden');

  try {
    // Build query params
    const params = new URLSearchParams({
      page: currentPage,
      per_page: currentPerPage,
      sort: currentSort,
      ...filters
    });

    const response = await fetch(`get_venues.php?${params}`);
    
    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    const data = await response.json();
    
    // Hide loading state
    loadingState.classList.add('hidden');

    if (data.venues && data.venues.length > 0) {
      // Display venues
      displayVenues(data.venues);
      
      // Update results count
      const start = (currentPage - 1) * currentPerPage + 1;
      const end = Math.min(start + data.venues.length - 1, data.pagination.total_records);
      resultsCount.textContent = `Showing ${start}-${end} of ${data.pagination.total_records} venues`;
      
      // Display pagination
      if (data.pagination.total_pages > 1) {
        displayPagination(data.pagination);
      }
    } else {
      // Show empty state
      emptyState.classList.remove('hidden');
      resultsCount.textContent = 'No venues found';
    }

  } catch (error) {
    console.error('Error loading venues:', error);
    loadingState.classList.add('hidden');
    venuesGrid.innerHTML = '<p class="text-red-500 col-span-full text-center py-12">Error loading venues. Please try again.</p>';
  }
}

function displayVenues(venues) {
  // Store venues for view switching
  window.currentVenues = venues;

  const venuesGrid = document.getElementById('venuesGrid');
  venuesGrid.innerHTML = '';

  // Update container classes based on view
  if (currentView === 'grid') {
    venuesGrid.className = 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8';
  } else {
    venuesGrid.className = 'space-y-4 mb-8';
  }

  venues.forEach(venue => {
    const card = currentView === 'grid' ? createVenueCard(venue) : createVenueListItem(venue);
    venuesGrid.appendChild(card);
  });
}

function createVenueCard(venue) {
  const card = document.createElement('div');
  card.className = 'punk-card venue-summary-card p-5 cursor-pointer transition-all';
  
  // Parse links if they exist
  let socialLinks = '';
  if (venue.links) {
    try {
      const links = JSON.parse(venue.links);
      socialLinks = Object.entries(links).map(([platform, url]) => 
        `<a href="${url}" target="_blank" rel="noopener noreferrer" class="punk-link text-sm">${platform}</a>`
      ).join(' • ');
    } catch (e) {
      if (venue.links.trim()) {
        socialLinks = `<a href="${venue.links}" target="_blank" rel="noopener noreferrer" class="punk-link text-sm">Website</a>`;
      }
    }
  }

  // Age restriction badge
  const ageBadge = venue.age_restriction ? 
    `<span class="punk-badge">${venue.age_restriction}</span>` : '';

  card.innerHTML = `
    <div class="mb-3">
      <h3 class="text-2xl font-bold mb-2" style="font-family: 'Bebas Neue', sans-serif;">
        ${venue.name}
      </h3>
      ${ageBadge}
    </div>
    
    <div class="space-y-2 text-sm">
      ${venue.type ? `<p class="text-gray-300"><span class="text-pink-500 font-bold">Type:</span> ${venue.type}</p>` : ''}
      ${venue.city || venue.state ? `<p class="text-gray-300"><span class="text-pink-500 font-bold">Location:</span> ${[venue.city, venue.state].filter(Boolean).join(', ')}</p>` : ''}
      ${venue.capacity ? `<p class="text-gray-300"><span class="text-pink-500 font-bold">Capacity:</span> ${venue.capacity}</p>` : ''}
    </div>

    ${socialLinks ? `<div class="mt-4 pt-4 border-t border-gray-700">${socialLinks}</div>` : ''}
    
    <div class="mt-4">
      <a href="/venue.html?id=${venue.id}" class="punk-link text-sm font-bold">View Details →</a>
    </div>
  `;

  // Make whole card clickable
  card.addEventListener('click', function(e) {
    if (e.target.tagName !== 'A') {
      window.location.href = `/venue.html?id=${venue.id}`;
    }
  });

  return card;
}

function createVenueListItem(venue) {
  const item = document.createElement('div');
  item.className = 'punk-card p-6 cursor-pointer';

  // Parse links if they exist
  let socialLinks = '';
  if (venue.links) {
    try {
      const links = JSON.parse(venue.links);
      socialLinks = Object.entries(links).map(([platform, url]) =>
        `<a href="${url}" target="_blank" rel="noopener noreferrer" class="punk-link text-sm">${platform}</a>`
      ).join(' • ');
    } catch (e) {
      if (venue.links.trim()) {
        socialLinks = `<a href="${venue.links}" target="_blank" rel="noopener noreferrer" class="punk-link text-sm">Website</a>`;
      }
    }
  }

  // Age restriction badge
  const ageBadge = venue.age_restriction ?
    `<span class="punk-badge">${venue.age_restriction}</span>` : '';

  item.innerHTML = `
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div class="flex-1">
        <div class="mb-3">
          <h3 class="text-3xl font-bold mb-2 inline-block mr-3" style="font-family: 'Bebas Neue', sans-serif;">
            ${venue.name}
          </h3>
          ${ageBadge}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-2 text-sm mb-3">
          ${venue.type ? `<p class="text-gray-300"><span class="text-pink-500 font-bold">Type:</span> ${venue.type}</p>` : ''}
          ${venue.city || venue.state ? `<p class="text-gray-300"><span class="text-pink-500 font-bold">Location:</span> ${[venue.city, venue.state].filter(Boolean).join(', ')}</p>` : ''}
          ${venue.capacity ? `<p class="text-gray-300"><span class="text-pink-500 font-bold">Capacity:</span> ${venue.capacity}</p>` : ''}
        </div>

        ${socialLinks ? `<div class="mt-3 text-sm">${socialLinks}</div>` : ''}
      </div>

      <div class="flex-shrink-0">
        <a href="/venue.html?id=${venue.id}" class="punk-button">View Details →</a>
      </div>
    </div>
  `;

  // Make whole item clickable except the button
  item.addEventListener('click', function(e) {
    if (e.target.tagName !== 'A' && !e.target.closest('a')) {
      window.location.href = `/venue.html?id=${venue.id}`;
    }
  });

  return item;
}

function displayPagination(pagination) {
  const container = document.getElementById('paginationContainer');
  container.classList.remove('hidden');
  container.innerHTML = '';

  const { current_page, total_pages } = pagination;

  // Previous button
  const prevBtn = document.createElement('button');
  prevBtn.className = current_page === 1 ? 'punk-button-outline opacity-50 cursor-not-allowed' : 'punk-button-outline';
  prevBtn.textContent = '← Prev';
  prevBtn.disabled = current_page === 1;
  prevBtn.onclick = () => {
    if (current_page > 1) {
      currentPage--;
      loadVenues(currentFilters);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };
  container.appendChild(prevBtn);

  // Page numbers
  const maxPagesToShow = 5;
  let startPage = Math.max(1, current_page - Math.floor(maxPagesToShow / 2));
  let endPage = Math.min(total_pages, startPage + maxPagesToShow - 1);
  
  if (endPage - startPage < maxPagesToShow - 1) {
    startPage = Math.max(1, endPage - maxPagesToShow + 1);
  }

  for (let i = startPage; i <= endPage; i++) {
    const pageBtn = document.createElement('button');
    pageBtn.className = i === current_page ? 'punk-button' : 'punk-button-outline';
    pageBtn.textContent = i;
    pageBtn.onclick = () => {
      currentPage = i;
      loadVenues(currentFilters);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    container.appendChild(pageBtn);
  }

  // Next button
  const nextBtn = document.createElement('button');
  nextBtn.className = current_page === total_pages ? 'punk-button-outline opacity-50 cursor-not-allowed' : 'punk-button-outline';
  nextBtn.textContent = 'Next →';
  nextBtn.disabled = current_page === total_pages;
  nextBtn.onclick = () => {
    if (current_page < total_pages) {
      currentPage++;
      loadVenues(currentFilters);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };
  container.appendChild(nextBtn);
}