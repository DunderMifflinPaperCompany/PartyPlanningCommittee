<?php

use PartyPlanningCommittee\Http\View;

/**
 * Belsnickel opens the dance floor. Requests are queued, judged, and ranked.
 *
 * @var list<array{song: string, requester: string, votes: int}> $requests
 */
$requests = $requests ?? [];
?>
<section data-music-requests data-max-requests="12" hidden>
    <h2>Dance party music requests</h2>
    <p>Belsnickel permits dancing, but only to songs the committee can defend.</p>

    <form data-music-request-form>
        <label>Song
            <input type="text" data-music-request-song name="song" placeholder="Title of the song">
        </label>
        <button type="submit">Request it</button>
    </form>

    <p class="request-verdict" data-music-request-verdict></p>

    <ul class="music-queue" data-music-request-list>
        <?php foreach ($requests as $request): ?>
            <li data-song="<?= View::e($request['song']) ?>"
                data-requester="<?= View::e($request['requester']) ?>"
                data-votes="<?= View::e((string) $request['votes']) ?>">
                <?= View::e($request['song']) ?> requested by <?= View::e($request['requester']) ?>
                (<?= View::e((string) $request['votes']) ?> votes)
                <button type="button" data-music-request-vote>Vote</button>
            </li>
        <?php endforeach; ?>
    </ul>

    <p class="queue-summary" data-music-request-summary></p>
</section>
