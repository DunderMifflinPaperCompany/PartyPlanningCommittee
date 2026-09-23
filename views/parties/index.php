<?php

use PartyPlanningCommittee\Http\View;

/**
 * Belsnickel lists every party in the ledger.
 *
 * @var list<PartyPlanningCommittee\Model\Party> $parties
 * @var array<string, int>                       $counts
 * @var array<int, int>                          $attendance
 */
?>
<h2><?= View::e($title ?? 'Parties') ?></h2>

<?php if ($parties === []): ?>
    <p class="empty">No parties on the books. Belsnickel judges this branch impish and idle.</p>
<?php else: ?>
    <table class="parties">
        <thead>
        <tr>
            <th>Party</th>
            <th>Branch</th>
            <th>When</th>
            <th>Budget</th>
            <th>Attending</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($parties as $party): ?>
            <tr class="status-<?= View::e($party->status()) ?>">
                <td><a href="/parties/<?= View::e((string) $party->id()) ?>"><?= View::e($party->title()) ?></a></td>
                <td><a href="/offices/<?= View::e($party->officeSlug()) ?>"><?= View::e(ucfirst($party->officeSlug())) ?></a></td>
                <td><?= View::e($party->scheduledFor()->format('D, M j Y g:ia')) ?></td>
                <td><?= View::e($party->formattedBudget()) ?></td>
                <td><?= View::e((string) ($attendance[$party->id()] ?? 0)) ?></td>
                <td><?= View::e($party->status()) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (!empty($counts)): ?>
    <h3>Parties per branch, as counted by Belsnickel</h3>
    <ul class="counts">
        <?php foreach ($counts as $slug => $total): ?>
            <li><?= View::e(ucfirst((string) $slug)) ?>: <?= View::e((string) $total) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
