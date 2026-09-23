<?php

use PartyPlanningCommittee\Http\View;
use PartyPlanningCommittee\Model\Rsvp;

/**
 * Belsnickel's published page: the one page an attendee may see. Admirable, because it
 * shows the celebration and the refreshment pledges, and nothing the committee keeps private.
 *
 * @var PartyPlanningCommittee\Model\Party       $party
 * @var PartyPlanningCommittee\Model\Office|null $office
 * @var list<PartyPlanningCommittee\Model\Rsvp>  $rsvps
 * @var list<PartyPlanningCommittee\Model\Rsvp>  $refreshments
 * @var int                                      $attending
 * @var bool                                     $overCapacity
 * @var string                                   $csrfToken
 */
?>
<article class="party invite status-<?= View::e($party->status()) ?>">
    <h2>You are invited: <?= View::e($party->title()) ?></h2>

    <?php if ($party->isCancelled()): ?>
        <p class="warning">Impish news: this party was cancelled. Belsnickel warms nobody today.</p>
    <?php endif; ?>

    <dl>
        <dt>Branch</dt>
        <dd><?= View::e($office !== null ? $office->label() : ucfirst($party->officeSlug())) ?></dd>
        <dt>Theme</dt>
        <dd><?= View::e($party->theme()) ?></dd>
        <dt>When</dt>
        <dd><?= View::e($party->scheduledFor()->format('l, F j, Y \a\t g:ia')) ?></dd>
        <dt>Organizer</dt>
        <dd><?= View::e($party->organizer()) ?></dd>
        <dt>Firmly attending</dt>
        <dd><?= View::e((string) $attending) ?></dd>
    </dl>

    <?php if ($overCapacity): ?>
        <p class="warning">Impish! More guests than chairs. Belsnickel is displeased.</p>
    <?php endif; ?>
</article>

<section class="refreshments">
    <h3>Refreshment sign-up</h3>

    <?php if ($refreshments === []): ?>
        <p class="empty">Nobody has pledged a single crumb. Belsnickel is watching, and judging.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($refreshments as $refreshment): ?>
                <li class="rsvp-<?= View::e($refreshment->status()) ?>">
                    <?= View::e($refreshment->dish()) ?> &mdash; <?= View::e($refreshment->employeeName()) ?>
                    (<?= View::e($refreshment->status()) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="rsvps">
    <h3>RSVP and pledge a refreshment</h3>

    <?php if ($party->isCancelled()): ?>
        <p class="empty">A cancelled party takes no RSVPs. Belsnickel has closed the ledger.</p>
    <?php else: ?>
        <p>No RSVP, no entry. Belsnickel demands a name and an honest answer.</p>
        <form method="post" action="/parties/<?= View::e((string) $party->id()) ?>/rsvps">
            <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken) ?>">
            <input type="hidden" name="source" value="invite">
            <label>Name
                <input type="text" name="employee_name" maxlength="80" required>
            </label>
            <label>Answer
                <select name="status">
                    <?php foreach (Rsvp::STATUSES as $status): ?>
                        <option value="<?= View::e($status) ?>"><?= View::e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Refreshment you will bring
                <input type="text" name="dish" maxlength="80" placeholder="Beet salad, cookies, pretzels">
            </label>
            <button type="submit">Submit RSVP</button>
        </form>
    <?php endif; ?>

    <h3>Guest list</h3>

    <?php if ($rsvps === []): ?>
        <p class="empty">No RSVPs yet. Be the first, and be admirable about it.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($rsvps as $rsvp): ?>
                <li class="rsvp-<?= View::e($rsvp->status()) ?>">
                    <?= View::e($rsvp->employeeName()) ?> &mdash; <?= View::e($rsvp->status()) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
