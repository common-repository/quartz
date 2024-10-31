<?php
require_once('wpframe.php');
wpframe_stop_direct_call(__FILE__);

$home = $wpframe_home;
$action = 'new';
if($_REQUEST['action'] == 'edit') $action = 'edit';

$quote = array();
if($action == 'edit') {
	$quote = $wpdb->get_row("SELECT quote,status FROM {$wpdb->prefix}quartz_quote WHERE ID = $_REQUEST[quote]");
}
?>

<div class="wrap">
<h2><?php e(ucfirst($action) . " Quote"); ?></h2>
<input type="hidden" id="title" name="ignore_me" value="This is here for a workaround for a editor bug" />

<?php
wpframe_add_editor_js();
?>

<form name="post" action="edit.php?page=quartz/all_quotes.php" method="post" id="post">
<div id="poststuff">
<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">

<div class="postbox">
<h3 class="hndle"><span><?php e('Quote') ?></span></h3>
<div class="inside">
<?php the_editor($quote->quote); ?>
</div></div>

<div class="postbox">
<h3 class="hndle"><span><?php e('Status') ?></span></h3>
<div class="inside">
<label for="status">Active</label> <input type="checkbox" name="status" value="1" id="status" <?php if($quote->status or $action=='new') print " checked='checked'"; ?> /><br />
</div></div>

</div>

<p class="submit">
<input type="hidden" name="action" value="<?php echo $action; ?>" />
<input type="hidden" name="quote" value="<?php echo $_REQUEST['quote']; ?>" />
<input type="hidden" id="user-id" name="user_ID" value="<?php echo (int) $user_ID ?>" />
<span id="autosave"></span>
<input type="submit" name="submit" value="<?php e('Save') ?>" style="font-weight: bold;" tabindex="4" />
</p>

</div>
</form>

<a href="edit.php?page=quartz/all_quotes.php"><?php e("Manage Quotes")?></a><br />
<a href="edit.php?page=quartz/import.php"><?php e("Bulk Import Quotes")?></a><br />
</div>
