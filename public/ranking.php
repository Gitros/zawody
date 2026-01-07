<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

requireLogin();
$db = getDb();

$error = '';

$events = $db->query("SELECT id, nazwa FROM konkurencja ORDER BY nazwa")->fetchAll();

$eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$orderMode = $_GET['mode'] ?? 'asc';
if ($orderMode !== 'asc' && $orderMode !== 'desc')
    $orderMode = 'asc';

$ranking = [];

if ($eventId > 0) {
    // UWAGA: W tej wersji bierzemy najlepszy wynik zawodnika w danej konkurencji.
    // Dla asc -> MIN(wartosc)
    // Dla desc -> MAX(wartosc)
    $agg = ($orderMode === 'asc') ? 'MIN' : 'MAX';
    $orderSql = ($orderMode === 'asc') ? 'ASC' : 'DESC';

    $sql = "
        SELECT
          z.id AS zawodnik_id,
          z.imie,
          z.nazwisko,
          $agg(w.wartosc) AS najlepszy_wynik
        FROM wynik w
        JOIN zawodnik z ON z.id = w.zawodnik_id
        WHERE w.konkurencja_id = ?
        GROUP BY z.id, z.imie, z.nazwisko
        ORDER BY najlepszy_wynik $orderSql, z.nazwisko, z.imie
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$eventId]);
    $ranking = $stmt->fetchAll();
}

require __DIR__ . '/../app/header.php';
?>

<h2>Klasyfikacja</h2>

<?php if (count($events) === 0): ?>
    <div class="alert alert-error">
        <b>Brak konkurencji.</b> Dodaj konkurencje, aby wyświetlić klasyfikację.
    </div>
<?php endif; ?>

<form method="get" class="toolbar">
    <select name="event_id">
        <option value="0">— wybierz konkurencję —</option>
        <?php foreach ($events as $e): ?>
            <?php $eid = (int) $e['id']; ?>
            <option value="<?= $eid ?>" <?= $eid === $eventId ? 'selected' : '' ?>>
                <?= htmlspecialchars($e['nazwa']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label style="display:flex; gap:8px; align-items:center;">
        <input type="radio" name="mode" value="asc" <?= $orderMode === 'asc' ? 'checked' : '' ?>>
        mniejszy wynik lepszy
    </label>

    <label style="display:flex; gap:8px; align-items:center;">
        <input type="radio" name="mode" value="desc" <?= $orderMode === 'desc' ? 'checked' : '' ?>>
        większy wynik lepszy
    </label>

    <button type="submit" class="btn btn-primary">Pokaż</button>
    <a href="ranking.php" class="btn btn-secondary">Wyczyść</a>
</form>

<hr>

<?php if ($eventId === 0): ?>
    <p>Wybierz konkurencję, aby zobaczyć klasyfikację.</p>
<?php else: ?>

    <h3>Wyniki w wybranej konkurencji (<?= count($ranking) ?>)</h3>

    <table class="table">
        <tr>
            <th>Miejsce</th>
            <th>Zawodnik</th>
            <th>Najlepszy wynik</th>
        </tr>

        <?php if (count($ranking) === 0): ?>
            <tr>
                <td colspan="3">Brak wyników dla tej konkurencji.</td>
            </tr>
        <?php else: ?>
            <?php $place = 1; ?>
            <?php foreach ($ranking as $r): ?>
                <tr>
                    <td><?= $place ?></td>
                    <td><?= htmlspecialchars($r['nazwisko'] . ' ' . $r['imie']) ?></td>
                    <td><?= htmlspecialchars($r['najlepszy_wynik']) ?></td>
                </tr>
                <?php $place++; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <p class="small" style="margin-top:10px;">
        Klasyfikacja pokazuje najlepszy wynik każdego zawodnika w tej konkurencji.
    </p>

<?php endif; ?>

<?php require __DIR__ . '/../app/footer.php'; ?>