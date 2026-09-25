<?php
// freelancer/profile.php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Freelancer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$freelancerId = $_GET['freelancerId'] ?? ($_SESSION['currentUser']['id'] ?? '');

$profile = null;
$isLoading = true;
$loadError = "";

try {
    $profile = (new Freelancer($db))->profile($freelancerId);
} catch (Exception $error) {
    $loadError = "Couldn't load this freelancer's profile.";
}

$isLoading = false;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($profile['fullName'] ?? 'Freelancer Profile') ?> - URaket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="w-full border-b">
        <div class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between">
            <!-- Brand Link -->
            <a href="../index.php" class="text-xl font-semibold tracking-tight">
                URaket
            </a>

            <!-- Navigation Links -->
            <div class="flex items-center gap-6">
            <nav aria-label="Freelancer navigation" class="flex items-center gap-6">
                <a href="dashboard.php" class="text-sm">Dashboard</a>
                <a href="../jobs/index.php?role=freelancer" class="text-sm">Job board</a>
                <a href="applications.php" class="text-sm">Applications</a>
                <a href="profile.php" class="text-sm font-semibold">Profile</a>
            </nav>

            <form method="POST" action="">
                <input type="hidden" name="action" value="logout">
                <button
    type="submit"
    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] transition-all duration-150 ease-in-out cursor-pointer hover:bg-black hover:text-white hover:border-black active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-1">
    Log out
</button>
            </form>
            </div>

        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        <div class="max-w-[1440px] mx-auto px-20 py-16">
            <div class="max-w-2xl flex flex-col gap-10">

                <!-- Back Link -->
                <a href="dashboard.php" class="text-xs underline self-start">
                    ← Back to dashboard
                </a>

                <?php if ($isLoading): ?>
                    <p class="text-sm">Loading…</p>

                <?php elseif ($loadError): ?>
                    <p role="alert" class="text-sm text-red-600">
                        <?= htmlspecialchars($loadError) ?>
                    </p>

                <?php elseif ($profile): ?>

                    <!-- Heading -->
                    <section class="flex flex-col gap-2">
                        <h1 class="text-2xl font-semibold">
                            <?= htmlspecialchars($profile['fullName']) ?>
                        </h1>
                        <?php if (!empty($profile['college'])): ?>
                            <p class="text-sm text-gray-600">
                                <?= htmlspecialchars($profile['college']) ?>
                            </p>
                        <?php endif; ?>
                    </section>

                    <!-- About -->
                    <?php if (!empty($profile['about'])): ?>
                        <section aria-labelledby="about-heading" class="flex flex-col gap-2">
                            <h2 id="about-heading" class="text-sm font-medium">About</h2>
                            <p class="text-sm"><?= htmlspecialchars($profile['about']) ?></p>
                        </section>
                    <?php endif; ?>

                    <!-- Resume -->
                    <?php if (!empty($profile['resumeLink'])): ?>
                        <section aria-labelledby="resume-heading" class="flex flex-col gap-2">
                            <h2 id="resume-heading" class="text-sm font-medium">Resume</h2>
                            <a href="<?= htmlspecialchars($profile['resumeLink']) ?>" target="_blank" rel="noopener noreferrer" class="text-sm underline">
                                View resume
                            </a>
                        </section>
                    <?php endif; ?>

                    <!-- Skills -->
                    <section aria-labelledby="skills-heading" class="flex flex-col gap-3">
                        <h2 id="skills-heading" class="text-sm font-medium">Skills</h2>
                        <?php if (!empty($profile['skills'])): ?>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($profile['skills'] as $skill): ?>
                                    <span class="text-xs border px-2 py-1">
                                        <?= htmlspecialchars($skill['name']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-sm">No skills listed yet.</p>
                        <?php endif; ?>
                    </section>

                    <!-- Reviews -->
                    <section aria-labelledby="reviews-heading" class="flex flex-col gap-4">
                        <h2 id="reviews-heading" class="text-lg font-medium">Reviews</h2>
                        <?php if (empty($profile['reviews'])): ?>
                            <p class="text-sm">No reviews yet.</p>
                        <?php else: ?>
                            <ul class="flex flex-col gap-4">
                                <?php foreach ($profile['reviews'] as $review): ?>
                                    <li class="border p-4 flex flex-col gap-1">
                                        <span class="text-sm font-medium">
                                            <?= htmlspecialchars($review['clientName']) ?>
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            <?= htmlspecialchars($review['rating']) ?> / 5 stars
                                        </span>
                                        <p class="text-sm">
                                            <?= htmlspecialchars($review['comment']) ?>
                                        </p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>

                <?php endif; ?>

            </div>
        </div>
    </main>

</body>
</html>