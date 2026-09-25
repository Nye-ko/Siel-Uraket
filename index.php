<?php
// index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>URaket</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col">

    <!-- ============================= Nav ============================= -->

    <header class="w-full">
        <div class="max-w-[1440px] mx-auto px-20 h-24 flex items-center justify-between">

            <a
                href="/"
                class="text-xl font-semibold tracking-tight"
            >
                URaket
            </a>

            <nav
                aria-label="Primary"
                class="flex items-center gap-8"
            >
            </nav>

            <div class="flex items-center gap-4">
                <a
                   href="#how-it-works-heading"
                   class="text-sm px-4 py-2"
                >
                   How It Works
                </a>

                <a
                    href="auth/login.php"
                    class="text-sm px-4 py-2"
                >
                    Log in
                </a>

                <button
                    type="button"
                    onclick="openRoleModal()"
                    class="text-sm px-5 py-2 border border-transparent bg-[#099639] text-white font-normal rounded-[10px] flex items-center justify-center hover:bg-[#077a2e] transition cursor-pointer"
                >
                    Sign up
                </button>

            </div>
        </div>
    </header>


    <main class="flex-1">

        <!-- ============================ Hero ============================ -->

        <section
            class="max-w-[1440px] mx-auto px-20 py-20 grid grid-cols-1 md:grid-cols-2 gap-12 items-center"
        >

            <div class="flex flex-col gap-6 max-w-xl">

                <h1 class="text-5xl leading-tight font-semibold">
                    Find work, hire talent — all inside CLSU.
                </h1>

                <p class="text-base leading-relaxed">
                    URaket connects students looking for paid gigs with
                    faculty and staff who need work done. Post a job or apply
                    to one — everything from hiring to reviews happens right
                    here.
                </p>

                <div class="flex items-center gap-4 pt-2">

                    <button
                        type="button"
                        onclick="openRoleModal()"
                        class="text-sm px-6 py-3 border border-transparent bg-[#099639] text-white font-medium rounded-[10px] flex items-center justify-center gap-2 hover:bg-[#077a2e] transition cursor-pointer"
                    >
                        Get started
                    </button>

                </div>

            </div>


            <!-- Illustration placeholder -->

            <img src="public/Siever_Silver.png" alt="Silver photo of Siever"
            class="w-full aspect-[4/5] max-w-md justify-self-center object-cover" >

        </section>


        <!-- ======================== How it works ======================== -->

        <section
            id="how-it-works"
            aria-labelledby="how-it-works-heading"
            class="max-w-[1440px] mx-auto px-20 py-20"
        >

            <h2
                id="how-it-works-heading"
                class="text-3xl font-semibold mb-12"
            >
                How it works
            </h2>


            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">

                <!-- Step 1 -->

                <article class="flex flex-col gap-3">

                    <span class="text-sm">
                        Step 1
                    </span>

                    <h3 class="text-lg font-medium">
                        Post or apply
                    </h3>

                    <p class="text-sm leading-relaxed">
                        Faculty/Staff post a job with the skills they need.
                        Students browse open gigs and apply with a
                        short pitch.
                    </p>

                </article>


                <!-- Step 2 -->

                <article class="flex flex-col gap-3">

                    <span class="text-sm">
                        Step 2
                    </span>

                    <h3 class="text-lg font-medium">
                        Get hired
                    </h3>

                    <p class="text-sm leading-relaxed">
                        The Faculty/Staff reviews applicants and accepts one.
                        That closes the job to further applications
                        automatically.
                    </p>

                </article>


                <!-- Step 3 -->

                <article class="flex flex-col gap-3">

                    <span class="text-sm">
                        Step 3
                    </span>

                    <h3 class="text-lg font-medium">
                        Complete and review
                    </h3>

                    <p class="text-sm leading-relaxed">
                        Once the Faculty/Staff marks the job complete, both
                        sides leave a review — that's what builds trust
                        for the next gig.
                    </p>

                </article>

            </div>

        </section>

    </main>


    <!-- ============================ Footer ============================ -->

    <footer class="w-full border-t">

        <div
            class="max-w-[1440px] mx-auto px-20 py-10 flex items-center justify-between"
        >

            <span class="text-sm">
                URaket
            </span>

            <span class="text-sm">
                &copy; <?php echo date("Y"); ?> URaket.
                For campus use only.
            </span>

        </div>

    </footer>


    <!-- ======================== Role Select Modal ======================== -->

    <div
        id="roleSelectModal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center"
        aria-hidden="true"
    >

        <!-- Backdrop -->

        <div
            class="absolute inset-0 bg-black/40"
            onclick="closeRoleModal()"
        ></div>


        <!-- Modal -->

        <section
            role="dialog"
            aria-modal="true"
            aria-labelledby="role-modal-heading"
            class="relative z-10 w-full max-w-md bg-white border p-8"
        >

            <div class="flex flex-col gap-6">

                <div class="flex flex-col gap-2">

                    <h2
                        id="role-modal-heading"
                        class="text-xl font-semibold"
                    >
                        Choose your role
                    </h2>

                    <p class="text-sm">
                        Select how you want to use Campus Gig.
                    </p>

                </div>


                <div class="flex flex-col gap-3">

                    <!-- Freelancer -->

                    <button
                        type="button"
                        onclick="selectRole('freelancer')"
                        class="border border-gray-300 hover:border-[#0DE255] hover:bg-[#0DE255]/10 hover:text-[#0DE255] px-4 py-3 text-left transition-colors cursor-pointer rounded-md"
                    >

                        <span class="block text-sm font-medium">
                            Freelancer
                        </span>

                        <span class="block text-xs mt-1">
                            Find campus gigs and apply for jobs.
                        </span>

                    </button>


                    <!-- Client -->

                    <button
                        type="button"
                        onclick="selectRole('client')"
                        class="border border-gray-300 hover:border-[#0DE255] hover:bg-[#0DE255]/10 hover:text-[#0DE255] px-4 py-3 text-left transition-colors cursor-pointer rounded-md"
                    >

                        <span class="block text-sm font-medium">
                            Client
                        </span>

                        <span class="block text-xs mt-1">
                            Post jobs and hire campus talent.
                        </span>

                    </button>

                </div>


                <button
                    type="button"
                    onclick="closeRoleModal()"
                    class="text-sm underline self-start"
                >
                    Cancel
                </button>

            </div>

        </section>

    </div>


    <!-- ============================ JavaScript ============================ -->

    <script>

        function openRoleModal() {
            const modal = document.getElementById(
                "roleSelectModal"
            );

            modal.classList.remove("hidden");
            modal.setAttribute("aria-hidden", "false");
        }


        function closeRoleModal() {
            const modal = document.getElementById(
                "roleSelectModal"
            );

            modal.classList.add("hidden");
            modal.setAttribute("aria-hidden", "true");
        }


        function selectRole(role) {

            closeRoleModal();

            window.location.href =
                "auth/signup.php?role=" +
                encodeURIComponent(role);
        }


        // Close modal with Escape

        document.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Escape") {
                    closeRoleModal();
                }

            }
        );

    </script>

</body>
</html>