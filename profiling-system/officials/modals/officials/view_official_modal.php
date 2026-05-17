<!-- ══════════════════════════════════════════════════════════════════
     VIEW OFFICIAL MODAL — v2 (shows all new fields)
     New: suffix, occupation_type, socioeconomic_status, pwd_type (Bisaya),
          annual income, barangay
     ══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="viewOfficialModal<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header"
                 style="background:linear-gradient(135deg,#0891b2 0%,#0e7490 100%);color:#fff">
                <h5 class="modal-title"><i class="fas fa-user-tie me-2"></i>Official Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 24px">
                <div class="row">

                    <!-- ── LEFT: PHOTO & STATUS ── -->
                    <div class="col-md-3 text-center">
                        <img src="uploads/officials/<?= htmlspecialchars($row['image_path'] ?? 'default.jpg') ?>"
                             class="img-fluid rounded mb-3" alt="Official Photo"
                             style="max-height:220px;border:3px solid #0891b2;object-fit:cover;"
                             onerror="this.onerror=null; this.src='uploads/officials/default_photo_male.jpg'">

                        <h6 class="fw-bold mb-0">
                            <?= htmlspecialchars($row['first_name'] . ' '
                                . ($row['middle_name'] ? $row['middle_name'][0] . '. ' : '')
                                . $row['surname']
                                . (!empty($row['suffix']) ? ', ' . $row['suffix'] : '')) ?>
                        </h6>
                        <p class="text-muted mb-1" style="font-size:13px">
                            <strong><?= htmlspecialchars($row['position']) ?></strong>
                        </p>
                        <?php if (!empty($row['chairmanship'])): ?>
                        <p class="text-muted mb-2" style="font-size:11px">
                            <?= htmlspecialchars($row['chairmanship']) ?>
                        </p>
                        <?php endif; ?>

                        <span class="badge <?= $row['status']==='Active'?'bg-success':'bg-danger' ?> mb-1">
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                        <?php if (($row['voters_status'] ?? '') === 'Yes'): ?>
                        <span class="badge bg-secondary mb-1">Registered Voter</span>
                        <?php endif; ?>
                        <?php if (($row['is_pwd'] ?? 'No') === 'Yes'): ?>
                        <span class="badge bg-warning text-dark mb-1">PWD</span>
                        <?php endif; ?>
                        <?php if (($row['is_deceased'] ?? 'No') === 'Yes'): ?>
                        <span class="badge bg-dark mb-1">Deceased</span>
                        <?php endif; ?>

                        <!-- SES Badge -->
                        <?php
                        $viewSES = $row['socioeconomic_status'] ?? '';
                        if (empty($viewSES) && isset($row['monthly_income']) && $row['monthly_income'] > 0) {
                            $m = (float)$row['monthly_income'];
                            if ($m < 10957)  $viewSES = 'Poor';
                            elseif ($m < 21914)  $viewSES = 'Low Income';
                            elseif ($m < 43828)  $viewSES = 'Lower Middle Income';
                            elseif ($m < 76669)  $viewSES = 'Middle Income';
                            elseif ($m < 131484) $viewSES = 'Upper Middle Income';
                            else $viewSES = 'High Income';
                        }
                        if (!empty($viewSES)):
                        ?>
                        <div class="mt-2">
                            <span class="badge" style="background:#f0f7f3;color:#1a6b3c;border:1px solid #1a6b3c;font-size:10px;">
                                <?= htmlspecialchars($viewSES) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- ── RIGHT: DETAILS ── -->
                    <div class="col-md-9">
                        <?php
                        // Helper to show a view row
                        if (!function_exists('viewRow')) {
                        function viewRow($label, $value, $badge = false, $badgeClass = 'bg-primary') {
                            $val = htmlspecialchars($value ?: '—');
                            $cell = $badge && $value ? "<span class='badge $badgeClass'>$val</span>" : $val;
                            echo "<div class='row mb-2'><div class='col-4 fw-semibold text-muted' style='font-size:12px'>$label</div><div class='col-8' style='font-size:13px'>$cell</div></div>";
                        }
                        }
                        ?>

                        <!-- Personal Information -->
                        <h6 class="view-section-hdr"><i class="fas fa-user text-primary me-1"></i> Personal Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <?php viewRow('Full Name',
                                    $row['first_name'] . ' ' . ($row['middle_name'] ?? '') . ' ' . $row['surname']
                                    . (!empty($row['suffix']) ? ', ' . $row['suffix'] : '')); ?>
                                <?php viewRow('Birthdate', !empty($row['birthdate']) ? date('F d, Y', strtotime($row['birthdate'])) . ' (Age ' . $row['age'] . ')' : ''); ?>
                                <?php viewRow('Birthplace', $row['birthplace'] ?? ''); ?>
                                <?php viewRow('Sex', $row['sex'] ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                <?php viewRow('Civil Status', $row['civil_status'] ?? ''); ?>
                                <?php viewRow('Nationality', $row['nationality'] ?? 'Filipino'); ?>
                                <?php viewRow('Blood Type', $row['blood_type'] ?? ''); ?>
                                <?php viewRow('Height (cm)', $row['height'] ?? ''); ?>
                                <?php viewRow('Weight (kg)', $row['weight'] ?? ''); ?>
                                <?php viewRow('Contact No.', $row['contact_no'] ?? ''); ?>
                            </div>
                        </div>

                        <hr class="my-2">
                        <!-- Position & Term -->
                        <h6 class="view-section-hdr"><i class="fas fa-id-badge text-success me-1"></i> Position & Term</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <?php viewRow('Position', $row['position'] ?? '', true, 'bg-primary'); ?>
                                <?php viewRow('Chairmanship', $row['chairmanship'] ?? ''); ?>
                                <?php viewRow('Status', $row['status'] ?? '', true, $row['status']==='Active'?'bg-success':'bg-danger'); ?>
                            </div>
                            <div class="col-md-6">
                                <?php viewRow('Term Start', !empty($row['term_start']) ? date('F d, Y', strtotime($row['term_start'])) : ''); ?>
                                <?php viewRow('Term End', !empty($row['term_end']) ? date('F d, Y', strtotime($row['term_end'])) : ''); ?>
                                <?php viewRow('Years in Service', ($row['years_in_service'] ?? '') !== '' ? $row['years_in_service'] . ' year(s)' : ''); ?>
                            </div>
                        </div>

                        <hr class="my-2">
                        <!-- Address -->
                        <h6 class="view-section-hdr"><i class="fas fa-map-marker-alt text-danger me-1"></i> Address</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <?php viewRow('Purok', $row['purok'] ?? ''); ?>
                                <?php viewRow('Barangay', $row['barangay'] ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                <?php viewRow('Municipality', $row['municipality'] ?? ''); ?>
                                <?php viewRow('Province', $row['province'] ?? ''); ?>
                            </div>
                        </div>

                        <hr class="my-2">
                        <!-- Occupation & Income — NEW fields -->
                        <h6 class="view-section-hdr"><i class="fas fa-briefcase text-warning me-1"></i> Occupation & Income</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <?php viewRow('Occupation Type', $row['occupation_type'] ?? ''); ?>
                                <?php viewRow('Occupation', $row['occupation'] ?? ''); ?>
                                <?php viewRow('Household Position', $row['household_position'] ?? ''); ?>
                                <?php viewRow('Father\'s Name', $row['father_name'] ?? ''); ?>
                                <?php viewRow('Father\'s Occupation', $row['father_occupation'] ?? ''); ?>
                                <?php viewRow('Mother\'s Name', $row['mother_name'] ?? ''); ?>
                                <?php viewRow('Mother\'s Occupation', $row['mother_occupation'] ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                <?php viewRow('Monthly Income', isset($row['monthly_income']) && $row['monthly_income'] !== '' ? '₱' . number_format((float)$row['monthly_income'], 2) : ''); ?>
                                <?php viewRow('Annual Income', isset($row['annual_income']) && $row['annual_income'] !== '' ? '₱' . number_format((float)$row['annual_income'], 2) : ''); ?>
                                <?php viewRow('Socioeconomic Status', !empty($viewSES) ? $viewSES : ''); ?>
                            </div>
                        </div>

                        <hr class="my-2">
                        <!-- Demographic -->
                        <h6 class="view-section-hdr"><i class="fas fa-id-card text-info me-1"></i> Demographic</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <?php viewRow('Religion', $row['religion'] ?? ''); ?>
                                <?php viewRow('Ethnicity', $row['ethnicity'] ?? ''); ?>
                                <?php viewRow('PhilHealth No.', $row['philhealth_no'] ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                <?php viewRow('PhilHealth Membership', $row['membership_type'] ?? ''); ?>
                                <?php viewRow('Length of Residency', isset($row['length_of_residency']) && $row['length_of_residency'] !== '' ? $row['length_of_residency'] . ' year(s)' : ''); ?>
                                <?php viewRow('Educational Attainment', $row['educational_attainment'] ?? ''); ?>
                            </div>
                        </div>

                        <hr class="my-2">
                        <!-- Housing & Social Flags -->
                        <h6 class="view-section-hdr"><i class="fas fa-home text-secondary me-1"></i> Housing</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <?php viewRow('House Ownership', $row['house_ownership'] ?? ''); ?>
                                <?php viewRow('House Material', $row['house_material'] ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                <?php viewRow('Toilet Type', $row['toilet_type'] ?? ''); ?>
                                <?php viewRow('Water Source', $row['water_source'] ?? ''); ?>
                            </div>
                        </div>

                        <hr class="my-2">
                        <!-- Social Flags -->
                        <h6 class="view-section-hdr"><i class="fas fa-hands-helping text-purple me-1"></i> Social Programs</h6>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php
                            $socialFlags=[['4Ps','is_4ps'],['NHTS','is_nhts'],['Solo Parent','is_solo_parent'],['Family Planning','family_planning']];
                            foreach($socialFlags as [$label,$field]):
                                $active=($row[$field]??'No')==='Yes';
                            ?>
                            <span class="badge <?= $active?'bg-success':'bg-light text-muted' ?> border">
                                <?= $active?'✓':'✗' ?> <?= $label ?>
                            </span>
                            <?php endforeach; ?>
                        </div>

                        <hr class="my-2">
                        <!-- Special Status — PWD with Bisaya -->
                        <?php if (($row['is_pwd'] ?? 'No') === 'Yes'): ?>
                        <h6 class="view-section-hdr" style="color:#92400e"><i class="fas fa-star-of-life me-1"></i> Special Status</h6>
                        <?php
                        // Display pwd_type (new) or fallback to pwd_details (legacy)
                        $displayPwdType = trim($row['pwd_type'] ?? $row['pwd_details'] ?? '');
                        $bisayaMap=[
                            'Visual Impairment'   => 'Pagkabuta / Pagkaablag sa Pagsud-ong',
                            'Hearing Impairment'  => 'Pagkabungol / Pagkaablag sa Pagpamati',
                            'Physical Disability' => 'Pagkapiit / Pagkaablag sa Lawas',
                            'Intellectual'        => 'Pagkaablag sa Pangisip',
                            'Psychosocial'        => 'Sakit sa Hunahuna / Kinaiya',
                            'Speech Impairment'   => 'Pagkaamang / Pagkaablag sa Pagsulti',
                        ];
                        ?>
                        <div class="p-3 rounded mb-3" style="background:#fff8e1;border-left:4px solid #f59e0b">
                            <div class="fw-bold" style="font-size:13px">
                                <i class="fas fa-wheelchair text-warning me-1"></i>
                                PWD — <?= htmlspecialchars($displayPwdType ?: 'Not specified') ?>
                            </div>
                            <?php if (isset($bisayaMap[$displayPwdType])): ?>
                            <div class="text-muted" style="font-size:11px;margin-top:2px">
                                <em><?= $bisayaMap[$displayPwdType] ?></em>
                            </div>
                            <?php endif; ?>
                            <?php if (($row['is_deceased'] ?? 'No') === 'Yes' && !empty($row['date_of_death'])): ?>
                            <div class="mt-1" style="font-size:12px">
                                <i class="fas fa-cross text-secondary me-1"></i>
                                Deceased: <?= date('F d, Y', strtotime($row['date_of_death'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Health Profile -->
                        <h6 class="view-section-hdr" style="color:#7f1d1d"><i class="fas fa-heartbeat me-1"></i> Health Profile</h6>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php
                            $healthFlags=[['Smoker','is_smoker'],['Binge Drinker','is_binge_drinker'],
                                          ['Hypertension','has_hypertension'],['Diabetes','has_diabetes'],
                                          ['Asthma','has_asthma'],['Tuberculosis','has_tb'],
                                          ['Cancer','has_cancer'],['Mental Health','has_mental_health']];
                            foreach($healthFlags as [$label,$field]):
                                $active=($row[$field]??'No')==='Yes';
                            ?>
                            <span class="badge <?= $active?'bg-danger':'bg-light text-muted' ?> border" style="font-size:10px">
                                <?= $active?'✓':'✗' ?> <?= $label ?>
                            </span>
                            <?php endforeach; ?>
                        </div>

                        <hr class="my-2">
                        <!-- Account Information -->
                        <h6 class="view-section-hdr"><i class="fas fa-user-circle text-primary me-1"></i> Account Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <?php viewRow('Username', $row['username'] ?? ''); ?>
                            </div>
                            <div class="col-md-6">
                                <?php viewRow('Date Created', !empty($row['created_at']) ? date('F d, Y h:i A', strtotime($row['created_at'])) : ''); ?>
                            </div>
                        </div>

                    </div><!-- /col-md-9 -->
                </div><!-- /row -->
            </div><!-- /modal-body -->
            <div class="modal-footer">
                <button type="button" class="btn btn-info text-white" onclick="printOfficialModal('viewOfficialModal<?= $row['id'] ?>')">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof window.printOfficialModal !== 'function') {
    window.printOfficialModal = function(modalId) {
        var modalElement = document.getElementById(modalId);
        if(!modalElement) return;
        var modalContent = modalElement.querySelector('.modal-body').innerHTML;
        
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write('<html><head><title>Print Official Profile</title>');
        // Include bootstrap and icons if possible by copying current stylesheets
        var styles = document.querySelectorAll('link[rel="stylesheet"], style');
        styles.forEach(function(s) {
            printWindow.document.write(s.outerHTML);
        });
        printWindow.document.write('<style>body { padding: 20px; background: #fff; } @media print { .btn, .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<div style="text-align: center; margin-bottom: 20px;"><h2>Official Profile</h2></div>');
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

<?php if (!defined('OFF_VIEW_STYLES')): define('OFF_VIEW_STYLES', true); ?>
<style>
.view-section-hdr{font-size:13px;font-weight:700;color:#374151;border-bottom:1px solid #e5e7eb;padding-bottom:4px;margin-bottom:10px;margin-top:4px;}
</style>
<?php endif; ?>