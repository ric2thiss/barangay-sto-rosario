<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#1a56db,#0891b2);color:#fff;">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Add New Staff</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="add_staff.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-user me-1"></i> Personal Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required maxlength="100" placeholder="Juan">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" maxlength="100" placeholder="Dela">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Surname <span class="text-danger">*</span></label>
                            <input type="text" name="surname" class="form-control" required maxlength="100" placeholder="Cruz">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Suffix</label>
                            <input type="text" name="suffix" class="form-control" maxlength="10" placeholder="Jr.">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" maxlength="150" placeholder="staff@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact No.</label>
                            <input type="text" name="contact_no" class="form-control" maxlength="20" placeholder="09XX-XXX-XXXX">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Position / Role</label>
                            <input type="text" name="position" class="form-control" maxlength="100" placeholder="e.g. Barangay Secretary, Clerk">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Photo</label>
                            <input type="file" name="image_path" class="form-control" accept="image/jpeg,image/png,image/gif">
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-key me-1"></i> Account Credentials</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required maxlength="50" placeholder="staff_username" pattern="[a-zA-Z0-9_]+">
                            <small class="text-muted">Letters, numbers, underscore only</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="6" maxlength="128">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6" maxlength="128">
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-shield-alt me-1"></i> Access Privileges</h6>
                    <p class="text-muted" style="font-size:.82rem;margin-bottom:12px;">Select the permissions this staff member should have:</p>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="can_view_residents" value="1" id="add_priv_view" checked>
                                <label class="form-check-label" for="add_priv_view"><i class="fas fa-eye text-info me-1"></i> View Residents</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="can_add_resident" value="1" id="add_priv_add">
                                <label class="form-check-label" for="add_priv_add"><i class="fas fa-plus text-success me-1"></i> Add Resident</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="can_edit_resident" value="1" id="add_priv_edit">
                                <label class="form-check-label" for="add_priv_edit"><i class="fas fa-edit text-warning me-1"></i> Edit Resident</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="can_approve" value="1" id="add_priv_approve">
                                <label class="form-check-label" for="add_priv_approve"><i class="fas fa-check-circle text-primary me-1"></i> Approve / Reject</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="can_delete" value="1" id="add_priv_delete">
                                <label class="form-check-label" for="add_priv_delete"><i class="fas fa-trash text-danger me-1"></i> Delete Resident</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="can_export" value="1" id="add_priv_export">
                                <label class="form-check-label" for="add_priv_export"><i class="fas fa-file-export text-secondary me-1"></i> Export Data</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Create Staff Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
