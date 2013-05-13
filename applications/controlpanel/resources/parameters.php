<?php

/*
$controlpanel_kernelRequiredParameters = Array(
	"get_state"				=>	Array("what"),
	"set_options"			=>	Array("siteTitle","siteDescription","siteAuthor","creationDate","locale","statistics"),
	"set_appearance"		=>	Array("dojoTheme","siteTheme"),
	"set_meta"				=>	Array("metaTags"),
	"set_smtp"				=>	Array("smtpServer","smtpPort","smtpIsAuthenticated","smtpUser","smtpPassword","smtpAddress","smtpService","smtpSecurity"),
	"set_ldap"				=>	Array("ldapServer","ldapPort","dc","cn","listerUserName","listerUserPassword","compatibleMode","ldapDefaultFilter"),
	"set_authentication"	=>	Array("authenticationMode","authenticateSession","cacheExternalUsers","externalAuthenticatorServer","externalAuthenticatorMode","externalAuthenticatorKey"),
	"set_authenticator"		=>	Array("enableAuthenticator","authenticatorMode","authenticatorKey"),
	"set_registration"		=>	Array("registrationMode","registrationAuthorization"),
	"set_advanced"			=>	Array("siteUrl","sitePath","siteExternalUrl","privateMode","dojoBaseUrl"),
	"set_crossDomain"		=>	Array("xdLoading","xdTimeout","xdList"),
	"set_requires"			=>	Array("dojoRequires"),
	"set_debug"				=>	Array("dojoDebug","dojoDebugPopup","dojoDebugDeep"),
	"set_maintenance"		=>	Array("suspendedMode","suspendedMessage"),
	"set_bootstrap"			=>	Array("bootstrap")
);
*/

$controlpanel_stateExtendedParams = Array(
	"general"			=>	Array("builder"=>"form",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("siteTitle","siteDescription","siteAuthor","creationDate","locale","statistics")),
	"appearance"		=>	Array("builder"=>"theme",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("dojoTheme","siteTheme")),
	"meta"				=>	Array("builder"=>"meta",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("metaTags")),
	"smtp"				=>	Array("builder"=>"form",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("smtpServer","smtpPort","smtpIsAuthenticated","smtpUser","smtpPassword","smtpAddress","smtpService","smtpSecurity")),
	"ldap"				=>	Array("builder"=>"form",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("ldapServer","ldapPort","dc","cn","listerUserName","listerUserPassword","compatibleMode","ldapDefaultFilter")),
	"authenticator"		=>	Array("builder"=>"form",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("enableAuthenticator","authenticatorMode","authenticatorKey")),
	"debug"				=>	Array("builder"=>"form",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("dojoDebug","dojoDebugPopup","dojoDebugDeep")),
	"authentication"	=>	Array("builder"=>"form",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("authenticationMode","authenticateSession","cacheExternalUsers","externalAuthenticatorServer","externalAuthenticatorMode","externalAuthenticatorKey")),
	"registration"		=>	Array("builder"=>"form",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("registrationMode","registrationAuthorization")),
	"advanced"			=>	Array("builder"=>"form",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("SITE_URL","SITE_PATH","SITE_EXTERNAL_URL","SITE_DEFAULT_CONTAINER","JS_BASE_URL")),
	"crossDomain"		=>	Array("builder"=>"form",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("JS_XD_LOADING","JS_XD_LOCATION","JS_XD_TIMEOUT")),
	"maintenance"		=>	Array("builder"=>"form",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("SITE_SUSPENDED","SITE_SUSPENDED_MESSAGE")),
	"bootstrap"			=>	Array("builder"=>"runlevel","table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("bootstrap")),
	"requires"			=>	Array("builder"=>"require",	"table"=>"options","where"=>array("siteId","=",COMODOJO_UNIQUE_IDENTIFIER),"include"=>Array("dojoRequires"))
);
	
$controlpanel_fieldsCompositionProperties = Array(



	"siteUrl"			=> Array("name" => "siteUrl", "type" => "ValidationTextBox", "labelCode" => '0134', "required" => true, "onClick" => false, "options" => false),
	"sitePath" 			=> Array("name" => "sitePath", "type" => "ValidationTextBox", "labelCode" => '0135', "required" => true, "onClick" => false, "options" => false),
	"siteExternalUrl"	=> Array("name" => "siteExternalUrl", "type" => "TextBox", "labelCode" => '0169', "required" => false, "onClick" => false, "options" => false),
	
	"metaTags" 			=> Array("name" => "metaTags", "type" => "Special", "labelCode" => '0181', "required" => true, "onClick" => false, "options" => false),
	"siteTheme" 		=> Array("name" => "siteTheme", "type" => "Select", "labelCode" => '0137', "required" => true, "onClick" => false, "options" => false),
	"dojoTheme" 		=> Array("name" => "dojoTheme", "type" => "Select", "labelCode" => '0138', "required" => true, "onClick" => false, "options" => Array(Array("name"=>"tundra","value"=>"tundra"),Array("name"=>"soria","value"=>"soria"),Array("name"=>"nihilo","value"=>"nihilo"),Array("name"=>"claro","value"=>"claro"))),
	"dojoRequires" 		=> Array("name" => "dojoRequires", "type" => "Special", "labelCode" => '0152', "required" => true, "onClick" => false, "options" => false),
	"locale"			=> Array("name" => "locale", "type" => "Select", "labelCode" => '0136', "required" => true, "onClick" => false, "options" => Array()),
	"xdLoading" 		=> Array("name" => "xdLoading", "type" => "OnOffSelect", "labelCode" => '0149', "required" => true, "onClick" => false, "options" => false),
	"xdTimeout" 		=> Array("name" => "xdTimeout", "type" => "ValidationTextBox", "labelCode" => '0150', "required" => true, "onClick" => false, "options" => false),
	"xdList" 			=> Array("name" => "xdList", "type" => "Select", "labelCode" => '0151', "required" => true, "onClick" => false, "options" => false),
	"dojoDebug" 		=> Array("name" => "dojoDebug", "type" => "OnOffSelect", "labelCode" => '0139', "required" => true, "onClick" => false, "options" => false),
	"dojoDebugPopup" 	=> Array("name" => "dojoDebugPopup", "type" => "OnOffSelect", "labelCode" => '0140', "required" => true, "onClick" => false, "options" => false),
	"dojoDebugDeep" 	=> Array("name" => "dojoDebugDeep", "type" => "OnOffSelect", "labelCode" => '0141', "required" => true, "onClick" => false, "options" => false),
	"dojoBaseUrl" 		=> Array("name" => "dojoBaseUrl", "type" => "TextBox", "labelCode" => '0142', "required" => false, "onClick" => false, "options" => false),
	"privateMode" 		=> Array("name" => "privateMode", "type" => "OnOffSelect", "labelCode" => '0143', "required" => true, "onClick" => false, "options" => false),
	"suspendedMode" 	=> Array("name" => "suspendedMode", "type" => "OnOffSelect", "labelCode" => '0144', "required" => true, "onClick" => false, "options" => false),
	"suspendedMessage" 	=> Array("name" => "suspendedMessage", "type" => "SmallEditor", "labelCode" => '0145', "required" => true, "onClick" => false, "options" => false),
	"cachingTime" 		=> Array("name" => "cachingTime", "type" => "ValidationTextBox", "labelCode" => '0146', "required" => true, "onClick" => false, "options" => false),
	"forceNoCache" 		=> Array("name" => "forceNoCache", "type" => "OnOffSelect", "labelCode" => '0147', "required" => true, "onClick" => false, "options" => false),
	"statistics" 		=> Array("name" => "statistics", "type" => "OnOffSelect", "labelCode" => '0148', "required" => true, "onClick" => false, "options" => false),
	"authenticationMode"			=> Array("name" => "authenticationMode", "type" => "Select", "labelCode" => '0153', "required" => true, "onClick" => false, "options" => Array(Array("name"=>"Local","value"=>0),Array("name"=>"LDAP - filtered","value"=>1),Array("name"=>"LDAP - unfiltered","value"=>2),Array("name"=>"External Authenticator","value"=>3))),
	"authenticateSession" 	=> Array("name" => "authenticateSession", "type" => "OnOffSelect", "labelCode" => '0184', "required" => true, "onClick" => false, "options" => false),
	"registrationMode" 	=> Array("name" => "registrationMode", "type" => "OnOffSelect", "labelCode" => '0168', "required" => true, "onClick" => false, "options" => false),
	"registrationAuthorization"	=> Array("name" => "registrationAuthorization", "type" => "OnOffSelect", "labelCode" => '0183', "required" => true, "onClick" => false, "options" => false),
	"cacheExternalUsers"			=> Array("name" => "cacheExternalUsers", "type" => "OnOffSelect", "labelCode" => '0167', "required" => true, "onClick" => false, "options" => false),
	"smtpServer" 		=> Array("name" => "smtpServer", "type" => "TextBox", "labelCode" => '0171', "required" => false, "onClick" => false, "options" => false),
	"smtpPort" 			=> Array("name" => "smtpPort", "type" => "TextBox", "labelCode" => '0172', "required" => false, "onClick" => false, "options" => false),
	"smtpIsAuthenticated"			=> Array("name" => "smtpIsAuthenticated", "type" => "OnOffSelect", "labelCode" => '0173', "required" => true, "onClick" => false, "options" => false),
	"smtpService"			=> Array("name" => "smtpService", "type" => "Select", "labelCode" => '0182', "required" => true, "onClick" => false, "options" => Array(Array("name"=>"SMTP","value"=>0),Array("name"=>"mail","value"=>1),Array("name"=>"SendMail","value"=>2))),
	"smtpSecurity"			=> Array("name" => "smtpSecurity", "type" => "Select", "labelCode" => '0185', "required" => true, "onClick" => false, "options" => Array(Array("name"=>"No","value"=>0),Array("name"=>"ssl","value"=>1),Array("name"=>"tls","value"=>2))),
	"smtpUser"			=> Array("name" => "smtpUser", "type" => "TextBox", "labelCode" => '0174', "required" => false, "onClick" => false, "options" => false),
	"smtpPassword" 		=> Array("name" => "smtpPassword", "type" => "TextBox", "labelCode" => '0175', "required" => false, "onClick" => false, "options" => false),
	"smtpAddress"		=> Array("name" => "smtpAddress", "type" => "TextBox", "labelCode" => '0176', "required" => false, "onClick" => false, "options" => false),
	"ldapServer" 		=> Array("name" => "ldapServer", "type" => "TextBox", "labelCode" => '0154', "required" => false, "onClick" => false, "options" => false),
	"ldapPort" 			=> Array("name" => "ldapPort", "type" => "TextBox", "labelCode" => '0155', "required" => false, "onClick" => false, "options" => false),
	"dc" 				=> Array("name" => "dc", "type" => "TextBox", "labelCode" => '0156', "required" => false, "onClick" => false, "options" => false),
	"cn" 				=> Array("name" => "cn", "type" => "TextBox", "labelCode" => '0157', "required" => false, "onClick" => false, "options" => false),
	"listerUserName" 	=> Array("name" => "listerUserName", "type" => "TextBox", "labelCode" => '0158', "required" => false, "onClick" => false, "options" => false),
	"listerUserPassword"	=> Array("name" => "listerUserPassword", "type" => "PasswordTextBox", "labelCode" => '0159', "required" => false, "onClick" => false, "options" => false),
	"ldapDefaultFilter"		=> Array("name" => "ldapDefaultFilter", "type" => "TextBox", "labelCode" => '0186', "required" => false, "onClick" => false, "options" => false),
	"compatibleMode"	=> Array("name" => "compatibleMode", "type" => "OnOffSelect", "labelCode" => '0160', "required" => true, "onClick" => false, "options" => false),
	"externalAuthenticatorServer"	=> Array("name" => "externalAuthenticatorServer", "type" => "TextBox", "labelCode" => '0164', "required" => false, "onClick" => false, "options" => false),
	"externalAuthenticatorMode"		=> Array("name" => "externalAuthenticatorMode", "type" => "Select", "labelCode" => '0165', "required" => false, "onClick" => false, "options" => Array(Array("name"=>"Plain","value"=>0),Array("name"=>"Shared Key","value"=>1))),
	"externalAuthenticatorKey"		=> Array("name" => "externalAuthenticatorKey", "type" => "TextBox", "labelCode" => '0166', "required" => false, "onClick" => false, "options" => false),
	"enableAuthenticator"			=> Array("name" => "enableAuthenticator", "type" => "OnOffSelect", "labelCode" => '0161', "required" => true, "onClick" => false, "options" => false),
	"authenticatorMode"	=> Array("name" => "authenticatorMode", "type" => "Select", "labelCode" => '0162', "required" => true, "onClick" => false, "options" => Array(Array("name"=>"Plain","value"=>0),Array("name"=>"Shared Key","value"=>1))),
	"authenticatorKey"	=> Array("name" => "authenticatorKey", "type" => "TextBox", "labelCode" => '0163', "required" => false, "onClick" => false, "options" => false),
	"bootstrap"			=> Array("name" => "bootstrap", "type" => "Special", "labelCode" => '0170', "required" => true, "onClick" => false, "options" => false)
);

/*
+------------------------------+
| option                       |
+------------------------------+


| 



|                   |
|                   |

|                |
|                 |
|              |
|   |




| BOOTSTRAP                    |
+------------------------------+
*/

?>