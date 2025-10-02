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
 * Fetch updates
 *
 * @package     tool_updatepluginscli
 * @subpackage  cli
 * @copyright   2025 Ponlawat WEERAPANPISIT <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

// Get the cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'output' => 'text',
        'fetch' => true
    ],
    [
        'h' => 'help',
        'o' => 'output'
    ],
);

$help =
<<<HELP
Script to invoke plugin updates checker.

Arguments:
- "output" / "o" (optional, default: "text")
    indicates format to return list of outdated plugins, value can be either "text", "json" or "none".
- "fetch" (default: true)
    set to false to skip fetching and return only the list outdated plugins from the last fetch.
HELP;

if ($unrecognized) {
    $unrecognized = implode("\n\t", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    cli_writeln($help);
    die();
}

$output = $options['output'];
if ($output != 'text' && $output != 'json' && $output != 'none') {
    cli_error('Invalid output format');
    die();
}

$fetch = $options['fetch'] && strtolower($options['fetch']) != 'false';
if ($fetch) {
    $output != 'json' && cli_write('Fetching updates...');
    \core\update\checker::instance()->fetch();
    $output != 'json' && cli_writeln('DONE');
} else {
    $output != 'json' && cli_writeln('Fetching skipped.');
}

if ($output == 'none') {
    cli_writeln('Finish');
    die();
}

/** @var \core\update\info[] $updatableplugins with key being plugin name */
$updatableplugins = [];

$plugins;
$plugininfo = \core\plugin_manager::instance()->get_plugins(true);
foreach ($plugininfo as $type => $plugins) {
    foreach ($plugins as $plugin) {
        $availableupdates = $plugin->available_updates();
        if (!$availableupdates) {
            continue;
        }
        foreach ($availableupdates as $update) {
            $pluginname = $type . '_' . $plugin->name;
            if (isset($updatableplugins[$pluginname]) && $updatableplugins[$pluginname]->version > $update->version) {
                continue;
            }
            $updatableplugins[$pluginname] = $update;
        }
    }
}

if ($output == 'json') {
    $results = [];
    foreach ($updatableplugins as $name => $updateinfo) {
        $results[] = array_merge(['name' => $name], (array)$updateinfo);
    }
    cli_writeln(json_encode($results));
} else if ($output == 'text') {
    cli_writeln("Plugin\tName\tNew Version\tNew Release\tMaturity");
    foreach ($updatableplugins as $name => $updateinfo) {
        $pluginname = get_string('pluginname', $name);
        $version = isset($updateinfo->version) ? $updateinfo->version : '-';
        $release = isset($updateinfo->release) ? $updateinfo->release : '-';
        $maturity = isset($updateinfo->maturity) ? get_string('maturity' . $updateinfo->maturity, 'core_admin') : '0';
        cli_writeln("{$name}\t{$pluginname}\t{$version}\t{$release}\t{$maturity}");
    }
}
