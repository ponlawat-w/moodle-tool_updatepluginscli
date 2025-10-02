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
 * Download updates
 *
 * @package     tool_updatepluginscli
 * @subpackage  cli
 * @copyright   2025 Ponlawat WEERAPANPISIT <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once(__DIR__ . '/../lib.php');

// Get the cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'custom' => null,
        'strict-all' => false,
    ],
    [
        'h' => 'help',
        'c' => 'custom',
    ]
);

$help = <<<HELP
Script to download available updates.

Arguments:
- "custom" / "c" (optional)
    Download available updates to only defined plugin. The value must be in format of "name" or "name:version".
    If ":version" not specified, it will update the latest version.
    For example: --custom=mod_forum:2025041400 OR --custom=mod_forum
- "strict-all" (optional, default: false)
    Set to "true" to make the script prematurely terminate if some plugins cannot be downloaded.
HELP;

if ($unrecognized) {
    $unrecognized = implode("\n\t", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    cli_writeln($help);
    die();
}

$pluginmanager = \core\plugin_manager::instance();

/** @var \core\update\info[] $updates */
$updates = [];

if ($options['custom']) {
    $components = explode(':', $options['custom']);
    [$component] = $components;
    $version = isset($components[1]) ? $components[1] : null;
    $plugininfo = $pluginmanager->get_plugin_info($component);
    if (!$plugininfo) {
        cli_error('Plugin not found');
        die();
    }
    if ($version) {
        $availableupdates = $plugininfo->available_updates();
        if ($availableupdates) {
            foreach ($availableupdates as $update) {
                if ($update->version == $version) {
                    $updates[] = $update;
                    break;
                }
            }
        }
    } else {
        $update = tool_updatepluginscli_getlatestupdate($plugininfo);
        if ($update) {
            $updates[] = $update;
        }
    }
} else {
    foreach ($pluginmanager->get_plugins(true) as $plugins) {
        foreach ($plugins as $plugin) {
            $update = tool_updatepluginscli_getlatestupdate($plugin);
            if ($update) {
                $updates[] = $update;
            }
        }
    }
}

/** @var \core\update\remote_info[] $installables */
$installables = [];
foreach ($updates as $update) {
    cli_write("Preparing {$update->component} ({$update->version})...");
    $installable = tool_updatepluginscli_getremoteupdate($pluginmanager, $update);
    if ($installable) {
        $installables[] = $installable;
        cli_writeln('READY');
    } else {
        cli_writeln('SKIPPED (cannot update remotely)');
        if ($options['strict-all']) {
            cli_error("Script ends because {$update->component} cannot be updated remotely.");
            die();
        }
    }
}

if (!count($installables)) {
    cli_writeln('Script terminates as there are no plugins to install.');
    die();
}

cli_write('Installing...');
$success = $pluginmanager->install_plugins($installables, true, true);
if ($success) {
    cli_writeln('OK');
} else {
    cli_error('Plugin manager returns an unsuccessful result when tryin to install the plugins.');
    die();
}

cli_writeln(
    'Plugins have been installed.'
    . ' Please run "admin/cli/upgrade.php" script or go to site administration in browser'
    . ' to upgrade the database.'
);
