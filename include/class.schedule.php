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

                echo "<pre>";
                var_dump(Validator::is_schedule($vars['schedule']));
                echo "</pre>";
                die();


        if(!$vars['schedule'] || !Validator::is_schedule($vars['schedule'])) {
            $errors['schedule']=__('Valid schedule required');
        }elseif(($eid=Schedule::getIdBySchedule($vars['schedule'])) && $eid!=$id) {
            $errors['schedule']=__('schedule already exists');
        }elseif($cfg && !strcasecmp($cfg->getAdminSchedule(), $vars['schedule'])) {
            $errors['schedule']=__('schedule already used as admin schedule!');
        }elseif(Staff::getIdBySchedule($vars['schedule'])) { //make sure the email doesn't belong to any of the staff
            $errors['schedule']=__('schedule in use by an agent');
        }

        if(!$vars['name'])
            $errors['name']=__('schedule name required');

        if($vars['mail_active'] || ($vars['smtp_active'] && $vars['smtp_auth'])) {
            if(!$vars['userid'])
                $errors['userid']=__('Username missing');

            if(!$id && !$vars['passwd'])
                $errors['passwd']=__('Password required');
            elseif($vars['passwd']
                    && $vars['userid']
                    && !Crypto::encrypt($vars['passwd'], SECRET_SALT, $vars['userid'])
                    )
                $errors['passwd'] = sprintf('%s - %s', __('Unable to encrypt password'), __('Get technical help!'));
        }

        list($vars['mail_protocol'], $encryption) = explode('/', $vars['mail_proto']);
        $vars['mail_encryption'] = $encryption ?: 'NONE';

        if($vars['mail_active']) {
            //Check pop/imapinfo only when enabled.
            if(!function_exists('imap_open'))
                $errors['mail_active']= __("IMAP doesn't exist. PHP must be compiled with IMAP enabled.");
            if(!$vars['mail_host'])
                $errors['mail_host']=__('Host name required');
            if(!$vars['mail_port'])
                $errors['mail_port']=__('Port required');
            if(!$vars['mail_protocol'])
                $errors['mail_protocol']=__('Select protocol');
            if(!$vars['mail_fetchfreq'] || !is_numeric($vars['mail_fetchfreq']))
                $errors['mail_fetchfreq']=__('Fetch interval required');
            if(!$vars['mail_fetchmax'] || !is_numeric($vars['mail_fetchmax']))
                $errors['mail_fetchmax']=__('Maximum schedules required');

            if(!isset($vars['postfetch']))
                $errors['postfetch']=__('Indicate what to do with fetched schedules');
            elseif(!strcasecmp($vars['postfetch'],'archive')) {
                if ($vars['mail_protocol'] == 'POP')
                    $errors['postfetch'] =  __('POP mail servers do not support folders');
                elseif (!$vars['mail_archivefolder'])
                    $errors['postfetch'] = __('Valid folder required');
            }
        }

        if($vars['smtp_active']) {
            if(!$vars['smtp_host'])
                $errors['smtp_host']=__('Host name required');
            if(!$vars['smtp_port'])
                $errors['smtp_port']=__('Port required');
        }

        //abort on errors
        if ($errors)
            return false;

        if(!$errors && ($vars['mail_host'] && $vars['userid'])) {
            $existing = static::objects()
                ->filter(array(
                    'mail_host' => $vars['mail_host'],
                    'userid' => $vars['userid']
                ));

            if ($id)
                $existing->exclude(array('schedule_id' => $id));

            if ($existing->exists())
                $errors['userid']=$errors['host']=__('Host/userid combination already in use.');
        }

        $passwd = $vars['passwd'] ?: $vars['cpasswd'];
        if(!$errors && $vars['mail_active']) {
            //note: password is unencrypted at this point...MailFetcher expect plain text.
            $fetcher = new MailFetcher(
                    array(
                        'host'  => $vars['mail_host'],
                        'port'  => $vars['mail_port'],
                        'username'  => $vars['userid'],
                        'password'  => $passwd,
                        'protocol'  => $vars['mail_protocol'],
                        'encryption' => $vars['mail_encryption'])
                    );
            if(!$fetcher->connect()) {
                //$errors['err']='Invalid login. Check '.Format::htmlchars($vars['mail_protocol']).' settings';
                $errors['err']=sprintf(__('Invalid login. Check %s settings'),Format::htmlchars($vars['mail_protocol']));
                $errors['mail']='<br>'.$fetcher->getLastError();
            }elseif($vars['mail_archivefolder'] && !$fetcher->checkMailbox($vars['mail_archivefolder'],true)) {
                 //$errors['postfetch']='Invalid or unknown mail folder! >> '.$fetcher->getLastError().'';
                 $errors['postfetch']=sprintf(__('Invalid or unknown mail folder! >> %s'),$fetcher->getLastError());
                 if(!$errors['mail'])
                     $errors['mail']=__('Invalid or unknown archive folder!');
            }
        }

        if(!$errors && $vars['smtp_active']) { //Check SMTP login only.
            require_once 'Mail.php'; // PEAR Mail package
            $smtp = mail::factory('smtp',
                    array ('host' => $vars['smtp_host'],
                           'port' => $vars['smtp_port'],
                           'auth' => (bool) $vars['smtp_auth'],
                           'username' =>$vars['userid'],
                           'password' =>$passwd,
                           'timeout'  =>20,
                           'debug' => false,
                           ));
            $mail = $smtp->connect();
            if(PEAR::isError($mail)) {
                $errors['err']=__('Unable to log in. Check SMTP settings.');
                $errors['smtp']='<br>'.$mail->getMessage();
            }else{
                $smtp->disconnect(); //Thank you, sir!
            }
        }

        if($errors) return false;

        $this->mail_errors = 0;
        $this->mail_lastfetch = null;
        $this->schedule = $vars['schedule'];
        $this->name = Format::striptags($vars['name']);
        //$this->dept_id = $vars['dept_id'];
        //$this->priority_id = $vars['priority_id'];
        $this->date_start = $vars['date_start'];
        $this->date_end = $vars['date_end'];
        $this->topic_id = $vars['topic_id'];
        $this->noautoresp = isset($vars['noautoresp'])?1:0;
        $this->userid = $vars['userid'];
        $this->mail_active = $vars['mail_active'];
        $this->mail_host = $vars['mail_host'];
        $this->mail_protocol = $vars['mail_protocol']?$vars['mail_protocol']:'POP';
        $this->mail_encryption = $vars['mail_encryption'];
        $this->mail_port = $vars['mail_port']?$vars['mail_port']:0;
        $this->mail_fetchfreq = $vars['mail_fetchfreq']?$vars['mail_fetchfreq']:0;
        $this->mail_fetchmax = $vars['mail_fetchmax']?$vars['mail_fetchmax']:0;
        $this->smtp_active = $vars['smtp_active'];
        $this->smtp_host = $vars['smtp_host'];
        $this->smtp_port = $vars['smtp_port']?$vars['smtp_port']:0;
        $this->smtp_auth = $vars['smtp_auth'];
        $this->smtp_spoofing = isset($vars['smtp_spoofing'])?1:0;
        $this->notes = Format::sanitize($vars['notes']);

        //Post fetch email handling...
        if ($vars['postfetch'] && !strcasecmp($vars['postfetch'],'delete')) {
            $this->mail_delete = 1;
            $this->mail_archivefolder = null;
        }
        elseif($vars['postfetch'] && !strcasecmp($vars['postfetch'],'archive') && $vars['mail_archivefolder']) {
            $this->mail_delete = 0;
            $this->mail_archivefolder = $vars['mail_archivefolder'];
        }
        else {
            $this->mail_delete = 0;
            $this->mail_archivefolder = null;
        }

        if ($vars['passwd']) //New password - encrypt.
            $this->userpass = Crypto::encrypt($vars['passwd'],SECRET_SALT, $vars['userid']);

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
