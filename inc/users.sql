BEGIN;

CREATE TABLE users (
  uid integer primary key autoincrement NOT NULL,
  time bigint DEFAULT (strftime('%s','now')) NOT NULL,
  name character varying NOT NULL,
  role text NOT NULL DEFAULT 'R', -- A (CEO), L (DIRECTOR), G (DEPT HEAD), H (SPONSOR) R(REGULAR)
  cap integer DEFAULT 0 NOT NULL, -- 1 = blind, 2 = committee secretary, 4 = admin, 8 = mod, 16 = speaker 32 = can't whisper( OR of capabilities).
  password character varying NOT NULL, --raw password
  rooms character varying, -- a ":" separated list of rooms nos which define which rooms the user can go in
  isguest boolean DEFAULT 0 NOT NULL
);
CREATE INDEX userindex ON users(name);
-- Below here you can add the specific users for your set up in the form of INSERT Statements


-- This list is test users to cover the complete range of functions. Note names are converted to lowercase, so only put lowercase names in here
INSERT INTO users(uid,name,role,cap,password,rooms,isguest) VALUES
(1,'alice','A',4,'password','7',0),
(2,'bob','L',3,'password','8',0),
(3,'carol','G',2,'password','7:8:9',0),
(4,'dave','H',0,'password','10',0),
(5,'eileen','R',8,'password','',0),
(6,'fred','R',16,'password','',0),
(7,'gail','R',0,'password','',0),
(8,'harry','R',0,'password','',1),
(9,'irene','R',32,'password','',0);




COMMIT;
VACUUM;
-- set it all up as Write Ahead Log for max performance and minimum contention with other users.
PRAGMA journal_mode=WAL;

