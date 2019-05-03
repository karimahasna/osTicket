<?php
/*********************************************************************
    schedules.inc.php

    Schedules

    Hasna Karimah <karimahasna98@gmail.com>
    Copyright (c)  2018 osTicket
    http://www.instagram.com/karimahasnaa

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    S??ee LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$qs = array();
$sortOptions = array(
        'username' => 'username',
        'date_start' => 'date_start',
        'date_end' => 'date_end',
        'created' => 'created',
        'updated' => 'updated');


$orderWays = array('DESC'=>'DESC', 'ASC'=>'ASC');
$sort = ($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])]) ?  strtolower($_REQUEST['sort']) : 'username';
if ($sort && $sortOptions[$sort]) {
        $order_column = $sortOptions[$sort];
}

$order_column = $order_column ? $order_column : 'username';

if ($_REQUEST['order'] && isset($orderWays[strtoupper($_REQUEST['order'])]))
{
        $order = $orderWays[strtoupper($_REQUEST['order'])];
} else {
        $order = 'ASC';
}

$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$page = ($_GET['p'] && is_numeric($_GET['p'])) ? $_GET['p'] : 1;
$count = Schedule::objects()->count();
$pageNav = new Pagenate($count, $page, PAGE_LIMIT);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('schedules.php', $qs);
$showing = $pageNav->showing().' '._N('schedule', 'schedules', $count);
$qstr = '&amp;order='.($order=='DESC' ? 'ASC' : 'DESC');

$def_dept_id = $cfg->getDefaultDeptId();
$def_dept_name = $cfg->getDefaultDept()->getName();

?>
<form action="schedules.php" method="POST" name="schedules">
    <div class="sticky bar opaque">
        <div class="content">
            <div class="pull-left flush-left">
                <h2><?php echo __('Schedules');?></h2>
            </div>
            <div class="pull-right flush-right">
                <a href="schedules.php?a=add" class="green button action-button"><i class="icon-plus-sign"></i> <?php echo __('Add New Schedule');?></a>
                <span class="action-button" data-dropdown="#action-dropdown-more">
                    <i class="icon-caret-down pull-right"></i>
                <span ><i class="icon-cog"></i> <?php echo __('More');?></span>
                </span>
                <div id="action-dropdown-more" class="action-dropdown anchor-right">
                    <ul id="actions">
                        <li class="danger">
                            <a class="confirm" data-name="delete" href="schedules.php?a=delete">
                                <i class="icon-trash icon-fixed-width"></i>
                                <?php echo __( 'Delete'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="clear"></div>
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
 <input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <thead>
        <tr>
            <th width="4%">&nbsp;</th>
            <th width="38%"><a <?php echo $schedule_sort; ?> href="schedules.php?<?php echo $qstr; ?>&sort=assignId"><?php echo __('Name');?></a></th>
            <th width="8%"><a  <?php echo $date_start_sort; ?> href="schedules.php?<?php echo $qstr; ?>&sort=date_start"><?php echo __('Date Start');?></a></th>
            <th width="15%"><a  <?php echo $dept_sort; ?> href="schedules.php?<?php echo $qstr; ?>&sort=date_end"><?php echo __('Date End');?></a></th>
            <th width="15%" nowrap><a  <?php echo $created_sort; ?>href="schedules.php?<?php echo $qstr; ?>&sort=created"><?php echo __('Created');?></a></th>
            <th width="20%" nowrap><a  <?php echo $updated_sort; ?>href="schedules.php?<?php echo $qstr; ?>&sort=updated"><?php echo __('Last Updated');?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $ids = ($errors && is_array($_POST['ids'])) ? $_POST['ids'] : null;
        if ($count):
            $defaultId=$cfg->getDefaultScheduleId();
            $schedules = Schedule::objects()
                ->order_by(sprintf('%s%s',
                            strcasecmp($order, 'DESC') ? '' : '-',
                            $order_column))
                ->limit($pageNav->getLimit())
                ->offset($pageNav->getStart());
               //echo "<pre>";
               //print_r($schedules);exit();

            foreach ($schedules as $schedule) {
                $id = $schedule->getId();
                $sel=false;
                if ($ids && in_array($id, $ids))
                    $sel=true;
                $default=($id==$defaultId);
                ?>
            <tr id="<?php echo $id; ?>">
                <td align="center">
                  <input type="checkbox" class="ckb" name="ids[]"
                    value="<?php echo $id; ?>"
                    <?php echo $sel ? 'checked="checked" ' : ''; ?>
                    <?php echo $default?'disabled="disabled" ':''; ?>>
                </td>
                <td><span class="ltr"><a href="schedules.php?id=<?php echo $id; ?>"><?php
                    echo Format::htmlchars((string) $schedule->username); ?></a></span>
                <?php echo ($default) ?' <small>'.__('(Default)').'</small>' : ''; ?>
                </td>
                <td>&nbsp;<?php echo Format::date($schedule->date_start); ?></td>
                <td>&nbsp;<?php echo Format::date($schedule->date_end); ?></td>
                <td>&nbsp;<?php echo Format::date($schedule->created); ?></td>
                <td>&nbsp;<?php echo Format::datetime($schedule->updated); ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="6">
            <?php if ($count){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No schedules found!');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if ($count):
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>

<?php
endif;
?>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected schedule', 'selected schedules', 2)) ;?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered.');?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel');?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!');?>" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
