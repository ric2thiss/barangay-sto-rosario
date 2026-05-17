<?php
include("connection.php");
include '../officials/hybrid_assets.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resident Registration — Barangay Sto. Rosario</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--primary:#0f3c6e;--primary-lt:#1f6bb8;--gold:#c8963e;--gold-lt:#e8b45a;}
body{background:linear-gradient(150deg,#071628 0%,#0f3c6e 50%,#1f6bb8 100%);min-height:100vh;font-family:"Segoe UI",sans-serif;position:relative;}
body::before{content:'';position:fixed;inset:0;background-image:radial-gradient(rgba(255,255,255,0.03) 1px,transparent 1px);background-size:26px 26px;pointer-events:none;z-index:0;}
.logo-img{height:55px;width:55px;border-radius:50%;}
.site-header{position:relative;z-index:10;display:flex;align-items:center;gap:14px;padding:13px 32px;background:rgba(5,15,40,0.72);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,0.08);}
.logo-badge{width:42px;height:42px;border-radius:50%;background:linear-gradient(145deg,var(--gold),var(--gold-lt));display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;box-shadow:0 3px 12px rgba(200,150,62,0.4);border:2px solid rgba(255,255,255,0.16);text-decoration:none;transition:transform 0.2s;}
.logo-badge:hover{transform:scale(1.07);}
.site-header-text h1{font-size:14px;font-weight:700;color:#fff;line-height:1.2;}
.site-header-text p{font-size:11px;color:rgba(255,255,255,0.45);letter-spacing:0.06em;text-transform:uppercase;}
.header-nav{margin-left:auto;display:flex;align-items:center;gap:5px;}
.nav-link-pill{font-size:12px;color:rgba(255,255,255,0.55);text-decoration:none;padding:5px 11px;border-radius:20px;border:1px solid rgba(255,255,255,0.12);transition:color 0.18s,border-color 0.18s,background 0.18s;}
.nav-link-pill:hover{color:#fff;border-color:rgba(255,255,255,0.3);background:rgba(255,255,255,0.07);}
.nav-link-pill.active{color:var(--gold-lt);border-color:rgba(200,150,62,0.4);background:rgba(200,150,62,0.08);}

.registration-form{background:#fff;border:none;padding:25px;max-width:980px;margin:24px auto 30px;font-size:14px;box-shadow:0 20px 60px rgba(5,15,40,0.45);position:relative;z-index:1;border-radius:4px;}
@media(max-width:576px){.registration-form{padding:16px 12px;margin:10px auto;font-size:13px;}}
.registration-form h4{text-align:center;font-weight:bold;margin-bottom:2px;color:#0f3c6e;}
.registration-form .subtitle{display:block;text-align:center;margin-bottom:15px;color:#6c757d;}
h6.fw-bold{color:#0f3c6e;margin-top:10px;}
.form-label{font-weight:600;font-size:12px;color:#333;}
input,select,textarea{border-radius:0!important;}
hr{border-top:2px solid #0f3c6e;opacity:0.3;}
.btn-success{background-color:#0f3c6e;border:none;padding:8px 22px;font-weight:500;}
.btn-success:hover{background-color:#0c2f55;}

/* Section highlight boxes */
.demo-box{background:#f0f7ff;border:1px solid #b8d4f0;border-left:4px solid #0f3c6e;padding:18px;margin-top:10px;border-radius:4px;}
.health-box{background:#fff8f0;border:1px solid #f0c890;border-left:4px solid #c8963e;padding:18px;margin-top:10px;border-radius:4px;}
.pwd-box{background:#fff8e1;border:1px solid #ffc107;padding:18px 20px;margin-top:10px;border-radius:6px;}
.pwd-icon-badge{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#f39c12,#e67e22);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0;box-shadow:0 2px 6px rgba(230,126,34,0.4);}

/* PWD type selection cards */
.pwd-type-card{display:flex;align-items:center;gap:10px;padding:9px 11px;border:1.5px solid #dee2e6;border-radius:6px;cursor:pointer;background:#fff;transition:border-color 0.18s,background 0.18s,box-shadow 0.18s;margin:0;width:100%;min-height:56px;}
.pwd-type-card:hover{border-color:#f39c12;background:#fffbf0;box-shadow:0 2px 8px rgba(243,156,18,0.15);}
.pwd-type-card.selected{border-color:#f39c12;background:#fff8e1;box-shadow:0 0 0 3px rgba(243,156,18,0.25);}
.pwd-card-icon{font-size:20px;width:28px;text-align:center;flex-shrink:0;}
.pwd-card-text{display:flex;flex-direction:column;line-height:1.25;}
.pwd-card-en{font-size:12px;font-weight:600;color:#333;}
.pwd-card-bis{font-size:10px;color:#888;font-style:italic;}

/* PWD Yes/No radio styling */
.yn-option{display:flex;align-items:center;gap:6px;padding:7px 16px;border:1.5px solid #dee2e6;border-radius:20px;cursor:pointer;font-size:13px;font-weight:500;transition:all 0.18s;background:#fff;}
.yn-option:hover{border-color:#0f3c6e;background:#f0f7ff;}
.yn-option.active-yes{border-color:#28a745;background:#e8f5e9;color:#155724;}
.yn-option.active-no{border-color:#6c757d;background:#f5f5f5;color:#495057;}
.newborn-box{background:#e3f2fd;border:1px solid #2196f3;padding:15px;margin-top:10px;border-radius:4px;}
.income-box{background:#f0fff4;border:1px solid #a8ddb5;border-left:4px solid #28a745;padding:18px;margin-top:10px;border-radius:4px;}

/* Socioeconomic badge */
.ses-badge{display:inline-block;padding:4px 12px;border-radius:12px;font-size:11px;font-weight:700;letter-spacing:0.04em;margin-top:4px;}
.ses-poor{background:#ffeaea;color:#c0392b;border:1px solid #e74c3c;}
.ses-low{background:#fff3cd;color:#856404;border:1px solid #ffc107;}
.ses-lower-middle{background:#fff8dc;color:#7d6608;border:1px solid #f0c820;}
.ses-middle{background:#e8f5e9;color:#1b5e20;border:1px solid #4caf50;}
.ses-upper-middle{background:#e3f2fd;color:#0d47a1;border:1px solid #2196f3;}
.ses-high{background:#f3e5f5;color:#4a148c;border:1px solid #9c27b0;}
.ses-none{background:#f5f5f5;color:#757575;border:1px solid #bdbdbd;}

/* "Other – specify" pattern */
.other-input-wrap{margin-top:6px;display:none;}
.other-input-wrap.show{display:block;}
.other-input-wrap input{border-left:3px solid #0f3c6e!important;}
.other-hint{font-size:10px;color:#666;font-style:italic;margin-top:2px;}

/* Yes/No radio groups */
.yn-group{display:flex;gap:14px;margin-top:4px;}
.yn-group label{display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;}
.yn-group input[type=radio]{cursor:pointer;}

/* Password */
.password-strength{height:5px;margin-top:5px;border-radius:3px;background-color:#e9ecef;}
.password-strength-bar{height:100%;border-radius:3px;transition:all 0.3s;}
.strength-weak{background-color:#dc3545;width:33%;}
.strength-medium{background-color:#ffc107;width:66%;}
.strength-strong{background-color:#28a745;width:100%;}

/* Validation */
.invalid-feedback{display:none;font-size:11px;color:#dc3545;margin-top:3px;}
.is-invalid{border-color:#dc3545!important;}
.is-invalid~.invalid-feedback{display:block;}
.is-valid{border-color:#28a745!important;}
.hidden{display:none;}
.alert-validation{position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;animation:slideIn .3s ease;}
@keyframes slideIn{from{transform:translateX(400px);opacity:0;}to{transform:translateX(0);opacity:1;}}

/* Auto-generated field styling */
.auto-field{background:#f8f9fa!important;border-left:3px solid #28a745!important;color:#155724;font-weight:600;}
.auto-label-badge{font-size:10px;background:#28a745;color:#fff;padding:1px 6px;border-radius:8px;margin-left:4px;font-weight:500;}

/* Photo */
.photo-upload-wrapper{border:1.5px dashed #b0bec5;border-radius:4px;padding:5px;background:#f8f9fa;width:100%;}
.photo-upload-wrapper:hover{border-color:#1f6bb8;}
.photo-inner{display:flex;align-items:stretch;gap:5px;width:100%;}
.photo-preview-box{width:80px;min-width:80px;height:80px;background:#e9ecef;border:1px solid #dee2e6;border-radius:3px;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;flex-shrink:0;}
.photo-preview-box img{width:100%;height:100%;object-fit:cover;}
.photo-placeholder{text-align:center;color:#adb5bd;padding:4px;}
.photo-placeholder i{font-size:22px;display:block;margin-bottom:2px;}
.photo-placeholder span{font-size:9px;display:block;line-height:1.2;}
.remove-photo{position:absolute;top:2px;right:2px;background:rgba(220,53,69,0.85);color:white;border:none;border-radius:50%;width:18px;height:18px;font-size:9px;cursor:pointer;display:none;align-items:center;justify-content:center;padding:0;line-height:1;}
.photo-btn-group{display:flex;flex-direction:column;gap:4px;flex:1;justify-content:center;}
.photo-btn-group .btn{font-size:11px;padding:5px 6px;border-radius:3px!important;white-space:nowrap;width:100%;text-align:left;}

.input-group-text{border-radius:0!important;}
.age-badge{position:absolute;top:-8px;right:-8px;background:#2196f3;color:white;padding:2px 8px;border-radius:12px;font-size:10px;font-weight:bold;}
.date-format-hint{font-size:10px;color:#6c757d;font-style:italic;}
.char-counter{font-size:11px;color:#6c757d;float:right;}

/* Camera Modal */
#cameraModal .modal-header{background:#0f3c6e;color:white;padding:12px 16px;}
#cameraModal .modal-header .btn-close{filter:invert(1);}
#cameraModal .modal-body{padding:16px;background:#1a1a2e;}
#cameraVideo{width:100%;border-radius:4px;background:#000;display:block;}
#cameraCanvas{display:none;}
.camera-controls{display:flex;justify-content:center;gap:10px;margin-top:12px;}
.btn-capture{background:#0f3c6e;color:white;border:none;padding:10px 28px;border-radius:30px;font-weight:600;font-size:14px;cursor:pointer;}
.btn-retake{background:#6c757d;color:white;border:none;padding:10px 20px;border-radius:30px;font-weight:500;font-size:13px;cursor:pointer;display:none;}
.btn-use-photo{background:#28a745;color:white;border:none;padding:10px 20px;border-radius:30px;font-weight:500;font-size:13px;cursor:pointer;display:none;}
.camera-snapshot-preview{display:none;width:100%;border-radius:4px;margin-top:10px;}
#cameraError{color:#f8d7da;text-align:center;padding:20px;font-size:13px;display:none;}
.camera-facing-btn{position:absolute;top:8px;right:8px;background:rgba(255,255,255,0.2);color:white;border:none;border-radius:50%;width:34px;height:34px;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;z-index:5;}
.camera-video-wrapper{position:relative;}

.site-footer{padding:14px 32px;text-align:center;border-top:1px solid rgba(255,255,255,0.06);font-size:11px;color:rgba(255,255,255,0.28);letter-spacing:0.04em;font-family:'Courier New',monospace;position:relative;z-index:1;}
.site-footer strong{color:rgba(200,150,62,0.6);font-weight:normal;}
@media(max-width:600px){.site-header{padding:10px 14px;flex-wrap:wrap;}.header-nav{margin-left:0;flex-wrap:wrap;}}
</style>
</head>
<body>

<header class="site-header">
    <a href="../officials/index.php" class="logo-badge" title="Home">
        <img src="../officials/image/logo.jpg" alt="Logo" class="logo-img">
    </a>
    <div class="site-header-text">
        <h1>Barangay Sto. Rosario</h1>
        <p>Magallanes, Agusan del Norte</p>
    </div>
    <nav class="header-nav">
        <a href="../officials/index.php"       class="nav-link-pill">🏠 Home</a>
        <a href="../officials/login.php"        class="nav-link-pill">👤 Login</a>
        <a href="register_residents.php"        class="nav-link-pill active">📝 Register</a>
    </nav>
</header>

<form class="registration-form" id="registrationForm" action="register_account.php" method="POST" enctype="multipart/form-data" novalidate>

    <h4>RESIDENT REGISTRATION FORM</h4>
    <small class="subtitle">Please fill out all required fields accurately</small>
    <hr>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- ACCOUNT INFORMATION -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <h6 class="fw-bold"><i class="fas fa-lock"></i> ACCOUNT INFORMATION</h6>
    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">USERNAME <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="text" name="username" id="username" class="form-control" required minlength="4" maxlength="20" pattern="[a-zA-Z0-9_]+" autocomplete="off">
                <span class="input-group-text">
                    <i class="fas fa-circle-notch fa-spin text-muted hidden" id="username-spinner"></i>
                    <i class="fas fa-check text-success hidden" id="username-check"></i>
                    <i class="fas fa-times text-danger hidden" id="username-x"></i>
                </span>
            </div>
            <div class="invalid-feedback" id="username-error"></div>
            <small class="text-muted">4–20 chars, letters/numbers/underscore</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">PASSWORD <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="password" name="password" id="password" class="form-control" required minlength="6" maxlength="50" autocomplete="new-password">
                <button class="btn btn-outline-secondary" type="button" id="togglePassword"><i class="fas fa-eye" id="passwordIcon"></i></button>
            </div>
            <div class="password-strength"><div class="password-strength-bar" id="strengthBar"></div></div>
            <div class="invalid-feedback" id="password-error"></div>
            <small class="text-muted" id="password-hint">Minimum 6 characters</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">CONFIRM PASSWORD <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6" autocomplete="new-password">
                <button class="btn btn-outline-secondary" type="button" id="toggleConfirm"><i class="fas fa-eye" id="confirmIcon"></i></button>
            </div>
            <div class="invalid-feedback" id="confirm-error"></div>
        </div>
    </div>

    <hr>
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- PERSONAL INFORMATION -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <h6 class="fw-bold"><i class="fas fa-user"></i> PERSONAL INFORMATION</h6>
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">FIRST NAME <span class="text-danger">*</span></label>
            <input type="text" name="first_name" id="first_name" class="form-control" required minlength="2" maxlength="50" autocomplete="given-name">
            <div class="invalid-feedback" id="first_name-error"></div>
        </div>
        <div class="col-md-3">
            <label class="form-label">MIDDLE NAME</label>
            <input type="text" name="middle_name" id="middle_name" class="form-control" maxlength="50" autocomplete="additional-name">
        </div>
        <div class="col-md-3">
            <label class="form-label">SURNAME <span class="text-danger">*</span></label>
            <input type="text" name="surname" id="surname" class="form-control" required minlength="2" maxlength="50" autocomplete="family-name">
            <div class="invalid-feedback" id="surname-error"></div>
        </div>
        <div class="col-md-3">
            <label class="form-label">SUFFIX <small class="text-muted fw-normal">(optional)</small></label>
            <select name="suffix" id="suffix" class="form-control">
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

    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">BIRTHDATE <span class="text-danger">*</span></label>
            <input type="date" name="birthdate" id="birthdate" class="form-control" required min="1900-01-01" autocomplete="bday">
            <div class="invalid-feedback" id="birthdate-error"></div>
            <small class="date-format-hint">Format: YYYY-MM-DD</small>
        </div>
        <div class="col-md-2">
            <label class="form-label">AGE <span class="text-danger">*</span></label>
            <div style="position:relative;">
                <input type="number" name="age" id="age" class="form-control" readonly required>
                <span id="age-badge" class="age-badge hidden"></span>
            </div>
            <input type="hidden" name="is_newborn" id="is_newborn" value="No">
        </div>
        <div class="col-md-4">
            <label class="form-label">BIRTHPLACE <span class="text-danger">*</span></label>
            <input type="text" name="birthplace" id="birthplace" class="form-control" required minlength="3" maxlength="100">
            <div class="invalid-feedback" id="birthplace-error"></div>
        </div>
        <div class="col-md-3">
            <label class="form-label">PHOTO <span class="text-danger">*</span></label>
            <div class="photo-upload-wrapper">
                <div class="photo-inner">
                    <div class="photo-preview-box" id="photoPreviewBox">
                        <div class="photo-placeholder" id="photoPlaceholder">
                            <i class="fas fa-user-circle"></i><span>No photo</span>
                        </div>
                        <img id="photoPreviewImg" src="" alt="" style="display:none;">
                        <button type="button" class="remove-photo" id="removePhotoBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="photo-btn-group">
                        <label class="btn btn-outline-secondary mb-0" style="cursor:pointer;">
                            <i class="fas fa-upload me-1"></i> Upload
                            <input type="file" name="image_path" id="image_path" accept="image/jpeg,image/jpg,image/png,image/gif" class="d-none" required>
                        </label>
                        <button type="button" class="btn btn-outline-primary" id="openCameraBtn"><i class="fas fa-camera me-1"></i> Camera</button>
                    </div>
                </div>
                <input type="hidden" name="camera_image" id="camera_image" value="">
            </div>
            <div class="invalid-feedback d-block" id="image-error" style="display:none!important;"></div>
            <small class="text-muted">Max 2MB · JPG PNG GIF</small>
        </div>
    </div>

    <div class="row mb-3 hidden" id="newborn-section">
        <div class="col-12">
            <div class="newborn-box"><i class="fas fa-baby"></i> <strong>NEWBORN DETECTED</strong>
                <p class="mb-0 mt-1" id="newborn-message"></p>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">SEX / GENDER <span class="text-danger">*</span></label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="sex" id="sex_m" value="Male" required onchange="toggleLgbtqReg()">
                <label class="form-check-label" for="sex_m">Male</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="sex" id="sex_f" value="Female" onchange="toggleLgbtqReg()">
                <label class="form-check-label" for="sex_f">Female</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="sex" id="sex_lgbtq" value="LGBTQ+" onchange="toggleLgbtqReg()">
                <label class="form-check-label" for="sex_lgbtq">LGBTQ+</label>
            </div>
            <!-- LGBTQ+ Identity Sub-dropdown -->
            <div id="lgbtq_reg_wrap" class="mt-2" style="display:none;">
                <label class="form-label" style="font-size:11px;font-weight:600;color:#7c3aed">
                    <i class="fas fa-rainbow me-1"></i> LGBTQ+ Identity
                </label>
                <select name="lgbtq_identity" id="lgbtq_identity_reg" class="form-control"
                        onchange="toggleLgbtqOtherReg()" style="border-left:3px solid #7c3aed!important">
                    <option value="">Select Identity</option>
                    <option value="Lesbian">Lesbian</option>
                    <option value="Gay">Gay</option>
                    <option value="Bisexual">Bisexual</option>
                    <option value="Transgender">Transgender</option>
                    <option value="Queer">Queer</option>
                    <option value="Other">Other (specify)</option>
                </select>
                <div id="lgbtq_other_reg_wrap" class="other-input-wrap">
                    <input type="text" name="lgbtq_other_text" id="lgbtq_other_text_reg" class="form-control"
                           maxlength="200" placeholder="Specify identity…" style="border-left:3px solid #7c3aed!important">
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">CIVIL STATUS <span class="text-danger">*</span></label>
            <select name="civil_status" id="civil_status" class="form-control" required>
                <option value="">Select</option>
                <option value="Single">Single</option>
                <option value="Married">Married</option>
                <option value="Widowed">Widowed</option>
                <option value="Separated">Separated</option>
                <option value="Annulled">Annulled</option>
                <option value="Live-in">Live-in</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">NATIONALITY</label>
            <input type="text" name="nationality" class="form-control" value="Filipino">
        </div>
        <div class="col-md-3">
            <label class="form-label">CONTACT NO.</label>
            <input type="text" name="contact_no" id="contact_no" class="form-control" pattern="[0-9+\-\s()]+" maxlength="20" autocomplete="tel" placeholder="09XXXXXXXXX">
        </div>
        <div class="col-md-3">
            <label class="form-label">EMAIL ADDRESS</label>
            <input type="email" name="email" id="email" class="form-control" maxlength="150" autocomplete="email" placeholder="example@gmail.com">
        </div>
    </div>

    <hr>
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- DEMOGRAPHIC INFORMATION -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <h6 class="fw-bold"><i class="fas fa-id-card"></i> DEMOGRAPHIC INFORMATION</h6>
    <div class="demo-box">
        <div class="row mb-3">

            <!-- RELIGION -->
            <div class="col-md-3">
                <label class="form-label">RELIGION</label>
                <select name="religion" id="religion" class="form-control" onchange="toggleOther(this,'religion_other_wrap')">
                    <option value="">Select</option>
                    <option value="Roman Catholic">Roman Catholic</option>
                    <option value="Islam">Islam</option>
                    <option value="Iglesia ni Cristo">Iglesia ni Cristo</option>
                    <option value="Seventh-day Adventist">Seventh-day Adventist</option>
                    <option value="Born Again Christian">Born Again Christian</option>
                    <option value="Baptist">Baptist</option>
                    <option value="Jehovah's Witness">Jehovah's Witness</option>
                    <option value="UCCP">UCCP</option>
                    <option value="Other">Other (please specify)</option>
                </select>
                <div class="other-input-wrap" id="religion_other_wrap">
                    <input type="text" name="religion_other" id="religion_other" class="form-control" maxlength="100" placeholder="Specify religion">
                    <div class="other-hint">Please type your religion</div>
                </div>
            </div>

            <!-- ETHNICITY -->
            <div class="col-md-3">
                <label class="form-label">ETHNICITY / INDIGENOUS GROUP</label>
                <select name="ethnicity" id="ethnicity" class="form-control" onchange="toggleOther(this,'ethnicity_other_wrap')">
                    <option value="">Select</option>
                    <option value="Visayan">Visayan</option>
                    <option value="Cebuano">Cebuano</option>
                    <option value="Mamanwa">Mamanwa (IP)</option>
                    <option value="Higaonon">Higaonon (IP)</option>
                    <option value="Manobo">Manobo (IP)</option>
                    <option value="Maranao">Maranao</option>
                    <option value="Tagalog">Tagalog</option>
                    <option value="Ilocano">Ilocano</option>
                    <option value="Bisaya">Bisaya</option>
                    <option value="Waray">Waray</option>
                    <option value="Other">Other (please specify)</option>
                </select>
                <div class="other-input-wrap" id="ethnicity_other_wrap">
                    <input type="text" name="ethnicity_other" id="ethnicity_other" class="form-control" maxlength="100" placeholder="Specify ethnicity/group">
                    <div class="other-hint">Please type your ethnicity or indigenous group</div>
                </div>
            </div>

            <!-- BLOOD TYPE -->
            <div class="col-md-2">
                <label class="form-label">BLOOD TYPE</label>
                <select name="blood_type" id="blood_type" class="form-control" onchange="toggleOther(this,'blood_type_other_wrap')">
                    <option value="">Select</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                    <option value="Unknown">Unknown</option>
                    <option value="Other">Other</option>
                </select>
                <div class="other-input-wrap" id="blood_type_other_wrap">
                    <input type="text" name="blood_type_other" id="blood_type_other" class="form-control" maxlength="20" placeholder="Specify blood type">
                </div>
            </div>

            <!-- HEIGHT -->
            <div class="col-md-2">
                <label class="form-label">HEIGHT (cm)</label>
                <input type="number" name="height" id="height" class="form-control" step="0.01" min="0" max="300" placeholder="e.g. 165">
                <small class="text-muted" style="font-size:10px;">In centimeters</small>
            </div>

            <!-- WEIGHT -->
            <div class="col-md-2">
                <label class="form-label">WEIGHT (kg)</label>
                <input type="number" name="weight" id="weight" class="form-control" step="0.01" min="0" max="500" placeholder="e.g. 60">
                <small class="text-muted" style="font-size:10px;">In kilograms</small>
            </div>

            <!-- PHILHEALTH -->
            <div class="col-md-4">
                <label class="form-label">PHILHEALTH (PHIC) NO.</label>
                <input type="text" name="philhealth_no" id="philhealth_no" class="form-control" maxlength="30" placeholder="e.g. 18-000000000-0">
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-md-3">
                <label class="form-label">LENGTH OF RESIDENCY (years)</label>
                <input type="number" name="length_of_residency" class="form-control" min="0" max="120" placeholder="e.g. 10">
            </div>
            <div class="col-md-3">
                <label class="form-label">PHILHEALTH MEMBERSHIP</label>
                <select name="membership_type" class="form-control">
                    <option value="">None / Not a member</option>
                    <option value="Private">Private (Employed)</option>
                    <option value="Government">Government Employee</option>
                    <option value="NHTS">NHTS (Indigent)</option>
                    <option value="Senior Citizen">Senior Citizen</option>
                    <option value="OFW">OFW</option>
                    <option value="Self-employed">Self-employed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">NHTS BENEFICIARY?</label>
                <div class="yn-group mt-1">
                    <label><input type="radio" name="is_nhts" value="Yes"> Yes</label>
                    <label><input type="radio" name="is_nhts" value="No" checked> No</label>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">4Ps BENEFICIARY?</label>
                <div class="yn-group mt-1">
                    <label><input type="radio" name="is_4ps" value="Yes"> Yes</label>
                    <label><input type="radio" name="is_4ps" value="No" checked> No</label>
                </div>
            </div>
        </div>

        <!-- PARENT INFORMATION -->
        <div class="row mb-2">
            <div class="col-md-3">
                <label class="form-label">FATHER'S NAME</label>
                <input type="text" name="father_name" id="father_name" class="form-control" maxlength="200" placeholder="e.g. Juan Dela Cruz">
            </div>
            <div class="col-md-3">
                <label class="form-label">FATHER'S OCCUPATION</label>
                <input type="text" name="father_occupation" id="father_occupation" class="form-control" maxlength="200" placeholder="e.g. Farmer, Driver">
            </div>
            <div class="col-md-3">
                <label class="form-label">MOTHER'S NAME</label>
                <input type="text" name="mother_name" id="mother_name" class="form-control" maxlength="200" placeholder="e.g. Maria Dela Cruz">
            </div>
            <div class="col-md-3">
                <label class="form-label">MOTHER'S OCCUPATION</label>
                <input type="text" name="mother_occupation" id="mother_occupation" class="form-control" maxlength="200" placeholder="e.g. Housewife, Teacher">
            </div>
        </div>
    </div>

    <hr>
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- HOUSEHOLD & HOUSING -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <h6 class="fw-bold"><i class="fas fa-home"></i> HOUSEHOLD & HOUSING INFORMATION</h6>
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">HOUSEHOLD POSITION <span class="text-danger">*</span></label>
            <select name="household_position" id="household_position" class="form-control" required>
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
        <div class="col-md-2">
            <label class="form-label">TOTAL HOUSEHOLD <span class="text-danger">*</span></label>
            <input type="number" name="total_household" id="total_household" class="form-control" min="1" max="50" required value="1">
        </div>
        <div class="col-md-3">
            <label class="form-label">SOLO PARENT?</label>
            <div class="yn-group mt-1">
                <label><input type="radio" name="is_solo_parent" value="Yes"> Yes</label>
                <label><input type="radio" name="is_solo_parent" value="No" checked> No</label>
            </div>
            <small class="text-muted" style="font-size:10px;">Single Mother / Single Father</small>
        </div>
        <div class="col-md-2">
            <label class="form-label">FAMILY PLANNING?</label>
            <div class="yn-group mt-1">
                <label><input type="radio" name="family_planning" value="Yes"> Yes</label>
                <label><input type="radio" name="family_planning" value="No" checked> No</label>
            </div>
        </div>
    </div>
    <div class="row mb-3">

        <!-- HOUSE OWNERSHIP -->
        <div class="col-md-3">
            <label class="form-label">HOUSE OWNERSHIP</label>
            <select name="house_ownership" id="house_ownership" class="form-control" onchange="toggleOther(this,'house_ownership_other_wrap')">
                <option value="">Select</option>
                <option value="Owned">Owned</option>
                <option value="Rented">Rented</option>
                <option value="Shared">Shared / With Relatives</option>
                <option value="Government">Government-provided</option>
                <option value="Other">Other (please specify)</option>
            </select>
            <div class="other-input-wrap" id="house_ownership_other_wrap">
                <input type="text" name="house_ownership_other" class="form-control" maxlength="100" placeholder="Specify ownership type">
                <div class="other-hint">Please describe ownership arrangement</div>
            </div>
        </div>

        <!-- HOUSE MATERIAL -->
        <div class="col-md-3">
            <label class="form-label">HOUSE MATERIAL</label>
            <select name="house_material" id="house_material" class="form-control" onchange="toggleOther(this,'house_material_other_wrap')">
                <option value="">Select</option>
                <option value="Concrete">Concrete / Hollow Block</option>
                <option value="Wood">Wood</option>
                <option value="Mixed">Mixed (Concrete + Wood)</option>
                <option value="Light Material">Light Material (Nipa/Bamboo)</option>
                <option value="Other">Other (please specify)</option>
            </select>
            <div class="other-input-wrap" id="house_material_other_wrap">
                <input type="text" name="house_material_other" class="form-control" maxlength="100" placeholder="Specify house material">
                <div class="other-hint">Please describe the main material</div>
            </div>
        </div>

        <!-- TOILET TYPE -->
        <div class="col-md-3">
            <label class="form-label">TOILET TYPE</label>
            <select name="toilet_type" id="toilet_type" class="form-control" onchange="toggleOther(this,'toilet_type_other_wrap')">
                <option value="">Select</option>
                <option value="With Flush">With Flush (Water Sealed)</option>
                <option value="Without Flush">Without Flush (Pit Latrine)</option>
                <option value="Shared">Shared / Communal</option>
                <option value="None">None</option>
                <option value="Other">Other (please specify)</option>
            </select>
            <div class="other-input-wrap" id="toilet_type_other_wrap">
                <input type="text" name="toilet_type_other" class="form-control" maxlength="100" placeholder="Specify toilet type">
            </div>
        </div>

        <!-- WATER SOURCE -->
        <div class="col-md-3">
            <label class="form-label">WATER SOURCE</label>
            <select name="water_source" id="water_source" class="form-control" onchange="toggleOther(this,'water_source_other_wrap')">
                <option value="">Select</option>
                <option value="Level 3 (Piped)">Level 3 – Piped (Water District)</option>
                <option value="Level 2 (Communal Faucet)">Level 2 – Communal Faucet</option>
                <option value="Level 1 (Deep Well)">Level 1 – Deep Well</option>
                <option value="Rainwater">Rainwater Collection</option>
                <option value="Spring">Spring / River</option>
                <option value="Bottled Water">Bottled / Purchased Water</option>
                <option value="Other">Other (please specify)</option>
            </select>
            <div class="other-input-wrap" id="water_source_other_wrap">
                <input type="text" name="water_source_other" class="form-control" maxlength="100" placeholder="Specify water source">
                <div class="other-hint">Please describe your water source</div>
            </div>
        </div>
    </div>

    <hr>
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- ADDRESS -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <h6 class="fw-bold"><i class="fas fa-map-marker-alt"></i> ADDRESS INFORMATION</h6>
    <div class="row mb-3">
        <div class="col-md-2">
            <label class="form-label">HH NO. <small class="text-muted fw-normal">(optional)</small></label>
            <input type="text" name="household_no" id="household_no" class="form-control"
                   maxlength="20" placeholder="e.g. 001"
                   style="border-left:3px solid #0f3c6e!important">
            <small class="text-muted" style="font-size:10px">Household Number</small>
        </div>
        <div class="col-md-2">
            <label class="form-label">PUROK <span class="text-danger">*</span></label>
            <select name="purok" id="purok" class="form-control" required>
                <option value="">Select Purok</option>
                <?php for($i=1;$i<=10;$i++): ?>
                <option value="Purok <?= $i ?>">Purok <?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">BARANGAY <span class="text-danger">*</span></label>
            <select name="barangay" id="barangay" class="form-control" required>
                <option value="">Select Barangay</option>
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
        <div class="col-md-3">
            <label class="form-label">MUNICIPALITY</label>
            <input type="text" name="municipality" class="form-control" value="Magallanes" readonly>
        </div>
        <div class="col-md-2">
            <label class="form-label">PROVINCE</label>
            <input type="text" name="province" class="form-control" value="Agusan Del Norte" readonly>
        </div>
    </div>

    <hr>
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- OCCUPATION & FINANCIAL -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <h6 class="fw-bold"><i class="fas fa-briefcase"></i> OCCUPATION & FINANCIAL INFORMATION</h6>
    <div class="income-box">
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">OCCUPATION TYPE <span class="text-danger">*</span></label>
                <select name="occupation_type" id="occupation_type" class="form-control" required onchange="toggleOther(this,'occupation_type_other_wrap')">
                    <option value="">Select Type</option>
                    <option value="Employed">Employed (Private)</option>
                    <option value="Government Employee">Government Employee</option>
                    <option value="Self-employed">Self-employed</option>
                    <option value="OFW">OFW / Overseas Worker</option>
                    <option value="Student">Student</option>
                    <option value="Unemployed">Unemployed</option>
                    <option value="Retired">Retired</option>
                    <option value="Homemaker">Homemaker / Housewife</option>
                    <option value="Farmer">Farmer / Fisherfolk</option>
                    <option value="Informal Worker">Informal / Daily Wage Worker</option>
                    <option value="PWD">PWD (Not Working)</option>
                    <option value="Other">Other (please specify)</option>
                </select>
                <div class="other-input-wrap" id="occupation_type_other_wrap">
                    <input type="text" name="occupation_type_other" id="occupation_type_other" class="form-control" maxlength="100" placeholder="Specify occupation type">
                    <div class="other-hint">Please describe your occupation type</div>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">OCCUPATION / JOB TITLE</label>
                <input type="text" name="occupation" id="occupation" class="form-control" maxlength="100" placeholder="e.g. Teacher, Farmer, Driver">
                <small class="text-muted" style="font-size:10px;">Specific job title or position</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">MONTHLY INCOME (₱)</label>
                <input type="number" name="monthly_income" id="monthly_income" class="form-control"
                       step="0.01" min="0" max="999999999.99" placeholder="0.00">
                <small class="text-muted" style="font-size:10px;"><i class="fas fa-info-circle"></i> Annual income auto-computed</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    ANNUAL INCOME (₱)
                    <span class="auto-label-badge"><i class="fas fa-magic"></i> Auto</span>
                </label>
                <input type="number" name="annual_income" id="annual_income" class="form-control auto-field"
                       readonly placeholder="Auto-computed" step="0.01">
                <small class="text-muted" style="font-size:10px;">Monthly × 12</small>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-4">
                <label class="form-label">
                    SOCIOECONOMIC STATUS
                    <span class="auto-label-badge"><i class="fas fa-magic"></i> Auto</span>
                </label>
                <input type="text" name="socioeconomic_status" id="socioeconomic_status" class="form-control auto-field" readonly placeholder="Based on annual income">
                <div id="ses-badge-display" class="mt-1"></div>
                <small class="text-muted" style="font-size:10px;">PSA-based income classification</small>
            </div>
            <div class="col-md-8">
                <label class="form-label">INCOME CLASSIFICATION GUIDE</label>
                <div style="font-size:10px;line-height:1.8;color:#555;background:#fff;padding:8px 12px;border-radius:4px;border:1px solid #dee2e6;">
                    <span class="ses-badge ses-poor">Poor</span> &lt; ₱10,957/mo &nbsp;|&nbsp;
                    <span class="ses-badge ses-low">Low Income</span> ₱10,957–₱21,914 &nbsp;|&nbsp;
                    <span class="ses-badge ses-lower-middle">Lower Middle</span> ₱21,914–₱43,828<br>
                    <span class="ses-badge ses-middle">Middle</span> ₱43,828–₱76,669 &nbsp;|&nbsp;
                    <span class="ses-badge ses-upper-middle">Upper Middle</span> ₱76,669–₱131,484 &nbsp;|&nbsp;
                    <span class="ses-badge ses-high">High Income</span> &gt; ₱131,484
                </div>
            </div>
        </div>
    </div>

    <hr>
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- VOTER & EDUCATION -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <h6 class="fw-bold"><i class="fas fa-graduation-cap"></i> VOTER & EDUCATION</h6>
    <div class="row mb-3">
        <div class="col-md-3">
            <label class="form-label">VOTER STATUS <span class="text-danger">*</span></label>
            <select name="voters_status" class="form-control" required>
                <option value="No">Not Registered</option>
                <option value="Yes">Registered Voter</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">EDUCATIONAL ATTAINMENT</label>
            <select name="educational_attainment" id="edu_attainment" class="form-control" onchange="toggleEducFields()">
                <option value="">Select</option>
                <option value="No Formal Education">No Formal Education</option>
                <optgroup label="Currently Studying">
                    <option value="Elementary Level">Elementary Level</option>
                    <option value="High School Level">High School Level</option>
                    <option value="Senior High School Level">Senior High School Level</option>
                    <option value="College Level">College Level</option>
                    <option value="Vocational Level">Vocational / Tech-Voc Level</option>
                </optgroup>
                <optgroup label="Graduate">
                    <option value="Elementary Graduate">Elementary Graduate</option>
                    <option value="High School Graduate">High School Graduate</option>
                    <option value="Senior High School Graduate">Senior High School Graduate</option>
                    <option value="College Graduate">College Graduate</option>
                    <option value="Vocational Graduate">Vocational / Tech-Voc Graduate</option>
                    <option value="Post Graduate">Post Graduate</option>
                </optgroup>
                <option value="Others">Others</option>
            </select>
        </div>
        <div class="col-md-3" id="grade_level_wrap" style="display:none;">
            <label class="form-label">GRADE / YEAR LEVEL</label>
            <input type="text" name="grade_level" id="grade_level" class="form-control" maxlength="50" placeholder="e.g. Grade 7, 2nd Year">
            <small class="text-muted" style="font-size:10px;">Current grade/year if still studying</small>
        </div>
        <div class="col-md-3" id="school_name_wrap" style="display:none;">
            <label class="form-label">SCHOOL NAME</label>
            <input type="text" name="school_name" id="school_name" class="form-control" maxlength="150" placeholder="e.g. Magallanes National HS">
            <small class="text-muted" style="font-size:10px;">Name of school (attended or graduated from)</small>
        </div>
    </div>

    <!-- ── Graduate-only fields: Course, Graduation Date, Eligibility ── -->
    <div class="row mb-3" id="graduate_fields_wrap" style="display:none;">
        <div class="col-md-4" id="course_wrap" style="display:none;">
            <label class="form-label">COURSE <span class="text-danger" id="course_req_star" style="display:none;">*</span></label>
            <select name="course" id="course" class="form-control" onchange="toggleCourseOther()">
                <option value="">Select Course</option>
                <option value="Bachelor of Science in Information Technology">BS Information Technology</option>
                <option value="Bachelor of Science in Computer Science">BS Computer Science</option>
                <option value="Bachelor of Science in Nursing">BS Nursing</option>
                <option value="Bachelor of Science in Accountancy">BS Accountancy</option>
                <option value="Bachelor of Science in Education">BS Education</option>
                <option value="Bachelor of Arts">Bachelor of Arts</option>
                <option value="Bachelor of Science in Criminology">BS Criminology</option>
                <option value="Bachelor of Science in Engineering">BS Engineering</option>
                <option value="Bachelor of Science in Agriculture">BS Agriculture</option>
                <option value="Bachelor of Science in Business Administration">BS Business Administration</option>
                <option value="Bachelor of Science in Social Work">BS Social Work</option>
                <option value="Bachelor of Science in Marine Transportation">BS Marine Transportation</option>
                <option value="Others">Others (Specify)</option>
            </select>
            <div id="course_other_wrap" style="display:none; margin-top:6px;">
                <input type="text" name="course_other" id="course_other" class="form-control" maxlength="150" placeholder="Specify your course">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label">DATE OF GRADUATION</label>
            <input type="date" name="graduation_date" id="graduation_date" class="form-control">
            <small class="text-muted" style="font-size:10px;">Full date of graduation (must be past date)</small>
            <div class="invalid-feedback" id="graduation_date_error">Graduation date cannot be in the future.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">ELIGIBILITY</label>
            <select name="eligibility" id="eligibility" class="form-control" onchange="toggleEligibilityOther()">
                <option value="">Select Eligibility</option>
                <option value="None">None</option>
                <option value="Civil Service Professional">Civil Service Professional (CSP)</option>
                <option value="Civil Service Sub-Professional">Civil Service Sub-Professional (CSSP)</option>
                <option value="RA 1080 - Teacher">RA 1080 – Licensed Teacher</option>
                <option value="RA 1080 - Nurse">RA 1080 – Licensed Nurse</option>
                <option value="RA 1080 - Engineer">RA 1080 – Licensed Engineer</option>
                <option value="RA 1080 - CPA">RA 1080 – Certified Public Accountant</option>
                <option value="RA 1080 - Criminologist">RA 1080 – Licensed Criminologist</option>
                <option value="Bar Exam Passer">Bar Exam Passer</option>
                <option value="Board Exam Passer">Board Exam Passer</option>
                <option value="Others">Others (Specify)</option>
            </select>
            <div id="eligibility_other_wrap" style="display:none; margin-top:6px;">
                <input type="text" name="eligibility_other" id="eligibility_other" class="form-control" maxlength="150" placeholder="Specify eligibility">
            </div>
        </div>
    </div>

    <hr>
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- SPECIAL STATUS -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <h6 class="fw-bold"><i class="fas fa-star-of-life"></i> SPECIAL STATUS</h6>
    <div class="row mb-3">
        <!-- PWD YES/NO -->
        <div class="col-md-4">
            <label class="form-label fw-bold">
                <i class="fas fa-wheelchair text-primary me-1"></i>
                PERSON WITH DISABILITY (PWD)?
            </label>
            <div class="yn-group mt-2">
                <label class="yn-option" id="pwd-no-label">
                    <input type="radio" name="is_pwd" id="is_pwd_no" value="No" checked> No
                </label>
                <label class="yn-option" id="pwd-yes-label">
                    <input type="radio" name="is_pwd" id="is_pwd_yes" value="Yes"> Yes
                </label>
            </div>
            <small class="text-muted" style="font-size:10px;">Alinsunod sa RA 7277 (Magna Carta for Disabled Persons)</small>
        </div>
        <!-- IS DECEASED -->
        <div class="col-md-3">
            <label class="form-label">IS DECEASED?</label>
            <select name="is_deceased" id="is_deceased" class="form-control">
                <option value="No">No</option>
                <option value="Yes">Yes</option>
            </select>
        </div>
        <div class="col-md-3 hidden" id="death_date_div">
            <label class="form-label">DATE OF DEATH</label>
            <input type="date" name="date_of_death" id="date_of_death" class="form-control">
            <small class="date-format-hint">Format: YYYY-MM-DD</small>
        </div>
    </div>

    <!-- PWD DETAILS PANEL — shown only when Yes is selected -->
    <div class="row mb-3 hidden" id="pwd_section">
        <div class="col-12">
            <div class="pwd-box">
                <div class="d-flex align-items-center mb-3">
                    <span class="pwd-icon-badge me-2"><i class="fas fa-wheelchair"></i></span>
                    <div>
                        <div class="fw-bold" style="color:#7d4f00;font-size:13px;">
                            TYPE OF DISABILITY <span class="text-danger">*</span>
                        </div>
                        <div style="font-size:10px;color:#a07830;">
                            Klase sa Kapansanan — Pilia ang angay nga kategorya
                        </div>
                    </div>
                </div>

                <!-- Disability type cards -->
                <div class="row g-2 mb-3" id="pwd-type-cards">
                    <?php
                    $pwd_types = [
                        ['val'=>'Physical Disability',      'en'=>'Physical Disability',       'bis'=>'Pisikal nga Kapansanan',               'icon'=>'fa-person-walking','color'=>'#1565c0'],
                        ['val'=>'Visual Disability',        'en'=>'Visual Disability',         'bis'=>'Kapansanan sa Panan-aw',               'icon'=>'fa-eye-slash',     'color'=>'#6a1b9a'],
                        ['val'=>'Hearing Disability',       'en'=>'Hearing Disability',        'bis'=>'Kapansanan sa Pandungog',              'icon'=>'fa-deaf',          'color'=>'#00695c'],
                        ['val'=>'Speech Disability',        'en'=>'Speech Disability',         'bis'=>'Kapansanan sa Pagsulti',               'icon'=>'fa-comment-slash', 'color'=>'#e65100'],
                        ['val'=>'Intellectual Disability',  'en'=>'Intellectual Disability',   'bis'=>'Panghunahuna nga Kapansanan',          'icon'=>'fa-brain',         'color'=>'#4527a0'],
                        ['val'=>'Psychosocial Disability',  'en'=>'Psychosocial Disability',   'bis'=>'Mental o Emosyonal nga Kapansanan',    'icon'=>'fa-heart-pulse',   'color'=>'#c62828'],
                        ['val'=>'Multiple Disabilities',    'en'=>'Multiple Disabilities',     'bis'=>'Daghang Kapansanan',                   'icon'=>'fa-people-group',  'color'=>'#2e7d32'],
                        ['val'=>'Chronic Illness',          'en'=>'Chronic Illness',           'bis'=>'Malungtarong Sakit',                   'icon'=>'fa-kit-medical',   'color'=>'#d84315'],
                        ['val'=>'Other','en'=>'Other (specify below)', 'bis'=>'Uban pa (ibutang sa ubos)', 'icon'=>'fa-ellipsis','color'=>'#546e7a'],
                    ];
                    foreach($pwd_types as $t): ?>
                    <div class="col-6 col-md-4">
                        <label class="pwd-type-card" data-val="<?= htmlspecialchars($t['val']) ?>">
                            <input type="radio" name="pwd_type" value="<?= htmlspecialchars($t['val']) ?>" class="pwd-type-radio d-none">
                            <span class="pwd-card-icon" style="color:<?= $t['color'] ?>">
                                <i class="fas <?= $t['icon'] ?>"></i>
                            </span>
                            <span class="pwd-card-text">
                                <span class="pwd-card-en"><?= htmlspecialchars($t['en']) ?></span>
                                <span class="pwd-card-bis"><?= htmlspecialchars($t['bis']) ?></span>
                            </span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Other — specify -->
                <div id="pwd_other_wrap" class="other-input-wrap">
                    <label class="form-label" style="font-size:12px;color:#7d4f00;">
                        <i class="fas fa-pen me-1"></i> Specify Disability Type <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="pwd_type_other" id="pwd_type_other" class="form-control"
                           maxlength="200"
                           placeholder="e.g. Autism Spectrum Disorder, Down Syndrome, Cerebral Palsy...">
                    <div class="other-hint">Ibutang ang klase sa kapansanan nga wala sa lista</div>
                </div>

                <!-- Hidden field carries the final resolved value to PHP -->
                <input type="hidden" name="pwd_type" id="pwd_type_hidden" value="">
            </div>
        </div>
    </div>

    <hr>
    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- HEALTH PROFILE -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <h6 class="fw-bold"><i class="fas fa-heartbeat"></i> HEALTH PROFILE</h6>
    <div class="health-box">
        <p class="mb-3 text-muted" style="font-size:12px;"><i class="fas fa-info-circle"></i> Mark all that apply to this resident. This information is kept confidential.</p>
        <div class="row">
            <div class="col-6 col-md-2 mb-3">
                <label class="form-label d-block">SMOKER?</label>
                <div class="yn-group">
                    <label><input type="radio" name="is_smoker" value="Yes"> Yes</label>
                    <label><input type="radio" name="is_smoker" value="No" checked> No</label>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <label class="form-label d-block">BINGE DRINKER?</label>
                <div class="yn-group">
                    <label><input type="radio" name="is_binge_drinker" value="Yes"> Yes</label>
                    <label><input type="radio" name="is_binge_drinker" value="No" checked> No</label>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <label class="form-label d-block">HYPERTENSION?</label>
                <div class="yn-group">
                    <label><input type="radio" name="has_hypertension" value="Yes"> Yes</label>
                    <label><input type="radio" name="has_hypertension" value="No" checked> No</label>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <label class="form-label d-block">DIABETES (DM)?</label>
                <div class="yn-group">
                    <label><input type="radio" name="has_diabetes" value="Yes"> Yes</label>
                    <label><input type="radio" name="has_diabetes" value="No" checked> No</label>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <label class="form-label d-block">ASTHMA?</label>
                <div class="yn-group">
                    <label><input type="radio" name="has_asthma" value="Yes"> Yes</label>
                    <label><input type="radio" name="has_asthma" value="No" checked> No</label>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <label class="form-label d-block">TUBERCULOSIS (TB)?</label>
                <div class="yn-group">
                    <label><input type="radio" name="has_tb" value="Yes"> Yes</label>
                    <label><input type="radio" name="has_tb" value="No" checked> No</label>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-2">
                <label class="form-label d-block">CANCER?</label>
                <div class="yn-group">
                    <label><input type="radio" name="has_cancer" value="Yes"> Yes</label>
                    <label><input type="radio" name="has_cancer" value="No" checked> No</label>
                </div>
            </div>
            <div class="col-6 col-md-2 mb-2">
                <label class="form-label d-block">MENTAL HEALTH?</label>
                <div class="yn-group">
                    <label><input type="radio" name="has_mental_health" value="Yes"> Yes</label>
                    <label><input type="radio" name="has_mental_health" value="No" checked> No</label>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mb-3 mt-3">
        <small class="text-muted">Already have an account? <a href="../officials/login.php" class="text-primary fw-semibold">Login here &rarr;</a></small>
    </div>
    <div class="text-end">
        <a href="../officials/index.php" class="btn btn-outline-secondary me-2"><i class="fas fa-home"></i> Home</a>
        <button type="submit" class="btn btn-success" id="submitBtn">
            <i class="fas fa-save"></i>
            <span id="submitText">Register Account</span>
            <span id="submitSpinner" class="spinner-border spinner-border-sm hidden" role="status"></span>
        </button>
    </div>
</form>

<!-- CAMERA MODAL -->
<div class="modal fade" id="cameraModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title mb-0"><i class="fas fa-camera me-2"></i> Take Photo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeCameraModalBtn"></button>
            </div>
            <div class="modal-body">
                <div id="cameraError"><i class="fas fa-exclamation-triangle fa-2x mb-2 d-block text-center"></i><span id="cameraErrorMsg">Camera not available.</span></div>
                <div class="camera-video-wrapper" id="cameraVideoWrapper">
                    <video id="cameraVideo" autoplay playsinline muted></video>
                    <button type="button" class="camera-facing-btn" id="switchCameraBtn"><i class="fas fa-sync-alt"></i></button>
                </div>
                <img id="cameraSnapshotPreview" class="camera-snapshot-preview" alt="Snapshot">
                <canvas id="cameraCanvas"></canvas>
                <div class="camera-controls">
                    <button type="button" class="btn-capture" id="captureBtn"><i class="fas fa-camera me-1"></i> Capture</button>
                    <button type="button" class="btn-retake"    id="retakeBtn"><i class="fas fa-redo me-1"></i> Retake</button>
                    <button type="button" class="btn-use-photo" id="usePhotoBtn"><i class="fas fa-check me-1"></i> Use Photo</button>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="site-footer">
    <p>Copyright &copy; 2026 <strong>Barangay Sto. Rosario Resident Information System</strong>. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const CHECK_USERNAME_URL = 'check_username.php';

// ── Philippine Socioeconomic Classification (PSA-based monthly thresholds) ────
// Source: PSA 2021 Family Income and Expenditure Survey brackets
const SES_BRACKETS = [
    { max: 10957,   label: 'Poor',               cssClass: 'ses-poor'         },
    { max: 21914,   label: 'Low Income',          cssClass: 'ses-low'          },
    { max: 43828,   label: 'Lower Middle Income', cssClass: 'ses-lower-middle' },
    { max: 76669,   label: 'Middle Income',       cssClass: 'ses-middle'       },
    { max: 131484,  label: 'Upper Middle Income', cssClass: 'ses-upper-middle' },
    { max: Infinity,label: 'High Income',         cssClass: 'ses-high'         },
];

function classifySES(monthlyIncome) {
    if (monthlyIncome === null || monthlyIncome === '' || isNaN(monthlyIncome)) return null;
    const m = parseFloat(monthlyIncome);
    if (m < 0) return null;
    for (const b of SES_BRACKETS) {
        if (m <= b.max) return b;
    }
    return SES_BRACKETS[SES_BRACKETS.length - 1];
}

// ── Income auto-computation ───────────────────────────────────────────────────
const monthlyIncomeInput   = document.getElementById('monthly_income');
const annualIncomeInput    = document.getElementById('annual_income');
const sesInput             = document.getElementById('socioeconomic_status');
const sesBadgeDisplay      = document.getElementById('ses-badge-display');

function updateIncomeAndSES() {
    const monthly = parseFloat(monthlyIncomeInput.value);
    if (!isNaN(monthly) && monthly >= 0) {
        const annual = (monthly * 12).toFixed(2);
        annualIncomeInput.value = annual;
        const ses = classifySES(monthly);
        if (ses) {
            sesInput.value = ses.label;
            sesBadgeDisplay.innerHTML = `<span class="ses-badge ${ses.cssClass}">${ses.label}</span>
                <small class="text-muted ms-1" style="font-size:10px;">₱${monthly.toLocaleString('en-PH',{minimumFractionDigits:2})}/mo → ₱${parseFloat(annual).toLocaleString('en-PH',{minimumFractionDigits:2})}/yr</small>`;
        }
    } else {
        annualIncomeInput.value = '';
        sesInput.value = '';
        sesBadgeDisplay.innerHTML = '<span class="ses-badge ses-none">—</span> <small class="text-muted" style="font-size:10px;">Enter monthly income to classify</small>';
    }
}

monthlyIncomeInput.addEventListener('input', updateIncomeAndSES);
updateIncomeAndSES(); // run on load

// ── Other/specify toggle ──────────────────────────────────────────────────────
function toggleOther(select, wrapId) {
    const wrap = document.getElementById(wrapId);
    if (!wrap) return;
    if (select.value === 'Other') {
        wrap.classList.add('show');
        const inp = wrap.querySelector('input');
        if (inp) { inp.required = true; setTimeout(()=>inp.focus(),50); }
    } else {
        wrap.classList.remove('show');
        const inp = wrap.querySelector('input');
        if (inp) { inp.required = false; inp.value = ''; }
    }
}

// ── Grade/School/Graduate visibility based on education level ────────────────
function toggleEducFields() {
    const val = document.getElementById('edu_attainment').value;
    const studyLevels    = ['Elementary Level','High School Level','Senior High School Level','College Level','Vocational Level'];
    const graduateLevels = ['Elementary Graduate','High School Graduate','Senior High School Graduate','College Graduate','Vocational Graduate','Post Graduate'];
    const courseLevels   = ['College Graduate','Vocational Graduate','Post Graduate'];
    const isStudying  = studyLevels.includes(val);
    const isGraduate  = graduateLevels.includes(val);
    const hasCourse   = courseLevels.includes(val);

    // Grade / Year Level — only for currently studying
    document.getElementById('grade_level_wrap').style.display = isStudying ? '' : 'none';
    // School Name — for both studying and graduates
    document.getElementById('school_name_wrap').style.display = (isStudying || isGraduate) ? '' : 'none';
    // Graduate fields block (graduation date, eligibility)
    document.getElementById('graduate_fields_wrap').style.display = isGraduate ? '' : 'none';
    // Course dropdown — only for College/Vocational/Post Graduate
    document.getElementById('course_wrap').style.display = hasCourse ? '' : 'none';

    // Reset fields when hidden
    if (!isStudying) { document.getElementById('grade_level').value = ''; }
    if (!isStudying && !isGraduate) { document.getElementById('school_name').value = ''; }
    if (!isGraduate) {
        document.getElementById('graduation_date').value = '';
        document.getElementById('eligibility').value = '';
        document.getElementById('eligibility_other').value = '';
        document.getElementById('eligibility_other_wrap').style.display = 'none';
    }
    if (!hasCourse) {
        document.getElementById('course').value = '';
        document.getElementById('course_other').value = '';
        document.getElementById('course_other_wrap').style.display = 'none';
    }
}
function toggleCourseOther() {
    const sel = document.getElementById('course');
    const wrap = document.getElementById('course_other_wrap');
    const inp = document.getElementById('course_other');
    if (sel.value === 'Others') {
        wrap.style.display = '';
        inp.required = true;
        setTimeout(() => inp.focus(), 50);
    } else {
        wrap.style.display = 'none';
        inp.required = false;
        inp.value = '';
    }
}
function toggleEligibilityOther() {
    const sel = document.getElementById('eligibility');
    const wrap = document.getElementById('eligibility_other_wrap');
    const inp = document.getElementById('eligibility_other');
    if (sel.value === 'Others') {
        wrap.style.display = '';
        inp.required = true;
        setTimeout(() => inp.focus(), 50);
    } else {
        wrap.style.display = 'none';
        inp.required = false;
        inp.value = '';
    }
}
toggleEducFields();

// ── Password toggles ──────────────────────────────────────────────────────────
document.getElementById('togglePassword').addEventListener('click',function(){
    const p=document.getElementById('password'),i=document.getElementById('passwordIcon');
    p.type=p.type==='password'?'text':'password';i.classList.toggle('fa-eye');i.classList.toggle('fa-eye-slash');
});
document.getElementById('toggleConfirm').addEventListener('click',function(){
    const p=document.getElementById('confirm_password'),i=document.getElementById('confirmIcon');
    p.type=p.type==='password'?'text':'password';i.classList.toggle('fa-eye');i.classList.toggle('fa-eye-slash');
});

// ── Username availability ─────────────────────────────────────────────────────
const uInput=document.getElementById('username'),uSpinner=document.getElementById('username-spinner'),uCheck=document.getElementById('username-check'),uX=document.getElementById('username-x');
let uTimeout,isUsernameValid=false;
uInput.addEventListener('input',function(){
    const v=this.value.trim();clearTimeout(uTimeout);
    [uSpinner,uCheck,uX].forEach(e=>e.classList.add('hidden'));
    this.classList.remove('is-valid','is-invalid');isUsernameValid=false;
    if(!v){setErr(this,'username-error','Username is required');return;}
    if(v.length<4){setErr(this,'username-error','Min 4 characters');uX.classList.remove('hidden');return;}
    if(v.length>20){setErr(this,'username-error','Max 20 characters');uX.classList.remove('hidden');return;}
    if(!/^[a-zA-Z0-9_]+$/.test(v)){setErr(this,'username-error','Letters, numbers, underscore only');uX.classList.remove('hidden');return;}
    uSpinner.classList.remove('hidden');
    uTimeout=setTimeout(()=>{
        $.ajax({url:CHECK_USERNAME_URL,method:'POST',data:{username:v},dataType:'json',
            success(r){uSpinner.classList.add('hidden');if(r.available){uCheck.classList.remove('hidden');setOk(uInput);isUsernameValid=true;}else{uX.classList.remove('hidden');setErr(uInput,'username-error','Username already taken');isUsernameValid=false;}},
            error(){uSpinner.classList.add('hidden');uX.classList.remove('hidden');setErr(uInput,'username-error','Error checking username');isUsernameValid=false;}
        });
    },500);
});

// ── Password strength ─────────────────────────────────────────────────────────
const pwdInput=document.getElementById('password'),confInput=document.getElementById('confirm_password'),sBar=document.getElementById('strengthBar'),pwdHint=document.getElementById('password-hint');
pwdInput.addEventListener('input',function(){
    const v=this.value,s=calcStr(v);updStr(s);
    if(!v){this.classList.remove('is-valid','is-invalid');sBar.className='password-strength-bar';return;}
    if(v.length<6){setErr(this,'password-error','Min 6 characters');return;}
    if(s.score<2){setErr(this,'password-error','Password too weak');return;}
    setOk(this);if(confInput.value)confInput.dispatchEvent(new Event('input'));
});
confInput.addEventListener('input',function(){
    if(!this.value){this.classList.remove('is-valid','is-invalid');return;}
    if(this.value!==pwdInput.value){setErr(this,'confirm-error','Passwords do not match');return;}
    setOk(this);
});
function calcStr(p){let s=0;if(p.length>=8)s++;if(p.length>=12)s++;if(/[a-z]/.test(p)&&/[A-Z]/.test(p))s++;if(/[0-9]/.test(p))s++;if(/[^a-zA-Z0-9]/.test(p))s++;return{score:s,strength:s<=2?'weak':s<=3?'medium':'strong'};}
function updStr(s){sBar.className='password-strength-bar';const m={weak:['strength-weak','Weak','#dc3545'],medium:['strength-medium','Medium','#ffc107'],strong:['strength-strong','Strong','#28a745']};const[cls,txt,col]=m[s.strength];sBar.classList.add(cls);pwdHint.textContent=txt;pwdHint.style.color=col;}

// ── Name capitalize ───────────────────────────────────────────────────────────
['first_name','middle_name','surname'].forEach(id=>{
    const f=document.getElementById(id);if(!f)return;
    f.addEventListener('input',function(){this.value=this.value.replace(/\b\w/g,c=>c.toUpperCase());if(this.required&&this.value.trim())setOk(this);});
});

// ── Birthdate / age ───────────────────────────────────────────────────────────
const bdInput=document.getElementById('birthdate'),ageInput=document.getElementById('age'),ageBadge=document.getElementById('age-badge'),isNewborn=document.getElementById('is_newborn');
bdInput.max=new Date().toISOString().split('T')[0];
bdInput.addEventListener('change',function(){
    const v=this.value;
    if(!v){ageInput.value='';ageBadge.classList.add('hidden');document.getElementById('newborn-section').classList.add('hidden');isNewborn.value='No';return;}
    const[yr,mo,dy]=v.split('-').map(Number),cur=new Date().getFullYear();
    if(yr<1900||yr>cur){setErr(this,'birthdate-error',`Year must be 1900–${cur}`);ageInput.value='';return;}
    const bd=new Date(yr,mo-1,dy),today=new Date();
    if(bd>today){setErr(this,'birthdate-error','Cannot be in the future');return;}
    const a=calcAge(bd,today);
    if(a.years===0){
        isNewborn.value='Yes';ageBadge.textContent=a.months+'mo';ageBadge.classList.remove('hidden');
        document.getElementById('newborn-section').classList.remove('hidden');
        document.getElementById('newborn-message').textContent=`Baby is ${a.months} month(s) and ${a.days} day(s) old. Automatically marked as NEWBORN.`;
        ageInput.value=0;
    } else {
        isNewborn.value='No';ageInput.value=a.years;ageBadge.classList.add('hidden');
        document.getElementById('newborn-section').classList.add('hidden');
    }
    setOk(this);
});
function calcAge(bd,t){let y=t.getFullYear()-bd.getFullYear(),m=t.getMonth()-bd.getMonth(),d=t.getDate()-bd.getDate();if(d<0){m--;d+=new Date(t.getFullYear(),t.getMonth(),0).getDate();}if(m<0){y--;m+=12;}return{years:y,months:m,days:d};}

// ── Photo ─────────────────────────────────────────────────────────────────────
const imgInput=document.getElementById('image_path'),camImg=document.getElementById('camera_image'),prevImg=document.getElementById('photoPreviewImg'),placeholder=document.getElementById('photoPlaceholder'),removeBtn=document.getElementById('removePhotoBtn');
function showPrev(src){prevImg.src=src;prevImg.style.display='block';placeholder.style.display='none';removeBtn.style.display='flex';}
function clearPrev(){prevImg.src='';prevImg.style.display='none';placeholder.style.display='flex';removeBtn.style.display='none';}
removeBtn.addEventListener('click',()=>{imgInput.value='';camImg.value='';imgInput.required=true;clearPrev();});
imgInput.addEventListener('change',function(){
    const f=this.files[0];camImg.value='';
    if(!f){clearPrev();return;}
    if(!['image/jpeg','image/jpg','image/png','image/gif'].includes(f.type)){showImgErr('Only JPG/PNG/GIF allowed');this.value='';return;}
    if(f.size>2*1024*1024){showImgErr('Max 2MB');this.value='';return;}
    const r=new FileReader();r.onload=e=>showPrev(e.target.result);r.readAsDataURL(f);
});
function showImgErr(m){const e=document.getElementById('image-error');e.textContent=m;e.style.display='block';}

// ── PWD Yes/No radio toggle ───────────────────────────────────────────────────
const pwdRadios    = document.querySelectorAll('input[name="is_pwd"]');
const pwdSection   = document.getElementById('pwd_section');
const pwdNoLabel   = document.getElementById('pwd-no-label');
const pwdYesLabel  = document.getElementById('pwd-yes-label');
const pwdOtherWrap = document.getElementById('pwd_other_wrap');
const pwdOtherInp  = document.getElementById('pwd_type_other');
const pwdHidden    = document.getElementById('pwd_type_hidden');

function updatePwdYnStyle() {
    const isYes = document.getElementById('is_pwd_yes').checked;
    pwdNoLabel.classList.toggle('active-no',  !isYes);
    pwdYesLabel.classList.toggle('active-yes', isYes);
    if (isYes) {
        pwdSection.classList.remove('hidden');
    } else {
        pwdSection.classList.add('hidden');
        document.querySelectorAll('.pwd-type-card').forEach(c => c.classList.remove('selected'));
        document.querySelectorAll('.pwd-type-radio').forEach(r => r.checked = false);
        pwdOtherWrap.classList.remove('show');
        pwdOtherInp.value = '';
        pwdOtherInp.required = false;
        pwdHidden.value = '';
    }
}
pwdRadios.forEach(r => r.addEventListener('change', updatePwdYnStyle));
updatePwdYnStyle(); // init

// ── PWD type card click selection ─────────────────────────────────────────────
document.querySelectorAll('.pwd-type-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.pwd-type-card').forEach(c => c.classList.remove('selected'));
        document.querySelectorAll('.pwd-type-radio').forEach(r => r.checked = false);
        this.classList.add('selected');
        const radio = this.querySelector('.pwd-type-radio');
        radio.checked = true;
        if (radio.value === 'Other') {
            pwdOtherWrap.classList.add('show');
            pwdOtherInp.required = true;
            pwdHidden.value = '';
            setTimeout(() => pwdOtherInp.focus(), 60);
        } else {
            pwdOtherWrap.classList.remove('show');
            pwdOtherInp.required = false;
            pwdOtherInp.value = '';
            pwdHidden.value = radio.value;
        }
    });
});

// Keep hidden field synced when typing in "Other" input
pwdOtherInp.addEventListener('input', function() {
    pwdHidden.value = this.value.trim();
});

// ── Deceased toggle ───────────────────────────────────────────────────────────
document.getElementById('is_deceased').addEventListener('change',function(){
    const div=document.getElementById('death_date_div'),dd=document.getElementById('date_of_death');
    if(this.value==='Yes'){div.classList.remove('hidden');dd.required=true;}
    else{div.classList.add('hidden');dd.required=false;dd.value='';}
});

// ── Helpers ───────────────────────────────────────────────────────────────────
function setErr(field,errId,msg){field.classList.remove('is-valid');field.classList.add('is-invalid');const e=document.getElementById(errId);if(e)e.textContent=msg;}
function setOk(field){field.classList.remove('is-invalid');field.classList.add('is-valid');}
function showAlert(msg,type){const d=document.createElement('div');d.className=`alert alert-${type} alert-validation alert-dismissible`;d.innerHTML=`<strong>${type==='danger'?'Error!':'Info'}</strong> ${msg}<button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>`;document.body.appendChild(d);setTimeout(()=>d.remove(),5000);}

// ── Form submit ───────────────────────────────────────────────────────────────
document.getElementById('registrationForm').addEventListener('submit',function(e){
    e.preventDefault();
    if(!isUsernameValid){showAlert('Please enter a valid available username','danger');uInput.focus();return;}
    if(!/^\d{4}-\d{2}-\d{2}$/.test(bdInput.value)){setErr(bdInput,'birthdate-error','Invalid birthdate!');return;}
    // ── Validate graduation date (must be past) ─────────────────────────
    const gradDateInput = document.getElementById('graduation_date');
    if (gradDateInput.value) {
        const gradDate = new Date(gradDateInput.value);
        if (gradDate > new Date()) { setErr(gradDateInput,'graduation_date_error','Graduation date cannot be in the future'); gradDateInput.focus(); return; }
    }
    // ── Validate course "Others" specify field ───────────────────────────
    const courseWrap = document.getElementById('course_wrap');
    if (courseWrap.style.display !== 'none') {
        const courseSel = document.getElementById('course');
        if (courseSel.value === 'Others') {
            const courseOther = document.getElementById('course_other');
            if (!courseOther.value.trim()) { showAlert('Please specify your course.','danger'); courseOther.focus(); return; }
            if (courseOther.value.trim().length > 150) { showAlert('Course name max 150 characters.','danger'); courseOther.focus(); return; }
        }
    }
    // ── Validate eligibility "Others" specify field ──────────────────────
    const eligSel = document.getElementById('eligibility');
    if (eligSel.value === 'Others') {
        const eligOther = document.getElementById('eligibility_other');
        if (!eligOther.value.trim()) { showAlert('Please specify your eligibility.','danger'); eligOther.focus(); return; }
    }
    const hasCam=camImg.value.trim().length>0,hasFile=imgInput.files&&imgInput.files.length>0;
    if(!hasCam&&!hasFile){showImgErr('Please upload or capture a photo');return;}
    // Validate occupation type
    const occType = document.getElementById('occupation_type');
    if (!occType.value) { showAlert('Please select an Occupation Type.','danger'); occType.focus(); return; }
    // Validate PWD type when PWD = Yes
    if (document.getElementById('is_pwd_yes').checked) {
        const pwdVal = document.getElementById('pwd_type_hidden').value.trim();
        if (!pwdVal) { showAlert('Please select a disability type for the PWD field.','danger'); document.getElementById('pwd_section').scrollIntoView({behavior:'smooth'}); return; }
    }
    // Check "Other – specify" fields are filled
    const otherFields=[
        {sel:'religion',inp:'religion_other'},
        {sel:'ethnicity',inp:'ethnicity_other'},
        {sel:'blood_type',inp:'blood_type_other'},
        {sel:'house_ownership',inp:'house_ownership_other'},
        {sel:'house_material',inp:'house_material_other'},
        {sel:'toilet_type',inp:'toilet_type_other'},
        {sel:'water_source',inp:'water_source_other'},
        {sel:'occupation_type',inp:'occupation_type_other'},
    ];
    for(const f of otherFields){
        const sel=document.getElementById(f.sel);
        if(sel&&sel.value==='Other'){
            const inp=document.querySelector(`input[name="${f.inp}"]`);
            if(inp&&!inp.value.trim()){showAlert(`Please specify the "${sel.previousElementSibling.textContent.replace(' *','').trim()}" field`,'danger');inp.focus();return;}
        }
    }
    const btn=document.getElementById('submitBtn');
    btn.disabled=true;document.getElementById('submitText').classList.add('hidden');document.getElementById('submitSpinner').classList.remove('hidden');
    this.submit();
});

// ── Camera ────────────────────────────────────────────────────────────────────
const openCamBtn=document.getElementById('openCameraBtn'),capBtn=document.getElementById('captureBtn'),retBtn=document.getElementById('retakeBtn'),useBtn=document.getElementById('usePhotoBtn'),swBtn=document.getElementById('switchCameraBtn'),camVideo=document.getElementById('cameraVideo'),camCanvas=document.getElementById('cameraCanvas'),snapPrev=document.getElementById('cameraSnapshotPreview'),camVidWrap=document.getElementById('cameraVideoWrapper'),camErrDiv=document.getElementById('cameraError'),camErrMsg=document.getElementById('cameraErrorMsg');
let camStream=null,facingMode='user',capturedURL=null;
const camModalEl=document.getElementById('cameraModal'),camModal=new bootstrap.Modal(camModalEl);
openCamBtn.addEventListener('click',()=>{resetCam();camModal.show();startCam();});
camModalEl.addEventListener('hidden.bs.modal',()=>{stopCam();resetCam();});
swBtn.addEventListener('click',()=>{facingMode=facingMode==='user'?'environment':'user';stopCam();startCam();});
capBtn.addEventListener('click',()=>{
    const w=camVideo.videoWidth||640,h=camVideo.videoHeight||480;
    camCanvas.width=w;camCanvas.height=h;
    const ctx=camCanvas.getContext('2d');
    if(facingMode==='user'){ctx.translate(w,0);ctx.scale(-1,1);}
    ctx.drawImage(camVideo,0,0,w,h);
    capturedURL=camCanvas.toDataURL('image/jpeg',0.92);
    snapPrev.src=capturedURL;snapPrev.style.display='block';camVidWrap.style.display='none';
    capBtn.style.display='none';retBtn.style.display='inline-block';useBtn.style.display='inline-block';
});
retBtn.addEventListener('click',()=>{capturedURL=null;snapPrev.style.display='none';camVidWrap.style.display='block';capBtn.style.display='inline-block';retBtn.style.display='none';useBtn.style.display='none';startCam();});
useBtn.addEventListener('click',()=>{if(!capturedURL)return;camImg.value=capturedURL;imgInput.value='';imgInput.required=false;showPrev(capturedURL);document.getElementById('image-error').style.display='none';camModal.hide();showAlert('Photo captured!','success');});
function startCam(){camErrDiv.style.display='none';camVidWrap.style.display='block';if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){showCamErr('Browser does not support camera.');return;}navigator.mediaDevices.getUserMedia({video:{facingMode,width:{ideal:1280},height:{ideal:720}},audio:false}).then(s=>{camStream=s;camVideo.srcObject=s;camVideo.play();}).catch(err=>{let m='Camera access denied.';if(err.name==='NotFoundError')m='No camera found.';if(err.name==='NotReadableError')m='Camera in use.';showCamErr(m);});}
function stopCam(){if(camStream){camStream.getTracks().forEach(t=>t.stop());camStream=null;}camVideo.srcObject=null;}
function resetCam(){capturedURL=null;snapPrev.src='';snapPrev.style.display='none';camVidWrap.style.display='block';capBtn.style.display='inline-block';retBtn.style.display='none';useBtn.style.display='none';camErrDiv.style.display='none';}
function showCamErr(m){camVidWrap.style.display='none';camErrDiv.style.display='block';camErrMsg.textContent=m;capBtn.style.display='none';}
camVideo.addEventListener('loadedmetadata',()=>{camVideo.style.transform=facingMode==='user'?'scaleX(-1)':'none';});

// ── LGBTQ+ toggle for registration form ──────────────────────────────────────
function toggleLgbtqReg() {
    const wrap = document.getElementById('lgbtq_reg_wrap');
    const isLgbtq = document.getElementById('sex_lgbtq').checked;
    wrap.style.display = isLgbtq ? 'block' : 'none';
    if (!isLgbtq) {
        document.getElementById('lgbtq_identity_reg').value = '';
        document.getElementById('lgbtq_other_reg_wrap').classList.remove('show');
        document.getElementById('lgbtq_other_text_reg').value = '';
    }
}
function toggleLgbtqOtherReg() {
    const ident = document.getElementById('lgbtq_identity_reg');
    const wrap  = document.getElementById('lgbtq_other_reg_wrap');
    const inp   = document.getElementById('lgbtq_other_text_reg');
    if (ident.value === 'Other') {
        wrap.classList.add('show');
        setTimeout(() => inp.focus(), 50);
    } else {
        wrap.classList.remove('show');
        inp.value = '';
    }
}
</script>
</body>
</html>