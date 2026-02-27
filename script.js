// ================================
// Wedding Website JavaScript
// ================================

document.addEventListener('DOMContentLoaded', function () {
    // Initialize all components
    initNavigation();
    initScrollEffects();
    loadQuizQuestions(); // Load quiz from database
    initRSVPForm();
    initSmoothScroll();
    loadSettings(); // Load dynamic settings
    preloadImages(); // Preload gallery and attire images
    initMediaUpload(); // Media upload functionality
});

// ================================
// Image Preloading
// ================================
function preloadImages() {
    // Preload gallery images
    const galleryToPreload = [
        'images/gallery/IMG_3649.webp',
        'images/gallery/IMG_3528.webp',
        'images/gallery/IMG_3568.webp',
        'images/gallery/IMG_3605.webp',
        'images/gallery/IMG_3724.webp',
        'images/gallery/IMG_3901.webp',
        'images/gallery/IMG_3610.webp',
        'images/gallery/IMG_3534.webp',
        'images/gallery/IMG_3596.webp',
        'images/gallery/IMG_3602.webp',
        'images/gallery/IMG_3609.webp',
        'images/gallery/IMG_3618.webp',
        'images/gallery/IMG_3639.webp',
        'images/gallery/IMG_3701.webp',
        'images/gallery/IMG_3783.webp',
        'images/gallery/IMG_3921.webp'
    ];

    // Preload attire images
    const attireToPreload = [
        'images/pale-cream-semi.webp',
        'images/pale-cream-formal.webp',
        'images/warm-tan-semi.webp',
        'images/warm-tan-formal.webp',
        'images/soft-turqoise-semi.webp',
        'images/soft-turqoise-formal.webp',
        'images/deep-teal-semi.webp',
        'images/deep-teal-formal.webp',
        'images/dark-teal-blue-semi.webp',
        'images/dark-teal-blue-formal.webp'
    ];

    // Combine all images
    const allImages = [...galleryToPreload, ...attireToPreload];

    // Preload each image
    allImages.forEach(src => {
        const img = new Image();
        img.src = src;
    });

    console.log(`Preloaded ${allImages.length} images`);
}

// ================================
// Settings Management
// ================================
async function loadSettings() {
    try {
        const response = await fetch('api/settings.php');
        if (!response.ok) throw new Error('Failed to load settings');
        const settings = await response.json();

        applySettings(settings);
    } catch (error) {
        console.error('Error loading settings:', error);
    }
}

function applySettings(settings) {
    // Update Couple Names
    if (settings.groom_name && settings.bride_name) {
        const namesEl = document.getElementById('coupleNames');
        if (namesEl) {
            namesEl.innerHTML = `${settings.groom_name} <span class="ampersand">&</span> ${settings.bride_name}`;
        }
    }

    // Update Wedding Date (all locations)
    if (settings.wedding_date) {
        const date = new Date(settings.wedding_date);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = date.toLocaleDateString('en-US', options);

        // Hero section
        const dateEl = document.getElementById('weddingDateDisplay');
        if (dateEl) {
            dateEl.textContent = formattedDate.toUpperCase();
        }

        // Details section (with day of week)
        const detailsDate = document.getElementById('weddingDateDetails');
        if (detailsDate) {
            const dayOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            detailsDate.textContent = date.toLocaleDateString('en-US', dayOptions);
        }
    }

    // Update RSVP Deadline
    if (settings.rsvp_deadline) {
        const deadlineEl = document.getElementById('rsvpDeadlineDisplay');
        if (deadlineEl) {
            const date = new Date(settings.rsvp_deadline);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            deadlineEl.textContent = date.toLocaleDateString('en-US', options);
        }
    }

    // Update Hashtags
    if (settings.wedding_hashtag) {
        const hashtagText = document.getElementById('weddingHashtagText');
        const hashtagFooter = document.getElementById('weddingHashtagFooter');

        if (hashtagText) hashtagText.textContent = settings.wedding_hashtag;
        if (hashtagFooter) hashtagFooter.textContent = settings.wedding_hashtag;
    }
}

// ================================
// Gallery Lightbox
// ================================
const galleryImages = [
    'images/gallery/IMG_3649.webp',
    'images/gallery/IMG_3528.webp',
    'images/gallery/IMG_3568.webp',
    'images/gallery/IMG_3605.webp',
    'images/gallery/IMG_3724.webp',
    'images/gallery/IMG_3901.webp',
    'images/gallery/IMG_3610.webp',
    'images/gallery/IMG_3534.webp',
    'images/gallery/IMG_3596.webp',
    'images/gallery/IMG_3602.webp',
    'images/gallery/IMG_3609.webp',
    'images/gallery/IMG_3618.webp',
    'images/gallery/IMG_3639.webp',
    'images/gallery/IMG_3701.webp',
    'images/gallery/IMG_3783.webp',
    'images/gallery/IMG_3921.webp'
];

let currentGalleryIndex = 0;

function openGalleryLightbox(index) {
    currentGalleryIndex = index;
    const lightbox = document.getElementById('galleryLightbox');
    const image = document.getElementById('galleryLightboxImage');
    const counter = document.getElementById('galleryCounter');

    image.src = galleryImages[index];
    counter.textContent = `${index + 1} / ${galleryImages.length}`;
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeGalleryLightbox() {
    const lightbox = document.getElementById('galleryLightbox');
    lightbox.classList.remove('active');
    document.body.style.overflow = '';
}

function navigateGallery(direction) {
    currentGalleryIndex += direction;

    // Loop around
    if (currentGalleryIndex < 0) {
        currentGalleryIndex = galleryImages.length - 1;
    } else if (currentGalleryIndex >= galleryImages.length) {
        currentGalleryIndex = 0;
    }

    const image = document.getElementById('galleryLightboxImage');
    const counter = document.getElementById('galleryCounter');

    image.src = galleryImages[currentGalleryIndex];
    counter.textContent = `${currentGalleryIndex + 1} / ${galleryImages.length}`;
}

// Close lightbox on background click
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('gallery-lightbox')) {
        closeGalleryLightbox();
    }
});

// Keyboard navigation for gallery
document.addEventListener('keydown', function (e) {
    const lightbox = document.getElementById('galleryLightbox');
    if (!lightbox || !lightbox.classList.contains('active')) return;

    if (e.key === 'Escape') {
        closeGalleryLightbox();
    } else if (e.key === 'ArrowLeft') {
        navigateGallery(-1);
    } else if (e.key === 'ArrowRight') {
        navigateGallery(1);
    }
});

// Store quiz data from database for scoring
let quizData = [];

// ================================
// Navigation
// ================================
function initNavigation() {
    const navbar = document.getElementById('navbar');
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');

    // Toggle mobile menu
    navToggle.addEventListener('click', function () {
        navMenu.classList.toggle('active');
        this.classList.toggle('active');
    });

    // Close menu on link click
    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            navMenu.classList.remove('active');
            navToggle.classList.remove('active');

            // Update active link
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Navbar scroll effect
    window.addEventListener('scroll', function () {
        if (window.scrollY > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }

        // Update active nav link based on scroll position
        updateActiveNavLink();
    });
}

function updateActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');

    let current = '';

    sections.forEach(section => {
        const sectionTop = section.offsetTop - 150;
        const sectionHeight = section.offsetHeight;

        if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
            current = section.getAttribute('id');
        }
    });

    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${current}`) {
            link.classList.add('active');
        }
    });
}

// ================================
// Scroll Effects
// ================================
function initScrollEffects() {
    // Add fade-in class to elements
    const fadeElements = document.querySelectorAll(
        '.journey-content, .detail-card, .member-card, .rsvp-form-container, .info-card, .upload-card, .gallery-preview'
    );

    fadeElements.forEach(el => {
        el.classList.add('fade-in');
    });

    // Intersection Observer for scroll animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    fadeElements.forEach(el => observer.observe(el));
}

// ================================
// Smooth Scroll
// ================================
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));

            if (target) {
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.scrollY - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// ================================
// RSVP & Quiz Form
// ================================
function initRSVPForm() {
    const form = document.getElementById('rsvp-form');

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Simple validation
            if (!data.name || !data.attending) {
                showNotification('Please fill in all required fields.', 'error');
                return;
            }

            // Collect quiz answers
            const quizAnswers = {};
            for (let i = 1; i <= 10; i++) {
                quizAnswers[`q${i}`] = data[`q${i}`];
            }

            // Calculate quiz score
            const quizScore = calculateQuizScore(quizAnswers);

            // Create guest object for API
            const guestData = {
                name: data.name,
                status: data.attending === 'yes' ? 'confirmed' : 'declined',
                message: data.message || '',
                quiz_score: quizScore,
                quiz_answers: quizAnswers
            };

            // Submit to API
            try {
                const response = await fetch('api/guests.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(guestData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Show success message
                    const message = data.attending === 'yes'
                        ? `Thank you for your RSVP! You scored ${quizScore}/10 on the quiz. We look forward to celebrating with you!`
                        : 'Thank you for letting us know. We\'ll miss you!';
                    showNotification(message, 'success');

                    // Reset form
                    form.reset();
                } else {
                    showNotification(result.error || 'Failed to submit RSVP. Please try again.', 'error');
                }
            } catch (error) {
                console.error('RSVP submission error:', error);
                showNotification('Connection error. Please try again.', 'error');
            }
        });
    }
}

function calculateQuizScore(answers) {
    // Calculate score using correct answers from database
    let score = 0;
    quizData.forEach((q, index) => {
        const questionIndex = index + 1;
        const userAnswer = answers[`q${questionIndex}`];
        // Compare user answer with correct answer from database
        if (userAnswer === q.correct_answer) {
            score++;
        }
    });
    return score;
}

// ================================
// Load Quiz Questions from Database
// ================================
async function loadQuizQuestions() {
    const container = document.getElementById('quiz-container');
    if (!container) return;

    try {
        const response = await fetch('api/quiz.php');
        if (!response.ok) throw new Error('Failed to load questions');

        const data = await response.json();
        quizData = data.questions || [];

        if (quizData.length === 0) {
            container.innerHTML = '<p style="color: var(--text-muted);">No quiz questions available.</p>';
            return;
        }

        renderQuizQuestions(container, quizData);
    } catch (error) {
        console.error('Error loading quiz:', error);
        container.innerHTML = '<p style="color: #E76F51;">Failed to load quiz questions. Please refresh the page.</p>';
    }
}

function renderQuizQuestions(container, questions) {
    container.innerHTML = questions.map((q, index) => {
        const questionNum = index + 1;
        const optionsHtml = q.options.map(opt =>
            `<option value="${escapeHtml(opt)}">${escapeHtml(opt)}</option>`
        ).join('');

        return `
            <div class="form-group">
                <label for="q${questionNum}">${questionNum}. ${escapeHtml(q.question)}</label>
                <select id="q${questionNum}" name="q${questionNum}" required>
                    <option value="" disabled selected>Select an answer...</option>
                    ${optionsHtml}
                </select>
            </div>
        `;
    }).join('');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ================================
// Notification System
// ================================
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">&times;</button>
    `;

    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        background: ${type === 'success' ? '#2A9D8F' : '#E76F51'};
        color: white;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
    `;

    // Add animation keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        @keyframes slideOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
        .notification-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .notification-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .notification-close:hover {
            opacity: 1;
        }
    `;
    document.head.appendChild(style);

    document.body.appendChild(notification);

    // Close button handler
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => notification.remove(), 300);
    });

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOut 0.3s ease-out forwards';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// ================================
// Countdown Timer (Optional)
// ================================
function initCountdown() {
    const weddingDate = new Date('February 28, 2026 15:00:00').getTime();

    const countdown = setInterval(() => {
        const now = new Date().getTime();
        const distance = weddingDate - now;

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        const countdownEl = document.getElementById('countdown');
        if (countdownEl) {
            countdownEl.innerHTML = `
                <div class="countdown-item">
                    <span class="countdown-number">${days}</span>
                    <span class="countdown-label">Days</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number">${hours}</span>
                    <span class="countdown-label">Hours</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number">${minutes}</span>
                    <span class="countdown-label">Minutes</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number">${seconds}</span>
                    <span class="countdown-label">Seconds</span>
                </div>
            `;
        }

        if (distance < 0) {
            clearInterval(countdown);
            if (countdownEl) {
                countdownEl.innerHTML = '<p class="countdown-message">The celebration has begun!</p>';
            }
        }
    }, 1000);
}

// ================================
// Image Lazy Loading
// ================================
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');

    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
}

// ================================
// Parallax Effect (Optional)
// ================================
function initParallax() {
    const hero = document.querySelector('.hero');

    window.addEventListener('scroll', () => {
        const scrolled = window.scrollY;
        if (hero && scrolled < window.innerHeight) {
            hero.style.backgroundPositionY = `${scrolled * 0.5}px`;
        }
    });
}

// ================================
// Attire Lightbox with Style Selection
// ================================
const attireImages = {
    beige: {
        'semi-formal': 'images/pale-cream-semi.webp',
        'formal': 'images/pale-cream-formal.webp'
    },
    tan: {
        'semi-formal': 'images/warm-tan-semi.webp',
        'formal': 'images/warm-tan-formal.webp'
    },
    turquoise: {
        'semi-formal': 'images/soft-turqoise-semi.webp',
        'formal': 'images/soft-turqoise-formal.webp'
    },
    teal: {
        'semi-formal': 'images/deep-teal-semi.webp',
        'formal': 'images/deep-teal-formal.webp'
    },
    charcoal: {
        'semi-formal': 'images/dark-teal-blue-semi.webp',
        'formal': 'images/dark-teal-blue-formal.webp'
    }
};

let currentColorKey = '';
let currentColorName = '';

function openAttireLightbox(colorKey, colorName) {
    const lightbox = document.getElementById('attireLightbox');
    const lightboxTitle = document.getElementById('lightboxTitle');
    const styleSelectSubtitle = document.getElementById('styleSelectSubtitle');
    const styleSelectScreen = document.getElementById('styleSelectScreen');
    const imageScreen = document.getElementById('imageScreen');

    if (lightbox && lightboxTitle) {
        currentColorKey = colorKey;
        currentColorName = colorName;

        lightboxTitle.textContent = colorName + ' Attire Inspiration';
        styleSelectSubtitle.textContent = `Choose the type of attire you'd like to see for ${colorName}.`;

        // Show style select, hide image
        styleSelectScreen.style.display = 'block';
        imageScreen.style.display = 'none';

        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function selectAttireStyle(style) {
    const styleSelectScreen = document.getElementById('styleSelectScreen');
    const imageScreen = document.getElementById('imageScreen');
    const imageTitle = document.getElementById('imageTitle');
    const lightboxImage = document.getElementById('lightboxImage');

    const styleName = style === 'semi-formal' ? 'Semi-Formal' : 'Formal';

    // Update image screen
    imageTitle.textContent = `${currentColorName} - ${styleName}`;
    lightboxImage.src = attireImages[currentColorKey][style];
    lightboxImage.alt = `${currentColorName} ${styleName} Philippine Attire`;

    // Switch screens
    styleSelectScreen.style.display = 'none';
    imageScreen.style.display = 'block';
}

function goBackToStyleSelect() {
    const styleSelectScreen = document.getElementById('styleSelectScreen');
    const imageScreen = document.getElementById('imageScreen');

    styleSelectScreen.style.display = 'block';
    imageScreen.style.display = 'none';
}

function closeLightbox() {
    const lightbox = document.getElementById('attireLightbox');
    if (lightbox) {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';

        // Reset to style select screen for next open
        const styleSelectScreen = document.getElementById('styleSelectScreen');
        const imageScreen = document.getElementById('imageScreen');
        if (styleSelectScreen) styleSelectScreen.style.display = 'block';
        if (imageScreen) imageScreen.style.display = 'none';
    }
}

// Close lightbox on overlay click
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('lightbox-overlay')) {
        closeLightbox();
    }
});

// Close lightbox on Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
});

// ================================
// Media Upload
// ================================
function initMediaUpload() {
    const fileInput = document.getElementById('mediaFileInput');
    const dropzone = document.getElementById('uploadDropzone');
    const chooseBtn = document.getElementById('chooseFilesBtn');
    const previewArea = document.getElementById('uploadPreviewArea');
    const previewGrid = document.getElementById('previewGrid');
    const clearBtn = document.getElementById('clearFilesBtn');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadMoreBtn = document.getElementById('uploadMoreBtn');
    const successDiv = document.getElementById('uploadSuccess');

    if (!fileInput || !dropzone) return;

    let selectedFiles = [];

    const ALLOWED_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/quicktime', 'video/webm', 'video/x-msvideo'
    ];
    const MAX_SIZE = 400 * 1024 * 1024; // 400MB

    // Choose files button
    chooseBtn.addEventListener('click', () => fileInput.click());

    // File input change
    fileInput.addEventListener('change', () => {
        handleFiles(fileInput.files);
    });

    // Drag and drop
    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('drag-over');
    });
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('drag-over');
    });
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('drag-over');
        handleFiles(e.dataTransfer.files);
    });

    // Clear files
    clearBtn.addEventListener('click', resetUpload);

    // Upload button
    uploadBtn.addEventListener('click', uploadFiles);

    // Upload more
    uploadMoreBtn.addEventListener('click', resetUpload);

    function handleFiles(fileList) {
        const files = Array.from(fileList);
        let rejected = [];

        files.forEach(file => {
            if (!ALLOWED_TYPES.includes(file.type)) {
                rejected.push(`${file.name}: not a valid photo or video`);
                return;
            }
            if (file.size > MAX_SIZE) {
                rejected.push(`${file.name}: exceeds 400MB limit`);
                return;
            }
            if (selectedFiles.length < 50) {
                selectedFiles.push(file);
            }
        });

        if (rejected.length > 0) {
            alert('Some files were not added:\n' + rejected.join('\n'));
        }

        if (selectedFiles.length > 0) {
            showPreviews();
        }
    }

    function showPreviews() {
        dropzone.style.display = 'none';
        previewArea.style.display = 'block';
        successDiv.style.display = 'none';

        document.getElementById('selectedFileCount').textContent =
            `${selectedFiles.length} file${selectedFiles.length > 1 ? 's' : ''} selected`;

        previewGrid.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'preview-item';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);
                item.appendChild(img);
            } else {
                const videoIcon = document.createElement('div');
                videoIcon.className = 'preview-video-icon';
                videoIcon.innerHTML = '<i class="fas fa-play-circle"></i>';
                item.appendChild(videoIcon);
            }

            const name = document.createElement('span');
            name.className = 'preview-name';
            name.textContent = file.name.length > 15
                ? file.name.substring(0, 12) + '...'
                : file.name;
            item.appendChild(name);

            const removeBtn = document.createElement('button');
            removeBtn.className = 'preview-remove';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = () => {
                selectedFiles.splice(index, 1);
                if (selectedFiles.length === 0) {
                    resetUpload();
                } else {
                    showPreviews();
                }
            };
            item.appendChild(removeBtn);

            previewGrid.appendChild(item);
        });
    }

    async function uploadFiles() {
        if (selectedFiles.length === 0) return;

        const uploaderNameInput = document.getElementById('uploaderName');
        const uploaderName = uploaderNameInput.value.trim();
        if (!uploaderName) {
            alert('Please enter your name before uploading.');
            uploaderNameInput.focus();
            return;
        }

        const progressDiv = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');

        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        progressDiv.style.display = 'block';

        const CHUNK_SIZE = 8 * 1024 * 1024; // 8MB chunks
        let totalBytes = selectedFiles.reduce((sum, f) => sum + f.size, 0);
        let uploadedBytes = 0;
        let successCount = 0;
        let errors = [];

        for (let fileIdx = 0; fileIdx < selectedFiles.length; fileIdx++) {
            const file = selectedFiles[fileIdx];
            progressText.textContent = `Uploading file ${fileIdx + 1} of ${selectedFiles.length}...`;

            if (file.size <= CHUNK_SIZE) {
                // Small file: direct upload
                try {
                    const formData = new FormData();
                    formData.append('files[]', file);
                    formData.append('uploader_name', uploaderName);

                    const response = await new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        xhr.upload.addEventListener('progress', (e) => {
                            if (e.lengthComputable) {
                                const currentTotal = uploadedBytes + e.loaded;
                                const pct = Math.round((currentTotal / totalBytes) * 100);
                                progressFill.style.width = pct + '%';
                                progressText.textContent = `Uploading file ${fileIdx + 1} of ${selectedFiles.length}... ${pct}%`;
                            }
                        });
                        xhr.addEventListener('load', () => resolve(xhr));
                        xhr.addEventListener('error', () => reject(new Error('Network error')));
                        xhr.open('POST', 'api/media.php');
                        xhr.send(formData);
                    });

                    const data = JSON.parse(response.responseText);
                    if (data.success) {
                        successCount += data.uploaded_count;
                    } else {
                        errors.push(file.name + ': ' + (data.error || 'Failed'));
                    }
                } catch (err) {
                    errors.push(file.name + ': ' + err.message);
                }
                uploadedBytes += file.size;
            } else {
                // Large file: chunked upload
                const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
                const uploadId = 'upload_' + Date.now() + '_' + fileIdx + '_' + Math.random().toString(36).substr(2, 9);
                let chunkSuccess = true;

                for (let chunkIdx = 0; chunkIdx < totalChunks; chunkIdx++) {
                    const start = chunkIdx * CHUNK_SIZE;
                    const end = Math.min(start + CHUNK_SIZE, file.size);
                    const chunk = file.slice(start, end);

                    try {
                        const formData = new FormData();
                        formData.append('action', 'chunk');
                        formData.append('chunk', chunk, 'chunk');
                        formData.append('upload_id', uploadId);
                        formData.append('chunk_index', chunkIdx);
                        formData.append('total_chunks', totalChunks);

                        const response = await fetch('api/media.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        if (!data.success) {
                            chunkSuccess = false;
                            errors.push(file.name + ': Chunk ' + chunkIdx + ' failed - ' + (data.error || 'Unknown'));
                            break;
                        }

                        uploadedBytes += (end - start);
                        const pct = Math.round((uploadedBytes / totalBytes) * 100);
                        progressFill.style.width = pct + '%';
                        progressText.textContent = `Uploading file ${fileIdx + 1} of ${selectedFiles.length}... ${pct}%`;
                    } catch (err) {
                        chunkSuccess = false;
                        errors.push(file.name + ': Network error on chunk ' + chunkIdx);
                        break;
                    }
                }

                // Assemble the chunks
                if (chunkSuccess) {
                    try {
                        progressText.textContent = `Processing file ${fileIdx + 1} of ${selectedFiles.length}...`;
                        const formData = new FormData();
                        formData.append('action', 'assemble');
                        formData.append('upload_id', uploadId);
                        formData.append('file_name', file.name);
                        formData.append('total_chunks', totalChunks);
                        formData.append('total_size', file.size);
                        formData.append('uploader_name', uploaderName);

                        const response = await fetch('api/media.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            successCount++;
                        } else {
                            errors.push(file.name + ': Assembly failed - ' + (data.error || 'Unknown'));
                        }
                    } catch (err) {
                        errors.push(file.name + ': Assembly error - ' + err.message);
                    }
                }
            }
        }

        // Show result
        if (successCount > 0) {
            dropzone.style.display = 'none';
            previewArea.style.display = 'none';
            successDiv.style.display = 'block';
            document.getElementById('uploadSuccessMsg').textContent =
                `${successCount} file${successCount > 1 ? 's' : ''} uploaded successfully!`;
            selectedFiles = [];
        } else {
            alert('Upload failed: ' + errors.join('\n'));
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Upload Files';
            progressDiv.style.display = 'none';
        }

        if (errors.length > 0 && successCount > 0) {
            console.warn('Some files failed:', errors);
        }
    }

    function resetUpload() {
        selectedFiles = [];
        fileInput.value = '';
        previewGrid.innerHTML = '';
        dropzone.style.display = 'block';
        previewArea.style.display = 'none';
        successDiv.style.display = 'none';
        document.getElementById('uploadProgress').style.display = 'none';
        document.getElementById('progressFill').style.width = '0%';
        const uploadBtn = document.getElementById('uploadBtn');
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Upload Files';
    }
}

// Initialize optional features
// initCountdown();
// initParallax();
