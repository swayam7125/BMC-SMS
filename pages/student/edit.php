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
    header("Location: ../../extra/student_tables.php?error=Invalid ID provided");
    exit;
}

$student_id = intval($_GET['id']);
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_image = trim($_POST['student_image']);
    $student_name = trim($_POST['student_name']);
    $rollno = trim($_POST['rollno']);
    $std = trim($_POST['std']);
    $email = trim($_POST['email']);
    $academic_year = trim($_POST['academic_year']);
    $school_id = intval($_POST['school_id']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $address = trim($_POST['address']);
    $father_name = trim($_POST['father_name']);
    $father_phone = trim($_POST['father_phone']);
    $mother_name = trim($_POST['mother_name']);
    $mother_phone = trim($_POST['mother_phone']);
    
    // Validation
    if (empty($student_image)) {
        $errors[] = "Student image is required";
    }

    if (empty($student_name)) {
        $errors[] = "Student name is required";
    }
    
    if (empty($rollno)) {
        $errors[] = "Roll number is required";
    }
    
    if (empty($std)) {
        $errors[] = "Class is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($academic_year)) {
        $errors[] = "Academic year is required";
    }
    
    if (empty($school_id)) {
        $errors[] = "School is required";
    }
    
    if (empty($father_name)) {
        $errors[] = "Father name is required";
    }
    
    if (empty($father_phone)) {
        $errors[] = "Father phone is required";
    } elseif (!preg_match('/^[0-9]{10}$/', $father_phone)) {
        $errors[] = "Father phone must be 10 digits";
    }
    
    if (!empty($mother_phone) && !preg_match('/^[0-9]{10}$/', $mother_phone)) {
        $errors[] = "Mother phone must be 10 digits";
    }
    
    // Check for duplicate roll number (excluding current student)
    if (!empty($rollno) && !empty($school_id)) {
        $check_rollno = "SELECT id FROM student WHERE rollno = ? AND school_id = ? AND id != ?";
        $stmt_check = mysqli_prepare($conn, $check_rollno);
        mysqli_stmt_bind_param($stmt_check, "sii", $rollno, $school_id, $student_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($result_check) > 0) {
            $errors[] = "Roll number already exists in this school";
        }
        mysqli_stmt_close($stmt_check);
    }
    
    // If no errors, update the database
    if (empty($errors)) {
        $update_student = "UPDATE student SET 
                          student_image = ?, student_name = ?, rollno = ?, std = ?, email = ?, academic_year = ?, 
                          school_id = ?, dob = ?, gender = ?, blood_group = ?, address = ?, 
                          father_name = ?, father_phone = ?, mother_name = ?, mother_phone = ?
                          WHERE id = ?";
        
        $stmt_update = mysqli_prepare($conn, $update_student);
        mysqli_stmt_bind_param($stmt_update, "ssssssissssssssi", 
    $student_image, $student_name, $rollno, $std, $email, $academic_year, 
    $school_id, $dob, $gender, $blood_group, $address, 
    $father_name, $father_phone, $mother_name, $mother_phone, $student_id);

        
        if (mysqli_stmt_execute($stmt_update)) {
            mysqli_stmt_close($stmt_update);
            header("Location: ../../extra/student_tables.php?success=Student updated successfully");
            exit;
        } else {
            $errors[] = "Error updating student: " . mysqli_stmt_error($stmt_update);
        }
    }
}

// Fetch current student data
$query = "SELECT * FROM student WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: ../../extra/student_tables.php?error=Student not found");
    exit;
}

$student = mysqli_fetch_assoc($result);
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
    <title>Edit Student - School Management System</title>

    <!-- Custom fonts -->
    <link href="../../assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

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
                        <h1 class="h3 mb-0 text-gray-800">Edit Student</h1>
                        <a href="../../extra/student_tables.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Students
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
                                    <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="student_image">Student Image *</label>
                                                    <input type="file" class="form-control" id="student_image" name="student_image" 
                                                           value="<?php echo htmlspecialchars($student['student_image']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="student_name">Student Name *</label>
                                                    <input type="text" class="form-control" id="student_name" name="student_name" 
                                                           value="<?php echo htmlspecialchars($student['student_name']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="rollno">Roll Number *</label>
                                                    <input type="text" class="form-control" id="rollno" name="rollno" 
                                                           value="<?php echo htmlspecialchars($student['rollno']); ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="std">Class *</label>
                                                    <input type="text" class="form-control" id="std" name="std" 
                                                           value="<?php echo htmlspecialchars($student['std']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="email">Email *</label>
                                                    <input type="email" class="form-control" id="email" name="email" 
                                                           value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="academic_year">Academic Year *</label>
                                                    <input type="text" class="form-control" id="academic_year" name="academic_year" 
                                                           value="<?php echo htmlspecialchars($student['academic_year']); ?>" 
                                                           placeholder="2024-25" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="school_id">School *</label>
                                                    <select class="form-control" id="school_id" name="school_id" required>
                                                        <option value="">Select School</option>
                                                        <?php while ($school = mysqli_fetch_assoc($schools_result)): ?>
                                                        <option value="<?php echo $school['id']; ?>" 
                                                                <?php echo ($school['id'] == $student['school_id']) ? 'selected' : ''; ?>>
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
                                                    <label for="dob">Date of Birth</label>
                                                    <input type="date" class="form-control" id="dob" name="dob" 
                                                           value="<?php echo htmlspecialchars($student['dob']); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="gender">Gender</label>
                                                    <select class="form-control" id="gender" name="gender">
                                                        <option value="">Select Gender</option>
                                                        <option value="male" <?php echo ($student['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                                        <option value="female" <?php echo ($student['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                                        <option value="others" <?php echo ($student['gender'] == 'others') ? 'selected' : ''; ?>>Others</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="blood_group">Blood Group</label>
                                                    <select class="form-control" id="blood_group" name="blood_group">
                                                        <option value="">Select Blood Group</option>
                                                        <option value="a+" <?php echo ($student['blood_group'] == 'a+') ? 'selected' : ''; ?>>A+</option>
                                                        <option value="a-" <?php echo ($student['blood_group'] == 'a-') ? 'selected' : ''; ?>>A-</option>
                                                        <option value="b+" <?php echo ($student['blood_group'] == 'b+') ? 'selected' : ''; ?>>B+</option>
                                                        <option value="b-" <?php echo ($student['blood_group'] == 'b-') ? 'selected' : ''; ?>>B-</option>
                                                        <option value="ab+" <?php echo ($student['blood_group'] == 'ab+') ? 'selected' : ''; ?>>AB+</option>
                                                        <option value="ab-" <?php echo ($student['blood_group'] == 'ab-') ? 'selected' : ''; ?>>AB-</option>
                                                        <option value="o+" <?php echo ($student['blood_group'] == 'o+') ? 'selected' : ''; ?>>O+</option>
                                                        <option value="o-" <?php echo ($student['blood_group'] == 'o-') ? 'selected' : ''; ?>>O-</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="address">Address</label>
                                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="father_name">Father Name *</label>
                                                    <input type="text" class="form-control" id="father_name" name="father_name" 
                                                           value="<?php echo htmlspecialchars($student['father_name']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="father_phone">Father Phone *</label>
                                                    <input type="text" class="form-control" id="father_phone" name="father_phone" 
                                                           value="<?php echo htmlspecialchars($student['father_phone']); ?>" 
                                                           maxlength="10" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="mother_name">Mother Name</label>
                                                    <input type="text" class="form-control" id="mother_name" name="mother_name" 
                                                           value="<?php echo htmlspecialchars($student['mother_name']); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="mother_phone">Mother Phone</label>
                                                    <input type="text" class="form-control" id="mother_phone" name="mother_phone" 
                                                           value="<?php echo htmlspecialchars($student['mother_phone']); ?>" 
                                                           maxlength="10">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Student
                                            </button>
                                            <a href="../../extra/student_tables.php" class="btn btn-secondary">
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