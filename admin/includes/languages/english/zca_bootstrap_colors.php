<?php
/**
 *  zca_bootstrap_colors.php
 *
 * @copyright Copyright 2003-2018 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version Author: rbarbour (ZCAdditions.com)  7/01/2018 06:00 AM Modified ZCA-BS-COLORS
 *
 * BOOTSTRAP v3.6.2
 */
define('HEADING_TITLE','ZCA Bootstrap Colors');

define('TEXT_INFORMATION', 'Any colors added for the template\'s v3.5.2 and later are initialized on an upgrade to &quot;not-set&quot;, enabling a store to set these values to match its color scheme prior to use. <b>Only</b> colors that indicate that &quot;not-set&quot; is an acceptable value should use that value; otherwise, the storefront\'s script that creates the CSS coloring will produce invalid CSS!<br><br><b>Note:</b> The <em>Value</em> column will be empty if the currently-configured value is the default.');

define('TEXT_INFO_EDIT_INTRO', 'Please make any necessary changes');
define('TEXT_INFO_DATE_ADDED', 'Date Added:');
define('TEXT_INFO_LAST_MODIFIED', 'Last Modified:');

define('TABLE_HEADING_CONFIGURATION_TITLE', 'Title');
define('TABLE_HEADING_CONFIGURATION_DEFAULT', 'Default');
define('TABLE_HEADING_CONFIGURATION_VALUE', 'Value');
define('TABLE_HEADING_NOT_SET_OK', '&quot;not-set&quot; OK?');
define('TABLE_HEADING_ACTION', 'Action');

// BOF SQL file
define('BUTTON_DOWNLOAD_SQL', 'Download SQL');
// EOF SQL file

// BOF CSV file
define('BUTTON_DOWNLOAD_CSV', 'Download CSV');
define('BUTTON_UPLOAD_CSV', 'Upload CSV');
define('TEXT_QUERY_FILENAME','Upload file:');

define('CSV_HEADER_KEY', 'Key');
define('CSV_HEADER_VALUE', 'Value');
define('CSV_HEADER_TITLE', 'Title');
define('CSV_HEADER_DEFAULT', 'Default');

define('UPLOAD_FILE_PROCESSED_ALL_OK', "File Processed; all %s updates successful.");
define('UPLOAD_FILE_PROCESSED_SOME_OK', "File Processed; %s out of %s updates successful.");

define('UPLOAD_SUCCESS', 'Success: ');
define('UPLOAD_WARNING', 'Warning: ');
define('UPLOAD_FAILED', 'Failed: ');
define('MISSING_CONFIGURATION', 'ZCA Bootstrap Colors configuration not found.');
define('NO_CSV_FILE', 'CSV File not specified or empty.');
define('CSV_FILE_MALFORMED', 'CSV file malformed.');
// EOF CSV file
