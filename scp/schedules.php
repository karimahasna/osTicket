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
include_once(INCLUDE_DIR.'class.email.php');
include_once(INCLUDE_DIR.'class.staff.php');

$schedule=null;
if($_REQUEST['id'] && !($schedule=Schedule::lookup($_REQUEST['id'])))
    $errors['err']=sprintf(__('%s: Unknown or invalid ID.'), __('schedule'));

if($_POST){
    switch(strtolower($_POST['do'])){
        case 'update':
            if(!$schedule){
                $errors['err']=sprintf(__('%s: Unknown or invalid'), __('schedule'));
            }
            elseif($schedule->update($_POST,$errors)){
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

            //email_id (from), email (to), subj, message

            $_POST['email_id'] = '1'; //email ID dari tab Emails (milih atau di set manual?)
            $_POST['email'] = Staff::getEmailByUsername($_POST['username']);
            $_POST['subj'] = 'osTicket test email'; //subject email
            $_POST['message'] = 'Nyobain'; //isi email

            // echo "<pre>";
            // var_dump($_POST);
            // echo "</pre>";
            // die();

            $email=null;
            $email=Email::lookup($_POST['email_id']);

            if($email->send($_POST['email'],$_POST['subj'], 
            Format::sanitize($_POST['message']), 
            null, array('reply-tag'=>false))) {
                Draft::deleteForNamespace('email.diag');
            }

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
