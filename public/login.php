<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';

startSession();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Podaj login i hasło.';
    } else {
        $db = getDb();

        $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;

            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Błędny login lub hasło.';
        }
    }
}

require __DIR__ . '/../app/header.php';
?>

<h2>Logowanie organizatora</h2>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><b><?= htmlspecialchars($error) ?></b></div>
<?php endif; ?>


<form method="post">
    <div class="form-row">
        <div class="field">
            <label>Login:</label>
            <input type="text" name="username" required>
        </div>

        <div class="field">
            <label>Hasło:</label>
            <input type="password" name="password" required>
        </div>

        <div class="field">
            <button class="btn btn-primary" type="submit">Zaloguj</button>
        </div>
    </div>
</form>


<p class="small">Test: login <b>admin</b>, hasło <b>admin123</b></p>

<?php require __DIR__ . '/../app/footer.php'; ?>