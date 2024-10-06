<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


 /**
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_mrproject
 * @copyright   2024 Youcef Haddou <youcef.haddou@univ-tiaret.dz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * This is the development branch (master) of the mrproject module.
 */

$plugin->component = 'mod_mrproject'; // Full name of the plugin (used for diagnostics).
$plugin->version   = 2024031400;      // The current module version (Date: YYYYMMDDXX).
$plugin->release   = '0.1.0';         // Human-friendly version name.
$plugin->requires  = 2020110900;      // requires Moodle 3.10.
$plugin->maturity  = MATURITY_STABLE; // Stable branch MOODLE_37_STABLE
