<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Freelancer.php';
require_once __DIR__ . '/../includes/functions.php';
$freelancerService = new Freelancer($db);

/*
 * Freelancer Job Detail
 * Converted from FreelancerJobDetailPage.jsx
 *
 * Job details and applications are loaded from MySQL.
 */

$currentUser = $_SESSION['currentUser'] ?? null;

if (!$currentUser) {
    header("Location: ../auth/login.php");
    exit;
}

if (($currentUser['role'] ?? '') !== 'freelancer') {
    if (($currentUser['role'] ?? '') === 'client') {
        header("Location: ../client/job-detail.php?jobId=" . urlencode($_GET['jobId'] ?? ''));
    } else {
        header("Location: ../index.php");
    }
    exit;
}

$jobId = $_GET['jobId'] ?? '';

$job = $freelancerService->job($jobId);

if (!$job) {
    http_response_code(404);
    $pageError = 'Job not found.';
} else {
    $pageError = '';
}

$actionError = '';
$reportSuccess = '';

$application = $job
    ? $freelancerService->application($currentUser['id'], $job['id'])
    : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $job) {
    $action = $_POST['action'] ?? '';

    if ($action === 'apply') {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($job['status'] !== 'open') {
            $actionError = 'This job is no longer accepting applications.';
        } elseif ($title === '' || $message === '') {
            $actionError = 'Enter an application title and message.';
        } elseif ($application) {
            $actionError = 'You have already applied to this job.';
        } else {
            $application = $freelancerService->apply(
                $currentUser['id'],
                $job['id'],
                $title,
                $message
            );
        }
    }

    if ($action === 'report') {
        $reason = trim($_POST['reason'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if ($reason === '') {
            $actionError = 'Choose a reason for the report.';
        } else {
            $freelancerService->report($currentUser['id'], $job['id'], $reason, $note);

            $reportSuccess = 'Report submitted. An admin will review this job post.';
        }
    }

    if ($action === 'review') {
        if (!$application || $application['status'] !== 'completed') {
            $actionError = 'You can only review a completed application.';
        } else {
            $rating = (int)($_POST['rating'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');

            if ($rating < 1 || $rating > 5) {
                $actionError = 'Choose a rating from 1 to 5.';
            } else {
                $freelancerService->review(
                    $application['id'],
                    $currentUser['id'],
                    $application['clientId'],
                    $rating,
                    $comment
                );
                $application['freelancerReview'] = [
                    'rating' => $rating,
                    'comment' => $comment,
                ];
            }
        }
    }

    if ($action === 'complete_job') {
        if (!$application || $application['status'] !== 'accepted') {
            $actionError = 'You can only complete an accepted job.';
        } else {
            if ($freelancerService->completeApplication($application['id'])) {
                $application['status'] = 'completed';
            } else {
                $actionError = "Couldn't complete the job. Try again.";
            }
        }
    }
}

$canApply = !$application && $job && $job['status'] === 'open';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $job ? e($job['title']) : 'Job Not Found' ?> - URaket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col">
<header class="w-full border-b">
    <div class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between">
        <a href="../index.php" class="text-xl font-semibold tracking-tight">Campus Gig</a>

        <div class="flex items-center gap-6">
        <nav aria-label="Freelancer navigation" class="flex items-center gap-6">
            <a href="dashboard.php" class="text-sm">Dashboard</a>
            <a href="applications.php" class="text-sm">Applications</a>
            <a href="profile.php" class="text-sm">Profile</a>
        </nav>

        <form method="POST" action="">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="text-sm px-4 py-2 border">Log out</button>
        </form>
        </div>
    </div>
</header>

<main class="flex-1">
    <div class="max-w-3xl mx-auto px-20 py-16 flex flex-col gap-10">

        <?php if (!$job): ?>
            <p role="alert" class="text-sm"><?= e($pageError) ?></p>
        <?php else: ?>

            <div class="flex flex-col gap-1">
                <a href="../jobs/index.php?role=freelancer" class="text-xs self-start underline">
                    ← Back to browse jobs
                </a>

                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold"><?= e($job['title']) ?></h1>
                    <span class="text-xs border px-2 py-0.5"><?= e($job['status']) ?></span>
                </div>

                <p class="text-sm">
                    <?= e($job['jobType']) ?> · ₱<?= e(money($job['budget'])) ?>
                </p>

                <p class="text-sm">
                    Posted by <span class="font-medium"><?= e($job['clientName']) ?></span>
                </p>
            </div>

            <?php if (!empty($job['description'])): ?>
                <section class="flex flex-col gap-2">
                    <h2 class="text-lg font-medium">About this job</h2>
                    <p class="text-sm leading-relaxed"><?= e($job['description']) ?></p>
                </section>
            <?php endif; ?>

            <?php if (!empty($job['skills'])): ?>
                <section class="flex flex-col gap-2">
                    <h2 class="text-sm font-medium">Required skills</h2>

                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($job['skills'] as $skill): ?>
                            <span class="text-xs border px-2 py-1"><?= e($skill['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($actionError): ?>
                <p role="alert" class="text-sm"><?= e($actionError) ?></p>
            <?php endif; ?>

            <?php if ($reportSuccess): ?>
                <div role="status" class="border p-4 text-sm"><?= e($reportSuccess) ?></div>
            <?php endif; ?>

            <?php if ($canApply): ?>
                <section class="border p-5 flex flex-col gap-3">
                    <p class="text-sm font-medium">Interested in this job?</p>
                    <p class="text-xs">Submit one application with a short title and message.</p>

                    <form method="POST" class="flex flex-col gap-3">
                        <input type="hidden" name="action" value="apply">

                        <label class="text-sm font-medium" for="application-title">Application title</label>
                        <input id="application-title" name="title" type="text"
                               class="border px-3 py-2 text-sm"
                               placeholder="e.g. Graphic design application" required>

                        <label class="text-sm font-medium" for="application-message">Message</label>
                        <textarea id="application-message" name="message" rows="4"
                                  class="border px-3 py-2 text-sm"
                                  placeholder="Tell the client why you're a good fit." required></textarea>

                        <button
    type="submit"
    class="inline-flex items-center justify-center text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] px-5 py-2.5 self-start transition-all duration-150 ease-in-out cursor-pointer hover:bg-[#099639] hover:text-white hover:border-[#099639] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#099639] focus:ring-offset-1"
>
    Apply to this job
</button>
                    </form>
                </section>
            <?php endif; ?>

            <?php if (($application['status'] ?? '') === 'pending'): ?>
                <div class="border p-5 flex flex-col gap-1">
                    <p class="text-sm font-medium">Your application is in.</p>
                    <p class="text-sm">
                        <?= e($job['clientName']) ?> hasn't responded yet — you'll see a status change here once they do.
                    </p>
                </div>
            <?php endif; ?>

            <?php if (($application['status'] ?? '') === 'rejected'): ?>
                <div class="border p-5 flex flex-col gap-2">
                    <p class="text-sm">This job went to another freelancer.</p>
                    <a href="../jobs/index.php?role=freelancer" class="text-sm underline self-start">
                        Browse more open jobs
                    </a>
                </div>
            <?php endif; ?>

            <?php if (($application['status'] ?? '') === 'accepted'): ?>
                <div class="border p-5 flex flex-col gap-3">
                    <div class="flex flex-col gap-1">
                        <p class="text-sm font-medium">You're hired.</p>
                        <p class="text-sm">
                            Coordinate the work directly with <?= e($job['clientName']) ?>.
                            Once you're done with the work, mark the job as completed.
                        </p>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="complete_job">
                        <button type="submit" class="text-sm px-5 py-2.5 border self-start">
                            Complete Job
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (($application['status'] ?? '') === 'completed'): ?>
                <section class="border p-5 flex flex-col gap-4">
                    <h2 class="text-lg font-medium">Review <?= e($job['clientName']) ?></h2>

                    <?php if (!empty($application['freelancerReview'])): ?>
                        <div class="text-sm">
                            <p>Rating: <?= e($application['freelancerReview']['rating']) ?>/5</p>
                            <p><?= e($application['freelancerReview']['comment']) ?></p>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="flex flex-col gap-3">
                            <input type="hidden" name="action" value="review">

                            <label for="rating" class="text-sm font-medium">Rating</label>
                            <select id="rating" name="rating" class="border px-3 py-2 text-sm" required>
                                <option value="">Select rating</option>
                                <option value="5">5</option>
                                <option value="4">4</option>
                                <option value="3">3</option>
                                <option value="2">2</option>
                                <option value="1">1</option>
                            </select>

                            <label for="comment" class="text-sm font-medium">Comment</label>
                            <textarea id="comment" name="comment" rows="4"
                                      class="border px-3 py-2 text-sm"
                                      placeholder="Share your experience."></textarea>

                            <button type="submit" class="text-sm px-5 py-2.5 border self-start">
                                Submit review
                            </button>
                        </form>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section aria-labelledby="report-job-heading"
                     class="border-t pt-6 flex flex-col gap-2">
                <h2 id="report-job-heading" class="text-sm font-medium">
                    Something wrong with this job?
                </h2>

                <p class="text-xs">
                    If the job violates the rules or looks misleading, you can report it for admin review.
                </p>

                <form method="POST" class="flex flex-col gap-3 max-w-xl">
                    <input type="hidden" name="action" value="report">

                    <label for="reason" class="text-sm font-medium">Reason</label>
                    <select id="reason" name="reason" class="border px-3 py-2 text-sm" required>
                        <option value="">Select a reason</option>
                        <option value="Misleading">Misleading</option>
                        <option value="Inappropriate">Inappropriate</option>
                        <option value="Scam">Scam</option>
                        <option value="Other">Other</option>
                    </select>

                    <label for="note" class="text-sm font-medium">Note</label>
                    <textarea id="note" name="note" rows="3"
                              class="border px-3 py-2 text-sm"
                              placeholder="Optional additional information."></textarea>

                    <button type="submit" class="text-sm underline self-start">
                        Report this job
                    </button>
                </form>
            </section>

        <?php endif; ?>

    </div>
</main>
</body>
</html>
