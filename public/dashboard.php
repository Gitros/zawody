<?php
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/db.php';

requireLogin();
$db = getDb();

$athletesCount = (int) $db->query("SELECT COUNT(*) FROM zawodnik")->fetchColumn();
$eventsCount = (int) $db->query("SELECT COUNT(*) FROM konkurencja")->fetchColumn();
$resultsCount = (int) $db->query("SELECT COUNT(*) FROM wynik")->fetchColumn();

require __DIR__ . '/../app/header.php';
?>

<h2>Panel organizatora</h2>
<p class="small" style="margin-bottom: 14px;">
    Wybierz moduł poniżej, żeby zarządzać danymi. Na górze masz szybkie statystyki.
</p>

<div class="stats">
    <div class="stat">
        <div class="stat-label">Zawodnicy</div>
        <div class="stat-value"><?= $athletesCount ?></div>
    </div>
    <div class="stat">
        <div class="stat-label">Konkurencje</div>
        <div class="stat-value"><?= $eventsCount ?></div>
    </div>
    <div class="stat">
        <div class="stat-label">Wyniki</div>
        <div class="stat-value"><?= $resultsCount ?></div>
    </div>
</div>

<hr>

<div class="cards">
    <a class="card" href="athletes.php">
        <div class="card-title">Zawodnicy</div>
        <div class="card-desc">Dodawanie, edycja, usuwanie i wyszukiwanie zawodników.</div>
        <div class="card-meta">Przejdź →</div>
    </a>

    <a class="card" href="events.php">
        <div class="card-title">Konkurencje</div>
        <div class="card-desc">Zarządzanie konkurencjami i ich nazwami.</div>
        <div class="card-meta">Przejdź →</div>
    </a>

    <a class="card" href="results.php">
        <div class="card-title">Wyniki</div>
        <div class="card-desc">Dodawanie wyników dla zawodników w konkurencjach + filtrowanie.</div>
        <div class="card-meta">Przejdź →</div>
    </a>

    <a class="card" href="ranking.php">
        <div class="card-title">Klasyfikacja</div>
        <div class="card-desc">Tabela rankingowa dla wybranej konkurencji.</div>
        <div class="card-meta">Przejdź →</div>
    </a>

    <a class="card" href="export.php">
        <div class="card-title">Eksport</div>
        <div class="card-desc">Generowanie raportu wyników do pliku tekstowego (.txt).</div>
        <div class="card-meta">Przejdź →</div>
    </a>
</div>

<?php
$lastResults = $db->query("
  SELECT
    w.data_wyniku,
    w.wartosc,
    z.imie,
    z.nazwisko,
    k.nazwa AS konkurencja
  FROM wynik w
  JOIN zawodnik z ON z.id = w.zawodnik_id
  JOIN konkurencja k ON k.id = w.konkurencja_id
  ORDER BY w.data_wyniku DESC, w.id DESC
  LIMIT 5
")->fetchAll();
?>

<hr>

<h3>Ostatnie wyniki</h3>

<table class="table">
    <tr>
        <th>Data</th>
        <th>Zawodnik</th>
        <th>Konkurencja</th>
        <th>Wartość</th>
    </tr>

    <?php if (count($lastResults) === 0): ?>
        <tr>
            <td colspan="4">Brak wyników.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($lastResults as $r): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($r['data_wyniku']) ?>
                </td>
                <td>
                    <?= htmlspecialchars($r['nazwisko'] . ' ' . $r['imie']) ?>
                </td>
                <td>
                    <?= htmlspecialchars($r['konkurencja']) ?>
                </td>
                <td>
                    <?= htmlspecialchars($r['wartosc']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>


<?php require __DIR__ . '/../app/footer.php'; ?>