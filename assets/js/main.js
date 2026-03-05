var modalElement;

// Initialize Modal on Page Load
document.addEventListener("DOMContentLoaded", function () {
    var modalEl = document.getElementById('workflowModal');
    if (modalEl) {
        modalElement = new bootstrap.Modal(modalEl);
    }
});

// 1. OPEN MODAL
function openEditModal(key, label) {
    // Set Title
    document.getElementById('modalTitle').innerText = "Update: " + label;
    document.getElementById('current_field_key').value = key;

    // Get Elements from the Card
    const cardSelect = document.getElementById('select_' + key);
    const cardNote = document.getElementById('input_note_' + key);

    // Sync Dropdown Options
    const modalSelect = document.getElementById('modal_status_select');
    modalSelect.innerHTML = cardSelect.innerHTML; // Copy options
    modalSelect.value = cardSelect.value; // Select current value

    // Sync Note Text
    document.getElementById('modal_note_text').value = cardNote.value;

    // Show Modal
    if (modalElement) modalElement.show();
}

// 2. SAVE CHANGES (Sync back to Card)
function saveModalChanges() {
    const key = document.getElementById('current_field_key').value;
    const newStatus = document.getElementById('modal_status_select').value;
    const newNote = document.getElementById('modal_note_text').value;

    // Update Card Dropdown
    document.getElementById('select_' + key).value = newStatus;

    // Update Hidden Note Input (for form submission)
    document.getElementById('input_note_' + key).value = newNote;

    // Show/Hide Note Indicator Icon
    const indicator = document.getElementById('note_indicator_' + key);
    if (newNote.trim() !== "") {
        indicator.classList.remove('d-none');
    } else {
        indicator.classList.add('d-none');
    }

    // Hide Modal
    if (modalElement) modalElement.hide();
}

/**
 * Toggles Password Visibility
 * @param {string} inputId - The ID of the password input field
 * @param {string} iconId - The ID of the icon element to toggle classes
 */
function togglePassword(inputId, iconId) {
    var input = document.getElementById(inputId);
    var icon = document.getElementById(iconId);

    if (input && icon) {
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    }
}
/* --- View Client Modal Logic --- */
var viewModalElement;

document.addEventListener("DOMContentLoaded", function () {
    var viewEl = document.getElementById('viewClientModal');
    if (viewEl) {
        viewModalElement = new bootstrap.Modal(viewEl);
    }
});

function openViewModal(button) {
    // 1. Retrieve Data
    try {
        var client = JSON.parse(button.getAttribute('data-client'));
    } catch (e) {
        console.error("Error parsing client data", e);
        return;
    }

    // 2. Set Header Info
    document.getElementById('view_company_name').innerText = client.company_name;
    document.getElementById('view_client_id').innerText = "#" + client.client_id;

    var editBtn = document.getElementById('view_edit_btn');
    if (editBtn) editBtn.href = "client-edit.php?id=" + client.client_id;

    // 3. Populate Basic Info helper
    function setVal(id, val) {
        var el = document.getElementById(id);
        if (el) el.innerText = val ? val : '-';
    }

    setVal('v_name', client.client_name);
    setVal('v_phone', client.phone_number);
    setVal('v_email', client.email);
    setVal('v_trade', client.trade_name_application); // Add if you have this field
    // --- LICENSE SCOPE SECTION ---
    var scopeStatus = client.license_scope_status || 'Pending';
    var scopeNote = client.license_scope_note || ''; // Ensure column name matches DB

    // 1. Set Status Text
    var scopeBadge = document.getElementById('badge_scope');
    if (scopeBadge) {
        scopeBadge.innerText = scopeStatus;

        // Apply Colors
        scopeBadge.className = 'view-badge'; // Reset
        if (scopeStatus === 'Approved' || scopeStatus.includes('Done')) {
            scopeBadge.classList.add('badge-approved');
        } else if (scopeStatus === 'Pending' || scopeStatus === 'Applied') {
            scopeBadge.classList.add('badge-pending');
        } else {
            scopeBadge.classList.add('badge-default');
        }
    }

    // 2. Set Note Text
    var scopeNoteEl = document.getElementById('note_scope');
    if (scopeNoteEl) {
        if (scopeNote && scopeNote !== '-') {
            scopeNoteEl.innerText = scopeNote;
            scopeNoteEl.style.display = 'block'; // Show if exists
        } else {
            scopeNoteEl.style.display = 'none'; // Hide if empty
        }
    }
    // 4. Financials
    var totalPaid = parseFloat(client.total_paid || 0);
    var contract = parseFloat(client.contract_value || 0);
    var due = contract - totalPaid;

    setVal('v_contract', contract.toLocaleString('en-US') + ' SAR');
    setVal('v_paid', totalPaid.toLocaleString('en-US') + ' SAR');
    setVal('v_due', due > 0 ? due.toLocaleString('en-US') + ' SAR' : 'Paid');

    // 5. GENERATE WORKFLOW CARDS
    var grid = document.getElementById('workflow_grid');
    if (grid) {
        grid.innerHTML = ''; // Clear previous

        // Define Steps Map
        var steps = [

            {
                key: 'hire',
                label: 'Foreign Hire',
                icon: 'bi-briefcase',
                status: client.hire_foreign_company,
                note: client.hire_foreign_company_note
            },
            {
                key: 'misa',
                label: 'MISA License',
                icon: 'bi-award',
                status: client.misa_application,
                note: client.misa_application_note
            },
            {
                key: 'sbc',
                label: 'SBC App',
                icon: 'bi-building',
                status: client.sbc_application,
                note: client.sbc_application_note
            },
            {
                key: 'art',
                label: 'Art. Assoc.',
                icon: 'bi-file-text',
                status: client.article_association,
                note: client.article_association_note
            },
            {
                key: 'qiwa',
                label: 'Qiwa',
                icon: 'bi-people',
                status: client.qiwa,
                note: client.qiwa_note
            },
            {
                key: 'muq',
                label: 'Muqeem',
                icon: 'bi-person-badge',
                status: client.muqeem,
                note: client.muqeem_note
            },
            {
                key: 'gosi',
                label: 'GOSI',
                icon: 'bi-shield-check',
                status: client.gosi,
                note: client.gosi_note
            },
            {
                key: 'coc',
                label: 'Chamber',
                icon: 'bi-bank',
                status: client.chamber_commerce,
                note: client.chamber_commerce_note
            }
        ];
        steps.forEach(step => {
            var status = step.status || 'Pending';

            // Skip disabled steps
            if (status === 'Not Required') return;

            // 1. Determine Main Card Class
            var colorClass = 'card-status-default';
            if (status === 'Approved' || status.includes('Done')) {
                colorClass = 'card-status-approved';
            } else if (status === 'Pending' || status === 'Applied') {
                colorClass = 'card-status-pending';
            } else if (status === 'In Process') {
                colorClass = 'card-status-process';
            }

            // 2. Generate Beautiful Note HTML
            var noteHtml = '';
            if (step.note && step.note.trim() !== '') {
                noteHtml = `<div class="wf-note"><i class="bi bi-chat-left-text"></i> <div>${step.note}</div></div>`;
            }

            // 3. Create Bootstrap Column Wrapper
            var colWrapper = document.createElement('div');
            // Mobile: full width, Tablet: half width, Large Desktop: 1/3 width
            colWrapper.className = 'col-12 col-md-6 col-xl-4';

            // 4. Create Card (Added h-100 for equal height rows)
            var card = document.createElement('div');
            card.className = `workflow-card h-100 ${colorClass}`;
            card.innerHTML = `
                <div class="wf-title"><i class="bi ${step.icon}"></i> ${step.label}</div>
                <div class="wf-status">${status}</div>
                ${noteHtml}
            `;

            colWrapper.appendChild(card);
            grid.appendChild(colWrapper);
        });
    }

    // 6. Show Modal
    if (viewModalElement) viewModalElement.show();
}
/* --- Live Search Logic --- */
/* --- Live Search Logic (Updated for Debugging) --- */
document.addEventListener("DOMContentLoaded", function () {
    setupLiveSearch('desktopSearchInput', 'desktopSearchResults');
    setupLiveSearch('mobileSearchInput', 'mobileSearchResults');
});

function setupLiveSearch(inputId, resultsId) {
    const input = document.getElementById(inputId);
    const resultsBox = document.getElementById(resultsId);

    if (!input || !resultsBox) return;

    let timeout = null;

    input.addEventListener('input', function () {
        clearTimeout(timeout);
        const term = this.value.trim();

        if (term.length < 2) {
            resultsBox.classList.add('d-none');
            return;
        }

        // Debounce 300ms
        timeout = setTimeout(() => {
            fetch(`search_api.php?term=${encodeURIComponent(term)}`)
                .then(async response => {
                    const text = await response.text(); // Read raw text first

                    // Try parsing JSON
                    try {
                        const data = JSON.parse(text);
                        if (!response.ok) throw new Error(data.message || "Server Error " + response.status);
                        return data;
                    } catch (e) {
                        // If JSON parse fails, throw the raw text (HTML Error)
                        throw new Error("Invalid Response: " + text.substring(0, 100) + "...");
                    }
                })
                .then(data => {
                    resultsBox.innerHTML = '';
                    if (data.length > 0) {
                        resultsBox.classList.remove('d-none');
                        data.forEach(client => {
                            const item = document.createElement('div');
                            item.className = 'search-result-item p-2 border-bottom border-secondary border-opacity-25';
                            item.style.cursor = 'pointer';
                            item.innerHTML = `<div class="d-flex align-items-center">
                                            <div class="avatar-small me-2" style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;background:rgba(212,175,55,0.2);color:#D4AF37;border-radius:50%;font-weight:bold;">
                                                ${client.company_name.substring(0,1).toUpperCase()}
                                            </div>
                                            <div>
                                                <div class="text-white small fw-bold">${client.company_name}</div>
                                                <div class="text-white-50" style="font-size: 0.7rem;">
                                                    #${client.client_id} • ${client.client_name || ''} • ${client.phone_number || ''} • ${client.email || ''}
                                                </div>
                                            </div>
                                        </div>
                                    `;
                            item.addEventListener('click', () => {
                                const dummyBtn = document.createElement('button');
                                dummyBtn.setAttribute('data-client', JSON.stringify(client));
                                openViewModal(dummyBtn);
                                resultsBox.classList.add('d-none');
                                input.value = '';
                                toggleMobileSearch();
                            });
                            resultsBox.appendChild(item);
                        });
                    } else {
                        resultsBox.classList.add('d-none');
                    }
                })
                .catch(err => {
                    console.error('SEARCH DEBUG ERROR:', err);
                    // Optional: Alert the user for easier debugging
                    // alert("Search Error: " + err.message); 
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.classList.add('d-none');
        }
    });
}

// Mobile Toggle Function
/* --- Mobile Search Toggle (Updated) --- */
function toggleMobileSearch() {
    var overlay = document.getElementById('mobileSearchOverlay');
    if (overlay) {
        // Toggle the 'show' class defined in theme.css
        if (overlay.classList.contains('show')) {
            overlay.classList.remove('show');
        } else {
            overlay.classList.add('show');
            // Auto-focus input
            var input = overlay.querySelector('input');
            if (input) setTimeout(() => input.focus(), 100);
        }
    }
}


/* --- Workflow Toggle (Optional Cards) --- */
function toggleWorkflowCard(key) {
    const checkbox = document.getElementById('enable_' + key);
    const card = document.getElementById('card_' + key);
    const select = document.getElementById('select_' + key);
    const editBtn = document.getElementById('btn_edit_' + key);

    if (checkbox.checked) {
        // Turn ON
        card.style.opacity = '1';
        select.disabled = false;
        editBtn.disabled = false;
    } else {
        // Turn OFF (Optional)
        card.style.opacity = '0.5';
        select.disabled = true;
        editBtn.disabled = true;
    }
}


/* --- Toggle Login Status API --- */
function toggleLoginStatus(type, id, checkbox) {
    const isChecked = checkbox.checked;

    fetch('toggle_status_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                type: type,
                id: id,
                status: isChecked
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert("Error: " + data.message);
                checkbox.checked = !isChecked; // Revert switch if backend failed
            }
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            alert("Failed to update status. Check connection.");
            checkbox.checked = !isChecked; // Revert switch
        });
}

/* =========================================
   GLOBAL PAGE LOADER LOGIC
   ========================================= */

// 1. Hide loader when page loads OR when returning via "Back" button (pageshow)
window.addEventListener('pageshow', function (event) {
    const loader = document.getElementById('global-loader');
    if (loader) {
        // event.persisted is true if the page is loaded from the bfcache (Back button)
        // We hide the loader whether it's a fresh load or a back button load
        setTimeout(() => {
            loader.classList.add('hidden');
        }, 300);
    }
});

// 2. Show loader when clicking links to navigate away
document.addEventListener('DOMContentLoaded', function () {
    // Select all links that do NOT open in a new tab, and are NOT anchor/JS links
    const links = document.querySelectorAll('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"]):not([href=""])');

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            // Ignore if user is holding CTRL/CMD to open in new tab
            if (e.ctrlKey || e.shiftKey || e.metaKey || e.altKey) return;

            const loader = document.getElementById('global-loader');
            if (loader) {
                // Show loader immediately
                loader.classList.remove('hidden');
            }
        });
    });
});

/* =========================================
   404 PAGE PARALLAX EFFECT
   ========================================= */
document.addEventListener("DOMContentLoaded", function () {
    const errorImage = document.querySelector('.error-svg');

    if (errorImage) {
        document.addEventListener('mousemove', function (e) {
            // Calculate mouse position relative to center of screen
            let xAxis = (window.innerWidth / 2 - e.pageX) / 30;
            let yAxis = (window.innerHeight / 2 - e.pageY) / 30;

            // Combine the mouse parallax with the CSS floating animation
            errorImage.style.transform = `translate(${xAxis}px, ${yAxis}px)`;
        });
    }
});

/* =========================================
   AJAX PAYROLL FILTERING (NO RELOAD)
   ========================================= */
function submitPayrollFilter(form) {
    const tableContainer = document.getElementById('payroll-table-container');
    const summaryContainer = document.getElementById('summary-cards-container');

    // Dim the containers to show loading state
    if (tableContainer) tableContainer.style.opacity = '0.3';
    if (summaryContainer) summaryContainer.style.opacity = '0.3';

    // Build the query URL
    const url = new URL(window.location.href.split('?')[0]);
    const formData = new FormData(form);
    url.search = new URLSearchParams(formData).toString();

    // Fetch the updated page silently
    fetch(url)
        .then(response => response.text())
        .then(html => {
            // Parse the returned HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Replace Summary Cards
            if (summaryContainer && doc.getElementById('summary-cards-container')) {
                summaryContainer.innerHTML = doc.getElementById('summary-cards-container').innerHTML;
                summaryContainer.style.opacity = '1';
            }

            // Replace Table
            if (tableContainer && doc.getElementById('payroll-table-container')) {
                tableContainer.innerHTML = doc.getElementById('payroll-table-container').innerHTML;
                tableContainer.style.opacity = '1';
            }

            // Update the browser URL without reloading (so sharing links works)
            window.history.pushState({}, '', url);
        })
        .catch(err => {
            console.error("Filter error:", err);
            // Revert opacity if it fails
            if (tableContainer) tableContainer.style.opacity = '1';
            if (summaryContainer) summaryContainer.style.opacity = '1';
        });
}

// Function for the Clear button
function clearPayrollFilters(form) {
    // Clear all inputs except the hidden user ID
    Array.from(form.elements).forEach(element => {
        if (element.name !== 'id' && element.name !== 'csrf_token') {
            element.value = '';
        }
    });
    // Trigger the AJAX filter
    submitPayrollFilter(form);
}


/* =========================================
   GLOBAL ROOQ DATE PICKER
   ========================================= */
document.addEventListener('DOMContentLoaded', function () {
    const datePicker = document.getElementById('rooqDatePicker');
    const monthYear = document.getElementById('dpMonthYear');
    const calendarDays = document.getElementById('dpCalendarDays');
    const prevBtn = document.getElementById('dpPrevMonth');
    const nextBtn = document.getElementById('dpNextMonth');

    // New Action Buttons
    const btnToday = document.getElementById('dpBtnToday');
    const btnMonth = document.getElementById('dpBtnMonth');
    const btnYear = document.getElementById('dpBtnYear');

    if (!datePicker) return;

    let activeInput = null;
    let viewingDate = new Date(); // The month currently being viewed

    function closeAndSubmit() {
        datePicker.classList.remove('show');
        
        // ONLY auto-submit if the input has the 'auto-filter' class
        if (activeInput && activeInput.classList.contains('auto-filter')) {
            if (activeInput.form) {
                if (typeof submitPayrollFilter === 'function' && document.getElementById('payroll-table-container')) {
                    submitPayrollFilter(activeInput.form);
                } else {
                    activeInput.form.submit();
                }
            }
        }
    }

    function renderCalendar(dateToView) {
        calendarDays.innerHTML = '';
        const year = dateToView.getFullYear();
        const month = dateToView.getMonth();

        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        monthYear.innerText = `${monthNames[month]} ${year}`;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        const today = new Date();
        const isCurrentMonth = today.getMonth() === month && today.getFullYear() === year;

        // Empty slots
        for (let i = 0; i < firstDay; i++) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'calendar-date empty';
            calendarDays.appendChild(emptyDiv);
        }

        // Real Days
        for (let i = 1; i <= daysInMonth; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-date';
            dayDiv.innerText = i;

            if (isCurrentMonth && i === today.getDate()) {
                dayDiv.classList.add('today');
            }

            // Click Event to select Exact Date
            dayDiv.addEventListener('click', function (e) {
                e.stopPropagation();
                if (activeInput) {
                    activeInput.value = `${year}-${String(month+1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                    closeAndSubmit();
                }
            });

            calendarDays.appendChild(dayDiv);
        }
    }

    // Previous/Next Month Logic
    prevBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        viewingDate.setMonth(viewingDate.getMonth() - 1);
        renderCalendar(viewingDate);
    });

    nextBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        viewingDate.setMonth(viewingDate.getMonth() + 1);
        renderCalendar(viewingDate);
    });

    // --- Action Button Logic ---
    if (btnToday) {
        btnToday.addEventListener('click', (e) => {
            e.stopPropagation();
            if (activeInput) {
                const today = new Date();
                activeInput.value = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                closeAndSubmit();
            }
        });
    }

    if (btnMonth) {
        btnMonth.addEventListener('click', (e) => {
            e.stopPropagation();
            if (activeInput) {
                activeInput.value = `${viewingDate.getFullYear()}-${String(viewingDate.getMonth()+1).padStart(2, '0')}`;
                closeAndSubmit();
            }
        });
    }

    if (btnYear) {
        btnYear.addEventListener('click', (e) => {
            e.stopPropagation();
            if (activeInput) {
                activeInput.value = `${viewingDate.getFullYear()}`;
                closeAndSubmit();
            }
        });
    }

    // Attach to all inputs with class 'rooq-date'
    const dateInputs = document.querySelectorAll('.rooq-date');
    const actionButtonsContainer = document.getElementById('dpActionButtons'); // NEW

    dateInputs.forEach(input => {
        input.setAttribute('readonly', true);
        input.style.cursor = 'pointer';

        input.addEventListener('click', function (e) {
            e.stopPropagation();
            activeInput = this;

            // NEW: Hide or Show the bottom action buttons based on the input's attribute
            if (actionButtonsContainer) {
                if (this.getAttribute('data-hide-buttons') === 'true') {
                    actionButtonsContainer.classList.remove('d-flex');
                    actionButtonsContainer.style.display = 'none';
                } else {
                    actionButtonsContainer.classList.add('d-flex');
                    actionButtonsContainer.style.display = '';
                }
            }

            if (this.value && this.value.length >= 4) {
                // Try to open calendar to the currently inputted year/month
                const parts = this.value.split('-');
                let y = parseInt(parts[0]);
                let m = parts.length > 1 ? parseInt(parts[1]) - 1 : 0;
                viewingDate = new Date(y, m, 1);
            } else {
                viewingDate = new Date();
            }

            renderCalendar(viewingDate);

            // Position popup
            const rect = this.getBoundingClientRect();
            datePicker.style.top = (rect.bottom + window.scrollY) + 'px';
            datePicker.style.left = rect.left + 'px';

            datePicker.classList.add('show');
        });
    });

    document.addEventListener('click', function (e) {
        if (!datePicker.contains(e.target) && !e.target.classList.contains('rooq-date')) {
            datePicker.classList.remove('show');
        }
    });
});

function validatePaymentDate() {
    var paymentDate = document.getElementById('modalPaymentDate').value;
    var joinDate = '<?php echo $exact_join_date; ?>';
    
    if (new Date(paymentDate) < new Date(joinDate)) {
        alert("Payment Date cannot be before the user's Joining Date (" + joinDate + ").");
        return false; // Stops form from submitting
    }
    return true; // Allows form submission
}

/* =========================================
   CHAT APPLICATION LOGIC
   ========================================= */
let lastChatHTML = "INITIAL_LOAD"; 

// --- SPA CHAT SWITCHER (NO PAGE RELOAD) ---
function switchChat(e, id, name, element) {
    e.preventDefault();
    
    // 1. ALWAYS trigger Mobile Slide-Over effect
    if (window.innerWidth < 768) {
        document.getElementById('chatSidebarList').classList.remove('d-block');
        document.getElementById('chatSidebarList').classList.add('d-none');
        document.getElementById('chatMainBox').classList.remove('d-none');
        document.getElementById('chatMainBox').classList.add('d-flex');
        
        const box = document.getElementById('chatBox');
        if(box) box.scrollTop = box.scrollHeight;
    }

    if(window.currentChatClientId === id) return;
    
    window.currentChatClientId = id;
    lastChatHTML = "FORCE_REFRESH";
    
    document.querySelectorAll('.client-chat-link').forEach(el => {
        el.classList.remove('bg-rooq-primary', 'text-white');
        el.classList.add('text-white-50', 'hover-white');
    });
    element.classList.remove('text-white-50', 'hover-white');
    element.classList.add('bg-rooq-primary', 'text-white');
    
    const headerSub = document.getElementById('chatHeaderSub');
    if(headerSub) headerSub.innerText = name;
    
    const box = document.getElementById('chatBox');
    if(box) box.innerHTML = "<div class='text-center text-white-50 mt-5'><div class='spinner-border spinner-border-sm me-2'></div> Loading messages...</div>";
    
    loadChats();
}

function closeMobileChat(e) {
    e.preventDefault();
    document.getElementById('chatMainBox').classList.remove('d-flex');
    document.getElementById('chatMainBox').classList.add('d-none');
    document.getElementById('chatSidebarList').classList.remove('d-none');
    document.getElementById('chatSidebarList').classList.add('d-block');
}

// --- STANDARD CHAT FUNCTIONS ---
function loadChats() {
    if (!window.currentChatClientId || window.currentChatClientId === 0) {
        const box = document.getElementById('chatBox');
        if(box && box.innerHTML === "") box.innerHTML = "<div class='text-center text-white-50 mt-5'>No active projects found.</div>";
        return;
    }
    
    fetch(`../app/Api/fetch_chats.php?client_id=${window.currentChatClientId}`)
    .then(r => {
        if (!r.ok) throw new Error("Server returned " + r.status);
        return r.text();
    })
    .then(html => {
        let content = html.trim();
        if (content === "") {
            content = "<div class='text-center text-white-50 mt-5'>No messages yet. Start the conversation!</div>";
        }
        
        if (content !== lastChatHTML) {
            const box = document.getElementById('chatBox');
            if(!box) return;
            const isScrolledToBottom = box.scrollHeight - box.clientHeight <= box.scrollTop + 100;
            box.innerHTML = content;
            if (isScrolledToBottom) box.scrollTop = box.scrollHeight;
            lastChatHTML = content;
        }
    }).catch(err => {
        console.error("Error loading chat:", err);
        const box = document.getElementById('chatBox');
        if(box) box.innerHTML = "<div class='text-danger text-center mt-5'><b>Error loading chats.</b></div>";
    });
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    if (!input) return;
    const msg = input.value.trim();
    if (!msg || !window.currentChatClientId || window.currentChatClientId === 0) return;
    
    input.value = ''; 
    input.style.height = '48px'; 

    const box = document.getElementById('chatBox');
    if (box.innerHTML.includes("No messages yet")) box.innerHTML = '';
    const tempBubble = `
        <div class='d-flex justify-content-end mb-3 w-100 temp-msg'>
            <div class='d-flex flex-column text-end' style='max-width: 85%;'>
                <div class='small text-white-50 fw-bold mb-1 pe-1 fst-italic'>Sending...</div>
                <div class='p-3 shadow-sm' style='background: #800020; color: #fff; border-radius: 15px 15px 2px 15px; display: inline-block; text-align: left; opacity: 0.8; word-break: break-word;'>
                    ${msg.replace(/\n/g, '<br>')}
                </div>
            </div>
        </div>`;
    
    box.insertAdjacentHTML('beforeend', tempBubble);
    box.scrollTop = box.scrollHeight; 
    lastChatHTML = "FORCE_REFRESH"; 

    fetch('../app/Api/send_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ client_id: window.currentChatClientId, message: msg })
    }).then(r => loadChats());
}

// Initialize chat ONLY if we are on a chat page!
document.addEventListener("DOMContentLoaded", () => {
    const chatBox = document.getElementById('chatBox');
    if (chatBox) { 
        if (window.currentChatClientId && window.currentChatClientId !== 0) {
            loadChats(); 
            setInterval(loadChats, 3000); 
            
            const chatInputBox = document.getElementById('chatInput');
            if(chatInputBox) {
                chatInputBox.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
        } else {
            chatBox.innerHTML = "<div class='text-center text-white-50 mt-5'>Please select a project from the sidebar to start chatting.</div>";
        }
    }
});



/* =========================================
   EXPENSE MANAGEMENT LOGIC
   ========================================= */
function viewExpense(title, amount, date, category, desc, user) {
    const titleEl = document.getElementById('viewTitle');
    
    // Safety check: If we are not on the expenses page, do nothing!
    if (!titleEl) return; 

    // Fill the modal fields with the exact row data
    titleEl.innerText = title;
    document.getElementById('viewAmount').innerText = amount;
    document.getElementById('viewDate').innerText = date;
    document.getElementById('viewCategory').innerText = category;
    document.getElementById('viewDesc').innerText = desc;
    document.getElementById('viewUser').innerText = user;

    // Show the modal using Bootstrap's JS API
    const expenseModal = new bootstrap.Modal(document.getElementById('viewExpenseModal'));
    expenseModal.show();
}


/* ==========================================================================
   FINANCE PAGE: PAYMENT FORM UNLOCK & TOOLTIPS
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Payment Form Unlock Toggle
    const toggleSwitch = document.getElementById('unlockPaymentForm');
    if(toggleSwitch) {
        const form = document.getElementById('paymentForm');
        const inputs = form.querySelectorAll('input, select, textarea, button');

        toggleSwitch.addEventListener('change', function() {
            const isEnabled = this.checked;
            inputs.forEach(input => {
                // Only toggle inputs that are NOT hidden fields
                if (input.type !== 'hidden') {
                    input.disabled = !isEnabled;
                }
            });
        });
    }

    // 2. Global Bootstrap Tooltip Initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

});
/* ==========================================================================
   GLOBAL CUSTOM CONFIRM ALERT (WORKS FOR BOTH LINKS & FORMS)
   ========================================================================== */
let confirmModalInstance = null; 

function showConfirmModal(message, onConfirmCallback) {
    // 1. Set the message
    document.getElementById('rooqConfirmMessage').innerText = message;
    
    // 2. Grab the "Yes, Proceed" button
    let oldBtn = document.getElementById('rooqConfirmActionBtn');
    
    // 3. CLONE the button to perfectly wipe out any old, stuck clicks!
    let newBtn = oldBtn.cloneNode(true);
    oldBtn.parentNode.replaceChild(newBtn, oldBtn);
    
    // 4. Attach the new action to the fresh button
    newBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Hide modal instantly so it feels snappy, then execute the action
        confirmModalInstance.hide(); 
        onConfirmCallback();
    });

    // 5. Initialize and show the modal
    if (!confirmModalInstance) {
        confirmModalInstance = new bootstrap.Modal(document.getElementById('rooqConfirmModal'));
    }
    confirmModalInstance.show();
}

// FOR STANDARD LINKS (<a> tags)
function confirmAction(event, url, message = "Are you sure you want to proceed?") {
    event.preventDefault();
    showConfirmModal(message, function() {
        window.location.href = url; // Redirect the page
    });
}

// FOR SECURE FORMS (<form> tags)
function confirmFormSubmit(event, formElement, message = "Are you sure you want to proceed?") {
    event.preventDefault();
    showConfirmModal(message, function() {
        formElement.submit(); // Submit the secure POST form
    });
}