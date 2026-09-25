<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Client.php';
$clientService = new Client($db);

/*
 * Campus Gig - Client Create Job
 * Converted from CreateJobPostPage.jsx
 *
 * File: client/create-job.php
 */

// ---------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirectTo(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// ---------------------------------------------------------------------
// Client-only protection
// ---------------------------------------------------------------------

$currentUser = $_SESSION['currentUser'] ?? null;

if (!$currentUser) {
    redirectTo('../auth/login.php');
}

if (($currentUser['role'] ?? '') !== 'client') {
    if (($currentUser['role'] ?? '') === 'freelancer') {
        redirectTo('../jobs/index.php?role=freelancer');
    }

    redirectTo('../index.php');
}

// ---------------------------------------------------------------------
// Job types
// Supported job types.
// ---------------------------------------------------------------------

$JOB_TYPES = [
    'Service',
    'Commission',
    'Part-time',
];

// ---------------------------------------------------------------------
$skillResult = $db->query('SELECT id, name FROM skills ORDER BY name');
$SKILLS = $skillResult->fetch_all(MYSQLI_ASSOC);

$MIN_SKILLS = 1;
$MAX_SKILLS = 5;

// ---------------------------------------------------------------------
// Form state
// ---------------------------------------------------------------------

$formValues = [
    'title' => '',
    'type' => '',
    'price' => '',
    'description' => '',
];

$selectedSkills = [];
$formError = '';
$fieldErrors = [
    'title' => '',
    'type' => '',
    'price' => '',
    'description' => '',
    'skills' => '',
];

$touchedFields = [
    'title' => false,
    'type' => false,
    'price' => false,
    'description' => false,
    'skills' => false,
];

// ---------------------------------------------------------------------
// Handle create-job form
// ---------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formValues['title'] = trim($_POST['title'] ?? '');
    $formValues['type'] = trim($_POST['type'] ?? '');
    $formValues['price'] = trim($_POST['price'] ?? '');
    $formValues['description'] = trim($_POST['description'] ?? '');

    $postedSkills = $_POST['skills'] ?? [];

    if (!is_array($postedSkills)) {
        $postedSkills = [];
    }

    // Keep only valid skill IDs.
    $validSkillIds = array_column($SKILLS, 'id');

    $selectedSkills = array_values(
        array_unique(
            array_intersect($postedSkills, $validSkillIds)
        )
    );

    $touchedFields = [
        'title' => true,
        'type' => true,
        'price' => true,
        'description' => true,
        'skills' => true,
    ];

    // -------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------

    if ($formValues['title'] === '') {
        $fieldErrors['title'] = 'Give the job a title.';
    }

    if ($formValues['type'] === '') {
        $fieldErrors['type'] = 'Choose a job type.';
    } elseif (!in_array($formValues['type'], $JOB_TYPES, true)) {
        $fieldErrors['type'] = 'Choose a valid job type.';
    }

    if ($formValues['price'] === '') {
        $fieldErrors['price'] =
            "Enter a price, or note that it's negotiable.";
    }

    if ($formValues['description'] === '') {
        $fieldErrors['description'] =
            'Add a short description.';
    }

    if (count($selectedSkills) < $MIN_SKILLS) {
        $fieldErrors['skills'] = 'Add at least 1 skill.';
    } elseif (count($selectedSkills) > $MAX_SKILLS) {
        $fieldErrors['skills'] =
            "Add at most {$MAX_SKILLS} skills.";
    }

    $hasErrors = false;

    foreach ($fieldErrors as $error) {
        if ($error !== '') {
            $hasErrors = true;
            break;
        }
    }

    // -------------------------------------------------------------
    // Create job
    // -------------------------------------------------------------

    if (!$hasErrors) {

        $jobId = $clientService->createJob($currentUser['id'], [
            'title' => $formValues['title'],
            'type' => $formValues['type'],
            'budget' => $formValues['price'],
            'description' => $formValues['description'],
        ], $selectedSkills);

        redirectTo(
            'job-detail.php?jobId=' . rawurlencode($jobId)
        );
    }

    $formError = "Please fix the errors below.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Post a Job - URaket</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col">

<header class="w-full border-b">

    <div
        class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between"
    >

        <a
            href="/"
            class="text-xl font-semibold tracking-tight"
        >
            URaket
        </a>

        <a
            href="/job-board.php?role=client"
            class="text-sm"
        >
            My job posts
        </a>

    </div>

</header>

<main class="flex-1">

    <div
        class="max-w-[1440px] mx-auto px-20 py-16"
    >

        <div
            class="max-w-xl flex flex-col gap-8"
        >

            <!-- Heading -->

            <div class="flex flex-col gap-2">

                <h1 class="text-2xl font-semibold">
                    Post a job
                </h1>

                <p class="text-sm">
                    Students will see this on the job board and can apply
                    directly.
                </p>

            </div>

            <!-- Form -->

            <form
                method="POST"
                action=""
                novalidate
                class="flex flex-col gap-6"
            >

                <?php if ($formError): ?>

                    <p
                        role="alert"
                        class="text-sm"
                    >
                        <?= e($formError) ?>
                    </p>

                <?php endif; ?>

                <!-- Title -->

                <div class="flex flex-col gap-1.5">

                    <label
                        for="title"
                        class="text-sm font-medium"
                    >
                        Title
                    </label>

                    <input
                        id="title"
                        name="title"
                        type="text"
                        value="<?= e($formValues['title']) ?>"
                        placeholder="e.g. Logo design for student org"
                        aria-invalid="<?= $touchedFields['title'] && $fieldErrors['title'] ? 'true' : 'false' ?>"
                        aria-describedby="title-error"
                        class="border px-3 py-2 text-sm"
                    >

                    <?php if (
                        $touchedFields['title'] &&
                        $fieldErrors['title']
                    ): ?>

                        <span
                            id="title-error"
                            role="alert"
                            class="text-xs"
                        >
                            <?= e($fieldErrors['title']) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <!-- Job type -->

                <div class="flex flex-col gap-1.5">

                    <label
                        for="type"
                        class="text-sm font-medium"
                    >
                        Job type
                    </label>

                    <select
                        id="type"
                        name="type"
                        aria-invalid="<?= $touchedFields['type'] && $fieldErrors['type'] ? 'true' : 'false' ?>"
                        aria-describedby="type-error"
                        class="border px-3 py-2 text-sm"
                    >

                        <option value="">
                            Select a type
                        </option>

                        <?php foreach ($JOB_TYPES as $type): ?>

                            <option
                                value="<?= e($type) ?>"
                                <?= $formValues['type'] === $type ? 'selected' : '' ?>
                            >
                                <?= e($type) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <?php if (
                        $touchedFields['type'] &&
                        $fieldErrors['type']
                    ): ?>

                        <span
                            id="type-error"
                            role="alert"
                            class="text-xs"
                        >
                            <?= e($fieldErrors['type']) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <!-- Price -->

                <div class="flex flex-col gap-1.5">

                    <label
                        for="price"
                        class="text-sm font-medium"
                    >
                        Price
                    </label>

                    <input
                        id="price"
                        name="price"
                        type="text"
                        value="<?= e($formValues['price']) ?>"
                        placeholder="e.g. ₱1,500 or negotiable"
                        aria-invalid="<?= $touchedFields['price'] && $fieldErrors['price'] ? 'true' : 'false' ?>"
                        aria-describedby="price-error"
                        class="border px-3 py-2 text-sm"
                    >

                    <?php if (
                        $touchedFields['price'] &&
                        $fieldErrors['price']
                    ): ?>

                        <span
                            id="price-error"
                            role="alert"
                            class="text-xs"
                        >
                            <?= e($fieldErrors['price']) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <!-- Description -->

                <div class="flex flex-col gap-1.5">

                    <label
                        for="description"
                        class="text-sm font-medium"
                    >
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="What does the student need to do? Any deadlines?"
                        aria-invalid="<?= $touchedFields['description'] && $fieldErrors['description'] ? 'true' : 'false' ?>"
                        aria-describedby="description-error"
                        class="border px-3 py-2 text-sm"
                    ><?= e($formValues['description']) ?></textarea>

                    <?php if (
                        $touchedFields['description'] &&
                        $fieldErrors['description']
                    ): ?>

                        <span
                            id="description-error"
                            role="alert"
                            class="text-xs"
                        >
                            <?= e($fieldErrors['description']) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <!-- Skills -->

                <div>

                    <label
                        class="text-sm font-medium block mb-1.5"
                    >
                        Skills needed (1–5)
                    </label>

                    <p class="text-xs mb-3">
                        Select at least 1 and at most 5 skills.
                    </p>

                    <div
                        class="flex flex-wrap gap-2"
                        id="skills-container"
                    >

                        <?php foreach ($SKILLS as $skill): ?>

                            <label
                                class="border px-3 py-2 text-sm cursor-pointer"
                            >

                                <input
                                    type="checkbox"
                                    name="skills[]"
                                    value="<?= e($skill['id']) ?>"
                                    class="skill-checkbox mr-1"
                                    <?= in_array(
                                        $skill['id'],
                                        $selectedSkills,
                                        true
                                    ) ? 'checked' : '' ?>
                                >

                                <?= e($skill['name']) ?>

                            </label>

                        <?php endforeach; ?>

                    </div>

                    <p
                        id="skill-count"
                        class="text-xs mt-2"
                    >
                        <?= count($selectedSkills) ?> / <?= $MAX_SKILLS ?>
                        selected
                    </p>

                    <?php if (
                        $touchedFields['skills'] &&
                        $fieldErrors['skills']
                    ): ?>

                        <span
                            role="alert"
                            class="text-xs block mt-1.5"
                        >
                            <?= e($fieldErrors['skills']) ?>
                        </span>

                    <?php endif; ?>

                </div>

                <!-- Submit -->

                <button
    type="submit"
    class="inline-flex items-center justify-center text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] px-5 py-3 self-start transition-all duration-150 ease-in-out cursor-pointer hover:bg-[#099639] hover:text-white hover:border-[#099639] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#099639] focus:ring-offset-1"
>
    Post job
</button>

            </form>

        </div>

    </div>

</main>

<script>
const checkboxes = Array.from(
    document.querySelectorAll(".skill-checkbox")
);

const maxSkills = <?= (int) $MAX_SKILLS ?>;

const skillCount = document.getElementById("skill-count");

function updateSkillLimit() {
    const selected = checkboxes.filter(
        (checkbox) => checkbox.checked
    );

    skillCount.textContent =
        selected.length + " / " + maxSkills + " selected";

    checkboxes.forEach((checkbox) => {
        if (!checkbox.checked) {
            checkbox.disabled = selected.length >= maxSkills;
        } else {
            checkbox.disabled = false;
        }
    });
}

checkboxes.forEach((checkbox) => {
    checkbox.addEventListener(
        "change",
        updateSkillLimit
    );
});

updateSkillLimit();
</script>

</body>
</html>