<?php
// pdf_compression_app.php

// Function to compress PDF
function compressPDF($inputFile, $outputFile, $quality = 'screen') {
    // Check if Ghostscript is installed
    exec("gs --version", $output, $returnVar);
    if ($returnVar !== 0) {
        throw new Exception("Ghostscript is not installed or not in the system PATH.");
    }

    // Compression quality options
    $qualityOptions = [
        'screen'     => '/screen',
        'ebook'      => '/ebook',
        'printer'    => '/printer',
        'prepress'   => '/prepress',
        'default'    => '/default'
    ];

    // Set the compression quality
    $qualityOption = isset($qualityOptions[$quality]) ? $qualityOptions[$quality] : $qualityOptions['default'];

    // Build the Ghostscript command
    $command = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS={$qualityOption} "
             . "-dNOPAUSE -dQUIET -dBATCH -sOutputFile={$outputFile} {$inputFile}";

    // Execute the command
    exec($command, $output, $returnVar);

    // Check if the compression was successful
    if ($returnVar !== 0) {
        throw new Exception("PDF compression failed.");
    }

    return true;
}

$message = '';

// Process the uploaded file
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Check if file was uploaded without errors
        if (isset($_FILES["pdfFile"]) && $_FILES["pdfFile"]["error"] == 0) {
            $allowed = array("pdf" => "application/pdf");
            $filename = $_FILES["pdfFile"]["name"];
            $filetype = $_FILES["pdfFile"]["type"];
            $filesize = $_FILES["pdfFile"]["size"];
        
            // Verify file extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!array_key_exists($ext, $allowed)) {
                throw new Exception("Error: Please select a valid PDF file.");
            }

            // Verify MIME type of the file
            if (in_array($filetype, $allowed)) {
                // Check file size - 5MB maximum
                $maxsize = 5 * 1024 * 1024;
                if ($filesize > $maxsize) {
                    throw new Exception("Error: File size is larger than the allowed limit.");
                }

                // Set up file paths
                $inputFile = $_FILES["pdfFile"]["tmp_name"];
                $outputFile = "compressed_" . $filename;

                // Get selected quality
                $quality = $_POST["quality"] ?? 'screen';

                // Compress PDF
                if (compressPDF($inputFile, $outputFile, $quality)) {
                    $message = "PDF compressed successfully. <a href='{$outputFile}' download>Download compressed PDF</a>";
                }
            } else {
                throw new Exception("Error: There was a problem uploading your file. Please try again.");
            }
        } else {
            throw new Exception("Error: " . $_FILES["pdfFile"]["error"]);
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Compression Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        form {
            background-color: #f0f0f0;
            padding: 20px;
            border-radius: 5px;
        }
        input[type="file"], select, input[type="submit"] {
            margin: 10px 0;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>PDF Compression Tool</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <label for="pdfFile">Select PDF file:</label><br>
        <input type="file" name="pdfFile" id="pdfFile" accept=".pdf" required><br>
        
        <label for="quality">Compression Quality:</label><br>
        <select name="quality" id="quality">
            <option value="screen">Screen</option>
            <option value="ebook">Ebook</option>
            <option value="printer">Printer</option>
            <option value="prepress">Prepress</option>
        </select><br>
        
        <input type="submit" value="Compress PDF">
    </form>

    <?php if ($message): ?>
        <div class="message">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
</body>
</html>