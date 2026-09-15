<?php
$page['dir'] = '\xml';
require_once('../includes/core.php');

header("Content-Type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<habbos>\n";

$habbos = [];
try {
    $habbos = $db->fetchAll('SELECT id, username, motto, look FROM users ORDER BY id DESC LIMIT 20');
} catch (Throwable $exception) {
    $habbos = [];
}
foreach ($habbos as $row) {
    $name = $input->HoloText($row['username']);
    $motto = $input->HoloText($row['motto'] ?? '');
    $image = $input->HoloText($user->avatarURL($row['look'] ?? '', 'b,4,3,sml,1,0'));
    printf("<habbo id=\"%s\" name=\"%s\" motto=\"%s\" url=\"".PATH."/home/%s\" image=\"%s\" badge=\"\" status=\"0\" />\n", (int) $row['id'], $name, $motto, $name, $image);
}

echo "</habbos>";
