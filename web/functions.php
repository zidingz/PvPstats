<?php

function getPlayerGender($guid)
{
  global $db;

  $query = sprintf("SELECT gender FROM characters WHERE guid = %d;", $guid);
  $row = $db->query($query)->fetch_array();

  return $row['gender'];
}

function getPlayerRace($guid)
{
  global $db;

  $query = sprintf("SELECT race FROM characters WHERE guid = %d;", $guid);
  $row = $db->query($query)->fetch_array();

  return $row['race'];
}

function getPlayerClass($guid)
{
  global $db;

  $query = sprintf("SELECT class FROM characters WHERE guid = %d;", $guid);
  $row = $db->query($query)->fetch_array();

  return $row['class'];
}

function getPlayerName($guid)
{
  global $db;

  $query = sprintf("SELECT name FROM characters WHERE guid = %d;", $guid);
  $row = $db->query($query)->fetch_array();

  return $row['name'];
}

function getPlayerColor($guid)
{
  global $db, $alliance_color, $horde_color;

  $query = sprintf("SELECT race FROM characters WHERE guid = %d", $guid);
  $row = $db->query($query)->fetch_row();

  switch ($row[0])
  {
    case 1:
    case 3:
    case 4:
    case 7:
    case 11:
      $color = $alliance_color;
      break;
    case 2:
    case 5:
    case 6:
    case 8:
    case 10:
      $color = $horde_color;
      break;
  }

  return $color;
}

function getGuildColor($guildid)
{
  global $db;

  $query = sprintf("SELECT leaderguid FROM guild WHERE guildid = %d", $guildid);
  $row = $db->query($query)->fetch_row();//*/

  return getPlayerColor($row[0]);
}

function getFactionScores($time_cond, $level_cond, $type_cond)
{
  global $db, $ALLIANCE, $HORDE;

  $score = array();

  if ($time_cond != "")
    $time_cond = "AND " . $time_cond;

  if ($level_cond != "")
    $level_cond = "AND " . $level_cond;

  if ($type_cond != "")
    $type_cond = "AND " . $type_cond;

  $query = sprintf("SELECT COUNT(*) FROM pvpstats_battlegrounds WHERE winner_faction = %d %s %s %s UNION SELECT COUNT(*) FROM pvpstats_battlegrounds WHERE winner_faction = %d %s %s %s;",
                   $ALLIANCE,
                   $time_cond,
                   $level_cond,
                   $type_cond,
                   $HORDE,
                   $time_cond,
                   $level_cond,
                   $type_cond);

  $result = $db->query($query);

  if (!$result)
    die("Error querying: " . $query);

  $row = $result->fetch_row();
  $score[0] = $row[0];

  $row = $result->fetch_row();

  if ($row != null)
    $score[1] = $row[0];
  else
    $score[1] = $score[0];

  return $score;
}

function getPlayersScores($time_cond, $level_cond, $type_cond)
{
  global $db, $limit, $players_group_and_order, $armory_url, $ALLIANCE, $HORDE, $ALLIANCE_RACES, $HORDE_RACES;

  if ($time_cond != "")
    $time_cond = "AND " . $time_cond;

  if ($level_cond != "")
    $level_cond = "AND " . $level_cond;

  if ($type_cond != "")
    $type_cond = "AND " . $type_cond;


  $query = sprintf("SELECT character_guid, count(character_guid) FROM pvpstats_players INNER JOIN pvpstats_battlegrounds ON pvpstats_players.battleground_id = pvpstats_battlegrounds.id INNER JOIN characters ON pvpstats_players.character_guid = characters.guid WHERE ((characters.race IN (%s) AND pvpstats_battlegrounds.winner_faction = %d ) OR (characters.race IN (%s) AND pvpstats_battlegrounds.winner_faction = %d )) %s %s %s %s %s",
                   $ALLIANCE_RACES,
                   $ALLIANCE,
                   $HORDE_RACES,
                   $HORDE,
                   $time_cond,
                   $level_cond,
                   $type_cond,
                   $players_group_and_order,
                   $limit);

  $result = $db->query($query);

  if (!$result)
    die("Error querying: " . $query);

  $row = $result->fetch_row();

  if ($row == null)
    return;

  $position = 1;

  printf("<tr><td>%d</td><td><a style=\"color: %s; \" target=\"_blank\" href=\"%s%s\"><strong>%s</strong></a></td><td style=\"min-width: 46px; padding-left: 0; padding-right: 0;\"><img src=\"img/class/%d.gif\"> <img src=\"img/race/%d-%d.gif\"></td><td>%d</td></tr>",
         $position,
         getPlayerColor($row[0]),
         $armory_url,
         getPlayerName($row[0]),
         getPlayerName($row[0]),
         getPlayerClass($row[0]),
         getPlayerRace($row[0]),
         getPlayerGender($row[0]),
         $row[1]);

  $prev_score = $row[1];


  while (($row = $result->fetch_row()) != null)
  {
    if ($prev_score != $row[1])
      $position++;

    printf("<tr><td>%d</td><td><a style=\"color: %s; \" target=\"_blank\" href=\"%s%s\"><strong>%s</strong></a></td><td style=\"min-width: 46px; padding-left: 0; padding-right: 0;\"><img src=\"img/class/%d.gif\"> <img src=\"img/race/%d-%d.gif\"></td><td>%d</td></tr>",
           $position,
           getPlayerColor($row[0]),
           $armory_url,
           getPlayerName($row[0]),
           getPlayerName($row[0]),
           getPlayerClass($row[0]),
           getPlayerRace($row[0]),
           getPlayerGender($row[0]),
           $row[1]);

    $prev_score = $row[1];
  }
}

function getGuildsScores($time_cond, $level_cond, $type_cond)
{
  global $db, $limit, $limit_guilds, $guilds_group_and_order, $guild_amory_url, $ALLIANCE, $HORDE, $ALLIANCE_RACES, $HORDE_RACES;

  if ($time_cond != "")
    $time_cond = "AND " . $time_cond;

  if ($level_cond != "")
    $level_cond = "AND " . $level_cond;

  if ($type_cond != "")
    $type_cond = "AND " . $type_cond;

  $query = sprintf("SELECT guild.name, COUNT(guild.name), guild.guildid FROM pvpstats_players INNER JOIN pvpstats_battlegrounds ON pvpstats_players.battleground_id = pvpstats_battlegrounds.id INNER JOIN guild_member ON guild_member.guid = pvpstats_players.character_guid INNER JOIN guild ON guild_member.guildid = guild.guildid INNER JOIN characters ON pvpstats_players.character_guid = characters.guid WHERE ((characters.race IN (%s) AND pvpstats_battlegrounds.winner_faction = %d ) OR (characters.race IN (%s) AND pvpstats_battlegrounds.winner_faction = %d )) %s %s %s %s %s",
                   $ALLIANCE_RACES,
                   $ALLIANCE,
                   $HORDE_RACES,
                   $HORDE,
                   $time_cond,
                   $level_cond,
                   $type_cond,
                   $guilds_group_and_order,
                   $limit_guilds);

  $result = $db->query($query);

  if (!$result)
    die("Error querying: " . $query);

  $row = $result->fetch_row();

  if ($row == null)
    return;

  $position = 1;

  printf("<tr><td>%d</td><td><a style=\"color: %s; \" target=\"_blank\" href=\"%s%s\"><strong>%s</strong></a></td><td>%d</td></tr>",
         $position,
         getGuildColor($row[2]),
         $guild_amory_url,
         $row[0],
         $row[0],
         $row[1]);

  $prev_score = $row[1];


  while (($row = $result->fetch_row()) != null)
  {
    if ($prev_score != $row[1])
      $position++;

    printf("<tr><td>%d</td><td><a style=\"color: %s; \" target=\"_blank\" href=\"%s%s\"><strong>%s</strong></a></td><td>%d</td></tr>",
           $position,
           getGuildColor($row[2]),
           $guild_amory_url,
           $row[0],
           $row[0],
           $row[1]);

    $prev_score = $row[1];
  }
}

function getGuildsMembers($battleground_id)
{
  global $db, $limit_guilds, $guilds_group_and_order, $guild_amory_url;

  $query = sprintf("SELECT guild.name, COUNT(guild.name), guild.guildid FROM pvpstats_players INNER JOIN pvpstats_battlegrounds ON pvpstats_players.battleground_id = pvpstats_battlegrounds.id INNER JOIN guild_member ON guild_member.guid = pvpstats_players.character_guid INNER JOIN guild ON guild_member.guildid = guild.guildid INNER JOIN characters ON pvpstats_players.character_guid = characters.guid WHERE pvpstats_battlegrounds.id = %s %s",
                   $battleground_id,
                   $guilds_group_and_order);

  $result = $db->query($query);

  if (!$result)
    die("Error querying: " . $query);

  $row = $result->fetch_row();

  if ($row == null)
    return;

  $position = 1;

  printf("<tr><td>%d</td><td><a style=\"color: %s; \" target=\"_blank\" href=\"%s%s\"><strong>%s</strong></a></td><td>%d</td></tr>",
         $position,
         getGuildColor($row[2]),
         $guild_amory_url,
         $row[0],
         $row[0],
         $row[1]);

  $prev_score = $row[1];


  while (($row = $result->fetch_row()) != null)
  {
    if ($prev_score != $row[1])
      $position++;

    printf("<tr><td>%d</td><td><a style=\"color: %s; \" target=\"_blank\" href=\"%s%s\"><strong>%s</strong></a></td><td>%d</td></tr>",
           $position,
           getGuildColor($row[2]),
           $guild_amory_url,
           $row[0],
           $row[0],
           $row[1]);

    $prev_score = $row[1];
  }
}

function getBattleGroundsOfDay($date)
{
  global $db, $time_format, $ALLIANCE, $HORDE, $alliance_color, $horde_color;

  $query = sprintf("SELECT * FROM pvpstats_battlegrounds WHERE DATE(date) = DATE('%s') ORDER BY date DESC;",
                  $date);

  $result = $db->query($query);

  if (!$result)
    die("Error querying: " . $query);

  while (($row = $result->fetch_array()) != null)
  {
    $datetime = new DateTime($row['date']);
    $time = $datetime->format($time_format);

    if ($row['winner_faction'] == $ALLIANCE)
      $color = $alliance_color;
    else if ($row['winner_faction'] == $HORDE)
      $color = $horde_color;

    printf("<tr style=\"color: %s; font-weight: bold;\" class=\"hover-pointer\" onClick=\"location.href='battleground.php?id=%s'\"><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr></a>",
           $color,
           $row['id'],
           $row['id'],
           getBattleGroundTypeShortName($row['type']),
           getLevelRangeByBracketId($row['bracket_id']),
           $time);
  }
}

function getBattleGrounds($day, $month, $year, $level_cond, $type_cond, $limit)
{
  global $db, $date_format, $time_format, $ALLIANCE, $HORDE, $alliance_color, $horde_color;

  if ($year != "")
    $year_cond = sprintf("YEAR(date) = '%s'", $year);
  else
    die("Function getBattleGrounds() called passing year = null");

  if ($month != "" && $month != 0)
    $month_cond = sprintf("AND MONTH(date) = '%s'", $month);
  else
    $month_cond = "";

  if ($day != "")
    $day_cond = sprintf("AND DAY(date) = '%s'", $day);
  else
    $day_cond = "";

  if ($level_cond != "")
    $level_cond = "AND " . $level_cond;
  else
    $level_cond = "";

  if ($type_cond != "")
    $type_cond = "AND " . $type_cond;
  else
    $type_cond = "";

  $query = sprintf("SELECT * FROM pvpstats_battlegrounds WHERE %s %s %s %s %s ORDER BY date DESC LIMIT 0, %d;",
                   $year_cond,
                   $month_cond,
                   $day_cond,
                   $level_cond,
                   $type_cond,
                   $limit);

  $result = $db->query($query);

  if (!$result)
    die("Error querying: " . $query);

  while (($row = $result->fetch_array()) != null)
  {
    $datetime = new DateTime($row['date']);
    $date = $datetime->format($date_format);
    $time = $datetime->format($time_format);

    if ($row['winner_faction'] == $ALLIANCE)
      $color = $alliance_color;
    else if ($row['winner_faction'] == $HORDE)
      $color = $horde_color;

    printf("<tr style=\"color: %s; font-weight: bold;\" class=\"hover-pointer\" onClick=\"location.href='battleground.php?id=%s'\"><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr></a>",
           $color,
           $row['id'],
           $row['id'],
           getBattleGroundTypeName($row['type']),
           getLevelRangeByBracketId($row['bracket_id']),
           $date,
           $time);
  }
}

function getLevelRangeByBracketId($bracket_id)
{
  global $expansion;

  if ($expansion < 3)
  {
    switch ($bracket_id)
    {
      case 1:
        return "10-19";
      case 2:
        return "20-29";
      case 3:
        return "30-39";
      case 4:
        return "40-49";
      case 5:
        return "50-59";
      case 6:
        if ($expansion > 0) return "60-69";
        return "60";
      case 7:
        if ($expansion > 1) return "70-79";
        return "70";
      case 8:
        return "80";
    }
  }
  else
  {
    switch ($bracket_id)
    {
      case 1:
        return "10-14";
      case 2:
        return "15-19";
      case 3:
        return "20-24";
      case 4:
        return "25-29";
      case 5:
        return "30-34";
      case 6:
        return "35-39";
      case 7:
        return "40-44";
      case 8:
        return "45-49";
      case 9:
        return "50-54";
      case 10:
        return "55-59";
      case 11:
        return "60-64";
      case 12:
        return "65-69";
      case 13:
        return "70-74";
      case 14:
        return "75-79";
      case 15:
        return "80-84";
      case 16:
        return "85";
    }
  }
}

function getBattleGroundTypeName($type)
{
  global $BATTLEGROUND_AV, $BATTLEGROUND_WS, $BATTLEGROUND_AB, $BATTLEGROUND_EY, $BATTLEGROUND_SA, $BATTLEGROUND_IC, $BATTLEGROUND_TP, $BATTLEGROUND_BFG;

  switch($type)
  {
    case $BATTLEGROUND_AV:
      return "Alterac Valley";
    case $BATTLEGROUND_WS:
      return "Warsong Gulch";
    case $BATTLEGROUND_AB:
      return "Arathi Basin";
    case $BATTLEGROUND_EY:
      return "Eye of the Storm";
    case $BATTLEGROUND_SA:
      return "Strand of the Ancients";
    case $BATTLEGROUND_IC:
      return "Isle of Conquest";
    case $BATTLEGROUND_TP:
      return "Twin Peaks";
    case $BATTLEGROUND_BFG:
      return "Battle For Gilneas";
  }
}

function getBattleGroundTypeShortName($type)
{
  global $BATTLEGROUND_AV, $BATTLEGROUND_WS, $BATTLEGROUND_AB, $BATTLEGROUND_EY, $BATTLEGROUND_SA, $BATTLEGROUND_IC, $BATTLEGROUND_TP, $BATTLEGROUND_BFG;

  switch($type)
  {
    case $BATTLEGROUND_AV:
      return "AV";
    case $BATTLEGROUND_WS:
      return "WG";
    case $BATTLEGROUND_AB:
      return "AB";
    case $BATTLEGROUND_EY:
      return "EotS";
    case $BATTLEGROUND_SA:
      return "SotA";
    case $BATTLEGROUND_IC:
      return "IoC";
    case $BATTLEGROUND_TP:
      return "TP";
    case $BATTLEGROUND_BFG:
      return "BFG";
  }
}

?>
