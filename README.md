# MB Chat

## An Introduction to MBChat

MB chat is a multi purpose chat program.  Originally developed for
http://www.melindasbackups.com so that the leadership team could hold
management meetings in real time rather than via e-mail, it went on to
become a more general meeting place for the site.

Early versions relied on the clients polling the database for new messages, but 
as the volume of users increased this turned out to be impractible above about 30 users.

The current version uses a chat server, initated automatically when the first user comes
on line and terminating when there are no users connected after a short timeout.

The configuration, and the operational aspects of chat, are managed with a small sqlite
database. Optionally users may be authenticated through a second sqlite database, in which case
chat displays a user logon form.  The alternative is to externally authenticate users, typically
via the fact that they are already logged on the web site for other reasons.  In the case
of Melinda's Backups this was the members SMF forum.  Example PHP scripts that do just that
are provided in the repository.

Users arrive at an entrance hall where they may enter one of several rooms.  The available rooms are 
defined in the operational database and may be of different types.  In particular there are
* Rooms that anyone may enter
* Rooms only members may enter (used on Melinda's Backups to define regular forum members)
* Rooms only guests may enter (used on Melinda's Backups to define juveniles)
* Rooms with creaking doors (used as room for telling ghost stories on Melinda's Backups)
* An auditorium (only defined speakers - but users can ask questions that moderators can release to the floor)
* Meeting rooms - only users who belong to a particular group (committee members?) 

Configuration of available rooms is controlled with simple update of a
controlling database.

It is also possible for participants to whisper to each other (pass private messages with
limited visibility), create private rooms and invite a limited number of guests in.

## Setting up

The vast majority of the parameters that chat needs to set up are stored in the sqlite database called
chat.db.  The supplied database creation script data/database.sql is commented to explain what these all are.
However, there are still some setup outside of these parameters.

Firstly you have to tell MBChat where to find the server and database.  There are two PHP define statements
in the file inc/client.inc which are commented.  Set those up

The second stage of step up depends on whether you have chosen internal or external authentication for users.
If you have chosen internal, you will now need to prepare the data/users.sql file to provide the initial population
of authenticated users.  **NOTE:** this is quite insecure with passwords stored in plain text.  It is recommended
to use the the external mechanism.

The example of how to do external authentication against an SMF forum and provide the correct data to chat is given
in the script remote/index.php.  Although it is a PHP script the output returned is actually a javascript source file
which either prevents the user continuing by redirecting to a backup page (location defined at the head of this script
file), or provides all the credentials necessary to provide chat with the information it needs to setup the rest if 
the system for the user. 

The remote/count.php file is an example of how a remote site can find out how many people in chat.  It needs telling where
the login/count.php script is on the machine running chat.

## Using chat.

For the majority of users it is assumed that they will be sitting at a desktop PC with a mouse.  At this point in time
basic functionality works with tablets and phones, but some of the more sophisticated options using a drag and drop technique
to invite users to private conversations (known as whispering) do not work.

It IS however possible to designate a user to be blind, and then the graphics and animation are replaced by more conventional
web site controls, so that screen readers will work with chat.  This has been tested with a blind member and works well.

When you first enter chat, you enter the *Entrance Hall* .  This shows a list of rooms which you may enter ( you do not see
rooms which you do not have permission to enter), and a list to the right which is a list of people also in the entrance 
hall with you.  If you mouse over any of the room buttons, the room button expands and the list on the right changes 
to show who is in the particular room. Click on the room to enter

Inside a room, there is a large panel to the left, containing the conversation, and the list of people in the room is on the right.
below those, on the left, is a line for entering your own text, and some emoticons which you can click on to add to the text you 
are typing. Clicking send (or using the return key) sends the message to the room.  You will see your own, and other peoples 
messages.  Peoples names are coloured by the designation or *role* given them.  This is defined (both roles and colour) in
the database.

It is possible to start a private conversation (a whisper) with someone by clicking on their name and dragging down to the area
where you normally input text.  As you get to the input area a black border appears, meaning if you let go at this point you will 
initiate this whisper.  As you let go, a *Whisper Box* appears somewhere near the top right of your screen.  This is like a small 
dialog box with an area to type text and send it.  When you do the message ONLY appears in the message list of yourself and the person
with whom you are whispering.  To let you know this is the case the text is preceeded by a comment that this is a whisper.

You or your co-whisperer can add other people into this private conversation by dragging their name into the input area of the Whisper Box.

You can also have multiple whispers (each with their own Whisper Box) with different subsets of people.  Just beware you don't mess up and 
say something that was meant to be private to the wrong person.

To prevent possible abuse (or the accusation of abuse) there is a restriction in the members can't whisper to guests (and vica versa). This
stems form the use of guests to represent juveniles at Melinda's Backups.  Incidentally there is also a *capability* that you can give to users
that prevent them from whispering.

As hinted in the previous paragraph, each user has a set of capabilities.  I have already mentioned blind, and cannot whisper, but there is also
secretary, admin, moderator and speaker roles.  Secretaries can obtain printouts of the conversation in a *meeting* room (see introduction for
the different room types) by entering a room with the control key pressed. This allows them to select the time period of interest, and 
the chat pane then shows the messages during that period of time.  A printer button produces a static web page (this may be changed
in the future to be a pdf file) ready for copy and pasting elsewhere.

Admin is like a secretary, except instead of meeting rooms, they can go and obtain printouts for all the other rooms. 

Moderators and Speakers come into special attention when in a room designated as an auditorium.  Speakers can talk, where as everyone else cannot,
they can only ask questions.  You ask a question by typing as normal, but instead of the text coming into the message pane when you press enter, 
it is instead stored in the on-line list against your name.  This change the background colour of your name to yellow.  Moderators can see that
you have asked a question. If a Moderator moves his mouse over your name he sees your question.  By clicking on it he can release it to the normal 
message pane, thus allowing everyone else to see it.

 
 

## A Security Option

Written as a proof of concept for some encrytion techniques, there is a version of chat
which will encrypt all messages to and from the server.  But just to do this, and prevent
someone getting access to the encryption key when the software itself is a web application
loaded from the server is no easy task.

The solution to this is for the client application, when it starts up in the users browser, to
dynamically create a public/private key pair, send the public key to the server and ask it to encrypt
the encyption key using it, so the client (and only the client) will be able to dycrypt it using
the private key.

To prevent the server from being spoofed, it also has to prove who it is, by encrypting a common secret

I am not sure this option ever needs to be deployed in production - one could just as easily use https,
so future versions of chat will most likely drop it.  The software remains in the repository as an example






 
