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
    header("Location: principal_list.php?error=Invalid ID provided");
    exit;
}

$principal_id = intval($_GET['id']);
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $principal_image = trim($_POST['principal_image']);
    $principal_name = trim($_POST['principal_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $principal_dob = $_POST['principal_dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $qualification = trim($_POST['qualification']);
    $salary = trim($_POST['salary']);
    $school_id = intval($_POST['school_id']);

    // Validation
    if (empty($principal_name)) {
        $errors[] = "Principal name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be 10 digits";
    }

    if (empty($gender)) {
        $errors[] = "Gender is required";
    }

    if (empty($blood_group)) {
        $errors[] = "Blood group is required";
    }

    if (empty($school_id)) {
        $errors[] = "School is required";
    }

    if (!empty($salary) && !is_numeric($salary)) {
        $errors[] = "Salary must be a valid number";
    }

    // Check for duplicate email (excluding current principal)
    if (!empty($email)) {
        $check_email = "SELECT id FROM principal WHERE email = ? AND id != ?";
        $stmt_check = mysqli_prepare($conn, $check_email);
        mysqli_stmt_bind_param($stmt_check, "si", $email, $principal_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $errors[] = "Email already exists";
        }
        mysqli_stmt_close($stmt_check);
    }

    // Check for duplicate phone (excluding current principal)
    if (!empty($phone)) {
        $check_phone = "SELECT id FROM principal WHERE phone = ? AND id != ?";
        $stmt_check = mysqli_prepare($conn, $check_phone);
        mysqli_stmt_bind_param($stmt_check, "si", $phone, $principal_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $errors[] = "Phone number already exists";
        }
        mysqli_stmt_close($stmt_check);
    }

    // If no errors, update the database
    if (empty($errors)) {
        $update_principal = "UPDATE principal SET 
                            principal_image = ?, principal_name = ?, email = ?, phone = ?, 
                            principal_dob = ?, gender = ?, blood_group = ?, address = ?, 
                            qualification = ?, salary = ?, school_id = ?
                            WHERE id = ?";

        $stmt_update = mysqli_prepare($conn, $update_principal);
        mysqli_stmt_bind_param(
            $stmt_update,
            "sssssssssdii",
            $principal_image,
            $principal_name,
            $email,
            $phone,
            $principal_dob,
            $gender,
            $blood_group,
            $address,
            $qualification,
            $salary,
            $school_id,
            $principal_id
        );

        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            header("Location: principal_list.php?success=Principal updated successfully");
            exit;
        } else {
            $errors[] = "Error updating principal: " . mysqli_stmt_error($stmt_update);
        }
    }
}

// Fetch current principal data
$query = "SELECT * FROM principal WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $principal_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: principal_list.php?error=Principal not found");
    exit;
}

$principal = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Fetch schools for dropdown
$schools_query = "SELECT id, school_name FROM school ORDER BY school_name";
$schools_result = mysqli_query($conn, $schools_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Edit Principal - School Management System</title>

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
                        <h1 class="h3 mb-0 text-gray-800">Edit Principal</h1>
                        <a href="principal_list.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Principals
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
                        <div class="col-lg-10">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Principal Information</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="principal_image">Principal Image</label>
                                                    <input type="file" class="form-control" id="principal_image"
                                                        name="principal_image"
                                                        value="<?php echo htmlspecialchars($principal['principal_image']); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="principal_name">Principal Name *</label>
                                                    <input type="text" class="form-control" id="principal_name"
                                                        name="principal_name"
                                                        value="<?php echo htmlspecialchars($principal['principal_name']); ?>"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email">Email *</label>
                                                    <input type="email" class="form-control" id="email" name="email"
                                                        value="<?php echo htmlspecialchars($principal['email']); ?>"
                                                        required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="phone">Phone *</label>
                                                    <input type="text" class="form-control" id="phone" name="phone"
                                                        value="<?php echo htmlspecialchars($principal['phone']); ?>"
                                                        maxlength="10" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="school_id">School *</label>
                                                    <select class="form-control" id="school_id" name="school_id"
                                                        required>
                                                        <option value="">Select School</option>
                                                        <?php while ($school = mysqli_fetch_assoc($schools_result)): ?>
                                                        <option value="<?php echo $school['id']; ?>"
                                                            <?php echo ($school['id'] == $principal['school_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($school['school_name']); ?>
                                                        </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="principal_dob">Date of Birth</label>
                                                    <input type="date" class="form-control" id="principal_dob"
                                                        name="principal_dob"
                                                        value="<?php echo htmlspecialchars($principal['principal_dob']); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="gender">Gender *</label>
                                                    <select class="form-control" id="gender" name="gender" required>
                                                        <option value="">Select Gender</option>
                                                        <option value="Male"
                                                            <?php echo ($principal['gender'] == 'Male') ? 'selected' : ''; ?>>
                                                            Male</option>
                                                        <option value="Female"
                                                            <?php echo ($principal['gender'] == 'Female') ? 'selected' : ''; ?>>
                                                            Female</option>
                                                        <option value="Others"
                                                            <?php echo ($principal['gender'] == 'Others') ? 'selected' : ''; ?>>
                                                            Others</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="blood_group">Blood Group *</label>
                                                    <select class="form-control" id="blood_group" name="blood_group"
                                                        required>
                                                        <option value="">Select Blood Group</option>
                                                        <option value="A+"
                                                            <?php echo ($principal['blood_group'] == 'A+') ? 'selected' : ''; ?>>
                                                            A+</option>
                                                        <option value="A-"
                                                            <?php echo ($principal['blood_group'] == 'A-') ? 'selected' : ''; ?>>
                                                            A-</option>
                                                        <option value="B+"
                                                            <?php echo ($principal['blood_group'] == 'B+') ? 'selected' : ''; ?>>
                                                            B+</option>
                                                        <option value="B-"
                                                            <?php echo ($principal['blood_group'] == 'B-') ? 'selected' : ''; ?>>
                                                            B-</option>
                                                        <option value="AB+"
                                                            <?php echo ($principal['blood_group'] == 'AB+') ? 'selected' : ''; ?>>
                                                            AB+</option>
                                                        <option value="AB-"
                                                            <?php echo ($principal['blood_group'] == 'AB-') ? 'selected' : ''; ?>>
                                                            AB-</option>
                                                        <option value="O+"
                                                            <?php echo ($principal['blood_group'] == 'O+') ? 'selected' : ''; ?>>
                                                            O+</option>
                                                        <option value="O-"
                                                            <?php echo ($principal['blood_group'] == 'O-') ? 'selected' : ''; ?>>
                                                            O-</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="qualification">Qualification</label>
                                                    <input type="text" class="form-control" id="qualification"
                                                        name="qualification"
                                                        value="<?php echo htmlspecialchars($principal['qualification']); ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="salary">Salary</label>
                                                    <input type="number" class="form-control" id="salary" name="salary"
                                                        value="<?php echo htmlspecialchars($principal['salary']); ?>"
                                                        step="0.01" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="address">Address</label>
                                                    <textarea class="form-control" id="address" name="address"
                                                        rows="3"><?php echo htmlspecialchars($principal['address']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Principal
                                            </button>
                                            <a href="principal_list.php" class="btn btn-secondary">
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