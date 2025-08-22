<?php
/**
 * Ticket Management Utilities for JLM
 * Comprehensive ticket tracking and management system
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/email_templates.php';

/**
 * Generate a unique ticket number (deprecated - use generate_ticket_number() from functions.php)
 * @param string $prefix Ticket prefix (default: JLM)
 * @return string Unique ticket number
 */
if (!function_exists('generateTicketNumber')) {
    function generateTicketNumber($prefix = 'JLM') {
        // Use the function from functions.php instead
        if (function_exists('generate_ticket_number')) {
            return generate_ticket_number($prefix);
        }
        
        // Fallback implementation
        $timestamp = date('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        return $prefix . '-' . $timestamp . '-' . $random;
    }
}

/**
 * Create a new ticket in the tracking system
 * @param string $ticketNumber The ticket number
 * @param string $formType Type of form (contact, booking, testimonial)
 * @param int $formId The ID of the form submission
 * @param string $priority Priority level (low, medium, high, urgent)
 * @return bool Success status
 */
function createTicket($ticketNumber, $formType, $formId, $priority = 'medium') {
    try {
        $pdo = getConnection();
        
        // Insert into ticket_tracking
        $stmt = $pdo->prepare("
            INSERT INTO ticket_tracking (ticket_number, form_type, form_id, priority) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$ticketNumber, $formType, $formId, $priority]);
        
        // Log the activity
        logTicketActivity($ticketNumber, 'created', "Ticket created for $formType submission", null, [
            'form_type' => $formType,
            'form_id' => $formId,
            'priority' => $priority
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error creating ticket: " . $e->getMessage());
        return false;
    }
}

/**
 * Log ticket activity
 * @param string $ticketNumber The ticket number
 * @param string $activityType Type of activity
 * @param string $description Activity description
 * @param int|null $performedBy User ID who performed the action
 * @param array|null $metadata Additional metadata
 * @return bool Success status
 */
function logTicketActivity($ticketNumber, $activityType, $description, $performedBy = null, $metadata = null) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO ticket_activities (ticket_number, activity_type, description, performed_by, metadata) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $metadataJson = $metadata ? json_encode($metadata) : null;
        $stmt->execute([$ticketNumber, $activityType, $description, $performedBy, $metadataJson]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error logging ticket activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get ticket information
 * @param string $ticketNumber The ticket number
 * @return array|false Ticket information or false if not found
 */
function getTicketInfo($ticketNumber) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            SELECT tt.*, 
                   CASE 
                       WHEN tt.form_type = 'contact' THEN cs.name
                       WHEN tt.form_type = 'booking' THEN bs.name
                       WHEN tt.form_type = 'testimonial' THEN ts.name
                   END as submitter_name,
                   CASE 
                       WHEN tt.form_type = 'contact' THEN cs.email
                       WHEN tt.form_type = 'booking' THEN bs.email
                       WHEN tt.form_type = 'testimonial' THEN ts.email
                   END as submitter_email,
                   u.name as assigned_to_name
            FROM ticket_tracking tt
            LEFT JOIN users u ON tt.assigned_to = u.id
            LEFT JOIN contact_submissions cs ON tt.form_type = 'contact' AND tt.form_id = cs.id
            LEFT JOIN booking_submissions bs ON tt.form_type = 'booking' AND tt.form_id = bs.id
            LEFT JOIN testimonials ts ON tt.form_type = 'testimonial' AND tt.form_id = ts.id
            WHERE tt.ticket_number = ?
        ");
        
        $stmt->execute([$ticketNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting ticket info: " . $e->getMessage());
        return false;
    }
}

/**
 * Get ticket activities
 * @param string $ticketNumber The ticket number
 * @return array Array of activities
 */
function getTicketActivities($ticketNumber) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            SELECT ta.*, u.name as performed_by_name
            FROM ticket_activities ta
            LEFT JOIN users u ON ta.performed_by = u.id
            WHERE ta.ticket_number = ?
            ORDER BY ta.performed_at DESC
        ");
        
        $stmt->execute([$ticketNumber]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting ticket activities: " . $e->getMessage());
        return [];
    }
}

/**
 * Update ticket status
 * @param string $ticketNumber The ticket number
 * @param string $status New status
 * @param int|null $performedBy User ID who performed the action
 * @return bool Success status
 */
function updateTicketStatus($ticketNumber, $status, $performedBy = null) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE ticket_tracking 
            SET status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE ticket_number = ?
        ");
        $stmt->execute([$status, $ticketNumber]);
        
        // Log the activity
        logTicketActivity($ticketNumber, 'status_changed', "Status changed to $status", $performedBy, [
            'new_status' => $status
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error updating ticket status: " . $e->getMessage());
        return false;
    }
}

/**
 * Assign ticket to user
 * @param string $ticketNumber The ticket number
 * @param int $userId User ID to assign to
 * @param int|null $performedBy User ID who performed the assignment
 * @return bool Success status
 */
function assignTicket($ticketNumber, $userId, $performedBy = null) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE ticket_tracking 
            SET assigned_to = ?, updated_at = CURRENT_TIMESTAMP
            WHERE ticket_number = ?
        ");
        $stmt->execute([$userId, $ticketNumber]);
        
        // Get user name for logging
        $userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $userName = $userStmt->fetchColumn();
        
        // Log the activity
        logTicketActivity($ticketNumber, 'assigned', "Ticket assigned to $userName", $performedBy, [
            'assigned_to' => $userId,
            'assigned_to_name' => $userName
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error assigning ticket: " . $e->getMessage());
        return false;
    }
}

/**
 * Add note to ticket
 * @param string $ticketNumber The ticket number
 * @param string $note The note to add
 * @param int|null $performedBy User ID who added the note
 * @return bool Success status
 */
function addTicketNote($ticketNumber, $note, $performedBy = null) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            UPDATE ticket_tracking 
            SET notes = CONCAT(IFNULL(notes, ''), '\n', ?), updated_at = CURRENT_TIMESTAMP
            WHERE ticket_number = ?
        ");
        $stmt->execute([date('Y-m-d H:i:s') . " - " . $note, $ticketNumber]);
        
        // Log the activity
        logTicketActivity($ticketNumber, 'note_added', "Note added: $note", $performedBy, [
            'note' => $note
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error adding ticket note: " . $e->getMessage());
        return false;
    }
}

/**
 * Search tickets
 * @param array $filters Search filters
 * @return array Array of tickets
 */
function searchTickets($filters = []) {
    try {
        $pdo = getConnection();
        
        $where = [];
        $params = [];
        
        if (!empty($filters['ticket_number'])) {
            $where[] = "tt.ticket_number LIKE ?";
            $params[] = '%' . $filters['ticket_number'] . '%';
        }
        
        if (!empty($filters['form_type'])) {
            $where[] = "tt.form_type = ?";
            $params[] = $filters['form_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "tt.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $where[] = "tt.priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['submitter_name'])) {
            $where[] = "(cs.name LIKE ? OR bs.name LIKE ? OR ts.name LIKE ?)";
            $params[] = '%' . $filters['submitter_name'] . '%';
            $params[] = '%' . $filters['submitter_name'] . '%';
            $params[] = '%' . $filters['submitter_name'] . '%';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "
            SELECT tt.*, 
                   CASE 
                       WHEN tt.form_type = 'contact' THEN cs.name
                       WHEN tt.form_type = 'booking' THEN bs.name
                       WHEN tt.form_type = 'testimonial' THEN ts.name
                   END as submitter_name,
                   CASE 
                       WHEN tt.form_type = 'contact' THEN cs.email
                       WHEN tt.form_type = 'booking' THEN bs.email
                       WHEN tt.form_type = 'testimonial' THEN ts.email
                   END as submitter_email,
                   u.name as assigned_to_name
            FROM ticket_tracking tt
            LEFT JOIN users u ON tt.assigned_to = u.id
            LEFT JOIN contact_submissions cs ON tt.form_type = 'contact' AND tt.form_id = cs.id
            LEFT JOIN booking_submissions bs ON tt.form_type = 'booking' AND tt.form_id = bs.id
            LEFT JOIN testimonials ts ON tt.form_type = 'testimonial' AND tt.form_id = ts.id
            $whereClause
            ORDER BY tt.created_at DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error searching tickets: " . $e->getMessage());
        return [];
    }
}

/**
 * Get ticket statistics
 * @return array Statistics
 */
function getTicketStats() {
    try {
        $pdo = getConnection();
        
        $stats = [];
        
        // Total tickets by status
        $stmt = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM ticket_tracking 
            GROUP BY status
        ");
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Total tickets by priority
        $stmt = $pdo->query("
            SELECT priority, COUNT(*) as count 
            FROM ticket_tracking 
            GROUP BY priority
        ");
        $stats['by_priority'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Total tickets by form type
        $stmt = $pdo->query("
            SELECT form_type, COUNT(*) as count 
            FROM ticket_tracking 
            GROUP BY form_type
        ");
        $stats['by_form_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent tickets (last 7 days)
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM ticket_tracking 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stats['recent_tickets'] = $stmt->fetchColumn();
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error getting ticket stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get database connection
 * @return PDO Database connection
 */
function getConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }
    return $pdo;
} 