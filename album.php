<?php
/** 
 * DropPHP sample
 *
 * http://fabi.me/en/php-projects/dropphp-dropbox-api-client/
 *
 * @author     Fabian Schlieper <fabian@fabi.me>
 * @copyright  Fabian Schlieper 2012
 * @version    1.1
 * @license    See license.txt
 *
 */
 
 // display all errors on the browser
error_reporting(E_ALL);
ini_set('display_errors','On');

 // if there are many files in your Dropbox it can take some time, so disable the max. execution time
set_time_limit(0);

require_once("DropboxClient.php");

// you have to create an app at https://www.dropbox.com/developers/apps and enter details below:
$dropbox = new DropboxClient(array(
	'app_key' => "fyqvlfnhu7ebneh",      // Put your Dropbox API key here
	'app_secret' => "3pbbwb3lavd0etb",   // Put your Dropbox API secret here
	'app_full_access' => false,
),'en');
// first try to load existing access token
$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
	//echo "loaded access token:";
	//print_r($access_token);
}
elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found!');
	
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}
// checks if access token is required
if(!$dropbox->IsAuthorized())
{
	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}
echo "<pre>";

$files = $dropbox->GetFiles("",false); 

?>

<html>
<body>
<center>
<form enctype="multipart/form-data" action="album.php" method="POST">
Upload Image : <input name="userfile" type="file" /><br/>
<input type="submit" value="Upload" name="upload"/>
</form>
</center>

<?php
if(isset($_POST['upload'])&& $_FILES['userfile'][ 'name']!="")
    {
		//echo "hi";
		$file = $_FILES['userfile'][ 'name'];
		$dropbox->UploadFile($_FILES['userfile']['tmp_name'],$file);
	    header("Location:album.php");
	}
?>
<form action="album.php" method="GET" >
<table>
<?php if(!empty($files)) {
        //$file = reset($files);
	echo "List of images";
    echo "<br>";
	foreach($files as $key=>$val){?>
	<tr>
	<td><?php echo $key ?></td>
	<td><a href=<?php print 'album.php?imagedisplay='.$key; ?>><?php print $key;?></a></td>
	<td>
	<button type="submit" name="delete"  value="<?php print  $key;?>" method="GET" formaction="album.php?delete=<?php print  $key?>">Delete</button>
	</td>
	</tr>
<?php
	 }
}?>
</table>
</form>
</body>
</html>

<?php
 if(isset($_GET['imagedisplay']))
    {
	  $test_file = "download_".basename($_GET['imagedisplay']);
	  echo "\r\n\r\n <img src='".$dropbox->GetLink($_GET['imagedisplay'],false)."'  />\r\n";
	  $dropbox->DownloadFile($_GET['imagedisplay'], $test_file);
	}

 if(isset($_GET['delete']))
    {
	$dropbox->Delete($_GET['delete']);
	 header("Location:album.php");
	}

	
function store_token($token, $name)
{
	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
}

function load_token($name)
{
	if(!file_exists("tokens/$name.token")) return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}

function delete_token($name)
{
	@unlink("tokens/$name.token");
}





function enable_implicit_flush()
{
	@apache_setenv('no-gzip', 1);
	@ini_set('zlib.output_compression', 0);
	@ini_set('implicit_flush', 1);
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	echo "<!-- ".str_repeat(' ', 2000)." -->";
}?>