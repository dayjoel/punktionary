// review-edits.js - Admin interface for reviewing edit suggestions

let currentFilter = 'pending';
let currentEditData = null;

// Load edits when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadEdits('pending');
    setupEventListeners();
});

function setupEventListeners() {
    // Filter tabs
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const status = this.dataset.status;
            currentFilter = status;

            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Load edits for this status
            loadEdits(status);
        });
    });

    // Modal close
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);

    // Review actions
    document.getElementById('approveBtn').addEventListener('click', () => reviewEdit('approve'));
    document.getElementById('rejectBtn').addEventListener('click', () => reviewEdit('reject'));

    // Close modal on background click
    document.getElementById('reviewModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
}

async function loadEdits(status) {
    const loadingState = document.getElementById('loadingState');
    const editsList = document.getElementById('editsList');
    const emptyState = document.getElementById('emptyState');

    loadingState.classList.remove('hidden');
    editsList.classList.add('hidden');
    emptyState.classList.add('hidden');

    try {
        const response = await fetch(`/api/get_pending_edits.php?status=${status}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to load edits');
        }

        // Update counts
        document.getElementById('pendingCount').textContent = data.counts.pending || 0;
        document.getElementById('approvedCount').textContent = data.counts.approved || 0;
        document.getElementById('rejectedCount').textContent = data.counts.rejected || 0;

        loadingState.classList.add('hidden');

        if (data.edits.length === 0) {
            emptyState.classList.remove('hidden');
            return;
        }

        // Display edits
        editsList.innerHTML = '';
        data.edits.forEach(edit => {
            editsList.appendChild(createEditCard(edit));
        });
        editsList.classList.remove('hidden');

    } catch (error) {
        console.error('Error loading edits:', error);
        loadingState.classList.add('hidden');
        editsList.innerHTML = `
            <div class="punk-card bg-red-900/20 border-red-500 p-6 text-center">
                <p class="text-red-400">Error loading edits: ${error.message}</p>
            </div>
        `;
        editsList.classList.remove('hidden');
    }
}

function createEditCard(edit) {
    const card = document.createElement('div');
    card.className = 'punk-card p-6 cursor-pointer hover:border-pink-500 transition-all';

    const statusColors = {
        'pending': 'text-yellow-500',
        'approved': 'text-green-500',
        'rejected': 'text-red-500'
    };

    const changesCount = Object.keys(edit.field_changes).length;
    const entityName = edit.original_data ? edit.original_data.name : `Unknown ${edit.entity_type}`;

    card.innerHTML = `
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <h3 class="text-xl font-bold">${entityName}</h3>
                    <span class="text-xs px-2 py-1 rounded bg-pink-500/20 text-pink-500 uppercase">
                        ${edit.entity_type}
                    </span>
                    <span class="text-xs px-2 py-1 rounded ${statusColors[edit.status]} bg-current/20 uppercase">
                        ${edit.status}
                    </span>
                </div>
                <div class="text-sm text-gray-400 space-y-1">
                    <p>Submitted by: <strong>${edit.submitted_by_username}</strong></p>
                    <p>Date: <strong>${formatDate(edit.created_at)}</strong></p>
                    <p>Changes: <strong>${changesCount} field${changesCount !== 1 ? 's' : ''}</strong></p>
                    ${edit.reviewed_by_username ? `<p>Reviewed by: <strong>${edit.reviewed_by_username}</strong> on ${formatDate(edit.reviewed_at)}</p>` : ''}
                </div>
            </div>
            <div>
                <button class="punk-button-outline">View Details</button>
            </div>
        </div>
    `;

    card.addEventListener('click', () => openReviewModal(edit));

    return card;
}

function openReviewModal(edit) {
    currentEditData = edit;
    const modal = document.getElementById('reviewModal');

    // Set header info
    document.getElementById('modalEntityType').textContent = edit.entity_type.charAt(0).toUpperCase() + edit.entity_type.slice(1);
    document.getElementById('modalEntityId').textContent = edit.entity_id;
    document.getElementById('modalSubmitter').textContent = edit.submitted_by_username;
    document.getElementById('modalDate').textContent = formatDate(edit.created_at);

    // Show original info
    const originalInfo = document.getElementById('originalInfo');
    if (edit.original_data) {
        originalInfo.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                ${Object.entries(edit.original_data)
                    .filter(([key]) => !['id', 'submitted_by', 'created_at', 'updated_at', 'photo_references'].includes(key))
                    .map(([key, value]) => `
                        <div>
                            <strong class="text-gray-400">${formatFieldName(key)}:</strong>
                            <p class="text-gray-200 break-words">${formatFieldValueForDisplay(key, value)}</p>
                        </div>
                    `).join('')}
            </div>
        `;
    } else {
        originalInfo.innerHTML = '<p class="text-gray-500">Original data not available</p>';
    }

    // Show changes comparison - only show fields that actually changed
    const changesComparison = document.getElementById('changesComparison');
    const actualChanges = Object.entries(edit.field_changes).filter(([field, newValue]) => {
        const oldValue = edit.original_data ? edit.original_data[field] : null;
        // Normalize values for comparison
        const normalizedOld = normalizeValue(oldValue);
        const normalizedNew = normalizeValue(newValue);
        return normalizedOld !== normalizedNew;
    });

    if (actualChanges.length === 0) {
        changesComparison.innerHTML = '<p class="text-gray-500 italic">No actual changes detected</p>';
    } else {
        changesComparison.innerHTML = actualChanges.map(([field, newValue]) => {
            const oldValue = edit.original_data ? edit.original_data[field] : 'N/A';
            return `
                <div class="punk-card bg-black/30 p-4">
                    <h4 class="font-bold text-pink-500 mb-2 uppercase tracking-wide">${formatFieldName(field)}</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">OLD VALUE:</p>
                            <p class="text-gray-400 line-through break-words">${formatFieldValueForDisplay(field, oldValue)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-green-500 mb-1">NEW VALUE:</p>
                            <p class="text-green-400 font-semibold break-words">${formatFieldValueForDisplay(field, newValue)}</p>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Show admin actions or reviewed state
    const adminActions = document.getElementById('adminActions');
    const reviewedState = document.getElementById('reviewedState');
    const adminNotes = document.getElementById('adminNotes');

    if (edit.status === 'pending') {
        adminActions.classList.remove('hidden');
        reviewedState.classList.add('hidden');
        adminNotes.value = '';
    } else {
        adminActions.classList.add('hidden');
        reviewedState.classList.remove('hidden');

        document.getElementById('reviewStatus').textContent = edit.status.toUpperCase();
        document.getElementById('reviewStatus').className = edit.status === 'approved' ? 'text-green-500' : 'text-red-500';
        document.getElementById('reviewedBy').textContent = edit.reviewed_by_username || 'Unknown';
        document.getElementById('reviewedAt').textContent = formatDate(edit.reviewed_at);

        if (edit.admin_notes) {
            document.getElementById('reviewNotesDisplay').classList.remove('hidden');
            document.getElementById('reviewNotesText').textContent = edit.admin_notes;
        } else {
            document.getElementById('reviewNotesDisplay').classList.add('hidden');
        }
    }

    // Clear any previous messages
    document.getElementById('reviewMessage').classList.add('hidden');

    modal.classList.remove('hidden');
}

function closeModal() {
    document.getElementById('reviewModal').classList.add('hidden');
    currentEditData = null;
}

async function reviewEdit(action) {
    if (!currentEditData) return;

    const adminNotes = document.getElementById('adminNotes').value.trim();
    const messageDiv = document.getElementById('reviewMessage');

    // Disable buttons during submission
    const approveBtn = document.getElementById('approveBtn');
    const rejectBtn = document.getElementById('rejectBtn');
    approveBtn.disabled = true;
    rejectBtn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('edit_id', currentEditData.id);
        formData.append('action', action);
        if (adminNotes) {
            formData.append('admin_notes', adminNotes);
        }

        const response = await fetch('/api/review_edit.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.className = 'mt-4 p-4 rounded bg-green-900/50 border border-green-500 text-green-400';
            messageDiv.textContent = data.message;
            messageDiv.classList.remove('hidden');

            // Wait a moment then close modal and reload list
            setTimeout(() => {
                closeModal();
                loadEdits(currentFilter);
            }, 1500);
        } else {
            throw new Error(data.error || 'Review failed');
        }
    } catch (error) {
        console.error('Error reviewing edit:', error);
        messageDiv.className = 'mt-4 p-4 rounded bg-red-900/50 border border-red-500 text-red-400';
        messageDiv.textContent = error.message;
        messageDiv.classList.remove('hidden');

        // Re-enable buttons
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatFieldName(field) {
    return field.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

function formatFieldValue(value) {
    if (value === null || value === undefined || value === '') {
        return '(empty)';
    }
    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
}

function formatFieldValueForDisplay(fieldName, value) {
    // Special formatting for specific fields
    if (value === null || value === undefined || value === '') {
        return '(empty)';
    }

    // Format logo as image preview
    if (fieldName === 'logo' && typeof value === 'string' && value.trim() !== '') {
        return `<img src="${value}" alt="Logo" class="max-w-xs max-h-32 rounded border border-pink-500" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" /><span style="display:none;" class="text-gray-400">${value}</span>`;
    }

    // Format links object as readable list
    if (fieldName === 'links' && typeof value === 'object' && !Array.isArray(value)) {
        const linkEntries = Object.entries(value)
            .filter(([key, url]) => url && url.trim() !== '')
            .map(([key, url]) => {
                const displayName = key.charAt(0).toUpperCase() + key.slice(1);
                return `${displayName}: ${url}`;
            });
        return linkEntries.length > 0 ? linkEntries.join('<br>') : '(empty)';
    }

    // Format arrays as comma-separated list
    if (Array.isArray(value)) {
        return value.length > 0 ? value.join(', ') : '(empty)';
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

function normalizeValue(value) {
    // Normalize values for comparison
    if (value === null || value === undefined || value === '') {
        return '';
    }
    if (typeof value === 'boolean') {
        return value ? '1' : '0';
    }
    if (typeof value === 'number') {
        return String(value);
    }
    if (typeof value === 'object') {
        // For arrays and objects, stringify and sort for consistent comparison
        if (Array.isArray(value)) {
            return JSON.stringify(value.sort());
        }
        return JSON.stringify(value);
    }
    // Trim strings and normalize whitespace
    return String(value).trim();
}
