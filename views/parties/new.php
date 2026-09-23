<?php

use PartyPlanningCommittee\Http\View;
use PartyPlanningCommittee\Model\Party;

/**
 * Belsnickel accepts proposals, but only after reading every field.
 *
 * @var list<PartyPlanningCommittee\Model\Office> $offices
 * @var list<string>                              $errors
 * @var array<string, mixed>                      $input
 * @var string                                    $csrfToken
 */
$input = $input ?? [];
$value = static fn (string $key): string => View::e((string) ($input[$key] ?? ''));
?>
<h2>Propose a party</h2>

<?php if ($errors !== []): ?>
    <ul class="errors">
        <?php foreach ($errors as $error): ?>
            <li><?= View::e($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post" action="/parties">
    <input type="hidden" name="csrf_token" value="<?= View::e($csrfToken) ?>">
    <label>Branch
        <select name="office_slug">
            <?php foreach ($offices as $office): ?>
                <option value="<?= View::e($office->slug()) ?>"<?= ($input['office_slug'] ?? '') === $office->slug() ? ' selected' : '' ?>>
                    <?= View::e($office->label()) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Title
        <input type="text" name="title" maxlength="120" value="<?= $value('title') ?>" required>
    </label>
    <label>Theme
        <input type="text" name="theme" value="<?= $value('theme') ?>" required>
    </label>
    <label>When
        <input type="datetime-local" name="scheduled_for" value="<?= $value('scheduled_for') ?>" required>
    </label>
    <label>Budget (dollars)
        <input type="number" name="budget" min="0" step="0.01" value="<?= $value('budget') ?>">
    </label>
    <label>Organizer
        <input type="text" name="organizer" value="<?= $value('organizer') ?>">
    </label>
    <label>Status
        <select name="status">
            <?php foreach (Party::STATUSES as $status): ?>
                <option value="<?= View::e($status) ?>"<?= ($input['status'] ?? '') === $status ? ' selected' : '' ?>><?= View::e($status) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Submit for Belsnickel's judgment</button>
</form>
