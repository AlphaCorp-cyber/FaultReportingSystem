
<?php
require_once 'config/config.php';

try {
    // Department credentials
    $departments = [
        [
            'code' => 'WATER_DEPT',
            'password' => 'water123',
            'first_name' => 'Water',
            'email' => 'water@redcliff.gov.zw',
            'department' => 'water'
        ],
        [
            'code' => 'ROADS_DEPT',
            'password' => 'roads123',
            'first_name' => 'Roads',
            'email' => 'roads@redcliff.gov.zw',
            'department' => 'roads'
        ],
        [
            'code' => 'ELECTRICITY_DEPT',
            'password' => 'electricity123',
            'first_name' => 'Electricity',
            'email' => 'electricity@redcliff.gov.zw',
            'department' => 'electricity'
        ],
        [
            'code' => 'PARKS_DEPT',
            'password' => 'parks123',
            'first_name' => 'Parks',
            'email' => 'parks@redcliff.gov.zw',
            'department' => 'parks'
        ],
        [
            'code' => 'WASTE_DEPT',
            'password' => 'waste123',
            'first_name' => 'Waste',
            'email' => 'waste@redcliff.gov.zw',
            'department' => 'waste'
        ],
        [
            'code' => 'GENERAL_DEPT',
            'password' => 'general123',
            'first_name' => 'General',
            'email' => 'general@redcliff.gov.zw',
            'department' => 'general'
        ]
    ];

    // Make payment_record_number nullable
    $db->query("ALTER TABLE users ALTER COLUMN payment_record_number DROP NOT NULL");
    
    // Add department_code column if it doesn't exist
    $db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS department_code VARCHAR(50) UNIQUE");

    // Delete existing department users
    $db->query("DELETE FROM users WHERE role = 'department'");

    echo "Creating department users with correct password hashes...\n\n";

    foreach ($departments as $dept) {
        $password_hash = password_hash($dept['password'], PASSWORD_DEFAULT);
        
        $db->query(
            "INSERT INTO users (first_name, last_name, email, password_hash, role, department, department_code, is_active) 
             VALUES (?, 'Department', ?, ?, 'department', ?, ?, true)",
            [
                $dept['first_name'],
                $dept['email'],
                $password_hash,
                $dept['department'],
                $dept['code']
            ]
        );

        echo "Created: {$dept['code']} with password: {$dept['password']}\n";
        echo "Password hash: $password_hash\n\n";
    }

    echo "All department users created successfully!\n\n";
    echo "Department Login Credentials:\n";
    echo "=============================\n";
    foreach ($departments as $dept) {
        echo "Code: {$dept['code']} | Password: {$dept['password']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
