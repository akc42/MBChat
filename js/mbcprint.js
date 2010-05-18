/*
 	Copyright (c) 2010 Alan Chandler
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
function MBCprint(uid,pass) {
    var coord = new Coordinator(['dom','key'],function(activity) {
        var desKey = activity.get('key').des;
        var msgs = $$('.dmsg');
        msgs.each(function(msg) {
            msg.set('text',des(desKey,Base64.decode(msg.get('text')),false).replace(/\0+/g,''));
        });
           
    });
    function padDigits(n, totalDigits) {  
        var pd = ''; 
        if (totalDigits > n.length) { 
            for (i=0; i < (totalDigits-n.length); i++) { 
                pd += '0'; 
            } 
        } 
        return pd + n; 
    }
    var rsa = new RSA();
    rsa.generateAsync(64,65537,function(key,rsa){
        var req = new Request.JSON({
            url:'getdes.php',
            link:'chain',
            onComplete: function(r,t) {
                if(r && r.status) {
                    var d = new BigInteger(r.des);
                    var desKey = padDigits(d.modPow(key.d,key.n).toString(10),10);
                    coord.done('key',{'des':desKey});
                }
            }
        });
        req.post({uid:uid,pass:pass,e:key.e.toString(),n:key.n.toString(10)});
    });
    return coord;
}

