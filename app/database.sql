--
-- 	Copyright (c) 2009 Alan Chandler
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

-- This file is for information only and is used only to manually create the chat.db file.


--  CREATE TYPE UNSUPPORTED BY SQLITE
-- CREATE TYPE user_role AS ENUM ('A','L','M','B','H','G','S','R','N');
-- CREATE TYPE x_type AS ENUM ('LI','LO','LT','RE','RX','RM','RN','WJ','WL','LH','ME','WH','MQ','MR','PE','PX');
-- CREATE TYPE room_type AS ENUM ('O','B','A','M','C'); 

BEGIN TRANSACTION;

CREATE TABLE rooms (
  rid integer primary key NOT NULL,
  name varchar(30) NOT NULL,
  type room_type NOT NULL,
  smf_group smallint default NULL
) ;


INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES (1, 'Members Lounge', 'O', NULL);
INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES (2, 'The Blue Room', 'A', NULL);
INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES (3, 'The Green Room', 'B', NULL);
INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES (4, 'Vamp Club', 'O', NULL);
INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES (5, 'Auditorium', 'M', NULL);
INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES (6, 'Lead Team', 'C', 9);
INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES (7, 'The Music Room', 'C', 12);
INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES (8, 'The Africa Room', 'C', 16);
INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES (9, 'The News Room', 'C', 17);
INSERT INTO `rooms` (`rid`, `name`, `type`, `smf_group`) VALUES (10, 'The Spiders Lair', 'C', 18);

CREATE TABLE users (
  uid integer primary key NOT NULL,
  time bigint DEFAULT (strftime('%s','now')) NOT NULL,
  name character varying NOT NULL,
  role char(1) NOT NULL default 'R',
  rid integer NOT NULL default 0,
  moderator char(1) NOT NULL default 'N',
  question character varying,
  private integer NOT NULL default 0,
  permanent text,                -- will be an md5 of the password
  groups text, -- a colon separated list of "smf_groups" for the committee rooms they are allowed to see
  present boolean NOT NULL DEFAULT 0
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
  uid integer NOT NULL REFERENCES users(uid) ON DELETE cascade,
  name character varying NOT NULL,
  role char(1) NOT NULL,
  rid integer NOT NULL,
  type char(2) NOT NULL,
  text character varying
);

CREATE TABLE parameters (
    name text primary key,
    value text
);

INSERT INTO parameters VALUES ('emoticon_dir','../static/images/emoticons'); -- emoticon directory (either absolute or relative to the application)
INSERT INTO parameters VALUES ('emoticon_url','/emoticons'); -- emoticon url 
INSERT INTO parameters VALUES ('sound_whisper','ding.mp3'); -- file path (absolute or relative) to sound for whisper box appearing
INSERT INTO parameters VALUES ('sound_move','exit.mp3'); -- file path (absolute or relative) to sound for person moving rooms or exiting
INSERT INTO parameters VALUES ('sound_creaky','creaky.mp3'); -- file path (absolute or relative) to sound for vamp room door 
INSERT INTO parameters VALUES ('sound_speak','poptop.mp3'); -- file path (absolute or relative) to sound when someone speaks (when you are quiet)
INSERT INTO parameters VALUES ('sound_music','iyl.mp3'); -- file path (absolute or relative) to background music
INSERT INTO parameters VALUES ('presence_interval','90'); -- how frequently to say that you are still present (in seconds)
INSERT INTO parameters VALUES ('user_timeout','270'); -- how long (in seconds) to timeout a user as no longer present
INSERT INTO parameters VALUES ('purge_message_interval','20'); -- messages older than this number of days will be purged from the database
INSERT INTO parameters VALUES ('wakeup_interval','300'); --how long (in seconds) with no pipe traffic to force a wakeup on all listeners
INSERT INTO parameters VALUES ('chatbot_name','Hephaestus'); --chatbot name - Hephaestus is a greek god - a coppersmith.
INSERT INTO parameters VALUES ('max_messages','100'); -- maximum number of back messages to display when entering a room
INSERT INTO parameters VALUES ('max_time','180'); -- maximum number of minutes of back messages to display when entering a room
INSERT INTO parameters VALUES ('log_fetch_delay','3000'); -- milliseconds to wait before actually getting print log
INSERT INTO parameters VALUES ('log_spin_rate','500'); -- milliseconds for each step of the spin timer
INSERT INTO parameters VALUES ('log_step_seconds','2');  -- number of spin steps where the time varies by one second
INSERT INTO parameters VALUES ('log_step_minutes','4'); -- number of spin steps where the time varies by one minute
INSERT INTO parameters VALUES ('log_step_hours','12'); -- number of spin steps where time varies by one hour
INSERT INTO parameters VALUES ('log_step_6hours','6'); -- number of spin steps where time varies by 6 hours (before switching to days)
INSERT INTO parameters VALUES ('entrance_hall','Entrance Hall'); -- entrance hall name
INSERT INTO parameters VALUES ('exit_location','http://mb.home/forum/index.php'); -- where to exit to.

COMMIT;
PRAGMA foreign_keys = true;
VACUUM;



