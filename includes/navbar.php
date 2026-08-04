<?php require_once __DIR__ . '/../config/session.php'; ?>
<?php require_once __DIR__ . '/../includes/functions.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
:root,
.light {
    --nb-bg: rgba(255,255,255,0.80);
    --nb-border: rgba(0,0,0,0.08);
    --nb-brand: #0f172a;
    --nb-brand-hover: #1e293b;
    --nb-btn-bg: rgba(0,0,0,0.04);
    --nb-btn-border: rgba(0,0,0,0.08);
    --nb-btn-color: #64748b;
    --nb-btn-hover-bg: rgba(0,0,0,0.06);
    --nb-btn-hover-color: #0f172a;
    --nb-link-color: #475569;
    --nb-link-hover-bg: rgba(0,0,0,0.04);
    --nb-link-hover-color: #0f172a;
    --nb-link-active-bg: rgba(0,0,0,0.06);
    --nb-link-active-color: #0f172a;
    --nb-dropdown-bg: rgba(255,255,255,0.97);
    --nb-dropdown-border: rgba(0,0,0,0.08);
    --nb-dropdown-shadow: 0 20px 60px rgba(0,0,0,0.12);
    --nb-text-hi: #0f172a;
    --nb-text-mid: #475569;
    --nb-text-low: #94a3b8;
    --nb-divider: rgba(0,0,0,0.06);
    --nb-mobile-bg: rgba(255,255,255,0.97);
}

.dark,
:root {
    --nb-bg: rgba(15,23,42,0.75);
    --nb-border: rgba(255,255,255,0.06);
    --nb-brand: #ffffff;
    --nb-brand-hover: #e2e8f0;
    --nb-btn-bg: rgba(255,255,255,0.05);
    --nb-btn-border: rgba(255,255,255,0.08);
    --nb-btn-color: #cbd5e1;
    --nb-btn-hover-bg: rgba(255,255,255,0.08);
    --nb-btn-hover-color: #ffffff;
    --nb-link-color: #94a3b8;
    --nb-link-hover-bg: rgba(255,255,255,0.06);
    --nb-link-hover-color: #ffffff;
    --nb-link-active-bg: rgba(255,255,255,0.08);
    --nb-link-active-color: #ffffff;
    --nb-dropdown-bg: rgba(30,41,59,0.97);
    --nb-dropdown-border: rgba(255,255,255,0.08);
    --nb-dropdown-shadow: 0 20px 60px rgba(0,0,0,0.5);
    --nb-text-hi: #e2e8f0;
    --nb-text-mid: #cbd5e1;
    --nb-text-low: #64748b;
    --nb-divider: rgba(255,255,255,0.06);
    --nb-mobile-bg: rgba(15,23,42,0.97);
}

@media (prefers-color-scheme: dark) {
    :root:not(.light) {
        --nb-bg: rgba(15,23,42,0.75);
        --nb-border: rgba(255,255,255,0.06);
        --nb-brand: #ffffff;
        --nb-brand-hover: #e2e8f0;
        --nb-btn-bg: rgba(255,255,255,0.05);
        --nb-btn-border: rgba(255,255,255,0.08);
        --nb-btn-color: #cbd5e1;
        --nb-btn-hover-bg: rgba(255,255,255,0.08);
        --nb-btn-hover-color: #ffffff;
        --nb-link-color: #94a3b8;
        --nb-link-hover-bg: rgba(255,255,255,0.06);
        --nb-link-hover-color: #ffffff;
        --nb-link-active-bg: rgba(255,255,255,0.08);
        --nb-link-active-color: #ffffff;
        --nb-dropdown-bg: rgba(30,41,59,0.97);
        --nb-dropdown-border: rgba(255,255,255,0.08);
        --nb-dropdown-shadow: 0 20px 60px rgba(0,0,0,0.5);
        --nb-text-hi: #e2e8f0;
        --nb-text-mid: #cbd5e1;
        --nb-text-low: #64748b;
        --nb-divider: rgba(255,255,255,0.06);
        --nb-mobile-bg: rgba(15,23,42,0.97);
    }
}

.nb-bar {
    position: sticky;
    top: 0;
    z-index: 1040;
    background: var(--nb-bg);
    backdrop-filter: blur(30px) saturate(1.4);
    -webkit-backdrop-filter: blur(30px) saturate(1.4);
    border-bottom: 1px solid var(--nb-border);
    padding: 0;
}

.nb-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    height: 58px;
    max-width: 100%;
}

/* Brand */
.nb-brand {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 1.15rem;
    color: var(--nb-brand);
    text-decoration: none;
    letter-spacing: -0.01em;
    transition: color 0.2s ease;
    white-space: nowrap;
}

.nb-brand:hover { color: var(--nb-brand-hover); }

/* Sidebar toggle (mobile) */
.nb-sidebar-toggle {
    display: none;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: var(--nb-btn-bg);
    border: 1px solid var(--nb-btn-border);
    color: var(--nb-btn-color);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.3rem;
    padding: 0;
}

.nb-sidebar-toggle:hover {
    background: var(--nb-btn-hover-bg);
    color: var(--nb-btn-hover-color);
}

@media (max-width: 991.98px) {
    .nb-sidebar-toggle { display: flex; }
}

/* Left group */
.nb-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Right group */
.nb-right {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Public nav links */
.nb-nav-links {
    display: flex;
    align-items: center;
    gap: 2px;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nb-nav-link {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--nb-link-color);
    text-decoration: none;
    padding: 8px 14px;
    border-radius: 8px;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.nb-nav-link:hover {
    color: var(--nb-link-hover-color);
    background: var(--nb-link-hover-bg);
}

.nb-nav-link.active {
    color: var(--nb-link-active-color);
    background: var(--nb-link-active-bg);
}

/* Login link */
.nb-login-link {
    font-size: 0.84rem;
    font-weight: 600;
    color: var(--nb-brand);
    text-decoration: none;
    padding: 7px 18px;
    border-radius: 100px;
    background: var(--nb-btn-bg);
    border: 1px solid var(--nb-btn-border);
    transition: all 0.2s ease;
    white-space: nowrap;
}

.nb-login-link:hover {
    background: var(--nb-btn-hover-bg);
    color: var(--nb-btn-hover-color);
}

/* Icon buttons (notification, profile) */
.nb-icon-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--nb-btn-bg);
    border: 1px solid var(--nb-btn-border);
    color: var(--nb-btn-color);
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
    text-decoration: none;
}

.nb-icon-btn:hover {
    background: var(--nb-btn-hover-bg);
    border-color: var(--nb-btn-border);
    color: var(--nb-btn-hover-color);
}

.nb-icon-btn i { font-size: 1.15rem; }

/* Notification badge */
.nb-notif-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 18px;
    height: 18px;
    border-radius: 100px;
    background: #ef4444;
    color: #ffffff;
    font-size: 0.62rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    line-height: 1;
    border: 2px solid var(--nb-bg);
    animation: nbBadgePop 0.3s ease;
}

@keyframes nbBadgePop {
    0% { transform: scale(0); }
    60% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Dropdown menus */
.nb-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    min-width: 320px;
    max-width: 400px;
    background: var(--nb-dropdown-bg);
    backdrop-filter: blur(40px);
    -webkit-backdrop-filter: blur(40px);
    border: 1px solid var(--nb-dropdown-border);
    border-radius: 16px;
    box-shadow: var(--nb-dropdown-shadow);
    padding: 8px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px);
    transition: all 0.2s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    z-index: 1050;
    list-style: none;
    margin: 0;
}

.nb-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Profile dropdown is narrower */
.nb-dropdown.profile-drop {
    min-width: 220px;
    max-width: 260px;
}

/* Dropdown header */
.nb-dd-header {
    padding: 10px 14px;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--nb-text-hi);
}

.nb-dd-divider {
    height: 1px;
    background: var(--nb-divider);
    margin: 4px 10px;
}

/* Notification items */
.nb-notif-item {
    display: block;
    padding: 10px 14px;
    border-radius: 10px;
    text-decoration: none;
    transition: background 0.2s ease;
    color: inherit;
}

.nb-notif-item:hover {
    background: var(--nb-link-hover-bg);
    color: inherit;
}

.nb-notif-msg {
    font-size: 0.85rem;
    color: var(--nb-text-hi);
    line-height: 1.5;
    white-space: normal;
}

.nb-notif-msg.unread { font-weight: 600; }

.nb-notif-time {
    font-size: 0.73rem;
    color: var(--nb-text-low);
    margin-top: 4px;
    display: block;
}

.nb-notif-new {
    display: inline-flex;
    padding: 2px 8px;
    border-radius: 100px;
    font-size: 0.62rem;
    font-weight: 700;
    background: rgba(245,158,11,0.12);
    border: 1px solid rgba(245,158,11,0.25);
    color: #fcd34d;
    flex-shrink: 0;
    margin-left: 8px;
}

.nb-notif-wrap {
    position: relative;
}

.nb-notif-dismiss {
    position: absolute;
    top: 6px;
    right: 6px;
    background: transparent;
    border: none;
    color: var(--nb-text-low);
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.75rem;
    line-height: 1;
    opacity: 0;
    transition: opacity 0.2s ease, color 0.2s ease;
}

.nb-notif-wrap:hover .nb-notif-dismiss {
    opacity: 1;
}

.nb-notif-dismiss:hover {
    color: var(--nb-red);
    background: rgba(239,68,68,0.10);
}

/* Dropdown footer link */
.nb-dd-footer {
    text-align: center;
    padding: 8px 14px;
}

.nb-dd-footer a {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--nb-link-color);
    text-decoration: none;
    transition: color 0.2s ease;
}

.nb-dd-footer a:hover { color: var(--nb-link-hover-color); }

/* Profile dropdown items */
.nb-profile-header {
    padding: 12px 14px;
}

.nb-profile-name {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--nb-text-hi);
}

.nb-profile-role {
    font-size: 0.75rem;
    color: var(--nb-text-low);
    margin-top: 2px;
}

.nb-dd-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--nb-text-mid);
    text-decoration: none;
    transition: all 0.2s ease;
}

.nb-dd-link:hover {
    background: var(--nb-link-hover-bg);
    color: var(--nb-link-hover-color);
}

.nb-dd-link i { font-size: 0.95rem; color: var(--nb-text-low); width: 18px; text-align: center; }

.nb-dd-link.danger { color: #fca5a5; }
.nb-dd-link.danger:hover { background: rgba(239,68,68,0.08); color: #fecaca; }
.nb-dd-link.danger i { color: #ef4444; }

/* Dropdown container for positioning */
.nb-dropdown-wrap {
    position: relative;
}

/* Empty notifications */
.nb-notif-empty {
    padding: 24px 14px;
    text-align: center;
    font-size: 0.85rem;
    color: var(--nb-text-low);
}

/* Toggler (public/mobile) */
.nb-toggler {
    display: none;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: var(--nb-btn-bg);
    border: 1px solid var(--nb-btn-border);
    color: var(--nb-btn-color);
    cursor: pointer;
    padding: 0;
    transition: all 0.2s ease;
}

.nb-toggler:hover {
    background: var(--nb-btn-hover-bg);
    color: var(--nb-btn-hover-color);
}

.nb-toggler .nb-toggler-icon {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 18px;
}

.nb-toggler .nb-toggler-icon span {
    display: block;
    height: 2px;
    background: currentColor;
    border-radius: 2px;
    transition: all 0.2s ease;
}

/* Public nav collapse */
.nb-collapse {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.nb-collapse .nb-nav-links {
    flex: 1;
    justify-content: center;
}

@media (max-width: 991.98px) {
    .nb-collapse {
        display: none;
        flex-direction: column;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--nb-mobile-bg);
        backdrop-filter: blur(30px);
        border-bottom: 1px solid var(--nb-border);
        padding: 16px 24px;
        gap: 8px;
    }

    .nb-collapse.open { display: flex; }

    .nb-collapse .nb-nav-links {
        flex-direction: column;
        align-items: stretch;
    }

    .nb-collapse .nb-nav-link { padding: 10px 14px; }

    .nb-collapse .nb-login-link { text-align: center; margin-top: 8px; }

    .nb-toggler { display: flex; }
}

/* ═══════════════════════════════
   RESPONSIVE
   ═══════════════════════════════ */
@media (max-width: 575.98px) {
    .nb-inner { padding: 0 14px; height: 52px; }
    .nb-brand { font-size: 1rem; }
    .nb-dropdown { min-width: 280px; right: -8px; }
    .nb-dropdown.profile-drop { min-width: 200px; }
}
</style>

<nav class="nb-bar">
    <div class="nb-inner">

        <!-- Left: sidebar toggle + brand -->
        <div class="nb-left">
            <?php if (isLoggedIn()): ?>
                <button class="nb-sidebar-toggle" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#appSidebar"
                        aria-controls="appSidebar" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
            <?php endif; ?>
            <a class="nb-brand" href="<?php echo BASE_URL; ?>/index.php"><?php echo e(APP_NAME); ?></a>
        </div>

        <?php if (!isLoggedIn()): ?>
            <!-- Public navigation -->
            <div class="nb-collapse" id="nbPublicNav">
                <ul class="nb-nav-links">
                    <li><a class="nb-nav-link" href="<?php echo BASE_URL; ?>/landing/home.php">Home</a></li>
                    <li><a class="nb-nav-link" href="<?php echo BASE_URL; ?>/landing/about.php">About</a></li>
                    <li><a class="nb-nav-link" href="<?php echo BASE_URL; ?>/landing/services.php">Services</a></li>
                    <li><a class="nb-nav-link" href="<?php echo BASE_URL; ?>/landing/officials.php">Officials</a></li>
                    <li><a class="nb-nav-link" href="<?php echo BASE_URL; ?>/landing/announcements.php">Announcements</a></li>
                    <li><a class="nb-nav-link" href="<?php echo BASE_URL; ?>/landing/events.php">Events</a></li>
                    <li><a class="nb-nav-link" href="<?php echo BASE_URL; ?>/landing/gallery.php">Gallery</a></li>
                    <li><a class="nb-nav-link" href="<?php echo BASE_URL; ?>/landing/contact.php">Contact</a></li>
                </ul>
                <a class="nb-login-link" href="<?php echo BASE_URL; ?>/auth/login.php">
                    <i class="bi bi-box-arrow-in-right" style="margin-right:6px;"></i>Login
                </a>
            </div>

            <!-- Mobile toggler -->
            <button class="nb-toggler" id="nbToggler" aria-label="Toggle navigation">
                <div class="nb-toggler-icon">
                    <span></span><span></span><span></span>
                </div>
            </button>

        <?php else: ?>
            <!-- Right: profile -->
            <div class="nb-right">

                <!-- Profile -->
                <div class="nb-dropdown-wrap">
                    <button class="nb-icon-btn" id="nbProfileBtn" aria-label="Account menu">
                        <i class="bi bi-person-circle" style="font-size:1.25rem;"></i>
                    </button>

                    <div class="nb-dropdown profile-drop" id="nbProfileDrop">
                        <div class="nb-profile-header">
                            <div class="nb-profile-name"><?php echo e($_SESSION['name'] ?? 'User'); ?></div>
                            <div class="nb-profile-role"><?php echo e(getRoleLabel(getCurrentRole())); ?></div>
                        </div>
                        <div class="nb-dd-divider"></div>
                        <?php if (getCurrentRole() === 'resident'): ?>
                            <a class="nb-dd-link" href="<?php echo BASE_URL; ?>/resident/profile.php"><i class="bi bi-person"></i>Profile</a>
                            <a class="nb-dd-link" href="<?php echo BASE_URL; ?>/resident/settings.php"><i class="bi bi-gear"></i>Settings</a>
                        <?php elseif (getCurrentRole() === 'secretary'): ?>
                            <a class="nb-dd-link" href="<?php echo BASE_URL; ?>/resident/profile.php"><i class="bi bi-person"></i>Profile</a>
                        <?php elseif (getCurrentRole() === 'admin'): ?>
                            <a class="nb-dd-link" href="<?php echo BASE_URL; ?>/admin/settings.php"><i class="bi bi-gear"></i>Settings</a>
                        <?php endif; ?>
                        <div class="nb-dd-divider"></div>
                        <a class="nb-dd-link danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</nav>

<!-- Dropdown toggle script -->
<script>
(function() {
    var baseUrl = '<?php echo BASE_URL; ?>';

    /* Generic dropdown toggle */
    function setupDrop(btnId, dropId) {
        var btn = document.getElementById(btnId);
        var drop = document.getElementById(dropId);
        if (!btn || !drop) return;

        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('.nb-dropdown.show').forEach(function(d) {
                if (d !== drop) d.classList.remove('show');
            });
            drop.classList.toggle('show');
        });
    }

    setupDrop('nbProfileBtn', 'nbProfileDrop');

    /* Mobile nav toggler */
    var nbToggler = document.getElementById('nbToggler');
    if (nbToggler) {
        nbToggler.addEventListener('click', function() {
            document.getElementById('nbPublicNav').classList.toggle('open');
        });
    }

    /* Close on outside click */
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.nb-dropdown.show').forEach(function(d) {
            if (!d.contains(e.target) && !d.previousElementSibling.contains(e.target)) {
                d.classList.remove('show');
            }
        });
    });

    /* Close on Escape */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.nb-dropdown.show').forEach(function(d) {
                d.classList.remove('show');
            });
        }
    });
})();
</script>