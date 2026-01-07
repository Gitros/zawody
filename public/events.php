<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

requireLogin();

$db = getDb();

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$error = '';
$success = '';

$search = trim($_GET['search'] ?? '');

// Formularz (dla edycji)
$editMode = false;
$formNazwa = '';

/* USUWANIE */
if ($action === 'delete' && $id > 0) {
    $stmt = $db->prepare("DELETE FROM konkurencja WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: events.php?msg=deleted");
    exit;
}

/* WCZYTANIE DO EDYCJI*/
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT id, nazwa FROM konkurencja WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        $editMode = true;
        $formNazwa = $row['nazwa'];
    } else {
        $error = "Nie znaleziono konkurencji.";
    }
}

/* ZAPIS  */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nazwa = trim($_POST['nazwa'] ?? '');
    $postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    // Prosta walidacja
    if ($nazwa === '') {
        $error = "Nazwa konkurencji jest wymagana.";
    } elseif (mb_strlen($nazwa) > 100) {
        $error = "Nazwa jest za długa (max 100 znaków).";
    } else {
        if ($postId > 0) {
            $stmt = $db->prepare("UPDATE konkurencja SET nazwa = ? WHERE id = ?");
            $stmt->execute([$nazwa, $postId]);

            header("Location: events.php?msg=updated");
            exit;
        } else {
            $stmt = $db->prepare("INSERT INTO konkurencja (nazwa) VALUES (?)");
            $stmt->execute([$nazwa]);

            header("Location: events.php?msg=added");
            exit;
        }
    }

    // jeśli walidacja nie przeszła, pokaż to co wpisano
    $formNazwa = $nazwa;
    $editMode = ($postId > 0);
    $id = $postId;
}

/* WIADOMOŚCI */
$msg = $_GET['msg'] ?? '';
if ($msg === 'added')
    $success = "Dodano konkurencję.";
if ($msg === 'updated')
    $success = "Zapisano zmiany.";
if ($msg === 'deleted')
    $success = "Usunięto konkurencję.";

/* LISTA */
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $db->prepare("
        SELECT id, nazwa
        FROM konkurencja
        WHERE nazwa LIKE ?
        ORDER BY nazwa
    ");
    $stmt->execute([$like]);
    $events = $stmt->fetchAll();
} else {
    $stmt = $db->query("
        SELECT id, nazwa
        FROM konkurencja
        ORDER BY nazwa
    ");
    $events = $stmt->fetchAll();
}

require __DIR__ . '/../app/header.php';
?>

<h2>Konkurencje</h2>

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
    <input type="text" name="search" placeholder="Szukaj po nazwie konkurencji..."
        value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary">Szukaj</button>
    <a href="events.php" class="btn btn-secondary">Wyczyść</a>
</form>

<hr>

<h3>
    <?= $editMode ? 'Edytuj konkurencję' : 'Dodaj konkurencję' ?>
</h3>

<form method="post">
    <?php if ($editMode): ?>
        <input type="hidden" name="id" value="<?= (int) $id ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="field">
            <label>Nazwa:</label>
            <input type="text" name="nazwa" value="<?= htmlspecialchars($formNazwa) ?>" maxlength="100">
        </div>

        <div class="field">
            <button type="submit" class="btn btn-primary">
                <?= $editMode ? 'Zapisz' : 'Dodaj' ?>
            </button>

            <?php if ($editMode): ?>
                <a href="events.php" class="btn btn-secondary">Anuluj</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<h3>Lista konkurencji (
    <?= count($events) ?>)
</h3>

<table class="table">
    <tr>
        <th>ID</th>
        <th>Nazwa</th>
        <th>Akcje</th>
    </tr>

    <?php if (count($events) === 0): ?>
        <tr>
            <td colspan="3">Brak wyników.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($events as $e): ?>
            <tr>
                <td>
                    <?= (int) $e['id'] ?>
                </td>
                <td>
                    <?= htmlspecialchars($e['nazwa']) ?>
                </td>
                <td>
                    <div class="actions">
                        <a class="btn btn-secondary" href="events.php?action=edit&id=<?= (int) $e['id'] ?>">Edytuj</a>
                        <a class="btn btn-danger" href="events.php?action=delete&id=<?= (int) $e['id'] ?>"
                            onclick="return confirm('Na pewno usunąć?');">Usuń</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

<?php require __DIR__ . '/../app/footer.php'; ?>