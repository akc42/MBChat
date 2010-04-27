/*
 * RSA.init2.js
 * An implementation of RSA public-key cryptography 
 * Methods for RSA private key operation and RSA key generation
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

function initRSA2( packages ) {
    __unit( "RSA.init2.js" );
    __uses( "packages.js" );
    __uses( "SecureRandom.js" );
    __uses( "BigInteger.init1.js" );
    __uses( "BigInteger.init2.js" );
    __uses( "RSA.init1.js" );
    __uses( "elapse.js" );

    /////////////////////////////////////////// 
    // import
    /////////////////////////////////////////// 
    // var RSA = __package( packages, id ).RSA;
    // var BigInteger = __package( packages, id ).BigInteger;
    // var SecureRandom = __package( packages, id ).SecureRandom;
    var RSA = __import( packages, "titaniumcore.crypto.RSA" );
    var BigInteger = __import( packages, "titaniumcore.crypto.BigInteger" );
    var SecureRandom = __import( packages, "titaniumcore.crypto.SecureRandom" );

    /////////////////////////////////////////// 
    // implementation
    /////////////////////////////////////////// 
    
    // // Set the private key fields N, e, and d from hex strings
    // RSA.prototype.setPrivate = function (N,E,D) {
    // 	if(N != null && E != null && N.length > 0 && E.length > 0) {
    // 		this.n = new BigInteger(N,16);
    // 		this.e = parseInt(E,16);
    // 		this.d = new BigInteger(D,16);
    // 	}
    // 	else
    // 		alert("Invalid private key");
    // };
    // 
    // 
    // // Set the private key fields N, e, d and CRT params from hex strings
    // RSA.prototype.setPrivateEx = function (N,E,D,P,Q,DP,DQ,C) {
    // 	if(N != null && E != null && N.length > 0 && E.length > 0) {
    // 		this.n = new BigInteger(N,16);
    // 		this.e = parseInt(E,16);
    // 		this.d = new BigInteger(D,16);
    // 		this.p = new BigInteger(P,16);
    // 		this.q = new BigInteger(Q,16);
    // 		this.dmp1 = new BigInteger(DP,16);
    // 		this.dmq1 = new BigInteger(DQ,16);
    // 		this.coeff = new BigInteger(C,16);
    // 	}
    // 	else alert("Invalid private key");
    // };
    
    /* 
     * privateKey(n,e,d)
     * Set a private key to the object.  Returns current value as an array
     * when no parameter is specified. The type of each parameters are
     * automatically converted to proper type.
     */
    RSA.prototype.privateKey = function (n,e,d,ksize) {
	if ( arguments.length == 0 ) {
	    return { n:this.n, e:this.e, d:this.d, ksize:this.ksize };
	} else {
	    this._n(n);
	    this._e(e);
	    this._d(d);
	    this._ksize(ksize);
	}
    };

    /* 
     * set(n,e,d,p,q,dmp1,dmq1,coeff)
     * Set all parameters related RSA key to the object.  Returns current
     * value as an array when no parameter is specified. The type of each
     * parameters are automatically converted to proper type.
     */
    RSA.prototype.key = function (n,e,d,p,q,dmp1,dmq1,coeff) {
	if ( arguments.length == 0 ) {
	    return { n:this.n, e:this.e, d:this.d, p:this.p, q:this.q, dmp1:this.dmp1, dmq1:this.dmq1, coeff:this.coeff };
	} else {
	    this._n(n);
	    this._e(e);
	    this._d(d);
	    this._p(p);
	    this._q(q);
	    this._dmp1(dmp1);
	    this._dmq1(dmq1);
	    this._coeff(coeff);
	}
    }

    // Added Jan15,2009
    // (protected)
    RSA.prototype.splitBitLength = function( bitlen ) {
	if ( this.tolerantlyGenerate ) {
	    var qs = bitlen >>1;
	    // One more bit for "a problem that length of composite number is not enough if unlucky."
	    // ex) you need 4digis. so you split into 2 digits and multiply them.
	    // 2digits x 2digits = 3digits~4digits ... 10x10 = 100  99*99=9801
	    return [ bitlen-qs + 1, qs + 1 ];
	} else {
	    trace( "strict1" );
	    var qs = bitlen >>1;
	    return [ bitlen-qs, qs ];
	}
    };
    // (protected)
    RSA.prototype.isProperBitLength = function( bi, bitlen ) {
	if ( this.tolerantlyGenerate ) {
	    // // and probably this check is not necessary.
	    // return ( bitlen + 1 ) <= bi.bitLength();
	    return true;
	} else {
	    trace( "strict2" );
	    return bitlen == bi.bitLength();
	}
    };
    
    // Generate a new random private key B bits long, using public expt E
    RSA.prototype.generate = function (B,E) {
	var rng = new SecureRandom();
	// Modified Jan15,2009 >>
	// var qs = B>>1;
	var qs = this.splitBitLength( B );
	// <<

	// Modified Jan 4,2009
	// this.e = parseInt(E,16);
	// var ee = new BigInteger( E, 16 );
	// Modified Jan 5,2009
	//if ( typeof E == "string" ) {
	//	this.e = parseInt(E,16);
	//} else if ( typeof E == "number" ) {
	//	this.e = e;
	//} else {
	//	throw "E must be a number object or a hex string. ";
	//}
	this._e(E);
	var ee = new BigInteger( this.e );

	for(;;) {
	    var et1= ElapsedTime.create();
	    for(;;) {
		et1.start("generateLoop1");
		// Modified Jan15,2009 >>
		// this.p = new BigInteger( B-qs, 1, rng );
		this.p = new BigInteger( qs[0], 1, rng );
		// <<

		et1.stop();
		if ( this.p.subtract(BigInteger.ONE).gcd(ee).compareTo(BigInteger.ONE) == 0 && this.p.isProbablePrime(10) )
		    break;
	    }

	    var et2= ElapsedTime.create();
	    for(;;) {
		et2.start("generateLoop2");
		// Modified Jan15,2009 >>
		// this.q = new BigInteger( qs, 1, rng );
		this.q = new BigInteger( qs[1], 1, rng );
		// <<
		et2.stop();
		if ( this.q.subtract( BigInteger.ONE ).gcd( ee ).compareTo( BigInteger.ONE ) == 0 && this.q.isProbablePrime(10) )
		    break;
	    }

	    // MODIFIED 15 Jan,2009 Ats >>>
	    // if ( this.p.compareTo(this.q) <= 0 ) {
	    //     var t = this.p;
	    //     this.p = this.q;
	    //     this.q = t;
	    // }
	    var cc = this.p.compareTo(this.q);
	    if ( cc == 0 ) {
		// see the comment RSA.init3.js
		continue;
	    } else if ( cc < 0 ) {
		var t = this.p;
		this.p = this.q;
		this.q = t;
	    }
	    // <<

	    var p1 = this.p.subtract( BigInteger.ONE );
	    var q1 = this.q.subtract( BigInteger.ONE );
	    var phi = p1.multiply( q1 );

	    if ( phi.gcd(ee).compareTo( BigInteger.ONE ) == 0 ) {
		this.n = this.p.multiply( this.q );
		// ADDED 15 Jan,2009 Ats >>>
		if ( ! this.isProperBitLength( this.n, B ) ) {
		    continue;
		}
		// <<
		this.d = ee.modInverse( phi );
		this.dmp1 = this.d.mod(p1);
		this.dmq1 = this.d.mod(q1);
		this.coeff = this.q.modInverse(this.p);
		break;
	    }
	}
	this._ksize(B);
    };

    /*
     * processPrivate(m) 
     * encrypt( when it is used as cipher ) /  decrypt ( when it is used as
     * signing ) message.  Parameter m specifies a BigInteger object to
     * encrypt/decrypt.  Returns an encrypted/decrypted BigInteger value.
     */
    RSA.prototype.processPrivate = function (m) {
	m = this.preprocessPrivate(m);
	if ( this.p == null || this.q == null ) {
	    return m.modPow(this.d, this.n);
	} else {
	    // TODO: re-calculate any missing CRT params
	    var p1 = m.mod(this.p).modPow(this.dmp1, this.p);
	    var q1 = m.mod(this.q).modPow(this.dmq1, this.q);
	
	    while(p1.compareTo(q1) < 0) {
		p1 = p1.ope_add(this.p);
	    }
	    return p1.subtract(q1).multiply(this.coeff).mod(this.p).multiply(this.q).ope_add(q1);
	}
    };

    RSA.prototype.privateEncrypt = function(m) {
	if ( this.messageFormat==null ) {
	    throw "Error. No message format is installed.";
	}
	return this.messageFormat.privateEncrypt( this, m );
    };
    RSA.prototype.privateDecrypt = function(m) {
	if ( this.messageFormat==null ) {
	    throw "Error. No message format is installed.";
	}
	return this.messageFormat.privateDecrypt( this, m );
    };
    RSA.prototype.privateEncryptMaxSize = function() {
	if ( this.messageFormat==null ) {
	    throw "Error. No message format is installed.";
	}
	return this.messageFormat.privateEncryptMaxSize( this );
    };

    /*
     * Set a private key as a binary representation string.
     */
    RSA.prototype.privateKeyBytes = function() {
	if ( this.keyFormat==null ) {
	    throw "Error. No key format is installed.";
	}
	if ( arguments.length==0 ) {
	    if ( this.ksize == null || this.ksize == 0 ) {
		throw "key size is not set.";
	    }
	    return this.keyFormat.encodePrivateKey( this.n, this.e, this.d, this.ksize );
	} else {
	    var key = this.keyFormat.decodePrivateKey( arguments[0] );
	    this.privateKey( key.n, key.e, key.d, key.ksize );
	    return this;
	}
    };

};
initRSA2( this );

// vim:ts=8 sw=4:noexpandtab:
