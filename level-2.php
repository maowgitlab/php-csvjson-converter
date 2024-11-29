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
        $conversionResult = $result;
        $downloadLink = $outputFile;
    } else {
        $errorMessage = "Gagal mengunggah file!";
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
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-10">
        <div class="bg-white shadow-md rounded-lg p-6 max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold text-center text-gray-700 mb-6">CSV <-> JSON Converter</h1>
            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700">Pilih File</label>
                    <input type="file" name="file" id="file" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="action" class="block text-sm font-medium text-gray-700">Konversi</label>
                    <select name="action" id="action" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="csv_to_json">CSV ke JSON</option>
                        <option value="json_to_csv">JSON ke CSV</option>
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Konversi
                    </button>
                </div>
            </form>
            <?php if (isset($conversionResult)): ?>
                <div class="mt-6 bg-green-50 p-4 rounded-md">
                    <h2 class="text-lg font-semibold text-green-700">Hasil Konversi</h2>
                    <pre class="bg-gray-100 p-3 rounded-md overflow-auto text-sm text-gray-800"><?= htmlspecialchars($conversionResult); ?></pre>
                    <a href="<?= $downloadLink; ?>" download
                        class="inline-block mt-4 bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Download File Hasil
                    </a>
                </div>
            <?php elseif (isset($errorMessage)): ?>
                <div class="mt-6 bg-red-50 p-4 rounded-md">
                    <p class="text-red-700"><?= $errorMessage; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
