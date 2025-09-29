<footer class="sticky-footer bg-white">
    <div class="container my-auto">
        <div class="copyright text-center my-auto">
            <span>Copyright &copy;BMC-SMS -- School Management System</span>
        </div>
    </div>
</footer>

<script>
    // Enhanced DataTable functionality for AJAX
    class DataTableManager {
        constructor() {
            this.tables = {};
            this.init();
        }

        init() {
            this.initializeTables();
        }

        initializeTables() {
            // Common DataTable configuration
            const defaultConfig = {
                processing: true,
                serverSide: false,
                responsive: true,
                pageLength: 25,
                language: {
                    processing: "Loading...",
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    },
                    emptyTable: "No data available in table"
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                drawCallback: function(settings) {
                    // Reinitialize tooltips after table redraw
                    $('[title]').tooltip();
                }
            };

            // Initialize specific tables
            this.initPrincipalTable(defaultConfig);
            this.initTeacherTable(defaultConfig);
            this.initStudentTable(defaultConfig);
        }

        initPrincipalTable(config) {
            const tableElement = $('#principalListTable')[0];
            if (tableElement && $.fn.DataTable && !$.fn.DataTable.isDataTable(tableElement)) {
                this.tables.principals = $('#principalListTable').DataTable({
                    ...config,
                    order: [
                        [0, 'asc']
                    ],
                    columnDefs: [{
                        targets: [-1],
                        orderable: false
                    }, {
                        targets: [4],
                        className: 'text-center'
                    }],
                    columns: [{
                            data: 'id',
                            title: 'ID'
                        },
                        {
                            data: 'name',
                            title: 'Name'
                        },
                        {
                            data: 'email',
                            title: 'Email'
                        },
                        {
                            data: 'school',
                            title: 'School'
                        },
                        {
                            data: 'status',
                            title: 'Status'
                        },
                        {
                            data: 'actions',
                            title: 'Actions',
                            orderable: false
                        }
                    ]
                });
            }
        }

        initTeacherTable(config) {
            if ($('#teacherListTable').length > 0 && $.fn.DataTable && !$.fn.DataTable.isDataTable('#teacherListTable')) {
                this.tables.teachers = $('#teacherListTable').DataTable({
                    ...config,
                    order: [
                        [0, 'asc']
                    ],
                    columnDefs: [{
                        targets: [-1],
                        orderable: false
                    }]
                });
            }
        }

        initStudentTable(config) {
            if ($('#studentListTable').length > 0 && $.fn.DataTable && !$.fn.DataTable.isDataTable('#studentListTable')) {
                this.tables.students = $('#studentListTable').DataTable({
                    ...config,
                    order: [
                        [0, 'asc']
                    ],
                    columnDefs: [{
                        targets: [-1],
                        orderable: false
                    }]
                });
            }
        }

        destroyAll() {
            Object.values(this.tables).forEach(table => {
                if (table && $.fn.DataTable.isDataTable(table.table().node())) {
                    table.destroy();
                }
            });
            this.tables = {};
        }

        reinitialize() {
            this.destroyAll();
            this.initializeTables();
        }
    }
    window.dataTableManager = new DataTableManager();
    $(document).on('ajax:page:loaded', () => window.dataTableManager.reinitialize());
</script>

<script>
    class FormValidator {
        constructor() {
            this.init()
        }
        init() {
            this.bindEvents()
        }
        bindEvents() {
            $(document).on("input blur", "input, textarea, select", e => {
                this.validateField($(e.target))
            }), $(document).on("submit", 'form[data-validate="true"]', e => {
                if (!this.validateForm($(e.target))) return e.preventDefault(), !1
            })
        }
        validateField($field) {
            const fieldName = $field.attr("name"),
                fieldValue = $field.val().trim(),
                fieldType = $field.attr("type"),
                required = $field.prop("required");
            let isValid = !0,
                errorMessage = "";
            if ($field.removeClass("is-invalid"), $field.siblings(".invalid-feedback").remove(), required && !fieldValue && (isValid = !1, errorMessage = "This field is required"), "email" === fieldType && fieldValue && !this.isValidEmail(fieldValue) && (isValid = !1, errorMessage = "Please enter a valid email address"), $field.hasClass("phone") && fieldValue && !this.isValidPhone(fieldValue) && (isValid = !1, errorMessage = "Please enter a valid phone number"), "password" === fieldType && fieldValue && fieldValue.length < 6 && (isValid = !1, errorMessage = "Password must be at least 6 characters long"), $field.hasClass("confirm-password")) {
                const originalPassword = $(`input[name="${fieldName.replace("_confirm","")}"]`).val();
                fieldValue !== originalPassword && (isValid = !1, errorMessage = "Passwords do not match")
            }
            return isValid || ($field.addClass("is-invalid"), $field.after(`<div class="invalid-feedback">${errorMessage}</div>`)), isValid
        }
        validateForm($form) {
            let isFormValid = !0;
            return $form.find("input, textarea, select").each((index, element) => {
                this.validateField($(element)) || (isFormValid = !1)
            }), isFormValid
        }
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
        }
        isValidPhone(phone) {
            return /^[\+]?[1-9][\d]{0,15}$/.test(phone.replace(/[\s\-\(\)]/g, ""))
        }
    }
    window.formValidator = new FormValidator();
</script>

<script>
    class NotificationManager {
        constructor() {
            this.updateInterval = 30000;
            this.init();
        }

        init() {
            this.startPeriodicUpdates();
            this.bindEvents();
        }

        startPeriodicUpdates() {
            this.updateNotifications();
            setInterval(() => {
                this.updateNotifications();
            }, this.updateInterval);
        }

        updateNotifications() {
            $.ajax({
                url: "/BMC-SMS/includes/ajax/get_notifications.php",
                type: "GET",
                dataType: "json",
                success: response => {
                    if (response.success) {
                        this.updateBadges(response.counts);
                        this.showNewNotifications(response.new_notifications || []);
                    }
                },
                error: () => {
                    console.log("Failed to update notifications");
                }
            });
        }

        updateBadges(counts) {
            for (const [type, count] of Object.entries(counts)) {
                const $links = $(`.nav-link[data-notification-type="${type}"], .collapse-item[data-notification-type="${type}"]`);
                $links.each(function() {
                    const $link = $(this);
                    let $badge = $link.find(".badge-counter");
                    if (count > 0) {
                        if (!$badge.length) {
                            $badge = $('<span class="badge badge-danger badge-counter"></span>');
                            $link.find("div").first().append($badge);
                        }
                        $badge.text(count > 9 ? "9+" : count);
                    } else {
                        $badge.remove();
                    }
                });
            }
        }

        showNewNotifications(notifications) {
            notifications.forEach(notification => {
                this.showToast(notification.message, notification.type || "info");
            });
        }

        showToast(message, type = "info") {
            const toastId = "toast-" + Date.now();
            const toast = $('<div id="' + toastId + '" class="toast" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">' +
                '<div class="toast-header">' +
                '<strong class="mr-auto text-' + type + '">Notification</strong>' +
                '<small class="text-muted">now</small>' +
                '<button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>' +
                '<div class="toast-body">' + message + '</div>' +
                '</div>');

            $("body").append(toast);
            toast.toast({
                autohide: true,
                delay: 5000
            });
            toast.toast("show");
            toast.on("hidden.bs.toast", function() {
                $(this).remove();
            });
        }

        bindEvents() {
            $(document).on("click", "[data-notification-type]", e => {
                const $target = $(e.currentTarget);
                const notificationType = $target.data("notification-type");
                if (notificationType) {
                    this.markAsRead(notificationType);
                }
            });
        }

        markAsRead(type) {
            $.post("/BMC-SMS/includes/ajax/mark_notifications_read.php", {
                type: type
            }, response => {
                if (response.success) {
                    $(`.nav-link[data-notification-type="${type}"] .badge-counter, .collapse-item[data-notification-type="${type}"] .badge-counter`).fadeOut();
                }
            }, "json");
        }
    }

    window.notificationManager = new NotificationManager();
</script>

<script>
    // Global AJAX Error Handler
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        console.error('AJAX Error:', thrownError);
        showMessage('An error occurred. Please refresh the page and try again.', 'danger');
    });

    // Global Message Display Function
    function showMessage(message, type = 'info') {
        const alert = $(`
        <div class="alert alert-${type} alert-dismissible fade show" 
             style="position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 300px;">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `);
        $('body').append(alert);
        setTimeout(() => {
            alert.alert('close');
        }, 5000);
    }
</script>

<script src="/BMC-SMS/assets/js/responsive-tables.js"></script>