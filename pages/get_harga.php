<?php
// Koneksi DB dan ambil data sama seperti tadi
$koneksi = new mysqli("localhost", "root", "", "hoteldata");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

$property = isset($_GET['property']) ? $_GET['property'] : '';
$ota = isset($_GET['ota']) ? $_GET['ota'] : '';

if (empty($property)) {
    echo "<p class='text-center text-red-500 font-medium my-4'>Properti tidak boleh kosong.</p>";
    exit;
}

// Ambil competitor properties
$query = "SELECT DISTINCT property_name FROM harga_hotel WHERE kompetitor_dari = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("s", $property);
$stmt->execute();
$result = $stmt->get_result();

$competitorProperties = [];
while ($row = $result->fetch_assoc()) {
    $competitorProperties[] = $row['property_name'];
}

// Gabungkan property utama
$allProperties = array_merge([$property], $competitorProperties);

// Ambil tanggal unik
$placeholders = implode(',', array_fill(0, count($allProperties), '?'));
$query = "SELECT DISTINCT tanggal FROM harga_hotel WHERE property_name IN ($placeholders) ORDER BY tanggal DESC";
$stmt = $koneksi->prepare($query);
$stmt->bind_param(str_repeat('s', count($allProperties)), ...$allProperties);
$stmt->execute();
$result = $stmt->get_result();

$tanggalList = [];
while ($row = $result->fetch_assoc()) {
    $tanggalList[] = $row['tanggal'];
}

// Ambil semua harga
$query = "SELECT tanggal, property_name, harga FROM harga_hotel WHERE property_name IN ($placeholders) ";
if ($ota !== "all") {
    $query .= "AND ota='$ota'";
}
$stmt = $koneksi->prepare($query);
$stmt->bind_param(str_repeat('s', count($allProperties)), ...$allProperties);
$stmt->execute();
$result = $stmt->get_result();

$dataHarga = [];
while ($row = $result->fetch_assoc()) {
    $dataHarga[$row['tanggal']][$row['property_name']] = $row['harga'];
}

// Tampilkan tabel clean & simple

echo "
<div class='overflow-x-auto p-4 bg-white rounded-md shadow-md'>
<table class='min-w-full table-auto border-collapse'>
  <thead>
    <tr>
      <th class='border-b border-gray-200 py-3 px-4 text-left text-gray-700 font-semibold'>Tanggal</th>";
foreach ($allProperties as $propName) {
    echo "<th class='border-b border-gray-200 py-3 px-4 text-left text-gray-700 font-semibold'>" . htmlspecialchars($propName) .  "</th>";
}
echo "
    </tr>
  </thead>
  <tbody>
";

foreach ($tanggalList as $tgl) {
    echo "<tr class='hover:bg-gray-50 transition-colors'>";
    $dateObj = DateTime::createFromFormat('Y-m-d', $tgl);
    $formattedDate = $dateObj ? $dateObj->format('d M Y') : $tgl;
    echo "<td class='py-3 px-4 text-gray-600 font-mono'>" . htmlspecialchars($formattedDate) . "</td>";

    foreach ($allProperties as $propName) {
        if (isset($dataHarga[$tgl][$propName])) {
            $hargaRaw = $dataHarga[$tgl][$propName];
            $hargaNum = floatval(preg_replace('/[^0-9\.]/', '', $hargaRaw));
            $hargaFormatted = 'Rp ' . number_format($hargaNum, 0, ',', '.');
            echo "<td class='py-3 px-4'>";
            echo "<span class='bg-green-100 text-green-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded-sm border border-green-400'>" . $hargaFormatted . "</span>";
            echo "</td>";
            
        } else {
            echo "<td class=''><span class='bg-pink-100 text-pink-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-sm dark:bg-pink-900 dark:text-pink-300'>None</span></td>";
        }
    }
    echo "</tr>";
}

echo "
  </tbody>
</table>
</div>
";
