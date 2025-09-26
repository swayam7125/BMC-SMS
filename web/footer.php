<?php
// $school_info is already available from the required header.php
// We use the fetched info directly from the array created in header.php.

$footer_info = [
    'address' => $school_info['address'] ?? '123 Education Lane<br>Knowledge City, 456789',
    'phone' => $school_info['phone'] ?? '+91 123 456 7890',
    'email' => $school_info['email'] ?? 'info@example.com'
];
?>
<div class="content-wrapper">
    <div class="container">
        <section class="contact-details" id="contact-details-section">
            <div class="row text-center text-md-left">
                <div class="col-12 col-md-6 col-lg-3 grid-margin">
                    <div class="pt-2">
                        <p class="text-muted m-0"><?php echo htmlspecialchars($footer_info['email']); ?></p>
                        <p class="text-muted m-0"><?php echo htmlspecialchars($footer_info['phone']); ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 grid-margin">
                    <h5 class="pb-2">Get in Touch</h5>
                    <p class="text-muted">Join our newsletter for updates!</p>
                    <form><input type="text" class="form-control" id="Email" placeholder="Email id"></form>
                    <div class="pt-3"><button class="btn btn-dark">Subscribe</button></div>
                </div>
                <div class="col-12 col-md-6 col-lg-3 grid-margin">
                    <h5 class="pb-2">Quick Links</h5>
                    <a href="admission.php">
                        <p class="m-0 pb-2">Admissions</p>
                    </a>
                    <a href="blog.php">
                        <p class="m-0 pt-1 pb-2">Blog</p>
                    </a>
                    <a href="#">
                        <p class="m-0 pt-1">Privacy Policy</p>
                    </a>
                </div>
                <div class="col-12 col-md-6 col-lg-3 grid-margin">
                    <h5 class="pb-2">Our Address</h5>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($footer_info['address'])); ?></p>
                    <div class="d-flex justify-content-center justify-content-md-start">
                        <a href="#"><span class="mdi mdi-facebook"></span></a>
                        <a href="#"><span class="mdi mdi-twitter"></span></a>
                        <a href="#"><span class="mdi mdi-instagram"></span></a>
                    </div>
                </div>
            </div>
        </section>

        <footer class="border-top">
            <p class="text-center text-muted pt-4">Copyright © <?php echo date("Y"); ?> <?php echo htmlspecialchars($school_info['school_name']); ?>. All rights reserved.</p>
        </footer>
    </div>
</div>