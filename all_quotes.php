<?php
require_once('wpframe.php');
wpframe_stop_direct_call(__FILE__);

if(isset($_REQUEST['submit'])) {
	$status = ($_REQUEST['status']) ? 1 : 0;
	if($_REQUEST['action'] == 'edit') { //Update goes here
		$wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}quartz_quote SET quote=%s, status='$status' WHERE ID=%d", $_REQUEST['content'], $_REQUEST['quote']));
		wpframe_message('Quote Updated');
	
	} else {
		$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}quartz_quote(quote,status) VALUES(%s,'$status')", $_REQUEST['content']));
		wpframe_message('Quote Added');
	}
}

if($_REQUEST['action'] == 'delete') {
	$wpdb->query("DELETE FROM {$wpdb->prefix}quartz_quote WHERE ID='$_REQUEST[quote]'");
	wpframe_message('Quote Deleted');
}
?>

<div class="wrap">
<h2><?php e("Manage Quotes"); ?></h2>

<?php
wp_enqueue_script( 'listman' );
wp_print_scripts();
?>

<table class="widefat">
	<thead>
	<tr>
		<th scope="col"><div style="text-align: center;"><?php e('ID'); ?></div></th>
		<th scope="col"><?php e('Quote'); ?></th>
		<th scope="col"><?php e('Status'); ?></th>
		<th scope="col" colspan="3"><?php e('Action'); ?></th>
	</tr>
	</thead>

	<tbody id="the-list">
<?php
$offset = 0;
$page = 1;
$items_per_page = 30;

if(isset($_REQUEST['paged'])) {
	$page = intval($_REQUEST['paged']);
	$offset = ($page - 1) * $items_per_page;
}
// Retrieve the quotees
$all_quote = $wpdb->get_results("SELECT ID, quote, status FROM `{$wpdb->prefix}quartz_quote` ORDER BY ID LIMIT $offset, $items_per_page");

if (count($all_quote)) {
	$bgcolor = '';
	$class = ('alternate' == $class) ? '' : 'alternate';
	$status = array(t('Inactive'), t('Active'));
	foreach($all_quote as $quote) { ?>
		<tr id='quote-<?php echo $quote->ID?>' class='<?php echo $class; ?>'>
		<th scope="row" style="text-align: center;"><?php echo $quote->ID ?></th>
		<td><?php echo stripslashes($quote->quote) ?></td>
		<td><?php echo $status[$quote->status]?></td>
		<td><a href='edit.php?page=quartz/quote_form.php&amp;quote=<?php echo $quote->ID?>&amp;action=edit' class='edit'><?php e('Edit'); ?></a></td>
		<td><a href='edit.php?page=quartz/all_quotes.php&amp;action=delete&amp;paged=<?php echo $page?>&amp;quote=<?php echo $quote->ID?>' class='delete' onclick="return confirm('<?php e(addslashes("You are about to delete this quote. Press 'OK' to delete and 'Cancel' to stop."))?>');"><?php e('Delete')?></a></td>
		</tr>
<?php
		}
?>
	 
<?php
	} else {
?>
	<tr style='background-color: <?php echo $bgcolor; ?>;'>
		<td colspan="8"><?php e('No quotes found.') ?></td>
	</tr>
<?php
}
?>
	</tbody>
</table>

<div class="tablenav">
<?php
$total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quartz_quote");
$total_pages = ceil($total_items / $items_per_page);
// for($i=1; $i<=$total_items; $i++ ) {
// 	if($i == $page) print " $i ";
// }
$page_links = paginate_links( array(
	'base' => add_query_arg( 'paged', '%#%' ),
	'format' => '',
	'total' => $total_pages,
	'current' => $page
));
if ( $page_links ) echo "<div class='tablenav-pages'>$page_links</div>";

?>

<a href="edit.php?page=quartz/quote_form.php&amp;action=new"><?php e("Create New Quote")?></a><br />
<a href="edit.php?page=quartz/import.php"><?php e("Import Quotes")?></a><br />
</div>
</div>
