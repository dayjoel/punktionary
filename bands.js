// bands.js - Handle band filtering, pagination, and display

let currentPage = 1;
let currentPerPage = 12;
let currentFilters = {};

document.addEventListener('DOMContentLoaded', function() {
  initBands();
});

function initBands() {
  // Load featured bands by default
  loadBands({ featured: true });

  // Filter form submission
  const filterForm = document.getElementById('filterForm');
  filterForm.addEventListener('submit', function(e) {
    e.preventDefault();
    currentPage = 1; // Reset to page 1 on new search
    applyFilters();
  });

  // Reset button
  const resetBtn = document.getElementById('resetBtn');
  resetBtn.addEventListener('click', function() {
    filterForm.reset();
    currentFilters = {};
    currentPage = 1;
    loadBands({ featured: true });
  });

  // Clear filters button (in empty state)
  const clearFiltersBtn = document.getElementById('clearFiltersBtn');
  if (clearFiltersBtn) {
    clearFiltersBtn.addEventListener('click', function() {
      filterForm.reset();
      currentFilters = {};
      currentPage = 1;
      loadBands({ featured: true });
    });
  }

  // Per page selector
  const perPageSelect = document.getElementById('perPageSelect');
  perPageSelect.addEventListener('change', function() {
    currentPerPage = parseInt(this.value);
    currentPage = 1; // Reset to page 1
    loadBands(currentFilters);
  });
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
  
  loadBands(currentFilters);
}

async function loadBands(filters = {}) {
  const bandsGrid = document.getElementById('bandsGrid');
  const loadingState = document.getElementById('loadingState');
  const emptyState = document.getElementById('emptyState');
  const paginationContainer = document.getElementById('paginationContainer');
  const resultsCount = document.getElementById('resultsCount');

  // Show loading state
  loadingState.classList.remove('hidden');
  bandsGrid.innerHTML = '';
  emptyState.classList.add('hidden');
  paginationContainer.classList.add('hidden');

  try {
    // Build query params
    const params = new URLSearchParams({
      page: currentPage,
      per_page: currentPerPage,
      ...filters
    });

    const response = await fetch(`get_bands.php?${params}`);
    
    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    const data = await response.json();
    
    // Hide loading state
    loadingState.classList.add('hidden');

    if (data.bands && data.bands.length > 0) {
      // Display bands
      displayBands(data.bands);
      
      // Update results count
      const start = (currentPage - 1) * currentPerPage + 1;
      const end = Math.min(start + data.bands.length - 1, data.pagination.total_records);
      resultsCount.textContent = `Showing ${start}-${end} of ${data.pagination.total_records} bands`;
      
      // Display pagination
      if (data.pagination.total_pages > 1) {
        displayPagination(data.pagination);
      }
    } else {
      // Show empty state
      emptyState.classList.remove('hidden');
      resultsCount.textContent = 'No bands found';
    }

  } catch (error) {
    console.error('Error loading bands:', error);
    loadingState.classList.add('hidden');
    bandsGrid.innerHTML = '<p class="text-red-500 col-span-full text-center py-12">Error loading bands. Please try again.</p>';
  }
}

function displayBands(bands) {
  const bandsGrid = document.getElementById('bandsGrid');
  bandsGrid.innerHTML = '';

  bands.forEach(band => {
    const card = createBandCard(band);
    bandsGrid.appendChild(card);
  });
}

function createBandCard(band) {
  const card = document.createElement('div');
  card.className = 'punk-card band-summary-card p-5 cursor-pointer';
  
  // Parse links if they exist
  let socialLinks = '';
  if (band.links) {
    try {
      const links = JSON.parse(band.links);
      socialLinks = Object.entries(links).map(([platform, url]) => 
        `<a href="${url}" target="_blank" rel="noopener noreferrer" class="punk-link text-sm">${platform}</a>`
      ).join(' • ');
    } catch (e) {
      // If links isn't JSON, just display as is
      if (band.links.trim()) {
        socialLinks = `<a href="${band.links}" target="_blank" rel="noopener noreferrer" class="punk-link text-sm">Website</a>`;
      }
    }
  }

  // Status badge
  const statusBadge = band.active ? 
    '<span class="punk-badge">Active</span>' : 
    '<span class="inline-block bg-gray-700 text-gray-300 px-3 py-1 text-xs font-bold uppercase">Inactive</span>';

  card.innerHTML = `
    <div class="mb-3">
      <h3 class="text-2xl font-bold mb-2" style="font-family: 'Bebas Neue', sans-serif;">
        ${band.name}
      </h3>
      ${statusBadge}
    </div>
    
    <div class="space-y-2 text-sm">
      ${band.genre ? `<p class="text-gray-300"><span class="text-pink-500 font-bold">Genre:</span> ${band.genre}</p>` : ''}
      ${band.city || band.state ? `<p class="text-gray-300"><span class="text-pink-500 font-bold">Location:</span> ${[band.city, band.state].filter(Boolean).join(', ')}</p>` : ''}
    </div>

    ${socialLinks ? `<div class="mt-4 pt-4 border-t border-gray-700">${socialLinks}</div>` : ''}
    
    <div class="mt-4">
      <a href="/band.html?id=${band.id}" class="punk-link text-sm font-bold">View Details →</a>
    </div>
  `;

  // Make whole card clickable
  card.addEventListener('click', function(e) {
    // Don't navigate if clicking a link
    if (e.target.tagName !== 'A') {
      window.location.href = `/band.html?id=${band.id}`;
    }
  });

  return card;
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
      loadBands(currentFilters);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };
  container.appendChild(prevBtn);

  // Page numbers (show max 5 pages)
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
      loadBands(currentFilters);
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
      loadBands(currentFilters);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };
  container.appendChild(nextBtn);
}