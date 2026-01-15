<?php
require_once __DIR__ . '/db.php';

class AppHelper {
    public static function getAllApps() {
        $conn = DB::connect();
        $res = $conn->query("SELECT * FROM apps WHERE is_active = 1 ORDER BY display_order ASC");
        $apps = [];
        if($res) while($r=$res->fetch_assoc()) $apps[] = $r;
        return $apps;
    }
    
    public static function getTheme($color) {
        $themes = [
            'blue' => [
                'gradient' => 'from-blue-500 to-indigo-500', 
                'icon_bg' => 'bg-blue-900/30', 
                'icon_text' => 'text-blue-400', 
                'icon_hover' => 'group-hover:bg-blue-600',
                'link_text' => 'text-blue-400',
                'nav_active' => 'bg-blue-600'
            ],
            'green' => [
                'gradient' => 'from-green-500 to-teal-500', 
                'icon_bg' => 'bg-green-900/30', 
                'icon_text' => 'text-green-400', 
                'icon_hover' => 'group-hover:bg-green-600',
                'link_text' => 'text-green-400',
                'nav_active' => 'bg-green-600'
            ],
            'amber' => [
                'gradient' => 'from-amber-500 to-orange-500', 
                'icon_bg' => 'bg-amber-900/30', 
                'icon_text' => 'text-amber-400', 
                'icon_hover' => 'group-hover:bg-amber-600',
                'link_text' => 'text-amber-400',
                'nav_active' => 'bg-amber-600'
            ],
            'sky' => [
                'gradient' => 'from-blue-400 to-sky-400', 
                'icon_bg' => 'bg-sky-900/30', 
                'icon_text' => 'text-sky-400', 
                'icon_hover' => 'group-hover:bg-sky-600',
                'link_text' => 'text-sky-400',
                'nav_active' => 'bg-sky-600'
            ],
            'purple' => [
                'gradient' => 'from-purple-500 to-pink-500', 
                'icon_bg' => 'bg-purple-900/30', 
                'icon_text' => 'text-purple-400', 
                'icon_hover' => 'group-hover:bg-purple-600',
                'link_text' => 'text-purple-400',
                'nav_active' => 'bg-purple-600'
            ],
        ];
        return $themes[$color] ?? $themes['blue'];
    }
}
?>
