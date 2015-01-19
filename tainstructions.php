<?php
$pageAccessLevel = 3;
$toroot = "../../";
require_once $toroot."inc/secure.php";
if (securitycheck($pageAccessLevel, $toroot) != 1) {
  exit;
}

include_once($toroot."inc/sql.php");
include($toroot.'inc/leagueinfo.php'); // creates $leaguename, $leagueabbr, $leagueaddy,$leaguecity,$leaguestate,$leaguezip,$leaguetitle

$db = &sqlconnect();
$teamID = (!empty($_GET['teamID'])) ? $_GET['teamID'] : '';
$teamID = (!empty($_POST['teamID'])) ? $_POST['teamID'] : $teamID;
if($teamID) // get existing values
{
	$sql='SELECT startdt, enddt, minGames, maxGames, tstrength, sos FROM SCHEDteamseas WHERE teamID='.$teamID;
	$rs=sqlexecute($db,$sql);
	$data=$rs->fetchRow();
	$rs->free();
	$sql='SELECT power, age, max FROM SCHEDmaxspecial WHERE teamID='.$teamID;
	$rs=sqlexecute($db,$sql);
	while($row=$rs->fetchRow())
	{
		$data['maxspec'][$row['age']][$row['power']]=$row['max'];
	}
	$rs->free();
}


//SAVE Team Availability initial settings
if($_POST['initialize'])
{
	include_once($toroot.'inc/class.validateforms.php');
	$valid= new FormValidator;
	$_POST['startdt']=$valid->validFullDate('startdt', 'ERROR: Please enter a valid Start Play date');
	$_POST['enddt']=$valid->validFullDate('enddt', 'ERROR: Please enter a valid End Play date');
	if(!$_POST['nosos'])
	{
		$valid->isEmpty('tstrength', 'ERROR: Please select the relative strength of your team.');
		$valid->isEmpty('sos', 'ERROR: Please select your strength of schedule preference.');
	}
	if($valid->isError())
		$errors=$valid->getErrorList();

	if($_POST['minGames'] > $_POST['maxGames'])
		$errors['minGames']='ERROR: The minimum games you want to play must be less than the maximum games.';


	if($errors)
	{
		$data=$_POST;
	}
	else
	{

		include_once($toroot.'inc/class.processforms.php');
		$up=new processforms;
		if($data['startdt'])
			$saved=$up->UpdateDB('SCHEDteamseas',$teamID, 'teamID');
		else
			$saved=$up->AddToDB('SCHEDteamseas');
		sqlexecute($db, 'DELETE FROM SCHEDmaxspecial WHERE teamID='.$teamID);
		foreach($_POST['maxspec'] AS $_age=>$_x)
		{
			foreach($_x AS $_power=>$_max)
			{
				$fields=array('teamID','age','power','max');
				$values=array($teamID,"'".$_age."'",$_power,$_max);
				$sql=buildinsertinto('SCHEDmaxspecial',$fields,$values);
				sqlexecute($db,$sql);
			}
		}
		$host=$_SERVER['HTTP_HOST'];
		header("Location: http://$host/members/team/tarestricdays.php?teamID=$teamID");	
	}
}

//DELETE existing team availability for this team
if (isset($_POST['clearschedule2']) && $teamID) 
{
	$delpats=array();
	$sql='SELECT patternID FROM SCHEDpatterns WHERE teamID='.$teamID;
	$rs=sqlexecute($db,$sql);
	while($row=$rs->fetchRow())
	{
		$delpats[]=$row['patternID'];
	}
	$rs->free();

	sqlexecute($db,'DELETE FROM SCHEDteamseas WHERE teamID='.$teamID);
	sqlexecute($db,'DELETE FROM SCHEDmaxspecial WHERE teamID='.$teamID);
	sqlexecute($db,'DELETE FROM SCHEDrestricdays WHERE teamID='.$teamID);
	//maybe not this one
	//sqlexecute($db,'DELETE FROM SCHEDteamfields WHERE teamID='.$teamID);

	foreach($delpats AS $_pid)
	{
		sqlexecute($db,'DELETE FROM SCHEDpatterns WHERE patternID='.$_pid);
		sqlexecute($db,'DELETE FROM SCHEDdayavail WHERE pattID='.$_pid);
		sqlexecute($db,'DELETE FROM SCHEDoneorother WHERE pattID='.$_pid);
		sqlexecute($db,'DELETE FROM SCHEDLINKweekpattern WHERE pattID='.$_pid);
	}
	$errors[]='Team Availability Settings have been reset.';
}

?>
<!DOCTYPE HTML>
<HTML>
	<head>
		<title>Team Availability<?php echo '  -  '.$leaguetitle; ?></title>
<?php
echo '<LINK HREF="'.$toroot.'javascripts/datestuff/datepicker.css" REL="stylesheet" TYPE="text/css">';
//put scripts here-> head.php include closes head and opens body
 include_once($toroot.'template/head.php'); 
?>
<H1>Team Availability Setup Preferences</H1>
 
<?php
include_once($toroot.'inc/chooseteam.php');
$printscreen=teamSelected($teamID,$level);
if($printscreen)
{
	if($level>=5)
		switchLink('Team');
	//check if write access to schedules is on
	include_once($toroot.'inc/getwriteflag.php');
	$accesson=getwriteflag('ta');
	if($level>6)
		$accesson=true;	
	elseif($accesson)
	{
		$sql='SELECT maxage FROM agegroups, teams WHERE agegroups.age=teams.age AND teams.teamID='.$teamID;
		$maxage=sqlgetone($db,$sql);
		$youngaccess=getwriteflag('tay');
		if( $maxage<=12 && !$youngaccess )
			$accesson=false;
	}
}
if($printscreen)
{
	$rs = sqlexecute($db,"select teams.name,teams.age,teams.age2,teams.power, teams.ownerID FROM teams 
	WHERE teamID=$teamID");
	$row=$rs->fetchRow();
	$tname=$row['name'];
	$tage=$row['age'];
	$tage2=($row['age2']) ? $row['age2'] : $row['age'];
	$tpval=$row['power'];
	$tpower=$leaguesettings['power'][$row['power']];
	$ownerID=$row['ownerID'];
    print "<H2>$tname</H2>
    		<H4>Age: $tage</H4>
    		<H4>Power: $tpower</H4>";
	
	require('teamavailheadnew.php');
	
	if($errors)
	{
		echo '<BR><DIV CLASS="errorbox" STYLE="display:none;">';
		foreach($errors AS $_e)
			echo $_e.'<BR>';
		echo '</DIV>';
	}
	include_once($toroot.'inc/season.php');
	$season=timeOfYear($db);

	if(!$accesson)
	{
		echo '<DIV CLASS="errorbox">Scheduling inputs are not currently available. If you need to edit your availability at this time please contact the league administrator.</DIV>';
	}
	elseif(isset($_POST['clearschedule'])) 
	{
		#clear schedule form
		echo '<DIV CLASS="errorbox" STYLE="display:none;">
			<form name="startup" action="tainstructions.php?teamID='.$teamID.'" method="post">
				<STRONG>Are you sure you want to delete your team availability schedule and start over?</STRONG><BR>
				<input type="submit" name="clearschedule2" value="  Delete Schedule" CLASS="zapbutton">
				<A HREF="teamavailcal.php?teamID='.$teamID.'">cancel</A>
			</form>
		</DIV>';
		#clear schedule form end
	}
	else
	{
		echo '<form action="'.$_SERVER['PHP_SELF'].'?teamID='.$teamID.'" method="post" CLASS="colored">
				<input type="hidden" name="teamID" value="'.$teamID.'">';
		if($data['startdt'])
		{
			$startdt=date('n/j/Y', strtotime($data['startdt']));
			$enddt=date('n/j/Y', strtotime($data['enddt']));
		}
		else
		{
			//get season dates for defaults
			$sql='SELECT beg,end FROM LUTseasons WHERE ID='.$season;
			$rs=sqlexecute($db,$sql);
			$row=$rs->fetchRow();
			$startdt=date('n/j/Y', strtotime($row['beg']));
			$enddt=date('n/j/Y',strtotime($row['end']));
			$rs->free();
		}
		echo '<FIELDSET><LEGEND>Team\'s Begin/End Play Dates</LEGEND>
			<BR>';
		if($maxage > 12)
			echo '
				<H5>Pre-Season Games (13U and older only)</H5>
				<P>
				Teams at 13U and 14U and some low silver teams at higher ages like to begin play earlier than Memorial Day Weekend. If teams want to start "early" they can indicate so on their team availability calendar and the league will schedule these dates provided there are other teams seeking the same. These games will count as official league games. The league does NOT schedule pre-season or exhibition games. If a team wishes to play such games, it should schedule these games on its own. The website provides a bulletin board for advertising to other teams for extra games (see Tools > Extra Games). CAUTION: When scheduling pre-season games do NOT add them to your online game schedule or they will be counted as official league games.
				</P>';
		echo '
				<H5>Length Of Regular Season</H5>
				<P> 
				 Each team is allowed to specify the earliest possible date for its first league scheduled game as well as the latest possible date for its last league scheduled date. The league will not schedule teams to play during the time period of sanctioned tournaments  that the team is registered for.  Each standings has a last date for counting games, usually a few days before the sanctioned tournament is scheduled to begin. This date is shown at the top of each standings.
				</P>
				<H5>Late Season Exhibition Games</H5>
				<P>
				If a team wishes to continue playing following the conclusion of sanctioned tournaments, the league will schedule such games during this period to the extent that match-ups are available, even though those games will not count in any standings.
				</P>
			<center><SPAN CLASS="notes">(please use mm/dd/yyyy format)</SPAN></center>';
		//start date
		echo '<LABEL FOR="startdt">Start Play:</LABEL> <INPUT TYPE="text" NAME="startdt" ID="startdt" CLASS="dateformat-m-sl-d-sl-Y highlight-days-67" SIZE="10" VALUE="'.$startdt.'"><BR>';		
		//end date
		echo '<LABEL FOR="enddt">End Play:</LABEL> <INPUT TYPE="text" NAME="enddt" ID="enddt" CLASS="dateformat-m-sl-d-sl-Y highlight-days-67" SIZE="10" VALUE="'.$enddt.'">
		</FIELDSET>
		<FIELDSET><LEGEND>Total Games</LEGEND>
			<LABEL FOR="minGames">Minimum Games:</LABEL>
				<SELECT NAME="minGames" ID="minGames">';
		for($i=1;$i<=50;$i++)
		{
			echo '<OPTION';
			if($i==$data['minGames'])
				echo ' SELECTED';
			echo '>'.$i.'</OPTION>';
		}
		echo '</SELECT><BR>
			<LABEL FOR="maxGames">Maximum Games:</LABEL>
				<SELECT NAME="maxGames" ID="maxGames">';
		for($i=1;$i<=50;$i++)
		{
			echo '<OPTION';
			if($i==$data['maxGames'])
				echo ' SELECTED';
			echo '>'.$i.'</OPTION>';
		}
		echo '</SELECT><BR>
			<LABEL FOR="maxContin">Max Continuous Games:</LABEL>
				<SELECT NAME="maxContin" ID="maxContin">';
		for($i=1;$i<=50;$i++)
		{
			echo '<OPTION';
			if($i==$data['maxContin'])
				echo ' SELECTED';
			echo '>'.$i.'</OPTION>';
		}
		echo '</SELECT><BR>
		</FIELDSET>
		<FIELDSET><LEGEND>Strength of schedule</LEGEND>';
		if($tpval<>1)
		{
			switch($data['tstrength'])
			{
				case 1:
					$selhigh=' CHECKED';
					break;
				case 2:
					$selmid=' CHECKED';
					break;
				case 3:
					$sellow=' CHECKED';
					break;
				default:
					$selhigh=$selmid=$sellow='';
			}
			switch($data['sos'])
			{
				case 1:
					$ckhigh=' CHECKED';
					break;
				case 2:
					$ckmid=' CHECKED';
					break;
				case 3:
					$cklow=' CHECKED';
					break;
				default:
					$ckhigh=$ckmid=$cklow='';
			}
			echo 'Relative Team Strength Within Class:
			<INPUT TYPE="RADIO" NAME="tstrength" VALUE="1"'.$selhigh.'>High End  
			<INPUT TYPE="RADIO" NAME="tstrength" VALUE="2"'.$selmid.'>Middle Range  
			<INPUT TYPE="RADIO" NAME="tstrength" VALUE="3"'.$sellow.'>Low End  
			<BR><BR>Strength of Schedule Preference:
			<INPUT TYPE="RADIO" NAME="sos" VALUE="1"'.$ckhigh.'>High End  
			<INPUT TYPE="RADIO" NAME="sos" VALUE="2"'.$ckmid.'>Middle Range  
			<INPUT TYPE="RADIO" NAME="sos" VALUE="3"'.$cklow.'>Low End<BR>';
		}
		else
			echo '<INPUT TYPE="hidden" NAME="nosos" VALUE="1">';
		//Diamond (1) and Gold (2) powers can select number of games with teams at other power at their age and one age up or down depending on the power
		if($tpval < 3)
		{
			$otherval=($tpval==1) ? 2 : 1;
			$otherpower=$leaguesettings['power'][$otherval];
			$nextage=false;
			foreach($leaguesettings['agegroups'] AS $_maxage=>$_age)
			{
				if($tpval==2 && $tage==$_age)
				{
					$otherage=$_currage;
					break;
				}
				elseif($nextage)
				{
					$otherage=$_age;
					break;
				}
				$_currage=$_age;
				if($tpval==1 && $tage==$_age)
					$nextage=true;
			}
			echo 'Max Games vs. '.$otherpower.' '.$tage.' Teams: <SELECT NAME="maxspec['.$tage.']['.$otherval.']">
					<OPTION VALUE="-1">no max';
			//gold teams must play at least 4 games with elite of same age
			if($otherval==1)
				$min=4;
			else
				$min=0;
			for($i=$min;$i<=16;$i++)
			{
				echo '<OPTION';
				if($i==$data['maxspec'][$tage][$otherval])
					echo ' SELECTED';
				echo '>'.$i.'</OPTION>';
			}
			echo '</SELECT><BR>';
			//9U does not have a lower age, Unlimited does not have higher
			if($otherage)
			{
				echo 'Max Games vs. '.$otherpower.' '.$otherage.' Teams: <SELECT NAME="maxspec['.$otherage.']['.$otherval.']">
					<OPTION VALUE="-1">no max';
				for($i=0;$i<=16;$i++)
				{
					echo '<OPTION';
					if($i==$data['maxspec'][$otherage][$otherval])
						echo ' SELECTED';
					echo '>'.$i.'</OPTION>';
				}
				echo '</SELECT>';
				if(($tage=='11U' && $otherage=='10U') || ($tage=='13U' && $otherage=='12U'))
					echo '<DIV CLASS="notes">NOTE: May require playing on smaller sized field.</DIV>';
				elseif(($tage=='10U' && $otherage=='11U') || ($tage=='12U' && $otherage=='13U'))
					echo '<DIV CLASS="notes">NOTE: May require playing on larger sized field.</DIV>';
			}
		}
		echo '</FIELDSET>
		<INPUT TYPE="submit" NAME="initialize" VALUE="Initialize Calendar">
			</FORM>';
			
		$loadscriptsatend='<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/prototype.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/effects.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/style.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/datepicker.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="ajax/tainstructionsnew.js"></SCRIPT>';
	}
	/*$loadscriptsatend='<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/prototype.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/effects.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/style.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/datepicker.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="ajax/tainstructions2.js"></SCRIPT>';*/
}


require($toroot.'template/foot.php');
?>
