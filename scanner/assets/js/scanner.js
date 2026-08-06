/* ============================================================
   Project T.R.A.C.E. — QR Scanner Module (Frontend)
   Handles camera scanning, AJAX lookup with autocomplete, feedback.
   ============================================================ */
(function() {
    'use strict';

    var CSRF_TOKEN = window.SCANNER_CFG ? SCANNER_CFG.csrf : '';
    var SCAN_API = window.SCANNER_CFG ? SCANNER_CFG.scanApi : 'api/scan_handler.php';
    var LOOKUP_API = window.SCANNER_CFG ? SCANNER_CFG.lookupApi : 'api/resident_lookup.php';
    var SEARCH_API = window.SCANNER_CFG ? SCANNER_CFG.searchApi : 'api/resident_search.php';

    var html5Qrcode = null;
    var running = false;
    var rendered = false;
    var currentCameraId = null;
    var cameras = [];
    var facingMode = 'environment';
    var sensitivityHigh = false;
    var torchOn = false;
    var batchMode = false;
    var lastScannedCode = '';
    var lastScanTime = 0;
    var consecutiveFails = 0;
    var processing = false;
    var recentScans = [];
    var autocompleteTimer = null;
    var activeAutocompleteIndex = -1;

    var el = {};

    document.addEventListener('DOMContentLoaded', function() {
        cacheEls();
        bindEvents();
        generateCsrf();
        loadEvents();
        loadQuickStats();
        bootScanner();
    });

    function cacheEls() {
        el.reader = document.getElementById('reader');
        el.frame = document.getElementById('scFrame');
        el.status = document.getElementById('scStatus');
        el.btnStart = document.getElementById('btnStart');
        el.btnStop = document.getElementById('btnStop');
        el.btnSwitch = document.getElementById('btnSwitch');
        el.btnTorch = document.getElementById('btnTorch');
        el.btnSens = document.getElementById('btnSens');
        el.btnBatch = document.getElementById('btnBatch');
        el.btnLookupToggle = document.getElementById('btnLookupToggle');
        el.lookupPanel = document.getElementById('lookupPanel');
        el.lookupInput = document.getElementById('lookupInput');
        el.btnLookupSubmit = document.getElementById('btnLookupSubmit');
        el.lookupResults = document.getElementById('lookupResults');
        el.autocompleteList = document.getElementById('autocompleteList');
        el.permission = document.getElementById('scPermission');
        el.camSelect = document.getElementById('camSelect');
        el.eventSelect = document.getElementById('eventSelect');
        el.eventCount = document.getElementById('eventCount');
        el.stats = {
            total: document.getElementById('statTotal'),
            ok: document.getElementById('statOk'),
            notfound: document.getElementById('statNotFound'),
            inactive: document.getElementById('statInactive')
        };
        el.modal = document.getElementById('resultModal');
        el.modalContent = document.getElementById('resultContent');
        el.batchBanner = document.getElementById('batchBanner');
        el.recentToggle = document.getElementById('btnRecent');
        el.recentDrawer = document.getElementById('recentDrawer');
        el.recentBackdrop = document.getElementById('recentBackdrop');
        el.recentList = document.getElementById('recentList');
        el.recentClose = document.getElementById('recentClose');
    }

    function bindEvents() {
        el.btnStart.addEventListener('click', function() { startScanner(); });
        el.btnStop.addEventListener('click', function() { stopScanner(); });
        el.btnSwitch.addEventListener('click', switchCamera);
        el.btnTorch.addEventListener('click', toggleTorch);
        el.btnSens.addEventListener('click', toggleSensitivity);
        el.btnBatch.addEventListener('click', toggleBatch);
        if (el.btnLookupToggle) {
            el.btnLookupToggle.addEventListener('click', function() {
                el.lookupPanel.classList.toggle('open');
                if (el.lookupPanel.classList.contains('open')) setTimeout(function() { el.lookupInput.focus(); }, 50);
            });
        }
        el.btnLookupSubmit.addEventListener('click', runLookup);
        el.lookupInput.addEventListener('input', function() {
            handleAutocompleteInput();
        });
        el.lookupInput.addEventListener('keydown', function(e) {
            handleAutocompleteKeydown(e);
        });
        el.lookupInput.addEventListener('focus', function() {
            if (el.lookupInput.value.trim().length >= 1) {
                handleAutocompleteInput();
            }
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.sc-autocomplete-wrap')) {
                hideAutocomplete();
            }
        });
        if (el.recentToggle) {
            el.recentToggle.addEventListener('click', openRecent);
        }
        el.recentClose.addEventListener('click', closeRecent);
        el.recentBackdrop.addEventListener('click', closeRecent);
        if (el.eventSelect) {
            el.eventSelect.addEventListener('change', function() {
                updateEventCountDisplay();
                var agendaId = getCurrentAgendaId();
                if (agendaId) {
                    var opt = el.eventSelect.querySelector('option[value="' + agendaId + '"]');
                    var title = opt ? opt.textContent : 'Event #' + agendaId;
                    setStatus('ready', 'Event selected: ' + title);
                    loadQuickStats();
                }
            });
        }
    }

    /* ── Autocomplete ── */
    function handleAutocompleteInput() {
        clearTimeout(autocompleteTimer);
        var term = (el.lookupInput.value || '').trim();
        activeAutocompleteIndex = -1;
        if (term.length < 1) {
            hideAutocomplete();
            return;
        }
        autocompleteTimer = setTimeout(function() {
            fetchAutocomplete(term);
        }, 250);
    }

    function handleAutocompleteKeydown(e) {
        var items = el.autocompleteList.querySelectorAll('.sc-ac-item');
        if (!items.length) {
            if (e.key === 'Enter') {
                e.preventDefault();
                runLookup();
            }
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeAutocompleteIndex = Math.min(activeAutocompleteIndex + 1, items.length - 1);
            highlightAutocompleteItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeAutocompleteIndex = Math.max(activeAutocompleteIndex - 1, 0);
            highlightAutocompleteItem(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeAutocompleteIndex >= 0 && items[activeAutocompleteIndex]) {
                items[activeAutocompleteIndex].click();
            } else {
                runLookup();
            }
        } else if (e.key === 'Escape') {
            hideAutocomplete();
        }
    }

    function highlightAutocompleteItem(items) {
        for (var i = 0; i < items.length; i++) {
            items[i].classList.toggle('active', i === activeAutocompleteIndex);
        }
        if (activeAutocompleteIndex >= 0 && items[activeAutocompleteIndex]) {
            items[activeAutocompleteIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    function fetchAutocomplete(term) {
        fetch(SEARCH_API + '?q=' + encodeURIComponent(term), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (!data.success || !data.residents || !data.residents.length) {
                hideAutocomplete();
                return;
            }
            renderAutocomplete(data.residents, term);
        }).catch(function() {
            hideAutocomplete();
        });
    }

    function renderAutocomplete(residents, term) {
        var html = '';
        var lowerTerm = term.toLowerCase();
        residents.forEach(function(r, idx) {
            var name = escapeHtml(r.full_name || '');
            var highlighted = highlightMatch(name, lowerTerm);
            var subtitle = r.senior_citizen_id ? escapeHtml(r.senior_citizen_id) : '';
            html += '<div class="sc-ac-item" data-rid="' + r.id + '" data-idx="' + idx + '">' +
                '<div class="sc-ac-name">' + highlighted + '</div>' +
                (subtitle ? '<div class="sc-ac-sub"><i class="bi bi-person-vcard"></i> ' + subtitle + '</div>' : '') +
                '</div>';
        });
        el.autocompleteList.innerHTML = html;
        el.autocompleteList.classList.add('show');
        activeAutocompleteIndex = -1;

        var items = el.autocompleteList.querySelectorAll('.sc-ac-item');
        Array.prototype.forEach.call(items, function(item) {
            item.addEventListener('click', function() {
                var rid = item.getAttribute('data-rid');
                selectAutocompleteResident(rid);
            });
        });
    }

    function highlightMatch(text, lowerTerm) {
        var lowerText = text.toLowerCase();
        var pos = lowerText.indexOf(lowerTerm);
        if (pos === -1) return text;
        var before = text.substring(0, pos);
        var match = text.substring(pos, pos + lowerTerm.length);
        var after = text.substring(pos + lowerTerm.length);
        return escapeHtml(before) + '<mark>' + escapeHtml(match) + '</mark>' + escapeHtml(after);
    }

    function selectAutocompleteResident(id) {
        hideAutocomplete();
        el.lookupInput.value = '';
        el.lookupResults.innerHTML = '<p class="sc-help"><i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i> Processing scan...</p>';
        sendScan('resident:' + id);
    }

    function hideAutocomplete() {
        el.autocompleteList.innerHTML = '';
        el.autocompleteList.classList.remove('show');
        activeAutocompleteIndex = -1;
    }

    function generateCsrf() {
        if (CSRF_TOKEN) return;
        try {
            var req = new XMLHttpRequest();
            req.open('GET', 'api/csrf.php', false);
            req.send();
            if (req.status === 200) {
                var data = JSON.parse(req.responseText);
                CSRF_TOKEN = data.csrf_token || '';
            }
        } catch (e) { /* ignore */ }
    }

    function loadEvents() {
        if (!el.eventSelect || !SCANNER_CFG || !SCANNER_CFG.eventsApi) return;
        try {
            fetch(SCANNER_CFG.eventsApi, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success || !Array.isArray(data.events)) return;
                    el.eventSelect.innerHTML = '<option value="">No event selected</option>';
                    data.events.forEach(function(ev) {
                        var opt = document.createElement('option');
                        opt.value = ev.id;
                        var label = ev.title;
                        if (ev.agenda_date) label += ' — ' + ev.agenda_date;
                        if (ev.time_from) label += ' ' + ev.time_from;
                        if (ev.is_scannable) label += ' [QR]';
                        if (ev.expected_attendees > 0) label += ' (' + ev.checkin_count + '/' + ev.expected_attendees + ')';
                        opt.textContent = label;
                        el.eventSelect.appendChild(opt);
                    });
                    autoSelectEvent(data.events);
                })
                .catch(function() { /* ignore */ });
        } catch (e) { /* ignore */ }
    }

    function autoSelectEvent(events) {
        if (!el.eventSelect) return;
        var scannable = events.filter(function(ev) { return ev.is_scannable; });
        var pool = scannable.length ? scannable : events;
        if (pool.length === 1) {
            el.eventSelect.value = pool[0].id;
        }
        updateEventCountDisplay();
    }

    function getCurrentAgendaId() {
        if (!el.eventSelect || !el.eventSelect.value) return null;
        return parseInt(el.eventSelect.value, 10) || null;
    }

    function updateEventCountDisplay() {
        if (!el.eventSelect || !el.eventCount) return;
        var val = el.eventSelect.value;
        if (!val) {
            el.eventCount.style.display = 'none';
            return;
        }
        var opt = el.eventSelect.querySelector('option[value="' + val + '"]');
        var text = opt ? opt.textContent : '';
        var m = text.match(/\((\d+)\/(\d+)\)/);
        if (m) {
            el.eventCount.textContent = m[1] + '/' + m[2];
            el.eventCount.style.display = 'inline-flex';
        } else {
            el.eventCount.style.display = 'none';
        }
    }

    /* ── Scanner lifecycle (Html5QrcodeScanner; matches the loaded library) ── */
    function bootScanner() {
        if (typeof Html5QrcodeScanner === 'undefined') {
            setStatus('error', 'Scanner library failed to load. Use Find by Name.');
            return;
        }
        startScanner();
    }

    function buildConfig() {
        return {
            fps: sensitivityHigh ? 12 : 10,
            qrbox: function(vw, vh) {
                var size = Math.min(vw, vh);
                return { width: Math.floor(size * (sensitivityHigh ? 0.85 : 0.7)), height: Math.floor(size * (sensitivityHigh ? 0.85 : 0.7)) };
            },
            formatsToSupport: [0],
            useBarCodeDetectorIfSupported: false,
            showTorchButtonIfSupported: true,
            showCameraSwitchButtonIfSupported: true,
            defaultZoomValueIfSupported: 1
        };
    }

    function isNoCodeMessage(msg) {
        if (!msg) return false;
        var m = String(msg).toLowerCase();
        return m.indexOf('nomultiformatreaders') !== -1 ||
            m.indexOf('no multiformat readers') !== -1 ||
            m.indexOf('no qr code') !== -1 ||
            m.indexOf('not found') !== -1 ||
            m.indexOf('notfound') !== -1 ||
            m.indexOf('notexception') !== -1 ||
            m.indexOf('qr code parse error') !== -1;
    }

    function startScanner() {
        if (running || rendered) return;
        el.permission.classList.remove('show');
        hideModal();
        setStatus('processing', 'Starting camera...');

        var config = buildConfig();

        try {
            html5Qrcode = new Html5QrcodeScanner('reader', config, false);
        } catch (e) {
            setStatus('error', 'Could not initialise scanner.');
            return;
        }

        html5Qrcode.render(onScanSuccess, function(errMsg) {
            var msg = String(errMsg || '');
            if (isNoCodeMessage(msg)) return;
            if (/permission|denied|NotAllowed|no camera|NotFoundError|in use|NotReadable|OverconstrainedError/i.test(msg)) {
                setStatus('error', 'Could not start camera. ' + msg);
                running = false;
                rendered = false;
                updateControls();
                el.permission.classList.add('show');
            }
        });

        rendered = true;
        running = true;
        updateControls();
        setStatus('ready', 'Ready — Point camera at QR code');
    }

    function stopScanner() {
        if (html5Qrcode) {
            try {
                html5Qrcode.clear().then(function() {
                    html5Qrcode = null;
                    running = false;
                    rendered = false;
                    updateControls();
                    setStatus('ready', 'Scanner stopped. Tap Start to scan again.');
                }).catch(function() {
                    html5Qrcode = null;
                    running = false;
                    rendered = false;
                    updateControls();
                });
                return;
            } catch (e) {
                // fall through
            }
        }
        html5Qrcode = null;
        running = false;
        rendered = false;
        updateControls();
    }

    function restartWith() {
        stopScanner();
        setTimeout(function() { startScanner(); }, 600);
    }

    function switchCamera() {
        facingMode = (facingMode === 'environment') ? 'user' : 'environment';
        restartWith();
    }

    function toggleTorch() {
        var libTorch = document.getElementById('html5-qrcode-button-torch');
        if (libTorch) {
            libTorch.click();
            torchOn = !torchOn;
            el.btnTorch.classList.toggle('active', torchOn);
        } else {
            setStatus('error', 'Torch not supported on this device.');
        }
    }

    function toggleSensitivity() {
        sensitivityHigh = !sensitivityHigh;
        el.btnSens.classList.toggle('active', sensitivityHigh);
        var lbl = el.btnSens.querySelector('span');
        if (lbl) lbl.textContent = sensitivityHigh ? 'High' : 'Normal';
        if (running) restartWith();
    }

    function toggleBatch() {
        batchMode = !batchMode;
        el.btnBatch.classList.toggle('active', batchMode);
        el.batchBanner.classList.toggle('show', batchMode);
    }

    /* ── Scan callbacks ── */
    function onScanSuccess(decodedText) {
        var now = Date.now();
        if (decodedText === lastScannedCode && (now - lastScanTime) < 5000) {
            return;
        }
        lastScannedCode = decodedText;
        lastScanTime = now;

        if (processing) return;
        processing = true;
        setStatus('processing', 'Processing...');
        playBeep();
        vibrate([60, 40, 120]);

        sendScan(decodedText).finally(function() {
            processing = false;
        });
    }

    function onScanFailure() {
        // Silent: frequent during scanning.
    }

    /* ── Lookup (manual search button) ── */
    function runLookup() {
        var term = (el.lookupInput.value || '').trim();
        if (!term) {
            el.lookupResults.innerHTML = '<p class="sc-help">Type a name or Senior Citizen ID.</p>';
            return;
        }
        hideAutocomplete();
        el.lookupResults.innerHTML = '<p class="sc-help"><i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i> Searching...</p>';
        fetchPost(LOOKUP_API, { term: term }).then(function(data) {
            if (!data.success) {
                el.lookupResults.innerHTML = '<p class="sc-help">' + escapeHtml(data.message) + '</p>';
                return;
            }
            if (data.resident) {
                el.lookupResults.innerHTML = '<p class="sc-help"><i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i> Processing scan...</p>';
                sendScan('resident:' + data.resident.id);
                return;
            }
            var html = '<div style="display:flex;flex-direction:column;gap:10px;">';
            (data.residents || []).forEach(function(r) {
                html += '<button class="sc-btn sc-btn-ghost" style="justify-content:space-between;width:100%;" ' +
                    'data-rid="' + r.id + '"><span>' + escapeHtml(r.full_name) +
                    '</span><span style="font-size:14px;color:#64748b;">' +
                    escapeHtml(r.senior_citizen_id || (r.qr_code_identifier || '')) + '</span></button>';
            });
            html += '</div>';
            el.lookupResults.innerHTML = html;
            Array.prototype.forEach.call(el.lookupResults.querySelectorAll('button[data-rid]'), function(b) {
                b.addEventListener('click', function() {
                    var id = b.getAttribute('data-rid');
                    el.lookupResults.innerHTML = '<p class="sc-help"><i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite;"></i> Processing scan...</p>';
                    sendScan('resident:' + id);
                });
            });
        }).catch(function() {
            el.lookupResults.innerHTML = '<p class="sc-help">Lookup failed. Please try again.</p>';
        });
    }

    /* ── AJAX ── */
    function sendScan(code) {
        var agendaId = getCurrentAgendaId();
        return fetchPost(SCAN_API, { qr_code: code, csrf_token: CSRF_TOKEN, agenda_id: agendaId }).then(function(data) {
            if (agendaId && data.agenda_id == agendaId) {
                updateEventCountDisplay();
            }
            handleScanResponse(data, code);
        }).catch(function(err) {
            consecutiveFails++;
            if (err && err.data && typeof err.data === 'object') {
                handleScanResponse(err.data, code);
            } else {
                var msg = (err && err.message) ? err.message : 'Network error. Try again or use Find by Name.';
                setStatus('error', msg);
                playError();
                maybeSuggestSearch();
            }
        });
    }

    function handleScanResponse(data, code) {
        if (data.success) {
            consecutiveFails = 0;
            addRecent(data.resident, data.status, code);
            if (batchMode) {
                showResult(data, true);
                setStatus('success', 'Verified: ' + (data.resident ? data.resident.full_name : 'Resident'));
                loadQuickStats();
                setTimeout(function() {
                    if (running) setStatus('ready', 'Ready — Point camera at QR code');
                }, 1500);
            } else {
                showResult(data, false);
                loadQuickStats();
            }
        } else {
            consecutiveFails++;
            addRecent(null, data.status || 'not_found', code);
            showNotFound(data, code);
            playError();
            loadQuickStats();
            maybeSuggestSearch();
            setStatus('error', data.message || 'Scan failed. Please try again or use Find by Name.');
        }
    }

    function maybeSuggestSearch() {
        if (consecutiveFails >= 3) {
            setStatus('error', 'Scan not working? Tap "Find by Name" below.');
            consecutiveFails = 0;
        }
    }

    function fetchPost(url, body) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    var data = null;
                    try { data = JSON.parse(xhr.responseText); } catch (e) { data = null; }
                    if (xhr.status >= 200 && xhr.status < 300 && data) {
                        resolve(data);
                    } else if (data && typeof data.success !== 'undefined') {
                        resolve(data);
                    } else {
                        var msg = (data && data.message) ? data.message : ('Request failed (' + xhr.status + ')');
                        reject({ status: xhr.status, message: msg, data: data });
                    }
                }
            };
            xhr.onerror = function() { reject({ status: 0, message: 'Network error' }); };
            xhr.send(JSON.stringify(body));
        });
    }

    /* ── Result display ── */
    function buildResultFromResident(r, status, message) {
        return {
            status: status,
            qr_code: r.qr_code_identifier || '',
            resident: r,
            message: message,
            scanned_at: nowStr(),
            scanned_by: window.SCANNER_CFG ? SCANNER_CFG.officialName : ''
        };
    }

    function showResult(data, batch) {
        var r = data.resident || {};
        var status = data.status || 'not_found';
        var badgeClass = status === 'active' ? 'ok' : (status === 'not_found' ? 'bad' : 'warn');
        var badgeText = status === 'active' ? 'VERIFIED RESIDENT' :
            (status === 'expired' ? 'EXPIRED' : (status === 'inactive' ? 'INACTIVE' : 'NOT FOUND'));
        var badgeIcon = status === 'active' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';

        var photo = r.photo_path ?
            '<img src="' + escapeAttr(r.photo_path) + '" alt="Photo">' :
            '<i class="bi bi-person-fill"></i>';

        var dob = r.date_of_birth ? formatDate(r.date_of_birth) : '\u2014';
        var age = (r.age !== null && r.age !== undefined) ? r.age : '\u2014';

        var medAlert = '';
        if (r.medical_conditions && String(r.medical_conditions).trim() !== '') {
            medAlert = '<div class="sc-alert-field">' +
                '<div class="sc-alert-title"><i class="bi bi-heart-pulse-fill"></i> Medical Alert / Allergies</div>' +
                '<div class="sc-alert-text">' + escapeHtml(r.medical_conditions) + '</div></div>';
        }

        var statusPill = '<span class="sc-status-pill ' + status + '">' + status.toUpperCase() + '</span>';

        var html =
            '<div class="sc-result-badge ' + badgeClass + '"><i class="bi ' + badgeIcon + '"></i>' + badgeText + '</div>' +
            '<div class="sc-result-body">' +
            '<div class="sc-result-photo">' + photo + '</div>' +
            '<div class="sc-result-info">' +
            '<h2 class="sc-result-name">' + escapeHtml(r.full_name || 'Unnamed Resident') + '</h2>' +
            (r.senior_citizen_id ? '<span class="sc-result-id">SC ID: ' + escapeHtml(r.senior_citizen_id) + '</span>' : '') +
            field('bi-cake2', 'Age / Born', age + ' yrs &middot; ' + dob) +
            field('bi-geo-alt', 'Address', r.address || '\u2014') +
            field('bi-telephone', 'Contact', r.contact_number || '\u2014') +
            field('bi-person-vcard', 'Emergency', (r.emergency_contact_name ? escapeHtml(r.emergency_contact_name) : '\u2014') +
                (r.emergency_contact_phone ? ' &middot; ' + escapeHtml(r.emergency_contact_phone) : '')) +
            field('bi-droplet-fill', 'Blood Type', r.blood_type || '\u2014') +
            field('bi-shield-check', 'Status', statusPill) +
            medAlert +
            '</div>' +
            '</div>' +
            '<div class="sc-result-footer">' +
            '<div class="sc-scan-meta">' +
            '<span><i class="bi bi-clock"></i> ' + escapeHtml(data.scanned_at || nowStr()) + '</span>' +
            '<span><i class="bi bi-person-badge"></i> ' + escapeHtml(data.scanned_by || 'Official') + '</span>' +
            '</div>' +
            (batch ? '' :
                '<div class="sc-remarks-wrap">' +
                '<label for="remarksInput"><i class="bi bi-pencil"></i> Remarks (optional)</label>' +
                '<textarea id="remarksInput" class="sc-remarks" placeholder="e.g. Claimed relief goods, Attended event"></textarea>' +
                '</div>') +
            '<div class="sc-result-buttons">' +
            (batch ? '<button class="sc-btn sc-btn-primary" id="btnResume"><i class="bi bi-camera"></i> Continue Scanning</button>' :
                '<button class="sc-btn sc-btn-primary" id="btnNext"><i class="bi bi-arrow-right-circle"></i> Scan Next Resident</button>') +
            '<button class="sc-btn sc-btn-ghost" id="btnCloseResult"><i class="bi bi-x-circle"></i> Close</button>' +
            '</div>' +
            '</div>';

        el.modalContent.innerHTML = html;
        el.modal.classList.add('show');

        var nextBtn = document.getElementById('btnNext');
        if (nextBtn) nextBtn.addEventListener('click', function() {
            var remarks = document.getElementById('remarksInput');
            if (remarks && remarks.value.trim()) {
                resubmitWithRemarks(data.qr_code, remarks.value.trim());
            }
            hideModal();
            resumeAfterResult();
        });
        var resumeBtn = document.getElementById('btnResume');
        if (resumeBtn) resumeBtn.addEventListener('click', function() {
            hideModal();
            setStatus('ready', 'Ready \u2014 Point camera at QR code');
        });
        var closeBtn = document.getElementById('btnCloseResult');
        if (closeBtn) closeBtn.addEventListener('click', function() {
            hideModal();
            resumeAfterResult();
        });
    }

    function resumeAfterResult() {
        loadQuickStats();
        if (running) {
            setStatus('ready', 'Ready \u2014 Point camera at QR code');
        } else {
            startScanner();
        }
    }

    function resubmitWithRemarks(code, remarks) {
        var agendaId = getCurrentAgendaId();
        fetchPost(SCAN_API, { qr_code: code, remarks: remarks, csrf_token: CSRF_TOKEN, agenda_id: agendaId }).catch(function() {});
    }

    function showNotFound(data, code) {
        var html =
            '<div class="sc-result-badge bad"><i class="bi bi-exclamation-triangle-fill"></i> NOT FOUND</div>' +
            '<div class="sc-result-body" style="flex-direction:column;">' +
            '<p style="font-size:19px;color:#334155;line-height:1.6;margin:0;">' +
            escapeHtml(data.message || 'QR Code not recognized. Please verify manually or register this resident.') + '</p>' +
            '<p style="font-size:15px;color:#64748b;margin:8px 0 0;">Code scanned: <code>' + escapeHtml(code) + '</code></p>' +
            '</div>' +
            '<div class="sc-result-footer">' +
            '<div class="sc-result-buttons">' +
            '<button class="sc-btn sc-btn-primary" id="btnNext"><i class="bi bi-arrow-right-circle"></i> Scan Next Resident</button>' +
            '<button class="sc-btn sc-btn-ghost" id="btnCloseResult"><i class="bi bi-x-circle"></i> Close</button>' +
            '</div>' +
            '</div>';

        el.modalContent.innerHTML = html;
        el.modal.classList.add('show');

        var nb = document.getElementById('btnNext');
        if (nb) nb.addEventListener('click', function() { hideModal();
            resumeAfterResult(); });
        var cb = document.getElementById('btnCloseResult');
        if (cb) cb.addEventListener('click', function() { hideModal();
            resumeAfterResult(); });
    }

    function field(icon, label, value) {
        if (value === '' || value === null || value === undefined) value = '\u2014';
        return '<div class="sc-field"><i class="bi ' + icon + '"></i>' +
            '<span class="sc-field-label">' + label + '</span>' +
            '<span class="sc-field-value">' + value + '</span></div>';
    }

    function hideModal() {
        el.modal.classList.remove('show');
    }

    /* ── Recent scans ── */
    function addRecent(resident, status, code) {
        var item = {
            name: resident ? resident.full_name : 'Unknown code',
            code: code,
            status: status,
            time: nowStr()
        };
        recentScans.unshift(item);
        if (recentScans.length > 5) recentScans.pop();
        renderRecent();
    }

    function renderRecent() {
        if (recentScans.length === 0) {
            el.recentList.innerHTML = '<div class="sc-recent-empty">No scans yet.</div>';
            return;
        }
        var html = '';
        recentScans.forEach(function(s) {
            var cls = s.status === 'active' || s.status === 'success' ? 'ok' :
                (s.status === 'not_found' ? 'bad' : 'warn');
            var label = s.status === 'active' || s.status === 'success' ? 'VERIFIED' :
                (s.status === 'not_found' ? 'NOT FOUND' : s.status.toUpperCase());
            html += '<div class="sc-recent-item">' +
                '<div class="ri-name">' + escapeHtml(s.name) + '</div>' +
                '<div class="ri-sub"><span class="ri-tag ' + cls + '">' + label + '</span>' +
                '<span>' + escapeHtml(s.time) + '</span></div></div>';
        });
        el.recentList.innerHTML = html;
    }

    function openRecent() {
        el.recentDrawer.classList.add('open');
        el.recentBackdrop.classList.add('show');
    }

    function closeRecent() {
        el.recentDrawer.classList.remove('open');
        el.recentBackdrop.classList.remove('show');
    }

    /* ── Quick stats ── */
    function loadQuickStats() {
        fetch('api/scan_stats.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (!d.success) return;
                setStat(el.stats.total, d.stats.total);
                setStat(el.stats.ok, d.stats.success);
                setStat(el.stats.notfound, d.stats.not_found);
                setStat(el.stats.inactive, d.stats.inactive);
            }).catch(function() {});
    }

    function setStat(node, val) {
        if (node) node.textContent = val;
    }

    /* ── Feedback ── */
    function playBeep() {
        try {
            var a = new Audio(window.SCANNER_CFG ? SCANNER_CFG.beepSrc : 'assets/audio/beep.wav');
            a.volume = 0.7;
            a.play().catch(function() { beepTone(880, 0.14); });
        } catch (e) { beepTone(880, 0.14); }
    }

    function playError() {
        try {
            var a = new Audio(window.SCANNER_CFG ? SCANNER_CFG.errorSrc : 'assets/audio/error.wav');
            a.volume = 0.7;
            a.play().catch(function() { beepTone(220, 0.32); });
        } catch (e) { beepTone(220, 0.32); }
    }

    function beepTone(freq, dur) {
        try {
            var ctx = new(window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtAtime(0.5, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + dur);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + dur);
        } catch (e) { /* ignore */ }
    }

    function vibrate(pattern) {
        if (navigator.vibrate) {
            try { navigator.vibrate(pattern); } catch (e) {}
        }
    }

    /* ── UI helpers ── */
    function setStatus(kind, text) {
        el.status.className = 'sc-status ' + kind;
        el.status.innerHTML = '<i class="bi ' +
            (kind === 'success' ? 'bi-check-circle-fill' :
                kind === 'error' ? 'bi-x-circle-fill' :
                kind === 'processing' ? 'bi-arrow-repeat' : 'bi-camera') + '"></i>' + escapeHtml(text);
    }

    function updateControls() {
        el.btnStart.disabled = running;
        el.btnStop.disabled = !running;
        el.btnSwitch.disabled = !running;
        el.btnTorch.disabled = !running;
    }

    function formatDate(d) {
        try {
            var dt = new Date(d);
            if (isNaN(dt)) return escapeHtml(d);
            return dt.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        } catch (e) { return escapeHtml(d); }
    }

    function nowStr() {
        try { return new Date().toLocaleString(); } catch (e) { return ''; }
    }

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, function(c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function escapeAttr(s) {
        return escapeHtml(s).replace(/`/g, '&#96;');
    }
})();