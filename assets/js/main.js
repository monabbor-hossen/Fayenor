/* ==========================================================================
   GLOBAL VARIABLES & MODAL INITIALIZATION
   ========================================================================== */
var modalElement = null;
var viewModalElement = null;

document.addEventListener("DOMContentLoaded", function () {
    // 1. Initialize Global Workflow Modal (if it exists on the page)
    var workflowEl = document.getElementById('workflowModal');
    if (workflowEl) {
        modalElement = new bootstrap.Modal(workflowEl);
    }

    // 2. Initialize View Client Modal (if it exists on the page)
    var viewEl = document.getElementById('viewClientModal');
    if (viewEl) {
        viewModalElement = new bootstrap.Modal(viewEl);
    }
    
    // 3. Global Bootstrap Tooltip Initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 4. Initialize Live Search (if elements exist)
    setupLiveSearch('desktopSearchInput', 'desktopSearchResults');
    setupLiveSearch('mobileSearchInput', 'mobileSearchResults');
});

/* ==========================================================================
   GLOBAL CUSTOM CONFIRM ALERT (BULLETPROOF METHOD)
   ========================================================================== */

// 1. FOR FORMS (Used on Users page)
function triggerFormModal(formId, customMessage) {
    document.getElementById('rooqConfirmMessage').innerText = customMessage;
    let oldBtn = document.getElementById('rooqConfirmActionBtn');
    let newBtn = oldBtn.cloneNode(true);
    oldBtn.parentNode.replaceChild(newBtn, oldBtn);
    
    newBtn.addEventListener('click', function() {
        document.getElementById(formId).submit();
    });
    
    var myModal = new bootstrap.Modal(document.getElementById('rooqConfirmModal'));
    myModal.show();
}

// 2. FOR LINKS/URLS (Used on Expenses & Client Deletion pages)
function triggerLinkModal(url, customMessage) {
    document.getElementById('rooqConfirmMessage').innerText = customMessage;
    let oldBtn = document.getElementById('rooqConfirmActionBtn');
    let newBtn = oldBtn.cloneNode(true);
    oldBtn.parentNode.replaceChild(newBtn, oldBtn);
    
    newBtn.addEventListener('click', function() {
        window.location.href = url;
    });
    
    var myModal = new bootstrap.Modal(document.getElementById('rooqConfirmModal'));
    myModal.show();
}

/* ==========================================================================
   CLIENT ADD/EDIT PAGE & WORKFLOW LOGIC
   ========================================================================== */

// Toggle between New and Existing Account forms
function toggleAccountFields() {
    let accNew = document.getElementById('acc_new');
    let newFields = document.getElementById('new_account_fields');
    let existFields = document.getElementById('existing_account_fields');
    
    if (accNew && newFields && existFields) {
        if (accNew.checked) {
            newFields.classList.remove('d-none');
            existFields.classList.add('d-none');
        } else {
            newFields.classList.add('d-none');
            existFields.classList.remove('d-none');
        }
    }
}

// Dim out Workflow cards when unchecked
function toggleWorkflowCard(key) {
    const checkbox = document.getElementById('enable_' + key);
    const card = document.getElementById('card_' + key);
    const select = document.getElementById('select_' + key);
    const editBtn = document.querySelector(`#card_${key} .btn-link`);

    if(checkbox && card) {
        if (checkbox.checked) {
            card.style.opacity = '1';
            card.style.filter = 'none';
            if(select) select.disabled = false;
            if(editBtn) editBtn.disabled = false;
        } else {
            card.style.opacity = '0.5';
            card.style.filter = 'grayscale(100%)';
            if(select) select.disabled = true;
            if(editBtn) editBtn.disabled = true;
        }
    }
}

// OPEN WORKFLOW MODAL
function openEditModal(key, label) {
    document.getElementById('modalTitle').innerText = "Update: " + label;
    document.getElementById('current_field_key').value = key;

    const cardSelect = document.getElementById('select_' + key);
    const cardNote = document.getElementById('input_note_' + key);

    const modalSelect = document.getElementById('modal_status_select');
    modalSelect.innerHTML = cardSelect.innerHTML; 
    modalSelect.value = cardSelect.value; 
    
    // Fallback if note input doesn't exist yet
    if(document.getElementById('modal_note_text')) {
        document.getElementById('modal_note_text').value = cardNote ? cardNote.value : '';
    }

    if (modalElement) modalElement.show();
}

// SAVE WORKFLOW CHANGES (Sync back to Card)
function saveModalChanges() {
    const key = document.getElementById('current_field_key').value;
    const newStatus = document.getElementById('modal_status_select').value;
    const newNote = document.getElementById('modal_note_text').value;

    document.getElementById('select_' + key).value = newStatus;
    document.getElementById('input_note_' + key).value = newNote;

    const indicator = document.getElementById('note_indicator_' + key);
    if (indicator) {
        if (newNote.trim() !== "") {
            indicator.classList.remove('d-none');
        } else {
            indicator.classList.add('d-none');
        }
    }

    if (modalElement) modalElement.hide();
}

/**
 * Toggles Password Visibility
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

/* ==========================================================================
   VIEW CLIENT MODAL LOGIC
   ========================================================================== */
function openViewModal(button) {
    try {
        var client = JSON.parse(button.getAttribute('data-client'));
    } catch (e) {
        console.error("Error parsing client data", e);
        return;
    }

    document.getElementById('view_company_name').innerText = client.company_name;
    document.getElementById('view_client_id').innerText = "#" + client.client_id;

    var editBtn = document.getElementById('view_edit_btn');
    if (editBtn) editBtn.href = "client-edit.php?id=" + client.client_id;

    function setVal(id, val) {
        var el = document.getElementById(id);
        if (el) el.innerText = val ? val : '-';
    }

    setVal('v_name', client.client_name);
    setVal('v_phone', client.phone_number);
    setVal('v_email', client.email);
    setVal('v_trade', client.trade_name_application); 
    
    // --- LICENSE SCOPE SECTION ---
    var scopeStatus = client.license_scope_status || 'Pending';
    var scopeNote = client.license_scope_note || ''; 

    var scopeBadge = document.getElementById('badge_scope');
    if (scopeBadge) {
        scopeBadge.innerText = scopeStatus;
        scopeBadge.className = 'view-badge'; 
        if (scopeStatus === 'Approved' || scopeStatus.includes('Done')) {
            scopeBadge.classList.add('badge-approved');
        } else if (scopeStatus === 'Pending' || scopeStatus === 'Applied') {
            scopeBadge.classList.add('badge-pending');
        } else {
            scopeBadge.classList.add('badge-default');
        }
    }

    var scopeNoteEl = document.getElementById('note_scope');
    if (scopeNoteEl) {
        if (scopeNote && scopeNote !== '-') {
            scopeNoteEl.innerText = scopeNote;
            scopeNoteEl.style.display = 'block'; 
        } else {
            scopeNoteEl.style.display = 'none'; 
        }
    }
    
    // Financials
    var totalPaid = parseFloat(client.total_paid || 0);
    var contract = parseFloat(client.contract_value || 0);
    var due = contract - totalPaid;

    setVal('v_contract', contract.toLocaleString('en-US') + ' SAR');
    setVal('v_paid', totalPaid.toLocaleString('en-US') + ' SAR');
    setVal('v_due', due > 0 ? due.toLocaleString('en-US') + ' SAR' : 'Paid');

    // GENERATE WORKFLOW CARDS
    var grid = document.getElementById('workflow_grid');
    if (grid) {
        grid.innerHTML = ''; 
        var steps = [
            { key: 'hire', label: 'Foreign Hire', icon: 'bi-briefcase', status: client.hire_foreign_company, note: client.hire_foreign_company_note },
            { key: 'misa', label: 'MISA License', icon: 'bi-award', status: client.misa_application, note: client.misa_application_note },
            { key: 'sbc', label: 'SBC App', icon: 'bi-building', status: client.sbc_application, note: client.sbc_application_note },
            { key: 'art', label: 'Art. Assoc.', icon: 'bi-file-text', status: client.article_association, note: client.article_association_note },
            { key: 'qiwa', label: 'Qiwa', icon: 'bi-people', status: client.qiwa, note: client.qiwa_note },
            { key: 'muq', label: 'Muqeem', icon: 'bi-person-badge', status: client.muqeem, note: client.muqeem_note },
            { key: 'gosi', label: 'GOSI', icon: 'bi-shield-check', status: client.gosi, note: client.gosi_note },
            { key: 'coc', label: 'Chamber', icon: 'bi-bank', status: client.chamber_commerce, note: client.chamber_commerce_note }
        ];
        
        steps.forEach(step => {
            var status = step.status || 'Pending';
            if (status === 'Not Required') return;

            var colorClass = 'card-status-default';
            if (status === 'Approved' || status.includes('Done')) {
                colorClass = 'card-status-approved';
            } else if (status === 'Pending' || status === 'Applied') {
                colorClass = 'card-status-pending';
            } else if (status === 'In Process') {
                colorClass = 'card-status-process';
            }

            var noteHtml = '';
            if (step.note && step.note.trim() !== '') {
                noteHtml = `<div class="wf-note"><i class="bi bi-chat-left-text"></i> <div>${step.note}</div></div>`;
            }

            var colWrapper = document.createElement('div');
            colWrapper.className = 'col-12 col-md-6 col-xl-4';

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

    if (viewModalElement) viewModalElement.show();
}

/* ==========================================================================
   LIVE SEARCH LOGIC
   ========================================================================== */
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

        timeout = setTimeout(() => {
            fetch(`search_api.php?term=${encodeURIComponent(term)}`)
                .then(async response => {
                    const text = await response.text(); 
                    try {
                        const data = JSON.parse(text);
                        if (!response.ok) throw new Error(data.message || "Server Error " + response.status);
                        return data;
                    } catch (e) {
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
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.classList.add('d-none');
        }
    });
}

function toggleMobileSearch() {
    var overlay = document.getElementById('mobileSearchOverlay');
    if (overlay) {
        if (overlay.classList.contains('show')) {
            overlay.classList.remove('show');
        } else {
            overlay.classList.add('show');
            var input = overlay.querySelector('input');
            if (input) setTimeout(() => input.focus(), 100);
        }
    }
}

/* ==========================================================================
   MISC UTILITIES (LOADER, PARALLAX, TOGGLES, PAYROLL)
   ========================================================================== */

function toggleLoginStatus(type, id, checkbox) {
    const isChecked = checkbox.checked;
    fetch('toggle_status_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: type, id: id, status: isChecked })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert("Error: " + data.message);
                checkbox.checked = !isChecked; 
            }
        })
        .catch(err => {
            console.error("Fetch Error:", err);
            alert("Failed to update status. Check connection.");
            checkbox.checked = !isChecked; 
        });
}

window.addEventListener('pageshow', function (event) {
    const loader = document.getElementById('global-loader');
    if (loader) {
        setTimeout(() => {
            loader.classList.add('hidden');
        }, 300);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const links = document.querySelectorAll('a:not([target="_blank"]):not([href^="#"]):not([href^="javascript"]):not([href=""])');
    links.forEach(link => {
        link.addEventListener('click', function (e) {
            if (e.ctrlKey || e.shiftKey || e.metaKey || e.altKey) return;
            const loader = document.getElementById('global-loader');
            if (loader) loader.classList.remove('hidden');
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const errorImage = document.querySelector('.error-svg');
    if (errorImage) {
        document.addEventListener('mousemove', function (e) {
            let xAxis = (window.innerWidth / 2 - e.pageX) / 30;
            let yAxis = (window.innerHeight / 2 - e.pageY) / 30;
            errorImage.style.transform = `translate(${xAxis}px, ${yAxis}px)`;
        });
    }
});

function submitPayrollFilter(form) {
    const tableContainer = document.getElementById('payroll-table-container');
    const summaryContainer = document.getElementById('summary-cards-container');

    if (tableContainer) tableContainer.style.opacity = '0.3';
    if (summaryContainer) summaryContainer.style.opacity = '0.3';

    const url = new URL(window.location.href.split('?')[0]);
    const formData = new FormData(form);
    url.search = new URLSearchParams(formData).toString();

    fetch(url)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            if (summaryContainer && doc.getElementById('summary-cards-container')) {
                summaryContainer.innerHTML = doc.getElementById('summary-cards-container').innerHTML;
                summaryContainer.style.opacity = '1';
            }

            if (tableContainer && doc.getElementById('payroll-table-container')) {
                tableContainer.innerHTML = doc.getElementById('payroll-table-container').innerHTML;
                tableContainer.style.opacity = '1';
            }
            window.history.pushState({}, '', url);
        })
        .catch(err => {
            console.error("Filter error:", err);
            if (tableContainer) tableContainer.style.opacity = '1';
            if (summaryContainer) summaryContainer.style.opacity = '1';
        });
}

function clearPayrollFilters(form) {
    Array.from(form.elements).forEach(element => {
        if (element.name !== 'id' && element.name !== 'csrf_token') {
            element.value = '';
        }
    });
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

    const btnToday = document.getElementById('dpBtnToday');
    const btnMonth = document.getElementById('dpBtnMonth');
    const btnYear = document.getElementById('dpBtnYear');

    if (!datePicker) return;

    let activeInput = null;
    let viewingDate = new Date(); 

    function closeAndSubmit() {
        datePicker.classList.remove('show');
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

        for (let i = 0; i < firstDay; i++) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'calendar-date empty';
            calendarDays.appendChild(emptyDiv);
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-date';
            dayDiv.innerText = i;
            if (isCurrentMonth && i === today.getDate()) {
                dayDiv.classList.add('today');
            }

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

    const dateInputs = document.querySelectorAll('.rooq-date');
    const actionButtonsContainer = document.getElementById('dpActionButtons'); 

    dateInputs.forEach(input => {
        input.setAttribute('readonly', true);
        input.style.cursor = 'pointer';

        input.addEventListener('click', function (e) {
            e.stopPropagation();
            activeInput = this;

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
                const parts = this.value.split('-');
                let y = parseInt(parts[0]);
                let m = parts.length > 1 ? parseInt(parts[1]) - 1 : 0;
                viewingDate = new Date(y, m, 1);
            } else {
                viewingDate = new Date();
            }

            renderCalendar(viewingDate);

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

/* =========================================
   CHAT APPLICATION LOGIC
   ========================================= */
let lastChatHTML = "INITIAL_LOAD"; 

function switchChat(e, id, name, element) {
    e.preventDefault();
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
        if (content === "") content = "<div class='text-center text-white-50 mt-5'>No messages yet. Start the conversation!</div>";
        
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
    if (!titleEl) return; 

    titleEl.innerText = title;
    document.getElementById('viewAmount').innerText = amount;
    document.getElementById('viewDate').innerText = date;
    document.getElementById('viewCategory').innerText = category;
    document.getElementById('viewDesc').innerText = desc;
    document.getElementById('viewUser').innerText = user;

    const expenseModal = new bootstrap.Modal(document.getElementById('viewExpenseModal'));
    expenseModal.show();
}

/* ==========================================================================
   FINANCE PAGE: PAYMENT FORM UNLOCK 
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function() {
    const toggleSwitch = document.getElementById('unlockPaymentForm');
    if(toggleSwitch) {
        const form = document.getElementById('paymentForm');
        const inputs = form.querySelectorAll('input, select, textarea, button');

        toggleSwitch.addEventListener('change', function() {
            const isEnabled = this.checked;
            inputs.forEach(input => {
                if (input.type !== 'hidden') {
                    input.disabled = !isEnabled;
                }
            });
        });
    }
});


/* ==========================================================================
   SIDEBAR & LAYOUT LOGIC
   ========================================================================== */
document.addEventListener("DOMContentLoaded", function () {
    var toggleBtn = document.getElementById("sidebarToggle");
    var sidebar = document.getElementById("portalSidebar");

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener("click", function (e) {
            e.preventDefault(); 
            sidebar.classList.toggle("show"); 
        });

        // Close sidebar if clicking outside on mobile
        document.addEventListener("click", function (e) {
            if (window.innerWidth < 992 &&
                !sidebar.contains(e.target) &&
                !toggleBtn.contains(e.target)) {
                sidebar.classList.remove("show");
            }
        });
    }
});

/* ==========================================================================
   LIVE NOTIFICATION AUTO-UPDATER
   ========================================================================== */
function checkLiveNotifications() {
    // Safely grab the Base URL from the meta tag in the footer
    const metaTag = document.getElementById('base_url_meta');
    const baseUrl = metaTag ? metaTag.getAttribute('content') : '';

    if (!baseUrl) return; // Safety check

    fetch(baseUrl + 'app/Api/check_notifications.php')
    .then(response => response.json())
    .then(data => {
        if (data.error) return;

        const badge = document.getElementById('liveNotificationBadge');
        const list = document.getElementById('liveNotificationList');

        // Update the Dropdown HTML
        if (list && data.html) {
            list.innerHTML = data.html;
        }

        // Update the Red Dot
        if (badge) {
            if (data.count > 0) {
                badge.innerText = data.count;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }
    })
    .catch(err => console.error("Notification check failed:", err));
}

// Check for new messages every 10 seconds silently in the background
setInterval(checkLiveNotifications, 10000);

/* ==========================================================================
   TOGGLE CLIENT EXPENSE ACCESS
   ========================================================================== */
function toggleClientExpense(clientId, checkbox) {
    const isChecked = checkbox.checked ? 1 : 0;
    checkbox.style.opacity = '0.5';

    // Fetch directly from the same folder!
    fetch('toggle_expense_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ client_id: clientId, show_expenses: isChecked })
    })
    .then(response => response.text()) // Grab raw text first to prevent silent crashes
    .then(text => {
        checkbox.style.opacity = '1';
        try {
            const data = JSON.parse(text);
            if (!data.success) {
                alert("Error: " + data.message);
                checkbox.checked = !isChecked; // Revert switch
            }
        } catch (e) {
            console.error("Server Error Response:", text);
            alert("Failed to save. The database column might be missing. Check the Console (F12) for details.");
            checkbox.checked = !isChecked; // Revert switch
        }
    })
    .catch(err => {
        console.error("Network Error:", err);
        checkbox.style.opacity = '1';
        checkbox.checked = !isChecked;
    });
}
/* ==========================================================================
   TOGGLE CLIENT EXPENSE ACCESS
   ========================================================================== */
function toggleClientExpense(clientId, checkbox) {
    const isChecked = checkbox.checked ? 1 : 0;
    
    // Dim the switch while saving
    checkbox.style.opacity = '0.5';

    // Pointing exactly to where the file is located!
    fetch('../app/Api/toggle_expense_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ client_id: clientId, show_expenses: isChecked })
    })
    .then(async response => {
        // If it hits a 404 page, throw an error immediately instead of reading HTML
        if (!response.ok) throw new Error("API File Not Found (404)");
        return response.json(); 
    })
    .then(data => {
        checkbox.style.opacity = '1';
        if (!data.success) {
            alert("Database Error: " + data.message);
            checkbox.checked = !isChecked; // Revert switch
        }
    })
    .catch(err => {
        console.error("Network Error:", err);
        checkbox.style.opacity = '1';
        alert("Failed to save. Make sure app/Api/toggle_expense_api.php exists!");
        checkbox.checked = !isChecked; // Revert switch
    });
}

/* ==========================================================================
   CONTRACT SIGNATURE PREVIEW & VALIDATION (default-contract.php)
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('signatureFileInput');
    
    // Safety check: Only run this script if the file input actually exists on the page
    if (!fileInput) return; 

    const previewBox = document.getElementById('signaturePreviewBox');
    const previewImg = document.getElementById('signaturePreviewImg');
    const previewLabel = document.getElementById('signaturePreviewLabel');
    const btnDeleteServer = document.getElementById('btnDeleteServer');
    const btnCancelUpload = document.getElementById('btnCancelUpload');
    const sizeError = document.getElementById('fileSizeError');
    
    // Fetch PHP variables safely from the HTML data attributes
    const originalImgSrc = previewBox.getAttribute('data-original-url');
    const hasOriginal = previewBox.getAttribute('data-has-original') === "1";
    const maxFileSize = 2 * 1024 * 1024; // 2MB

    // 1. When user selects a file
    fileInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        sizeError.style.display = 'none';
        
        if (file) {
            // Check file size limit
            if (file.size > maxFileSize) {
                fileInput.value = ''; 
                sizeError.style.display = 'block'; 
                return; 
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewLabel.innerText = "New Signature Preview:";
                previewBox.classList.remove('d-none');
                previewBox.classList.add('d-flex');
                
                if (btnDeleteServer) btnDeleteServer.style.display = 'none';
                btnCancelUpload.style.display = 'inline-block';
            }
            reader.readAsDataURL(file);
        }
    });

    // 2. Cancel upload
    btnCancelUpload.addEventListener('click', function() {
        fileInput.value = ''; 
        sizeError.style.display = 'none'; 
        
        if (hasOriginal) {
            previewImg.src = originalImgSrc;
            previewLabel.innerText = "Current Signature:";
            if (btnDeleteServer) btnDeleteServer.style.display = 'inline-block';
            btnCancelUpload.style.display = 'none';
        } else {
            previewBox.classList.remove('d-flex');
            previewBox.classList.add('d-none');
        }
    });
});