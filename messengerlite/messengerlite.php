<?php
/**
Name: Messengerlite
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: An internal message service that allow the admins to create categories to send message to specifik users
Version: 0.1
Type: Module
 */
class messengerlite
{
    private $arrModuleSettings = [];
    private $db;
    private $id;
    private $action;
    private $subaction;
    private $input;
    private $userId;
    private $_tblGroup = "message_group";
    private $tblGroup = "";
    private $_tblGroupMember = "message_group_member";
    private $tblGroupMember = "";
    private $_tblThread = "message_thread";
    private $tblThread = "";
    private $_tblMessage = "message";
    private $tblMessage = "";
    private $isSuperUser = false;
    private $userCache = [];
    private $groupCache = [];

    /**
     * Initialize the messengerlite module.
     *
     * @param array $arrModuleSettings Module configuration settings.
     */
    function __construct($arrModuleSettings)
    {
        $this->arrModuleSettings = $arrModuleSettings;
        addCSS('system/modules/messengerlite/html/messengerlite.css');
        $this->db = new db();
        $this->input = new input();
        $this->userId = intval(getUserID());
        $this->id = input::_get('id','INTNOZERO');

        $this->tblGroup = $this->db->o_prefix($this->_tblGroup);
        $this->tblGroupMember = $this->db->o_prefix($this->_tblGroupMember);
        $this->tblThread = $this->db->o_prefix($this->_tblThread);
        $this->tblMessage = $this->db->o_prefix($this->_tblMessage);
        $this->isSuperUser = isSuperUser() ? true : false;
        print "
        <style>
        .main__container {
            height:100%;
        }
        </style>
        ";
        print "<div class='messengerlite_container'>";
        // $db = db::getInstance();
        $sql = $this->db->o_get_all('message_group',"SELECT id,name FROM [table] WHERE deleted=0 AND companyid=" . getCompanyID());
        $this->groupCache = array_column($sql, 'name', 'id');

        // Spara resurser och anropa listning av användare
        $sql = $this->db->o_get_all('user',"SELECT id,namn FROM [table] WHERE deleted=0 AND companyid=" . getCompanyID());
        $this->userCache = array_column($sql, 'namn', 'id');
        $_url = $GLOBALS['CONFIG']['www-root'] . '/system/modules/messengerlite/html/';
        print " <div class='messengerlite_navbar'>\n";
        $t = (int) self::checkNotification();
        print "<a href='" . getURL([],true) . "' class=\"notification-icon ";
        print ($t) ? " has-new-messages":"";
        print "\"><img src='" . $_url . "inbox.svg' class='module_icon messengerlite_icon' title='" . $GLOBALS['LANG']['messengerlite']['nav']['inbox'] . "'/>";
        print "<span class=\"notification-circle\"></span>\n";
        print "</a>\n";

        print "<a href='" . getURL(array('action' => 'new'),true) . "'><img src='" . $_url . "new.svg' class='module_icon messengerlite_icon' title='" . $GLOBALS['LANG']['messengerlite']['nav']['new'] . "'/></a>\n";
        
        // Archive icon here?
        // print "<a href='" . getURL(array('action' => 'new'),true) . "'><img src='" . $_url . "new.svg' class='module_icon messengerlite_icon' title='" . $GLOBALS['LANG']['messengerlite']['nav']['new'] . "'/></a>\n";

        print '<a href="' . getURL(['action' => 'close_thread', 'id' => $this->id], true) . '" onclick="return confirm(\'Vill du arkivera det här meddelandet? Det kommer inte längre visas som nytt.\');" style="display:none;" class="messengerlite_icon_archive">';
        print "<img src='" . $_url . "archive.svg' class='module_icon messengerlite_icon messengerlite_archive' title='" . $GLOBALS['LANG']['messengerlite']['nav']['archive'] . "'/>";
        print '</a>';





        if ($this->isSuperUser) {
            // Om subaction admin så visa grupphanteringen direkt
            print '<a href="' . getURL(array('subaction' => 'admin'),true) . '" style="margin-left:auto;">
            <img src="' . $_url . 'admin.svg" class="module_icon messengerlite_icon" title="' . $GLOBALS['LANG']['messengerlite']['nav']['admin'] . '"/>
            </a>';
        } else {
            print "<div style=\"margin-left:auto;\"></div>";
        }
        print "</div>\n";


        $this->action = input::_get('action');
        $this->subaction = input::_get('subaction');
        $this->id = input::_get('id','INTNOZERO');


        // pre(self::checkNotification());
        // En grupp kan ha flera trådar, en tråd har ett ämne och flera meddelanden. En tråd är alltid kopplad till en grupp. En tråd har en status (öppen/stängd). Endast öppna trådar kan få nya meddelanden. Endast admins och de som är med i gruppen kan se trådar och meddelanden.
        print "<div class='messengerlite_content'>\n";
        // ADMIN stuff here
        if ($this->subaction === 'admin' && $this->isSuperUser) {
            print "<h2>Administration</h2>";

            // Kolla om members
            if ($this->action === 'members' && !is_null($this->id)) {
                $this->manageMembers();
                print "</div>\n";
                print "</div>\n";
                return;
            }
            $this->listGroups();
            print "</div>\n";
            print "</div>\n";
            return;
        }
        
        // USER stuff here
        if ($this->action === 'close_thread' && $this->id > 0) {
            $this->closeThread($this->id);
            print "</div>\n";
            print "</div>\n";
            return;
        }
        if ($this->action === 'save_reply' && $this->id > 0) {
            $this->saveReply();
            print "</div>\n";
            print "</div>\n";
            return;
        }
        // Also check for access to thread
        if ($this->action === 'view' && $this->id > 0) {
            $this->viewMessage($this->id);

            print "</div>\n";
            return;
        }
        if ($this->action === 'save') {
            $this->saveNewMessage();
            print "</div>\n";
            print "</div>\n";
            return;
        }
        if ($this->action === 'new') {
            $this->newMessage();
            print "</div>\n";
            print "</div>\n";
            return;
        }
        $this->listMessages();
        print "</div>\n";
        print "</div>\n";
    }

    function saveNewMessage() {
        // Save category and start a new conversation
        $a = input::_postAll(true);
        // is an existing category?
        if (!isset($this->groupCache[$a['category']])) {
            print "No category found";
            return;
        } 
        // Save
        $now = date('Y-m-d H:i:s');
        // Insert Thread

        $this->db->o_insert($this->_tblThread,[
        'groupid'=> $a['category'],
        'user_sender_id' => $this->userId,
        'subject' => addslashes($a['subject']), 
        'status' => 'open', 
        'created' => $now, 
        'updated' => $now, 
        'companyid' => getCompanyID()
        ]);
        // Get thread id
        $threadid = $this->db->o_insert_id();
        // Insert message
        $this->db->o_insert($this->_tblMessage, [
            'threadid' => $threadid, 
            'sender_userid' => $this->userId, 
            'message' => addslashes($a['message']), 
            'created' => $now, 
            'companyid' => getCompanyID()
            ]);

        setMsg('s', 'Meddelandet är skickat.');
        header('Location: '. getURL([],true)); 
        return;
    }

    function newMessage() {
        addJS('system/modules/messengerlite/html/messengerlite.js');
        print '<h2>Nytt meddelande</h2>';


        // Two Step Proccess?

        // Select category
        $arrGroups = $this->db->o_get_all('','SELECT * FROM ' . $this->tblGroup . ' WHERE deleted=0 AND companyid=' . getCompanyID());
        if (count($arrGroups)<=0) {
            print "No categories";
            return;
        }

        $aCat = [];
        foreach ($arrGroups as $key => $value) {
            $aCat[$key] = "<h2>".$value['name'] . "</h2><p>" . $value['description']."</p>";
        }
        print "<div style='width:80%;margin:auto;' class='messengerlite_selectCat'>";
        print "<p>Välj kategori</p>";
        $f = new form();
        print $f->form_begin();
        // print $f->field_RADIOGROUP([
        //     'name' => 'category',
        //     'options' => $aCat,
        //     'field' => 'category'
        // ]);

        foreach ($aCat as $k => $v) {
            print "<div class='messengerlite_categories'>\n";
            print "<input type=\"radio\" name=\"db_category\" id=\"db_category_{$k}\" value=\"{$k}\" class=\"frm_category fld_db_category\">\n";
            print "<label for=\"db_category_{$k}\">{$v}</label>\n";
            print "</div>\n";
            // <input type="radio" name="db_category" id="db_category_0" value="1" class="frm_category fld_db_category">
            // <label for="db_category_0">Ekonomi - Här kan du uppge nytt konto att få lönen utbetald på.</label>
        }


        // print $f->field_RADIO([
        //     'name' => 'category',
        //     'options' => $aCat,
        //     'field' => 'category'
        // ]);
        // <input type="radio" name="db_category" id="db_category_0" value="1" class="frm_category fld_db_category">
        // <label for="db_category_0">Ekonomi - Här kan du uppge nytt konto att få lönen utbetald på.</label>
        print "<div class='messengerlite_categories' style='flex-direction: column;'>\n";
        print $f->field_INPUT([
            'name' => 'subject',
            'field' => 'subject',
            'required' => 'required',
            'caption' => ['Rubrik','']
        ]);
        print "</div>\n";

        print "<div class='messengerlite_categories' style='flex-direction: column;'>\n";
        print $f->field_TEXTAREA([
            'name' => 'message',
            'field' => 'message',
            'required' => 'required',
            'caption' => ['Meddelande','']
        ]);
        print "</div>\n";


        print "<input type=\"submit\" value=\"Skapa ett nytt meddelande\" name=\"db_submit\" id=\"db_submit\" class=\"\" style='width:100%;display:block;max-width:unset;margin-top:1rem; padding:.5rem;' disabled='disabled'>\n";

        // check if all objects is done
        // print "<script>\n";
        // print "        $('.frm_category').on('click', function() {\n";
        // print "            $('#db_submit').removeAttr(\"disabled\");\n";
        // print "        });\n";
        // print " function checkMessengerlite() {\n";
        // print "        });\n";
        
        // print "</script>\n";


        // print "<script>\n";
        // print "        $('.frm_category').on('click', function() {\n";
        // print "            $('#db_submit').removeAttr(\"disabled\");\n";
        // print "        });\n";
        // print "</script>\n";

        // print 
        // print $f->form_submit();
        print $f->form_end();
        print "</div>";

        // pre($arrGroups);

    }

    /**
     * Formats a given datetime into a relative time string (e.g., '5m', '2h', '3d').
     *
     * @param string $datetime The datetime string to be formatted.
     * @return string A string representing the time elapsed since the given datetime.
     */
    private function formatTimeAgo($datetime) {
        $now = time();
        $timestamp = strtotime($datetime);
        $diff = $now - $timestamp;

        if ($diff < 3600) { // less than 1 hour
            $minutes = floor($diff / 60);
            return $minutes . 'm';
        } elseif ($diff < 86400) { // less than 1 day
            $hours = floor($diff / 3600);
            return $hours . 'h';
        } elseif ($diff < 604800) { // less than 1 week
            $days = floor($diff / 86400);
            return $days . 'd';
        } elseif ($diff < 2592000) { // less than 1 month (approx 30 days)
            $weeks = floor($diff / 604800);
            return $weeks . 'w';
        } elseif ($diff < 31536000) { // less than 1 year
            $months = floor($diff / 2592000);
            return $months . 'M';
        } else {
            $years = floor($diff / 31536000);
            return $years . 'y';
        }
    }

    /**
     * Lists messages from the inbox.
     *
     * This function retrieves and displays messages from the database for the current user.
     * It fetches distinct message threads and their latest messages, then formats and prints
     * each message using the messageBox method.
     */
    /**
     * Retrieve and render the current user's inbox messages.
     *
     * This method loads the latest message for each thread the
     * current user participates in and outputs the message boxes.
     */
    function listMessages()
    {
        print '<h2>Inkorg</h2>';
        $messages = $this->db->o_get_all('', 'SELECT DISTINCT t.id, t.groupid, t.user_sender_id, t.subject, t.status, t.created, t.updated, t.companyid, t.ordning, m.message as message, m.sender_userid as sender_userid, m.created as created,
        CASE WHEN t.status = \'closed\' THEN 0 WHEN m.sender_userid != ' . $this->userId . ' THEN 1 ELSE 0 END as my_turn,
        CASE WHEN t.status = \'closed\' THEN 1 ELSE 0 END as is_closed
        FROM mt_message_thread t
        LEFT JOIN mt_message_group_member gm ON t.groupid = gm.groupid AND gm.deleted = 0
        LEFT JOIN mt_message m ON m.threadid = t.id AND m.deleted = 0 AND m.created = (SELECT MAX(created) FROM mt_message WHERE threadid = t.id AND deleted = 0)
        WHERE t.deleted = 0
        AND (t.user_sender_id = ' . $this->userId . ' OR gm.userid = ' . $this->userId . ')
        ORDER BY is_closed ASC, my_turn DESC, t.updated DESC;');

        foreach ($messages as $m) {
            if ($m['sender_userid'] == $this->userId) {
                $sender_name = 'Du';
            } else {
                $sender_name = isset($this->userCache[$m['sender_userid']]) ? $this->userCache[$m['sender_userid']] : 'Unknown';
            }
            $m['sender_name'] = $sender_name;
            print $this->messageBox($m);
        }
    }

    /**
     * Display a single message thread with all messages and a reply form.
     *
     * @param int $threadid The ID of the thread to display.
     */
    function viewMessage($threadid)
    {
        $_url = $GLOBALS['CONFIG']['www-root'] . '/system/modules/messengerlite/html/';
        // addJS('system/modules/messengerlite/html/messengerlite.js');
        // Visa en tråd med alla meddelanden och en form för att svara
        $thread = $this->db->o_get_row('', 'SELECT t.*, g.name as groupname FROM ' . $this->tblThread . ' t INNER JOIN ' . $this->tblGroup . ' g ON g.id=t.groupid WHERE t.id=' . $threadid . ' AND t.deleted=0');

        if (!is_array($thread)) { echo '<p>Tråden finns inte.</p>'; return; }

        $allowed = $this->db->o_get_row('', 'SELECT id FROM ' . $this->tblGroupMember . ' WHERE groupid=' . intval($thread['groupid']) . ' AND userid=' . $this->userId . ' AND deleted=0');
        if (!$this->isSuperUser && !is_array($allowed) && intval($thread['user_sender_id']) !== $this->userId) { echo '<p>Ingen åtkomst.</p>'; return; }


        $isClosed = ($thread['status'] === 'closed');
        print '<h2><b>' . htmlspecialchars($thread['groupname']) . '</b> - ' . htmlspecialchars($thread['subject']);
        if ($isClosed) {
            print ' <span style="font-size:0.7em;font-weight:normal;background-color:var(--waiting-color);border-radius:.25rem;padding:0 .4rem;vertical-align:middle;">Arkiverad</span>';
        // } else {

            //     print '<p style="float:right;padding-top:4px;"><a href="' . getURL(['action' => 'close_thread', 'id' => $threadid], true) . '" onclick="return confirm(\'Vill du arkivera det här meddelandet? Det kommer inte längre visas som nytt.\');" style="font-size:0.85em;">';
            //     print "<img src='" . $_url . "archive.svg' style='width:24px;height:24px;' class='module_icon messengerlite_archive' title='" . $GLOBALS['LANG']['messengerlite']['nav']['archive'] . "'/>";
            //     print '</a></p>'; //Arkivera/stäng meddelandet
        }

        print '</h2>';
        if (!$isClosed) {
            print "            <style>\n";
            print "            .messengerlite_icon_archive {display:block !important;}\n";
            print "            </style>\n";
        }
        // if (!$isClosed && ($this->isSuperUser || intval($thread['user_sender_id']) === $this->userId)) {
        // if (!$isClosed ) {
        //     print '<p style="float:right;"><a href="' . getURL(['action' => 'close_thread', 'id' => $threadid], true) . '" onclick="return confirm(\'Vill du arkivera det här meddelandet? Det kommer inte längre visas som nytt.\');" style="font-size:0.85em;">';
        //     print "<img src='" . $_url . "archive.svg' style='width:32px;height:32px;' class='module_icon messengerlite_archive' title='" . $GLOBALS['LANG']['messengerlite']['nav']['archive'] . "'/>";
        //     print '</a></p>'; //Arkivera/stäng meddelandet
        // }
        $messages = $this->db->o_get_all_ai('', 'SELECT *, sender_userid as userid FROM ' . $this->tblMessage . ' WHERE threadid=' . $threadid . ' AND deleted=0 ORDER BY created ASC');
        foreach ($messages as $m) {
            $m['sender_name'] = (intval($m['sender_userid']) === $this->userId) ? 'Du' : (isset($this->userCache[intval($m['sender_userid'])]) ? $this->userCache[intval($m['sender_userid'])] : 'Unknown');
            $m['splitview'] = intval(intval($m['sender_userid']) === $this->userId);
            print $this->messageBox($m);
        }



        print "</div>";

        print "<div class='messengerlite_form'>\n";
        $f = new form();
        print $f->form_begin(
            arrSettings: array(
                'arrQuery' => array(
                    'action' => 'save_reply', 
                    'id' => $threadid
                ),
            )
        );
        print $f->createSafeForm($this->_tblMessage, $threadid);
        print $f->field_HIDDEN([
            'name' => 'id',
            'value' => $threadid,
            'field' => 'id',
        ]);
        print $f->field_INPUT([
            'name' => 'message',
            // 'caption' => 'Ditt svar',
            'rows' => 4,
            // 'style' => 'width:100%;max-width:800px;',
            'field' => 'message',
        ]);

        print "<input type='image' src='" . $GLOBALS['CONFIG']['www-root'] . "/system/modules/messengerlite/html/send.svg' border='0' alt='Submit' class='messengerlite_submit' />";
        // print '<input type="submit" hidden />';
        print $f->form_end();
        print "</div>\n";
    }

    private function closeThread(int $threadid)
    {
        $threadid = intval($threadid);
        $thread = $this->db->o_get_row('', 'SELECT * FROM ' . $this->tblThread . ' WHERE id=' . $threadid . ' AND deleted=0');
        if (!is_array($thread)) {
            setMsg('e', 'Tråden finns inte.');
            header('Location: ' . getURL([], true));
            exit;
        }
        if (!$this->isThreadAllowed($threadid)) {
        // if (!$this->isSuperUser && intval($thread['user_sender_id']) !== $this->userId) {
            setMsg('e', 'Du har inte behörighet att arkivera det här meddelandet.');
            header('Location: ' . getURL(['action' => 'view', 'id' => $threadid], true));
            exit;
        }
        $this->db->o_update($this->_tblThread, ['status' => 'closed'], 'id=' . $threadid);
        setMsg('s', 'Meddelandet är arkiverat.');
        header('Location: ' . getURL([], true));
        exit;
    }

    /**
     * Check whether the current user is allowed to view or reply to a thread.
     *
     * @param int $threadid The thread ID to validate.
     * @return bool True if the current user has access, false otherwise.
     */
    function isThreadAllowed($threadid) {
        $threadid = intval($threadid);
        if ($threadid <= 0) {
            return false;
        }

        $thread = $this->db->o_get_row('', 'SELECT * FROM ' . $this->tblThread . ' WHERE id=' . $threadid . ' AND deleted=0');
        if (!is_array($thread)) {
            return false;
        }

        if ($this->isSuperUser) {
            return true;
        }

        if (intval($thread['user_sender_id']) === $this->userId) {
            return true;
        }

        $allowed = $this->db->o_get_row('', 'SELECT id FROM ' . $this->tblGroupMember . ' WHERE groupid=' . intval($thread['groupid']) . ' AND userid=' . $this->userId . ' AND deleted=0');
        return is_array($allowed);
    }


    /**
     * Generates an HTML message box for a given message.
     *
     * This function creates a clickable message box that displays the subject,
     * sender's name, latest message, and the time since the message was created.
     *
     * @param array $msg An associative array containing message details:
     *                   - 'id': The unique identifier of the message.
     *                   - 'subject': The subject of the message.
     *                   - 'sender_name': The name of the latest sender.
     *                   - 'message': The content of the latest message.
     *                   - 'created': The timestamp of when the latest message was created.
     *                   - ['splitview']: A boolean indicating if the message should be styled as sent by the current user.
     *
     * @return string The HTML output for the message box.
     */
    function messageBox($msg) {
        // $boxClass = "";
        // pre($msg);
        if (isset($msg['splitview'])) { // && $msg['splitview'] === true
            $boxClass = 'messengerlite_message_split';
            if($msg['splitview']) {
                $boxClass .= ' messengerlite_message_right';
            } else {
                $boxClass .= ' messengerlite_message_left';
            }
            // $boxClass = 'messengerlite_message_split';
        } else {
            $boxClass = '';
        }

        $myTurn = !empty($msg['my_turn']);
        if ($myTurn) {
            $boxClass .= ' messengerlite_my_turn';
        }
        // pre($msg);
        if (!empty($msg['is_closed']) || (isset($msg['status']) && $msg['status'] === 'closed')) {
            $boxClass .= ' messengerlite_archived';
        }

        $output = "\t<div class='messengerlite_message " . $boxClass . "' ";
        $output .= (!isset($msg['splitview'])) ? "onclick=\"window.location.href='" . getURL(array('action' => 'view', 'id' => $msg['id'])) . "'\"" : "";
        $output .= " style='cursor:pointer;'>\n";
        if ($msg['subject']) {
            $output .= "\t\t<h2>" . htmlspecialchars($msg['subject']);
            // if ($myTurn) {
            //     $output .= " <span class='messengerlite_my_turn_badge'>Svara</span>";
            // }
            $output .= "</h2>\n";
        }
        $output .= "\t\t<div>\n";
        // $output .= $this->userId;
        $output .= "\t\t\t<div><b>" . htmlspecialchars($msg['sender_name']) . ":</b></div>\n";
        $output .= "\t\t\t<div>" . htmlspecialchars($msg['message']) . "</div>\n";
        $output .= "\t\t\t<div>" . $this->formatTimeAgo($msg['created']) . "</div>\n";
        $output .= "\t\t</div>\n";
        $output .= "\t</div>\n";
        return $output;
    }


    /**
     * Manages the members of a group by checking the current state of users
     * and updating the database based on user input.
     */
    private function manageMembers()
    {
        if (!isset($this->groupCache[$this->id])) {
            echo '<p>Gruppen finns inte.</p>';
            return;
        }
        // echo "<h2>Admin</h2>";
        $uc = $this->userCache;
        asort($uc);
        // $db = db::getInstance(); //message_group_member
        $ret = $this->db->o_get_all('',"SELECT * FROM " . $this->tblGroupMember . " WHERE groupid=" . $this->id . " AND deleted=0 AND companyid=" . getCompanyID(),'userid'); //,showDebug:true
        // pre($ret);
        // pre($uc);
        $arrUser = [];
        foreach ($uc as $k => $v) {
            $arrUser[$k] = ($ret[$k]) ? 1 : 0 ;
        }


        // spara om post finns och sedan header..
        $save_dta = input::_postAll();
        if (isset($save_dta['table']) && $save_dta['table'] === $this->tblGroupMember) {
            // Check Hash
            // pre($save_dta);
            $fc = new formcatcher();
            if (!$fc->checkHash($this->tblGroupMember)) {
                setMsg('e', 'Ogiltig hash. Försök igen.');
                return;
            }
            $t_au = $arrUser;
            $t_nu = $save_dta['db_userid'] ?? [];
            $st_a = 0;
            $st_d = 0;
            foreach ($t_au as $k => $v) {
                if ($t_nu[$k] != $v && $v == 1) {
                    // Lägg till
                    // pre('remove ' . $k);
                    if($this->db->o_update($this->_tblGroupMember, array('deleted' => 1), "groupid=" . $this->id . " AND userid=" . $k . " AND deleted=0 AND companyid=" . getCompanyID())) {
                        // pre('successfully removed ' . $k);
                    // // if($this->db->o_delete($this->tblGroupMember, "groupid=" . $this->id . " AND userid=" . $k . " AND companyid=" . getCompanyID())) {
                        $st_d++;
                    } else {
                        setMsg('e', 'Fel vid borttagning av användare ' . $this->userCache[$k]);
                    }
                    // $this->db->o_insert('message_group_member', array('groupid' => $this->id, 'userid' => $k, 'can_reply' => 1, 'companyid' => getCompanyID()));
                } elseif ($t_nu[$k] != $v && $v == 0) {
                    // Ta bort
                    // pre('add ' . $k);
                    if ($this->db->o_insert('message_group_member', array('groupid' => $this->id, 'userid' => $k, 'can_reply' => 1, 'companyid' => getCompanyID()))) {
                        $st_a++;
                    } else {
                        setMsg('e', 'Fel vid tillägg av användare ' . $this->userCache[$k]);
                    }
                }
            }
            if($st_a > 0) {
                setMsg('s', 'Användare tillagda: ' . $st_a);
            }
            if($st_d > 0) {
                setMsg('s', 'Användare borttagna: ' . $st_d);
            }
            // pre($t_nu);
            // pre($t_au);
            header('Location: '. getURL(['action' => 'members','subaction' => 'admin'],true)); //,'id' => $this->id
            return;
        }

        // pre($arrUser);
        $f = new form();
        print $f->form_begin(
            arrSettings: array(
                'arrQuery' => array(
                    'action' => 'members', 
                    'subaction' => 'admin', 
                    'id' => $this->id
                ),
            )
        );
        // $output .= "<input type=\"hidden\" name=\"table\" id=\"table\" value=\"" . $table . "\">\n";
        print $f->createTable($this->tblGroupMember);
        print $f->createHash($this->tblGroupMember,$this->id);
        print $f->field_HIDDEN([
            'name' => 'id',
            'value' => $this->id,
            'field' => 'id'
        ]);
        print $f->field_CHECKBOXTABLE([
            'name' => 'users',
            'type' => 'checkboxtable',
            'caption' => $GLOBALS['LANG']['message_group']['members'],
            'options' => $uc,
            'value' => $arrUser,
            'field' => 'userid',
        ]);

        print $f->form_submit();
        print $f->form_end();

    }

    /**
     * Lists groups and manages them, then lists all members as recipients.
     * 
     * This function merges module settings with global message group settings
     * and initializes a list and edit operation.
     */
    private function listGroups()
    {
        //Lista grupper och hantera dem
        // Sedan göra så att man listar alla medlemmar som är mottagare.
        $tmp = $this->arrModuleSettings;
        $arrModuleSettings = array_merge($tmp, $GLOBALS['TABLE']['message_group']);
        $list = new listandedit($arrModuleSettings);
        return;
    }



    /**
     * Render the group management interface for superusers.
     *
     * @param array $arrModuleSettings Module configuration settings for groups.
     */
    private function renderManageGroups($arrModuleSettings)
    {
        $manageAction = $this->input->get('manage_groups');
        if (isset($_GET['manage_groups']) && in_array($manageAction, array('add', 'edit', 'save', 'delete'))) {
            $_GET['action'] = $manageAction;
        }
        $_GET['manage_groups'] = '';
        echo '<a class="button" href="' . getURL(array('action' => 'manage_groups', 'manage_groups' => 'add')) . '">Lägg till ny grupp</a><br /><br />';
        $tmp = $arrModuleSettings;
        $arrModuleSettings = array_merge($tmp, $GLOBALS['TABLE']['message_group']);
        $list = new listandedit($arrModuleSettings);
        if (input::_get('action') === 'save') {
            header('Location: '. getURL(['action' => 'manage_groups'],true));
            exit;
        }
    }

    /**
     * Render the member management interface for superusers.
     *
     * @param array $arrModuleSettings Module configuration settings for group members.
     */
    private function renderManageMembers($arrModuleSettings)
    {
        $manageAction = $this->input->get('manage_members');
        if (isset($_GET['manage_members']) && in_array($manageAction, array('add', 'edit', 'save', 'delete'))) {
            $_GET['action'] = $manageAction;
        }
        $_GET['manage_members'] = '';
        echo '<a class="button" href="' . getURL(array('action' => 'manage_members', 'manage_members' => 'add')) . '">Lägg till användare i grupp</a><br /><br />';
        $tmp = $arrModuleSettings;
        $arrModuleSettings = array_merge($tmp, $GLOBALS['TABLE']['message_group_member']);
        $list = new listandedit($arrModuleSettings);
        if (input::_get('action') === 'save') {
            header('Location: '. getURL(['action' => 'manage_members'],true));
            exit;
        }
    }

    /**
     * Return the list of groups available to the current user.
     *
     * @return array The groups that the user can access.
     */
    private function myGroups()
    {
        if ($this->isSuperUser) {
            return $this->db->o_get_all_ai('', 'SELECT * FROM ' . $this->tblGroup . ' WHERE deleted=0 ORDER BY name');
        }
        return $this->db->o_get_all_ai('', 'SELECT g.* FROM ' . $this->tblGroup . ' g INNER JOIN ' . $this->tblGroupMember . ' m ON m.groupid=g.id WHERE m.userid=' . $this->userId . ' AND g.deleted=0 AND m.deleted=0 ORDER BY g.name');
    }

    /**
     * Render the form for composing a new message thread.
     */
    private function renderNewForm()
    {
        $groups = $this->myGroups();
        echo '<h3>Nytt meddelande</h3>';
        if (!is_array($groups) || count($groups) === 0) {
            echo '<p>Du är inte tillagd i någon meddelandegrupp ännu.</p>';
            return;
        }
        echo '<form method="post" action="' . getURL(array('action' => 'save_new')) . '">';
        echo '<label>Grupp</label><br /><select name="groupid">';
        foreach ($groups as $g) {
            echo '<option value="' . intval($g['id']) . '">' . htmlspecialchars($g['name']) . '</option>';
        }
        echo '</select><br /><br />';
        echo '<label>Ämne</label><br /><input type="text" name="subject" style="width:100%;max-width:600px;" /><br /><br />';
        echo '<label>Meddelande</label><br /><textarea name="message" rows="8" style="width:100%;max-width:800px;"></textarea><br /><br />';
        echo '<button type="submit">Skicka</button></form>';
    }

    /**
     * Save a newly created message thread and its initial message.
     */
    private function saveNewThread()
    {
        $groupid = intval($_POST['groupid']);
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        if ($groupid <= 0 || $subject === '' || $message === '') {
            setMsg('e', 'Alla fält måste fyllas i.');
            return;
        }
        $isMember = $this->db->o_get_row('', 'SELECT id FROM ' . $this->tblGroupMember . ' WHERE groupid=' . $groupid . ' AND userid=' . $this->userId . ' AND deleted=0');
        if (!$this->isSuperUser && !is_array($isMember)) {
            setMsg('e', 'Du får bara skriva till grupper där du är tillagd.');
            return;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->o_insert('message_thread', array('groupid' => $groupid, 'user_sender_id' => $this->userId, 'subject' => addslashes($subject), 'status' => 'open', 'created' => $now, 'updated' => $now, 'companyid' => getCompanyID()));
        $threadid = $this->db->o_insert_id();
        $this->db->o_insert('message', array('threadid' => $threadid, 'sender_userid' => $this->userId, 'message' => addslashes($message), 'created' => $now, 'companyid' => getCompanyID()));
        setMsg('s', 'Meddelandet är skickat.');
    }

    /**
     * Render the list of message threads for the inbox view.
     */
    private function renderInbox()
    {
        if ($this->isSuperUser) {
            $sql = 'SELECT t.*, g.name as groupname FROM ' . $this->tblThread . ' t INNER JOIN ' . $this->tblGroup . ' g ON g.id=t.groupid WHERE t.deleted=0 ORDER BY t.updated DESC';
        } else {
            $sql = 'SELECT DISTINCT t.*, g.name as groupname FROM ' . $this->tblThread . ' t INNER JOIN ' . $this->tblGroup . ' g ON g.id=t.groupid LEFT JOIN ' . $this->tblGroupMember . ' m ON m.groupid=t.groupid WHERE t.deleted=0 AND (t.user_sender_id=' . $this->userId . ' OR m.userid=' . $this->userId . ') ORDER BY t.updated DESC';
        }
        $threads = $this->db->o_get_all_ai('', $sql);
        echo '<h3>Trådar</h3>';
        if (!is_array($threads) || count($threads) === 0) {
            echo '<p>Inga trådar än.</p>';
            return;
        }
        echo '<table><tr><th>Grupp</th><th>Ämne</th><th>Senast uppdaterad</th><th></th></tr>';
        foreach ($threads as $t) {
            echo '<tr><td>' . htmlspecialchars($t['groupname']) . '</td><td>' . htmlspecialchars($t['subject']) . '</td><td>' . $this->formatTimeAgo($t['updated']) . '</td><td><a href="' . getURL(array('action' => 'view', 'threadid' => $t['id'])) . '">Öppna</a></td></tr>';
        }
        echo '</table>';
    }

    /**
     * Render the detail view for a single thread, including all messages.
     *
     * @param int $threadid The ID of the thread to display.
     */
    private function renderThread($threadid)
    {
        $thread = $this->db->o_get_row('', 'SELECT t.*, g.name as groupname FROM ' . $this->tblThread . ' t INNER JOIN ' . $this->tblGroup . ' g ON g.id=t.groupid WHERE t.id=' . $threadid . ' AND t.deleted=0');
        if (!is_array($thread)) { echo '<p>Tråden finns inte.</p>'; return; }
        $allowed = $this->db->o_get_row('', 'SELECT id FROM ' . $this->tblGroupMember . ' WHERE groupid=' . intval($thread['groupid']) . ' AND userid=' . $this->userId . ' AND deleted=0');
        if (!$this->isSuperUser && !is_array($allowed) && intval($thread['user_sender_id']) !== $this->userId) { echo '<p>Ingen åtkomst.</p>'; return; }
        echo '<h3>' . htmlspecialchars($thread['subject']) . '</h3><p>Grupp: <b>' . htmlspecialchars($thread['groupname']) . '</b></p>';
        $messages = $this->db->o_get_all_ai('', 'SELECT * FROM ' . $this->tblMessage . ' WHERE threadid=' . $threadid . ' AND deleted=0 ORDER BY created ASC');
        echo '<div style="border:1px solid #ccc;padding:10px;">';
        foreach ($messages as $m) {
            // echo '<div style="margin-bottom:12px;"><b>Användare #' . intval($m['sender_userid']) . '</b> <i>' . $m['created'] . '</i><br />' . nl2br(htmlspecialchars($m['message'])) . '</div>';
            echo '<div style="margin-bottom:12px;"><b>' . $this->userCache[intval($m['sender_userid'])] . '</b> <i>' . $this->formatTimeAgo($m['created']) . '</i><br />' . nl2br(htmlspecialchars($m['message'])) . '</div>';
        }
        echo '</div><br />';
        echo '<form method="post" action="' . getURL(array('action' => 'save_reply', 'id' => $threadid)) . '"><textarea name="message" rows="5" style="width:100%;max-width:800px;"></textarea><br /><br /><button type="submit">Svara</button></form>';
    }

    /**
     * Save a reply message to an existing thread and update the thread timestamp.
     */
    private function saveReply()
    {
        // TODO: Var kommer x o y ifrån i post
        $threadid = $this->id;
        if(!$this->isThreadAllowed($threadid)) {
            setMsg("e","Thread not allowed");
            return;
        }
        $a = input::_postAll(true);
        // pre($a);
        // pre($this->_tblMessage);
        $f = new formcatcher();
        if (!$f->checkHash($this->_tblMessage)) {
            setMsg("e","Message did not passed security check");
            return;
        }

        // pre($_POST);
        debug();
        $now = date('Y-m-d H:i:s');
        $message = trim($a['message']);
        if ($threadid <= 0 || $message === '') { setMsg('e', 'Meddelandet är tomt.'); return; }
        $this->db->o_insert($this->_tblMessage, array('threadid' => $threadid, 'sender_userid' => $this->userId, 'message' => addslashes($message), 'created' => $now, 'companyid' => getCompanyID()));
        $this->db->o_update($this->_tblThread, array('updated' => $now, 'status' => 'open'), 'id=' . $threadid);
        setMsg('s', 'Svar skickat.');
        header('Location: ' . getURL(array('action' => 'view', 'id' => $threadid)));
        exit;



        return;


        $message = trim($_POST['message']);
        if ($threadid <= 0 || $message === '') { setMsg('e', 'Meddelandet är tomt.'); return; }
        $thread = $this->db->o_get_row('', 'SELECT * FROM ' . $this->tblThread . ' WHERE id=' . $threadid . ' AND deleted=0');
        if (!is_array($thread)) { setMsg('e', 'Tråd saknas.'); return; }
        if (!$this->isThreadAllowed($threadid)) { setMsg('e', 'Du har inte behörighet att svara.'); return; }
        $now = date('Y-m-d H:i:s');
        $this->db->o_insert('message', array('threadid' => $threadid, 'sender_userid' => $this->userId, 'message' => addslashes($message), 'created' => $now, 'companyid' => getCompanyID()));
        $this->db->o_update('message_thread', array('updated' => $now), 'id=' . $threadid);
        setMsg('s', 'Svar skickat.');
        header('Location: ' . getURL(array('action' => 'view', 'id' => $threadid)));
        exit;


        // $threadid = $this->id;
        // $message = trim($_POST['message']);
        // if ($threadid <= 0 || $message === '') { setMsg('e', 'Meddelandet är tomt.'); return; }
        // $thread = $this->db->o_get_row('', 'SELECT * FROM ' . $this->tblThread . ' WHERE id=' . $threadid . ' AND deleted=0');
        // if (!is_array($thread)) { setMsg('e', 'Tråd saknas.'); return; }
        // if (!$this->isThreadAllowed($threadid)) { setMsg('e', 'Du har inte behörighet att svara.'); return; }
        // $now = date('Y-m-d H:i:s');
        // $this->db->o_insert('message', array('threadid' => $threadid, 'sender_userid' => $this->userId, 'message' => addslashes($message), 'created' => $now, 'companyid' => getCompanyID()));
        // $this->db->o_update('message_thread', array('updated' => $now), 'id=' . $threadid);
        // setMsg('s', 'Svar skickat.');
        // header('Location: ' . getURL(array('action' => 'view', 'id' => $threadid)));
        // exit;
    }

    static function checkNotification() {
        $db = db::getInstance();
        $userId = intval(getUserID());
        $companyId = intval(getCompanyID());

        $tblThread = $db->o_prefix('message_thread');
        $tblMessage = $db->o_prefix('message');
        $tblGroupMember = $db->o_prefix('message_group_member');

        $rows = $db->o_get_all('', "
            SELECT DISTINCT t.id
            FROM {$tblThread} t
            LEFT JOIN {$tblGroupMember} gm
                ON gm.groupid = t.groupid AND gm.deleted = 0 AND gm.userid = {$userId}
            LEFT JOIN {$tblMessage} m
                ON m.threadid = t.id AND m.deleted = 0
                AND m.created = (SELECT MAX(created) FROM {$tblMessage} WHERE threadid = t.id AND deleted = 0)
            WHERE t.deleted = 0
              AND t.status = 'open'
              AND t.companyid = {$companyId}
              AND (t.user_sender_id = {$userId} OR gm.userid = {$userId})
              AND m.sender_userid != {$userId}
        ");

        return is_array($rows) ? count($rows) : 0;
    }
}
