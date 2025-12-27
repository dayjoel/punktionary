async function handleSearch(formId, resultsId, endpoint) {
  const form = document.getElementById(formId);
  const results = document.getElementById(resultsId);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

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
        div.classList.add('mb-6');

        if (endpoint.includes('bands')) {
          div.innerHTML = `
            <strong class="text-white text-lg">${item.name}</strong><br>
            Genre: ${item.genre || 'N/A'}<br>
            City: ${item.city || 'N/A'}, State: ${item.state || 'N/A'}<br>
            Active: ${item.active ? 'Yes' : 'No'}<br>
          `;
        } else {
          div.innerHTML = `
            <strong class="text-white text-lg">${item.name}</strong><br>
            Type: ${item.type || 'N/A'}<br>
            Capacity: ${item.capacity || 'N/A'}<br>
            City: ${item.city || 'N/A'}, State: ${item.state || 'N/A'}<br>
            Age Restriction: ${item.age_restriction || 'N/A'}<br>
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

handleSearch('bandsSearchForm', 'bandsResults', 'bands_search.php');
handleSearch('venuesSearchForm', 'venuesResults', 'venues_search.php');
