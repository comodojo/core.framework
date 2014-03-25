<?php

$drop = Array('options','users','users_cache','users_registration','users_recovery','roles','cron','cron_worklog','events','keychains','services','test');

$create = Array(
	'roles'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('reference','INTEGER',Array('unsigned'=>true,'null'=>false,'primary'=>true)),
			Array('description','TEXT',Array('null'=>false))
			)
		),
	'users'	=>	Array(
		'columns'	=>	Array(
			Array('userId','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('userName','STRING',Array('length'=>32,'unique'=>true,'null'=>false)),
			Array('userPass','STRING',Array('length'=>32,'null'=>false)),
			Array('userRole','INTEGER',Array('unsigned'=>true,'null'=>false)),
			Array('enabled','BOOL',Array('default'=>0)),
			Array('ldap','BOOL',Array('default'=>0)),
			Array('rpc','BOOL',Array('default'=>0)),
			Array('completeName','STRING',Array('length'=>32,'default'=>null)),
			Array('gravatar','BOOL',Array('default'=>0)),
			Array('email','STRING',Array('length'=>64,'null'=>false)),
			Array('birthday','DATE',Array('default'=>null)),
			Array('gender','STRING',Array('length'=>1,'default'=>null)),
			Array('url','TEXT',Array('default'=>null)),
			Array('private_identifier','STRING',Array('length'=>128,'null'=>false)),
			Array('public_identifier','STRING',Array('length'=>128,'null'=>false))
			)
		),
	'users_cache'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('userName','STRING',Array('length'=>32,'unique'=>true,'null'=>false)),
			Array('userPass','STRING',Array('length'=>32,'null'=>false)),
			Array('userRole','INTEGER',Array('unsigned'=>true,'null'=>false)),
			Array('ttl','INTEGER',Array('default'=>0)),
			)
		),
	'users_registration'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('timestamp','INTEGER',Array('length'=>64,'null'=>false)),
			Array('userName','STRING',Array('length'=>32,'null'=>false)),
			Array('userPass','STRING',Array('length'=>32,'null'=>false)),
			Array('email','STRING',Array('length'=>32,'null'=>false)),
			Array('completeName','STRING',Array('length'=>32,'null'=>false)),
			Array('birthday','DATE',Array('default'=>null)),
			Array('gender','STRING',Array('length'=>1,'default'=>null)),
			Array('code','STRING',Array('length'=>128,'null'=>false)),
			Array('authorized','BOOL',Array('default'=>0)),
			Array('confirmed','BOOL',Array('default'=>0)),
			Array('expired','BOOL',Array('default'=>0))
			)
		),
	'users_recovery'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('timestamp','INTEGER',Array('length'=>64,'null'=>false)),
			Array('userName','STRING',Array('length'=>32,'null'=>false)),
			Array('email','STRING',Array('length'=>32,'null'=>false)),
			Array('code','STRING',Array('length'=>128,'null'=>false)),
			Array('confirmed','BOOL',Array('default'=>0)),
			Array('expired','BOOL',Array('default'=>0))
			)
		),
	'options'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('option','STRING',Array('length'=>32,'null'=>false)),
			Array('value','TEXT',Array('default'=>null)),
			Array('siteId','STRING',Array('length'=>128,'null'=>false))
			)
		),
	'cron'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('name','STRING',Array('length'=>64,'null'=>false),'primary'=>true),
			Array('job','STRING',Array('length'=>64,'null'=>false)),
			Array('description','TEXT',Array('default'=>null)),
			Array('enabled','BOOL',Array('default'=>0)),
			Array('min','STRING',Array('length'=>16,'default'=>null)),
			Array('hour','STRING',Array('length'=>16,'default'=>null)),
			Array('day_of_month','STRING',Array('length'=>16,'default'=>null)),
			Array('month','STRING',Array('length'=>16,'default'=>null)),
			Array('day_of_week','STRING',Array('length'=>16,'default'=>null)),
			Array('year','STRING',Array('length'=>16,'default'=>null)),
			Array('params','TEXT',Array('default'=>null)),
			Array('last_run','INTEGER',Array('length'=>64,'default'=>null))
			)
		),
	'cron_worklog'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('pid','INTEGER',Array('unsigned'=>true,'default'=>null)),
			Array('name','STRING',Array('length'=>64,'null'=>false)),
			Array('job','STRING',Array('length'=>64,'null'=>false)),
			Array('status','STRING',Array('length'=>12,'null'=>false)),
			Array('success','BOOL',Array('default'=>0)),
			Array('result','TEXT',Array('default'=>null)),
			Array('start','STRING',Array('length'=>64,'null'=>false)),
			Array('end','STRING',Array('length'=>64,'default'=>null))
			)
		),
	'events'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('type','TEXT',Array('null'=>false)),
			Array('referTo','TEXT',Array('default'=>null)),
			Array('success','BOOL',Array('default'=>1)),
			//Array('timestamp','INTEGER',Array('length'=>64,'null'=>false)),
			Array('date','DATE',Array('null'=>false)),
			Array('time','TIME',Array('null'=>false)),
			Array('userName','STRING',Array('length'=>32,'null'=>false)),
			Array('host','STRING',Array('length'=>32,'default'=>null)),
			Array('userAgent','TEXT',Array('default'=>null)),
			Array('browser','TEXT',Array('default'=>null)),
			Array('OS','TEXT',Array('default'=>null)),
			Array('sessionId','TEXT',Array('default'=>null)),
			)
		),
	'keychains'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('account_name','STRING',Array('length'=>64,'null'=>false)),
			Array('description','TEXT',Array('default'=>null)),
			Array('keyUser','TEXT',Array('null'=>false)),
			Array('keyPass','TEXT',Array('null'=>false)),
			Array('type','STRING',Array('length'=>256,'default'=>null)),
			Array('name','STRING',Array('length'=>256,'default'=>null)),
			Array('host','STRING',Array('length'=>256,'default'=>null)),
			Array('port','STRING',Array('length'=>256,'default'=>null)),
			Array('model','STRING',Array('length'=>256,'default'=>null)),
			Array('prefix','STRING',Array('length'=>256,'default'=>null)),
			Array('custom','STRING',Array('length'=>256,'default'=>null)),
			Array('keychain','STRING',Array('length'=>256,'null'=>false))
			)
		),
	'services'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('name','STRING',Array('length'=>64,'null'=>false)),
			Array('description','TEXT',Array('default'=>null)),
			Array('enabled','BOOL',Array('default'=>0)),
			Array('encoding','STRING',Array('length'=>32,'default'=>null)),
			Array('cache','STRING',Array('length'=>12,'default'=>null)),
			Array('ttl','INTEGER',Array('length'=>64,'default'=>0)),
			Array('transport','STRING',Array('length'=>12,'default'=>null)),
			Array('required_parameters','TEXT',Array('default'=>null)),
			Array('database','STRING',Array('length'=>32,'default'=>null))
			)
		),
	'test'	=>	Array(
		'columns'	=>	Array(
			Array('id','INTEGER',Array('unsigned'=>true,'null'=>false,'autoincrement'=>true,'primary'=>true)),
			Array('name','STRING',Array('length'=>64,'null'=>false)),
			Array('description','TEXT',Array('default'=>null)),
			Array('pattern','TEXT',Array('default'=>null)),
			Array('content','TEXT',Array('default'=>null)),
			Array('timestamp','INTEGER',Array('length'=>64,'null'=>false)),
			Array('date','DATE',Array('default'=>null)),
			Array('userName','STRING',Array('length'=>32,'null'=>false)),
			Array('rating','INTEGER',Array('default'=>0)),
			Array('refer','INTEGER',Array('default'=>0)),
			Array('type','STRING',Array('length'=>32,'default'=>null))
			)
		)
	);

$fill = Array(
	Array('roles',Array(0, 1, "Administrator")),
	Array('roles',Array(0, 100, "PowerUser")),
	Array('roles',Array(0, 1000, "User")),
	Array('users',Array(0, $_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_USER'], MD5($_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_PASSWORD']),
		1, 1, 0, 0, $_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_USER'], 0, $_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['ADMIN_MAIL'],
		$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_DATE'], "M", $_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['SITE_URL'],
		random(),random())),
	
	Array('test',Array(0,'test', 'this is a test','TEST','foo boo',strtotime('now'),'10-10-2013','admin','5','false','TEST_TYPE_1')),
	Array('test',Array(0,'test1','this is a test','TEST','foo boo',strtotime('now'),'10-10-2013','admin','4','false','TEST_TYPE_1')),
	Array('test',Array(0,'test2','this is a test','TEST','foo boo',strtotime('now'),'10-10-2013','admin','3','false','TEST_TYPE_1')),
	Array('test',Array(0,'test3','this is a test','TEST','foo boo',strtotime('now'),'10-10-2013','admin','2','false','TEST_TYPE_2')),
	Array('test',Array(0,'test4','this is a test','TEST','foo boo',strtotime('now'),'10-10-2013','admin','1','false','TEST_TYPE_2'))
	);

foreach($_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values'] as $key=>$value) {
	if (
		$key == 'UNIQUE_IDENTIFIER' OR
		$key == 'PUBLIC_IDENTIFIER' OR
		$key == 'SESSION_IDENTIFIER' OR
		$key == 'CONFIGURATION_FOLDER' OR
		$key == 'APPLICATION_FOLDER' OR
		$key == 'HOME_FOLDER' OR
		$key == 'USERS_FOLDER' OR
		$key == 'TEMP_FOLDER' OR
		$key == 'FILESTORE_FOLDER' OR
		$key == 'CACHE_FOLDER' OR
		$key == 'THUMBNAILS_FOLDER' OR
		$key == 'SERVICE_FOLDER' OR
		$key == 'CRON_FOLDER' OR
		$key == 'DB_HOST' OR
		$key == 'DB_PORT' OR
		$key == 'DB_NAME' OR
		$key == 'DB_USER' OR
		$key == 'DB_PASSWORD' OR
		$key == 'DB_PREFIX' OR
		$key == 'DB_DATA_MODEL' OR
		$key == 'STARTUP_CACHE_ENABLED' OR
		$key == 'GLOBAL_DEBUG_ENABLED' OR
		$key == 'GLOBAL_DEBUG_LEVEL' OR
		$key == 'ADMIN_USER' OR
		$key == 'ADMIN_MAIL' OR
		$key == 'ADMIN_PASSWORD'
	) continue;
	
	if ( ($key == "SITE_TAGS") OR ($key == "BOOTSTRAP") ){
		array_push($fill, Array('options',Array(0,$key,array2json($value),$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['UNIQUE_IDENTIFIER'])));
	} else {
		array_push($fill,Array('options',Array(0,$key,$value,$_SESSION[SITE_UNIQUE_IDENTIFIER]['installer_values']['UNIQUE_IDENTIFIER'])));
	}
}

?>