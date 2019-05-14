<?php
/*********************************************************************
    class.schedule.php

    Hasna Karimah <karimahasna98@gmail.com>
    Copyright (c)  2018 osTicket
    http://www.instagram.com/karimahasnaa

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

include_once INCLUDE_DIR.'class.role.php';
include_once(INCLUDE_DIR.'class.dept.php');
include_once(INCLUDE_DIR.'class.mailfetch.php');

class Schedule extends VerySimpleModel {
    static $meta = array(
        'table' => SCHEDULE_TABLE,
        'pk' => array('schedule_id')
    );

    const PERM_BANLIST = 'emails.banlist';

    static protected $perms = array(
            );

    function getId() {
        return $this->schedule_id;
    }

    function __toString() {
        if ($this->name)
            return sprintf('%s <%s>', $this->name, $this->schedule);

        return $this->schedule;
    }


    function __onload() {
        
    }

    function getSchedule() {
        return $this->schedule;
    }

    function getName() {
        return $this->username;
    }

    //TAMBAHAN HASNA
    function getDateStart() {
        return $this->date_start;
    }    

    function getDateEnd() {
        return $this->date_end;
    }

    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        $base = $this->getHashtable(); 
        return $base;
    }

   function delete() {
        global $cfg;

        if (!$cfg  || $this->getId()==$cfg->getDefaultScheduleId())
            return false;

        if (!parent::delete())
            return false;

        Schedule::objects()
            ->filter(array('schedule_id' => $this->getId()))
            ->update(array(
                'schedule_id' => $cfg->getDefaultScheduleId()
            ));

        return true;
    }


    /******* Static functions ************/

   static function getIdBySchedule($schedule) {
        $qs = static::objects()->filter(Q::any(array(
                        'schedule'  => $schedule,
                        'userid' => $schedule
                        )))
            ->values_flat('schedule_id');

        $row = $qs->first();
        return $row ? $row[0] : false;
    }

    static function create($vars=false) {
        $inst = new static($vars);
        $inst->created = SqlFunction::NOW();
        return $inst;
    }

    function save($refetch=false) {
        if ($this->dirty)
            $this->updated = SqlFunction::NOW();
        return parent::save($refetch || $this->dirty);
    }

    function update($vars, &$errors=false) {
        global $cfg;

        $id = isset($this->schedule_id) ? $this->getId() : 0;
        if($id && $id!=$vars['id'])
            $errors['err']=__('Get technical help!')
                .' '.__('Internal error occurred');

                // echo "<pre>";
                // var_dump(Validator::is_schedule($vars['schedule']));
                // echo "</pre>";
                // die();


        // if(($eid=Schedule::getIdBySchedule($vars['schedule'])) && $eid!=$id) {
        //     $errors['schedule']=__('schedule already exists');
        // }elseif($cfg && !strcasecmp($cfg->getAdminSchedule(), $vars['schedule'])) {
        //     $errors['schedule']=__('schedule already used as admin schedule!');
        // }elseif(Staff::getIdBySchedule($vars['schedule'])) { //make sure the email doesn't belong to any of the staff
        //     $errors['schedule']=__('schedule in use by an agent');
        // }

        if(!$vars['username'])
            $errors['username']=__('Agent required');

        if(!$vars['date_start'])
            $errors['date_start']=__('Start Date required');

        if(!$vars['date_end'])
            $errors['date_end']=__('End Date required');


        $this->username = $vars['username'];
        $this->date_start = $vars['date_start'];
        $this->date_end = $vars['date_end'];

        //abort on errors
        if ($errors)
            return false;



        if ($this->save())
            return true;

        if ($id) { //update
            $errors['err']=sprintf(__('Unable to update %s.'), __('this schedule'))
               .' '.__('Internal error occurred');
        }
        else {
            $errors['err']=sprintf(__('Unable to add %s.'), __('this schedule'))
               .' '.__('Internal error occurred');
        }

        return false;
    }

    static function getPermissions() {
        return self::$perms;
    }
}
RolePermission::register(/* @trans */ 'Miscellaneous', Schedule::getPermissions());
?>
