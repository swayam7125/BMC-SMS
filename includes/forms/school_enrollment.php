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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The PHP logic for saving the data remains the same and will work correctly.
    $school_name = trim($_POST['school_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $school_opening = $_POST['school_opening'];
    $school_type = $_POST['school_type'];
    $education_board = implode(',', (isset($_POST['education_board']) ? $_POST['education_board'] : []));
    $school_medium = implode(',', (isset($_POST['school_medium']) ? $_POST['school_medium'] : []));
    $school_category = implode(',', (isset($_POST['school_category']) ? $_POST['school_category'] : []));
    $logo_path_for_db = null;

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
                $logo_path_for_db = $destination;
            } else {
                $errors[] = "Failed to move uploaded logo.";
            }
        } else {
            $errors[] = "Invalid file type for logo.";
        }
    }

    if (empty($school_name)) $errors[] = "School name is required";

    if (empty($errors)) {
        try {
            $insert_query = "INSERT INTO school (school_logo, school_name, email, phone, school_opening, school_type, education_board, school_medium, school_category, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param(
                $stmt,
                "ssssssssss",
                $logo_path_for_db,
                $school_name,
                $email,
                $phone,
                $school_opening,
                $school_type,
                $education_board,
                $school_medium,
                $school_category,
                $address
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: ../../pages/school/school_list.php?success=School enrolled successfully");
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
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Enroll School - School Management System</title>
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,900" rel="stylesheet">
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- Corrected Font Awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/custom.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../sidebar.php';?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include_once '../../includes/header.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Enroll New School</h1>
                        <a href="../../pages/school/school_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Back to List</a>
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
                                        <img src="../../assets/img/default-school.png" alt="School Logo" id="logoPreview" class="img-thumbnail mb-2" style="width: 150px; height: 150px; object-fit: contain;">
                                        <div class="form-group"><label for="school_logo" class="small btn btn-sm btn-info"><i class="fas fa-upload fa-sm"></i> Upload Logo</label><input type="file" class="d-none" id="school_logo" name="school_logo"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-row">
                                            <div class="form-group col-md-12"><label for="school_name">School Name *</label><input type="text" class="form-control" name="school_name" required></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6"><label for="email">Email Address *</label><input type="email" class="form-control" name="email" required></div>
                                            <div class="form-group col-md-6"><label for="phone">Phone Number *</label><input type="tel" class="form-control" name="phone" maxlength="10" required></div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="school_opening">School Opening Date *</label><input type="date" class="form-control" name="school_opening" required></div>
                                    <div class="form-group col-md-6"><label for="school_type">School Type *</label><select class="form-control" name="school_type" required>
                                            <option value="">-- Select Type --</option>
                                            <option value="Government">Government</option>
                                            <option value="Private">Private</option>
                                        </select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6"><label for="education_board">Education Board *</label><select class="form-control multi-select" name="education_board[]" multiple="multiple" required><?php $boards = ['CBSE', 'State', 'IGCSE'];
                                                                                                                                                                                                                            foreach ($boards as $board): ?><option value="<?php echo $board; ?>"><?php echo $board; ?></option><?php endforeach; ?></select></div>
                                    <div class="form-group col-md-6"><label for="school_medium">School Medium *</label><select class="form-control multi-select" name="school_medium[]" multiple="multiple" required><?php $mediums = ['English', 'Hindi', 'Regional Language'];
                                                                                                                                                                                                                        foreach ($mediums as $medium): ?><option value="<?php echo $medium; ?>"><?php echo $medium; ?></option><?php endforeach; ?></select></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-12">
                                        <label for="school_category">School Category *</label>
                                        <select class="form-control multi-select" id="school_category" name="school_category[]" multiple="multiple" required>
                                            <?php $categories = ['Pre-Primary', 'Primary(1-5)', 'Upper Primary(6-8)', 'Secondary(9-10)', 'Higher Secondary(11-12)'];
                                            foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group"><label for="address">Address *</label><textarea class="form-control" name="address" rows="3" required></textarea></div>
                                <hr>
                                <div class="form-group mt-4">
                                    <button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Enroll School</button>
                                    <button type="reset" class="btn btn-secondary"><i class="fas fa-times"></i> Reset Form</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once '../footer.php'; ?>
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
            // Initialize all multi-select dropdowns
            $('.multi-select').select2();
        });

        // Logo preview logic
        document.getElementById('school_logo').addEventListener('change', function(event) {
            if (event.target.files[0]) {
                document.getElementById('logoPreview').src = URL.createObjectURL(event.target.files[0]);
            }
        });
    </script>
</body>

</html>