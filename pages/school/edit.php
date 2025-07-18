<?php

include_once "../../includes/connect.php";
include_once "../../encryption.php";

// Check if user is logged in
$role = null;
if (isset($_COOKIE['encrypted_user_role'])) {
    $role = decrypt_id($_COOKIE['encrypted_user_role']);
}

// Redirect to login if not logged in
if (!$role) {
    header("Location: ../../login.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: school_list.php?error=Invalid ID provided");
    exit;
}

$school_id = intval($_GET['id']);
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_name = trim($_POST['school_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $principal_name = trim($_POST['principal_name']);

    // Validation
    if (empty($school_name)) {
        $errors[] = "School name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }

    if (empty($address)) {
        $errors[] = "Address is required";
    }

    // If no errors, update the database
    if (empty($errors)) {
        mysqli_autocommit($conn, false);

        try {
            // Update school table
            $update_school = "UPDATE school SET school_name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
            $stmt_school = mysqli_prepare($conn, $update_school);
            mysqli_stmt_bind_param($stmt_school, "ssssi", $school_name, $email, $phone, $address, $school_id);

            if (!mysqli_stmt_execute($stmt_school)) {
                throw new Exception("Error updating school record: " . mysqli_stmt_error($stmt_school));
            }

            // Update or insert principal record
            if (!empty($principal_name)) {
                // Check if principal record exists
                $check_principal = "SELECT id FROM principal WHERE school_id = ?";
                $stmt_check = mysqli_prepare($conn, $check_principal);
                mysqli_stmt_bind_param($stmt_check, "i", $school_id);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);

                if (mysqli_num_rows($result_check) > 0) {
                    // Update existing principal
                    $update_principal = "UPDATE principal SET principal_name = ? WHERE school_id = ?";
                    $stmt_principal = mysqli_prepare($conn, $update_principal);
                    mysqli_stmt_bind_param($stmt_principal, "si", $principal_name, $school_id);
                } else {
                    // Insert new principal
                    $insert_principal = "INSERT INTO principal (school_id, principal_name) VALUES (?, ?)";
                    $stmt_principal = mysqli_prepare($conn, $insert_principal);
                    mysqli_stmt_bind_param($stmt_principal, "is", $school_id, $principal_name);
                }

                if (!mysqli_stmt_execute($stmt_principal)) {
                    throw new Exception("Error updating principal record: " . mysqli_stmt_error($stmt_principal));
                }

                mysqli_stmt_close($stmt_check);
                mysqli_stmt_close($stmt_principal);
            } else {
                // If principal name is empty, delete the principal record
                $delete_principal = "DELETE FROM principal WHERE school_id = ?";
                $stmt_delete = mysqli_prepare($conn, $delete_principal);
                mysqli_stmt_bind_param($stmt_delete, "i", $school_id);
                mysqli_stmt_execute($stmt_delete);
                mysqli_stmt_close($stmt_delete);
            }

            mysqli_commit($conn);
            mysqli_stmt_close($stmt_school);

            header("Location: school_list.php?success=School updated successfully");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

// Fetch current school data
$query = "SELECT s.id, s.school_name, s.email, s.phone, s.address, p.principal_name 
          FROM school s 
          LEFT JOIN principal p ON s.id = p.school_id 
          WHERE s.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: school_list.php?error=School not found");
    exit;
}

$school = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit School - School Management System</title>

    <!-- Custom fonts -->
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles -->
    <link href="../../assets/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include_once '../../includes/sidebar/BMC_sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include_once '../../includes/header/BMC_header.php'; ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit School</h1>
                        <a href="school_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Schools
                        </a>
                    </div>

                    <!-- Display errors -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Edit Form -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">School Information</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="school_name">School Name *</label>
                                            <input type="text" class="form-control" id="school_name" name="school_name"
                                                value="<?php echo htmlspecialchars($school['school_name']); ?>"
                                                required>
                                        </div>

                                        <div class="form-group">
                                            <label for="email">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="<?php echo htmlspecialchars($school['email']); ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="phone">Phone Number *</label>
                                            <input type="text" class="form-control" id="phone" name="phone"
                                                value="<?php echo htmlspecialchars($school['phone']); ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="address">Address *</label>
                                            <textarea class="form-control" id="address" name="address" rows="3"
                                                required><?php echo htmlspecialchars($school['address']); ?></textarea>
                                        </div>

                                        <div class="form-group">
                                            <label for="principal_name">Principal Name</label>
                                            <input type="text" class="form-control" id="principal_name"
                                                name="principal_name"
                                                value="<?php echo htmlspecialchars($school['principal_name'] ?? ''); ?>"
                                                placeholder="Enter principal name (optional)">
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update School
                                            </button>
                                            <a href="school_list.php" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include_once '../../includes/footer/BMC_footer.php'; ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="../../assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../../assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../../assets/js/sb-admin-2.min.js"></script>

</body>

</html>