<?php
// /client/profile.php
//
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Client.php';
$clientService = new Client($db);

$currentUser = $_SESSION['currentUser'] ?? null;
$action = $_POST['action'] ?? null;

if (!$currentUser || ($currentUser['role'] ?? '') !== 'client') {
    header('Location: ../auth/login.php');
    exit;
}

$client = $clientService->profile($currentUser['id']);
$postedJobs = $clientService->jobs($currentUser['id']);

if (!$client) {
    http_response_code(404);
    exit('Client profile not found.');
}

if ($action === 'logout') {
        session_destroy(); // Destroy system session file
        header('Location: ../index.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Profile | URaket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col bg-gray-50 text-gray-900">

<!-- Header / Navigation -->
<header class="w-full border-b bg-white sticky top-0 z-10">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        
        <a href="../index.php" class="text-xl font-bold tracking-tight text-black">
            URaket
        </a>

        <nav class="flex items-center gap-1 sm:gap-2" aria-label="Client navigation">
            <a href="dashboard.php" class="text-sm font-medium px-3 py-2 rounded text-gray-600 hover:text-black hover:bg-gray-100 transition">
                Dashboard
            </a>
            <a href="../jobs/index.php" class="text-sm font-medium px-3 py-2 rounded text-gray-600 hover:text-black hover:bg-gray-100 transition">
                Job Board
            </a>
            <form method="POST" action="">
                <input type="hidden" name="action" value="logout">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] transition-all duration-150 ease-in-out cursor-pointer hover:bg-black hover:text-white hover:border-black active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-1"
                > Log out
                </button>
            </form>
        </nav>

    </div>
</header>

<!-- Main Body -->
<main class="flex-1">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 sm:py-12 flex flex-col gap-8">

        <!-- Page Heading -->
        <section class="border-b pb-6">
            <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">
                Client Account
            </p>
            <h1 class="text-3xl font-bold tracking-tight mt-1">
                Profile
            </h1>
            <p class="text-sm text-gray-600 mt-1 max-w-2xl">
                View your profile information and manage the jobs you have posted on URaket.
            </p>
        </section>

        <!-- Profile Card -->
        <section class="bg-white border rounded-xl shadow-sm overflow-hidden" aria-labelledby="profile-information-heading">
            <div class="p-6 sm:p-8 flex flex-col gap-6">
                
                <!-- Avatar & Header Info -->
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-black text-white flex items-center justify-center text-2xl font-bold shrink-0">
                        <?= htmlspecialchars(strtoupper(substr($client['name'], 0, 1))) ?>
                    </div>
                    <div>
                        <h2 id="profile-information-heading" class="text-xl font-semibold text-gray-900">
                            <?= htmlspecialchars($client['name']) ?>
                        </h2>
                        <span class="inline-block mt-1 text-xs px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 font-medium">
                            Client Account
                        </span>
                    </div>
                </div>

                <hr class="border-gray-100">

                <!-- Profile Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                            College / Office
                        </span>
                        <p class="text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($client['collegeOrOffice']) ?>
                        </p>
                    </div>

                    <div class="md:col-span-2 flex flex-col gap-1">
                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                            About
                        </span>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            <?= htmlspecialchars($client['about']) ?>
                        </p>
                    </div>
                </div>

            </div>
        </section>

        <!-- Posted Jobs Section -->
        <section class="flex flex-col gap-4" aria-labelledby="posted-jobs-heading">
            
            <div class="flex items-center justify-between border-b pb-3">
                <div>
                    <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">
                        Your Activity
                    </p>
                    <h2 id="posted-jobs-heading" class="text-xl font-semibold text-gray-900">
                        Posted Jobs
                    </h2>
                </div>

                <?php if (!empty($postedJobs)): ?>
                    <span class="text-xs font-medium bg-gray-200 text-gray-800 px-2.5 py-1 rounded-full">
                        <?= count($postedJobs) ?> <?= count($postedJobs) === 1 ? 'job' : 'jobs' ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (empty($postedJobs)): ?>
                
                <!-- Empty State -->
                <div class="bg-white border border-dashed rounded-xl p-12 text-center flex flex-col items-center justify-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900">No jobs posted yet</h3>
                    <p class="text-sm text-gray-500 max-w-sm">
                        You haven't posted any jobs yet. Create your first job to start finding student freelancers.
                    </p>
                    <a href="create-job.php" class="mt-2 text-sm font-medium px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">
                        Create a job
                    </a>
                </div>

            <?php else: ?>

                <!-- Job List -->
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($postedJobs as $job): ?>
                        <?php 
                            $isOpen = strtolower($job['status']) === 'open';
                            $statusClasses = $isOpen 
                                ? 'bg-green-100 text-green-800 border-green-200' 
                                : 'bg-gray-100 text-gray-700 border-gray-200';
                        ?>
                        <article class="bg-white border rounded-xl p-5 shadow-sm hover:shadow-md transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border <?= $statusClasses ?>">
                                        <?= htmlspecialchars($job['status']) ?>
                                    </span>
                                </div>

                                <h3 class="text-lg font-semibold text-gray-900">
                                    <?= htmlspecialchars($job['title']) ?>
                                </h3>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                    <span class="font-medium text-gray-700">
                                        Budget: <strong class="text-gray-900">₱<?= number_format($job['budget'], 2) ?></strong>
                                    </span>
                                    <span>•</span>
                                    <span>
                                        Deadline: <strong class="text-gray-900"><?= htmlspecialchars($job['deadline']) ?></strong>
                                    </span>
                                </div>
                            </div>

                            <div class="sm:shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100">
                                <a href="job-detail.php?id=<?= urlencode($job['id']) ?>" class="inline-flex items-center gap-1 text-sm font-medium text-black hover:underline group">
                                    View job
                                    <span class="group-hover:translate-x-1 transition-transform" aria-hidden="true">&rarr;</span>
                                </a>
                            </div>

                        </article>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </section>

    </div>
</main>

<!-- Footer -->
<footer class="w-full border-t bg-white mt-auto">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 flex items-center justify-between text-xs text-gray-500">
        <p>&copy; <?= date('Y') ?> URaket. All rights reserved.</p>
        <p>Client Portal</p>
    </div>
</footer>

</body>
</html>