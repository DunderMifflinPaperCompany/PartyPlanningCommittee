<?php

use PartyPlanningCommittee\Http\View;
use PartyPlanningCommittee\Model\Party;

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
    <!-- Impish without JavaScript? No: the filter stays hidden until the module unhides it. -->
    <form class="party-filter" data-party-filter hidden>
        <label>Search
            <input type="search" data-filter-query placeholder="Title or branch">
        </label>
        <label>Status
            <select data-filter-status>
                <option value="all">all</option>
                <?php foreach (Party::STATUSES as $status): ?>
                    <option value="<?= View::e($status) ?>"><?= View::e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>
    <p class="filter-summary" data-filter-summary></p>

    <table class="parties" data-party-table>
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
            <tr class="status-<?= View::e($party->status()) ?>"
                data-title="<?= View::e($party->title()) ?>"
                data-office="<?= View::e($party->officeSlug()) ?>"
                data-status="<?= View::e($party->status()) ?>">
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
