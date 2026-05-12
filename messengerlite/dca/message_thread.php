<?PHP
$GLOBALS['TABLE']['message_thread']=array (
	'table' => 'message_thread',
	'list'  => array(
		'order' => '',
		'width' => '',
		'edit' => false
	),
	'allowAjax' => 0,
	'search'    => array (
		'searchfields'  =>  'id',
		'searchcaption' =>  $GLOBALS['LANG']['message_thread']['searchcaption'],
		'type'          =>  'select',
		'order'         =>  'datum DESC',
		'foreignKey'    =>  'vehicle.regnr',
	),
	'form'      =>  array (
		'palette'   =>  'id,groupid,user_sender_id,subject,status,created,updated,companyid,deleted,ordning'
	),
	'fields' => array (
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['id']
		),
		'groupid' => array(
			'type'     => 'int',
			'caption'  => $GLOBALS['LANG']['message_thread']['groupid']
		),
		'user_sender_id' => array(
			'type'     => 'int',
			'caption'  => $GLOBALS['LANG']['message_thread']['user_sender_id']
		),
		'subject' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['message_thread']['subject']
		),
		'status' => array(
			'type'     => 'varchar',
			'caption'  => $GLOBALS['LANG']['message_thread']['status']
		),
		'created' => array(
			'type'     => 'datetime',
			'caption'  => $GLOBALS['LANG']['message_thread']['created']
		),
		'updated' => array(
			'type'     => 'datetime',
			'caption'  => $GLOBALS['LANG']['message_thread']['updated']
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