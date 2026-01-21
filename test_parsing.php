<?php
// Mocking the behavior of iclockController parsing
$content = "191 2026-01-20 08:58:59 255 15 0 0 0 0 0 0";
$rows = explode("\n", $content);

echo "Testing parsing for line: '$content'\n\n";

foreach ($rows as $row) {
    if (trim($row) != "") {
        // Method 1: Tab separated (current code might assume this?)
        $data_arr_tab = explode("\t", $row);
        echo "Explode by TAB count: " . count($data_arr_tab) . "\n";
        
        // Method 2: Space separated (what the log looks like)
        $data_arr_space = explode(" ", $row);
        echo "Explode by SPACE count: " . count($data_arr_space) . "\n";
        
        // Check current logic simulation
        // Assuming the controller does something like this:
        if (count($data_arr_tab) > 1) {
            echo "Used TAB separation.\n";
            print_r($data_arr_tab);
        } else {
            echo "TAB separation failed (count <= 1).\n";
            // Does it fallback to space?
        }
    }
}
