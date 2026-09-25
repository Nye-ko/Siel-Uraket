<?php
session_start();

$loginForm = $_SESSION['loginForm'] ?? [];
unset($_SESSION['loginForm']);

$email = $loginForm['email'] ?? '';
$formError = $loginForm['formError'] ?? '';
$emailError = $loginForm['emailError'] ?? '';
$passwordError = $loginForm['passwordError'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in - URaket</title>

    <!-- Tailwind CSS CDN; remove this and use your existing Tailwind build if applicable. -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex flex-col">

<header class="w-full">
    <div class="max-w-[1440px] mx-auto px-20 h-24 flex items-center">
        <a href="/" class="text-xl font-semibold tracking-tight">
            URaket
        </a>
    </div>
</header>

<main class="flex-1 flex items-center justify-center px-4 sm:px-20 font-['Neue_Montreal',sans-serif]">
    <div class="w-full max-w-[420px] flex flex-col gap-5 py-16">

        <!-- Title & Navigation Header -->
        <div class="w-full max-w-[358px] p-2.5 flex flex-col justify-center items-start gap-2.5">
            <h1 class="text-[28px] font-medium leading-tight text-black">
                Log in
            </h1>

            <div class="flex items-center gap-[10px] text-base text-black font-normal">
                <span>New here?</span>
                <a href="signup.php" class="underline hover:opacity-70 transition-opacity">
                    Create an account
                </a>
            </div>
        </div>

        <!-- Form Card Container -->
        <div class="w-full max-w-[420px] p-2.5 sm:px-2.5 sm:py-[18px] border border-[#D3D3D3] rounded-[5px] bg-white overflow-hidden">
            <div class="w-full p-2.5 flex flex-col justify-center items-center gap-1">

                <form method="POST" action="../process/loginprocess.php" novalidate class="w-full py-[5px] flex flex-col justify-center items-start gap-[20px]">

                    <?php if ($formError !== ''): ?>
                        <p role="alert" class="w-full text-sm text-red-600">
                            <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endif; ?>

                    <!-- Email Field -->
                    <div class="w-full flex flex-col justify-center items-start gap-2">
                        <label for="email" class="text-[16px] font-normal text-black">
                            Email
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            aria-invalid="<?= $emailError !== '' ? 'true' : 'false' ?>"
                            aria-describedby="email-error"
                            class="w-full h-[50px] px-4 text-base border border-black rounded-[10px] focus:outline-none focus:ring-2 focus:ring-black transition-all"
                        >

                        <?php if ($emailError !== ''): ?>
                            <span id="email-error" role="alert" class="text-xs text-red-600">
                                <?= htmlspecialchars($emailError, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Password Field -->
                    <div class="w-full flex flex-col justify-center items-start gap-2">
                        <label for="password" class="text-[16px] font-normal text-black">
                            Password
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            aria-invalid="<?= $passwordError !== '' ? 'true' : 'false' ?>"
                            aria-describedby="password-error"
                            class="w-full h-[50px] px-4 text-base border border-black rounded-[10px] focus:outline-none focus:ring-2 focus:ring-black transition-all"
                        >

                        <?php if ($passwordError !== ''): ?>
                            <span id="password-error" role="alert" class="text-xs text-red-600">
                                <?= htmlspecialchars($passwordError, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full h-[50px] bg-[#099639] hover:bg-[#088231] active:bg-[#07702a] text-white text-[18px] font-medium rounded-[10px] transition-colors cursor-pointer flex items-center justify-center mt-2"
                    >
                        Log in
                    </button>

                </form>

            </div>
        </div>

    </div>
</main>

</body>
</html>