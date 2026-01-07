<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

requireLogin();

$db = getDb();

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$error = '';
$success = '';

/* POBRANIE LIST DO SELECTÓW */
$athletes = $db->query("SELECT id, imie, nazwisko FROM zawodnik ORDER BY nazwisko, imie")->fetchAll();
$events = $db->query("SELECT id, nazwa FROM konkurencja ORDER BY nazwa")->fetchAll();

/* FILTRY */
$filterAthleteId = isset($_GET['athlete_id']) ? (int) $_GET['athlete_id'] : 0;
$filterEventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;

/* FORM  */
$editMode = false;
$formAthleteId = 0;
$formEventId = 0;
$formWartosc = '';
$formData = date('Y-m-d');

/* USUWANIE */
if ($action === 'delete' && $id > 0) {
    $stmt = $db->prepare("DELETE FROM wynik WHERE id = ?");
    $stmt->execute([$id]);

    // zostaw filtry po usunięciu
    $qs = http_build_query([
        'msg' => 'deleted',
        'athlete_id' => $filterAthleteId,
        'event_id' => $filterEventId
    ]);
    header("Location: results.php?$qs");
    exit;
}

/* WCZYTANIE DO EDYCJI */
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT id, zawodnik_id, konkurencja_id, wartosc, data_wyniku FROM wynik WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        $editMode = true;
        $formAthleteId = (int) $row['zawodnik_id'];
        $formEventId = (int) $row['konkurencja_id'];
        $formWartosc = (string) $row['wartosc'];
        $formData = $row['data_wyniku'];
    } else {
        $error = "Nie znaleziono wyniku.";
    }
}

/* ZAPIS */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    $zawodnikId = isset($_POST['zawodnik_id']) ? (int) $_POST['zawodnik_id'] : 0;
    $konkurencjaId = isset($_POST['konkurencja_id']) ? (int) $_POST['konkurencja_id'] : 0;
    $wartosc = trim($_POST['wartosc'] ?? '');
    $data = $_POST['data_wyniku'] ?? '';

    if ($zawodnikId <= 0) {
        $error = "Wybierz zawodnika.";
    } elseif ($konkurencjaId <= 0) {
        $error = "Wybierz konkurencję.";
    } elseif ($wartosc === '') {
        $error = "Podaj wartość wyniku.";
    } elseif (!is_numeric(str_replace(',', '.', $wartosc))) {
        $error = "Wartość wyniku musi być liczbą (np. 12.34).";
    } elseif ($data === '') {
        $error = "Podaj datę wyniku.";
    } else {
        $wartoscDb = (float) str_replace(',', '.', $wartosc);

        if ($postId > 0) {
            $stmt = $db->prepare("
                UPDATE wynik
                SET zawodnik_id = ?, konkurencja_id = ?, wartosc = ?, data_wyniku = ?
                WHERE id = ?
            ");
            $stmt->execute([$zawodnikId, $konkurencjaId, $wartoscDb, $data, $postId]);

            $qs = http_build_query([
                'msg' => 'updated',
                'athlete_id' => $filterAthleteId,
                'event_id' => $filterEventId
            ]);
            header("Location: results.php?$qs");
            exit;
        } else {
            $stmt = $db->prepare("
                INSERT INTO wynik (zawodnik_id, konkurencja_id, wartosc, data_wyniku)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$zawodnikId, $konkurencjaId, $wartoscDb, $data]);

            $qs = http_build_query([
                'msg' => 'added',
                'athlete_id' => $filterAthleteId,
                'event_id' => $filterEventId
            ]);
            header("Location: results.php?$qs");
            exit;
        }
    }

    // jeśli błąd walidacji, to wypełnij formularz tym co user wpisał
    $editMode = ($postId > 0);
    $id = $postId;
    $formAthleteId = $zawodnikId;
    $formEventId = $konkurencjaId;
    $formWartosc = $wartosc;
    $formData = $data ?: date('Y-m-d');
}

/* WIADOMOŚCI */
$msg = $_GET['msg'] ?? '';
if ($msg === 'added')
    $success = "Dodano wynik.";
if ($msg === 'updated')
    $success = "Zapisano zmiany.";
if ($msg === 'deleted')
    $success = "Usunięto wynik.";

/* POBIERANIE LISTY WYNIKÓW */
$sql = "
    SELECT
      w.id,
      w.wartosc,
      w.data_wyniku,
      z.id AS zawodnik_id,
      z.imie,
      z.nazwisko,
      k.id AS konkurencja_id,
      k.nazwa AS konkurencja_nazwa
    FROM wynik w
    JOIN zawodnik z ON z.id = w.zawodnik_id
    JOIN konkurencja k ON k.id = w.konkurencja_id
    WHERE 1=1
";

$params = [];

if ($filterAthleteId > 0) {
    $sql .= " AND w.zawodnik_id = ?";
    $params[] = $filterAthleteId;
}
if ($filterEventId > 0) {
    $sql .= " AND w.konkurencja_id = ?";
    $params[] = $filterEventId;
}

$sql .= " ORDER BY w.data_wyniku DESC, k.nazwa, z.nazwisko, z.imie";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

require __DIR__ . '/../app/header.php';
?>

<h2>Wyniki</h2>

<?php if (count($athletes) === 0 || count($events) === 0): ?>
    <div class="alert alert-error">
        <b>Brak danych do wyboru.</b><br>
        Dodaj najpierw przynajmniej jednego zawodnika i jedną konkurencję.
    </div>
<?php endif; ?>

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

<form method="get" class="toolbar">
    <select name="athlete_id">
        <option value="0">— wszyscy zawodnicy —</option>
        <?php foreach ($athletes as $a): ?>
            <?php
            $aid = (int) $a['id'];
            $label = $a['nazwisko'] . ' ' . $a['imie'];
            ?>
            <option value="<?= $aid ?>" <?= $filterAthleteId === $aid ? 'selected' : '' ?>>
                <?= htmlspecialchars($label) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="event_id">
        <option value="0">— wszystkie konkurencje —</option>
        <?php foreach ($events as $e): ?>
            <?php $eid = (int) $e['id']; ?>
            <option value="<?= $eid ?>" <?= $filterEventId === $eid ? 'selected' : '' ?>>
                <?= htmlspecialchars($e['nazwa']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button class="btn btn-primary" type="submit">Filtruj</button>
    <a class="btn btn-secondary" href="results.php">Wyczyść</a>
</form>

<hr>

<h3>
    <?= $editMode ? 'Edytuj wynik' : 'Dodaj wynik' ?>
</h3>

<form method="post">
    <?php if ($editMode): ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="field">
            <label>Zawodnik:</label>
            <select name="zawodnik_id">
                <option value="0">— wybierz —</option>
                <?php foreach ($athletes as $a): ?>
                    <?php
                    $aid = (int) $a['id'];
                    $label = $a['nazwisko'] . ' ' . $a['imie'];
                    ?>
                    <option value="<?= $aid ?>" <?= $formAthleteId === $aid ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Konkurencja:</label>
            <select name="konkurencja_id">
                <option value="0">— wybierz —</option>
                <?php foreach ($events as $e): ?>
                    <?php $eid = (int) $e['id']; ?>
                    <option value="<?= $eid ?>" <?= $formEventId === $eid ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['nazwa']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Wartość:</label>
            <input type="text" name="wartosc" placeholder="np. 12.34" value="<?= htmlspecialchars($formWartosc) ?>">
        </div>

        <div class="field">
            <label>Data:</label>
            <input type="date" name="data_wyniku" value="<?= htmlspecialchars($formData) ?>">
        </div>

        <div class="field">
            <button type="submit" class="btn btn-primary">
                <?= $editMode ? 'Zapisz' : 'Dodaj' ?>
            </button>

            <?php if ($editMode): ?>
                <a href="results.php?<?= htmlspecialchars(http_build_query(['athlete_id' => $filterAthleteId, 'event_id' => $filterEventId])) ?>"
                    class="btn btn-secondary">Anuluj</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<h3>Lista wyników (
    <?= count($results) ?>)
</h3>

<table class="table">
    <tr>
        <th>ID</th>
        <th>Zawodnik</th>
        <th>Konkurencja</th>
        <th>Wartość</th>
        <th>Data</th>
        <th>Akcje</th>
    </tr>

    <?php if (count($results) === 0): ?>
        <tr>
            <td colspan="6">Brak wyników.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($results as $r): ?>
            <tr>
                <td>
                    <?= (int) $r['id'] ?>
                </td>
                <td>
                    <?= htmlspecialchars($r['nazwisko'] . ' ' . $r['imie']) ?>
                </td>
                <td>
                    <?= htmlspecialchars($r['konkurencja_nazwa']) ?>
                </td>
                <td>
                    <?= htmlspecialchars($r['wartosc']) ?>
                </td>
                <td>
                    <?= htmlspecialchars($r['data_wyniku']) ?>
                </td>
                <td>
                    <div class="actions">
                        <a class="btn btn-secondary"
                            href="results.php?action=edit&id=<?= (int) $r['id'] ?>&<?= htmlspecialchars(http_build_query(['athlete_id' => $filterAthleteId, 'event_id' => $filterEventId])) ?>">
                            Edytuj
                        </a>
                        <a class="btn btn-danger"
                            href="results.php?action=delete&id=<?= (int) $r['id'] ?>&<?= htmlspecialchars(http_build_query(['athlete_id' => $filterAthleteId, 'event_id' => $filterEventId])) ?>"
                            onclick="return confirm('Na pewno usunąć?');">
                            Usuń
                        </a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

<?php require __DIR__ . '/../app/footer.php'; ?>