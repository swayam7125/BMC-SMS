<?php
// $school_info is already available from the required header.php
// We use the fetched info directly from the array created in header.php.

$footer_info = [
    'address' => $school_info['address'] ?? '123 Education Lane<br>Knowledge City, 456789',
    'phone' => $school_info['phone'] ?? '+91 123 456 7890',
    'email' => $school_info['email'] ?? 'info@example.com',
    
    // Placeholder coordinates from school ID 4 (Sanskar Bharti Vidyalay) in sms.sql
    'latitude' => $school_info['latitude'] ?? 21.21060270, 
    'longitude' => $school_info['longitude'] ?? 72.76795460,
    
    // ⭐ NEW: Social Media URLs (Assuming header.php fetches these from public.school)
    'facebook_url' => $school_info['facebook_url'] ?? '#',
    'twitter_url' => $school_info['twitter_url'] ?? '#',
    'instagram_url' => $school_info['instagram_url'] ?? '#'
];

// Construct the Google Maps link using the coordinates
// Note: Using a generic Google Maps URL format
$map_link = 'https://www.google.com/maps/search/?api=1&query=' . 
    ($footer_info['latitude'] ?? '') . ',' . ($footer_info['longitude'] ?? '');
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
                    <p class="text-muted">Follow us on social media for updates!</p>
                    
                    <div class="d-flex justify-content-center justify-content-md-start pt-3">
                        <?php if (!empty($footer_info['facebook_url']) && $footer_info['facebook_url'] !== '#'): ?>
                            <a href="<?php echo htmlspecialchars($footer_info['facebook_url']); ?>" target="_blank"><span class="mdi mdi-facebook"></span></a>
                        <?php endif; ?>
                        
                        <?php if (!empty($footer_info['twitter_url']) && $footer_info['twitter_url'] !== '#'): ?>
                            <a href="<?php echo htmlspecialchars($footer_info['twitter_url']); ?>" target="_blank"><span class="mdi mdi-twitter"></span></a>
                        <?php endif; ?>
                        
                        <?php if (!empty($footer_info['instagram_url']) && $footer_info['instagram_url'] !== '#'): ?>
                            <a href="<?php echo htmlspecialchars($footer_info['instagram_url']); ?>" target="_blank"><span class="mdi mdi-instagram"></span></a>
                        <?php endif; ?>
                    </div>
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
                    <a href="<?php echo htmlspecialchars($map_link); ?>" target="_blank" class="text-muted">
                        <?php echo nl2br(htmlspecialchars($footer_info['address'])); ?>
                    </a>
                </div>
                
            </div>
        </section>

        <footer class="border-top">
            <p class="text-center text-muted pt-4">Copyright © <?php echo date("Y"); ?> <?php echo htmlspecialchars($school_info['school_name']); ?>. All rights reserved.</p>
        </footer>
    </div>
</div>