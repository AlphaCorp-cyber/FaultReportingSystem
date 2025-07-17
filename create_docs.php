
<?php
echo "Generating comprehensive system documentation...\n";
echo "=====================================\n\n";

// Check if PHPWord is available
if (!file_exists('vendor/autoload.php')) {
    echo "Error: PHPWord library not found. Please run: composer require phpoffice/phpword\n";
    exit(1);
}

// Run the documentation generator
include 'generate_system_documentation.php';

echo "\nDocumentation generation completed!\n";
echo "You can download the generated DOCX file from the file explorer.\n";
?>
