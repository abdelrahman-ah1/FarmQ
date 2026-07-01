<?php ob_start(); ?>
<section class="section">
    <div class="container">
        <div class="page-header">
            <h1><?= htmlspecialchars($t->get('farms.title')) ?></h1>
            <a href="/farms/create?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('farms.create_btn')) ?></a>
        </div>

        <?php if ($inviteUrl = flash('invite_url')): ?>
        <div class="dashboard-card invite-link-card">
            <h2><?= htmlspecialchars($t->get('farms.invite_link_title')) ?></h2>
            <p class="muted"><?= htmlspecialchars($t->get('farms.invite_link_hint')) ?></p>
            <input type="text" class="invite-url-input" readonly value="<?= htmlspecialchars((string) $inviteUrl) ?>" onclick="this.select()">
        </div>
        <?php endif; ?>

        <?php if ($error = flash('invite_error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($t->get('farms.invite_errors.' . $error)) ?></div>
        <?php endif; ?>

        <?php if ($farms === []): ?>
        <div class="dashboard-card empty-state">
            <p class="muted"><?= htmlspecialchars($t->get('farms.empty_body')) ?></p>
        </div>
        <?php else: ?>
        <div class="farm-list">
            <?php foreach ($farms as $farm): ?>
            <article class="farm-card">
                <div>
                    <h3><?= htmlspecialchars($farm['name']) ?></h3>
                    <p class="muted"><?= htmlspecialchars($t->get('regions.' . $farm['region'])) ?></p>
                    <?php if (!empty($farm['governorate'])): ?>
                    <p class="muted"><?= htmlspecialchars($t->get('governorates.' . $farm['governorate'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="farm-card-actions">
                <?php if ((int) ($farm['owner_user_id'] ?? 0) === (int) ($user['id'] ?? 0)): ?>
                <a href="/farms/boundary?lang=<?= $t->locale() ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars($t->get('farms.edit_boundary')) ?></a>
                <form method="post" action="/farms/invite?lang=<?= $t->locale() ?>" class="inline-form invite-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="farm_id" value="<?= (int) $farm['id'] ?>">
                    <select name="access_role" aria-label="<?= htmlspecialchars($t->get('farms.invite_role')) ?>">
                        <option value="viewer"><?= htmlspecialchars($t->get('farms.role_viewer')) ?></option>
                        <option value="editor"><?= htmlspecialchars($t->get('farms.role_editor')) ?></option>
                    </select>
                    <button type="submit" class="btn btn-secondary btn-sm"><?= htmlspecialchars($t->get('farms.invite_btn')) ?></button>
                </form>
                <?php endif; ?>
                <span class="tier-badge tier-<?= htmlspecialchars($farm['tier']) ?>">
                    <?= htmlspecialchars($t->get('plans.' . $farm['tier'])) ?>
                </span>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
