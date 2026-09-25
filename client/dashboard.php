<?php
session_start();

$currentUser = $_SESSION['currentUser'] ?? null;

if (!$currentUser || ($currentUser['role'] ?? '') !== 'client') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Client.php';
require_once __DIR__ . '/../includes/functions.php';
$clientService = new Client($db);

$jobs = $clientService->jobs($currentUser['id']);
$clientStats = $clientService->stats($currentUser['id']);
$totalJobs = $clientStats['totalJobs'];
$totalApplications = $clientStats['totalApplications'];
$openJobs = count(array_filter($jobs, fn($job) => $job['status'] === 'open'));
$closedJobs = count(array_filter($jobs, fn($job) => $job['status'] !== 'open'));
$pendingApplications = array_sum(array_column($jobs, 'pendingCount'));

// ---------------------------------------------------------------------
// Actions (Logout & Close Job)
// ---------------------------------------------------------------------

$liveMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Close job posting
    if ($action === 'close_job') {
        $jobId = $_POST['job_id'] ?? '';
        foreach ($jobs as &$job) {
            if ($job['id'] === $jobId && $job['status'] === 'open') {
                if ($clientService->closeJob($currentUser['id'], $jobId)) {
                    $job['status'] = 'closed';
                    $liveMessage = 'Job "' . $job['title'] . '" marked as closed.';
                }
                break;
            }
        }
        unset($job);
        $jobs = $clientService->jobs($currentUser['id']);
    }

    // Logout Action
    if ($action === 'logout') {
        $_SESSION = []; // Clear all session variables completely
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy(); // Destroy system session file
        header('Location: ../index.php');
        exit;
    }
}

// ---------------------------------------------------------------------
// Filters
// ---------------------------------------------------------------------

$allowedTabs = ['all', 'open', 'closed'];
$activeTab = $_GET['tab'] ?? 'all';

if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'all';
}

if ($activeTab === 'all') {
    $visibleJobs = $jobs;
} else {
    $visibleJobs = array_filter(
        $jobs,
        fn($j) => $j['status'] === $activeTab
    );
}

// ---------------------------------------------------------------------
// Job Board Link
// ---------------------------------------------------------------------
// job-detail.php requires a jobId.
// Use the first available job for the header Job Board link.

$jobBoardJobId = '';

if (!empty($jobs)) {
    $firstJob = reset($jobs);
    $jobBoardJobId = $firstJob['id'] ?? '';
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

    <title>Client Dashboard - URaket</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col bg-gray-50 text-gray-900">

<header class="w-full border-b bg-white">

    <div class="max-w-[1440px] mx-auto px-6 md:px-20 h-20 flex items-center justify-between">

        <div class="flex items-baseline gap-3">

            <a
                href="../index.php"
                class="text-xl font-semibold tracking-tight"
            >
                URaket
            </a>

            <span class="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-medium">
                Client
            </span>

        </div>

        <div class="flex items-center gap-4">

            <span class="text-sm text-gray-600 hidden sm:inline">
                <?= e($currentUser['name'] ?? 'Faculty / Staff') ?>
            </span>
            <nav aria-label="Client navigation" class="flex items-center gap-6">
                    
                    <!-- FIXED: Go to job board -->
                <a href="../jobs/index.php?role=client" class="text-sm">Job board</a>
                    
                <a href="../client/profile.php" class="text-sm">Profile</a>
            </nav>
            <form method="POST" action="">
                <input type="hidden" name="action" value="logout">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] transition-all duration-150 ease-in-out cursor-pointer hover:bg-black hover:text-white hover:border-black active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-1"
                > Log out
                </button>
                </form>
        </div>

    </div>

</header>

<main class="flex-1">

    <div class="max-w-[1440px] mx-auto px-6 md:px-20 py-12 flex flex-col gap-10">

        <?php if ($liveMessage !== ''): ?>

            <div class="p-4 bg-green-50 border border-green-200 text-green-800 text-sm rounded">
                <?= e($liveMessage) ?>
            </div>

        <?php endif; ?>

        <!-- Page Heading & Post Job CTA -->

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">

            <div>

                <h1 class="text-2xl font-semibold">
                    Client Dashboard
                </h1>

                <p class="text-sm text-gray-600">
                    Manage your posted jobs and review student applications.
                </p>

            </div>

            <a
                href="create-job.php"
                class="inline-flex items-center justify-center text-sm font-medium px-5 py-2.5 bg-black text-white rounded hover:bg-gray-800 transition"
            >
                + Post a New Job
            </a>

        </div>

        <!-- Metric Cards -->

        <section
            aria-labelledby="stats-heading"
            class="flex flex-col gap-4"
        >

            <h2
                id="stats-heading"
                class="text-lg font-medium"
            >
                Overview
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <div class="bg-white border p-5 rounded flex flex-col gap-2">

                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total Job Posts
                    </span>

                    <span class="text-3xl font-semibold">
                        <?= e($totalJobs) ?>
                    </span>

                </div>

                <div class="bg-white border p-5 rounded flex flex-col gap-2">

                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Active Jobs
                    </span>

                    <span class="text-3xl font-semibold text-green-600">
                        <?= e($openJobs) ?>
                    </span>

                </div>

                <div class="bg-white border p-5 rounded flex flex-col gap-2">

                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total Applications Received
                    </span>

                    <span class="text-3xl font-semibold">
                        <?= e($totalApplications) ?>
                    </span>

                </div>

                <div class="bg-white border p-5 rounded flex flex-col gap-2">

                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Applications Needing Review
                    </span>

                    <span class="text-3xl font-semibold text-amber-600">
                        <?= e($pendingApplications) ?>
                    </span>

                </div>

            </div>

        </section>

        <!-- Posted Jobs List -->

        <section
            aria-labelledby="jobs-heading"
            class="flex flex-col gap-6"
        >

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4">

                <h2
                    id="jobs-heading"
                    class="text-lg font-medium"
                >
                    Your Postings
                </h2>

                <!-- Tabs -->

                <div
                    role="tablist"
                    class="flex gap-2"
                >

                    <?php foreach (
                        ['all' => 'All', 'open' => 'Active', 'closed' => 'Closed']
                        as $tabId => $tabLabel
                    ): ?>

                        <?php $isSelected = $activeTab === $tabId; ?>

                        <a
                            href="?tab=<?= urlencode($tabId) ?>"
                            class="text-xs px-3 py-1.5 border rounded <?= $isSelected ? 'bg-black text-white border-black' : 'bg-white text-gray-700 hover:bg-gray-100' ?>"
                        >
                            <?= e($tabLabel) ?>
                        </a>

                    <?php endforeach; ?>

                </div>

            </div>

            <?php if (empty($visibleJobs)): ?>

                <div class="bg-white border border-dashed p-12 text-center rounded flex flex-col items-center gap-2">

                    <p class="text-sm font-medium">
                        No job postings found.
                    </p>

                    <p class="text-xs text-gray-500">
                        Post a new job opportunity to start receiving student applications.
                    </p>

                </div>

            <?php else: ?>

                <div class="bg-white border rounded divide-y">

                    <?php foreach ($visibleJobs as $job): ?>

                        <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">

                            <div class="flex flex-col gap-1">

                                <div class="flex items-center gap-2">

                                    <!-- UPDATED: Job title now opens client/job-detail.php -->

                                    <a
                                        href="job-detail.php?jobId=<?= e($job['id']) ?>"
                                        class="text-base font-medium hover:underline"
                                    >
                                        <?= e($job['title']) ?>
                                    </a>

                                    <span
                                        class="text-xs px-2 py-0.5 rounded <?= $job['status'] === 'open' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' ?>"
                                    >
                                        <?= e(ucfirst($job['status'])) ?>
                                    </span>

                                </div>

                                <div class="text-xs text-gray-500 flex items-center gap-3">

                                    <span>
                                        <?= e($job['category']) ?>
                                    </span>

                                    <span>
                                        •
                                    </span>

                                    <span>
                                        <?= e($job['budget']) ?>
                                    </span>

                                    <span>
                                        •
                                    </span>

                                    <span>
                                        Posted <?= e(formatDate($job['postedAt'])) ?>
                                    </span>

                                </div>

                            </div>

                            <div class="flex items-center gap-3">

                                <!-- UPDATED: Applications link opens client/job-detail.php -->

                                <a
                                    href="job-detail.php?jobId=<?= e($job['id']) ?>"
                                    class="text-xs px-3 py-2 border rounded bg-gray-50 hover:bg-gray-100 transition"
                                >
                                    Applications (<?= e($job['applicationsCount']) ?>)

                                    <?php if ($job['pendingCount'] > 0): ?>

                                        <span class="ml-1 px-1.5 py-0.5 bg-amber-200 text-amber-900 rounded-full font-bold">
                                            <?= e($job['pendingCount']) ?>
                                        </span>

                                    <?php endif; ?>

                                </a>

                                <?php if ($job['status'] === 'open'): ?>

                                    <form
                                        method="POST"
                                        action=""
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="close_job"
                                        >

                                        <input
                                            type="hidden"
                                            name="job_id"
                                            value="<?= e($job['id']) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="text-xs px-3 py-2 border text-red-600 hover:bg-red-50 rounded transition"
                                        >
                                            Close Job
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>

</body>
</html>