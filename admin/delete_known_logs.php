<?php
/**
 * @package admin
 * @copyright Copyright 2003-2018 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
require('includes/application_top.php');

?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
  <head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
  </head>
  <body>
    <!-- header //-->
    <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
    <!-- header_eof //-->
    <div class="container-fluid">
      <!-- body //-->
  <h1><?php echo HEADING_TITLE; ?></h1>

<?php
$queries[] = [
   'name' => 'max_user_connections', 
   'string' => "already has more than 'max_user_connections' active connections"
]; 
$queries[] = [
   'name' => 'Connection timed out',
   'string' => "mysqli_connect.*Connection timed out",
]; 
$queries[] = [
   'name' => 'Connection refused',
   'string' => "mysqli_connect.*Connection refused",
]; 
$queries[] = [
   'name' => 'Connect failure',
   'string' => "mysqli_connect.*No such file or directory",
];
$queries[] = [
   'name' => 'No route to host',
   'string' => "mysqli_connect.*No route to host",
]; 
$queries[] = [
   'name' => 'Too many connections',
   'string' => "mysqli_connect.*Too many connections",
]; 
$queries[] = [
   'name' => 'Packets out of order',
   'string' => "Packets out of order.",
]; 
$queries[] = [
   'name' => 'Greeting Packet',
   'string' => "mysqli_connect.*Error while reading greeting packet.",
]; 

// More possible queries - edit or remove as desired.
// ...
chdir(DIR_FS_LOGS); 
foreach ($queries as $item) { 
  $count = 0; 
  $cmd = 'grep -l "' . $item['string'] . '" *.log 2>/dev/null'; 
  $handle = popen($cmd, 'r'); 
  while (!feof($handle)) { 
      $fn = fgets($handle,512); 
      $fn = rtrim($fn);
      if (!empty($fn) && file_exists($fn)) { 
         unlink($fn);
         $count++; 
      }
  }

  echo $item['name'] . ' - ' . OUTPUT_MSG . $count . '<br>'; 
}
?>
<br><br><hr>
        <table>
          <tr>
            <td class="main"><?php echo 'Cleanup Debug Log Files'; ?></td>
            <td class="main"><?php echo zen_draw_form('clean_debug_files', FILENAME_STORE_MANAGER, 'action=clean_debug_files', 'post'); ?>
                <input class="btn btn-default btn-sm" type="submit" value="<?php echo IMAGE_CONFIRM; ?>">
                <?php echo '</form>'; ?>
          </tr>
        </table>
      <!-- body_text_eof //-->
    </div>
    <!-- body_eof //-->

    <!-- footer //-->
    <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
    <!-- footer_eof //-->
  </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
