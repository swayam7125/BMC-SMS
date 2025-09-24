<?php
require_once 'functions.php';

/**
 * The main layout template for BMC-SMS
 */
class Layout {
    private $title;
    private $content;
    private $scripts;
    private $styles;
    private $data;

    public function __construct($options = []) {
        $this->title = $options['title'] ?? 'BMC-SMS';
        $this->content = $options['content'] ?? '';
        $this->scripts = array_merge(
            [
                // Core scripts that should be loaded first
                'vendor/jquery/jquery.min.js',
                'vendor/bootstrap/js/bootstrap.bundle.min.js',
                'vendor/jquery-easing/jquery.easing.min.js',
                'assets/js/sb-admin-2.min.js',
                
                // ADDED: The new script for fast, single-page application style navigation
                'assets/js/spa-navigation.js',

                // Application scripts
                'assets/js/app.js',
                // 'assets/js/ajax-navigation.js', // REMOVED: Replaced by spa-navigation.js
                'assets/js/ajax-upload.js'
            ],
            $options['scripts'] ?? []
        );
        $this->styles = array_merge(
            [
                // Core styles
                'vendor/fontawesome-free/css/all.min.css',
                'assets/css/sb-admin-2.min.css',
                'assets/css/ajax-loader.css',
                'assets/css/sidebar.css',
                'assets/css/scrollbar_hidden.css'
            ],
            $options['styles'] ?? []
        );
        $this->data = $options['data'] ?? [];
    }

    public function render() {
        if (is_ajax_request()) {
            echo $this->content;
            return;
        }

        // Check if user is logged in
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit();
        }

        // Extract data to make it available in the view
        extract($this->data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo h($this->title); ?></title>

    <base href="<?php echo base_url(); ?>">
    
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <?php foreach($this->styles as $style): ?>
        <link href="<?php echo h($style); ?>" rel="stylesheet">
    <?php endforeach; ?>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'header.php'; ?>
                <div id="main-content" class="container-fluid">
                    <?php echo $this->content; ?>
                </div>
            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>

    <?php include 'logout_modal.php'; ?>
    
    <?php foreach($this->scripts as $script): ?>
        <script src="<?php echo h($script); ?>"></script>
    <?php endforeach; ?>

    <script>
    // Initialize global application settings
    window.BMC_SMS = {
        baseUrl: '<?php echo base_url(); ?>',
        currentUser: {
            id: '<?php echo isset($_SESSION['user_id']) ? h($_SESSION['user_id']) : ''; ?>',
            role: '<?php echo isset($_SESSION['role']) ? h($_SESSION['role']) : ''; ?>'
        }
    };
    </script>
</body>
</html>
<?php
    }
}

// Initialize the layout if this file is included directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    $layout = new Layout();
    $layout->render();
}
?>