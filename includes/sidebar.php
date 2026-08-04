<?php require_once __DIR__ . '/../includes/auth.php'; ?>
<?php
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
function navActive(string $href, string $currentPage): string {
    return basename(parse_url($href, PHP_URL_PATH) ?? '') === $currentPage ? ' active' : '';
}

$role = getCurrentRole();

$navGroups = [];

    if ($role === 'admin') {
    $navGroups = array(
        'Overview' => array(
            array('dashboard.php', 'bi-speedometer2', 'Dashboard'),
        ),
        'People & Records' => array(
            array('users.php', 'bi-people', 'Users'),
            array('officials.php', 'bi-person-badge', 'Officials'),
            array('residents.php', 'bi-house-door', 'Residents'),
            array('resident_profiling.php', 'bi-person-vcard', 'Resident Profiling'),
        ),
        'Operations' => array(
            array('projects.php', 'bi-kanban', 'Projects'),
            array('budget.php', 'bi-cash-coin', 'Budget'),
            array('agenda.php', 'bi-calendar-check', 'Agenda'),
            array('applications.php', 'bi-file-earmark-text', 'Applications'),
            array('appointments.php', 'bi-calendar-check', 'Appointments'),
            array('templates.php', 'bi-file-earmarked-ruled', 'Templates'),
            array('../scanner/index.php', 'bi-upc-scan', 'QR Scanner'),
        ),
        'Communication' => array(
            array('announcements.php', 'bi-megaphone', 'Announcements'),
            array('landing_content.php', 'bi-layout-text-window', 'Landing Content'),
        ),
        'Surveillance' => array(
            array('cctv.php', 'bi-camera-video', 'CCTV Live'),
        ),
        'System' => array(
            array('reports.php', 'bi-graph-up', 'Reports'),
            array('logs.php', 'bi-clock-history', 'Logs'),
            array('../scanner/logs.php', 'bi-clock-history', 'Scan Logs'),
            array('../scanner/attendance_sheet.php', 'bi-clipboard-check', 'Attendance'),
            array('backup.php', 'bi-cloud-arrow-down', 'Backup'),
            array('settings.php', 'bi-gear', 'Settings'),
        ),
    );
    $base = BASE_URL . '/admin/';
    } elseif ($role === 'secretary') {
    $navGroups = array(
        'Overview' => array(
            array('dashboard.php', 'bi-speedometer2', 'Dashboard'),
        ),
        'Residents' => array(
            array('residents.php', 'bi-house-door', 'Residents'),
            array('resident_profiling.php', 'bi-person-vcard', 'Resident Profiling'),
            array('qr.php', 'bi-qr-code', 'QR'),
            array('documents.php', 'bi-file-earmark-text', 'Documents'),
            array('requests.php', 'bi-inbox', 'Requests'),
            array('appointments.php', 'bi-calendar-check', 'Appointments'),
        ),
        'Operations' => array(
            array('projects.php', 'bi-kanban', 'Projects'),
            array('budget.php', 'bi-cash-coin', 'Budget'),
            array('agenda.php', 'bi-calendar-check', 'Agenda'),
            array('../scanner/index.php', 'bi-upc-scan', 'QR Scanner'),
            array('../scanner/attendance_sheet.php', 'bi-clipboard-check', 'Attendance'),
        ),
        'Communication' => array(
            array('announcements.php', 'bi-megaphone', 'Announcements'),
        ),
        'System' => array(
            array('reports.php', 'bi-graph-up', 'Reports'),
            array('../scanner/logs.php', 'bi-clock-history', 'Scan Logs'),
            array('settings.php', 'bi-gear', 'Settings'),
        ),
    );
    $base = BASE_URL . '/secretary/';
} else {
    $navGroups = array(
        'Overview' => array(
            array('dashboard.php', 'bi-speedometer2', 'Dashboard'),
        ),
        'My Account' => array(
            array('profile.php', 'bi-person', 'Profile'),
            array('qr.php', 'bi-qr-code', 'QR'),
        ),
        'Services' => array(
            array('requests.php', 'bi-inbox', 'Requests'),
            array('appointments.php', 'bi-calendar-check', 'Appointments'),
        ),
        'Updates' => array(
            array('notifications.php', 'bi-bell', 'Notifications'),
        ),
        'System' => array(
            array('settings.php', 'bi-gear', 'Settings'),
        ),
    );
    $base = BASE_URL . '/resident/';
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   SIDEBAR â€” DESIGN SYSTEM
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.sb-wrap {
    width: 280px;
    height: 100vh;
    position: sticky;
    top: 0;
    left: 0;
    background: rgba(15,23,42,0.6);
    backdrop-filter: blur(30px);
    -webkit-backdrop-filter: blur(30px);
    border-right: 1px solid rgba(255,255,255,0.06);
    display: flex;
    flex-direction: column;
    z-index: 1035;
    overflow: hidden;
}

/* Offcanvas mode (mobile) */
.sb-wrap.offcanvas {
    position: fixed;
    width: 290px;
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    background: rgba(15,23,42,0.97);
    backdrop-filter: blur(40px);
    -webkit-backdrop-filter: blur(40px);
}

.sb-wrap.offcanvas.show {
    transform: translateX(0);
}

/* Backdrop */
.sb-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1034;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sb-backdrop.show {
    display: block;
    opacity: 1;
}

/* â”€â”€ Header â”€â”€ */
.sb-header {
    padding: 20px 22px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    flex-shrink: 0;
}

.sb-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sb-brand {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    font-size: 1.05rem;
    color: #ffffff;
    margin: 0;
    line-height: 1;
}

.sb-close {
    display: none;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1rem;
    padding: 0;
}

.sb-close:hover {
    background: rgba(255,255,255,0.08);
    color: #e2e8f0;
}

@media (max-width: 991.98px) {
    .sb-close { display: flex; }
}

/* Role label */
.sb-role {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.sb-role.admin {
    background: rgba(244,63,94,0.10);
    border: 1px solid rgba(244,63,94,0.20);
    color: #fda4af;
}

.sb-role.secretary {
    background: rgba(245,158,11,0.10);
    border: 1px solid rgba(245,158,11,0.20);
    color: #fcd34d;
}

.sb-role.resident {
    background: rgba(14,165,233,0.10);
    border: 1px solid rgba(14,165,233,0.20);
    color: #7dd3fc;
}

/* â”€â”€ Scrollable nav body â”€â”€ */
.sb-body {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 16px 14px 24px;

    /* Thin scrollbar */
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.08) transparent;
}

.sb-body::-webkit-scrollbar { width: 5px; }
.sb-body::-webkit-scrollbar-track { background: transparent; }
.sb-body::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.08);
    border-radius: 100px;
}
.sb-body::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.14);
}

/* â”€â”€ Section labels â”€â”€ */
.sb-section {
    font-size: 0.68rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding: 16px 10px 6px;
    margin: 0;
}

.sb-section:first-child { padding-top: 4px; }

/* â”€â”€ Navigation links â”€â”€ */
.sb-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 500;
    color: #94a3b8;
    text-decoration: none;
    transition: all 0.2s ease;
    position: relative;
    margin-bottom: 2px;
}

.sb-link:hover {
    color: #e2e8f0;
    background: rgba(255,255,255,0.05);
}

.sb-link i {
    font-size: 1.05rem;
    width: 20px;
    text-align: center;
    flex-shrink: 0;
    transition: color 0.2s ease;
}

/* Active state */
.sb-link.active {
    color: #ffffff;
    background: rgba(139,92,246,0.12);
    font-weight: 600;
}

.sb-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 8px;
    bottom: 8px;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: linear-gradient(180deg, #8b5cf6, #a78bfa);
}

.sb-link.active i {
    color: #a78bfa;
}

/* â”€â”€ Footer â”€â”€ */
.sb-footer {
    padding: 14px 22px;
    border-top: 1px solid rgba(255,255,255,0.05);
    flex-shrink: 0;
}

.sb-footer-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 500;
    color: #fca5a5;
    text-decoration: none;
    transition: all 0.2s ease;
}

.sb-footer-link:hover {
    background: rgba(239,68,68,0.08);
    color: #fecaca;
}

.sb-footer-link i {
    font-size: 1rem;
    width: 20px;
    text-align: center;
}

/* â”€â”€ Hide sidebar on pages without layout â”€â”€ */
.sb-hidden .sb-wrap { display: none; }

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   RESPONSIVE
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
@media (max-width: 991.98px) {
    .sb-wrap:not(.offcanvas) { display: none; }
}

@media (min-width: 992px) {
    .sb-wrap.offcanvas { display: none; }
    .sb-backdrop { display: none !important; }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   LIGHT MODE & SYSTEM THEME
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
html.light .sb-wrap {
    background: rgba(0, 0, 0, 0.05);
    border-right-color: rgba(0, 0, 0, 0.12);
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-wrap {
        background: rgba(0, 0, 0, 0.05);
        border-right-color: rgba(0, 0, 0, 0.12);
    }
}

html.light .sb-wrap.offcanvas {
    background: rgba(255, 255, 255, 0.98);
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-wrap.offcanvas {
        background: rgba(255, 255, 255, 0.98);
    }
}

html.light .sb-brand {
    color: #1e293b;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-brand {
        color: #1e293b;
    }
}

html.light .sb-close {
    color: #64748b;
    background: rgba(0, 0, 0, 0.05);
    border-color: rgba(0, 0, 0, 0.1);
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-close {
        color: #64748b;
        background: rgba(0, 0, 0, 0.05);
        border-color: rgba(0, 0, 0, 0.1);
    }
}

html.light .sb-close:hover {
    color: #334155;
    background: rgba(0, 0, 0, 0.08);
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-close:hover {
        color: #334155;
        background: rgba(0, 0, 0, 0.08);
    }
}

html.light .sb-section {
    color: #94a3b8;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-section {
        color: #94a3b8;
    }
}

html.light .sb-link {
    color: #475569;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-link {
        color: #475569;
    }
}

html.light .sb-link:hover {
    color: #1e293b;
    background: rgba(0, 0, 0, 0.06);
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-link:hover {
        color: #1e293b;
        background: rgba(0, 0, 0, 0.06);
    }
}

html.light .sb-link.active {
    color: #1e293b;
    background: rgba(139, 92, 246, 0.10);
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-link.active {
        color: #1e293b;
        background: rgba(139, 92, 246, 0.10);
    }
}

html.light .sb-footer {
    border-top-color: rgba(0, 0, 0, 0.12);
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-footer {
        border-top-color: rgba(0, 0, 0, 0.12);
    }
}

html.light .sb-footer-link {
    color: #dc3545;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-footer-link {
        color: #dc3545;
    }
}

html.light .sb-footer-link:hover {
    color: #bb2d3b;
    background: rgba(220, 53, 69, 0.06);
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-footer-link:hover {
        color: #bb2d3b;
        background: rgba(220, 53, 69, 0.06);
    }
}

html.light .sb-role.admin {
    background: rgba(220, 53, 69, 0.08);
    border-color: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-role.admin {
        background: rgba(220, 53, 69, 0.08);
        border-color: rgba(220, 53, 69, 0.2);
        color: #dc3545;
    }
}

html.light .sb-role.secretary {
    background: rgba(245, 158, 11, 0.08);
    border-color: rgba(245, 158, 11, 0.2);
    color: #d97706;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-role.secretary {
        background: rgba(245, 158, 11, 0.08);
        border-color: rgba(245, 158, 11, 0.2);
        color: #d97706;
    }
}

html.light .sb-role.resident {
    background: rgba(14, 165, 233, 0.08);
    border-color: rgba(14, 165, 233, 0.2);
    color: #0284c7;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-role.resident {
        background: rgba(14, 165, 233, 0.08);
        border-color: rgba(14, 165, 233, 0.2);
        color: #0284c7;
    }
}

html.light .sb-header {
    border-bottom-color: rgba(0, 0, 0, 0.08);
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-header {
        border-bottom-color: rgba(0, 0, 0, 0.08);
    }
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .sb-body {
        scrollbar-color: rgba(0,0,0,0.1) transparent;
    }
    html:not(.dark) .sb-body::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.1);
    }
    html:not(.dark) .sb-body::-webkit-scrollbar-thumb:hover {
        background: rgba(0,0,0,0.18);
    }
}
</style>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     DESKTOP SIDEBAR (sticky, always visible on lg+)
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="sb-wrap d-none d-lg-flex" id="sbDesktop">
    <div class="sb-header">
        <div class="sb-header-top">
            <h5 class="sb-brand">Navigation</h5>
        </div>
        <div class="sb-role <?php echo e($role); ?>">
            <i class="bi bi-<?php echo $role === 'admin' ? 'shield-lock-fill' : ($role === 'secretary' ? 'person-workspace' : 'person-fill'); ?>"></i>
            <?php echo e(ucfirst($role)); ?>
        </div>
    </div>

    <div class="sb-body">
        <?php foreach ($navGroups as $groupLabel => $links): ?>
            <p class="sb-section"><?php echo e($groupLabel); ?></p>
            <?php foreach ($links as [$file, $icon, $label]):
                $href = $base . $file;
            ?>
                <a class="sb-link<?php echo navActive($href, $currentPage); ?>" href="<?php echo e($href); ?>">
                    <i class="bi <?php echo e($icon); ?>"></i>
                    <span><?php echo e($label); ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <div class="sb-footer">
        <a class="sb-footer-link" href="<?php echo BASE_URL; ?>/auth/logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Sign Out</span>
        </a>
    </div>
</div>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MOBILE SIDEBAR (offcanvas, toggled by navbar)
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="sb-backdrop" id="sbBackdrop"></div>
<div class="sb-wrap offcanvas d-lg-none" id="sbMobile" tabindex="-1">
    <div class="sb-header">
        <div class="sb-header-top">
            <h5 class="sb-brand">Navigation</h5>
            <button class="sb-close" id="sbCloseBtn" aria-label="Close sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="sb-role <?php echo e($role); ?>">
            <i class="bi bi-<?php echo $role === 'admin' ? 'shield-lock-fill' : ($role === 'secretary' ? 'person-workspace' : 'person-fill'); ?>"></i>
            <?php echo e(ucfirst($role)); ?>
        </div>
    </div>

    <div class="sb-body">
        <?php foreach ($navGroups as $groupLabel => $links): ?>
            <p class="sb-section"><?php echo e($groupLabel); ?></p>
            <?php foreach ($links as [$file, $icon, $label]):
                $href = $base . $file;
            ?>
                <a class="sb-link<?php echo navActive($href, $currentPage); ?>" href="<?php echo e($href); ?>">
                    <i class="bi <?php echo e($icon); ?>"></i>
                    <span><?php echo e($label); ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <div class="sb-footer">
        <a class="sb-footer-link" href="<?php echo BASE_URL; ?>/auth/logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Sign Out</span>
        </a>
    </div>
</div>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     SIDEBAR TOGGLE SCRIPT
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<script>
(function() {
    var mobile   = document.getElementById('sbMobile');
    var backdrop = document.getElementById('sbBackdrop');
    var closeBtn = document.getElementById('sbCloseBtn');

    if (!mobile) return;

    function openSidebar() {
        mobile.classList.add('show');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        mobile.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    /* Open: triggered by navbar sidebar-toggle button */
    document.querySelectorAll('[data-bs-toggle="offcanvas"][data-bs-target="#appSidebar"], .nb-sidebar-toggle, .sidebar-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            openSidebar();
        });
    });

    /* Close */
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobile.classList.contains('show')) {
            closeSidebar();
        }
    });

    /* Close on link click (mobile) */
    mobile.querySelectorAll('.sb-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) closeSidebar();
        });
    });
})();
</script>

