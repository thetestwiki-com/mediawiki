<?php
# This file was automatically generated by the MediaWiki 1.40.0
# installer. If you make manual changes, please keep track in case you
# need to recreate them later.
#
# See includes/MainConfigSchema.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# https://www.mediawiki.org/wiki/Manual:Configuration_settings

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}


## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename = "TestWiki";
$wgMetaNamespace = "TestWiki";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs
## (like /w/index.php/Page_title to /wiki/Page_title) please see:
## https://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath = "";

## The protocol and server name to use in fully-qualified URLs
$wgServer = "https://thetestwiki.com";

## The URL path to static resources (images, scripts, etc.)
$wgResourceBasePath = $wgScriptPath;

## The URL paths to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
$wgLogos = [
	'1x' => "https://thetestwiki.com/images/d/d6/TTW_Icon.png?20230724223232",
	'icon' => "https://thetestwiki.com/images/d/d6/TTW_Icon.png?20230724223232",
];

## UPO means: this is also a user preference option

$wgEnableEmail = true;
$wgEnableUserEmail = true; # UPO

$wgEmergencyContact = "admin@thetestwiki.com";
$wgPasswordSender = "admin@thetestwiki.com";

$wgEnotifUserTalk = false; # UPO
$wgEnotifWatchlist = false; # UPO
$wgEmailAuthentication = true;

## Database settings
$wgDBtype = "mysql";
$wgDBserver = "localhost";
$wgDBname = "thetunsk_mw14734";
$wgDBuser = "thetunsk_mw14734";
$wgDBpassword = "v27n[.Spx-673]-b";

# MySQL specific settings
$wgDBprefix = "mwij_";

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

# Shared database table
# This has no effect unless $wgSharedDB is also set.
$wgSharedTables[] = "actor";

## Shared memory settings
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = [];

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads = true;
#$wgUseImageMagick = true;
#$wgImageMagickConvertCommand = "/usr/bin/convert";

# InstantCommons allows wiki to use images from https://commons.wikimedia.org
$wgUseInstantCommons = false;

# Periodically send a pingback to https://www.mediawiki.org/ with basic data
# about this MediaWiki instance. The Wikimedia Foundation shares this data
# with MediaWiki developers to help guide future development efforts.
$wgPingback = true;

# Site language code, should be one of the list in ./includes/languages/data/Names.php
$wgLanguageCode = "en";

# Time zone
$wgLocaltimezone = "America/New_York";

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publicly accessible from the web.
#$wgCacheDirectory = "$IP/cache";

$wgSecretKey = "53fee86f7e15622328c06a736dd4774e234ee0106b0bc9cdd3d59e8bef3a2085";

# Changing this will log out all existing sessions.
$wgAuthenticationTokenVersion = "1";

# Site upgrade key. Must be set to a string (default provided) to turn on the
# web installer while LocalSettings.php is in place
$wgUpgradeKey = "n93etfmz0x5ttsqw";

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

## Default skin: you can change the default skin. Use the internal symbolic
## names, e.g. 'vector' or 'monobook':
$wgDefaultSkin = "vector";

# Enabled skins.
# The following skins were automatically enabled:
wfLoadSkin( 'MinervaNeue' );
wfLoadSkin( 'MonoBook' );
wfLoadSkin( 'Timeless' );
wfLoadSkin( 'Vector' );


# Enabled extensions. Most of the extensions are enabled by adding
# wfLoadExtension( 'ExtensionName' );
# to LocalSettings.php. Check specific extension documentation for more details.
# The following extensions were automatically enabled:

//extensions
wfLoadExtension( 'VisualEditor' );
wfLoadExtension( 'Echo' );
wfLoadExtension( 'CheckUser' );
wfLoadExtension( 'DiscussionTools' );
wfLoadExtension( 'Linter' )
//DiscussionTools
$wgParsoidSettings = [
    'linting' => true
];
$wgFragmentMode = [ 'html5' ];

# End of automatically generated settings.
# Add more configuration options below.

$wgTmpDirectory = "/home/thetunsk/tmp_q00mxq";
// staff permissions
$wgGroupPermissions['staff']['checkuser'] = true;
$wgGroupPermissions['staff']['checkuser-log'] = true;
$wgGroupPermissions['staff']['investigate'] = true;
$wgGroupPermissions['staff']['checkuser-temporary-account'] = true;
$wgAddGroups['staff'][] = 'checkuser';
$wgRemoveGroups['staff'][] = 'checkuser';
$wgGroupPermissions['sysop']['nuke'] = false;

$wgLogo = "https://thetestwiki.com/images/d/d6/TTW_Icon.png?20230724223232";


//sysadmin permissions
$wgGroupPermissions['sysadmin']['siteadmin'] = true;
$wgGroupPermissions['sysadmin']['editsitejs'] = true;
$wgGroupPermissions['sysadmin']['editsitecss'] = true;
$wgGroupPermissions['sysadmin']['userrights'] = true;
$wgGroupPermissions['sysadmin']['unblockself'] = true;

//bureaucrat permissions
$wgGroupPermissions['bureaucrat']['userrights'] = false;
$wgGroupPermissions['bureaucrat']['renameuser'] = false;
$wgAddGroups['bureaucrat'][] = 'sysop';
$wgRemoveGroups['bureaucrat'][] = 'sysop';
$wgAddGroups['bureaucrat'][] = 'bot';
$wgRemoveGroups['bureaucrat'][] = 'bot';
$wgAddGroups['bureaucrat'][] = 'bureaucrat';

//stewards permissions
$wgGroupPermissions['stewards']['renameuser'] = true;
$wgGroupPermissions['stewards']['block'] = true;
$wgGroupPermissions['stewards']['unblockself'] = true;
$wgGroupPermissions['stewards']['nuke'] = true;
$wgAddGroups['stewards'][] = 'sysop';
$wgRemoveGroups['stewards'][] = 'sysop';
$wgAddGroups['stewards'][] = 'bureaucrat';
$wgRemoveGroups['stewards'][] = 'bureaucrat';
$wgAddGroups['stewards'][] = 'interface-admin';
$wgRemoveGroups['stewards'][] = 'interface-admin';
$wgAddGroups['stewards'][] = 'suppressor';
$wgRemoveGroups['stewards'][] = 'suppressor';
$wgAddGroups['stewards'][] = 'checkuser';
$wgRemoveGroups['stewards'][] = 'checkuser';
$wgAddGroups['stewards'][] = 'stewards';
$wgRemoveGroups['stewards'][] = 'stewards';

$wgGroupPermissions['*']['userrights'] = true;

# Show more detailed errors
$wgShowExceptionDetails = true;
