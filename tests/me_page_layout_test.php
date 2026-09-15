<?php
/** /me must render the original PHPRetro hotel-view layout, not the account-summary stub. */
$root = dirname(__DIR__);
$source = file_get_contents($root.'/me.php');
$assertions = 0;
function check(bool $condition, string $message): void {
    global $assertions;
    if (!$condition) { throw new RuntimeException($message); }
    $assertions++;
}

check(str_contains($source, 'id="new-personal-info"'), 'me.php has the hotel-view personal info habblet');
check(str_contains($source, 'id="habbo-plate"'), 'me.php shows the avatar plate');
check(str_contains($source, "\$page['cat'] = 'home'"), 'me.php selects the user Home tab');

$header = file_get_contents($root.'/templates/community_header.php');
check(str_contains($header, "['me', 'home', 'profile', 'welcome']"), 'community header forces Home-section pages onto the user tab');
check(str_contains($header, "\$_GET['name'] ?? ''"), 'community header does not read an undefined name query key');

$profile = file_get_contents($root.'/profile.php');
check(str_contains($profile, "\$page['cat'] = 'home'"), 'profile sits under the user Home tab');

$home = file_get_contents($root.'/home.php');
check(str_contains($home, "\$page['cat'] = 'home'"), 'homes sit under the user Home tab');
check(str_contains($source, 'id="column2"'), 'me.php has the second habblet column');
check(str_contains($source, 'hotcampaigns-habblet-list'), 'me.php still lists campaigns');
check(str_contains($source, 'id="newspromo"'), 'me.php still has the news promo');
check(str_contains($source, 'id="minimail"'), 'me.php keeps minimail');
check(!str_contains($source, 'Welcome, <?php echo $escape($profile[\'username\'])'), 'me.php is not the account-summary stub');
check(!preg_match('/\$db->query\(/', $source), 'me.php does not use legacy mysql $db->query');

echo 'PASS: '.$assertions.' assertions; /me uses the original two-column hotel-view layout.'."\n";
