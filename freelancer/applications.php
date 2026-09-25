<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Freelancer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

/*
 * Freelancer Applications
 * Converted from FreelancerApplicationsPage.jsx
 */

$currentUser = $_SESSION['currentUser'] ?? null;

if (!$currentUser) {
    header("Location: ../login.php");
    exit;
}

if (($currentUser['role'] ?? '') !== 'freelancer') {
    header("Location: ../index.php");
    exit;
}

$freelancerService = new Freelancer($db);
$applications = $freelancerService->applications($currentUser['id']);

$statusOrder = [
    'pending' => 0,
    'accepted' => 1,
    'completed' => 2,
    'rejected' => 3,
];

$statusLabels = [
    'pending' => 'Pending',
    'accepted' => 'Accepted',
    'completed' => 'Completed',
    'rejected' => 'Rejected',
];

usort($applications, function ($a, $b) use ($statusOrder) {
    return ($statusOrder[$a['status']] ?? 99) <=> ($statusOrder[$b['status']] ?? 99);
});

$counts = [
    'pending' => 0,
    'accepted' => 0,
    'completed' => 0,
    'rejected' => 0,
];

foreach ($applications as $application) {
    if (isset($counts[$application['status']])) {
        $counts[$application['status']]++;
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatDate($isoString) {
    $timestamp = strtotime($isoString);
    return $timestamp ? date('M j, Y', $timestamp) : $isoString;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Applications - URaket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col">
<header class="w-full border-b">
    <div class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between">
        <a href="../index.php" class="text-xl font-semibold tracking-tight">URaket</a>

        <div class="flex items-center gap-6">
        <nav aria-label="Freelancer navigation" class="flex items-center gap-6">
            <a href="dashboard.php" class="text-sm">Dashboard</a>
            <a href="../jobs/index.php?role=freelancer" class="text-sm">Job board</a>
            <a href="profile.php" class="text-sm">Profile</a>
        </nav>

        <form method="POST" action="">
            <input type="hidden" name="action" value="logout">
            <button
    type="submit"
    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] transition-all duration-150 ease-in-out cursor-pointer hover:bg-black hover:text-white hover:border-black active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-1"
>
    Log out
</button>
        </form>
        </div>
    </div>
</header>

<main class="flex-1">
    <div class="max-w-[1440px] mx-auto px-20 py-16 flex flex-col gap-10">

        <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-semibold">Your applications</h1>
            <p class="text-sm">Track where each application stands.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <?php foreach ([
                ['label' => 'Pending', 'value' => $counts['pending']],
                ['label' => 'Accepted', 'value' => $counts['accepted']],
                ['label' => 'Completed', 'value' => $counts['completed']],
                ['label' => 'Rejected', 'value' => $counts['rejected']],
            ] as $card): ?>
                <div class="border p-4 flex flex-col gap-1">
                    <span class="text-xs"><?= e($card['label']) ?></span>
                    <span class="text-xl font-semibold"><?= e($card['value']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($applications) === 0): ?>
            <div class="border border-dashed p-10 flex flex-col items-center gap-3 text-center">
                <p class="text-sm font-medium">You haven't applied to any jobs yet.</p>
                <a href="../job-board.php?role=freelancer" class="text-sm px-4 py-2 border">
                    Browse the job board
                </a>
            </div>
        <?php else: ?>
            <section aria-labelledby="applications-list-heading" class="flex flex-col gap-4">
                <h2 id="applications-list-heading" class="text-lg font-medium">Applications</h2>

                <ul class="flex flex-col border-t">
                    <?php foreach ($applications as $application): ?>
                        <li class="border-b py-5">
                            <a href="job-detail.php?jobId=<?= urlencode($application['jobId']) ?>"
                               class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-medium"><?= e($application['jobTitle']) ?></span>
                                    <span class="text-xs"><?= e($application['clientName']) ?></span>
                                    <span class="text-xs">
                                        Applied <?= e(formatDate($application['appliedAt'])) ?>
                                    </span>
                                </div>

                                <span class="text-xs border px-2 py-1 self-start sm:self-auto">
                                    <?= e($statusLabels[$application['status']] ?? $application['status']) ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

    </div>
</main>
</body>
</html>