// carousel.js - Admin interface for managing carousel items

let editingItemId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadCarouselItems();
    setupEventListeners();
});

function setupEventListeners() {
    // Scrape URL button
    document.getElementById('scrapeBtn').addEventListener('click', scrapeUrl);

    // Enter key on scrape URL input
    document.getElementById('scrapeUrl').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            scrapeUrl();
        }
    });

    // Image URL input - show preview
    document.getElementById('itemImage').addEventListener('input', function() {
        const url = this.value.trim();
        const preview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');

        if (url) {
            previewImg.src = url;
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
        }
    });

    // Form submission
    document.getElementById('carouselForm').addEventListener('submit', saveCarouselItem);

    // Cancel button
    document.getElementById('cancelBtn').addEventListener('click', resetForm);
}

async function scrapeUrl() {
    const urlInput = document.getElementById('scrapeUrl');
    const url = urlInput.value.trim();
    const statusDiv = document.getElementById('scrapeStatus');
    const scrapeBtn = document.getElementById('scrapeBtn');

    if (!url) {
        showStatus('Please enter a URL', 'error');
        return;
    }

    // Basic URL validation
    try {
        new URL(url);
    } catch (e) {
        showStatus('Invalid URL format', 'error');
        return;
    }

    scrapeBtn.disabled = true;
    scrapeBtn.textContent = 'Scraping...';
    statusDiv.classList.add('hidden');

    try {
        const response = await fetch(`/api/scrape_url_metadata.php?url=${encodeURIComponent(url)}`);
        const data = await response.json();

        if (data.success) {
            // Fill form with scraped data
            if (data.metadata.title) {
                document.getElementById('itemTitle').value = data.metadata.title;
            }
            if (data.metadata.description) {
                document.getElementById('itemDescription').value = data.metadata.description;
            }
            if (data.metadata.image) {
                document.getElementById('itemImage').value = data.metadata.image;
                // Trigger preview
                document.getElementById('itemImage').dispatchEvent(new Event('input'));
            }
            if (url) {
                document.getElementById('itemLink').value = url;
            }

            showStatus('Metadata scraped successfully!', 'success');
        } else {
            throw new Error(data.error || 'Failed to scrape URL');
        }
    } catch (error) {
        console.error('Scrape error:', error);
        showStatus('Error: ' + error.message, 'error');
    } finally {
        scrapeBtn.disabled = false;
        scrapeBtn.textContent = 'Scrape Metadata';
    }
}

function showStatus(message, type) {
    const statusDiv = document.getElementById('scrapeStatus');
    statusDiv.className = type === 'success'
        ? 'p-3 rounded bg-green-900/50 border border-green-500 text-green-400'
        : 'p-3 rounded bg-red-900/50 border border-red-500 text-red-400';
    statusDiv.textContent = message;
    statusDiv.classList.remove('hidden');

    if (type === 'success') {
        setTimeout(() => {
            statusDiv.classList.add('hidden');
        }, 3000);
    }
}

async function saveCarouselItem(e) {
    e.preventDefault();

    const formData = new FormData();
    const form = e.target;

    // Collect form data
    formData.append('title', document.getElementById('itemTitle').value.trim());
    formData.append('description', document.getElementById('itemDescription').value.trim());
    formData.append('image_url', document.getElementById('itemImage').value.trim());
    formData.append('link_url', document.getElementById('itemLink').value.trim());
    formData.append('display_order', document.getElementById('itemOrder').value);
    formData.append('active', document.getElementById('itemActive').checked ? 1 : 0);
    formData.append('publish_date', document.getElementById('itemPublish').value);
    formData.append('expire_date', document.getElementById('itemExpire').value);

    const saveBtn = document.getElementById('saveBtn');
    const messageDiv = document.getElementById('formMessage');

    saveBtn.disabled = true;
    saveBtn.textContent = editingItemId ? 'Updating...' : 'Saving...';

    try {
        let response;

        if (editingItemId) {
            // Update existing item
            const params = new URLSearchParams(formData);
            params.append('id', editingItemId);

            response = await fetch('/api/carousel_items.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString()
            });
        } else {
            // Create new item
            response = await fetch('/api/carousel_items.php', {
                method: 'POST',
                body: formData
            });
        }

        const data = await response.json();

        if (data.success) {
            messageDiv.className = 'p-4 rounded bg-green-900/50 border border-green-500 text-green-400';
            messageDiv.textContent = data.message;
            messageDiv.classList.remove('hidden');

            // Reset form and reload list
            setTimeout(() => {
                resetForm();
                loadCarouselItems();
                messageDiv.classList.add('hidden');
            }, 1500);
        } else {
            throw new Error(data.error || 'Failed to save carousel item');
        }
    } catch (error) {
        console.error('Save error:', error);
        messageDiv.className = 'p-4 rounded bg-red-900/50 border border-red-500 text-red-400';
        messageDiv.textContent = 'Error: ' + error.message;
        messageDiv.classList.remove('hidden');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = editingItemId ? 'Update Carousel Item' : 'Save Carousel Item';
    }
}

async function loadCarouselItems() {
    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const itemsList = document.getElementById('itemsList');

    loadingState.classList.remove('hidden');
    emptyState.classList.add('hidden');
    itemsList.classList.add('hidden');

    try {
        const response = await fetch('/api/carousel_items.php?admin=true');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to load carousel items');
        }

        loadingState.classList.add('hidden');

        if (data.items.length === 0) {
            emptyState.classList.remove('hidden');
            return;
        }

        // Display items
        itemsList.innerHTML = '';
        data.items.forEach(item => {
            itemsList.appendChild(createItemCard(item));
        });
        itemsList.classList.remove('hidden');

    } catch (error) {
        console.error('Error loading carousel items:', error);
        loadingState.classList.add('hidden');
        itemsList.innerHTML = `
            <div class="punk-card bg-red-900/20 border-red-500 p-6 text-center">
                <p class="text-red-400">Error loading carousel items: ${error.message}</p>
            </div>
        `;
        itemsList.classList.remove('hidden');
    }
}

function createItemCard(item) {
    const card = document.createElement('div');
    card.className = 'punk-card p-4';

    const now = new Date();
    const publishDate = item.publish_date ? new Date(item.publish_date) : null;
    const expireDate = item.expire_date ? new Date(item.expire_date) : null;

    let statusBadge = '';
    if (!item.active) {
        statusBadge = '<span class="text-xs px-2 py-1 rounded bg-gray-500/20 text-gray-500 uppercase">Inactive</span>';
    } else if (publishDate && publishDate > now) {
        statusBadge = '<span class="text-xs px-2 py-1 rounded bg-yellow-500/20 text-yellow-500 uppercase">Scheduled</span>';
    } else if (expireDate && expireDate < now) {
        statusBadge = '<span class="text-xs px-2 py-1 rounded bg-red-500/20 text-red-500 uppercase">Expired</span>';
    } else {
        statusBadge = '<span class="text-xs px-2 py-1 rounded bg-green-500/20 text-green-500 uppercase">Active</span>';
    }

    card.innerHTML = `
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-shrink-0">
                <img src="${item.image_url}"
                     alt="${item.title}"
                     class="w-32 h-24 object-cover rounded border-2 border-pink-500"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22128%22 height=%2296%22%3E%3Crect width=%22128%22 height=%2296%22 fill=%22%23333%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%23666%22%3ENo Image%3C/text%3E%3C/svg%3E'">
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-start gap-2 mb-2">
                    <h3 class="text-xl font-bold flex-1">${item.title}</h3>
                    ${statusBadge}
                    <span class="text-xs px-2 py-1 rounded bg-pink-500/20 text-pink-500">
                        Order: ${item.display_order}
                    </span>
                </div>
                ${item.description ? `<p class="text-sm text-gray-400 mb-2 line-clamp-2">${item.description}</p>` : ''}
                <div class="text-xs text-gray-500 space-y-1">
                    ${item.link_url ? `<p>Link: <a href="${item.link_url}" target="_blank" class="text-pink-500 hover:underline break-all">${item.link_url}</a></p>` : ''}
                    ${item.publish_date ? `<p>Publish: <strong>${formatDate(item.publish_date)}</strong></p>` : ''}
                    ${item.expire_date ? `<p>Expires: <strong>${formatDate(item.expire_date)}</strong></p>` : ''}
                    <p>Created by: <strong>${item.created_by_name || 'Unknown'}</strong> on ${formatDate(item.created_at)}</p>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <button class="punk-button-outline text-sm" onclick="editItem(${item.id})">
                    Edit
                </button>
                <button class="punk-button-outline text-sm text-red-500 border-red-500 hover:bg-red-500/20" onclick="deleteItem(${item.id}, '${item.title.replace(/'/g, "\\'")}')">
                    Delete
                </button>
            </div>
        </div>
    `;

    return card;
}

async function editItem(id) {
    try {
        const response = await fetch('/api/carousel_items.php?admin=true');
        const data = await response.json();

        if (!data.success) {
            throw new Error('Failed to load carousel items');
        }

        // Convert id to number for comparison
        const itemId = parseInt(id);
        const item = data.items.find(i => parseInt(i.id) === itemId);
        if (!item) {
            throw new Error('Item not found');
        }

        // Populate form
        editingItemId = id;
        document.getElementById('itemTitle').value = item.title || '';
        document.getElementById('itemDescription').value = item.description || '';
        document.getElementById('itemImage').value = item.image_url || '';
        document.getElementById('itemLink').value = item.link_url || '';
        document.getElementById('itemOrder').value = item.display_order || 0;
        document.getElementById('itemActive').checked = item.active == 1;
        document.getElementById('itemPublish').value = item.publish_date || '';
        document.getElementById('itemExpire').value = item.expire_date || '';

        // Trigger image preview
        document.getElementById('itemImage').dispatchEvent(new Event('input'));

        // Update button text
        document.getElementById('saveBtn').textContent = 'Update Carousel Item';
        document.getElementById('cancelBtn').classList.remove('hidden');

        // Scroll to form
        window.scrollTo({ top: 0, behavior: 'smooth' });

    } catch (error) {
        console.error('Error loading item for edit:', error);
        alert('Error loading item: ' + error.message);
    }
}

async function deleteItem(id, title) {
    if (!confirm(`Are you sure you want to delete "${title}"?`)) {
        return;
    }

    try {
        const response = await fetch('/api/carousel_items.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        });

        const data = await response.json();

        if (data.success) {
            loadCarouselItems();
        } else {
            throw new Error(data.error || 'Failed to delete item');
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('Error deleting item: ' + error.message);
    }
}

function resetForm() {
    editingItemId = null;
    document.getElementById('carouselForm').reset();
    document.getElementById('scrapeUrl').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('saveBtn').textContent = 'Save Carousel Item';
    document.getElementById('cancelBtn').classList.add('hidden');
    document.getElementById('formMessage').classList.add('hidden');
    document.getElementById('scrapeStatus').classList.add('hidden');
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}
