<?php ob_start(); ?>
<section class="section">
    <div class="container">
        <h1><?= htmlspecialchars($t->get('ingestion.title')) ?></h1>
        <p class="muted"><?= htmlspecialchars($t->get('ingestion.subtitle')) ?></p>

        <?php if ($uploadMessage): ?>
        <div class="alert alert-error"><?= htmlspecialchars($t->get('ingestion.errors.' . $uploadMessage)) ?></div>
        <?php endif; ?>

        <div class="ingestion-grid">
            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('ingestion.upload_title')) ?></h2>
                <p class="muted"><?= htmlspecialchars($t->get('ingestion.upload_hint')) ?></p>

                <p class="sample-downloads">
                    <a href="<?= asset('samples/soil_sample_template.csv') ?>" class="btn btn-secondary btn-sm" download>
                        <?= htmlspecialchars($t->get('ingestion.download_template')) ?>
                    </a>
                    <a href="<?= htmlspecialchars($regionSampleUrl) ?>" class="btn btn-secondary btn-sm" download>
                        <?= htmlspecialchars($t->get('ingestion.download_region_sample')) ?>
                    </a>
                </p>

                <form id="csv-upload-form" method="post" action="/ingestion/upload?lang=<?= $t->locale() ?>" enctype="multipart/form-data" class="form" data-requires-online>
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="csv_file"><?= htmlspecialchars($t->get('ingestion.csv_file')) ?></label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required>
                    </div>
                    <div id="upload-progress" class="upload-progress" hidden>
                        <div class="upload-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                            <div id="upload-progress-bar" class="upload-progress-bar"></div>
                        </div>
                    </div>
                    <p id="upload-status" class="upload-status muted"
                       data-uploading="<?= htmlspecialchars($t->get('ingestion.uploading')) ?>"
                       data-failed="<?= htmlspecialchars($t->get('ingestion.upload_failed')) ?>"
                       data-retry="<?= htmlspecialchars($t->get('ingestion.upload_retry')) ?>"></p>
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t->get('ingestion.upload_submit')) ?></button>
                </form>

                <?php if ($uploadErrors !== []): ?>
                <div class="parse-errors">
                    <h3><?= htmlspecialchars($t->get('ingestion.row_errors')) ?></h3>
                    <ul>
                        <?php foreach ($uploadErrors as $line => $rowErrors): ?>
                        <li>
                            <?= htmlspecialchars($t->get('ingestion.row', ['line' => $line])) ?>:
                            <?php foreach ($rowErrors as $field => $code): ?>
                            <span class="field-error"><?= htmlspecialchars($t->get('ingestion.field_errors.' . $code)) ?></span>
                            <?php endforeach; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('ingestion.crop_title')) ?></h2>
                <p class="muted"><?= htmlspecialchars($t->get('ingestion.crop_hint', ['region' => $t->get('regions.' . $activeFarm['region'])])) ?></p>

                <?php if ($availableCrops === []): ?>
                <p class="muted"><?= htmlspecialchars($t->get('ingestion.no_crops')) ?></p>
                <?php else: ?>
                <form method="post" action="/ingestion/crop?lang=<?= $t->locale() ?>" class="form">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="crop_code"><?= htmlspecialchars($t->get('ingestion.select_crop')) ?></label>
                        <select id="crop_code" name="crop_code" required>
                            <option value=""><?= htmlspecialchars($t->get('ingestion.choose_crop')) ?></option>
                            <?php foreach ($availableCrops as $crop): ?>
                            <?php $name = $t->locale() === 'ar' ? $crop['name_ar'] : $crop['name_en']; ?>
                            <option value="<?= htmlspecialchars($crop['crop_code']) ?>"
                                <?= ($selectedCrop && $selectedCrop['crop_code'] === $crop['crop_code']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t->get('ingestion.crop_submit')) ?></button>
                </form>
                <?php endif; ?>

                <?php if ($selectedCrop): ?>
                <p class="selected-crop">
                    <?= htmlspecialchars($t->get('ingestion.current_crop')) ?>:
                    <strong><?= htmlspecialchars($selectedCrop['display_name']) ?></strong>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($recentSamples !== []): ?>
        <div class="dashboard-card" style="margin-top: 1rem;">
            <h2><?= htmlspecialchars($t->get('ingestion.recent_samples')) ?></h2>
            <div class="table-wrap">
                <table class="plan-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($t->get('ingestion.col_date')) ?></th>
                            <th>N</th>
                            <th>P</th>
                            <th>K</th>
                            <th>pH</th>
                            <th>EC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSamples as $sample): ?>
                        <tr>
                            <td><?= htmlspecialchars($sample['sample_date']) ?></td>
                            <td><?= $sample['npk_n'] !== null ? htmlspecialchars((string) $sample['npk_n']) : '—' ?></td>
                            <td><?= $sample['npk_p'] !== null ? htmlspecialchars((string) $sample['npk_p']) : '—' ?></td>
                            <td><?= $sample['npk_k'] !== null ? htmlspecialchars((string) $sample['npk_k']) : '—' ?></td>
                            <td><?= $sample['ph'] !== null ? htmlspecialchars((string) $sample['ph']) : '—' ?></td>
                            <td><?= $sample['salinity_ec'] !== null ? htmlspecialchars((string) $sample['salinity_ec']) : '—' ?></td>
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
