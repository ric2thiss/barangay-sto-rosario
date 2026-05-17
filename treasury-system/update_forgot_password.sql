-- Add email + password reset support for existing databases
-- If a statement fails because it already exists, you can ignore that error.

ALTER TABLE users ADD COLUMN email VARCHAR(150) UNIQUE DEFAULT NULL;
ALTER TABLE users ADD COLUMN email_verified_at DATETIME DEFAULT NULL;
ALTER TABLE users ADD COLUMN email_verification_token VARCHAR(64) DEFAULT NULL;
ALTER TABLE users ADD COLUMN email_verification_expires_at DATETIME DEFAULT NULL;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_password_resets_user_id ON password_resets (user_id);
CREATE INDEX idx_password_resets_expires_at ON password_resets (expires_at);
