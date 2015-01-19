<?php
//TODO: opwkend1 and 2 are queried like a million times.
//if there is no dates set then no calendar shows
//but opening weekend is optional and nothing should depend on it. --FIXED
$pageAccessLevel = 3;
$toroot = "../../";
require_once $toroot."inc/secure.php";
if (securitycheck($pageAccessLevel, $toroot) != 1) {
  exit;
}
include_once($toroot."inc/sql.php");
include($toroot."inc/timeslots.php");
include_once($toroot."inc/season.php");
$yyyy = getSeason();
include($toroot.'inc/leagueinfo.php'); // creates $leaguename, $leagueabbr, $leagueaddy, $leaguecity, $leaguestate, $leaguezip,$leaguetitle

$level=getUserLevel();
if($level<=3)
	$teamID=myID();
else
{
	$teamID = (!empty($_POST['team'])) ? $_POST['team'] : '';
	$teamID = (!empty($_POST['teamID'])) ? $_POST['teamID'] : $teamID;
	$teamID = (!empty($_GET['team'])) ? $_GET['team'] : $teamID;
	$teamID = (!empty($_GET['teamID'])) ? $_GET['teamID'] : $teamID;
}
$sessionid = session_id();

$db = &sqlconnect();


//DELETE existing team availability for this team
if (isset($_POST['clearschedule'])) 
{
	$sql = "delete from teamscheduledates where teamID=$teamID";
	sqlexecute($db,$sql);
	$sql = "delete from teamseasons where teamID=$teamID";
	sqlexecute($db,$sql);
	$sql = "delete from teamopeningdh where teamID=$teamID";
	sqlexecute($db,$sql);
	$sql = "delete from gamedistrib where teamID=$teamID";
	sqlexecute($db,$sql);
}


//if confirming Team Avail Sched
if( isset($_POST['confirm']) )
{
	$update = buildupdateset('SCHEDteamseas', array('confirm'), array('NOW()'));
	$sql = $update." where teamID=$teamID";
	sqlexecute($db,$sql);
}

if($_GET['view']=='ow')
{
	$update = buildupdateset('teamopeningdh', array('confirm'), array('NOW()'));
	$sql = $update." where teamID=$teamID";
	sqlexecute($db,$sql);	
}

$title='Team Availability Calendar';
	
//make sure settings have been set before allowing calendar view
if($teamID)
{
	$sql='SELECT startdt FROM SCHEDteamseas WHERE teamID='.$teamID;
	$set=sqlgetone($db,$sql);
	if(!$set)
	{
		$host=$_SERVER['HTTP_HOST'];
		header("Location: http://$host/members/team/tainstructions.php?teamID=$teamID");
		exit;
	}
}

$clrs=array('shaded','beige','pink','gray','green','purple','shadeddark','beigedark','pinkdark','graydark','greendark','purpledark');
?>
<!DOCTYPE HTML>
<HTML>
	<head>
		<title><?php echo $title.' - '.$leaguename; ?></title>
<?php
//put scripts here-> head.php include closes head and opens body
 include_once($toroot.'template/head.php'); 

include_once($toroot.'inc/chooseteam.php');
if($error)
	echo $error.'<BR><A HREF="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'">Return to Calendar</A>';
else
	$printscreen=teamSelected($teamID,$level);
if ($printscreen) 
{
	if($level>=5)
		switchLink('Team');
	/*if($dhposted)
	{
		echo '<P>When filling out your team availability calendar, be sure to block out any dates or times where your team will be unable to play because of conflicts with other events such as:</P>
			<OL>
			<LI> Holidays ---> Memorial Day Weekend or the 4th of July
			<LI> School Exams ---> Night before SAT or Finals
			<LI> Other School Events ---> Sports Banquets, Graduation, Dances, Proms, Class Trips.
			<LI> Non-availability of Manager/Coaches ---> Weddings, Baptisms, Planned Trips.
			</OL>
			<H5>PRE-SEASON GAMES (13U and older only)</H5>
			<P>
			Teams at 13U and 14U and some low silver teams at higher ages like to begin play earlier than Memorial Day Weekend. If teams want to start "early" they can indicate so on their team availability calendar and the league will schedule these dates provided there are other teams seeking the same. These games will count as official league games. The league does NOT schedule pre-season or exhibition games. If a team wishes to play such games, it should schedule these games on its own. The website provides a bulletin board for advertising to other teams for extra games (see Tools > Extra Games). CAUTION: When scheduling pre-season games do NOT add them to your online game schedule or they will be counted as official league games.
			</P>

			<FORM ACTION="fcalsched2.php" METHOD="GET">
			<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'">
			<INPUT TYPE="submit" VALUE="CONTINUE">
			</FORM>';
	}*/
	//check if write access to schedules is on
	if($level < 9)
	{
		include_once($toroot.'inc/getwriteflag.php');
		$accesson=getwriteflag('ta');
		if($accesson)
		{
			$sql='SELECT maxage FROM agegroups, teams WHERE agegroups.age=teams.age AND teams.teamID='.$teamID;
			$maxage=sqlgetone($db,$sql);
			$youngaccess=getwriteflag('tay');
			if( $maxage<=12 && !$youngaccess )
				$accesson=false;
		}
	}
	else
		$accesson=true;

	if($accesson)
	{	
		$sql = "select name from teams where teamID=$teamID";
		$name = sqlgetone($db,$sql);

		//check if Opening Weekend is on
		//$owon=getwriteflag('owuse');
		//check if write access to OW is on
		//$owaccess=getwriteflag('ow');
		/*if($owon)
		{
			$rs = sqlexecute($db,"SELECT opwkend1, opwkend2 
						FROM agegroups INNER JOIN teams 
						ON teams.age=agegroups.age where teamID=$teamID");
			$row = $rs->fetchRow();			
			if( !empty($row['opwkend1']))
			{
				$opwkendstart = strtotime($row['opwkend1']);
				$opwkendend = strtotime($row['opwkend2']);
				//print "OPENING WEEKEND " . date("m/d/Y",$opwkendstart) . " " . date("m/d/Y",$opwkendend) ."<br>\n";
			}
			$rs->free();
			$rs = sqlexecute($db,"select maxDH, rule, dhrule from teamopeningdh where teamID=$teamID");
			if ($rs->numRows()>0) 
			{
				$row = $rs->fetchRow();
				$maxDH = $row['maxDH'];
				$owrule = $row['rule'];
				$owdhrule = $row['dhrule'];
			}
			$rs->free();
		}*/

		echo '<h1>'.$title.'</H1>
				<h2>for '.$name.'</H2>';
    	
		require('teamavailheadnew.php');
		
		//get weekly pattern info
		$sql='SELECT patnum,fri,thurs,pattID,name FROM SCHEDLINKweekpattern, SCHEDpatterns WHERE SCHEDpatterns.patternID=SCHEDLINKweekpattern.pattID AND SCHEDpatterns.teamID='.$teamID;
		$rs=sqlexecute($db,$sql);
		$weeklinks=$weekcolors=array();
		while($row=$rs->fetchRow())
		{
			$patterns[$row['pattID']]['title']='Pattern '.$row['patnum'].' - '.$row['name'];
			$weeklinks[$row['fri']]=$row['pattID'];
			$patterns[$row['pattID']]['color']=$weekcolors[$row['fri']]=$clrs[$row['patnum']];
			$sql2='SELECT weekday,avail,DHavail,start,end,night FROM SCHEDdayavail WHERE pattID='.$row['pattID'];
			$rs2=sqlexecute($db,$sql2);
			while($row2=$rs2->fetchRow())
			{
				$patterns[$row['pattID']][$row2['weekday']]=$row2;
			}
			$rs2->free();
		}
		$rs->free();

		echo '<CENTER>
			<DIV CLASS="noticebox"><P>When filling out your team availability calendar, be sure to <U>block out any dates</U> or times where your team will be unable to play because of conflicts with other events such as:</P>
			<OL>
			<LI> Holidays ---> Memorial Day Weekend or the 4th of July
			<LI> School Exams ---> Night before SAT or Finals
			<LI> Other School Events ---> Sports Banquets, Graduation, Dances, Proms, Class Trips.
			<LI> Non-availability of Manager/Coaches ---> Weddings, Baptisms, Planned Trips.
			</OL>
		</DIV>';

		//CALENDAR KEY
		if($patterns)
		{
			foreach($patterns AS $_pid=>$_info)
			{
				echo '<SPAN STYLE="width:15px;border: solid 1px #000000;padding-left:10px;padding-right:10px" CLASS="'.$_info['color'].'"></SPAN> = Pattern '.$_info['title'].'<BR>';
			}
		}
		//<SPAN STYLE="background-color:#FFFFFF;width:15px;border: solid 1px #000000;padding-left:10px;padding-right:10px"></SPAN>= Available';
		/*if($owon)
		{
			echo'<BR><SPAN STYLE="width:15px;border: 3px dotted #FF0000; padding-left:10px; padding-right:10px"></SPAN> = '.$leaguesettings['owname'];
		}*/
		//echo '<BR><SPAN STYLE="background-color:#fcdaa6;width:15px;border: solid 1px #000000;padding-left:10px;padding-right:10px"></SPAN>&nbsp;= Weekend OR-Play';

		echo '<BR><IMG SRC="'.$toroot.'images/car.png" ALT="long distance" WIDTH="38">&nbsp;=&nbsp;available for long distance play
		<BR><IMG SRC="'.$toroot.'images/house.png" ALT="home only" WIDTH="18">&nbsp;=&nbsp; available for home game only
		<BR><IMG SRC="'.$toroot.'images/trophy.png" ALT="home only" WIDTH="18">&nbsp;=&nbsp; registered for '.$leagueabbr.' tournament on this day';
		

		echo '<BR><DIV STYLE="font-size:1.2em;text-align:left;">To change the pattern used for any week, please click pencil(<IMG SRC="/images/pencil.png" CLASS="noborder">) to left of desired week.</DIV>';
			

		//block out days on team availability calendar
		include_once($toroot.'inc/blocktourndays.php');
		$tourns=blockTournDates($teamID);

		// BEGIN CALENDAR

		// get season start/end dates from db
		$sql = "SELECT startdt,enddt,opwkend1,opwkend2,fall 
			FROM SCHEDteamseas,teams 
			LEFT JOIN agegroups ON agegroups.age=teams.age 
			WHERE SCHEDteamseas.teamID=teams.teamID AND teams.teamID=$teamID";
		$rs = sqlexecute($db,$sql);
		$row = $rs->fetchRow();
		if (!empty($row['startdt']))
		{
			if($row['fall']==0 && !empty($row['opwkend1']))			
					$seasonst = (strtotime($row['startdt'])>strtotime($row['opwkend1'])) ? strtotime($row['opwkend1']) : strtotime($row['startdt']);
			else
				$seasonst=strtotime($row['startdt']);
			$seasonen = strtotime($row['enddt']);
		}
		else 
		{
				$sy = $ey = getSeason();
				$seasonst = strtotime("1/1/".$sy);
				$seasonen = strtotime("12/31/".$ey);
		}
		$sm = date("n",$seasonst);
		$sy = date('Y', $seasonst);
		$em = date("n",$seasonen);
		$ey = date('Y',$seasonen);
		$rs->free();

		//get days with special restrictions
		$sql='SELECT rday,avail FROM SCHEDrestricdays WHERE teamID='.$teamID;
		$rs=sqlexecute($db,$sql);
		while($row=$rs->fetchRow())
		{
			$restricdays[$row['rday']]=$row['avail'];
		}
		$rs->free();

		$month=new DateTime("$sm/1/$sy");
		
		$begmo=false;
		while(strtotime($month->format('m/d/Y')) <= $seasonen) 
		{
			$i=date('m', strtotime($month->format('m/d/Y')));
			$yr=date('Y', strtotime($month->format('m/d/Y')));
			echo '<DIV CLASS="calendar">
					<DIV CLASS="header">'.date("F", mktime(0, 0, 0, $i, 1, $yr)).'</DIV>
					<DIV CLASS="body">';
			echo  '<table cellpadding="1" cellspacing="1" border="0" width="650">
				<tr><TD></TD>
					<td align="center" STYLE="border-top:1px dotted black;border-left:1px dotted black;"><H3>F</H3></td>
					<td align="center" STYLE="border-top:1px dotted black;""><H3>S</H3></td>
					<td align="center" STYLE="border-top:1px dotted black;""><H3>S</H3></td>
					<td align="center" STYLE="border-top:1px dotted black;border-right:1px dotted black;"><H3>M</H3></td>
					<td align="center" STYLE="border-top:1px dotted black;border-left:1px dotted black;" CLASS="rowalt"><H3>T</H3></td>
					<td align="center" STYLE="border-top:1px dotted black;" CLASS="rowalt"><H3>W</H3></td>
					<td align="center" STYLE="border-top:1px dotted black;border-right:1px dotted black;" CLASS="rowalt"><H3>T</H3></td></tr>';
				$wk=1;
				$dd = ($begmo && $begmo<7) ? $begmo : 1;
				$currmo = $tm = $i;
				$dnc = 5;
				$dn = date("w", mktime(0, 0, 0, $i, $dd, $yr));
			echo '<TR CLASS="'.$weekcolors[$dbdate].'" CLASS="cal-edit">';
			$diff=0;
			while ($dnc != $dn) 
			{
				$diff++;
				$dnc=($dnc<6) ? $dnc+1 : 0;
			}
			$dnc=5;
			while ($dnc != $dn) 
			{
				if($dnc==5)
				{
					$firstday=mktime(0, 0, 0, $i, $dd, $yr);
					$fri=$firstday-($diff*24*60*60);
					$thurs=$fri+(6*24*60*60);
					//echo "$fri -> ".date('Y-m-d',$fri)."<BR>";
					if($weekcolors[date("Y-m-d", $fri)])
						echo  '<TD><H5 CLASS="date-head"><SPAN CLASS="cal-edit">
							<IMG SRC="/images/pencil.png" ALIGN="right" CLASS="noborder">
							<DIV CLASS="links">
								<a 	href="'.$toroot.'members/team/teamavpattern.php?teamID='.$teamID.'&wkds='.$fri.'-'.$thurs.'">'.$patterns[$weeklinks[date('Y-m-d',$fri)]]['title'].'</a>
							</DIV>
						</SPAN></H5></TD>';
					else
						echo '<TD></TD>';
				}
				echo '<TD>&nbsp</TD>';
				$dnc=($dnc<6) ? $dnc+1 : 0;
			}
			$predt=$nxtdt=false;
			while ($currmo == $i) 
			{
				$dbdate=date("Y-m-d", mktime(0, 0, 0, $i, $dd, $yr));
				//added second piece of this if statment to fix table layout 11/21/2014
				if($dn==4 && $cal_started)
				{
					if($weekcolors[$dbdate])
						echo '</TR><TR CLASS="'.$weekcolors[$dbdate].'" CLASS="cal-edit">';
					else
						echo '</TR><TR>';
				}
				$cal_started=true;
				$tm = date("n", mktime(0, 0, 0, $i, $dd+1, $yr));
				$dn = date("w", mktime(0, 0, 0, $i, $dd, $yr));
				$ddd=date("d",mktime(0, 0, 0, $i, $dd, $yr));
				//don't switch months until you get thru Thurs
				if($tm<>$i)
				{
					//trigger display of month for next mo days
					if($nxtdt)
						$predt=date('M',mktime(0, 0, 0, $i, $dd, $yr));
					else
						$nxtdt=true;
					if($dn==4)
					{
						$currmo=$tm;
						$begmo=$ddd+1;
					}
				}
				$thisday = mktime(0,0,0,$i,$dd,$yr);
				$rn = 0;
				$tdrs = array();
				$tdc="";
				$dh="";
	
				#specific dates
				$drd = date("Y-m-d", $thisday);
				//$tdcbg = "#cccccc";
				
				//Opening Weekend Info
				if(!empty($opwkendstart) && $thisday >= $opwkendstart && $thisday <= $opwkendend )
				{
					$border='border: 3px dotted #FF0000;';
					//avail=4 when an OW AND day, avail=5 when an OW OR day
					//check if they have entered any ow preferences
					$sql='SELECT teamID FROM teamopeningdh WHERE teamID='.$teamID;
					$setow=sqlgetone($db,$sql);
					if(!$setow)
						$dh = '<DIV CLASS="error">Need to Set OW Prefs</DIV>';
				}
				else
				{
					$bgc='';
					$border='';
				}
				//if we're on Friday, first cell of row is change pattern edit linke
				if($dn==5)
				{
					$fri=mktime(0, 0, 0, $i, $dd, $yr);
					$thurs=$fri+(6*24*60*60);
					if($weekcolors[date("Y-m-d", $fri)])
						echo  '<TD><H5 CLASS="date-head"><SPAN CLASS="cal-edit">
							<IMG SRC="/images/pencil.png" ALIGN="right" CLASS="noborder">
							<DIV CLASS="links">
								<a 	href="'.$toroot.'members/team/teamavpattern.php?teamID='.$teamID.'&wkds='.$fri.'-'.$thurs.'">'.$patterns[$weeklinks[date('Y-m-d',$fri)]]['title'].'</a>
							</DIV>
						</SPAN></H5></TD>';
					else
						echo '<TD></TD>';
				}

				//Info to display for this day
				if($thisday <= $seasonen && $thisday >= $seasonst)
				{
					$_pid=$weeklinks[date('Y-m-d',$fri)];
					if($patterns[$_pid][$dn]['avail'])
					{
						//$tdc=$patterns[$_pid][$dn]['avail'].'<BR>';
						if($patterns[$_pid][$dn]['start'])
							$tdc.=date('g:iA',strtotime($patterns[$_pid][$dn]['start'])).'<BR>'.date('g:iA',strtotime($patterns[$_pid][$dn]['end'])).'<BR>';
						$tdc.='P'.$patterns[$_pid][$dn]['avail'].'<BR>';
						if($patterns[$_pid][$dn]['DHavail'])
							$tdc.='DHP='.$patterns[$_pid][$dn]['DHavail'].'<BR>';
						else
							$tdc.='DHP=NO<BR>';
						if(empty($restricdays[$dbdate]))
							$timg= '<A HREF="tarestricdays.php?teamID='.$teamID.'"><IMG SRC="'.$toroot.'images/car.png" ALT="long distance" WIDTH="38"></A>';
						elseif($restricdays[$dbdate]=='H')
							$timg= '<A HREF="tarestricdays.php?teamID='.$teamID.'"><IMG SRC="'.$toroot.'images/house.png" ALT="tournament" WIDTH="18"></A>';
						else
							$timg='';
					}
					else
					{
						$tdc='<BR>Not Available<BR><BR>';
						$timg='';
					}
					//show tournament for ENYTB events for 4 days
					//echo $thisday.'--'.$tourns[$thisday].'=='.$leagueabbr.' || '.$tournblock.'>0<BR>';
					if($tourns[$thisday]==$leagueabbr || $tournblock>0)
					{
						$timg.='<IMG SRC="'.$toroot.'images/trophy.png" ALT="tournament" WIDTH="18">';
						if($tourns[$thisday]==$leagueabbr)
							$tournblock=1;
					}
					if($tournblock>0 && $tournblock<4)
						$tournblock++;
					else
						$tournblock=0;
					$times = '<DIV STYLE="width:88px; padding:4px; margin:1px;'.$border.'" CLASS="P'.$patterns[$_pid][$dn]['avail'].'">'.$tdc.$timg.'</DIV>';
				}
				else
					$times='<DIV STYLE="width:88px; padding:4px; margin:1px;'.$border.'"><BR><BR><BR><BR></TD>';


				$wk++;

				print "<td valign=\"top\" CLASS=\"week-$wk\">\n";
				echo "<H5 CLASS=\"date-head\">&nbsp;$predt ".date("j", mktime(0, 0, 0, $i, $dd, $yr))."</H5>";
				print $times;
				echo '</TD>';
				$dd++;
			}
			print "</tr>\n</table>\n";
			echo '</DIV>
				</DIV>';
			$month->modify("+1 month");
		}	
	}
	else
	{
		echo '<DIV CLASS="errorbox">Team Availability is not currently available. Contact your league representative if you have questions.</DIV>';
	}
} 
$loadscriptsatend='<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/effects.js"></SCRIPT>
<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/style.js"></SCRIPT>';
require($toroot.'template/foot.php');
?>
