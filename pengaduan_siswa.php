<?php
// Establish database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "suara_kita"; // Database name as seen in phpMyAdmin

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch complaints from the database
// We'll select the relevant columns for display: id, kategori, judul, status, created_at (as 'tanggal')
$sql = "SELECT id, kategori, judul, status, created_at FROM pengaduan ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pengaduan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f0e5; /* Light brown/beige background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 80%;
            max-width: 900px;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #e0b080; /* A darker shade of brown */
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .status-diproses {
            background-color: #add8e6; /* Light blue */
            color: #333;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status-pending {
            background-color: #ffd700; /* Gold */
            color: #333;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status-ditolak {
            background-color: #ffa07a; /* Light salmon */
            color: #333;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status-selesai {
            background-color: #90ee90; /* Light green */
            color: #333;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .logout-button {
            background-color: #dc3545; /* Red */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 20px;
            display: inline-block;
        }
        .logout-button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Cek Status Pengaduan Anda</h2>
        <?php
        if ($result->num_rows > 0) {
            echo "<table>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>No</th>";
            echo "<th>Kategori</th>";
            echo "<th>Judul</th>";
            echo "<th>Status</th>";
            echo "<th>Tanggal</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            $no = 1;
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($row["kategori"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["judul"]) . "</td>";
                echo "<td>";
                // Apply specific styling based on status
                $statusClass = '';
                switch (strtolower($row["status"])) {
                    case 'diproses':
                        $statusClass = 'status-diproses';
                        break;
                    case 'pending':
                        $statusClass = 'status-pending';
                        break;
                    case 'ditolak':
                        $statusClass = 'status-ditolak';
                        break;
                    case 'selesai':
                        $statusClass = 'status-selesai';
                        break;
                    default:
                        $statusClass = ''; // No specific class for other statuses
                        break;
                }
                echo "<span class='" . $statusClass . "'>" . htmlspecialchars($row["status"]) . "</span>";
                echo "</td>";
                // Format the created_at timestamp to just date
                echo "<td>" . date('d M Y', strtotime($row["created_at"])) . "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>Belum ada pengaduan.</p>";
        }
        $conn->close();
        ?>
        <a href="dashboard_siswa.php" class="logout-button" style="background-color: #6c757d;">Kembali ke Dashboard</a>
    </div>
</body>
</html>