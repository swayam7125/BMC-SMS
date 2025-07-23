<?php
include_once "../../includes/connect.php";
include_once "../../encryption.php";

$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: school_list.php?error=Invalid ID provided");
    exit;
}
$school_id = intval($_GET['id']);
$errors = [];

// Fetch current school data to populate the form
// Removed 'school_std' from SELECT query
$query = "SELECT id, school_logo, school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address FROM school WHERE id = ?";
$stmt_fetch = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt_fetch, "i", $school_id);
mysqli_stmt_execute($stmt_fetch);
$result = mysqli_stmt_get_result($stmt_fetch);
if (mysqli_num_rows($result) === 0) {
    header("Location: school_list.php?error=School not found");
    exit;
}
$school = mysqli_fetch_assoc($result);
$selected_boards = explode(',', $school['education_board'] ?? '');
$selected_mediums = explode(',', $school['school_medium'] ?? '');
$selected_categories = explode(',', $school['school_category'] ?? '');
$selected_stds = explode(',', $school['school_std'] ?? '');
$original_logo_path = $school['school_logo']; // This path is stored directly in DB
mysqli_stmt_close($stmt_fetch);


// Handle form submission for the update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_name = trim($_POST['school_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $school_opening = $_POST['school_opening'];
    $school_type = $_POST['school_type'];
    $education_board = implode(',', (isset($_POST['education_board']) ? $_POST['education_board'] : []));
    $school_medium = implode(',', (isset($_POST['school_medium']) ? $_POST['school_medium'] : []));
    $school_category = implode(',', (isset($_POST['school_category']) ? $_POST['school_category'] : []));
    $school_std = implode(',', (isset($_POST['school_std']) ? $_POST['school_std'] : []));
    $logo_path_for_db = $original_logo_path; // Default to original path

    // Handle new logo upload
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['school_logo'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_exts)) {
            $target_dir = "../../pages/school/uploads/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $new_filename = uniqid('logo_', true) . '.' . $file_ext;
            $destination = $target_dir . $new_filename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $logo_path_for_db = $destination; // Store the new path in DB

                // Delete old logo if it exists and is not the new one
                if (!empty($original_logo_path) && file_exists($original_logo_path) && $original_logo_path !== $destination) {
                    unlink($original_logo_path);
                }
            } else {
                $errors[] = "Failed to move new logo.";
            }
        } else {
            $errors[] = "Invalid file type for logo.";
        }
    }

    if (empty($school_name)) $errors[] = "School name is required";

    if (empty($errors)) {
        try {
            // Removed school_std from UPDATE query
            $update_query = "UPDATE school SET school_logo=?, school_name=?, email=?, phone=?, address=?, school_opening=?, school_type=?, education_board=?, school_medium=?, school_category=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param(
                $stmt,
                "sssssssssssi", // Ensure 's' for school_logo_path and all other string fields
                $logo_path_for_db,
                $school_name,
                $email,
                $phone,
                $address,
                $school_opening,
                $school_type,
                $education_board,
                $school_medium,
                $school_category,
                // Removed $school_std,
                $school_id
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: ../../pages/school/school_list.php?success=School updated successfully");
                exit;
            } else {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        } catch (Exception $e) {
            if (mysqli_errno($conn) == 1062) {
                $errors[] = "A school with this email or phone number already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
            // Re-populate form fields if there's an error
            $school['school_name'] = $school_name;
            $school['email'] = $email;
            $school['phone'] = $phone;
            $school['address'] = $address;
            $school['school_opening'] = $school_opening;
            $school['school_type'] = $school_type;
            $school['education_board'] = $education_board;
            $school['school_medium'] = $school_medium;
            $school['school_category'] = $school_category;
            $school['school_std'] = $school_std;
            $school['school_logo'] = $logo_path_for_db; // Keep new logo if uploaded for sticky form
            $selected_boards = explode(',', $education_board);
            $selected_mediums = explode(',', $school_medium);
            $selected_categories = explode(',', $school_category);
            $selected_stds = explode(',', $school_std);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit School - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body id="page-top">
    <div id="wrapper">
    <?php include '../../includes/sidebar.php';?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit School</h1>
                        <a href="school_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
                    </div>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">School Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <label>School Logo</label><br>
                                        <img src="<?php echo !empty($school['school_logo']) && file_exists($school['school_logo']) ? htmlspecialchars($school['school_logo']) : '../../assets/img/default-school.png'; ?>" alt="School Logo" id="logoPreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: contain;">
                                        <div class="form-group"><label for="school_logo" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Change Logo</label><input type="file" class="d-none" id="school_logo" name="school_logo"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="school_name">School Name *</label><input type="text" class="form-control" name="school_name" value="<?php echo htmlspecialchars($school['school_name']); ?>" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email Address *</label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($school['email']); ?>" required></div>
                                            <div class="form-group col-md-6"><label for="phone">Phone Number *</label><input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($school['phone']); ?>" maxlength="10" required></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="school_opening">School Opening Date *</label><input type="date" class="form-control" name="school_opening" value="<?php echo htmlspecialchars($school['school_opening']); ?>" required></div>
                                    <div class="form-group col-md-6"><label for="school_type">School Type *</label><select class="form-control" name="school_type" required>
                                            <option value="Government" <?php if ($school['school_type'] == 'Government') echo 'selected'; ?>>Government</option>
                                            <option value="Private" <?php if ($school['school_type'] == 'Private') echo 'selected'; ?>>Private</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="education_board">Education Board *</label><select class="form-control multi-select" name="education_board[]" multiple="multiple" required><?php $boards = ['CBSE', 'State', 'IGCSE'];
                                                                                                                                                                                                                            foreach ($boards as $board): ?><option value="<?php echo $board; ?>" <?php if (in_array($board, $selected_boards)) echo 'selected'; ?>><?php echo $board; ?></option><?php endforeach; ?></select></div>
                                    <div class="form-group col-md-6"><label for="school_medium">School Medium *</label><select class="form-control multi-select" name="school_medium[]" multiple="multiple" required><?php $mediums = ['English', 'Hindi', 'Regional Language'];
                                                                                                                                                                                                                        foreach ($mediums as $medium): ?><option value="<?php echo $medium; ?>" <?php if (in_array($medium, $selected_mediums)) echo 'selected'; ?>><?php echo $medium; ?></option><?php endforeach; ?></select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-12"> <label for="school_category">School Category *</label>
                                        <select class="form-control multi-select" id="school_category" name="school_category[]" multiple="multiple" required>
                                            <?php $categories = ['Pre-Primary', 'Primary', 'Upper Primary', 'Secondary', 'Higher Secondary'];
                                            foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat; ?>" <?php if (in_array($cat, $selected_categories)) echo 'selected'; ?>><?php echo $cat; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    </div>
                                <div class="form-group"><label for="address">Address *</label><textarea class="form-control" name="address" rows="3" required><?php echo htmlspecialchars($school['address']); ?></textarea></div>
                                <hr>
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update School</button>
                                    <a href="school_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            include '../../includes/footer.php';
            ?>
            </div>
            </div>
    </div>
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="/BMC-SMS/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.multi-select').select2();

            // Removed all dynamic dropdown logic for school_std
            // const categoryToStandardMap = {...};
            // const categorySelect = $('#school_category');
            // const standardSelect = $('#school_std');
            // function updateStandardOptions() {...}
            // categorySelect.on('change', updateStandardOptions);
            // updateStandardOptions();
        });

        document.getElementById('school_logo').addEventListener('change', function(event) {
            if (event.target.files[0]) {
                document.getElementById('logoPreview').src = URL.createObjectURL(event.target.files[0]);
            }
        });
    </script>
</body>

</html> 