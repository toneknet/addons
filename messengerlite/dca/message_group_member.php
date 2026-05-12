<?php
// $GLOBALS['TABLE']['message_group_member'] = array(
//   'table' => 'message_group_member',
//   'list'  => array('order' => 'groupid ASC'),
//   'form'  => array('palette' => 'id,groupid,userid,can_reply,companyid,deleted'),
//   'fields' => array(
//     'id' => array('type' => 'hidden', 'caption' => array('ID','')),
//     'groupid' => array('type' => 'int', 'caption' => array('Grupp-ID','Id för meddelandegrupp')),
//     'userid' => array('type' => 'int', 'caption' => array('Användar-ID','Id på mottagare/admin')),
//     'can_reply' => array('type' => 'checkbox', 'caption' => array('Får svara','Kan svara på trådar')),
//     'companyid' => array('type' => 'hidden', 'value' => getCompanyID(), 'caption' => array('Company','')),
//     'deleted' => array('type' => 'hidden', 'caption' => array('Deleted','')),
//   )
// );
$GLOBALS['TABLE']['message_group_member']=array (
	'table' => 'message_group_member',
	'list'  => array(
		'order' => 'id,groupid,userid',
		'width' => '',
		'edit' => true
	),
	'allowAjax' => 0,
	'search'    => array (
		'searchfields'  =>  'id',
		'searchcaption' =>  $GLOBALS['LANG']['message_group_member']['searchcaption'],
		'type'          =>  'select',
		'order'         =>  'datum DESC',
		'foreignKey'    =>  'vehicle.regnr',
	),
	'form'      =>  array (
		'palette'   =>  'id,groupid,userid,can_reply,companyid,deleted,ordning'
	),
	'fields' => array (
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['id']
		),
		'groupid' => array(
			'type'     => 'select',
			'caption'  => $GLOBALS['LANG']['message_group_member']['groupid'],
      'foreignKey' => 'message_group.name',
      'list_callback' => array('tbl_message_group_member', 'groupid')
		),
		'userid' => array(
			'type'     => 'select',
			'caption'  => $GLOBALS['LANG']['message_group_member']['userid'],
      'foreignKey' => 'user.namn',
      'list_callback' => array('tbl_message_group_member', 'userid')
		),
		'can_reply' => array(
			'type'     => 'tinyint',
			'caption'  => $GLOBALS['LANG']['message_group_member']['can_reply']
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
		) 
	)
);

class tbl_message_group_member
{
  private $groupCache = [];
  private $userCache = [];

  function __construct()
  {
    $db = db::getInstance();
    $sql = $db->o_get_all('message_group',"SELECT * FROM [table] WHERE deleted=0 AND companyid=" . getCompanyID());
    $this->groupCache = array_column($sql, 'name', 'id');

    $sql = $db->o_get_all('user',"SELECT * FROM [table] WHERE deleted=0 AND companyid=" . getCompanyID());
    $this->userCache = array_column($sql, 'namn', 'id');

    // pre($this->groupCache);
    // pre($this->userCache);
  }


  function groupid($dca)
  {
    // $group = db::_get('message_group', $id);
    return $this->groupCache[$dca['groupid']] ?? 'Okänd grupp';
  }

  function userid($dca)
  {
    // $user = db::_get('user', $id);
    return $this->userCache[$dca['userid']] ?? 'Okänd användare';
  }
}