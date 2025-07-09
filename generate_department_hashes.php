
<?php
// Generate correct password hashes for department users

$departments = [
    'WATER_DEPT' => 'water123',
    'ROADS_DEPT' => 'roads123',
    'ELECTRICITY_DEPT' => 'electricity123',
    'PARKS_DEPT' => 'parks123',
    'WASTE_DEPT' => 'waste123',
    'GENERAL_DEPT' => 'general123'
];

echo "Generating password hashes for department users:\n\n";

foreach ($departments as $dept_code => $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Department: $dept_code\n";
    echo "Password: $password\n";
    echo "Hash: $hash\n\n";
}

echo "SQL UPDATE statements:\n\n";

foreach ($departments as $dept_code => $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "UPDATE users SET password_hash = '$hash' WHERE department_code = '$dept_code';\n";
}
?>
