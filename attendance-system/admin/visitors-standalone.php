<?php
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../auth/helpers.php";
requireAuth(); // Require authentication - redirects to login if not authenticated

// Include Breadcrumb component for getGreeting function (but don't render breadcrumb)
include_once '../shared/components/Breadcrumb.php';

// Get current user for greeting
$currentUser = currentUser();
$userName = $currentUser ? ($currentUser['full_name'] ?? $currentUser['username']) : 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitors Logbook | Face ID</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fc; /* Light background for the main content area */
        }
        /* Video container for visual cue */
        #video-feed-container {
            min-height: 400px;
            /* background-color: #333; <-- Removed as video will show through */
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            border: 4px solid #007bff; /* Highlight border */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            position: relative; /* Needed for canvas overlay */
        }
        /* Style for the actual video and canvas */
        #webcam-video, #video-overlay {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 10;
        }
        #video-overlay {
            z-index: 20; /* Canvas on top of video */
        }
        /* Recognition status container */
        #recognition-status {
            min-height: 250px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        #logList li {
            padding: 8px 4px;
            border-bottom: 1px solid #f3f4f6;
        }
        #logList li:last-child {
            border-bottom: none;
        }
        #logList::-webkit-scrollbar {
            width: 4px;
        }
        #logList::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }
    </style>
</head>
<body>

    <main class="min-h-screen p-6">

        <header class="mb-6">
            <div class="flex justify-between items-center mb-1">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Visitors Logging</h1>
                    <p class="text-gray-500 text-sm"><?= getGreeting($userName) ?> - Use face recognition to quickly logging.</p>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Live Camera Feed</h2>
                
                <div id="video-feed-container" class="w-full rounded-xl overflow-hidden relative">
                    <video id="webcam-video" class="w-full h-full object-cover hidden" autoplay playsinline></video>
                    
                    <canvas id="video-overlay" class="w-full h-full hidden"></canvas>

                    <div id="video-placeholder" class="absolute inset-0 flex flex-col items-center justify-center p-4 bg-black bg-opacity-30 rounded-xl">
                        <svg class="w-20 h-20 text-blue-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.107 4h3.784a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <p class="text-white font-medium">Click "Start Camera" to begin</p>
                        <p class="text-sm text-gray-200 mt-1" id="camera-status">Status: Ready to start camera.</p>
                    </div>
                </div>
                
                <!-- Camera Control Buttons -->
                <div class="mt-4 flex justify-center gap-4">
                    <button id="start-camera-btn" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Start Camera
                    </button>
                    <button id="stop-camera-btn" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200 flex items-center gap-2 hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"></path>
                        </svg>
                        Stop Camera
                    </button>
                </div>
            </div>

            <div class="lg:col-span-1 flex flex-col space-y-6">

                <div id="recognition-status" class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 text-center flex-grow">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Recognition Status</h2>
                    
                    <svg id="status-icon" class="w-16 h-16 text-yellow-500 mx-auto animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    
                    <p class="mt-4 text-2xl font-bold text-gray-800" id="status-title">Loading Models...</p>
                    <p class="text-gray-500" id="status-message">Please wait for the system to initialize.</p>
                    
                    <div id="recognized-user" class="mt-6 p-4 border-t border-gray-200 hidden">
                        <img id="user-photo" src="https://placehold.co/80x80/007bff/white?text=R" alt="User Photo" class="w-20 h-20 rounded-full mx-auto mb-2">
                        <p class="text-lg font-semibold text-green-600" id="user-action">CHECK-IN SUCCESSFUL!</p>
                        <p class="text-sm text-gray-700" id="user-details">Resident: Jane Doe (Unit 302)</p>
                        <p class="text-xs text-gray-500" id="user-time">Time: 10:15 AM</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-3">Recent Visitor Logs</h2>
                    <ul id="logList" class="space-y-1 text-sm text-gray-700 max-h-[400px] overflow-y-auto">
                        <li class="text-center text-gray-400 py-4">Loading visitor logs...</li>
                    </ul>
                </div>

            </div>
        </div>

    </main>

    <!-- Modular JavaScript Entry Point -->
    <!-- Note: Face-API.js is loaded via CDN above and will be available as a global variable -->
    <script type="module" src="./js/visitors/main.js"></script>
</body>
</html>
