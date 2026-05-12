<?php
/**
Name: Import games from swehockey.se
Author: Martin Tonek
Author URI: https://www.tonek.se
Description: Lets you import the games from swehockey.se 
Version: 0.1
Type: Module
 */
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// Bygga en importeringsfunktion från t.ex. hockeyallsvenskan och hockeyettan..
/**
 * Byggt en funktion som med hjälp av https://stats.swehockey.se/ kan hämta matcher och lägga in dem i systemet.
 * Man gör detta i flera steg.
 * 
 * 1a. Hämta url från swehockey och koipera sidan där matcherna är t.ex. https://stats.swehockey.se/ScheduleAndResults/Schedule/17354
 * 1b. Välj vilket lag du vill lägga in för (select) Manuella
 * 1c. Välj grundbemanning
 * 1d. Välj pass start (-1,5)
 * 1e. Välj pass slut (2,5)
 * 1f. Hämta data
 * 
 * 2. Kontrollera och bekräfta
 * 
 * 3. Klart!
 */


/*
        <li>- Veckodag (kan lämnas tom)<li>
        <li>- Datum<li>
        <li>- Matchstart<li>
        <li>- Hemmalag<li>
        <li>- Bortalag<li>
        <li>- Antal vakter<li>
        <li>- Pass start (-1,5)<li>
        <li>- Pass slut (2,5)<li>
        <li>- Kund ID<li>
*/


class import_hockey
{
  private $dbFormSettings;
  private $allowEdit = false;
  private $teams = [
    3   => 'Västervik',
    10  => 'Kalmar',
    11  => 'Vimmerby',
    49  => 'IK Oskarshamn'

  ];
  private $settings = [
    3   => [
      'grund' => 2,
      'pass_start' => '-1.5',
      'pass_slut' => '2.5',
    ],
    10  => [
      'grund' => 2,
      'pass_start' => '-2',
      'pass_slut' => '2.5',
    ],
    11  => [
      'grund' => 2,
      'pass_start' => '-1.5',
      'pass_slut' => '2.5',
    ],
    49  => [
      'grund' => 6,
      'pass_start' => '-1.5',
      'pass_slut' => '2.5',
    ],

  ];

  function __construct($arrModuleSettings)
  {
    // Fixa Access här!
    if ($arrModuleSettings['own_access']['edit']) {
      $this->allowEdit = true;
    }
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    addJS('system/modules/import_hockey/html/import_hockey.js');
    // debug();
    // print "<pre>";
    // print __CLASS__ . "\n";
    // print_r($arrModuleSettings['own_access']);
    // print "</pre>";
    $db = db::getInstance();
    $this->teams = $db->o_get_options('customers', "SELECT id,name FROM [table] WHERE isHockey=1 AND deleted=0 ORDER BY name ASC");
    // pre($this->teams);

    print returnBtn();
    // print "<a href='?action=split'>Dela</a><br>";
    print "<h1>Importera HOCKEY!!</h1>";
    // print "<p class='notice' style='font-size:14px;'>Import sker från <a href=\"https://stats.swehockey.se\" target=\"_blank\">https://stats.swehockey.se</a>. Du behöver gå in på t.ex.<i>'HockeyAllsvenskan / Schedule & Results / Schedule'</i>. Se till att ha rätt säsong med :)</p>";

    print "<table class='notice' style='font-size:14px;'>
    <tr><th colspan='4'>Import sker från <a href=\"https://stats.swehockey.se\" target=\"_blank\">https://stats.swehockey.se (länk)</a>. Du behöver gå in på t.ex.<i>'HockeyAllsvenskan / Schedule & Results / Schedule'</i>. Se till att ha rätt säsong med :)</th></tr>
    <tr><td>Vimmerby</td><td>2st</td><td>1,5 h innan matchstart</td><td> (4h)</td></tr>
    <tr><td>Oskarshamn</td><td>6st</td><td>1.5 h innan matchstart</td><td>(4h)</td></tr>
    <tr><td>Kalmar</td><td>2st fredag, lördag 3st</td><td>2 h innan matchstart</td><td>(4.5h)</td></tr>
    <tr><td colspan='4'>Träningsmatcher oskarshamn  och kalmar brukar vara 2st</td></tr>
    </table>\n";

    // if (input::_post('status') == "upload") {
    //   $this->uploadFileAndSetData();
    // } else

    // $this->getFromJson();
    // $this->testJSChange();
    // return;


    if (input::_post('status') == "update") {
      // STEP 4
      $this->updateDatabase();
    } elseif (input::_post('status') == "upgrade") {
      // STEP 3
      $this->upgradeData();

    } elseif (input::_post('status') == "retrive" && input::_post('team','INTNOZERO')>0) {
      // STEP 2
      $this->uploadFromURLAndSetData();
    } elseif (input::_get('action') == "split") {
      $this->splitHockey();
    } else {
      // STEP 1
      $this->getFromWWW();
    }
  }

  function splitHockey() {
    return;
    # pid = 49
    # 2025-09-19
    // print "HEJ";
    $db = db::getInstance();
    $sql = $db->o_get_all('events',"SELECT * FROM [table] WHERE startdate>='2025-09-19 00:00:00' and pid=49 and deleted=0");
    // pre($sql);
    foreach ($sql as $k => $v) {
      $id = $v['id'];
      $arrUpdate = $v;
      $arrUpdate['gid'] = $v['id'];
      $arrUpdate['required'] =($v['required']-2);

      pre($arrUpdate);
      $arrInsert = $arrUpdate;
      $arrInsert['startdate'] = date("Y-m-d H:i:s",strtotime($v['startdate'] . '-30minutes'));
      $arrInsert['required'] =2;
      unset($arrInsert['id']);
      pre($arrInsert);
      // if ($db->o_update('events', $arrUpdate, " id={$id} ")) print "Updated<br>";
      // if ($db->o_insert('events', $arrInsert)) print "Inserted<br>";
      print "<hr>";
      
    }


  }

  function testJSChange() {
    debug();
    if(isset($_POST) && count($_POST)>0) {
      print "POSTED";
    }



    $teams = json_decode(
      '{"8":{"pid":11,"gamestart":"2025-09-19 19:00","name":"Vimmerby HC ( vs. IF Troja)","startdate":"2025-09-19 17:30","stopdate":"2025-09-19 21:30","required":"2","active":0},"21":{"pid":11,"gamestart":"2025-09-26 00:00","name":"Vimmerby HC ( vs. Almtuna IS)","startdate":"2025-09-25 22:30","stopdate":"2025-09-26 02:30","required":"2","active":0},"36":{"pid":11,"gamestart":"2025-10-01 19:00","name":"Vimmerby HC ( vs. BIK Karlskoga)","startdate":"2025-10-01 17:30","stopdate":"2025-10-01 21:30","required":"2","active":0},"47":{"pid":11,"gamestart":"2025-10-08 19:00","name":"Vimmerby HC ( vs. IF Bj\u00f6rkl\u00f6ven)","startdate":"2025-10-08 17:30","stopdate":"2025-10-08 21:30","required":"2","active":0},"57":{"pid":11,"gamestart":"2025-10-10 19:00","name":"Vimmerby HC ( vs. Nybro Vikings IF)","startdate":"2025-10-10 17:30","stopdate":"2025-10-10 21:30","required":"2","active":0},"66":{"pid":11,"gamestart":"2025-10-15 19:00","name":"Vimmerby HC ( vs. S\u00f6dert\u00e4lje SK)","startdate":"2025-10-15 17:30","stopdate":"2025-10-15 21:30","required":"2","active":0},"94":{"pid":11,"gamestart":"2025-10-29 19:00","name":"Vimmerby HC ( vs. Mora IK)","startdate":"2025-10-29 17:30","stopdate":"2025-10-29 21:30","required":"2","active":0},"124":{"pid":11,"gamestart":"2025-11-16 14:00","name":"Vimmerby HC ( vs. IK Oskarshamn)","startdate":"2025-11-16 12:30","stopdate":"2025-11-16 16:30","required":"2","active":0},"134":{"pid":11,"gamestart":"2025-11-19 19:00","name":"Vimmerby HC ( vs. \u00d6stersunds IK)","startdate":"2025-11-19 17:30","stopdate":"2025-11-19 21:30","required":"2","active":0},"157":{"pid":11,"gamestart":"2025-11-28 00:00","name":"Vimmerby HC ( vs. V\u00e4ster\u00e5s IK)","startdate":"2025-11-27 22:30","stopdate":"2025-11-28 02:30","required":"2","active":0},"171":{"pid":11,"gamestart":"2025-12-05 19:00","name":"Vimmerby HC ( vs. MoDo Hockey)","startdate":"2025-12-05 17:30","stopdate":"2025-12-05 21:30","required":"2","active":0},"192":{"pid":11,"gamestart":"2025-12-12 19:00","name":"Vimmerby HC ( vs. Mora IK)","startdate":"2025-12-12 17:30","stopdate":"2025-12-12 21:30","required":"2","active":0},"205":{"pid":11,"gamestart":"2025-12-19 00:00","name":"Vimmerby HC ( vs. Kalmar HC)","startdate":"2025-12-18 22:30","stopdate":"2025-12-19 02:30","required":"2","active":0},"220":{"pid":11,"gamestart":"2025-12-29 19:00","name":"Vimmerby HC ( vs. IF Troja)","startdate":"2025-12-29 17:30","stopdate":"2025-12-29 21:30","required":"2","active":0},"222":{"pid":11,"gamestart":"2026-01-02 19:00","name":"Vimmerby HC ( vs. AIK)","startdate":"2026-01-02 17:30","stopdate":"2026-01-02 21:30","required":"2","active":0},"234":{"pid":11,"gamestart":"2026-01-05 00:00","name":"Vimmerby HC ( vs. Kalmar HC)","startdate":"2026-01-04 22:30","stopdate":"2026-01-05 02:30","required":"2","active":0},"241":{"pid":11,"gamestart":"2026-01-07 19:00","name":"Vimmerby HC ( vs. MoDo Hockey)","startdate":"2026-01-07 17:30","stopdate":"2026-01-07 21:30","required":"2","active":0},"248":{"pid":11,"gamestart":"2026-01-09 19:00","name":"Vimmerby HC ( vs. Nybro Vikings IF)","startdate":"2026-01-09 17:30","stopdate":"2026-01-09 21:30","required":"2","active":0},"257":{"pid":11,"gamestart":"2026-01-16 18:00","name":"Vimmerby HC ( vs. V\u00e4ster\u00e5s IK)","startdate":"2026-01-16 16:30","stopdate":"2026-01-16 20:30","required":"2","active":0},"279":{"pid":11,"gamestart":"2026-01-23 00:00","name":"Vimmerby HC ( vs. Almtuna IS)","startdate":"2026-01-22 22:30","stopdate":"2026-01-23 02:30","required":"2","active":0},"281":{"pid":11,"gamestart":"2026-01-24 18:00","name":"Vimmerby HC ( vs. IF Bj\u00f6rkl\u00f6ven)","startdate":"2026-01-24 16:30","stopdate":"2026-01-24 20:30","required":"2","active":0},"310":{"pid":11,"gamestart":"2026-02-06 19:00","name":"Vimmerby HC ( vs. IK Oskarshamn)","startdate":"2026-02-06 17:30","stopdate":"2026-02-06 21:30","required":"2","active":0},"329":{"pid":11,"gamestart":"2026-02-23 19:00","name":"Vimmerby HC ( vs. BIK Karlskoga)","startdate":"2026-02-23 17:30","stopdate":"2026-02-23 21:30","required":"2","active":0},"336":{"pid":11,"gamestart":"2026-02-25 19:00","name":"Vimmerby HC ( vs. \u00d6stersunds IK)","startdate":"2026-02-25 17:30","stopdate":"2026-02-25 21:30","required":"2","active":0},"351":{"pid":11,"gamestart":"2026-03-01 16:30","name":"Vimmerby HC ( vs. AIK)","startdate":"2026-03-01 15:00","stopdate":"2026-03-01 19:00","required":"2","active":0},"357":{"pid":11,"gamestart":"2026-03-04 19:00","name":"Vimmerby HC ( vs. S\u00f6dert\u00e4lje SK)","startdate":"2026-03-04 17:30","stopdate":"2026-03-04 21:30","required":"2","active":0}}',
      true
    );
    print "<form action=\"#\" method=\"post\" name=\"changeWWW\" id=\"changeWWW\">\n";
    print "<input type=\"hidden\" id=\"status\" name=\"status\" value=\"upgrade\">\n";
    print "<input type=\"text\" id=\"grund\" name=\"grund\" value=\"2\">\n";
    print "<input type=\"text\" id=\"pass_start\" name=\"pass_start\" value=\"-1.5\">\n";
    print "<input type=\"text\" id=\"pass_slut\" name=\"pass_slut\" value=\"2.5\">\n";
    
    // print "<div class='grid-row'>";
    // foreach ($_POST as $key => $value) {
    //   if($key == 'status' || $key == 'submit' || $key == 'url') continue; // || $key == 'team'
    //   // print "<div>";
    //   // print "<label>$key</label>\n";
    //   print "<input type=\"hidden\" name=\"$key\" value=\"" . $value . "\">\n";
    //   // print "</div>"; 
    // }
    // print "</div>"; 

    print "<style>\n";
    print "
    .grid-row {
      display: grid;
      grid-template-columns: repeat(9, 1fr);
    }
    .grid-row div {
      width: unset !important;
    }
    .error_on_input {
      background-color:red;
    }
    
    ";
    print "</style>\n";
    print "<h2>Här kan du ändra manuellt!</h2>\n";
    print "<p>Det räcker att ändra matchens starttid så räknar den ut det själv sedan. Första kryssrutan är att du ändrat, den andra är att du valt att ta bort den raden.</p>\n";
    print "<hr><a href='#' class='changeNow'>byt</a>\n"; 
    foreach ($teams as $k => $v) {
        print "<div class='grid-row'>";
        print "<label><input type=\"checkbox\" name=\"[$k][edited]\" id=\"post_{$k}_edited\" data-id=\"{$k}\" value=\"1\"> Ändrad</label>\n";
        print "<label><input type=\"checkbox\" name=\"[$k][delete]\" id=\"post_{$k}_delete\" data-id=\"{$k}\" value=\"1\"> Ignorera</label>\n";
        if (isset($v['error']) && $v['error']) {
          $error_class ="error_on_input";
        } else {
          $error_class ="";
        }
        foreach ($v as $sk => $sv) {
          if ($sk == 'error') continue;
          print "<div>";
          // if (isset($v['error']) && $v['error']) {
          //   $starttime_class ="starttime_on_input";
          // } else {
          //   $starttime_class ="";
          // }
          unset($teams[$k]['error']);
          print "<input type=\"text\" name=\"[$k][$sk]\" id=\"post_{$k}_{$sk}\" data-id=\"{$k}\" class=\"{$error_class} clsFrm{$sk}\" value=\"" . $sv . "\">\n";
          print "</div>";
        }
        print "</div>";
    }
    // print "<pre>";
    // foreach ($teams as $k => $v) {
    //   print "INSERT INTO mt_events SET pid=" . $v[8] . ", name='" . $v[3] . " ( vs. " . $v[4] . ")', startdate='" . $v[1] . " " . $v[9] . ":00', stopdate='" . $v[1] . " " . $v[10] . ":00', required=" . $v[5] . ", active=" . $v[12] . ";\n";
    // }
    // print "</pre>";
    print "<textarea id=\"data\" name=\"data\" rows=\"4\" cols=\"50\" style=\"display:block; height:20rem;\">";
    print json_encode($teams);
    print "</textarea>\n";
    print "<input type=\"submit\" value=\"Ändra följande\" name=\"submit\">\n";
    print "</form>\n";
  }

  function updateDatabase() {
    $data = input::_post('data');
    $arrData = json_decode($data, true);
    $db = db::getInstance();
    // pre($arrData);
    $bg_color = "blue";
    print "<table>";
    foreach ($arrData as $v) {
      print "<tr>";
      $v['companyid'] = 1;
      // FIX uneeded
      unset($v['gamestart']);
      // pre($v);
      // if (!isset($v[12])) $v[12] = 0;

      // if ($db->o_insert('events', array(
      //   'pid' => $v[8],
      //   'name' => $v[3] . " ( vs. " . $v[4] . ")",
      //   'startdate' => $v[1] . " " . $v[9] . ':00',
      //   'stopdate' => $v[1] . " " . $v[10] . ':00',
      //   'required' => $v[5],
      //   'active' => 0,
      //   'companyid' => 1,
      // ))) {
      //   print "OK!: ";
      // } else {
      //   print "ERR: ";
      // }
      // SQL here
      // if (
      // //   $db->o_insert('events', array(
      // //     'pid' => $v[8],
      // //     'name' => $v[3] . " ( vs. " . $v[4] . ")",
      // //     'startdate' => $v[1] . " " . $v[9] . ':00',
      // //     'stopdate' => $v[1] . " " . $v[10] . ':00',
      // //     'required' => $v[5],
      // //     'active' => $v[12],
      // //     'companyid' => 1,
      // //   ))
      if ($db->o_insert('events',$v)) {
        $bg_color = "green";
      } else {
        $bg_color = "red";
      }
      // pre($v);
      print "<td style='width:30px; background-color: {$bg_color};'>&nbsp;&nbsp;</td>";
      print "<td style='padding-left:10px;'>";
      // print "INSERT INTO mt_events SET pid=" . $v[8] . ", name='" . $v[3] . " ( vs. " . $v[4] . ")', startdate='" . $v[1] . " " . $v[9] . ":00', stopdate='" . $v[1] . " " . $v[10] . ":00', required=" . $v[5] . ", active=" . $v[12] . "; ";
      print "INSERT INTO mt_events SET pid=" . $v['pid'] . ", name='" . $v['name'] . "', startdate='" . $v['startdate'] . ":00', stopdate='" . $v['stopdate'] . ":00', required=" . $v['required'] . ", active=" . $v['active'] . ";\n";
      print "</td>";
      print "</tr>";
      // print "<br>";
      // die('Test only');
    }
    print "</table>";
    // pre($arrData);
  }


  function upgradeData() {


    // // BUILD FORM HERE
    // print "<form action=\"#\" method=\"post\" name=\"fromWWW\">\n";
    // print "<input type=\"hidden\" name=\"status\" value=\"update\">\n";
    // // print "<label for=\"data\">Pass Slut:</label>\n";
    // print "<textarea id=\"data\" name=\"data\" rows=\"4\" cols=\"50\" style=\"display:none;\">";
    // print json_encode($teams);
    // print "</textarea>\n";
    // print "<br><br>\n";
    // // print "<input type=\"text\" name=\"data\" id=\"data\" value='" . json_encode($teams) . "'><br>\n";
    // print "<input type=\"submit\" value=\"Kontrollerade och godkända för att läggas till\" name=\"submit\">\n";
    // print "</form>\n";
  }


  function getFromJson() {
    /**
     * https://www.hockeyallsvenskan.se/api/sports-v2/game-schedule?seasonUuid=xs4m9qupsi&seriesUuid=qQ9-594cW8OWD&gameTypeUuid=qQ9-af37Ti40B&gamePlace=all&played=all
     */
    $path = ROOTPATH . $GLOBALS['CONFIG']['uploadDir'] ?? null;
    if (is_null($path)) return;
    $dta = file_get_contents("https://www.hockeyallsvenskan.se/api/sports-v2/game-schedule?seasonUuid=xs4m9qupsi&seriesUuid=qQ9-594cW8OWD&gameTypeUuid=qQ9-af37Ti40B&gamePlace=all&played=all");
    $arrTeams = json_decode($dta,true);
    // $arrTeams = json_decode(file_get_contents($path."hockeyallsvenskan2025.json"),true);
    print "<table>";
    $allowedTeams = ['IKO', 'KHC', 'VH'];
    foreach ($arrTeams['gameInfo'] as $key => $v) {
      print "<!-- " . $v['homeTeamInfo']['names']['code'] . " -->\n";
      // if ($v['homeTeamInfo']['names']['code'] == "VH") { // IKO KHC VH
      if (in_array($v['homeTeamInfo']['names']['code'],$allowedTeams)) {
        print "<tr>";
        print "<td>";
        print (substr($v['startDateTime'],11,2) == "00") ? "X" : "";
        print "</td>";
        print "<td>" . $v['startDateTime'] . "</td>";
        print "<td>" . $v['homeTeamInfo']['names']['short'] . " ( vs. " . $v['awayTeamInfo']['names']['short'] .")</td>";
        // print "<td>" . $v['awayTeamInfo']['names']['short'] . "</td>";
        // pre($v);
        print "</tr>";
      }
      # code...
    }
    print "</table>";
    // pre($arrTeams);
  }


  function uploadFromURLAndSetData() {
    // debug();
    $arrErrors = [];
    // print "[START]";
    $team_id = input::_post('team','INTNOZERO'); //"Västervik";
    $team_name = $this->teams[$team_id];
    $grund = input::_post('grund');
    $pass_start = input::_post('pass_start');
    $pass_slut = input::_post('pass_slut');
    $activate = input::_post('activate', 'INT') ?? 0;
    // var_dump($activate);


    $plugin = PLUGINPATH . "simple_html_dom/simple_html_dom.php";
    include($plugin);
    $url = file_get_contents(input::_post('url'));
    $html = str_get_html($url);
    // $url = __DIR__ . '/tmp/vik_2024.html';
    // $html = file_get_html($url);


    // pre($html);


    $dta = [];
    $date = NULL;
    foreach ($html->find('.tblContent') as $article) { //#RefreshContent
      foreach ($article->find('tr') as $row) {
        $tmpDta = [];
        foreach ($row->find('td') as $v) {
          $tmpDta[] = strip_tags($v);
        }
        $dta[] = $tmpDta;
      }
    }

    // Tmpstring
    // $tmpString = "PL. 2 HockeyAllsvenskan";
    // foreach ($dta as $key => $value) {
    //   if (isset($value[2]) && str_starts_with($value[2],$tmpString)) {
    //     $dta[$key][2] = "Kalmar HC - (kvartsfinal)"; //str_replace($tmpString,"Kalmar HC", $value);
    //     pre($value);
    //   } else {
    //     unset($dta[$key]);
    //   }
    // }


    // Tmp namn här!
    //2 = PL. 2 HockeyAllsvenskan
    // pre($dta);
    // die();
    $errorExists = false;

    foreach ($dta as $k => $v) {
      if (count($v) <= 1) {
        unset($dta[$k]);
        continue;
      }
      // var_dump($v[0]);
      // pre($v);
      $type = $this->checkStringType($v[0]);
      if ($type == 'Integer' || $type == 'None') {
        // Playout 2024
        // ONLY 6 items
        // Move teamnames to 3rd
        // $dta[$k][3] = $v[2];
        $v[3] = $v[2];
        // Split date and time
        $v[1] = str_replace("\xC2\xA0", ' ', html_entity_decode($v[1]));
        list($date, $v[2]) = explode(" ", $v[1]);
        // Set time
        // print $v[2];
        $dta[$k][2] = $v[2];
          // pre($v[2]);
      } else {
        // Hockeyallsvenskan 2024
        // Fix date AND SET
        if (strlen(trim($v[0])) > 7) {
          $date = trim($v[0]);
        }
      }
      // pre($date);
      // pre($dta[$k][2]);
      $dta[$k][21] = "";
      // FIX..
      if ($dta[$k][2] == "00:00") {
        $dta[$k][2] = "19:00";
        $dta[$k][20] = "00:00";
        $dta[$k][21] = "Tid prel.";
        // print "FAIL!";
      }
      // Set date
      $dta[$k][0] = $date; // OK
      $dta[$k][1] = $date; // OK
      // Split teams
      $t = explode('-', str_replace(array("  ", "&nbsp;", "Final"), array('', '', ''), $v[3]));
      // pre($t);
      $pattern = '~^(\xC2\xA0|&nbsp;)*(.*?)(\xC2\xA0|&nbsp;)*$~';
      $dta[$k][3] = trim(preg_replace($pattern, '$2', $t[0])); // OK
      $dta[$k][4] = trim(preg_replace($pattern, '$2', $t[1])); // OK
      // Fixa kval och liknande saker
      $dta[$k][4] = preg_replace('/Åttonde.*/', '', $dta[$k][4]);
      $dta[$k][4] = preg_replace('/Kvarts.*/', '', $dta[$k][4]);
      $dta[$k][4] = preg_replace('/Semifinal.*/', '', $dta[$k][4]);
      $dta[$k][4] = preg_replace('/Final.*/', '', $dta[$k][4]);
      // print $dta[$k][4];
      // $dta[$k][3] = trim($t[0]); // OK
      // $dta[$k][4] = trim($t[1]); // OK
      // var_dump(trim($dta[$k][3]));
      // $before = ($dta[$k][3]); //html_entity_decode
      // $after = preg_replace($pattern, '$2', $before);
      // pre("##" . htmlentities($dta[$k][3]) . "##");
      // (var_dump($after));

      if (!str_starts_with(trim($t[0]), $team_name)) {
        unset($dta[$k]);
        continue;
      }
      // FIX STARTTIME
      $debug_start = $pass_start * 3600;
      $time_remove = - (trim($pass_start) * 3600);
      $tmp_start = strtotime($date . " " . $dta[$k][2]); //$v[2]
      $dta[$k][9] = date("H:i", $tmp_start - $time_remove);
      // FIX ENDTIME
      $time_add = (trim($pass_slut) * 3600);
      $dta[$k][10] = date("H:i", $tmp_start + $time_add);
      // BUG DATE Variables
      // print "<table><tr>";
      // print "<td>{$pass_start}</td>";
      // print "<td>{$pass_slut}</td>";
      // print "<td>{$date}</td>";
      // print "<td>{$v[2]}</td>";
      // print "<td>" . $tmp_start . "</td>";
      // print "<td>" . $time_remove . "</td>";
      // print "<td>" . $debug_start . "</td>";
      // print "<td>" . date("Y-m-d H:i", $tmp_start - $time_remove) . "</td>";
      // print "<td>" . $dta[$k][9] . "</td>";
      // print "<td>" . $time_add . "</td>";
      // print "<td>" . date("Y-m-d H:i", $tmp_start + $time_add) . "</td>";
      // print "<td>" . $dta[$k][10] . "</td>";
      // print "</tr></table>\n";





      // pre($tmp_start + $time_add);

      // GRUND
      $dta[$k][5] = $grund; // OK
      // Set TEAM ID
      $dta[$k][8] = $team_id; // OK
      // ACtivated
      $dta[$k][12] = $activate; // OK
      // New variables
      $dta[$k]['pid'] = $team_id;
      $dta[$k]['gamestart'] = date("Y-m-d", $tmp_start). " " . $dta[$k][2];
      $dta[$k]['name'] = $dta[$k][3] . " ( vs. " . $dta[$k][4] . ") " . $dta[$k][21];
      $dta[$k]['startdate'] = date("Y-m-d H:i", $tmp_start - $time_remove);
      $dta[$k]['stopdate'] = date("Y-m-d H:i", $tmp_start + $time_add);
      $dta[$k]['required'] = $grund;
      $dta[$k]['active'] = $activate;

      // ERRORS
      if ($dta[$k][2] == "00:00" || isset($dta[$k][20])) {
        // $dta[$k]['error'] = true;
        $errorExists = true;
        $arrErrors[$k]=1;
      // } else {
        // $dta[$k]['error'] = false;
      }

      // Remove garbage
      unset($dta[$k][6]);
      unset($dta[$k][21]);
      unset($dta[$k][20]);
      for ($i=0; $i < 13; $i++) { 
        if (isset($dta[$k][$i])) unset($dta[$k][$i]);
      }

      // pre($dta[$k]);

    }

    if ($errorExists) {
          print "<p class='error' style='font-size:14px;'>Det förekommer fel i data från servern. Några starttider är inte satta än! Dessa är markerade nedan.</p>";
      // print setMsg('e','Fel förekommer i data från servern. Några starttider är inte satta än!');
    }
    $teams = $dta;
    // $teams = array_filter($dta, function ($v, $k) use ($team_name) {
    //   return str_starts_with($v[3], $team_name);
    // }, ARRAY_FILTER_USE_BOTH);

    // pre($teams);
    print "<h4>Följande anrop kommer att göras!</h4>";
    if (1==2) {
      print "<form action=\"#\" method=\"post\" name=\"changeWWW\">\n";
      print "<input type=\"hidden\" name=\"status\" value=\"upgrade\">\n";
      // print "<div class='grid-row'>";
      foreach ($_POST as $key => $value) {
        if($key == 'status' || $key == 'submit' || $key == 'url') continue; // || $key == 'team'
        // print "<div>";
        // print "<label>$key</label>\n";
        print "<input type=\"hidden\" name=\"$key\" value=\"" . $value . "\">\n";
        // print "</div>"; 
      }
      // print "</div>"; 



      print "<style>\n";
      print "
      .grid-row {
        display: grid;
        grid-template-columns: repeat(13, 1fr);
      }
      .grid-row div {
        width: unset !important;
      }
      .error_on_input {
        background-color:red;
      }
      
      ";
      print "</style>\n";
      print "<h2>Kryssa i de du har ändrat!</h2>\n";
      print "<p>Det räcker att ändra matchens starttid så räknar den ut det själv sedan. Första kryssrutan är att du ändrat, den andra är att du valt att ta bort den raden.</p>\n";
      // print "<pre>";
      foreach ($teams as $k => $v) {
        // if(isSuperUser()) {
          print "<div class='grid-row'>";
          // print "<div>";
          print "<label><input type=\"checkbox\" name=\"post[$k]['edited']\" value=\"1\"> Ändra</label>\n";
          // print "\n";
          // print "<label>Ändrad</label>\n";
          // print "<input type=\"checkbox\" name=\"post[$k]['edited']\" value=\"1\">\n";
          // print "</div>";         
          // print "<div>";
          print "<label><input type=\"checkbox\" name=\"post[$k]['delete']\" value=\"1\"> Ignorera</label>\n";
          // print "\n";
          // print "<label>Raderas</label>\n";
          // print "<input type=\"checkbox\" name=\"post[$k]['delete']\" value=\"1\">\n";
          // print "</div>"; 
          if ($v['error']) {
            $error_class ="error_on_input";
          } else {
            $error_class ="";
          }
          foreach ($v as $sk => $sv) {
            if ($sk == 'error') continue;
            print "<div>";
            if ($v['error']) {
              $starttime_class ="starttime_on_input";
            } else {
              $starttime_class ="";
            }
            unset($teams[$k]['error']);
            // pre($sv);
            // pre(gettype($sv));

            print "<input type=\"text\" name=\"post[$k][$sk]\" id=\"post_{$k}_{$sk}\"class=\"{$error_class}\" value=\"" . $sv . "\">\n";
            print "</div>";
          }
          // if (!isset($v['error'])) {
          //   print "<div>";
          //   // print "<input type=\"text\" name=\"post[$k][$v]\" value=\"{$sv}\">\n";
          //   print "</div>";
          // }


          print "</div>";
        // } else {
        //   // if (!isset($v['error'])) {
        //     print "INSERT INTO mt_events SET pid=" . $v[8] . ", name='" . $v[3] . " ( vs. " . $v[4] . ")', startdate='" . $v[1] . " " . $v[9] . ":00', stopdate='" . $v[1] . " " . $v[10] . ":00', required=" . $v[5] . ", active=" . $v[12] . ";\n";
        //   // } else {
        //   //   print "Set new starttime!\n";
        //   // }
        // }



        // print " <br>";
      }
      // print "<pre>";
      // foreach ($teams as $k => $v) {
      //   print "INSERT INTO mt_events SET pid=" . $v[8] . ", name='" . $v[3] . " ( vs. " . $v[4] . ")', startdate='" . $v[1] . " " . $v[9] . ":00', stopdate='" . $v[1] . " " . $v[10] . ":00', required=" . $v[5] . ", active=" . $v[12] . ";\n";
      // }
      // print "</pre>";
      print "<textarea id=\"data\" name=\"data\" rows=\"4\" cols=\"50\" style=\"display:block; height:20rem;\">";
      print json_encode($teams);
      print "</textarea>\n";
      print "<input type=\"submit\" value=\"Ändra följande\" name=\"submit\">\n";
      print "</form>\n";
    }
    // Build a preform here that kan check all data and 
      print "<pre>";
      foreach ($teams as $k => $v) {
        // print "INSERT INTO mt_events SET pid=" . $v[8] . ", name='" . $v[3] . " ( vs. " . $v[4] . ")', startdate='" . $v[1] . " " . $v[9] . ":00', stopdate='" . $v[1] . " " . $v[10] . ":00', required=" . $v[5] . ", active=" . $v[12] . ";\n";
        if (isset($arrErrors[$k])) {
          print "<span style=\"color:red;font-weight:bolder;\">===&gt;</span> ";
        } else {
          // print "<span> ";
        }
        print "INSERT INTO mt_events SET pid=" . $v['pid'] . ", name='" . $v['name'] . "', startdate='" . $v['startdate'] . ":00', stopdate='" . $v['stopdate'] . ":00', required=" . $v['required'] . ", active=" . $v['active'] . ";\n";
      }
      print "</pre>";    
    // BUILD FORM HERE
    print "<form action=\"#\" method=\"post\" name=\"fromWWW\">\n";
    print "<input type=\"hidden\" name=\"status\" value=\"update\">\n";
    // print "<label for=\"data\">Pass Slut:</label>\n";
    print "<textarea id=\"data\" name=\"data\" rows=\"4\" cols=\"50\" style=\"display:block; height:12rem;\">";
    print json_encode($teams);
    print "</textarea>\n";
    print "<br><br>\n";
    // print "<input type=\"text\" name=\"data\" id=\"data\" value='" . json_encode($teams) . "'><br>\n";
    print "<input type=\"submit\" value=\"Kontrollerade och godkända för att läggas till\" name=\"submit\">\n";
    print "</form>\n";

    //
    pre($teams);

  }


  function uploadFileAndSetData() {
    $target_file = ROOTPATH . "uploads/" . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    print "<h3>Upload mode</h3>";
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
      $db = db::getInstance();
      $arrCust = $db->o_get_options("customers", "SELECT id,name FROM [table] WHERE deleted=0");

      echo "The file " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " has been uploaded.";
      // Go and create
      $data = mb_convert_encoding(file_get_contents($target_file), "UTF-8", "Windows-1252"); // ansi western 1252
      // Import file to array
      $arrTmp = explode("\n", $data);
      foreach ($arrTmp as $v) {
        $aT = explode(";", $v);
        if (!isset($aT[2])) continue;
        // Fix start and end times
        // print $aT[2];
        // print "<br>";
        // print
        $time = strtotime($aT[2]);
        // print "<br>";
        // var_dump($aT[6]);
        // print
        $start = floatval(str_replace(",", ".", $aT[6])) * 60; // In minutes
        // print "<br>";
        // print
        $end = floatval(str_replace(",", ".", $aT[7])) * 60; // In minutes
        // print "<br>";
        // print
        $aT[] = date("H:i", strtotime($start . ' minutes', $time));
        // print "<br>";
        // print
        $aT[] = date("H:i", strtotime($end . ' minutes', $time));
        // print "<hr>";
        $arrData[] = $aT;
      }
      //


      // Ask for OK
      print "<table border=1>";
      print "<tr>";
      // print "<th>Veckodag</th>";
      print "<th>Datum</th>";
      print "<th>Matchstart</th>";
      print "<th>Hemmalag</th>";
      print "<th>Bortalag</th>";
      print "<th>Antal vakter</th>";
      print "<th>Pass start</th>";
      print "<th>Pass slut</th>";
      print "<th>Kund</th>";
      print "</tr>";
      foreach ($arrData as $v) {
        print "<tr>";
        print "<td>" . $v[1] . "</td>";
        print "<td>" . $v[2] . "</td>";
        print "<td>" . $v[3] . "</td>";
        print "<td>" . $v[4] . "</td>";
        print "<td>" . $v[5] . "</td>";
        print "<td>" . $v[9] . "</td>";
        print "<td>" . $v[10] . "</td>";
        print "<td>" . $arrCust[intval($v[8])] . "</td>";
        print "</tr>";
      }
      print "</table>";
      print "<form action=\"#\" method=\"post\" enctype=\"multipart/form-data\">\n";
      print "  <input type=\"hidden\" name=\"status\" value=\"update\">\n";
      // print "  Välj spelschema att ladda upp:\n";
      print "  <input type=\"text\" name=\"data\" value='" . json_encode($arrData) . "'>\n";
      // print "  <input type=\"file\" name=\"fileToUpload\" id=\"fileToUpload\">\n";
      print "  <input type=\"submit\" value=\"Lägg till\" name=\"submit\">\n";
      print "</form>\n";

      // print "<pre>";
      // print_r($arrData);
      // print "</pre>";
    } else {
      echo "Sorry, there was an error uploading your file.";
    }
  }

  function uploadFileForm() {
    print "<form action=\"#\" method=\"post\" enctype=\"multipart/form-data\" name=\"fromFile\">\n";
    print "  <input type=\"hidden\" name=\"status\" value=\"upload\">\n";
    print "  Välj spelschema att ladda upp:\n";
    print "<p>Filen skall vara i *.csv med följande syntax</p>";
    print "<p>Fredag;2024-09-20;18:00;IK Oskarshamn;Djurgården Hockey;4;-1,5;2,5;49</p>";
    print "<p>
      <ul style='list-style-type: none;margin-left: 15px;'>
        <li>- Veckodag (kan lämnas tom)<li>
        <li>- Datum<li>
        <li>- Matchstart<li>
        <li>- Hemmalag<li>
        <li>- Bortalag<li>
        <li>- Antal vakter<li>
        <li>- Pass start (-1,5)<li>
        <li>- Pass slut (2,5)<li>
        <li>- Kund ID<li>
      </ul>
    </p>";
    print "  <input type=\"file\" name=\"fileToUpload\" id=\"fileToUpload\">\n";
    print "  <input type=\"submit\" value=\"Ladda upp\" name=\"submit\">\n";
    print "</form>\n";
    // $this->fixvvik_flashscore();
  }

  function getFromWWW() {
    print "<form action=\"#\" method=\"post\" name=\"fromWWW\">\n";
    print "<input type=\"hidden\" name=\"status\" value=\"retrive\">\n";
    print "<label for=\"team\">Välj lag</label>\n";
    // $teams
    // print "<script>\n";
    // print "</script>\n";
    print "<select name=\"team\" id=\"team\">\n";
    print "<option value=\"\">-- Välj --</option>\n";
    foreach ($this->teams as $team_id => $team_name) {
      print "<option value=\"{$team_id}\">{$team_name}</option>\n";
    }
    print "</select><br>\n";
    
    ?>
    <script>
    $("#team").on("change", function() {
      var team = $(this).val();
      if (team == "") {
        $(this).val($.data(this, 'current'));
        return;
      }
      var teaminfo = JSON.parse('<?php print json_encode($this->settings); ?>');
      var s = teaminfo[team];
      // console.log(
      //   'triggered',
      //   team,
      //   teaminfo[team]
      //   );
      $("#grund").val(s['grund']);
      $("#pass_start").val(s['pass_start']);
      $("#pass_slut").val(s['pass_slut']);
      $.data(this, 'current', team);
    });
    </script>
    <style>
      .testbtn {
        background-color: var(--btn-bg-color);
        color: var(--btn-color);
        padding: 5px;
        
        font-weight: 300;
        font-size: 1.35em;
        letter-spacing: 0.05em;
        cursor: pointer;
        border: none;
        border-radius: 4px;
        margin-bottom: 1rem;
        color: inherit;
        text-decoration: none;
        text-align: center;
        align-self: center; 
       
      }

    </style>
    <?php



    // https://stats.swehockey.se/ScheduleAndResults/Schedule/18266
    print "<label for=\"url\">  Välj spelschema att hämta: <a href=\"https://stats.swehockey.se/\" target=\"_blank\">Klicka här för att gå till https://stats.swehockey.se/ (Öppnas i ny flik)</a><br><table><tr><td><a href=\"#\" class='testbtn' onclick=\"document.getElementById('url').value = 'https://stats.swehockey.se/ScheduleAndResults/Schedule/18266';\">HA Grund 2025</a></td></tr></table></label>\n";
    print "<input type=\"text\" name=\"url\" id=\"url\" value=\"\"><br>\n";
    print "<label for=\"grund\">Grundbemanning:</label>\n";
    print "<input type=\"text\" name=\"grund\" id=\"grund\" value='2'><br>\n";

    print "<label for=\"pass_start\">Pass Start:</label>\n";
    print "<input type=\"text\" name=\"pass_start\" id=\"pass_start\" value='-1.5'><br>\n";

    print "<label for=\"pass_slut\">Pass Slut:</label>\n";
    print "<input type=\"text\" name=\"pass_slut\" id=\"pass_slut\" value='2.5'><br>\n";

    print "<label for=\"activate\">Aktivera</label>\n";
    print "<input type=\"checkbox\" name=\"activate\" id=\"activate\" value='1'><br>\n";

    print "<input type=\"submit\" value=\"Ladda upp\" name=\"submit\">\n";
    print "</form>\n";
  }


  function checkStringType($input) {
    $input = trim($input);
    // Check if the string is a time in HH:MM format
    if (preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $input)) {
      return "Time";
    }

    // Check if the string is a valid date
    $date = DateTime::createFromFormat('Y-m-d', $input);
    if ($date && $date->format('Y-m-d') === $input) {
      return "Date";
    }

    // Check if the string is an integer
    if (is_numeric($input) && (int)$input == $input) {
      return "Integer";
    }

    // If neither, return None
    return "None";
  }
}



// Example usage
// $inputStrings = ['2023-10-15', '14:30', 'invalid', '23:59', '2024-02-29', '123', '45.67'];

// foreach ($inputStrings as $input) {
//   echo "Input: $input - Type: " . checkStringType($input) . "<br>";
// }
