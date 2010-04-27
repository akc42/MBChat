/*
 * RSA.init1.js
 * An implementation of RSA public-key cryptography 
 * Methods for RSA public key operation.
 *
 * See RSA.readme.txt for further information.
 *
 *
 * ACKNOWLEDGMENT
 *
 *     This library is originally written by Tom Wu
 *
 *     Copyright (c) 2005  Tom Wu
 *     All Rights Reserved.
 *     http://www-cs-students.stanford.edu/~tjw/jsbn/
 *
 * MODIFICATION
 *
 *     Some modifications are applied by Atsushi Oka
 *
 *     Atsushi Oka
 *     http://oka.nu/
 *
 *     - Packaged
 *     - Added Object-Oriented Interface.
 *     - Added Asynchronous Execution Feauture.
 */

function initRSA1( packages ) {
    __unit( "RSA.init1.js" );
    __uses( "packages.js" );
    // __uses( "SecureRandom.js" );
    __uses( "BigInteger.init1.js" );
    __uses( "isarray.js" );

    /////////////////////////////////////////////////////////////////////
    // import
    /////////////////////////////////////////////////////////////////////
    var BigInteger = __import( packages, "titaniumcore.crypto.BigInteger" );
    // var SecureRandom = __import( packages, "titaniumcore.crypto.SecureRandom" );

    /////////////////////////////////////
    // implementation
    /////////////////////////////////////

    /////////////////////////////////////////////////////////////////////
    // class RSA
    /////////////////////////////////////////////////////////////////////
    
    var none = function(m){
	return m;
    };
    
    // "empty" RSA key constructor
    var RSA = function () {
	this.n = null;
	this.e = 0;
	this.d = null;
	this.p = null;
	this.q = null;
	this.dmp1 = null;
	this.dmq1 = null;
	this.coeff = null;
	this.ksize = 0;
	this.tolerantlyGenerate=true;
	this.preprocessPublic = none;
	this.preprocessPrivate = none;
	this.keyFormat = RSA.defaultKeyFormat;
	this.messageFormat = RSA.defaultMessageFormat;
    }
    RSA.prototype.className = "RSA";

    RSA.prototype.toString = function (r) {
	if ( r == null ) r = 16;
	var rsaKey =this;
	var result=""
	    + "n="    + ( rsaKey.n     == null ? "" : rsaKey.n    .toString(r) + "(" + this.n.bitLength() + ")" ) + "\n"
	    + "e="    + ( rsaKey.e     == null ? "" : rsaKey.e    .toString(r) ) +"\n"
	    + "d="    + ( rsaKey.d     == null ? "" : rsaKey.d    .toString(r) + "(" + this.d.bitLength() + ")" ) + "\n"
	    + "p="    + ( rsaKey.p     == null ? "" : rsaKey.p    .toString(r) ) +"\n"
	    + "q="    + ( rsaKey.q     == null ? "" : rsaKey.q    .toString(r) ) +"\n"
	    + "dmp1=" + ( rsaKey.dmp1  == null ? "" : rsaKey.dmp1 .toString(r) ) +"\n"
	    + "dmq1=" + ( rsaKey.dmq1  == null ? "" : rsaKey.dmq1 .toString(r) ) +"\n"
	    + "coeff="+ ( rsaKey.coeff == null ? "" : rsaKey.coeff.toString(r) ) +"\n"
	return result;
    };

    RSA.defaultKeyFormat= null;
    RSA.installKeyFormat= function( keyFormat ){
	RSA.defaultKeyFormat  = keyFormat;
    };

    RSA.defaultMessageFormat= null;
    RSA.installMessageFormat= function( messageFormat ){
	RSA.defaultMessageFormat  = messageFormat;
    };

    // (private)
    function makeGetterSetter(name){
	return function() {
	    if ( arguments.length == 0 ) {
		return this[name];
	    } else {
		var value = arguments[0];
		var type = typeof value;
		if ( value == null ) {
		    throw "parameter " + name + " cannot be null.";
		} else if ( type == "object" && value.className == "BigInteger" ) {
		    this[name] = value;
		} else if ( type == "object" && value.isArray ) {
		    this[name] = new BigInteger(value);
		} else if ( type == "string" ) {
		    this[name] = new BigInteger( value, 16 );
		} else if ( type == "number" ) {
		    this[name] = new BigInteger( value );
		} else {
		    throw "parameter " + name + " must be a BigInteger object or a hex string or a number object.";
		}

		return this;
	    }
	};
    }

    // (private)
    function makeGetterSetter2(name){
	return function() {
	    if ( arguments.length == 0 ) {
		return this[name];
	    } else {
		var value = arguments[0];
		var type = typeof value;
		if ( value == null ) {
		    throw "parameter " + name + " cannot be null.";
		} else if ( type == "object" && value.className == "BigInteger" ) {
		    this[name] = value.intValue();
		} else if ( type == "object" && value.isArray ) {
		    this[name] = new BigInteger(value).intValue();
		} else if ( type == "string" ) {
		    this[name] = parseInt( value, 16 );
		} else if ( type == "number" ) {
		    this[name] = value;
		} else {
		    throw "parameter " + name + " must be a BigInteger object or a hex string or a number object.";
		}

		return this;
	    }
	};
    }

    RSA.prototype._n = makeGetterSetter( "n" );
    RSA.prototype._e = makeGetterSetter2( "e" );
    RSA.prototype._d = makeGetterSetter( "d" );
    RSA.prototype._p = makeGetterSetter( "p" );
    RSA.prototype._q = makeGetterSetter( "q" );
    RSA.prototype._dmp1 = makeGetterSetter( "dmp1" );
    RSA.prototype._dmq1 = makeGetterSetter( "dmq1" );
    RSA.prototype._coeff = makeGetterSetter( "coeff" );
    RSA.prototype._ksize = makeGetterSetter2( "ksize" );


    // // Set the public key fields N and e from hex strings
    // RSA.prototype.setPublic = function (N,E) {
    // 	if(N != null && E != null && N.length > 0 && E.length > 0) {
    // 		this.n = new BigInteger(N,16);
    // 		this.e = parseInt(E,16);
    // 	}
    // 	else
    // 		alert("Invalid public key");
    // };
    // 	
    // // Perform raw public operation on "x": return x^e (mod n)
    // RSA.prototype.doPublic = function (x) {
    // 	return x.modPowInt(this.e, this.n);
    // };

    /* 
     * publicKey(n,e)
     * Set a public key to the object.  Returns current value as an array
     * when no parameter is specified. The type of each parameters are
     * automatically converted to proper type.
     */
    RSA.prototype.publicKey = function (n,e,ksize) {
	if ( arguments.length == 0 ) {
	    return { n:this.n, e:this.e, ksize:this.ksize };
	} else {
	    this._n(n);
	    this._e(e);
	    this._ksize( ksize );
	}
    };

    /*
     * processPublic(m) 
     * encrypt( when it is used as cipher ) /  decrypt ( when it is used as
     * signing ) message.  Parameter m specifies a BigInteger object to
     * encrypt/decrypt.  Returns an encrypted/decrypted BigInteger value.
     */
    RSA.prototype.processPublic = function (m) {
	m = this.preprocessPublic(m);
	return m.modPowInt(this.e, this.n);
    };

    RSA.prototype.publicEncrypt = function(m) {
	if ( this.messageFormat==null ) {
	    throw "Error. No message format is installed.";
	}
	return this.messageFormat.publicEncrypt( this, m );
    };
    RSA.prototype.publicDecrypt = function(m) {
	if ( this.messageFormat==null ) {
	    throw "Error. No message format is installed.";
	}
	return this.messageFormat.publicDecrypt( this, m );
    };
    RSA.prototype.publicEncryptMaxSize = function() {
	if ( this.messageFormat==null ) {
	    throw "Error. No message format is installed.";
	}
	return this.messageFormat.publicEncryptMaxSize( this );
    };

    /*
     * Set a public key as a binary representation string.
     */
    RSA.prototype.publicKeyBytes = function() {
	if ( this.keyFormat==null ) {
	    throw "Error. No key format is installed.";
	}
	if ( arguments.length==0 ) {
	    if ( this.ksize == null || this.ksize == 0 ) {
		throw "key size is not set.";
	    }
	    return this.keyFormat.encodePublicKey( this.n, this.e, this.ksize );
	} else {
	    var key = this.keyFormat.decodePublicKey( arguments[0] );
	    this.publicKey( key.n, key.e, key.ksize );
	    return this;
	}
    };

    /////////////////////////////////////////////////////////////////////

    RSA.log = function(message) {
	// trace(message);
    };

    RSA.err = function(message) {
	trace(message);
    };

    /////////////////////////////////////////////////////////////////////
    // export
    /////////////////////////////////////////////////////////////////////
    __export( packages, "titaniumcore.crypto.RSA" ,RSA );
};

initRSA1( this );

// vim:ts=8 sw=4:noexpandtab:
