<?php
/**
 * WordPress command-line / web-based installer 
 * You just pass the parameters to the database and the installer will download the latest version
 * of WordPress, add these parameters and add the salt parameters
 * It automatically creates a new "wordpress" directory in the same directory as the installer
 * 
 * @package: WordPressInstaller
 * @author: Brunomag Concept SRL
 * @autor-uri: www.brunomag.ro
 */

$typeOfPHPExecution = php_sapi_name();

//check to see if it is a command line execution
if ($typeOfPHPExecution == 'cli'){

    if (count($argv) == 1)
           die('Run the installer in the the directory in which you wish to install Wordpress. 
         Usage: installer.php db_name db_user db_pass db_host
         ');
    

    $hostname = addslashes($argv[4]);
    $dbname = addslashes($argv[1]);
    $dbuser = addslashes($argv[2]);
    $dbpass = addslashes($argv[3]);

}
else {
        $hostname = addslashes($_REQUEST['hostname']);
        $dbname = addslashes($_REQUEST['dbname']);
        $dbuser = addslashes($_REQUEST['dbuser']);
        $dbpass = addslashes($_REQUEST['dbpass']);
}

//Verifying for empty values
if (trim($hostname) == ''){
    die("Error: Hostname is empty!");
}
if (trim($dbname) == ''){
    die("Error: DB name is empty!");
}
if (trim($dbuser) == ''){
    die("Error: DB user is empty!");
}
if (trim($dbpass) == ''){
    die("Error: DB password is empty!");
}


$zipUrl = 'http://wordpress.org/latest.zip';
$archiveFileName = "latest.zip";

$filePointer = fopen($archiveFileName, "w");
$downloadedZipFileContents = file_get_contents($zipUrl);
fwrite($filePointer, $downloadedZipFileContents);
fclose($filePointer);


$path = pathinfo(realpath(__FILE__), PATHINFO_DIRNAME);
$zip = new ZipArchive();
$res = $zip->open($archiveFileName);

if ($res === TRUE) {
  // extract it to the path we determined above
  $zip->extractTo($path);
  $zip->close();
  echo "$archiveFileName extracted to $path";
} else {
  die("Couldn't open $archiveFileName");
}

unlink($archiveFileName);

chdir("wordpress");


$wpconfig = file("wp-config-sample.php");

//modify wp-config according to the parameters provided

$wpconfigNewFile = '';
foreach ($wpconfig as $lineNumer => $line){
    //search and replace DB_NAME / DB_USER / DB_PASS / DB_HOST
    if (preg_match('/localhost/', $line)){
       $line = str_replace('localhost', $hostname, $line);
    }
    if (preg_match('/database_name_here/', $line)){
       $line = str_replace('database_name_here', $dbname, $line);
    }
    if (preg_match('/username_here/', $line)){
       $line = str_replace('username_here', $dbuser, $line);
    }
    if (preg_match('/password_here/', $line)){
       $line = str_replace('password_here', $dbpass, $line);
    }


    //this is the order of the CONSTANTS
    //we need to have only one #REPLACEME# string in order to
    //replace that with the corresponding salt below

    //matches 2 lines
    if (preg_match('/AUTH_KEY/', $line)){
       $line = "";
    }   

    if (preg_match('/LOGGED_IN_KEY/', $line)){
       $line = "";
    }   

    if (preg_match('/NONCE_KEY/', $line)){
       $line = "";
    }   

    //matches 2 lines
    if (preg_match('/AUTH_SALT/', $line)){
       $line = "";
    }   
    
    if (preg_match('/LOGGED_IN_SALT/', $line)){
       $line = "";
    }   

    if (preg_match('/NONCE_SALT/', $line)){
       $line = "#REPLACEME#";
    }   

    $wpconfigNewFile .= $line;
    

}




//add Salt parameters

$saltUrl = "https://api.wordpress.org/secret-key/1.1/salt/";
// get "salt contents"
$saltContents = file_get_contents($saltUrl);

$wpconfigNewFile = str_replace('#REPLACEME#', $saltContents, $wpconfigNewFile);

//write to file
if (file_exists("wp-config-sample.php")){
    if (!file_exists('wp-config.php')){
       rename("wp-config-sample.php", "wp-config.php");
       $wpconfigResource = fopen("wp-config.php", "w");
       fwrite($wpconfigResource, $wpconfigNewFile);
       fclose($wpconfigResource);

    }
    else die('File: wp-config.php already exists!');
}
else {
    $wpconfigResource = fopen("wp-config.php", "w");
    fwrite($wpconfigResource, $wpconfigNewFile);
    fclose($wpconfigResource);
}

