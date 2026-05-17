<?php
/**
 * Breadcrumb Component
 * 
 * @param array $items Array of breadcrumb items [['label' => 'Home', 'link' => 'dashboard.php'], ...]
 * @return void
 */
function Breadcrumb($items = [])
{
    // Default: Home -> Current Page
    $defaultItems = [
        ['label' => 'Dashboard', 'link' => 'dashboard.php']
    ];
    
    $breadcrumbs = !empty($items) ? $items : $defaultItems;
    ?>
    <nav class="flex mb-4" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3 px-0">
            <?php foreach ($breadcrumbs as $index => $item): ?>
                <li class="inline-flex items-center">
                    <?php if ($index > 0): ?>
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    <?php endif; ?>
                    <?php if ($index === count($breadcrumbs) - 1): ?>
                        <!-- Current page (not a link) -->
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2"><?= htmlspecialchars($item['label']) ?></span>
                    <?php else: ?>
                        <!-- Link to previous pages -->
                        <a href="<?= htmlspecialchars($item['link']) ?>" class="ml-1 text-sm font-medium text-gray-700 hover:text-gray-900 md:ml-2">
                            <?= htmlspecialchars($item['label']) ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}

/**
 * Get greeting based on time of day
 * 
 * @param string|null $userName User's name (optional)
 * @return string
 */
function getGreeting($userName = null)
{
    $hour = (int)date('H');
    $greeting = '';
    
    if ($hour < 12) {
        $greeting = 'Good morning';
    } elseif ($hour < 17) {
        $greeting = 'Good afternoon';
    } else {
        $greeting = 'Good evening';
    }
    
    if ($userName) {
        return $greeting . ', ' . htmlspecialchars($userName);
    }
    
    return $greeting;
}

