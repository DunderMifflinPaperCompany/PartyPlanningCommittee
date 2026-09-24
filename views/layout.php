<?php

use PartyPlanningCommittee\Http\View;

/**
 * Belsnickel's layout. Every value is escaped here, without exception.
 *
 * @var string                                        $title
 * @var string                                        $content
 * @var list<PartyPlanningCommittee\Model\Office>     $offices
 */
$offices = $offices ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'Party Planning Committee') ?> | Dunder Mifflin PPC</title>
    <link rel="stylesheet" href="/assets/style.css">
    <!-- Admirable: behaviour lives in external, testable modules. Inline scripts are impish. -->
    <script type="module" src="/assets/js/main.js" defer></script>
</head>
<body>
<header class="masthead">
    <h1><a href="/">Dunder Mifflin Party Planning Committee</a></h1>
    <p class="tagline">Judged by Belsnickel: impish or admirable, never merry by default.</p>
    <nav>
        <a href="/">All parties</a>
        <a href="/upcoming">Upcoming</a>
        <a href="/calendar">Calendar</a>
        <a href="/parties/new">Propose a party</a>
        <?php foreach ($offices as $office): ?>
            <a href="/offices/<?= View::e($office->slug()) ?>"><?= View::e($office->name()) ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main>
    <?= $content ?? '' ?>
</main>
<footer>
    <p>Belsnickel is always watching. Choose: impish or admirable.</p>
</footer>
</body>
</html>
