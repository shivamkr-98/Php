<?php
include 'id_map.php';

$id = $_GET['id'] ?? '';
if (!isset($id_map[$id])) {
  die("Invalid ID");
}

$link = $id_map[$id];
header("Location: $link");
exit;
?>
