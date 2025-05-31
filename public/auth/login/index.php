<?php
Doc::setTitle('Login');
if (isset($_SESSION['user'])) {
    header('location: ' . getRoot());
}
?>

<div class="min-h-screen bg-gray-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Log into DGT
        </h2>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 border-2 border-slate-200 sm:rounded-lg sm:px-10">
            <form action="<?= App::currentPath() . '/action' ?>" class="space-y-6" method="POST">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">
                        Username
                    </label>
                    <div class="mt-1">
                        <input id="username" name="username" type="username" autocomplete="username" required
                            class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                            placeholder="Enter your username address">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="mt-1 relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm"
                            placeholder="Enter your password">

                        <!-- Eye Icon -->
                        <i class="ri-eye-line absolute right-3 top-[35px] -translate-y-1/2 text-gray-500 cursor-pointer text-xl"
                            onclick="togglePassInput(this,'#password')"></i>
                    </div>
                </div>


                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox"
                            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="<?= $_ENV['ROOT'] . SEGMENTS[0] . '/forget-password'; ?>" class="font-medium text-blue-600 hover:text-blue-500">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" name="LoginSubmit"
                        class="group relative w-full cursor-pointer flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md !text-white !bg-accent hover:!bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent">
                        Sign in
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>