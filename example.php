<?php
    
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    set_time_limit ( 0 );
    
    echo '<link rel="stylesheet" type="text/css" href="/twlphp/css/normalize.css" media="all">';
    echo '<link rel="stylesheet" type="text/css" href="/twlphp/css/twlstyle.css?v=5.5" media="all">';
    //echo '<script type="text/javascript" src="/twlphp/js/jsstuffs.js"></script>';
    
    include($_SERVER['DOCUMENT_ROOT']."/twlphp/config.php");
    
    include($_SERVER['DOCUMENT_ROOT']."/twlphp/functions.php");
    
    $mainlink=mysqlconnect($server,$user,$pass,$db); //Connect to server
    
    function debug_to_console( $data ) {
        $output = $data;
        if ( is_array( $output ) )
            $output = implode( ',', $output);
        
        echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
    }
    
    function gamerslist($mainlink)  {
        
        $query = "
        SELECT
        A.name AS member_name
        ,X.member_id
        ,B.word_default AS member_rank
        , CONCAT(X.game1,',', X.game2, ',', X.game3) AS games
        ,C.st_owned AS steamgames
        FROM
        (SELECT member_id
         ,CASE WHEN field_14 IN (NULL,'NULL','0','','Other','Others','None','Casual','Casual Gaming') THEN 'None' WHEN field_14 IS NULL THEN 'None' ELSE field_14 END AS game1
         ,CASE WHEN field_15 IN (NULL,'NULL','0','','Other','Others','None','Casual','Casual Gaming') THEN 'None' WHEN field_15 IS NULL THEN 'None' ELSE field_15 END AS game2
         ,CASE WHEN field_47 IN (NULL,'NULL','0','','Other','Others','None','Casual','Casual Gaming') THEN 'None' WHEN field_47 IS NULL THEN 'None' ELSE field_47 END AS game3
         FROM dicore_pfields_content
         ) X
        LEFT JOIN dicore_members AS A ON X.member_id = A.member_id
        LEFT JOIN dicore_sys_lang_words AS B ON B.word_key = CONCAT('core_group_',A.member_group_id)
        LEFT JOIN disteam_profiles AS C ON C.st_member_id = A.member_id
        WHERE B.word_default !='Registered Guest' AND B.word_default !='Guests'";
        
        $result = myslquery($query,$mainlink);  //Call custom function
        $dbtemp =array();
        while($row = mysqli_fetch_assoc($result)) {
            $row['orderno'] = recordtoorderno($row);
            $dbtemp[] = $row;
        }
        usort($dbtemp , "compareorderdivis" );
        $database =array();
        foreach ($dbtemp as &$record) {
            $games = explode(",", $record['games']);
            $steamGames = json_decode($record['steamgames'], true);
            foreach($steamGames as $game){
                if($game['name'] == "Tom Clancy's Rainbow Six Siege") $game['name'] = "Rainbow Six Siege";
                array_push($games, $game['name']);
            }
            $games = array_unique($games);
            
            foreach ($games as &$game_name) {
                if ($game_name!='None'){
                    $database[$game_name][] = array(member_id => $record['member_id'], member_name => $record['member_name'], member_rank => $record['member_rank'], orderno => $record['orderno']);
                }
            }
        }
        
        arsort($database);
        
        $output = "";
        $output .= "<div id='Games'><div class='games-container flex-col container-border'>";
        
        foreach ($database as $game_name => &$gamearray) {
            
            $output .= "<div class='game game-".RemoveSpecialChar($game_name)."'><a onclick='toggle_visibility(Array(&quot;#game-".RemoveSpecialChar($game_name)."&quot;))'><h4><button class='game-".RemoveSpecialChar($game_name)."' title='toggle' >&#8921;</button>".$game_name." (".count($gamearray).")</h4></a><div id='game-".RemoveSpecialChar($game_name)."' class='hidden'><ol class='flex-row container-border'>";
            
            foreach ($gamearray as &$record) {
                $output .= "<li class='rank-".RemoveSpecialChar($record['member_rank'])."'><a href='https://di.community/profile/".$record['member_id']."-".strtolower($record['member_name'])."/' target='_blank'><span class='rank-".RemoveSpecialChar($record['member_rank'])."'>".$record['member_name']."</span></a></li>";
            }
            
            $output .= "</ol></div></div>";
            
        }
        
        $output .= "</div></div>";
        
        echo $output;
    }
    
    function recordtoorderno($record)   {
        global $debug, $Divisions, $DivGames, $Ranks, $teams, $tempdivorder, $positions;
        $recordorder = 0;
        //$recordorder += $record['games'] * 100000;
        $recordorder += (((isset($tempdivorder[$record['division']]))?$tempdivorder[$record['division']][0]:99) * 100000);
        $recordorder += (((isset($teams[$record['team']]))?$teams[$record['team']]:99) * 1000);
        $recordorder += (((isset($Ranks[$record['member_rank']]))?$Ranks[$record['member_rank']][0]:999)) ;
        return $recordorder;
    }
    
    
    //$userpref=userinputs();

    echo "<h3>Games owned according to Steam and forum profile</h3><h4>Link your steam profile to the forums by editing your profile and filling in your Steam ID64!</h4>";
    echo rankarray();
    echo "<div id='Gamers'><div class='gamers-container flex-row'>";
    gamerslist($mainlink);
    
    echo '</div></div>';

    mysqlclose($mainlink); //Close Server
    //generatecache($cacheloc, $userpref);
    ?>