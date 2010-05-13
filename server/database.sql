--
-- 	Copyright (c) 2009,2010 Alan Chandler
--    This file is part of MBChat.
--
--    MBChat is free software: you can redistribute it and/or modify
--    it under the terms of the GNU General Public License as published by
--    the Free Software Foundation, either version 3 of the License, or
--    (at your option) any later version.
--
--    MBChat is distributed in the hope that it will be useful,
--    but WITHOUT ANY WARRANTY; without even the implied warranty of
--    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
--    GNU General Public License for more details.
--
--    You should have received a copy of the GNU General Public License
--    along with MBChat (file COPYING.txt).  If not, see <http://www.gnu.org/licenses/>.
--

BEGIN;

CREATE TABLE rooms (
  rid integer primary key NOT NULL,
  name varchar(30) NOT NULL,
  type integer NOT NULL -- 0 = Open, 1 = meeting, 2 = guests can't speak, 3 moderated, 4 members(adult) only, 5 guests(child) only
) ;

INSERT INTO `rooms` (`rid`, `name`, `type`) VALUES (1, 'The Forum', 0);
INSERT INTO `rooms` (`rid`, `name`, `type`) VALUES (2, 'Operations Gallery', 2); --Guests Can't Speak
INSERT INTO `rooms` (`rid`, `name`, `type`) VALUES (3, 'Dungeon Club', 0);
INSERT INTO `rooms` (`rid`, `name`, `type`) VALUES (4, 'Auditorium', 3); -- Moderated Room
INSERT INTO `rooms` (`rid`, `name`, `type`) VALUES (5, 'The Board Room', 1);
INSERT INTO `rooms` (`rid`, `name`, `type`) VALUES (6, 'Operations', 1);
INSERT INTO `rooms` (`rid`, `name`, `type`) VALUES (7, 'Marketing', 1);
INSERT INTO `rooms` (`rid`, `name`, `type`) VALUES (8, 'Engineering',1);
INSERT INTO `rooms` (`rid`, `name`, `type`) VALUES (9, 'IT Dept', 1);
INSERT INTO `rooms` (`rid`, `name`, `type`) VALUES (10, 'Finance', 1);

CREATE TABLE parameters (
    name text primary key,
    value text,
    grp integer NOT NULL
);
-- The first group of parameters are needed by chat as it starts, but will not be needed again. There will be loaded (as a group) on demand from
-- The chat page

INSERT INTO parameters VALUES ('remote_error','http://mb.home/chat2/chat.html',1); --for redirecting users who have failed remote authentication
INSERT INTO parameters VALUES ('guests_allowed','yes',1); -- if we are allowing guests?  yes if we are, anything else means no.
INSERT INTO parameters VALUES ('emoticon_dir','./emoticons',1); -- emoticon directory (either absolute or relative to the application)
INSERT INTO parameters VALUES ('emoticon_url','/emoticons/',1); -- emoticon url 
INSERT INTO parameters VALUES ('remote_key','MB.COM',1); -- key used for remote connector 

-- Same as the first group, but separated out for easy of use - they will be returned in the same request under different json object (sounds)

INSERT INTO parameters VALUES ('whisper','ding.mp3',2); -- file name in sound directory for whisper box appearing
INSERT INTO parameters VALUES ('move','exit.mp3',2); -- file name in sound directory for person moving rooms or exiting
INSERT INTO parameters VALUES ('creaky','creaky.mp3',2); -- file name in sound directory for vamp room door 
INSERT INTO parameters VALUES ('speak','poptop.mp3',2); -- file name in sound directory when someone speaks (when you are quiet)
INSERT INTO parameters VALUES ('music','iyl.mp3',2); -- file name in sound directory to background music

-- Colours of different roles separated out for easy of use uses the colours json object - name represents the role id.
INSERT INTO parameters VALUES ('A','843B00',3); -- brown CEO (Not Used)
INSERT INTO parameters VALUES ('L','036972',3); -- teal DIRECTOR (Kelley, Cherry)
INSERT INTO parameters VALUES ('H','6A00FF',3); -- purple SPONSOR (Melinda)
INSERT INTO parameters VALUES ('G','808000',3); -- olive DEPT HEAD (Janet)
INSERT INTO parameters VALUES ('S','0000CD',3); -- blue (speaker in auditorium if previously a REG)
INSERT INTO parameters VALUES ('M','C00000',3); -- red (mod in auditorium) regardless of normal role
INSERT INTO parameters VALUES ('B','009700',3); -- green GUESTS
INSERT INTO parameters VALUES ('R','414141',3); -- grey REGULAR
INSERT INTO parameters VALUES ('C','476C8E',3); -- RAF blue (chatbot).


-- these are needed within the javascript chat program to set things up.  We will provide them during the login process

INSERT INTO parameters VALUES ('presence_interval','90',4); -- how frequently to say that you are still present (in seconds)
INSERT INTO parameters VALUES ('chatbot_name','Hephaestus',4); --chatbot name - Hephaestus is a greek god - a coppersmith.
INSERT INTO parameters VALUES ('log_fetch_delay','3000',4); -- milliseconds to wait before actually getting print log
INSERT INTO parameters VALUES ('log_spin_rate','500',4); -- milliseconds for each step of the spin timer
INSERT INTO parameters VALUES ('log_step_seconds','2',4);  -- number of spin steps where the time varies by one second
INSERT INTO parameters VALUES ('log_step_minutes','4',4); -- number of spin steps where the time varies by one minute
INSERT INTO parameters VALUES ('log_step_hours','12',4); -- number of spin steps where time varies by one hour
INSERT INTO parameters VALUES ('log_step_6hours','6',4); -- number of spin steps where time varies by 6 hours (before switching to days)
INSERT INTO parameters VALUES ('entrance_hall','Entrance Hall',4); -- entrance hall name
INSERT INTO parameters VALUES ('whisper_restrict','true',4);  -- if true, then non B's and B's can't whisper to each other
INSERT INTO parameters VALUES ('max_messages','100',4); -- maximum number of back messages to display when entering a room


-- The following are used operationally and as such are required individually throughout operation.
-- Most will be cached into named variables on server start up (and so will not see any changes during the running of the server),
--  although some will be called when needed

-- next parameter only enabled if we are going to encrypt messages
-- INSERT INTO parameters VALUES ('des_key','0',9); -- placeholder for des-Key
INSERT INTO parameters VALUES ('exit_location','http://chat/index.php',9); -- where to exit to.
INSERT INTO parameters VALUES ('max_time','180',9); -- maximum number of minutes of back messages to display when entering a room
INSERT INTO parameters VALUES ('tick_interval','10',9); -- how long (in seconds) that the server should check for exit
INSERT INTO parameters VALUES ('check_ticks','6',9); -- how long (in tick intervals) should the server wait before testing the database for timeout
INSERT INTO parameters VALUES ('user_timeout','270',9); -- how long (in seconds) to timeout a user as no longer present
INSERT INTO parameters VALUES ('purge_message_interval','20',9); -- messages older than this number of days will be purged from the database




CREATE TABLE users (
  uid integer primary key NOT NULL,
  time bigint DEFAULT (strftime('%s','now')) NOT NULL,
  name character varying NOT NULL,
  role char(1) NOT NULL default 'R',
  rid integer NOT NULL default 0,
  mod char(1) NOT NULL default 'N',
  question character varying,
  private integer NOT NULL default 0,
  cap integer NOT NULL default 0,
  rooms character_varying 
);
    
CREATE table wid_sequence ( value integer);
INSERT INTO wid_sequence (value) VALUES (1);

CREATE TABLE participant (
  uid integer NOT NULL REFERENCES users (uid) ON DELETE CASCADE ON UPDATE CASCADE,
  wid integer NOT NULL,
  primary key (uid,wid)
);


-- expect to do insert into participant values ('Some uid Value', (select value from wid_sequence));
-- sequence is updated manually in the code (only needs to be increased when a completely new whisper box is generated - not 
-- when a new participant is

CREATE TABLE log (
  lid integer primary key,
  time bigint DEFAULT (strftime('%s','now')) NOT NULL,
  uid integer NOT NULL,
  name character varying NOT NULL,
  role char(1) NOT NULL,
  rid integer NOT NULL,
  type char(2) NOT NULL,
  text character varying
);
COMMIT;

PRAGMA foreign_keys = true;
VACUUM;



