#MB Chat

MC chat is a multi purpose chat program.  Originally developed for
http://www.melindasbackups.com so that the leadership team could hold
management meetings in real time rather than e-mail, it went on to
become a more general meeting place for the site.

Since that original inception, other facilities have been added,
including a version which allowed complete security so that secure
conversations could be held over the internet without anyone listening
in.  This included preventing attacks from people pretending to be the
chat server.  In order to achieve this level of security some novel
techniques were deployed to allow the browser to create a public and
private key pair and then pass the public key to the server for it to
encrpyt the main session encryption key back to the browser.

As well as general rooms for everyone to attend, it is also possible
to define meeting rooms - with access rights controlled by group
membership (a concept of
an SMF forum) to allow committee meetings to be held.  Secretaries of
the committees can review and print logs of the conversation to aid in
minute taking. It is also possible to define rooms that act like an auditorium
(where there are designated speakers, but where there can be
controlled question asking by the audience - using a moderator to
release the questions to the floor).  

Configuration of available rooms is controlled with simple update of a
controlling database - based in sqlite.

It is also possible for participants to whisper to each other (pass private messages with
limited visibility), create private rooms and invite a limited number
of guests in.



 
