<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Repositories\SoilSampleRepository;
use FarmQ\Services\AuthService;
use FarmQ\Services\CropSelectionService;
use FarmQ\Services\CsvIngestionService;
use FarmQ\Services\FarmContext;

final class IngestionController
{
    /** @var array<string, string> */
    private const REGION_SAMPLES = [
        'delta' => 'samples/soil_test_delta_cotton.csv',
        'upper_egypt' => 'samples/soil_test_upper_egypt_sugarcane.csv',
        'reclaimed_desert' => 'samples/soil_test_reclaimed_citrus.csv',
    ];

    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService(),
        private FarmContext $farmContext = new FarmContext(),
        private CsvIngestionService $csv = new CsvIngestionService(),
        private SoilSampleRepository $samples = new SoilSampleRepository(),
        private CropSelectionService $crops = new CropSelectionService()
    ) {
    }

    public function index(): string
    {
        $user = $this->auth->requireAuth();
        $activeFarm = $this->requireActiveFarm($user);
        $canEdit = (new \FarmQ\Services\FarmAccessService())->canEdit((int) $activeFarm['id'], (int) $user['id']);

        return view('ingestion/index', app_view_data($this->t, $user, [
            'pageTitle' => $this->t->get('ingestion.title'),
            'activeFarm' => $activeFarm,
            'canEdit' => $canEdit,
            'availableCrops' => $this->crops->availableForFarm($activeFarm),
            'selectedCrop' => $this->crops->selectedCrop($activeFarm, $this->t->locale()),
            'latestSample' => $this->samples->latestForFarm((int) $activeFarm['id']),
            'recentSamples' => $this->samples->listForFarm((int) $activeFarm['id'], 5),
            'uploadErrors' => flash('upload_errors') ?? [],
            'uploadMessage' => flash('upload_message') ?? null,
            'regionSampleUrl' => asset(self::REGION_SAMPLES[$activeFarm['region']] ?? 'samples/soil_sample_template.csv'),
        ]));
    }

    public function upload(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();
        $activeFarm = $this->farmContext->requireActiveEditable($user, $this->t);

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            flash('upload_message', 'upload_failed');
            redirect('/ingestion?lang=' . $this->t->locale());
        }

        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            flash('upload_message', 'invalid_type');
            redirect('/ingestion?lang=' . $this->t->locale());
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            flash('upload_message', 'too_large');
            redirect('/ingestion?lang=' . $this->t->locale());
        }

        $result = $this->csv->parse((string) $file['tmp_name']);

        if (!$result['ok']) {
            if (isset($result['errors'])) {
                flash('upload_errors', $result['errors']);
            }
            flash('upload_message', $result['message'] ?? 'parse_failed');
            redirect('/ingestion?lang=' . $this->t->locale());
        }

        $farmId = (int) $activeFarm['id'];
        $rowsToImport = [];
        $skippedDuplicates = 0;
        foreach ($result['rows'] as $row) {
            if ($this->samples->existsOnDate($farmId, (string) $row['sample_date'])) {
                $skippedDuplicates++;
                continue;
            }
            $rowsToImport[] = $row;
        }

        if ($rowsToImport === []) {
            flash('upload_message', $skippedDuplicates > 0 ? 'all_duplicates' : 'no_data_rows');
            if (isset($result['errors']) && $result['errors'] !== []) {
                flash('upload_errors', $result['errors']);
            }
            redirect('/ingestion?lang=' . $this->t->locale());
        }

        $count = $this->samples->createBatch(
            $farmId,
            $rowsToImport,
            basename((string) $file['name'])
        );

        if (isset($result['errors']) && $result['errors'] !== []) {
            flash('upload_errors', $result['errors']);
            flash('upload_message', 'partial_import');
        }

        $message = $this->t->get('ingestion.upload_success', ['count' => $count]);
        if ($skippedDuplicates > 0) {
            $message .= ' ' . $this->t->get('ingestion.skipped_duplicates', ['count' => $skippedDuplicates]);
        }
        flash('success', $message);
        redirect('/ingestion?lang=' . $this->t->locale());
    }

    public function selectCrop(): never
    {
        verify_csrf();
        $user = $this->auth->requireAuth();
        $activeFarm = $this->farmContext->requireActiveEditable($user, $this->t);

        $cropCode = trim($_POST['crop_code'] ?? '');
        $result = $this->crops->select($activeFarm, $cropCode);

        if (!$result['ok']) {
            flash('upload_message', $result['error'] ?? 'invalid_crop');
            redirect('/ingestion?lang=' . $this->t->locale());
        }

        flash('success', $this->t->get('ingestion.crop_saved'));
        redirect('/ingestion?lang=' . $this->t->locale());
    }

    /** @param array<string, mixed> $user */
    private function requireActiveFarm(array $user): array
    {
        $farm = $this->farmContext->active($user);
        if ($farm === null) {
            flash('errors', ['farm' => 'required']);
            redirect('/farms/create?lang=' . $this->t->locale());
        }

        return $farm;
    }
}
