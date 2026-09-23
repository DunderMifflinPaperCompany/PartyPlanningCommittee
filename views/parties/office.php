<?php

use PartyPlanningCommittee\Http\View;

/**
 * Belsnickel inspects a single branch.
 *
 * @var PartyPlanningCommittee\Model\Office      $office
 * @var list<PartyPlanningCommittee\Model\Party> $parties
 * @var array<int, int>                          $attendance
 */
?>
<h2><?= View::e($office->label()) ?></h2>
<p class="capacity">Party room capacity: <?= View::e((string) $office->capacity()) ?> souls. Overcrowding is impish.</p>

<?php if ($parties === []): ?>
    <p class="empty">Belsnickel finds no parties here. A branch without celebration is admirable only to accountants.</p>
<?php else: ?>
    <ul class="party-list">
        <?php foreach ($parties as $party): ?>
            <li class="status-<?= View::e($party->status()) ?>">
                <a href="/parties/<?= View::e((string) $party->id()) ?>"><?= View::e($party->title()) ?></a>
                <span class="when"><?= View::e($party->scheduledFor()->format('M j, Y g:ia')) ?></span>
                <span class="theme"><?= View::e($party->theme()) ?></span>
                <span class="attending"><?= View::e((string) ($attendance[$party->id()] ?? 0)) ?> attending</span>
                <span class="status"><?= View::e($party->status()) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<p><a href="/parties/new">Propose a party for <?= View::e($office->name()) ?></a></p>
