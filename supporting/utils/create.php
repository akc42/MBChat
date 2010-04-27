<?php
/*
 	Copyright (c) 2009 Alan Chandler
    This file is part of MBChat.

    MBChat is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    MBChat is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with MBChat (file COPYING.txt).  If not, see <http://www.gnu.org/licenses/>.
*/




?>
<html>
<head>
        <style type="text/css">
body {
    font-family: Arial;
    color: #345;
}
h1 {
    border-bottom: 3px solid #345;
}
a {
    color: #666;
}
li {
    padding: 2px;
}
sup.new {
    text-transform: uppercase;
    color: #f00;
    font-weight: bold;
}
form label, form a {
    display: block;
    margin: 10px 0;
}
form label input {
    margin-left: 5px;
}
        </style>
</head>
<body>
<h1>MB Chat User</h1>

<p></p>
<form action="createuser.php" method="post">

    <label>Username:<input type="text" name="username" value="" /></label>
    <label>Password:<input type="password" name="password" value="" /></label>
    <label>Capabilities:<input type="text" name="capabilities" value="" /></label>
    <input type="submit" name="submit" value="Create"/>

<form>
</body>
</html>

