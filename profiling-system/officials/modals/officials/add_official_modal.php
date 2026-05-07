<!-- ══════════════════════════════════════════════════════════════════
     ADD OFFICIAL MODAL — COLOR-CONSISTENT WITH ADD RESIDENT MODAL
     ══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="addOfficialModal" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width:1200px">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#0f3c6e">
                <h5 class="modal-title"><i class="fas fa-user-tie me-2"></i>Add New Barangay Official</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="add_officials.php" method="POST" enctype="multipart/form-data" id="addOfficialForm">
                <div class="modal-body" style="max-height:78vh;overflow-y:auto;padding:20px 24px">

                    <!-- ACCOUNT CREDENTIALS -->
                    <div class="off-section-hdr"><i class="fas fa-lock"></i> Account Credentials</div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label off-req">Username</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="username" id="offAddUsername"
                                       required minlength="4" maxlength="20" pattern="[a-zA-Z0-9_]+"
                                       placeholder="4–20 chars, letters/numbers/_" autocomplete="off">
                                <span class="input-group-text px-2">
                                    <i class="fas fa-circle-notch fa-spin text-muted d-none" id="offAddUSpinner"></i>
                                    <i class="fas fa-check text-success d-none" id="offAddUCheck"></i>
                                    <i class="fas fa-times text-danger d-none" id="offAddUX"></i>
                                </span>
                            </div>
                            <div class="off-field-hint" id="offAddUErr"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label off-req">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="offAddPassword"
                                       required minlength="6" maxlength="50" placeholder="Minimum 6 characters">
                                <button class="btn btn-outline-secondary" type="button" id="offAddTogglePwd">
                                    <i class="fas fa-eye" id="offAddPwdIcon"></i>
                                </button>
                            </div>
                            <div class="pwd-strength-bar"><div id="offAddPwdBar"></div></div>
                            <div class="off-field-hint" id="offAddPwdHint">Min 6 characters</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label off-req">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="confirm_password" id="offAddConfirmPwd"
                                       required minlength="6" maxlength="50" placeholder="Re-enter password">
                                <button class="btn btn-outline-secondary" type="button" id="offAddToggleConfirm">
                                    <i class="fas fa-eye" id="offAddConfirmIcon"></i>
                                </button>
                            </div>
                            <div class="off-field-hint" id="offAddConfirmErr"></div>
                        </div>
                    </div>

                    <!-- PHOTO UPLOAD -->
                    <div class="off-section-hdr mt-1"><i class="fas fa-camera"></i> Photo</div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Upload Photo <span class="text-danger">*</span></label>
                            <div class="off-photo-wrapper">
                                <div class="off-photo-inner">
                                    <div class="off-photo-preview-box" id="offAddPhotoPreviewBox">
                                        <div class="off-photo-placeholder" id="offAddPhotoPlaceholder">
                                            <i class="fas fa-user-tie"></i><span>No photo</span>
                                        </div>
                                        <img id="offAddPhotoPreviewImg" src="" alt=""
                                             style="display:none;width:100%;height:100%;object-fit:cover;">
                                        <button type="button" class="off-remove-photo d-none" id="offAddRemovePhotoBtn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="off-photo-btn-group">
                                        <label class="btn btn-sm btn-outline-secondary mb-0" style="cursor:pointer;font-size:11px;">
                                            <i class="fas fa-upload me-1"></i> Upload
                                            <input type="file" name="image" id="offAddImage"
                                                   accept="image/jpeg,image/jpg,image/png,image/gif" class="d-none">
                                        </label>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                id="offAddOpenCameraBtn" style="font-size:11px;">
                                            <i class="fas fa-camera me-1"></i> Camera
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="camera_image" id="offAddCameraImage" value="">
                            </div>
                            <small class="text-muted" style="font-size:10px;">Max 2MB · JPG PNG GIF</small>
                            <div class="off-field-hint" id="offAddImgErr"></div>
                        </div>
                    </div>

                    <!-- PERSONAL INFORMATION -->
                    <div class="off-section-hdr mt-1"><i class="fas fa-user"></i> Personal Information</div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label off-req">First Name</label>
                            <input type="text" class="form-control" name="first_name" required maxlength="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" maxlength="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label off-req">Surname</label>
                            <input type="text" class="form-control" name="surname" required maxlength="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Suffix</label>
                            <select class="form-control" name="suffix">
                                <option value="">None</option>
                                <option value="Jr.">Jr.</option><option value="Sr.">Sr.</option>
                                <option value="II">II</option><option value="III">III</option>
                                <option value="IV">IV</option><option value="V">V</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label off-req">Birthdate</label>
                            <input type="date" class="form-control" name="birthdate" id="offAddBirthdate"
                                   min="1900-01-01" required>
                            <small class="text-muted" style="font-size:10px">Use the calendar picker (YYYY-MM-DD)</small>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label off-req">Age</label>
                            <input type="number" class="form-control" name="age" id="offAddAge" readonly required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label off-req">Birthplace</label>
                            <input type="text" class="form-control" name="birthplace" required maxlength="255">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label off-req">Sex</label>
                            <select class="form-control" name="sex" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="LGBTQ+">LGBTQ+</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label off-req">Civil Status</label>
                            <select class="form-control" name="civil_status" required>
                                <option value="">Select</option>
                                <option>Single</option><option>Married</option><option>Widowed</option>
                                <option>Separated</option><option>Annulled</option><option>Live-in</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Nationality</label>
                            <input type="text" class="form-control" name="nationality" value="Filipino" maxlength="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Contact No.</label>
                            <input type="text" class="form-control" name="contact_no" placeholder="09XXXXXXXXX" maxlength="20">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" placeholder="example@gmail.com" maxlength="150">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Blood Type</label>
                            <select class="form-control" name="blood_type" id="offAddBloodType">
                                <option value="">Select</option>
                                <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                                <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
                                <option value="Unknown">Unknown</option><option value="Other">Other</option>
                            </select>
                            <div class="other-wrap d-none" id="offAddBloodTypeOtherWrap">
                                <input type="text" class="form-control mt-1" name="blood_type_other" maxlength="20" placeholder="Specify blood type">
                            </div>
                        </div>
                    </div>

                    <!-- POSITION & TERM -->
                    <div class="off-section-hdr mt-1">
                        <i class="fas fa-id-badge"></i> Position & Term Information
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label off-req">Position</label>
                            <select class="form-control" name="position" required>
                                <option value="">-- Select Position --</option>
                                <option>Barangay Captain</option><option>Barangay Kagawad</option>
                                <option>Sangguniang Barangay (SB) Member</option>
                                <option>SK Chairman</option><option>Barangay Secretary</option>
                                <option>Barangay Treasurer</option><option>Barangay Health Worker</option>
                                <option>Barangay Tanod</option><option>Lupon Member</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Chairmanship / Committee</label>
                            <input type="text" class="form-control" name="chairmanship" maxlength="255">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label off-req">Status</label>
                            <select class="form-control" name="status" required>
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="On Leave">On Leave</option>
                                <option value="Retired">Retired</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label off-req">Term Start</label>
                            <input type="date" class="form-control" name="term_start" id="offAddTermStart" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label off-req">Term End</label>
                            <input type="date" class="form-control" name="term_end" id="offAddTermEnd" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Years in Service</label>
                            <input type="number" class="form-control" name="years_in_service"
                                   id="offAddYearsService" min="0" max="50" readonly placeholder="Auto-calculated">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Voter Status</label>
                            <select class="form-control" name="voters_status">
                                <option value="Yes">Registered Voter</option>
                                <option value="No">Not Registered</option>
                            </select>
                        </div>
                    </div>

                    <!-- ADDRESS -->
                    <div class="off-section-hdr mt-1"><i class="fas fa-map-marker-alt"></i> Address Information</div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">HH No.</label>
                            <input type="text" class="form-control" name="household_no" maxlength="50">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label off-req">Purok</label>
                            <select class="form-control" name="purok" required>
                                <option value="">Select</option>
                                <?php for($i=1;$i<=10;$i++): ?>
                                <option value="Purok <?= $i ?>">Purok <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label off-req">Barangay</label>
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
                            <input type="text" class="form-control" name="municipality" value="Magallanes" maxlength="100">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Province</label>
                            <input type="text" class="form-control" name="province" value="Agusan Del Norte" maxlength="100">
                        </div>
                    </div>

                    <!-- OCCUPATION & INCOME -->
                    <div class="off-section-hdr mt-1"><i class="fas fa-briefcase"></i> Occupation & Income</div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label off-req">Occupation Type</label>
                            <select class="form-control" name="occupation_type" id="offAddOccupationType" required>
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
                                <option value="Public Servant">Public Servant</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="offAddOccupationTypeOtherWrap">
                                <input type="text" class="form-control mt-1" name="occupation_type_other" maxlength="100" placeholder="Specify occupation type">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Occupation / Job Title</label>
                            <input type="text" class="form-control" name="occupation" maxlength="255">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label off-req">Household Position</label>
                            <select class="form-control" name="household_position" required>
                                <option value="">Select Position</option>
                                <option value="Head">Head of Family</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Son">Son</option><option value="Daughter">Daughter</option>
                                <option value="Grandson">Grandson</option><option value="Granddaughter">Granddaughter</option>
                                <option value="Father">Father</option><option value="Mother">Mother</option>
                                <option value="Brother">Brother</option><option value="Sister">Sister</option>
                                <option value="Uncle">Uncle</option><option value="Aunt">Aunt</option>
                                <option value="Nephew">Nephew</option><option value="Niece">Niece</option>
                                <option value="Cousin">Cousin</option>
                                <option value="Son-in-law">Son-in-law</option><option value="Daughter-in-law">Daughter-in-law</option>
                                <option value="Brother-in-law">Brother-in-law</option><option value="Sister-in-law">Sister-in-law</option>
                                <option value="Grandparent">Grandparent</option>
                                <option value="Other">Other Relative</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Monthly Income (₱)</label>
                            <input type="number" class="form-control" name="monthly_income" id="offAddMonthlyIncome"
                                   step="0.01" min="0" placeholder="0.00">
                            <small class="text-muted" style="font-size:10px">Annual income &amp; SES auto-computed</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Annual Income (₱) <small class="text-muted">(auto)</small></label>
                            <input type="number" class="form-control bg-light" name="annual_income"
                                   id="offAddAnnualIncome" readonly placeholder="Auto-computed">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Socioeconomic Status <small class="text-muted">(auto)</small></label>
                            <input type="text" class="form-control bg-light" id="offAddSESDisplay"
                                   name="socioeconomic_status_display" readonly placeholder="Based on monthly income">
                        </div>
                    </div>

                    <!-- HOUSEHOLD -->
                    <div class="off-section-hdr mt-1"><i class="fas fa-home"></i> Household Information</div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Total Household</label>
                            <input type="number" class="form-control" name="total_household" value="1" min="1" max="50">
                        </div>
                    </div>

                    <!-- EDUCATION -->
                    <div class="off-section-hdr mt-1"><i class="fas fa-graduation-cap"></i> Educational Attainment</div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label off-req">Educational Attainment</label>
                            <select class="form-control" name="educational_attainment" id="offAddEduAttain" required>
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
                        <div class="col-md-3 mb-3" id="offAddGradeLevelWrap">
                            <label class="form-label">Grade / Year Level</label>
                            <input type="text" class="form-control" name="grade_level" maxlength="50">
                        </div>
                        <div class="col-md-6 mb-3" id="offAddSchoolNameWrap">
                            <label class="form-label">School Name</label>
                            <input type="text" class="form-control" name="school_name" maxlength="255">
                        </div>
                    </div>
                    <!-- Graduate sub-fields (Course, Graduation Date, Eligibility) -->
                    <div class="row d-none" id="offAddGraduateFields">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Course / Program</label>
                            <select class="form-control" name="course" id="offAddCourse">
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
                                <option value="Associate">Associate</option>
                                <option value="TESDA NC II">TESDA NC II</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <div class="d-none mt-1" id="offAddCourseOtherWrap">
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
                            <select class="form-control" name="eligibility" id="offAddEligibility">
                                <option value="">None / Not Applicable</option>
                                <option value="Civil Service Professional">Civil Service Professional</option>
                                <option value="Civil Service Sub-Professional">Civil Service Sub-Professional</option>
                                <option value="PRC License">PRC License</option>
                                <option value="Bar">Bar</option>
                                <option value="Board Passer">Board Passer</option>
                                <option value="Others">Others (specify)</option>
                            </select>
                            <div class="d-none mt-1" id="offAddEligOtherWrap">
                                <input type="text" class="form-control" name="eligibility_other"
                                       maxlength="150" placeholder="Specify eligibility"
                                       style="border-left:3px solid #0e9f6e!important">
                            </div>
                        </div>
                    </div>

                    <!-- DEMOGRAPHIC -->
                    <div class="off-section-hdr mt-1">
                        <i class="fas fa-id-card"></i> Demographic Information
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Religion</label>
                            <select class="form-control" name="religion" id="offAddReligion">
                                <option value="">Select</option>
                                <option>Roman Catholic</option><option>Islam</option>
                                <option>Iglesia ni Cristo</option><option>Seventh-day Adventist</option>
                                <option>Born Again Christian</option><option>Baptist</option>
                                <option>Jehovah's Witness</option><option>UCCP</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="offAddReligionOtherWrap">
                                <input type="text" class="form-control mt-1" name="religion_other" maxlength="100" placeholder="Type your religion">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Ethnicity / IP Group</label>
                            <select class="form-control" name="ethnicity" id="offAddEthnicity">
                                <option value="">Select</option>
                                <option>Visayan</option><option>Cebuano</option>
                                <option>Mamanwa (IP)</option><option>Higaonon (IP)</option>
                                <option>Manobo (IP)</option><option>Maranao</option>
                                <option>Tagalog</option><option>Ilocano</option>
                                <option>Bisaya</option><option>Waray</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="offAddEthnicityOtherWrap">
                                <input type="text" class="form-control mt-1" name="ethnicity_other" maxlength="100" placeholder="Type ethnicity/group">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">PhilHealth (PHIC) No.</label>
                            <input type="text" class="form-control" name="philhealth_no" maxlength="50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">PhilHealth Membership</label>
                            <select class="form-control" name="membership_type">
                                <option value="">None / Not a member</option>
                                <option value="Government">Government Employee</option>
                                <option value="Private">Private (Employed)</option>
                                <option value="NHTS">NHTS (Indigent)</option>
                                <option value="Senior Citizen">Senior Citizen</option>
                                <option value="OFW">OFW</option>
                                <option value="Self-employed">Self-employed</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Residency (yrs)</label>
                            <input type="number" class="form-control" name="length_of_residency" min="0" max="120">
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

                    <!-- HOUSING -->
                    <div class="off-section-hdr mt-1"><i class="fas fa-house-user"></i> Housing Information</div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">House Ownership</label>
                            <select class="form-control" name="house_ownership" id="offAddHouseOwnership">
                                <option value="">Select</option>
                                <option value="Owned">Owned</option><option value="Rented">Rented</option>
                                <option value="Shared">Shared / With Relatives</option>
                                <option value="Government">Government-provided</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="offAddHouseOwnershipOtherWrap">
                                <input type="text" class="form-control mt-1" name="house_ownership_other" maxlength="100" placeholder="Specify ownership">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">House Material</label>
                            <select class="form-control" name="house_material" id="offAddHouseMaterial">
                                <option value="">Select</option>
                                <option value="Concrete">Concrete / Hollow Block</option><option value="Wood">Wood</option>
                                <option value="Mixed">Mixed (Concrete + Wood)</option>
                                <option value="Light Material">Light Material (Nipa/Bamboo)</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="offAddHouseMaterialOtherWrap">
                                <input type="text" class="form-control mt-1" name="house_material_other" maxlength="100" placeholder="Specify material">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Toilet Type</label>
                            <select class="form-control" name="toilet_type" id="offAddToiletType">
                                <option value="">Select</option>
                                <option value="With Flush">With Flush (Water Sealed)</option>
                                <option value="Without Flush">Without Flush (Pit Latrine)</option>
                                <option value="Shared">Shared / Communal</option>
                                <option value="None">None</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="offAddToiletTypeOtherWrap">
                                <input type="text" class="form-control mt-1" name="toilet_type_other" maxlength="100" placeholder="Specify toilet type">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Water Source</label>
                            <select class="form-control" name="water_source" id="offAddWaterSource">
                                <option value="">Select</option>
                                <option value="Level 3 (Piped)">Level 3 – Piped</option>
                                <option value="Level 2 (Communal Faucet)">Level 2 – Communal Faucet</option>
                                <option value="Level 1 (Deep Well)">Level 1 – Deep Well</option>
                                <option value="Rainwater">Rainwater Collection</option>
                                <option value="Spring">Spring / River</option>
                                <option value="Bottled Water">Bottled / Purchased</option>
                                <option value="Other">Other (specify)</option>
                            </select>
                            <div class="other-wrap d-none" id="offAddWaterSourceOtherWrap">
                                <input type="text" class="form-control mt-1" name="water_source_other" maxlength="100" placeholder="Specify water source">
                            </div>
                        </div>
                    </div>

                    <!-- SPECIAL STATUS -->
                    <div class="off-section-hdr mt-1">
                        <i class="fas fa-star-of-life"></i> Special Status
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label off-req">PWD?</label>
                            <select class="form-control" name="is_pwd" id="offAddIsPwd" required>
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Deceased?</label>
                            <select class="form-control" name="is_deceased" id="offAddIsDeceased">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 d-none" id="offAddDeathDateDiv">
                            <label class="form-label off-req">Date of Death</label>
                            <input type="date" class="form-control" name="date_of_death" id="offAddDateOfDeath">
                        </div>
                    </div>

                    <!-- PWD TYPE card-picker -->
                    <div class="d-none mb-3" id="offAddPwdSection">
                        <div class="off-pwd-box">
                            <label class="form-label mb-2">
                                ⚕️ Type of Disability <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2" id="offAddPwdCards">
                                <?php
                                $offPwdTypes = [
                                    ['Visual Impairment',   'fa-eye-slash',       'Pagkabuta / Pagkaablag sa Pagsud-ong'],
                                    ['Hearing Impairment',  'fa-deaf',            'Pagkabungol / Pagkaablag sa Pagpamati'],
                                    ['Physical Disability', 'fa-wheelchair',      'Pagkapiit / Pagkaablag sa Lawas'],
                                    ['Intellectual',        'fa-brain',           'Pagkaablag sa Pangisip'],
                                    ['Psychosocial',        'fa-head-side-virus', 'Sakit sa Hunahuna / Kinaiya'],
                                    ['Speech Impairment',   'fa-comment-slash',   'Pagkaamang / Pagkaablag sa Pagsulti'],
                                    ['Other',               'fa-ellipsis-h',      'Uban Pa'],
                                ];
                                foreach ($offPwdTypes as [$label, $icon, $bisaya]):
                                ?>
                                <div class="col-6 col-md-3">
                                    <label class="off-pwd-card-label w-100">
                                        <input type="radio" name="pwd_type" value="<?= htmlspecialchars($label) ?>"
                                               class="d-none off-pwd-type-radio">
                                        <div class="off-pwd-card">
                                            <i class="fas <?= $icon ?> mb-1 d-block"></i>
                                            <span class="d-block" style="font-size:11px;font-weight:700"><?= $label ?></span>
                                            <span class="d-block text-muted" style="font-size:9px;line-height:1.2"><?= $bisaya ?></span>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-none mt-2" id="offAddPwdOtherWrap">
                                <input type="text" class="form-control" id="offAddPwdOtherText"
                                       maxlength="200" placeholder="Please describe the disability…">
                            </div>
                            <input type="hidden" name="pwd_type_resolved" id="offAddPwdTypeResolved" value="">
                            <div class="off-field-hint mt-1" id="offAddPwdErr"></div>
                        </div>
                    </div>

                    <!-- HEALTH PROFILE -->
                    <div class="off-section-hdr mt-1">
                        <i class="fas fa-heartbeat"></i> Health Profile
                    </div>
                    <div class="row g-2">
                        <?php
                        $offHealthFields = [
                            ['is_smoker','Smoker?'],['is_binge_drinker','Binge Drinker?'],
                            ['has_hypertension','Hypertension (HPN)?'],['has_diabetes','Diabetes (DM)?'],
                            ['has_asthma','Asthma?'],['has_tb','Tuberculosis (TB)?'],
                            ['has_cancer','Cancer?'],['has_mental_health','Mental Health?'],
                        ];
                        foreach($offHealthFields as [$fname, $flabel]):
                        ?>
                        <div class="col-6 col-md-3 mb-2">
                            <label class="form-label" style="font-size:11px;font-weight:600"><?= $flabel ?></label>
                            <select class="form-control form-control-sm" name="<?= $fname ?>">
                                <option value="No">No</option><option value="Yes">Yes</option>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div><!-- /modal-body -->

                <div class="modal-footer" style="background:#f8fafc">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="submit" class="btn btn-success" id="offAddOfficialSubmitBtn">
                        <i class="fas fa-save"></i>
                        <span id="offAddOfficialSubmitText">Save Official</span>
                        <span class="spinner-border spinner-border-sm d-none" id="offAddOfficialSpinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CAMERA MODAL -->
<div class="modal fade" id="offAddCameraModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f3c6e;color:#fff;padding:10px 16px;">
                <h6 class="modal-title mb-0"><i class="fas fa-camera me-2"></i> Capture Photo</h6>
                <button type="button" class="btn-close btn-close-white" id="offAddCloseCameraBtn"></button>
            </div>
            <div class="modal-body" style="padding:16px;background:#1a1a2e;">
                <div id="offAddCamError" style="display:none;color:#f8d7da;text-align:center;padding:24px 10px;">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                    <span id="offAddCamErrorMsg">Camera not available.</span>
                </div>
                <div id="offAddCamVideoWrap" style="position:relative;">
                    <video id="offAddCamVideo" autoplay playsinline muted
                           style="width:100%;border-radius:4px;background:#000;display:block;"></video>
                    <button type="button" id="offAddCamSwitchBtn"
                            style="position:absolute;top:8px;right:8px;background:rgba(255,255,255,0.2);color:#fff;border:none;border-radius:50%;width:34px;height:34px;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <img id="offAddCamSnapshot" src="" alt="Snapshot"
                     style="display:none;width:100%;border-radius:4px;margin-top:10px;">
                <canvas id="offAddCamCanvas" style="display:none;"></canvas>
                <div style="display:flex;justify-content:center;gap:10px;margin-top:12px;flex-wrap:wrap;">
                    <button type="button" id="offAddCamCaptureBtn"
                            style="background:#0f3c6e;color:#fff;border:none;padding:9px 26px;border-radius:30px;font-weight:600;font-size:13px;cursor:pointer;">
                        <i class="fas fa-camera me-1"></i> Capture
                    </button>
                    <button type="button" id="offAddCamRetakeBtn"
                            style="display:none;background:#6c757d;color:#fff;border:none;padding:9px 18px;border-radius:30px;font-weight:500;font-size:13px;cursor:pointer;">
                        <i class="fas fa-redo me-1"></i> Retake
                    </button>
                    <button type="button" id="offAddCamUseBtn"
                            style="display:none;background:#28a745;color:#fff;border:none;padding:9px 18px;border-radius:30px;font-weight:500;font-size:13px;cursor:pointer;">
                        <i class="fas fa-check me-1"></i> Use Photo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.off-section-hdr {
    background: #f0f4f8;
    border-left: 4px solid #0f3c6e;
    color: #0f3c6e;
    font-weight: 700;
    font-size: 13px;
    padding: 7px 14px;
    margin-bottom: 14px;
    border-radius: 0;
    display: flex;
    align-items: center;
    gap: 7px;
}
.form-label.off-req::after { content: ' *'; color: #dc3545; }
.off-field-hint { font-size: 11px; color: #dc3545; margin-top: 2px; min-height: 16px; }
.pwd-strength-bar { height: 4px; background: #e9ecef; border-radius: 3px; margin-top: 4px; }
.pwd-strength-bar > div { height: 100%; border-radius: 3px; transition: all .3s; }
.other-wrap input { border-left: 3px solid #0f3c6e !important; }
.off-pwd-box { background: #fff8e1; border: 1px solid #ffc107; border-left: 3px solid #f59e0b; padding: 12px; border-radius: 4px; }
.off-pwd-card-label { cursor: pointer; margin: 0; }
.off-pwd-card { border: 2px solid #e2e8f0; border-radius: 8px; padding: 10px 6px; text-align: center; font-size: 11px; font-weight: 600; color: #64748b; transition: all .15s; background: #fff; }
.off-pwd-card i { font-size: 18px; color: #94a3b8; }
.off-pwd-card:hover { border-color: #94a3b8; }
.off-pwd-card-active { border-color: #1a56db !important; background: #eff6ff !important; color: #1a56db !important; }
.off-pwd-card-active i { color: #1a56db !important; }
.off-photo-wrapper { border: 1.5px dashed #b0bec5; border-radius: 4px; padding: 6px; background: #f8f9fa; }
.off-photo-wrapper:hover { border-color: #1f6bb8; }
.off-photo-inner { display: flex; align-items: stretch; gap: 6px; }
.off-photo-preview-box { width: 72px; min-width: 72px; height: 72px; background: #e9ecef; border: 1px solid #dee2e6; border-radius: 3px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; flex-shrink: 0; }
.off-photo-placeholder { text-align: center; color: #adb5bd; padding: 4px; }
.off-photo-placeholder i { font-size: 20px; display: block; margin-bottom: 2px; }
.off-photo-placeholder span { font-size: 9px; display: block; }
.off-remove-photo { position: absolute; top: 2px; right: 2px; background: rgba(220,53,69,.85); color: #fff; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 9px; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; }
.off-photo-btn-group { display: flex; flex-direction: column; gap: 4px; flex: 1; justify-content: center; }
#offAddCameraModal { z-index: 1070 !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var SES_BRACKETS = [[10957,'Poor'],[21914,'Low Income'],[43828,'Lower Middle Income'],[76669,'Middle Income'],[131484,'Upper Middle Income']];

    function computeSES(monthly) {
        if (!monthly || monthly <= 0) return '';
        for (var i = 0; i < SES_BRACKETS.length; i++) {
            if (monthly < SES_BRACKETS[i][0]) return SES_BRACKETS[i][1];
        }
        return 'High Income';
    }

    function toggleOtherWrap(selectEl, wrapId) {
        var wrap = document.getElementById(wrapId);
        if (!wrap) return;
        var inp = wrap.querySelector('input');
        if (selectEl.value === 'Other') {
            wrap.classList.remove('d-none');
            if (inp) { inp.required = true; }
        } else {
            wrap.classList.add('d-none');
            if (inp) { inp.required = false; inp.value = ''; }
        }
    }

    /* Monthly income → annual + SES */
    document.getElementById('offAddMonthlyIncome').addEventListener('input', function () {
        var m = parseFloat(this.value) || 0;
        document.getElementById('offAddAnnualIncome').value = m > 0 ? (m * 12).toFixed(2) : '';
        document.getElementById('offAddSESDisplay').value = computeSES(m);
    });

    /* Other dropdowns */
    var otherPairs = [
        ['offAddBloodType',      'offAddBloodTypeOtherWrap'],
        ['offAddOccupationType', 'offAddOccupationTypeOtherWrap'],
        ['offAddReligion',       'offAddReligionOtherWrap'],
        ['offAddEthnicity',      'offAddEthnicityOtherWrap'],
        ['offAddHouseOwnership', 'offAddHouseOwnershipOtherWrap'],
        ['offAddHouseMaterial',  'offAddHouseMaterialOtherWrap'],
        ['offAddToiletType',     'offAddToiletTypeOtherWrap'],
        ['offAddWaterSource',    'offAddWaterSourceOtherWrap'],
    ];
    otherPairs.forEach(function(pair) {
        var el = document.getElementById(pair[0]);
        if (el) el.addEventListener('change', function() { toggleOtherWrap(this, pair[1]); });
    });

    /* Education sub-fields */
    document.getElementById('offAddEduAttain').addEventListener('change', function () {
        var show = ['Elementary','High School','Senior High School','College','Vocational','Post Graduate'].includes(this.value);
        document.getElementById('offAddGradeLevelWrap').style.opacity = show ? '1' : '0.4';
        document.getElementById('offAddSchoolNameWrap').style.opacity = show ? '1' : '0.4';
        // Graduate sub-fields
        var gradRow = document.getElementById('offAddGraduateFields');
        if (gradRow) {
            if (['College','Vocational','Post Graduate'].includes(this.value)) {
                gradRow.classList.remove('d-none');
            } else {
                gradRow.classList.add('d-none');
                var c = document.getElementById('offAddCourse'); if(c) c.value='';
                var co = document.getElementById('offAddCourseOtherWrap'); if(co) co.classList.add('d-none');
                var e = document.getElementById('offAddEligibility'); if(e) e.value='';
                var eo = document.getElementById('offAddEligOtherWrap'); if(eo) eo.classList.add('d-none');
            }
        }
    });

    /* Course "Others" toggle */
    var courseEl = document.getElementById('offAddCourse');
    if (courseEl) courseEl.addEventListener('change', function() {
        var wrap = document.getElementById('offAddCourseOtherWrap');
        if (this.value === 'Others') { wrap.classList.remove('d-none'); }
        else { wrap.classList.add('d-none'); var inp = wrap.querySelector('input'); if(inp) inp.value=''; }
    });

    /* Eligibility "Others" toggle */
    var eligEl = document.getElementById('offAddEligibility');
    if (eligEl) eligEl.addEventListener('change', function() {
        var wrap = document.getElementById('offAddEligOtherWrap');
        if (this.value === 'Others') { wrap.classList.remove('d-none'); }
        else { wrap.classList.add('d-none'); var inp = wrap.querySelector('input'); if(inp) inp.value=''; }
    });

    /* Birthdate → Age */
    var bdEl = document.getElementById('offAddBirthdate');
    bdEl.max = new Date().toISOString().split('T')[0];
    bdEl.addEventListener('change', function () {
        if (!this.value) { document.getElementById('offAddAge').value = ''; return; }
        var bd = new Date(this.value), today = new Date();
        if (bd > today) { alert('Birthdate cannot be in the future!'); this.value = ''; document.getElementById('offAddAge').value = ''; return; }
        var age = today.getFullYear() - bd.getFullYear();
        var md = today.getMonth() - bd.getMonth();
        if (md < 0 || (md === 0 && today.getDate() < bd.getDate())) age--;
        document.getElementById('offAddAge').value = age >= 0 ? age : 0;
    });

    /* Term Start → Years in Service */
    document.getElementById('offAddTermStart').addEventListener('change', function () {
        document.getElementById('offAddTermEnd').min = this.value;
        if (!this.value) { document.getElementById('offAddYearsService').value = ''; return; }
        var start = new Date(this.value), today = new Date();
        var yrs = today.getFullYear() - start.getFullYear();
        var md = today.getMonth() - start.getMonth();
        if (md < 0 || (md === 0 && today.getDate() < start.getDate())) yrs--;
        document.getElementById('offAddYearsService').value = yrs >= 0 ? yrs : 0;
    });

    /* Password toggles */
    document.getElementById('offAddTogglePwd').addEventListener('click', function () {
        var p = document.getElementById('offAddPassword');
        var i = document.getElementById('offAddPwdIcon');
        p.type = p.type === 'password' ? 'text' : 'password';
        i.classList.toggle('fa-eye'); i.classList.toggle('fa-eye-slash');
    });
    document.getElementById('offAddToggleConfirm').addEventListener('click', function () {
        var p = document.getElementById('offAddConfirmPwd');
        var i = document.getElementById('offAddConfirmIcon');
        p.type = p.type === 'password' ? 'text' : 'password';
        i.classList.toggle('fa-eye'); i.classList.toggle('fa-eye-slash');
    });

    /* Password strength */
    document.getElementById('offAddPassword').addEventListener('input', function () {
        var v = this.value, bar = document.getElementById('offAddPwdBar'), hint = document.getElementById('offAddPwdHint');
        if (!v) { bar.style.width = '0'; hint.textContent = 'Min 6 characters'; hint.style.color = ''; return; }
        var s = 0;
        if (v.length >= 8) s++; if (v.length >= 12) s++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++; if (/[0-9]/.test(v)) s++; if (/[^a-zA-Z0-9]/.test(v)) s++;
        var m = s <= 2 ? ['33%','#dc3545','Weak'] : s <= 3 ? ['66%','#ffc107','Medium'] : ['100%','#28a745','Strong'];
        bar.style.cssText = 'width:' + m[0] + ';background:' + m[1]; hint.textContent = m[2]; hint.style.color = m[1];
    });

    /* Confirm password match */
    document.getElementById('offAddConfirmPwd').addEventListener('input', function () {
        var pwd = document.getElementById('offAddPassword').value;
        document.getElementById('offAddConfirmErr').textContent = (this.value && pwd !== this.value) ? 'Passwords do not match' : '';
    });

    /* Username availability check */
    var uValid = false;
    var uTimer = null;
    document.getElementById('offAddUsername').addEventListener('input', function () {
        var v = this.value.trim();
        var spinner = document.getElementById('offAddUSpinner');
        var chk = document.getElementById('offAddUCheck');
        var xIcon = document.getElementById('offAddUX');
        var err = document.getElementById('offAddUErr');
        [spinner, chk, xIcon].forEach(function(e) { e.classList.add('d-none'); });
        uValid = false; err.textContent = '';
        clearTimeout(uTimer);
        if (!v) return;
        if (v.length < 4) { err.textContent = 'Min 4 characters'; xIcon.classList.remove('d-none'); return; }
        if (v.length > 20) { err.textContent = 'Max 20 characters'; xIcon.classList.remove('d-none'); return; }
        if (!/^[a-zA-Z0-9_]+$/.test(v)) { err.textContent = 'Letters, numbers, underscore only'; xIcon.classList.remove('d-none'); return; }
        spinner.classList.remove('d-none');
        uTimer = setTimeout(function () {
            if (typeof $ === 'undefined') { spinner.classList.add('d-none'); uValid = true; chk.classList.remove('d-none'); return; }
            $.ajax({ url: 'check_username_official.php', method: 'POST', data: { username: v }, dataType: 'json', timeout: 4000,
                success: function(r) { spinner.classList.add('d-none'); if (r.available) { chk.classList.remove('d-none'); uValid = true; } else { xIcon.classList.remove('d-none'); err.textContent = 'Username already taken'; } },
                error: function() { spinner.classList.add('d-none'); xIcon.classList.remove('d-none'); err.textContent = 'Could not verify'; }
            });
        }, 300);
    });

    /* Photo upload / preview */
    var prevImg    = document.getElementById('offAddPhotoPreviewImg');
    var placeholder= document.getElementById('offAddPhotoPlaceholder');
    var removeBtn  = document.getElementById('offAddRemovePhotoBtn');
    var imgInput   = document.getElementById('offAddImage');
    var camInput   = document.getElementById('offAddCameraImage');
    var imgErr     = document.getElementById('offAddImgErr');

    function showPreview(src) { prevImg.src = src; prevImg.style.display = 'block'; placeholder.style.display = 'none'; removeBtn.classList.remove('d-none'); imgErr.textContent = ''; }
    function clearPreview() { prevImg.src = ''; prevImg.style.display = 'none'; placeholder.style.display = 'flex'; removeBtn.classList.add('d-none'); }

    removeBtn.addEventListener('click', function () { imgInput.value = ''; camInput.value = ''; clearPreview(); });
    imgInput.addEventListener('change', function () {
        var f = this.files[0]; camInput.value = '';
        if (!f) { clearPreview(); return; }
        if (!['image/jpeg','image/jpg','image/png','image/gif'].includes(f.type)) { imgErr.textContent = 'Only JPG/PNG/GIF allowed'; this.value = ''; return; }
        if (f.size > 2 * 1024 * 1024) { imgErr.textContent = 'Max 2 MB'; this.value = ''; return; }
        var reader = new FileReader();
        reader.onload = function(e) { showPreview(e.target.result); };
        reader.readAsDataURL(f);
    });

    /* PWD toggle */
    document.getElementById('offAddIsPwd').addEventListener('change', function () {
        var sec = document.getElementById('offAddPwdSection');
        if (this.value === 'Yes') { sec.classList.remove('d-none'); }
        else { sec.classList.add('d-none'); resetPwdCards(); }
    });

    function resetPwdCards() {
        document.querySelectorAll('.off-pwd-type-radio').forEach(function(r) {
            r.checked = false; r.disabled = false;
            var card = r.closest('label') && r.closest('label').querySelector('.off-pwd-card');
            if (card) card.classList.remove('off-pwd-card-active');
        });
        document.getElementById('offAddPwdOtherWrap').classList.add('d-none');
        var ot = document.getElementById('offAddPwdOtherText');
        if (ot) { ot.value = ''; ot.required = false; }
        var hid = document.getElementById('offAddPwdTypeResolved');
        if (hid) { hid.name = 'pwd_type_resolved'; hid.value = ''; }
        document.getElementById('offAddPwdErr').textContent = '';
    }

    /* PWD card selection */
    document.querySelectorAll('.off-pwd-type-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.off-pwd-type-radio').forEach(function(r) {
                var card = r.closest('label') && r.closest('label').querySelector('.off-pwd-card');
                if (card) card.classList.remove('off-pwd-card-active');
            });
            var myCard = this.closest('label') && this.closest('label').querySelector('.off-pwd-card');
            if (myCard) myCard.classList.add('off-pwd-card-active');
            var wrap = document.getElementById('offAddPwdOtherWrap');
            var ot   = document.getElementById('offAddPwdOtherText');
            if (this.value === 'Other') {
                wrap.classList.remove('d-none');
                if (ot) { ot.required = true; ot.focus(); }
            } else {
                wrap.classList.add('d-none');
                if (ot) { ot.required = false; ot.value = ''; }
            }
            document.getElementById('offAddPwdErr').textContent = '';
        });
    });

    /* Deceased toggle */
    document.getElementById('offAddIsDeceased').addEventListener('change', function () {
        var div = document.getElementById('offAddDeathDateDiv');
        var dd  = document.getElementById('offAddDateOfDeath');
        if (this.value === 'Yes') { div.classList.remove('d-none'); dd.required = true; }
        else { div.classList.add('d-none'); dd.required = false; dd.value = ''; }
    });
    document.getElementById('offAddDateOfDeath').max = new Date().toISOString().split('T')[0];

    /* Form submit */
    document.getElementById('addOfficialForm').addEventListener('submit', function (e) {
        if (!uValid) {
            e.preventDefault();
            alert('Please wait for username check or fix the username field.');
            document.getElementById('offAddUsername').focus(); return false;
        }
        var pwd = document.getElementById('offAddPassword').value;
        if (pwd.length < 6) { e.preventDefault(); alert('Password must be at least 6 characters.'); return false; }
        if (pwd !== document.getElementById('offAddConfirmPwd').value) {
            e.preventDefault(); alert('Passwords do not match!'); return false;
        }
        var hasCam  = camInput.value.trim().length > 0;
        var hasFile = imgInput.files && imgInput.files.length > 0;
        if (!hasCam && !hasFile) {
            e.preventDefault();
            imgErr.textContent = 'Please upload or capture a photo.';
            imgErr.scrollIntoView({ behavior: 'smooth', block: 'center' }); return false;
        }
        var ts = document.getElementById('offAddTermStart').value;
        var te = document.getElementById('offAddTermEnd').value;
        if (ts && te && new Date(te) <= new Date(ts)) {
            e.preventDefault(); alert('Term End must be after Term Start.'); return false;
        }
        /* PWD validation */
        if (document.getElementById('offAddIsPwd').value === 'Yes') {
            var checked = document.querySelector('.off-pwd-type-radio:checked:not([disabled])');
            if (!checked) {
                e.preventDefault();
                document.getElementById('offAddPwdErr').textContent = 'Please select a disability type.';
                document.getElementById('offAddPwdSection').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            if (checked.value === 'Other') {
                var otText = document.getElementById('offAddPwdOtherText').value.trim();
                if (!otText) {
                    e.preventDefault();
                    document.getElementById('offAddPwdErr').textContent = 'Please describe the disability.';
                    document.getElementById('offAddPwdOtherText').focus(); return false;
                }
                checked.disabled = true;
                var hid = document.getElementById('offAddPwdTypeResolved');
                hid.name = 'pwd_type'; hid.value = otText;
            }
        }
        /* Other-specify validation */
        var pairs2 = [
            ['offAddReligion','religion_other'],['offAddEthnicity','ethnicity_other'],
            ['offAddBloodType','blood_type_other'],['offAddHouseOwnership','house_ownership_other'],
            ['offAddHouseMaterial','house_material_other'],['offAddToiletType','toilet_type_other'],
            ['offAddWaterSource','water_source_other'],['offAddOccupationType','occupation_type_other']
        ];
        for (var pi = 0; pi < pairs2.length; pi++) {
            var sel = document.getElementById(pairs2[pi][0]);
            if (sel && sel.value === 'Other') {
                var inp = document.querySelector('#addOfficialModal input[name="' + pairs2[pi][1] + '"]');
                if (inp && !inp.value.trim()) {
                    e.preventDefault(); alert('Please fill in the "' + pairs2[pi][1].replace('_other','').replace(/_/g,' ') + '" field.'); inp.focus(); return false;
                }
            }
        }
        document.getElementById('offAddOfficialSubmitBtn').disabled = true;
        document.getElementById('offAddOfficialSubmitText').classList.add('d-none');
        document.getElementById('offAddOfficialSpinner').classList.remove('d-none');
    });

    /* Modal close reset */
    document.getElementById('addOfficialModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addOfficialForm').reset();
        document.getElementById('offAddPwdSection').classList.add('d-none');
        document.getElementById('offAddDeathDateDiv').classList.add('d-none');
        document.getElementById('offAddDateOfDeath').required = false;
        document.getElementById('offAddAge').value = '';
        document.getElementById('offAddYearsService').value = '';
        document.getElementById('offAddAnnualIncome').value = '';
        document.getElementById('offAddSESDisplay').value = '';
        document.getElementById('offAddPassword').type = 'password';
        document.getElementById('offAddPwdIcon').className = 'fas fa-eye';
        document.getElementById('offAddConfirmPwd').type = 'password';
        document.getElementById('offAddConfirmIcon').className = 'fas fa-eye';
        document.getElementById('offAddPwdBar').style.cssText = '';
        document.getElementById('offAddPwdHint').textContent = 'Min 6 characters';
        document.getElementById('offAddPwdHint').style.color = '';
        document.getElementById('offAddConfirmErr').textContent = '';
        document.getElementById('offAddUErr').textContent = '';
        ['offAddUSpinner','offAddUCheck','offAddUX'].forEach(function(id){ document.getElementById(id).classList.add('d-none'); });
        uValid = false;
        clearPreview(); imgErr.textContent = '';
        document.getElementById('offAddOfficialSubmitBtn').disabled = false;
        document.getElementById('offAddOfficialSubmitText').classList.remove('d-none');
        document.getElementById('offAddOfficialSpinner').classList.add('d-none');
        resetPwdCards();
        document.querySelectorAll('#addOfficialModal .other-wrap').forEach(function(w) {
            w.classList.add('d-none');
            var i = w.querySelector('input'); if (i) { i.required = false; i.value = ''; }
        });
        offAddCamStop();
    });

    /* CAMERA */
    var camVideo    = document.getElementById('offAddCamVideo');
    var camCanvas   = document.getElementById('offAddCamCanvas');
    var camSnapshot = document.getElementById('offAddCamSnapshot');
    var camVidWrap  = document.getElementById('offAddCamVideoWrap');
    var camErrDiv   = document.getElementById('offAddCamError');
    var camErrMsg   = document.getElementById('offAddCamErrorMsg');
    var camCapBtn   = document.getElementById('offAddCamCaptureBtn');
    var camRetBtn   = document.getElementById('offAddCamRetakeBtn');
    var camUseBtn   = document.getElementById('offAddCamUseBtn');
    var camSwBtn    = document.getElementById('offAddCamSwitchBtn');
    var camModalEl  = document.getElementById('offAddCameraModal');
    var camStream   = null;
    var camFacing   = 'user';
    var camCaptured = null;

    document.getElementById('offAddOpenCameraBtn').addEventListener('click', function () {
        offAddCamReset();
        offAddCamStart();
        bootstrap.Modal.getOrCreateInstance(camModalEl).show();
    });

    document.getElementById('offAddCloseCameraBtn').addEventListener('click', function () {
        bootstrap.Modal.getOrCreateInstance(camModalEl).hide();
    });

    camModalEl.addEventListener('hidden.bs.modal', function () {
        offAddCamStop();
        offAddCamReset();
        camModalEl.style.zIndex = '';
    });

    camModalEl.addEventListener('shown.bs.modal', function () {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length >= 2) {
            backdrops[backdrops.length - 1].style.zIndex = '1069';
        }
        camModalEl.style.zIndex = '1070';
    });

    camSwBtn.addEventListener('click', function () {
        camFacing = camFacing === 'user' ? 'environment' : 'user';
        offAddCamStop(); offAddCamStart();
    });

    camCapBtn.addEventListener('click', function () {
        var w = camVideo.videoWidth || 640, h = camVideo.videoHeight || 480;
        camCanvas.width = w; camCanvas.height = h;
        var ctx = camCanvas.getContext('2d');
        if (camFacing === 'user') { ctx.translate(w, 0); ctx.scale(-1, 1); }
        ctx.drawImage(camVideo, 0, 0, w, h);
        camCaptured = camCanvas.toDataURL('image/jpeg', 0.92);
        camSnapshot.src = camCaptured; camSnapshot.style.display = 'block';
        camVidWrap.style.display = 'none'; camCapBtn.style.display = 'none';
        camRetBtn.style.display = 'inline-block'; camUseBtn.style.display = 'inline-block';
    });

    camRetBtn.addEventListener('click', function () {
        camCaptured = null; camSnapshot.style.display = 'none';
        camVidWrap.style.display = 'block'; camCapBtn.style.display = 'inline-block';
        camRetBtn.style.display = 'none'; camUseBtn.style.display = 'none';
        offAddCamStart();
    });

    camUseBtn.addEventListener('click', function () {
        if (!camCaptured) return;
        camInput.value = camCaptured;
        imgInput.value = '';
        showPreview(camCaptured);
        bootstrap.Modal.getOrCreateInstance(camModalEl).hide();
    });

    camVideo.addEventListener('loadedmetadata', function () {
        this.style.transform = camFacing === 'user' ? 'scaleX(-1)' : 'none';
    });

    function offAddCamStart() {
        camErrDiv.style.display = 'none'; camVidWrap.style.display = 'block';
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            offAddCamShowErr('Your browser does not support camera access.'); return;
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: camFacing, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false })
            .then(function(stream) { camStream = stream; camVideo.srcObject = stream; camVideo.play(); })
            .catch(function(err) {
                var msg = 'Camera access denied.';
                if (err.name === 'NotFoundError') msg = 'No camera found.';
                if (err.name === 'NotReadableError') msg = 'Camera is already in use.';
                offAddCamShowErr(msg);
            });
    }

    function offAddCamStop() {
        if (camStream) { camStream.getTracks().forEach(function(t) { t.stop(); }); camStream = null; }
        camVideo.srcObject = null;
    }

    function offAddCamReset() {
        camCaptured = null; camSnapshot.src = ''; camSnapshot.style.display = 'none';
        camVidWrap.style.display = 'block'; camCapBtn.style.display = 'inline-block';
        camRetBtn.style.display = 'none'; camUseBtn.style.display = 'none';
        camErrDiv.style.display = 'none';
    }

    function offAddCamShowErr(msg) {
        camVidWrap.style.display = 'none'; camErrDiv.style.display = 'block';
        camErrMsg.textContent = msg; camCapBtn.style.display = 'none';
    }

}); /* end DOMContentLoaded */
</script>