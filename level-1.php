<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $file = $_FILES['file'];
    $action = $_POST['action'];
    $uploadDir = 'uploads/';
    $filePath = $uploadDir . basename($file['name']);

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        if ($action == 'csv_to_json') {
            $result = csvToJson($filePath);
            $outputFile = $uploadDir . pathinfo($filePath, PATHINFO_FILENAME) . '.json';
        } elseif ($action == 'json_to_csv') {
            $result = jsonToCsv($filePath);
            $outputFile = $uploadDir . pathinfo($filePath, PATHINFO_FILENAME) . '.csv';
        } else {
            echo "Invalid action!";
            exit;
        }

        file_put_contents($outputFile, $result);
        echo "<h3>Hasil Konversi:</h3>";
        echo "<pre>" . htmlspecialchars($result) . "</pre>";
        echo "<p><a href='$outputFile' download>Download File Hasil</a></p>";
    } else {
        echo "Gagal mengunggah file!";
    }
}

function csvToJson($filePath) {
    $data = array_map('str_getcsv', file($filePath));
    $headers = array_shift($data);
    $json = [];

    foreach ($data as $row) {
        $json[] = array_combine($headers, $row);
    }

    return json_encode($json, JSON_PRETTY_PRINT);
}

function jsonToCsv($filePath) {
    $data = json_decode(file_get_contents($filePath), true);
    if (!$data) {
        return "Invalid JSON format!";
    }

    $csv = fopen('php://temp', 'r+');
    fputcsv($csv, array_keys($data[0]));

    foreach ($data as $row) {
        fputcsv($csv, $row);
    }

    rewind($csv);
    return stream_get_contents($csv);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV <-> JSON Converter</title>
</head>
<body>
    <h1>CSV & JSON Converter</h1>
    <form method="post" enctype="multipart/form-data">
        <label for="file">Pilih File:</label>
        <input type="file" name="file" id="file" required><br><br>
        <label for="action">Konversi:</label>
        <select name="action" id="action" required>
            <option value="csv_to_json">CSV ke JSON</option>
            <option value="json_to_csv">JSON ke CSV</option>
        </select><br><br>
        <button type="submit">Konversi</button>
    </form>
</body>
</html>
