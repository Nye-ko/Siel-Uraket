<?php
session_start();

/*
 * Campus Gig - Signup Page
 *
 * Converted from the supplied SignupPage.jsx.
 * Uses PHP sessions instead of React localStorage.
 */

$MIN_PASSWORD_LENGTH = 8;

$ROLE_COPY = [
    'freelancer' => [
        'label' => 'Student',
        'description' => 'Find and apply jobs; build your profile.',
        'orgFieldLabel' => 'College',
        'orgFieldPlaceholder' => 'e.g. College of Engineering',
        'profilePath' => '../freelancer/profile.php',
    ],
    'client' => [
        'label' => 'Faculty / staff',
        'description' => 'Post jobs and hire students.',
        'orgFieldLabel' => 'College or office',
        'orgFieldPlaceholder' => "e.g. Registrar's Office",
        'profilePath' => '/client/profile.php',
    ],
];

$signupForm = $_SESSION['signupForm'] ?? [];
unset($_SESSION['signupForm']);

$selectedRole = $signupForm['role'] ?? ($_GET['role'] ?? null);

if (!array_key_exists($selectedRole, $ROLE_COPY)) {
    $selectedRole = null;
}

$fullName = $signupForm['fullName'] ?? '';
$email = $signupForm['email'] ?? '';
$password = '';
$confirmPassword = '';
$orgUnit = $signupForm['orgUnit'] ?? '';
$agreedToTerms = $signupForm['agreedToTerms'] ?? false;

$formError = $signupForm['formError'] ?? '';
$fieldErrors = $signupForm['fieldErrors'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create your account - URaket</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col">

<header class="w-full border-b">
    <div class="max-w-[1440px] mx-auto px-20 h-24 flex items-center">
        <a href="../index.php" class="text-xl font-semibold tracking-tight">
            URaket
        </a>
    </div>
</header>

<main class="flex-1 flex items-center justify-center px-4 sm:px-20">
    <div class="w-full max-w-[420px] flex flex-col gap-[28px] py-16">

        <!-- Title Header -->
        <div class="p-[10px] flex flex-col gap-2.5">
            <h1 class="text-[28px] font-medium leading-tight text-black">
                Create your account
            </h1>

            <p class="flex items-center gap-[10px] text-base text-black">
                <span>Already have one?</span>
                <a href="login.php" class="underline text-black hover:opacity-70 transition-opacity">
                    Log in
                </a>
            </p>
        </div>

        <?php if (!$selectedRole): ?>

            <!-- Role Selection State -->
            <section aria-labelledby="role-heading" class="w-full flex flex-col justify-start items-start gap-5">
    
    <!-- Heading -->
    <div class="flex flex-col gap-1 w-full">
        <h2 id="role-heading" class="text-[18px] font-medium text-black">
            Choose your role
        </h2>
        <p class="text-sm font-normal text-black">
            Your account can have one
        </p>
    </div>

    <!-- Role Buttons Container -->
    <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">

        <!-- Student / Freelancer Option -->
        <form method="GET" action="" class="w-full h-full flex">
            <input type="hidden" name="role" value="freelancer">
            <button
                type="submit"
                class="w-full h-full p-4 border border-black rounded-[4px] flex flex-col justify-start items-start gap-2 text-left hover:border-[#0DE255] hover:bg-[#0DE255]/5 transition cursor-pointer group"
            >
                <span class="text-[18px] font-medium text-black group-hover:text-[#0DE255] transition-colors">
                    <?= htmlspecialchars($ROLE_COPY['freelancer']['label'] ?? 'Student') ?>
                </span>
                <span class="text-sm font-normal text-black leading-normal">
                    <?= htmlspecialchars($ROLE_COPY['freelancer']['description'] ?? 'Find and apply jobs, and build your profile.') ?>
                </span>
            </button>
        </form>

        <!-- Faculty / Client Option -->
        <form method="GET" action="" class="w-full h-full flex">
            <input type="hidden" name="role" value="client">
            <button
                type="submit"
                class="w-full h-full p-4 border border-black rounded-[4px] flex flex-col justify-start items-start gap-2 text-left hover:border-[#0DE255] hover:bg-[#0DE255]/5 transition cursor-pointer group"
            >
                <span class="text-[18px] font-medium text-black group-hover:text-[#0DE255] transition-colors">
                    <?= htmlspecialchars($ROLE_COPY['client']['label'] ?? 'Faculty / Staff') ?>
                </span>
                <span class="text-sm font-normal text-black leading-normal">
                    <?= htmlspecialchars($ROLE_COPY['client']['description'] ?? 'Post jobs and hire students.') ?>
                </span>
            </button>
        </form>

    </div>

    <!-- Error Message -->
    <?php if (!empty($formError)): ?>
        <p role="alert" class="text-sm text-red-600 mt-1">
            <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

</section>

        <?php else: ?>

            <!-- Form State -->
            <?php $roleCopy = $ROLE_COPY[$selectedRole]; ?>

            <!-- Role Summary Banner -->
            <div class="w-full min-h-[110px] p-[18px] border border-[#D9D9D9] rounded-[4px] flex items-center justify-between gap-4">
                <div class="flex flex-col justify-center items-start gap-1">
                    <span class="text-sm text-black">
                        Signing up as
                    </span>

                    <strong class="text-lg font-medium text-black">
                        <?= htmlspecialchars($roleCopy['label']) ?>
                    </strong>

                    <span class="text-sm text-black">
                        <?= htmlspecialchars($roleCopy['description']) ?>
                    </span>
                </div>

                <a href="signup.php" class="text-sm text-black underline self-start hover:opacity-70 transition-opacity shrink-0">
                    Change
                </a>
            </div>

            <!-- Inputs Container -->
            <div class="w-full p-4 sm:p-[18px] border border-[#D3D3D3] rounded-[5px]">

                <form method="POST" action="../process/signupprocess.php" novalidate class="flex flex-col gap-[28px]">

                    <input
                        type="hidden"
                        name="role"
                        value="<?= htmlspecialchars($selectedRole, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <?php if ($formError): ?>
                        <p role="alert" class="text-sm text-red-600">
                            <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endif; ?>

                    <!-- Full name -->
                    <div class="flex flex-col gap-2">
                        <label for="fullName" class="text-[16px] font-normal text-[#1C1B1F]">
                            Full Name
                        </label>

                        <input
                            id="fullName"
                            name="fullName"
                            type="text"
                            autocomplete="name"
                            value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                            aria-invalid="<?= isset($fieldErrors['fullName']) ? 'true' : 'false' ?>"
                            class="w-full h-[50px] px-4 text-base border border-black rounded-[10px] focus:outline-none focus:ring-2 focus:ring-black"
                        >

                        <?php if (isset($fieldErrors['fullName'])): ?>
                            <span role="alert" class="text-xs text-red-600">
                                <?= htmlspecialchars($fieldErrors['fullName'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="flex flex-col gap-2">
                        <label for="email" class="text-[16px] font-normal text-black">
                            Email
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            aria-invalid="<?= isset($fieldErrors['email']) ? 'true' : 'false' ?>"
                            class="w-full h-[50px] px-4 text-base border border-black rounded-[10px] focus:outline-none focus:ring-2 focus:ring-black"
                        >

                        <?php if (isset($fieldErrors['email'])): ?>
                            <span role="alert" class="text-xs text-red-600">
                                <?= htmlspecialchars($fieldErrors['email'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Organization -->
                    <div class="flex flex-col gap-2">
                        <label for="orgUnit" class="text-[16px] font-normal text-black">
                            <?= htmlspecialchars($roleCopy['orgFieldLabel']) ?>
                        </label>

                        <input
                            id="orgUnit"
                            name="orgUnit"
                            type="text"
                            value="<?= htmlspecialchars($orgUnit, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="<?= htmlspecialchars($roleCopy['orgFieldPlaceholder'], ENT_QUOTES, 'UTF-8') ?>"
                            aria-invalid="<?= isset($fieldErrors['orgUnit']) ? 'true' : 'false' ?>"
                            class="w-full h-[50px] px-4 text-base border border-black rounded-[10px] focus:outline-none focus:ring-2 focus:ring-black"
                        >

                        <?php if (isset($fieldErrors['orgUnit'])): ?>
                            <span role="alert" class="text-xs text-red-600">
                                <?= htmlspecialchars($fieldErrors['orgUnit'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Password -->
                    <div class="flex flex-col gap-2">
                        <label for="password" class="text-[16px] font-normal text-black">
                            Password
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            aria-invalid="<?= isset($fieldErrors['password']) ? 'true' : 'false' ?>"
                            aria-describedby="password-hint"
                            class="w-full h-[50px] px-4 text-base border border-black rounded-[10px] focus:outline-none focus:ring-2 focus:ring-black"
                        >

                        <span id="password-hint" class="text-xs text-gray-600">
                            At least <?= $MIN_PASSWORD_LENGTH ?> characters.
                        </span>

                        <?php if (isset($fieldErrors['password'])): ?>
                            <span role="alert" class="text-xs text-red-600">
                                <?= htmlspecialchars($fieldErrors['password'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Confirm password -->
                    <div class="flex flex-col gap-2">
                        <label for="confirmPassword" class="text-[16px] font-normal text-black">
                            Confirm Password
                        </label>

                        <input
                            id="confirmPassword"
                            name="confirmPassword"
                            type="password"
                            autocomplete="new-password"
                            aria-invalid="<?= isset($fieldErrors['confirmPassword']) ? 'true' : 'false' ?>"
                            class="w-full h-[50px] px-4 text-base border border-black rounded-[10px] focus:outline-none focus:ring-2 focus:ring-black"
                        >

                        <?php if (isset($fieldErrors['confirmPassword'])): ?>
                            <span role="alert" class="text-xs text-red-600">
                                <?= htmlspecialchars($fieldErrors['confirmPassword'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Terms -->
                    <div class="flex flex-col gap-1.5">

                        <label class="flex items-start gap-2 text-sm text-black cursor-pointer">
                            <input
                                name="agreedToTerms"
                                type="checkbox"
                                value="1"
                                class="mt-1 rounded accent-[#099639]"
                                <?= $agreedToTerms ? 'checked' : '' ?>
                            >

                            <span>
                                I agree to the
                                <a href="/" class="underline hover:opacity-70 transition-opacity">
                                    terms of use
                                </a>.
                            </span>
                        </label>

                        <?php if (isset($fieldErrors['agreedToTerms'])): ?>
                            <span role="alert" class="text-xs text-red-600">
                                <?= htmlspecialchars($fieldErrors['agreedToTerms'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>

                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full h-[50px] bg-[#099639] hover:bg-[#088231] active:bg-[#07702a] text-white text-[18px] font-medium rounded-[10px] transition-colors cursor-pointer flex items-center justify-center"
                    >
                        Create account
                    </button>

                </form>

            </div>

        <?php endif; ?>

    </div>
</main>

</body>
</html>