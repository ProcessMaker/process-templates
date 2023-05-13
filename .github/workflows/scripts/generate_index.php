<?php

function compute_hash($data) {
    return $data ? sha1($data) : null;
}

function update_readme($categories) {
    $readme = fopen("README.md", "w");
    fwrite($readme, "# ProcessMaker Process Templates\n");
    ksort($categories);  // Sort categories alphabetically
    foreach ($categories as $category => $templates) {
        $category = str_replace("-", " ", $category);
        $category = ucwords($category);
        fwrite($readme, "## $category\n");
        usort($templates, function($a, $b) { return strcmp($a['name'], $b['name']); });  // Sort templates alphabetically within each category
        foreach ($templates as $template) {
            fwrite($readme, "- **[{$template['name']}](/{$template['relative_path']})**: {$template['description']}\n");
        }
    }
    fclose($readme);
}

function main() {
    $root_dir = ".";
    $categories = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root_dir));

    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        if (pathinfo($file->getPathname(), PATHINFO_EXTENSION) != "json") continue;
        if ($file->getFilename() == "index.json") continue;

        $filepath = $file->getPathname();
        $mod_time = date("Y-m-d H:i:s", filemtime($filepath));
        $data = json_decode(file_get_contents($filepath), true);
        $category = str_replace("./", "", $file->getPath());

        $template_info = [
            "name" => $data["name"],
            "description" => $data["export"][$data["root"]]["description"],
            "hash" => compute_hash($data["export"][$data["root"]]["attributes"]["manifest"]),
            "mod_time" => $mod_time,
            "relative_path" => $filepath,
            "uuid" => $data["root"],
        ];

        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }

        $categories[$category][] = $template_info;
    }

    ksort($categories);  // Sort categories alphabetically
    foreach ($categories as $category => $templates) {
        usort($templates, function($a, $b) { return strcmp($a['name'], $b['name']); });  // Sort templates alphabetically within each category
        $categories[$category] = $templates;
    }

    file_put_contents("index.json", json_encode($categories, JSON_PRETTY_PRINT));

    update_readme($categories);
}

main();
