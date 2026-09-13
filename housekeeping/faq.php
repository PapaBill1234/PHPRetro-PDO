<?php
$page['dir'] = '\\housekeeping'; $page['housekeeping'] = true; $page['rank'] = 5;
require_once('../includes/core.php'); require_once('./includes/hksession.php');
require_once('../includes/AdminAudit.php');
$database = new Database(); $action = $_GET['do'] ?? 'list'; $notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'delete') { $database->execute('DELETE FROM phpretro_faq WHERE id = ?', [$id]); $notice = 'FAQ entry removed.'; }
    else {
        $category = trim((string) ($_POST['category'] ?? 'general')); $question = trim((string) ($_POST['question'] ?? '')); $answer = trim((string) ($_POST['answer'] ?? ''));
        $order = (int) ($_POST['sort_order'] ?? 0); $active = isset($_POST['active']) ? 1 : 0;
        if ($question === '' || $answer === '') { $notice = 'Question and answer are required.'; }
        elseif ($id > 0) { $database->execute('UPDATE phpretro_faq SET category = ?, question = ?, answer = ?, sort_order = ?, active = ? WHERE id = ?', [$category, $question, $answer, $order, $active, $id]); $notice = 'FAQ entry updated.'; }
        else { $database->execute('INSERT INTO phpretro_faq (category, question, answer, sort_order, active) VALUES (?, ?, ?, ?, ?)', [$category, $question, $answer, $order, $active]); $notice = 'FAQ entry created.'; }
    } if ($_SERVER['REQUEST_METHOD'] === 'POST') { AdminAudit::log($database, (int) $user->id, 'faq_'.$action, 'faq', $id ?: null); } $action = 'list';
}
$entry = ['id'=>0,'category'=>'general','question'=>'','answer'=>'','sort_order'=>0,'active'=>1];
if ($action === 'edit') { $loaded = $database->fetchRow('SELECT id, category, question, answer, sort_order, active FROM phpretro_faq WHERE id = ?', [(int) ($_GET['id'] ?? 0)]); if ($loaded !== false) { $entry = $loaded; } }
$entries = $database->fetchAll('SELECT id, category, question, answer, sort_order, active FROM phpretro_faq ORDER BY category, sort_order, id');
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$page['name'] = 'FAQ'; $page['category'] = 'tools'; require_once('./templates/housekeeping_header.php');
?>
<div class="page_title"><span class="page_name">FAQ</span></div><div class="page_main"><div class="center">
<?php if ($notice !== '') { ?><div class="clean-ok"><?php echo $escape($notice); ?></div><?php } ?>
<?php if ($action === 'create' || $action === 'edit') { ?><form method="post"><input type="hidden" name="id" value="<?php echo (int) $entry['id']; ?>"><label>Category</label><br><input name="category" maxlength="100" value="<?php echo $escape($entry['category']); ?>"><br><label>Question</label><br><input name="question" maxlength="255" value="<?php echo $escape($entry['question']); ?>"><br><label>Answer</label><br><textarea name="answer"><?php echo $escape($entry['answer']); ?></textarea><br><label>Display order</label><br><input name="sort_order" type="number" value="<?php echo (int) $entry['sort_order']; ?>"><br><label><input name="active" type="checkbox" value="1"<?php echo (int) $entry['active'] === 1 ? ' checked' : ''; ?>> Active</label><br><input type="submit" value="Save"></form><?php } else { ?>
<p><a href="<?php echo PATH; ?>/housekeeping/faq?do=create">New FAQ entry</a></p><table><tr><th>Category</th><th>Question</th><th>Order</th><th>Visible</th><th>Actions</th></tr><?php foreach ($entries as $item) { ?><tr><td><?php echo $escape($item['category']); ?></td><td><?php echo $escape($item['question']); ?></td><td><?php echo (int) $item['sort_order']; ?></td><td><?php echo (int) $item['active'] === 1 ? 'Yes' : 'No'; ?></td><td><a href="<?php echo PATH; ?>/housekeeping/faq?do=edit&id=<?php echo (int) $item['id']; ?>">Edit</a> <form style="display:inline" method="post" action="<?php echo PATH; ?>/housekeeping/faq?do=delete"><input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>"><button>Delete</button></form></td></tr><?php } ?></table><?php } ?>
</div></div><?php require_once('./templates/housekeeping_footer.php'); ?>