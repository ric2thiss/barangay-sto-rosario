<!-- ══════════════════════════════════════════════════════════════════
     ADD RESIDENT MODAL — FIXED
     Key fixes:
       1. Added missing #addPwdErr element to HTML (was referenced in JS but never existed)
       2. PWD "Other" path: hidden field pwd_type_resolved carries the free-text;
          on submit we disable the radio and rename the hidden field to pwd_type
          so only one value is submitted. No radio.value mutation.
       3. Modal reset now properly restores radio name attributes.
     ══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="addResidentModal" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width:1250px">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#0f3c6e">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Resident</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="add_residents.php" method="POST" enctype="multipart/form-data" id="addResidentForm">
                <div class="modal-body" style="max-height:78vh;overflow-y:auto;padding:20px 24px">

                    <!-- ── ACCOUNT CREDENTIALS ──────────────────────────── -->
                    <div class="section-hdr"><i class="fas fa-lock"></i> Account Credentials</div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label req">Username</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="username" id="addUsername"
                                       required minlength="4" maxlength="20" pattern="[a-zA-Z0-9_]+"
                                       placeholder="4–20 chars, letters/numbers/_" autocomplete="off">
                                <span class="input-group-text px-2">
                                    <i class="fas fa-circle-notch fa-spin text-muted d-none" id="addUSpinner"></i>
                                    <i class="fas fa-check text-success d-none" id="addUCheck"></i>
                                    <i class="fas fa-times text-danger d-none" id="addUX"></i>
                                </span>
                            </div>
                            <div class="field-hint" id="addUErr"></div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label req">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="addPassword"
                                       required minlength="6" maxlength="50" placeholder="Minimum 6 characters">
                                <button class="btn btn-outline-secondary" type="button" id="addTogglePwd">
                                    <i class="fas fa-eye" id="addPwdIcon"></i>
                                </button>
                            </div>
                            <div class="pwd-strength-bar"><div id="addPwdBar"></div></div>
                            <div class="field-hint" id="addPwdHint">Min 6 characters</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label req">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="confirm_password"
                                       id="addConfirmPwd" required minlength="6" maxlength="50"
                                       placeholder="Re-enter password">
                                <button class="btn btn-outline-secondary" type="button" id="addToggleConfirm">
                                    <i class="fas fa-eye" id="addConfirmIcon"></i>
                                </button>
                            </div>
                            <div class="field-hint" id="addConfirmErr"></div>
                        </div>

                        <!-- PHOTO -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Upload Photo <span class="text-danger">*</span></label>
                            <div class="add-photo-wrapper">
                                <div class="add-photo-inner">
                                    <div class="add-photo-preview-box" id="addPhotoPreviewBox">
                                        <div class="add-photo-placeholder" id="addPhotoPlaceholder">
                                            <i class="fas fa-user-circle"></i>
                                            <span>No photo</span>
                                        </div>
                                        <img id="addPhotoPreviewImg" src="" alt=""
                                             style="display:none;width:100%;height:100%;object-fit:cover;">
                                        <button type="button" class="add-remove-photo d-none" id="addRemovePhotoBtn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="add-photo-btn-group">
                                        <label class="btn btn-sm btn-outline-secondary mb-0"
                                               style="cursor:pointer;font-size:11px;">
                                            <i class="fas fa-upload me-1"></i> Upload
                                            <input type="file" name="Image" id="addImage"
                                                   accept="image/jpeg,image/jpg,image/png,image/gif"
                                                   class="d-none">
                                        </label>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                id="addOpenCameraBtn" style="font-size:11px;">
                                            <i class="fas fa-camera me-1"></i> Camera
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="camera_image" id="addCameraImage" value="">
                            </div>
                            <small class="text-muted" style="font-size:10px;">Max 2MB · JPG PNG GIF</small>
                            <div class="field-hint" id="addImgErr"></div>
                        </div>
                    </div>

                    <!-- ── PERSONAL INFORMATION ─────────────────────────── -->
                    <div class="section-hdr mt-1"><i class="fas fa-user"></i> Personal Information</div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label req">First Name</label>
                            <input type="text" class="form-control" name="first_name" required maxlength="50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" maxlength="50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label req">Surname</label>
                            <input type="text" class="form-control" name="surname" required maxlength="50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Suffix</label>
                            <select class="form-control" name="suffix">
                                <option value="">None</option>
                                <option value="Jr.">Jr.</option>
                                <option value="Sr.">Sr.</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label req">Birthdate</label>
                            <input type="date" class="form-control" name="birthdate" id="addBirthdate"
                                   min="1900-01-01" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label req">Age</label>
                            <input type="number" class="form-control" name="age" id="addAge" readonly required>
                            <input type="hidden" name="is_newborn" id="addIsNewborn" value="No">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label req">Birthplace</label>
                            <input type="text" class="form-control" name="birthplace" required maxlength="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label req">Sex / Gender</label>
                            <select class="form-control" name="sex" id="addSexSelect" required
                                    onchange="addToggleLgbtq()">
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="LGBTQ+">LGBTQ+</option>
                            </select>
                            <!-- LGBTQ+ Sub-dropdown -->
                            <div class="d-none mt-2" id="addLgbtqWrap">
                                <label class="form-label" style="font-size:11px;font-weight:600;color:#7c3aed">
                                    <i class="fas fa-rainbow me-1"></i> LGBTQ+ Identity
                                </label>
                                <select class="form-control" name="lgbtq_identity" id="addLgbtqIdentity"
                                        onchange="addToggleLgbtqOther()" style="border-left:3px solid #7c3aed!important">
                                    <option value="">Select Identity</option>
                                    <option value="Lesbian">Lesbian</option>
                                    <option value="Gay">Gay</option>
                                    <option value="Bisexual">Bisexual</option>
                                    <option value="Transgender">Transgender</option>
                                    <option value="Queer">Queer</option>
                                    <option value="Other">Other (specify)</option>
                                </select>
                                <div class="d-none mt-1" id="addLgbtqOtherWrap">
                                    <input type="text" class="form-control" name="lgbtq_other_text"
                                           id="addLgbtqOtherText" maxlength="200"
                                           placeholder="Please specify identity…"
                                           style="border-left:3px solid #7c3aed!important">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label req">Civil Status</label>
                            <select class="form-control" name="civil_status" required>
                                <option value="">Select</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                                <option value="Annulled">Annulled</option>
                                <option value="Live-in">Live-in</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Nationality</label>
                            <input type="text" class="form-control" name="nationality"
                                   value="Filipino" maxlength="50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Contact No.</label>
                            <input type="text" class="form-control" name="contact_no"
                                   placeholder="09XXXXXXXXX" maxlength="20">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email"
                                   placeholder="example@gmail.com" maxlength="150">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Blood Type</label>
                            <select class="form-control" name="blood_type" id="addBloodType">
                                <option value="">Select</option>
                                <option>A+</option><option>A-</option>
                                <option>B+</option><option>B-</option>
                                <option>AB+</option><option>AB-</option>
                                <option>O+</option><option>O-</option>
                                <option value="Unknown">Unknown</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="other-wrap d-none" id="addBloodTypeOtherWrap">
                                <input type="text" class="form-control mt-1" name="blood_type_other"
                                       maxlength="20" placeholder="Specify blood type">
                            </div>
                        </div>
                    </div>

                    <!-- ── DEMOGRAPHIC INFORMATION ──────────────────────── -->
                    <div class="section-hdr mt-1" style="background:#e8f0fe;border-left-color:#1a56db;color:#1a56db">
                        <i class="fas fa-id-card"></i> Demographic Information
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Religion</label>
                            <select class="form-control" name="religion" id="addReligion">
                                <option value="">Select</option>
                                <option>Roman Catholic</option><option>Islam</option>
                                <option>Iglesia ni Cristo</option><option>Seventh-day Adventist</option>
                                <option>Born Again Christian</option><option>Baptist</option>
                                <option>Jehovah's Witness</option><option>UCCP</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="addReligionOtherWrap">
                                <input type="text" class="form-control mt-1" name="religion_other"
                                       maxlength="100" placeholder="Type your religion">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Ethnicity / IP Group</label>
                            <select class="form-control" name="ethnicity" id="addEthnicity">
                                <option value="">Select</option>
                                <option>Visayan</option><option>Cebuano</option>
                                <option>Mamanwa (IP)</option><option>Higaonon (IP)</option>
                                <option>Manobo (IP)</option><option>Maranao</option>
                                <option>Tagalog</option><option>Ilocano</option>
                                <option>Bisaya</option><option>Waray</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="addEthnicityOtherWrap">
                                <input type="text" class="form-control mt-1" name="ethnicity_other"
                                       maxlength="100" placeholder="Type ethnicity/group">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">PhilHealth (PHIC) No.</label>
                            <input type="text" class="form-control" name="philhealth_no"
                                   maxlength="30" placeholder="18-000000000-0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">PhilHealth Membership</label>
                            <select class="form-control" name="membership_type">
                                <option value="">None / Not a member</option>
                                <option value="Private">Private (Employed)</option>
                                <option value="Government">Government Employee</option>
                                <option value="NHTS">NHTS (Indigent)</option>
                                <option value="Senior Citizen">Senior Citizen</option>
                                <option value="OFW">OFW</option>
                                <option value="Self-employed">Self-employed</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Height (cm)</label>
                            <input type="number" class="form-control" name="height" step="0.01" min="0" max="300" placeholder="e.g. 165">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" name="weight" step="0.01" min="0" max="500" placeholder="e.g. 60">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Residency (yrs)</label>
                            <input type="number" class="form-control" name="length_of_residency"
                                   min="0" max="120" placeholder="e.g. 10">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">NHTS?</label>
                            <select class="form-control" name="is_nhts">
                                <option value="No">No</option><option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">4Ps?</label>
                            <select class="form-control" name="is_4ps">
                                <option value="No">No</option><option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Solo Parent?</label>
                            <select class="form-control" name="is_solo_parent">
                                <option value="No">No</option>
                                <option value="Yes">Yes – Single Mother / Single Father</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Family Planning?</label>
                            <select class="form-control" name="family_planning">
                                <option value="No">No</option><option value="Yes">Yes</option>
                            </select>
                        </div>
                    </div>

                    <!-- ── ADDRESS ──────────────────────────────────────── -->
                    <div class="section-hdr mt-1"><i class="fas fa-map-marker-alt"></i> Address Information</div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">HH No.</label>
                            <input type="text" class="form-control" name="household_no"
                                   maxlength="20" placeholder="e.g. 001">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label req">Purok</label>
                            <select class="form-control" name="purok" required>
                                <option value="">Select</option>
                                <?php for($i=1;$i<=10;$i++): ?>
                                <option value="Purok <?= $i ?>">Purok <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label req">Barangay</label>
                            <select class="form-control" name="barangay" required>
                                <option value="">Select</option>
                                <option value="Buhang">Buhang</option>
                                <option value="Caloc-an">Caloc-an</option>
                                <option value="Guiasan">Guiasan</option>
                                <option value="Marcos">Marcos</option>
                                <option value="Poblacion">Poblacion</option>
                                <option value="Santo Niño">Santo Niño</option>
                                <option value="Santo Rosario" selected>Santo Rosario</option>
                                <option value="Taod-oy">Taod-oy</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Municipality</label>
                            <input type="text" class="form-control" name="municipality" value="Magallanes">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Province</label>
                            <input type="text" class="form-control" name="province" value="Agusan Del Norte">
                        </div>
                    </div>

                    <!-- ── HOUSING ───────────────────────────────────────── -->
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">House Ownership</label>
                            <select class="form-control" name="house_ownership" id="addHouseOwnership">
                                <option value="">Select</option>
                                <option value="Owned">Owned</option>
                                <option value="Rented">Rented</option>
                                <option value="Shared">Shared / With Relatives</option>
                                <option value="Government">Government-provided</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="addHouseOwnershipOtherWrap">
                                <input type="text" class="form-control mt-1" name="house_ownership_other"
                                       maxlength="100" placeholder="Specify ownership">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">House Material</label>
                            <select class="form-control" name="house_material" id="addHouseMaterial">
                                <option value="">Select</option>
                                <option value="Concrete">Concrete / Hollow Block</option>
                                <option value="Wood">Wood</option>
                                <option value="Mixed">Mixed (Concrete + Wood)</option>
                                <option value="Light Material">Light Material (Nipa/Bamboo)</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="addHouseMaterialOtherWrap">
                                <input type="text" class="form-control mt-1" name="house_material_other"
                                       maxlength="100" placeholder="Specify material">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Toilet Type</label>
                            <select class="form-control" name="toilet_type" id="addToiletType">
                                <option value="">Select</option>
                                <option value="With Flush">With Flush (Water Sealed)</option>
                                <option value="Without Flush">Without Flush (Pit Latrine)</option>
                                <option value="Shared">Shared / Communal</option>
                                <option value="None">None</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="addToiletTypeOtherWrap">
                                <input type="text" class="form-control mt-1" name="toilet_type_other"
                                       maxlength="100" placeholder="Specify toilet type">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Water Source</label>
                            <select class="form-control" name="water_source" id="addWaterSource">
                                <option value="">Select</option>
                                <option value="Level 3 (Piped)">Level 3 – Piped</option>
                                <option value="Level 2 (Communal Faucet)">Level 2 – Communal Faucet</option>
                                <option value="Level 1 (Deep Well)">Level 1 – Deep Well</option>
                                <option value="Rainwater">Rainwater Collection</option>
                                <option value="Spring">Spring / River</option>
                                <option value="Bottled Water">Bottled / Purchased</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="addWaterSourceOtherWrap">
                                <input type="text" class="form-control mt-1" name="water_source_other"
                                       maxlength="100" placeholder="Specify water source">
                            </div>
                        </div>
                    </div>

                    <!-- ── OCCUPATION & INCOME ──────────────────────────── -->
                    <div class="section-hdr mt-1"><i class="fas fa-briefcase"></i> Occupation & Income</div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label req">Occupation Type</label>
                            <select class="form-control" name="occupation_type" id="addOccupationType" required>
                                <option value="">Select</option>
                                <option value="Employed">Employed (Private)</option>
                                <option value="Government Employee">Government Employee</option>
                                <option value="Self-employed">Self-employed</option>
                                <option value="Farmer">Farmer / Fisherfolk</option>
                                <option value="OFW">OFW / Overseas Worker</option>
                                <option value="Student">Student</option>
                                <option value="Unemployed">Unemployed</option>
                                <option value="Retired">Retired</option>
                                <option value="Homemaker">Homemaker</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="addOccupationTypeOtherWrap">
                                <input type="text" class="form-control mt-1" name="occupation_type_other"
                                       maxlength="100" placeholder="Specify occupation type">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Occupation / Job Title</label>
                            <input type="text" class="form-control" name="occupation"
                                   maxlength="100" placeholder="e.g. Teacher, Farmer — leave blank if none">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Household Position <span class="text-danger">*</span></label>
                            <select class="form-control" name="household_position" required>
                                <option value="">Select Position</option>
                                <option value="Head">Head of Family</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                                <option value="Grandson">Grandson</option>
                                <option value="Granddaughter">Granddaughter</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Brother">Brother</option>
                                <option value="Sister">Sister</option>
                                <option value="Uncle">Uncle</option>
                                <option value="Aunt">Aunt</option>
                                <option value="Nephew">Nephew</option>
                                <option value="Niece">Niece</option>
                                <option value="Cousin">Cousin</option>
                                <option value="Son-in-law">Son-in-law</option>
                                <option value="Daughter-in-law">Daughter-in-law</option>
                                <option value="Brother-in-law">Brother-in-law</option>
                                <option value="Sister-in-law">Sister-in-law</option>
                                <option value="Grandparent">Grandparent</option>
                                <option value="Other">Other Relative</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Monthly Income (₱)</label>
                            <input type="number" class="form-control" name="monthly_income" id="addMonthlyIncome"
                                   step="0.01" min="0" placeholder="0.00"
                                   oninput="addComputeIncome()">
                            <small class="text-muted" style="font-size:10px">Annual income &amp; SES auto-computed</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Annual Income (₱) <small class="text-muted fw-normal">(auto)</small></label>
                            <input type="number" class="form-control bg-light" name="annual_income"
                                   id="addAnnualIncome" readonly placeholder="Auto-computed">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Socioeconomic Status <small class="text-muted fw-normal">(auto)</small></label>
                            <input type="text" class="form-control bg-light" id="addSESDisplay"
                                   readonly placeholder="Based on monthly income">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Total Household</label>
                            <input type="number" class="form-control" name="total_household"
                                   value="1" min="1" max="50">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Father's Name</label>
                            <input type="text" class="form-control" name="father_name" maxlength="200" placeholder="e.g. Juan Dela Cruz">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Father's Occupation</label>
                            <input type="text" class="form-control" name="father_occupation" maxlength="200" placeholder="e.g. Farmer, Driver">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Mother's Name</label>
                            <input type="text" class="form-control" name="mother_name" maxlength="200" placeholder="e.g. Maria Dela Cruz">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Mother's Occupation</label>
                            <input type="text" class="form-control" name="mother_occupation" maxlength="200" placeholder="e.g. Housewife, Teacher">
                        </div>
                    </div>

                    <!-- ── VOTER & EDUCATION ────────────────────────────── -->
                    <div class="section-hdr mt-1"><i class="fas fa-graduation-cap"></i> Voter & Education</div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label req">Voter Status</label>
                            <select class="form-control" name="voters_status" required>
                                <option value="No">Not Registered</option>
                                <option value="Yes">Registered Voter</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Educational Attainment</label>
                            <select class="form-control" name="educational_attainment" id="addEduAttain"
                                    onchange="addToggleEduFields()">
                                <option value="">Select</option>
                                <option value="No Formal Education">No Formal Education</option>
                                <option value="Elementary">Elementary</option>
                                <option value="High School">High School</option>
                                <option value="Senior High School">Senior High School</option>
                                <option value="College">College</option>
                                <option value="Vocational">Vocational / Tech-Voc</option>
                                <option value="Post Graduate">Post Graduate</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3" id="addGradeLevelWrap">
                            <label class="form-label">Grade / Year Level</label>
                            <input type="text" class="form-control" name="grade_level"
                                   maxlength="50" placeholder="e.g. Grade 7, 2nd Year">
                        </div>
                        <div class="col-md-3 mb-3" id="addSchoolNameWrap">
                            <label class="form-label">School Name</label>
                            <input type="text" class="form-control" name="school_name"
                                   maxlength="150" placeholder="e.g. Magallanes NHS">
                        </div>
                    </div>
                    <!-- Graduate sub-fields (shown when College/Vocational/Post Graduate) -->
                    <div class="row d-none" id="addGraduateFields">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Course / Program</label>
                            <select class="form-control" name="course" id="addCourse"
                                    onchange="addToggleCourseOther()">
                                <option value="">Select Course</option>
                                <option value="BS Information Technology">BS Information Technology</option>
                                <option value="BS Computer Science">BS Computer Science</option>
                                <option value="BS Education">BS Education</option>
                                <option value="BS Nursing">BS Nursing</option>
                                <option value="BS Accountancy">BS Accountancy</option>
                                <option value="BS Business Administration">BS Business Administration</option>
                                <option value="BS Criminology">BS Criminology</option>
                                <option value="BS Civil Engineering">BS Civil Engineering</option>
                                <option value="BS Agriculture">BS Agriculture</option>
                                <option value="BS Social Work">BS Social Work</option>
                                <option value="BS Psychology">BS Psychology</option>
                                <option value="Associate">Associate Degree</option>
                                <option value="TESDA NC II">TESDA NC II</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <div class="d-none mt-1" id="addCourseOtherWrap">
                                <input type="text" class="form-control" name="course_other"
                                       maxlength="150" placeholder="Specify course/program"
                                       style="border-left:3px solid #0e9f6e!important">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Graduation Date</label>
                            <input type="date" class="form-control" name="graduation_date">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Eligibility</label>
                            <select class="form-control" name="eligibility" id="addEligibility"
                                    onchange="addToggleEligOther()">
                                <option value="">None / Not Applicable</option>
                                <option value="Civil Service Professional">Civil Service Professional</option>
                                <option value="Civil Service Sub-Professional">Civil Service Sub-Professional</option>
                                <option value="PRC License">PRC Licensed</option>
                                <option value="Bar">Bar Passer</option>
                                <option value="Board Passer">Board Passer</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <div class="d-none mt-1" id="addEligOtherWrap">
                                <input type="text" class="form-control" name="eligibility_other"
                                       maxlength="150" placeholder="Specify eligibility"
                                       style="border-left:3px solid #0e9f6e!important">
                            </div>
                        </div>
                    </div>

                    <!-- ── SPECIAL STATUS ────────────────────────────────── -->
                    <div class="section-hdr mt-1" style="background:#fef9ec;border-left-color:#c8963e;color:#92400e">
                        <i class="fas fa-star-of-life"></i> Special Status
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label req">PWD?</label>
                            <select class="form-control" name="is_pwd" id="addIsPwd" required>
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Newborn?</label>
                            <select class="form-control" name="is_newborn_select" id="addIsNewbornSel">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Deceased?</label>
                            <select class="form-control" name="is_deceased" id="addIsDeceased">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 d-none" id="addDeathDateDiv">
                            <label class="form-label req">Date of Death</label>
                            <input type="date" class="form-control" name="date_of_death" id="addDateOfDeath">
                        </div>
                    </div>

                    <!-- PWD CARD-PICKER -->
                    <div class="d-none mb-3" id="addPwdSection">
                        <div class="pwd-box">
                            <label class="form-label mb-2">
                                ⚕️ Type of Disability <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2" id="addPwdCards">
                                <?php
                                $pwdTypes = [
                                    ['Visual Impairment',   'fa-eye-slash'],
                                    ['Hearing Impairment',  'fa-deaf'],
                                    ['Physical Disability', 'fa-wheelchair'],
                                    ['Intellectual',        'fa-brain'],
                                    ['Psychosocial',        'fa-head-side-virus'],
                                    ['Speech Impairment',   'fa-comment-slash'],
                                    ['Other',               'fa-ellipsis-h'],
                                ];
                                foreach ($pwdTypes as [$label, $icon]):
                                ?>
                                <div class="col-6 col-md-3">
                                    <label class="pwd-card-label w-100">
                                        <!-- FIX: radio stays named "pwd_type" always; value never mutated -->
                                        <input type="radio" name="pwd_type" value="<?= htmlspecialchars($label) ?>"
                                               class="d-none pwd-type-radio-add">
                                        <div class="pwd-card">
                                            <i class="fas <?= $icon ?> mb-1 d-block"></i>
                                            <span><?= htmlspecialchars($label) ?></span>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- "Other" free-text input -->
                            <div class="d-none mt-2" id="addPwdOtherWrap">
                                <input type="text" class="form-control" id="addPwdOtherText"
                                       maxlength="200" placeholder="Please describe the disability…">
                            </div>

                            <!-- FIX: This hidden field carries the final resolved value for "Other".
                                 On submit, if "Other" is selected, this gets renamed to pwd_type
                                 and the radio gets disabled, so only one pwd_type is sent. -->
                            <input type="hidden" name="pwd_type_resolved" id="addPwdTypeResolved" value="">

                            <!-- FIX: Error element now exists in DOM (was missing in original) -->
                            <div class="field-hint mt-1" id="addPwdErr"></div>
                        </div>
                    </div>

                    <!-- ── HEALTH PROFILE ────────────────────────────────── -->
                    <div class="section-hdr mt-1" style="background:#fff3f3;border-left-color:#e02424;color:#7f1d1d">
                        <i class="fas fa-heartbeat"></i> Health Profile
                    </div>
                    <div class="row g-2">
                        <?php
                        $healthFields = [
                            ['is_smoker',        'Smoker?'],
                            ['is_binge_drinker', 'Binge Drinker?'],
                            ['has_hypertension', 'Hypertension (HPN)?'],
                            ['has_diabetes',     'Diabetes (DM)?'],
                            ['has_asthma',       'Asthma?'],
                            ['has_tb',           'Tuberculosis (TB)?'],
                            ['has_cancer',       'Cancer?'],
                            ['has_mental_health','Mental Health?'],
                        ];
                        foreach($healthFields as [$fname, $flabel]):
                        ?>
                        <div class="col-6 col-md-3 mb-2">
                            <label class="form-label" style="font-size:11px;font-weight:600"><?= $flabel ?></label>
                            <select class="form-control form-control-sm" name="<?= $fname ?>">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div><!-- /modal-body -->

                <div class="modal-footer" style="background:#f8fafc">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="submit" class="btn btn-success" id="addResidentSubmitBtn">
                        <i class="fas fa-save"></i>
                        <span id="addResidentSubmitText">Save Resident</span>
                        <span class="spinner-border spinner-border-sm d-none" id="addResidentSpinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CAMERA MODAL -->
<div class="modal fade" id="addCameraModal" tabindex="-1" data-bs-backdrop="static"
     style="z-index:1070 !important;">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f3c6e;color:#fff;padding:10px 16px;">
                <h6 class="modal-title mb-0"><i class="fas fa-camera me-2"></i> Capture Photo</h6>
                <button type="button" class="btn-close btn-close-white" id="addCloseCameraBtn"></button>
            </div>
            <div class="modal-body" style="padding:16px;background:#1a1a2e;">
                <div id="addCamError" style="display:none;color:#f8d7da;text-align:center;padding:24px 10px;">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                    <span id="addCamErrorMsg">Camera not available.</span>
                </div>
                <div id="addCamVideoWrap" style="position:relative;">
                    <video id="addCamVideo" autoplay playsinline muted
                           style="width:100%;border-radius:4px;background:#000;display:block;"></video>
                    <button type="button" id="addCamSwitchBtn"
                            style="position:absolute;top:8px;right:8px;background:rgba(255,255,255,0.2);
                                   color:#fff;border:none;border-radius:50%;width:34px;height:34px;
                                   font-size:14px;cursor:pointer;display:flex;align-items:center;
                                   justify-content:center;">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <img id="addCamSnapshot" src="" alt="Snapshot"
                     style="display:none;width:100%;border-radius:4px;margin-top:10px;">
                <canvas id="addCamCanvas" style="display:none;"></canvas>
                <div style="display:flex;justify-content:center;gap:10px;margin-top:12px;flex-wrap:wrap;">
                    <button type="button" id="addCamCaptureBtn"
                            style="background:#0f3c6e;color:#fff;border:none;padding:9px 26px;border-radius:30px;font-weight:600;font-size:13px;cursor:pointer;">
                        <i class="fas fa-camera me-1"></i> Capture
                    </button>
                    <button type="button" id="addCamRetakeBtn"
                            style="display:none;background:#6c757d;color:#fff;border:none;padding:9px 18px;border-radius:30px;font-weight:500;font-size:13px;cursor:pointer;">
                        <i class="fas fa-redo me-1"></i> Retake
                    </button>
                    <button type="button" id="addCamUseBtn"
                            style="display:none;background:#28a745;color:#fff;border:none;padding:9px 18px;border-radius:30px;font-weight:500;font-size:13px;cursor:pointer;">
                        <i class="fas fa-check me-1"></i> Use Photo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── SHARED MODAL STYLES ─────────────────────────────────────────────────── -->
<style>
.section-hdr{background:#f0f4f8;border-left:4px solid #0f3c6e;color:#0f3c6e;font-weight:700;font-size:13px;padding:7px 14px;margin-bottom:14px;border-radius:3px;display:flex;align-items:center;gap:7px;}
.form-label.req::after{content:' *';color:#dc3545;}
.field-hint{font-size:11px;color:#dc3545;margin-top:2px;min-height:16px;}
.pwd-strength-bar{height:4px;background:#e9ecef;border-radius:3px;margin-top:4px;}
.pwd-strength-bar>div{height:100%;border-radius:3px;transition:all .3s;}
.other-wrap input{border-left:3px solid #0f3c6e !important;}
.pwd-box{background:#fff8e1;border:1px solid #ffc107;border-left:3px solid #f59e0b;padding:12px;border-radius:4px;}
.pwd-card-label{cursor:pointer;margin:0;}
.pwd-card{border:2px solid #e2e8f0;border-radius:8px;padding:10px 6px;text-align:center;font-size:11px;font-weight:600;color:#64748b;transition:all .15s;background:#fff;}
.pwd-card i{font-size:18px;color:#94a3b8;}
.pwd-card-active{border-color:#1a56db !important;background:#eff6ff !important;color:#1a56db !important;}
.pwd-card-active i{color:#1a56db !important;}
.pwd-card:hover{border-color:#94a3b8;}
.add-photo-wrapper{border:1.5px dashed #b0bec5;border-radius:4px;padding:6px;background:#f8f9fa;}
.add-photo-wrapper:hover{border-color:#1f6bb8;}
.add-photo-inner{display:flex;align-items:stretch;gap:6px;}
.add-photo-preview-box{width:72px;min-width:72px;height:72px;background:#e9ecef;border:1px solid #dee2e6;border-radius:3px;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;flex-shrink:0;}
.add-photo-placeholder{text-align:center;color:#adb5bd;padding:4px;}
.add-photo-placeholder i{font-size:20px;display:block;margin-bottom:2px;}
.add-photo-placeholder span{font-size:9px;display:block;line-height:1.2;}
.add-remove-photo{position:absolute;top:2px;right:2px;background:rgba(220,53,69,.85);color:#fff;border:none;border-radius:50%;width:18px;height:18px;font-size:9px;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;}
.add-photo-btn-group{display:flex;flex-direction:column;gap:4px;flex:1;justify-content:center;}
.add-photo-btn-group .btn{width:100%;text-align:left;white-space:nowrap;}
#addCameraModal{z-index:1070 !important;}
</style>

<!-- ── ADD MODAL SCRIPTS ───────────────────────────────────────────────────── -->
<script>
(function () {
'use strict';

/* ── LGBTQ+ toggle functions ── */
window.addToggleLgbtq = function () {
    var sel  = document.getElementById('addSexSelect');
    var wrap = document.getElementById('addLgbtqWrap');
    var ident = document.getElementById('addLgbtqIdentity');
    if (!sel || !wrap) return;
    if (sel.value === 'LGBTQ+') {
        wrap.classList.remove('d-none');
    } else {
        wrap.classList.add('d-none');
        if (ident) { ident.value = ''; }
        var ow = document.getElementById('addLgbtqOtherWrap');
        if (ow) ow.classList.add('d-none');
        var ot = document.getElementById('addLgbtqOtherText');
        if (ot) { ot.value = ''; }
    }
};
window.addToggleLgbtqOther = function () {
    var ident = document.getElementById('addLgbtqIdentity');
    var ow    = document.getElementById('addLgbtqOtherWrap');
    var ot    = document.getElementById('addLgbtqOtherText');
    if (!ident || !ow) return;
    if (ident.value === 'Other') {
        ow.classList.remove('d-none');
        if (ot) setTimeout(function(){ ot.focus(); }, 50);
    } else {
        ow.classList.add('d-none');
        if (ot) ot.value = '';
    }
};

/* ── Shared helper: toggle "Other" field ── */
if (typeof window.modalToggleOther !== 'function') {
    window.modalToggleOther = function (sel, wrapId) {
        var wrap = document.getElementById(wrapId); if (!wrap) return;
        var inp = wrap.querySelector('input');
        if (sel.value === 'Other') {
            wrap.classList.remove('d-none');
            if (inp) { inp.required = true; setTimeout(function(){ inp.focus(); }, 50); }
        } else {
            wrap.classList.add('d-none');
            if (inp) { inp.required = false; inp.value = ''; }
        }
    };
}

/* ── Education fields ── */
window.addToggleEduFields = function () {
    var val  = document.getElementById('addEduAttain').value;
    var show = ['Elementary','High School','Senior High School','College','Vocational','Post Graduate'].includes(val);
    document.getElementById('addGradeLevelWrap').style.opacity = show ? '1' : '0.4';
    document.getElementById('addSchoolNameWrap').style.opacity = show ? '1' : '0.4';
    // Graduate sub-fields
    var gradRow = document.getElementById('addGraduateFields');
    if (gradRow) {
        if (['College','Vocational','Post Graduate'].includes(val)) {
            gradRow.classList.remove('d-none');
        } else {
            gradRow.classList.add('d-none');
            var c = document.getElementById('addCourse'); if(c) c.value='';
            var co = document.getElementById('addCourseOtherWrap'); if(co) co.classList.add('d-none');
            var e = document.getElementById('addEligibility'); if(e) e.value='';
            var eo = document.getElementById('addEligOtherWrap'); if(eo) eo.classList.add('d-none');
        }
    }
};
window.addToggleCourseOther = function() {
    var sel = document.getElementById('addCourse');
    var wrap = document.getElementById('addCourseOtherWrap');
    if (!sel || !wrap) return;
    if (sel.value === 'Others') { wrap.classList.remove('d-none'); }
    else { wrap.classList.add('d-none'); var inp = wrap.querySelector('input'); if(inp) inp.value=''; }
};
window.addToggleEligOther = function() {
    var sel = document.getElementById('addEligibility');
    var wrap = document.getElementById('addEligOtherWrap');
    if (!sel || !wrap) return;
    if (sel.value === 'Others') { wrap.classList.remove('d-none'); }
    else { wrap.classList.add('d-none'); var inp = wrap.querySelector('input'); if(inp) inp.value=''; }
};

/* ── SES classifier ── */
var SES_BRACKETS = [
    [10957,'Poor'],[21914,'Low Income'],[43828,'Lower Middle Income'],
    [76669,'Middle Income'],[131484,'Upper Middle Income']
];
window.addComputeIncome = function () {
    var m = parseFloat(document.getElementById('addMonthlyIncome').value) || 0;
    document.getElementById('addAnnualIncome').value = m > 0 ? (m * 12).toFixed(2) : '';
    var ses = '';
    if (m > 0) {
        ses = 'High Income';
        for (var i = 0; i < SES_BRACKETS.length; i++) {
            if (m < SES_BRACKETS[i][0]) { ses = SES_BRACKETS[i][1]; break; }
        }
    }
    document.getElementById('addSESDisplay').value = ses;
};

/* ══════════════════════════════════════════════════════════════════════
   PWD TOGGLE
   Shows/hides the disability card picker when is_pwd changes.
   ══════════════════════════════════════════════════════════════════════ */
window.addTogglePwd = function () {
    var isPwdYes = document.getElementById('addIsPwd').value === 'Yes';
    var sec = document.getElementById('addPwdSection');
    if (isPwdYes) {
        sec.classList.remove('d-none');
    } else {
        sec.classList.add('d-none');
        _addResetPwdCards();
    }
};

function _addResetPwdCards() {
    document.querySelectorAll('.pwd-type-radio-add').forEach(function (r) {
        r.checked = false;
        r.disabled = false;
        r.setAttribute('name', 'pwd_type');
        var card = r.closest('.pwd-card-label') && r.closest('.pwd-card-label').querySelector('.pwd-card');
        if (card) card.classList.remove('pwd-card-active');
    });
    document.getElementById('addPwdOtherWrap').classList.add('d-none');
    var ot = document.getElementById('addPwdOtherText');
    if (ot) { ot.value = ''; ot.required = false; }
    document.getElementById('addPwdTypeResolved').value = '';
    document.getElementById('addPwdTypeResolved').name  = 'pwd_type_resolved';
    document.getElementById('addPwdErr').textContent = '';
}

/* ── PWD card selection ── */
window.addPwdTypeChange = function (radio) {
    // Deactivate all cards
    document.querySelectorAll('.pwd-type-radio-add').forEach(function (r) {
        var card = r.closest('.pwd-card-label').querySelector('.pwd-card');
        card.classList.remove('pwd-card-active');
    });
    // Activate chosen card
    radio.closest('.pwd-card-label').querySelector('.pwd-card').classList.add('pwd-card-active');

    var otherWrap  = document.getElementById('addPwdOtherWrap');
    var otherInput = document.getElementById('addPwdOtherText');

    if (radio.value === 'Other') {
        otherWrap.classList.remove('d-none');
        otherInput.required = true;
        otherInput.focus();
    } else {
        otherWrap.classList.add('d-none');
        otherInput.required = false;
        otherInput.value = '';
    }
    document.getElementById('addPwdErr').textContent = '';
};

/* ── Deceased toggle ── */
window.addToggleDeceased = function () {
    var div = document.getElementById('addDeathDateDiv');
    var dd  = document.getElementById('addDateOfDeath');
    if (document.getElementById('addIsDeceased').value === 'Yes') {
        div.classList.remove('d-none'); dd.required = true;
    } else {
        div.classList.add('d-none'); dd.required = false; dd.value = '';
    }
};
document.getElementById('addDateOfDeath').max = new Date().toISOString().split('T')[0];

/* ── Password toggles ── */
document.getElementById('addTogglePwd').addEventListener('click', function () {
    var p = document.getElementById('addPassword'), i = document.getElementById('addPwdIcon');
    p.type = p.type === 'password' ? 'text' : 'password';
    i.classList.toggle('fa-eye'); i.classList.toggle('fa-eye-slash');
});
document.getElementById('addToggleConfirm').addEventListener('click', function () {
    var p = document.getElementById('addConfirmPwd'), i = document.getElementById('addConfirmIcon');
    p.type = p.type === 'password' ? 'text' : 'password';
    i.classList.toggle('fa-eye'); i.classList.toggle('fa-eye-slash');
});

/* ── Password strength ── */
document.getElementById('addPassword').addEventListener('input', function () {
    var v = this.value, bar = document.getElementById('addPwdBar'), hint = document.getElementById('addPwdHint');
    if (!v) { bar.style.width='0'; hint.textContent='Min 6 characters'; hint.style.color=''; return; }
    var s = 0;
    if (v.length>=8) s++; if (v.length>=12) s++;
    if (/[a-z]/.test(v)&&/[A-Z]/.test(v)) s++; if (/[0-9]/.test(v)) s++; if (/[^a-zA-Z0-9]/.test(v)) s++;
    var m = s<=2?['33%','#dc3545','Weak']:s<=3?['66%','#ffc107','Medium']:['100%','#28a745','Strong'];
    bar.style.cssText='width:'+m[0]+';background:'+m[1]; hint.textContent=m[2]; hint.style.color=m[1];
    if (document.getElementById('addConfirmPwd').value) addCheckConfirm();
});
function addCheckConfirm() {
    var pwd=document.getElementById('addPassword').value, cpwd=document.getElementById('addConfirmPwd').value;
    document.getElementById('addConfirmErr').textContent = (cpwd && pwd!==cpwd) ? 'Passwords do not match' : '';
}
document.getElementById('addConfirmPwd').addEventListener('input', addCheckConfirm);

/* ── Username availability check ── */
var addUInput  = document.getElementById('addUsername');
var addUSpin   = document.getElementById('addUSpinner');
var addUChk    = document.getElementById('addUCheck');
var addUXIcon  = document.getElementById('addUX');
var addUErrEl  = document.getElementById('addUErr');
var addUTimer  = null;
var addUValid  = false;

function _addUReset(){ [addUSpin,addUChk,addUXIcon].forEach(function(e){ e.classList.add('d-none'); }); }

addUInput.addEventListener('input', function () {
    var v=this.value.trim(); clearTimeout(addUTimer); _addUReset(); addUValid=false; addUErrEl.textContent='';
    if (!v) return;
    if (v.length<4){ addUErrEl.textContent='Min 4 characters'; addUXIcon.classList.remove('d-none'); return; }
    if (v.length>20){ addUErrEl.textContent='Max 20 characters'; addUXIcon.classList.remove('d-none'); return; }
    if (!/^[a-zA-Z0-9_]+$/.test(v)){ addUErrEl.textContent='Letters, numbers, underscore only'; addUXIcon.classList.remove('d-none'); return; }
    addUSpin.classList.remove('d-none');
    addUTimer=setTimeout(function(){
        if(typeof $==='undefined'){ addUSpin.classList.add('d-none'); addUValid=true; addUChk.classList.remove('d-none'); return; }
        $.ajax({ url:'../resident/check_username.php', method:'POST', data:{username:v}, dataType:'json', timeout:4000,
            success:function(r){ addUSpin.classList.add('d-none'); if(r.available){ addUChk.classList.remove('d-none'); addUErrEl.textContent=''; addUValid=true; }else{ addUXIcon.classList.remove('d-none'); addUErrEl.textContent='Username already taken'; addUValid=false; } },
            error:function(){ addUSpin.classList.add('d-none'); addUXIcon.classList.remove('d-none'); addUErrEl.textContent='Could not verify — please try again'; addUValid=false; }
        });
    }, 300);
});

/* ── Birthdate → Age ── */
document.getElementById('addBirthdate').max = new Date().toISOString().split('T')[0];
document.getElementById('addBirthdate').addEventListener('change', function () {
    var v=this.value; if (!v){ document.getElementById('addAge').value=''; document.getElementById('addIsNewborn').value='No'; return; }
    var bd=new Date(v), today=new Date();
    if (bd>today){ alert('Birthdate cannot be in the future!'); this.value=''; document.getElementById('addAge').value=''; return; }
    var age=today.getFullYear()-bd.getFullYear(), md=today.getMonth()-bd.getMonth();
    if (md<0||(md===0&&today.getDate()<bd.getDate())) age--;
    document.getElementById('addAge').value = age>=0 ? age : 0;
    var isNewborn = age===0 ? 'Yes' : 'No';
    document.getElementById('addIsNewborn').value = isNewborn;
    var nb2 = document.getElementById('addIsNewbornSel'); if (nb2) nb2.value = isNewborn;
});

/* ── Photo ── */
var addPrevImg    = document.getElementById('addPhotoPreviewImg');
var addPlaceholder= document.getElementById('addPhotoPlaceholder');
var addRemoveBtn  = document.getElementById('addRemovePhotoBtn');
var addImgInput   = document.getElementById('addImage');
var addCamImgInput= document.getElementById('addCameraImage');
var addImgErr     = document.getElementById('addImgErr');

function addShowPreview(src){ addPrevImg.src=src; addPrevImg.style.display='block'; addPlaceholder.style.display='none'; addRemoveBtn.classList.remove('d-none'); addImgErr.textContent=''; }
function addClearPreview(){ addPrevImg.src=''; addPrevImg.style.display='none'; addPlaceholder.style.display='flex'; addRemoveBtn.classList.add('d-none'); }
addRemoveBtn.addEventListener('click', function(){ addImgInput.value=''; addCamImgInput.value=''; addClearPreview(); });
addImgInput.addEventListener('change', function(){
    var f=this.files[0]; addCamImgInput.value='';
    if (!f){ addClearPreview(); return; }
    if (!['image/jpeg','image/jpg','image/png','image/gif'].includes(f.type)){ addImgErr.textContent='Only JPG/PNG/GIF'; this.value=''; return; }
    if (f.size>2*1024*1024){ addImgErr.textContent='Max 2 MB'; this.value=''; return; }
    var reader=new FileReader(); reader.onload=function(e){ addShowPreview(e.target.result); }; reader.readAsDataURL(f);
});

/* ══════════════════════════════════════════════════════════════════════
   FORM SUBMIT
   PWD "Other" strategy:
     - If "Other" card is selected, disable the radio (stops it from submitting)
       and rename the hidden field to "pwd_type" with the free-text value.
     - If a non-Other card is selected, the radio submits normally.
     - On reset (modal close), we re-enable radios and restore their names.
   ══════════════════════════════════════════════════════════════════════ */
document.getElementById('addResidentForm').addEventListener('submit', function (e) {

    /* Username */
    if (!addUValid){ e.preventDefault(); alert('Please enter a valid available username.'); addUInput.focus(); return false; }

    /* Password */
    var pwd = document.getElementById('addPassword').value;
    if (pwd.length<6){ e.preventDefault(); alert('Password must be at least 6 characters.'); return false; }
    if (pwd !== document.getElementById('addConfirmPwd').value){ e.preventDefault(); alert('Passwords do not match!'); document.getElementById('addConfirmPwd').focus(); return false; }

    /* Photo */
    var hasCam=addCamImgInput.value.trim().length>0, hasFile=addImgInput.files&&addImgInput.files.length>0;
    if (!hasCam&&!hasFile){ e.preventDefault(); addImgErr.textContent='Please upload or capture a photo.'; addImgErr.scrollIntoView({behavior:'smooth',block:'center'}); return false; }

    /* PWD type validation + submit fix */
    if (document.getElementById('addIsPwd').value === 'Yes') {
        var checkedRadio = document.querySelector('.pwd-type-radio-add:checked');

        if (!checkedRadio) {
            e.preventDefault();
            document.getElementById('addPwdErr').textContent = 'Please select a disability type.';
            document.getElementById('addPwdSection').scrollIntoView({behavior:'smooth', block:'center'});
            return false;
        }

        if (checkedRadio.value === 'Other') {
            var otherText = document.getElementById('addPwdOtherText').value.trim();
            if (!otherText) {
                e.preventDefault();
                document.getElementById('addPwdErr').textContent = 'Please describe the disability.';
                document.getElementById('addPwdOtherText').focus();
                return false;
            }
            // FIX: Disable the radio (removes it from form data) and submit
            // via the hidden field renamed to "pwd_type"
            checkedRadio.disabled = true;
            var hiddenField = document.getElementById('addPwdTypeResolved');
            hiddenField.name  = 'pwd_type';
            hiddenField.value = otherText;
        }
        // Non-"Other": radio with name="pwd_type" submits its value automatically — nothing to do.
    }

    /* Other-specify fields validation */
    var otherPairs = [
        ['addReligion',      'religion_other'],
        ['addEthnicity',     'ethnicity_other'],
        ['addBloodType',     'blood_type_other'],
        ['addHouseOwnership','house_ownership_other'],
        ['addHouseMaterial', 'house_material_other'],
        ['addToiletType',    'toilet_type_other'],
        ['addWaterSource',   'water_source_other'],
        ['addOccupationType','occupation_type_other']
    ];
    for (var pi=0; pi<otherPairs.length; pi++) {
        var sel = document.getElementById(otherPairs[pi][0]);
        if (sel && sel.value === 'Other') {
            var inp = document.querySelector('#addResidentModal input[name="'+otherPairs[pi][1]+'"]');
            if (inp && !inp.value.trim()) {
                e.preventDefault();
                alert('Please specify the "'+otherPairs[pi][1].replace('_other','').replace(/_/g,' ')+'" field.');
                inp.focus(); return false;
            }
        }
    }

    /* Spinner */
    document.getElementById('addResidentSubmitBtn').disabled = true;
    document.getElementById('addResidentSubmitText').classList.add('d-none');
    document.getElementById('addResidentSpinner').classList.remove('d-none');
});

/* ── Reset on modal open (ensure clean state) ── */
document.getElementById('addResidentModal').addEventListener('shown.bs.modal', function () {
    document.getElementById('addIsPwd').value = 'No';
    window.addTogglePwd();
});

/* ── Reset on modal close ── */
document.getElementById('addResidentModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('addResidentForm').reset();
    document.getElementById('addPwdSection').classList.add('d-none');
    document.getElementById('addDeathDateDiv').classList.add('d-none');
    document.getElementById('addDateOfDeath').required = false;
    document.getElementById('addAge').value = '';
    document.getElementById('addAnnualIncome').value = '';
    document.getElementById('addSESDisplay').value = '';
    document.getElementById('addPassword').type = 'password';
    document.getElementById('addPwdIcon').className = 'fas fa-eye';
    document.getElementById('addConfirmPwd').type = 'password';
    document.getElementById('addConfirmIcon').className = 'fas fa-eye';
    document.getElementById('addPwdBar').style.cssText = '';
    document.getElementById('addPwdHint').textContent = 'Min 6 characters';
    document.getElementById('addPwdHint').style.color = '';
    document.getElementById('addConfirmErr').textContent = '';
    _addUReset(); addUErrEl.textContent = ''; addUValid = false;
    addClearPreview(); addImgErr.textContent = '';
    document.getElementById('addResidentSubmitBtn').disabled = false;
    document.getElementById('addResidentSubmitText').classList.remove('d-none');
    document.getElementById('addResidentSpinner').classList.add('d-none');
    // FIX: Full PWD reset including re-enabling disabled radios
    _addResetPwdCards();
    document.querySelectorAll('#addResidentModal .other-wrap').forEach(function (w) {
        w.classList.add('d-none');
        var i = w.querySelector('input'); if (i) { i.required = false; i.value = ''; }
    });
    window.addToggleEduFields();
    addCamStop();
});

/* ── Camera ── */
function getAddCamModal() {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById('addCameraModal'), { backdrop: true, keyboard: true });
}
var addCamVideo    = document.getElementById('addCamVideo');
var addCamCanvas   = document.getElementById('addCamCanvas');
var addCamSnapshot = document.getElementById('addCamSnapshot');
var addCamVidWrap  = document.getElementById('addCamVideoWrap');
var addCamErrDiv   = document.getElementById('addCamError');
var addCamErrMsg   = document.getElementById('addCamErrorMsg');
var addCamCapBtn   = document.getElementById('addCamCaptureBtn');
var addCamRetBtn   = document.getElementById('addCamRetakeBtn');
var addCamUseBtn   = document.getElementById('addCamUseBtn');
var addCamSwBtn    = document.getElementById('addCamSwitchBtn');
var addCamModalEl  = document.getElementById('addCameraModal');
var addCamStream   = null, addCamFacing='user', addCamCaptured=null;

document.getElementById('addOpenCameraBtn').addEventListener('click', function(){ addCamReset(); getAddCamModal().show(); addCamStart(); });
document.getElementById('addCloseCameraBtn').addEventListener('click', function(){ getAddCamModal().hide(); });
addCamModalEl.addEventListener('hidden.bs.modal', function(){ addCamStop(); addCamReset(); document.getElementById('addResidentModal').focus(); });
addCamModalEl.addEventListener('shown.bs.modal', function(){ var b=document.querySelectorAll('.modal-backdrop'); if(b.length>=2) b[b.length-1].style.zIndex='1069'; });
addCamSwBtn.addEventListener('click', function(){ addCamFacing=addCamFacing==='user'?'environment':'user'; addCamStop(); addCamStart(); });
addCamCapBtn.addEventListener('click', function(){
    var w=addCamVideo.videoWidth||640, h=addCamVideo.videoHeight||480;
    addCamCanvas.width=w; addCamCanvas.height=h;
    var ctx=addCamCanvas.getContext('2d');
    if(addCamFacing==='user'){ ctx.translate(w,0); ctx.scale(-1,1); }
    ctx.drawImage(addCamVideo,0,0,w,h);
    addCamCaptured=addCamCanvas.toDataURL('image/jpeg',0.92);
    addCamSnapshot.src=addCamCaptured; addCamSnapshot.style.display='block';
    addCamVidWrap.style.display='none'; addCamCapBtn.style.display='none';
    addCamRetBtn.style.display='inline-block'; addCamUseBtn.style.display='inline-block';
});
addCamRetBtn.addEventListener('click', function(){
    addCamCaptured=null; addCamSnapshot.style.display='none'; addCamVidWrap.style.display='block';
    addCamCapBtn.style.display='inline-block'; addCamRetBtn.style.display='none'; addCamUseBtn.style.display='none';
    addCamStart();
});
addCamUseBtn.addEventListener('click', function(){
    if (!addCamCaptured) return;
    addCamImgInput.value=addCamCaptured; addImgInput.value='';
    addShowPreview(addCamCaptured); getAddCamModal().hide();
});
addCamVideo.addEventListener('loadedmetadata', function(){ this.style.transform=addCamFacing==='user'?'scaleX(-1)':'none'; });

function addCamStart(){
    addCamErrDiv.style.display='none'; addCamVidWrap.style.display='block';
    if (!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){ addCamShowErr('Your browser does not support camera access.'); return; }
    navigator.mediaDevices.getUserMedia({video:{facingMode:addCamFacing,width:{ideal:1280},height:{ideal:720}},audio:false})
    .then(function(stream){ addCamStream=stream; addCamVideo.srcObject=stream; addCamVideo.play(); })
    .catch(function(err){ var msg='Camera access denied.'; if(err.name==='NotFoundError') msg='No camera found.'; if(err.name==='NotReadableError') msg='Camera is in use.'; addCamShowErr(msg); });
}
function addCamStop(){ if(addCamStream){ addCamStream.getTracks().forEach(function(t){ t.stop(); }); addCamStream=null; } addCamVideo.srcObject=null; }
function addCamReset(){ addCamCaptured=null; addCamSnapshot.src=''; addCamSnapshot.style.display='none'; addCamVidWrap.style.display='block'; addCamCapBtn.style.display='inline-block'; addCamRetBtn.style.display='none'; addCamUseBtn.style.display='none'; addCamErrDiv.style.display='none'; }
function addCamShowErr(msg){ addCamVidWrap.style.display='none'; addCamErrDiv.style.display='block'; addCamErrMsg.textContent=msg; addCamCapBtn.style.display='none'; }

/* ══════════════════════════════════════════════════════════════════════
   ATTACH ALL onchange LISTENERS HERE (inside IIFE, after functions defined)
   This replaces inline onchange= attributes, which fire before this script
   executes and cause "is not a function" errors.
   ══════════════════════════════════════════════════════════════════════ */
function _bindChange(id, fn) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('change', fn);
}

/* PWD / deceased / edu toggles */
_bindChange('addIsPwd',      function()  { window.addTogglePwd(); });
_bindChange('addIsDeceased', function()  { window.addToggleDeceased(); });
_bindChange('addEduAttain',  function()  { window.addToggleEduFields(); });

/* "Other" dropdowns — modalToggleOther is safe to call; it is declared on window */
_bindChange('addBloodType',      function() { window.modalToggleOther(this,'addBloodTypeOtherWrap'); });
_bindChange('addReligion',       function() { window.modalToggleOther(this,'addReligionOtherWrap'); });
_bindChange('addEthnicity',      function() { window.modalToggleOther(this,'addEthnicityOtherWrap'); });
_bindChange('addHouseOwnership', function() { window.modalToggleOther(this,'addHouseOwnershipOtherWrap'); });
_bindChange('addHouseMaterial',  function() { window.modalToggleOther(this,'addHouseMaterialOtherWrap'); });
_bindChange('addToiletType',     function() { window.modalToggleOther(this,'addToiletTypeOtherWrap'); });
_bindChange('addWaterSource',    function() { window.modalToggleOther(this,'addWaterSourceOtherWrap'); });
_bindChange('addOccupationType', function() { window.modalToggleOther(this,'addOccupationTypeOtherWrap'); });

/* PWD card radios */
document.querySelectorAll('.pwd-type-radio-add').forEach(function(radio) {
    radio.addEventListener('change', function() { window.addPwdTypeChange(this); });
});

})();
</script>