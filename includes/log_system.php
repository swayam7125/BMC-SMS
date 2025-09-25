<?php
/**
 * Logs an interaction to a weekly file, named after the Monday of the current week.
 *
 * @param string $role The role of the user (e.g., 'principal', 'teacher').
 * @param string $userId The ID of the user performing the action.
 * @param string $action A description of the action being logged.
 * @param string $userName The name of the user performing the action (optional).
 * @return void
 */
function log_interaction($role, $userId, $action, $userName = 'Unknown User') {
    // Defines the secure log directory path: two levels up from 'includes' (i.e., outside BMC-SMS).
    $log_dir = dirname(__DIR__, 2) . '/logs'; 

    if (!is_dir($log_dir)) {
        // Creates the directory automatically on first use.
        if (!mkdir($log_dir, 0755, true)) {
            error_log("Failed to create secure log directory: " . $log_dir);
            return;
        }
    }

    try {
        // 1. Determine the start date of the current week (Monday) based on ISO 8601 (N=1 for Monday)
        $today = new DateTime();
        $days_to_subtract = $today->format('N') - 1;
        
        $monday = clone $today;
        $monday->modify("-{$days_to_subtract} days");
        
        $log_filename = $monday->format('Y-m-d') . '.log';
        $log_path = $log_dir . '/' . $log_filename;

        // 2. Prepare the log entry
        $timestamp = $today->format('Y-m-d H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'N/A';
        $uri = $_SERVER['REQUEST_URI'] ?? 'N/A';
        
        // Log format now includes user name
        $log_entry = sprintf("[%s] [%s] [User: %s | ID: %s | Role: %s] [%s %s] %s\n",
            $timestamp,
            $ip_address,
            $userName,
            $userId,
            $role,
            $method,
            $uri,
            $action
        );

        // 3. Append the entry to the weekly file using mode 3 for secure appending
        error_log($log_entry, 3, $log_path);

    } catch (Exception $e) {
        // Fallback for logging errors
        error_log("BMC-SMS LOGGING SYSTEM ERROR: " . $e->getMessage());
    }
}