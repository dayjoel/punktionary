// manage-users.js - Admin interface for managing user permissions
// account_type: 0 = user, 1 = admin, 2 = god

let allUsers = [];
let currentUserId = null;
let currentUserAccountType = 0;

// Load users when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadCurrentUser();
    loadUsers();
    setupSearch();
});

async function loadCurrentUser() {
    try {
        const response = await fetch('/api/user_profile.php');
        const data = await response.json();
        if (data.success) {
            currentUserId = data.user.id;
            currentUserAccountType = data.user.account_type;
        }
    } catch (error) {
        console.error('Error loading current user:', error);
    }
}

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
        currentUserAccountType = data.current_user_account_type;
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

    const accountType = parseInt(user.account_type);
    const isCurrentUser = user.id == currentUserId;

    const roleConfig = {
        0: { class: 'bg-gray-500/20 text-gray-400', text: 'USER' },
        1: { class: 'bg-pink-500/20 text-pink-500', text: 'ADMIN' },
        2: { class: 'bg-purple-500/20 text-purple-500', text: 'GOD' }
    };

    const roleInfo = roleConfig[accountType] || roleConfig[0];

    const joinDate = new Date(user.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });

    let actionButtons = '';
    if (isCurrentUser) {
        actionButtons = `<p class="text-gray-500 text-sm italic">You cannot modify your own privileges</p>`;
    } else if (accountType == 2 && currentUserAccountType < 2) {
        // Admins cannot modify god accounts
        actionButtons = `<p class="text-gray-500 text-sm italic">God-tier account (protected)</p>`;
    } else {
        // Show buttons based on current user's permission level
        if (currentUserAccountType == 2) {
            // God can change anyone to any level
            actionButtons = `
                <div class="flex gap-2 flex-wrap">
                    ${accountType != 0 ? `<button onclick="setAccountType(${user.id}, 0)" class="punk-button-outline text-sm">Make User</button>` : ''}
                    ${accountType != 1 ? `<button onclick="setAccountType(${user.id}, 1)" class="punk-button text-sm">Make Admin</button>` : ''}
                    ${accountType != 2 ? `<button onclick="setAccountType(${user.id}, 2)" class="punk-button bg-purple-600 hover:bg-purple-700 text-sm">Make God</button>` : ''}
                </div>
            `;
        } else {
            // Regular admins can only toggle between user and admin
            if (accountType == 0) {
                actionButtons = `<button onclick="setAccountType(${user.id}, 1)" class="punk-button">Make Admin</button>`;
            } else if (accountType == 1) {
                actionButtons = `<button onclick="setAccountType(${user.id}, 0)" class="punk-button-outline border-red-500 text-red-500 hover:bg-red-500 hover:text-white">Revoke Admin</button>`;
            }
        }
    }

    card.innerHTML = `
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4 flex-1">
                <img src="${user.profile_picture_url || '/images/default-avatar.svg'}"
                     alt="${user.display_name}"
                     class="w-16 h-16 rounded-full border-2 border-pink-500" />
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h3 class="text-xl font-bold">${user.display_name}${isCurrentUser ? ' <span class="text-sm text-gray-500">(You)</span>' : ''}</h3>
                        <span class="text-xs px-2 py-1 rounded ${roleInfo.class} uppercase font-bold">
                            ${roleInfo.text}
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
                ${actionButtons}
            </div>
        </div>
    `;

    return card;
}

async function setAccountType(userId, newAccountType) {
    const typeNames = {0: 'user', 1: 'admin', 2: 'god'};
    const user = allUsers.find(u => u.id == userId);

    if (!user) {
        alert('User not found');
        return;
    }

    const actionText = `change ${user.display_name}'s account type to ${typeNames[newAccountType]}`;

    if (!confirm(`Are you sure you want to ${actionText}?`)) {
        return;
    }

    // Disable buttons during operation
    const actionsDiv = document.getElementById(`actions-${userId}`);
    const originalHTML = actionsDiv.innerHTML;
    actionsDiv.innerHTML = '<p class="text-gray-500 text-sm">Processing...</p>';

    try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('account_type', newAccountType);

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

// Make setAccountType available globally
window.setAccountType = setAccountType;
