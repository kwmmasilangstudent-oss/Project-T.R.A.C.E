<?php if (!isLoggedIn()): ?>
<footer class="py-4 mt-5 text-center text-muted">
    <div class="container">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo e(APP_NAME); ?>. All rights reserved.</p>
    </div>
</footer>
<?php endif; ?>
<style>
html.light :root {
    --ds-bg: #ffffff;
    --ds-card: rgba(0,0,0,0.02);
    --ds-text: #1e293b;
    --ds-text-sec: #475569;
    --ds-text-muted: #64748b;
    --ds-text-dim: #94a3b8;
    --ds-border: rgba(0,0,0,0.08);
    --ds-border-lt: rgba(0,0,0,0.12);
    --rs-bg: #ffffff;
    --rs-card: rgba(0,0,0,0.02);
    --rs-text: #1e293b;
    --rs-text-sec: #475569;
    --rs-text-muted: #64748b;
    --rs-text-dim: #94a3b8;
    --rs-border: rgba(0,0,0,0.08);
    --rs-border-lt: rgba(0,0,0,0.12);
    --sg-bg: #ffffff;
    --sg-card: rgba(0,0,0,0.02);
    --sg-text: #1e293b;
    --sg-text-sec: #475569;
    --sg-text-muted: #64748b;
    --sg-text-dim: #94a3b8;
    --sg-border: rgba(0,0,0,0.08);
    --sg-border-lt: rgba(0,0,0,0.12);
    --lg-bg: #ffffff;
    --lg-card: rgba(0,0,0,0.02);
    --lg-text: #1e293b;
    --lg-text-sec: #475569;
    --lg-text-muted: #64748b;
    --lg-text-dim: #94a3b8;
    --lg-border: rgba(0,0,0,0.08);
    --lg-border-lt: rgba(0,0,0,0.12);
    --rq-bg: #ffffff;
    --rq-card: rgba(0,0,0,0.02);
    --rq-text: #1e293b;
    --rq-text-sec: #475569;
    --rq-text-muted: #64748b;
    --rq-text-dim: #94a3b8;
    --rq-border: rgba(0,0,0,0.08);
    --rq-border-lt: rgba(0,0,0,0.12);
    --pj-bg: #ffffff;
    --pj-card: rgba(0,0,0,0.02);
    --pj-text: #1e293b;
    --pj-text-sec: #475569;
    --pj-text-muted: #64748b;
    --pj-text-dim: #94a3b8;
    --pj-border: rgba(0,0,0,0.08);
    --pj-border-lt: rgba(0,0,0,0.12);
    --dc-bg: #ffffff;
    --dc-card: rgba(0,0,0,0.02);
    --dc-text: #1e293b;
    --dc-text-sec: #475569;
    --dc-text-muted: #64748b;
    --dc-text-dim: #94a3b8;
    --dc-border: rgba(0,0,0,0.08);
    --dc-border-lt: rgba(0,0,0,0.12);
    --bg-bg: #ffffff;
    --bg-card: rgba(0,0,0,0.02);
    --bg-text: #1e293b;
    --bg-text-sec: #475569;
    --bg-text-muted: #64748b;
    --bg-text-dim: #94a3b8;
    --bg-border: rgba(0,0,0,0.08);
    --bg-border-lt: rgba(0,0,0,0.12);
    --an-bg: #ffffff;
    --an-card: rgba(0,0,0,0.02);
    --an-text: #1e293b;
    --an-text-sec: #475569;
    --an-text-muted: #64748b;
    --an-text-dim: #94a3b8;
    --an-border: rgba(0,0,0,0.08);
    --an-border-lt: rgba(0,0,0,0.12);
    --ap-bg: #ffffff;
    --ap-card: rgba(0,0,0,0.02);
    --ap-text: #1e293b;
    --ap-text-sec: #475569;
    --ap-text-muted: #64748b;
    --ap-text-dim: #94a3b8;
    --ap-border: rgba(0,0,0,0.08);
    --ap-border-lt: rgba(0,0,0,0.12);
    --rp-bg: #ffffff;
    --rp-card: rgba(0,0,0,0.02);
    --rp-text: #1e293b;
    --rp-text-sec: #475569;
    --rp-text-muted: #64748b;
    --rp-text-dim: #94a3b8;
    --rp-border: rgba(0,0,0,0.08);
    --rp-border-lt: rgba(0,0,0,0.12);
    --qr-bg: #ffffff;
    --qr-card: rgba(0,0,0,0.02);
    --qr-text: #1e293b;
    --qr-text-sec: #475569;
    --qr-text-muted: #64748b;
    --qr-text-dim: #94a3b8;
    --qr-border: rgba(0,0,0,0.08);
    --qr-border-lt: rgba(0,0,0,0.12);
    --at-bg: #ffffff;
    --at-card: rgba(0,0,0,0.02);
    --at-text: #1e293b;
    --at-text-sec: #475569;
    --at-text-muted: #64748b;
    --at-text-dim: #94a3b8;
    --at-border: rgba(0,0,0,0.08);
    --at-border-lt: rgba(0,0,0,0.12);
    --cal-bg: #ffffff;
    --cal-card: rgba(0,0,0,0.02);
    --cal-text: #1e293b;
    --cal-text-sec: #475569;
    --cal-text-muted: #64748b;
    --cal-text-dim: #94a3b8;
    --cal-border: rgba(0,0,0,0.08);
    --cal-border-lt: rgba(0,0,0,0.12);
}

@media (prefers-color-scheme: light) {
    html:not(.dark) :root {
        --ds-bg: #ffffff;
        --ds-card: rgba(0,0,0,0.02);
        --ds-text: #1e293b;
        --ds-text-sec: #475569;
        --ds-text-muted: #64748b;
        --ds-text-dim: #94a3b8;
        --ds-border: rgba(0,0,0,0.08);
        --ds-border-lt: rgba(0,0,0,0.12);
        --rs-bg: #ffffff;
        --rs-card: rgba(0,0,0,0.02);
        --rs-text: #1e293b;
        --rs-text-sec: #475569;
        --rs-text-muted: #64748b;
        --rs-text-dim: #94a3b8;
        --rs-border: rgba(0,0,0,0.08);
        --rs-border-lt: rgba(0,0,0,0.12);
        --sg-bg: #ffffff;
        --sg-card: rgba(0,0,0,0.02);
        --sg-text: #1e293b;
        --sg-text-sec: #475569;
        --sg-text-muted: #64748b;
        --sg-text-dim: #94a3b8;
        --sg-border: rgba(0,0,0,0.08);
        --sg-border-lt: rgba(0,0,0,0.12);
        --lg-bg: #ffffff;
        --lg-card: rgba(0,0,0,0.02);
        --lg-text: #1e293b;
        --lg-text-sec: #475569;
        --lg-text-muted: #64748b;
        --lg-text-dim: #94a3b8;
        --lg-border: rgba(0,0,0,0.08);
        --lg-border-lt: rgba(0,0,0,0.12);
        --rq-bg: #ffffff;
        --rq-card: rgba(0,0,0,0.02);
        --rq-text: #1e293b;
        --rq-text-sec: #475569;
        --rq-text-muted: #64748b;
        --rq-text-dim: #94a3b8;
        --rq-border: rgba(0,0,0,0.08);
        --rq-border-lt: rgba(0,0,0,0.12);
        --pj-bg: #ffffff;
        --pj-card: rgba(0,0,0,0.02);
        --pj-text: #1e293b;
        --pj-text-sec: #475569;
        --pj-text-muted: #64748b;
        --pj-text-dim: #94a3b8;
        --pj-border: rgba(0,0,0,0.08);
        --pj-border-lt: rgba(0,0,0,0.12);
        --dc-bg: #ffffff;
        --dc-card: rgba(0,0,0,0.02);
        --dc-text: #1e293b;
        --dc-text-sec: #475569;
        --dc-text-muted: #64748b;
        --dc-text-dim: #94a3b8;
        --dc-border: rgba(0,0,0,0.08);
        --dc-border-lt: rgba(0,0,0,0.12);
        --bg-bg: #ffffff;
        --bg-card: rgba(0,0,0,0.02);
        --bg-text: #1e293b;
        --bg-text-sec: #475569;
        --bg-text-muted: #64748b;
        --bg-text-dim: #94a3b8;
        --bg-border: rgba(0,0,0,0.08);
        --bg-border-lt: rgba(0,0,0,0.12);
        --an-bg: #ffffff;
        --an-card: rgba(0,0,0,0.02);
        --an-text: #1e293b;
        --an-text-sec: #475569;
        --an-text-muted: #64748b;
        --an-text-dim: #94a3b8;
        --an-border: rgba(0,0,0,0.08);
        --an-border-lt: rgba(0,0,0,0.12);
        --ap-bg: #ffffff;
        --ap-card: rgba(0,0,0,0.02);
        --ap-text: #1e293b;
        --ap-text-sec: #475569;
        --ap-text-muted: #64748b;
        --ap-text-dim: #94a3b8;
        --ap-border: rgba(0,0,0,0.08);
        --ap-border-lt: rgba(0,0,0,0.12);
        --rp-bg: #ffffff;
        --rp-card: rgba(0,0,0,0.02);
        --rp-text: #1e293b;
        --rp-text-sec: #475569;
        --rp-text-muted: #64748b;
        --rp-text-dim: #94a3b8;
        --rp-border: rgba(0,0,0,0.08);
        --rp-border-lt: rgba(0,0,0,0.12);
        --qr-bg: #ffffff;
        --qr-card: rgba(0,0,0,0.02);
        --qr-text: #1e293b;
        --qr-text-sec: #475569;
        --qr-text-muted: #64748b;
        --qr-text-dim: #94a3b8;
        --qr-border: rgba(0,0,0,0.08);
        --qr-border-lt: rgba(0,0,0,0.12);
        --at-bg: #ffffff;
        --at-card: rgba(0,0,0,0.02);
        --at-text: #1e293b;
        --at-text-sec: #475569;
        --at-text-muted: #64748b;
        --at-text-dim: #94a3b8;
        --at-border: rgba(0,0,0,0.08);
        --at-border-lt: rgba(0,0,0,0.12);
        --cal-bg: #ffffff;
        --cal-card: rgba(0,0,0,0.02);
        --cal-text: #1e293b;
        --cal-text-sec: #475569;
        --cal-text-muted: #64748b;
        --cal-text-dim: #94a3b8;
        --cal-border: rgba(0,0,0,0.08);
        --cal-border-lt: rgba(0,0,0,0.12);
        --db-bg: #ffffff;
        --db-card: rgba(0,0,0,0.02);
        --db-text: #1e293b;
        --db-text-sec: #475569;
        --db-text-muted: #64748b;
        --db-text-dim: #94a3b8;
        --db-border: rgba(0,0,0,0.08);
        --db-border-lt: rgba(0,0,0,0.12);
        --st-bg: #ffffff;
        --st-card: rgba(0,0,0,0.02);
        --st-text: #1e293b;
        --st-text-sec: #475569;
        --st-text-muted: #64748b;
        --st-text-dim: #94a3b8;
        --st-border: rgba(0,0,0,0.08);
        --st-border-lt: rgba(0,0,0,0.12);
        --nf-bg: #ffffff;
        --nf-card: rgba(0,0,0,0.02);
        --nf-text: #1e293b;
        --nf-text-sec: #475569;
        --nf-text-muted: #64748b;
        --nf-text-dim: #94a3b8;
        --nf-border: rgba(0,0,0,0.08);
        --nf-border-lt: rgba(0,0,0,0.12);
        --pf-bg: #ffffff;
        --pf-card: rgba(0,0,0,0.02);
        --pf-text: #1e293b;
        --pf-text-sec: #475569;
        --pf-text-muted: #64748b;
        --pf-text-dim: #94a3b8;
        --pf-border: rgba(0,0,0,0.08);
        --pf-border-lt: rgba(0,0,0,0.12);
        --sr-bg: #ffffff;
        --sr-card: rgba(0,0,0,0.02);
        --sr-text: #1e293b;
        --sr-text-sec: #475569;
        --sr-text-muted: #64748b;
        --sr-text-dim: #94a3b8;
        --sr-border: rgba(0,0,0,0.08);
        --sr-border-lt: rgba(0,0,0,0.12);
    }
}

html.light .ds-page,
html.light .rs-page,
html.light .sg-page,
html.light .lg-page,
html.light .rq-page,
html.light .pj-page,
html.light .dc-page,
html.light .bg-page,
html.light .an-page,
html.light .ap-page,
html.light .rp-page,
html.light .qr-page,
html.light .at-page,
html.light .cal-page,
html.light .db-page-wrapper,
html.light .st-page-wrapper,
html.light .nf-page-wrapper,
html.light .pf-page-wrapper,
html.light .sr-page-wrapper {
    background: var(--ds-bg, #ffffff) !important;
    color: var(--ds-text, #1e293b) !important;
}

html.light body {
    background: var(--ds-bg, #ffffff) !important;
    color: var(--ds-text, #1e293b) !important;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .ds-page,
    html:not(.dark) .rs-page,
    html:not(.dark) .sg-page,
    html:not(.dark) .lg-page,
    html:not(.dark) .rq-page,
    html:not(.dark) .pj-page,
    html:not(.dark) .dc-page,
    html:not(.dark) .bg-page,
    html:not(.dark) .an-page,
    html:not(.dark) .ap-page,
    html:not(.dark) .rp-page,
    html:not(.dark) .qr-page,
    html:not(.dark) .at-page,
    html:not(.dark) .cal-page,
    html:not(.dark) .db-page-wrapper,
    html:not(.dark) .st-page-wrapper,
    html:not(.dark) .nf-page-wrapper,
    html:not(.dark) .pf-page-wrapper,
    html:not(.dark) .sr-page-wrapper {
        background: var(--ds-bg, #ffffff) !important;
        color: var(--ds-text, #1e293b) !important;
    }
    html:not(.dark) body {
        background: var(--ds-bg, #ffffff) !important;
        color: var(--ds-text, #1e293b) !important;
    }
}

html.light .ds-title,
html.light .ds-card-tt,
html.light .ds-stat-value,
html.light .ds-list-name,
html.light .ds-greeting strong,
html.light .rs-title,
html.light .rs-card-tt,
html.light .rs-resident-name,
html.light .rs-desc,
html.light .sg-title,
html.light .sg-card-tt,
html.light .rq-title,
html.light .rq-card-tt,
html.light .pj-title,
html.light .pj-card-tt,
html.light .dc-title,
html.light .dc-card-tt,
html.light .bg-title,
html.light .bg-card-tt,
html.light .an-title,
html.light .an-card-tt,
html.light .rp-title,
html.light .rp-card-tt,
html.light .ap-title,
html.light .ap-card-tt,
html.light .qr-title,
html.light .qr-card-tt,
html.light .at-title,
html.light .at-card-tt,
html.light .cal-title,
html.light .cal-card-tt,
html.light .lg-title,
html.light .lg-card-tt,
html.light .db-page-title,
html.light .db-card-tt,
html.light .db-stat-value,
html.light .db-list-name,
html.light .db-greeting strong,
html.light .st-page-title,
html.light .st-card-tt,
html.light .nf-page-title,
html.light .nf-card-tt,
html.light .pf-page-title,
html.light .pf-card-tt,
html.light .sr-page-title,
html.light .sr-card-tt {
    color: #1e293b !important;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .ds-title,
    html:not(.dark) .ds-card-tt,
    html:not(.dark) .ds-stat-value,
    html:not(.dark) .ds-list-name,
    html:not(.dark) .ds-greeting strong,
    html:not(.dark) .rs-title,
    html:not(.dark) .rs-card-tt,
    html:not(.dark) .rs-resident-name,
    html:not(.dark) .rs-desc,
    html:not(.dark) .sg-title,
    html:not(.dark) .sg-card-tt,
    html:not(.dark) .rq-title,
    html:not(.dark) .rq-card-tt,
    html:not(.dark) .pj-title,
    html:not(.dark) .pj-card-tt,
    html:not(.dark) .dc-title,
    html:not(.dark) .dc-card-tt,
    html:not(.dark) .bg-title,
    html:not(.dark) .bg-card-tt,
    html:not(.dark) .an-title,
    html:not(.dark) .an-card-tt,
    html:not(.dark) .rp-title,
    html:not(.dark) .rp-card-tt,
    html:not(.dark) .ap-title,
    html:not(.dark) .ap-card-tt,
    html:not(.dark) .qr-title,
    html:not(.dark) .qr-card-tt,
    html:not(.dark) .at-title,
    html:not(.dark) .at-card-tt,
    html:not(.dark) .cal-title,
    html:not(.dark) .cal-card-tt,
    html:not(.dark) .lg-title,
    html:not(.dark) .lg-card-tt,
    html:not(.dark) .db-page-title,
    html:not(.dark) .db-card-tt,
    html:not(.dark) .db-stat-value,
    html:not(.dark) .db-list-name,
    html:not(.dark) .db-greeting strong,
    html:not(.dark) .st-page-title,
    html:not(.dark) .st-card-tt,
    html:not(.dark) .nf-page-title,
    html:not(.dark) .nf-card-tt,
    html:not(.dark) .pf-page-title,
    html:not(.dark) .pf-card-tt,
    html:not(.dark) .sr-page-title,
    html:not(.dark) .sr-card-tt {
        color: #1e293b !important;
    }
}

html.light .ds-card,
html.light .rs-card,
html.light .sg-card,
html.light .rq-card,
html.light .pj-card,
html.light .dc-card,
html.light .bg-card,
html.light .an-card,
html.light .ap-card,
html.light .rp-card,
html.light .qr-card,
html.light .at-card,
html.light .lg-card,
html.light .db-card,
html.light .st-card,
html.light .nf-card,
html.light .pf-card,
html.light .sr-card,
html.light .glass-card,
html.light .delete-toast-card,
html.light .modal-content {
    background: var(--ds-card, rgba(0,0,0,0.02)) !important;
    border-color: var(--ds-border, rgba(0,0,0,0.08)) !important;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .ds-card,
    html:not(.dark) .rs-card,
    html:not(.dark) .sg-card,
    html:not(.dark) .rq-card,
    html:not(.dark) .pj-card,
    html:not(.dark) .dc-card,
    html:not(.dark) .bg-card,
    html:not(.dark) .an-card,
    html:not(.dark) .ap-card,
    html:not(.dark) .rp-card,
    html:not(.dark) .qr-card,
    html:not(.dark) .at-card,
    html:not(.dark) .lg-card,
    html:not(.dark) .glass-card,
    html:not(.dark) .delete-toast-card,
    html:not(.dark) .modal-content {
        background: var(--ds-card, rgba(0,0,0,0.02)) !important;
        border-color: var(--ds-border, rgba(0,0,0,0.08)) !important;
    }
}

html.light .ds-input,
html.light .ds-select,
html.light .rs-input,
html.light .rs-select,
html.light .sg-input,
html.light .sg-select,
html.light .rq-input,
html.light .rq-select,
html.light .pj-input,
html.light .pj-select,
html.light .dc-input,
html.light .dc-select,
html.light .bg-input,
html.light .bg-select,
html.light .an-input,
html.light .an-select,
html.light .ap-input,
html.light .ap-select,
html.light .rp-input,
html.light .rp-select,
html.light .qr-input,
html.light .qr-select,
html.light .at-input,
html.light .at-select,
html.light .cal-input,
html.light .cal-select,
html.light .lg-input,
html.light .lg-select,
html.light .db-input,
html.light .db-select,
html.light .st-input,
html.light .st-select,
html.light .nf-input,
html.light .nf-select,
html.light .pf-input,
html.light .pf-select,
html.light .sr-input,
html.light .sr-select,
html.light .form-control,
html.light .form-select {
    background: rgba(0,0,0,0.04) !important;
    border-color: rgba(0,0,0,0.12) !important;
    color: #1e293b !important;
}

html.light .ds-input::placeholder,
html.light .rs-input::placeholder,
html.light .sg-input::placeholder,
html.light .rq-input::placeholder,
html.light .pj-input::placeholder,
html.light .dc-input::placeholder,
html.light .bg-input::placeholder,
html.light .an-input::placeholder,
html.light .ap-input::placeholder,
html.light .rp-input::placeholder,
html.light .qr-input::placeholder,
html.light .at-input::placeholder,
html.light .cal-input::placeholder,
html.light .lg-input::placeholder,
html.light .db-input::placeholder,
html.light .st-input::placeholder,
html.light .nf-input::placeholder,
html.light .pf-input::placeholder,
html.light .sr-input::placeholder {
    color: rgba(0,0,0,0.4) !important;
}

html.light .ds-input:focus,
html.light .ds-select:focus,
html.light .rs-input:focus,
html.light .rs-select:focus,
html.light .sg-input:focus,
html.light .sg-select:focus,
html.light .rq-input:focus,
html.light .rq-select:focus,
html.light .pj-input:focus,
html.light .pj-select:focus,
html.light .dc-input:focus,
html.light .dc-select:focus,
html.light .bg-input:focus,
html.light .bg-select:focus,
html.light .an-input:focus,
html.light .an-select:focus,
html.light .ap-input:focus,
html.light .ap-select:focus,
html.light .rp-input:focus,
html.light .rp-select:focus,
html.light .qr-input:focus,
html.light .qr-select:focus,
html.light .at-input:focus,
html.light .at-select:focus,
html.light .cal-input:focus,
html.light .cal-select:focus,
html.light .lg-input:focus,
html.light .lg-select:focus,
html.light .db-input:focus,
html.light .db-select:focus,
html.light .st-input:focus,
html.light .st-select:focus,
html.light .nf-input:focus,
html.light .nf-select:focus,
html.light .pf-input:focus,
html.light .pf-select:focus,
html.light .sr-input:focus,
html.light .sr-select:focus,
html.light .form-control:focus,
html.light .form-select:focus {
    background: rgba(0,0,0,0.06) !important;
    border-color: #8b5cf6 !important;
    box-shadow: 0 0 0 3px rgba(139,92,246,0.12) !important;
    color: #1e293b !important;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .ds-input,
    html:not(.dark) .ds-select,
    html:not(.dark) .rs-input,
    html:not(.dark) .rs-select,
    html:not(.dark) .sg-input,
    html:not(.dark) .sg-select,
    html:not(.dark) .rq-input,
    html:not(.dark) .rq-select,
    html:not(.dark) .pj-input,
    html:not(.dark) .pj-select,
    html:not(.dark) .dc-input,
    html:not(.dark) .dc-select,
    html:not(.dark) .bg-input,
    html:not(.dark) .bg-select,
    html:not(.dark) .an-input,
    html:not(.dark) .an-select,
    html:not(.dark) .ap-input,
    html:not(.dark) .ap-select,
    html:not(.dark) .rp-input,
    html:not(.dark) .rp-select,
    html:not(.dark) .qr-input,
    html:not(.dark) .qr-select,
    html:not(.dark) .at-input,
    html:not(.dark) .at-select,
    html:not(.dark) .cal-input,
    html:not(.dark) .cal-select,
    html:not(.dark) .lg-input,
    html:not(.dark) .lg-select,
    html:not(.dark) .form-control,
    html:not(.dark) .form-select {
        background: rgba(0,0,0,0.04) !important;
        border-color: rgba(0,0,0,0.12) !important;
        color: #1e293b !important;
    }
    html:not(.dark) .ds-input::placeholder,
    html:not(.dark) .rs-input::placeholder,
    html:not(.dark) .sg-input::placeholder,
    html:not(.dark) .rq-input::placeholder,
    html:not(.dark) .pj-input::placeholder,
    html:not(.dark) .dc-input::placeholder,
    html:not(.dark) .bg-input::placeholder,
    html:not(.dark) .an-input::placeholder,
    html:not(.dark) .ap-input::placeholder,
    html:not(.dark) .rp-input::placeholder,
    html:not(.dark) .qr-input::placeholder,
    html:not(.dark) .at-input::placeholder,
    html:not(.dark) .cal-input::placeholder,
    html:not(.dark) .lg-input::placeholder,
    html:not(.dark) .db-input::placeholder,
    html:not(.dark) .st-input::placeholder,
    html:not(.dark) .nf-input::placeholder,
    html:not(.dark) .pf-input::placeholder,
    html:not(.dark) .sr-input::placeholder {
        color: rgba(0,0,0,0.4) !important;
    }
    html:not(.dark) .ds-input:focus,
    html:not(.dark) .ds-select:focus,
    html:not(.dark) .rs-input:focus,
    html:not(.dark) .rs-select:focus,
    html:not(.dark) .sg-input:focus,
    html:not(.dark) .sg-select:focus,
    html:not(.dark) .rq-input:focus,
    html:not(.dark) .rq-select:focus,
    html:not(.dark) .pj-input:focus,
    html:not(.dark) .pj-select:focus,
    html:not(.dark) .dc-input:focus,
    html:not(.dark) .dc-select:focus,
    html:not(.dark) .bg-input:focus,
    html:not(.dark) .bg-select:focus,
    html:not(.dark) .an-input:focus,
    html:not(.dark) .an-select:focus,
    html:not(.dark) .ap-input:focus,
    html:not(.dark) .ap-select:focus,
    html:not(.dark) .rp-input:focus,
    html:not(.dark) .rp-select:focus,
    html:not(.dark) .qr-input:focus,
    html:not(.dark) .qr-select:focus,
    html:not(.dark) .at-input:focus,
    html:not(.dark) .at-select:focus,
    html:not(.dark) .cal-input:focus,
    html:not(.dark) .cal-select:focus,
    html:not(.dark) .lg-input:focus,
    html:not(.dark) .lg-select:focus,
    html:not(.dark) .db-input:focus,
    html:not(.dark) .db-select:focus,
    html:not(.dark) .st-input:focus,
    html:not(.dark) .st-select:focus,
    html:not(.dark) .nf-input:focus,
    html:not(.dark) .nf-select:focus,
    html:not(.dark) .pf-input:focus,
    html:not(.dark) .pf-select:focus,
    html:not(.dark) .sr-input:focus,
    html:not(.dark) .sr-select:focus,
    html:not(.dark) .form-control:focus,
    html:not(.dark) .form-select:focus {
        background: rgba(0,0,0,0.06) !important;
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 0 3px rgba(139,92,246,0.12) !important;
        color: #1e293b !important;
    }
}

html.light .ds-select option,
html.light .rs-select option,
html.light .sg-select option,
html.light .rq-select option,
html.light .pj-select option,
html.light .dc-select option,
html.light .bg-select option,
html.light .an-select option,
html.light .ap-select option,
html.light .rp-select option,
html.light .qr-select option,
html.light .at-select option,
html.light .cal-select option,
html.light .lg-select option,
html.light .db-select option,
html.light .st-select option,
html.light .nf-select option,
html.light .pf-select option,
html.light .sr-select option {
    background: #f8f9fa !important;
    color: #212529 !important;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .ds-select option,
    html:not(.dark) .rs-select option,
    html:not(.dark) .sg-select option,
    html:not(.dark) .rq-select option,
    html:not(.dark) .pj-select option,
    html:not(.dark) .dc-select option,
    html:not(.dark) .bg-select option,
    html:not(.dark) .an-select option,
    html:not(.dark) .ap-select option,
    html:not(.dark) .rp-select option,
    html:not(.dark) .qr-select option,
    html:not(.dark) .at-select option,
    html:not(.dark) .cal-select option,
    html:not(.dark) .lg-select option,
    html:not(.dark) .db-select option,
    html:not(.dark) .st-select option,
    html:not(.dark) .nf-select option,
    html:not(.dark) .pf-select option,
    html:not(.dark) .sr-select option {
        background: #f8f9fa !important;
        color: #212529 !important;
    }
}

html.light .table,
html.light .table thead th,
html.light .table tbody td,
html.light .db-table,
html.light .db-table thead th,
html.light .db-table tbody td,
html.light .st-table,
html.light .st-table thead th,
html.light .st-table tbody td,
html.light .nf-table,
html.light .nf-table thead th,
html.light .nf-table tbody td,
html.light .pf-table,
html.light .pf-table thead th,
html.light .pf-table tbody td,
html.light .sr-table,
html.light .sr-table thead th,
html.light .sr-table tbody td {
    color: #1e293b !important;
    border-color: rgba(0,0,0,0.08) !important;
}

html.light .table thead th,
html.light .db-table thead th,
html.light .st-table thead th,
html.light .nf-table thead th,
html.light .pf-table thead th,
html.light .sr-table thead th {
    color: #475569 !important;
}

html.light .table tbody tr:hover,
html.light .db-table tbody tr:hover,
html.light .st-table tbody tr:hover,
html.light .nf-table tbody tr:hover,
html.light .pf-table tbody tr:hover,
html.light .sr-table tbody tr:hover {
    background: rgba(0,0,0,0.03) !important;
}

@media (prefers-color-scheme: light) {
    html:not(.dark) .table,
    html:not(.dark) .db-table,
    html:not(.dark) .st-table,
    html:not(.dark) .nf-table,
    html:not(.dark) .pf-table,
    html:not(.dark) .sr-table { color: #1e293b !important; border-color: rgba(0,0,0,0.08) !important; }
    html:not(.dark) .table thead th,
    html:not(.dark) .db-table thead th,
    html:not(.dark) .st-table thead th,
    html:not(.dark) .nf-table thead th,
    html:not(.dark) .pf-table thead th,
    html:not(.dark) .sr-table thead th { color: #475569 !important; border-bottom-color: rgba(0,0,0,0.08) !important; }
    html:not(.dark) .table tbody td,
    html:not(.dark) .db-table tbody td,
    html:not(.dark) .st-table tbody td,
    html:not(.dark) .nf-table tbody td,
    html:not(.dark) .pf-table tbody td,
    html:not(.dark) .sr-table tbody td { border-bottom-color: rgba(0,0,0,0.08) !important; }
    html:not(.dark) .table tbody tr:hover,
    html:not(.dark) .db-table tbody tr:hover,
    html:not(.dark) .st-table tbody tr:hover,
    html:not(.dark) .nf-table tbody tr:hover,
    html:not(.dark) .pf-table tbody tr:hover,
    html:not(.dark) .sr-table tbody tr:hover { background: rgba(0,0,0,0.03) !important; }
}

html.light .modal-content {
    background: rgba(255,255,255,0.98) !important;
    border-color: rgba(0,0,0,0.12) !important;
}

html.light .modal-header,
html.light .modal-footer {
    border-color: rgba(0,0,0,0.08) !important;
}

html.light .alert-success {
    background: rgba(16,185,129,0.08) !important;
    border-color: rgba(16,185,129,0.2) !important;
    color: #059669 !important;
}

html.light .alert-danger {
    background: rgba(239,68,68,0.08) !important;
    border-color: rgba(239,68,68,0.2) !important;
    color: #dc2626 !important;
}

html.light .db-stat-pill,
html.light .db-action-card,
html.light .db-section-header,
html.light .st-card,
html.light .nf-card,
html.light .pf-card,
html.light .sr-card {
    background: var(--ds-card, rgba(0,0,0,0.02)) !important;
    border-color: var(--ds-border, rgba(0,0,0,0.08)) !important;
}

html.light .db-stat-pill:hover,
html.light .db-action-card:hover {
    background: rgba(0,0,0,0.04) !important;
    border-color: rgba(0,0,0,0.14) !important;
}

html.light .db-section-title,
html.light .db-card-title,
html.light .db-ann-title,
html.light .db-notif-text,
html.light .db-pill-value,
html.light .db-action-title,
html.light .st-page-title,
html.light .st-card-title,
html.light .nf-page-title,
html.light .nf-card-title,
html.light .pf-page-title,
html.light .pf-card-title,
html.light .sr-page-title,
html.light .sr-card-title {
    color: #1e293b !important;
}

html.light .db-section-sub,
html.light .db-card-subtitle,
html.light .db-ann-meta,
html.light .db-notif-time,
html.light .db-pill-label,
html.light .db-action-sub,
html.light .st-page-desc,
html.light .st-card-subtitle,
html.light .nf-page-desc,
html.light .nf-card-subtitle,
html.light .pf-page-desc,
html.light .pf-card-subtitle,
html.light .sr-page-desc,
html.light .sr-card-subtitle {
    color: #475569 !important;
}

html.light a { color: #8b5cf6 !important; }
html.light a:hover { color: #7c3aed !important; }

@media (prefers-color-scheme: light) {
    html:not(.dark) .modal-content { background: rgba(255,255,255,0.98) !important; border-color: rgba(0,0,0,0.12) !important; }
    html:not(.dark) .modal-header, html:not(.dark) .modal-footer { border-color: rgba(0,0,0,0.08) !important; }
    html:not(.dark) .alert-success { background: rgba(16,185,129,0.08) !important; border-color: rgba(16,185,129,0.2) !important; color: #059669 !important; }
    html:not(.dark) .alert-danger { background: rgba(239,68,68,0.08) !important; border-color: rgba(239,68,68,0.2) !important; color: #dc2626 !important; }
    html:not(.dark) .db-stat-pill,
    html:not(.dark) .db-action-card,
    html:not(.dark) .db-section-header,
    html:not(.dark) .st-card,
    html:not(.dark) .nf-card,
    html:not(.dark) .pf-card,
    html:not(.dark) .sr-card { background: var(--ds-card, rgba(0,0,0,0.02)) !important; border-color: var(--ds-border, rgba(0,0,0,0.08)) !important; }
    html:not(.dark) .db-section-title,
    html:not(.dark) .db-card-title,
    html:not(.dark) .db-ann-title,
    html:not(.dark) .db-notif-text,
    html:not(.dark) .db-pill-value,
    html:not(.dark) .db-action-title,
    html:not(.dark) .st-page-title,
    html:not(.dark) .st-card-title,
    html:not(.dark) .nf-page-title,
    html:not(.dark) .nf-card-title,
    html:not(.dark) .pf-page-title,
    html:not(.dark) .pf-card-title,
    html:not(.dark) .sr-page-title,
    html:not(.dark) .sr-card-title { color: #1e293b !important; }
    html:not(.dark) .db-section-sub,
    html:not(.dark) .db-card-subtitle,
    html:not(.dark) .db-ann-meta,
    html:not(.dark) .db-notif-time,
    html:not(.dark) .db-pill-label,
    html:not(.dark) .db-action-sub,
    html:not(.dark) .st-page-desc,
    html:not(.dark) .st-card-subtitle,
    html:not(.dark) .nf-page-desc,
    html:not(.dark) .nf-card-subtitle,
    html:not(.dark) .pf-page-desc,
    html:not(.dark) .pf-card-subtitle,
    html:not(.dark) .sr-page-desc,
    html:not(.dark) .sr-card-subtitle { color: #475569 !important; }
    html:not(.dark) a { color: #8b5cf6 !important; }
    html:not(.dark) a:hover { color: #7c3aed !important; }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo asset('js/app.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function() {
            var btns = this.querySelectorAll('button[type="submit"]');
            btns.forEach(function(btn) {
                btn.disabled = true;
                btn.dataset.origHtml = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (btn.textContent.trim() || 'Saving...');
            });
        });
    });
});
</script>
</body>
</html>
