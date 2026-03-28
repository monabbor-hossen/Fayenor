<div class="modal fade" id="viewClientModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass-modal">
            <div class="modal-header border-bottom justify-content-between border-white border-opacity-10">
                <div>
                    <h5 class="modal-title text-white fw-bold mb-0" id="view_company_name">Company Name</h5>
                    <span class="badge bg-secondary text-dark mt-1" id="view_client_id">#ID</span>
                </div>
                <div class="d-flex gap-2">
                    <a href="#" id="view_edit_btn" class="btn btn-sm btn-outline-warning d-flex align-items-center">
                        <i class="bi bi-pencil-square me-2"></i> Edit Client
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-6 border-end border-white border-opacity-10">
                        <h6 class="view-section-title">Contact Information</h6>

                        <div class="view-label">Client Name</div>
                        <div class="view-value" id="v_name">-</div>

                        <div class="view-label">Phone Number</div>
                        <div class="view-value" id="v_phone">-</div>

                        <div class="view-label">Email Address</div>
                        <div class="view-value" id="v_email">-</div>

                        <div class="view-label">Trade Name App</div>
                        <div class="view-value" id="v_trade">-</div>
                    </div>

                    <div class="col-md-6 ps-md-4">
                        <h6 class="view-section-title">Financial Overview</h6>

                        <div class="row">
                            <div class="col-6">
                                <div class="view-label">Contract Value</div>
                                <div class="view-value text-secondary" id="v_contract">-</div>
                            </div>
                            <div class="col-6">
                                <div class="view-label">Paid Amount</div>
                                <div class="view-value text-success" id="v_paid">-</div>
                            </div>
                            <div class="col-12">
                                <div class="view-label">Due Balance</div>
                                <div class="view-value" id="v_due">-</div>
                            </div>
                        </div>

                        <h6 class="view-section-title mt-4">License Scope</h6>
                        <div id="badge_scope" class="view-badge badge-default">-</div>
                        <div id="note_scope" class="wf-note mt-2" style="display:none;">-</div>
                    </div>
                </div>
                <div class="mt-4">
                    <h6 class="view-section-title"
                        style="color:#D4AF37;font-size:0.8rem;text-transform:uppercase;margin-bottom:15px;">
                        <i class="bi bi-diagram-3 me-2"></i>Workflow Progress
                    </h6>

                    <div id="workflow_grid" class="row g-3 mt-2">
                        <div class="col-md-4 col-6">
                            <div class="view-label">Hire Foreign Co.</div>
                            <div id="badge_hire" class="view-badge badge-default">-</div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="view-label">MISA App</div>
                            <div id="badge_misa" class="view-badge badge-default">-</div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="view-label">SBC App</div>
                            <div id="badge_sbc" class="view-badge badge-default">-</div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="view-label">Art. Association</div>
                            <div id="badge_art" class="view-badge badge-default">-</div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="view-label">Qiwa</div>
                            <div id="badge_qiwa" class="view-badge badge-default">-</div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="view-label">Muqeem</div>
                            <div id="badge_muqeem" class="view-badge badge-default">-</div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="view-label">GOSI</div>
                            <div id="badge_gosi" class="view-badge badge-default">-</div>
                        </div>
                        <div class="col-md-4 col-6">
                            <div class="view-label">Chamber of Comm.</div>
                            <div id="badge_coc" class="view-badge badge-default">-</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="glass-calendar-popup" id="rooqDatePicker">
    <div class="calendar-header">
        <button type="button" class="calendar-btn" id="dpPrevMonth" title="Previous Month"><i class="bi bi-chevron-left"></i></button>
        <h5 id="dpMonthYear">Month Year</h5>
        <button type="button" class="calendar-btn" id="dpNextMonth" title="Next Month"><i class="bi bi-chevron-right"></i></button>
    </div>
    
    <div class="calendar-grid">
        <div class="calendar-day-name">Su</div>
        <div class="calendar-day-name">Mo</div>
        <div class="calendar-day-name">Tu</div>
        <div class="calendar-day-name">We</div>
        <div class="calendar-day-name">Th</div>
        <div class="calendar-day-name">Fr</div>
        <div class="calendar-day-name">Sa</div>
    </div>
    
    <div class="calendar-grid" id="dpCalendarDays">
        </div>
    <div class="mt-3 pt-3 border-top border-secondary border-opacity-25 d-flex gap-2 justify-content-between" id="dpActionButtons">
        <button type="button" class="btn btn-sm btn-outline-light flex-grow-1 fw-bold" id="dpBtnToday" style="font-size: 0.75rem;">Today</button>
        <button type="button" class="btn btn-sm btn-outline-light flex-grow-1 fw-bold" id="dpBtnMonth" style="font-size: 0.75rem;">This Month</button>
        <button type="button" class="btn btn-sm btn-outline-light flex-grow-1 fw-bold" id="dpBtnYear" style="font-size: 0.75rem;">This Year</button>
    </div>
</div>

<div class="modal fade" id="viewExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal rounded-4 shadow-lg">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white fw-bold"><i class="bi bi-receipt text-secondary me-2"></i>Expense Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                
                <h4 id="viewTitle" class="text-white fw-bold mb-3">--</h4>
                
                <div class="row mb-4">
                    <div class="col-6">
                        <div class="view-label text-white-50 small text-uppercase">Amount</div>
                        <div class="view-value text-danger fw-bold fs-4">SAR <span id="viewAmount">0.00</span></div>
                    </div>
                    <div class="col-6">
                        <div class="view-label text-white-50 small text-uppercase mb-1">Category</div>
                        <div class="view-value"><span id="viewCategory" class="badge bg-dark border border-warning text-warning px-3 py-2">--</span></div>
                    </div>
                </div>

                <div class="row mb-4 border-top border-light border-opacity-10 pt-3">
                    <div class="col-6">
                        <div class="view-label text-white-50 small text-uppercase">Date</div>
                        <div class="view-value text-white fs-6" id="viewDate">--</div>
                    </div>
                    <div class="col-6">
                        <div class="view-label text-white-50 small text-uppercase">Project / Company</div>
                        <div class="view-value text-info fw-bold fs-6" id="viewUser">--</div>
                    </div>
                </div>

                <div class="bg-dark bg-opacity-50 p-3 rounded-3 border border-light border-opacity-10">
                    <div class="view-label text-white-50 small text-uppercase mb-2"><i class="bi bi-card-text me-1 text-secondary"></i> Description</div>
                    <div class="text-white small" id="viewDesc" style="white-space: pre-wrap; line-height: 1.6;">--</div>
                </div>

            </div>
            <div class="modal-footer border-top border-light border-opacity-10">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rooqConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content glass-modal rounded-4 shadow-lg text-center" style="border: 1px solid rgba(220, 53, 69, 0.4); background: rgba(20, 5, 10, 0.98); backdrop-filter: blur(20px);">
            <div class="modal-body p-4">
                <div class="icon-box bg-danger bg-opacity-25 text-danger rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto shadow" style="width: 65px; height: 65px;">
                    <i class="bi bi-exclamation-triangle fs-2"></i>
                </div>
                <h5 class="text-white fw-bold mb-2">Are you sure?</h5>
                <p class="text-white-50 small mb-4" id="rooqConfirmMessage">You won't be able to revert this action!</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="rooqConfirmActionBtn" class="btn btn-danger rounded-pill px-4 fw-bold shadow-lg">Yes, Proceed</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="workflowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-modal rounded-4 border border-secondary shadow-lg">
            <div class="modal-header border-bottom border-light border-opacity-10">
                <h5 class="modal-title text-white fw-bold" id="modalTitle">Update Status</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="current_field_key">
                <div class="mb-4">
                    <label class="form-label text-secondary small fw-bold">Status</label>
                    <select id="modal_status_select" class="form-select glass-input"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">Note / Remark</label>
                    <textarea id="modal_note_text" class="form-control glass-input" rows="3" placeholder="Add specific details or dates here..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top border-light border-opacity-10">
                <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-rooq-primary rounded-pill px-4" onclick="saveModalChanges()">Save Changes</button>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo BASE_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/all.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>



</body>
</html>