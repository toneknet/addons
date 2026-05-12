<?php
// $GLOBALS['TABLE']['message_group'] = array(
//   'table' => 'message_group',
//   'list'  => array('order' => 'name ASC'),
//   'form'  => array('palette' => 'id,name,description,created_by,companyid,deleted'),
//   'fields' => array(
//     'id' => array('type' => 'hidden', 'caption' => array('ID','')),
//     'name' => array('type' => 'varchar', 'caption' => array('Gruppnamn','Namn på gruppen')),
//     'description' => array('type' => 'text', 'caption' => array('Beskrivning','Vad gruppen används för')),
//     'created_by' => array('type' => 'hidden', 'value' => getUserID(), 'caption' => array('Skapad av','')),
//     'companyid' => array('type' => 'hidden', 'value' => getCompanyID(), 'caption' => array('Company','')),
//     'deleted' => array('type' => 'hidden', 'caption' => array('Deleted','')),
//   )
// );
$GLOBALS['TABLE']['message_group']=array (
	'table' => 'message_group',
	'list'  => array(
		'order' => 'name,description,members',
		'width' => '',
		'edit' => true,
  //   'buttons' => [
  //   [
  //     'action' => 'action=edit&subaction=admin',
  //     'label'  => 'Ändra',
  //     'icon'   => 'edit'
  //   ]
  // ]
    'buttons' => array (
      'edit' => array(
        'action' => 'action=edit',
        'param' => [
          'subaction' => 'admin'
        ],
        'icon' => 'edit', 
        'label' => $GLOBALS['LANG']['GENERAL']['edit']
      ),
      'members' => array(
        'action' => 'action=members',
        'param' => [
          'subaction' => 'admin',
          // 'tab' => 'members'
        ],
        'icon' => 'users', 
        'label' => $GLOBALS['LANG']['message_group']['members'][0]
      ),
      'delete' => array(
        'action' => 'action=delete',
        'param' => [
          'subaction' => 'admin'
        ],
        'icon' => 'delete', 
        'label' => $GLOBALS['LANG']['GENERAL']['delete']
      ),
    )
	),
	'allowAjax' => 0,
	'search'    => array (
		'searchfields'  =>  'id',
		'searchcaption' =>  $GLOBALS['LANG']['message_group']['searchcaption'],
		'type'          =>  'select',
		'order'         =>  'datum DESC',
		'foreignKey'    =>  'vehicle.regnr',
	),
	'form'      =>  array (
		'palette'   =>  'id,name,description,created_by,notification'
	),
	'fields' => array (
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['id']
		),
		'name' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['message_group']['name']
		),
		'description' => array(
			'type'     => 'textarea',
			'caption'  => $GLOBALS['LANG']['message_group']['description']
		),
		'created_by' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['message_group']['created_by'],
      'value'    => getUserID()
		),
		'companyid' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['companyid']
		),
		'deleted' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['deleted']
		),
		'ordning' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['ordning']
		),
    'notification' => array(
			'type'     => 'checkbox',
			'caption'  => $GLOBALS['LANG']['message_group']['notification']
    ),
		'members' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['message_group']['members'],
      'list_callback' => array('tbl_message_group', 'members')
		) 
	)
);

class tbl_message_group
{
  private $userCache = [];
  private $db;

  function __construct()
  {
    $this->db = db::getInstance();
    // $sql = $this->db->o_get_all('message_group',"SELECT * FROM [table] WHERE deleted=0 AND companyid=" . getCompanyID());
    // $this->groupCache = array_column($sql, 'name', 'id');

    $sql = $this->db->o_get_all('user',"SELECT * FROM [table] WHERE deleted=0 AND companyid=" . getCompanyID());
    $this->userCache = array_column($sql, 'namn', 'id');
  }
  function members($dca)
  {
    // Get all members of the group
    $members = $this->db->o_get_all('message_group_member', "SELECT id,userid FROM [table] WHERE groupid = {$dca['id']} AND deleted = 0");
    $output = '';
    foreach ($members as $member) {
      $userId = $member['userid'];
      if (strlen($output) > 0) {
        $output .= '<br>';
      }
      $output .= $this->userCache[$userId];
    }
    // pre($members);
    return $output; // Returnerar gruppens ID så att det kan användas i list_callback i message_group_member
    // $members = dbSelect('message_group_member', 'userid', "groupid = {$id} AND deleted = 0");
    // $memberNames = array();
    // foreach ($members as $member) {
    //   $user = dbSelect('user', 'namn', "id = {$member['userid']}");
    //   if ($user) {
    //     $memberNames[] = $user[0]['namn'];
    //   }
    // }
    // return implode(', ', $memberNames);
  }
}