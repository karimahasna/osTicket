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
        'pk' => array('schedule_id')/*,
        'joins' => array(
            'priority' => array(
                'constraint' => array('priority_id' => 'Priority.priority_id'),
                'null' => true,
            ),
            'dept' => array(
                'constraint' => array('dept_id' => 'Dept.id'),
                'null' => true,
            ),
            'topic' => array(
                'constraint' => array('topic_id' => 'Topic.topic_id'),
                'null' => true,
            ),
        )*/
    );

    const PERM_BANLIST = 'emails.banlist';

    static protected $perms = array(
            self::PERM_BANLIST => array(
                'title' =>
                /* @trans */ 'Banlist',
                'desc'  =>
                /* @trans */ 'Ability to add/remove schedules from banlist via ticket interface',
                'primary' => true,
            ));


    //var $address;
    //var $mail_proto;
    //var $date_start;
    //var $date_end;

    function getId() {
        return $this->schedule_id;
    }

    function __toString() {
        if ($this->name)
            return sprintf('%s <%s>', $this->name, $this->schedule);

        return $this->schedule;
    }


    function __onload() {
        /*$this->mail_proto = $this->get('mail_protocol');
        if ($this->mail_encryption == 'SSL')
            $this->mail_proto .= "/".$this->mail_encryption;

        $this->address=$this->name?($this->name.'<'.$this->schedule.'>'):$this->schedule;*/
    }

    function getSchedule() {
        return $this->schedule;
    }

    /*function getAddress() {
        return $this->address;
    }*/

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
/*
    function getDept() {
        return $this->dept;
    }

    function getTopicId() {
        return $this->topic_id;
    }

    function getTopic() {
        return $this->topic;
    }

    function autoRespond() {
        return !$this->noautoresp;
    }

    function getPasswd() {
        if (!$this->userpass)
            return '';
        return Crypto::decrypt($this->userpass, SECRET_SALT, $this->userid);
    }
*/
    function getHashtable() {
        return $this->ht;
    }

    function getInfo() {
        $base = $this->getHashtable(); 
        //$base['mail_proto'] = $this->mail_proto;
        return $base;
    }

    function getMailAccountInfo() {

        /*NOTE: Do not change any of the tags - otherwise mail fetching will fail */
        $info = array(
                //Mail server info
                'host'  => $this->mail_host,
                'port'  => $this->mail_port,
                'protocol'  => $this->mail_protocol,
                'encryption' => $this->mail_encryption,
                'username'  => $this->userid,
                'password' => Crypto::decrypt($this->userpass, SECRET_SALT, $this->userid),
                //osTicket specific
                'schedule_id'  => $this->getId(), //Required for email routing to work.
                'max_fetch' => $this->mail_fetchmax,
                'delete_mail' => $this->mail_delete,
                'archive_folder' => $this->mail_archivefolder
        );

        return $info;
    }

    function isSMTPEnabled() {

        return (
                $this->smtp_active
                    && ($info=$this->getSMTPInfo())
                    && (!$info['auth'] || $info['password'])
                );
    }

    function allowSpoofing() {
        return ($this->smtp_spoofing);
        
    }

    function getSMTPInfo() {

        $info = array (
                'host' => $this->smtp_host,
                'port' => $this->smtp_port,
                'auth' => (bool) $this->smtp_auth,
                'username' => $this->userid,
                'password' => Crypto::decrypt($this->userpass, SECRET_SALT, $this->userid)
                );

        return $info;
    }

    function send($to, $subject, $message, $attachments=null, $options=null) {

        $mailer = new Mailer($this);
        if($attachments)
            $mailer->addAttachments($attachments);

        return $mailer->send($to, $subject, $message, $options);
    }

    function sendAutoReply($to, $subject, $message, $attachments=null, $options=array()) {
        $options+= array('autoreply' => true);
        return $this->send($to, $subject, $message, $attachments, $options);
    }

    function sendAlert($to, $subject, $message, $attachments=null, $options=array()) {
        $options+= array('notice' => true);
        return $this->send($to, $subject, $message, $attachments, $options);
    }

   function delete() {
        global $cfg;
        //Make sure we are not trying to delete default emails.
        if(!$cfg || $this->getId()==$cfg->getDefaultScheduleId() || $this->getId()==$cfg->getAlertScheduleId()) //double...double check.
            return 0;

        if (!parent::delete())
            return false;

        Dept::objects()
            ->filter(array('schedule_id' => $this->getId()))
            ->update(array(
                'schedule_id' => $cfg->getDefaultScheduleId()
            ));

        Dept::objects()
            ->filter(array('autoresp_schedule_id' => $this->getId()))
            ->update(array(
                'autoresp_schedule_id' => 0,
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

        // very basic checks
        //$vars['cpasswd']=$this->getPasswd(); //Current decrypted password.
        //$vars['name']=Format::striptags(trim($vars['username']));
        // $vars['schedule']=trim($vars['schedule']);

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

    /*static function getAddresses($options=array()) {
        $objects = static::objects();
        if ($options['smtp'])
            $objects = $objects->filter(array('smtp_active'=>true));

        $addresses = array();
        foreach ($objects->values_flat('schedule_id', 'schedule') as $row) {
            list($id, $schedule) = $row;
            $addresses[$id] = $schedule;
        }
        return $addresses;
    }*/
}
RolePermission::register(/* @trans */ 'Miscellaneous', Schedule::getPermissions());
?>
