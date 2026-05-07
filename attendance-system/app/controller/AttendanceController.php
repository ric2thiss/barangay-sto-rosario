<?php

class AttendanceController {
    protected $db;
    protected $attendanceRepository;
    protected $residentRepository;
    protected $windowRepository;
    protected $fingerprintsRepository;
    protected $activityRepository;
    protected $profilingDbName;

    public function __construct()
    {
        $this->db = (new Database())->connect();
        $this->attendanceRepository = new AttendanceRepository($this->db);
        $this->residentRepository = new ResidentRepository($this->db);
        $this->windowRepository = new AttendanceWindowRepository($this->db);
        $this->fingerprintsRepository = new FingerprintsRepository($this->db);
        $this->activityRepository = new ActivityRepository($this->db);
        $this->profilingDbName = defined("PROFILING_DB_NAME") ? PROFILING_DB_NAME : "profiling-system";
    }
    // public function index()
    // {
    //     $attendances = $this->attendance::all();
    //     // $fingerprint = $this->fingerprints::all();
    //     $lastAttendance = Attendance::query()->orderBy('id', 'DESC')->first();
    //     $employee = Employee::query()->where('employee_id', $lastAttendance->employee_id)->first();
    //     $resident = Resident::query()->where("resident_id", $employee->resident_id)->get();

    //     // $employee = null;

    //     // if ($lastAttendance) {
    //     //     $employee = Employee::query()
    //     //         ->where("employee_id", $lastAttendance->employee_id)
    //     //         ->first();
    //     // }
    
    //     return [
    //         "attendances"=>$attendances,
    //         "attendancesTodayCount" => $this->getAttendanceCountToday(),
    //         // "fingerprints"=>$fingerprint,
    //         "lastAttendee" => $lastAttendance,
    //         "lastAttendeeEmployee" => $employee,
    //         "windows"=> $this->windows()
    //     ];
    // }

    public function index()
    {
        $attendances = $this->attendanceRepository->findAll();
        $lastAttendance = $this->attendanceRepository->getLast();

        $employee = null; // kept for backwards compatibility in response payload
        $resident = null; // used by frontend as "person info" (we map from profiling-system.barangay_official)
        $correspondingAttendance = null;

        if ($lastAttendance) {
            $employeeId = is_object($lastAttendance) ? $lastAttendance->employee_id : $lastAttendance['employee_id'];

            // Employees are sourced from profiling-system.barangay_official (read-only).
            // Map official -> resident-like payload for existing frontend code that expects first_name/last_name.
            try {
                $stmt = $this->db->prepare("
                    SELECT
                        bo.id AS employee_id,
                        bo.first_name,
                        bo.middle_name,
                        bo.surname AS last_name,
                        bo.chairmanship AS department_name,
                        bo.position AS position_name,
                        bo.status AS activity_name,
                        bo.image_path
                    FROM `{$this->profilingDbName}`.`barangay_official` AS bo
                    WHERE bo.id = ?
                    LIMIT 1
                ");
                $stmt->execute([(string) $employeeId]);
                $official = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $official = null;
            }

            $employee = [
                "employee_id" => (string) $employeeId,
                "position_name" => $official['position_name'] ?? null,
                "department_name" => $official['department_name'] ?? null,
            ];

            // Build profile photo URL for officials (profiling-system/officials/uploads/officials/)
            $photoUrl = null;
            if ($official && !empty($official['image_path'])) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $origin = $scheme . '://' . $host;
                $filename = basename(trim($official['image_path']));
                $photoUrl = $origin . '/profiling-system/officials/uploads/officials/' . rawurlencode($filename);
            }

            $resident = $official ? [
                "resident_id" => null,
                "first_name" => $official['first_name'] ?? null,
                "middle_name" => $official['middle_name'] ?? null,
                "last_name" => $official['last_name'] ?? null,
                "suffix" => null,
                "photo_path" => $official['image_path'] ?? null,
                "photo_url" => $photoUrl,
            ] : null;
            
            // Get the corresponding attendance (in/out pair) for the same date
            $window = is_object($lastAttendance) ? $lastAttendance->window : $lastAttendance['window'];
            $timestamp = is_object($lastAttendance) ? ($lastAttendance->timestamp ?? $lastAttendance->created_at) : ($lastAttendance['timestamp'] ?? $lastAttendance['created_at']);
            
            // Extract date from timestamp - handle both timestamp and datetime formats
            if ($timestamp) {
                // Try to extract date from timestamp
                $dateObj = new DateTime($timestamp);
                $date = $dateObj->format('Y-m-d');
                
                // Get corresponding attendance
                $correspondingAttendance = $this->attendanceRepository->getCorrespondingAttendance($employeeId, $window, $date);
            }
        }

        return [
            "attendances" => $attendances,
            "attendancesTodayCount" => $this->getAttendanceCountToday(),
            "lastAttendee" => $lastAttendance,
            "lastAttendeeEmployee" => $employee,
            "lastAttendeeResident" => $resident,
            "correspondingAttendance" => $correspondingAttendance,
            // "windows" => $this->windows(),
        ];
    }

    public function getAttendanceBetween($from, $to)
    {
        return $this->attendanceRepository->getBetween($from, $to);
    }



    public function store($data)
    {
        
        // Validation
        if (!isset($data["employee_id"]) || empty($data["employee_id"])) {
            http_response_code(422);
            echo json_encode([
                "success" => false,
                "error"   => "Employee ID is required"
            ]);
            return;
        }

        if (!is_string($data["employee_id"])) {
            http_response_code(422);
            echo json_encode([
                "success" => false,
                "error"   => "Employee ID must be string"
            ]);
            return;
        }

        // Normalize employee_id (C# client may send form-encoded strings with whitespace)
        $data["employee_id"] = trim($data["employee_id"]);
        if ($data["employee_id"] === '') {
            http_response_code(422);
            echo json_encode([
                "success" => false,
                "error"   => "Employee ID is required"
            ]);
            return;
        }

        if (!isset($data["window"]) || empty($data["window"])) {
            http_response_code(422);
            echo json_encode([
                "success" => false,
                "error"   => "No valid attendance window"
            ]);
            return;
        }

        // Auto-fill timestamps
        $data["created_at"] = $this->now();
        $data["updated_at"] = $this->now();

        // Normalize window label to lowercase for consistency
        $data["window"] = strtolower(trim($data["window"]));

        // Get valid windows
        $windows = $this->getWindows();
        $labels  = array_map('strtolower', array_column($windows, 'label'));

        // Validate window (case-insensitive comparison)
        if (!isset($data["window"]) || !in_array($data["window"], $labels)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "error"   => "Invalid or missing window label"
            ]);
            return;
        }

        // Validate that employee_id is enrolled in employee_fingerprints (source of truth for biometric templates)
        // This avoids reliance on attendance_system.employees (which may be removed if employees live in profiling-system).
        // Also normalizes numeric IDs (e.g., "001" -> "1") based on what's stored in employee_fingerprints.
        $fingerprint = $this->fingerprintsRepository->findByEmployeeIdentifier($data["employee_id"]);
        if (!$fingerprint) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "error"   => "Employee not found. Only employees can log attendance."
            ]);
            return;
        }
        $storedEmployeeId = is_object($fingerprint) ? ($fingerprint->employee_id ?? null) : ($fingerprint['employee_id'] ?? null);
        if ($storedEmployeeId) {
            $data["employee_id"] = (string) $storedEmployeeId;
        }

        // Check if already logged today (window is already normalized to lowercase)
        if ($this->attendanceRepository->existsTodayForWindow($data["employee_id"], $data["window"])) {
            http_response_code(409); // Conflict
            echo json_encode([
                "success" => false,
                "error"   => "Already logged for this window today"
            ]);
            return;
        }

        // Optional activity/event tag: explicit in request, else default from admin setting (biometric client)
        $resolvedActivityId = null;
        if (array_key_exists("activity_id", $data) && $data["activity_id"] !== null && $data["activity_id"] !== "") {
            $aid = (int) $data["activity_id"];
            if ($aid > 0) {
                if (!$this->activityRepository->existsById($aid)) {
                    http_response_code(422);
                    echo json_encode([
                        "success" => false,
                        "error"   => "Invalid activity_id",
                    ]);
                    return;
                }
                $resolvedActivityId = $aid;
            }
        } else {
            $activeRaw = Settings::getValue("active_attendance_activity_id", "");
            if ($activeRaw !== "" && ctype_digit((string) $activeRaw)) {
                $aid = (int) $activeRaw;
                if ($aid > 0 && $this->activityRepository->existsById($aid)) {
                    $resolvedActivityId = $aid;
                }
            }
        }
        $data["activity_id"] = $resolvedActivityId;

        // 5. Save attendance (window is already normalized to lowercase)
        try {
            $saved = $this->attendanceRepository->create($data);

            http_response_code(201);
            echo json_encode([
                "success" => true,
                "data"    => $saved,
                "message" => "Attendance recorded successfully"
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "error"   => "Failed to save attendance",
                "details" => $e->getMessage()
            ]);
        }
    }



    public function windows()
    {
        return  ["windows"=> $this->getWindows()];
    }

     private function getWindows()
    {
        // Fetch windows from database
        try {
            return $this->windowRepository->getWindowsArray();
        } catch (Exception $e) {
            // Fallback to default windows if database fetch fails
            return [
                [
                    'label' => 'morning_in',
                    'start' => '06:00:00',
                    'end' => '11:59:00',
                ],
                [
                    'label' => 'morning_out',
                    'start' => '12:00:00',
                    'end' => '12:59:00',
                ],
                [
                    'label' => 'afternoon_in',
                    'start' => '13:00:00',
                    'end' => '15:59:00',
                ],
                [
                    'label' => 'afternoon_out',
                    'start' => '16:00:00',
                    'end' => '18:30:00',
                ],
            ];
        }
    }

    function now($format = "Y-m-d H:i:s", $timezone = null)
    {
        if ($timezone === null || $timezone === '') {
            $timezone = @date_default_timezone_get() ?: "Asia/Manila";
        }
        try {
            $tz = new DateTimeZone($timezone);
        } catch (Exception $e) {
            $tz = new DateTimeZone("Asia/Manila");
        }
        $dt = new DateTime("now", $tz);
        return $dt->format($format);
    }

    function getAttendanceCountToday()
    {
        return $this->attendanceRepository->getCountToday();
    }

    /**
     * Get paginated attendance records with search and date filtering
     *
     * @param int $page Current page number
     * @param int $perPage Records per page
     * @param string $searchQuery Search term (optional)
     * @param string|null $fromDate Filter from date (optional)
     * @param string|null $toDate Filter to date (optional)
     * @return array
     */
    public function getPaginatedAttendances($page = 1, $perPage = 10, $searchQuery = '', $fromDate = null, $toDate = null, $activityFilter = null)
    {
        return $this->attendanceRepository->getPaginated($page, $perPage, $searchQuery, $fromDate, $toDate, $activityFilter);
    }




}