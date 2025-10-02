<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin library.
 *
 * @package     tool_updatepluginscli
 * @copyright   2025 Ponlawat WEERAPANPISIT <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Get update info with the highest version value, or null if no updates available.
 * @param \core\plugininfo\base $plugin
 * @return \core\update\info|null
 */
function tool_updatepluginscli_getlatestupdate($plugin) {
    $availableupdates = $plugin->available_updates();
    if (!$availableupdates) {
        return null;
    }
    /** @var \core\update\info|null $maxupdate */
    $maxupdate = null;
    foreach ($availableupdates as $update) {
        if (!is_null($maxupdate) && $maxupdate->version > $update->version) {
            continue;
        }
        $maxupdate = $update;
    }
    return $maxupdate;
}

/**
 * Get remote update info from an update info, or false if remote plugin is not installable.
 * @param \core\plugin_manager $pluginmanager
 * @param \core\update\info $updateinfo
 * @return \core\update\remote_info|bool
 */
function tool_updatepluginscli_getremoteupdate($pluginmanager, $updateinfo) {
    if (!$pluginmanager->is_remote_plugin_installable($updateinfo->component, $updateinfo->version)) {
        return false;
    }
    $remoteinfo = $pluginmanager->get_remote_plugin_info($updateinfo->component, $updateinfo->version, true);
    return $remoteinfo ? $remoteinfo : false;
}
