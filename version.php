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
 * Version information for the essayautograde question type.
 *
 * @package    qtype
 * @subpackage essayautograde
 * @copyright  2018 Gordon Bateson (gordon.bateson@gmail.com)
 * @copyright  based on work by 2005 Mark Nielsen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->cron      = 0;
$plugin->component = 'qtype_essayautograde';
$plugin->maturity  = MATURITY_STABLE;
$plugin->requires  = 2015111600; // Moodle 3.0
$plugin->version   = 2019060584;
$plugin->release   = '2019-06-05 (84)';

// https://docs.moodle.org/dev/Releases
// Moodle 3.7 2019052000 20 May 2019
// Moodle 3.6 2018120300  3 Dec 2018
// Moodle 3.5 2018051700 17 May 2018
// Moodle 3.4 2017111300 13 Nov 2017
// Moodle 3.3 2017051500 15 May 2017
// Moodle 3.2 2016120500  5 Dec 2016
// Moodle 3.1 2016052300 23 May 2016
// Moodle 3.0 2015111600 16 Nov 2015
// Moodle 2.9 2015051100 11 May 2015
// Moodle 2.8 2014111000 10 Nov 2014
// Moodle 2.7 2014051200 12 May 2014
// Moodle 2.6 2013111800 18 Nov 2013
// Moodle 2.5 2013051400 14 May 2013
// Moodle 2.4 2012120300  3 Dec 2012
// Moodle 2.3 2012062500 25 Jun 2012
// Moodle 2.2 2011120500  5 Dec 2011
// Moodle 2.1 2011070100  1 Jul 2011
// Moodle 2.0 2010112400 24 Nov 2010
