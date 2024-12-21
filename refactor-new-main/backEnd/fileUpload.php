<?php
$servername = "ftpupload.net";
$username = "if0_37620931";
$password = "UwwZHTAZwJ2u";
$dbname = "if0_37620931_gpacalc";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve form data
$dept = $_POST['dept'];
// $module = $_POST['module'];
$batch = $_POST['batch'];
$credits = $_POST['credits'];

// File upload handling
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $filename = $_FILES['file']['name'];
    $filetype = $_FILES['file']['type'];
    $filedata = file_get_contents($_FILES['file']['tmp_name']);
} else {
    die("File upload error");
}

// Determine the appropriate table based on the department
$tableName = '';
switch ($dept) {
    case 'BTC':
        $tableName = 'btcresults';
        break;
    case 'ETC':
        $tableName = 'etcresults';
        break;
    case 'ITC':
        $tableName = 'itcresults';
        break;
    default:
        die("Invalid department");
}

// Prepare and execute the SQL statement
$stmt = $conn->prepare("INSERT INTO $tableName (filename, filetype, filedata, credits, batch) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssis", $filename, $filetype, $filedata, $credits, $batch);

if ($stmt->execute()) {
    echo "File uploaded and data inserted successfully";
} else {
    echo "Error: " . $stmt->error;
}

// Close the statement and connection
$stmt->close();
$conn->close();
