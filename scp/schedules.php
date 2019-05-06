<?php
/*********************************************************************
    schedules.php

    Schedules

    Hasna Karimah <karimahasna98@gmail.com>
    Copyright (c)  2019 osTicket
    http://www.instagram.com/karimahasnaa

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    S??ee LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

require('admin.inc.php');
include_once(INCLUDE_DIR.'class.schedule.php');

$schedule_id=null;
if($_REQUEST['id'] && !($schedule=Schedule::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('schedule_id'));

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$schedule_id){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('schedule'));
            }
            elseif($schedule_id->update($_POST,$errors)){
                $msg=sprintf(__('Successfully updated %s.'),
                    __('this schedule'));
            }
            elseif(!$errors['err']){
                $errors['err'] = sprintf('%s %s',
                    sprintf(__('Unable to update %s.'), __('this schedule')),
                    __('Correct any errors below and try again.'));
            }
            break;
        case 'create':


            $box = Schedule::create();

            if ($box->update($_POST, $errors)) {
                $id = $box->getId();
                $msg=sprintf(__('Successfully added %s.'), Format::htmlchars($_POST['name']));
                $_REQUEST['a']=null;
            }elseif(!$errors['err']){
                $errors['err']=sprintf('%s %s',
                    sprintf(__('Unable to add %s.'), __('this schedule')),
                    __('Correct any errors below and try again.'));
            }

            // echo "<pre>";
            // var_dump($errors);
            // echo "</pre>";
            // die();

            break;
        case 'mass_process':
            if(!$_POST['ids'] || !is_array($_POST['ids']) || !count($_POST['ids'])) {
                $errors['err'] = sprintf(__('You must select at least %s.'),
                    __('one agent'));
            } else {
                $count=count($_POST['ids']);

                switch (strtolower($_POST['a'])) {
                case 'delete':
                    $i=0;
                    foreach($_POST['ids'] as $k=>$v) {
                        if($v!=$cfg->getDefaultScheduleId() && ($e=Schedule::lookup($v)) && $e->delete())
                            $i++;
                    }

                    if($i && $i==$count)
                        $msg = sprintf(__('Successfully deleted %s.'),
                            _N('selected schedule', 'selected schedules', $count));
                    elseif($i>0)
                        $warn = sprintf(__('%1$d of %2$d %3$s deleted'), $i, $count,
                            _N('selected schedule', 'selected schedules', $count));
                    elseif(!$errors['err'])
                        $errors['err'] = sprintf(__('Unable to delete %s.'),
                            _N('selected schedule', 'selected schedules', $count));
                    break;

                default:
                    $errors['err'] = sprintf('%s - %s', __('Unknown action'), __('Get technical help!'));
print_r($schedules);
                }
            }
            break;
        default:
            $errors['err'] = __('Unknown action');
            break;
    }
}

$page='schedules.inc.php';
$tip_namespace = 'schedules.schedule';
if($schedule || ($_REQUEST['a'] && !strcasecmp($_REQUEST['a'],'add'))) {
    $page='schedule.inc.php';
}

$nav->setTabActive('schedule'); //tabs active sesuai dengan tabs[NAME] function getTabs() on class.nav.php
$ost->addExtraHeader('<meta name="tip-namespace" content="' . $tip_namespace . '" />',
    "$('#content').data('tipNamespace', '".$tip_namespace."');");
require(STAFFINC_DIR.'header.inc.php');
require(STAFFINC_DIR.$page);
include(STAFFINC_DIR.'footer.inc.php');
?>
