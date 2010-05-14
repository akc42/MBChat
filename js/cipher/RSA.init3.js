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
 *
 *	Alan Chandler (http://www.chandlerfamily.org.uk) May 2010
 *  	I found the nonstructured routines conflicted with Mootools framework,
 *		so rewrote to use my own equivalent (although simpler) version.  This file has then
 *		then been modified to use it. 
 */

(function ( ) {

	//This file has been reworked to rely on class NS - A Chandler May 2010
	
    /////////////////////////////////////////// 
    // import
    /////////////////////////////////////////// 
    // var RSA = __package( packages, id ).RSA;
    // var BigInteger = __package( packages, id ).BigInteger;
    // var SecureRandom = __package( packages, id ).SecureRandom;

    /////////////////////////////////////////// 
    // implementation
    /////////////////////////////////////////// 

	
	
    RSA.prototype.generateAsync = function(keylen,exp,result) {
	var self=this;
	var generator = new NS( stepping_generate,true);
	

	var _result = function(keyPair) {
	    result( self );
	};
	generator.EXEC([keylen,exp],_result);
	

    /////////////////////////////////////////// 
    // implementation
    /////////////////////////////////////////// 

	// Generate a new random private key B bits long, using public expt E
	function stepping_generate (myArgs) {
		var B,E;
		B = myArgs[0];
		E = myArgs[1];

	    //var rng = new SecureRandom(); // MODIFIED 2008/12/07
	    var rng;

	    // var qs = B>>1;
	    var qs = self.splitBitLength( B );
	    
	    self._e(E);
	    var ee = new BigInteger(self.e);

	    var p1; 
	    var q1; 
	    var phi;

	    return DO([

		// Step1.ver2

		    function () {
				RSA.log("RSAEngine:1.1");
				self.p = new BigInteger();
				rng = new SecureRandom();

		    },
		    function () {
				RSA.log("RSAEngine:1.2");
				// return self.p.stepping_fromNumber1( B-qs, 1, rng ).BREAK();
				return self.p.stepping_fromNumber1( qs[0], 1, rng );
		    },
			[
		    // Step1.3 ver3
				function () {
					RSA.log("RSAEngine:1.3.1");
					if ( self.p.subtract(BigInteger.ONE).gcd(ee).compareTo(BigInteger.ONE) != 0 ) return AGAIN();
				},
				function () {
					RSA.log("RSAEngine:1.3.2 : calling stepping_isProbablePrime");
					return self.p.stepping_isProbablePrime(10);
				},
				function (result) {
					RSA.log("RSAEngine:1.3.3 : returned stepping_isProbablePrime" + result );
					if ( result ) {
						RSA.log("RSAEngine:1.3.3=>EXIT");
						return DONE();
					} else {
						RSA.log("RSAEngine:1.3.3=>AGAIN");
						return AGAIN();
					}
				},
				EXIT
			],
			function() {
				RSA.log("RSAEngine:2.0");
			},
			// Step2.ver2
			[
				function() {
				RSA.log("RSAEngine:2.1");
				self.q = new BigInteger();
				},
				function () {
				RSA.log("RSAEngine:2.2");
				// return self.q.stepping_fromNumber1( qs, 1, rng ).BREAK();
				return self.q.stepping_fromNumber1( qs[1], 1, rng );
				},
				// Step2.3 ver2>>>
				function () {
					var result = self.q.subtract( BigInteger.ONE ).gcd( ee ).compareTo( BigInteger.ONE );
					RSA.log("RSAEngine:2.3.1 returned from q.stepping_from number with result "+result);
					if ( result != 0 ) return AGAIN();
				},
				function() {
					RSA.log("RSAEngine:2.3.2");
					return self.q.stepping_isProbablePrime(10);
				},
				function(result) {
					RSA.log( "RSAEngine:2.3.3:result="+result );
					if ( result ) {
						RSA.log("RSAEngine:2.3.3=>EXIT");
						return DONE();
					} else {
						RSA.log("RSAEngine:2.3.3=>AGAIN");
						return AGAIN();
					}
				},
				// <<<
				EXIT
			],
			function() {
				RSA.log("RSAEngine:2.3");
				if ( self.p.compareTo(self.q) <= 0 ) {
				    var t = self.p;
				    self.p = self.q;
				    self.q = t;
				}
				RSA.log("RSAEngine:3.1");
				RSA.log( "p=" + self.p.toString(16) );
				RSA.log( "q=" + self.q.toString(16) );
			},

			// // Step3.2 ver2 >>>
			function() {
				RSA.log("RSAEngine:3.2");
				/* var */ p1 = self.p.subtract( BigInteger.ONE );
				/* var */ q1 = self.q.subtract( BigInteger.ONE );
				/* var */ phi = p1.multiply( q1 );
				if ( phi.gcd(ee).compareTo( BigInteger.ONE ) == 0 ) {
					RSA.log("RSAEngine:3.2=>BREAK");
					return ;
				} else {
					RSA.log("RSAEngine:3.2=>AGAIN");
					return AGAIN();
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
					return AGAIN();
				}
				self.n = self.p.multiply( self.q );
				// ADDED 2008/12/1 >>>
				// if ( self.n.bitLength() != B ) { 
				// if ( self.n.bitLength() < B ) { // modified 2009/1/13
				if ( ! self.isProperBitLength( self.n, B ) ) { // modified 2009/1/15
					RSA.log("RSAEngine:3.3.2.1:AGAIN bitLength="+self.n.bitLength() + " B=" + B );
					return AGAIN();
				}
			},
			function() {
				RSA.log("RSAEngine:3.3.1");


				RSA.log("RSAEngine:3.3.1(1)");
				self.d = ee.modInverse( phi );
				RSA.log("RSAEngine:3.3.2(2)");

				self._ksize(B); // added Jan15,2009
			},
			function() {
				RSA.log("RSAEngine:3.3.2");

				self.dmp1 = self.d.mod(p1);
				self.dmq1 = self.d.mod(q1);

			},
			function() {
				RSA.log("RSAEngine:3.3.3");

				self.coeff = self.q.modInverse(self.p);

			},
			// <<<
			EXIT
	    ]);
	}
    }

})();

// vim:ts=8 sw=4:noexpandtab:
