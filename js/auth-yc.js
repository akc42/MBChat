function getHTTPObject(){var xmlhttp=false;if(typeof XMLHttpRequest!="undefined"){try{xmlhttp=new XMLHttpRequest()}catch(e){xmlhttp=false}}else{
/*@cc_on
        @if (@_jscript_version >= 5)
            try {
                xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (E) {
                    xmlhttp = false;
                }
            }
        @end @*/
}return xmlhttp}function login(d){var e=document.getElementById("login");var f=e.username.value;var c=e.password.value;var b=getHTTPObject();var a=e.action;b.open("get",a,false,f,c);b.send("");if(b.status==200){document.location=a}else{document.location=d}return false}function logout(){var a=getHTTPObject();a.open("get",window.location.href,false,"null","null");a.send("");return false};