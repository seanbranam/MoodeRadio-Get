<?php


/** YT-Play API
  *
  * Lee Jordan @duracell80
  * 11/26/2019

    item_cast          : http://localhost/yt-play/?type=cast&src=videopage
    playlist_clear     : http://localhost/yt-play/?type=stream&src=0
    playlist_load      : http://localhost/yt-play/?type=stream&src=videopage
    playlist_loadlist  : http://localhost/yt-play/?type=list&src=videoplaylist<PLKK4T0Fm7nwGEZUZtQn7hbciVbN6hkVFl>
    playlist_start     : http://localhost/yt-play/?type=stream&src=1
    playlist_regen     : http://localhost/yt-play/?type=regen&src=_YouTube/asmr/asmr-short-selection.m3u (lists in RADIO directory)
    playlist_vega      : http://localhost/yt-play/?type=regen (without src)


    Download item to /mnt/SDCARD/ as .m4a
    audio_dl           : http://localhost/yt-play/?type=download&src=6LEmDEZVa5g

*/


$cmd        = $_GET["cmd"];
$srcRAW     = $_GET["src"];
$type       = $_GET["type"];


// CHECK SOURCE FOR YOUTUBE ID
$rx = '~
  ^(?:https?://)?                           # Optional protocol
   (?:www[.])?                              # Optional sub-domain
   (?:youtube[.]com/watch[?]v=|youtu[.]be/) # Mandatory domain name (w/ query string in .com)
   ([^&]{11})                               # Video id of 11 characters as capture group 1
    ~x';

$has_match = preg_match($rx, $srcRAW, $matches);

if ($type == "list") {
    $src = $srcRAW;
} else {
    // Allow 2 character codes
    if (strlen($srcRAW) < 3) {
        $src = $srcRAW;
    } else {

        // Double down on youtube check and allow regenerations
        if($has_match == "0"){
            if ($type == "regen") {
                $src = $srcRAW;
            } else {
                echo("Error: Not YouTube URL");
                exit;
            }


        } else {
            $srcSPLIT = explode("v=", $srcRAW);
            $src = $srcSPLIT[1];
        }
    }
}

// DEBUG SECURITY
//echo($src);
//exit;


$apiPath    = "/var/www/yt-play/";
$playPath   = "/var/lib/mpd/playlists/";
$radioPath  = "/var/lib/mpd/music/RADIO/";   



// THE STREAM ENABLER ... Stream YouTube via Playlists!
if(isset($src) && !empty($src)){
    switch ($src) {

        // CLEAR PLAYLIST ?type=stream&src=0
        case "-1":
            shell_exec("sudo " . $apiPath . "playlist_clear.sh");
            echo("YouTube Playlist Cleared");
            break;

        // PLAY PLAYLIST ?type=stream&src=1
        case "1":
            shell_exec("sudo " . $apiPath . "playlist_start.sh");
            echo("YouTube Playlist Started");
            break;

        
        default:
            
            // YT PLAYLIST
            if($type == "list"){
                $runcmd = "sudo " . $apiPath . "playlist_loadlist.sh " . $src;
                shell_exec($runcmd);
                echo("YouTube Playlist Transferred");
                
                break;
                
            // STREAM or CAST    
            } else {
                $runcmd = "sudo " . $apiPath . "playlist_load.sh " . $src;

                if($type == "stream" || $type == "cast"){
                    shell_exec($runcmd);
                    echo("YouTube Playlist Updated");
                }
                // CAST YouTube URL to Moode
                if($type == "cast"){
                    // Clear the YouTube_Play and Moode's playing playlist (YouTube_Load is left alone)
                    shell_exec("sudo " . $apiPath . "playlist_clear.sh");
                    shell_exec("mpc clear");

                    // Regenerate Moode with the Casted Audio
                    shell_exec($runcmd);
                    shell_exec("mpc load YouTube_Play");
                    shell_exec("mpc play");

                    echo(" ... and Stream Playing");
                }
                break;
            }
    }
}











// SOME AWESOME COMMANDS
if(isset($type) && !empty($type)){
    switch ($type) {
        // INSPECT ?type=info&src=videopage
        case "info":
            $runcmd         = "sudo ".$apiPath."yt-info.sh " . $src;
            $json_string    = shell_exec($runcmd);

            header('Content-type: text/javascript');
            echo(pretty_json($json_string));
            break;



        // DOWNLOAD ?type=dl&src=videopage
        case "download":
            if(isset($src) && !empty($src)){
                $runcmd = "sudo ".$apiPath."yt-dl.sh " . $src;
                shell_exec($runcmd);
                echo("YouTube Audio Downloaded"); 
            } else {
                echo("Failed to Download: Video Source Missing"); 
            }
            break;


        // REGENERATE Playlist or Vega
        case "regen":


            if(isset($src) && !empty($src)){
                // Example ?type=regen&src=_Networks/yt/asmr/asmr-short-selection.m3u
                $runcmd = "sudo cp -f ".$radioPath . $src . " " . $playPath . "YouTube_Load.m3u";
                echo("Playlist was Regenerated");

            } else {
                // Example ?type=regen

                $runcmd = "sudo cp -f " . $apiPath . "yt-init.m3u " .$playPath . "YouTube_Load.m3u";
                echo("Suzanne Vega was Regenerated");
            }
            shell_exec($runcmd);
            shell_exec("sudo " . $apiPath . "playlist_regen.sh");
            break;
            
        
        case "stream":

            break;
            
        case "list":

            break;
            
        case "cast":

            break;

        default:

            break;

    }
    
}

















/*  NICE TO HAVE COMMANDS
    mpc status         : http://localhost/yt-play/?cmd=status
    mpc update         : http://localhost/yt-play/?cmd=update
    mpc lsplaylists    : http://localhost/yt-play/?cmd=list
    mpc stop           : http://localhost/yt-play/?cmd=stop
    mpc play           : http://localhost/yt-play/?cmd=play
    mpc pause          : http://localhost/yt-play/?cmd=pause
    mpc prev           : http://localhost/yt-play/?cmd=prev
    mpc next           : http://localhost/yt-play/?cmd=next

    SEEK FORWARD
    fwd15s,30s,60s and 5m
    http://localhost/yt-play/?cmd=fwd30

    SEEK BACK
    bck15s,30s,60s and 5m
    http://localhost/yt-play/?cmd=bck5m
*/



if(isset($cmd) && !empty($cmd)){
    switch ($cmd) {
        
        // UPDATE DATABASE
        case "update":
            $runcmd = "mpc update";
            echo(shell_exec($runcmd));
            break;
            
        // STATUS
        case "status":
            $runcmd = "mpc status";
            echo(shell_exec($runcmd));
            break;
            
        // LIST Playlists
        case "list":
            $runcmd = "mpc lsplaylists";
            $list   = shell_exec($runcmd); 
            $playlists = explode("\n", $list);
                
            header('Content-type: text/javascript');
            echo(pretty_json(json_encode($playlists)."\n"));
            
            break;
            
        // STOP
        case "stop":
            $runcmd = "mpc stop";
            echo(shell_exec($runcmd));
            break;
        
        // PLAY
        case "play":
            $runcmd = "mpc play";
            echo(shell_exec($runcmd));
            break;
            
        // PAUSE
        case "pause":
            $runcmd = "mpc pause-if-playing";
            echo(shell_exec($runcmd));
            break;
            
        // PREV
        case "prev":
            $runcmd = "mpc prev";
            echo(shell_exec($runcmd));
            break;
            
        // NEXT
        case "next":
            $runcmd = "mpc next";
            echo(shell_exec($runcmd));
            break;
            
        // SKIP FORWARD 15s
        case "fwd15":
            $runcmd = "mpc seek +15";
            echo(shell_exec($runcmd));
            break;
        
        // SKIP FORWARD 30s
        case "fwd30":
            $runcmd = "mpc seek +30";
            echo(shell_exec($runcmd));
            break;
            
        // SKIP FORWARD 60s
        case "fwd60":
            $runcmd = "mpc seek +60";
            echo(shell_exec($runcmd));
            break;
            
        // SKIP FORWARD 5m
        case "fwd5m":
            $runcmd = "mpc seek +300";
            echo(shell_exec($runcmd));
            break;
            
        // SKIP BACK 15s
        case "bck15":
            $runcmd = "mpc seek -15";
            echo(shell_exec($runcmd));
            break;
        
        // SKIP BACK 30s
        case "bck30":
            $runcmd = "mpc seek -30";
            echo(shell_exec($runcmd));
            break;
            
        // SKIP BACK 60s
        case "bck60":
            $runcmd = "mpc seek -60";
            echo(shell_exec($runcmd));
            break;
            
        // SKIP BACK 5m
        case "bck5m":
            $runcmd = "mpc seek -300";
            echo(shell_exec($runcmd));
            break;

        
    
            
        default:
            break;
    }
}



/**
 * JSON beautifier
 * @Juan Lago
 */


function pretty_json($json, $ret= "\n", $ind="\t") {

    $beauty_json = '';
    $quote_state = FALSE;
    $level = 0; 

    $json_length = strlen($json);

    for ($i = 0; $i < $json_length; $i++)
    {                               

        $pre = '';
        $suf = '';

        switch ($json[$i])
        {
            case '"':                               
                $quote_state = !$quote_state;                                                           
                break;

            case '[':                                                           
                $level++;               
                break;

            case ']':
                $level--;                   
                $pre = $ret;
                $pre .= str_repeat($ind, $level);       
                break;

            case '{':

                if ($i - 1 >= 0 && $json[$i - 1] != ',')
                {
                    $pre = $ret;
                    $pre .= str_repeat($ind, $level);                       
                }   

                $level++;   
                $suf = $ret;                                                                                                                        
                $suf .= str_repeat($ind, $level);                                                                                                   
                break;

            case ':':
                $suf = ' ';
                break;

            case ',':

                if (!$quote_state)
                {  
                    $suf = $ret;                                                                                                
                    $suf .= str_repeat($ind, $level);
                }
                break;

            case '}':
                $level--;   

            case ']':
                $pre = $ret;
                $pre .= str_repeat($ind, $level);
                break;

        }

        $beauty_json .= $pre.$json[$i].$suf;

    }

    return $beauty_json;

}   

?>
