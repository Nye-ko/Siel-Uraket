<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$currentUser = $_SESSION['currentUser'] ?? null;

$skillResult = $db->query('SELECT id, name FROM skills ORDER BY name');
$skills = $skillResult->fetch_all(MYSQLI_ASSOC);

$jobResult = $db->query(
    'SELECT jobs.id, jobs.title, jobs.job_type AS jobType, jobs.budget,
            jobs.client_id AS clientId, users.full_name AS clientName,
            jobs.status, jobs.description, jobs.deadline
     FROM jobs
     INNER JOIN users ON users.id = jobs.client_id
     WHERE jobs.status = "open"
     ORDER BY jobs.posted_at DESC'
);
$jobs = $jobResult->fetch_all(MYSQLI_ASSOC);

foreach ($jobs as &$job) {
    $skillStatement = $db->prepare(
        'SELECT skills.id, skills.name
         FROM job_skills
         INNER JOIN skills ON skills.id = job_skills.skill_id
         WHERE job_skills.job_id = ?
         ORDER BY skills.name'
    );
    $skillStatement->bind_param('s', $job['id']);
    $skillStatement->execute();
    $job['skills'] = $skillStatement->get_result()->fetch_all(MYSQLI_ASSOC);
    $skillStatement->close();
}
unset($job);

$applicationResult = $db->query(
    'SELECT id, job_id AS jobId, freelancer_id AS freelancerId, status
     FROM applications'
);
$applications = $applicationResult->fetch_all(MYSQLI_ASSOC);



/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function getUserApplications($applications, $freelancerId)
{
    return array_filter(
        $applications,
        function ($application) use ($freelancerId) {
            return $application['freelancerId'] === $freelancerId;
        }
    );
}

function hasAppliedToJob(
    $applications,
    $jobId,
    $freelancerId
) {
    foreach ($applications as $application) {
        if (
            $application['jobId'] === $jobId &&
            $application['freelancerId'] === $freelancerId
        ) {
            return true;
        }
    }

    return false;
}


/*
|--------------------------------------------------------------------------
| Unauthenticated
|--------------------------------------------------------------------------
*/

if (!$currentUser):
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Job Board | URaket</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col">

<header class="w-full border-b">
    <div
        class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between"
    >
        <a
            href="../index.php"
            class="text-xl font-semibold tracking-tight"
        >
            URaket
        </a>
    </div>
</header>

<main class="flex-1">
    <div
        class="max-w-[1440px] mx-auto px-20 py-16"
    >
        <section class="flex flex-col gap-4">
            <h1 class="text-xl font-semibold">
                Log in to continue
            </h1>

            <p class="text-sm">
                Please log in before accessing the job board.
            </p>

            <a
                href="../auth/login.php"
                class="text-sm underline self-start"
            >
                Log in
            </a>
        </section>
    </div>
</main>

</body>
</html>

<?php
exit;
endif;


/*
|--------------------------------------------------------------------------
| Unauthorized role
|--------------------------------------------------------------------------
*/

if (
    !isset($currentUser['role']) ||
    !in_array(
        $currentUser['role'],
        ['admin', 'freelancer', 'client'],
        true
    )
):
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Access Denied | URaket</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col">

<header class="w-full border-b">
    <div
        class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between"
    >
        <a
            href="../index.php"
            class="text-xl font-semibold tracking-tight"
        >
            URaket
        </a>
    </div>
</header>

<main class="flex-1">
    <div
        class="max-w-[1440px] mx-auto px-20 py-16"
    >
        <section class="flex flex-col gap-4">
            <h1 class="text-xl font-semibold">
                Access denied
            </h1>

            <p class="text-sm">
                Your account does not have permission to access
                the job board.
            </p>

            <a
                href="../index.php"
                class="text-sm underline self-start"
            >
                Back home
            </a>
        </section>
    </div>
</main>

</body>
</html>

<?php
exit;
endif;


/*
|--------------------------------------------------------------------------
| Freelancer data
|--------------------------------------------------------------------------
*/

$isFreelancer =
    $currentUser['role'] === 'freelancer';

$isClient =
    $currentUser['role'] === 'client';

$isAdmin =
    $currentUser['role'] === 'admin';

$visibleJobs = [];

if ($isFreelancer) {

    $freelancerId =
        $currentUser['id'];

    foreach ($jobs as $job) {

        if ($job['status'] !== 'open') {
            continue;
        }

        $job['hasApplied'] = hasAppliedToJob(
            $applications,
            $job['id'],
            $freelancerId
        );

        $visibleJobs[] = $job;
    }
}


/*
|--------------------------------------------------------------------------
| Client jobs
|--------------------------------------------------------------------------
*/

$clientJobs = [];

if ($isClient) {

    foreach ($jobs as $job) {

        if (
            $job['clientId'] !==
            $currentUser['id']
        ) {
            continue;
        }

        $applicantCount = 0;

        foreach (
            $applications
            as $application
        ) {
            if (
                $application['jobId'] ===
                $job['id']
            ) {
                $applicantCount++;
            }
        }

        $job['applicantCount'] =
            $applicantCount;

        $clientJobs[] = $job;
    }
}

if ($isAdmin) {
    foreach ($jobs as $job) {
        if ($job['status'] === 'open') {
            $visibleJobs[] = $job;
        }
    }
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
        Job Board | URaket
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="min-h-screen flex flex-col">

<header class="w-full border-b">

    <div
        class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between"
    >

        <a
            href="../index.php"
            class="text-xl font-semibold tracking-tight"
        >
            URaket
        </a>

        <nav
            aria-label="<?= $isClient
                ? 'Client navigation'
                : 'Freelancer navigation' ?>"
            class="flex items-center gap-6"
        >

            <?php if ($isFreelancer): ?>
                <header class="w-full border-b">
                    <div class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between">

                    <div class="flex items-center gap-6">
                        <nav aria-label="Freelancer navigation" class="flex items-center gap-6">

                    <a href="../freelancer/index.php?role=freelancer" class="text-sm">Job board</a>
            
                    <a href="../freelancer/applications.php" class="text-sm">Applications</a>
            
                    <a href="../freelancer/profile.php" class="text-sm">Profile</a>
                    </nav>

                    <form method="POST" action="../process/logoutprocess.php" class="flex items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] transition-all duration-150 ease-in-out cursor-pointer hover:bg-black hover:text-white hover:border-black active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-1">
                        Log out
                    </button>
                    </form>

        </div>
    </div>

            <?php else: ?>

                <a
                    href="../client/dashboard.php"
                    class="text-sm"
                >
                    Dashboard
                </a>

                <a
                    href="../client/profile.php"
                    class="text-sm"
                >
                    Profile
                </a>

                <form method="POST" action="../process/logoutprocess.php" class="flex items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] transition-all duration-150 ease-in-out cursor-pointer hover:bg-black hover:text-white hover:border-black active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-1">
                        Log out
                    </button>
                </form>

            <?php endif; ?>

        </nav>

    </div>

</header>


<main class="flex-1">

    <div
        class="max-w-[1440px] mx-auto px-20 py-16 flex flex-col gap-10"
    >

<?php if ($isFreelancer): ?>

        <!-- =======================================================
             FREELANCER JOB BOARD
        ======================================================== -->

        <div class="flex flex-col gap-2">

            <h1 class="text-2xl font-semibold">
                Job board
            </h1>

            <p class="text-sm">
                Find jobs that match your skills.
            </p>

        </div>


        <!-- Search -->

        <section
            aria-labelledby="search-heading"
            class="flex flex-col gap-2"
        >

            <h2
                id="search-heading"
                class="text-sm font-medium"
            >
                Search jobs
            </h2>

            <input
                id="searchJobs"
                type="search"
                placeholder="Search by title, description, or skill…"
                class="border px-3 py-2 text-sm w-full max-w-xl"
            >

        </section>


        <!-- Filters -->

        <section
            aria-labelledby="filter-heading"
            class="flex flex-col gap-3"
        >

            <div
                class="flex items-center justify-between gap-4"
            >

                <h2
                    id="filter-heading"
                    class="text-sm font-medium"
                >
                    Filter by skill
                </h2>

                <button
                    id="clearFilters"
                    type="button"
                    class="text-xs underline hidden"
                >
                    Clear filters
                </button>

            </div>


            <div class="flex flex-wrap gap-2">

                <?php foreach (
                    $skills
                    as $skill
                ): ?>

                    <button
                        type="button"
                        class="skill-filter text-xs px-3 py-1.5 border"
                        data-skill-id="<?= e($skill['id']) ?>"
                    >
                        <?= e($skill['name']) ?>
                    </button>

                <?php endforeach; ?>

            </div>

        </section>


        <!-- Results -->

        <section
            aria-labelledby="results-heading"
            class="flex flex-col gap-4"
        >

            <div
                class="flex items-center justify-between gap-4"
            >

                <h2
                    id="results-heading"
                    class="text-sm font-medium"
                >
                    <span id="jobCount">
                        <?= count($visibleJobs) ?>
                    </span>

                    open job<?= count($visibleJobs) === 1
                        ? ''
                        : 's' ?>
                </h2>

                <a
                    href="../freelancer/applications.php"
                    class="text-sm underline"
                >
                    My applications
                </a>

            </div>


            <div
                id="noResults"
                class="border border-dashed p-10 flex flex-col items-center gap-1 text-center hidden"
            >

                <p class="text-sm font-medium">
                    No jobs match your search.
                </p>

                <p class="text-xs">
                    Try clearing a filter or checking back later.
                </p>

            </div>


            <div
                id="jobGrid"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >

                <?php foreach (
                    $visibleJobs
                    as $job
                ): ?>

                    <article
                        class="job-card border p-5 flex flex-col gap-4"
                        data-title="<?= e(strtolower($job['title'])) ?>"
                        data-description="<?= e(strtolower($job['description'])) ?>"
                        data-skills="<?= e(
                            strtolower(
                                implode(
                                    ' ',
                                    array_map(
                                        function ($skill) {
                                            return $skill['name'];
                                        },
                                        $job['skills']
                                    )
                                )
                            )
                        ) ?>"
                        data-skill-ids="<?= e(
                            implode(
                                ',',
                                array_map(
                                    function ($skill) {
                                        return $skill['id'];
                                    },
                                    $job['skills']
                                )
                            )
                        ) ?>"
                    >

                        <a
                            href="<?= ($isFreelancer
                                ? '../freelancer/job-detail.php?jobId='
                                : ($isClient
                                    ? '../client/job-detail.php?jobId='
                                    : 'detail.php?role=admin&jobId='))
                                . rawurlencode($job['id']) ?>"
                            class="flex flex-col gap-3"
                        >

                            <div class="flex flex-col gap-1">

                                <h3 class="text-sm font-medium">
                                    <?= e($job['title']) ?>
                                </h3>

                                <span class="text-xs">
                                    <?= e($job['jobType']) ?>
                                    · ₱<?= number_format(
                                        $job['budget']
                                    ) ?>
                                </span>

                                <span class="text-xs">
                                    Posted by
                                    <?= e($job['clientName']) ?>
                                </span>

                            </div>


                            <p class="text-xs">
                                <?= e($job['description']) ?>
                            </p>


                            <div
                                class="flex flex-wrap gap-2"
                            >

                                <?php foreach (
                                    $job['skills']
                                    as $skill
                                ): ?>

                                    <span
                                        class="text-xs border px-2 py-1"
                                    >
                                        <?= e(
                                            $skill['name']
                                        ) ?>
                                    </span>

                                <?php endforeach; ?>

                            </div>

                        </a>


                        <?php if (
                            $job['hasApplied']
                        ): ?>

                            <a
                                href="<?= ($isFreelancer
                                    ? '../freelancer/job-detail.php?jobId='
                                    : ($isClient
                                        ? '../client/job-detail.php?jobId='
                                        : 'detail.php?role=admin&jobId='))
                                    . rawurlencode($job['id']) ?>"
                                class="text-xs px-4 py-2 border self-start"
                            >
                                View application
                            </a>

                        <?php else: ?>

                            <button
    type="button"
    class="apply-button inline-flex items-center justify-center text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] px-4 py-2 self-start transition-all duration-150 ease-in-out cursor-pointer hover:bg-[#099639] hover:text-white hover:border-[#099639] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#099639] focus:ring-offset-1"
    data-job-id="<?= e($job['id']) ?>"
    data-job-title="<?= e($job['title']) ?>"
>
    Apply
</button>

                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>


<?php else: ?>

        <!-- =======================================================
             CLIENT JOB BOARD
        ======================================================== -->

        <div
            class="flex items-center justify-between gap-4"
        >

            <div class="flex flex-col gap-2">

                <h1 class="text-2xl font-semibold">
                    Your job posts
                </h1>

                <p class="text-sm">
                    Manage applicants and track progress here.
                </p>

            </div>


            <a
    href="../client/create-job.php"
    class="inline-flex items-center justify-center text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] px-5 py-2.5 transition-all duration-150 ease-in-out cursor-pointer hover:bg-[#099639] hover:text-white hover:border-[#099639] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#099639] focus:ring-offset-1"
>
    Post a job
</a>

        </div>


        <?php if (
            count($clientJobs) === 0
        ): ?>

            <div
                class="border border-dashed p-10 flex flex-col items-center gap-2 text-center"
            >

                <p class="text-sm font-medium">
                    You haven't posted a job yet.
                </p>

                <a
                    href="../client/create-job.php"
                    class="text-sm px-4 py-2 border"
                >
                    Post your first job
                </a>

            </div>

        <?php else: ?>

            <ul class="flex flex-col border-t">

                <?php foreach (
                    $clientJobs
                    as $job
                ): ?>

                    <li
                        class="border-b py-4"
                    >

                        <a
                            href="../client/job-detail.php?jobId=<?= e($job['id']) ?>"
                            class="flex items-center justify-between gap-4"
                        >

                            <div
                                class="flex flex-col gap-1"
                            >

                                <span
                                    class="text-sm font-medium"
                                >
                                    <?= e(
                                        $job['title']
                                    ) ?>
                                </span>

                                <span class="text-xs">

                                    <?= e(
                                        $job['jobType']
                                    ) ?>

                                    · ₱<?= number_format(
                                        $job['budget']
                                    ) ?>

                                    · <?= e(
                                        $job['applicantCount']
                                    ) ?>

                                    applicant<?= $job[
                                        'applicantCount'
                                    ] === 1
                                        ? ''
                                        : 's' ?>

                                </span>

                            </div>


                            <span
                                class="text-xs border px-2 py-0.5"
                            >
                                <?= e(
                                    $job['status']
                                ) ?>
                            </span>

                        </a>

                    </li>

                <?php endforeach; ?>

            </ul>

        <?php endif; ?>

<?php endif; ?>

    </div>

</main>


<!-- =============================================================
     APPLY MODAL
============================================================== -->

<?php if ($isFreelancer): ?>

<div
    id="applyModal"
    class="fixed inset-0 bg-black/40 hidden items-center justify-center p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="applyModalTitle"
>

    <div
        class="bg-white border p-6 w-full max-w-lg"
    >

        <div class="flex flex-col gap-2 mb-6">

            <h2
                id="applyModalTitle"
                class="text-xl font-semibold"
            >
                Apply to this job
            </h2>

            <p
                id="selectedJobTitle"
                class="text-sm"
            ></p>

        </div>


        <form
            id="applyForm"
            action="/applications/create.php"
            method="POST"
            class="flex flex-col gap-5"
        >

            <input
                type="hidden"
                name="job_id"
                id="applyJobId"
            >


            <div class="flex flex-col gap-1.5">

                <label
                    for="applicationTitle"
                    class="text-sm font-medium"
                >
                    Application title
                </label>

                <input
                    id="applicationTitle"
                    name="title"
                    type="text"
                    required
                    class="border px-3 py-2 text-sm"
                    placeholder="e.g. Graphic designer application"
                >

            </div>


            <div class="flex flex-col gap-1.5">

                <label
                    for="applicationMessage"
                    class="text-sm font-medium"
                >
                    Message
                </label>

                <textarea
                    id="applicationMessage"
                    name="message"
                    rows="5"
                    required
                    class="border px-3 py-2 text-sm"
                    placeholder="Tell the client why you're a good fit."
                ></textarea>

            </div>


            <div
                class="flex items-center gap-3"
            >

                <button
    type="submit"
    class="inline-flex items-center justify-center text-sm font-medium text-black bg-white border border-[#D3D3D3] rounded-[6px] px-5 py-2.5 transition-all duration-150 ease-in-out cursor-pointer hover:bg-[#099639] hover:text-white hover:border-[#099639] active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#099639] focus:ring-offset-1"
>
    Submit application
</button>

                <button
                    type="button"
                    id="closeApplyModal"
                    class="text-sm underline"
                >
                    Cancel
                </button>

            </div>

        </form>

    </div>

</div>

<?php endif; ?>


<script>

<?php if ($isFreelancer): ?>

/*
|--------------------------------------------------------------------------
| Freelancer search + skill filtering
|--------------------------------------------------------------------------
*/

const searchInput =
    document.getElementById('searchJobs');

const clearFiltersButton =
    document.getElementById('clearFilters');

const jobCards =
    Array.from(
        document.querySelectorAll('.job-card')
    );

const jobCount =
    document.getElementById('jobCount');

const noResults =
    document.getElementById('noResults');

const skillButtons =
    Array.from(
        document.querySelectorAll('.skill-filter')
    );

let selectedSkillIds = [];


function updateJobs()
{
    const query =
        searchInput.value
            .trim()
            .toLowerCase();

    let visibleCount = 0;


    jobCards.forEach(function (card) {

        const title =
            card.dataset.title || '';

        const description =
            card.dataset.description || '';

        const skills =
            card.dataset.skills || '';

        const skillIds =
            (
                card.dataset.skillIds || ''
            )
            .split(',')
            .filter(Boolean);


        const matchesSearch =
            !query ||
            title.includes(query) ||
            description.includes(query) ||
            skills.includes(query);


        const matchesSkills =
            selectedSkillIds.length === 0 ||
            skillIds.some(function (id) {
                return selectedSkillIds.includes(id);
            });


        const shouldShow =
            matchesSearch &&
            matchesSkills;


        card.classList.toggle(
            'hidden',
            !shouldShow
        );


        if (shouldShow) {
            visibleCount++;
        }

    });


    jobCount.textContent =
        visibleCount;


    noResults.classList.toggle(
        'hidden',
        visibleCount !== 0
    );


    clearFiltersButton.classList.toggle(
        'hidden',
        selectedSkillIds.length === 0 &&
        !searchInput.value
    );
}


searchInput.addEventListener(
    'input',
    updateJobs
);


skillButtons.forEach(function (button) {

    button.addEventListener(
        'click',
        function () {

            const skillId =
                button.dataset.skillId;


            if (
                selectedSkillIds.includes(
                    skillId
                )
            ) {

                selectedSkillIds =
                    selectedSkillIds.filter(
                        function (id) {
                            return id !== skillId;
                        }
                    );

                button.textContent =
                    button.textContent.replace(
                        '✓ ',
                        ''
                    );

            } else {

                selectedSkillIds.push(
                    skillId
                );

                button.textContent =
                    '✓ ' +
                    button.textContent;
            }


            updateJobs();
        }
    );

});


clearFiltersButton.addEventListener(
    'click',
    function () {

        selectedSkillIds = [];

        searchInput.value = '';


        skillButtons.forEach(
            function (button) {

                button.textContent =
                    button.textContent.replace(
                        '✓ ',
                        ''
                    );
            }
        );


        updateJobs();
    }
);


/*
|--------------------------------------------------------------------------
| Apply modal
|--------------------------------------------------------------------------
*/

// const applyModal =
//     document.getElementById('applyModal');

// const applyForm =
//     document.getElementById('applyForm');

// const applyJobId =
//     document.getElementById('applyJobId');

// const selectedJobTitle =
//     document.getElementById('selectedJobTitle');

// const closeApplyModal =
//     document.getElementById('closeApplyModal');


// document.querySelectorAll(
//     '.apply-button'
// ).forEach(function (button) {

//     button.addEventListener(
//         'click',
//         function () {

//             applyJobId.value =
//                 button.dataset.jobId;

//             selectedJobTitle.textContent =
//                 button.dataset.jobTitle;

//             applyModal.classList.remove(
//                 'hidden'
//             );

//             applyModal.classList.add(
//                 'flex'
//             );

//         }
//     );

// });


function closeModal()
{
    applyModal.classList.add(
        'hidden'
    );

    applyModal.classList.remove(
        'flex'
    );

    applyForm.reset();
}


closeApplyModal.addEventListener(
    'click',
    closeModal
);


applyModal.addEventListener(
    'click',
    function (event) {

        if (
            event.target ===
            applyModal
        ) {
            closeModal();
        }

    }
);

<?php endif; ?>

</script>

</body>
</html>