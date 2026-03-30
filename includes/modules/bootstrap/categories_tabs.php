<?php
/**
 * categories_tabs.php module
 *
 * @package   templateSystem
 * @copyright Copyright 2003-2018 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license   http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version   $Id: Scott C Wilson Sat Aug 25 07:25:23 2018 -0400 Modified in v1.5.6 $
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$order_by = " ORDER BY c.sort_order, cd.categories_name ";

$includeAllCategories = $zca_include_zero_product_categories ?? true;

$categories_tab_query =
    "SELECT c.sort_order, c.categories_id, cd.categories_name
       FROM " . TABLE_CATEGORIES . " c, " . TABLE_CATEGORIES_DESCRIPTION . " cd
      WHERE c.categories_id = cd.categories_id
        AND c.parent_id = 0
        AND cd.language_id = " . (int)$_SESSION['languages_id'] . "
        AND c.categories_status = 1" .
        $order_by;
$categories_tab = $db->Execute($categories_tab_query);

$links_list = [];
$current_category_tab = (int)$cPath;
foreach ($categories_tab as $category) {
    // currently selected category
    if ($current_category_tab === (int)$category['categories_id']) {
        $new_style = 'nav-item nav-link m-1 activeLink';
        $categories_tab_current = $category['categories_name'];
    } else {
        if (!$includeAllCategories) {
            $count = zen_products_in_category_count($category['categories_id']);
            if ($count === 0) {
                continue;
            }
        }
        $new_style = 'nav-item nav-link m-1';
        $categories_tab_current = $category['categories_name'];
    }
    // create link to top level category
    $links_list[] =
        '<a class="' . $new_style . '" href="' . zen_href_link(FILENAME_DEFAULT, 'cPath=' . (int)$category['categories_id']) . '">' .
            $categories_tab_current .
        '</a> ';
}
