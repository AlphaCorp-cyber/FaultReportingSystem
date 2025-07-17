
<?php
require_once 'vendor/autoload.php';
require_once 'config/config.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Font;

// Create new PHPWord instance
$phpWord = new PhpWord();

// Define styles
$phpWord->addTitleStyle(1, ['name' => 'Arial', 'size' => 20, 'bold' => true, 'color' => '2E74B5']);
$phpWord->addTitleStyle(2, ['name' => 'Arial', 'size' => 16, 'bold' => true, 'color' => '1F4E79']);
$phpWord->addTitleStyle(3, ['name' => 'Arial', 'size' => 14, 'bold' => true, 'color' => '365F91']);

$headerStyle = ['name' => 'Arial', 'size' => 12, 'bold' => true];
$normalStyle = ['name' => 'Arial', 'size' => 11];
$codeStyle = ['name' => 'Courier New', 'size' => 10];

// Create sections
$section = $phpWord->addSection();

// Title Page
$section->addTitle('Redcliff Municipality Fault Reporting System', 1);
$section->addText('Complete System Documentation', $headerStyle);
$section->addTextBreak(2);

$titleTable = $section->addTable();
$titleTable->addRow();
$titleTable->addCell(3000)->addText('System Version:', $headerStyle);
$titleTable->addCell(3000)->addText('1.0.0', $normalStyle);
$titleTable->addRow();
$titleTable->addCell(3000)->addText('Generated Date:', $headerStyle);
$titleTable->addCell(3000)->addText(date('F j, Y'), $normalStyle);
$titleTable->addRow();
$titleTable->addCell(3000)->addText('Technology Stack:', $headerStyle);
$titleTable->addCell(3000)->addText('PHP, PostgreSQL, Bootstrap, JavaScript', $normalStyle);

$section->addPageBreak();

// Table of Contents
$section->addTitle('Table of Contents', 1);
$tocItems = [
    '1. System Overview',
    '2. System Architecture',
    '3. Technical Specifications',
    '4. User Guide',
    '5. Login Credentials',
    '6. System Diagrams',
    '7. Database Design',
    '8. API Documentation',
    '9. Installation Guide',
    '10. Troubleshooting'
];

foreach ($tocItems as $item) {
    $section->addText($item, $normalStyle);
}
$section->addPageBreak();

// 1. System Overview
$section->addTitle('1. System Overview', 1);

$section->addTitle('1.1 Purpose', 2);
$section->addText('The Redcliff Municipality Fault Reporting System is a web-based platform designed to digitize and streamline the process of reporting, tracking, and managing infrastructure faults within Redcliff Municipality, Zimbabwe.', $normalStyle);

$section->addTitle('1.2 Key Features', 2);
$features = [
    'Online fault reporting with photo evidence',
    'User verification through payment records',
    'Real-time status tracking',
    'Administrative dashboard for fault management',
    'Predictive analytics for fault prevention',
    'Department-specific login portals',
    'Automated notifications and updates',
    'Comprehensive reporting and analytics'
];

foreach ($features as $feature) {
    $section->addText('• ' . $feature, $normalStyle);
}

$section->addTitle('1.3 System Objectives', 2);
$objectives = [
    'Allow verified residents to report faults via web interface',
    'Capture detailed information and location of faults',
    'Automatically allocate faults to correct municipal departments',
    'Provide status updates to residents',
    'Use historical data to predict potential faults'
];

foreach ($objectives as $objective) {
    $section->addText('• ' . $objective, $normalStyle);
}

$section->addPageBreak();

// 2. System Architecture
$section->addTitle('2. System Architecture', 1);

$section->addTitle('2.1 Technology Stack', 2);
$techTable = $section->addTable();
$techTable->addRow();
$techTable->addCell(2000)->addText('Component', $headerStyle);
$techTable->addCell(3000)->addText('Technology', $headerStyle);
$techTable->addCell(3000)->addText('Purpose', $headerStyle);

$techStack = [
    ['Frontend', 'HTML5, CSS3, Bootstrap 5, JavaScript', 'User interface and interactions'],
    ['Backend', 'PHP 8.2', 'Server-side logic and processing'],
    ['Database', 'PostgreSQL', 'Data storage and management'],
    ['Web Server', 'Apache/Nginx', 'HTTP request handling'],
    ['Deployment', 'Replit Platform', 'Cloud hosting and deployment']
];

foreach ($techStack as $tech) {
    $techTable->addRow();
    $techTable->addCell(2000)->addText($tech[0], $normalStyle);
    $techTable->addCell(3000)->addText($tech[1], $normalStyle);
    $techTable->addCell(3000)->addText($tech[2], $normalStyle);
}

$section->addTitle('2.2 System Components', 2);
$components = [
    'Authentication Module: Handles user login and verification',
    'Fault Reporting Module: Manages fault submission and tracking',
    'Admin Dashboard: Provides management interface for municipal staff',
    'Prediction System: Uses machine learning for fault analytics',
    'File Management: Handles photo evidence and document uploads',
    'Notification System: Manages alerts and status updates'
];

foreach ($components as $component) {
    $section->addText('• ' . $component, $normalStyle);
}

$section->addPageBreak();

// 3. Technical Specifications
$section->addTitle('3. Technical Specifications', 1);

$section->addTitle('3.1 Database Schema', 2);
$section->addText('Main Tables:', $headerStyle);

$dbTables = [
    'users: User accounts and authentication data',
    'fault_reports: Fault submissions and tracking information',
    'departments: Municipal department information',
    'fault_progress_updates: Status change history',
    'notifications: System notifications and alerts',
    'activity_log: User activity tracking'
];

foreach ($dbTables as $table) {
    $section->addText('• ' . $table, $normalStyle);
}

$section->addTitle('3.2 File Upload Specifications', 2);
$fileSpecs = [
    'Supported formats: JPG, JPEG, PNG, GIF, PDF',
    'Maximum file size: 5MB',
    'Storage location: /uploads/evidence/',
    'Security: File type validation and secure naming'
];

foreach ($fileSpecs as $spec) {
    $section->addText('• ' . $spec, $normalStyle);
}

$section->addPageBreak();

// 4. User Guide
$section->addTitle('4. User Guide', 1);

$section->addTitle('4.1 Resident Portal', 2);
$section->addText('Step-by-step guide for residents:', $headerStyle);

$residentSteps = [
    '1. Navigate to the system homepage',
    '2. Click "Resident Login" to access the resident portal',
    '3. Enter your registered email and password',
    '4. Upon successful login, access the dashboard',
    '5. Click "Submit New Fault" to report an issue',
    '6. Fill in the fault details form:',
    '   - Select fault category (Water, Roads, Electricity, etc.)',
    '   - Provide descriptive title',
    '   - Enter detailed description',
    '   - Specify exact location',
    '   - Upload photo evidence (optional)',
    '7. Submit the form to generate a reference number',
    '8. Track fault status in "My Faults" section'
];

foreach ($residentSteps as $step) {
    $section->addText($step, $normalStyle);
}

$section->addTitle('4.2 Admin Portal', 2);
$section->addText('Administrative functions:', $headerStyle);

$adminSteps = [
    '1. Access admin portal via /admin/login.php',
    '2. Use admin credentials to log in',
    '3. Dashboard provides overview of system status',
    '4. Manage faults through "Fault Management" section',
    '5. Assign faults to appropriate departments',
    '6. Update fault status and progress',
    '7. Generate reports and analytics',
    '8. Manage user accounts and verifications'
];

foreach ($adminSteps as $step) {
    $section->addText($step, $normalStyle);
}

$section->addPageBreak();

// 5. Login Credentials
$section->addTitle('5. Login Credentials', 1);

$section->addTitle('5.1 Demo Admin Account', 2);
$adminCreds = $section->addTable();
$adminCreds->addRow();
$adminCreds->addCell(2000)->addText('Email:', $headerStyle);
$adminCreds->addCell(4000)->addText('admin@redcliff.gov.zw', $codeStyle);
$adminCreds->addRow();
$adminCreds->addCell(2000)->addText('Password:', $headerStyle);
$adminCreds->addCell(4000)->addText('admin123', $codeStyle);
$adminCreds->addRow();
$adminCreds->addCell(2000)->addText('Access URL:', $headerStyle);
$adminCreds->addCell(4000)->addText('/admin/login.php', $codeStyle);

$section->addTitle('5.2 Demo Resident Account', 2);
$residentCreds = $section->addTable();
$residentCreds->addRow();
$residentCreds->addCell(2000)->addText('Email:', $headerStyle);
$residentCreds->addCell(4000)->addText('john.doe@example.com', $codeStyle);
$residentCreds->addRow();
$residentCreds->addCell(2000)->addText('Password:', $headerStyle);
$residentCreds->addCell(4000)->addText('resident123', $codeStyle);
$residentCreds->addRow();
$residentCreds->addCell(2000)->addText('Access URL:', $headerStyle);
$residentCreds->addCell(4000)->addText('/auth/login.php', $codeStyle);

$section->addTitle('5.3 System URLs', 2);
$urls = [
    'Homepage: /',
    'Resident Login: /auth/login.php',
    'Resident Registration: /auth/register.php',
    'Admin Login: /admin/login.php',
    'Department Dashboard: /admin/section_dashboard.php'
];

foreach ($urls as $url) {
    $section->addText('• ' . $url, $codeStyle);
}

$section->addPageBreak();

// 6. System Diagrams
$section->addTitle('6. System Diagrams', 1);

$section->addTitle('6.1 Context Diagram', 2);
$section->addText('The system operates within the following context:', $normalStyle);
$contextElements = [
    'External Entities:',
    '• Residents - Submit and track fault reports',
    '• Municipal Staff - Manage and resolve faults',
    '• Department Heads - Oversee department operations',
    '• System Administrator - Manage system configuration',
    '',
    'System Boundary:',
    '• Web-based Fault Reporting System',
    '• Database Management System',
    '• File Storage System',
    '• Notification Services'
];

foreach ($contextElements as $element) {
    $section->addText($element, $normalStyle);
}

$section->addTitle('6.2 Data Flow Diagram (Level 0)', 2);
$dataFlow = [
    'Process 1: User Authentication',
    '• Input: Login credentials',
    '• Output: User session, access permissions',
    '',
    'Process 2: Fault Submission',
    '• Input: Fault details, evidence files',
    '• Output: Fault reference number, confirmation',
    '',
    'Process 3: Fault Management',
    '• Input: Status updates, assignments',
    '• Output: Updated fault records, notifications',
    '',
    'Process 4: Reporting & Analytics',
    '• Input: Historical fault data',
    '• Output: Reports, predictions, insights'
];

foreach ($dataFlow as $flow) {
    $section->addText($flow, $normalStyle);
}

$section->addTitle('6.3 System Flowchart', 2);
$flowchart = [
    'Start → User Access System',
    '↓',
    'Authentication Required?',
    '├─ Yes → Login Process → Verify Credentials',
    '│   ├─ Valid → Grant Access → Dashboard',
    '│   └─ Invalid → Display Error → Return to Login',
    '└─ No → Public Pages',
    '↓',
    'User Actions:',
    '├─ Resident → Submit Fault → Validation → Store in Database',
    '├─ Admin → Manage Faults → Update Status → Notify Users',
    '└─ Department → Process Assignments → Update Progress',
    '↓',
    'End Process'
];

foreach ($flowchart as $step) {
    $section->addText($step, $normalStyle);
}

$section->addPageBreak();

// 7. Database Design
$section->addTitle('7. Database Design', 1);

$section->addTitle('7.1 Entity Relationship Diagram', 2);
$section->addText('Main Entities and Relationships:', $headerStyle);

$entities = [
    'Users Entity:',
    '• id (Primary Key)',
    '• email (Unique)',
    '• password_hash',
    '• first_name, last_name',
    '• role (admin, resident, department)',
    '• verification_status',
    '',
    'Fault_Reports Entity:',
    '• id (Primary Key)',
    '• reference_number (Unique)',
    '• user_id (Foreign Key → Users)',
    '• category, title, description',
    '• location, latitude, longitude',
    '• status, priority',
    '• assigned_department, assigned_to',
    '',
    'Relationships:',
    '• Users (1) → (Many) Fault_Reports',
    '• Departments (1) → (Many) Fault_Reports',
    '• Fault_Reports (1) → (Many) Progress_Updates'
];

foreach ($entities as $entity) {
    $section->addText($entity, $normalStyle);
}

$section->addTitle('7.2 Database Tables', 2);
$tableInfo = [
    'users: 8 columns, stores user authentication and profile data',
    'fault_reports: 20 columns, main fault tracking information',
    'departments: 8 columns, municipal department details',
    'fault_progress_updates: 7 columns, status change history',
    'notifications: 7 columns, system alerts and messages',
    'activity_log: 6 columns, user action tracking'
];

foreach ($tableInfo as $info) {
    $section->addText('• ' . $info, $normalStyle);
}

$section->addPageBreak();

// 8. API Documentation
$section->addTitle('8. API Documentation', 1);

$section->addTitle('8.1 API Endpoints', 2);
$apiEndpoints = [
    'GET /api/get_fault_details.php',
    '• Purpose: Retrieve detailed fault information',
    '• Parameters: fault_id',
    '• Response: JSON fault object',
    '',
    'POST /api/update_fault_status.php',
    '• Purpose: Update fault status and progress',
    '• Parameters: fault_id, status, notes',
    '• Response: Success/error message',
    '',
    'POST /api/upload_evidence.php',
    '• Purpose: Upload photo evidence for faults',
    '• Parameters: fault_id, file upload',
    '• Response: File URL and confirmation',
    '',
    'GET /api/get_fault_progress.php',
    '• Purpose: Get fault progress history',
    '• Parameters: fault_id',
    '• Response: Progress update array'
];

foreach ($apiEndpoints as $endpoint) {
    $section->addText($endpoint, $normalStyle);
}

$section->addPageBreak();

// 9. Installation Guide
$section->addTitle('9. Installation Guide', 1);

$section->addTitle('9.1 System Requirements', 2);
$requirements = [
    'Web Server: Apache 2.4+ or Nginx',
    'PHP: Version 8.0 or higher',
    'Database: PostgreSQL 12+ or MySQL 8.0+',
    'Storage: Minimum 1GB for application files',
    'Memory: 512MB RAM minimum',
    'Extensions: PDO, GD, mbstring, openssl'
];

foreach ($requirements as $req) {
    $section->addText('• ' . $req, $normalStyle);
}

$section->addTitle('9.2 Installation Steps', 2);
$installSteps = [
    '1. Clone or download system files',
    '2. Configure database connection in config/config.php',
    '3. Run database setup script: php setup_database.php',
    '4. Set file permissions for uploads directory',
    '5. Configure web server virtual host',
    '6. Test system access and functionality',
    '7. Create initial admin user account'
];

foreach ($installSteps as $step) {
    $section->addText($step, $normalStyle);
}

$section->addPageBreak();

// 10. Troubleshooting
$section->addTitle('10. Troubleshooting', 1);

$section->addTitle('10.1 Common Issues', 2);
$troubleshoot = [
    'Database Connection Error:',
    '• Check database credentials in config.php',
    '• Verify database server is running',
    '• Ensure database exists and is accessible',
    '',
    'File Upload Failures:',
    '• Check uploads directory permissions (755)',
    '• Verify file size limits in PHP configuration',
    '• Ensure supported file types are being used',
    '',
    'Login Issues:',
    '• Verify user credentials in database',
    '• Check session configuration',
    '• Clear browser cache and cookies',
    '',
    'Permission Denied Errors:',
    '• Check file and directory permissions',
    '• Verify web server user has access',
    '• Review security configurations'
];

foreach ($troubleshoot as $issue) {
    $section->addText($issue, $normalStyle);
}

$section->addTitle('10.2 System Logs', 2);
$section->addText('Important log locations:', $headerStyle);
$logs = [
    'PHP Error Log: Check server error logs',
    'Application Log: Database queries and user actions',
    'Web Server Log: HTTP request and response logs',
    'System Activity: Available in admin dashboard'
];

foreach ($logs as $log) {
    $section->addText('• ' . $log, $normalStyle);
}

// Footer
$section->addTextBreak(3);
$section->addText('End of Documentation', ['name' => 'Arial', 'size' => 12, 'bold' => true, 'color' => '808080']);
$section->addText('Generated by Redcliff Municipality Fault Reporting System', ['name' => 'Arial', 'size' => 10, 'color' => '808080']);

// Save the document
$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$filename = 'Redcliff_Fault_System_Documentation_' . date('Y-m-d') . '.docx';
$objWriter->save($filename);

echo "Documentation generated successfully: " . $filename . "\n";
echo "File size: " . number_format(filesize($filename) / 1024, 2) . " KB\n";
echo "The document includes:\n";
echo "- Complete system overview and architecture\n";
echo "- User guides for residents and administrators\n";
echo "- Login credentials and access information\n";
echo "- Technical diagrams and database design\n";
echo "- API documentation and troubleshooting guide\n";
?>
