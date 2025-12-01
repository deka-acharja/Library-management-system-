<?php
require '../vendor/autoload.php'; // PhpSpreadsheet
include('../includes/db.php');
include('../includes/dashboard_header.php');

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    if (!file_exists($file)) {
        die("Error: Excel file not found.");
    }

    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    array_shift($rows); // Remove header row

    $success = 0;
    $fail = 0;

    foreach ($rows as $rowIndex => $row) {
        if (count($row) < 13) { // Now expect 13 columns (including quantity)
            echo "<div class='alert alert-error'>Row " . ($rowIndex + 2) . " skipped: Not enough columns.</div>";
            $fail++;
            continue;
        }

        list(
            $title,
            $author,
            $genre,
            $serialno,
            $pages,
            $publication_details,
            $isbn,
            $edition,
            $image,
            $section,
            $call_no,
            $rack_no,
            $quantity
        ) = $row;

        // Sanitize image
        $image = trim(basename($image));
        $image_path = __DIR__ . "/../uploads/$image";

        if (!file_exists($image_path) || is_dir($image_path)) {
            echo "<div class='alert alert-error'>Invalid image file: $image</div>";
            $fail++;
            continue;
        }

        $image_blob = file_get_contents($image_path);

        // Determine availability based on quantity
        $availability = (intval($quantity) > 0) ? 'Available' : 'Not Available';

        // Insert into books
        $stmt1 = $conn->prepare("INSERT INTO books 
            (title, author, genre, serialno, pages, publication_details, ISBN, edition, image, availability)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt1->bind_param(
            "ssssisssss",
            $title,
            $author,
            $genre,
            $serialno,
            $pages,
            $publication_details,
            $isbn,
            $edition,
            $image_blob,
            $availability
        );

        if ($stmt1->execute()) {
            $book_id = $stmt1->insert_id;

            // Insert into books_information
            $stmt2 = $conn->prepare("INSERT INTO books_information 
                (book_id, section, call_no, rack_no) VALUES (?, ?, ?, ?)");
            $stmt2->bind_param("isss", $book_id, $section, $call_no, $rack_no);

            // Insert into inventory
            $stmt3 = $conn->prepare("INSERT INTO inventory (book_id, quantity) VALUES (?, ?)");
            $stmt3->bind_param("ii", $book_id, $quantity);

            if ($stmt2->execute() && $stmt3->execute()) {
                $success++;
            } else {
                echo "<div class='alert alert-error'>Failed to insert book info or inventory for: $title</div>";
                $fail++;
            }

            $stmt2->close();
            $stmt3->close();
        } else {
            echo "<div class='alert alert-error'>Failed to insert book: $title</div>";
            $fail++;
        }

        $stmt1->close();
    }

    echo "<div class='upload-summary'>";
    echo "<div class='summary-card'>";
    echo "<h3><i class='fas fa-chart-line'></i> Upload Summary</h3>";
    echo "<div class='summary-stats'>";
    echo "<div class='stat-item success'><span class='stat-number'>$success</span><span class='stat-label'>Successfully Inserted</span></div>";
    echo "<div class='stat-item error'><span class='stat-number'>$fail</span><span class='stat-label'>Failed</span></div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Books - Royal Institute of Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --main-green: #2e6a33;
            --dark-green: #1d4521;
            --light-green: #c9dccb;
            --accent-green: #4a9950;
            --ultra-light-green: #eef5ef;
            --text-dark: #263028;
            --text-medium: #45634a;
            --text-light: #6b8f70;
            --white: #ffffff;
            --error-red: #d64541;
            --success-green: #2ecc71;
            --warning-yellow: #f39c12;
            --font-main: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
            --border-radius: 12px;
            --border-radius-small: 6px;
            --box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            --box-shadow-hover: 0 12px 35px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-main);
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, #f8fdf9 50%, var(--light-green) 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            padding-top: 200px; /* Space for fixed header */
        }

        /* FIXED HEADER STYLES - Matching the image */
        .header-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 9999;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Top Contact Bar */
        .top-contact {
            background: linear-gradient(135deg,rgb(32, 114, 32), #0b4119);
            color: white;
            padding: 8px 30px;
            width: 100%;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            font-size: 13px;
        }

        .contact-text i {
            margin-right: 6px;
            margin-left: 15px;
        }

        .contact-text i:first-child {
            margin-left: 0;
        }

        /* Main Header Navigation */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #fdfeff;
            color: #0a0a0a;
            padding: 15px 30px;
            border-bottom: 2px solid var( --accent-green);
        }

        .nav-left img,
        .nav-right img {
            height: 140px;
            width: auto;
        }

        .nav-center {
            text-align: center;
            color: #080808;
            flex: 1;
        }

        .site-title {
            font-size: 2rem;
            font-weight: bold;
            color: #000;
            margin: 8px 0;
        }

        .dzongkha-text {
            font-size: 1.4rem;
            margin-bottom: 8px;
            text-align: center;
            color: #000;
        }

        .subtitle {
            font-size: 1rem;
            font-style: italic;
            color: #1e7e34;
            margin-top: 5px;
        }

        .highlight {
            color: #155724;
            font-weight: bold;
        }

        /* Main content wrapper */
        .content-wrapper {
            padding: 40px 20px 50px;
            min-height: 100vh;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--main-green);
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: var(--text-medium);
            font-weight: 400;
        }

        /* Upload Card */
        .upload-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 40px;
            margin-bottom: 30px;
            border: 1px solid rgba(46, 106, 51, 0.1);
            transition: var(--transition);
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .upload-card:hover {
            box-shadow: var(--box-shadow-hover);
            transform: translateY(-2px);
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px dashed var(--light-green);
            border-radius: var(--border-radius);
            background: var(--ultra-light-green);
            color: var(--text-medium);
            font-size: 16px;
            transition: var(--transition);
            cursor: pointer;
        }

        .file-input:hover {
            border-color: var(--accent-green);
            background: rgba(74, 153, 80, 0.05);
        }

        .file-input:focus {
            outline: none;
            border-color: var(--main-green);
            box-shadow: 0 0 0 3px rgba(46, 106, 51, 0.1);
        }

        /* Instructions Card */
        .instructions-card {
            background: linear-gradient(135deg, var(--ultra-light-green), rgba(201, 220, 203, 0.3));
            border-radius: var(--border-radius);
            padding: 25px;
            margin: 20px 0;
            border-left: 4px solid var(--accent-green);
        }

        .instructions-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--main-green);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .instructions-list {
            list-style: none;
            padding: 0;
        }

        .instructions-list li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
            color: var(--text-medium);
            line-height: 1.6;
        }

        .instructions-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--success-green);
            font-weight: bold;
        }

        .highlight-text {
            background: linear-gradient(135deg, var(--warning-yellow), #e67e22);
            color: var(--white);
            padding: 2px 6px;
            border-radius: var(--border-radius-small);
            font-weight: 500;
            font-family: 'Courier New', monospace;
        }

        /* Submit Button */
        .submit-btn {
            background: linear-gradient(135deg, var(--main-green), var(--accent-green));
            color: var(--white);
            border: none;
            padding: 15px 40px;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 55px;
            box-shadow: 0 4px 15px rgba(46, 106, 51, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 106, 51, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(214, 69, 65, 0.1), rgba(192, 57, 43, 0.1));
            border: 1px solid rgba(214, 69, 65, 0.3);
            color: var(--error-red);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(39, 174, 96, 0.1));
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: var(--success-green);
        }

        /* Upload Summary */
        .upload-summary {
            margin-top: 30px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        .summary-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            border: 1px solid rgba(46, 106, 51, 0.1);
        }

        .summary-card h3 {
            font-size: 1.5rem;
            color: var(--main-green);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .stat-item.success {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(39, 174, 96, 0.1));
            border: 2px solid rgba(46, 204, 113, 0.3);
        }

        .stat-item.error {
            background: linear-gradient(135deg, rgba(214, 69, 65, 0.1), rgba(192, 57, 43, 0.1));
            border: 2px solid rgba(214, 69, 65, 0.3);
        }

        .stat-number {
            display: block;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-item.success .stat-number {
            color: var(--success-green);
        }

        .stat-item.error .stat-number {
            color: var(--error-red);
        }

        .stat-label {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-medium);
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: var(--white);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding-top: 180px;
            }

            .navbar {
                flex-direction: column;
                text-align: center;
                padding: 10px 15px;
            }

            .nav-left img,
            .nav-right img {
                height: 100px;
            }

            .nav-center {
                margin: 10px 0;
            }

            .site-title {
                font-size: 1.5rem;
            }

            .dzongkha-text {
                font-size: 1.2rem;
            }

            .top-contact {
                padding: 8px 15px;
                justify-content: center;
                text-align: center;
                font-size: 11px;
            }

            .page-title {
                font-size: 2rem;
            }

            .upload-card {
                padding: 25px;
                margin: 0 10px 30px;
            }

            .content-wrapper {
                padding: 20px 10px 40px;
            }
        }
    </style>
</head>

<body>
    <!-- Fixed Header Section -->
    <div class="header-wrapper">
        <!-- Top contact info bar -->
        <div class="top-contact">
            <div class="contact-text">
                <i class="fas fa-envelope"></i>www.rim.edu.bt
                <i class="fas fa-phone"></i>+975-12345678
            </div>
        </div>

        <!-- Main Navigation bar -->
        <div class="navbar">
            <div class="nav-left">
                <img src="../images/left-logo.png" alt="RIM Logo" class="logo">
            </div>
            <div class="nav-center">
                <h2 class="dzongkha-text">༄༅།། རྒྱལ་གཞུང་འཛིན་སྐྱོང་སློབ་སྡེ།</h2>
                <h1 class="site-title">
                    ROYAL INSTITUTE OF <span class="highlight">MANAGEMENT</span>
                </h1>
                <p class="subtitle">management for growth & development</p>
            </div>
            <div class="nav-right">
                <img src="../images/right-logo1.png" alt="Bhutan Logo" class="logo">
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-upload"></i>
                Bulk Upload Books
            </h1>
            <p class="page-subtitle">Import multiple books from Excel files with ease</p>
        </div>

        <!-- Upload Card -->
        <div class="upload-card">
            <form class="upload-form" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-file-excel"></i>
                        Select Excel File
                    </label>
                    <input type="file" name="excel_file" accept=".xls,.xlsx" required class="file-input" id="fileInput">
                </div>

                <div class="instructions-card">
                    <h3 class="instructions-title">
                        <i class="fas fa-info-circle"></i>
                        Important Instructions
                    </h3>
                    <ul class="instructions-list">
                        <li>Place book cover images in the <span class="highlight-text">uploads/</span> directory</li>
                        <li>Image filenames in Excel must exactly match files in uploads folder</li>
                        <li>Excel file must contain exactly <span class="highlight-text">13 columns</span> in this order:</li>
                        <li style="margin-left: 20px; font-family: 'Courier New', monospace; color: var(--main-green);">
                            title • author • genre • serialno • pages • publication_details • ISBN • edition • image • section • call_no • rack_no • quantity
                        </li>
                        <li>Supported formats: <span class="highlight-text">.xls</span> and <span class="highlight-text">.xlsx</span></li>
                    </ul>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Upload Books</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Form submission with loading state
        document.getElementById('uploadForm').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn.querySelector('span');
            const btnIcon = submitBtn.querySelector('i');

            submitBtn.disabled = true;
            btnIcon.className = 'loading';
            btnText.textContent = 'Uploading...';
        });

        // File input enhancement
        document.getElementById('fileInput').addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            if (fileName) {
                console.log('Selected file:', fileName);
            }
        });

        // Keep header always visible
        document.addEventListener("DOMContentLoaded", function () {
            window.onscroll = function () {
                document.querySelector(".header-wrapper").style.top = "0";
            };
        });
    </script>
</body>

</html>