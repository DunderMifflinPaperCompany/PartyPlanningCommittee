<?php

use PartyPlanningCommittee\Http\View;

/**
 * Belsnickel found nothing, and says so plainly.
 *
 * @var string $message
 */
?>
<h2>Belsnickel is displeased</h2>
<p class="warning"><?= View::e($message ?? 'Nothing here.') ?></p>
<p><a href="/">Return to the ledger</a></p>
