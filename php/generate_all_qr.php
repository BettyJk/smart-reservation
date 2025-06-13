<?php
// generate_all_qr.php - Output QR code data strings for all seats and the teacher desk

// Output seat QR code data
for ($i = 1; $i <= 45; $i++) {
    echo "seat:$i\n";
}
// Output teacher desk QR code data
echo "desk:teacher\n";

echo "\nCopy each line and use any online QR code generator (e.g. https://www.qr-code-generator.com/) to create the QR images.";
