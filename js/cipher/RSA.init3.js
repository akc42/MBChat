/*
 * RSA.init3.js
 * An implementation of RSA public-key cryptography 
 * Methods for Asynchronous processing.
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

function initRSA3( packages ) {
    __unit( "RSA.init3.js" );
    __uses( "packages.js" );
    __uses( "SecureRandom.js" );
    __uses( "BigInteger.init1.js" );
    __uses( "BigInteger.init2.js" );
    __uses( "nonstructured.js" ); // See ... (1)
    __uses( "BigInteger.init3.js" );
    __uses( "RSA.init1.js" );
    __uses( "RSA.init2.js" );

    // (1) nonstructured.js
    // This file relies on nonstructured.js
    // See http://oka.nu/lib/nonstructured/nonstructured.readme.txt

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

    RSA.prototype.generateAsync = function(keylen,exp,progress,result,done) {
	var self=this;
	var generator = this.stepping_generate( keylen, exp );
	var _result = function() {
	    result( self );
	    return BREAK;
	};
	return ( [ generator, _result, EXIT ] ).ready().frequency(1).timeout(1).progress(progress).done(done).go();
    };
    
    RSA.prototype.processPublicAsync = function(message,progress,result,done){
	var closure= this.stepping_processPublic(message);
	var receiver = function(scope,param,subparam) {
	    result( subparam.result.toByteArray() );
	    return BREAK;
	};
	return ( [ closure, receiver, EXIT ] ).ready().frequency(1).timeout(1).progress(progress).done(done).go();
    };

    RSA.prototype.processPrivateAsync = function(message,progress,result,done){
	var closure= this.stepping_processPrivate(message);
	var receiver = function(scope,param,subparam) {
	    result( subparam.result.toByteArray() );
	    return BREAK;
	};
	return ( [ closure, receiver, EXIT ] ).ready().frequency(1).timeout(1).progress(progress).done(done).go();
    };

    RSA.prototype.privateEncryptAsync = function(message,progress,result,done) {
	if ( this.messageFormat==null ) {
	    throw "Error. No message format is installed.";
	}
	return this.messageFormat.privateEncryptAsync( this, message,progress,result,done );
    };

    RSA.prototype.privateDecryptAsync = function(message,progress,result, done) {
	if ( this.messageFormat==null ) {
	    throw "Error. No message format is installed.";
	}
	return this.messageFormat.privateDecryptAsync( this, message,progress,result,done );
    };


    /////////////////////////////////////////// 
    // implementation
    /////////////////////////////////////////// 

	// Generate a new random private key B bits long, using public expt E
	RSA.prototype.stepping_generate = function (B,E) {
	    var self=this;

	    //var rng = new SecureRandom(); // MODIFIED 2008/12/07
	    var rng;

	    // var qs = B>>1;
	    var qs = this.splitBitLength( B );
	    
	    // Modified Jan 4,2009
	    // self.e = parseInt(E,16);
	    // var ee = new BigInteger(E,16);
    
	    // Modified Jan 5,2009
	    //if ( typeof E == "string" ) {
	    //	self.e = parseInt(E,16);
	    //} else if ( typeof E == "number" ) {
	    //	self.e = e;
	    //} else {
	    //	throw "E must be a number object or a hex string. ";
	    //}
	    self._e(E);
	    var ee = new BigInteger(self.e);

	    var p1; 
	    var q1; 
	    var phi;

	    var et1 = ElapsedTime.create();
	    var et2 = ElapsedTime.create();
	    var et3 = ElapsedTime.create();
	    return [
		function() {
		    RSA.log("RSAEngine:0.0");
		    et1.start("Step1");
		    return BREAK;
		},
		// // Step1.ver1
		// function () {
		//// 	self.p = new BigInteger( B-qs, 1, rng );
		// 	self.p = new BigInteger( qs[0], 1, rng );
		// 	if ( self.p.subtract(BigInteger.ONE).gcd(ee).compareTo(BigInteger.ONE) == 0 && self.p.isProbablePrime(10) )
		// 		return BREAK;
		// 	return CONTINUE;
		// },

		// Step1.ver2
		[
		    function () {
			RSA.log("RSAEngine:1.1");
			self.p = new BigInteger();
			rng = new SecureRandom();
			return BREAK;
		    },
		    function () {
			RSA.log("RSAEngine:1.2");
			// return self.p.stepping_fromNumber1( B-qs, 1, rng ).BREAK();
			return self.p.stepping_fromNumber1( qs[0], 1, rng ).BREAK();
		    },
		    // // Step1.3 ver1
		    // function () {
		    // 	RSA.log("RSAEngine:1.3");
		    // 	if ( self.p.subtract(BigInteger.ONE).gcd(ee).compareTo(BigInteger.ONE) == 0 && self.p.isProbablePrime(10) )
		    // 		return EXIT;
		    // 	else
		    // 		return AGAIN;
		    // }

		    // // Step1.3 ver2
		    // function () {
		    // 	RSA.log("RSAEngine:1.3.1");
		    // 	if ( self.p.subtract(BigInteger.ONE).gcd(ee).compareTo(BigInteger.ONE) == 0 )
		    // 		return BREAK;
		    // 	else
		    // 		return AGAIN;
		    // },
		    // function () {
		    // 	RSA.log("RSAEngine:1.3.2");
		    // 	if ( self.p.isProbablePrime(10) ) {
		    // 		RSA.log("RSAEngine:1.3.2=>EXIT");
		    // 		return EXIT;
		    // 	} else {
		    // 		RSA.log("RSAEngine:1.3.2=>AGAIN");
		    // 		return AGAIN;
		    // 	}
		    // },

		    // Step1.3 ver3
		    function () {
			RSA.log("RSAEngine:1.3.1");
			if ( self.p.subtract(BigInteger.ONE).gcd(ee).compareTo(BigInteger.ONE) == 0 )
			    return BREAK;
			else
			    return AGAIN;
		    },
		    function () {
			RSA.log("RSAEngine:1.3.2 : calling stepping_isProbablePrime");
			return self.p.stepping_isProbablePrime(10).BREAK();
		    },
		    function (scope,param,subparam) {
			RSA.log("RSAEngine:1.3.3 : returned stepping_isProbablePrime" + subparam.result );
			if ( subparam.result ) {
			    RSA.log("RSAEngine:1.3.3=>EXIT");
			    return EXIT;
			} else {
			    RSA.log("RSAEngine:1.3.3=>AGAIN");
			    return AGAIN;
			}
		    },
		    EXIT
		].NAME("stepping_generate.Step1"),
		function() {
		    et1.stop();
		    RSA.log("RSAEngine:1.4");
		    return BREAK;
		},
		function() {
		    RSA.log("RSAEngine:2.0");
		    et2.start("Step2");
		    return BREAK;
		},
		// // Step2.ver1
		// function() {
		//// 	self.q = new BigInteger( qs, 1, rng );
		// 	self.q = new BigInteger( qs[1], 1, rng );
		// 	if ( self.q.subtract( BigInteger.ONE ).gcd( ee ).compareTo( BigInteger.ONE ) == 0 && self.q.isProbablePrime(10) )
		// 		return BREAK;
		// 	return CONTINUE;
		// },

		// Step2.ver2
		[
		    function() {
			RSA.log("RSAEngine:2.1");
			self.q = new BigInteger();
			return BREAK;
		    },
		    function () {
			RSA.log("RSAEngine:2.2");
			// return self.q.stepping_fromNumber1( qs, 1, rng ).BREAK();
			return self.q.stepping_fromNumber1( qs[1], 1, rng ).BREAK();
		    },
		    // // Step2.3 ver1 >>
		    // function () {
		    // 	RSA.log("RSAEngine:2.3");
		    // 	if ( self.q.subtract( BigInteger.ONE ).gcd( ee ).compareTo( BigInteger.ONE ) == 0 && self.q.isProbablePrime(10) )
		    // 		return EXIT;
		    // 	else
		    // 		return AGAIN;
		    // }
		    // <<

		    // // Step2.3 ver2>>>
		    // function () {
		    // 	RSA.log("RSAEngine:2.3.1");
		    // 	if ( self.q.subtract( BigInteger.ONE ).gcd( ee ).compareTo( BigInteger.ONE ) == 0 )
		    // 		return BREAK;
		    // 	else
		    // 		return AGAIN;
		    // },
		    // function () {
		    // 	RSA.log("RSAEngine:2.3.2");
		    // 	if ( self.q.isProbablePrime(10) ) {
		    // 		RSA.log("RSAEngine:2.3.2=>EXIT");
		    // 		return EXIT;
		    // 	} else {
		    // 		RSA.log("RSAEngine:2.3.2=>AGAIN");
		    // 		return AGAIN;
		    // 	}
		    // },
		    //<<<
		    // Step2.3 ver2>>>
		    function () {
			RSA.log("RSAEngine:2.3.1");
			if ( self.q.subtract( BigInteger.ONE ).gcd( ee ).compareTo( BigInteger.ONE ) == 0 )
			    return BREAK;
			else
			    return AGAIN;
		    },
		    function() {
			RSA.log("RSAEngine:2.3.2");
			return self.q.stepping_isProbablePrime(10).BREAK();
		    },
		    function(scope,param,subparam) {
			RSA.log( "RSAEngine:2.3.3:subparam.result="+subparam.result );
			if ( subparam.result ) {
			    RSA.log("RSAEngine:2.3.3=>EXIT");
			    return EXIT;
			} else {
			    RSA.log("RSAEngine:2.3.3=>AGAIN");
			    return AGAIN;
			}
		    },
		    // <<<
		    EXIT
		].NAME("stepping_generate.Step2"),
		function() {
		    et2.stop();
		    RSA.log("RSAEngine:2.3");
		    return BREAK;
		},
		function() {
		    if ( self.p.compareTo(self.q) <= 0 ) {
			var t = self.p;
			self.p = self.q;
			self.q = t;
		    }
		    return BREAK;
		},
		function() {
		    RSA.log("RSAEngine:3.1");
		    RSA.log( "p=" + self.p.toString(16) );
		    RSA.log( "q=" + self.q.toString(16) );
		    et3.start("Step3");
		    return BREAK;
		},
		// //Step3.2 ver1
		// function() {
		// 	RSA.log("RSAEngine:3.2");
		// 	var p1 = self.p.subtract( BigInteger.ONE );
		// 	var q1 = self.q.subtract( BigInteger.ONE );
		// 	var phi = p1.multiply( q1 );
		// 	RSA.log("RSAEngine:3.3");
		// 	if ( phi.gcd(ee).compareTo( BigInteger.ONE ) == 0 ) {
		// 		RSA.log("RSAEngine:3.3.1");
		// 		self.n = self.p.multiply( self.q );
		// 		// ADDED 2008/12/1 >>>
		// 		if ( self.n.bitLength() != B ) {
		// 			RSA.log("RSAEngine:3.3.2.1:AGAIN bitLength="+self.n.bitLength() + " B=" + B );
		// 			return AGAIN;
		// 		}
		// 		RSA.log("RSAEngine:3.3.2.2");
		// 		// ADDED 2008/12/1 <<<
		// 		var et4 =ElapsedTime.create();
		// 		et4.start("modInverse1");
		// 		self.d = ee.modInverse( phi );
		// 		et4.stop();
		// 		et4.start("modInverse2");
		// 		self.dmp1 = self.d.mod(p1);
		// 		self.dmq1 = self.d.mod(q1);
		// 		et4.stop();
		// 		et4.start("modInverse3");
		// 		self.coeff = self.q.modInverse(self.p);
		// 		et4.stop();
		// 		return BREAK;
		// 	}
		// 	RSA.log("RSAEngine:3.4");
		// 	return AGAIN;
		// },

		// // Step3.2 ver2 >>>
		function() {
		    RSA.log("RSAEngine:3.2");
		    /* var */ p1 = self.p.subtract( BigInteger.ONE );
		    /* var */ q1 = self.q.subtract( BigInteger.ONE );
		    /* var */ phi = p1.multiply( q1 );
		    if ( phi.gcd(ee).compareTo( BigInteger.ONE ) == 0 ) {
			RSA.log("RSAEngine:3.2=>BREAK");
			return BREAK;
		    } else {
			RSA.log("RSAEngine:3.2=>AGAIN");
			return AGAIN;
		    }
		},
		function() {
		    RSA.log("RSAEngine:3.2.sub");
		    // ADDED 11Dec,2008 Ats >>>
		    // When p and q in a RSA key have the same value, the RSA
		    // key cannot encrypt/decrypt messages correctly.
		    // Check if they have the same value and if so regenerate these value again.
		    // Though rarely do p and q conflict when key length is large enough.
		    // <<<
		    if ( self.p.compareTo( self.q ) ==0 ) {
			RSA.log("RSAEngine:3.2.sub +++ P & Q ARE EQUAL !!!");
			return AGAIN;
		    }
		    self.n = self.p.multiply( self.q );
		    // ADDED 2008/12/1 >>>
		    // if ( self.n.bitLength() != B ) { 
		    // if ( self.n.bitLength() < B ) { // modified 2009/1/13
		    if ( ! self.isProperBitLength( self.n, B ) ) { // modified 2009/1/15
			RSA.log("RSAEngine:3.3.2.1:AGAIN bitLength="+self.n.bitLength() + " B=" + B );
			return AGAIN;
		    }
		    // ADDED 2008/12/1 <<<
		    return BREAK;
		},
		function() {
		    RSA.log("RSAEngine:3.3.1");

		    var et4 =ElapsedTime.create();

		    RSA.log("RSAEngine:3.3.1(1)");
		    et4.start("modInverse1");
		    self.d = ee.modInverse( phi );
		    et4.stop();
		    RSA.log("RSAEngine:3.3.2(2)");

		    self._ksize(B); // added Jan15,2009
		    return BREAK;
		},
		function() {
		    RSA.log("RSAEngine:3.3.2");

		    var et4 =ElapsedTime.create();
		    et4.start("modInverse2");
		    self.dmp1 = self.d.mod(p1);
		    self.dmq1 = self.d.mod(q1);
		    et4.stop();

		    return BREAK;
		},
		function() {
		    RSA.log("RSAEngine:3.3.3");

		    var et4 =ElapsedTime.create();
		    et4.start("modInverse3");
		    self.coeff = self.q.modInverse(self.p);
		    et4.stop();

		    return BREAK;
		},

		function() {
		    et3.stop();
		    RSA.log("RSAEngine:3.5");
		    return BREAK;
		},
		// <<<
		EXIT
	    ].NAME("stepping_generate");
	};

    /////////////////////////////////////////// 
	
	RSA.prototype.stepping_processPublic = function(m){
	    return m.stepping_modPow( new BigInteger(this.e), this.n);
	};

	RSA.prototype.stepping_processPrivate = function(m){
	    return m.stepping_modPow( this.d, this.n );
	};
	
    
};
initRSA3( this );

// vim:ts=8 sw=4:noexpandtab:
