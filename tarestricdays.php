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

?>
<!DOCTYPE HTML>
<HTML>
	<head>
		<title>Team Availability Restricted Days<?php echo '  -  '.$leaguetitle; ?></title>
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


	//delete stuff
	if($_POST['delete'] && $_POST['teamID'])
	{
		if($_POST['rday'])
			sqlexecute($db,"DELETE FROM SCHEDrestricdays WHERE rday='".$_POST['rday']."' AND teamID=".$_POST['teamID']);
	}
	if($_POST)
	{
		include_once($toroot.'inc/class.validateforms.php');
		$valid=new FormValidator;
	}

	//get existing restricted days
	$longdist=$homeonly=$daysset=array();
	$sql='SELECT rday,avail FROM SCHEDrestricdays WHERE teamID='.$teamID.' ORDER BY rday';
	$rs=sqlexecute($db,$sql);
	while($row=$rs->fetchRow())
	{
		$daysset[]=$row['rday'];
		if($row['avail']=='L')
			$longdist[]=$row['rday'];
		else
			$homeonly[]=$row['rday'];
	}
	$rs->free();

	//save restricted days
	if($_POST['longdist'])
	{
		$sql='SELECT startdt, enddt FROM SCHEDteamseas WHERE teamID='.$teamID;
		$rs=sqlexecute($db,$sql);
		$row=$rs->fetchRow();
		$st=$row['startdt'];
		$en=$row['enddt'];
		$rs->fetchRow();
		$valid->isWithinRangeDate('longdist', 'ERROR: The date was not within the range of dates you selected for your season. ('.$st.' thru '.$en.').', $st, $en);
		$_POST['longdist']=$valid->validFullDate('longdist', 'ERROR: You did not enter a valid date.');
		if($valid->isError())
			$errors=$valid->getErrorList();
		if(in_array($_POST['longidst'], $daysset))
			$errors['used']='ERROR: '.$_POST['rday'].' already has restrictions applied to it. If you wish to change the type of restriction, first delete the existing restriction.';
		if($errors)
		{
			echo '<DIV CLASS="error">';
			foreach($errors AS $_err)
				echo $_err;
			echo '</DIV>';
		}
		else
		{
			$fields=array('teamID', 'rday', 'avail');
			$values=array($teamID, "'".$_POST['longdist']."'", "'L'");
			$sql=buildinsertinto('SCHEDrestricdays', $fields,$values);
			sqlexecute($db,$sql);
			$longdist[]=$_POST['longdist'];
		}
	}
	elseif($_POST['homeonly'])
	{
		$sql='SELECT startdt, enddt, maxGames FROM SCHEDteamseas WHERE teamID='.$teamID;
		$rs=sqlexecute($db,$sql);
		$row=$rs->fetchRow();
		$st=$row['startdt'];
		$en=$row['enddt'];
		$max=$row['maxGames'];
		$rs->fetchRow();
		$valid->isWithinRangeDate('homeonly', 'ERROR: The date was not within the range of dates you selected for your season. ('.$st.' thru '.$en.').', $st, $en);
		$_POST['homeonly']=$valid->validFullDate('homeonly', 'ERROR: You did not enter a valid date.');
		if($valid->isError())
			$errors=$valid->getErrorList();
		if(in_array($_POST['homeonly'], $daysset))
			$errors['used']='ERROR: '.$_POST['rday'].' already has restrictions applied to it. If you wish to change the type of restriction, first delete the existing restriction.';
		$sql="SELECT COUNT(*) AS ct FROM SCHEDrestricdays WHERE avail='H' AND teamID=$teamID";
		$_hgs=sqlgetone($db,$sql);
		if($_hgs>=($max/2))
			$errors['max']='ERROR: You are not allowed to designate additional dates as Home Game Only days because the number of dates already designated have reached 50% of the number of total desired games.';
		if($errors)
		{
			echo '<DIV CLASS="errorbox" STYLE="display:none">';
			foreach($errors AS $_err)
				echo $_err.'<BR>';
			echo '</DIV>';
		}
		else
		{
			$fields=array('teamID', 'rday', 'avail');
			$values=array($teamID, "'".$_POST['homeonly']."'", "'H'");
			$sql=buildinsertinto('SCHEDrestricdays', $fields,$values);
			sqlexecute($db,$sql);
			$homeonly[]=$_POST['homeonly'];
		}
	}
	/*elseif($_POST['field'] && $_POST['priority']) //save team fields
	{
		$fields=array('teamID', 'priority', 'fieldID');
		$values=array($teamID, $_POST['priority'], $_POST['field']);
		$sql=buildinsertinto('SCHEDteamfields',$fields,$values);
		sqlexecute($db,$sql);
	}*/

	//display restricted days
	echo '<H4>Not Available for Long Distance Travel on These Days</H4>
			<UL>';
	foreach($longdist AS $_day)
	{
		echo '<FORM ACTION="" METHOD="post">
				<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'"> 
				<INPUT TYPE="hidden" NAME="rday" VALUE="'.$_day.'">
				<INPUT TYPE="submit" NAME="delete" VALUE="X">'.date('n/j/Y (D)',strtotime($_day)).'</FORM>';
	}
	echo '</UL><FORM ACTION="" METHOD="post">
			<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'"">
			<INPUT TYPE="text" NAME="longdist" SIZE="10" ID="longdist">
			<INPUT TYPE="submit" VALUE="SAVE">
			</FORM><DIV CLASS="notes">(mm/dd/yyyy)</DIV>
		<H4>Available for Home Games Only on These Days</H4>';
	foreach($homeonly AS $_day)
	{
		echo '<FORM ACTION="" METHOD="post">
				<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'"> 
				<INPUT TYPE="hidden" NAME="rday" VALUE="'.$_day.'">
				<INPUT TYPE="submit" NAME="delete" VALUE="X">'.date('n/j/Y (D)',strtotime($_day)).'</FORM>';
	}
	echo '<FORM ACTION="" METHOD="post">
			<INPUT TYPE="hidden" NAME="teamID" VALUE="'.$teamID.'"">
			<INPUT TYPE="text" NAME="homeonly" SIZE="10" ID="homeonly">
			<INPUT TYPE="submit" VALUE="SAVE">
			</FORM><DIV CLASS="notes">(mm/dd/yyyy)</DIV>';
	echo 'When finished, <A HREF="teamavail.php?teamID='.$teamID.'">Click Here to Continue</A>';
	$loadscriptsatend='<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/prototype.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/effects.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/style.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="'.$toroot.'javascripts/datepicker.js"></SCRIPT>
		<SCRIPT TYPE="text/javascript" SRC="ajax/tarestricdays.js"></SCRIPT>';
}

require($toroot.'template/foot.php');
?>