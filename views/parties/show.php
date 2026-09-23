<?php

use PartyPlanningCommittee\Http\View;
use PartyPlanningCommittee\Model\Rsvp;

/**
 * Belsnickel opens one party to full inspection.
 *
 * @var PartyPlanningCommittee\Model\Party       $party
 * @var PartyPlanningCommittee\Model\Office|null $office
 * @var list<PartyPlanningCommittee\Model\Rsvp>  $rsvps
 * @var int                                      $attending
 * @var bool                                     $overCapacity
 * @var string                                   $csrfToken
 */
?>
<article class="party status-<?= View::e($party->status()) ?>">
    <h2><?= View::e($party->title()) ?></h2>
    <dl>
        <dt>Branch</dt>
        <dd><?= View::e($office !== null ? $office->label() : ucfirst($party->officeSlug())) ?></dd>
        <dt>Theme</dt>
        <dd><?= View::e($party->theme()) ?></dd>
        <dt>When</dt>
        <dd><?= View::e($party->scheduledFor()->format('l, F j, Y \a\t g:ia')) ?></dd>
        <dt>Budget</dt>
        <dd><?= View::e($party->formattedBudget()) ?></dd>
        <dt>Organizer</dt>
        <dd><?= View::e($party->organizer()) ?></dd>
        <dt>Status</dt>
        <dd><?= View::e($party->status()) ?></dd>
        <dt>Belsnickel's verdict</dt>
        <dd><?= $party->isCancelled() ? 'Impish. A cancelled party warms nobody.' : 'Admirable, provided the RSVPs are honest.' ?></dd>
    </dl>

    <?php if ($overCapacity): ?>
        <p class="warning">Impish! More guests than chairs. Belsnickel is displeased.</p>
    <?php endif; ?>
</article>

<section class="rsvps">
    <h3>Guest list (<?= View::e((string) $attending) ?> firmly attending)</h3>

    <?php if ($rsvps === []): ?>
        <p class="empty">No RSVPs yet. No RSVP, no entry.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($rsvps as $rsvp): ?>
                <li class="rsvp-<?= View::e($rsvp->status()) ?>">
                    <?= View::e($rsvp->employeeName()) ?> &mdash; <?= View::e($rsvp->status()) ?>
                    <?php if ($rsvp->dish() !== ''): ?>
                        (bringing <?= View::e($rsvp->dish()) ?>)
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="/parties/<?= View::e((string) $party->id()) ?>/rsvps">
        <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken) ?>">
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
        <label>Dish
            <input type="text" name="dish" maxlength="80">
        </label>
        <button type="submit">Submit RSVP</button>
    </form>
</section>

<section class="publishing">
    <h3>Published page</h3>

    <?php if ($party->isPublished()): ?>
        <p>
            Published. Attendees may RSVP and pledge refreshments at
            <a href="/parties/<?= View::e((string) $party->id()) ?>/invite">/parties/<?= View::e((string) $party->id()) ?>/invite</a>.
        </p>
    <?php else: ?>
        <p class="empty">Not published. Belsnickel keeps this party to the committee until you say otherwise.</p>
    <?php endif; ?>

    <form method="post" action="/parties/<?= View::e((string) $party->id()) ?>/publish">
        <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken) ?>">
        <input type="hidden" name="published" value="<?= $party->isPublished() ? '0' : '1' ?>">
        <button type="submit"><?= $party->isPublished() ? 'Unpublish this party' : 'Publish this party' ?></button>
    </form>
</section>

<?php if (!$party->isCancelled()): ?>
    <form method="post" action="/parties/<?= View::e((string) $party->id()) ?>/cancel" class="danger">
        <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken) ?>">
        <button type="submit">Cancel this party</button>
    </form>
<?php endif; ?>
