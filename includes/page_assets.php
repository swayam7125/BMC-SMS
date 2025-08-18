<?php
/**
 * Returns an array of additional styles and scripts needed for specific pages
 */
function get_page_assets($page_name) {
    $assets = [
        // Core pages
        'book_list.php' => [
            'styles' => [
                'vendor/datatables/dataTables.bootstrap4.min.css'
            ],
            'scripts' => [
                'vendor/datatables/jquery.dataTables.min.js',
                'vendor/datatables/dataTables.bootstrap4.min.js'
            ]
        ],
        'profile.php' => [
            'styles' => [
                'assets/css/profile.css'
            ]
        ],
        
        // Student pages
        'student_list.php' => [
            'styles' => [
                'assets/css/student_view.css'
            ],
            'scripts' => [
                'vendor/datatables/jquery.dataTables.min.js',
                'vendor/datatables/dataTables.bootstrap4.min.js',
                'assets/js/custom_student_scripts.js'
            ]
        ],
        'student_view.php' => [
            'styles' => [
                'assets/css/student_view.css'
            ],
            'scripts' => [
                'assets/js/custom_student_scripts.js'
            ]
        ],
        
        // Teacher pages
        'teacher_view.php' => [
            'styles' => [
                'assets/css/teacher_view.css'
            ],
            'scripts' => [
                'assets/js/custom_teacher_scripts.js'
            ]
        ],
        'marks_entry.php' => [
            'scripts' => [
                'assets/js/custom_marks_scripts.js'
            ]
        ],
        
        // Principal pages
        'principal_view.php' => [
            'styles' => [
                'assets/css/principal_view.css'
            ],
            'scripts' => [
                'assets/js/custom_principal.js',
                'vendor/chart.js/Chart.min.js',
                'assets/js/dynamic_chart.js'
            ]
        ],
        
        // Librarian pages
        'librarian_view.php' => [
            'styles' => [
                'assets/css/librarian_view.css'
            ]
        ],
        
        // School pages
        'school_view.php' => [
            'styles' => [
                'assets/css/school_view.css'
            ],
            'scripts' => [
                'assets/js/custom_school_scripts.js'
            ]
        ],
        
        // Assignment pages
        'view_assignments.php' => [
            'styles' => [
                'assets/css/view_assignments.css'
            ],
            'scripts' => [
                'assets/js/student-assignment.js'
            ]
        ],
        'assignment.php' => [
            'scripts' => [
                'assets/js/assignmnet.js'
            ]
        ],
        
        // Mark pages
        'view_my_marks.php' => [
            'styles' => [
                'assets/css/view_my_marks.css'
            ],
            'scripts' => [
                'assets/js/view_my_marks.js'
            ]
        ],
        
        // Notification pages
        'notification.php' => [
            'styles' => [
                'assets/css/notification_window.css'
            ],
            'scripts' => [
                'assets/js/notification.js',
                'assets/js/notification_window.js'
            ]
        ],
        'notification_history.php' => [
            'styles' => [
                'assets/css/notification_window.css'
            ],
            'scripts' => [
                'assets/js/notification.js'
            ]
        ],
        
        // Message pages
        'message.php' => [
            'styles' => [
                'assets/css/message.css'
            ],
            'scripts' => [
                'assets/js/message.js'
            ]
        ],
        'send_notes.php' => [
            'scripts' => [
                'assets/vendor/tinymce/tinymce.min.js',
                'assets/js/custom_notes.js'
            ]
        ],
        
        // Calendar pages
        'calendar.php' => [
            'styles' => [
                'assets/css/calender.css'
            ],
            'scripts' => [
                'assets/js/calender.js'
            ]
        ]
    ];

    // Get assets for the requested page, return empty arrays if page not found
    return $assets[basename($page_name)] ?? ['styles' => [], 'scripts' => []];
}
