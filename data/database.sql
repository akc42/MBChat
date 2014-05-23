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
  type integer NOT NULL -- 0 = Open, 1 = meeting, 2 = guests can't speak, 3 moderated, 4 members(adult) only, 5 guests(child) only, 6 creaky door
) ;

INSERT INTO rooms (rid, name, type) VALUES 
(1, 'The Forum', 0),
(2, 'Operations Gallery', 2), --Guests Can't Speak
(3, 'Dungeon Club', 6), --creaky door
(4, 'Auditorium', 3), -- Moderated Room
(5, 'Blue Room', 4), -- Members Only (in Melinda's Backups this is Adults)
(6, 'Green Room', 5), -- Guest Only (in Melinda's Backups this is Juveniles AKA Baby Backups)
(7, 'The Board Room', 1), --various meeting rooms - need to be on users room list
(8, 'Marketing', 1),
(9, 'Engineering',1),
(10, 'IT Dept', 1),
(11, 'Finance', 1);

CREATE TABLE parameters (
    name text primary key,
    value text,
    grp integer NOT NULL
);

-- The first group of parameters are needed by chat as it starts, but will not be needed again. There will be loaded (as a group) on demand from
-- The chat page

INSERT INTO parameters (name,value,grp) VALUES 
('remote_error','/chat.php',1), --for redirecting users who have failed remote authentication
('guests_allowed','yes',1), -- if we are allowing guests?  yes if we are, anything else means no.
('emoticon_dir','./emoticons',1), -- emoticon directory (either absolute or relative to the application)
('emoticon_url','emoticons/',1), -- emoticon url 
('rsa','no',1), -- Are we using an RSA encryption to return session key, yes if we are anything else means no
('purge_guest','5',1), --Only used when doing own authentication, days before purging guests.
('external','',1), --url to authenticate if doing external - must be null string to do internal

-- Same as the first group, but separated out for easy of use - they will be returned in the same request under different json object (sounds)

('whisper','ding.mp3',2), -- file name in sound directory for whisper box appearing
('move','exit.mp3',2), -- file name in sound directory for person moving rooms or exiting
('creaky','creaky.mp3',2), -- file name in sound directory for vamp room door 
('speak','poptop.mp3',2), -- file name in sound directory when someone speaks (when you are quiet)
('music','iyl.mp3',2), -- file name in sound directory to background music

-- Colours of different roles separated out for easy of use uses the colours json object - name represents the role id.
-- You can choose as many, or as few roles as you like, although 'S','M','R' and 'C' have special meaning and should
-- not be removed. 

('A','843B00',3), -- brown CEO (Not Used)
('L','036972',3), -- teal DIRECTOR (Kelley, Cherry)
('H','6A00FF',3), -- purple SPONSOR (Melinda)
('G','808000',3), -- olive DEPT HEAD (Janet)
('S','0000CD',3), -- blue (speaker in auditorium if previously a REG)
('M','C00000',3), -- red (mod in auditorium) regardless of normal role
('B','009700',3), -- green GUESTS
('R','414141',3), -- grey REGULAR
('C','476C8E',3), -- RAF blue (chatbot)


-- these are needed within the javascript chat program to set things up.  We will provide them during the login process

('presence_interval','90',4), -- how frequently to say that you are still present (in seconds)
('chatbot_name','Hephaestus',4), --chatbot name - Hephaestus is a greek god - a coppersmith.
('log_fetch_delay','3000',4), -- milliseconds to wait before actually getting print log
('log_spin_rate','500',4), -- milliseconds for each step of the spin timer
('log_step_seconds','2',4),  -- number of spin steps where the time varies by one second
('log_step_minutes','4',4), -- number of spin steps where the time varies by one minute
('log_step_hours','12',4), -- number of spin steps where time varies by one hour
('log_step_6hours','6',4), -- number of spin steps where time varies by 6 hours (before switching to days)
('entrance_hall','Entrance Hall',4), -- entrance hall name
('whisper_restrict','true',4),  -- if true, then Members and Guest can't whisper to each other
('max_messages','100',4), -- maximum number of back messages to display when entering a room
('read_timeout','45',4), --time in seconds to refresh reads that haven't had any response

-- The following are used operationally and as such are required individually throughout operation.
-- Most will be cached into named variables on server start up (and so will not see any changes during the running of the server),
--  although some will be called when needed

-- next parameter only enabled if we are going to encrypt messages
-- ('des_key','0',9), -- placeholder for des-Key
('exit_location','/chat/index.php',9), -- where to exit to.
('max_time','180',9), -- maximum number of minutes of back messages to display when entering a room
('tick_interval','10',9), -- how long (in seconds) that the server should check for exit
('check_ticks','6',9), -- how long (in tick intervals) should the server wait before testing the database for timeout
('user_timeout','270',9), -- how long (in seconds) to timeout a user as no longer present
('purge_message_interval','20',9), -- messages older than this number of days will be purged from the database
('security_msg','This is your security message for today',9), --security message, only used if des_key set
-- This is a special parameter, used only by chatter itself, not users, to validate the version of the database. 
('db_version','2',9);  



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
-- not using WAL mode because only chatter is accessing it, so no contention.  Avoid the overhead that WAL mode would introduce


