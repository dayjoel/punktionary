// manage-users.js - Admin interface for managing user permissions

let allUsers = [];

// Load users when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    setupSearch();
});

function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const filtered = allUsers.filter(user =>
            user.display_name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query)
        );
        displayUsers(filtered);
    });
}

async function loadUsers() {
    const loadingState = document.getElementById('loadingState');
    const usersList = document.getElementById('usersList');
    const emptyState = document.getElementById('emptyState');

    loadingState.classList.remove('hidden');
    usersList.classList.add('hidden');
    emptyState.classList.add('hidden');

    try {
        const response = await fetch('/api/get_all_users.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to load users');
        }

        allUsers = data.users;
        loadingState.classList.add('hidden');

        if (allUsers.length === 0) {
            emptyState.classList.remove('hidden');
            return;
        }

        displayUsers(allUsers);
        usersList.classList.remove('hidden');

    } catch (error) {
        console.error('Error loading users:', error);
        loadingState.classList.add('hidden');
        usersList.innerHTML = `
            <div class="punk-card bg-red-900/20 border-red-500 p-6 text-center">
                <p class="text-red-400">Error loading users: ${error.message}</p>
            </div>
        `;
        usersList.classList.remove('hidden');
    }
}

function displayUsers(users) {
    const usersList = document.getElementById('usersList');
    usersList.innerHTML = '';

    users.forEach(user => {
        usersList.appendChild(createUserCard(user));
    });
}

function createUserCard(user) {
    const card = document.createElement('div');
    card.className = 'punk-card p-6';

    const isAdmin = user.is_admin == 1;
    const roleClass = isAdmin ? 'bg-pink-500/20 text-pink-500' : 'bg-gray-500/20 text-gray-400';
    const roleText = isAdmin ? 'ADMIN' : 'USER';

    const joinDate = new Date(user.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });

    card.innerHTML = `
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4 flex-1">
                <img src="${user.profile_picture_url || '/images/default-avatar.svg'}"
                     alt="${user.display_name}"
                     class="w-16 h-16 rounded-full border-2 border-pink-500" />
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="text-xl font-bold">${user.display_name}</h3>
                        <span class="text-xs px-2 py-1 rounded ${roleClass} uppercase font-bold">
                            ${roleText}
                        </span>
                    </div>
                    <div class="text-sm text-gray-400 space-y-1">
                        <p>${user.email}</p>
                        <p>Joined: ${joinDate}</p>
                        <p>Signed in via: ${user.oauth_provider.charAt(0).toUpperCase() + user.oauth_provider.slice(1)}</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-2" id="actions-${user.id}">
                ${isAdmin ? `
                    <button onclick="toggleAdmin(${user.id}, false)" class="punk-button-outline border-red-500 text-red-500 hover:bg-red-500 hover:text-white">
                        Revoke Admin
                    </button>
                ` : `
                    <button onclick="toggleAdmin(${user.id}, true)" class="punk-button">
                        Make Admin
                    </button>
                `}
            </div>
        </div>
    `;

    return card;
}

async function toggleAdmin(userId, makeAdmin) {
    const actionText = makeAdmin ? 'grant admin privileges to' : 'revoke admin privileges from';
    const user = allUsers.find(u => u.id === userId);

    if (!confirm(`Are you sure you want to ${actionText} ${user.display_name}?`)) {
        return;
    }

    // Disable buttons during operation
    const actionsDiv = document.getElementById(`actions-${userId}`);
    const originalHTML = actionsDiv.innerHTML;
    actionsDiv.innerHTML = '<p class="text-gray-500 text-sm">Processing...</p>';

    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('is_admin', makeAdmin ? '1' : '0');

        const response = await fetch('/api/update_user_admin.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Reload users list to show updated status
            await loadUsers();
        } else {
            throw new Error(data.error || 'Failed to update user');
        }
    } catch (error) {
        console.error('Error updating user:', error);
        alert('Failed to update user: ' + error.message);
        // Restore buttons
        actionsDiv.innerHTML = originalHTML;
    }
}

// Make toggleAdmin available globally
window.toggleAdmin = toggleAdmin;
