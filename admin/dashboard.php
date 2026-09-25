<?php
session_start();

$currentUser = $_SESSION['currentUser'] ?? null;

if (!isset($_SESSION['currentUser']) || $_SESSION['currentUser']['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Admin.php';
require_once __DIR__ . '/../includes/functions.php';
$admin = new Admin($db);
$stats = $admin->stats();

$reports = $admin->getJobReports();

$liveMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // -------------------------------------------------------------
    // Resolve report
    // -------------------------------------------------------------

    if ($action === 'resolve_report') {
        $reportId = $_POST['report_id'] ?? '';

        if ($reportId !== '') {
            foreach ($reports as &$report) {
                if ($report['id'] === $reportId) {
                    if ($report['status'] === 'pending' && $admin->resolveReport($reportId)) {
                        header('Location: ?tab=all&report=' . urlencode($reportId));
                        exit;
                    }

                    break;
                }
            }

            unset($report);
        }
    }

    // -------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------

    if ($action === 'logout') {
        unset($_SESSION['currentUser']);

        header('Location: ../index.php');
        exit;
    }
}

// ---------------------------------------------------------------------
// Report filters
// ---------------------------------------------------------------------

$allowedTabs = ['pending', 'resolved', 'all'];

$activeTab = $_GET['tab'] ?? 'pending';

if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'pending';
}

$expandedReportId = $_GET['report'] ?? null;

$pendingCount = count(
    array_filter(
        $reports,
        fn($report) => $report['status'] === 'pending'
    )
);

if ($activeTab === 'all') {
    $visibleReports = $reports;
} else {
    $visibleReports = array_filter(
        $reports,
        fn($report) => $report['status'] === $activeTab
    );
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

    <title>Admin Dashboard - URaket</title>

    <!-- Tailwind CDN. Use your existing Tailwind build in production. -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col">

<header class="w-full border-b">
    <div
        class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between"
    >
        <div class="flex items-baseline gap-3">

            <a
                href="../index.php" 
                class="text-xl font-semibold tracking-tight"
            >
                URaket
            </a>

            <span class="text-sm">
                Admin
            </span>

        </div>

        <form method="POST" action="">
            <input
                type="hidden"
                name="action"
                value="logout"
            >

            <button
    type="submit"
    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] transition-all duration-150 ease-in-out cursor-pointer hover:bg-black hover:text-white hover:border-black active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-1"
>
    Log out
</button>
        </form>
    </div>
</header>

<main class="flex-1">

    <div
        class="max-w-[1440px] mx-auto px-20 py-16 flex flex-col gap-16"
    >

        <?php if ($liveMessage !== ''): ?>
            <p
                aria-live="polite"
                role="status"
                class="sr-only"
            >
                <?= e($liveMessage) ?>
            </p>
        <?php endif; ?>

        <!-- Page heading -->
        <div class="flex flex-col gap-2">

            <h1 class="text-2xl font-semibold">
                Dashboard
            </h1>

            <p class="text-sm">
                Platform-wide activity and moderation.
            </p>

        </div>

        <!-- Aggregate counts -->
        <section
            aria-labelledby="stats-heading"
            class="flex flex-col gap-6"
        >

            <h2
                id="stats-heading"
                class="text-lg font-medium"
            >
                Counts
            </h2>

            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"
            >

                <!-- Users -->
                <div class="border p-5 flex flex-col gap-3">

                    <span class="text-sm">
                        Users
                    </span>

                    <span
                        class="text-3xl font-semibold"
                        aria-live="off"
                    >
                        <?= $stats['totalUsers']?>
                    </span>

                    <dl class="flex flex-col gap-1">

                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <dt class="text-xs">
                                Freelancers
                            </dt>

                            <dd class="text-xs">
                                <?= $stats['totalFreelancers'] ?>
                            </dd>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <dt class="text-xs">
                                Clients
                            </dt>

                            <dd class="text-xs">
                                <?= $stats['totalClients'] ?>
                            </dd>
                        </div>

                    </dl>

                </div>

                <!-- Job posts -->
                <div class="border p-5 flex flex-col gap-3">

                    <span class="text-sm">
                        Job posts
                    </span>

                    <span
                        class="text-3xl font-semibold"
                        aria-live="off"
                    >
                        <?= $stats['totalJobPosts'] ?>
                    </span>

                    <dl class="flex flex-col gap-1">

                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <dt class="text-xs">
                                Open
                            </dt>

                            <dd class="text-xs">
                                <?= $stats['openJobPosts'] ?>
                            </dd>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <dt class="text-xs">
                                Closed
                            </dt>

                            <dd class="text-xs">
                                <?= $stats['closedJobPosts'] ?>
                            </dd>
                        </div>

                    </dl>

                </div>

                <!-- Applications -->
                <div class="border p-5 flex flex-col gap-3">

                    <span class="text-sm">
                        Applications
                    </span>

                    <span
                        class="text-3xl font-semibold"
                        aria-live="off"
                    >
                        <?= $stats['totalApplications'] ?>
                    </span>

                    <dl class="flex flex-col gap-1">

                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <dt class="text-xs">
                                Pending
                            </dt>

                            <dd class="text-xs">
                                <?= $stats['pendingApplications'] ?>
                            </dd>
                        </div>

                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <dt class="text-xs">
                                Accepted
                            </dt>

                            <dd class="text-xs">
                                <?= $stats['acceptedApplications'] ?>
                            </dd>
                        </div>

                    </dl>

                </div>

                <!-- Pending reports -->
                <div class="border p-5 flex flex-col gap-3">

                    <span class="text-sm">
                        Pending reports
                    </span>

                    <span
                        class="text-3xl font-semibold"
                        aria-live="off"
                    >
                        <?= $pendingCount ?>
                    </span>

                </div>

            </div>
        </section>

        <!-- Job reports -->
        <section
            aria-labelledby="reports-heading"
            class="flex flex-col gap-6"
        >

            <div
                class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
            >

                <h2
                    id="reports-heading"
                    class="text-lg font-medium"
                >
                    Job reports
                </h2>

                <div
                    role="tablist"
                    aria-label="Filter reports by status"
                    class="flex gap-2"
                >

                    <?php foreach (
                        [
                            'pending' => 'Pending',
                            'resolved' => 'Resolved',
                            'all' => 'All',
                        ] as $tabId => $tabLabel
                    ): ?>

                        <?php
                        $tabUrl = '?tab=' . urlencode($tabId);
                        $isSelected = $activeTab === $tabId;
                        ?>

                        <a
    href="<?= e($tabUrl) ?>"
    role="tab"
    aria-selected="<?= $isSelected ? 'true' : 'false' ?>"
    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-[6px] border transition-all duration-150 ease-in-out cursor-pointer <?= $isSelected 
        ? 'bg-black text-white border-black shadow-sm' 
        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:text-black' ?>"
>
    <span><?= e($tabLabel) ?></span>

    <?php if ($tabId === 'pending' && $pendingCount > 0): ?>
        <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-semibold rounded-full transition-colors <?= $isSelected 
            ? 'bg-white text-black' 
            : 'bg-gray-100 text-gray-800' ?>">
            <?= e($pendingCount) ?>
        </span>
    <?php endif; ?>
</a>

                    <?php endforeach; ?>

                </div>
            </div>

            <?php if ($liveMessage !== ''): ?>
                <p
                    role="status"
                    class="text-sm"
                >
                    <?= e($liveMessage) ?>
                </p>
            <?php endif; ?>

            <?php if (empty($visibleReports)): ?>

                <div
                    class="border border-dashed p-10 flex flex-col items-center gap-1 text-center"
                >

                    <p class="text-sm font-medium">

                        <?php if ($activeTab === 'pending'): ?>
                            No pending reports right now.
                        <?php else: ?>
                            No reports here.
                        <?php endif; ?>

                    </p>

                    <p class="text-xs">

                        <?php if ($activeTab === 'pending'): ?>
                            New reports from clients or freelancers will show up here.
                        <?php else: ?>
                            Switch tabs to see reports in a different state.
                        <?php endif; ?>

                    </p>

                </div>

            <?php else: ?>

                <ul class="flex flex-col border-t">

                    <?php foreach ($visibleReports as $report): ?>

                        <?php
                        $isExpanded =
                            $expandedReportId === $report['id'];

                        $detailUrl =
                            '?tab=' .
                            urlencode($activeTab) .
                            '&report=' .
                            urlencode($report['id']);
                        ?>

                        <li class="border-b">

                            <!-- Report row -->
                            <div
                                class="py-4 grid grid-cols-1 md:grid-cols-[1fr,auto,auto,auto] gap-3 md:items-center"
                            >

                                <!-- Job/report summary -->
                                <a
                                    href="<?= e($detailUrl) ?>"
                                    class="text-left flex flex-col gap-1"
                                    aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>"
                                >

                                    <span class="text-sm font-medium">
                                        <?= $report['jobTitle'] ?>
                                    </span>

                                    <span class="text-xs">
                                        Reason:
                                        <?= $report['reasonLabel'] ?>
                                    </span>

                                </a>

                                <!-- Reporter -->
                                <span class="text-xs">
                                    Reported by
                                    <?= $report['reporterName'] ?>
                                    (<?= $report['reporterRole'] ?>)
                                </span>

                                <!-- Date/status -->
                                <span class="text-xs">
                                    <?= formatDate($report['reportedAt']) ?>
                                    ·
                                    <?= $report['status'] ?>
                                </span>

                                <!-- Resolve action -->
                                <?php if ($report['status'] === 'pending'): ?>

                                    <form
                                        method="POST"
                                        action=""
                                        class="justify-self-start md:justify-self-end"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="resolve_report"
                                        >

                                        <input
                                            type="hidden"
                                            name="report_id"
                                            value="<?= $report['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="text-sm px-4 py-2 border"
                                        >
                                            Mark resolved
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span
                                        class="text-xs justify-self-start md:justify-self-end"
                                    >
                                        Resolved
                                    </span>

                                <?php endif; ?>

                            </div>

                            <!-- Expanded report details -->
                            <?php if ($isExpanded): ?>

                                <div
                                    id="report-detail-<?= $report['id'] ?>"
                                    class="pb-4 flex flex-col gap-2 text-sm max-w-2xl"
                                >

                                    <p>
                                        <strong>
                                            Job:
                                        </strong>

                                        <a
                                            href="../jobs/detail.php?role=admin&jobId=<?= urlencode($report['jobId']) ?>"
                                            class="underline"
                                        >
                                            View job detail
                                        </a>
                                    </p>

                                    <p>
                                        <strong>
                                            Reporter's note:
                                        </strong>

                                        <?= $report['note']
                                            ? e($report['note'])
                                            : 'No additional note provided.' ?>
                                    </p>

                                </div>

                            <?php endif; ?>

                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php endif; ?>

        </section>

    </div>

</main>

</body>
</html>