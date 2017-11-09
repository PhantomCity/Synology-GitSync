<?PHP

$SynoGitSync_Profile = [];

$cfgFN = md5($_Server['SCRIPT_NAME'] );
include_once('../configs/' . $cfgFN);  


$payloadRaw = file_get_contents('php://input');
$payload = json_decode($payloadRaw , true);
$PushKey = $_SERVER['HTTP_X_HUB_SIGNATURE'];

function CheckProfileFits($payload, $profileTrigger, $hash)
{
  if (!isset($profileTrigger))
    return false;
  if (!isset($payload))
    return false;
  list($algo, $verify) = explode('=', $hash);
  
  $res = true;
  foreach ($profileTrigger as $k=>$v)
  {
    if ('!Master-KEY' == $k)      $res &= $verify === hash_hmac($algo, $payloadRaw, $v); // Doesn't work. at all :(
    if ('branch' == $k)          $res &= $v === $payload['ref'];
    if ('repo_full_name' == $k)  $res &= $v === $payload['repository']['full_name'];
    if ('authors' == $k)         $res &= in_array($payload['head_commit']['author'], $v);
  }
  return $res;
}


foreach ($SynoGitSync_Profile as $profName => $prof)
if (CheckProfileFits($payload, $prof['On'], $PushKey))
{
  echo "Profile Fits: ".$profName."\n";
  $tempFN = md5(time());
  $tempZip = $tempFN.'.zip';
  
  $projName = $payload['repository']['name'];
  $aBranch = $payload['repository']['master_branch'];
  $GitUrl = $payload['repository']['url'] . '/archive/' . $aBranch . '.zip';

  if (copy($GitUrl, $tempZip))
  {
    echo "Branch ".$GitUrl." downloaded..."."\n";
    echo "ZIP: ".$tempZip."\n";
    
    $zip = new ZipArchive;
    if ($zip->open('./'.$tempZip))
    {
      echo 'Doing update '.$profName ."\n";
      $zip->extractTo($tempFN.'/');
      $zip->close();
      
      $target = $prof['TargetFolder'];
      $TargetDir = $prof['TargetBase'] . $target;
      
      $rnTo = false;
      if (file_exists($TargetDir))
      {
        $rnTo = './' . $target . '-' . time();
        if (isset($prof['Backup']) && is_string($prof['Backup']))
          $rnTo = $prof['Backup'] . $target.'-'. time();
        
        @mkdir($rnTo);
        echo 'renaming to: '.$rnTo ."\n";
        echo 'from: '.$TargetDir ."\n";
        echo rename($TargetDir, $rnTo) ."\n";
      }
      
      echo 'Replacing '.$tempFN ."\n";
      recurse_copy($tempFN.'/'.$projName.'-'.$aBranch, $TargetDir);
      exec('chmod -R -v 755 '.$TargetDir);
      
      //rmdir('./'.$fn);
      exec(sprintf("rm -rf %s", './'.$tempFN));
      echo 'Temporary zip removed '.$tempFN ."\n";
      
      if (isset($prof['Backup']) && is_bool($prof['Backup']) and (!$prof['Backup']) && (false !== $rnTo))
      {
        exec(sprintf("rm -rf %s", $rnTo));
        echo 'Temporary folder removed '.$rnTo ."\n";
      }
    }
    unlink($tempZip);
  }
}



function recurse_copy($src,$dst)
{ 
  $dir = opendir($src); 
  @mkdir($dst);
  while(false !== ( $file = readdir($dir)) )
  {
    if (( $file != '.' ) && ( $file != '..' ))
    {
      if ( is_dir($src . '/' . $file) )
        recurse_copy($src . '/' . $file,$dst . '/' . $file);
      else
        copy($src . '/' . $file,$dst . '/' . $file);
    }
  }
  closedir($dir);
}


?>
