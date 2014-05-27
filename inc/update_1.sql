UPDATE parameters SET value = '/img/emoticons/' WHERE name = 'emoticon_url';
UPDATE parameters SET value = '/home/alan/dev/mb.com/static/public_html/img/emoticons' WHERE name = 'emoticon_dir';


INSERT INTO parameters VALUES 
('purge_guest','5',1), --Only used when doing own authentication, days before purging guests.
('external','/chat/remote/index.php',1), --url to authenticate if doing external - must be null string to do internal

('db_version','2',9);
VACUUM;
  
