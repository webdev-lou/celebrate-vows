// ================================
// Admin Dashboard JavaScript (API Version)
// ================================

// API Endpoints
const API = {
    AUTH: 'api/auth.php',
    GUESTS: 'api/guests.php',
    QUIZ: 'api/quiz.php',
    SETTINGS: 'api/settings.php'
};

// State
let guests = [];
let quizQuestions = [];
let currentPage = 1;
let ITEMS_PER_PAGE = 10;

// Initialize on DOM Load
document.addEventListener('DOMContentLoaded', function () {
    initNavigation();
    initMobileMenu();
    initGuestManagement();
    initQuizManagement();
    initSettings();
    initModal();
    loadDashboardData();
});

// ================================
// Mobile Menu Toggle
// ================================
function initMobileMenu() {
    const toggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!toggle || !sidebar || !overlay) return;

    toggle.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        // Toggle icon
        const icon = this.querySelector('i');
        if (sidebar.classList.contains('open')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });

    // Close sidebar when clicking overlay
    overlay.addEventListener('click', function () {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        const icon = toggle.querySelector('i');
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
    });

    // Close sidebar when clicking a nav item (mobile)
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function () {
            if (window.innerWidth <= 992) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                const icon = toggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    });
}

// ================================
// Logout Function
// ================================
async function logout() {
    try {
        await fetch(`${API.AUTH}?action=logout`, { method: 'POST' });
        window.location.href = 'login.php';
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = 'login.php';
    }
}

// ================================
// Navigation
// ================================
function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const sections = {
        guests: document.getElementById('dashboardSection'),
        quiz: document.getElementById('quizSection'),
        settings: document.getElementById('settingsSection')
    };

    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const section = this.dataset.section;

            // Update active nav
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');

            // Show/hide sections
            Object.values(sections).forEach(s => s.classList.add('hidden'));
            if (sections[section]) {
                sections[section].classList.remove('hidden');
            }

            // Update page title
            const titles = {
                guests: 'Guest List Management',
                quiz: 'Quiz Manager',
                settings: 'Settings'
            };
            document.querySelector('.page-title').textContent = titles[section] || 'Guest List Management';
        });
    });
}

// ================================
// API Data Fetching
// ================================
async function fetchGuests() {
    try {
        const response = await fetch(API.GUESTS);
        if (!response.ok) {
            if (response.status === 401) {
                window.location.href = 'login.php';
                return;
            }
            throw new Error('Failed to fetch guests');
        }
        const data = await response.json();
        guests = data.guests || [];
        return data;
    } catch (error) {
        console.error('Error fetching guests:', error);
        showNotification('Failed to load guests', 'error');
        return { guests: [], stats: { total: 0, confirmed: 0, declined: 0 } };
    }
}

async function fetchQuizQuestions() {
    try {
        const response = await fetch(API.QUIZ);
        if (!response.ok) throw new Error('Failed to fetch questions');
        const data = await response.json();
        quizQuestions = data.questions || [];
        return quizQuestions;
    } catch (error) {
        console.error('Error fetching quiz questions:', error);
        return [];
    }
}

// ================================
// Dashboard Data Loading
// ================================
async function loadDashboardData() {
    const data = await fetchGuests();

    // Update stats
    const stats = data.stats || { total: 0, confirmed: 0, declined: 0 };
    document.getElementById('totalGuests').textContent = stats.total || 0;
    document.getElementById('confirmedGuests').textContent = stats.confirmed || 0;
    document.getElementById('declinedGuests').textContent = stats.declined || 0;

    // Calculate percentages
    const total = parseInt(stats.total) || 0;
    const confirmed = parseInt(stats.confirmed) || 0;

    if (total > 0) {
        const confirmRate = Math.round((confirmed / total) * 100);
        document.getElementById('confirmedProgress').style.width = `${confirmRate}%`;
        document.getElementById('declinedRate').textContent = `${confirmRate}%`;
    }

    // Render guest table
    renderGuestTable();

    // Render top scorers
    renderTopScorers();

    // Load quiz questions
    await fetchQuizQuestions();
    renderQuestionsList();
}

// ================================
// Top 5 Quiz Scorers
// ================================
function renderTopScorers() {
    const container = document.getElementById('topScorersList');

    // Filter out (Groom) and (Bride) and guests with no quiz score
    const eligibleGuests = guests.filter(guest => {
        const name = guest.name.toLowerCase();
        return guest.quiz_score !== null &&
            guest.quiz_score > 0 &&
            !name.includes('(groom)') &&
            !name.includes('(bride)');
    });

    // Sort by quiz score (highest first), then by created_at (earliest first for tie-breaker)
    const sortedGuests = eligibleGuests.sort((a, b) => {
        if (b.quiz_score !== a.quiz_score) {
            return b.quiz_score - a.quiz_score;
        }
        // If same score, earliest submission wins
        return new Date(a.created_at) - new Date(b.created_at);
    });

    // Take top 5
    const topScorers = sortedGuests.slice(0, 5);

    if (topScorers.length === 0) {
        container.innerHTML = '<div class="no-scorers">No quiz scores yet</div>';
        return;
    }

    const rankClasses = ['gold', 'silver', 'bronze', '', ''];

    container.innerHTML = topScorers.map((guest, index) => `
        <div class="scorer-item">
            <div class="scorer-rank ${rankClasses[index]}">${index + 1}</div>
            <span class="scorer-name">${escapeHtml(guest.name)}</span>
            <span class="scorer-score">${guest.quiz_score}/10</span>
        </div>
    `).join('');
}

// ================================
// Guest Table Rendering
// ================================
function renderGuestTable() {
    const tbody = document.getElementById('guestTableBody');
    const statusFilter = document.getElementById('statusFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();

    // Filter guests
    let filteredGuests = guests.filter(guest => {
        const matchesStatus = statusFilter === 'all' || guest.status === statusFilter;
        const matchesSearch = guest.name.toLowerCase().includes(searchTerm);
        return matchesStatus && matchesSearch;
    });

    // Pagination
    const totalPages = Math.ceil(filteredGuests.length / ITEMS_PER_PAGE);
    currentPage = Math.min(currentPage, totalPages || 1);
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const paginatedGuests = filteredGuests.slice(startIndex, startIndex + ITEMS_PER_PAGE);

    // Update pagination info
    document.getElementById('showingStart').textContent = filteredGuests.length ? startIndex + 1 : 0;
    document.getElementById('showingEnd').textContent = Math.min(startIndex + ITEMS_PER_PAGE, filteredGuests.length);
    document.getElementById('totalResults').textContent = filteredGuests.length;

    // Render table rows
    if (paginatedGuests.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                    No guests found
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = paginatedGuests.map(guest => {
            const initials = guest.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const avatarColors = ['teal', 'gold', 'blue'];
            const avatarColor = avatarColors[guest.id % avatarColors.length];
            const formattedDate = guest.created_at ? new Date(guest.created_at).toLocaleDateString() : '-';
            const quizScore = guest.quiz_score !== null ? `${guest.quiz_score}/10` : '-';

            return `
                <tr data-id="${guest.id}">
                    <td>
                        <div class="guest-info">
                            <div class="guest-avatar ${avatarColor}">${initials}</div>
                            <div class="guest-details">
                                <span class="guest-name">${escapeHtml(guest.name)}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge ${guest.status}">${capitalize(guest.status)}</span>
                    </td>
                    <td class="quiz-score">${quizScore}</td>
                    <td><span class="guest-message">${escapeHtml(guest.message || '-')}</span></td>
                    <td>${formattedDate}</td>
                    <td>
                        <button class="action-btn" onclick="editGuest(${guest.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="deleteGuest(${guest.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Render pagination
    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const pagination = document.getElementById('pagination');
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }

    let html = `
        <button class="page-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fas fa-chevron-left"></i>
        </button>
    `;

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            html += `<span style="color: var(--text-muted);">...</span>`;
        }
    }

    html += `
        <button class="page-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <i class="fas fa-chevron-right"></i>
        </button>
    `;

    pagination.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    renderGuestTable();
}

// ================================
// Guest Management
// ================================
function initGuestManagement() {
    document.getElementById('searchInput').addEventListener('input', () => {
        currentPage = 1;
        renderGuestTable();
    });

    document.getElementById('statusFilter').addEventListener('change', () => {
        currentPage = 1;
        renderGuestTable();
    });

    document.getElementById('itemsPerPage').addEventListener('change', (e) => {
        ITEMS_PER_PAGE = parseInt(e.target.value);
        currentPage = 1;
        renderGuestTable();
    });

    document.getElementById('addGuestBtn').addEventListener('click', () => openGuestModal());

    document.getElementById('guestForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        await saveGuest();
    });

    document.getElementById('exportBtn').addEventListener('click', exportGuests);
}

async function saveGuest() {
    const id = document.getElementById('guestId').value;
    const guestData = {
        name: document.getElementById('guestName').value,
        status: document.getElementById('guestStatus').value,
        message: document.getElementById('guestMessage').value
    };

    try {
        const url = id ? `${API.GUESTS}?id=${id}` : API.GUESTS;
        const method = id ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(guestData)
        });

        const result = await response.json();

        if (response.ok) {
            showNotification(id ? 'Guest updated successfully' : 'Guest added successfully');
            closeGuestModal();
            await loadDashboardData();
        } else {
            showNotification(result.error || 'Failed to save guest', 'error');
        }
    } catch (error) {
        console.error('Error saving guest:', error);
        showNotification('Failed to save guest', 'error');
    }
}

function openGuestModal(guest = null) {
    const modal = document.getElementById('guestModal');
    const title = document.getElementById('modalTitle');

    if (guest) {
        title.textContent = 'Edit Guest';
        document.getElementById('guestId').value = guest.id;
        document.getElementById('guestName').value = guest.name;
        document.getElementById('guestStatus').value = guest.status;
        document.getElementById('guestMessage').value = guest.message || '';
    } else {
        title.textContent = 'Add New Guest';
        document.getElementById('guestForm').reset();
        document.getElementById('guestId').value = '';
    }

    modal.classList.add('active');
}

function closeGuestModal() {
    document.getElementById('guestModal').classList.remove('active');
}

function editGuest(id) {
    const guest = guests.find(g => g.id == id);
    if (guest) {
        openGuestModal(guest);
    }
}

async function deleteGuest(id) {
    if (!confirm('Are you sure you want to delete this guest?')) return;

    try {
        const response = await fetch(`${API.GUESTS}?id=${id}`, { method: 'DELETE' });
        const result = await response.json();

        if (response.ok) {
            showNotification('Guest deleted successfully');
            await loadDashboardData();
        } else {
            showNotification(result.error || 'Failed to delete guest', 'error');
        }
    } catch (error) {
        console.error('Error deleting guest:', error);
        showNotification('Failed to delete guest', 'error');
    }
}

function exportGuests() {
    if (guests.length === 0) {
        showNotification('No guests to export', 'error');
        return;
    }

    const csvContent = [
        ['Name', 'Status', 'Quiz Score', 'Message', 'Date'].join(','),
        ...guests.map(g => [
            `"${g.name}"`,
            g.status,
            g.quiz_score || '',
            `"${(g.message || '').replace(/"/g, '""')}"`,
            g.created_at ? new Date(g.created_at).toLocaleDateString() : ''
        ].join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `wedding_guests_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);

    showNotification('Guest list exported successfully');
}

// ================================
// Modal
// ================================
function initModal() {
    document.getElementById('closeModal').addEventListener('click', closeGuestModal);
    document.getElementById('cancelModal').addEventListener('click', closeGuestModal);
    document.getElementById('guestModal').addEventListener('click', function (e) {
        if (e.target === this) closeGuestModal();
    });
}
// ================================
// Quiz Management
// ================================
const MAX_QUESTIONS = 10;
let editingQuestionId = null;

function initQuizManagement() {
    document.getElementById('quizForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        await saveQuestion();
    });

    // Add cancel edit button handler
    document.getElementById('cancelQuizEdit')?.addEventListener('click', cancelQuizEdit);
}

async function saveQuestion() {
    const question = document.getElementById('questionText').value;
    const options = [
        document.getElementById('choice1').value,
        document.getElementById('choice2').value,
        document.getElementById('choice3').value,
        document.getElementById('choice4').value
    ];
    const correctIndex = document.querySelector('input[name="correctAnswer"]:checked')?.value;

    if (correctIndex === undefined) {
        showNotification('Please select the correct answer', 'error');
        return;
    }

    // Check 10 question limit when adding new
    if (!editingQuestionId && quizQuestions.length >= MAX_QUESTIONS) {
        showNotification(`Maximum of ${MAX_QUESTIONS} questions allowed`, 'error');
        return;
    }

    const questionData = {
        question: question,
        options: options,
        correct_answer: options[parseInt(correctIndex)],
        sort_order: editingQuestionId ? undefined : quizQuestions.length + 1
    };

    try {
        const url = editingQuestionId ? `${API.QUIZ}?id=${editingQuestionId}` : API.QUIZ;
        const method = editingQuestionId ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(questionData)
        });

        const result = await response.json();

        if (response.ok) {
            showNotification(editingQuestionId ? 'Question updated successfully' : 'Question added successfully');
            resetQuizForm();
            await fetchQuizQuestions();
            renderQuestionsList();
        } else {
            showNotification(result.error || 'Failed to save question', 'error');
        }
    } catch (error) {
        console.error('Error saving question:', error);
        showNotification('Failed to save question', 'error');
    }
}

function editQuestion(id) {
    const question = quizQuestions.find(q => q.id == id);
    if (!question) return;

    editingQuestionId = id;

    // Populate form
    document.getElementById('questionText').value = question.question;
    document.getElementById('choice1').value = question.options[0] || '';
    document.getElementById('choice2').value = question.options[1] || '';
    document.getElementById('choice3').value = question.options[2] || '';
    document.getElementById('choice4').value = question.options[3] || '';

    // Select the correct answer radio
    const correctIndex = question.options.indexOf(question.correct_answer);
    if (correctIndex >= 0) {
        document.querySelector(`input[name="correctAnswer"][value="${correctIndex}"]`).checked = true;
    }

    // Update UI to show editing mode
    document.getElementById('quizFormTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Question';
    document.getElementById('quizSubmitBtn').innerHTML = '<i class="fas fa-save"></i> Update Question';
    document.getElementById('cancelQuizEdit').style.display = 'block';

    // Enable button when editing (not adding new)
    updateQuizButtonState();

    // Scroll to form
    document.getElementById('quizForm').scrollIntoView({ behavior: 'smooth' });

    // Re-render list to highlight editing question
    renderQuestionsList();
}

function cancelQuizEdit() {
    resetQuizForm();
}

function resetQuizForm() {
    editingQuestionId = null;
    document.getElementById('quizForm').reset();
    document.getElementById('quizFormTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Question';
    document.getElementById('quizSubmitBtn').innerHTML = '<i class="fas fa-save"></i> Save Question';
    document.getElementById('cancelQuizEdit').style.display = 'none';
    updateQuizButtonState();
}

function updateQuizButtonState() {
    const addBtn = document.getElementById('quizSubmitBtn');
    // Only disable when adding NEW question and at max limit
    // When editing (editingQuestionId is set), always enable
    if (!editingQuestionId && quizQuestions.length >= MAX_QUESTIONS) {
        addBtn.disabled = true;
        addBtn.title = `Maximum ${MAX_QUESTIONS} questions reached`;
    } else {
        addBtn.disabled = false;
        addBtn.title = '';
    }
}

function renderQuestionsList() {
    const container = document.getElementById('questionsList');
    const countEl = document.getElementById('questionCount');

    countEl.textContent = quizQuestions.length;

    // Update add button state based on limit
    updateQuizButtonState();

    if (quizQuestions.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-question-circle"></i>
                <p>No questions yet. Add your first question!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = quizQuestions.map((q, index) => `
        <div class="question-item ${editingQuestionId == q.id ? 'editing' : ''}">
            <div class="question-number">${index + 1}</div>
            <div class="question-content">
                <p class="question-text">${escapeHtml(q.question)}</p>
                <div class="question-options">
                    ${q.options.map(opt => `
                        <span class="option ${opt === q.correct_answer ? 'correct' : ''}">
                            ${opt === q.correct_answer ? '<i class="fas fa-check"></i>' : ''}
                            ${escapeHtml(opt)}
                        </span>
                    `).join('')}
                </div>
            </div>
            <div class="question-actions">
                <button class="action-btn" onclick="editQuestion(${q.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn delete" onclick="deleteQuestion(${q.id})" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

async function deleteQuestion(id) {
    if (!confirm('Delete this question?')) return;

    try {
        const response = await fetch(`${API.QUIZ}?id=${id}`, { method: 'DELETE' });
        const result = await response.json();

        if (response.ok) {
            showNotification('Question deleted');
            if (editingQuestionId == id) {
                resetQuizForm();
            }
            await fetchQuizQuestions();
            renderQuestionsList();
        } else {
            showNotification(result.error || 'Failed to delete', 'error');
        }
    } catch (error) {
        showNotification('Failed to delete question', 'error');
    }
}

// ================================
// Settings
// ================================
// ================================
// Settings
// ================================
function initSettings() {
    loadSettings();

    document.getElementById('settingsForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        await saveSettings();
    });

    document.getElementById('clearDataBtn')?.addEventListener('click', async function () {
        if (!confirm('Are you sure you want to delete ALL guest data? This cannot be undone!')) return;
        // ... (rest of clear data logic)
    });
}

async function loadSettings() {
    try {
        const response = await fetch(API.SETTINGS);
        if (!response.ok) throw new Error('Failed to load settings');
        const settings = await response.json();

        // Populate form fields
        if (document.getElementById('groom_name')) document.getElementById('groom_name').value = settings.groom_name || '';
        if (document.getElementById('bride_name')) document.getElementById('bride_name').value = settings.bride_name || '';
        if (document.getElementById('wedding_date')) document.getElementById('wedding_date').value = settings.wedding_date || '';
        if (document.getElementById('rsvp_deadline')) document.getElementById('rsvp_deadline').value = settings.rsvp_deadline || '';
        if (document.getElementById('wedding_hashtag')) document.getElementById('wedding_hashtag').value = settings.wedding_hashtag || '';

    } catch (error) {
        console.error('Error loading settings:', error);
        showNotification('Failed to load settings', 'error');
    }
}

async function saveSettings() {
    const formData = new FormData(document.getElementById('settingsForm'));
    const settings = Object.fromEntries(formData.entries());

    try {
        const response = await fetch(API.SETTINGS, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(settings)
        });

        const result = await response.json();

        if (response.ok) {
            showNotification('Settings saved successfully');
        } else {
            showNotification(result.error || 'Failed to save settings', 'error');
        }
    } catch (error) {
        console.error('Error saving settings:', error);
        showNotification('Failed to save settings', 'error');
    }
}



// ================================
// Utilities
// ================================
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message, type = 'success') {
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">&times;</button>
    `;

    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        background: ${type === 'success' ? '#2A9D8F' : '#E76F51'};
        color: white;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;

    document.body.appendChild(notification);

    notification.querySelector('.notification-close').addEventListener('click', () => notification.remove());

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in forwards';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Add keyframe animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
