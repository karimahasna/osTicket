<?php
/*********************************************************************
    schedule.inc.php

    Schedule

    Hasna Karimah <karimahasna98@gmail.com>
    Copyright (c)  2018 osTicket
    http://www.instagram.com/karimahasnaa

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    S??ee LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

ini_set('display_errors',0);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);


if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');
$info = $qs = array();
if($schedule && $_REQUEST['a']!='add'){
    $title=__('Update Schedule');
    $action='update';
    $submit_text=__('Save Changes');
    $info=$schedule->getInfo();
    $info['id']=$schedule->getId();
    if($info['schedule_delete'])
        $info['postfetch']='delete';
    elseif($info['schedule_archivefolder'])
        $info['postfetch']='archive';
    else
        $info['postfetch']=''; //nothing.
    if($info['userpass'])
        $passwdtxt=__('To change password enter new password above.');
    $qs += array('id' => $schedule->getId());

}else {
    $title=__('Add New Schedule');
    $action='create';
    $submit_text=__('Submit');
    $info['ispublic']=isset($info['ispublic'])?$info['ispublic']:1;
/*    $info['ticket_auto_response']=isset($info['ticket_auto_response'])?$info['ticket_auto_response']:1;
    $info['message_auto_response']=isset($info['message_auto_response'])?$info['message_auto_response']:1;
    if (!$info['mail_fetchfreq'])
        $info['mail_fetchfreq'] = 5;
    if (!$info['mail_fetchmax'])
        $info['mail_fetchmax'] = 10;
    if (!isset($info['smtp_auth']))
        $info['smtp_auth'] = 1;
 */   $qs += array('a' => $_REQUEST['a']);

}
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
<h2><?php echo $title; ?>
    <?php if (isset($info['schedule'])) { ?><small>
    — <?php echo $info['schedule']; ?></small>
    <?php } ?>
</h2>
<form action="schedules.php?<?php echo Http::build_query($qs); ?>" method="post" class="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="<?php echo $action; ?>">
 <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
 <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
 <table class="form_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Assign');?></strong></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="180" class="required">
                <?php echo __('Assign to');?>
            </td>
            <td>
                <select id="schedule_id" name="schedule_id">
                    <option value="0" selected="selected">&mdash; <?php echo __('Select an Agent OR a Team');?> &mdash;</option>
                    <?php
                    if(($users=Staff::getAvailableStaffMembers())) {
                        echo '<OPTGROUP label="'.sprintf(__('Agents (%d)'), count($users)).'">';
                        foreach($users as $id => $name) {
                            $k="s$id";
                            echo sprintf('<option value="%s" %s>%s</option>',
                                        $k,(($info['schedule_id']==$k)?'selected="selected"':''),$name);
                        }
                        echo '</OPTGROUP>';
                    }

                    if(($teams=Team::getActiveTeams())) {
                        echo '<OPTGROUP label="'.sprintf(__('Teams (%d)'), count($teams)).'">';
                        foreach($teams as $id => $name) {
                            $k="t$id";
                            echo sprintf('<option value="%s" %s>%s</option>',
                                        $k,(($info['schedule_id']==$k)?'selected="selected"':''),$name);
                        }
                        echo '</OPTGROUP>';
                    }
                    ?>
                </select>&nbsp;<span class='error'>&nbsp;<?php echo $errors['schedule_id']; ?></span>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('New Schedule'); ?></strong></em>
            </th>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('Start Date');?>
            </td>
        <span>
			<?php
                    $todo=$_POST['todo'];
                    if(isset($todo) and $todo=="submit"){
                        //$date_value_start="$date_start";
                        //$date_value_end="$date_end";
                    }
                ?>
            <td>
                <input class="dp" id="date_start" name="date_start" value="<?php echo Format::htmlchars($info['duedate']); ?>" size="12" autocomplete=OFF>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['duedate']; ?></font>
            <i class="help-tip icon-question-sign" href="#new_ticket_department"></i>

            </td>
        </tr>
        <tr>
            <td width="180">
                <?php echo __('End Date'); ?>
            </td>
		<span>
			<td>
                <input class="dp" id="date_end" name="date_end" value="<?php echo Format::htmlchars($info['duedate']); ?>" size="12" autocomplete=OFF>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['duedate']; ?></font>
            <i class="help-tip icon-question-sign" href="#new_ticket_priority"></i>
            </td>
			
		</span>
		&nbsp;<span class="error"><?php echo $errors['priority_id']; ?></span>
            </td>
        </tr>
        
        
    </tbody>
</table>
<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick='window.location.href="schedules.php"'>
</p>
</form>