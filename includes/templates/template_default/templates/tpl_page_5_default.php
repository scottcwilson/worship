<?php
/**
 * Page Template
 *
 * @package templateSystem
 * @copyright Copyright 2003-2005 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: tpl_page_4_default.php 3464 2006-04-19 00:07:26Z ajeh $
 */
?>
<div class="centerColumn" id="pageFour">
<h1 id="pageFourHeading"><?php echo HEADING_TITLE; ?></h1>

<div id="pageFourMainContent" class="content">
<?php
/**
 * require the html_define for the page_4 page
 */
  require($define_page);
?>
<iframe src="https://calendar.google.com/calendar/embed?height=600&wkst=1&ctz=America%2FNew_York&bgcolor=%23F4511E&showTz=0&showCalendars=0&showTabs=0&showPrint=0&src=bG9yYXJvemtvd3NraUBnbWFpbC5jb20&color=%23EF6C00" style="border:solid 1px #777" width="800" height="600" frameborder="0" scrolling="no"></iframe>

</div>

<div class="buttonRow back"><?php echo zen_back_link() . zen_image_button(BUTTON_IMAGE_BACK, BUTTON_BACK_ALT) . '</a>'; ?></div>
</div>
