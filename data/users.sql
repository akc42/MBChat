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
COMMIT;
VACUUM;
    
