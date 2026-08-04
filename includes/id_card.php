<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAuth(['admin', 'secretary']);

if (!defined('BASE_URL')) {
    define('BASE_URL', '/FinalTrace');
}

$residentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$printMode = isset($_GET['print']) && $_GET['print'] === 'true';

if ($residentId <= 0) {
    http_response_code(400);
    echo 'Invalid resident ID';
    exit;
}

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT r.*, pi.citizenship FROM residents r LEFT JOIN personal_information pi ON r.id = pi.resident_id WHERE r.id = ? LIMIT 1');
$stmt->execute([$residentId]);
$resident = $stmt->fetch();

if (!$resident) {
    http_response_code(404);
    echo 'Resident not found';
    exit;
}

$barangayName = getSetting('barangay_name', 'Barangay');
$barangayAddress = getSetting('barangay_address', '');
$barangayLogo = getSetting('barangay_logo', '');

$age = '';
if (!empty($resident['birth_date'])) {
    $age = (new DateTime($resident['birth_date']))->diff(new DateTime('today'))->y;
}

$issueDate = date('M d, Y');
$birthDateFormatted = !empty($resident['birth_date']) ? date('M d, Y', strtotime($resident['birth_date'])) : '';

$typeLabels = [
    'regular' => 'Regular',
    'senior_citizen' => 'Senior Citizen',
    'pwd' => 'PWD',
    '4ps' => '4Ps Beneficiary',
    'indigent' => 'Indigent'
];
$residentType = $resident['resident_type'] ?? 'regular';
$typeLabel = $typeLabels[$residentType] ?? ucfirst($residentType);

$photoPath = 'assets/uploads/photos/' . $residentId . '.jpg';
$photoExists = file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . str_replace('\\', '/', $photoPath));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - <?php echo htmlspecialchars($resident['full_name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ═══════════════════════════════
           RESET & BASE
           ═══════════════════════════════ */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            color: #f0f4f8;
            min-height: 100vh;
        }

        /* ═══════════════════════════════
           ATMOSPHERE (screen only)
           ═══════════════════════════════ */
        .id-screen {
            min-height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        /* Noise */
        .id-screen::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
        }

        /* Grid */
        .id-screen::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.015) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }

        .id-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            z-index: 0;
            animation: idFloat 22s ease-in-out infinite;
        }
        .id-orb.a { width: 400px; height: 400px; background: rgba(139,92,246,0.07); top: -12%; left: -8%; }
        .id-orb.b { width: 300px; height: 300px; background: rgba(16,185,129,0.06); bottom: -10%; right: -6%; animation-delay: -11s; }
        .id-orb.c { width: 220px; height: 220px; background: rgba(14,165,233,0.05); top: 50%; right: 25%; animation-delay: -5s; animation-duration: 26s; }

        @keyframes idFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%      { transform: translate(25px, -15px) scale(1.05); }
            66%      { transform: translate(-15px, 10px) scale(0.95); }
        }

        /* ═══════════════════════════════
           SCREEN CHROME WRAPPER
           ═══════════════════════════════ */
        .id-chrome {
            position: relative;
            z-index: 2;
            max-width: 460px;
            width: 100%;
        }

        .id-chrome-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            padding: 32px;
            transition: border-color 0.3s ease;
        }

        .id-chrome-card:hover { border-color: rgba(255,255,255,0.12); }

        /* Header */
        .id-chrome-hd {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .id-chrome-hd-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .id-chrome-ico {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            background: rgba(139,92,246,0.12);
            border: 1px solid rgba(139,92,246,0.20);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #a78bfa;
        }

        .id-chrome-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
        }

        .id-chrome-sub {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 2px;
        }

        /* Back button */
        .id-btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #94a3b8;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .id-btn-back:hover {
            background: rgba(255,255,255,0.08);
            color: #e2e8f0;
        }

        /* ID card preview container */
        .id-preview-wrap {
            display: flex;
            justify-content: center;
            padding: 24px 0;
        }

        /* Info text */
        .id-chrome-info {
            text-align: center;
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .id-chrome-info p {
            font-size: 0.78rem;
            color: #475569;
            line-height: 1.5;
            margin: 0;
        }

        /* Print button */
        .id-btn-print {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            border: none;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            color: #ffffff;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 0 4px 16px rgba(139,92,246,0.25);
            margin-top: 20px;
        }

        .id-btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(139,92,246,0.35);
        }

        .id-btn-print:active { transform: translateY(0); }

        .id-btn-print i { transition: transform 0.2s ease; }
        .id-btn-print:hover i { transform: translateY(-1px); }

        /* ═══════════════════════════════
           ID CARD (printable — LIGHT theme)
           ═══════════════════════════════ */
        .idc {
            width: 3.375in;
            height: 2.125in;
            padding: 0.13in;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.1in;
            box-shadow: 0 8px 24px rgba(0,0,0,0.25);
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #1e293b;
            position: relative;
        }

        /* Subtle top accent stripe */
        .idc::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #8b5cf6, #10b981);
        }

        /* Header */
        .idc-header {
            display: flex;
            align-items: center;
            gap: 0.08in;
            padding-bottom: 0.06in;
            margin-bottom: 0.07in;
            border-bottom: 1.5px solid #e2e8f0;
            margin-top: 0.02in;
        }

        .idc-logo {
            width: 0.42in;
            height: 0.42in;
            border-radius: 6px;
            object-fit: cover;
            flex-shrink: 0;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.16in;
            font-family: 'Playfair Display', serif;
        }

        .idc-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 6px;
        }

        .idc-title {
            font-size: 0.145in;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.1;
            font-family: 'Playfair Display', serif;
        }

        .idc-subtitle {
            font-size: 0.075in;
            color: #64748b;
            line-height: 1.2;
            margin-top: 1px;
        }

        /* Body */
        .idc-body {
            display: flex;
            gap: 0.1in;
            flex: 1;
            min-height: 0;
        }

        .idc-photo {
            width: 0.72in;
            height: 0.72in;
            border-radius: 6px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #cbd5e1;
            flex-shrink: 0;
        }

        .idc-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .idc-photo i { font-size: 0.38in; }

        .idc-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.015in;
        }

        .idc-name {
            font-size: 0.14in;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 0.025in;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .idc-field {
            font-size: 0.095in;
            line-height: 1.35;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 0.04in;
        }

        .idc-field i {
            font-size: 0.095in;
            color: #8b5cf6;
            flex-shrink: 0;
            width: 0.11in;
            text-align: center;
        }

        .idc-field .v {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Type badge */
        .idc-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.01in 0.06in;
            border-radius: 3px;
            font-size: 0.075in;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .idc-badge-regular       { background: #f1f5f9; color: #475569; }
        .idc-badge-senior_citizen { background: #fef3c7; color: #92400e; }
        .idc-badge-pwd           { background: #dbeafe; color: #1e40af; }
        .idc-badge-4ps           { background: #d1fae5; color: #065f46; }
        .idc-badge-indigent      { background: #ffe4e6; color: #9f1239; }

        /* Footer */
        .idc-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: auto;
            padding-top: 0.04in;
            border-top: 1px solid #f1f5f9;
        }

        .idc-dates {
            font-size: 0.078in;
            color: #94a3b8;
            line-height: 1.35;
        }

        .idc-dates i {
            font-size: 0.075in;
            color: #cbd5e1;
            margin-right: 2px;
        }

        .idc-qr {
            width: 0.52in;
            height: 0.52in;
            flex-shrink: 0;
            border-radius: 4px;
            overflow: hidden;
        }

        .idc-qr img {
            width: 100%;
            height: 100%;
            display: block;
        }

        /* ═══════════════════════════════
           PRINT STYLES
           ═══════════════════════════════ */
        @media print {
            @page {
                size: 3.375in 2.125in;
                margin: 0;
            }

            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }

            .no-print,
            .id-screen::before,
            .id-screen::after,
            .id-orb { display: none !important; }

            .id-screen {
                background: #fff !important;
                padding: 0 !important;
                min-height: auto !important;
            }

            .id-chrome { display: none !important; }

            .id-preview-wrap {
                padding: 0 !important;
                display: block !important;
            }

            .idc {
                margin: 0 !important;
                box-shadow: none !important;
                border: 0.5px solid #ccc !important;
            }

            .idc::before { display: none !important; }
        }

        /* ═══════════════════════════════
           ANIMATION
           ═══════════════════════════════ */
        .id-reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .id-reveal.vis { opacity: 1; transform: translateY(0); }
        .id-reveal.d1 { transition-delay: 0.1s; }
        .id-reveal.d2 { transition-delay: 0.25s; }
        .id-reveal.d3 { transition-delay: 0.4s; }
    </style>
</head>
<body>

<div class="id-screen">
    <div class="id-orb a"></div>
    <div class="id-orb b"></div>
    <div class="id-orb c"></div>

    <!-- ════════ Screen Chrome ════════ -->
    <?php if (!$printMode): ?>
    <div class="id-chrome id-reveal d1 no-print">
        <div class="id-chrome-card">

            <div class="id-chrome-hd">
                <div class="id-chrome-hd-left">
                    <div class="id-chrome-ico">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                    <div>
                        <div class="id-chrome-title">Resident ID Card</div>
                        <div class="id-chrome-sub"><?php echo htmlspecialchars($resident['full_name']); ?></div>
                    </div>
                </div>
                <a href="javascript:history.back()" class="id-btn-back">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <!-- ID Card Preview -->
            <div class="id-preview-wrap id-reveal d2">
                <div class="idc">
                    <div class="idc-header">
                        <?php if (!empty($barangayLogo)): ?>
                            <div class="idc-logo"><img src="<?php echo asset($barangayLogo); ?>" alt="Logo"></div>
                        <?php else: ?>
                            <div class="idc-logo"><?php echo strtoupper(substr($barangayName, 0, 2)); ?></div>
                        <?php endif; ?>
                        <div>
                            <div class="idc-title"><?php echo htmlspecialchars($barangayName); ?></div>
                            <div class="idc-subtitle"><?php echo htmlspecialchars($barangayAddress ?: 'Resident Identification Card'); ?></div>
                        </div>
                    </div>

                    <div class="idc-body">
                        <div class="idc-photo">
                            <?php if ($photoExists): ?>
                                <img src="<?php echo asset($photoPath); ?>" alt="Photo">
                            <?php else: ?>
                                <i class="bi bi-person-fill"></i>
                            <?php endif; ?>
                        </div>
                        <div class="idc-info">
                            <div class="idc-name"><?php echo htmlspecialchars($resident['full_name']); ?></div>
                            <div class="idc-field">
                                <i class="bi bi-upc-scan"></i>
                                <span class="v">ID: <?php echo str_pad($resident['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="idc-field">
                                <i class="bi bi-person"></i>
                                <span class="v"><?php echo htmlspecialchars($resident['sex'] ?? '-'); ?> &middot; Age <?php echo $age ?: '-'; ?></span>
                            </div>
                            <div class="idc-field">
                                <i class="bi bi-geo-alt"></i>
                                <span class="v"><?php echo htmlspecialchars($resident['address'] ?? '-'); ?></span>
                            </div>
                            <div class="idc-field">
                                <i class="bi bi-bookmark-fill"></i>
                                <span class="idc-badge idc-badge-<?php echo htmlspecialchars($residentType); ?>"><?php echo htmlspecialchars($typeLabel); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="idc-footer">
                        <div class="idc-dates">
                            <div><i class="bi bi-calendar3"></i> Issued: <?php echo $issueDate; ?></div>
                            <div><i class="bi bi-cake2"></i> Born: <?php echo $birthDateFormatted ?: '-'; ?></div>
                        </div>
                        <img src="<?php echo BASE_URL; ?>/includes/qr.php?type=resident&id=<?php echo $resident['id']; ?>" alt="QR" class="idc-qr">
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="id-chrome-info id-reveal d3">
                <button class="id-btn-print" onclick="window.print()">
                    <i class="bi bi-printer"></i>
                    <span>Print ID Card</span>
                </button>
                <p style="margin-top: 14px;">Prints one CR80-sized card (3.375&Prime; &times; 2.125&Prime;)</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ════════ Print-only card (hidden on screen when chrome visible) ════════ -->
    <?php if ($printMode): ?>
    <div class="id-preview-wrap" style="padding:0;">
        <div class="idc">
            <div class="idc-header">
                <?php if (!empty($barangayLogo)): ?>
                    <div class="idc-logo"><img src="<?php echo asset($barangayLogo); ?>" alt="Logo"></div>
                <?php else: ?>
                    <div class="idc-logo"><?php echo strtoupper(substr($barangayName, 0, 2)); ?></div>
                <?php endif; ?>
                <div>
                    <div class="idc-title"><?php echo htmlspecialchars($barangayName); ?></div>
                    <div class="idc-subtitle"><?php echo htmlspecialchars($barangayAddress ?: 'Resident Identification Card'); ?></div>
                </div>
            </div>

            <div class="idc-body">
                <div class="idc-photo">
                    <?php if ($photoExists): ?>
                        <img src="<?php echo asset($photoPath); ?>" alt="Photo">
                    <?php else: ?>
                        <i class="bi bi-person-fill"></i>
                    <?php endif; ?>
                </div>
                <div class="idc-info">
                    <div class="idc-name"><?php echo htmlspecialchars($resident['full_name']); ?></div>
                    <div class="idc-field">
                        <i class="bi bi-upc-scan"></i>
                        <span class="v">ID: <?php echo str_pad($resident['id'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="idc-field">
                        <i class="bi bi-person"></i>
                        <span class="v"><?php echo htmlspecialchars($resident['sex'] ?? '-'); ?> &middot; Age <?php echo $age ?: '-'; ?></span>
                    </div>
                    <div class="idc-field">
                        <i class="bi bi-geo-alt"></i>
                        <span class="v"><?php echo htmlspecialchars($resident['address'] ?? '-'); ?></span>
                    </div>
                    <div class="idc-field">
                        <i class="bi bi-bookmark-fill"></i>
                        <span class="idc-badge idc-badge-<?php echo htmlspecialchars($residentType); ?>"><?php echo htmlspecialchars($typeLabel); ?></span>
                    </div>
                </div>
            </div>

            <div class="idc-footer">
                <div class="idc-dates">
                    <div><i class="bi bi-calendar3"></i> Issued: <?php echo $issueDate; ?></div>
                    <div><i class="bi bi-cake2"></i> Born: <?php echo $birthDateFormatted ?: '-'; ?></div>
                </div>
                <img src="<?php echo BASE_URL; ?>/includes/qr.php?type=resident&id=<?php echo $resident['id']; ?>" alt="QR" class="idc-qr">
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($printMode): ?>
<script>
    window.addEventListener('load', function() { window.print(); });
</script>
<?php else: ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.id-reveal').forEach(function(el) {
            el.classList.add('vis');
        });
    });
</script>
<?php endif; ?>

</body>
</html>