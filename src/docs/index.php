<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2012-11-27
 * Modified    : 2013-06-21
 * For LOVD    : 3.0-06
 *
 * Copyright   : 2004-2013 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/

define('ROOT_PATH', '../');
define('TAB_SELECTED', 'docs');
require ROOT_PATH . 'inc-init.php';





if (PATH_COUNT == 1 && !ACTION) {
    // URL: /docs
    // Provide link to PDF; later we will point to the HTML files.

    define('PAGE_TITLE', 'LOVD 3.0 documentation');
    $_T->printHeader();
    $_T->printTitle();

    print('      The LOVD 3.0 documentation is currently still work in progress.<BR>Currently available is the LOVD 3.0 user manual.<BR>' .
          '      <UL>' . "\n" .
          '        <LI><A href="docs/LOVD_manual_3.0.pdf" target="_blank">LOVD manual 3.0-06</A> (41 pages, 919Kb) - last updated June 21th 2013</LI></UL>' . "\n\n");

    $_T->printFooter();
    exit;
}
?>
