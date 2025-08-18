<?php
/**
 * Page Converter Script
 * Helps convert existing PHP pages to use the new AJAX-based system
 */
class PageConverter {
    private $source_file;
    private $destination_file;
    private $content;
    private $scripts = [];
    private $styles = [];
    private $title = 'BMC-SMS';

    public function __construct($source_file) {
        $this->source_file = $source_file;
        $this->destination_file = $source_file;
        $this->content = file_get_contents($source_file);
    }

    public function convert() {
        // Extract title
        if (preg_match('/<title>(.*?)<\/title>/s', $this->content, $matches)) {
            $this->title = $matches[1];
        }

        // Extract scripts
        preg_match_all('/<script.*?src=[\'"]([^\'"]+)[\'"].*?><\/script>/i', $this->content, $matches);
        foreach ($matches[1] as $script) {
            if (strpos($script, 'http') === false && strpos($script, '../../') === 0) {
                $this->scripts[] = substr($script, 6); // Remove ../../
            }
        }

        // Extract styles
        preg_match_all('/<link.*?href=[\'"]([^\'"]+)[\'"].*?>/i', $this->content, $matches);
        foreach ($matches[1] as $style) {
            if (strpos($style, 'http') === false && strpos($style, '../../') === 0) {
                $this->styles[] = substr($style, 6); // Remove ../../
            }
        }

        // Extract main content
        if (preg_match('/<div class="container-fluid">(.*?)<\/div>\s*<\/div>\s*<\/div>/s', $this->content, $matches)) {
            $content = $matches[1];
        } else {
            $content = "<!-- Could not extract content automatically -->\n";
        }

        // Generate new file content
        $new_content = $this->generateNewContent($content);

        // Save the file
        file_put_contents($this->destination_file, $new_content);
    }

    private function generateNewContent($content) {
        return <<<PHP
<?php
require_once '../../includes/connect.php';
require_once '../../includes/functions.php';
require_once '../../includes/ajax_helpers.php';
require_once '../../encryption.php';

// Authentication
\$role = isset(\$_COOKIE['encrypted_user_role']) ? decrypt_id(\$_COOKIE['encrypted_user_role']) : null;
\$user_id = isset(\$_COOKIE['encrypted_user_id']) ? decrypt_id(\$_COOKIE['encrypted_user_id']) : null;

if (!\$role) {
    Response::error('Access denied', url('login.php'));
}

// Generate page content
ob_start();
?>
<div class="container-fluid">
$content
</div>
<?php
\$content = ob_get_clean();

// Handle the page request
handle_page_request([
    'content_file' => __FILE__,
    'title' => '{$this->title}',
    'scripts' => [
        // Core scripts are included automatically
        {$this->formatArray($this->scripts)}
    ],
    'styles' => [
        // Core styles are included automatically
        {$this->formatArray($this->styles)}
    ]
]);
PHP;
    }

    private function formatArray($array) {
        return implode(",\n        ", array_map(function($item) {
            return "'$item'";
        }, array_unique($array)));
    }
}

// Usage example:
// $converter = new PageConverter('path/to/file.php');
// $converter->convert();
