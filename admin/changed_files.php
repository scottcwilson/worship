<?php
// Changed files report by That Software Guy
// https://www.thatsoftwareguy.com/zencart_changed_files.html
require('includes/application_top.php');
if (file_exists(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . 'changed_files.php')) {
  include(DIR_WS_LANGUAGES . $_SESSION['language'] . '/' . 'changed_files.php');
}

if (!class_exists('RecursiveDirectoryIterator') || (!class_exists('RecursiveIteratorIterator'))) {
   die("RecursiveDirectoryIterator and/or RecursiveIteratorIterator have not been defined on your system"); 
}

// Add any filetypes you do not want reported - IGNORE list 
$filetype_exclusions = array(".log", ".txt", ".sql", ".pdf", ".jpg", ".png", ".csv", ".jpeg", ".gif"); 

$start_dir = DIR_FS_CATALOG; 
$it = new RecursiveDirectoryIterator($start_dir);
$files = array(); 
foreach(new RecursiveIteratorIterator($it) as $file)
{
    if (!is_dir($file)) { 
        $mtime = @filemtime($file); 
        $size = @filesize($file); 
        if ($mtime == FALSE) continue; 
        $type = @pathinfo($file, PATHINFO_EXTENSION); 
        if ($type == FALSE) continue; 
        $type = "." . $type; 
        if (in_array($type, $filetype_exclusions)) continue; 
        $files[] = array('name' => $file, 
                         'size' => $size, 
                         'mtime' => $mtime); 
    }
}
usort($files, "file_cmp");
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
  <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
</head>
<body>
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<div id="pageWrapper">
  <h1><?php echo HEADING_TITLE ?></h1>
<?php 
foreach ($files as $file) {
   $name = str_replace(DIR_FS_CATALOG, "", $file['name']); 
   $zero = false; 
   if ($file['size'] == 0) {
     $zero = true; 
     echo '<font color="red">'; 
   }
   echo $name . '&nbsp;&nbsp;' . date('Y-m-d H:i:s', $file['mtime']) . '&nbsp;&nbsp;' . $file['size'] . FILE_BYTES . "<br />";
   if ($zero) echo '</font>'; 
}
?>
</div>
<!-- body_eof //-->

<div class="bottom">
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</div>
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
<?php
function file_cmp($a, $b) {
   if ($a['mtime'] == $b['mtime'])
      return 0;
   if ($a['mtime'] < $b['mtime'])
      return 1;
   return -1;
}
