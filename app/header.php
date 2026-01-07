<?php
require_once __DIR__ . '/auth.php';
startSession();
?>
<!doctype html>
<html lang="pl">

<head>
    <meta charset="utf-8">
    <title>Zawody sportowe</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <nav class="navbar">
        <div class="nav-left">
            <span class="logo">Zawody sportowe</span>
        </div>

        <?php if (isLoggedIn()): ?>
            <div class="nav-right">
                <a href="dashboard.php">Panel</a>
                <a href="athletes.php">Zawodnicy</a>
                <a href="events.php">Konkurencje</a>
                <a href="results.php">Wyniki</a>
                <a href="ranking.php">Klasyfikacja</a>
                <a href="export.php">Eksport</a>
                <a href="logout.php" class="logout">Wyloguj</a>
            </div>
        <?php endif; ?>
    </nav>

    <?php if (isLoggedIn()): ?>
        <section class="hero">
            <div class="hero-overlay">
                <h1>Witamy w systemie zawodów sportowych</h1>
                <p>Zarządzaj zawodnikami, konkurencjami i wynikami w jednym miejscu</p>
            </div>
        </section>
    <?php endif; ?>

    <div class="content">