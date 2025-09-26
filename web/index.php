<?php
$pageTitle = 'Welcome';
require_once '../includes/connect.php';

// Define the ID of the school to feature for statistics
$school_id_to_feature = 4;

// --- CRITICAL CHANGE: Set INITIAL/FALLBACK values to match your image's minimal counts ---
$student_count = '7+'; 
$teacher_count = '5+'; 
$classroom_count = '50+';
$pass_percentage = '98%';

try {
  // Fetch count statistics dynamically for the specified school ID (4)
  $student_count_raw = $conn->query("SELECT COUNT(*) FROM student WHERE school_id = {$school_id_to_feature}")->fetchColumn();
  $teacher_count_raw = $conn->query("SELECT COUNT(*) FROM teacher WHERE school_id = {$school_id_to_feature}")->fetchColumn();
  
  // These variables are placeholders or come from another table, but we use the displayed defaults for safety:
  $classroom_count_raw = 50; 
  $pass_percentage_raw = 98;

  // Format the numbers for display, using the fetched data
  if ($student_count_raw > 0) {
      $student_count = number_format($student_count_raw);
  }
  if ($teacher_count_raw > 0) {
      $teacher_count = $teacher_count_raw;
  }
  $classroom_count = $classroom_count_raw;
  $pass_percentage = $pass_percentage_raw . '%';

} catch (PDOException $e) {
  // If connection fails, the initial fallback values ('7+', '9+', '50+', '98%') are automatically used.
  error_log("Homepage DB Error: " . $e->getMessage());
}
?>
<?php include 'header.php'; ?>

<main>
  <div class="banner">
    <div class="container">
      <h1 class="font-weight-semibold">Nurturing Minds, Shaping Futures.</h1>
      <h6 class="font-weight-normal text-muted pb-3">Welcome to <?php echo htmlspecialchars($school_info['school_name']); ?>, where we are committed to providing an environment of academic excellence and holistic development.</h6>
      <div>
        <a href="admission.php" class="btn btn-opacity-light mr-1">Apply for Admission</a>
        <a href="track_admission.php" class="btn btn-opacity-success ml-1">Track Application</a>
      </div>
      <img src="images/Group171.svg" alt="School illustration" class="img-fluid">
    </div>
  </div>
  <div class="content-wrapper">
    <div class="container">
      <section class="features-overview" id="facilities-section">
        <div class="content-header">
          <h2>World-Class Facilities</h2>
          <h6 class="section-subtitle text-muted">We provide state-of-the-art facilities to ensure the best learning experience.</h6>
        </div>
        <div class="d-md-flex justify-content-between">
          <div class="grid-margin d-flex justify-content-start">
            <div class="features-width"><img src="images/Group12.svg" alt="Icon for Smart Laboratories" class="img-icons">
              <h5 class="py-3">Smart<br>Laboratories</h5>
              <p class="text-muted">Equipped with the latest technology to foster innovation and practical learning.</p>
            </div>
          </div>
          <div class="grid-margin d-flex justify-content-center">
            <div class="features-width"><img src="images/Group7.svg" alt="Icon for Modern Library" class="img-icons">
              <h5 class="py-3">Modern<br>Library</h5>
              <p class="text-muted">A vast collection of books and digital resources to encourage a love for reading.</p>
            </div>
          </div>
          <div class="grid-margin d-flex justify-content-end">
            <div class="features-width"><img src="images/Group5.svg" alt="Icon for Sports Complex" class="img-icons">
              <h5 class="py-3">Sports<br>Complex</h5>
              <p class="text-muted">Extensive grounds and professional coaching for physical and mental well-being.</p>
            </div>
          </div>
        </div>
      </section>
      <section class="digital-marketing-service" id="promo-section">
        <div class="row align-items-center">
          <div class="col-12 col-lg-7 grid-margin grid-margin-lg-0" data-aos="fade-right">
            <h3 class="m-0">Fostering Creativity and<br>Critical Thinking</h3>
            <div class="col-lg-7 col-xl-6 p-0">
              <p class="py-4 m-0 text-muted">Our curriculum is designed not just for academic success, but to develop well-rounded individuals who are curious, creative, and ready to take on the world.</p>
            </div>
          </div>
          <div class="col-12 col-lg-5 p-0 img-digital grid-margin grid-margin-lg-0" data-aos="fade-left"><img src="images/Group1.png" alt="Students in a classroom" class="img-fluid"></div>
        </div>
        <div class="row align-items-center">
          <div class="col-12 col-lg-7 text-center flex-item grid-margin" data-aos="fade-right"><img src="images/Group2.png" alt="Students collaborating" class="img-fluid"></div>
          <div class="col-12 col-lg-5 flex-item grid-margin" data-aos="fade-left">
            <h3 class="m-0">A Community of<br>Inspired Learners</h3>
            <div class="col-lg-9 col-xl-8 p-0">
              <p class="py-4 m-0 text-muted">We believe in a collaborative approach to education, where students, teachers, and parents work together to create a supportive and engaging learning community.</p>
            </div>
          </div>
        </div>
      </section>
      <section class="case-studies" id="stats-section">
        <div class="row grid-margin">
          <div class="col-12 text-center pb-5">
            <h2>Our Achievements at a Glance</h2>
            <h6 class="section-subtitle text-muted">We are proud of our students and our commitment to excellence.</h6>
          </div>
          <div class="col-12 col-md-6 col-lg-3 stretch-card mb-4 mb-lg-0" data-aos="zoom-in">
            <div class="card color-cards">
              <div class="card-body p-0">
                <div class="bg-primary text-center card-contents">
                  <h2 class="text-white font-weight-bold"><?php echo htmlspecialchars($student_count); ?></h2>
                  <h5 class="text-white">Students Enrolled</h5>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-3 stretch-card mb-4 mb-lg-0" data-aos="zoom-in" data-aos-delay="200">
            <div class="card color-cards">
              <div class="card-body p-0">
                <div class="bg-warning text-center card-contents">
                  <h2 class="text-white font-weight-bold"><?php echo htmlspecialchars($teacher_count); ?></h2>
                  <h5 class="text-white">Qualified Teachers</h5>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-3 stretch-card mb-4 mb-lg-0" data-aos="zoom-in" data-aos-delay="400">
            <div class="card color-cards">
              <div class="card-body p-0">
                <div class="bg-violet text-center card-contents">
                  <h2 class="text-white font-weight-bold"><?php echo htmlspecialchars($classroom_count); ?></h2>
                  <h5 class="text-white">Classrooms</h5>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-3 stretch-card" data-aos="zoom-in" data-aos-delay="600">
            <div class="card color-cards">
              <div class="card-body p-0">
                <div class="bg-success text-center card-contents">
                  <h2 class="text-white font-weight-bold"><?php echo htmlspecialchars($pass_percentage); ?></h2>
                  <h5 class="text-white">Pass Percentage</h5>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</main>

<?php include 'footer.php'; ?>

<script src="vendors/jquery/jquery.min.js"></script>
<script src="vendors/bootstrap/bootstrap.min.js"></script>
<script src="vendors/owl-carousel/js/owl.carousel.min.js"></script>
<script src="vendors/aos/js/aos.js"></script>
<script src="js/landingpage.js"></script>
</body>

</html>