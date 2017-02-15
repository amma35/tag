<?php
/*
 -------------------------------------------------------------------------
 Tag plugin for GLPI
 Copyright (C) 2003-2017 by the Tag Development Team.

 https://github.com/pluginsGLPI/tag
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Tag.

 Tag is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Tag is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Tag. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

define ('PLUGIN_TAG_VERSION', '0.90-1.3');

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_tag_check_config($verbose=false) {
   return true;
}

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_tag() {
   global $PLUGIN_HOOKS, $UNINSTALL_TYPES, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['tag'] = true;

   $plugin = new Plugin();
   if ($plugin->isInstalled("tag") && $plugin->isActivated("tag")) {

      // add link on plugin name in Configuration > Plugin
      $PLUGIN_HOOKS['config_page']['tag'] = "front/tag.php";

      // require spectrum (for glpi >= 9.2)
      $CFG_GLPI['javascript']['config']['commondropdown']['PluginTagTag'] = ['colorpicker'];

      // Plugin use specific massive actions
      $PLUGIN_HOOKS['use_massive_action']['tag'] = true;

      // Plugin uninstall : after uninstall action
      if ($plugin->isInstalled("uninstall") && $plugin->isActivated("uninstall")) {
         foreach ($UNINSTALL_TYPES as $u_itemtype) {
            $PLUGIN_HOOKS['plugin_uninstall_after']['tag'][$u_itemtype] = 'plugin_uninstall_after_tag';
         }
      }

      // insert tag dropdown into all possible itemtypes
      $PLUGIN_HOOKS['pre_item_form']['tag'] = ['PluginTagTag', 'preItemForm'];

      // plugin datainjection
      $PLUGIN_HOOKS['plugin_datainjection_populate']['tag'] = "plugin_datainjection_populate_tag";

      // add needed javascript files
      $PLUGIN_HOOKS['add_javascript']['tag'][] = 'js/common.js';
   }

   // only on itemtype form
   if (preg_match_all("/.*\/(.*)\.form\.php/", $_SERVER['REQUEST_URI'], $matches) !== false) {
      if (isset($matches[1][0])) {
         $itemtype = $matches[1][0];

         if (preg_match_all("/plugins\/(.*)\//U", $_SERVER['REQUEST_URI'], $matches_plugin) !== false) {
            if (isset($matches_plugin[1][0])) {
               $itemtype = "Plugin" . ucfirst($matches_plugin[1][0]) . ucfirst($itemtype);
            }
         }

         if (class_exists($itemtype)
             && PluginTagTag::canItemtype($itemtype)) {
            //normalize classname case
            $object   = new $itemtype();
            $itemtype = get_class($object);

            // Tag have no tag associated
            if ($itemtype != 'PluginTagTag') {
               $PLUGIN_HOOKS['pre_item_update']['tag'][$itemtype] = 'plugin_pre_item_update_tag';
               $PLUGIN_HOOKS['pre_item_purge']['tag'][$itemtype]  = 'plugin_pre_item_purge_tag';
            }
         }
      }
   }
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_tag() {
   return array('name'       => __('Tag Management', 'tag'),
            'version'        => PLUGIN_TAG_VERSION,
            'author'         => '<a href="http://www.teclib.com">Teclib\'</a> - Infotel conseil',
            'homepage'       => 'https://github.com/pluginsGLPI/tag',
            'license'        => '<a href="../plugins/tag/LICENSE" target="_blank">GPLv2+</a>',
            'minGlpiVersion' => "0.90");
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_tag_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '0.90', 'lt')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '0.90');
      } else {
         echo __('This plugin requires GLPI >= 0.90');
      }
   } else {
      return true;
   }
   return false;
}
