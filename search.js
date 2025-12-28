// search.js - Search functionality for bands and venues

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
  initSearch();
});

function initSearch() {
  handleSearch('bandsSearchForm', 'bandsResults', 'bands_search.php');
  handleSearch('venuesSearchForm', 'venuesResults', 'venues_search.php');
}

async function handleSearch(formId, resultsId, endpoint) {
  const form = document.getElementById(formId);
  const results = document.getElementById(resultsId);

  if (!form || !results) {
    console.warn(`Form or results element not found: ${formId}, ${resultsId}`);
    return;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    e.stopPropagation(); // Prevent event from bubbling up

    console.log("Submitting:", Object.fromEntries(new FormData(form)));

    results.innerHTML = '<p class="text-gray-400">Loading...</p>';

    try {
      const formData = new FormData(form);
      const response = await fetch(endpoint, { method: 'POST', body: formData });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const data = await response.json();
      console.log(`✅ Results from ${endpoint}:`, data);

      results.innerHTML = '';

      const resultsArray = Array.isArray(data) ? data : Object.values(data);

      if (resultsArray.length === 0) {
        results.innerHTML = '<p class="text-gray-400">No results found.</p>';
        return;
      }

      resultsArray.forEach(item => {
        const div = document.createElement('div');
        div.classList.add('mb-6', 'punk-card', 'p-4');

        if (endpoint.includes('bands')) {
          div.innerHTML = `
            <strong class="text-white text-lg font-bold block mb-2" style="font-family: 'Bebas Neue', sans-serif;">${item.name}</strong>
            <p class="text-gray-300"><span class="text-pink-500 font-bold">Genre:</span> ${item.genre || 'N/A'}</p>
            <p class="text-gray-300"><span class="text-pink-500 font-bold">Location:</span> ${item.city || 'N/A'}, ${item.state || 'N/A'}</p>
            <p class="text-gray-300"><span class="text-pink-500 font-bold">Active:</span> ${item.active ? 'Yes' : 'No'}</p>
          `;
        } else {
          div.innerHTML = `
            <strong class="text-white text-lg font-bold block mb-2" style="font-family: 'Bebas Neue', sans-serif;">${item.name}</strong>
            <p class="text-gray-300"><span class="text-pink-500 font-bold">Type:</span> ${item.type || 'N/A'}</p>
            <p class="text-gray-300"><span class="text-pink-500 font-bold">Capacity:</span> ${item.capacity || 'N/A'}</p>
            <p class="text-gray-300"><span class="text-pink-500 font-bold">Location:</span> ${item.city || 'N/A'}, ${item.state || 'N/A'}</p>
            <p class="text-gray-300"><span class="text-pink-500 font-bold">Age Restriction:</span> ${item.age_restriction || 'N/A'}</p>
          `;
        }

        results.appendChild(div);
      });

    } catch (error) {
      console.error(`❌ Error fetching ${endpoint}:`, error);
      results.innerHTML = `<p class="text-red-500">Error searching ${formId.replace('SearchForm','').toLowerCase()}.</p>`;
    }
  });
}