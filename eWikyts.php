<?php
# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
\$wgWikytsTitle = "YourTitle";
require_once( "\$IP/extensions/eWikyts/eWikyts.php" );
EOT;
        exit( 1 );
}
 
$dir = dirname(__FILE__) . '/';
 
$wgAutoloadClasses['eWikyts'] = $dir . 'eWikyts_body.php'; # Tell MediaWiki to load the extension body.
$wgExtensionMessagesFiles['eWikyts'] = $dir . 'eWikyts.i18n.php';
$wgSpecialPages['eWikyts'] = 'eWikyts'; # Let MediaWiki know about your new special page.
$wgHooks['LanguageGetSpecialPageAliases'][] = 'myExtensionLocalizedPageName'; # Add any aliases for the special page.
 
function myExtensionLocalizedPageName(&$specialPageArray, $code) {
  if (function_exists('wfLoadExtensionMessages'))
    wfLoadExtensionMessages('eWikyts');
  $text = wfMsg('ewikyts');
 
  # Convert from title in text form to DBKey and put it into the alias array:
  $title = Title::newFromText($text);
  $specialPageArray['eWikyts'][] = $title->getDBKey();
 
  return true;
}

