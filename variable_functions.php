<?php

/*
 * This file is a part of the Civ13 project.
 *
 * Copyright (c) 2022-present Valithor Obsidion <valithor@valzargaming.com>
 */

/*
 *
 * Ready Event
 *
*/

$on_ready = function ($civ13)
{
    $discord = $civ13->discord;
    $timer_function = $civ13->functions['misc']['timer_function'];
    
    $civ13->logger->info('logged in as ' . $discord->user->username . "#" . $discord->user->discriminator . ' (' . $discord->id . ')');
    $civ13->logger->info('------');
    
    if (! (isset($civ13->timers['relay_timer'])) || (! $civ13->timers['relay_timer'] instanceof React\EventLoop\Timer\Timer) ) {
        $civ13->logger->info('chat relay timer started');
        $civ13->timers['relay_timer'] = $discord->getLoop()->addPeriodicTimer(10, function() use ($timer_function, $civ13) {
            $timer_function($civ13);
        });
    }
};

$status_changer_random = function ($civ13)
{
    if ($status_path = $civ13->files['status_path']) {
        if ($file = fopen($status_path, 'r')) {
            while (($fp = fgets($file, 4096)) !== false) {
                $status_array[] = $fp;
            }
            if (count($status_array) > 0) {
                $line = explode(";", $status_array[rand(0, count($status_array)-1)]);
                $status = (string) $line[0];
                $type = (int) $line[1];
                $state = (string) $line[2];
            }
        } else $civ13->logger->warning("unable to open file " . $civ13->files['status_path'].PHP_EOL);
    } else $civ13->logger->warning('status_path is not defined'.PHP_EOL);
    
    if ($status) {
        $activity = new \Discord\Parts\User\Activity($civ13->discord, [ //Discord status            
            'name' => $status,
            'type' => $type, //0, 1, 2, 3, 4 | Game/Playing, Streaming, Listening, Watching, Custom Status
        ]);
        if($status_changer = $civ13->functions['misc']['status_changer'])
            $status_changer($civ13->discord, $activity, $state);
    }
};

$status_changer_timer = function ($civ13)
{
    if($status_changer_random = $civ13->functions['ready']['status_changer_random']);
        $civ13->timers['status_changer_timer'] = $civ13->discord->getLoop()->addPeriodicTimer(120, function() use ($civ13, $status_changer_random) {
        $status_changer_random($civ13);
    });
};

/*
 *
 * Message Event
 *
 */
 
$on_message = function ($civ13, $message)
{
    $discord = $civ13->discord;
    $loop = $civ13->loop;
    $command_symbol = $civ13->command_symbol;
    $owner_id = $civ13->owner_id;
    $admiral = $civ13->role_ids['admiral'];
    $captain = $civ13->role_ids['captain'];
    $knight = $civ13->role_ids['knight'];
    $veteran = $civ13->role_ids['veteran'];
    $infantry = $civ13->role_ids['infantry'];
    $insults_path = $civ13->files['insults_path'];
    $nomads_discord2ooc = $civ13->files['nomads_discord2ooc'];
    $tdm_discord2ooc = $civ13->files['tdm_discord2ooc'];
    $nomads_discord2admin = $civ13->files['nomads_discord2admin'];
    $tdm_discord2admin = $civ13->files['tdm_discord2admin'];
    $nomads_discord2dm = $civ13->files['nomads_discord2dm'];
    $tdm_discord2dm = $civ13->files['tdm_discord2dm'];
    $nomads_discord2ban = $civ13->files['nomads_discord2ban'];
    $tdm_discord2ban = $civ13->files['tdm_discord2ban'];
    $nomads_discord2unban = $civ13->files['nomads_discord2unban'];
    $tdm_discord2unban = $civ13->files['tdm_discord2unban'];
    $nomads_whitelist = $civ13->files['nomads_whitelist'];
    $tdm_whitelist = $civ13->files['tdm_whitelist'];
    $nomads_bans = $civ13->files['nomads_bans'];
    $tdm_bans = $civ13->files['tdm_bans'];
    $nomads_updateserverabspaths = $civ13->files['nomads_updateserverabspaths'];
    $nomads_serverdata = $civ13->files['nomads_serverdata'];
    $nomads_dmb = $civ13->files['nomads_dmb'];
    $nomads_killsudos = $civ13->files['nomads_killsudos'];
    $nomads_killciv13 = $civ13->files['nomads_killciv13'];
    $nomads_mapswap = $civ13->files['nomads_mapswap'];
    $tdm_mapswap = $civ13->files['tdm_mapswap'];
    $tdm_updateserverabspaths = $civ13->files['tdm_updateserverabspaths'];
    $tdm_serverdata = $civ13->files['tdm_serverdata'];
    $tdm_dmb = $civ13->files['tdm_dmb'];
    $tdm_killsudos = $civ13->files['tdm_killsudos'];
    $tdm_killciv13 = $civ13->files['tdm_killciv13'];
    $nomads_ip = $civ13->ips['nomads_ip'];
    $nomads_port = $civ13->ports['nomads_port'];
    $tdm_ip = $civ13->ips['tdm_ip'];
    $tdm_port = $civ13->ports['tdm_port'];
    
    if ($message->guild->owner_id != $owner_id) return; //Only process commands from a guild that Taislin owns
    if (!$command_symbol) $command_symbol = '!s';
    
    $author_user = $message->author; //This will need to be updated in a future release of DiscordPHP
    if ($author_member = $message->member) {
        $author_perms = $author_member->getPermissions($message->channel); //Populate permissions granted by roles
        $author_guild = $message->guild ?? $discord->guilds->offsetGet($message->guild_id);
    }
    
    $message_content = '';
    $message_content_lower = '';
    if (str_starts_with($message->content, $command_symbol . ' ')) { //Add these as slash commands?
        $message_content = substr($message->content, strlen($command_symbol)+1);
        $message_content_lower = strtolower($message_content);
    } elseif (str_starts_with($message->content, '<@!' . $discord->id . '>')) { //Add these as slash commands?
        $message_content = trim(substr($message->content, strlen($discord->id)+4));
        $message_content_lower = strtolower($message_content);
    } elseif (str_starts_with($message->content, '<@' . $discord->id . '>')) { //Add these as slash commands?
        $message_content = trim(substr($message->content, strlen($discord->id)+3));
        $message_content_lower = strtolower($message_content);
    }
    if (! $message_content) return;

    if (str_starts_with($message_content_lower, 'ping')) {
        $message->reply('Pong!');
        return;
    }
    if (str_starts_with($message_content_lower, 'help')) {
        $message->reply('**List of Commands**: bancheck, insult, cpu, ping, (un)whitelistme, rankme, ranking. **Staff only**: ban, hostciv, killciv, restartciv, mapswap, hosttdm, killtdm, restarttdm, tdmmapswap');
        return;
    }
    
    if (str_starts_with($message_content_lower,'cpu')) {
        if (substr(php_uname(), 0, 7) == "Windows") {
            $p = shell_exec('powershell -command "gwmi Win32_PerfFormattedData_PerfOS_Processor | select PercentProcessorTime"');
            $p = preg_replace('/\s+/', ' ', $p); //reduce spaces
            $p = str_replace("PercentProcessorTime", "", $p);
            $p = str_replace("--------------------", "", $p);
            $p = preg_replace('/\s+/', ' ', $p); //reduce spaces
            $load_array = explode(" ", $p);

            $x=0;
            foreach ($load_array as $line) {
                if ($line != " " && $line != "") {
                    if ($x==0) {
                        $load = "CPU Usage: $line%" . PHP_EOL;
                        break;
                    }
                    if ($x!=0) {
                        //$load = $load . "Core $x: $line%" . PHP_EOL; //No need to report individual cores right now
                    }
                    $x++;
                }
            }
            return $message->channel->sendMessage($load);
        } else { //Linux
            $cpu_load = '-1';
            if ($cpu_load_array = sys_getloadavg())
                $cpu_load = array_sum($cpu_load_array) / count($cpu_load_array);
            return $message->channel->sendMessage('CPU Usage: ' . $cpu_load . "%");
        }
        return $message->channel->sendMessage('Unrecognized operating system!');
    }
    
    if (str_starts_with($message_content_lower, 'insult')) {
        $split_message = explode(' ', $message_content); //$split_target[1] is the target
        if ((count($split_message) > 1 ) && strlen($split_message[1] > 0)) {
            $incel = $split_message[1];
            $insults_array = array();
            
            if ($file = fopen($insults_path, 'r')) {
                while (($fp = fgets($file, 4096)) !== false) {
                    if (trim(strtolower($fp)) == trim(strtolower($incel)))
                        $insults_array[] = $insult;
                }
                if (count($insults_array > 0)) {
                    $insult = $insults_array[rand(0, count($insults_array)-1)];
                    return $message->channel->sendMessage("$incel, $insult");
                }
            } else return $message->channel->sendMessage("Unable to access `$insults_path`");
        }
        return;
    }
    if (str_starts_with($message_content_lower, 'ooc ')) {
        $message_filtered = substr($message_content, 4);
        switch (strtolower($message->channel->name)) {
            case 'ooc-nomads':                    
                $file = fopen($nomads_discord2ooc, "a");
                $txt = $message->author->username . ":::$message_filtered" . PHP_EOL;
                fwrite($file, $txt);
                fclose($file);
                break;
            case 'ooc-tdm':
                $file = fopen($tdm_discord2ooc, "a");
                $txt = $message->author->username . ":::$message_filtered" . PHP_EOL;
                fwrite($file, $txt);
                fclose($file);
                break;
            default:
                $message->reply('You need to be in either the #ooc-nomads or #ooc-tdm channel to use this command.');
        }
        return;
    }
    if (str_starts_with($message_content_lower, 'asay ')) {
        $message_filtered = substr($message_content, 5);
        switch (strtolower($message->channel->name)) {
            case 'ahelp-nomads':
                $file = fopen($nomads_discord2admin, "a");
                $txt = $message->author->username . ":::$message_filtered" . PHP_EOL;
                fwrite($file, $txt);
                fclose($file);
                break;
            case 'ahelp-tdm':
                $file = fopen($tdm_discord2admin, "a");
                $txt = $message->author->username . ":::$message_filtered" . PHP_EOL;
                fwrite($file, $txt);
                fclose($file);
                break;
            default:
                $message->reply('You need to be in either the #ahelp-nomads or #ahelp-tdm channel to use this command.');
        }
        return;
    }
    if (str_starts_with($message_content_lower, 'dm ') || str_starts_with($message_content_lower, 'pm ')) {
        $message_content = substr($message_content, 3);
        $split_message = explode(": ", $message_content);
        switch (strtolower($message->channel->name)) {
            case 'ahelp-nomads':
                $file = fopen($nomads_discord2dm, "a");
                $txt = $message->author->username.":::".$split_message[0].":::".$split_message[1].PHP_EOL;
                fwrite($file, $txt);
                fclose($file);
                break;
            case 'ahelp-tdm':
                $file = fopen($tdm_discord2dm, "a");
                $txt = $message->author->username.":::".$split_message[0].":::".$split_message[1].PHP_EOL;
                fwrite($file, $txt);
                fclose($file);
                break;
            default:
                $message->reply('You need to be in either the #ahelp-nomads or #ahelp-tdm channel to use this command.');
        }
        return;
    }
    if (str_starts_with($message_content_lower, 'ban ')) {
        $message_content = substr($message_content, 4);
        $split_message = explode('; ', $message_content); //$split_target[1] is the target
        if (! $split_message[0]) return $message->reply('Missing ban ckey! Please use the format `ban ckey; duration; reason`');
        if (! $split_message[1]) return $message->reply('Missing ban duration! Please use the format `ban ckey; duration; reason`');
        if (! $split_message[2]) return $message->reply('Missing ban reason! Please use the format `ban ckey; duration; reason`');
        $file = fopen($nomads_discord2ban, "a");
        $txt = $message->author->username.":::".$split_message[0].":::".$split_message[1].":::".$split_message[2].PHP_EOL;
        fwrite($file, $txt);
        fclose($file);
        
        $file = fopen($tdm_discord2ban, "a");
        $txt = $message->author->username.":::".$split_message[0].":::".$split_message[1].":::".$split_message[2].PHP_EOL;
        fwrite($file, $txt);
        fclose($file);
        $result = '**' . $message->member->username . '#' . $message->member->discriminator . '** banned **' . $split_message[0] . '** for **' . $split_message[1] . '** with the reason **' . $split_message[2] . '**.';
        return $message->channel->sendMessage($result);
    }
    if (str_starts_with($message_content_lower, 'unban ')) {
        $message_content = substr($message_content, 6);
        $split_message = explode('; ', $message_content);
        
        $file = fopen($nomads_discord2unban, "a");
        $txt = $message->author->username . "#" . $message->author->discriminator . ":::".$split_message[0];
        fwrite($file, $txt);
        fclose($file);
        
        $file = fopen($tdm_discord2unban, "a");
        $txt = $message->author->username . "#" . $message->author->discriminator . ":::".$split_message[0];
        fwrite($file, $txt);
        fclose($file);

        $result = '**' . $message->author->username . '** unbanned **' . $split_message[0] . '**.';
        return $message->channel->sendMessage($result);
    }
    #whitelist
    if (str_starts_with($message_content_lower, 'whitelistme')) {
        $split_message = trim(substr($message_content, 11));
        if (strlen($split_message) > 0) { // if len($split_message) > 1 and len($split_message[1]) > 0:
            $ckey = $split_message;
            $ckey = strtolower($ckey);
            $ckey = str_replace('_', '', $ckey);
            $ckey = str_replace(' ', '', $ckey);
            $accepted = false;
            if ($author_member = $message->member) {
                foreach ($author_member->roles as $role) {
                    switch ($role->id) {
                        case $admiral:
                        case $captain:
                        case $knight:
                        case $veteran:
                            $accepted = true;
                    }
                }
                if ($accepted) {
                    $found = false;
                    $whitelist1 = fopen($nomads_whitelist, "r") ?? NULL;
                    if ($whitelist1) {
                        while (($fp = fgets($whitelist1, 4096)) !== false) {
                            $line = trim(str_replace(PHP_EOL, "", $fp));
                            $linesplit = explode(";", $line);
                            foreach ($linesplit as $split) {
                                if ($split == $ckey)
                                    $found = true;
                            }
                        }
                        fclose($whitelist1);
                    }
                    $whitelist2 = fopen($tdm_whitelist, "r") ?? NULL;
                    if ($whitelist2) {
                        while (($fp = fgets($whitelist2, 4096)) !== false) {
                            $line = trim(str_replace(PHP_EOL, "", $fp));
                            $linesplit = explode(";", $line);
                            foreach ($linesplit as $split)
                                if ($split == $ckey)
                                    $found = true;
                        }
                        fclose($whitelist2);
                    }
                    
                    if (!$found) {
                        $found2 = false;
                        $whitelist1 = fopen($nomads_whitelist, "r") ?? NULL;
                        if ($whitelist1) {
                            while (($fp = fgets($whitelist1, 4096)) !== false) {
                                $line = trim(str_replace(PHP_EOL, "", $fp));
                                $linesplit = explode(";", $line);
                                foreach ($linesplit as $split) {
                                    if ($split == $message->member->username)
                                        $found2 = true;
                                }
                            }
                        fclose($whitelist1);
                        }
                    } else return $message->channel->sendMessage("$ckey is already in the whitelist!");
                    
                    $txt = $ckey."=".$message->member->username.PHP_EOL;
                    if ($whitelist1 = fopen($nomads_whitelist, "a")) {
                        fwrite($whitelist1, $txt);
                        fclose($whitelist1);
                    }
                    if ($whitelist2 = fopen($tdm_whitelist, "a")) {
                        fwrite($whitelist2, $txt);
                        fclose($whitelist2);
                    }
                    return $message->channel->sendMessage("$ckey has been added to the whitelist.");
                } else return $message->channel->sendMessage('Rejected! You need to have at least the [' . $author_guild->roles ? $author_guild->roles->offsetGet("$veteran")->name : "Veteran" . '] rank.');
            } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');
        } else return $message->channel->sendMessage("Wrong format. Please try '!s whitelistme [ckey].'");
        return;
    }
    if (str_starts_with($message_content_lower, 'unwhitelistme')) {
        $accepted = false;
        if ($author_member = $message->member) {
            foreach ($author_member->roles as $role) {
                switch ($role->id) {
                    case $admiral:
                    case $captain:
                    case $knight:
                    case $veteran:
                    case $infantry:
                        $accepted = true;
                }
            }
            if ($accepted) {
                $removed = "N/A";
                $lines_array = array();
                if ($wlist = fopen($nomads_whitelist, "r")) {
                    while (($fp = fgets($playerlogs, 4096)) !== false) {
                        $lines_array[] = $fp;
                    }
                    fclose($wlist);
                } else return $message->channel->sendMessage("Unable to access `$nomads_whitelist`");
                if ($count($lines_array) > 0) {
                    if ($wlist = fopen($nomads_whitelist, "w")) {
                        foreach ($lines_array as $line)
                            if (!str_contains($line, $message->member->username)) {
                                fwrite($wlist, $line);
                            } else {
                                $removed = explode('=', $line);
                                $removed = $removed[0];
                            }
                        fclose($wlist);
                    } else return $message->channel->sendMessage("Unable to access `$nomads_whitelist.txt`");
                }
                
                $lines_array = array();
                if ($wlist = fopen($tdm_whitelist, "r")) {
                    while (($fp = fgets($playerlogs, 4096)) !== false) {
                        $lines_array[] = $fp;
                    }
                    fclose($wlist);
                } else return $message->channel->sendMessage("Unable to access `$tdm_whitelist`");
                if ($count($lines_array) > 0) {
                    if ($wlist = fopen($tdm_whitelist, "w")) {
                        foreach ($lines_array as $line)
                            if (!str_contains($line, $message->member->username)) {
                                fwrite($wlist, $line);
                            } else {
                                $removed = explode('=', $line);
                                $removed = $removed[0];
                            }
                        fclose($wlist);
                    } else return $message->channel->sendMessage("Unable to access `$tdm_whitelist`");
                }
                return $message->channel->sendMessage("Ckey $removed has been removed from the whitelist.");
            } else return $message->channel->sendMessage('Rejected! You need to have at least the [' . $author_guild->roles ? $author_guild->roles->offsetGet("$veteran")->name : "Veteran" . '] rank.');
        } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');
        return;
    }
    if (str_starts_with($message_content_lower, 'hostciv')) {
        $accepted = false;
        if ($author_member = $message->member) {
            foreach ($author_member->roles as $role) {
                switch ($role->id) {
                    case $admiral:
                    case $captain:
                        $accepted = true;
                }
            }
            if ($accepted) {
                $message->channel->sendMessage("Please wait, updating the code...");
                \execInBackground("python3 $nomads_updateserverabspaths");
                $message->channel->sendMessage("Updated the code.");
                \execInBackground("rm -f $nomads_serverdata");
                \execInBackground("DreamDaemon $nomads_dmb $nomads_port -trusted -webclient -logself &");
                $message->channel->sendMessage("Attempted to bring up Civilization 13 (Main Server) <byond://$nomads_ip:$nomads_port>");
                $discord->getLoop()->addTimer(10, function() use ($nomads_killsudos) { # ditto
                    \execInBackground("python3 $nomads_killsudos");
                });
            } else return $message->channel->sendMessage('Rejected! You need to have at least the [' . $author_guild->roles ? $author_guild->roles->offsetGet("$captain")->name : "Captain" . '] rank.');
        } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');
        return;
    }
    if (str_starts_with($message_content_lower, 'killciv')) {
        $accepted = false;
        if ($author_member = $message->member) {
            foreach ($author_member->roles as $role) {
                switch ($role->id) {
                    case $admiral:
                    case $captain:
                        $accepted = true;
                }
            }
            if ($accepted) {
                \execInBackground("python3 $nomads_killciv13");
                return $message->channel->sendMessage("Attempted to kill Civilization 13 Server.");
            } else return $message->channel->sendMessage("Denied!");
        } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');
        return;
    }
    if (str_starts_with($message_content_lower, 'restartciv')) {
        $accepted = false;
        if ($author_member = $message->member) {
            foreach ($author_member->roles as $role) {
                switch ($role->id) {
                    case $admiral:
                    case $captain:
                        $accepted = true;
                }
            }
            if ($accepted) {
                \execInBackground("python3 $nomads_killciv13");
                $message->channel->sendMessage("Attempted to kill Civilization 13 Server.");
                \execInBackground("python3 $nomads_updateserverabspaths");
                $message->channel->sendMessage("Updated the code.");
                \execInBackground("rm -f $nomads_serverdata");
                \execInBackground("DreamDaemon $nomads_dmb $nomads_port -trusted -webclient -logself &");
                $message->channel->sendMessage("Attempted to bring up Civilization 13 (Main Server) <byond://$nomads_ip:$nomads_port>");
                $discord->getLoop()->addTimer(10, function() use ($nomads_killsudos) { # ditto
                    \execInBackground("python3 $nomads_killsudos");
                });
            } else return $message->channel->sendMessage('Rejected! You need to have at least the [' . $author_guild->roles ? $author_guild->roles->offsetGet("$captain")->name : "Captain" . '] rank.');
        } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');
        return;
    }
    if (str_starts_with($message_content_lower, 'restarttdm')) {
        $accepted = false;
        if ($author_member = $message->member) {
            foreach ($author_member->roles as $role) {
                switch ($role->id) {
                    case $admiral:
                    case $captain:
                        $accepted = true;
                }
            }
            if ($accepted) {
                \execInBackground("python3 $tdm_killciv13");
                $message->channel->sendMessage("Attempted to kill Civilization 13 TDM Server.");
                \execInBackground("python3 $tdm_updateserverabspaths");
                $message->channel->sendMessage("Updated the code.");
                \execInBackground("rm -f $tdm_serverdata");
                \execInBackground("DreamDaemon $tdm_dmb $tdm_port -trusted -webclient -logself &");
                $discord->getLoop()->addTimer(10, function() use ($message, $tdm_ip, $tdm_port, $tdm_kills) { # ditto
                    $message->channel->sendMessage("Attempted to bring up Civilization 13 (TDM Server) <byond://$tdm_ip:$tdm_port>");
                    \execInBackground("python3 $tdm_killsudos");
                });
            } else return $message->channel->sendMessage('Rejected! You need to have at least the [' . $author_guild->roles->offsetGet("$captain")->name . '] rank.');
        } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');
        return;
    }
    if (str_starts_with($message_content_lower, 'mapswap')) {
        $accepted = false;
        if ($author_member = $message->member) {
            foreach ($author_member->roles as $role) {
                switch ($role->id) {
                    case $admiral:
                    case $captain:
                        $accepted = true;
                }
            }
            if ($accepted) {
                $split_message = explode("mapswap ", $message_content);
                if ((count($split_message) > 1) && (strlen($split_message[1]) > 0)) {
                    $mapto = $split_message[1];
                    $mapto = strtoupper($mapto);
                    $message->channel->sendMessage("Attempting to change map to $mapto");
                    \execInBackground("python3 $tdm_mapswap $mapto");
                    /*
                    $message->channel->sendMessage('Calling mapswap...');
                    $process = \mapswap($nomads_mapswap, $mapto);
                    $process->stdout->on('end', function () use ($message, $mapto) {
                        $message->channel->sendMessage("Attempting to change map to $mapto");
                    });
                    $process->stdout->on('error', function (Exception $e) use ($message, $mapto) {
                        $message->channel->sendMessage("Error changing map to $mapto: " . $e->getMessage());
                    });
                    $process->start();
                    */
                    
                }
            } else return $message->channel->sendMessage('Rejected! You need to have at least the [' . $author_guild->roles->offsetGet("$captain")->name . '] rank.');
        } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');
        return;
    }
    if (str_starts_with($message_content_lower, 'hosttdm')) {
        $accepted = false;
        if ($author_member = $message->member) {
            foreach ($author_member->roles as $role) {
                switch ($role->id) {
                    case $admiral:
                    case $captain:
                        $accepted = true;
                }
            }
            if ($accepted) {
                $message->channel->sendMessage("Please wait, updating the code...");
                \execInBackground("python3 $tdm_updateserverabspaths");
                $message->channel->sendMessage("Updated the code.");
                \execInBackground("rm -f $tdm_serverdata");
                \execInBackground("DreamDaemon $tdm_dmb $tdm_port -trusted -webclient -logself &");
                $message->channel->sendMessage("Attempted to bring up Civilization 13 (TDM Server) <byond://$tdm_ip:$tdm_port>");
                $discord->getLoop()->addTimer(10, function() use ($tdm_killsudos) { # ditto
                    \execInBackground("python3 $tdm_killsudos");
                });
            } else return $message->channel->sendMessage('Rejected! You need to have at least the [' . $author_guild->roles->offsetGet("$captain")->name . '] rank.');
        } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');
        return;
    }
    if (str_starts_with($message_content_lower, 'killtdm')) {
        $accepted = false;
        if ($author_member = $message->member) {
            foreach ($author_member->roles as $role) {
                switch ($role->id) {
                    case $admiral:
                    case $captain:
                        $accepted = true;
                }
            }
            if ($accepted) {
                \execInBackground("python3 $tdm_killciv13");
                return $message->channel->sendMessage("Attempted to kill Civilization 13 (TDM Server).");
            } else return $message->channel->sendMessage("Denied!");
        } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');
        return;
    }
    if (str_starts_with($message_content_lower, 'tdmmapswap')) {
        $accepted = false;
        if ($author_member = $message->member) {
            foreach ($author_member->roles as $role) {
                switch ($role->id) {
                    case $admiral:
                    case $captain:
                    case $knight:
                        $accepted = true;
                }
            }
            if ($accepted) {
                $split_message = explode("mapswap ", $message_content);
                if ((count($split_message) > 1) && (strlen($split_message[1]) > 0)) {
                    $mapto = $split_message[1];
                    $mapto = strtoupper($mapto);
                    $message->channel->sendMessage("Attempting to change map to $mapto");
                    \execInBackground("python3 $tdm_mapswap $mapto");
                    /*
                    $message->channel->sendMessage('Calling mapswap...');
                    $process = \mapswap($nomads_mapswap, $mapto);
                    $process->stdout->on('end', function () use ($message, $mapto) {
                        $message->channel->sendMessage("Attempting to change map to $mapto");
                    });
                    $process->stdout->on('error', function (Exception $e) use ($message, $mapto) {
                        $message->channel->sendMessage("Error changing map to $mapto: " . $e->getMessage());
                    });
                    $process->start();
                    */
                }
            } else return $message->channel->sendMessage('Rejected! You need to have at least the [' . $author_guild->roles->offsetGet("$knight")->name . '] rank.');
        } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');
        return;
    }
    
    if (str_starts_with($message_content_lower, "banlist")) {
        if ($author_member = $message->member) {
            foreach ($author_member->roles as $role) {
                switch ($role->id) {
                    case $admiral:
                    case $captain:
                    case $knight:
                        $accepted = true;
                }
            }
        }
        if ($accepted) {
            $builder = Discord\Builders\MessageBuilder::new();
            $builder->addFile($tdm_bans, 'bans.txt');
            return $message->channel->sendMessage($builder);
        } return $message->channel->sendMessage('Rejected! You need to have at least the [' . $author_guild->roles->offsetGet("$knight")->name . '] rank.');
    }
    
    if (str_starts_with($message_content_lower, "bancheck")) {
        $split_message = explode('bancheck ', $message_content);
        if ((count($split_message) > 1) && (strlen($split_message[1]) > 0)) {
            $ckey = trim($split_message[1]);
            $ckey = strtolower($ckey);
            $ckey = str_replace('_', '', $ckey);
            $ckey = str_replace(' ', '', $ckey);
            $banreason = "unknown";
            $found = false;
            $filecheck1 = fopen($nomads_bans, "r") ?? NULL;
            if ($filecheck1) {
                while (($fp = fgets($filecheck1, 4096)) !== false) {
                    str_replace(PHP_EOL, "", $fp);
                    $filter = "|||";
                    $line = trim(str_replace($filter, "", $fp));
                    $linesplit = explode(";", $line); //$split_ckey[0] is the ckey
                    if ((count($linesplit)>=8) && ($linesplit[8] == $ckey)) {
                        $found = true;
                        $banreason = $linesplit[3];
                        $bandate = $linesplit[5];
                        $banner = $linesplit[4];
                        $message->channel->sendMessage("**$ckey** has been banned from **Nomads** on **$bandate** for **$banreason** by $banner.");
                    }
                }
                fclose($filecheck1);
            }
            $filecheck2 = fopen($tdm_bans, "r") ?? NULL;
            if ($filecheck2) {
                while (($fp = fgets($filecheck2, 4096)) !== false) {
                    str_replace(PHP_EOL, "", $fp);
                    $filter = "|||";
                    $line = trim(str_replace($filter, "", $fp));
                    $linesplit = explode(";", $line); //$split_ckey[0] is the ckey
                    if ((count($linesplit)>=8) && ($linesplit[8] == $ckey)) {
                        $found = true;
                        $banreason = $linesplit[3];
                        $bandate = $linesplit[5];
                        $banner = $linesplit[4];
                        $message->channel->sendMessage("**$ckey** has been banned from **TDM** on **$bandate** for **$banreason** by $banner.");
                    }
                }
                fclose($filecheck2);
            }
            if (!$found) return $message->channel->sendMessage("No bans were found for **$ckey**.");
        } else return  $message->channel->sendMessage("Wrong format. Please try '!s bancheck [ckey].'");
        return;
    }
    if (str_starts_with($message_content_lower,'serverstatus')) { //See GitHub Issue #1
        $embed = $discord->factory(\Discord\Parts\Embed\Embed::class);
        $_1714 = !\portIsAvailable(1714);
        $server_is_up = $_1714;
        if (!$server_is_up) {
            $embed->setColor(0x00ff00);
            $embed->addFieldValues("TDM Server Status", "Offline");
            #$message->channel->sendEmbed($embed);
            #return;
        } else {
            $data = "None";
            if ($_1714) {
                if (!$data = file_get_contents($tdm_serverdata))
                    return $message->channel->sendMessage("Unable to access `$tdm_serverdata`");
            } else {
                $embed->setColor(0x00ff00);
                $embed->addFieldValues("TDM Server Status", "Offline");
                #$message->channel->sendEmbed($embed);
                #return;
            }
            $data = str_replace('<b>Address</b>: ', '', $data);
            $data = str_replace('<b>Map</b>: ', '', $data);
            $data = str_replace('<b>Gamemode</b>: ', '', $data);
            $data = str_replace('<b>Players</b>: ', '', $data);
            $data = str_replace('</b>', '', $data);
            $data = str_replace('<b>', '', $data);
            $data = explode(';', $data);
            #embed = discord.Embed(title="**Civ13 Bot**", color=0x00ff00)
            $embed->setColor(0x00ff00);
            $embed->addFieldValues("TDM Server Status", "Online");
            if (isset($data[1])) $embed->addFieldValues("Address", '<'.$data[1].'>');
            if (isset($data[2])) $embed->addFieldValues("Map", $data[2]);
            if (isset($data[3])) $embed->addFieldValues("Gamemode", $data[3]);
            if (isset($data[4])) $embed->addFieldValues("Players", $data[4]);

            #$message->channel->sendEmbed($embed);
            #return;
        }
        $_1715 = !\portIsAvailable(1715);
        $server_is_up = ($_1715);
        if (!$server_is_up) {
            $embed->setColor(0x00ff00);
            $embed->addFieldValues("Nomads Server Status", "Offline");
            #$message->channel->sendEmbed($embed);
            #return;
        } else {
            $data = "None";
            if ($_1714) {
                if (!$data = file_get_contents($nomads_serverdata))
                    return $message->channel->sendMessage("Unable to access `$nomads_serverdata`");
            } else {
                $embed->setColor(0x00ff00);
                $embed->addFieldValues("Nomads Server Status", "Offline");
                #$message->channel->sendEmbed($embed);
                #return;
            }
            $data = str_replace('<b>Address</b>: ', '', $data);
            $data = str_replace('<b>Map</b>: ', '', $data);
            $data = str_replace('<b>Gamemode</b>: ', '', $data);
            $data = str_replace('<b>Players</b>: ', '', $data);
            $data = str_replace('</b>', '', $data);
            $data = str_replace('<b>', '', $data);
            $data = explode(';', $data);
            #embed = discord.Embed(title="**Civ13 Bot**", color=0x00ff00)
            $embed->setColor(0x00ff00);
            $embed->addFieldValues("Nomads Server Status", "Online");
            if (isset($data[1])) $embed->addFieldValues("Address", '<'.$data[1].'>');
            if (isset($data[2])) $embed->addFieldValues("Map", $data[2]);
            if (isset($data[3])) $embed->addFieldValues("Gamemode", $data[3]);
            if (isset($data[4])) $embed->addFieldValues("Players", $data[4]);
        }
        $message->channel->sendEmbed($embed);
        return;
    }
};

$on_message2 = function ($civ13, $message)
{
    $discord = $civ13->discord;
    $loop = $civ13->loop;
    $owner_id = $civ13->owner_id;
    $recalculate_ranking = $civ13->functions['misc']['recalculate_ranking'];
    $ranking_path = $civ13->files['ranking_path'];
    $tdm_awards_path = $civ13->files['tdm_awards_path'];
    $tdm_awards_br_path = $civ13->files['tdm_awards_br_path'];
    $typespess_path = $civ13->files['typespess_path'];
    $typespess_launch_server_path = $civ13->files['typespess_launch_server_path'];
    $command_symbol = $civ13->command_symbol;
    
    if ($message->guild->owner_id != $owner_id) return; //Only process commands from a guild that Taislin owns
    if (!$command_symbol) $command_symbol = '!s';
    
    if (str_starts_with($message->content, $command_symbol . ' ')) { //Add these as slash commands?
        $message_content = substr($message->content, strlen($command_symbol)+1);
        $message_content_lower = strtolower($message_content);
        if (str_starts_with($message_content_lower, 'ranking')) {
            $recalculate_ranking($civ13);
            $line_array = array();
            if ($search = fopen($ranking_path, "r")) {
                while (($fp = fgets($search, 4096)) !== false) {
                    $line_array[] = $fp;
                }
                fclose($search);
            } else return $message->channel->sendMessage("Unable to access `$ranking_path`");
            $topsum = 1;
            $msg = '';
            for ($x=0;$x<count($line_array);$x++) {
                $line = $line_array[$x];
                if ($topsum <= 10) {
                    $line = trim(str_replace(PHP_EOL, "", $line));
                    $topsum += 1;
                    $sline = explode(';', $line);
                    $msg .= "(". ($topsum - 1) ."): **".$sline[1]."** with **".$sline[0]."** points." . PHP_EOL;
                } else break;
            }
            if ($msg != '') return $message->channel->sendMessage($msg);
        }
        if (str_starts_with($message_content_lower, 'rankme')) {
            $split_message = explode('rankme ', $message_content);
            $ckey = "";
            $medal_s = 0;
            $result = "";
            if ((count($split_message) > 1) && (strlen($split_message[1]) > 0)) {
                $ckey = $split_message[1];
                $ckey = strtolower($ckey);
                $ckey = str_replace('_', '', $ckey);
                $ckey = str_replace(' ', '', $ckey);
            }
            $recalculate_ranking($civ13);
            $line_array = array();
            if ($search = fopen($ranking_path, "r")) {
                while (($fp = fgets($search, 4096)) !== false) {
                    $line_array[] = $fp;
                }
                fclose($search);
            } else return $message->channel->sendMessage("Unable to access `$ranking_path`");
            $found = 0;
            $result = '';
            for ($x=0;$x<count($line_array);$x++) {
                $line = $line_array[$x];
                $line = trim(str_replace(PHP_EOL, "", $line));
                $sline = explode(';', $line);
                if ($sline[1] == $ckey) {
                    $found = 1;
                    $result .= "**" . $sline[1] . "**" . " has a total rank of **" . $sline[0] . "**.";
                };
            }
            if (!$found) return $message->channel->sendMessage("No medals found for this ckey.");
            return $message->channel->sendMessage($result);
        }
        if (str_starts_with($message_content_lower, 'medals')) {
            $split_message = explode('medals ', $message_content);
            $ckey = "";
            if ((count($split_message) > 1) && (strlen($split_message[1]) > 0)) {
                $ckey = $split_message[1];
                $ckey = strtolower($ckey);
                $ckey = str_replace('_', '', $ckey);
                $ckey = str_replace(' ', '', $ckey);
            }
            $result = '';
            $search = fopen($tdm_awards_path, 'r');
            $found = false;
            while(! feof($search)) {
                $line = fgets($search);
                $line = trim(str_replace(PHP_EOL, "", $line)); # remove '\n' at end of line
                if (str_contains($line, $ckey)) {
                    $found = true;
                    $duser = explode(';', $line);
                    if ($duser[0] == $ckey) {
                        $medal_s = "<:long_service:705786458874707978>";
                        if ($duser[2] == "long service medal")
                            $medal_s = "<:long_service:705786458874707978>";
                        if ($duser[2] == "combat medical badge")
                            $medal_s = "<:combat_medical_badge:706583430141444126>";
                        if ($duser[2] == "tank destroyer silver badge")
                            $medal_s = "<:tank_silver:705786458882965504>";
                        if ($duser[2] == "tank destroyer gold badge")
                            $medal_s = "<:tank_gold:705787308926042112>";
                        if ($duser[2] == "assault badge")
                            $medal_s = "<:assault:705786458581106772>";
                        if ($duser[2] == "wounded badge")
                            $medal_s = "<:wounded:705786458677706904>";
                        if ($duser[2] == "wounded silver badge")
                            $medal_s = "<:wounded_silver:705786458916651068>";
                        if ($duser[2] == "wounded gold badge")
                            $medal_s = "<:wounded_gold:705786458845216848>";
                        if ($duser[2] == "iron cross 1st class")
                            $medal_s = "<:iron_cross1:705786458572587109>";
                        if ($duser[2] == "iron cross 2nd class")
                            $medal_s = "<:iron_cross2:705786458849673267>";
                        $result .= "**" . $duser[1] . ":**" . " " . $medal_s . " **" . $duser[2] . "**, *" . $duser[4] . "*, " . $duser[5] . PHP_EOL;
                    }
                }
            }
            if ($result != '') return $message->channel->sendMessage($result);
            if (!$found && ($result == '')) return $message->channel->sendMessage("No medals found for this ckey.");
        }
        if (str_starts_with($message_content_lower, 'brmedals')) {
            $split_message = explode('brmedals ', $message_content);
            $ckey = "";
            if ((count($split_message) > 1) && (strlen($split_message[1]) > 0)) {
                $ckey = $split_message[1];
                $ckey = strtolower($ckey);
                $ckey = str_replace('_', '', $ckey);
                $ckey = str_replace(' ', '', $ckey);
            }
            $result = '';
            $search = fopen($tdm_awards_br_path, 'r');
            $found = false;
            while(! feof($search)) {
                $line = fgets($search);
                $line = trim(str_replace(PHP_EOL, "", $line)); # remove '\n' at end of line
                if (str_contains($line, $ckey)) {
                    $found = true;
                    $duser = explode(';', $line);
                    if ($duser[0] == $ckey) {
                        $result .= "**" . $duser[1] . ":** placed *" . $duser[2] . " of  ". $duser[5] . ",* on " . $duser[4] . " (" . $duser[3] . ")" . PHP_EOL;
                    }
                }
            }
            if ($result != '') return $message->channel->sendMessage($result);
            if (!$found && ($result == '')) return $message->channel->sendMessage("No medals found for this ckey.");
        }
        if (str_starts_with($message_content_lower, 'ts')) {
            $split_message = explode('ts ', $message_content);
            if ((count($split_message) > 1) && (strlen($split_message[1]) > 0)) {
                $state = $split_message[1];
                $accepted = false;
                
                if ($author_member = $message->member) {
                    foreach ($author_member->roles as $role) {
                        switch ($role->id) {
                            case $admiral:
                                $accepted = true;
                        }
                    }
                } else return $message->channel->sendMessage('Error! Unable to get Discord Member class.');

                if ($accepted) {
                    if ($state == "on") {
                        \execInBackground("cd $typespess_path");
                        \execInBackground('git pull');
                        \execInBackground("sh $typespess_launch_server_path &");
                        return $message->channel->sendMessage("Put **TypeSpess Civ13** test server on: http://civ13.com/ts");
                    } elseif ($state == "off") {
                        \execInBackground('killall index.js');
                        return $message->channel->sendMessage("**TypeSpess Civ13** test server down.");
                    }
                }
            }
        }
    }
};

/*
 *
 * Misc functions
 *
 */
$recalculate_ranking = function ($civ13)
{
    $tdm_awards_path = $civ13->files['tdm_awards_path'];
    $ranking_path = $civ13->files['ranking_path'];
    
    $ranking = array();
    $ckeylist = array();
    $result = array();
    
    if ($search = fopen($tdm_awards_path, "r")) {
        while(! feof($search)) {
            $medal_s = 0;
            $line = fgets($search);
            $line = trim(str_replace(PHP_EOL, "", $line)); # remove '\n' at end of line
            $duser = explode(';', $line);
            if ($duser[2] == "long service medal")
                $medal_s += 0.5;
            if ($duser[2] == "combat medical badge")
                $medal_s += 2;
            if ($duser[2] == "tank destroyer silver badge")
                $medal_s += 0.75;
            if ($duser[2] == "tank destroyer gold badge")
                $medal_s += 1.5;
            if ($duser[2] == "assault badge")
                $medal_s += 1.5;
            if ($duser[2] == "wounded badge")
                $medal_s += 0.5;
            if ($duser[2] == "wounded silver badge")
                $medal_s += 0.75;
            if ($duser[2] == "wounded gold badge")
                $medal_s += 1;
            if ($duser[2] == "iron cross 1st class")
                $medal_s += 3;
            if ($duser[2] == "iron cross 2nd class")
                $medal_s += 5;
            $result[] = $medal_s . ';' . $duser[0];
            if (!in_array($duser[0], $ckeylist))
                $ckeylist[] = $duser[0];
        }
    } else return $message->channel->sendMessage("Unable to access `$tdm_awards_path`");
    
    foreach ($ckeylist as $i) {
        $sumc = 0;
        foreach ($result as $j) {
            $sj = explode(';', $j);
            if ($sj[1] == $i)
                $sumc += (float) $sj[0];
        }
        $ranking[] = [$sumc,$i];
    }
    usort($ranking, function($a, $b) {
        return $a[0] <=> $b[0];
    });
    $sorted_list = array_reverse($ranking);
    if ($search = fopen($ranking_path, 'w')) {
        foreach ($sorted_list as $i)
            fwrite($search, $i[0] . ";" . $i[1] . PHP_EOL);
    } else return $message->channel->sendMessage("Unable to access `$ranking`");
    fclose ($search);
};

$ooc_relay = function ($civ13, $guild, string $file_path, string $channel_id)
{
    $filesystem = $civ13->filesystem;
     
    if ($file = fopen($file_path, "r+")) {
        while (($fp = fgets($file, 4096)) !== false) {
            $fp = str_replace(PHP_EOL, "", $fp);
            if ($target_channel = $guild->channels->offsetGet($channel_id)) $target_channel->sendMessage($fp);
            else $civ13->logger->warning("unable to find channel $target_channel");
        }
        ftruncate($file, 0); //clear the file
        fclose($file);
    } else $civ13->logger->warning("unable to open $file_path");

    /*
    echo '[RELAY - PATH] ' . $file_path . PHP_EOL;
    if ($target_channel = $guild->channels->offsetGet($channel_id)) {
        if ($file = $filesystem->file($file_path)) {
            $file->getContents()->then(
            function (string $contents) use ($file, $target_channel) {
                $promise = React\Async\async(function () use ($contents, $file, $target_channel) {
                    if ($contents) echo '[RELAY - CONTENTS] ' . $contents . PHP_EOL;
                    $lines = explode(PHP_EOL, $contents);
                    $promise2 = React\Async\async(function () use ($lines, $target_channel) {
                        foreach ($lines as $line) {
                            if ($line) {
                                echo '[RELAY - LINE] ' . $line . PHP_EOL;
                                $target_channel->sendMessage($line);
                            }
                        }
                        return;
                    })();
                    React\Async\await($promise2);
                })();
                $promise->then(function () use ($file) {
                    echo '[RELAY - TRUNCATE]' . PHP_EOL;
                    $file->putContents('');
                }, function (Exception $e) {
                    echo '[RELAY - ERROR] ' . $e->getMessage() . PHP_EOL;
                });
                React\Async\await($promise);
            })->then(function () use ($file) {
                echo '[RELAY - getContents]' . PHP_EOL;
            }, function (Exception $e) {
                echo '[RELAY - ERROR] ' . $e->getMessage() . PHP_EOL;
            });
        } else echo "[RELAY - ERROR] Unable to open $file_path" . PHP_EOL;
    } else echo "[RELAY - ERROR] Unable to get channel $channel_id" . PHP_EOL;
    */
    
    /*
    if ($target_channel = $guild->channels->offsetGet($channel_id)) {
        if ($file = $filesystem->file($file_path)) {
            $file->getContents()->then(function (string $contents) use ($file, $target_channel) {
                var_dump($contents);
                $contents = explode(PHP_EOL, $contents);
                foreach ($contents as $line) {
                    $target_channel->sendMessage($line);
                }
            })->then(
                function () use ($file) {
                    $file->putContents('');
                }, function (Exception $e) {
                    echo '[RELAY - getContents Error] ' . $e->getMessage() . PHP_EOL;
                }
            )->done();
        } else echo "[RELAY - ERROR] Unable to open $file_path" . PHP_EOL;
    } else echo "[RELAY - ERROR] Unable to get channel $channel_id" . PHP_EOL;
    */
};

$timer_function = function ($civ13)
{
    $discord = $civ13->discord;
    $ooc_relay = $civ13->functions['misc']['ooc_relay'];
    $civ13_guild_id = $civ13->civ13_guild_id;
    $nomads_ooc_path = $civ13->files['nomads_ooc_path'];
    $nomads_admin_path = $civ13->files['nomads_admin_path'];
    $tdm_ooc_path = $civ13->files['tdm_ooc_path'];
    $tdm_admin_path = $civ13->files['tdm_admin_path'];
    $nomads_ooc_channel = $civ13->channel_ids['nomads_ooc_channel'];
    $nomads_admin_channel = $civ13->channel_ids['nomads_admin_channel'];
    $tdm_ooc_channel = $civ13->channel_ids['tdm_ooc_channel'];
    $tdm_admin_channel = $civ13->channel_ids['tdm_admin_channel'];
    
    if ($guild = $discord->guilds->offsetGet($civ13_guild_id)) {
        $ooc_relay($civ13, $guild, $nomads_ooc_path, $nomads_ooc_channel);  // #ooc-nomads
        $ooc_relay($civ13, $guild, $nomads_admin_path, $nomads_admin_channel);  // #ahelp-nomads
        $ooc_relay($civ13, $guild, $tdm_ooc_path, $tdm_ooc_channel);  // #ooc-tdm
        $ooc_relay($civ13, $guild, $tdm_admin_path, $tdm_admin_channel);  // #ahelp-tdm
    } else $civ13->logger->warning("unable to get guild $civ13_guild_id");
};

$status_changer = function ($discord, $activity, $state = 'online') {
    $discord->updatePresence($activity, false, $state);
};