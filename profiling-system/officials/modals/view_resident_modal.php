<?php
/**
 * view_resident_modal.php
 * Include inside the while loop in resident.php — $row is available.
 *
 * SYNC with register_account.php:
 *   + suffix displayed in full name
 *   + occupation_type
 *   + pwd_type (replaces pwd_details)
 *   + annual_income (auto-derived if missing)
 *   + socioeconomic_status (auto-derived if missing)
 */
$id = $row['id'];
$isDeceased = ($row['is_deceased']==='Yes'||$row['is_deceased']==1);
$isPwd      = ($row['is_pwd']     ==='Yes'||$row['is_pwd']     ==1);

if (!function_exists('yesNo')) {
    function yesNo($val) {
        $y = ($val==='Yes'||$val==1);
        return $y
            ? '<span class="demo-tag tag-green">Yes</span>'
            : '<span class="demo-tag tag-gray">No</span>';
    }
}
if (!function_exists('dispVal')) {
    function dispVal($val, $fallback='—') {
        $v = trim($val ?? '');
        return $v !== '' ? htmlspecialchars($v) : $fallback;
    }
}

// Derive annual income if not stored
$annualIncome = $row['annual_income'] ?? '';
if (($annualIncome === '' || $annualIncome === null) && !empty($row['monthly_income']))
    $annualIncome = round((float)$row['monthly_income'] * 12, 2);

// Derive SES if not stored
$ses = $row['socioeconomic_status'] ?? '';
if (empty($ses) && !empty($row['monthly_income'])) {
    $m = (float)$row['monthly_income'];
    if ($m < 10957)      $ses = 'Poor';
    elseif ($m < 21914)  $ses = 'Low Income';
    elseif ($m < 43828)  $ses = 'Lower Middle Income';
    elseif ($m < 76669)  $ses = 'Middle Income';
    elseif ($m < 131484) $ses = 'Upper Middle Income';
    else                 $ses = 'High Income';
}

// PWD type — check both pwd_type (new) and pwd_details (legacy)
$pwdType = trim($row['pwd_type'] ?? $row['pwd_details'] ?? '');

// SES colour mapping
$sesColors = [
    'Poor'               => 'background:#fee2e2;color:#991b1b',
    'Low Income'         => 'background:#fff7ed;color:#c2410c',
    'Lower Middle Income'=> 'background:#fefce8;color:#854d0e',
    'Middle Income'      => 'background:#f0fdf4;color:#166534',
    'Upper Middle Income'=> 'background:#eff6ff;color:#1d4ed8',
    'High Income'        => 'background:#f5f3ff;color:#5b21b6',
];
$sesStyle = isset($sesColors[$ses]) ? $sesColors[$ses] : 'background:#f1f5f9;color:#64748b';

// Full name with suffix
$fullName = trim(implode(' ', array_filter([
    $row['first_name'],
    $row['middle_name'] ?? '',
    $row['surname'],
])));
if (!empty($row['suffix'])) $fullName .= ', ' . $row['suffix'];
?>

<div class='modal fade' id='viewResidentModal<?= $id ?>' tabindex='-1'>
    <div class='modal-dialog modal-xl' style='max-width:1100px'>
        <div class='modal-content'>
            <div class='modal-header text-white' style='background:#0891b2'>
                <h5 class='modal-title'><i class='fas fa-id-card me-2'></i>Resident Profile</h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body p-0'>
                <div class='d-flex flex-column flex-md-row'>

                    <!-- ── LEFT SIDEBAR ────────────────────────────────── -->
                    <div style='background:#0f172a;min-width:200px;max-width:220px;padding:24px 16px;display:flex;flex-direction:column;align-items:center;gap:12px'>
                        <img src='uploads/residents/<?= htmlspecialchars($row['image_path']) ?>'
                             style='width:130px;height:150px;object-fit:cover;border-radius:8px;border:3px solid rgba(255,255,255,0.15)'
                             onerror="this.onerror=null; this.src='uploads/residents/default_photo_male.jpg'" alt='Photo'>
                        <div style='text-align:center;color:#fff'>
                            <div style='font-weight:700;font-size:13px;line-height:1.3'>
                                <?= htmlspecialchars($fullName) ?>
                            </div>
                            <div style='font-size:11px;color:rgba(255,255,255,0.5);margin-top:2px'>
                                ID: <?= str_pad($id, 6, '0', STR_PAD_LEFT) ?>
                            </div>
                        </div>
                        <!-- Status badges -->
                        <div style='display:flex;flex-wrap:wrap;gap:4px;justify-content:center'>
                            <?php if($isPwd):?><span class='demo-tag tag-red' style='font-size:10px'>PWD</span><?php endif;?>
                            <?php if($isDeceased):?><span class='demo-tag' style='background:#1e293b;color:#94a3b8;font-size:10px'>Deceased</span><?php endif;?>
                            <?php if($row['age']<=1):?><span class='demo-tag tag-teal' style='font-size:10px'>Newborn</span><?php endif;?>
                            <?php if($row['age']>=60):?><span class='demo-tag tag-orange' style='font-size:10px'>Senior</span><?php endif;?>
                            <?php if($row['voters_status']==='Yes'):?><span class='demo-tag tag-gray' style='font-size:10px'>Voter</span><?php endif;?>
                            <?php if(($row['is_4ps']??'No')==='Yes'):?><span class='demo-tag tag-purple' style='font-size:10px'>4Ps</span><?php endif;?>
                            <?php if(($row['is_solo_parent']??'No')==='Yes'):?><span class='demo-tag tag-teal' style='font-size:10px'>Solo Parent</span><?php endif;?>
                            <?php if(($row['is_nhts']??'No')==='Yes'):?><span class='demo-tag tag-amber' style='font-size:10px'>NHTS</span><?php endif;?>
                        </div>
                        <!-- Age group block -->
                        <div style='background:rgba(255,255,255,0.07);border-radius:6px;padding:8px 12px;text-align:center;width:100%'>
                            <div style='font-size:10px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:.5px'>Age Group</div>
                            <div style='font-size:13px;font-weight:700;color:#fff;margin-top:2px'>
                                <?php
                                if($row['age']<=1)      echo 'Newborn';
                                elseif($row['age']<=14) echo 'Child';
                                elseif($row['age']<=24) echo 'Youth';
                                elseif($row['age']<=59) echo 'Adult';
                                else                    echo 'Senior Citizen';
                                ?>
                            </div>
                            <div style='font-size:18px;font-weight:800;color:#38bdf8'>
                                <?= $row['age'] ?> <span style='font-size:11px;font-weight:400'>yrs</span>
                            </div>
                        </div>
                        <?php if (!empty($ses)): ?>
                        <div style='<?= $sesStyle ?>;border-radius:6px;padding:6px 10px;text-align:center;width:100%;font-size:10px;font-weight:700'>
                            <?= htmlspecialchars($ses) ?>
                        </div>
                        <?php endif; ?>
                        <div style='font-size:10px;color:rgba(255,255,255,0.3);text-align:center'>
                            Registered:<br><?= !empty($row['created_at']) && strtotime($row['created_at']) > 0 ? date('M d, Y', strtotime($row['created_at'])) : '—' ?>
                            <?php if(!empty($row['updated_at']) && strtotime($row['updated_at']) > 0 && $row['updated_at']!==$row['created_at']): ?>
                            <br>Updated: <?= date('M d, Y', strtotime($row['updated_at'])) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ── RIGHT CONTENT ─────────────────────────────────── -->
                    <div style='flex:1;padding:20px 24px;overflow-y:auto;max-height:78vh'>

                        <!-- PERSONAL -->
                        <div class='view-section-hdr'><i class='fas fa-user'></i> Personal Information</div>
                        <div class='row'>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>Full Name</span>
                                    <span class='info-val fw-semibold'><?= htmlspecialchars($fullName) ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Birthdate</span>
                                    <span class='info-val'><?= !empty($row['birthdate'])?date('F d, Y',strtotime($row['birthdate'])):'—' ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Birthplace</span>
                                    <span class='info-val'><?= dispVal($row['birthplace']) ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Sex</span>
                                    <span class='info-val'><?= dispVal($row['sex']) ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Civil Status</span>
                                    <span class='info-val'><?= dispVal($row['civil_status']) ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Nationality</span>
                                    <span class='info-val'><?= dispVal($row['nationality'],'Filipino') ?></span>
                                </div>
                            </div>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>Contact No.</span>
                                    <span class='info-val'><?= dispVal($row['contact_no']) ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Blood Type</span>
                                    <span class='info-val'><?= dispVal($row['blood_type']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Height (cm)</span>
                                    <span class='info-val'><?= dispVal($row['height']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Weight (kg)</span>
                                    <span class='info-val'><?= dispVal($row['weight']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Religion</span>
                                    <span class='info-val'><?= dispVal($row['religion']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Ethnicity / IP Group</span>
                                    <span class='info-val'><?= dispVal($row['ethnicity']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Length of Residency</span>
                                    <span class='info-val'><?= isset($row['length_of_residency'])&&$row['length_of_residency']!==null ? $row['length_of_residency'].' years' : '—' ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- ADDRESS -->
                        <div class='view-section-hdr mt-3'><i class='fas fa-map-marker-alt'></i> Address Information</div>
                        <div class='row'>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>HH No.</span>
                                    <span class='info-val'><?= dispVal($row['household_no']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Purok</span>
                                    <span class='info-val'><?= dispVal($row['purok']) ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Complete Address</span>
                                    <span class='info-val'><?= htmlspecialchars(implode(', ', array_filter([$row['purok'],$row['barangay'],$row['municipality'],$row['province']]))) ?></span>
                                </div>
                            </div>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>House Ownership</span>
                                    <span class='info-val'><?= dispVal($row['house_ownership']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>House Material</span>
                                    <span class='info-val'><?= dispVal($row['house_material']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Toilet Type</span>
                                    <span class='info-val'><?= dispVal($row['toilet_type']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Water Source</span>
                                    <span class='info-val'><?= dispVal($row['water_source']??'') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- OCCUPATION, INCOME & EDUCATION -->
                        <div class='view-section-hdr mt-3'><i class='fas fa-briefcase'></i> Occupation, Income & Education</div>
                        <div class='row'>
                            <div class='col-md-6'>
                                <!-- OCCUPATION TYPE — new -->
                                <div class='info-row'><span class='info-label'>Occupation Type</span>
                                    <span class='info-val'><?= dispVal($row['occupation_type']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Occupation / Job Title</span>
                                    <span class='info-val'><?= dispVal($row['occupation']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Monthly Income</span>
                                    <span class='info-val'>
                                        <?= ($row['monthly_income']!==null&&$row['monthly_income']!=='')
                                            ? '₱'.number_format((float)$row['monthly_income'],2) : '—' ?>
                                    </span>
                                </div>
                                <div class='info-row'><span class='info-label'>Annual Income</span>
                                    <span class='info-val'>
                                        <?= ($annualIncome!==null&&$annualIncome!=='')
                                            ? '₱'.number_format((float)$annualIncome,2) : '—' ?>
                                    </span>
                                </div>
                                <!-- SES -->
                                <div class='info-row'><span class='info-label'>Socioeconomic Status</span>
                                    <span class='info-val'>
                                        <?php if ($ses): ?>
                                        <span style='display:inline-block;padding:2px 9px;border-radius:10px;font-size:.72rem;font-weight:700;<?= $sesStyle ?>'>
                                            <?= htmlspecialchars($ses) ?>
                                        </span>
                                        <?php else: ?>—<?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>Voter Status</span>
                                    <span class='info-val'><?= $row['voters_status']==='Yes'?'<span class="demo-tag tag-blue">Registered Voter</span>':'Not Registered' ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Educational Attainment</span>
                                    <span class='info-val'><?= dispVal($row['educational_attainment']??'') ?></span>
                                </div>
                                <?php if(!empty($row['grade_level'])||!empty($row['school_name'])): ?>
                                <div class='info-row'><span class='info-label'>Grade / Year Level</span>
                                    <span class='info-val'><?= dispVal($row['grade_level']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>School Name</span>
                                    <span class='info-val'><?= dispVal($row['school_name']??'') ?></span>
                                </div>
                                <?php endif; ?>
                                <?php
                                // Graduate-level fields
                                $courseDisplay = '';
                                if (!empty($row['course'])) {
                                    $courseDisplay = ($row['course'] === 'Others' && !empty($row['course_other']))
                                        ? $row['course_other'] : $row['course'];
                                }
                                $eligDisplay = '';
                                if (!empty($row['eligibility'])) {
                                    $eligDisplay = ($row['eligibility'] === 'Others' && !empty($row['eligibility_other']))
                                        ? $row['eligibility_other'] : $row['eligibility'];
                                }
                                ?>
                                <?php if (!empty($courseDisplay)): ?>
                                <div class='info-row'><span class='info-label'>Course</span>
                                    <span class='info-val'><?= htmlspecialchars($courseDisplay) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($row['graduation_date'])): ?>
                                <div class='info-row'><span class='info-label'>Date of Graduation</span>
                                    <span class='info-val'><?= date('F d, Y', strtotime($row['graduation_date'])) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($eligDisplay)): ?>
                                <div class='info-row'><span class='info-label'>Eligibility</span>
                                    <span class='info-val'><span class='demo-tag tag-blue'><?= htmlspecialchars($eligDisplay) ?></span></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- HOUSEHOLD & SOCIAL PROGRAMS -->
                        <div class='view-section-hdr mt-3'><i class='fas fa-home'></i> Household & Social Programs</div>
                        <div class='row'>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>Household Position</span>
                                    <span class='info-val'><span class='demo-tag tag-blue'><?= dispVal($row['household_position']??'') ?></span></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Total Household Members</span>
                                    <span class='info-val'><?= $row['total_household']??'—' ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Father's Name</span>
                                    <span class='info-val'><?= dispVal($row['father_name']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Father's Occ.</span>
                                    <span class='info-val'><?= dispVal($row['father_occupation']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Mother's Name</span>
                                    <span class='info-val'><?= dispVal($row['mother_name']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Mother's Occ.</span>
                                    <span class='info-val'><?= dispVal($row['mother_occupation']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Solo Parent</span>
                                    <span class='info-val'><?= yesNo($row['is_solo_parent']??'No') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>Family Planning</span>
                                    <span class='info-val'><?= yesNo($row['family_planning']??'No') ?></span>
                                </div>
                            </div>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>PhilHealth No.</span>
                                    <span class='info-val'><?= dispVal($row['philhealth_no']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>PhilHealth Membership</span>
                                    <span class='info-val'><?= dispVal($row['membership_type']??'') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>NHTS Beneficiary</span>
                                    <span class='info-val'><?= yesNo($row['is_nhts']??'No') ?></span>
                                </div>
                                <div class='info-row'><span class='info-label'>4Ps Beneficiary</span>
                                    <span class='info-val'><?= yesNo($row['is_4ps']??'No') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- HEALTH PROFILE -->
                        <div class='view-section-hdr mt-3'><i class='fas fa-heartbeat'></i> Health Profile</div>
                        <div class='row'>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>Smoker</span><span class='info-val'><?= yesNo($row['is_smoker']??'No') ?></span></div>
                                <div class='info-row'><span class='info-label'>Binge Drinker</span><span class='info-val'><?= yesNo($row['is_binge_drinker']??'No') ?></span></div>
                                <div class='info-row'><span class='info-label'>Hypertension (HPN)</span><span class='info-val'><?= yesNo($row['has_hypertension']??'No') ?></span></div>
                                <div class='info-row'><span class='info-label'>Diabetes (DM)</span><span class='info-val'><?= yesNo($row['has_diabetes']??'No') ?></span></div>
                            </div>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>Asthma</span><span class='info-val'><?= yesNo($row['has_asthma']??'No') ?></span></div>
                                <div class='info-row'><span class='info-label'>Tuberculosis (TB)</span><span class='info-val'><?= yesNo($row['has_tb']??'No') ?></span></div>
                                <div class='info-row'><span class='info-label'>Cancer</span><span class='info-val'><?= yesNo($row['has_cancer']??'No') ?></span></div>
                                <div class='info-row'><span class='info-label'>Mental Health</span><span class='info-val'><?= yesNo($row['has_mental_health']??'No') ?></span></div>
                            </div>
                        </div>

                        <!-- SPECIAL STATUS -->
                        <div class='view-section-hdr mt-3'><i class='fas fa-star-of-life'></i> Special Status</div>
                        <div class='row'>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>PWD</span>
                                    <span class='info-val'><?= yesNo($row['is_pwd']) ?></span>
                                </div>
                                <?php if($isPwd && $pwdType !== ''): ?>
                                <div class='info-row'><span class='info-label'>PWD Type</span>
                                    <span class='info-val'>
                                        <div style='background:#fff8e1;border:1px solid #ffc107;border-left:3px solid #f59e0b;padding:6px 10px;border-radius:4px;font-size:12px'>
                                            <i class='fas fa-wheelchair me-1 text-warning'></i>
                                            <?= htmlspecialchars($pwdType) ?>
                                        </div>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <div class='info-row'><span class='info-label'>Newborn</span>
                                    <span class='info-val'><?= yesNo($row['is_newborn']) ?></span>
                                </div>
                            </div>
                            <div class='col-md-6'>
                                <div class='info-row'><span class='info-label'>Deceased</span>
                                    <span class='info-val'><?= yesNo($row['is_deceased']) ?></span>
                                </div>
                                <?php if($isDeceased && !empty($row['date_of_death'])): ?>
                                <div class='info-row'><span class='info-label'>Date of Death</span>
                                    <span class='info-val'><?= date('F d, Y', strtotime($row['date_of_death'])) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div><!-- /right content -->
                </div><!-- /flex -->
            </div><!-- /modal-body -->

            <div class='modal-footer' style='background:#f8fafc'>
                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>
                    <i class='fas fa-times'></i> Close
                </button>
                <button type='button' class='btn btn-info text-white' onclick="printProfileModal('viewResidentModal<?= $id ?>')">
                    <i class='fas fa-print'></i> Print
                </button>
                <button type='button' class='btn btn-primary'
                        data-bs-toggle='modal'
                        data-bs-target='#editModal<?= $id ?>'
                        data-bs-dismiss='modal'>
                    <i class='fas fa-edit'></i> Edit Details
                </button>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof window.printProfileModal !== 'function') {
    window.printProfileModal = function(modalId) {
        var modalElement = document.getElementById(modalId);
        if(!modalElement) return;
        var modalContent = modalElement.querySelector('.modal-body').innerHTML;
        
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write('<html><head><title>Print Profile</title>');
        // Include bootstrap and icons if possible by copying current stylesheets
        var styles = document.querySelectorAll('link[rel="stylesheet"], style');
        styles.forEach(function(s) {
            printWindow.document.write(s.outerHTML);
        });
        printWindow.document.write('<style>body { padding: 20px; background: #fff; } @media print { .btn, .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<div style="text-align: center; margin-bottom: 20px;"><h2>Resident Profile</h2></div>');
        printWindow.document.write(modalContent);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        
        // Wait for styles to load
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 800);
    };
}
</script>

<style>
.view-section-hdr{font-weight:700;font-size:12px;color:#0f3c6e;text-transform:uppercase;letter-spacing:.6px;border-bottom:2px solid #e2e8f0;padding-bottom:6px;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.info-row{display:flex;padding:5px 0;border-bottom:1px solid #f1f5f9;font-size:13px;}
.info-row:last-child{border-bottom:none;}
.info-label{font-weight:600;color:#64748b;min-width:175px;font-size:12px;}
.info-val{color:#1e293b;flex:1;}
.demo-tag{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.7rem;font-weight:700;white-space:nowrap;}
.tag-green{background:#ecfdf5;color:#065f46;}
.tag-gray{background:#f1f5f9;color:#64748b;}
.tag-red{background:#fef2f2;color:#991b1b;}
.tag-teal{background:#ecfeff;color:#0e7490;}
.tag-orange{background:#fff7ed;color:#c2410c;}
.tag-blue{background:#eff6ff;color:#1d4ed8;}
.tag-purple{background:#f5f3ff;color:#5b21b6;}
.tag-amber{background:#fffbeb;color:#92400e;}
</style>