<?php
require_once 'auth/Auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = Auth::user();
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VDraw – Visual Python & Data Structures Learning Platform</title>
    <meta name="description" content="VDraw helps students and teachers learn Python, data structures, statistics, and AI through interactive visualizations, animations, charts, and simulations.">
    <meta name="keywords" content="python visualizer, python explainer, data structure visualization, graph animation, tree animation, statistics visualization, AI python coding, grade 9 computer science, grade 10 computer science, grade 11 computer science, grade 12 computer science, students, teachers">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="images/favicon.png" type="image/png">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            900: '#312e81',
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'fade-in-up': 'fadeInUp 0.8s ease-out forwards',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': {
                                transform: 'translateY(0)'
                            },
                            '50%': {
                                transform: 'translateY(-10px)'
                            },
                        },
                        fadeInUp: {
                            '0%': {
                                opacity: '0',
                                transform: 'translateY(20px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --bg-color: #0f172a;
            --text-color: #f8fafc;
            --panel-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            background-image:
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(236, 72, 153, 0.15) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(45, 212, 191, 0.15) 0px, transparent 50%);
            background-attachment: fixed;
        }

        .glass-card {
            background: var(--panel-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 10px 40px -10px rgba(99, 102, 241, 0.3);
            background: rgba(30, 41, 59, 0.85);
        }

        .icon-glow {
            filter: drop-shadow(0 0 8px rgba(99, 102, 241, 0.3));
        }

        /* Modal Animation */
        .modal-enter {
            opacity: 0;
            transform: scale(0.95);
        }

        .modal-enter-active {
            opacity: 1;
            transform: scale(1);
            transition: all 0.3s ease-out;
        }

        .modal-exit {
            opacity: 1;
            transform: scale(1);
        }

        .modal-exit-active {
            opacity: 0;
            transform: scale(0.95);
            transition: all 0.2s ease-in;
        }
    </style>
    <script src="frontend/ads/ads.js" defer></script>
</head>

<body class="min-h-screen flex flex-col relative overflow-x-hidden">

    <!-- Header -->
    <header
        class="h-20 flex items-center justify-between px-6 md:px-12 fixed w-full z-20 glass-card !bg-slate-900/50 !border-x-0 !border-t-0 rounded-none">
        <div class="flex items-center gap-3">
            <div
                class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-purple-600 flex items-center justify-center shadow-lg shadow-brand-500/20">
                <i class="fa-solid fa-graduation-cap text-white text-lg"></i>
            </div>
            <div class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-brand-400 to-purple-400">
                VDraw Education</div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="toggleModal('about-modal')"
                class="hidden md:flex items-center gap-2 px-4 py-2 rounded-full bg-slate-800/50 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition-all group mr-2">
                <span class="text-sm font-medium">About Developer</span>
                <i class="fa-solid fa-code text-brand-400 group-hover:rotate-12 transition-transform"></i>
            </button>

            <!-- Change Log Button (dynamically shown if entries exist) -->
            <button id="changelog-btn" onclick="toggleModal('changelog-modal')"
                class="hidden md:hidden items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 text-white border border-green-500/50 transition-all group mr-2 shadow-lg shadow-green-500/20">
                <i class="fa-solid fa-clock-rotate-left group-hover:-rotate-12 transition-transform"></i>
                <span class="text-sm font-medium" id="changelog-btn-text">What's New</span>
            </button>

            <?php if ($currentUser): ?>
                <!-- User Profile -->
                <?php $dashboardLink = ($currentUser['role'] === 'admin') ? 'admin/dashboard/' : 'user/dashboard/'; ?>
                <a href="<?php echo $dashboardLink; ?>" class="flex items-center gap-2 px-4 py-2 rounded-full bg-slate-800/50 hover:bg-slate-700 border border-slate-700 transition-all group">
                    <div class="w-6 h-6 rounded-full bg-brand-600 flex items-center justify-center font-bold text-white text-xs shadow-md">
                        <?php echo substr($currentUser['full_name'] ?? 'U', 0, 1); ?>
                    </div>
                    <span class="text-sm font-medium text-slate-300 group-hover:text-white max-w-[100px] truncate hidden sm:inline"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                </a>
            <?php else: ?>
                <!-- Login Button -->
                <a href="login.php?redirect=<?php echo urlencode('index.php'); ?>"
                    class="flex items-center gap-2 px-5 py-2 rounded-full bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold shadow-lg shadow-brand-500/20 transition-all transform hover:scale-105">
                    <i class="fa-solid fa-right-to-bracket"></i> Login / Signup
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Tracking Script (Phase 15) -->
    <script src="frontend/js/tracking.js"></script>
    <script>
        // start tracking Home api code
        document.addEventListener('DOMContentLoaded', () => {
            if (window.initTracking) window.initTracking('Home', 'api/track.php');
        });
        // end tracking Home api code
    </script>


    <!-- Main Content -->
    <main class="flex-1 flex flex-col items-center justify-center pt-32 pb-20 px-4 z-10">

        <div class="text-center mb-16 max-w-4xl mx-auto animate-fade-in-up">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-6 leading-tight">Visual Learning for Python, Data Structures & Statistics</h1>
            <h2 class="text-xl md:text-2xl font-semibold text-brand-400 mb-6">Learn complex computer science concepts using animations, graphs, charts, and AI-powered Python tools.</h2>

            <div class="flex flex-wrap justify-center gap-4 mt-8">
                <span class="px-4 py-2 rounded-full bg-slate-800/50 border border-brand-500/30 text-brand-300 text-sm font-medium animate-fade-in-up opacity-0 shadow-lg shadow-brand-500/10 backdrop-blur-md" style="animation-delay: 0.8s; animation-fill-mode: forwards;">
                    <i class="fa-solid fa-lightbulb mr-2 text-yellow-400"></i> Focus on Explanation
                </span>
                <span class="px-4 py-2 rounded-full bg-slate-800/50 border border-brand-500/30 text-brand-300 text-sm font-medium animate-fade-in-up opacity-0 shadow-lg shadow-brand-500/10 backdrop-blur-md" style="animation-delay: 1.2s; animation-fill-mode: forwards;">
                    <i class="fa-solid fa-brain mr-2 text-pink-400"></i> Memorize Less
                </span>
                <span class="px-4 py-2 rounded-full bg-slate-800/50 border border-brand-500/30 text-brand-300 text-sm font-medium animate-fade-in-up opacity-0 shadow-lg shadow-brand-500/10 backdrop-blur-md" style="animation-delay: 1.6s; animation-fill-mode: forwards;">
                    <i class="fa-solid fa-layer-group mr-2 text-cyan-400"></i> Simple with Depth
                </span>
            </div>

        </div>

        <!-- Video Section -->
        <!-- Hero Image Section -->
        <div class="w-full max-w-5xl mx-auto mb-20 relative animate-fade-in-up group" style="animation-delay: 0.2s;">
            <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-purple-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
            <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-slate-700/50 bg-slate-900">
                <img src="images/home.png" alt="VDraw Dashboard" class="w-full h-auto object-cover transform group-hover:scale-[1.01] transition-transform duration-700">

                <!-- Overlay Gradient -->
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent pointer-events-none"></div>
            </div>
        </div>

        <script>
            // Video playback handler for App cards
            function playAppVideo(container) {
                const img = container.querySelector('.app-cover');
                const btn = container.querySelector('.play-btn');
                const video = container.querySelector('video');

                if (video) {
                    // Hide cover and button
                    if (img) img.style.display = 'none';
                    if (btn) btn.style.display = 'none';

                    // Show and play video
                    video.classList.remove('hidden');
                    video.playbackRate = 1.15; // Set speed requested by user
                    video.play();
                }
            }
        </script>

        <!-- Intro Text Lower -->
        <div class="text-center mb-16 max-w-4xl mx-auto animate-fade-in-up" style="animation-delay: 0.3s;">
            <p class="text-lg text-slate-300 mb-6">VDraw is a visual learning platform designed for students and teachers. It transforms Python code, data structures, and statistics into <strong class="text-brand-300">clear animations and interactive simulations</strong>.</p>
            <p class="text-base text-slate-400 leading-relaxed max-w-3xl mx-auto">VDraw makes computer science easier by turning abstract concepts into visual experiences. Students can explore Python code execution, data structures like lists, stacks, queues, trees, and graphs, and statistical concepts such as mean, median, mode, variance, and standard deviation. Teachers can use VDraw to create engaging lessons, presentations, slides, and demonstrations for classrooms.</p>
        </div>

        <div class="w-full max-w-7xl">
            <!-- AD PLACEMENT: Home Between Explainer Sections -->
            <!-- Note: Phase 11 says "Between PyViz Explainer and DViz". 
                 The current layout is Row 1 (Stats, Linear, Graph) then Row 2 (PyViz, DViz).
                 So "Between" might mean between Row 1 and Row 2? Or specifically layout shift?
                 User Requirement: "Between PyViz Explainer and DViz Library Icon Boxes".
                 They are side-by-side in "Second Row". 
                 Inserting an ad between them would break the grid.
                 Interpretation: Maybe Above or Below the PyViz row?
                 Or: "Ad 1: Between "Visualization" and "Detailed Statistics" panels" (That was VDraw Landing).
                 Home Page Req: "Between PyViz Explainer and DViz Library Icon Boxes".
                 I will place it in the gap between the two cards in the flex container? 
                 Or maybe just above the Second Row to be safe.
                 Let's place it ABOVE the Second Row (PyViz/DViz). 
            -->



            <!-- First Row (3 items) -->
            <!-- Dynamic Apps Grid -->
            <!-- Dynamic Apps Grid -->
            <div class="flex flex-wrap justify-center gap-8 mb-8">
                <?php
                require_once 'auth/AppHelper.php';
                $apps = AppHelper::getAllApps();
                $adInserted = false;

                foreach ($apps as $index => $app):
                    $theme = AppHelper::getTheme($app['theme_color'] ?? 'blue');
                    $url = "frontend/" . $app['name'] . "/";
                    // Dynamic image name: lowercase app name + .png
                    $imgFile = strtolower($app['name']) . '.png';
                    // Check if the image file exists
                    $imgPath = 'images/' . $imgFile;
                    $imgExists = file_exists($imgPath);

                    // Check if video file exists
                    $vidFile = strtolower($app['name']) . '.mp4';
                    $vidPath = 'images/' . $vidFile;
                    $vidExists = file_exists($vidPath);
                ?>
                    <div class="w-full max-w-sm group">
                        <div class="glass-card h-full p-8 rounded-3xl relative overflow-hidden flex flex-col items-center text-center animate-fade-in-up"
                            style="animation-delay: <?php echo ($index * 0.1); ?>s;">
                            <div
                                class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r <?php echo $theme['gradient']; ?> transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-500">
                            </div>

                            <?php if ($vidExists): ?>
                                <!-- Video Container -->
                                <div class="w-full mb-6 relative rounded-2xl overflow-hidden shadow-lg border border-slate-700/50 cursor-pointer h-48 bg-slate-900 group/video" onclick="playAppVideo(this)">

                                    <!-- Poster Image -->
                                    <img src="images/<?php echo $imgExists ? $imgFile : 'favicon.png'; ?>"
                                        alt="<?php echo htmlspecialchars($app['home_title']); ?>"
                                        class="app-cover w-full h-full object-cover opacity-90 group-hover/video:opacity-75 transition-opacity duration-300">

                                    <!-- Play Button -->
                                    <div class="play-btn absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <div class="w-14 h-14 rounded-full bg-slate-900/50 backdrop-blur-sm border border-white/20 text-white flex items-center justify-center text-2xl shadow-xl group-hover/video:scale-110 group-hover/video:bg-brand-600 transition-all duration-300 pl-1">
                                            <i class="fa-solid fa-play"></i>
                                        </div>
                                    </div>

                                    <!-- Video Element -->
                                    <video class="w-full h-full absolute inset-0 hidden object-cover" controls playsinline preload="none">
                                        <source src="images/<?php echo $vidFile; ?>" type="video/mp4">
                                    </video>

                                    <!-- App Icon Badge (visible only when video not playing) -->
                                    <div class="play-btn absolute -bottom-0 right-0 p-2 z-10">
                                        <div class="w-10 h-10 rounded-lg <?php echo $theme['icon_bg']; ?> <?php echo $theme['icon_text']; ?> flex items-center justify-center shadow-lg border border-slate-700/50">
                                            <i class="<?php echo $app['icon_class']; ?>"></i>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($imgExists): ?>
                                <a href="<?php echo $url; ?>" target="_blank" class="w-full block">
                                    <div class="w-full mb-6 relative group-hover:-translate-y-2 transition-transform duration-500">
                                        <img src="images/<?php echo $imgFile; ?>"
                                            alt="<?php echo htmlspecialchars($app['home_title']); ?>"
                                            class="w-full h-40 object-cover rounded-2xl shadow-lg border border-slate-700/50">
                                        <div class="absolute -bottom-4 left-1/2 transform -translate-x-1/2 w-12 h-12 rounded-xl <?php echo $theme['icon_bg']; ?> <?php echo $theme['icon_text']; ?> flex items-center justify-center shadow-lg backdrop-blur-md border border-slate-700/50">
                                            <i class="<?php echo $app['icon_class']; ?>"></i>
                                        </div>
                                    </div>
                                </a>
                                <div class="mt-4"></div>
                            <?php else: ?>
                                <a href="<?php echo $url; ?>" target="_blank" class="w-full block">
                                    <div
                                        class="w-16 h-16 rounded-2xl <?php echo $theme['icon_bg']; ?> <?php echo $theme['icon_text']; ?> flex items-center justify-center mb-6 text-3xl group-hover:scale-110 group-hover:text-white <?php echo $theme['icon_hover']; ?> transition-all duration-300 icon-glow mx-auto">
                                        <i class="<?php echo $app['icon_class']; ?>"></i>
                                    </div>
                                </a>
                            <?php endif; ?>

                            <h3 class="text-2xl font-bold text-white mb-4"><?php echo htmlspecialchars($app['home_title']); ?></h3>
                            <p class="text-slate-400 leading-relaxed text-sm"><?php echo htmlspecialchars($app['home_description']); ?></p>
                            <div
                                class="mt-auto pt-6 opacity-0 group-hover:opacity-100 transition-opacity transform translate-y-2 group-hover:translate-y-0">
                                <a href="<?php echo $url; ?>" target="_blank" class="<?php echo $theme['link_text']; ?> text-sm font-bold flex items-center justify-center gap-2 hover:underline">Launch
                                    App <i class="fa-solid fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Insert Ad after 4th item (index 3) or similar logic if needed
                    if ($index === 3):
                    ?>
                        <!-- AD PLACEMENT: Interstitial -->
                        <div class="glass-card w-full max-w-sm p-4 rounded-3xl relative overflow-hidden flex flex-col items-center justify-center text-center hidden" data-ad-placement="home_between_sections">
                            <!-- Ad content will be injected here -->
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer
        class="border-t border-slate-800 bg-slate-900/50 grayscale opacity-70 hover:grayscale-0 hover:opacity-100 transition-all duration-500 py-8 relative z-10 box-border">
        <div class="container mx-auto px-6 text-center">
            <p class="text-xs text-slate-600 max-w-2xl mx-auto mb-6 leading-relaxed">
                VDraw is built for students and teachers from Grade 9 to Grade 12. It supports classroom learning, self-study, presentations, slides, and interactive demonstrations for computer science, Python programming, data structures, and statistics.
            </p>
            <p class="text-slate-500 text-sm mb-2">&copy; 2026 VDraw Education Suite. All rights reserved.</p>
            <p class="text-slate-600 text-xs text-center flex items-center justify-center gap-4">
                <span>Powered by <a href="https://webworldcenter.com" target="_blank" class="text-brand-400 hover:text-brand-300 font-medium">Web World Center</a></span>
                <span class="w-1 h-1 rounded-full bg-slate-700"></span>
                <button onclick="toggleModal('terms-modal')" class="text-slate-500 hover:text-white transition-colors">Terms of Use</button>
                <span class="w-1 h-1 rounded-full bg-slate-700"></span>
                <button onclick="toggleModal('privacy-modal')" class="text-slate-500 hover:text-white transition-colors">Privacy Policy</button>
            </p>
        </div>
    </footer>

    <!-- Background Orbs -->
    <div
        class="fixed top-20 left-10 w-64 h-64 bg-purple-600/10 rounded-full blur-[100px] pointer-events-none animate-float">
    </div>
    <div class="fixed bottom-20 right-10 w-96 h-96 bg-brand-600/10 rounded-full blur-[120px] pointer-events-none animate-float"
        style="animation-delay: -2s;"></div>

    <!-- About Modal -->
    <div id="about-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity opacity-0" id="modal-backdrop"
            onclick="toggleModal('about-modal')"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-lg bg-[#0f172a] border border-slate-700 rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 overflow-hidden"
            id="modal-content">

            <!-- Close Button -->
            <button onclick="toggleModal('about-modal')"
                class="absolute top-4 right-4 text-slate-400 hover:text-white transition-colors z-10">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <div class="p-8">
                <div class="flex flex-col md:flex-row gap-6 items-center md:items-start">
                    <!-- Info -->
                    <div class="text-center md:text-left pt-1 w-full">
                        <h3 class="text-3xl font-bold text-white mb-2">VDraw.cc Team</h3>
                        <p class="text-brand-400 text-sm font-medium italic mb-6">Innovating Education through Visualization</p>

                        <a href="mailto:hello@vdraw.cc" class="inline-flex items-center gap-2 text-slate-400 hover:text-white text-sm transition-colors group justify-center md:justify-start">
                            <i class="fa-regular fa-envelope group-hover:text-brand-400 transition-colors"></i>
                            <span>hello@vdraw.cc</span>
                        </a>
                    </div>
                </div>

                <!-- Quote -->
                <div class="mt-8 bg-slate-800/50 rounded-xl p-6 relative border border-slate-700/50">
                    <i class="fa-solid fa-quote-left text-brand-500/20 text-4xl absolute -top-3 -left-2"></i>
                    <p class="text-slate-300 text-base leading-relaxed italic relative z-10">
                        "We developed this suite to bridge the gap between abstract concepts and visual understanding. As educators and developers, we felt the need for tools that bring modern education to life, contributing to a brighter future for students and teachers alike."
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div id="terms-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/90 backdrop-blur-md transition-opacity opacity-0" id="modal-backdrop" onclick="toggleModal('terms-modal')"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-4xl h-[90vh] bg-[#0f172a] border border-slate-700 rounded-2xl shadow-2xl section-terms transform scale-95 opacity-0 transition-all duration-300 overflow-hidden flex flex-col" id="modal-content">

            <!-- Header -->
            <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-[#0f172a] z-10 shrink-0">
                <h2 class="text-2xl font-bold text-white">Terms and Conditions</h2>
                <button onclick="toggleModal('terms-modal')" class="text-slate-400 hover:text-white transition-colors bg-slate-800 p-2 rounded-full w-8 h-8 flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="p-8 overflow-y-auto custom-scrollbar text-slate-300 space-y-6 text-sm md:text-base leading-relaxed">

                <p class="italic text-slate-500 text-xs">Effective Date: Jan 2026</p>

                <p>These Terms and Conditions ("<strong>Terms</strong>") govern your access to and use of the VDraw website, web applications, and related services (collectively, the "<strong>Service</strong>"). By accessing, browsing, signing up, logging in, or otherwise using the Service, you confirm that you have read, understood, and agree to be bound by these Terms.</p>

                <div class="bg-blue-900/10 border border-blue-500/20 p-4 rounded-lg">
                    <strong class="text-blue-400">Important Notice:</strong> This document serves as the agreement between you and VDraw regarding the use of our educational tools.
                </div>

                <hr class="border-slate-800">

                <h3 class="text-xl font-bold text-white">1. About VDraw</h3>
                <p>VDraw is a collection of educational tools and apps designed to help students and teachers learn concepts through interactive visualizations, code playgrounds, quizzes, and related learning experiences.</p>

                <h3 class="text-xl font-bold text-white">2. Eligibility</h3>
                <ul class="list-disc pl-5 space-y-2">
                    <li>You must be able to form a legally binding agreement under your local laws.</li>
                    <li>If you are under the legal age of majority, you may only use the Service with the involvement and consent of a parent, legal guardian, or authorized school representative.</li>
                </ul>

                <h3 class="text-xl font-bold text-white">3. Acceptance of These Terms</h3>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Signing up, logging in, or continuing to use the Service means you accept these Terms.</strong></li>
                    <li>If you do not agree, do not use the Service.</li>
                </ul>

                <h3 class="text-xl font-bold text-white">4. Educational Use Only (Non-Commercial)</h3>
                <p>The Service is provided <strong>for educational purposes only</strong>.</p>

                <h4 class="text-lg font-bold text-brand-400 mt-2">4.1 Permitted Use</h4>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Personal learning</li>
                    <li>Classroom teaching and academic activities</li>
                    <li>Demonstrations and training in educational environments</li>
                </ul>

                <h4 class="text-lg font-bold text-brand-400 mt-2">4.2 Prohibited Commercial Use</h4>
                <p>You must <strong>not</strong> use the Service (or any part of it) for commercial purposes, including but not limited to:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Selling access to the Service or its features</li>
                    <li>Offering the Service as part of a paid training/product bundle</li>
                    <li>Reselling, licensing, or redistributing the Service</li>
                    <li>Using the Service to provide paid services to third parties</li>
                </ul>
                <p class="mt-2 text-xs text-slate-500">If you want commercial usage rights, you must contact us for written permission.</p>

                <h3 class="text-xl font-bold text-white">5. Accounts and User Responsibilities</h3>
                <ul class="list-disc pl-5 space-y-2">
                    <li>You are responsible for maintaining the confidentiality of your account credentials.</li>
                    <li>You agree to provide accurate and up-to-date information.</li>
                    <li>You are responsible for all activity that occurs under your account.</li>
                </ul>

                <h3 class="text-xl font-bold text-white">6. Acceptable Use Policy</h3>
                <p>You agree <strong>not</strong> to:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Attempt to bypass security, authentication, rate limits, quotas, or usage restrictions.</li>
                    <li>Reverse engineer, decompile, disassemble, or attempt to extract the source code.</li>
                    <li>Copy, scrape, download, mirror, or reproduce substantial parts of the Service without permission.</li>
                    <li>Use bots, automation, or scripts to access the Service.</li>
                    <li>Upload content that is unlawful, harmful, abusive, harassing, hateful, or threatening.</li>
                </ul>

                <h3 class="text-xl font-bold text-white">7. Monitoring and Termination</h3>
                <p>We reserve the right to monitor usage, investigate violations, and limit, suspend, or terminate your account without notice if we believe you are violating these Terms.</p>

                <h3 class="text-xl font-bold text-white">8. Intellectual Property</h3>
                <p>The Service, including its design, features, branding, user interface, text, graphics, code, and content (except content you submit) is owned by us or our licensors and is protected by applicable intellectual property laws.</p>

                <h3 class="text-xl font-bold text-white">9. User Content and Inputs</h3>
                <p>You may submit text, prompts, files, or other inputs ("User Content") to use certain features.</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>You represent that you have the right to submit User Content.</li>
                    <li>You remain responsible for your User Content.</li>
                    <li>You grant us a non-exclusive, worldwide license to use User Content as necessary to operate, maintain, secure, improve, and provide the Service.</li>
                </ul>

                <h3 class="text-xl font-bold text-white">10. AI/Generated Output Disclaimer</h3>
                <p>Some parts of the Service may generate output (e.g., explanations, code, answers, summaries). You understand and agree that:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Output may be inaccurate, incomplete, or unsuitable for your specific purpose.</li>
                    <li>You must review, test, and verify any output before relying on it.</li>
                    <li>We are not responsible for any outcomes resulting from the use of generated output.</li>
                </ul>

                <h3 class="text-xl font-bold text-white">11. Privacy and Data Collection</h3>
                <p>Your use of the Service may involve collection of certain information, including:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Account data (e.g., name, email, login details)</li>
                    <li>Usage data (e.g., pages used, feature usage, timestamps)</li>
                    <li>Device and network data (e.g., IP address, browser type)</li>
                </ul>
                <p>We handle information as described in our Privacy Policy.</p>

                <h3 class="text-xl font-bold text-white">12. Sharing Information With Partners</h3>
                <p>You agree that we may share certain user information with our trusted partners and service providers for purposes such as:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Operating and improving the Service</li>
                    <li>Security and fraud prevention</li>
                    <li>Analytics and performance monitoring</li>
                    <li>Customer support</li>
                    <li>Marketing and promotions (as described below)</li>
                </ul>

                <h3 class="text-xl font-bold text-white">13. Marketing Messages and Promotions</h3>
                <p>By using the Service and providing your contact information, you agree that we may:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>Send promotional emails, notifications, and messages about updates, new features, educational resources, and offers</li>
                    <li>Contact you using the information you provide</li>
                </ul>

                <h4 class="text-lg font-bold text-brand-400 mt-2">13.1 Opt-Out</h4>
                <p>You can opt out of promotional communications anytime by using the unsubscribe link in an email or contacting us. Service-related messages may still be sent even if you opt out of promotions.</p>

                <h3 class="text-xl font-bold text-white">14. Third-Party Services, Links, and Advertisements</h3>
                <p>The Service may include links to third-party websites, services, tools, integrations, or resources that are not owned or controlled by us.</p>
                <p>We do not control, endorse, or take responsibility for the content, policies, availability, accuracy, or practices of any third-party services or websites.</p>
                <p>Accessing or using third-party services is entirely at your own risk and subject to their own terms and policies.</p>
                <p>We are not liable for any loss, damage, or issues arising from your use of third-party services, links, or resources.</p>

                <h4 class="text-lg font-bold text-brand-400 mt-4">14.1 Sponsored Content and Advertisements</h4>
                <p>The Service may display advertisements, sponsored content, or promotional materials provided by our partners or sponsors.</p>
                <p>These advertisements help us cover operational costs, including but not limited to expenses related to AI models, APIs, backend infrastructure, hosting, and maintenance.</p>
                <p>Advertisements may appear automatically and may vary based on availability, region, or system configuration.</p>
                <p>We do not guarantee, verify, or endorse any products, services, or claims made in such advertisements.</p>
                <p>Any interaction, communication, or transaction between you and advertisers is solely between you and the advertiser.</p>
                <p>We are not responsible or liable for any content shown in advertisements or for any outcomes, losses, or damages resulting from your interaction with them.</p>
                <p>You acknowledge and agree that the presence of ads does not create any obligation on us regarding their quality, accuracy, or suitability.</p>

                <h3 class="text-xl font-bold text-white">15. Availability and Changes</h3>
                <p>We may modify, update, or discontinue any part of the Service at any time, add or change features, or update these Terms.</p>

                <h3 class="text-xl font-bold text-white">16. Disclaimers</h3>
                <p>THE SERVICE IS PROVIDED <strong>"AS IS"</strong> AND <strong>"AS AVAILABLE"</strong>.</p>
                <p>To the maximum extent permitted by law, we disclaim all warranties, express or implied, including fitness for a particular purpose, merchantability, and non-infringement. We do not guarantee uninterrupted, error-free, secure, or virus-free operation.</p>

                <h3 class="text-xl font-bold text-white">17. Limitation of Liability</h3>
                <p>To the maximum extent permitted by law:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>We will not be liable for indirect, incidental, special, consequential, or punitive damages.</li>
                    <li>Our total liability will not exceed the amount you paid to us in the last 3 months, or PKR 5,000, whichever is lower.</li>
                </ul>

                <h3 class="text-xl font-bold text-white">18. Indemnity</h3>
                <p>You agree to indemnify and hold us harmless from any claims, liabilities, damages, losses, and expenses arising out of your use/misuse of the Service or violation of these Terms.</p>

                <h3 class="text-xl font-bold text-white">19. Governing Law and Dispute Resolution</h3>
                <p>These Terms are governed by the laws of <strong>Pakistan</strong>. Any dispute will be subject to the exclusive jurisdiction of the courts located in <strong>Punjab, Pakistan</strong>.</p>

                <h3 class="text-xl font-bold text-white">20. Contact</h3>
                <p class="flex items-center gap-2">
                    <i class="fa-regular fa-envelope text-brand-400"></i>
                    <a href="mailto:hello@vdraw.cc" class="text-white hover:underline">hello@vdraw.cc</a>
                </p>

                <h3 class="text-xl font-bold text-white">21. Entire Agreement</h3>
                <p>These Terms form the entire agreement between you and us regarding the Service and supersede any prior agreements.</p>

            </div>

            <!-- Footer Actions -->
            <div class="p-6 border-t border-slate-800 bg-[#0f172a] shrink-0 text-right">
                <button onclick="toggleModal('terms-modal')" class="px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded font-bold transition-colors">Close</button>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div id="privacy-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/90 backdrop-blur-md transition-opacity opacity-0" id="modal-backdrop" onclick="toggleModal('privacy-modal')"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-4xl h-[90vh] bg-[#0f172a] border border-slate-700 rounded-2xl shadow-2xl section-privacy transform scale-95 opacity-0 transition-all duration-300 overflow-hidden flex flex-col" id="modal-content">

            <!-- Header -->
            <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-[#0f172a] z-10 shrink-0">
                <h2 class="text-2xl font-bold text-white">Privacy Policy</h2>
                <button onclick="toggleModal('privacy-modal')" class="text-slate-400 hover:text-white transition-colors bg-slate-800 p-2 rounded-full w-8 h-8 flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="p-8 overflow-y-auto custom-scrollbar text-slate-300 space-y-6 text-sm md:text-base leading-relaxed">

                <p class="italic text-slate-500 text-xs">Effective Date: Jan 2026</p>

                <p>At VDraw ("<strong>we</strong>", "<strong>our</strong>", or "<strong>us</strong>"), we respect your privacy and are committed to protecting the personal information you share with us. This Privacy Policy explains how we collect, use, and safeguard your information when you use our website and educational tools (collectively, the "<strong>Service</strong>").</p>

                <hr class="border-slate-800">

                <h3 class="text-xl font-bold text-white">1. Information We Collect</h3>
                <p>We collect information to provide and improve our educational services.</p>

                <h4 class="text-lg font-bold text-brand-400 mt-2">1.1 Information You Provide</h4>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Account Information:</strong> When you sign up, we collect your name, email address, and password.</li>
                    <li><strong>User Content:</strong> Any code, text, or inputs you submit to our playgrounds or tools.</li>
                    <li><strong>Communications:</strong> If you contact us for support or feedback, we collect the details of that communication.</li>
                </ul>

                <h4 class="text-lg font-bold text-brand-400 mt-2">1.2 Automatically Collected Information</h4>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Usage Data:</strong> We track how you interact with the Service, such as pages visited, features used, and time spent.</li>
                    <li><strong>Device Information:</strong> We collect information about the device and browser you use to access the Service, including IP address and browser type.</li>
                    <li><strong>Cookies:</strong> We use cookies to maintain your session and preferences.</li>
                </ul>

                <h3 class="text-xl font-bold text-white">2. How We Use Your Information</h3>
                <p>We use the collected information for the following purposes:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li>To provide, maintain, and improve the Service.</li>
                    <li>To personalize your learning experience.</li>
                    <li>To communicate with you about updates, features, and security alerts.</li>
                    <li>To monitor and analyze trends and usage to enhance functionality.</li>
                    <li>To protect the security and integrity of our platform.</li>
                </ul>

                <h3 class="text-xl font-bold text-white">3. Data Sharing and Disclosure</h3>
                <p>We do not sell your personal information. We may share your information in the following circumstances:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Service Providers:</strong> With trusted third-party vendors who assist us in operating the Service (e.g., hosting, analytics, email delivery).</li>
                    <li><strong>Legal Requirements:</strong> If required by law or to protect our rights, property, or safety.</li>
                    <li><strong>With Your Consent:</strong> We may share information with your explicit permission.</li>
                </ul>

                <h3 class="text-xl font-bold text-white">4. Data Security</h3>
                <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no internet transmission is completely secure, and we cannot guarantee absolute security.</p>

                <h3 class="text-xl font-bold text-white">5. Your Rights</h3>
                <p>Depending on your location, you may have rights regarding your personal information, including:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Access:</strong> The right to request a copy of the information we hold about you.</li>
                    <li><strong>Correction:</strong> The right to request correction of inaccurate information.</li>
                    <li><strong>Deletion:</strong> The right to request deletion of your account and personal data.</li>
                </ul>
                <p>To exercise these rights, please contact us at <a href="mailto:hello@vdraw.cc" class="text-brand-400 hover:underline">hello@vdraw.cc</a>.</p>

                <h3 class="text-xl font-bold text-white">6. Third-Party Links</h3>
                <p>Our Service may contain links to third-party websites. We are not responsible for the privacy practices or content of those sites. We encourage you to review their privacy policies.</p>

                <h3 class="text-xl font-bold text-white">7. Children's Privacy</h3>
                <p>Our Service is intended for educational use. If you are under the age of majority in your jurisdiction, you should use the Service with the guidance of a parent or guardian.</p>

                <h3 class="text-xl font-bold text-white">8. Changes to This Policy</h3>
                <p>We may update this Privacy Policy from time to time. We will notify you of any significant changes by posting the new policy on this page with a new effective date.</p>

                <h3 class="text-xl font-bold text-white">9. Contact Us</h3>
                <p>If you have any questions about this Privacy Policy, please contact us at:</p>
                <p class="flex items-center gap-2">
                    <i class="fa-regular fa-envelope text-brand-400"></i>
                    <a href="mailto:hello@vdraw.cc" class="text-white hover:underline">hello@vdraw.cc</a>
                </p>

            </div>

            <!-- Footer Actions -->
            <div class="p-6 border-t border-slate-800 bg-[#0f172a] shrink-0 text-right">
                <button onclick="toggleModal('privacy-modal')" class="px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded font-bold transition-colors">Close</button>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            const backdrop = modal.querySelector('#modal-backdrop');
            const content = modal.querySelector('#modal-content');

            if (modal.classList.contains('hidden')) {
                // Open
                modal.classList.remove('hidden');
                // Force reflow
                void modal.offsetWidth;

                // Add show classes
                backdrop.classList.remove('opacity-0');
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            } else {
                // Close
                backdrop.classList.add('opacity-0');
                content.classList.remove('scale-100', 'opacity-100');
                content.classList.add('scale-95', 'opacity-0');

                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new AdManager({
                appKey: 'vdraw', // Assuming Home is generalized as 'vdraw' or 'home'
                rootUrl: 'frontend/ads/'
            });

            // Check for Change Log entries and show button if any exist
            checkChangeLogEntries();
        });

        async function checkChangeLogEntries() {
            try {
                const res = await fetch('api/changelog.php?action=check');
                const data = await res.json();
                if (data.status === 'success' && data.visible) {
                    const btn = document.getElementById('changelog-btn');
                    btn.classList.remove('md:hidden');
                    btn.classList.add('md:flex');
                    document.getElementById('changelog-btn-text').innerText = data.button_title || "What's New";
                }
            } catch (e) {
                console.log('Changelog check failed:', e);
            }
        }

        let changeLogOffset = 0;
        const CHANGELOG_LIMIT = 10;

        async function loadChangeLogModal(append = false) {
            const container = document.getElementById('changelog-content');

            if (!append) {
                changeLogOffset = 0;
                container.innerHTML = '<div class="flex justify-center py-8"><i class="fa-solid fa-spinner fa-spin text-brand-400 text-xl"></i></div>';
            }

            try {
                const res = await fetch(`api/changelog.php?action=list&limit=${CHANGELOG_LIMIT}&offset=${changeLogOffset}`);
                const data = await res.json();

                if (data.status === 'success' && data.logs.length > 0) {
                    let html = '';

                    if (!append) {
                        html = '<div id="changelog-list" class="space-y-4">';
                    }

                    data.logs.forEach((log, idx) => {
                        const typeIcon = getTypeIcon(log.change_type);
                        const typeColor = getTypeColor(log.change_type);
                        const appBadges = log.app_names.length ? log.app_names.map(a =>
                            `<span class="px-1.5 py-0.5 rounded-full bg-slate-700 text-slate-300 text-[9px]">${a}</span>`
                        ).join(' ') : '';

                        html += `
                        <div class="relative pl-6 border-l-2 ${typeColor.border}">
                            <div class="absolute left-[-7px] top-0 w-3 h-3 rounded-full ${typeColor.bg} flex items-center justify-center">
                                ${typeIcon}
                            </div>
                            <div class="bg-slate-800/50 rounded-lg p-3 border border-slate-700/50 hover:border-slate-600 transition-colors">
                                <div class="flex flex-wrap items-center justify-between gap-1 mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase ${typeColor.badge}">
                                            ${formatType(log.change_type)}
                                        </span>
                                        ${log.version_tag ? `<span class="text-[9px] text-slate-500">${log.version_tag}</span>` : ''}
                                    </div>
                                    <span class="text-[10px] text-slate-500">${formatDate(log.created_date)}</span>
                                </div>
                                <h4 class="text-white font-semibold text-sm mb-1">${log.title}</h4>
                                <p class="text-slate-400 text-xs leading-relaxed mb-2">${log.description}</p>
                                ${appBadges ? `<div class="flex flex-wrap gap-1">${appBadges}</div>` : ''}
                            </div>
                        </div>`;
                    });

                    if (!append) {
                        html += '</div>';

                        // Add load more button if there are more entries
                        if (data.has_more) {
                            html += `<div class="text-center mt-4" id="load-more-container">
                                <button onclick="loadMoreChangeLogs()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-lg border border-slate-700 transition-colors">
                                    <i class="fa-solid fa-chevron-down mr-1"></i> Load More
                                </button>
                            </div>`;
                        }

                        container.innerHTML = html;
                    } else {
                        // Append to existing list
                        const list = document.getElementById('changelog-list');
                        list.insertAdjacentHTML('beforeend', html);

                        // Update or remove load more button
                        const loadMoreContainer = document.getElementById('load-more-container');
                        if (loadMoreContainer) {
                            if (data.has_more) {
                                loadMoreContainer.innerHTML = `
                                    <button onclick="loadMoreChangeLogs()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-lg border border-slate-700 transition-colors">
                                        <i class="fa-solid fa-chevron-down mr-1"></i> Load More
                                    </button>`;
                            } else {
                                loadMoreContainer.innerHTML = '<span class="text-slate-600 text-xs italic">No more entries</span>';
                            }
                        }
                    }

                    changeLogOffset += data.logs.length;
                } else if (!append) {
                    container.innerHTML = '<div class="text-center py-8 text-slate-500 italic text-sm">No updates available.</div>';
                }
            } catch (e) {
                if (!append) {
                    container.innerHTML = '<div class="text-center py-8 text-red-400 text-sm">Failed to load change log.</div>';
                }
            }
        }

        function loadMoreChangeLogs() {
            const btn = document.querySelector('#load-more-container button');
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Loading...';
                btn.disabled = true;
            }
            loadChangeLogModal(true);
        }

        function isRecentEntry(dateStr) {
            const entryDate = new Date(dateStr);
            const now = new Date();
            const diffDays = (now - entryDate) / (1000 * 60 * 60 * 24);
            return diffDays <= 7;
        }

        function formatDate(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }

        function formatType(t) {
            if (t === 'new_feature') return 'New Feature';
            if (t === 'bug_fix') return 'Bug Fix';
            return 'Improvement';
        }

        function getTypeIcon(t) {
            if (t === 'new_feature') return '<i class="fa-solid fa-star text-[8px] text-green-200"></i>';
            if (t === 'bug_fix') return '<i class="fa-solid fa-bug text-[8px] text-red-200"></i>';
            return '<i class="fa-solid fa-bolt text-[8px] text-blue-200"></i>';
        }

        function getTypeColor(t) {
            if (t === 'new_feature') return {
                border: 'border-green-500',
                bg: 'bg-green-500',
                badge: 'bg-green-900 text-green-300'
            };
            if (t === 'bug_fix') return {
                border: 'border-red-500',
                bg: 'bg-red-500',
                badge: 'bg-red-900 text-red-300'
            };
            return {
                border: 'border-blue-500',
                bg: 'bg-blue-500',
                badge: 'bg-blue-900 text-blue-300'
            };
        }
    </script>

    <!-- Change Log Modal -->
    <div id="changelog-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/90 backdrop-blur-md transition-opacity opacity-0" id="modal-backdrop" onclick="toggleModal('changelog-modal')"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-4xl h-[90vh] bg-[#0f172a] border border-slate-700 rounded-2xl shadow-2xl transform scale-95 opacity-0 transition-all duration-300 overflow-hidden flex flex-col" id="modal-content">

            <!-- Header -->
            <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-gradient-to-r from-[#0f172a] to-slate-800 z-10 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center shadow-lg">
                        <i class="fa-solid fa-clock-rotate-left text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">What's New</h2>
                        <p class="text-xs text-slate-400">Latest updates and improvements</p>
                    </div>
                </div>
                <button onclick="toggleModal('changelog-modal')" class="text-slate-400 hover:text-white transition-colors bg-slate-800 p-2 rounded-full w-8 h-8 flex items-center justify-center">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div id="changelog-content" class="p-6 overflow-y-auto custom-scrollbar flex-1">
                <div class="flex justify-center py-8"><i class="fa-solid fa-spinner fa-spin text-brand-400 text-2xl"></i></div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-slate-800 bg-[#0f172a] shrink-0 text-center">
                <button onclick="toggleModal('changelog-modal')" class="px-8 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-bold transition-colors">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Override toggleModal to trigger changelog loading
        const originalToggleModal = window.toggleModal;
        window.toggleModal = function(modalId) {
            if (modalId === 'changelog-modal') {
                const modal = document.getElementById(modalId);
                if (modal.classList.contains('hidden')) {
                    loadChangeLogModal();
                }
            }
            originalToggleModal(modalId);
        };
    </script>
</body>


</html>