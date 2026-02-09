<?php
require_once __DIR__ . '/includes/session.php';
Session::requireLogin();
$adminUsername = Session::getAdminUsername();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miko & Mae | Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css?v=1.0.2">
</head>

<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <span class="logo-icon">M</span>
                    <div class="logo-text">
                        <span class="logo-title">Miko & Mae</span>
                        <span class="logo-subtitle">ADMIN PORTAL</span>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    <a href="#" class="nav-item active" data-section="guests">
                        <i class="fas fa-users"></i>
                        <span>Guest List</span>
                    </a>
                    <a href="#" class="nav-item" data-section="quiz">
                        <i class="fas fa-question-circle"></i>
                        <span>Quiz Manager</span>
                    </a>
                    <a href="#" class="nav-item" data-section="settings">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </nav>

                <div class="sidebar-footer">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?= strtoupper(substr($adminUsername, 0, 1)) ?>
                        </div>
                        <div class="user-details">
                            <span class="user-name">
                                <?= htmlspecialchars($adminUsername) ?>
                            </span>
                            <span class="user-role">Administrator</span>
                        </div>
                    </div>
                    <button class="btn-logout" onclick="logout()" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <h1 class="page-title">Guest List Management</h1>
                <div class="header-actions">

                    <button class="btn btn-primary" id="addGuestBtn">
                        <i class="fas fa-plus"></i>
                        <span>Add New Guest</span>
                    </button>
                </div>
            </header>

            <!-- Dashboard Section -->
            <section class="content-section" id="dashboardSection">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">TOTAL GUESTS</span>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                        <div class="stat-value" id="totalGuests">0</div>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            <span id="guestTrend">0%</span>
                            <span class="trend-period">vs last week</span>
                        </div>
                    </div>

                    <div class="stat-card highlight">
                        <div class="stat-header">
                            <span class="stat-label">CONFIRMED</span>
                            <i class="fas fa-check-circle stat-icon"></i>
                        </div>
                        <div class="stat-value" id="confirmedGuests">0</div>
                        <div class="stat-progress">
                            <div class="progress-bar" id="confirmedProgress"></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">DECLINED</span>
                            <i class="fas fa-times-circle stat-icon"></i>
                        </div>
                        <div class="stat-value" id="declinedGuests">0</div>
                        <div class="stat-subtitle">
                            <span id="declinedRate">0%</span> response rate
                        </div>
                    </div>

                    <div class="stat-card top-scorers-card">
                        <div class="stat-header">
                            <span class="stat-label">🏆 TOP 5 QUIZ SCORERS</span>
                            <i class="fas fa-trophy stat-icon" style="color: var(--accent-gold);"></i>
                        </div>
                        <div class="top-scorers-list" id="topScorersList">
                            <div class="loading-scorers">
                                <i class="fas fa-spinner fa-spin"></i> Loading...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Guest Table -->
                <div class="table-container">
                    <div class="table-header">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search guests by name or email...">
                        </div>
                        <div class="table-filters">
                            <select id="statusFilter" class="filter-select">
                                <option value="all">All Statuses</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="declined">Declined</option>
                            </select>
                            <button class="btn btn-outline" id="exportBtn">
                                <i class="fas fa-download"></i>
                                <span>Export</span>
                            </button>
                        </div>
                    </div>

                    <table class="data-table" id="guestTable">
                        <thead>
                            <tr>
                                <th>Guest Name</th>
                                <th>Status</th>
                                <th>Quiz Score</th>
                                <th>Message</th>
                                <th>Date RSVP'd</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="guestTableBody">
                            <!-- Rows populated by JavaScript -->
                        </tbody>
                    </table>

                    <div class="table-footer">
                        <div class="table-footer-left">
                            <span class="table-info">Showing <span id="showingStart">0</span> to <span
                                    id="showingEnd">0</span> of <span id="totalResults">0</span> results</span>
                            <select id="itemsPerPage" class="items-per-page">
                                <option value="10">10 per page</option>
                                <option value="30">30 per page</option>
                                <option value="60">60 per page</option>
                                <option value="90">90 per page</option>
                                <option value="120">120 per page</option>
                                <option value="150">150 per page</option>
                            </select>
                        </div>
                        <div class="pagination" id="pagination">
                            <!-- Pagination populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quiz Manager Section -->
            <section class="content-section hidden" id="quizSection">
                <div class="section-header">
                    <h2>Quiz Manager</h2>
                    <p>Create and manage quiz questions for your guests</p>
                </div>

                <div class="quiz-grid">
                    <!-- Add New Question Form -->
                    <div class="quiz-form-card">
                        <h3 id="quizFormTitle"><i class="fas fa-plus-circle"></i> Add New Question</h3>
                        <form id="quizForm">
                            <div class="form-group">
                                <label for="questionText">Question</label>
                                <textarea id="questionText" rows="3" placeholder="Enter your question..."
                                    required></textarea>
                            </div>

                            <div class="choices-container">
                                <label>Answer Choices</label>
                                <div class="choice-input">
                                    <input type="radio" name="correctAnswer" value="0" required>
                                    <input type="text" id="choice1" placeholder="Choice 1" required>
                                </div>
                                <div class="choice-input">
                                    <input type="radio" name="correctAnswer" value="1">
                                    <input type="text" id="choice2" placeholder="Choice 2" required>
                                </div>
                                <div class="choice-input">
                                    <input type="radio" name="correctAnswer" value="2">
                                    <input type="text" id="choice3" placeholder="Choice 3" required>
                                </div>
                                <div class="choice-input">
                                    <input type="radio" name="correctAnswer" value="3">
                                    <input type="text" id="choice4" placeholder="Choice 4" required>
                                </div>
                                <p class="form-hint"><i class="fas fa-info-circle"></i> Select the radio button next to
                                    the correct answer</p>
                            </div>

                            <div class="form-buttons">
                                <button type="submit" id="quizSubmitBtn" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> Save Question
                                </button>
                                <button type="button" id="cancelQuizEdit" class="btn btn-secondary btn-block"
                                    style="display: none;">
                                    <i class="fas fa-times"></i> Cancel Edit
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Existing Questions List -->
                    <div class="questions-list-card">
                        <h3><i class="fas fa-list"></i> Existing Questions (<span id="questionCount">0</span>)</h3>
                        <div class="questions-list" id="questionsList">
                            <!-- Questions populated by JavaScript -->
                            <div class="empty-state">
                                <i class="fas fa-question-circle"></i>
                                <p>No questions yet. Add your first question!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Settings Section -->
            <section class="content-section hidden" id="settingsSection">
                <div class="section-header">
                    <h2>Settings</h2>
                    <p>Configure your admin portal preferences</p>
                </div>

                <div class="settings-card">
                    <h3>Wedding Details</h3>
                    <form id="settingsForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="groom_name">Groom's Name</label>
                                <input type="text" id="groom_name" name="groom_name">
                            </div>
                            <div class="form-group">
                                <label for="bride_name">Bride's Name</label>
                                <input type="text" id="bride_name" name="bride_name">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="wedding_date">Wedding Date</label>
                            <input type="date" id="wedding_date" name="wedding_date">
                        </div>
                        <div class="form-group">
                            <label for="rsvp_deadline">RSVP Deadline</label>
                            <input type="date" id="rsvp_deadline" name="rsvp_deadline">
                        </div>
                        <div class="form-group">
                            <label for="wedding_hashtag">Wedding Hashtag</label>
                            <input type="text" id="wedding_hashtag" name="wedding_hashtag"
                                placeholder="#MikoAndMaeForever">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </form>
                </div>

                <div class="settings-card danger-zone">
                    <h3>Danger Zone</h3>
                    <p>These actions cannot be undone.</p>
                    <button class="btn btn-danger" id="clearDataBtn">
                        <i class="fas fa-trash"></i> Clear All Guest Data
                    </button>
                </div>
            </section>
        </main>
    </div>

    <!-- Add/Edit Guest Modal -->
    <div class="modal-overlay" id="guestModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Guest</h3>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <form id="guestForm">
                <div class="modal-body">
                    <input type="hidden" id="guestId">
                    <div class="form-group">
                        <label for="guestName">Full Name *</label>
                        <input type="text" id="guestName" required>
                    </div>
                    <div class="form-group">
                        <label for="guestStatus">Status *</label>
                        <select id="guestStatus" required>
                            <option value="confirmed">Confirmed</option>
                            <option value="declined">Declined</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="guestMessage">Message</label>
                        <textarea id="guestMessage" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="cancelModal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Guest</button>
                </div>
            </form>
        </div>
    </div>

    <script src="admin.js?v=1.0.0"></script>
</body>

</html>