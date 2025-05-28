<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$koneksi = new mysqli("localhost", "root", "", "hoteldata");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Ambil daftar property 'self'
$query = "SELECT DISTINCT property_name FROM harga_hotel WHERE type='self' ORDER BY property_name";
$result = $koneksi->query($query);
if (!$result) die("Query error: " . $koneksi->error);

$properties = [];
while ($row = $result->fetch_assoc()) {
    $properties[] = $row['property_name'];
}

// Ambil daftar OTA 'competitor'
$queryOta = "SELECT DISTINCT ota FROM harga_hotel WHERE type='competitor' ORDER BY ota";
$resultOta = $koneksi->query($queryOta);
if (!$resultOta) die("Query OTA error: " . $koneksi->error);

$otas = [];
while ($row = $resultOta->fetch_assoc()) {
    $otas[] = $row['ota'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Harga Hotel & Kompetitor</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.6/flowbite.min.css" rel="stylesheet" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        /* Theme warna biru dongker modern */
        :root {
            --primary: #1e3a8a;
            --primary-light: #3b82f6;
            --primary-dark: #172554;
            --primary-muted: #c7d2fe;
        }

        body {
            background-color: #f3f4f6;
            color: var(--primary-dark);
            font-family: "DM Sans", sans-serif;
            font-optical-sizing: auto;
            font-weight: 500;
            font-style: normal;
        }

        header {
            background: linear-gradient(90deg, var(--primary-dark), var(--primary));
            box-shadow: 0 4px 10px rgb(30 58 138 / 0.4);
        }

        h1 {
            color: white;
            font-weight: 600;
            font-size: 1.5rem;
            text-align: center;
            padding: 1.5rem 0;
            text-shadow: 0 2px 5px rgb(0 0 0 / 0.25);
            user-select: none;
        }

        /* Container utama */
        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Properti sidebar */
        .sidebar {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgb(30 58 138 / 0.1);
            padding: 1rem;
            max-height: 80vh;
            overflow-y: auto;
        }

        .sidebar h2 {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary);
            margin-bottom: 1rem;
            user-select: none;
        }

        .property-item {
            padding: 0.6rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.3s ease;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.4rem;
            user-select: none;
        }

        .property-item:hover,
        .property-item.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 0 10px var(--primary-light);
        }

        /* Konten tabel */
        .content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgb(30 58 138 / 0.1);
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .content h3 {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
            user-select: none;
        }

        /* Filter select */
        #filterOta {
            max-width: 220px;
            padding: 0.5rem 1rem;
            border: 2px solid var(--primary);
            border-radius: 8px;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1rem;
            transition: border-color 0.3s ease;
        }

        #filterOta:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 10px var(--primary-light);
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        thead tr {
            background: var(--primary);
            color: white;
            user-select: none;
        }

        thead th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 700;
            border-bottom: 2px solid var(--primary-light);
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tbody tr:hover {
            background-color: var(--primary-muted);
            cursor: default;
        }

        tbody td {
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Loading & error states */
        .status-message {
            padding: 3rem 0;
            text-align: center;
            font-weight: 600;
            color: var(--primary-dark);
            user-select: none;
        }

        .status-loading {
            color: var(--primary-light);
            font-style: italic;
        }

        .status-error {
            color: #dc2626;
            /* merah */
        }

        /* Layout flex */
        .dashboard {
            display: flex;
            gap: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard {
                flex-direction: column;
            }

            .sidebar {
                max-height: none;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>📊 Dashboard Harga Hotel & Kompetitor</h1>
    </header>

    <main>
        <?php if (empty($properties)) : ?>
            <p class="status-message status-error">Data properti tidak ditemukan.</p>
        <?php else : ?>
            <div class="dashboard">
                <aside class="sidebar" role="navigation" aria-label="Daftar Properti">
                    <h2>Properti</h2>
                    <?php foreach ($properties as $i => $property) : ?>
                        <div tabindex="0" class="property-item <?= $i === 0 ? 'active' : '' ?>" data-property="<?= htmlspecialchars($property) ?>">
                            <?= htmlspecialchars($property) ?>
                        </div>
                    <?php endforeach; ?>
                </aside>

                <section class="content" aria-live="polite" aria-atomic="true">
                    <h3 id="contentTitle">Harga untuk properti: <span id="selectedProperty"><?= htmlspecialchars($properties[0]) ?></span></h3>

                    <label for="filterOta">Filter OTA:</label>
                    <select id="filterOta" aria-controls="hargaTable" aria-label="Filter OTA">
                        <?php foreach ($otas as $ota) : ?>
                            <option value="<?= htmlspecialchars($ota) ?>"><?= htmlspecialchars($ota) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div id="hargaWrapper" style="overflow-x:auto;">
                        <table id="hargaTable" role="grid" aria-describedby="contentTitle" class="min-w-full">
                            <thead>

                            </thead>
                            <tbody id="hargaBody">
                                <tr>
                                    <td colspan="3" class="status-message status-loading">Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </main>

    <footer class="text-center text-sm py-6 text-gray-500 select-none">
        &copy; <?= date('Y') ?> Rate plan System by Sinergi 
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.6/flowbite.min.js"></script>
    <script>
        (() => {
            const propertyItems = document.querySelectorAll('.property-item');
            const selectedPropertySpan = document.getElementById('selectedProperty');
            const filterOtaSelect = document.getElementById('filterOta');
            const hargaBody = document.getElementById('hargaBody');

            let currentProperty = selectedPropertySpan.textContent;
            let currentOta = 'all';

            function setActiveProperty(newActiveElem) {
                propertyItems.forEach(el => el.classList.remove('active'));
                newActiveElem.classList.add('active');
                currentProperty = newActiveElem.getAttribute('data-property');
                selectedPropertySpan.textContent = currentProperty;
                filterOtaSelect.value = 'all';
                currentOta = 'all';
                loadHarga(currentProperty, currentOta);
            }

            async function loadHarga(property, ota) {
                hargaBody.innerHTML = `<tr><td colspan="3" class="status-message status-loading">Memuat data...</td></tr>`;
                try {
                    const response = await fetch(`get_harga.php?property=${encodeURIComponent(property)}&ota=${encodeURIComponent(ota)}`);
                    if (!response.ok) throw new Error('Network response not ok');
                    const html = await response.text();

                    if (html.trim() === '') {
                        hargaBody.innerHTML = `<tr><td colspan="3" class="status-message status-error">Tidak ada data harga.</td></tr>`;
                    } else {
                        hargaBody.innerHTML = html;
                    }
                } catch (error) {
                    hargaBody.innerHTML = `<tr><td colspan="3" class="status-message status-error">Gagal memuat data. Coba muat ulang.</td></tr>`;
                    console.error(error);
                }
            }

            // Setup property clicks
            propertyItems.forEach(item => {
                item.addEventListener('click', () => setActiveProperty(item));
                item.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        setActiveProperty(item);
                    }
                });
            });

            // Setup OTA filter change
            filterOtaSelect.addEventListener('change', (e) => {
                currentOta = e.target.value;
                loadHarga(currentProperty, currentOta);
            });

            // Load initial data
            loadHarga(currentProperty, currentOta);
        })();
    </script>
</body>

</html>