            <?php foreach ($comments as $comment): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_PATH ?>/profile.php?id=<?= htmlspecialchars($comment['author']) ?>">
                            <p>
                                <?= htmlspecialchars(fetchName($comment['author'])) ?>
                            </p>
                        </a>
                        <a href="<?= BASE_PATH ?>/profile.php?id=<?= htmlspecialchars($comment['author']) ?>">
                            <?php
                            $pfpPath = fetchPFP($comment['author']);
                            $pfpPath = $pfpPath ? $pfpPath : 'default.png';
                            ?>
                            <img class="pfp-fallback" src="<?= BASE_PATH ?>/media/pfp/<?= $pfpPath ?>"
                                alt="<?= htmlspecialchars(fetchName($comment['author'])) ?>'s profile picture" loading="lazy"
                                width="50px">
                        </a>
                    </td>
                    <td>
                        <p><b><time class="">
                                    <?= time_elapsed_string($comment['date']) ?>
                                </time></b></p>
                        <p>
                            <?= htmlspecialchars($comment['text']) ?>
                        </p>
                        <br>
                        <?php
                        $reportType = 'comment';
                        if (($commentType ?? '') === 'blog') {
                            $reportType = 'blog_comment';
                        } elseif (($commentType ?? '') === 'bulletin') {
                            $reportType = 'bulletin_comment';
                        }
                        ?>
                        <p class="report">
                            <a href="<?= BASE_PATH ?>/report.php?type=<?= $reportType ?>&id=<?= htmlspecialchars($comment['id']) ?>" rel="nofollow">
                                <img src="<?= BASE_PATH ?>/static/icons/flag_red.png" class="icon" aria-hidden="true" loading="lazy" alt="">
                                Segnala Commento
                            </a>
                        </p>
                        <?php 
                        if (!isset($commentType)) { $commentType = ''; } 
                        $replies = fetchCommentReplies($comment['id'], $commentType);
                        if (!empty($replies)): ?>
                            <?php include("comments_reply_block.php") ?>
                        <?php endif; ?>

                        <?php if ($userId == $comment['author'] || $userId == $toid): ?>
                            <a href="deletecomment.php?id=<?= htmlspecialchars($comment['id']) ?>">
                                <button>Elimina</button>
                            </a>
                        <?php endif; ?>
                        <a href="addcomment.php?id=<?= $toid ?>&reply=<?= htmlspecialchars($comment['id']) ?>">
                            <button>Rispondi</button>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>