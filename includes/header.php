<?php
$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? 'A transparent and resilient barangay management system for every resident.';
$pageOgImage = $pageOgImage ?? asset('img/og-default.png');
$pageOgType = $pageOgType ?? 'website';

$themeClass = 'light';
if (isLoggedIn()) {
    $themeClass = 'system';
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT theme_preference FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $theme = $stmt->fetchColumn();
        if ($theme) {
            $themeClass = $theme;
        }
    } catch (Throwable $e) {}
} else {
    $themeClass = getSetting('theme', 'light');
}
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo e($themeClass); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageDescription); ?>">
    <meta property="og:title" content="<?php echo e($pageTitle); ?>">
    <meta property="og:description" content="<?php echo e($pageDescription); ?>">
    <meta property="og:type" content="<?php echo e($pageOgType); ?>">
    <meta property="og:url" content="<?php echo e((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
    <?php if (!empty($pageOgImage)): ?>
    <meta property="og:image" content="<?php echo e($pageOgImage); ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo e($pageDescription); ?>">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "GovernmentOrganization",
        "name": "<?php echo e(APP_NAME); ?>",
        "description": "<?php echo e($pageDescription); ?>"
    }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo asset('css/style.css'); ?>" rel="stylesheet">
</head>
<body>
<script>
    (function() {
        var theme = document.documentElement.className;
        if (!theme || theme === 'undefined') {
            var isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.remove('light', 'dark');
            document.documentElement.classList.add(isDark ? 'dark' : 'light');
        }
    })();
</script>