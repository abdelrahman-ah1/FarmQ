<?php
$errorKey = static function (array $errors, string $field) use ($t): string {
    if (!isset($errors[$field])) {
        return '';
    }
    return htmlspecialchars($t->get('auth.errors.' . $errors[$field]));
};
ob_start();
?>
<section class="section auth-section">
    <div class="container narrow">
        <div class="auth-card">
            <h1><?= htmlspecialchars($t->get('auth.signup_title')) ?></h1>

            <form method="post" action="/signup?lang=<?= $t->locale() ?>" class="form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="full_name"><?= htmlspecialchars($t->get('auth.full_name')) ?></label>
                    <input type="text" id="full_name" name="full_name" required value="<?= old('full_name', '', $old ?? []) ?>">
                    <?= $errorKey($errors ?? [], 'full_name') ? '<span class="field-error">' . $errorKey($errors, 'full_name') . '</span>' : '' ?>
                </div>

                <div class="form-group">
                    <label for="email"><?= htmlspecialchars($t->get('auth.email')) ?></label>
                    <input type="email" id="email" name="email" required value="<?= old('email', '', $old ?? []) ?>">
                    <?= $errorKey($errors ?? [], 'email') ? '<span class="field-error">' . $errorKey($errors, 'email') . '</span>' : '' ?>
                </div>

                <div class="form-group">
                    <label for="role"><?= htmlspecialchars($t->get('auth.role')) ?></label>
                    <select id="role" name="role" required>
                        <?php foreach (['operator', 'agronomist', 'owner'] as $role): ?>
                        <option value="<?= $role ?>" <?= (($old['role'] ?? 'operator') === $role) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t->get('auth.roles.' . $role)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password"><?= htmlspecialchars($t->get('auth.password')) ?></label>
                    <input type="password" id="password" name="password" required minlength="8">
                    <?= $errorKey($errors ?? [], 'password') ? '<span class="field-error">' . $errorKey($errors, 'password') . '</span>' : '' ?>
                </div>

                <div class="form-group">
                    <label for="password_confirm"><?= htmlspecialchars($t->get('auth.password_confirm')) ?></label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                    <?= $errorKey($errors ?? [], 'password_confirm') ? '<span class="field-error">' . $errorKey($errors, 'password_confirm') . '</span>' : '' ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars($t->get('auth.signup_submit')) ?></button>
            </form>

            <p class="auth-footer">
                <?= htmlspecialchars($t->get('auth.has_account')) ?>
                <a href="/login?lang=<?= $t->locale() ?>"><?= htmlspecialchars($t->get('nav.login')) ?></a>
            </p>
        </div>
        <div style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: var(--text-muted);">
            <p>Designed by LogiQ Studio</p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/base.php');
