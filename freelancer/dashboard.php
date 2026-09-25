<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Freelancer.php';
require_once __DIR__ . '/../includes/functions.php';
$currentUser = $_SESSION['currentUser'] ?? null;

if (!$currentUser || ($currentUser['role'] ?? '') !== 'freelancer') {
    header('Location: ../auth/login.php');
    exit;
}

$freelancerService = new Freelancer($db);

$stats = $freelancerService->stats($currentUser['id']);

$applications = $freelancerService->applications($currentUser['id']);

$statusOrder = [
    'pending' => 0,
    'accepted' => 1,
    'completed' => 2,
    'rejected' => 3,
];

usort($applications, function ($a, $b) use ($statusOrder) {
    return ($statusOrder[$a['status']] ?? 99) <=> ($statusOrder[$b['status']] ?? 99);
});

$recommendedJobs = $freelancerService->openJobs();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Dashboard - Campus Gig</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col">
<header class="w-full border-b">
    <div class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between">
        <a href="../index.php" class="text-xl font-semibold tracking-tight">Campus Gig</a>

        <div class="flex items-center gap-6">
        <nav aria-label="Freelancer navigation" class="flex items-center gap-6">
            
            <!-- FIXED: Go to job board -->
            <a href="../jobs/index.php?role=freelancer" class="text-sm">Job board</a>
            
            <!-- FIXED: Stay in freelancer folder -->
            <a href="applications.php" class="text-sm">Applications</a>
            
            <!-- FIXED: Stay in freelancer folder -->
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

        <section class="flex flex-col gap-2">
            <h1 class="text-3xl font-semibold">
                <?php if (!empty($currentUser['fullName'])): ?>
                    Welcome back, <?= e($currentUser['fullName']) ?>
                <?php else: ?>
                    Freelancer dashboard
                <?php endif; ?>
            </h1>

            <p class="text-sm">
                Find campus gigs, track your applications, and manage your profile.
            </p>
        </section>

        <section aria-labelledby="applications-heading" class="flex flex-col gap-4">
            <div class="flex items-center justify-between gap-4">
                <h2 id="applications-heading" class="text-xl font-semibold">Recent applications</h2>
                <!-- FIXED: Stay in freelancer folder -->
                <a href="applications.php" class="text-sm underline">View all</a>
            </div>

            <?php if (count($applications) === 0): ?>
                <div class="border border-dashed p-8 flex flex-col gap-3">
                    <p class="text-sm font-medium">You haven't applied to any jobs yet.</p>
                    <!-- FIXED: Go to job board -->
                    <a href="../jobs/index.php?role=freelancer" class="text-sm underline">
                        Browse the job board
                    </a>
                </div>
            <?php else: ?>
                <ul class="flex flex-col border-t">
                    <?php foreach (array_slice($applications, 0, 4) as $application): ?>
                        <li class="border-b py-4">
                            <!-- FIXED: Stay in freelancer folder -->
                            <a href="job-detail.php?jobId=<?= urlencode($application['jobId']) ?>"
                               class="flex items-center justify-between gap-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-medium"><?= e($application['jobTitle']) ?></span>
                                    <span class="text-xs"><?= e($application['clientName']) ?></span>
                                </div>

                                <span class="text-xs border px-2 py-0.5">
                                    <?= e($application['status']) ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section aria-labelledby="recommended-jobs-heading" class="flex flex-col gap-4">
            <div class="flex items-center justify-between gap-4">
                <h2 id="recommended-jobs-heading" class="text-xl font-semibold">Open gigs</h2>
                <!-- FIXED: Go to job board -->
                <a href="../jobs/index.php?role=freelancer" class="text-sm underline">View job board</a>
            </div>

            <?php if (count($recommendedJobs) === 0): ?>
                <p class="text-sm">There are no open gigs to show right now.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach (array_slice($recommendedJobs, 0, 4) as $job): ?>
                        <article class="border p-5 flex flex-col gap-4">
                            <div class="flex flex-col gap-1">
                                <h3 class="text-base font-medium"><?= e($job['title']) ?></h3>
                                <span class="text-xs"><?= e($job['clientName']) ?></span>
                            </div>

                            <?php if (!empty($job['description'])): ?>
                                <p class="text-sm"><?= e($job['description']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($job['skills'])): ?>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($job['skills'] as $skill): ?>
                                        <span class="text-xs border px-2 py-1">
                                            <?= e($skill['name'] ?? $skill) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- FIXED: Stay in freelancer folder -->
                            <a href="job-detail.php?jobId=<?= urlencode($job['id']) ?>"
   class="inline-flex items-center justify-center text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] px-4 py-2 self-start transition-all duration-150 ease-in-out cursor-pointer hover:bg-[#099639] hover:text-white hover:border-[#099639] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#099639] focus:ring-offset-1">
    View job
</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>
</body>
</html>