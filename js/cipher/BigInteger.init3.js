/*
 * BigInteger.init3.js
 * A class which is a representation of variable lengthed integer.
 * Functions for Asynchronous Excecution
 *
 * See BigInteger.readme.txt for further information.
 *
 * ACKNOWLEDGMENT
 *     
 *     This class is originally written by Tom Wu
 *
 *     Copyright (c) 2005  Tom Wu
 *     All Rights Reserved.
 *     http://www-cs-students.stanford.edu/~tjw/jsbn/
 *
 *
 *     Several modifications are applied by Atsushi Oka
 *
 *     Atsushi Oka
 *     http://oka.nu/
 *
 *     - Packaged
 *     - Added Asynchronous Execution Feauture.
 *     - Modified some names of methods for use in Flash ActionScript
 *     - Fixed Some trivial bugs.
 */

function initBigInteger3( packages ) {
    __unit( "BigInteger.init3.js" );
    __uses( "packages.js" );
    // __uses( "nonstructured.js" );  // See ... (1)
    __uses( "BigInteger.init1.js" );
    __uses( "BigInteger.init2.js" );

    // (1) nonstructured.js
    // This file relies on nonstructured.js
    // See http://oka.nu/lib/nonstructured/nonstructured.readme.txt

    ///////////////////////////////////////
    // import
    ////////////////////////////////////////
    // var BigInteger = __package( packages,path ).BigInteger;
    var BigInteger = __import( packages, "titaniumcore.crypto.BigInteger" );

    var lowprimes  = BigInteger.lowprimes;
    var lplim      = BigInteger.lplim;


    ///////////////////////////////////////
    // implementation
    ////////////////////////////////////////

    BigInteger.prototype.stepping_fromNumber1 = function( bitLength, certainty, rnd ) {
	var self=this;
	BigInteger.log("stepping_fromNumber1");

	/*
	// ver1
	return function() {
	    BigInteger.log("stepping_fromNumber1.2");
	    // new BigInteger(int,int,RNG)
	    var et = ElapsedTime.create();
	    et.start( "fromNumber1" );
	    if( bitLength < 2 ) {
		self.fromInt( 1 );
	    } else {
		self.fromNumber2( bitLength, rnd );

		if( ! self.testBit( bitLength-1 ) )  // force MSB set
		    self.bitwiseTo( BigInteger.ONE.shiftLeft( bitLength - 1 ), BigInteger.op_or, self );

		if( self.isEven() )
		    self.dAddOffset( 1,0 ); // force odd

		var et2= ElapsedTime.create();
		et2.start( "fromNumber1.loop" );
		while( ! self.isProbablePrime( certainty ) ) {
		    self.dAddOffset( 2, 0 );
		    if( self.bitLength() > bitLength ) {
			self.subTo( BigInteger.ONE.shiftLeft(bitLength-1), self );
		    }
		}
		et2.stop();
	    }
	    et.stop();
	    return BREAK;
	};
	*/

	var NULL_CHECKER = {
	    toString : function() {
		return "*** FAILED TO RETRIEVE RESULT ***";
	    }
	};

	// ver2
	return function() {
	    BigInteger.log( "stepping_fromNumber1.1" );
	    // new BigInteger(int,int,RNG)
	    if( bitLength < 2 ) {
		self.fromInt( 1 );
		return BREAK;
	    } else {
		self.fromNumber2( bitLength, rnd );

		if( ! self.testBit( bitLength-1 ) )  // force MSB set
		    self.bitwiseTo( BigInteger.ONE.shiftLeft( bitLength - 1 ), BigInteger.op_or, self );

		if( self.isEven() )
		    self.dAddOffset( 1,0 ); // force odd

		BigInteger.log( "stepping_fromNumber1.2" );
		return [
		    // // ver1>>
		    // function() {
		    // 	BigInteger.log("stepping_fromNumber1.2.1");
		    // 	var et = ElapsedTime.create();
		    // 	et.start("stepping_fromNumber1.2.1");
		    // 	if ( self.isProbablePrime( certainty )  ) {
		    // 		et.stop();
		    // 		return EXIT;
		    // 	} else {
		    // 		et.stop();
		    // 		return BREAK;
		    // 	}
		    // },
		    // // ver1<<

		    // ver2 >>
		    function(scope,param,subparam) {
			subparam.result = NULL_CHECKER;
			BigInteger.log( "stepping_fromNumber1.2.1.1: calling stepping_isProbablePrime" );
			return self.stepping_isProbablePrime( certainty ).BREAK();
		    },
		    function(scope,param,subparam) {
			BigInteger.log( "stepping_fromNumber1.2.1.2: returned stepping_isProbablePrime:" + subparam.result );
			if ( subparam.result == null || subparam.result == NULL_CHECKER ) {
			    BigInteger.err( "stepping_fromNumber1.2.1.2: returned stepping_isProbablePrime: subparam.result == WARNING NULL " + subparam.result );
			}
			var result = subparam.result;
			if ( result ) {
			    return EXIT;
			} else {
			    return BREAK;
			}
		    },
		    // ver2 <<

		    function() {
			BigInteger.log("stepping_fromNumber1.2.2");
			self.dAddOffset( 2, 0 );
			if( self.bitLength() > bitLength ) {
			    self.subTo( BigInteger.ONE.shiftLeft(bitLength-1), self );
			}
			return BREAK;
		    },
		    AGAIN
		].BREAK();
	    }
	};
    }
    
    // (public) test primality with certainty >= 1-.5^t
    /* ver1
    BigInteger.prototype.stepping_isProbablePrime = function (t) {
	BigInteger.log( "stepping_isProbablePrime:create" );
	var self = this;
	return function(scope,param,subparam) {
	    BigInteger.log("stepping_isProbablePrime:called:" + param.result );

	    var et1 = ElapsedTime.create();
	    et1.start( "stepping_isProbablePrime" );

	    var i, x = self.abs();
	    if( x.t == 1 && x[0] <= lowprimes[ lowprimes.length-1 ] ) {
		for ( i = 0; i < lowprimes.length; ++i )
		    if ( x[0] == lowprimes[i] ) {
			//return true;
			param.result = true;
			return BREAK;
		    }
		// return false;
		param.result = false;
		return BREAK;
	    }

	    if ( x.isEven() ) {
		// return false;
		param.result = false;
		return BREAK;
	    }

	    i = 1;
	    while ( i < lowprimes.length ) {
		var m = lowprimes[i];
		var j = i+1;
		while( j < lowprimes.length && m < lplim ) {
		    m *= lowprimes[j++];
		}

		m = x.modInt(m);
		while( i < j ) {
		    if( m % lowprimes[i++] == 0 ) {
			// return false;
			param.result = false;
			return BREAK;
		    }
		}
	    }

	    // return x.millerRabin(t);
	    var et2 = ElapsedTime.create();
	    BigInteger.log("isProbablePrime:calling:"  );
	    et2.start("isProbablePrime.millerRabin");
	    var result = x.millerRabin(t);
	    et2.stop();
	    et1.stop();
	    param.result = result;
	    return BREAK;
	};
    };
    */

    // ver2>>
    BigInteger.prototype.stepping_isProbablePrime = function (t) {
	BigInteger.log( "stepping_isProbablePrime:create" );
	var self = this;
	var x = self.abs();
	var et1 = ElapsedTime.create();
	var et2 = ElapsedTime.create();
	return [
	    function(scope,param,subparam) {
		BigInteger.log("stepping_isProbablePrime No.1: " );
		// if ( param.result == null ) {
		// 	BigInteger.err("stepping_isProbablePrime No.1: WARNING param.result=null / param="+param );
		// }

		et1.start( "stepping_isProbablePrime" );

		var i;
		if( x.t == 1 && x[0] <= lowprimes[ lowprimes.length-1 ] ) {
		    for ( i = 0; i < lowprimes.length; ++i )
			if ( x[0] == lowprimes[i] ) {
			    BigInteger.log( "stepping_isProbablePrime.1 EXIT" );
			    //return true;
			    param.result = true;
			    return EXIT;
			}
		    BigInteger.log( "stepping_isProbablePrime.2 EXIT" );
		    // return false;
		    param.result = false;
		    return EXIT;
		}

		if ( x.isEven() ) {
		    BigInteger.log( "stepping_isProbablePrime.3 EXIT" );
		    // return false;
		    param.result = false;
		    return EXIT;
		}

		i = 1;
		while ( i < lowprimes.length ) {
		    var m = lowprimes[i];
		    var j = i+1;
		    while( j < lowprimes.length && m < lplim ) {
			m *= lowprimes[j++];
		    }

		    m = x.modInt(m);
		    while( i < j ) {
			if( m % lowprimes[i++] == 0 ) {
			    BigInteger.log( "stepping_isProbablePrime:4 EXIT" );
			    // return false;
			    param.result = false;
			    return EXIT;
			}
		    }
		}

		BigInteger.log( "stepping_isProbablePrime:5 BREAK" );
		return BREAK;
	    },

	    // // ver1>>
	    // function(scope,param,subparam) {
	    // 	BigInteger.log("stepping_isProbablePrime No.2:called:" + param.result );
	    // 	// return x.millerRabin(t);
	    // 	et2.start("isProbablePrime.millerRabin");
	    // 	var result = x.millerRabin(t);
	    // 	et2.stop();
	    // 	et1.stop();
	    // 	param.result = result;
	    // 	return BREAK;
	    // },
	    // // ver1<<

	    // ver2>>
	    function(scope,param,subparam) {
		BigInteger.log( "stepping_isProbablePrime No.2: calling millerRabin : subparam.result=" + subparam.result );
		et2.start("isProbablePrime.millerRabin");
		subparam.result=null;
		return x.stepping_millerRabin(t).BREAK();
	    },
	    function(scope,param,subparam) {
		BigInteger.log( "stepping_isProbablePrime No.3: returning millerRabin : subparam.result=" + subparam.result );
		et2.stop();
		et1.stop();
		param.result = subparam.result;
		BigInteger.log( "stepping_isProbablePrime No.3: param.result=" + param.result );
		return BREAK;
	    },
	    // ver2<<
	    EXIT
	].NAME("stepping_isProbablePrime");
    };
    // ver2<<


    
    // (protected) true if probably prime (HAC 4.24, Miller-Rabin)
    BigInteger.prototype.stepping_millerRabin = function ( t ) {
	BigInteger.log( "stepping_millerRabin" );
	var self=this;

	// VER1>>
	/*
	return function( score, param, subparam ) {
	    var n1 = self.subtract( BigInteger.ONE );
	    var k = n1.getLowestSetBit();
	    if ( k <= 0) {
		// return false;
		param.result = false;
		return BREAK;
	    }

	    var r = n1.shiftRight(k);
	    t = (t+1) >> 1;

	    if ( t > lowprimes.length )
		t = lowprimes.length;

	    var a = new BigInteger();
	    for ( var i = 0; i < t; ++i ) {
		a.fromInt( lowprimes[i] );
		var y = a.modPow( r,self );
		if( y.compareTo( BigInteger.ONE ) != 0 && y.compareTo( n1 ) != 0 ) {
		    var j = 1;
		    while ( j++ < k && y.compareTo( n1 ) != 0 ) {
			y = y.modPowInt( 2, self );
			if ( y.compareTo( BigInteger.ONE ) == 0 ) {
			    // return false;
			    param.result = false;
			    return BREAK;
			}
		    }
		    if ( y.compareTo( n1 ) != 0 ) {
			// return false;
			param.result = false;
			return BREAK;
		    }
		}
	    }
	    // return true;
	    param.result = true;
	    return BREAK;
	};
	*/
	// VER1<<

	// VER2>>

	// LOOP1
	var n1;
	var k;
	var r;
	var a;

	// LOOP2	
	var i=0;	
	var y;
	return [
	    function( scope, param, subparam ) {
		BigInteger.log( "stepping_millerRabin:No1" );
		n1 = self.subtract( BigInteger.ONE );
		k = n1.getLowestSetBit();
		if ( k <= 0) {
		    // return false;
		    param.result = false;
		    //return BREAK;
		    return EXIT;
		}

		r = n1.shiftRight(k);
		t = (t+1) >> 1;

		if ( t > lowprimes.length )
		    t = lowprimes.length;

		a = new BigInteger();

		return BREAK;
	    },

	    // // ver1
	    // function( scope, param, subparam ) {
	    // 	for ( var i = 0; i < t; ++i ) {
	    // 		a.fromInt( lowprimes[i] );
	    // 		
	    // 		var y = a.modPow( r,self );
	    // 		if( y.compareTo( BigInteger.ONE ) != 0 && y.compareTo( n1 ) != 0 ) {
	    // 			var j = 1;
	    // 			while ( j++ < k && y.compareTo( n1 ) != 0 ) {
	    // 				y = y.modPowInt( 2, self );
	    // 				if ( y.compareTo( BigInteger.ONE ) == 0 ) {
	    // 					// return false;
	    // 					param.result = false;
	    // 					// return BREAK;
	    // 					return EXIT;
	    // 				}
	    // 			}
	    // 			if ( y.compareTo( n1 ) != 0 ) {
	    // 				// return false;
	    // 				param.result = false;
	    // 				// return BREAK;
	    // 				return EXIT;
	    // 			}
	    // 		}
	    // 	}
	    // 	return BREAK;
	    // },
	    // // ver1

	    // // ver2
	    // function( scope, param, subparam ) {
	    // 	for ( var i = 0; i < t; ++i ) {
	    // 		a.fromInt( lowprimes[i] );
	    // 		
	    // 		var y = a.modPow( r,self );
	    // 		if( y.compareTo( BigInteger.ONE ) != 0 && y.compareTo( n1 ) != 0 ) {
	    // 			var j = 1;
	    // 			while ( j++ < k && y.compareTo( n1 ) != 0 ) {
	    // 				y = y.modPowInt( 2, self );
	    // 				if ( y.compareTo( BigInteger.ONE ) == 0 ) {
	    // 					// return false;
	    // 					param.result = false;
	    // 					// return BREAK;
	    // 					// return EXIT;
	    // 					return LABEL("LOOP1").EXIT();
	    // 				}
	    // 			}
	    // 			if ( y.compareTo( n1 ) != 0 ) {
	    // 				// return false;
	    // 				param.result = false;
	    // 				// return BREAK;
	    // 				// return EXIT;
	    // 				return LABEL("LOOP1").EXIT();
	    // 			}
	    // 		}
	    // 	}
	    // 	// return BREAK;
	    // 	return LABEL("LOOP1").BREAK();
	    // },
	    // // ver2

	    // ver3
	    // function( scope, param, subparam ) {
	    // for ( var i = 0; i < t; ++i ) {
	    [
		function() {
		    BigInteger.log( "stepping_millerRabin:No2.1" );
		    if ( i < t ) {
			BigInteger.log( "stepping_millerRabin:No2.1.1" );
			return BREAK;
		    } else {
			BigInteger.log( "stepping_millerRabin:No2.1.2" );
			return EXIT;
		    }
		},
		function() {
		    BigInteger.log( "stepping_millerRabin:No2.2" );
		    a.fromInt( lowprimes[i] );
		    return BREAK;
		},
		// // ver1>>
		// function() {
		// 	/*var*/ y = a.modPow( r,self );
		// 	return BREAK;
		// },
		// // ver1<<
		// ver2>>>
		function() {
		    BigInteger.log( "stepping_millerRabin:No2.3 : calling stepping_modPow()")
		    return a.stepping_modPow(r,self).BREAK();
		},
		function(scope,param,subparam) {
		    y = subparam.result;
		    BigInteger.log( "stepping_millerRabin:No2.4 : returned from stepping_modPow() result=" + y)
		    return BREAK;
		},
		// ver2<<<

		function (scope,param,subparam) {
		    BigInteger.log( "stepping_millerRabin:No2.5 " );
		    if( y.compareTo( BigInteger.ONE ) != 0 && y.compareTo( n1 ) != 0 ) {
			BigInteger.log( "stepping_millerRabin:No2.5.1 " );
			var j = 1;
			while ( j++ < k && y.compareTo( n1 ) != 0 ) {
			    BigInteger.log( "stepping_millerRabin:No2.5.2 j=" + j );
			    y = y.modPowInt( 2, self );
			    if ( y.compareTo( BigInteger.ONE ) == 0 ) {
				BigInteger.log( "stepping_millerRabin:No2.5.3 " );
				// return false;
				param.result = false;
				// return BREAK;
				// return EXIT;
				return LABEL("LOOP1").EXIT();
			    }
			}
			if ( y.compareTo( n1 ) != 0 ) {
			    // return false;
			    param.result = false;
			    // return BREAK;
			    // return EXIT;
			    BigInteger.log( "stepping_millerRabin:No2.5.4 " + param );
			    return LABEL("LOOP1").EXIT();
			}
			BigInteger.log( "stepping_millerRabin:No2.5.5 " );
		    }
		    BigInteger.log( "stepping_millerRabin:No2.5.2 " );
		    return BREAK;
		},
		function () {
		    BigInteger.log( "stepping_millerRabin:No2.6" );
		    ++i;
		    return BREAK;
		},
		AGAIN
	    ],
	    // }
	    // return BREAK;
	    // return LABEL("LOOP1").BREAK();
	    //},
	    //ver3

	    function ( scope, param, subparam ) {
		// return true;
		param.result = true;
		BigInteger.log( "stepping_millerRabin:No3 : param.result=" + param.result );
		// BigInteger.log( "stepping_millerRabin:No3" );
		// trace( "stepping_millerRabin:No3 : param.result=" + param.result );
		// return BREAK;
		return EXIT;
	    },
	    AGAIN
	].IDENTIFY("LOOP1");
	// VER2 <<
    };


    // // ver1
    // BigInteger.prototype.stepping_modPow = function (e,m) {
    // 	var self=this;
    // 	return function( scope, param, subparam ) {
    // 		var et = ElapsedTime .create();
    //
    // 		et.start( "modPow" );
    //
    // 		var i = e.bitLength(), k, r = new BigInteger(1), z;
    // 		if ( i <= 0 ){
    // 			// return r;
    // 			param.result = r;
    // 			return BREAK;
    // 		}
    // 		else if(i < 18) k = 1;
    // 		else if(i < 48) k = 3;
    // 		else if(i < 144) k = 4;
    // 		else if(i < 768) k = 5;
    // 		else k = 6;
    // 		if(i < 8) {
    // 			// BigInteger.log( "modPow.Classic" );
    // 			z = new BigInteger.Classic(m);
    // 		} else if(m.isEven()) {
    // 			// BigInteger.log( "modPow.Barrett" );
    // 			z = new BigInteger.Barrett(m);
    // 		} else {
    // 			// BigInteger.log( "modPow.Montgomery" );
    // 			z = new BigInteger.Montgomery(m);
    // 		}
    // 	
    // 		// precomputation
    // 		var g = new Array(), n = 3, k1 = k-1, km = (1<<k)-1;
    // 		g[1] = z.convert(self);
    // 		if ( k > 1 ) {
    // 			var g2 = new BigInteger();
    // 			z.sqrTo(g[1],g2);
    // 			while(n <= km) {
    // 				g[n] = new BigInteger();
    // 				z.mulTo(g2,g[n-2],g[n]);
    // 				n += 2;
    // 			}
    // 		}
    //
    // 	
    // 		var et1 = ElapsedTime .create();
    // 		var et2 = ElapsedTime .create();
    // 		var et3 = ElapsedTime .create();
    // 		var et4 = ElapsedTime .create();
    // 		var j = e.t-1, w, is1 = true, r2 = new BigInteger(), t;
    // 		i = BigInteger.nbits(e[j])-1;
    //
    // 		while(j >= 0) {
    // 			et1.start( "modPow1" );
    // 			if ( i >= k1) {
    // 				w = ( e[j] >> ( i - k1 ) ) & km;
    // 			} else {
    // 				w = ( e[j] & ( ( 1 << (i + 1 ) ) - 1 ) ) << ( k1 -i );
    // 				if ( j > 0 ) w |= e[j-1] >> ( BigInteger.DB + i - k1 );
    // 			}
    // 	
    // 			n = k;
    // 			while((w&1) == 0) {
    // 				w >>= 1; --n;
    // 			}
    //
    // 			if ( (i -= n) < 0) {
    // 				i += BigInteger.DB;
    // 				--j; 
    // 			}
    // 			et1.stop();
    //
    // 			et2.start( "modPow2" );
    // 			et2.stop();
    //
    // 			et3.start( "modPow3" );
    // 			if( is1 ) {	// ret == 1, don't bother squaring or multiplying it
    // 				g[w].copyTo(r);
    // 				is1 = false;
    // 			} else {
    // 				while(n > 1) {
    // 					z.sqrTo(r,r2);
    // 					z.sqrTo(r2,r);
    // 					n -= 2; 
    // 				}
    // 				if(n > 0){
    // 					z.sqrTo(r,r2);
    // 				} else {
    // 					t = r;
    // 					r = r2;
    // 					r2 = t; 
    // 				}
    // 				z.mulTo( r2, g[w], r );
    // 			}
    // 			et3.stop()
    // 	
    // 			et4.start( "modPow4" );
    // 			while ( j >= 0 && ( e[j] & ( 1 << i ) ) == 0 ) {
    // 				z.sqrTo(r,r2);
    // 				t = r;
    // 				r = r2;
    // 				r2 = t;
    // 				if(--i < 0) {
    // 					i = BigInteger.DB-1;
    // 					--j;
    // 				}
    // 			}
    // 			et4.stop()
    // 		}
    //
    // 		et.stop();
    // 		// return z.revert(r);
    // 		param.result = z.revert(r);
    // 		return BREAK;
    // 	};
    // };
    // // ver1

    // ver2
    BigInteger.prototype.stepping_modPow = function (e,m) {
	var et = ElapsedTime .create();
	var et1 = ElapsedTime .create();
	var et2 = ElapsedTime .create();
	var et3 = ElapsedTime .create();
	var et4 = ElapsedTime .create();
	var self=this;

	var i,k,r,z;
	var g;
	var j,w,is1,r2,t;
	return [
	    function( scope, param, subparam ) {
		BigInteger.log("stepping_modPow 1:" );
		et.start( "modPow" );

		// var i = e.bitLength(), k, r = new BigInteger(1), z;
		i = e.bitLength(); r = new BigInteger(1);

		if ( i <= 0 ){
		    // return r;
		    param.result = r;
		    // return BREAK;
		    return EXIT;
		}
		else if(i < 18) k = 1;
		else if(i < 48) k = 3;
		else if(i < 144) k = 4;
		else if(i < 768) k = 5;
		else k = 6;
		if(i < 8) {
		    // BigInteger.log( "modPow.Classic" );
		    z = new BigInteger.Classic(m);
		} else if(m.isEven()) {
		    // BigInteger.log( "modPow.Barrett" );
		    z = new BigInteger.Barrett(m);
		} else {
		    // BigInteger.log( "modPow.Montgomery" );
		    z = new BigInteger.Montgomery(m);
		}
	    
		// precomputation
		/*var*/ g = new Array(), n = 3, k1 = k-1, km = (1<<k)-1;
		g[1] = z.convert(self);
		if ( k > 1 ) {
		    var g2 = new BigInteger();
		    z.sqrTo(g[1],g2);
		    while(n <= km) {
			g[n] = new BigInteger();
			z.mulTo(g2,g[n-2],g[n]);
			n += 2;
		    }
		}

	    
		// /*var*/ j = e.t-1, w, is1 = true, r2 = new BigInteger(), t;
		j = e.t-1; is1 = true; r2 = new BigInteger();

		i = BigInteger.nbits(e[j])-1;

		//
		return BREAK;
	    },
	    function( scope, param, subparam ) {
		BigInteger.log("stepping_modPow 2: j="+j );
		// while(j >= 0) {
		if ( j >= 0 ) {
		    et1.start( "modPow1" );
		    if ( i >= k1) {
			w = ( e[j] >> ( i - k1 ) ) & km;
		    } else {
			w = ( e[j] & ( ( 1 << (i + 1 ) ) - 1 ) ) << ( k1 -i );
			if ( j > 0 ) w |= e[j-1] >> ( BigInteger.DB + i - k1 );
		    }
	    
		    n = k;
		    while((w&1) == 0) {
			w >>= 1; --n;
		    }

		    if ( (i -= n) < 0) {
			i += BigInteger.DB;
			--j; 
		    }
		    et1.stop();

		    et2.start( "modPow2" );
		    et2.stop();

		    et3.start( "modPow3" );
		    if( is1 ) {	// ret == 1, don't bother squaring or multiplying it
			g[w].copyTo(r);
			is1 = false;
		    } else {
			while(n > 1) {
			    z.sqrTo(r,r2);
			    z.sqrTo(r2,r);
			    n -= 2; 
			}
			if(n > 0){
			    z.sqrTo(r,r2);
			} else {
			    t = r;
			    r = r2;
			    r2 = t; 
			}
			z.mulTo( r2, g[w], r );
		    }
		    et3.stop()
	    
		    et4.start( "modPow4" );
		    while ( j >= 0 && ( e[j] & ( 1 << i ) ) == 0 ) {
			z.sqrTo(r,r2);
			t = r;
			r = r2;
			r2 = t;
			if(--i < 0) {
			    i = BigInteger.DB-1;
			    --j;
			}
		    }
		    et4.stop();
		    return CONTINUE;
		} else {
		    return BREAK;
		}
		// }
		// return BREAK;
	    },
	    function(scope,param,subparam) {
		et.stop();
		// return z.revert(r);
		param.result = z.revert(r);
		BigInteger.log("stepping_modPow 3:result=" + param.result );
		//return BREAK;
		return EXIT;
	    },
	    AGAIN
	];
    };
    // ver2<<


    // // (protected) true if probably prime (HAC 4.24, Miller-Rabin)
    // BigInteger.prototype.millerRabin = function ( t ) {
    // 	BigInteger.log( "millerRabin" );
    // 	var n1 = this.subtract( BigInteger.ONE );
    // 	var k = n1.getLowestSetBit();
    // 	if ( k <= 0)
    // 		return false;
    //
    // 	var r = n1.shiftRight(k);
    // 	t = (t+1) >> 1;
    //
    // 	if ( t > lowprimes.length )
    // 		t = lowprimes.length;
    //
    // 	var a = new BigInteger();
    // 	for ( var i = 0; i < t; ++i ) {
    // 		a.fromInt( lowprimes[i] );
    // 		var y = a.modPow( r,this );
    // 		if( y.compareTo( BigInteger.ONE ) != 0 && y.compareTo( n1 ) != 0 ) {
    // 			var j = 1;
    // 			while ( j++ < k && y.compareTo( n1 ) != 0 ) {
    // 				y = y.modPowInt( 2, this );
    // 				if ( y.compareTo( BigInteger.ONE ) == 0 )
    // 					return false;
    // 			}
    // 			if ( y.compareTo( n1 ) != 0 )
    // 				return false;
    // 		}
    // 	}
    // 	return true;
    // };
    
    // (protected) this^e, e < 2^32, doing sqr and mul with "r" (HAC 14.79)
    BigInteger.prototype.exp = function (e,z) {
	// trace( "exp() e "+ e + "/z="+z );
	if(e > 0xffffffff || e < 1) return BigInteger.ONE;
	var r = new BigInteger(), r2 = new BigInteger(), g = z.convert(this), i = BigInteger.nbits(e)-1;
	// BigInteger.log( "r="  + r ); 
	// BigInteger.log( "r2=" + r2);
	// BigInteger.log( "g="  + g );
	// BigInteger.log( "i="  + i );
	g.copyTo(r);
	// BigInteger.log( "g="  + g.toString(16) ); 
	// BigInteger.log( "r="  + r.toString(16) ); 
	while(--i >= 0) {
	    z.sqrTo(r,r2);
	    // trace( "i="+i +" " + r2.toString(16) );
	    // if((e&(1<<i)) > 0) z.mulTo(r2,g,r);
	    // else { var t = r; r = r2; r2 = t; }
	    if ( ( e & ( 1 << i ) ) > 0 ) {
		z.mulTo(r2,g,r);
		// trace( "*i="+i +" " + r.toString(16) );
	    } else { 
		var t = r; r = r2; r2 = t; 
	    }
	}
	return z.revert(r);
    };
    
    // (public) this^e % m, 0 <= e < 2^32
    BigInteger.prototype.modPowInt = function (e,m) {
	var z;
	if(e < 256 || m.isEven()) z = new BigInteger.Classic(m); else z = new BigInteger.Montgomery(m);
	return this.exp(e,z);
    };



}
initBigInteger3( this );


// vim:ts=8 sw=4:noexpandtab:
