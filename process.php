<?php
// process.php

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

// Redirect back to the form with a message
header("Location: index.html?message=" . urlencode($message));
exit();
?>
