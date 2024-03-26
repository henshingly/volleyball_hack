<?php
/** Liga Manager Online 4
  *
  * http://lmo.sourceforge.net/
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License as
  * published by the Free Software Foundation; either version 2 of
  * the License, or (at your option) any later version.
  *
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
  * General Public License for more details.
  *
  * REMOVING OR CHANGING THE COPYRIGHT NOTICES IS NOT ALLOWED!
  *
  */

//take a look at the example ligue the hack is delivered with
//Volleyball mode is switched on by naming liga like "Volleyball*"
//No changes where made in the database, therefore
//results must be entered in the lmo notes as follwos: "note xyz (25:10 ...), so please enter carefully :-)
//NB: set points are calculated automatically and stored !! if not manually entered !!
//You can enter the sum of the ballpoints, then you have to enter the setpoints
//The old calculation method can be used if you change in "Basic Values/Match System/After regular end" the won-points from 3 to 2.
//"Volleyball Season XY -X = 3:0 ...0:3 maximized info
//"Volleyball Season XY -M = 3P..0P  middle sized info
//"Volleyball Season XY = 3P..0P     short sized info
//"Volleyball Season XY -O = old sort order with Diff. instead of Quot.

define ("VB_MAX_RATIO",9999999);
define ("VB_FACTOR",10000);
$vb_version = "<acronym title='Volleyball Patch sponsored by VV Berlin updated by Henshingly - Version 1.01'>vb1.01</acronym>";
$vb_roundInfo = false;
$vb_tab_old   = false;
$vb_tab_mid   = false;
$vb_tab_max   = false;
$vb_setratio   = array();
$vb_pointratio = array();
$vb_set30      = array();
$vb_set31      = array();
$vb_set32      = array();
$vb_set23      = array();
$vb_set13      = array();
$vb_set03      = array();
$vb_ppoints = array();
$vb_mpoints = array();
// $vb_dpoints = array();

//dynamic replacement for "hardCopy" in the original code
function getTeamNumber($tab) {
  return  intval( substr($tab, -7 ));
}

function getVolleyballNameVB() {
global $text;
  //set default
  $vbtext="Volleyball";
  if ($text[2000]!="") {
    $vbtext=$text[2000];
  }
  return $vbtext;
}

function isLeagueVB() {
global $titel;
if (strpos($titel,getVolleyballNameVB() ) >-1)
  {
    return true;
  } else {
    return false;
  }
}

function setTabWidthVB(&$gesamtbreite) {
  global $vb_tab_mid,$vb_tab_max;
  if (isLeagueVB() == true ) {
    $gesamtbreite += 2; //Volleyball
    if ($vb_tab_mid==true) {
      $vb_tab_max=false;
    }
    if ($vb_tab_mid==true) {
      $gesamtbreite += 5;
    } else {
      if ($vb_tab_max==true) {
        $gesamtbreite += 7;
      }
    }
  }
}

function setOptionsVB (&$output_titel) {
  global $vb_roundinfo,$vb_tab_max,$vb_tab_mid,$vb_tab_old;
  if (isLeagueVB() == true) {
    $vbposo=strpos($output_titel," -O");
    $vbposs=strpos($output_titel," -S");
    $vbposx=strpos($output_titel," -X");
    $vbposm=strpos($output_titel," -M");
    $vb_roundInfo  = ($vbposs>-1);
    $vb_tab_old = ($vbposo>-1);
    $vb_tab_max = ($vbposx>-1);
    $vb_tab_mid = ($vbposm>-1);
    if ($vbposo==false) {
      $vbposo=999;
    }
    if ($vbposs==false) {
      $vbposs=999;
    }
    if ($vbposx==false) {
      $vbposx=999;
    }
    if ($vbposm==false) {
      $vbposm=999;
    }
    $vbposmin = min($vbposs,$vbposx,$vbposm,$vbposo);
    if ($vbposmin>-1) {
      $output_titel = substr($output_titel,0,$vbposmin);
    }
  }
}

function insertArraysVB (&$array, $anzteams) {
  global $vb_ppoints,$vb_mpoints,
  $vb_setratio,$vb_pointratio,
  $vb_set30,$vb_set31,$vb_set32,$vb_set23,$vb_set13,$vb_set03;

  if ( isLeagueVB() == true ) {
    $vb_ppoints = array_pad($array, $anzteams+1, "0");
    $vb_mpoints = array_pad($array, $anzteams+1, "0");
    $vb_setratio = array_pad($array, $anzteams+1, "0");
    $vb_pointratio = array_pad($array, $anzteams+1, "0");
    $vb_set30 = array_pad($array, $anzteams+1, "0");
    $vb_set31 = array_pad($array, $anzteams+1, "0");
    $vb_set32 = array_pad($array, $anzteams+1, "0");
    $vb_set23 = array_pad($array, $anzteams+1, "0");
    $vb_set13 = array_pad($array, $anzteams+1, "0");
    $vb_set03 = array_pad($array, $anzteams+1, "0");
  }
}

function calcFileResultsVB($mnote,&$goala,&$goalb,&$msieg) {
  if (isLeagueVB() == true) {
    $pointplus  = 0;
    $pointminus = 0;
    $setplus    = 0;
    $setminus   = 0;
    $error      = false;
    if (getPointsFromCommentVB($mnote,$pointplus,$pointminus,$setplus,$setminus,$error)==true) {
      if (($setplus+$setminus)>1) { //there must be a minimum of two result sets
        if ($goala==-1) {
          $goala=$setplus;
        }
        if ($goalb==-1) {
          $goalb=$setminus;
        }
      }
    }
  }
}


// evaluates a comment like "coment xy results: (25:10, 25 - 10 ; 10:25 ; 25:27 | 15:10)"
// only weak error check: only non int values are recognized as an error
// number of sets are not checked, also irregular set end
function getResultStringVB($comment) {
  $apos = strpos($comment,"(");
  if ($apos===false) {
    return "";
  }
  $comment = substr($comment,$apos+1);
  $apos = strpos($comment,")");
  if ($apos===false) {
    return "";
  }
  $comment = substr($comment,0,$apos);
  $comment = str_replace(" ","",$comment); //delete spaces
  $delimiters = array(";",",","|");
  $results = str_replace($delimiters, $delimiters[0], $comment);
  return $results;
}

function getLocationStringVB($comment) {
  return substr($comment,0,6);
}

function getSrTeamStringVB($comment) {
  $astring = substr($comment,7, 255)." ";
  $apos = strpos($astring,"(");
  if ($apos!==false) {
    $astring=substr($astring,0,$apos-1)." ";
  }
  return substr($astring,0,strpos($astring," "));
}

function getPointsFromCommentVB($comment,&$pointplus,&$pointminus,&$setplus,&$setminus,&$error) {
  $results = getResultStringVB($comment);
  if ($results=="") {
    return false;
  }
  $results = explode(";", $results); //array [0] 25:10 [1] 10:25 etc
  $error = false;
  $pointplus  = 0;
  $pointminus = 0;
  $setplus    = 0;
  $setminus   = 0;
  foreach ($results as $value) {
    $delimiters = array(":","-");
    $value  = str_replace($delimiters, $delimiters[0], $value);
    $result = explode($delimiters[0],$value);
    $i=0;
    foreach ($result as $point) {
      if (is_numeric($point)==true) {
        if ($i==0) {
          $plus  = $point;
        } else  if ($i==1) {
          $minus = $point;
        }
      } else {
        $plus=0;
        $minus=0;
        $error=true;
      }
      $i=$i+1;
    }
    $pointplus  = $pointplus  + $plus;
    $pointminus = $pointminus + $minus;
    if ($plus>$minus) {
      $setplus = $setplus + 1;
    } else {
      if ($plus < $minus) {
        $setminus = $setminus + 1;
      }
    }
  }
  return true;
}

function formatRatioVB($ratio) {
  global $vb_tab_old;
  if ($vb_tab_old==true) {
    return $ratio;
  } else {
    if ($ratio==VB_MAX_RATIO) {
      return 'Max.';
    } else {
      $ratio = $ratio / VB_FACTOR;
      return number_format($ratio,3);
    }
  }
}

function calcRatioVB($pos,$neg) {
  global $vb_tab_old;
  if ($vb_tab_old==true) {
    $result=$pos - $neg;
  } else {
    $result = round ( ($pos / ( $neg + 1/1000/VB_FACTOR)) * VB_FACTOR );
    if ($result>VB_MAX_RATIO) {
      $result=VB_MAX_RATIO;
    }
  }
  return $result;
}

function calcSetPointsVB($won,$norevert,$mnote,$satza,$satzb,&$punkte,&$vb_ppoints,&$vb_mpoints,$a,$p0s,$p0u,$p0n,&$vb_set30,&$vb_set31,&$vb_set32,&$vb_set23,&$vb_set13,&$vb_set03) {
  $pointplus  = 0;
  $pointminus = 0;
  $setplus    = 0;
  $setminus   = 0;
  $error      = false;

  if (getPointsFromCommentVB($mnote,$pointplus,$pointminus,$setplus,$setminus,$error)==true) {
    if (($satza=="") || ($satza=="_")) {
      $satza=$setplus;
    }
    if (($satzb=="") || ($satzb=="_")) {
      $satzb=$setminus;
    }
    if ($norevert==true) {
      $vb_ppoints[$a] = $vb_ppoints[$a] + $pointplus;
      $vb_mpoints[$a] = $vb_mpoints[$a] + $pointminus;
    } else {
      $vb_ppoints[$a] = $vb_ppoints[$a] + $pointminus;
      $vb_mpoints[$a] = $vb_mpoints[$a] + $pointplus;
    }
  }
  if ($won==true) {
    if (abs($satza-$satzb)>2) {
      $punkte[$a] = $punkte[$a] + $p0s;
      $vb_set30[$a]  = $vb_set30[$a]  + 1;
    } else if (abs($satza-$satzb)>1) {
      $punkte[$a] = $punkte[$a] + $p0s;
      $vb_set31[$a]  = $vb_set31[$a]  + 1;
    } else {
      $punkte[$a] = $punkte[$a] + $p0s - $p0u;
      $vb_set32[$a]  = $vb_set32[$a]  + 1;
    }
  } else {
    if (abs($satza-$satzb)>2) {
      $vb_set03[$a]  = $vb_set03[$a]  + 1;
    } else if (abs($satza-$satzb)>1) {
      $vb_set13[$a]  = $vb_set13[$a]  + 1;
    } else {
      $punkte[$a] = $punkte[$a] + $p0u;
      $vb_set23[$a]  = $vb_set23[$a]  + 1;
    }
  }
}

?>