// admin-panel.js - Unified admin panel

let adminCurrentUser = null;
let selectedUser = null;

// Load data when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPage);
} else {
    // DOM already loaded
    initPage();
}

function initPage() {
    loadCurrentUser();
    loadStats();
    setupSearch();
}

async function loadCurrentUser() {
    try {
        const response = await fetch('/api/user_profile.php');
        const data = await response.json();
        if (data.success) {
            adminCurrentUser = data.user;
        }
    } catch (error) {
        console.error('Error loading current user:', error);
    }
}

async function loadStats() {
    try {
        // Load edit counts
        const editsResponse = await fetch('/api/get_pending_edits.php?status=pending');
        const editsData = await editsResponse.json();

        if (editsData.success) {
            document.getElementById('pendingCount').innerHTML = editsData.counts.pending || 0;
            document.getElementById('approvedCount').innerHTML = editsData.counts.approved || 0;
            document.getElementById('rejectedCount').innerHTML = editsData.counts.rejected || 0;
        } else {
            console.error('Failed to load edit counts:', editsData.error);
            document.getElementById('pendingCount').innerHTML = '0';
            document.getElementById('approvedCount').innerHTML = '0';
            document.getElementById('rejectedCount').innerHTML = '0';
        }

        // Load user count
        const usersResponse = await fetch('/api/get_all_users.php');
        const usersData = await usersResponse.json();

        if (usersData.success) {
            document.getElementById('totalUsersCount').innerHTML = usersData.users.length;
        } else {
            console.error('Failed to load user count:', usersData.error);
            document.getElementById('totalUsersCount').innerHTML = '0';
        }
    } catch (error) {
        console.error('Error loading stats:', error);
        document.getElementById('pendingCount').textContent = '?';
        document.getElementById('approvedCount').textContent = '?';
        document.getElementById('rejectedCount').textContent = '?';
        document.getElementById('totalUsersCount').textContent = '?';
    }
}

function setupSearch() {
    const searchInput = document.getElementById('userSearch');
    const searchBtn = document.getElementById('searchBtn');

    // Search on button click
    searchBtn.addEventListener('click', performSearch);

    // Search on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });

    // Close modal
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);
    document.getElementById('userModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
}

async function performSearch() {
    const query = document.getElementById('userSearch').value.trim();

    if (query.length < 2) {
        alert('Please enter at least 2 characters to search');
        return;
    }

    const searchBtn = document.getElementById('searchBtn');
    searchBtn.disabled = true;
    searchBtn.textContent = 'Searching...';

    try {
        const response = await fetch('/api/get_all_users.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to search users');
        }

        // Filter users by query
        const queryLower = query.toLowerCase();
        const results = data.users.filter(user =>
            user.display_name.toLowerCase().includes(queryLower) ||
            user.email.toLowerCase().includes(queryLower)
        );

        displaySearchResults(results);

    } catch (error) {
        console.error('Search error:', error);
        alert('Failed to search users: ' + error.message);
    } finally {
        searchBtn.disabled = false;
        searchBtn.textContent = 'Search Users';
    }
}

function displaySearchResults(results) {
    const resultsList = document.getElementById('searchResultsList');
    const searchResults = document.getElementById('searchResults');
    const noResults = document.getElementById('noResults');

    resultsList.innerHTML = '';

    if (results.length === 0) {
        searchResults.classList.add('hidden');
        noResults.classList.remove('hidden');
        return;
    }

    noResults.classList.add('hidden');
    searchResults.classList.remove('hidden');

    const accountTypes = {0: 'USER', 1: 'ADMIN', 2: 'GOD'};
    const roleClasses = {
        0: 'bg-gray-500/20 text-gray-400',
        1: 'bg-pink-500/20 text-pink-500',
        2: 'bg-purple-500/20 text-purple-500'
    };

    results.forEach(user => {
        const accountType = parseInt(user.account_type);
        const roleClass = roleClasses[accountType] || roleClasses[0];
        const roleText = accountTypes[accountType] || 'USER';

        const userCard = document.createElement('div');
        userCard.className = 'punk-card p-4 cursor-pointer hover:border-pink-500 transition-colors';
        userCard.innerHTML = `
            <div class="flex items-center gap-3">
                <img src="${user.profile_picture_url || '/images/default-avatar.svg'}"
                     alt="${user.display_name}"
                     class="w-12 h-12 rounded-full border-2 border-pink-500" />
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold">${user.display_name}</span>
                        <span class="text-xs px-2 py-1 rounded ${roleClass} uppercase font-bold">
                            ${roleText}
                        </span>
                    </div>
                    <p class="text-sm text-gray-400">${user.email}</p>
                </div>
            </div>
        `;

        userCard.addEventListener('click', () => openUserModal(user));
        resultsList.appendChild(userCard);
    });
}

function openUserModal(user) {
    selectedUser = user;
    const accountType = parseInt(user.account_type);

    const accountTypes = {0: 'User', 1: 'Admin', 2: 'God'};
    const roleClasses = {
        0: 'bg-gray-500/20 text-gray-400',
        1: 'bg-pink-500/20 text-pink-500',
        2: 'bg-purple-500/20 text-purple-500'
    };

    const roleClass = roleClasses[accountType] || roleClasses[0];
    const roleText = accountTypes[accountType] || 'User';

    const joinDate = new Date(user.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    document.getElementById('userInfo').innerHTML = `
        <div class="flex items-center gap-4 mb-4">
            <img src="${user.profile_picture_url || '/images/default-avatar.svg'}"
                 alt="${user.display_name}"
                 class="w-20 h-20 rounded-full border-2 border-pink-500" />
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <h3 class="text-2xl font-bold">${user.display_name}</h3>
                    <span class="text-xs px-2 py-1 rounded ${roleClass} uppercase font-bold">
                        ${roleText}
                    </span>
                </div>
                <p class="text-gray-400">${user.email}</p>
                <p class="text-sm text-gray-500 mt-1">Joined: ${joinDate}</p>
            </div>
        </div>
    `;

    // Check permissions
    const currentAccountType = adminCurrentUser ? adminCurrentUser.account_type : 0;
    const isCurrentUser = adminCurrentUser && user.id == adminCurrentUser.id;

    // Hide/show god button based on permissions
    const makeGodBtn = document.getElementById('makeGodBtn');
    if (currentAccountType < 2) {
        makeGodBtn.classList.add('hidden');
    } else {
        makeGodBtn.classList.remove('hidden');
    }

    // Show actions or message
    const accountActions = document.getElementById('accountActions');
    if (isCurrentUser) {
        accountActions.innerHTML = '<p class="text-gray-500 italic">You cannot modify your own privileges</p>';
    } else if (accountType == 2 && currentAccountType < 2) {
        accountActions.innerHTML = '<p class="text-gray-500 italic">God-tier accounts cannot be modified by admins</p>';
    } else {
        accountActions.innerHTML = `
            <h3 class="font-bold text-pink-500 mb-3">Change Account Type:</h3>
            <div class="flex gap-3 flex-wrap">
                <button onclick="changeUserAccountType(0)" class="punk-button-outline ${accountType == 0 ? 'opacity-50 cursor-not-allowed' : ''}" ${accountType == 0 ? 'disabled' : ''}>
                    Make User
                </button>
                <button onclick="changeUserAccountType(1)" class="punk-button ${accountType == 1 ? 'opacity-50 cursor-not-allowed' : ''}" ${accountType == 1 ? 'disabled' : ''}>
                    Make Admin
                </button>
                ${currentAccountType >= 2 ? `
                    <button onclick="changeUserAccountType(2)" class="punk-button bg-purple-600 hover:bg-purple-700 ${accountType == 2 ? 'opacity-50 cursor-not-allowed' : ''}" ${accountType == 2 ? 'disabled' : ''}>
                        Make God
                    </button>
                ` : ''}
            </div>
        `;
    }

    // Clear previous messages
    document.getElementById('modalMessage').classList.add('hidden');

    // Show modal
    document.getElementById('userModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('userModal').classList.add('hidden');
    selectedUser = null;
}

async function changeUserAccountType(newAccountType) {
    if (!selectedUser) return;

    const typeNames = {0: 'user', 1: 'admin', 2: 'god'};
    const actionText = `change ${selectedUser.display_name}'s account type to ${typeNames[newAccountType]}`;

    if (!confirm(`Are you sure you want to ${actionText}?`)) {
        return;
    }

    const modalMessage = document.getElementById('modalMessage');
    const accountActions = document.getElementById('accountActions');

    try {
        const formData = new FormData();
        formData.append('user_id', selectedUser.id);
        formData.append('account_type', newAccountType);

        const response = await fetch('/api/update_user_admin.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            modalMessage.className = 'p-4 rounded mb-4 bg-green-900/50 border border-green-500 text-green-400';
            modalMessage.textContent = data.message;
            modalMessage.classList.remove('hidden');

            // Update selected user's account type
            selectedUser.account_type = newAccountType;

            // Reload stats
            loadStats();

            // Update the modal display
            setTimeout(() => {
                openUserModal(selectedUser);
            }, 1500);
        } else {
            throw new Error(data.error || 'Failed to update user');
        }
    } catch (error) {
        console.error('Error updating user:', error);
        modalMessage.className = 'p-4 rounded mb-4 bg-red-900/50 border border-red-500 text-red-400';
        modalMessage.textContent = 'Failed to update user: ' + error.message;
        modalMessage.classList.remove('hidden');
    }
}

// Make changeUserAccountType available globally
window.changeUserAccountType = changeUserAccountType;
