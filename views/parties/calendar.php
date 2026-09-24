<?php

use PartyPlanningCommittee\Http\View;

/**
 * Belsnickel's calendar. The grid and the downloads are drawn by the browser, but the
 * list below is rendered by the server, so a reader without JavaScript is never left
 * staring at an impish blank wall.
 *
 * @var list<array<string, mixed>> $events
 * @var string                     $eventsJson
 */
$events = $events ?? [];
$eventsJson = $eventsJson ?? '[]';
?>
<h2><?= View::e($title ?? 'Calendar') ?></h2>

<?php if ($events === []): ?>
    <p class="empty">An empty calendar. Belsnickel judges this committee impish and idle.</p>
<?php else: ?>
    <!-- Admirable: the data is handed over once, hex-escaped, never re-parsed out of the markup. -->
    <script type="application/json" data-calendar-events><?= $eventsJson ?></script>

    <section class="calendar" data-calendar hidden>
        <header class="calendar-toolbar">
            <button type="button" data-calendar-prev>&laquo; Previous</button>
            <h3 data-calendar-heading></h3>
            <button type="button" data-calendar-next>Next &raquo;</button>
        </header>
        <table class="calendar-grid" data-calendar-grid>
            <thead>
            <tr>
                <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
        <p class="calendar-summary" data-calendar-summary></p>
    </section>

    <!-- Impish to offer a download that cannot work: the buttons stay hidden until the module binds them. -->
    <section class="calendar-export" data-calendar-export hidden>
        <h3>Take the calendar with you</h3>
        <p>Belsnickel permits three formats. Choose, and be judged.</p>
        <button type="button" data-calendar-download="ics">Download .ics</button>
        <button type="button" data-calendar-download="csv">Download .csv</button>
        <button type="button" data-calendar-download="json">Download .json</button>
        <p class="calendar-export-summary" data-calendar-export-summary></p>
    </section>

    <h3>Every date, as the server sees it</h3>
    <ul class="calendar-fallback">
        <?php foreach ($events as $event): ?>
            <li class="status-<?= View::e((string) $event['status']) ?>">
                <time datetime="<?= View::e((string) $event['start']) ?>">
                    <?= View::e((string) $event['date']) ?> <?= View::e((string) $event['time']) ?>
                </time>
                &mdash;
                <a href="<?= View::e((string) $event['url']) ?>"><?= View::e((string) $event['title']) ?></a>
                (<?= View::e((string) $event['location']) ?>, <?= View::e((string) $event['status']) ?>)
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
