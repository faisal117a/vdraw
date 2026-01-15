<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dataDir = __DIR__ . '/data';

if (!is_dir($dataDir)) {
    echo json_encode(['levels' => []]);
    exit;
}

/**
 * Clean display name from filename
 */
function cleanDisplayName($filename) {
    // Remove extension
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Remove prefix (s, s1, m, i, s), m1_, etc.) followed by separator
    // Pattern: ^[a-z]+[0-9]*[\_\-\)\.\s]+
    $name = preg_replace('/^[a-z]+[0-9]*[\_\-\)\.\s]+/i', '', $name);
    
    // Remove remaining underscores/dashes just in case
    $name = str_replace(['_', '-'], ' ', $name);
    
    // Trim
    $name = trim($name);
    
    // Title Case
    return ucwords(strtolower($name));
}

$index = ['levels' => []];

// Scan Levels (all folders)
$parts = glob($dataDir . '/*', GLOB_ONLYDIR);
if ($parts) {
    natsort($parts); // Natural sort (part1, part2, part10)

    foreach ($parts as $partPath) {
        $partDir = basename($partPath);
        if ($partDir === 'all pdf') continue; // Exclude utility folder
        $levelId = $partDir;
        
        $levelObj = [
            'id' => $levelId,
            'dir' => $partDir,
            'label' => ucfirst($partDir),
            'visuals' => []
        ];

        // Scan Chapters (chapter*)
        $chapters = glob($partPath . '/chapter*', GLOB_ONLYDIR);
        if (!$chapters) $chapters = [];
        natsort($chapters); 

        foreach ($chapters as $chapPath) {
            $chapDir = basename($chapPath);
            
            // Derive Title from Folder Name
            // If "chapter1" -> "Chapter 1"
            // If "Introduction" -> "Introduction"
            $title = ucwords(str_replace(['_', '-'], ' ', $chapDir));
            
            // Normalize "chapter1" to "Chapter 1"
            if (stripos($title, 'Chapter') === 0) {
                // Ensure space after Chapter if missing
                // "Chapter1" -> "Chapter 1"
                $title = preg_replace('/Chapter(\d)/i', 'Chapter $1', $title);
            }

            $visualObj = [
                'id' => $chapDir, // Use folder name as ID
                'dir' => $chapDir,
                'title' => $title,
                'assets' => [
                    'presentations' => [],
                    'videos' => [],
                    'ideas' => [],
                    'summary' => []
                ]
            ];

            // Scan Subfolders
            $subs = ['slides', 'images', 'videos'];
            foreach ($subs as $sub) {
                $subPath = $chapPath . '/' . $sub;
                if (is_dir($subPath)) {
                    $files = scandir($subPath);
                    foreach ($files as $file) {
                        if ($file === '.' || $file === '..') continue;
                        
                        $relativePath = "data/$partDir/$chapDir/$sub/$file";
                        $display = cleanDisplayName($file);
                        $lowerFile = strtolower($file);

                        // Removed Heuristic to overwrite Chapter Title from file content
                        // as per user request "chapter name must be folder name"

                        // Categorize
                        if ($sub === 'slides') {
                            if ($lowerFile[0] === 's') {
                                 $visualObj['assets']['presentations'][] = ['type' => 'short', 'title' => $display, 'path' => $relativePath];
                            } elseif ($lowerFile[0] === 'm') {
                                 $visualObj['assets']['presentations'][] = ['type' => 'mcq', 'title' => $display, 'path' => $relativePath];
                            } elseif ($lowerFile[0] === 'i') {
                                 $visualObj['assets']['summary'][] = ['title' => $display, 'path' => $relativePath];
                            }
                        } elseif ($sub === 'images') {
                            $visualObj['assets']['ideas'][] = ['title' => $display, 'path' => $relativePath];
                        } elseif ($sub === 'videos') {
                             $visualObj['assets']['videos'][] = ['title' => $display, 'path' => $relativePath];
                        }
                    }
                }
            }
            $levelObj['visuals'][] = $visualObj;
        }
        $index['levels'][] = $levelObj;
    }
}

echo json_encode($index, JSON_PRETTY_PRINT);
?>
