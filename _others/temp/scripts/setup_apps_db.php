<?php
require_once __DIR__ . '/auth/db.php';

$conn = DB::connect();

// Create apps table
$sql = "CREATE TABLE IF NOT EXISTS apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL, -- Folder name, used for URL
    nav_title VARCHAR(50) NOT NULL, -- Text in navigation bar
    home_title VARCHAR(100) NOT NULL, -- Title in Home Box
    home_description TEXT, -- Description in Home Box
    icon_class VARCHAR(100), -- FontAwesome class
    theme_color VARCHAR(20) DEFAULT 'blue', -- blue, green, amber, sky, purple
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'apps' created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Prepare initial data
$apps = [
    [
        'name' => 'Stats',
        'nav_title' => 'Stats',
        'home_title' => 'Statistics Lab',
        'home_description' => 'Master the fundamentals of descriptive statistics. Analyze datasets, calculate variance and regression, and visualize distributions with interactive charts designed for classroom demonstrations.',
        'icon_class' => 'fa-solid fa-chart-simple',
        'theme_color' => 'blue',
        'display_order' => 10
    ],
    [
        'name' => 'Linear',
        'nav_title' => 'Linear',
        'home_title' => 'Linear Studio',
        'home_description' => 'Explore linear data structures like Stacks, Queues, and Linked Lists. Watch operations like Push, Pop, and Enqueue happen in real-time to build a solid foundation in computer science.',
        'icon_class' => 'fa-solid fa-layer-group',
        'theme_color' => 'green',
        'display_order' => 20
    ],
    [
        'name' => 'Graph',
        'nav_title' => 'Graph',
        'home_title' => 'TGDraw Graph',
        'home_description' => 'Dive into the world of non-linear structures. Build and traverse Trees and Graphs interactively, perfect for understanding complex algorithms like BFS, DFS, and pathfinding.',
        'icon_class' => 'fa-solid fa-diagram-project',
        'theme_color' => 'amber',
        'display_order' => 30
    ],
    [
        'name' => 'PyViz',
        'nav_title' => 'PyViz',
        'home_title' => 'PyViz Explainer',
        'home_description' => 'Demystify Python code execution. Visualize the flow of logic, variable changes, and function calls step-by-step, making programming concepts accessible to every student.',
        'icon_class' => 'fa-brands fa-python',
        'theme_color' => 'sky',
        'display_order' => 40
    ],
    [
        'name' => 'DViz',
        'nav_title' => 'Dviz',
        'home_title' => 'DViz Library',
        'home_description' => 'Your comprehensive digital library. Access a curated collection of educational slides and course materials, structured to support a complete curriculum in data visualization.',
        'icon_class' => 'fa-solid fa-book-open',
        'theme_color' => 'purple',
        'display_order' => 50
    ]
];

// Insert data if table is empty
$check = $conn->query("SELECT COUNT(*) as count FROM apps");
$row = $check->fetch_assoc();

if ($row['count'] == 0) {
    $stmt = $conn->prepare("INSERT INTO apps (name, nav_title, home_title, home_description, icon_class, theme_color, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($apps as $app) {
        $stmt->bind_param("ssssssi", $app['name'], $app['nav_title'], $app['home_title'], $app['home_description'], $app['icon_class'], $app['theme_color'], $app['display_order']);
        $stmt->execute();
    }
    echo "Initial apps inserted.\n";
} else {
    echo "Apps table already populated.\n";
}

echo "Database setup complete.";
?>
