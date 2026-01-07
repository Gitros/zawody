<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

requireLogin();

$db = getDb();

/*
  Ten plik ogarnia:
  - wyświetlanie listy zawodników
  - dodawanie
  - edycję
  - usuwanie
  - wyszukiwanie
*/

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$error = '';
$success = '';

$search = trim($_GET['search'] ?? '');

// Dane do formularza 
$editMode = false;
$formImie = '';
$formNazwisko = '';

/* USUWANIE */
if ($action === 'delete' && $id > 0) {
    // Proste zabezpieczenie: usuwanie tylko przez zalogowanego
    $stmt = $db->prepare("DELETE FROM zawodnik WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: athletes.php?msg=deleted");
    exit;
}

/* WYCZYTANIE DANYCH DO EDYCJI */
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT id, imie, nazwisko FROM zawodnik WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        $editMode = true;
        $formImie = $row['imie'];
        $formNazwisko = $row['nazwisko'];
    } else {
        $error = "Nie znaleziono zawodnika.";
    }
}

/* ZAPIS (DODAJ / EDYTUJ) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie = trim($_POST['imie'] ?? '');
    $nazwisko = trim($_POST['nazwisko'] ?? '');
    $postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    // Prosta walidacja
    if ($imie === '' || $nazwisko === '') {
        $error = "Imię i nazwisko są wymagane.";
    } elseif (mb_strlen($imie) > 50) {
        $error = "Imię jest za długie (max 50 znaków).";
    } elseif (mb_strlen($nazwisko) > 80) {
        $error = "Nazwisko jest za długie (max 80 znaków).";
    } else {
        if ($postId > 0) {
            // EDYCJA
            $stmt = $db->prepare("UPDATE zawodnik SET imie = ?, nazwisko = ? WHERE id = ?");
            $stmt->execute([$imie, $nazwisko, $postId]);

            header("Location: athletes.php?msg=updated");
            exit;
        } else {
            // DODAWANIE
            $stmt = $db->prepare("INSERT INTO zawodnik (imie, nazwisko) VALUES (?, ?)");
            $stmt->execute([$imie, $nazwisko]);

            header("Location: athletes.php?msg=added");
            exit;
        }
    }

    // Jeśli walidacja nie przeszła, to pokaż formularz z tym co user wpisał
    $formImie = $imie;
    $formNazwisko = $nazwisko;
    $editMode = ($postId > 0);
    $id = $postId;
}

/* WIADOMOŚCI PO PRZEKIEROWANIU */
$msg = $_GET['msg'] ?? '';
if ($msg === 'added')
    $success = "Dodano zawodnika.";
if ($msg === 'updated')
    $success = "Zapisano zmiany.";
if ($msg === 'deleted')
    $success = "Usunięto zawodnika.";

/* POBIERANIE LISTY */
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $db->prepare("
        SELECT id, imie, nazwisko
        FROM zawodnik
        WHERE imie LIKE ? OR nazwisko LIKE ?
        ORDER BY nazwisko, imie
    ");
    $stmt->execute([$like, $like]);
    $athletes = $stmt->fetchAll();
} else {
    $stmt = $db->query("
        SELECT id, imie, nazwisko
        FROM zawodnik
        ORDER BY nazwisko, imie
    ");
    $athletes = $stmt->fetchAll();
}

require __DIR__ . '/../app/header.php';
?>

<h2>Zawodnicy</h2>

<?php if ($success !== ''): ?>
    <div class="alert alert-success"><b><?= htmlspecialchars($success) ?></b></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><b><?= htmlspecialchars($error) ?></b></div>
<?php endif; ?>


<form method="get" class="toolbar">
    <input type="text" name="search" placeholder="Szukaj po imieniu lub nazwisku..."
        value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary">Szukaj</button>
    <a href="athletes.php" class="btn btn-secondary">Wyczyść</a>
</form>


<hr style="margin: 15px 0;">

<h3>
    <?= $editMode ? 'Edytuj zawodnika' : 'Dodaj zawodnika' ?>
</h3>
<form method="post">
    <?php if ($editMode): ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="field">
            <label>Imię:</label>
            <input type="text" name="imie" value="<?= htmlspecialchars($formImie) ?>" maxlength="50">
        </div>

        <div class="field">
            <label>Nazwisko:</label>
            <input type="text" name="nazwisko" value="<?= htmlspecialchars($formNazwisko) ?>" maxlength="80">
        </div>

        <div class="field">
            <button type="submit" class="btn btn-primary">
                <?= $editMode ? 'Zapisz' : 'Dodaj' ?>
            </button>

            <?php if ($editMode): ?>
                <a href="athletes.php" class="btn btn-secondary">Anuluj</a>
            <?php endif; ?>
        </div>
    </div>
</form>


<h3>Lista zawodników (
    <?= count($athletes) ?>)
</h3>

<table class="table">
    <tr>
        <th>ID</th>
        <th>Imię</th>
        <th>Nazwisko</th>
        <th>Akcje</th>
    </tr>

    <?php if (count($athletes) === 0): ?>
        <tr>
            <td colspan="4">Brak wyników.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($athletes as $a): ?>
            <tr>
                <td>
                    <?= (int) $a['id'] ?>
                </td>
                <td>
                    <?= htmlspecialchars($a['imie']) ?>
                </td>
                <td>
                    <?= htmlspecialchars($a['nazwisko']) ?>
                </td>
                <td>
                    <div class="actions">
                        <a class="btn btn-secondary" href="athletes.php?action=edit&id=<?= (int) $a['id'] ?>">Edytuj</a>
                        <a class="btn btn-danger" href="athletes.php?action=delete&id=<?= (int) $a['id'] ?>"
                            onclick="return confirm('Na pewno usunąć?');">Usuń</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

<?php require __DIR__ . '/../app/footer.php'; ?>