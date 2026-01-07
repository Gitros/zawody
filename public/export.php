<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

requireLogin();
$db = getDb();

// gdzie zapisujemy pliki
$config = require __DIR__ . '/../app/config.php';
$exportsDir = __DIR__ . '/../storage/exports';

// jeśli folder nie istnieje - spróbuj utworzyć
if (!is_dir($exportsDir)) {
    @mkdir($exportsDir, 0777, true);
}

$error = '';
$success = '';
$downloadLink = '';

$events = $db->query("SELECT id, nazwa FROM konkurencja ORDER BY nazwa")->fetchAll();

/* GENEROWANIE PLIKU */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventIdPost = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;

    // pobranie nazwy konkurencji (jeśli wybrano)
    $eventName = '';
    if ($eventIdPost > 0) {
        $stmt = $db->prepare("SELECT nazwa FROM konkurencja WHERE id = ?");
        $stmt->execute([$eventIdPost]);
        $row = $stmt->fetch();
        if (!$row) {
            $error = "Nie znaleziono konkurencji.";
        } else {
            $eventName = $row['nazwa'];
        }
    }

    // jeśli nie było błędu, pobierz dane do raportu
    if ($error === '') {
        $sql = "
            SELECT
              w.id,
              w.wartosc,
              w.data_wyniku,
              z.imie,
              z.nazwisko,
              k.nazwa AS konkurencja
            FROM wynik w
            JOIN zawodnik z ON z.id = w.zawodnik_id
            JOIN konkurencja k ON k.id = w.konkurencja_id
        ";

        $params = [];
        if ($eventIdPost > 0) {
            $sql .= " WHERE w.konkurencja_id = ?";
            $params[] = $eventIdPost;
        }

        $sql .= " ORDER BY k.nazwa, w.data_wyniku DESC, z.nazwisko, z.imie";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if (count($rows) === 0) {
            $error = "Brak wyników do eksportu.";
        } else {
            // przygotuj zawartość pliku
            $now = date('Y-m-d H:i:s');
            $lines = [];

            $lines[] = "RAPORT WYNIKÓW";
            $lines[] = "Wygenerowano: $now";

            if ($eventIdPost > 0) {
                $lines[] = "Konkurencja: " . $eventName;
            } else {
                $lines[] = "Konkurencja: (wszystkie)";
            }

            $lines[] = str_repeat("-", 50);

            // format wierszy
            $lp = 1;
            foreach ($rows as $r) {
                $zawodnik = $r['nazwisko'] . ' ' . $r['imie'];
                $konk = $r['konkurencja'];
                $wartosc = $r['wartosc'];
                $data = $r['data_wyniku'];

                $lines[] = $lp . ". " . $zawodnik . " | " . $konk . " | " . $wartosc . " | " . $data;
                $lp++;
            }

            $content = implode(PHP_EOL, $lines) . PHP_EOL;

            // nazwa pliku (bez polskich znaków w nazwie)
            $safePart = ($eventIdPost > 0) ? ('konkurencja_' . $eventIdPost) : 'wszystkie';
            $fileName = "raport_" . $safePart . "_" . date('Ymd_His') . ".txt";
            $filePath = $exportsDir . '/' . $fileName;

            // zapis pliku
            $ok = file_put_contents($filePath, $content);

            if ($ok === false) {
                $error = "Nie udało się zapisać pliku. Sprawdź uprawnienia folderu storage/exports.";
            } else {
                $success = "Wygenerowano raport: $fileName";
                // link do pobrania przez ten sam plik
                $downloadLink = "export.php?download=" . urlencode($fileName);
            }
        }
    }
}

/* POBIERANIE PLIKU  */
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $path = $exportsDir . '/' . $file;

    if (!is_file($path)) {
        die("Plik nie istnieje.");
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    header('Content-Length: ' . filesize($path));

    readfile($path);
    exit;
}

require __DIR__ . '/../app/header.php';
?>

<h2>Eksport wyników</h2>

<?php if ($success !== ''): ?>
    <div class="alert alert-success"><b>
            <?= htmlspecialchars($success) ?>
        </b></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><b>
            <?= htmlspecialchars($error) ?>
        </b></div>
<?php endif; ?>

<form method="post" class="toolbar">
    <select name="event_id">
        <option value="0">— wszystkie konkurencje —</option>
        <?php foreach ($events as $e): ?>
            <?php $eid = (int) $e['id']; ?>
            <option value="<?= $eid ?>">
                <?= htmlspecialchars($e['nazwa']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-primary">Generuj raport .txt</button>
</form>

<?php if ($downloadLink !== ''): ?>
    <p style="margin-top: 12px;">
        <a class="btn btn-secondary" href="<?= htmlspecialchars($downloadLink) ?>">Pobierz raport</a>
    </p>
<?php endif; ?>

<p class="small" style="margin-top: 10px;">
    Pliki są zapisywane w folderze: <b>storage/exports/</b>
</p>

<?php require __DIR__ . '/../app/footer.php'; ?>