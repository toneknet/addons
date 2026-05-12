<?php

/*
 *  settingsfilen
 *
 * Module: messengerlite_notification
 */

$GLOBALS['MODULE']['messengerlite_notification']=array (
    // 'table' => true,
    'template'  => false,
    // 'form'  =>  array (
    //   'palette' =>  true,
    // ),
    'fields' => array (
        'messengerlite_target' => array(
			'type'     => 'select',
            'field'     => 'messengerlite_target',
			'caption'  => $GLOBALS['LANG']['messengerlite_notification']['messengerlite_target'],
            'options' => getPageStructure()
            // 'value'    => '',
            // 'foreignKey' => 'page.'
		),

        // 'width' => array(
		// 	'type'     => 'varchar',
        //     'field'     => 'list[width]',
		// 	'caption'  => $GLOBALS['LANG']['LISTANDEDIT']['width'],
        //     'value'    => '',
		// ),
        // 'edit' => array(
		// 	'type'     => 'checkbox',
        //     'field'     => 'list[edit]',
		// 	'caption'  => $GLOBALS['LANG']['LISTANDEDIT']['edit'],
        //     'value'    => '',
		// ),
        // 'where' => array(
		// 	'type'     => 'varchar',
        //     'field'     => 'list[where]',
		// 	'caption'  => $GLOBALS['LANG']['LISTANDEDIT']['where'],
        //     'value'    => '',
		// ),
        // 'allow_add' => array(
		// 	'type'     => 'checkbox',
        //     'field'     => 'list[allow_add]',
		// 	'caption'  => $GLOBALS['LANG']['LISTANDEDIT']['allow_add'],
        //     'value'    => '',
		// ),
        // 'allow_search' => array(
		// 	'type'     => 'checkbox',
        //     'field'     => 'list[allow_search]',
		// 	'caption'  => $GLOBALS['LANG']['LISTANDEDIT']['allow_search'],
        //     'value'    => '',
		// ),
	)
);
