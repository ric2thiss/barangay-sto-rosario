<?php

class VerificationLogController
{
    protected $verificationLogRepository;
    protected $verificationTokenRepository;

    public function __construct() {
        $db = (new Database())->connect();
        $this->verificationLogRepository = new VerificationLogRepository($db);
        $this->verificationTokenRepository = new VerificationTokenRepository($db);
    }

    public function store(array $data)
    {
        // Save log in verification_logs
        $this->verificationLogRepository->create([
            "employee_id" => $data["employee_id"],
            "status"      => $data["status"],
            "device_id"   => $data["device_id"] ?? "KIOSK-01",
            "ip_address"  => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        // Generate one-time confirmation token
        $confirmToken = bin2hex(random_bytes(16));

        // Save token to DB with current timestamp
        $this->verificationTokenRepository->create([
            "employee_id" => $data["employee_id"],
            "status"      => $data["status"],
            "token"       => $confirmToken,
            "created_at"  => date("Y-m-d H:i:s"),
        ]);

        return $confirmToken;
    }

    public function confirm(string $token)
    {
        // Fetch token from DB
        $record = $this->verificationTokenRepository->findByToken($token);

        if (!$record) {
            return "<h2>❌ Invalid or expired token.</h2>";
        }

        // Check if token is valid (not expired)
        if (!$this->verificationTokenRepository->isTokenValid($token, 60)) {
            return "<h2>❌ Token expired.</h2>";
        }

        // Token is valid, return info
        $employeeId = is_object($record) ? $record->employee_id : $record['employee_id'];
        $status = is_object($record) ? $record->status : $record['status'];
        
        $employeeId = htmlspecialchars($employeeId);
        $status = htmlspecialchars($status);

        return "<h2>✅ Verification Successful</h2>
                <p>Employee ID: {$employeeId}</p>
                <p>Status: {$status}</p>
                <p>Token: {$token}</p>";
    }
}
