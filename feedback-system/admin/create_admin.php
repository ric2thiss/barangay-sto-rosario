<?php
// create_admin.php

// Include your existing database configuration
require_once '../config/config.php';// Assuming your database config is in config.php

// // Check if user has admin privileges - using your existing requireAdmin function
// requireAdmin(); // This will redirect if not admin

// Initialize variables
$firstname = $middlename = $lastname = $username = $email = '';
$errors = [];
$success = '';

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $firstname = trim($conn->real_escape_string($_POST['firstname']));
    $middlename = trim($conn->real_escape_string($_POST['middlename']));
    $lastname = trim($conn->real_escape_string($_POST['lastname']));
    $username = trim($conn->real_escape_string($_POST['username']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation rules
    if (empty($firstname)) {
        $errors['firstname'] = 'First name is required';
    } elseif (strlen($firstname) < 2) {
        $errors['firstname'] = 'First name must be at least 2 characters';
    }

    if (empty($lastname)) {
        $errors['lastname'] = 'Last name is required';
    } elseif (strlen($lastname) < 2) {
        $errors['lastname'] = 'Last name must be at least 2 characters';
    }

    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username must be at least 4 characters';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['username'] = 'Username already exists';
        }
        $stmt->close();
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors['email'] = 'Email already registered';
        }
        $stmt->close();
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter and one number';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare SQL statement - ALWAYS SET user_type as 'admin'
        $stmt = $conn->prepare("
            INSERT INTO users 
            (firstname, middlename, lastname, username, email, password, user_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'admin', NOW())
        ");
        
        // Bind parameters
        $stmt->bind_param(
            "ssssss", 
            $firstname, 
            $middlename, 
            $lastname, 
            $username, 
            $email, 
            $hashed_password
        );
        
        // Execute and check for success
        if ($stmt->execute()) {
            // Success message
            $success = 'Admin account created successfully!';
            
            // Clear form fields
            $firstname = $middlename = $lastname = $username = $email = '';
            
        } else {
            $errors['database'] = 'Error creating account: ' . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Administrator Account</title>
    <link rel="icon" href="../img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1F3A93 0%, #1a317d 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .admin-container {
            max-width: 900px;
            margin: 20px auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            background: rgba(255, 255, 255, 0.95);
        }
        .card-header {
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 25px;
            text-align: center;
        }
        .card-body {
            padding: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        .required::after {
            content: " *";
            color: #dc3545;
        }
        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-user-shield"></i> Create Administrator Account
                    <span class="admin-badge">ADMIN ONLY</span>
                </h3>
                <p class="mb-0 mt-2">Create a new system administrator with full privileges</p>
            </div>
            <div class="card-body">
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Success!</strong> <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors['database'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?php echo $errors['database']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> This form creates <strong>Administrator Accounts</strong> only. 
                    These accounts will have full system access and privileges.
                </div>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="firstname" class="form-label required">First Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['firstname']) ? 'is-invalid' : ''; ?>" 
                                   id="firstname" name="firstname" 
                                   value="<?php echo htmlspecialchars($firstname); ?>" required>
                            <?php if (isset($errors['firstname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['firstname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="middlename" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middlename" name="middlename" 
                                   value="<?php echo htmlspecialchars($middlename); ?>">
                            <small class="text-muted">Optional</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="lastname" class="form-label required">Last Name</label>
                            <input type="text" class="form-control <?php echo isset($errors['lastname']) ? 'is-invalid' : ''; ?>" 
                                   id="lastname" name="lastname" 
                                   value="<?php echo htmlspecialchars($lastname); ?>" required>
                            <?php if (isset($errors['lastname'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['lastname']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label required">Username</label>
                            <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                   id="username" name="username" 
                                   value="<?php echo htmlspecialchars($username); ?>" required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['username']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 4 characters</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label required">Email Address</label>
                            <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['email']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label required">Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['password']; ?>
                                </div>
                            <?php endif; ?>
                            <small class="text-muted">Minimum 8 characters with uppercase and number</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label required">Confirm Password</label>
                            <input type="password" class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                   id="confirm_password" name="confirm_password" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback">
                                    <?php echo $errors['confirm_password']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="reset" class="btn btn-secondary me-md-2">Clear Form</button>
                        <button type="submit" class="btn btn-primary">Create Administrator</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <small class="text-muted">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Administrator accounts have full system access. Use with caution.
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>