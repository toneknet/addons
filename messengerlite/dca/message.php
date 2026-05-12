<?PHP
$GLOBALS['TABLE']['message']=array (
	'table' => 'message',
	'list'  => array(
		'order' => '',
		'width' => '',
		'edit' => false
	),
	'allowAjax' => 0,
	'search'    => array (
		'searchfields'  =>  'id',
		'searchcaption' =>  $GLOBALS['LANG']['message']['searchcaption'],
		'type'          =>  'select',
		'order'         =>  'datum DESC',
		'foreignKey'    =>  'vehicle.regnr',
	),
	'form'      =>  array (
		'palette'   =>  'id,threadid,sender_userid,message,created,companyid,deleted,ordning'
	),
	'fields' => array (
		'id' => array(
			'type'     => 'hidden',
			'caption'  => $GLOBALS['LANG']['SYSTEM']['id']
		),
		'threadid' => array(
			'type'     => 'int',
			'caption'  => $GLOBALS['LANG']['message']['threadid']
		),
		'sender_userid' => array(
			'type'     => 'int',
			'caption'  => $GLOBALS['LANG']['message']['sender_userid']
		),
		'message' => array(
			'type'     => 'text',
			'caption'  => $GLOBALS['LANG']['message']['message']
		),
		'created' => array(
			'type'     => 'datetime',
			'caption'  => $GLOBALS['LANG']['message']['created']
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