generate_php = """<?php
function generate_id($text) {
  return substr(md5($text), 0, 10);
}

$filename = "main.txt";
if (!file_exists($filename)) {
  die("main.txt not found.");
}

$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$teachers = [];
$current_teacher = null;
$current_chapter = null;
$current_lectures = [];

function flush_chapter(&$teachers, &$current_teacher, &$current_chapter, &$current_lectures) {
  if ($current_chapter && !empty($current_lectures)) {
    $current_teacher["chapters"][] = [
      "chapter_name" => $current_chapter,
      "lectures" => $current_lectures
    ];
    $current_chapter = null;
    $current_lectures = [];
  }
}

$id_map = [];

for ($i = 0; $i < count($lines); $i++) {
  $line = trim($lines[$i]);

  if (strpos($line, '@Teacher:') === 0) {
    if ($current_teacher) {
      flush_chapter($teachers, $current_teacher, $current_chapter, $current_lectures);
      $teachers[] = $current_teacher;
    }
    $current_teacher = [
      "teacher" => trim(str_replace('@Teacher:', '', $line)),
      "image" => "",
      "chapters" => []
    ];
  } elseif (strpos($line, '$image:') === 0 || strpos($line, 'image:') === 0) {
    $current_teacher["image"] = trim(explode(':', $line, 2)[1]);
  } elseif (strpos($line, '#Chapter:') === 0) {
    flush_chapter($teachers, $current_teacher, $current_chapter, $current_lectures);
    $current_chapter = trim(str_replace('#Chapter:', '', $line));
  } elseif (preg_match('/^[A-Za-z].*\\|.*$/', $line)) {
    $title = $line;
    $video_url = isset($lines[$i+1]) ? trim($lines[$i+1]) : "";
    $notes_url = isset($lines[$i+2]) ? trim($lines[$i+2]) : "";
    if (strpos($video_url, 'http') === 0 && strpos($notes_url, 'http') === 0) {
      $video_id = generate_id($video_url);
      $notes_id = generate_id($notes_url);
      $current_lectures[] = [
        "title" => $title,
        "video_id" => $video_id,
        "notes_id" => $notes_id
      ];
      $id_map[$video_id] = $video_url;
      $id_map[$notes_id] = $notes_url;
    }
  } elseif (preg_match('/Sheet$/', $line)) {
    $sheet_url = isset($lines[$i+1]) ? trim($lines[$i+1]) : "";
    if (strpos($sheet_url, 'http') === 0) {
      $sheet_id = generate_id($sheet_url);
      $current_lectures[] = [
        "title" => $line,
        "sheet_id" => $sheet_id
      ];
      $id_map[$sheet_id] = $sheet_url;
    }
  }
}
if ($current_teacher) {
  flush_chapter($teachers, $current_teacher, $current_chapter, $current_lectures);
  $teachers[] = $current_teacher;
}

// Write data.php
file_put_contents("data.php", "<?php\\nheader('Content-Type: application/json');\\necho json_encode(" . var_export(["teachers" => $teachers], true) . ", JSON_PRETTY_PRINT);\\n?>");

// Write id_map.php
file_put_contents("id_map.php", "<?php\\n\\n\$id_map = " . var_export($id_map, true) . ";\\n?>");

echo "✅ data.php and id_map.php generated from main.txt";
?>"""

# Save this as generate.php
with open("/mnt/data/generate.php", "w", encoding="utf-8") as f:
    f.write(generate_php)

"/mnt/data/generate.php created successfully."
