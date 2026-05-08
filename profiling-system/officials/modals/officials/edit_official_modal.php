<?php
/**
 * modals/officials/edit_official_modal.php
 *
 * FIXES applied vs previous version:
 *
 * 1. PWD submit: NO radio.value mutation.
 *    Old code did `checked.value = ot` which permanently changed the radio's
 *    value attribute — the next time the modal opened, "Other" was already
 *    pre-selected with the previous free-text value, and the card highlight
 *    logic broke.  New code: disable the radio (removes it from POST) and
 *    rename the hidden field to pwd_type so exactly one value is sent.
 *
 * 2. Modal reset (hidden.bs.modal): re-enables all disabled radios AND
 *    restores the hidden field name back to pwd_type_resolved so the cycle
 *    can repeat correctly on the next open.
 *
 * 3. All JS is inside a single IIFE per record — no globals leaked that
 *    could be clobbered by the next included modal.  window.modalToggleOther
 *    and window.editComputeIncome are still on window (shared helpers), but
 *    everything else (event listeners, camera state) is local to the IIFE.
 *
 * 4. The per-record camera modal now correctly uses
 *    bootstrap.Modal.getOrCreateInstance() ONLY inside click/event handlers,
 *    never at parse / IIFE-init time.
 */

$offId = $row['id'];

if (!function_exists('offSelOpt')) {
    function offSelOpt($val, $current) {
        return (string)$val === (string)$current ? ' selected' : '';
    }
}
if (!function_exists('offYnSel')) {
    function offYnSel($field, $row) {
        $yes = (($row[$field] ?? 'No') === 'Yes');
        return "<option value='No'" . (!$yes ? ' selected' : '') . ">No</option>" .
               "<option value='Yes'" . ($yes  ? ' selected' : '') . ">Yes</option>";
    }
}
if (!function_exists('offOtherVal')) {
    function offOtherVal($field, $row, $known) {
        $v = $row[$field] ?? '';
        return in_array($v, $known) ? $v : ($v !== '' ? 'Other' : '');
    }
}
if (!function_exists('offOtherText')) {
    function offOtherText($field, $row, $known) {
        $v = $row[$field] ?? '';
        return (!in_array($v, $known) && $v !== '') ? htmlspecialchars($v) : '';
    }
}

$offKnownReligion  = ['Roman Catholic','Islam','Iglesia ni Cristo','Seventh-day Adventist','Born Again Christian','Baptist',"Jehovah's Witness",'UCCP'];
$offKnownEthnicity = ['Visayan','Cebuano','Mamanwa (IP)','Higaonon (IP)','Manobo (IP)','Maranao','Tagalog','Ilocano','Bisaya','Waray'];
$offKnownBlood     = ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'];
$offKnownOwnership = ['Owned','Rented','Shared','Government'];
$offKnownMaterial  = ['Concrete','Wood','Mixed','Light Material'];
$offKnownToilet    = ['With Flush','Without Flush','Shared','None'];
$offKnownWater     = ['Level 3 (Piped)','Level 2 (Communal Faucet)','Level 1 (Deep Well)','Rainwater','Spring','Bottled Water'];
$offKnownOccType   = ['Employed','Government Employee','Self-employed','Farmer','OFW','Student','Unemployed','Retired','Homemaker','Public Servant'];
$offKnownBarangay  = ['Buhang','Caloc-an','Guiasan','Marcos','Poblacion','Santo Niño','Santo Rosario','Taod-oy'];

$offPwdTypes = [
    ['Visual Impairment',   'fa-eye-slash',       'Pagkabuta / Pagkaablag sa Pagsud-ong'],
    ['Hearing Impairment',  'fa-deaf',            'Pagkabungol / Pagkaablag sa Pagpamati'],
    ['Physical Disability', 'fa-wheelchair',      'Pagkapiit / Pagkaablag sa Lawas'],
    ['Intellectual',        'fa-brain',           'Pagkaablag sa Pangisip'],
    ['Psychosocial',        'fa-head-side-virus', 'Sakit sa Hunahuna / Kinaiya'],
    ['Speech Impairment',   'fa-comment-slash',   'Pagkaamang / Pagkaablag sa Pagsulti'],
    ['Other',               'fa-ellipsis-h',      'Uban Pa'],
];

$offIsDeceased      = ($row['is_deceased'] ?? 'No') === 'Yes';
$offIsPwd           = ($row['is_pwd']      ?? 'No') === 'Yes';
$offCurrentPwdType  = trim($row['pwd_type'] ?? $row['pwd_details'] ?? '');
$offPwdTypeLabels   = array_column($offPwdTypes, 0);
$offPwdIsKnown      = in_array($offCurrentPwdType, $offPwdTypeLabels);
$offPwdIsOther      = ($offIsPwd && !$offPwdIsKnown && $offCurrentPwdType !== '');

$offMonthly    = isset($row['monthly_income']) && $row['monthly_income'] !== '' ? (float)$row['monthly_income'] : null;
$offSESDisplay = '';
if ($offMonthly !== null && $offMonthly > 0) {
    if ($offMonthly < 10957)  $offSESDisplay = 'Poor';
    elseif ($offMonthly < 21914)  $offSESDisplay = 'Low Income';
    elseif ($offMonthly < 43828)  $offSESDisplay = 'Lower Middle Income';
    elseif ($offMonthly < 76669)  $offSESDisplay = 'Middle Income';
    elseif ($offMonthly < 131484) $offSESDisplay = 'Upper Middle Income';
    else $offSESDisplay = 'High Income';
}

$offOccTypeVal   = offOtherVal('occupation_type', $row, $offKnownOccType);
$offOccTypeOther = offOtherText('occupation_type', $row, $offKnownOccType);
?>

<div class='modal fade' id='editOfficialModal<?= $offId ?>' tabindex='-1'>
    <div class='modal-dialog modal-xl' style='max-width:1250px'>
        <div class='modal-content'>
            <div class='modal-header text-white' style='background:#0f3c6e'>
                <h5 class='modal-title'>
                    <i class='fas fa-user-edit me-2'></i>
                    Edit Official — <?= htmlspecialchars($row['first_name'] . ' ' . $row['surname']) ?>
                </h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>

            <form action='update_official.php' method='POST' enctype='multipart/form-data' id='editOffForm<?= $offId ?>'>
                <div class='modal-body' style='max-height:78vh;overflow-y:auto;padding:20px 24px'>
                    <input type='hidden' name='id' value='<?= $offId ?>'>

                    <div class='alert py-2 px-3 mb-3' style='font-size:12px;border-left:4px solid #0f3c6e;background:#f0f4f8;color:#0f3c6e;border-radius:4px;'>
                        <i class='fas fa-info-circle me-1'></i>
                        Username &amp; password managed separately. Current: <strong><?= htmlspecialchars($row['username'] ?? '—') ?></strong>
                    </div>

                    <!-- PERSONAL -->
                    <div class='off-edit-hdr'><i class='fas fa-user'></i> Personal Information</div>
                    <div class='row'>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>First Name <span class='text-danger'>*</span></label>
                            <input type='text' class='form-control' name='first_name' value='<?= htmlspecialchars($row['first_name']) ?>' required maxlength='50'>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Middle Name</label>
                            <input type='text' class='form-control' name='middle_name' value='<?= htmlspecialchars($row['middle_name'] ?? '') ?>' maxlength='50'>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Surname <span class='text-danger'>*</span></label>
                            <input type='text' class='form-control' name='surname' value='<?= htmlspecialchars($row['surname']) ?>' required maxlength='50'>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Suffix</label>
                            <select class='form-control' name='suffix'>
                                <option value=''>None</option>
                                <?php foreach(['Jr.','Sr.','II','III','IV','V'] as $sfx): ?>
                                <option value='<?= $sfx ?>'<?= offSelOpt($sfx, $row['suffix'] ?? '') ?>><?= $sfx ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Birthdate <span class='text-danger'>*</span></label>
                            <input type='date' class='form-control' name='birthdate' id='offEditBd<?= $offId ?>'
                                   value='<?= htmlspecialchars($row['birthdate'] ?? '') ?>' min='1900-01-01' required>
                            <small class='text-muted' style='font-size:10px'>Use the calendar picker (YYYY-MM-DD)</small>
                        </div>
                        <div class='col-md-2 mb-3'>
                            <label class='form-label fw-semibold'>Age <span class='text-danger'>*</span></label>
                            <input type='number' class='form-control' name='age' id='offEditAge<?= $offId ?>'
                                   value='<?= intval($row['age'] ?? 0) ?>' readonly required>
                        </div>
                        <div class='col-md-4 mb-3'>
                            <label class='form-label fw-semibold'>Birthplace <span class='text-danger'>*</span></label>
                            <input type='text' class='form-control' name='birthplace' value='<?= htmlspecialchars($row['birthplace'] ?? '') ?>' required maxlength='100'>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Contact No.</label>
                            <input type='text' class='form-control' name='contact_no' value='<?= htmlspecialchars($row['contact_no'] ?? '') ?>' maxlength='20'>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Email Address</label>
                            <input type='email' class='form-control' name='email' value='<?= htmlspecialchars($row['email'] ?? '') ?>' maxlength='150' placeholder='example@gmail.com'>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Sex <span class='text-danger'>*</span></label>
                            <select class='form-control' name='sex' required>
                                <option value='Male'<?= offSelOpt('Male',$row['sex']??'') ?>>Male</option>
                                <option value='Female'<?= offSelOpt('Female',$row['sex']??'') ?>>Female</option>
                                <option value='LGBTQ+'<?= offSelOpt('LGBTQ+',$row['sex']??'') ?>>LGBTQ+</option>
                            </select>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Civil Status <span class='text-danger'>*</span></label>
                            <select class='form-control' name='civil_status' required>
                                <?php foreach(['Single','Married','Widowed','Separated','Annulled','Live-in'] as $cs): ?>
                                <option value='<?= $cs ?>'<?= offSelOpt($cs,$row['civil_status']??'') ?>><?= $cs ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Nationality</label>
                            <input type='text' class='form-control' name='nationality' value='<?= htmlspecialchars($row['nationality'] ?? 'Filipino') ?>' maxlength='50'>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Blood Type</label>
                            <?php $btVal=offOtherVal('blood_type',$row,$offKnownBlood); $btOth=offOtherText('blood_type',$row,$offKnownBlood); ?>
                            <select class='form-control' name='blood_type' id='offEditBt<?= $offId ?>'>
                                <option value=''>Select</option>
                                <?php foreach($offKnownBlood as $b): ?>
                                <option value='<?= $b ?>'<?= offSelOpt($b,$btVal) ?>><?= $b ?></option>
                                <?php endforeach; ?>
                                <option value='Other'<?= $btVal==='Other'?' selected':'' ?>>Other</option>
                            </select>
                            <div class='other-wrap <?= $btVal==="Other"?"":"d-none" ?>' id='offEditBtWrap<?= $offId ?>'>
                                <input type='text' class='form-control mt-1' name='blood_type_other' maxlength='20' value='<?= $btOth ?>' placeholder='Specify' <?= $btVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- POSITION & TERM -->
                    <div class='off-edit-hdr'>
                        <i class='fas fa-id-badge'></i> Position & Term
                    </div>
                    <div class='row'>
                        <div class='col-md-4 mb-3'>
                            <label class='form-label fw-semibold'>Position <span class='text-danger'>*</span></label>
                            <select class='form-control' name='position' required>
                                <option value=''>-- Select --</option>
                                <?php foreach(['Barangay Captain','Barangay Kagawad','Sangguniang Barangay (SB) Member','SK Chairman','Barangay Secretary','Barangay Treasurer','Barangay Health Worker','Barangay Tanod','Lupon Member'] as $pos): ?>
                                <option value='<?= $pos ?>'<?= offSelOpt($pos,$row['position']??'') ?>><?= $pos ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class='col-md-4 mb-3'>
                            <label class='form-label fw-semibold'>Chairmanship</label>
                            <input type='text' class='form-control' name='chairmanship' value='<?= htmlspecialchars($row['chairmanship'] ?? '') ?>' maxlength='100'>
                        </div>
                        <div class='col-md-4 mb-3'>
                            <label class='form-label fw-semibold'>Status <span class='text-danger'>*</span></label>
                            <select class='form-control' name='status' required>
                                <?php foreach(['Active','Inactive','On Leave','Retired'] as $st): ?>
                                <option value='<?= $st ?>'<?= offSelOpt($st,$row['status']??'Active') ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Term Start <span class='text-danger'>*</span></label>
                            <input type='date' class='form-control' name='term_start' id='offEditTs<?= $offId ?>' value='<?= htmlspecialchars($row['term_start'] ?? '') ?>' required>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Term End <span class='text-danger'>*</span></label>
                            <input type='date' class='form-control' name='term_end' id='offEditTe<?= $offId ?>' value='<?= htmlspecialchars($row['term_end'] ?? '') ?>' required>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Years in Service</label>
                            <input type='number' class='form-control' name='years_in_service' id='offEditYs<?= $offId ?>' value='<?= intval($row['years_in_service'] ?? 0) ?>' min='0' max='50' readonly>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Voter Status</label>
                            <select class='form-control' name='voters_status'>
                                <option value='Yes'<?= offSelOpt('Yes',$row['voters_status']??'Yes') ?>>Registered Voter</option>
                                <option value='No'<?= offSelOpt('No',$row['voters_status']??'Yes') ?>>Not Registered</option>
                            </select>
                        </div>
                    </div>

                    <!-- ADDRESS -->
                    <div class='off-edit-hdr mt-1'><i class='fas fa-map-marker-alt'></i> Address</div>
                    <div class='row'>
                        <div class='col-md-2 mb-3'>
                            <label class='form-label fw-semibold'>HH No.</label>
                            <input type='text' class='form-control' name='household_no' value='<?= htmlspecialchars($row['household_no'] ?? '') ?>' maxlength='20'>
                        </div>
                        <div class='col-md-2 mb-3'>
                            <label class='form-label fw-semibold'>Purok <span class='text-danger'>*</span></label>
                            <select class='form-control' name='purok' required>
                                <option value=''>Select</option>
                                <?php for($i=1;$i<=10;$i++): ?>
                                <option value='Purok <?= $i ?>'<?= offSelOpt("Purok $i",$row['purok']??'') ?>>Purok <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Barangay <span class='text-danger'>*</span></label>
                            <select class='form-control' name='barangay' required>
                                <option value=''>Select</option>
                                <?php foreach($offKnownBarangay as $br): ?>
                                <option value='<?= $br ?>'<?= offSelOpt($br,$row['barangay']??'') ?>><?= $br ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Municipality</label>
                            <input type='text' class='form-control' name='municipality' value='<?= htmlspecialchars($row['municipality'] ?? '') ?>'>
                        </div>
                        <div class='col-md-2 mb-3'>
                            <label class='form-label fw-semibold'>Province</label>
                            <input type='text' class='form-control' name='province' value='<?= htmlspecialchars($row['province'] ?? '') ?>'>
                        </div>
                    </div>

                    <!-- OCCUPATION & INCOME -->
                    <div class='off-edit-hdr mt-1'><i class='fas fa-briefcase'></i> Occupation & Income</div>
                    <div class='row'>
                        <div class='col-md-4 mb-3'>
                            <label class='form-label fw-semibold'>Occupation Type <span class='text-danger'>*</span></label>
                            <select class='form-control' name='occupation_type' id='offEditOccType<?= $offId ?>' required>
                                <option value=''>Select</option>
                                <?php foreach($offKnownOccType as $ot): ?>
                                <option value='<?= $ot ?>'<?= offSelOpt($ot,$offOccTypeVal) ?>><?= $ot ?></option>
                                <?php endforeach; ?>
                                <option value='Other'<?= $offOccTypeVal==='Other'?' selected':'' ?>>Other (specify)</option>
                            </select>
                            <div class='other-wrap <?= $offOccTypeVal==="Other"?"":"d-none" ?>' id='offEditOccTypeWrap<?= $offId ?>'>
                                <input type='text' class='form-control mt-1' name='occupation_type_other' maxlength='100' value='<?= $offOccTypeOther ?>' placeholder='Specify' <?= $offOccTypeVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class='col-md-4 mb-3'>
                            <label class='form-label fw-semibold'>Occupation / Job Title</label>
                            <input type='text' class='form-control' name='occupation' value='<?= htmlspecialchars($row['occupation'] ?? '') ?>' maxlength='100'>
                        </div>
                        <div class='col-md-4 mb-3'>
                            <label class='form-label fw-semibold'>Household Position <span class='text-danger'>*</span></label>
                            <select class='form-control' name='household_position' required>
                                <option value=''>Select</option>
                                <?php foreach(['Head','Spouse','Son','Daughter','Grandson','Granddaughter','Father','Mother','Brother','Sister','Uncle','Aunt','Nephew','Niece','Cousin','Son-in-law','Daughter-in-law','Brother-in-law','Sister-in-law','Grandparent','Other'] as $hp): ?>
                                <option value='<?= $hp ?>'<?= offSelOpt($hp,$row['household_position']??'') ?>><?= $hp==='Head'?'Head of Family':($hp==='Other'?'Other Relative':$hp) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-4 mb-3'>
                            <label class='form-label fw-semibold'>Monthly Income (₱)</label>
                            <input type='number' step='0.01' class='form-control' name='monthly_income' id='offEditMonthly<?= $offId ?>'
                                   value='<?= $row['monthly_income'] ?? '' ?>' placeholder='0.00' min='0'>
                        </div>
                        <div class='col-md-4 mb-3'>
                            <label class='form-label fw-semibold'>Annual Income (₱) <small class='text-muted'>(auto)</small></label>
                            <input type='number' class='form-control bg-light' name='annual_income' id='offEditAnnual<?= $offId ?>'
                                   value='<?= $row['annual_income'] ?? '' ?>' readonly>
                        </div>
                        <div class='col-md-4 mb-3'>
                            <label class='form-label fw-semibold'>Socioeconomic Status <small class='text-muted'>(auto)</small></label>
                            <input type='text' class='form-control bg-light' id='offEditSES<?= $offId ?>'
                                   value='<?= htmlspecialchars($offSESDisplay) ?>' readonly>
                        </div>
                    </div>
                    <div class='row'>
                        <div class='col-md-2 mb-3'>
                            <label class='form-label fw-semibold'>Total Household</label>
                            <input type='number' class='form-control' name='total_household' value='<?= intval($row['total_household'] ?? 1) ?>' min='1' max='50'>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Father's Name</label>
                            <input type="text" class="form-control" name="father_name"
                                   value="<?= htmlspecialchars($row['father_name'] ?? '') ?>" maxlength="200" placeholder="e.g. Juan Dela Cruz">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Father's Occupation</label>
                            <input type="text" class="form-control" name="father_occupation"
                                   value="<?= htmlspecialchars($row['father_occupation'] ?? '') ?>" maxlength="200" placeholder="e.g. Farmer, Driver">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Mother's Name</label>
                            <input type="text" class="form-control" name="mother_name"
                                   value="<?= htmlspecialchars($row['mother_name'] ?? '') ?>" maxlength="200" placeholder="e.g. Maria Dela Cruz">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Mother's Occupation</label>
                            <input type="text" class="form-control" name="mother_occupation"
                                   value="<?= htmlspecialchars($row['mother_occupation'] ?? '') ?>" maxlength="200" placeholder="e.g. Housewife, Teacher">
                        </div>
                    </div>

                    <!-- EDUCATION -->
                    <div class='off-edit-hdr mt-1'><i class='fas fa-graduation-cap'></i> Educational Attainment</div>
                    <div class='row'>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Attainment <span class='text-danger'>*</span></label>
                            <select class='form-control' name='educational_attainment' id='offEditEdu<?= $offId ?>' required>
                                <option value=''>Select</option>
                                <?php foreach(['No Formal Education','Elementary','High School','Senior High School','College','Vocational','Post Graduate'] as $ea): ?>
                                <option value='<?= $ea ?>'<?= offSelOpt($ea,$row['educational_attainment']??'') ?>><?= $ea ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class='col-md-3 mb-3' id='offEditGradeWrap<?= $offId ?>'>
                            <label class='form-label fw-semibold'>Grade / Year Level</label>
                            <input type='text' class='form-control' name='grade_level' value='<?= htmlspecialchars($row['grade_level'] ?? '') ?>' maxlength='50'>
                        </div>
                        <div class='col-md-6 mb-3' id='offEditSchoolWrap<?= $offId ?>'>
                            <label class='form-label fw-semibold'>School Name</label>
                            <input type='text' class='form-control' name='school_name' value='<?= htmlspecialchars($row['school_name'] ?? '') ?>' maxlength='150'>
                        </div>
                    </div>

                    <!-- DEMOGRAPHIC -->
                    <div class='off-edit-hdr mt-1'>
                        <i class='fas fa-id-card'></i> Demographic
                    </div>
                    <div class='row'>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Religion</label>
                            <?php $relVal=offOtherVal('religion',$row,$offKnownReligion); $relOth=offOtherText('religion',$row,$offKnownReligion); ?>
                            <select class='form-control' name='religion' id='offEditRel<?= $offId ?>'>
                                <option value=''>Select</option>
                                <?php foreach($offKnownReligion as $r): ?>
                                <option value='<?= $r ?>'<?= offSelOpt($r,$relVal) ?>><?= $r ?></option>
                                <?php endforeach; ?>
                                <option value='Other'<?= $relVal==='Other'?' selected':'' ?>>Other</option>
                            </select>
                            <div class='other-wrap <?= $relVal==="Other"?"":"d-none" ?>' id='offEditRelWrap<?= $offId ?>'>
                                <input type='text' class='form-control mt-1' name='religion_other' maxlength='100' value='<?= $relOth ?>' placeholder='Specify' <?= $relVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Ethnicity</label>
                            <?php $ethVal=offOtherVal('ethnicity',$row,$offKnownEthnicity); $ethOth=offOtherText('ethnicity',$row,$offKnownEthnicity); ?>
                            <select class='form-control' name='ethnicity' id='offEditEth<?= $offId ?>'>
                                <option value=''>Select</option>
                                <?php foreach($offKnownEthnicity as $e): ?>
                                <option value='<?= $e ?>'<?= offSelOpt($e,$ethVal) ?>><?= $e ?></option>
                                <?php endforeach; ?>
                                <option value='Other'<?= $ethVal==='Other'?' selected':'' ?>>Other</option>
                            </select>
                            <div class='other-wrap <?= $ethVal==="Other"?"":"d-none" ?>' id='offEditEthWrap<?= $offId ?>'>
                                <input type='text' class='form-control mt-1' name='ethnicity_other' maxlength='100' value='<?= $ethOth ?>' placeholder='Specify' <?= $ethVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>PhilHealth No.</label>
                            <input type='text' class='form-control' name='philhealth_no' value='<?= htmlspecialchars($row['philhealth_no'] ?? '') ?>' maxlength='30'>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>PhilHealth Membership</label>
                            <select class='form-control' name='membership_type'>
                                <option value=''>None</option>
                                <?php foreach(['Government','Private','NHTS','Senior Citizen','OFW','Self-employed'] as $mt): ?>
                                <option value='<?= $mt ?>'<?= offSelOpt($mt,$row['membership_type']??'') ?>><?= $mt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class='row'>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">Height (cm)</label>
                            <input type="number" class="form-control" name="height"
                                   value="<?= htmlspecialchars($row['height'] ?? '') ?>" step="0.01" min="0" max="300">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">Weight (kg)</label>
                            <input type="number" class="form-control" name="weight"
                                   value="<?= htmlspecialchars($row['weight'] ?? '') ?>" step="0.01" min="0" max="500">
                        </div>
                        <div class='col-md-2 mb-3'>
                            <label class='form-label fw-semibold'>Residency (yrs)</label>
                            <input type='number' class='form-control' name='length_of_residency' value='<?= $row['length_of_residency'] ?? '' ?>' min='0' max='120'>
                        </div>
                        <div class='col-md-2 mb-3'><label class='form-label fw-semibold'>NHTS?</label><select class='form-control' name='is_nhts'><?= offYnSel('is_nhts',$row) ?></select></div>
                        <div class='col-md-2 mb-3'><label class='form-label fw-semibold'>4Ps?</label><select class='form-control' name='is_4ps'><?= offYnSel('is_4ps',$row) ?></select></div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Solo Parent?</label>
                            <select class='form-control' name='is_solo_parent'>
                                <option value='No'<?= ($row['is_solo_parent']??'No')==='No'?' selected':'' ?>>No</option>
                                <option value='Yes'<?= ($row['is_solo_parent']??'No')==='Yes'?' selected':'' ?>>Yes – Single Mother/Father</option>
                            </select>
                        </div>
                        <div class='col-md-3 mb-3'><label class='form-label fw-semibold'>Family Planning?</label><select class='form-control' name='family_planning'><?= offYnSel('family_planning',$row) ?></select></div>
                    </div>

                    <!-- HOUSING -->
                    <div class='row'>
                        <?php
                        $owVal=offOtherVal('house_ownership',$row,$offKnownOwnership); $owOth=offOtherText('house_ownership',$row,$offKnownOwnership);
                        $matVal=offOtherVal('house_material',$row,$offKnownMaterial);  $matOth=offOtherText('house_material',$row,$offKnownMaterial);
                        $tolVal=offOtherVal('toilet_type',$row,$offKnownToilet);       $tolOth=offOtherText('toilet_type',$row,$offKnownToilet);
                        $watVal=offOtherVal('water_source',$row,$offKnownWater);       $watOth=offOtherText('water_source',$row,$offKnownWater);
                        ?>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>House Ownership</label>
                            <select class='form-control' name='house_ownership' id='offEditOw<?= $offId ?>'>
                                <option value=''>Select</option>
                                <?php foreach($offKnownOwnership as $o): ?><option value='<?= $o ?>'<?= offSelOpt($o,$owVal) ?>><?= $o ?></option><?php endforeach; ?>
                                <option value='Other'<?= $owVal==='Other'?' selected':'' ?>>Other</option>
                            </select>
                            <div class='other-wrap <?= $owVal==="Other"?"":"d-none" ?>' id='offEditOwWrap<?= $offId ?>'>
                                <input type='text' class='form-control mt-1' name='house_ownership_other' maxlength='100' value='<?= $owOth ?>' placeholder='Specify' <?= $owVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>House Material</label>
                            <select class='form-control' name='house_material' id='offEditMat<?= $offId ?>'>
                                <option value=''>Select</option>
                                <?php foreach($offKnownMaterial as $m): ?><option value='<?= $m ?>'<?= offSelOpt($m,$matVal) ?>><?= $m ?></option><?php endforeach; ?>
                                <option value='Other'<?= $matVal==='Other'?' selected':'' ?>>Other</option>
                            </select>
                            <div class='other-wrap <?= $matVal==="Other"?"":"d-none" ?>' id='offEditMatWrap<?= $offId ?>'>
                                <input type='text' class='form-control mt-1' name='house_material_other' maxlength='100' value='<?= $matOth ?>' placeholder='Specify' <?= $matVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Toilet Type</label>
                            <select class='form-control' name='toilet_type' id='offEditTol<?= $offId ?>'>
                                <option value=''>Select</option>
                                <?php foreach($offKnownToilet as $t): ?><option value='<?= $t ?>'<?= offSelOpt($t,$tolVal) ?>><?= $t ?></option><?php endforeach; ?>
                                <option value='Other'<?= $tolVal==='Other'?' selected':'' ?>>Other</option>
                            </select>
                            <div class='other-wrap <?= $tolVal==="Other"?"":"d-none" ?>' id='offEditTolWrap<?= $offId ?>'>
                                <input type='text' class='form-control mt-1' name='toilet_type_other' maxlength='100' value='<?= $tolOth ?>' placeholder='Specify' <?= $tolVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class='col-md-3 mb-3'>
                            <label class='form-label fw-semibold'>Water Source</label>
                            <select class='form-control' name='water_source' id='offEditWat<?= $offId ?>'>
                                <option value=''>Select</option>
                                <?php foreach($offKnownWater as $w): ?><option value='<?= $w ?>'<?= offSelOpt($w,$watVal) ?>><?= $w ?></option><?php endforeach; ?>
                                <option value='Other'<?= $watVal==='Other'?' selected':'' ?>>Other</option>
                            </select>
                            <div class='other-wrap <?= $watVal==="Other"?"":"d-none" ?>' id='offEditWatWrap<?= $offId ?>'>
                                <input type='text' class='form-control mt-1' name='water_source_other' maxlength='100' value='<?= $watOth ?>' placeholder='Specify' <?= $watVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- SPECIAL STATUS -->
                    <div class='off-edit-hdr mt-1'>
                        <i class='fas fa-star-of-life'></i> Special Status
                    </div>
                    <div class='row'>
                        <div class='col-md-2 mb-3'>
                            <label class='form-label fw-semibold'>PWD? <span class='text-danger'>*</span></label>
                            <select class='form-control' name='is_pwd' id='offEditPwdSel<?= $offId ?>' required>
                                <option value='No'<?= !$offIsPwd?' selected':'' ?>>No</option>
                                <option value='Yes'<?= $offIsPwd?' selected':'' ?>>Yes</option>
                            </select>
                        </div>
                        <div class='col-md-2 mb-3'>
                            <label class='form-label fw-semibold'>Deceased?</label>
                            <select class='form-control' name='is_deceased' id='offEditDecSel<?= $offId ?>'>
                                <option value='No'<?= !$offIsDeceased?' selected':'' ?>>No</option>
                                <option value='Yes'<?= $offIsDeceased?' selected':'' ?>>Yes</option>
                            </select>
                        </div>
                        <div class='col-md-3 mb-3 <?= !$offIsDeceased?"d-none":"" ?>' id='offEditDeathDiv<?= $offId ?>'>
                            <label class='form-label fw-semibold'>Date of Death</label>
                            <input type='date' class='form-control' name='date_of_death' id='offEditDeathDate<?= $offId ?>'
                                   value='<?= htmlspecialchars($row['date_of_death'] ?? '') ?>'>
                        </div>
                    </div>

                    <!-- PWD card picker -->
                    <div class='mb-3 <?= !$offIsPwd?"d-none":"" ?>' id='offEditPwdSection<?= $offId ?>'>
                        <div class='off-edit-pwd-box'>
                            <label class='form-label mb-2'>⚕️ Type of Disability <span class='text-danger'>*</span></label>
                            <div class='row g-2'>
                                <?php foreach($offPwdTypes as [$label, $icon, $bisaya]):
                                    $isChecked = !$offPwdIsOther && ($offCurrentPwdType === $label);
                                ?>
                                <div class='col-6 col-md-3'>
                                    <label class='off-pwd-card-label w-100'>
                                        <input type='radio' name='pwd_type' value='<?= htmlspecialchars($label) ?>'
                                               class='d-none off-pwd-radio-<?= $offId ?>'
                                               <?= $isChecked?'checked':'' ?>>
                                        <div class='off-pwd-card <?= $isChecked?"off-pwd-card-active":"" ?>'>
                                            <i class='fas <?= $icon ?> mb-1 d-block'></i>
                                            <span class='d-block' style='font-size:11px;font-weight:700'><?= $label ?></span>
                                            <span class='d-block text-muted' style='font-size:9px'><?= $bisaya ?></span>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- "Other" free-text -->
                            <div class='mt-2 <?= !($offPwdIsOther||$offCurrentPwdType==="Other")?"d-none":"" ?>' id='offEditPwdOtherWrap<?= $offId ?>'>
                                <input type='text' class='form-control' id='offEditPwdOtherText<?= $offId ?>'
                                       maxlength='200' value='<?= $offPwdIsOther?htmlspecialchars($offCurrentPwdType):'' ?>'
                                       placeholder='Describe the disability…'
                                       <?= ($offPwdIsOther||$offCurrentPwdType==='Other')?'required':'' ?>>
                            </div>
                            <!--
                                FIX 1: Hidden field carries the resolved "Other" value.
                                On submit we rename this to pwd_type and disable the radio
                                so only one pwd_type value reaches the server.
                                On modal reset we restore name="pwd_type_resolved" and
                                re-enable all radios so the next open works correctly.
                            -->
                            <input type='hidden' name='pwd_type_resolved' id='offEditPwdResolved<?= $offId ?>' value=''>
                            <div class='off-field-hint mt-1' id='offEditPwdErr<?= $offId ?>'></div>
                        </div>
                    </div>

                    <!-- HEALTH PROFILE -->
                    <div class='off-edit-hdr mt-1'>
                        <i class='fas fa-heartbeat'></i> Health Profile
                    </div>
                    <div class='row g-2'>
                        <?php
                        $hFields=[['is_smoker','Smoker?'],['is_binge_drinker','Binge Drinker?'],['has_hypertension','Hypertension?'],
                                  ['has_diabetes','Diabetes?'],['has_asthma','Asthma?'],['has_tb','TB?'],
                                  ['has_cancer','Cancer?'],['has_mental_health','Mental Health?']];
                        foreach($hFields as [$hf,$hl]): $hv=($row[$hf]??'No')==='Yes'; ?>
                        <div class='col-6 col-md-3 mb-2'>
                            <label class='form-label' style='font-size:11px;font-weight:600'><?= $hl ?></label>
                            <select class='form-control form-control-sm' name='<?= $hf ?>'>
                                <option value='No'<?= !$hv?' selected':'' ?>>No</option>
                                <option value='Yes'<?= $hv?' selected':'' ?>>Yes</option>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- IMAGE -->
                    <div class='off-edit-hdr mt-1'><i class='fas fa-image'></i> Profile Image</div>
                    <div class='row'>
                        <div class='col-md-6 mb-3'>
                            <label class='form-label fw-semibold'>Upload New Image <span class='text-muted'>(Optional)</span></label>
                            <input type='file' class='form-control' name='image' id='offEditImg<?= $offId ?>'
                                   accept='image/jpeg,image/jpg,image/png,image/gif'>
                            <small class='text-muted'>Leave blank to keep current. Max 2MB</small>
                        </div>
                        <div class='col-md-6 mb-3 text-center'>
                            <label class='form-label fw-semibold d-block'>Current Image</label>
                            <img src='uploads/officials/<?= htmlspecialchars($row['image_path'] ?? 'default.jpg') ?>'
                                 width='90' height='90' class='rounded border' style='object-fit:cover'
                                 onerror="this.onerror=null; this.src='uploads/officials/default_photo_male.jpg'">
                        </div>
                    </div>

                </div><!-- /modal-body -->

                <div class='modal-footer' style='background:#f8fafc'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'><i class='fas fa-times'></i> Close</button>
                    <button type='submit' class='btn' style='background:#0f3c6e;color:#fff' id='offEditSubmit<?= $offId ?>'>
                        <i class='fas fa-save'></i>
                        <span id='offEditSubmitTxt<?= $offId ?>'>Update Official</span>
                        <span class='spinner-border spinner-border-sm d-none' id='offEditSpinner<?= $offId ?>'></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!defined('OFF_EDIT_STYLES')): define('OFF_EDIT_STYLES', true); ?>
<style>
.off-edit-hdr{background:#f0f4f8;border-left:4px solid #0f3c6e;color:#0f3c6e;font-weight:700;font-size:13px;padding:7px 14px;margin-bottom:14px;border-radius:0;display:flex;align-items:center;gap:7px;}
.off-edit-pwd-box{background:#fff8e1;border:1px solid #ffc107;border-left:3px solid #f59e0b;padding:12px;border-radius:4px;}
.off-pwd-card-label{cursor:pointer;margin:0;}
.off-pwd-card{border:2px solid #e2e8f0;border-radius:8px;padding:10px 6px;text-align:center;transition:all .15s;background:#fff;}
.off-pwd-card i{font-size:18px;color:#94a3b8;}
.off-pwd-card-active{border-color:#1a56db!important;background:#eff6ff!important;color:#1a56db!important;}
.off-pwd-card-active i{color:#1a56db!important;}
.other-wrap input{border-left:3px solid #0f3c6e !important;}
.off-field-hint{font-size:11px;color:#dc3545;margin-top:2px;min-height:16px;}
</style>
<?php endif; ?>

<script>
/*
 * IIFE — all state is local to this record (id = <?= $offId ?>).
 * Nothing leaks to window except the two shared helpers
 * (window._offEditToggleOther, window._offEditSES) which are
 * defined once with a guard and are stateless.
 */
(function () {
    'use strict';

    var id = <?= $offId ?>;

    /* ── Shared stateless helpers (defined once across all edit modals) ── */
    if (!window._offEditToggleOther) {
        window._offEditToggleOther = function (sel, wrapId) {
            var wrap = document.getElementById(wrapId);
            if (!wrap) return;
            var inp = wrap.querySelector('input');
            if (sel.value === 'Other') {
                wrap.classList.remove('d-none');
                if (inp) inp.required = true;
            } else {
                wrap.classList.add('d-none');
                if (inp) { inp.required = false; inp.value = ''; }
            }
        };
    }

    var SES = [[10957,'Poor'],[21914,'Low Income'],[43828,'Lower Middle Income'],[76669,'Middle Income'],[131484,'Upper Middle Income']];
    if (!window._offEditSES) {
        window._offEditSES = function (m) {
            if (!m || m <= 0) return '';
            for (var i = 0; i < SES.length; i++) {
                if (m < SES[i][0]) return SES[i][1];
            }
            return 'High Income';
        };
    }

    /* ── Wire "Other" dropdowns ── */
    [
        ['offEditBt'      + id, 'offEditBtWrap'      + id],
        ['offEditRel'     + id, 'offEditRelWrap'     + id],
        ['offEditEth'     + id, 'offEditEthWrap'     + id],
        ['offEditOw'      + id, 'offEditOwWrap'      + id],
        ['offEditMat'     + id, 'offEditMatWrap'     + id],
        ['offEditTol'     + id, 'offEditTolWrap'     + id],
        ['offEditWat'     + id, 'offEditWatWrap'     + id],
        ['offEditOccType' + id, 'offEditOccTypeWrap' + id],
    ].forEach(function (pair) {
        var el = document.getElementById(pair[0]);
        if (el) el.addEventListener('change', function () {
            window._offEditToggleOther(this, pair[1]);
        });
    });

    /* ── Monthly income → annual + SES ── */
    document.getElementById('offEditMonthly' + id).addEventListener('input', function () {
        var m = parseFloat(this.value) || 0;
        document.getElementById('offEditAnnual' + id).value = m > 0 ? (m * 12).toFixed(2) : '';
        document.getElementById('offEditSES'    + id).value = window._offEditSES(m);
    });

    /* ── Birthdate → Age ── */
    var bdEl = document.getElementById('offEditBd' + id);
    bdEl.max = new Date().toISOString().split('T')[0];
    bdEl.addEventListener('change', function () {
        if (!this.value) return;
        var bd = new Date(this.value), today = new Date();
        var age = today.getFullYear() - bd.getFullYear();
        var md  = today.getMonth() - bd.getMonth();
        if (md < 0 || (md === 0 && today.getDate() < bd.getDate())) age--;
        document.getElementById('offEditAge' + id).value = age >= 0 ? age : 0;
    });

    /* ── Term Start → min for Term End + Years in Service ── */
    document.getElementById('offEditTs' + id).addEventListener('change', function () {
        document.getElementById('offEditTe' + id).min = this.value;
        if (!this.value) { document.getElementById('offEditYs' + id).value = ''; return; }
        var start = new Date(this.value), today = new Date();
        var yrs = today.getFullYear() - start.getFullYear();
        var md  = today.getMonth() - start.getMonth();
        if (md < 0 || (md === 0 && today.getDate() < start.getDate())) yrs--;
        document.getElementById('offEditYs' + id).value = yrs >= 0 ? yrs : 0;
    });

    /* ── Education sub-fields ── */
    function toggleEdu() {
        var val  = document.getElementById('offEditEdu' + id).value;
        var show = ['Elementary','High School','Senior High School','College','Vocational'].includes(val);
        document.getElementById('offEditGradeWrap'  + id).style.opacity = show ? '1' : '0.4';
        document.getElementById('offEditSchoolWrap' + id).style.opacity = show ? '1' : '0.4';
    }
    document.getElementById('offEditEdu' + id).addEventListener('change', toggleEdu);
    toggleEdu();

    /* ── PWD section toggle ── */
    document.getElementById('offEditPwdSel' + id).addEventListener('change', function () {
        var sec = document.getElementById('offEditPwdSection' + id);
        if (this.value === 'Yes') {
            sec.classList.remove('d-none');
        } else {
            sec.classList.add('d-none');
            resetPwdCards();
        }
    });

    /* ── PWD card selection ── */
    document.querySelectorAll('.off-pwd-radio-' + id).forEach(function (radio) {
        radio.addEventListener('change', function () {
            /* Highlight active card */
            document.querySelectorAll('.off-pwd-radio-' + id).forEach(function (r) {
                var card = r.closest('label') && r.closest('label').querySelector('.off-pwd-card');
                if (card) card.classList.remove('off-pwd-card-active');
            });
            var myCard = this.closest('label') && this.closest('label').querySelector('.off-pwd-card');
            if (myCard) myCard.classList.add('off-pwd-card-active');

            /* Show/hide "Other" free-text */
            var wrap = document.getElementById('offEditPwdOtherWrap' + id);
            var ot   = document.getElementById('offEditPwdOtherText' + id);
            if (this.value === 'Other') {
                if (wrap) wrap.classList.remove('d-none');
                if (ot)   { ot.required = true; ot.focus(); }
            } else {
                if (wrap) wrap.classList.add('d-none');
                if (ot)   { ot.required = false; ot.value = ''; }
            }
            document.getElementById('offEditPwdErr' + id).textContent = '';
        });
    });

    /* ── Deceased toggle ── */
    document.getElementById('offEditDecSel' + id).addEventListener('change', function () {
        var div = document.getElementById('offEditDeathDiv'  + id);
        var dd  = document.getElementById('offEditDeathDate' + id);
        if (this.value === 'Yes') { div.classList.remove('d-none'); dd.required = true; }
        else { div.classList.add('d-none'); dd.required = false; dd.value = ''; }
    });

    /* ── Image file validation ── */
    document.getElementById('offEditImg' + id).addEventListener('change', function () {
        var f = this.files[0]; if (!f) return;
        if (f.size > 2 * 1024 * 1024) { alert('Image must be under 2MB!'); this.value = ''; return; }
        if (!['image/jpeg','image/jpg','image/png','image/gif'].includes(f.type)) {
            alert('Only JPG, PNG, GIF allowed!'); this.value = ''; return;
        }
    });

    /* ─────────────────────────────────────────────────────────────────────
       FORM SUBMIT
       PWD "Other" safe pattern (FIX 1):
         • If "Other" card is selected, DISABLE the radio (removes it from
           POST data) and RENAME the hidden field from pwd_type_resolved →
           pwd_type with the free-text value.
         • If a non-"Other" card is selected the radio submits its value
           normally — nothing to do.
         • radio.value is NEVER mutated.
    ───────────────────────────────────────────────────────────────────── */
    document.getElementById('editOffForm' + id).addEventListener('submit', function (e) {

        /* Term date sanity */
        var ts = document.getElementById('offEditTs' + id).value;
        var te = document.getElementById('offEditTe' + id).value;
        if (ts && te && new Date(te) <= new Date(ts)) {
            e.preventDefault(); alert('Term End must be after Term Start.'); return false;
        }

        /* PWD validation */
        if (document.getElementById('offEditPwdSel' + id).value === 'Yes') {
            /* :not([disabled]) so a re-submit after a previous "Other" save
               doesn't find the disabled radio and think nothing is selected */
            var checked = document.querySelector('.off-pwd-radio-' + id + ':checked:not([disabled])');
            if (!checked) {
                e.preventDefault();
                document.getElementById('offEditPwdErr' + id).textContent = 'Please select a disability type.';
                document.getElementById('offEditPwdSection' + id).scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            if (checked.value === 'Other') {
                var otText = document.getElementById('offEditPwdOtherText' + id).value.trim();
                if (!otText) {
                    e.preventDefault();
                    document.getElementById('offEditPwdErr' + id).textContent = 'Please describe the disability.';
                    document.getElementById('offEditPwdOtherText' + id).focus();
                    return false;
                }
                /* Safe: disable radio so it is excluded from POST,
                   rename hidden field so it IS included as pwd_type */
                checked.disabled = true;
                var hid = document.getElementById('offEditPwdResolved' + id);
                if (hid) { hid.name = 'pwd_type'; hid.value = otText; }
            }
        }

        /* "Other-specify" fields */
        var oPairs = [
            ['offEditBt'      + id, 'blood_type_other'],
            ['offEditRel'     + id, 'religion_other'],
            ['offEditEth'     + id, 'ethnicity_other'],
            ['offEditOw'      + id, 'house_ownership_other'],
            ['offEditMat'     + id, 'house_material_other'],
            ['offEditTol'     + id, 'toilet_type_other'],
            ['offEditWat'     + id, 'water_source_other'],
            ['offEditOccType' + id, 'occupation_type_other'],
        ];
        for (var pi = 0; pi < oPairs.length; pi++) {
            var sel = document.getElementById(oPairs[pi][0]);
            if (sel && sel.value === 'Other') {
                var inp = document.querySelector(
                    '#editOfficialModal' + id + ' input[name="' + oPairs[pi][1] + '"]'
                );
                if (inp && !inp.value.trim()) {
                    e.preventDefault();
                    alert('Please fill in the "' +
                          oPairs[pi][1].replace('_other','').replace(/_/g,' ') + '" field.');
                    inp.focus(); return false;
                }
            }
        }

        /* Spinner */
        document.getElementById('offEditSubmit'    + id).disabled = true;
        document.getElementById('offEditSubmitTxt' + id).classList.add('d-none');
        document.getElementById('offEditSpinner'   + id).classList.remove('d-none');
    });

    /* ─────────────────────────────────────────────────────────────────────
       MODAL RESET (hidden.bs.modal)
       FIX 2: Re-enable disabled radios and restore hidden field name so
       the next open of THIS modal works correctly.
    ───────────────────────────────────────────────────────────────────── */
    document.getElementById('editOfficialModal' + id).addEventListener('hidden.bs.modal', function () {
        /* Submit button */
        document.getElementById('offEditSubmit'    + id).disabled = false;
        document.getElementById('offEditSubmitTxt' + id).classList.remove('d-none');
        document.getElementById('offEditSpinner'   + id).classList.add('d-none');

        /* PWD — re-enable all radios, restore hidden field */
        resetPwdCards();

        /* Other-wrap inputs */
        document.querySelectorAll('#editOfficialModal' + id + ' .other-wrap').forEach(function (w) {
            w.classList.add('d-none');
            var i = w.querySelector('input');
            if (i) { i.required = false; i.value = ''; }
        });
    });

    /* ── resetPwdCards — used by PWD toggle OFF and by modal reset ── */
    function resetPwdCards() {
        document.querySelectorAll('.off-pwd-radio-' + id).forEach(function (r) {
            r.checked  = false;
            r.disabled = false;    /* FIX 2: re-enable so next submit works */
            var card = r.closest('label') && r.closest('label').querySelector('.off-pwd-card');
            if (card) card.classList.remove('off-pwd-card-active');
        });
        var wrap = document.getElementById('offEditPwdOtherWrap' + id);
        var ot   = document.getElementById('offEditPwdOtherText' + id);
        var hid  = document.getElementById('offEditPwdResolved'  + id);
        if (wrap) wrap.classList.add('d-none');
        if (ot)   { ot.value = ''; ot.required = false; }
        if (hid)  {
            hid.name  = 'pwd_type_resolved'; /* FIX 2: restore original name */
            hid.value = '';
        }
        document.getElementById('offEditPwdErr' + id).textContent = '';
    }

}()); /* end IIFE for record <?= $offId ?> */
</script>