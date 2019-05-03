<?php
/*********************************************************************
    scheduleTable.php

    Table Schedule - basic stats...etc.

    Hasna Karimah <karimahasna98@gmail.com>
    Copyright (c)  2019 osTicket
    http://www.instagram.com/karimahasnaa

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require('staff.inc.php');

require_once INCLUDE_DIR . 'class.report.php';

if ($_POST['export']) {
    $report = new OverviewReport($_POST['start'], $_POST['period']);
    switch (true) {
    case ($data = $report->getTabularData($_POST['export'])):
        $ts = strftime('%Y%m%d');
        $group = Format::slugify($_POST['export']);
        $delimiter = ',';
        if (class_exists('NumberFormatter')) {
            $nf = NumberFormatter::create(Internationalization::getCurrentLocale(),
                NumberFormatter::DECIMAL);
            $s = $nf->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
            if ($s == ',')
                $delimiter = ';';
        }

        Http::download("stats-$group-$ts.csv", 'text/csv');
        $output = fopen('php://output', 'w');
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $data['columns'], $delimiter);
        foreach ($data['data'] as $row)
            fputcsv($output, $row, $delimiter);
        exit;
    }
}

$nav->setTabActive('dashboard');
$ost->addExtraHeader('<meta name="tip-namespace" content="dashboard.dashboard" />',
    "$('#content').data('tipNamespace', 'dashboard.dashboard');");

require(STAFFINC_DIR.'header.inc.php');
require_once(STAFFINC_DIR.'scheduleTable.inc.php');
include(STAFFINC_DIR.'footer.inc.php');
?>
