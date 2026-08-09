<div class="modal fade" id="actionConfirmModal" tabindex="-1" aria-labelledby="actionConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--rq-card); border: 1px solid var(--rq-border); border-radius: var(--rq-rad-lg);">
            <div class="modal-header" style="border-bottom: 1px solid var(--rq-border);">
                <h5 class="modal-title" id="actionConfirmLabel" style="color: #ffffff; font-weight: 700;">Confirm Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color: #e2e8f0; padding: 24px;">
                <p id="confirmMessage" style="margin: 0; line-height: 1.6;"></p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--rq-border); gap: 12px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #e2e8f0;">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmActionBtn" style="background: linear-gradient(135deg, #10b981, #059669); border: none; color: #ffffff; font-weight: 600;">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
