<!DOCTYPE html>
<html>
<head>
    <title>GPA Calculator</title>
    <style>
        body {
            background-image: url("bg.jpg");
            background-size: cover; 
            background-position: center; 
            background-repeat: no-repeat; 
            background-attachment: fixed; 
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 600px;
            opacity: 0.8;
            font-family: monospace;
            font-weight: bolder;
        }
        h2 {
            text-align: center;
        }
        .output {
            margin-top: 20px;
            font-size: larger;
            text-align: center;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>GPA Calculator Output</h2>
        <div class="output">
            <?php
            require 'vendor/autoload.php';

            use Smalot\PdfParser\Parser;

            $servername = "localhost";
            $username = "bhanuka";
            $password = "mysql";
            $dbname = "gpacalc";

            $conn = new mysqli($servername, $username, $password, $dbname);

            if ($conn->connect_error) {
                die("<p class='error'>Connection failed: " . $conn->connect_error . "</p>");
            }

            function checkDepartment($dept, $batch) {
                global $conn;
                $results = [];

                if (($dept == 'ict') || ($dept == 'ICT')) {
                    $sql = "SELECT filedata, credits FROM itcresults WHERE batch = ?";
                } else if (($dept == 'egt') || ($dept == 'EGT')) {
                    $sql = "SELECT filedata, credits FROM etcresults WHERE batch = ?";
                } else if (($dept == 'bst') || ($dept == 'BST')) {
                    $sql = "SELECT filedata, credits FROM btcresults WHERE batch = ?";
                }

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $batch);
                $stmt->execute();
                $stmt->bind_result($pdfBlob, $credits);

                while ($stmt->fetch()) {
                    $results[] = ['pdfBlob' => $pdfBlob, 'credits' => $credits];
                }

                $stmt->close();

                return $results;
            }

            function findDept($username) {
                $pattern = '/[A-z]{3}/';
                preg_match_all($pattern, $username, $matches);
                return $matches[0][0];
            }

            function findBatch($username) {
                $pattern = '/\d{2}/';
                preg_match_all($pattern, $username, $matches);
                return $matches[0][0];
            }

            function calculateGradePoint($grade) {
                switch ($grade) {
                    case 'A+':
                    case 'A':
                        return 4.0;
                    case 'A-':
                        return 3.7;
                    case 'B+':
                        return 3.3;
                    case 'B':
                        return 3.0;
                    case 'B-':
                        return 2.7;
                    case 'C+':
                        return 2.3;
                    case 'C':
                        return 2.0;
                    case 'C-':
                        return 1.7;
                    case 'D+':
                        return 1.3;
                    case 'D':
                        return 1.0;
                    case 'E':
                        return 0.0;
                    default:
                        return 'fail';
                }
            }

            // Extracting department and batch
            $uname = $_POST['uname'];
            $department = findDept($uname);
            $batch = findBatch($uname);

            echo "<p>Department: $department</p>";
            echo "<p>Batch: $batch</p>";

            $results = checkDepartment($department, $batch);

            if (empty($results)) {
                die("<p class='error'>No PDF data found.</p>");
            }

            echo "<p>Number of result sheets found: " . count($results) . "</p>";

            $parser = new Parser();
            $gradePoints = [];
            $totalCredits = 0;

            foreach ($results as $index => $result) {
                // echo "<p>Processing Grade Sheet " . ($index + 1) . "</p>";

                $pdf = $parser->parseContent($result['pdfBlob']);
                $text = $pdf->getText();

                $pattern = '/\b([A-Z]{3}\/\d{2}\/\d{3})\s+([A-E][+-]?|AB\(ESA\)|E\(ESA\)|E\(CA & ESA\))/';
                preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    if ($match[1] == $uname) {
                        $gradePoint = calculateGradePoint($match[2]);
                        if ($gradePoint !== 'fail') {
                            $gradePoints[] = $gradePoint * $result['credits'];
                            $totalCredits += $result['credits'];
                        }
                    }
                }
            }

            if (empty($gradePoints)) {
                echo "<p>No grades found for the username.</p>";
            } else {
                $gpa = array_sum($gradePoints) / $totalCredits;
                echo "<p>Your overall GPA: " . number_format($gpa, 3) . "</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>
