<?php

class VisitorLogController {
    protected $visitorLogRepository;
    protected $residentRepository;

    public function __construct() {
        $db = (new Database())->connect();
        $this->visitorLogRepository = new VisitorLogRepository();
        $this->residentRepository = new ResidentRepository($db);
    }

    /**
     * Create a visitor log entry
     * 
     * @param array $data Visitor log data
     * @return array
     */
    public function store(array $data): array {
        $residentId = isset($data['resident_id']) && !empty($data['resident_id']) ? (int) $data['resident_id'] : null;
        $isResident = $residentId !== null;

        $residentRecord = null;
        if ($isResident) {
            $residentRecord = $this->residentRepository->getAllWithRelations((string) $residentId);
            if (empty($residentRecord)) {
                return [
                    'success' => false,
                    'error' => "Resident not found in profiling system."
                ];
            }
        }

        if (!$isResident) {
            $requiredFields = ['first_name', 'last_name', 'address', 'purpose'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'error' => "Field '{$field}' is required"
                    ];
                }
            }
        } elseif (empty($data['purpose'])) {
            return [
                'success' => false,
                'error' => "Field 'purpose' is required"
            ];
        }

        if ($isResident && $residentRecord) {
            $purok = $residentRecord['purok'] ?? '';
            $addressParts = array_filter([
                $purok ? (stripos($purok, 'purok') === false ? 'Purok ' . $purok : $purok) : '',
                $residentRecord['barangay'] ? 'Brgy. ' . $residentRecord['barangay'] : '',
                $residentRecord['municipality_city'] ?? '',
                $residentRecord['province'] ?? '',
            ]);
            $resolvedAddress = !empty($addressParts) ? implode(', ', $addressParts) : ($data['address'] ?? 'N/A');

            $logData = [
                'resident_id' => $residentId,
                'first_name' => $residentRecord['first_name'] ?? ($data['first_name'] ?? ''),
                'middle_name' => $residentRecord['middle_name'] ?? ($data['middle_name'] ?? null),
                'last_name' => $residentRecord['last_name'] ?? ($data['last_name'] ?? ''),
                'birthdate' => $residentRecord['birthdate'] ?? null,
                'address' => !empty($data['address']) ? trim($data['address']) : $resolvedAddress,
                'purpose' => trim($data['purpose']),
                'is_resident' => 1,
                'had_booking' => isset($data['had_booking']) ? ($data['had_booking'] ? 1 : 0) : 0,
                'booking_id' => isset($data['booking_id']) ? trim((string) $data['booking_id']) : null,
            ];
        } else {
            if (!isset($data['birthdate']) || empty($data['birthdate'])) {
                return [
                    'success' => false,
                    'error' => 'Birthdate is required for non-resident visitors'
                ];
            }

            $logData = [
                'resident_id' => null,
                'first_name' => trim($data['first_name'] ?? ''),
                'middle_name' => isset($data['middle_name']) ? trim($data['middle_name']) : null,
                'last_name' => trim($data['last_name'] ?? ''),
                'birthdate' => $data['birthdate'],
                'address' => trim($data['address'] ?? ''),
                'purpose' => trim($data['purpose']),
                'is_resident' => 0,
                'had_booking' => 0,
                'booking_id' => null,
            ];
        }

        try {
            $log = $this->visitorLogRepository->createLog($logData);

            return [
                'success' => true,
                'message' => 'Visitor log created successfully',
                'data' => $log
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create visitor log',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get visitor logs with filters
     * 
     * @param array $filters Optional filters
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array
     */
    public function index(array $filters = [], int $limit = null, int $offset = null): array {
        try {
            $logs = $this->visitorLogRepository->getLogs($filters, $limit, $offset);
            $count = $this->visitorLogRepository->getCount($filters);

            return [
                'success' => true,
                'data' => $logs,
                'count' => $count
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch visitor logs',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Visitor Reports: paginated rows with demographics join + summary stats (READ-ONLY).
     *
     * @param array $filters date_from, date_to (Y-m-d H:i:s), optional search, purpose, gender, purok
     */
    public function indexForReports(array $filters, int $limit, int $offset, string $sortDir = 'DESC'): array {
        try {
            $logs = $this->visitorLogRepository->getLogsForReports($filters, $limit, $offset, $sortDir);
            $count = $this->visitorLogRepository->getCountForReports($filters);
            $uniqueVisitors = $this->visitorLogRepository->getUniqueVisitorsCountForReports($filters);

            $dateFrom = $filters['date_from'] ?? '';
            $dateTo = $filters['date_to'] ?? '';
            $filterOptions = $this->visitorLogRepository->getReportFilterOptions($dateFrom, $dateTo);

            return [
                'success' => true,
                'data' => $logs,
                'count' => $count,
                'summary' => [
                    'total_records' => $count,
                    'unique_visitors' => $uniqueVisitors,
                    'date_from' => substr((string) $dateFrom, 0, 10),
                    'date_to' => substr((string) $dateTo, 0, 10),
                ],
                'filter_options' => $filterOptions,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch visitor reports',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get statistics for visitor logs
     * 
     * @param string $dateFrom Start date (Y-m-d H:i:s)
     * @param string $dateTo End date (Y-m-d H:i:s)
     * @return array
     */
    public function getStatistics(string $dateFrom, string $dateTo): array {
        try {
            $filters = ['date_from' => $dateFrom, 'date_to' => $dateTo];
            
            $totalVisitors = $this->visitorLogRepository->getCount($filters);
            $residentVisitors = $this->visitorLogRepository->getCount(array_merge($filters, ['is_resident' => 1]));
            $nonResidentVisitors = $this->visitorLogRepository->getCount(array_merge($filters, ['is_resident' => 0]));
            $withBooking = $this->visitorLogRepository->getCount(array_merge($filters, ['had_booking' => 1]));
            $withoutBooking = $this->visitorLogRepository->getCount(array_merge($filters, ['had_booking' => 0]));

            return [
                'success' => true,
                'total_visitors' => $totalVisitors,
                'resident_visitors' => $residentVisitors,
                'non_resident_visitors' => $nonResidentVisitors,
                'with_booking' => $withBooking,
                'without_booking' => $withoutBooking
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch statistics',
                'message' => $e->getMessage()
            ];
        }
    }
}
