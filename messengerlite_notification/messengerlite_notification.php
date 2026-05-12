<?php
/**
Name: Messengerlite Notification
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Notification module for the messengerlite
Version: 0.1
Type: Module
Requires: Messengerlite
 */
class messengerlite_notification extends messengerlite{
  private $dbFormSettings;
  private $allowEdit = false;

  function __construct($arrModuleSettings) {
    $id = $arrModuleSettings['messengerlite_target'] ?? 0;
    if ($id == 0) return;
    // Hårdkodad rad ?
    if ($arrModuleSettings['own_access_pages'][$id]['allow'] ? false : true) return;
    $url = route::getURLbyID($id);
    if (empty($url)) return;
    addCSSM(__CLASS__);
    $t = (int) messengerlite::checkNotification();
    if ($t>0) {
      $cls = 'has-new-messages';
      $circle = "\t<span class=\"bar-notification-circle\">" . $t . "</span>\n";
    } else {
      $cls = "";
      $circle = "";
    }



      $_url = $GLOBALS['CONFIG']['www-root'] . '/system/modules/messengerlite_notification/html/';
      print "<a href='{$url}' class=\"bar-notification-icon {$cls}\">\n\t<img src='" . $_url . "email.svg' style='width:32px;height:32px;' class='module_icon' title='" . $GLOBALS['LANG']['messengerlite']['nav']['inbox'] . "'/>\n"; 
      print $circle;
      print "</a>\n";

  }
}
