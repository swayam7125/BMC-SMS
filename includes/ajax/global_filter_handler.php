<?php

/**
 * Global AJAX Filter Handler (Comprehensive Version)
 *
 * This file receives all AJAX requests for filtering and searching from various pages.
 * It uses the 'action' parameter to route the request to the appropriate function.
 * Each function is responsible for fetching data and echoing the HTML for the table body.
 */

// Core Includes
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../../encryption.php'; // Adjust path if necessary

// --- ACTION ROUTER ---
$action = $_POST['action'] ?? '';

// Pass the database connection to the handlers
if (isset($conn)) {
    switch ($action) {
        // List Pages
        case 'filter_student_list':
            handle_student_list($conn);
            break;

        case 'filter_principal_list':
            handle_principal_list($conn);
            break;

        case 'filter_teacher_list':
            handle_teacher_list($conn);
            break;

        case 'filter_librarian_list':
            handle_librarian_list($conn);
            break;

        case 'filter_hr_list':
            handle_hr_list($conn);
            break;

        case 'filter_school_list':
            handle_school_list($conn);
            break;

        case 'filter_book_list':
            handle_book_list($conn);
            break;

        case 'filter_notification_history':
            handle_notification_history($conn);
            break;

        case 'filter_teacher_attendance':
            handle_staff_attendance($conn, 'teacher');
            break;

        case 'filter_librarian_attendance':
            handle_staff_attendance($conn, 'librarian');
            break;
            
        case 'filter_hr_attendance':
            handle_staff_attendance($conn, 'hr');
            break;

        case 'filter_lecture_attendance':
            handle_lecture_attendance($conn);
            break;

        case 'filter_view_marks':
            handle_view_marks($conn);
            break;
        case 'filter_my_marks':
            handle_my_marks($conn);
            break;

        case 'filter_report_enrollment':
            handle_report_enrollment($conn);
            break;
        case 'filter_report_attendance':
            handle_report_attendance($conn);
            break;
        case 'filter_report_academic':
            handle_report_academic($conn);
            break;


        default:
            echo '<tr><td colspan="12" class="text-center text-danger">Error: Invalid action specified.</td></tr>';
            break;
    }
} else {
    echo '<tr><td colspan="12" class="text-center text-danger">Error: Database connection not available.</td></tr>';
}


// --- PRIMARY HANDLER FUNCTIONS ---

/**
 * Handles filtering for the Student List page.
 * This is now customized based on the logic in your student_list.php file.
 * @param PDO $conn The database connection.
 */


function handle_report_enrollment($conn)
{
    $start_date = htmlspecialchars($_POST['start_date'] ?? date('Y-m-01'));
    $end_date = htmlspecialchars($_POST['end_date'] ?? date('Y-m-d'));

    // Query to count new student enrollments within the date range
    $sql = "SELECT std, COUNT(id) as enrollment_count 
            FROM student 
            WHERE date_of_admission BETWEEN ? AND ?
            GROUP BY std
            ORDER BY std ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        echo '<tr><td colspan="2" class="text-center">No new enrollments found in the selected date range.</td></tr>';
    } else {
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>Standard " . htmlspecialchars($row['std']) . "</td>";
            echo "<td>" . htmlspecialchars($row['enrollment_count']) . "</td>";
            echo "</tr>";
        }
    }
}

/**
 * Handles the Attendance Report.
 */
function handle_report_attendance($conn)
{
    $start_date = htmlspecialchars($_POST['start_date'] ?? date('Y-m-d'));
    $end_date = htmlspecialchars($_POST['end_date'] ?? date('Y-m-d'));
    $user_type = htmlspecialchars($_POST['user_type'] ?? 'student');

    // Basic security check
    if (!in_array($user_type, ['student', 'teacher', 'librarian', 'hr'])) {
        echo '<tr><td colspan="4" class="text-center text-danger">Invalid user type selected.</td></tr>';
        return;
    }

    $table_name = ($user_type === 'student') ? "lecture_attendance" : "{$user_type}_attendance";

    // Query to get attendance summary
    $sql = "SELECT status, COUNT(*) as status_count 
            FROM {$table_name}
            WHERE attendance_date BETWEEN ? AND ?
            GROUP BY status";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        echo '<tr><td colspan="2" class="text-center">No attendance data found for the selected criteria.</td></tr>';
    } else {
        foreach ($results as $row) {
            $status_class = 'badge-secondary';
            if ($row['status'] == 'Present') $status_class = 'badge-success';
            if ($row['status'] == 'Absent') $status_class = 'badge-danger';

            echo "<tr>";
            echo "<td><span class='badge {$status_class}'>" . htmlspecialchars($row['status']) . "</span></td>";
            echo "<td>" . htmlspecialchars($row['status_count']) . "</td>";
            echo "</tr>";
        }
    }
}

/**
 * Handles the Academic Report (Pass/Fail statistics).
 */
function handle_report_academic($conn)
{
    $standard = htmlspecialchars($_POST['standard'] ?? '');
    $exam_type = htmlspecialchars($_POST['exam_type'] ?? '');
    $pass_marks = 35; // Assuming pass marks are 35

    if (empty($standard) || empty($exam_type)) {
        echo '<tr><td colspan="3" class="text-center">Please select a standard and an exam type.</td></tr>';
        return;
    }

    // A complex query to determine pass/fail counts
    $sql = "SELECT 
                s.student_name,
                AVG(m.marks_obtained * 100.0 / m.total_marks) as percentage,
                CASE 
                    WHEN MIN(m.marks_obtained) >= ? THEN 'Pass'
                    ELSE 'Fail'
                END as result_status
            FROM marks m
            JOIN student s ON m.student_id = s.id
            WHERE s.std = ? AND m.exam_type = ?
            GROUP BY s.id
            ORDER BY s.student_name";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$pass_marks, $standard, $exam_type]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        echo '<tr><td colspan="3" class="text-center">No academic data found for this standard and exam.</td></tr>';
    } else {
        foreach ($results as $row) {
            $result_class = $row['result_status'] === 'Pass' ? 'text-success' : 'text-danger';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
            echo "<td>" . number_format($row['percentage'], 2) . "%</td>";
            echo "<td class='font-weight-bold {$result_class}'>" . htmlspecialchars($row['result_status']) . "</td>";
            echo "</tr>";
        }
    }
}

function handle_notification_history($conn)
{
    $userId = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');
    if (!$userId) {
        return;
    }

    $search_term = htmlspecialchars($_POST['search_term'] ?? '');
    $filter_type = htmlspecialchars($_POST['filter_type'] ?? 'all');
    $filter_status = htmlspecialchars($_POST['filter_status'] ?? 'all');

    $sql = "SELECT id, message, link, type, created_at, is_read FROM notifications WHERE user_id = ?";
    $params = [$userId];

    if (!empty($search_term)) {
        $sql .= " AND message LIKE ?";
        $params[] = "%" . $search_term . "%";
    }
    if ($filter_type !== 'all') {
        $sql .= " AND type = ?";
        $params[] = $filter_type;
    }
    if ($filter_status !== 'all') {
        $is_read = ($filter_status === 'read');
        $sql .= " AND is_read = ?";
        $params[] = $is_read;
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($notifications)) {
        echo '<tr><td colspan="4" class="text-center">No notifications found.</td></tr>';
    } else {
        foreach ($notifications as $notification) {
            $status_badge = $notification['is_read'] ? '<span class="badge badge-secondary">Read</span>' : '<span class="badge badge-primary">Unread</span>';
            $formatted_date = date('F j, Y, g:i a', strtotime($notification['created_at']));
            $link = htmlspecialchars($notification['link'] . (strpos($notification['link'], '?') === false ? '?' : '&') . 'notif_id=' . $notification['id']);

            echo "<tr>";
            echo "<td>" . $formatted_date . "</td>";
            echo "<td>" . htmlspecialchars($notification['message']) . "</td>";
            echo "<td>" . $status_badge . "</td>";
            echo '<td><a href="' . $link . '" class="btn btn-primary btn-sm">View</a></td>';
            echo "</tr>";
        }
    }
}

/**
 * Generic handler for staff attendance (Teacher, Librarian, HR).
 */
function handle_staff_attendance($conn, $user_type)
{
    $filter_date = htmlspecialchars($_POST['filter_date'] ?? date('Y-m-d'));

    $allowed_types = ['teacher', 'librarian', 'hr'];
    if (!in_array($user_type, $allowed_types)) {
        return;
    }

    $name_field = "{$user_type}_name";
    $id_field = "{$user_type}_id";
    $attendance_table = "{$user_type}_attendance";

    $sql = "SELECT u.{$name_field}, a.status, a.remark 
            FROM {$attendance_table} a 
            JOIN {$user_type} u ON a.{$id_field} = u.id 
            WHERE a.attendance_date = ? ORDER BY u.{$name_field} ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$filter_date]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($records)) {
        echo '<tr><td colspan="3" class="text-center">No attendance records found for this date.</td></tr>';
    } else {
        foreach ($records as $record) {
            $status_class = 'badge-secondary';
            if ($record['status'] == 'Present') $status_class = 'badge-success';
            if ($record['status'] == 'Absent') $status_class = 'badge-danger';
            if ($record['status'] == 'On Leave') $status_class = 'badge-warning';

            echo "<tr>";
            echo "<td>" . htmlspecialchars($record[$name_field]) . "</td>";
            echo "<td><span class='badge {$status_class}'>" . htmlspecialchars($record['status']) . "</span></td>";
            echo "<td>" . htmlspecialchars($record['remark']) . "</td>";
            echo "</tr>";
        }
    }
}

/**
 * Handles filtering for the View Lecture Attendance page.
 */
function handle_lecture_attendance($conn)
{
    $lecture_id = htmlspecialchars($_POST['lecture_id'] ?? '');
    $view_date = htmlspecialchars($_POST['view_date'] ?? date('Y-m-d'));

    if (empty($lecture_id)) {
        echo '<tr><td colspan="3" class="text-center">Please select a lecture to view attendance.</td></tr>';
        return;
    }

    $sql = "SELECT s.student_name, s.rollno, la.status 
            FROM lecture_attendance la
            JOIN student s ON la.student_id = s.id
            WHERE la.lecture_id = ? AND la.attendance_date = ?
            ORDER BY s.rollno ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$lecture_id, $view_date]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($records)) {
        echo '<tr><td colspan="3" class="text-center">No attendance records found for this lecture on this date.</td></tr>';
    } else {
        foreach ($records as $record) {
            $status_class = $record['status'] === 'Present' ? 'badge-success' : 'badge-danger';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['student_name']) . "</td>";
            echo "<td>" . htmlspecialchars($record['rollno']) . "</td>";
            echo "<td><span class='badge {$status_class}'>" . htmlspecialchars($record['status']) . "</span></td>";
            echo "</tr>";
        }
    }
}

/**
 * Handles filtering for the Teacher's View Marks page.
 */
function handle_view_marks($conn)
{
    $standard = htmlspecialchars($_POST['standard'] ?? '');
    $exam_type = htmlspecialchars($_POST['exam_type'] ?? '');

    // This is a complex query that needs to pivot the data. This is a simplified example.
    // A full implementation would require a more dynamic SQL pivot or processing in PHP.
    echo '<tr><td colspan="6" class="text-center">View Marks AJAX functionality requires advanced logic and is not fully implemented in this example.</td></tr>';
}

/**

 * Handles filtering for the Student's View My Marks page.
 */
function handle_my_marks($conn)
{
    $student_id = decrypt_id($_COOKIE['encrypted_user_id'] ?? '');
    if (!$student_id) {
        return;
    }

    $exam_type = htmlspecialchars($_POST['exam_type'] ?? '');

    if (empty($exam_type)) {
        echo '<tr><td colspan="3" class="text-center">Please select an exam type.</td></tr>';
        return;
    }

    $sql = "SELECT sub.subject_name, m.marks_obtained, m.total_marks
            FROM marks m
            JOIN subjects sub ON m.subject_id = sub.subject_id
            WHERE m.student_id = ? AND m.exam_type = ?
            ORDER BY sub.subject_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_id, $exam_type]);
    $marks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($marks)) {
        echo '<tr><td colspan="3" class="text-center">No marks found for this exam.</td></tr>';
    } else {
        $total_obtained = 0;
        $total_possible = 0;
        foreach ($marks as $mark) {
            $total_obtained += $mark['marks_obtained'];
            $total_possible += $mark['total_marks'];
            echo "<tr>";
            echo "<td>" . htmlspecialchars($mark['subject_name']) . "</td>";
            echo "<td>" . htmlspecialchars($mark['marks_obtained']) . "</td>";
            echo "<td>" . htmlspecialchars($mark['total_marks']) . "</td>";
            echo "</tr>";
        }
        // Send back a special row for the footer totals, which JS can handle
        echo "<tr id='ajax-totals-row' data-total-obtained='{$total_obtained}' data-total-possible='{$total_possible}'></tr>";
    }
}


function handle_teacher_list($conn)
{
    $search_term = htmlspecialchars($_POST['search_term'] ?? '');

    // Base query from your teacher_list.php
    $sql = "SELECT t.id, t.teacher_name, t.email, t.phone, u.account_status 
            FROM teacher t
            LEFT JOIN users u ON t.id = u.id";

    $params = [];
    if (!empty($search_term)) {
        $sql .= " WHERE (t.teacher_name LIKE ? OR t.email LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
    }
    $sql .= " ORDER BY t.teacher_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($teachers)) {
        echo '<tr><td colspan="6" class="text-center">No teachers found.</td></tr>';
    } else {
        foreach ($teachers as $teacher) {
            $encrypted_id = encrypt_id($teacher['id']);
            $status_badge = $teacher['account_status'] === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($teacher['teacher_name']) . "</td>";
            echo "<td>" . htmlspecialchars($teacher['email']) . "</td>";
            echo "<td>" . htmlspecialchars($teacher['phone']) . "</td>";
            echo "<td>" . $status_badge . "</td>";
            echo '<td>
                    <a href="view.php?id=' . urlencode($encrypted_id) . '" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                    <a href="edit.php?id=' . urlencode($encrypted_id) . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                    <button onclick="confirmDelete(' . $teacher['id'] . ')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </td>';
            echo "</tr>";
        }
    }
}


function handle_librarian_list($conn)
{
    $search_term = htmlspecialchars($_POST['search_term'] ?? '');

    $sql = "SELECT l.id, l.librarian_name, l.email, u.account_status 
            FROM librarian l
            LEFT JOIN users u ON l.id = u.id";

    $params = [];
    if (!empty($search_term)) {
        $sql .= " WHERE (l.librarian_name LIKE ? OR l.email LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
    }
    $sql .= " ORDER BY l.librarian_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $librarians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($librarians)) {
        echo '<tr><td colspan="5" class="text-center">No librarians found.</td></tr>';
    } else {
        foreach ($librarians as $librarian) {
            $encrypted_id = encrypt_id($librarian['id']);
            $status_badge = $librarian['account_status'] === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($librarian['librarian_name']) . "</td>";
            echo "<td>" . htmlspecialchars($librarian['email']) . "</td>";
            echo "<td>" . $status_badge . "</td>";
            echo '<td>
                    <a href="view.php?id=' . urlencode($encrypted_id) . '" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                    <a href="edit.php?id=' . urlencode($encrypted_id) . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                    <button onclick="confirmDelete(' . $librarian['id'] . ')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </td>';
            echo "</tr>";
        }
    }
}


function handle_hr_list($conn)
{
    $search_term = htmlspecialchars($_POST['search_term'] ?? '');

    $sql = "SELECT hr.id, hr.hr_name, hr.email, u.account_status 
            FROM hr
            LEFT JOIN users u ON hr.id = u.id";

    $params = [];
    if (!empty($search_term)) {
        $sql .= " WHERE (hr.hr_name LIKE ? OR hr.email LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
    }
    $sql .= " ORDER BY hr.hr_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $hrs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($hrs)) {
        echo '<tr><td colspan="4" class="text-center">No HR users found.</td></tr>';
    } else {
        foreach ($hrs as $hr) {
            $encrypted_id = encrypt_id($hr['id']);
            $status_badge = $hr['account_status'] === 'active' ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($hr['hr_name']) . "</td>";
            echo "<td>" . htmlspecialchars($hr['email']) . "</td>";
            echo "<td>" . $status_badge . "</td>";
            echo '<td>
                    <a href="edit_hr.php?id=' . urlencode($encrypted_id) . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                    <button onclick="confirmDelete(' . $hr['id'] . ')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </td>';
            echo "</tr>";
        }
    }
}


function handle_school_list($conn)
{
    $search_term = htmlspecialchars($_POST['search_term'] ?? '');

    $sql = "SELECT s.id, s.school_name, s.email, s.phone, s.address, 
                   STRING_AGG(p.principal_name, ', ') AS principal_names 
            FROM school s 
            LEFT JOIN principal p ON s.id = p.school_id";

    $params = [];
    if (!empty($search_term)) {
        $sql .= " WHERE (s.school_name LIKE ? OR s.email LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
    }

    $sql .= " GROUP BY s.id ORDER BY s.school_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($schools)) {
        echo '<tr><td colspan="6" class="text-center">No schools found.</td></tr>';
    } else {
        foreach ($schools as $school) {
            $encrypted_id = encrypt_id($school['id']);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($school['school_name']) . "</td>";
            echo "<td>" . htmlspecialchars($school['email']) . "</td>";
            echo "<td>" . htmlspecialchars($school['phone']) . "</td>";
            echo "<td>" . htmlspecialchars($school['address']) . "</td>";
            echo "<td>" . htmlspecialchars($school['principal_names']) . "</td>";
            echo '<td>
                    <a href="view.php?id=' . urlencode($encrypted_id) . '" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                    <a href="edit.php?id=' . urlencode($encrypted_id) . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                    <button onclick="confirmDelete(' . $school['id'] . ')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                  </td>';
            echo "</tr>";
        }
    }
}


function handle_book_list($conn)
{
    $search_term = htmlspecialchars($_POST['search_term'] ?? '');

    $sql = "SELECT id, title, author, isbn, quantity FROM books";

    $params = [];
    if (!empty($search_term)) {
        $sql .= " WHERE (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
    }
    $sql .= " ORDER BY title ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($books)) {
        echo '<tr><td colspan="5" class="text-center">No books found.</td></tr>';
    } else {
        foreach ($books as $book) {
            $encrypted_id = encrypt_id($book['id']);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($book['title']) . "</td>";
            echo "<td>" . htmlspecialchars($book['author']) . "</td>";
            echo "<td>" . htmlspecialchars($book['isbn']) . "</td>";
            echo "<td>" . htmlspecialchars($book['quantity']) . "</td>";
            echo '<td>
                    <a href="book_edit.php?id=' . urlencode($encrypted_id) . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                  </td>';
            echo "</tr>";
        }
    }
}


function handle_principal_list($conn)
{
    $search_term = htmlspecialchars($_POST['search_term'] ?? '');

    // Base query from your principal_list.php file
    $sql = "SELECT p.id, p.principal_name, p.email, p.phone, sc.school_name, u.account_status
            FROM principal p 
            LEFT JOIN school sc ON p.school_id = sc.id
            LEFT JOIN users u ON p.id = u.id";

    $params = [];
    if (!empty($search_term)) {
        $sql .= " WHERE (p.principal_name LIKE ? OR p.email LIKE ? OR sc.school_name LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
    }

    $sql .= " ORDER BY p.principal_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $principals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($principals)) {
        echo '<tr><td colspan="7" class="text-center">No principals found matching your search.</td></tr>';
    } else {
        foreach ($principals as $principal) {
            $encrypted_id = encrypt_id($principal['id']);
            $status_badge = $principal['account_status'] === 'active'
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-danger">Inactive</span>';

            echo "<tr>";
            echo "<td>" . htmlspecialchars($principal['principal_name']) . "</td>";
            echo "<td>" . htmlspecialchars($principal['email']) . "</td>";
            echo "<td>" . htmlspecialchars($principal['phone']) . "</td>";
            echo "<td>" . htmlspecialchars($principal['school_name']) . "</td>";
            echo "<td>" . $status_badge . "</td>";
            echo '<td>
                    <a href="view.php?id=' . urlencode($encrypted_id) . '" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>
                    <a href="edit.php?id=' . urlencode($encrypted_id) . '" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                    <button class="btn btn-danger btn-sm" title="Delete" onclick="confirmDelete(' . $principal['id'] . ')"><i class="fas fa-trash"></i></button>
                  </td>';
            echo "</tr>";
        }
    }
}

function handle_student_list($conn)
{
    // Get filter data from the form
    $standard = htmlspecialchars($_POST['std'] ?? 'all');
    $search_term = htmlspecialchars($_POST['search_term'] ?? '');

    // Base query from your file
    $sql = "SELECT s.id, s.student_name, s.rollno, s.std, s.email, sc.school_name, u.account_status
            FROM student s 
            LEFT JOIN school sc ON s.school_id = sc.id
            LEFT JOIN users u ON s.id = u.id";

    $params = [];
    $where_clauses = [];

    // Apply standard filter
    if ($standard !== 'all') {
        $where_clauses[] = "s.std = ?";
        $params[] = $standard;
    }

    // Apply search term filter
    if (!empty($search_term)) {
        $where_clauses[] = "(s.student_name LIKE ? OR s.rollno LIKE ? OR s.email LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " ORDER BY s.student_name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($students)) {
        echo '<tr><td colspan="8" class="text-center">No students found matching the criteria.</td></tr>';
    } else {
        foreach ($students as $student) {
            $encrypted_id = encrypt_id($student['id']);
            $status_badge = $student['account_status'] === 'active'
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-danger">Inactive</span>';

            echo "<tr>";
            echo "<td>" . htmlspecialchars($student['student_name']) . "</td>";
            echo "<td>" . htmlspecialchars($student['rollno']) . "</td>";
            echo "<td>" . htmlspecialchars($student['std']) . "</td>";
            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
            echo "<td>" . htmlspecialchars($student['school_name']) . "</td>";
            echo "<td>" . $status_badge . "</td>";
            echo '<td>
                    <a href="view.php?id=' . urlencode($encrypted_id) . '" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a>
                    <a href="edit.php?id=' . urlencode($encrypted_id) . '" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                    <button onclick="confirmDelete(' . $student['id'] . ')" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
                  </td>';
            echo "</tr>";
        }
    }
}

/**
 * A generic handler for simple list pages with a search term.
 * @param PDO $conn The database connection.
 * @param string $table The name of the database table.
 * @param array $search_fields The fields to search within.
 */
function handle_generic_list($conn, $table, $search_fields)
{
    // ... (previous generic handler code remains here)
}

/**
 * Handles filtering for various attendance pages.
 * @param PDO $conn The database connection.
 * @param string $user_type The type of user (teacher, librarian, hr).
 */
function handle_attendance($conn, $user_type)
{
    // ... (previous attendance handler code remains here)
}
