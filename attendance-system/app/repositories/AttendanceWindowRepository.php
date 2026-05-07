<?php

require_once __DIR__ . '/BaseRepository.php';

class AttendanceWindowRepository extends BaseRepository {
    protected function getModelClass(): string {
        return AttendanceWindow::class;
    }

    /**
     * Get all windows ordered by start_time
     * @return array
     */
    public function findAll(): array {
        return $this->modelClass::query()
            ->orderBy('start_time', 'ASC')
            ->get();
    }

    /**
     * Find window by label (case-insensitive)
     * @param string $label
     * @return object|array|null
     */
    public function findByLabel(string $label) {
        // Use case-insensitive comparison for label lookup
        return $this->modelClass::query()
            ->whereRaw('LOWER(TRIM(label)) = ?', [strtolower(trim($label))])
            ->first();
    }

    /**
     * Get windows as array format expected by AttendanceController
     * Normalizes labels to lowercase for consistency
     * @return array
     */
    public function getWindowsArray(): array {
        $windows = $this->findAll();
        $result = [];
        
        foreach ($windows as $window) {
            $label = is_object($window) ? $window->label : $window['label'];
            if (is_object($window)) {
                $lg = property_exists($window, 'late_grace_minutes') ? $window->late_grace_minutes : null;
            } else {
                $lg = $window['late_grace_minutes'] ?? null;
            }
            if ($lg === '' || $lg === null) {
                $lg = null;
            } else {
                $lg = (int) $lg;
            }
            $result[] = [
                'label' => strtolower(trim($label)), // Normalize to lowercase
                'start' => is_object($window) ? $window->start_time : $window['start_time'],
                'end' => is_object($window) ? $window->end_time : $window['end_time'],
                'late_grace_minutes' => $lg,
            ];
        }
        
        return $result;
    }
}
