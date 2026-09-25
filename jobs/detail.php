<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$currentUser = $_SESSION['currentUser'] ?? null;
$role = $currentUser['role'] ?? null;
$jobId = $_GET['jobId'] ?? '';

if (!$currentUser || !in_array($role, ['admin', 'client', 'freelancer'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$statement = $db->prepare(
    'SELECT jobs.id, jobs.title, jobs.job_type, jobs.budget, jobs.description,
            jobs.status, jobs.deadline, jobs.client_id, users.full_name AS client_name,
            user_profiles.organization
     FROM jobs
     INNER JOIN users ON users.id = jobs.client_id
     LEFT JOIN user_profiles ON user_profiles.user_id = users.id
     WHERE jobs.id = ?'
);
$statement->bind_param('s', $jobId);
$statement->execute();
$job = $statement->get_result()->fetch_assoc() ?: null;
$statement->close();

if (!$job) {
    http_response_code(404);
    $error = 'Job not found.';
} else {
    $skillStatement = $db->prepare(
        'SELECT skills.name
         FROM job_skills
         INNER JOIN skills ON skills.id = job_skills.skill_id
         WHERE job_skills.job_id = ?
         ORDER BY skills.name'
    );
    $skillStatement->bind_param('s', $jobId);
    $skillStatement->execute();
    $skills = $skillStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $skillStatement->close();
}

$backUrl = $role === 'admin'
    ? '../admin/dashboard.php'
    : ($role === 'client' ? '../client/dashboard.php' : '../freelancer/dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($job['title'] ?? 'Job detail') ?> - URaket</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col">
<header class="w-full border-b">
    <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between">
        <a href="../index.php" class="text-xl font-semibold">URaket</a>
        <a href="<?= e($backUrl) ?>" class="text-sm underline">Back to dashboard</a>
    </div>
</header>
<main class="flex-1">
    <div class="max-w-3xl mx-auto px-6 py-16">
        <?php if (isset($error)): ?>
            <p role="alert" class="text-sm"><?= e($error) ?></p>
        <?php else: ?>
            <a href="<?= e($backUrl) ?>" class="text-xs underline">Back</a>
            <section class="mt-6 flex flex-col gap-5">
                <div>
                    <h1 class="text-2xl font-semibold"><?= e($job['title']) ?></h1>
                    <p class="text-sm mt-2">
                        <?= e($job['job_type']) ?> · ₱<?= number_format((float)$job['budget'], 2) ?> · <?= e($job['status']) ?>
                    </p>
                </div>
                <p class="text-sm leading-relaxed"><?= e($job['description']) ?></p>
                <p class="text-sm">
                    Posted by <?= e($job['client_name']) ?><?= $job['organization'] ? ' · ' . e($job['organization']) : '' ?>
                </p>
                <?php if (!empty($skills)): ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($skills as $skill): ?>
                            <span class="text-xs border px-2 py-1"><?= e($skill['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($job['deadline']): ?>
                    <p class="text-sm">Deadline: <?= e($job['deadline']) ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
