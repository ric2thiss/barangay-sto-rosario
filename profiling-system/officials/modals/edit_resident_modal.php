<?php
/**
 * edit_resident_modal.php
 * Include inside the BUFFERED while loop in resident.php — $row is available.
 *
 * SYNC with register_account.php:
 *   + suffix
 *   + occupation_type (with Other support)
 *   + pwd_type card-picker (replaces pwd_details free-text)
 *   + annual_income: auto-computed from monthly × 12 (read-only)
 *   + socioeconomic_status: read-only display (auto-classified)
 *   + barangay: dropdown with allowed values
 */
$id = $row['id'];

if (!function_exists('selOpt')) {
    function selOpt($val, $current) { return $val === $current ? ' selected' : ''; }
}
if (!function_exists('ynSel')) {
    function ynSel($field, $row) {
        $v=$row[$field]??'No'; $isYes=($v==='Yes'||$v==='1'||$v===1);
        $no=!$isYes?' selected':''; $yes=$isYes?' selected':'';
        return "<option value='No'$no>No</option><option value='Yes'$yes>Yes</option>";
    }
}
if (!function_exists('otherVal')) {
    function otherVal($field, $row, $knownValues) {
        $v=$row[$field]??''; return in_array($v,$knownValues)?$v:($v!==''?'Other':'');
    }
}
if (!function_exists('otherText')) {
    function otherText($field, $row, $knownValues) {
        $v=$row[$field]??''; return !in_array($v,$knownValues)&&$v!==''?htmlspecialchars($v):'';
    }
}

$knownReligion   = ['Roman Catholic','Islam','Iglesia ni Cristo','Seventh-day Adventist','Born Again Christian','Baptist',"Jehovah's Witness",'UCCP'];
$knownEthnicity  = ['Visayan','Cebuano','Mamanwa (IP)','Higaonon (IP)','Manobo (IP)','Maranao','Tagalog','Ilocano','Bisaya','Waray'];
$knownBlood      = ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'];
$knownOwnership  = ['Owned','Rented','Shared','Government'];
$knownMaterial   = ['Concrete','Wood','Mixed','Light Material'];
$knownToilet     = ['With Flush','Without Flush','Shared','None'];
$knownWater      = ['Level 3 (Piped)','Level 2 (Communal Faucet)','Level 1 (Deep Well)','Rainwater','Spring','Bottled Water'];
$knownOccType    = ['Employed','Government Employee','Self-employed','Farmer','OFW','Student','Unemployed','Retired','Homemaker'];
$allowedBarangay = ['Buhang','Caloc-an','Guiasan','Marcos','Poblacion','Santo Niño','Santo Rosario','Taod-oy'];
$pwdTypeList     = ['Visual Impairment','Hearing Impairment','Physical Disability','Intellectual','Psychosocial','Speech Impairment'];

$isDeceased   = ($row['is_deceased']==='Yes'||$row['is_deceased']==1);
$isPwd        = ($row['is_pwd']==='Yes'||$row['is_pwd']==1);
$isSoloParent = ($row['is_solo_parent']==='Yes'||$row['is_solo_parent']==1);

// Determine stored pwd_type — may be in pwd_type or legacy pwd_details column
$storedPwdType = trim($row['pwd_type'] ?? $row['pwd_details'] ?? '');
$isPwdKnown    = in_array($storedPwdType, $pwdTypeList);
$pwdTypeCard   = $isPwdKnown ? $storedPwdType : ($storedPwdType !== '' ? 'Other' : '');
$pwdTypeOther  = (!$isPwdKnown && $storedPwdType !== '') ? htmlspecialchars($storedPwdType) : '';

// Occupation type
$occTypeVal   = otherVal('occupation_type', $row, $knownOccType);
$occTypeOther = otherText('occupation_type', $row, $knownOccType);

// SES — compute if missing
$storedSES = $row['socioeconomic_status'] ?? '';
if (empty($storedSES) && !empty($row['monthly_income'])) {
    $m = (float)$row['monthly_income'];
    if ($m < 10957)      $storedSES = 'Poor';
    elseif ($m < 21914)  $storedSES = 'Low Income';
    elseif ($m < 43828)  $storedSES = 'Lower Middle Income';
    elseif ($m < 76669)  $storedSES = 'Middle Income';
    elseif ($m < 131484) $storedSES = 'Upper Middle Income';
    else                 $storedSES = 'High Income';
}

// Annual income — compute if missing
$storedAnnual = $row['annual_income'] ?? '';
if (($storedAnnual === '' || $storedAnnual === null) && !empty($row['monthly_income']))
    $storedAnnual = round((float)$row['monthly_income'] * 12, 2);
?>

<!-- EDIT MODAL for resident ID <?= $id ?> -->
<div class="modal fade" id="editModal<?= $id ?>" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width:1250px">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:#1a56db">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Edit Resident — <?= htmlspecialchars($row['first_name'].' '.$row['surname']) ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form action="edit_residents.php" method="POST" enctype="multipart/form-data" id="editForm<?= $id ?>">
                <div class="modal-body" style="max-height:78vh;overflow-y:auto;padding:20px 24px">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <!-- ── PERSONAL INFORMATION ──────────────────────── -->
                    <div class="section-hdr"><i class="fas fa-user"></i> Personal Information</div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name"
                                   value="<?= htmlspecialchars($row['first_name']) ?>" required maxlength="50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name"
                                   value="<?= htmlspecialchars($row['middle_name']??'') ?>" maxlength="50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Surname <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="surname"
                                   value="<?= htmlspecialchars($row['surname']) ?>" required maxlength="50">
                        </div>
                        <!-- SUFFIX -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Suffix</label>
                            <select class="form-control" name="suffix">
                                <option value="">None</option>
                                <?php foreach(['Jr.','Sr.','II','III','IV','V'] as $sfx): ?>
                                <option value="<?= $sfx ?>"<?= selOpt($sfx, $row['suffix']??'') ?>><?= $sfx ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Birthdate <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="birthdate"
                                   id="editBirthdate<?= $id ?>"
                                   value="<?= htmlspecialchars($row['birthdate']) ?>"
                                   min="1900-01-01" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">Age <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="age"
                                   id="editAge<?= $id ?>" value="<?= (int)$row['age'] ?>" readonly required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Birthplace <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="birthplace"
                                   value="<?= htmlspecialchars($row['birthplace']) ?>" required maxlength="100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Contact No.</label>
                            <input type="text" class="form-control" name="contact_no"
                                   value="<?= htmlspecialchars($row['contact_no']??'') ?>"
                                   placeholder="09XXXXXXXXX" maxlength="20">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control" name="email"
                                   value="<?= htmlspecialchars($row['email']??'') ?>"
                                   placeholder="example@gmail.com" maxlength="150">
                        </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Sex <span class="text-danger">*</span></label>
                            <select class="form-control" name="sex" required>
                                <option value="Male"<?=  selOpt('Male',  $row['sex']) ?>>Male</option>
                                <option value="Female"<?= selOpt('Female',$row['sex']) ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Civil Status <span class="text-danger">*</span></label>
                            <select class="form-control" name="civil_status" required>
                                <?php foreach(['Single','Married','Widowed','Separated','Annulled','Live-in'] as $cs): ?>
                                <option value="<?= $cs ?>"<?= selOpt($cs,$row['civil_status']) ?>><?= $cs ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Nationality</label>
                            <input type="text" class="form-control" name="nationality"
                                   value="<?= htmlspecialchars($row['nationality']??'Filipino') ?>" maxlength="50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Blood Type</label>
                            <?php $btVal=otherVal('blood_type',$row,$knownBlood); $btOther=otherText('blood_type',$row,$knownBlood); ?>
                            <select class="form-control" name="blood_type" id="editBloodType<?= $id ?>"
                                    onchange="modalToggleOther(this,'editBloodTypeOtherWrap<?= $id ?>')">
                                <option value="">Select</option>
                                <?php foreach($knownBlood as $b): ?>
                                <option value="<?= $b ?>"<?= selOpt($b,$btVal) ?>><?= $b ?></option>
                                <?php endforeach; ?>
                                <option value="Other"<?= $btVal==='Other'?' selected':'' ?>>Other</option>
                            </select>
                            <div class="other-wrap <?= $btVal==='Other'?'':'d-none' ?>" id="editBloodTypeOtherWrap<?= $id ?>">
                                <input type="text" class="form-control mt-1" name="blood_type_other"
                                       maxlength="20" value="<?= $btOther ?>" placeholder="Specify"
                                       <?= $btVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- ── DEMOGRAPHIC INFORMATION ───────────────────── -->
                    <div class="section-hdr" style="background:#e8f0fe;border-left-color:#1a56db;color:#1a56db">
                        <i class="fas fa-id-card"></i> Demographic Information
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Religion</label>
                            <?php $relVal=otherVal('religion',$row,$knownReligion); $relOther=otherText('religion',$row,$knownReligion); ?>
                            <select class="form-control" name="religion" id="editReligion<?= $id ?>"
                                    onchange="modalToggleOther(this,'editReligionOtherWrap<?= $id ?>')">
                                <option value="">Select</option>
                                <?php foreach($knownReligion as $r): ?>
                                <option value="<?= $r ?>"<?= selOpt($r,$relVal) ?>><?= $r ?></option>
                                <?php endforeach; ?>
                                <option value="Other"<?= $relVal==='Other'?' selected':'' ?>>Other (specify)</option>
                            </select>
                            <div class="other-wrap <?= $relVal==='Other'?'':'d-none' ?>" id="editReligionOtherWrap<?= $id ?>">
                                <input type="text" class="form-control mt-1" name="religion_other"
                                       maxlength="100" value="<?= $relOther ?>" placeholder="Type religion"
                                       <?= $relVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Ethnicity / IP Group</label>
                            <?php $ethVal=otherVal('ethnicity',$row,$knownEthnicity); $ethOther=otherText('ethnicity',$row,$knownEthnicity); ?>
                            <select class="form-control" name="ethnicity" id="editEthnicity<?= $id ?>"
                                    onchange="modalToggleOther(this,'editEthnicityOtherWrap<?= $id ?>')">
                                <option value="">Select</option>
                                <?php foreach($knownEthnicity as $e): ?>
                                <option value="<?= $e ?>"<?= selOpt($e,$ethVal) ?>><?= $e ?></option>
                                <?php endforeach; ?>
                                <option value="Other"<?= $ethVal==='Other'?' selected':'' ?>>Other (specify)</option>
                            </select>
                            <div class="other-wrap <?= $ethVal==='Other'?'':'d-none' ?>" id="editEthnicityOtherWrap<?= $id ?>">
                                <input type="text" class="form-control mt-1" name="ethnicity_other"
                                       maxlength="100" value="<?= $ethOther ?>" placeholder="Type ethnicity"
                                       <?= $ethVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">PhilHealth (PHIC) No.</label>
                            <input type="text" class="form-control" name="philhealth_no"
                                   value="<?= htmlspecialchars($row['philhealth_no']??'') ?>"
                                   maxlength="30" placeholder="18-000000000-0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">PhilHealth Membership</label>
                            <select class="form-control" name="membership_type">
                                <option value="">None / Not a member</option>
                                <?php foreach(['Private','Government','NHTS','Senior Citizen','OFW','Self-employed'] as $mt): ?>
                                <option value="<?= $mt ?>"<?= selOpt($mt,$row['membership_type']??'') ?>><?= $mt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
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
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">Residency (yrs)</label>
                            <input type="number" class="form-control" name="length_of_residency"
                                   value="<?= htmlspecialchars($row['length_of_residency']??'') ?>"
                                   min="0" max="120">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">NHTS?</label>
                            <select class="form-control" name="is_nhts"><?= ynSel('is_nhts',$row) ?></select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">4Ps?</label>
                            <select class="form-control" name="is_4ps"><?= ynSel('is_4ps',$row) ?></select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Solo Parent?</label>
                            <select class="form-control" name="is_solo_parent">
                                <option value="No"<?= !$isSoloParent?' selected':'' ?>>No</option>
                                <option value="Yes"<?= $isSoloParent?' selected':'' ?>>Yes – Single Mother / Single Father</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Family Planning?</label>
                            <select class="form-control" name="family_planning"><?= ynSel('family_planning',$row) ?></select>
                        </div>
                    </div>

                    <!-- ── ADDRESS INFORMATION ───────────────────────── -->
                    <div class="section-hdr mt-1"><i class="fas fa-map-marker-alt"></i> Address Information</div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">HH No.</label>
                            <input type="text" class="form-control" name="household_no"
                                   value="<?= htmlspecialchars($row['household_no']??'') ?>" maxlength="20">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">Purok <span class="text-danger">*</span></label>
                            <select class="form-control" name="purok" required>
                                <option value="">Select</option>
                                <?php for($i=1;$i<=10;$i++): ?>
                                <option value="Purok <?= $i ?>"<?= selOpt("Purok $i",$row['purok']) ?>>Purok <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <!-- Barangay dropdown — same allowed values as register_account.php -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Barangay <span class="text-danger">*</span></label>
                            <select class="form-control" name="barangay" required>
                                <option value="">Select</option>
                                <?php foreach($allowedBarangay as $bg): ?>
                                <option value="<?= $bg ?>"<?= selOpt($bg,$row['barangay']) ?>><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Municipality</label>
                            <input type="text" class="form-control" name="municipality"
                                   value="<?= htmlspecialchars($row['municipality']) ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">Province</label>
                            <input type="text" class="form-control" name="province"
                                   value="<?= htmlspecialchars($row['province']) ?>">
                        </div>
                    </div>

                    <!-- ── HOUSING ───────────────────────────────────── -->
                    <div class="row">
                        <?php
                        $owVal=otherVal('house_ownership',$row,$knownOwnership); $owOther=otherText('house_ownership',$row,$knownOwnership);
                        $matVal=otherVal('house_material',$row,$knownMaterial);  $matOther=otherText('house_material',$row,$knownMaterial);
                        $tolVal=otherVal('toilet_type',$row,$knownToilet);       $tolOther=otherText('toilet_type',$row,$knownToilet);
                        $watVal=otherVal('water_source',$row,$knownWater);       $watOther=otherText('water_source',$row,$knownWater);
                        ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">House Ownership</label>
                            <select class="form-control" name="house_ownership" id="editHouseOwnership<?= $id ?>"
                                    onchange="modalToggleOther(this,'editHouseOwnershipOtherWrap<?= $id ?>')">
                                <option value="">Select</option>
                                <?php foreach($knownOwnership as $o): ?>
                                <option value="<?= $o ?>"<?= selOpt($o,$owVal) ?>><?= $o ?></option>
                                <?php endforeach; ?>
                                <option value="Other"<?= $owVal==='Other'?' selected':'' ?>>Other (specify)</option>
                            </select>
                            <div class="other-wrap <?= $owVal==='Other'?'':'d-none' ?>" id="editHouseOwnershipOtherWrap<?= $id ?>">
                                <input type="text" class="form-control mt-1" name="house_ownership_other"
                                       maxlength="100" value="<?= $owOther ?>" placeholder="Specify"
                                       <?= $owVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">House Material</label>
                            <select class="form-control" name="house_material" id="editHouseMaterial<?= $id ?>"
                                    onchange="modalToggleOther(this,'editHouseMaterialOtherWrap<?= $id ?>')">
                                <option value="">Select</option>
                                <?php foreach($knownMaterial as $m): ?>
                                <option value="<?= $m ?>"<?= selOpt($m,$matVal) ?>><?= $m ?></option>
                                <?php endforeach; ?>
                                <option value="Other"<?= $matVal==='Other'?' selected':'' ?>>Other (specify)</option>
                            </select>
                            <div class="other-wrap <?= $matVal==='Other'?'':'d-none' ?>" id="editHouseMaterialOtherWrap<?= $id ?>">
                                <input type="text" class="form-control mt-1" name="house_material_other"
                                       maxlength="100" value="<?= $matOther ?>" placeholder="Specify"
                                       <?= $matVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Toilet Type</label>
                            <select class="form-control" name="toilet_type" id="editToiletType<?= $id ?>"
                                    onchange="modalToggleOther(this,'editToiletTypeOtherWrap<?= $id ?>')">
                                <option value="">Select</option>
                                <?php foreach($knownToilet as $t): ?>
                                <option value="<?= $t ?>"<?= selOpt($t,$tolVal) ?>><?= $t ?></option>
                                <?php endforeach; ?>
                                <option value="Other"<?= $tolVal==='Other'?' selected':'' ?>>Other (specify)</option>
                            </select>
                            <div class="other-wrap <?= $tolVal==='Other'?'':'d-none' ?>" id="editToiletTypeOtherWrap<?= $id ?>">
                                <input type="text" class="form-control mt-1" name="toilet_type_other"
                                       maxlength="100" value="<?= $tolOther ?>" placeholder="Specify"
                                       <?= $tolVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Water Source</label>
                            <select class="form-control" name="water_source" id="editWaterSource<?= $id ?>"
                                    onchange="modalToggleOther(this,'editWaterSourceOtherWrap<?= $id ?>')">
                                <option value="">Select</option>
                                <?php foreach($knownWater as $w): ?>
                                <option value="<?= $w ?>"<?= selOpt($w,$watVal) ?>><?= $w ?></option>
                                <?php endforeach; ?>
                                <option value="Other"<?= $watVal==='Other'?' selected':'' ?>>Other (specify)</option>
                            </select>
                            <div class="other-wrap <?= $watVal==='Other'?'':'d-none' ?>" id="editWaterSourceOtherWrap<?= $id ?>">
                                <input type="text" class="form-control mt-1" name="water_source_other"
                                       maxlength="100" value="<?= $watOther ?>" placeholder="Specify"
                                       <?= $watVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- ── OCCUPATION & INCOME ───────────────────────── -->
                    <div class="section-hdr mt-1"><i class="fas fa-briefcase"></i> Occupation & Income</div>
                    <div class="row">
                        <!-- OCCUPATION TYPE -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Occupation Type <span class="text-danger">*</span></label>
                            <select class="form-control" name="occupation_type" id="editOccType<?= $id ?>" required
                                    onchange="modalToggleOther(this,'editOccTypeOtherWrap<?= $id ?>')">
                                <option value="">Select</option>
                                <?php foreach($knownOccType as $ot): ?>
                                <option value="<?= $ot ?>"<?= selOpt($ot,$occTypeVal) ?>><?= $ot ?></option>
                                <?php endforeach; ?>
                                <option value="Other"<?= $occTypeVal==='Other'?' selected':'' ?>>Other (specify)</option>
                            </select>
                            <div class="other-wrap <?= $occTypeVal==='Other'?'':'d-none' ?>" id="editOccTypeOtherWrap<?= $id ?>">
                                <input type="text" class="form-control mt-1" name="occupation_type_other"
                                       maxlength="100" value="<?= $occTypeOther ?>" placeholder="Specify type"
                                       <?= $occTypeVal==='Other'?'required':'' ?>>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Occupation / Job Title</label>
                            <input type="text" class="form-control" name="occupation"
                                   value="<?= htmlspecialchars($row['occupation']??'') ?>"
                                   maxlength="100" placeholder="e.g. Teacher, Farmer">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Household Position <span class="text-danger">*</span></label>
                            <select class="form-control" name="household_position" required>
                                <option value="">Select</option>
                                <?php
                                $hpOptions=['Head','Spouse','Son','Daughter','Grandson','Granddaughter',
                                            'Father','Mother','Brother','Sister','Uncle','Aunt',
                                            'Nephew','Niece','Cousin','Son-in-law','Daughter-in-law',
                                            'Brother-in-law','Sister-in-law','Grandparent','Other'];
                                foreach($hpOptions as $hp):
                                    $label=$hp==='Head'?'Head of Family':($hp==='Other'?'Other Relative':$hp);
                                ?>
                                <option value="<?= $hp ?>"<?= selOpt($hp,$row['household_position']??'') ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Monthly Income (₱)</label>
                            <input type="number" step="0.01" class="form-control" name="monthly_income"
                                   id="editMonthlyIncome<?= $id ?>"
                                   value="<?= htmlspecialchars($row['monthly_income']??'') ?>"
                                   placeholder="0.00" min="0"
                                   oninput="editComputeIncome(<?= $id ?>)">
                            <small class="text-muted" style="font-size:10px">Annual income &amp; SES auto-computed</small>
                        </div>
                        <!-- Annual income: read-only, auto-computed -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Annual Income (₱)
                                <small class="text-muted fw-normal">(auto)</small>
                            </label>
                            <input type="number" step="0.01" class="form-control bg-light" name="annual_income"
                                   id="editAnnualIncome<?= $id ?>" readonly
                                   value="<?= htmlspecialchars($storedAnnual) ?>" placeholder="Auto-computed">
                        </div>
                        <!-- SES: read-only display -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Socioeconomic Status
                                <small class="text-muted fw-normal">(auto)</small>
                            </label>
                            <input type="text" class="form-control bg-light" id="editSESDisplay<?= $id ?>"
                                   readonly value="<?= htmlspecialchars($storedSES) ?>"
                                   placeholder="Based on monthly income">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">Total Household</label>
                            <input type="number" class="form-control" name="total_household"
                                   value="<?= (int)($row['total_household']??1) ?>" min="1" max="50">
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

                    <!-- ── VOTER & EDUCATION ─────────────────────────── -->
                    <div class="section-hdr mt-1"><i class="fas fa-graduation-cap"></i> Voter & Education</div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Voter Status <span class="text-danger">*</span></label>
                            <select class="form-control" name="voters_status" required>
                                <option value="No"<?=  selOpt('No', $row['voters_status']??'No') ?>>Not Registered</option>
                                <option value="Yes"<?= selOpt('Yes',$row['voters_status']??'No') ?>>Registered Voter</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-semibold">Educational Attainment</label>
                            <select class="form-control" name="educational_attainment"
                                    id="editEduAttain<?= $id ?>"
                                    onchange="editToggleEduFields(<?= $id ?>)">
                                <option value="">Select</option>
                                <?php foreach(['No Formal Education','Elementary','High School','Senior High School','College','Vocational','Post Graduate'] as $ea): ?>
                                <option value="<?= $ea ?>"<?= selOpt($ea,$row['educational_attainment']??'') ?>><?= $ea ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3" id="editGradeLevelWrap<?= $id ?>">
                            <label class="form-label fw-semibold">Grade / Year Level</label>
                            <input type="text" class="form-control" name="grade_level"
                                   value="<?= htmlspecialchars($row['grade_level']??'') ?>"
                                   maxlength="50" placeholder="e.g. Grade 7">
                        </div>
                        <div class="col-md-3 mb-3" id="editSchoolNameWrap<?= $id ?>">
                            <label class="form-label fw-semibold">School Name</label>
                            <input type="text" class="form-control" name="school_name"
                                   value="<?= htmlspecialchars($row['school_name']??'') ?>"
                                   maxlength="150" placeholder="e.g. Magallanes NHS">
                        </div>
                    </div>
                    <?php
                    $curEdu = $row['educational_attainment'] ?? '';
                    $showGrad = in_array($curEdu, ['College','Vocational','Post Graduate']);
                    $knownCourses = ['BS Information Technology','BS Computer Science','BS Education','BS Nursing','BS Accountancy','BS Business Administration','BS Criminology','BS Civil Engineering','BS Agriculture','BS Social Work','BS Psychology','Associate','TESDA NC II'];
                    $curCourse = $row['course'] ?? '';
                    $courseIsOther = ($curCourse !== '' && !in_array($curCourse, $knownCourses));
                    $curCourseOther = $row['course_other'] ?? '';
                    $knownElig = ['Civil Service Professional','Civil Service Sub-Professional','PRC License','Bar','Board Passer'];
                    $curElig = $row['eligibility'] ?? '';
                    $eligIsOther = ($curElig !== '' && !in_array($curElig, $knownElig));
                    $curEligOther = $row['eligibility_other'] ?? '';
                    ?>
                    <!-- Graduate sub-fields -->
                    <div class="row <?= $showGrad ? '' : 'd-none' ?>" id="editGraduateFields<?= $id ?>">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Course / Program</label>
                            <select class="form-control" name="course" id="editCourse<?= $id ?>"
                                    onchange="editToggleCourseOther(<?= $id ?>)">
                                <option value="">Select Course</option>
                                <?php foreach($knownCourses as $c): ?>
                                <option value="<?= $c ?>"<?= selOpt($c,$curCourse) ?>><?= $c ?></option>
                                <?php endforeach; ?>
                                <option value="Others"<?= ($courseIsOther || $curCourse==='Others')?' selected':'' ?>>Others (specify)</option>
                            </select>
                            <div class="<?= ($courseIsOther || $curCourse==='Others') ? '' : 'd-none' ?> mt-1" id="editCourseOtherWrap<?= $id ?>">
                                <input type="text" class="form-control" name="course_other"
                                       maxlength="150" value="<?= htmlspecialchars($curCourseOther ?: ($courseIsOther ? $curCourse : '')) ?>"
                                       placeholder="Specify course/program"
                                       style="border-left:3px solid #0e9f6e!important">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Graduation Date</label>
                            <input type="date" class="form-control" name="graduation_date"
                                   value="<?= htmlspecialchars($row['graduation_date']??'') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Eligibility</label>
                            <select class="form-control" name="eligibility" id="editEligibility<?= $id ?>"
                                    onchange="editToggleEligOther(<?= $id ?>)">
                                <option value="">None / Not Applicable</option>
                                <?php foreach($knownElig as $el): ?>
                                <option value="<?= $el ?>"<?= selOpt($el,$curElig) ?>><?= $el ?></option>
                                <?php endforeach; ?>
                                <option value="Others"<?= ($eligIsOther || $curElig==='Others')?' selected':'' ?>>Others (specify)</option>
                            </select>
                            <div class="<?= ($eligIsOther || $curElig==='Others') ? '' : 'd-none' ?> mt-1" id="editEligOtherWrap<?= $id ?>">
                                <input type="text" class="form-control" name="eligibility_other"
                                       maxlength="150" value="<?= htmlspecialchars($curEligOther ?: ($eligIsOther ? $curElig : '')) ?>"
                                       placeholder="Specify eligibility"
                                       style="border-left:3px solid #0e9f6e!important">
                            </div>
                        </div>
                    </div>

                    <!-- ── SPECIAL STATUS ────────────────────────────── -->
                    <div class="section-hdr mt-1" style="background:#fef9ec;border-left-color:#c8963e;color:#92400e">
                        <i class="fas fa-star-of-life"></i> Special Status
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">PWD? <span class="text-danger">*</span></label>
                            <select class="form-control" name="is_pwd" id="editIsPwd<?= $id ?>" required
                                    onchange="editTogglePwd(<?= $id ?>)">
                                <option value="No"<?=  !$isPwd?' selected':'' ?>>No</option>
                                <option value="Yes"<?= $isPwd?' selected':'' ?>>Yes</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">Newborn?</label>
                            <select class="form-control" name="is_newborn">
                                <?php $inb=($row['is_newborn']==='Yes'||$row['is_newborn']==1); ?>
                                <option value="No"<?=  !$inb?' selected':'' ?>>No</option>
                                <option value="Yes"<?= $inb?' selected':'' ?>>Yes</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label fw-semibold">Deceased?</label>
                            <select class="form-control" name="is_deceased" id="editIsDeceased<?= $id ?>"
                                    onchange="editToggleDeceased(<?= $id ?>)">
                                <option value="No"<?=  !$isDeceased?' selected':'' ?>>No</option>
                                <option value="Yes"<?= $isDeceased?' selected':'' ?>>Yes</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 <?= !$isDeceased?'d-none':'' ?>" id="editDeathDateDiv<?= $id ?>">
                            <label class="form-label fw-semibold">Date of Death</label>
                            <input type="date" class="form-control" name="date_of_death"
                                   id="editDateOfDeath<?= $id ?>"
                                   value="<?= htmlspecialchars($row['date_of_death']??'') ?>"
                                   <?= $isDeceased?'required':'' ?>>
                        </div>
                    </div>

                    <!-- PWD TYPE card-picker -->
                    <div class="mb-3 <?= !$isPwd?'d-none':'' ?>" id="editPwdSection<?= $id ?>">
                        <div class="pwd-box">
                            <label class="form-label mb-2">
                                ⚕️ Type of Disability <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2">
                                <?php
                                $pwdTypeIcons=['Visual Impairment'=>'fa-eye-slash','Hearing Impairment'=>'fa-deaf',
                                               'Physical Disability'=>'fa-wheelchair','Intellectual'=>'fa-brain',
                                               'Psychosocial'=>'fa-head-side-virus','Speech Impairment'=>'fa-comment-slash',
                                               'Other'=>'fa-ellipsis-h'];
                                foreach($pwdTypeIcons as $pt => $icon):
                                    $checked = ($pwdTypeCard === $pt) ? 'checked' : '';
                                ?>
                                <div class="col-6 col-md-3">
                                    <label class="pwd-card-label w-100">
                                        <input type="radio" name="pwd_type" value="<?= $pt ?>"
                                               class="d-none pwd-type-radio-<?= $id ?>" <?= $checked ?>
                                               onchange="editPwdTypeChange(<?= $id ?>, this)">
                                        <div class="pwd-card <?= $checked?'pwd-card-active':'' ?>">
                                            <i class="fas <?= $icon ?> mb-1 d-block"></i>
                                            <span><?= $pt ?></span>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- "Other – specify" free text -->
                            <div class="mt-2 <?= ($pwdTypeCard==='Other')?'':'d-none' ?>"
                                 id="editPwdOtherWrap<?= $id ?>">
                                <input type="text" class="form-control" id="editPwdOtherText<?= $id ?>"
                                       maxlength="200" value="<?= $pwdTypeOther ?>"
                                       placeholder="Please describe the disability…"
                                       <?= ($pwdTypeCard==='Other')?'required':'' ?>>
                            </div>
                            <div class="field-hint mt-1" id="editPwdErr<?= $id ?>"></div>
                        </div>
                    </div>

                    <!-- ── HEALTH PROFILE ────────────────────────────── -->
                    <div class="section-hdr mt-1" style="background:#fff3f3;border-left-color:#e02424;color:#7f1d1d">
                        <i class="fas fa-heartbeat"></i> Health Profile
                    </div>
                    <div class="row g-2">
                        <?php
                        $hFields=[['is_smoker','Smoker?'],['is_binge_drinker','Binge Drinker?'],
                                  ['has_hypertension','Hypertension (HPN)?'],['has_diabetes','Diabetes (DM)?'],
                                  ['has_asthma','Asthma?'],['has_tb','Tuberculosis (TB)?'],
                                  ['has_cancer','Cancer?'],['has_mental_health','Mental Health?']];
                        foreach($hFields as [$hf,$hl]):
                            $hv=(($row[$hf]??'No')==='Yes'||($row[$hf]??0)==1);
                        ?>
                        <div class="col-6 col-md-3 mb-2">
                            <label class="form-label" style="font-size:11px;font-weight:600"><?= $hl ?></label>
                            <select class="form-control form-control-sm" name="<?= $hf ?>">
                                <option value="No"<?= !$hv?' selected':'' ?>>No</option>
                                <option value="Yes"<?= $hv?' selected':'' ?>>Yes</option>
                            </select>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- ── PROFILE IMAGE ─────────────────────────────── -->
                    <div class="section-hdr mt-1"><i class="fas fa-image"></i> Profile Image</div>
                    <div class="row align-items-center">
                        <div class="col-md-3 mb-3 text-center">
                            <label class="form-label fw-semibold d-block">Current Photo</label>
                            <img src="uploads/residents/<?= htmlspecialchars($row['image_path']) ?>"
                                 id="editCurrentPhoto<?= $id ?>" width="90" height="90"
                                 class="rounded border shadow-sm" style="object-fit:cover"
                                 onerror="this.onerror=null; this.src='uploads/residents/default_photo_male.jpg'" alt="Photo">
                        </div>
                        <div class="col-md-9 mb-3">
                            <label class="form-label fw-semibold">Replace Photo <span class="text-muted fw-normal">(Optional)</span></label>
                            <div class="edit-photo-wrapper" id="editPhotoWrapper<?= $id ?>">
                                <div class="edit-photo-inner">
                                    <div class="edit-photo-preview-box" id="editPhotoPreviewBox<?= $id ?>">
                                        <div class="edit-photo-placeholder" id="editPhotoPlaceholder<?= $id ?>">
                                            <i class="fas fa-camera-retro"></i>
                                            <span>New photo</span>
                                        </div>
                                        <img id="editPhotoPreviewImg<?= $id ?>" src="" alt=""
                                             style="display:none;width:100%;height:100%;object-fit:cover;">
                                        <button type="button" class="edit-remove-photo d-none"
                                                id="editRemovePhotoBtn<?= $id ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="edit-photo-btn-group">
                                        <label class="btn btn-sm btn-outline-secondary mb-0"
                                               style="cursor:pointer;font-size:11px;">
                                            <i class="fas fa-upload me-1"></i> Upload
                                            <input type="file" name="image_path" id="editImageFile<?= $id ?>"
                                                   accept="image/jpeg,image/jpg,image/png,image/gif" class="d-none">
                                        </label>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                id="editOpenCameraBtn<?= $id ?>" style="font-size:11px;">
                                            <i class="fas fa-camera me-1"></i> Camera
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="camera_image" id="editCameraImage<?= $id ?>" value="">
                            </div>
                            <small class="text-muted">Leave blank to keep current photo. Max 2MB · JPG PNG GIF</small>
                        </div>
                    </div>

                </div><!-- /modal-body -->

                <div class="modal-footer" style="background:#f8fafc">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn<?= $id ?>">
                        <i class="fas fa-save"></i> Update Resident
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CAMERA MODAL for edit (per-resident) -->
<div class="modal fade" id="editCameraModal<?= $id ?>" tabindex="-1"
     data-bs-backdrop="static" style="z-index:1070 !important;">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header" style="background:#1a56db;color:#fff;padding:10px 16px;">
                <h6 class="modal-title mb-0">
                    <i class="fas fa-camera me-2"></i> Capture Photo
                    — <?= htmlspecialchars($row['first_name'].' '.$row['surname']) ?>
                </h6>
                <button type="button" class="btn-close btn-close-white" id="editCloseCameraBtn<?= $id ?>"></button>
            </div>
            <div class="modal-body" style="padding:16px;background:#1a1a2e;">
                <div id="editCamError<?= $id ?>" style="display:none;color:#f8d7da;text-align:center;padding:24px 10px;">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                    <span id="editCamErrorMsg<?= $id ?>">Camera not available.</span>
                </div>
                <div id="editCamVideoWrap<?= $id ?>" style="position:relative;">
                    <video id="editCamVideo<?= $id ?>" autoplay playsinline muted
                           style="width:100%;border-radius:4px;background:#000;display:block;"></video>
                    <button type="button" id="editCamSwitchBtn<?= $id ?>"
                            style="position:absolute;top:8px;right:8px;background:rgba(255,255,255,0.2);color:#fff;border:none;border-radius:50%;width:34px;height:34px;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <img id="editCamSnapshot<?= $id ?>" src="" alt=""
                     style="display:none;width:100%;border-radius:4px;margin-top:10px;">
                <canvas id="editCamCanvas<?= $id ?>" style="display:none;"></canvas>
                <div style="display:flex;justify-content:center;gap:10px;margin-top:12px;flex-wrap:wrap;">
                    <button type="button" id="editCamCaptureBtn<?= $id ?>"
                            style="background:#1a56db;color:#fff;border:none;padding:9px 26px;border-radius:30px;font-weight:600;font-size:13px;cursor:pointer;">
                        <i class="fas fa-camera me-1"></i> Capture
                    </button>
                    <button type="button" id="editCamRetakeBtn<?= $id ?>"
                            style="display:none;background:#6c757d;color:#fff;border:none;padding:9px 18px;border-radius:30px;font-weight:500;font-size:13px;cursor:pointer;">
                        <i class="fas fa-redo me-1"></i> Retake
                    </button>
                    <button type="button" id="editCamUseBtn<?= $id ?>"
                            style="display:none;background:#28a745;color:#fff;border:none;padding:9px 18px;border-radius:30px;font-weight:500;font-size:13px;cursor:pointer;">
                        <i class="fas fa-check me-1"></i> Use Photo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#editModal<?= $id ?> .section-hdr{background:#f0f4f8;border-left:4px solid #1a56db;color:#1a56db;font-weight:700;font-size:13px;padding:7px 14px;margin-bottom:14px;border-radius:3px;display:flex;align-items:center;gap:7px;}
#editModal<?= $id ?> .other-wrap input{border-left:3px solid #1a56db !important;}
#editModal<?= $id ?> .pwd-box{background:#fff8e1;border:1px solid #ffc107;border-left:3px solid #f59e0b;padding:12px;border-radius:4px;}
.pwd-card-label{cursor:pointer;margin:0;}
.pwd-card{border:2px solid #e2e8f0;border-radius:8px;padding:10px 6px;text-align:center;font-size:11px;font-weight:600;color:#64748b;transition:all .15s;background:#fff;}
.pwd-card i{font-size:18px;color:#94a3b8;}
.pwd-card-active,.pwd-type-radio-<?= $id ?>:checked + .pwd-card{border-color:#1a56db !important;background:#eff6ff !important;color:#1a56db !important;}
.pwd-card-active i,.pwd-type-radio-<?= $id ?>:checked + .pwd-card i{color:#1a56db !important;}
.edit-photo-wrapper{border:1.5px dashed #b0bec5;border-radius:4px;padding:6px;background:#f8f9fa;}
.edit-photo-wrapper:hover{border-color:#1a56db;}
.edit-photo-inner{display:flex;align-items:stretch;gap:6px;}
.edit-photo-preview-box{width:72px;min-width:72px;height:72px;background:#e9ecef;border:1px solid #dee2e6;border-radius:3px;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;flex-shrink:0;}
.edit-photo-placeholder{text-align:center;color:#adb5bd;padding:4px;}
.edit-photo-placeholder i{font-size:20px;display:block;margin-bottom:2px;}
.edit-photo-placeholder span{font-size:9px;display:block;line-height:1.2;}
.edit-remove-photo{position:absolute;top:2px;right:2px;background:rgba(220,53,69,.85);color:#fff;border:none;border-radius:50%;width:18px;height:18px;font-size:9px;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.edit-photo-btn-group{display:flex;flex-direction:column;gap:4px;flex:1;justify-content:center;}
.edit-photo-btn-group .btn{width:100%;text-align:left;white-space:nowrap;}
.field-hint{font-size:11px;color:#dc3545;margin-top:2px;min-height:16px;}
</style>

<script>
(function () {
'use strict';
var RID = <?= $id ?>;

/* ── Expose shared helpers if not already defined ── */
if (typeof window.modalToggleOther !== 'function') {
    window.modalToggleOther = function (sel, wrapId) {
        var wrap = document.getElementById(wrapId); if (!wrap) return;
        var inp = wrap.querySelector('input');
        if (sel.value === 'Other') { wrap.classList.remove('d-none'); if (inp) { inp.required = true; setTimeout(function(){ inp.focus(); }, 50); } }
        else { wrap.classList.add('d-none'); if (inp) { inp.required = false; inp.value = ''; } }
    };
}

window.editToggleEduFields = window.editToggleEduFields || function (id) {
    var el = document.getElementById('editEduAttain' + id); if (!el) return;
    var show = ['Elementary','High School','Senior High School','College','Vocational','Post Graduate'].includes(el.value);
    document.getElementById('editGradeLevelWrap' + id).style.opacity = show ? '1' : '0.4';
    document.getElementById('editSchoolNameWrap' + id).style.opacity  = show ? '1' : '0.4';
    // Graduate sub-fields
    var gradRow = document.getElementById('editGraduateFields' + id);
    if (gradRow) {
        if (['College','Vocational','Post Graduate'].includes(el.value)) {
            gradRow.classList.remove('d-none');
        } else {
            gradRow.classList.add('d-none');
        }
    }
};
window.editToggleCourseOther = window.editToggleCourseOther || function(id) {
    var sel = document.getElementById('editCourse' + id);
    var wrap = document.getElementById('editCourseOtherWrap' + id);
    if (!sel || !wrap) return;
    if (sel.value === 'Others') { wrap.classList.remove('d-none'); }
    else { wrap.classList.add('d-none'); var inp = wrap.querySelector('input'); if(inp) inp.value=''; }
};
window.editToggleEligOther = window.editToggleEligOther || function(id) {
    var sel = document.getElementById('editEligibility' + id);
    var wrap = document.getElementById('editEligOtherWrap' + id);
    if (!sel || !wrap) return;
    if (sel.value === 'Others') { wrap.classList.remove('d-none'); }
    else { wrap.classList.add('d-none'); var inp = wrap.querySelector('input'); if(inp) inp.value=''; }
};
editToggleEduFields(RID);

/* ── Income auto-compute ── */
var SES_B = [[10957,'Poor'],[21914,'Low Income'],[43828,'Lower Middle Income'],[76669,'Middle Income'],[131484,'Upper Middle Income']];
window.editComputeIncome = window.editComputeIncome || function (id) {
    var m = parseFloat(document.getElementById('editMonthlyIncome' + id).value) || 0;
    document.getElementById('editAnnualIncome' + id).value = m > 0 ? (m * 12).toFixed(2) : '';
    var ses = '';
    if (m > 0) { ses = 'High Income'; for (var i = 0; i < SES_B.length; i++) { if (m < SES_B[i][0]) { ses = SES_B[i][1]; break; } } }
    document.getElementById('editSESDisplay' + id).value = ses;
};

/* ── PWD toggle ── */
window.editTogglePwd = window.editTogglePwd || function (id) {
    var val = document.getElementById('editIsPwd' + id).value;
    var sec = document.getElementById('editPwdSection' + id);
    if (val === 'Yes') { sec.classList.remove('d-none'); }
    else {
        sec.classList.add('d-none');
        document.querySelectorAll('.pwd-type-radio-' + id).forEach(function(r){ r.checked = false; r.closest('.pwd-card-label').querySelector('.pwd-card').classList.remove('pwd-card-active'); });
        document.getElementById('editPwdOtherWrap' + id).classList.add('d-none');
        document.getElementById('editPwdErr' + id).textContent = '';
        var ot = document.getElementById('editPwdOtherText' + id); if (ot) { ot.value = ''; ot.required = false; }
    }
};

/* ── PWD card change ── */
window.editPwdTypeChange = window.editPwdTypeChange || function (id, radio) {
    document.querySelectorAll('.pwd-type-radio-' + id).forEach(function(r){
        r.closest('.pwd-card-label').querySelector('.pwd-card').classList.remove('pwd-card-active');
    });
    radio.closest('.pwd-card-label').querySelector('.pwd-card').classList.add('pwd-card-active');
    var wrap = document.getElementById('editPwdOtherWrap' + id);
    var ot   = document.getElementById('editPwdOtherText' + id);
    if (radio.value === 'Other') { wrap.classList.remove('d-none'); if (ot) { ot.required = true; ot.focus(); } }
    else { wrap.classList.add('d-none'); if (ot) { ot.required = false; ot.value = ''; } }
    document.getElementById('editPwdErr' + id).textContent = '';
};

/* ── Deceased toggle ── */
window.editToggleDeceased = window.editToggleDeceased || function (id) {
    var div = document.getElementById('editDeathDateDiv' + id);
    var dd  = document.getElementById('editDateOfDeath'  + id);
    if (document.getElementById('editIsDeceased' + id).value === 'Yes') { div.classList.remove('d-none'); dd.required = true; }
    else { div.classList.add('d-none'); dd.required = false; dd.value = ''; }
};

/* ── Birthdate → Age ── */
var bdEl = document.getElementById('editBirthdate' + RID);
bdEl.max = new Date().toISOString().split('T')[0];
bdEl.addEventListener('change', function () {
    if (!this.value) return;
    var bd = new Date(this.value), today = new Date();
    var age = today.getFullYear() - bd.getFullYear(), md = today.getMonth() - bd.getMonth();
    if (md < 0 || (md === 0 && today.getDate() < bd.getDate())) age--;
    document.getElementById('editAge' + RID).value = age >= 0 ? age : 0;
});

/* ── Photo upload preview ── */
var prevImg=document.getElementById('editPhotoPreviewImg'+RID), placeholder=document.getElementById('editPhotoPlaceholder'+RID);
var removeBtn=document.getElementById('editRemovePhotoBtn'+RID), fileInput=document.getElementById('editImageFile'+RID);
var camInput=document.getElementById('editCameraImage'+RID);
function showPrev(src){ prevImg.src=src; prevImg.style.display='block'; placeholder.style.display='none'; removeBtn.classList.remove('d-none'); }
function clearPrev(){ prevImg.src=''; prevImg.style.display='none'; placeholder.style.display='flex'; removeBtn.classList.add('d-none'); fileInput.value=''; camInput.value=''; }
removeBtn.addEventListener('click', clearPrev);
fileInput.addEventListener('change', function(){
    var f=this.files[0]; camInput.value='';
    if(!f){ clearPrev(); return; }
    if(f.size>2*1024*1024){ alert('Image must be under 2MB!'); this.value=''; return; }
    if(!['image/jpeg','image/jpg','image/png','image/gif'].includes(f.type)){ alert('Only JPG, PNG, GIF allowed!'); this.value=''; return; }
    var reader=new FileReader(); reader.onload=function(e){ showPrev(e.target.result); }; reader.readAsDataURL(f);
});

/* ── Form submit validation ── */
document.getElementById('editForm'+RID).addEventListener('submit', function(e){
    if (document.getElementById('editIsPwd'+RID).value==='Yes'){
        var checked=document.querySelector('.pwd-type-radio-'+RID+':checked');
        if (!checked){ e.preventDefault(); document.getElementById('editPwdErr'+RID).textContent='Please select a disability type.'; document.getElementById('editPwdSection'+RID).scrollIntoView({behavior:'smooth',block:'center'}); return false; }
        if (checked.value==='Other'){
            var ot=document.getElementById('editPwdOtherText'+RID).value.trim();
            if (!ot){ e.preventDefault(); document.getElementById('editPwdErr'+RID).textContent='Please describe the disability.'; document.getElementById('editPwdOtherText'+RID).focus(); return false; }
            checked.value=ot;
        }
    }
    var pairs=[['editReligion'+RID,'religion_other'],['editEthnicity'+RID,'ethnicity_other'],
               ['editBloodType'+RID,'blood_type_other'],['editHouseOwnership'+RID,'house_ownership_other'],
               ['editHouseMaterial'+RID,'house_material_other'],['editToiletType'+RID,'toilet_type_other'],
               ['editWaterSource'+RID,'water_source_other'],['editOccType'+RID,'occupation_type_other']];
    for (var pi=0;pi<pairs.length;pi++){
        var sel=document.getElementById(pairs[pi][0]);
        if(sel&&sel.value==='Other'){
            var inp=document.querySelector('#editModal'+RID+' input[name="'+pairs[pi][1]+'"]');
            if(inp&&!inp.value.trim()){ e.preventDefault(); alert('Please specify the "'+pairs[pi][1].replace('_other','').replace(/_/g,' ')+'" field.'); inp.focus(); return false; }
        }
    }
});

/* ── Camera ── */
var camModalEl=document.getElementById('editCameraModal'+RID);
var camVideo=document.getElementById('editCamVideo'+RID), camCanvas=document.getElementById('editCamCanvas'+RID);
var camSnapshot=document.getElementById('editCamSnapshot'+RID), camVidWrap=document.getElementById('editCamVideoWrap'+RID);
var camErrDiv=document.getElementById('editCamError'+RID), camErrMsg=document.getElementById('editCamErrorMsg'+RID);
var camCapBtn=document.getElementById('editCamCaptureBtn'+RID), camRetBtn=document.getElementById('editCamRetakeBtn'+RID);
var camUseBtn=document.getElementById('editCamUseBtn'+RID), camSwBtn=document.getElementById('editCamSwitchBtn'+RID);
var camStream=null, camFacing='user', camCaptured=null;
function getCamModal(){ return bootstrap.Modal.getOrCreateInstance(camModalEl,{backdrop:true,keyboard:true}); }
document.getElementById('editOpenCameraBtn'+RID).addEventListener('click',function(){ camReset(); getCamModal().show(); camStart(); });
document.getElementById('editCloseCameraBtn'+RID).addEventListener('click',function(){ getCamModal().hide(); });
camModalEl.addEventListener('hidden.bs.modal',function(){ camStop(); camReset(); });
camModalEl.addEventListener('shown.bs.modal',function(){ var b=document.querySelectorAll('.modal-backdrop'); if(b.length>=2) b[b.length-1].style.zIndex='1069'; });
camSwBtn.addEventListener('click',function(){ camFacing=camFacing==='user'?'environment':'user'; camStop(); camStart(); });
camCapBtn.addEventListener('click',function(){
    var w=camVideo.videoWidth||640, h=camVideo.videoHeight||480; camCanvas.width=w; camCanvas.height=h;
    var ctx=camCanvas.getContext('2d'); if(camFacing==='user'){ ctx.translate(w,0); ctx.scale(-1,1); }
    ctx.drawImage(camVideo,0,0,w,h); camCaptured=camCanvas.toDataURL('image/jpeg',0.92);
    camSnapshot.src=camCaptured; camSnapshot.style.display='block'; camVidWrap.style.display='none';
    camCapBtn.style.display='none'; camRetBtn.style.display='inline-block'; camUseBtn.style.display='inline-block';
});
camRetBtn.addEventListener('click',function(){ camCaptured=null; camSnapshot.style.display='none'; camVidWrap.style.display='block'; camCapBtn.style.display='inline-block'; camRetBtn.style.display='none'; camUseBtn.style.display='none'; camStart(); });
camUseBtn.addEventListener('click',function(){ if(!camCaptured) return; camInput.value=camCaptured; fileInput.value=''; showPrev(camCaptured); getCamModal().hide(); });
camVideo.addEventListener('loadedmetadata',function(){ this.style.transform=camFacing==='user'?'scaleX(-1)':'none'; });
function camStart(){ camErrDiv.style.display='none'; camVidWrap.style.display='block'; if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){ camShowErr('Camera not supported.'); return; } navigator.mediaDevices.getUserMedia({video:{facingMode:camFacing,width:{ideal:1280},height:{ideal:720}},audio:false}).then(function(s){ camStream=s; camVideo.srcObject=s; camVideo.play(); }).catch(function(err){ var msg='Camera access denied.'; if(err.name==='NotFoundError') msg='No camera found.'; if(err.name==='NotReadableError') msg='Camera in use.'; camShowErr(msg); }); }
function camStop(){ if(camStream){ camStream.getTracks().forEach(function(t){ t.stop(); }); camStream=null; } camVideo.srcObject=null; }
function camReset(){ camCaptured=null; camSnapshot.src=''; camSnapshot.style.display='none'; camVidWrap.style.display='block'; camCapBtn.style.display='inline-block'; camRetBtn.style.display='none'; camUseBtn.style.display='none'; camErrDiv.style.display='none'; }
function camShowErr(msg){ camVidWrap.style.display='none'; camErrDiv.style.display='block'; camErrMsg.textContent=msg; camCapBtn.style.display='none'; }
document.getElementById('editModal'+RID).addEventListener('hidden.bs.modal',function(){ camStop(); clearPrev(); });
})();
</script>