<?php ob_start(); ?>
<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($t->get('billing.title')) ?></h1>
                <p class="muted"><?= htmlspecialchars($t->get('billing.subtitle')) ?></p>
            </div>
        </div>

        <?php if ($billingError): ?>
        <div class="alert alert-error"><?= htmlspecialchars($t->get('billing.errors.' . $billingError)) ?></div>
        <?php endif; ?>

        <?php if ($isMock): ?>
        <div class="alert alert-info"><?= htmlspecialchars($t->get('billing.mock_notice')) ?></div>
        <?php endif; ?>

        <div class="dashboard-card billing-plan">
            <div class="billing-price">
                <span class="price"><?= htmlspecialchars(number_format($price, 0)) ?> <?= htmlspecialchars($t->get('billing.egp')) ?></span>
                <span class="muted"><?= htmlspecialchars($t->get('billing.per_season', ['days' => $seasonDays])) ?></span>
            </div>
            <p class="muted"><?= htmlspecialchars($t->get('billing.rails_note')) ?></p>

            <?php if ($farmStates === []): ?>
            <p class="muted"><?= htmlspecialchars($t->get('billing.no_farms')) ?></p>
            <a href="/farms/create?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('farms.create_btn')) ?></a>
            <?php else: ?>
            <form method="post" action="/billing/checkout?lang=<?= $t->locale() ?>" class="form billing-form">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="farm_id"><?= htmlspecialchars($t->get('billing.select_farm')) ?></label>
                    <select id="farm_id" name="farm_id" required>
                        <?php foreach ($farmStates as $state): ?>
                        <?php $farm = $state['farm']; ?>
                        <option value="<?= (int) $farm['id'] ?>" <?= $state['is_paid'] ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($farm['name']) ?>
                            <?= $state['is_paid'] ? '— ' . htmlspecialchars($t->get('billing.already_paid')) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_rail"><?= htmlspecialchars($t->get('billing.payment_method')) ?></label>
                    <select id="payment_rail" name="payment_rail" required>
                        <?php foreach ($rails as $rail): ?>
                        <option value="<?= htmlspecialchars($rail) ?>"><?= htmlspecialchars($t->get('billing.rails.' . $rail)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t->get('billing.pay_now')) ?></button>
            </form>
            <?php endif; ?>
        </div>

        <?php if ($history !== []): ?>
        <div class="dashboard-card">
            <h2><?= htmlspecialchars($t->get('billing.history_title')) ?></h2>
            <div class="table-wrap">
                <table class="plan-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($t->get('billing.col_date')) ?></th>
                            <th><?= htmlspecialchars($t->get('billing.col_amount')) ?></th>
                            <th><?= htmlspecialchars($t->get('billing.col_method')) ?></th>
                            <th><?= htmlspecialchars($t->get('billing.col_status')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $txn): ?>
                        <tr>
                            <td><?= htmlspecialchars(substr((string) $txn['created_at'], 0, 10)) ?></td>
                            <td><?= htmlspecialchars(number_format((float) $txn['amount_egp'], 0)) ?> <?= htmlspecialchars($t->get('billing.egp')) ?></td>
                            <td><?= htmlspecialchars($t->get('billing.rails.' . $txn['payment_rail'])) ?></td>
                            <td><span class="txn-status txn-<?= htmlspecialchars($txn['status']) ?>"><?= htmlspecialchars($t->get('billing.status.' . $txn['status'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
