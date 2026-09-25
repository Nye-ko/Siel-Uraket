<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Client.php';
$clientService = new Client($db);

/*
 * Campus Gig - Client Job Detail
 * Converted from ClientJobDetailPage.jsx
 *
 * File name: job-detail.php
 *
 * This PHP version keeps the original client-only protection,
 * ownership check, applicant actions, completion flow, and review flow.
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

function getCurrentUser(): ?array
{
    return isset($_SESSION['currentUser'])
        && is_array($_SESSION['currentUser'])
        ? $_SESSION['currentUser']
        : null;
}

// ---------------------------------------------------------------------
// Current user / role protection
// ---------------------------------------------------------------------

$currentUser = getCurrentUser();

if (!$currentUser) {
    redirectTo('../auth/login.php');
}

if (($currentUser['role'] ?? '') !== 'client') {
    if (($currentUser['role'] ?? '') === 'freelancer') {
        $jobIdForRedirect = $_GET['jobId'] ?? '';
        redirectTo('../jobs/detail.php?jobId=' . rawurlencode($jobIdForRedirect));
    }

    redirectTo('../index.php');
}

// ---------------------------------------------------------------------
// Job ID
//
// Supports:
//   /client/job-detail.php?jobId=j-102
// and, if your server passes a route parameter:
//   /client/job-detail.php/j-102
// ---------------------------------------------------------------------

$jobId = $_GET['jobId'] ?? '';

if ($jobId === '') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $parts = array_values(
        array_filter(
            explode('/', trim((string) $path, '/'))
        )
    );

    $lastPart = end($parts);

    if ($lastPart && $lastPart !== 'job-detail.php') {
        $jobId = preg_replace('/\.php$/', '', $lastPart);
    }
}

if ($jobId === '') {
    http_response_code(400);
    $loadError = 'Job not found.';
}

// ---------------------------------------------------------------------
// Load job
// ---------------------------------------------------------------------

$job = $clientService->job($currentUser['id'], $jobId);
$applications = $job ? $clientService->applications($jobId) : [];
$loadError = '';

if (!$job) {
    $loadError = "Couldn't load this job.";
}

// ---------------------------------------------------------------------
// IMPORTANT OWNERSHIP CHECK
// ---------------------------------------------------------------------

if ($job && $job['clientId'] !== ($currentUser['id'] ?? null)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>You can't manage this job - Campus Gig</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen flex flex-col">

    <header class="w-full border-b">
        <div class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between">
            <a href="../index.php" class="text-xl font-semibold tracking-tight">
                Campus Gig
            </a>

            <nav aria-label="Client navigation" class="flex items-center gap-6">
                <a href="../client/dashboard.php" class="text-sm">
                    Dashboard
                </a>

                <a href="../client/profile.php" class="text-sm">
                    Profile
                </a>
            </nav>
        </div>
    </header>

    <main class="flex-1">
        <div class="max-w-[1440px] mx-auto px-20 py-16">
            <section class="flex flex-col gap-4 max-w-3xl">
                <h1 class="text-xl font-semibold">
                    You can't manage this job
                </h1>

                <p class="text-sm">
                    This job belongs to another client.
                </p>

                <a
                    href="dashboard.php"
                    class="text-sm underline self-start"
                >
                    Back to your jobs
                </a>
            </section>
        </div>
    </main>

    </body>
    </html>
    <?php
    exit;
}

// ---------------------------------------------------------------------
// Handle client actions
// ---------------------------------------------------------------------

$actionError = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job) {
    $action = $_POST['action'] ?? '';
    $applicationId = $_POST['application_id'] ?? '';

    // Re-check ownership on every action.
    if ($job['clientId'] !== ($currentUser['id'] ?? null)) {
        $actionError = "You cannot manage another client's job.";
    } else {

        // -------------------------------------------------------------
        // Reject application
        // -------------------------------------------------------------

        if ($action === 'reject') {
            $found = false;

            foreach ($applications as &$application) {
                if ($application['id'] === $applicationId) {
                    $found = true;

                    if ($application['status'] === 'pending') {
                        if ($clientService->updateApplicationStatus($applicationId, 'rejected')) {
                            $application['status'] = 'rejected';
                            $successMessage = 'Applicant rejected.';
                        } else {
                            $actionError = "Couldn't reject that applicant. Try again.";
                        }
                    }

                    break;
                }
            }

            unset($application);

            if (!$found) {
                $actionError = "Couldn't reject that applicant. Try again.";
            }
        }

        // -------------------------------------------------------------
        // Accept application
        // -------------------------------------------------------------

        if ($action === 'accept') {

            if ($job['status'] !== 'open') {
                $actionError =
                    'This job is no longer accepting applications.';
            } else {

                $found = false;

                foreach ($applications as &$application) {
                    if ($application['id'] === $applicationId) {
                        $found = true;

                        if ($application['status'] === 'pending') {
                            if ($clientService->updateApplicationStatus($applicationId, 'accepted')) {
                                $application['status'] = 'accepted';
                            } else {
                                $actionError = "Couldn't accept that applicant. Try again.";
                            }
                        }

                        break;
                    }
                }

                unset($application);

                if (!$found) {
                    $actionError =
                        "Couldn't accept that applicant. Try again.";
                } else if ($actionError === '') {

                    // Reject every other pending applicant.
                    foreach ($applications as &$application) {
                        if (
                            $application['id'] !== $applicationId &&
                            $application['status'] === 'pending'
                        ) {
                            $clientService->updateApplicationStatus($application['id'], 'rejected');
                            $application['status'] = 'rejected';
                        }
                    }

                    unset($application);

                    if ($clientService->updateJobStatus($jobId, 'closed')) {
                        $job['status'] = 'closed';
                        $successMessage =
                            'Applicant accepted and the job was closed.';
                    } else {
                        $actionError = "Couldn't update job status. Try again.";
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // Mark completed
        // -------------------------------------------------------------

        if ($action === 'mark_completed') {
            $hiredApplication = null;

            foreach ($applications as $application) {
                if (
                    $application['status'] === 'accepted' ||
                    $application['status'] === 'completed'
                ) {
                    $hiredApplication = $application;
                    break;
                }
            }

            if (!$hiredApplication) {
                $actionError =
                    'There is no hired application to complete.';
            } elseif ($hiredApplication['status'] !== 'accepted') {
                $actionError =
                    'This application has already been completed.';
            } else {

                if ($clientService->updateApplicationStatus($hiredApplication['id'], 'completed')) {
                    foreach ($applications as &$application) {
                        if ($application['id'] === $hiredApplication['id']) {
                            $application['status'] = 'completed';
                            break;
                        }
                    }

                    unset($application);

                    if ($clientService->updateJobStatus($jobId, 'completed')) {
                        $job['status'] = 'completed';
                        $successMessage = 'Job marked as completed.';
                    } else {
                        $actionError = "Couldn't update job status. Try again.";
                    }
                } else {
                    $actionError = "Couldn't mark application as completed. Try again.";
                }
            }
        }

        // -------------------------------------------------------------
        // Submit client review
        // -------------------------------------------------------------

        if ($action === 'submit_review') {
            $rating = (int) ($_POST['rating'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');

            $hiredApplicationIndex = null;

            foreach ($applications as $index => $application) {
                if (
                    $application['status'] === 'completed'
                ) {
                    $hiredApplicationIndex = $index;
                    break;
                }
            }

            if ($hiredApplicationIndex === null) {
                $actionError =
                    'You can only review a completed application.';
            } elseif ($rating < 1 || $rating > 5) {
                $actionError =
                    'Please select a rating from 1 to 5.';
            } else {

                if ($clientService->submitReview(
                    $applications[$hiredApplicationIndex]['id'],
                    $currentUser['id'],
                    $applications[$hiredApplicationIndex]['freelancerId'],
                    $rating,
                    $comment
                )) {
                    $applications[$hiredApplicationIndex]['clientReview'] = [
                        'rating' => $rating,
                        'comment' => $comment,
                        'submittedAt' => date('c'),
                    ];

                    $successMessage = 'Review submitted.';
                } else {
                    $actionError = "Couldn't submit review. Try again.";
                }
            }
        }
    }
}

// ---------------------------------------------------------------------
// Derived state
// ---------------------------------------------------------------------

$pendingApplications = array_values(
    array_filter(
        $applications,
        fn($application) =>
            $application['status'] === 'pending'
    )
);

$hiredApplication = null;

foreach ($applications as $application) {
    if (
        $application['status'] === 'accepted' ||
        $application['status'] === 'completed'
    ) {
        $hiredApplication = $application;
        break;
    }
}

// ---------------------------------------------------------------------
// Page shell
// ---------------------------------------------------------------------

function renderHeader(): void
{
    ?>
    <header class="w-full border-b">
        <div
            class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between"
        >
            <a
                href="../index.php"
                class="text-xl font-semibold tracking-tight"
            >
                Campus Gig
            </a>

            <nav
                aria-label="Client navigation"
                class="flex items-center gap-6"
            >
                <a
                    href="dashboard.php"
                    class="text-sm"
                >
                    Dashboard
                </a>

                <a
                    href="profile.php"
                    class="text-sm"
                >
                    Profile
                </a>
            </nav>
        </div>
    </header>
    <?php
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

    <title>
        <?= $job ? e($job['title']) : 'Job Detail' ?> - Campus Gig
    </title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col">

<?php renderHeader(); ?>

<main class="flex-1">

    <div
        class="max-w-[1440px] mx-auto px-20 py-16 flex flex-col gap-10 max-w-3xl"
    >

        <?php if ($loadError || !$job): ?>

            <p role="alert" class="text-sm">
                <?= e($loadError ?: 'Job not found.') ?>
            </p>

        <?php else: ?>

            <!-- Header -->
            <div class="flex flex-col gap-1">

                <a
                    href="dashboard.php"
                    class="text-xs self-start underline"
                >
                    ← Back to your jobs
                </a>

                <div class="flex flex-wrap items-center gap-3">

                    <h1 class="text-2xl font-semibold">
                        <?= e($job['title']) ?>
                    </h1>

                    <span class="text-xs border px-2 py-0.5">
                        <?= e($job['status']) ?>
                    </span>

                </div>

                <p class="text-sm">
                    <?= e($job['jobType']) ?>
                    · ₱<?= number_format((float) $job['budget']) ?>
                </p>

                <p class="text-sm">
                    Posted by
                    <span class="font-medium">
                        <?= e($job['clientName']) ?>
                    </span>
                </p>

            </div>

            <!-- Description -->
            <section class="flex flex-col gap-2">

                <h2 class="text-lg font-medium">
                    About this job
                </h2>

                <p class="text-sm leading-relaxed">
                    <?= e($job['description']) ?>
                </p>

            </section>

            <!-- Skills -->
            <?php if (!empty($job['skills'])): ?>

                <section class="flex flex-col gap-2">

                    <h2 class="text-sm font-medium">
                        Required skills
                    </h2>

                    <div class="flex flex-wrap gap-2">

                        <?php foreach ($job['skills'] as $skill): ?>

                            <span
                                class="text-xs border px-2 py-1"
                            >
                                <?= e($skill['name']) ?>
                            </span>

                        <?php endforeach; ?>

                    </div>

                </section>

            <?php endif; ?>

            <?php if ($actionError): ?>

                <p
                    role="alert"
                    class="text-sm"
                >
                    <?= e($actionError) ?>
                </p>

            <?php endif; ?>

            <?php if ($successMessage): ?>

                <p
                    role="status"
                    class="text-sm"
                >
                    <?= e($successMessage) ?>
                </p>

            <?php endif; ?>

            <!-- Applicants -->
            <?php if ($job['status'] === 'open'): ?>

                <section
                    aria-labelledby="applicants-heading"
                    class="flex flex-col gap-4"
                >

                    <div class="flex flex-col gap-1">

                        <h2
                            id="applicants-heading"
                            class="text-lg font-medium"
                        >
                            Applicants
                        </h2>

                        <p class="text-xs">
                            Review applications and choose one freelancer.
                        </p>

                    </div>

                    <?php if (count($pendingApplications) === 0): ?>

                        <div class="border border-dashed p-8">

                            <p class="text-sm">
                                No pending applications yet.
                            </p>

                        </div>

                    <?php else: ?>

                        <ul class="flex flex-col border-t">

                            <?php foreach ($pendingApplications as $application): ?>

                                <li
                                    class="border-b py-5 flex flex-col gap-3"
                                >

                                    <div class="flex flex-col gap-1">

                                        <span class="text-sm font-medium">
                                            <?= e($application['freelancerName']) ?>
                                        </span>

                                        <span class="text-sm">
                                            <?= e($application['title']) ?>
                                        </span>

                                        <p class="text-sm">
                                            <?= e($application['message']) ?>
                                        </p>

                                    </div>

                                    <div class="flex flex-wrap gap-3">

                                        <!-- Accept -->
                                        <form
                                            method="POST"
                                            action=""
                                            onsubmit="return confirm('Accept this applicant? Accepting closes this post and rejects every other pending applicant.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="accept"
                                            >

                                            <input
                                                type="hidden"
                                                name="application_id"
                                                value="<?= e($application['id']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="text-sm px-4 py-2 border"
                                            >
                                                Accept
                                            </button>

                                        </form>

                                        <!-- Reject -->
                                        <form
                                            method="POST"
                                            action=""
                                            onsubmit="return confirm('Reject this applicant?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="reject"
                                            >

                                            <input
                                                type="hidden"
                                                name="application_id"
                                                value="<?= e($application['id']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="text-sm px-4 py-2 border"
                                            >
                                                Reject
                                            </button>

                                        </form>

                                    </div>

                                </li>

                            <?php endforeach; ?>

                        </ul>

                    <?php endif; ?>

                </section>

            <?php endif; ?>

            <!-- Hired / completed -->
            <?php if ($hiredApplication): ?>

                <section
                    aria-labelledby="hired-heading"
                    class="flex flex-col gap-6"
                >

                    <div class="flex flex-col gap-2">

                        <h2
                            id="hired-heading"
                            class="text-lg font-medium"
                        >
                            Hired
                        </h2>

                        <div
                            class="border p-5 flex flex-col gap-1"
                        >

                            <span class="text-sm font-medium">
                                <?= e($hiredApplication['freelancerName']) ?>
                            </span>

                            <span class="text-xs">
                                Status:
                                <?= e($hiredApplication['status']) ?>
                            </span>

                        </div>

                    </div>

                    <?php if ($hiredApplication['status'] === 'accepted'): ?>

                        <form
                            method="POST"
                            action=""
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="mark_completed"
                            >

                            <button
                                type="submit"
                                class="text-sm px-5 py-2.5 border self-start"
                            >
                                Mark as completed
                            </button>

                        </form>

                    <?php endif; ?>

                    <?php if ($hiredApplication['status'] === 'completed'): ?>

                        <?php
                        $existingReview =
                            $hiredApplication['clientReview'] ?? null;
                        ?>

                        <section
                            aria-labelledby="review-heading"
                            class="flex flex-col gap-4"
                        >

                            <h3
                                id="review-heading"
                                class="text-lg font-medium"
                            >
                                Review
                            </h3>

                            <?php if ($existingReview): ?>

                                <div class="border p-5 flex flex-col gap-2">

                                    <span class="text-sm font-medium">
                                        Your review
                                    </span>

                                    <span class="text-sm">
                                        Rating:
                                        <?= e($existingReview['rating']) ?>/5
                                    </span>

                                    <?php if (
                                        !empty($existingReview['comment'])
                                    ): ?>

                                        <p class="text-sm">
                                            <?= e($existingReview['comment']) ?>
                                        </p>

                                    <?php endif; ?>

                                </div>

                            <?php else: ?>

                                <form
                                    method="POST"
                                    action=""
                                    class="flex flex-col gap-4"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="submit_review"
                                    >

                                    <div class="flex flex-col gap-1.5">

                                        <label
                                            for="rating"
                                            class="text-sm font-medium"
                                        >
                                            Rating
                                        </label>

                                        <select
                                            id="rating"
                                            name="rating"
                                            required
                                            class="border px-3 py-2 text-sm"
                                        >

                                            <option value="">
                                                Select a rating
                                            </option>

                                            <option value="5">
                                                5 / 5
                                            </option>

                                            <option value="4">
                                                4 / 5
                                            </option>

                                            <option value="3">
                                                3 / 5
                                            </option>

                                            <option value="2">
                                                2 / 5
                                            </option>

                                            <option value="1">
                                                1 / 5
                                            </option>

                                        </select>

                                    </div>

                                    <div class="flex flex-col gap-1.5">

                                        <label
                                            for="comment"
                                            class="text-sm font-medium"
                                        >
                                            Comment
                                        </label>

                                        <textarea
                                            id="comment"
                                            name="comment"
                                            rows="4"
                                            class="border px-3 py-2 text-sm"
                                            placeholder="Share your experience with this freelancer."
                                        ></textarea>

                                    </div>

                                    <button
                                        type="submit"
                                        class="text-sm px-5 py-2.5 border self-start"
                                    >
                                        Submit review
                                    </button>

                                </form>

                            <?php endif; ?>

                        </section>

                    <?php endif; ?>

                </section>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</main>

</body>
</html>