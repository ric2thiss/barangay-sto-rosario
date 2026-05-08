<!-- Edit Staff Modal (uses $row from the loop in staff_management.php) -->
<div class="modal fade" id="editStaffModal<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#ff8a00,#e02424);color:#fff;">
                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i> Edit Staff — <?= htmlspecialchars($row['first_name'].' '.$row['surname']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="edit_staff.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <div class="modal-body">
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-user me-1"></i> Personal Information</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required maxlength="100" value="<?= htmlspecialchars($row['first_name']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control" maxlength="100" value="<?= htmlspecialchars($row['middle_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Surname <span class="text-danger">*</span></label>
                            <input type="text" name="surname" class="form-control" required maxlength="100" value="<?= htmlspecialchars($row['surname']) ?>">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Suffix</label>
                            <input type="text" name="suffix" class="form-control" maxlength="10" value="<?= htmlspecialchars($row['suffix'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" maxlength="150" value="<?= htmlspecialchars($row['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact No.</label>
                            <input type="text" name="contact_no" class="form-control" maxlength="20" value="<?= htmlspecialchars($row['contact_no'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Position / Role</label>
                            <input type="text" name="position" class="form-control" maxlength="100" value="<?= htmlspecialchars($row['position'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Photo</label>
                            <input type="file" name="image_path" class="form-control" accept="image/jpeg,image/png,image/gif">
                            <small class="text-muted">Leave empty to keep current photo</small>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-shield-alt me-1"></i> Access Privileges</h6>
                    <div class="row">
                        <?php
                        $edit_privs = [
                            'can_view_residents' => ['View Residents', 'fa-eye', 'text-info'],
                            'can_add_resident'   => ['Add Resident', 'fa-plus', 'text-success'],
                            'can_edit_resident'  => ['Edit Resident', 'fa-edit', 'text-warning'],
                            'can_approve'        => ['Approve / Reject', 'fa-check-circle', 'text-primary'],
                            'can_delete'         => ['Delete Resident', 'fa-trash', 'text-danger'],
                            'can_export'         => ['Export Data', 'fa-file-export', 'text-secondary'],
                        ];
                        foreach ($edit_privs as $col => [$label, $icon, $color]): ?>
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="<?= $col ?>" value="1"
                                       id="edit_<?= $row['id'] ?>_<?= $col ?>"
                                       <?= (int)$row[$col] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="edit_<?= $row['id'] ?>_<?= $col ?>">
                                    <i class="fas <?= $icon ?> <?= $color ?> me-1"></i> <?= $label ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>
                    <h6 class="fw-bold text-primary mb-3"><i class="fas fa-info-circle me-1"></i> Account Status</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="Active" <?= $row['status']==='Active'?'selected':'' ?>>Active</option>
                                <option value="Inactive" <?= $row['status']==='Inactive'?'selected':'' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($row['username']) ?>" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
