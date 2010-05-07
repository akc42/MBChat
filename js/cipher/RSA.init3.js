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

(function (){
    // (1) nonstructured.js
    // This file relies on nonstructured.js
    // See http://oka.nu/lib/nonstructured/nonstructured.readme.txt

    /////////////////////////////////////////// 
    // import
    /////////////////////////////////////////// 
    // var RSA = __package( packages, id ).RSA;
    // var BigInteger = __package( packages, id ).BigInteger;
    // var SecureRandom = __package( packages, id ).SecureRandom;

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
	return ( [ generator, _result, EXIT ]).ready().frequency(1).timeout(1).progress(progress).done(done).go();
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
	    
	    self._e(E);
	    var ee = new BigInteger(self.e);

	    var p1; 
	    var q1; 
	    var phi;

	    return [
		function() {
		    RSA.log("RSAEngine:0.0");
		    return BREAK;
		},

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
		    RSA.log("RSAEngine:1.4");
		    return BREAK;
		},
		function() {
		    RSA.log("RSAEngine:2.0");

		    return BREAK;
		},

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

		    return BREAK;
		},

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
		    if ( self.p.compareTo( self.q ) ==0 ) {
			RSA.log("RSAEngine:3.2.sub +++ P & Q ARE EQUAL !!!");
			return AGAIN;
		    }
		    self.n = self.p.multiply( self.q );
		    if ( ! self.isProperBitLength( self.n, B ) ) { // modified 2009/1/15
			RSA.log("RSAEngine:3.3.2.1:AGAIN bitLength="+self.n.bitLength() + " B=" + B );
			return AGAIN;
		    }
		    // ADDED 2008/12/1 <<<
		    return BREAK;
		},
		function() {
		    RSA.log("RSAEngine:3.3.1");


		    RSA.log("RSAEngine:3.3.1(1)");
		    self.d = ee.modInverse( phi );
		    RSA.log("RSAEngine:3.3.2(2)");

		    self._ksize(B); // added Jan15,2009
		    return BREAK;
		},
		function() {
		    RSA.log("RSAEngine:3.3.2");

		    self.dmp1 = self.d.mod(p1);
		    self.dmq1 = self.d.mod(q1);

		    return BREAK;
		},
		function() {
		    RSA.log("RSAEngine:3.3.3");

		    self.coeff = self.q.modInverse(self.p);
		    return BREAK;
		},

		function() {

		    RSA.log("RSAEngine:3.5");
		    return BREAK;
		},
		// <<<
		EXIT
	    ].NAME("stepping_generate");
	};

})();


// vim:ts=8 sw=4:noexpandtab:
