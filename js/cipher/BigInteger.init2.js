/*
 * BigInteger.init2.js
 * A class which is a representation of variable lengthed integer.
 * > Extended JavaScript BN functions, required for RSA private ops.
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

function initBigInteger2( packages ) {
    __unit( "BigInteger.init2.js" );
    __uses( "packages.js" );
    __uses( "BigInteger.init1.js" );
    // __uses( "elapse.js" );

    ///////////////////////////////////////
    // import
    ///////////////////////////////////////
    // var BigInteger = __package( packages, path ).BigInteger;
    var BigInteger = __import( packages, "titaniumcore.crypto.BigInteger" );


    ///////////////////////////////////////
    // implementation
    ///////////////////////////////////////

    // (public)
    BigInteger.prototype.clone = function () { var r = new BigInteger(); this.copyTo(r); return r; };
    
    // (public) return value as integer
    BigInteger.prototype.intValue = function () {
	if ( this.s < 0 ) {
	    if( this.t == 1 ) {
		return this[0]-BigInteger.DV;
	    } else if( this.t == 0 ) {
		return -1;
	    }
	} else if ( this.t == 1 ) {
	    return this[0];
	} else if( this.t == 0 ) {
	    return 0;
	}
	// assumes 16 < DB < 32
	return ( ( this[1] & ( ( 1 << ( 32 - BigInteger.DB ) ) -1 ) ) << BigInteger.DB ) | this[0];
    };
    
    // (public) return value as byte
    BigInteger.prototype.byteValue = function () { 
	return ( this.t ==0 ) ? this.s : ( this[0] << 24 ) >> 24; 
    };
    
    // (public) return value as short (assumes DB>=16)
    BigInteger.prototype.shortValue = function () { 
	return ( this.t==0 ) ? this.s : ( this[0] << 16 ) >> 16;
    };
    
    // (protected) return x s.t. r^x < DV
    BigInteger.prototype.chunkSize = function (r) {
	return Math.floor( Math.LN2 * BigInteger.DB / Math.log( r ) ); 
    };
    
    // (public) 0 if this == 0, 1 if this > 0
    BigInteger.prototype.signum = function () {
	if( this.s < 0 )
	    return -1;
	else if ( this.t <= 0 || (this.t == 1 && this[0] <= 0 ) )
	    return 0;
	else
	    return 1;
    };
    
    // (protected) convert to radix string
    BigInteger.prototype.toRadix = function (b) {
	if ( b == null ) {
	    b = 10;
	}
	if ( this.signum() == 0 || b < 2 || b > 36 ) {
	    return "0";
	}
	var cs = this.chunkSize( b );
	var a = Math.pow( b, cs );
	var d = new BigInteger(a), y = new BigInteger(), z = new BigInteger(), r = "";
	this.divRemTo(d,y,z);
	while ( y.signum() > 0 ) {
	    r = ( a + z.intValue() ).toString(b).substr(1) + r;
	    y.divRemTo( d, y, z );
	}
	return z.intValue().toString(b) + r;
    };
    
    // (protected) convert from radix string
    BigInteger.prototype.fromRadix = function (s,b) {
	//ElapsedTime.start( "fromRadix" );
	this.fromInt(0);
	if ( b == null ){
	    b = 10;
	}
	var cs = this.chunkSize( b );
	var d = Math.pow( b, cs );
	var mi = false;
	var j = 0;
	var w = 0;

	for ( var i = 0; i < s.length; ++i ) {
	    var x = BigInteger.intAt( s, i );
	    if( x < 0 ) {
		if ( s.charAt(i) == "-" && this.signum() == 0 ) {
		    mi = true;
		}
		continue;
	    }
	    w = b * w + x;
	    if ( ++j >= cs ) {
		this.dMultiply( d );
		this.dAddOffset( w, 0 );
		j = 0;
		w = 0;
	    }
	}
	if( j > 0 ) {
	    this.dMultiply( Math.pow( b, j ) );
	    this.dAddOffset( w, 0 );
	}
	if ( mi ) {
	    BigInteger.ZERO.subTo( this, this );
	}
	// ElapsedTime.stop(  );
    };

    var _ctr=0;
    
    /* 2008/11/24 MODIFIED 
    // (protected) alternate constructor
    BigInteger.prototype.fromNumber = function (a,b,c) {
	if ( "number" == typeof b ) {
	    // new BigInteger(int,int,RNG)
	    var et = ElapsedTime.create();
	    et.start( "fromNumber1" );
	    if( a < 2 ) {
		this.fromInt( 1 );
	    } else {
		this.fromNumber( a, c );

		if( ! this.testBit( a-1 ) )  // force MSB set
		    this.bitwiseTo( BigInteger.ONE.shiftLeft( a - 1 ), BigInteger.op_or, this );

		if( this.isEven() )
		    this.dAddOffset( 1,0 ); // force odd

		var et2= ElapsedTime.create();
		et2.start( "fromNumber1.loop" );
		while( ! this.isProbablePrime( b ) ) {
		    this.dAddOffset( 2, 0 );
		    if( this.bitLength() > a ) {
			this.subTo( BigInteger.ONE.shiftLeft(a-1), this );
		    }
		}
		et2.stop();
	    }
	    et.stop();
	} else {
	    // new BigInteger(int,RNG)
	    var et = ElapsedTime.create();
	    et.start( "fromNumber2" );
	    var x = new Array(), t = a & 7;
	    x.length = (a>>3)+1;
	    b.nextBytes( x );
	    if ( t > 0 )
		x[0] &= ((1<<t)-1);
	    else
		x[0] = 0;
	    this.fromString( x, 256 );
	    et.stop();
	}
    };
    */
    /*
    // (protected) alternate constructor
    BigInteger.prototype.fromNumber = function (a,b,c) {
	if ( "number" == typeof b ) {
	    this.fromNumber1( a,b,c );
	} else {
	    this.fromNumber2( a,b );
	}
    };
    BigInteger.prototype.fromNumber1 = function( a, b, c ) {
	// new BigInteger(int,int,SecureRandom)
	var et = ElapsedTime.create();
	et.start( "fromNumber1" );
	if( a < 2 ) {
	    this.fromInt( 1 );
	} else {
	    this.fromNumber2( a, c );

	    if( ! this.testBit( a-1 ) )  // force MSB set
		this.bitwiseTo( BigInteger.ONE.shiftLeft( a - 1 ), BigInteger.op_or, this );

	    if( this.isEven() )
		this.dAddOffset( 1,0 ); // force odd

	    var et2= ElapsedTime.create();
	    et2.start( "fromNumber1.loop" );
	    while( ! this.isProbablePrime( b ) ) {
		this.dAddOffset( 2, 0 );
		if( this.bitLength() > a ) {
		    this.subTo( BigInteger.ONE.shiftLeft(a-1), this );
		}
	    }
	    et2.stop();
	}
	et.stop();
    }

    BigInteger.prototype.fromNumber2 = function( a, b ) {
	// new BigInteger(int,SecureRandom)
	var et = ElapsedTime.create();
	et.start( "fromNumber2" );
	var x = new Array(), t = a & 7;
	x.length = (a>>3)+1;
	b.nextBytes( x );
	if ( t > 0 )
	    x[0] &= ((1<<t)-1);
	else
	    x[0] = 0;
	this.fromString( x, 256 );
	et.stop();
    };
    */

    BigInteger.prototype.fromNumber1 = function( bitLength, certainty, rnd ) {
	// new BigInteger(int,int,SecureRandom)
	var et = ElapsedTime.create();
	et.start( "fromNumber1" );
	if( bitLength < 2 ) {
	    this.fromInt( 1 );
	} else {
	    this.fromNumber2( bitLength, rnd );

	    if( ! this.testBit( bitLength-1 ) ) {  // force MSB set
		this.bitwiseTo( BigInteger.ONE.shiftLeft( bitLength - 1 ), BigInteger.op_or, this );
	    }

	    if( this.isEven() ) {
		this.dAddOffset( 1,0 ); // force odd
	    }

	    var et2= ElapsedTime.create();
	    et2.start( "fromNumber1.loop" );
	    while( ! this.isProbablePrime( certainty ) ) {
		this.dAddOffset( 2, 0 );
		if( this.bitLength() > bitLength ) {
		    this.subTo( BigInteger.ONE.shiftLeft( bitLength - 1 ), this );
		}
	    }
	    et2.stop();
	}
	et.stop();
    }

    BigInteger.prototype.fromNumber2 = function( bitLength, rnd ) {
	// new BigInteger(int,SecureRandom)
	var et = ElapsedTime.create();
	et.start( "fromNumber2" );
	var x = new Array();
	var t = bitLength & 7;
	x.length = ( bitLength >> 3 ) + 1;
	rnd.nextBytes( x );
	if ( t > 0 )
	    x[0] &= ( ( 1<<t ) -1 );
	else
	    x[0] = 0;
	this.fromString( x, 256 );
	et.stop();
    };

    
    // (public) convert to bigendian byte array
    BigInteger.prototype.toByteArray = function () {
	var i = this.t;
	var r = new Array();
	r[0] = this.s;
	var p = BigInteger.DB - ( i * BigInteger.DB ) % 8;
	var d;
	var k = 0;
	if ( i-- > 0 ) {
	    if ( p < BigInteger.DB && ( d = this[i] >> p ) != ( this.s & BigInteger.DM ) >> p ) {
		r[ k++ ] = d | ( this.s << ( BigInteger.DB - p ) );
	    }
	    while ( i >= 0 ) {
		if ( p < 8 ) {
		    d = ( this[i] & ( ( 1 << p ) - 1 ) ) << ( 8 - p );
		    d |= this[--i] >> ( p += BigInteger.DB - 8 );
		} else {
		    d = ( this[i] >> ( p-=8 ) ) & 0xff;
		    if ( p <= 0 ) {
			p += BigInteger.DB; 
			--i;
		    }
		}
		if ( ( d & 0x80 ) != 0 ) {
		    d |= -256;
		}
		if ( k == 0 && ( this.s&0x80 ) != ( d&0x80 ) ){
		    ++k;
		}
		if ( k > 0 || d != this.s) {
		    r[k++] = d;
		}
	    }
	}
	// ADDED JAN 6,2009 ATSUSHI OKA
	for ( var idx=0; idx<r.length; idx++ ) {
	    r[idx] = 0xff & r[idx];
	}
	// <<<
	return r;
    };
    
    BigInteger.prototype.equals = function (a) { return(this.compareTo(a)==0); };
    BigInteger.prototype.min = function (a) { return(this.compareTo(a)<0)?this:a; };
    BigInteger.prototype.max = function (a) { return(this.compareTo(a)>0)?this:a; };
    
    // (protected) r = this op a (bitwise)
    BigInteger.prototype.bitwiseTo = function (a,op,r) {
	var i, f, m = Math.min( a.t, this.t );
	for(i = 0; i < m; ++i) r[i] = op(this[i],a[i]);
	if(a.t < this.t) {
	    f = a.s&BigInteger.DM;
	    for(i = m; i < this.t; ++i) r[i] = op(this[i],f);
	    r.t = this.t;
	}
	else {
	    f = this.s&BigInteger.DM;
	    for(i = m; i < a.t; ++i) r[i] = op(f,a[i]);
	    r.t = a.t;
	}
	r.s = op(this.s,a.s);
	r.clamp();
    };
    
    // (public) this & a
    BigInteger.op_and = function (x,y) { return x & y; };
    // MODIFIED BY ATS 2008/11/22 FOR COMPATIBILITY TO Flash ActionScript
    // BigInteger.prototype.and = function (a) { var r = new BigInteger(); this.bitwiseTo(a,BigInteger.op_and,r); return r; };
    BigInteger.prototype.ope_and = function (a) { var r = new BigInteger(); this.bitwiseTo(a,BigInteger.op_and,r); return r; };
    BigInteger.prototype[ "and" ] = BigInteger.prototype.ope_and;
    // <<<
    
    // (public) this | a
    BigInteger.op_or = function (x,y) { return x|y; };
    // MODIFIED BY ATS 2008/11/22 FOR COMPATIBILITY TO Flash ActionScript >>>
    // BigInteger.prototype.or = function (a) { var r = new BigInteger(); this.bitwiseTo(a,BigInteger.op_or,r); return r; };
    BigInteger.prototype.ope_or = function (a) { var r = new BigInteger(); this.bitwiseTo(a,BigInteger.op_or,r); return r; };
    BigInteger.prototype[ "or" ] = BigInteger.prototype.ope_or;
    // <<<
    
    // (public) this ^ a
    // MODIFIED BY ATS 2008/11/22 FOR COMPATIBILITY TO Flash ActionScript >>>
    BigInteger.op_xor = function (x,y) { return x^y; };
    // BigInteger.prototype.xor = function (a) { var r = new BigInteger(); this.bitwiseTo(a,BigInteger.op_xor,r); return r; };
    BigInteger.prototype.ope_xor = function (a) { var r = new BigInteger(); this.bitwiseTo(a,BigInteger.op_xor,r); return r; };
    BigInteger.prototype[ "xor" ] = BigInteger.prototype.ope_xor;
    // <<<
    
    // (public) this & ~a
    // MODIFIED BY ATS 2008/11/22 FOR COMPATIBILITY TO Flash ActionScript >>>
    BigInteger.op_andnot = function (x,y) { return x&~y; };
    // BigInteger.prototype.andNot = function (a) { var r = new BigInteger(); this.bitwiseTo(a,BigInteger.op_andnot,r); return r; };
    BigInteger.prototype.ope_andNot = function (a) { var r = new BigInteger(); this.bitwiseTo(a,BigInteger.op_andnot,r); return r; };
    BigInteger.prototype[ "andNot" ] = BigInteger.prototype.ope_andNot;
    
    // (public) ~this
    // MODIFIED BY ATS 2008/11/22 FOR COMPATIBILITY TO Flash ActionScript >>>
    // BigInteger.prototype.not = function () {
    //   var r = new BigInteger();
    //   for(var i = 0; i < this.t; ++i) r[i] = BigInteger.DM&~this[i];
    //   r.t = this.t;
    //   r.s = ~this.s;
    //   return r;
    // };
    BigInteger.prototype.ope_not = function () {
	var r = new BigInteger();
	for(var i = 0; i < this.t; ++i) r[i] = BigInteger.DM&~this[i];
	r.t = this.t;
	r.s = ~this.s;
	return r;
    };
    BigInteger.prototype[ "not" ] = BigInteger.prototype.ope_not;
    // <<<
    
    // (public) this << n
    BigInteger.prototype.shiftLeft = function (n) {
	var r = new BigInteger();
	if(n < 0) this.rShiftTo(-n,r); else this.lShiftTo(n,r);
	return r;
    };
    
    // (public) this >> n
    BigInteger.prototype.shiftRight = function (n) {
	var r = new BigInteger();
	if(n < 0) this.lShiftTo(-n,r); else this.rShiftTo(n,r);
	return r;
    };
    
    // (private)return index of lowest 1-bit in x, x < 2^31
    BigInteger.lbit = function (x) {
	if(x == 0) return -1;
	var r = 0;
	if((x&0xffff) == 0) { x >>= 16; r += 16; }
	if((x&0xff) == 0) { x >>= 8; r += 8; }
	if((x&0xf) == 0) { x >>= 4; r += 4; }
	if((x&3) == 0) { x >>= 2; r += 2; }
	if((x&1) == 0) ++r;
	return r;
    };
    
    // (public) returns index of lowest 1-bit (or -1 if none)
    BigInteger.prototype.getLowestSetBit = function () {
	for(var i = 0; i < this.t; ++i)
	    if(this[i] != 0) return i * BigInteger.DB + BigInteger.lbit( this[i] );
	if(this.s < 0) return this.t * BigInteger.DB;
	return -1;
    };
    
    // (private) return number of 1 bits in x
    BigInteger.cbit = function (x) {
	var r = 0;
	while(x != 0) { x &= x-1; ++r; }
	return r;
    };
    
    // (public) return number of set bits
    BigInteger.prototype.bitCount = function () {
	var r = 0, x = this.s&BigInteger.DM;
	for(var i = 0; i < this.t; ++i) r += BigInteger.cbit(this[i]^x);
	return r;
    };
    
    // (public) true iff nth bit is set
    BigInteger.prototype.testBit = function (n) {
	var j = Math.floor(n/BigInteger.DB);
	if(j >= this.t) return(this.s!=0);
	return((this[j]&(1<<(n%BigInteger.DB)))!=0);
    };
    
    // (protected) this op (1<<n)
    BigInteger.prototype.changeBit = function (n,op) {
	var r = BigInteger.ONE.shiftLeft(n);
	this.bitwiseTo(r,op,r);
	return r;
    };
    
    // (public) this | (1<<n)
    //BigInteger.prototype.setBit = function (n) { return this.changeBit( n, op_or ); }; // is this supposed to be with the prefix JSBN ?? ATS 2008/11/22
    BigInteger.prototype.setBit = function (n) { return this.changeBit( n, BigInteger.op_or ); }; // FIXED 12 DEC,2008 Ats adding suffix on it
    
    // (public) this & ~(1<<n)
    // BigInteger.prototype.clearBit = function (n) { return this.changeBit( n, op_andnot ); }; // is this supposed to be with the prefix JSBN ?? ATS 2008/11/22
    BigInteger.prototype.clearBit = function (n) { return this.changeBit( n, BigInteger.op_andnot ); }; // FIXED 12 DEC,2008 Ats adding suffix on it
    
    // (public) this ^ (1<<n)
    //BigInteger.prototype.flipBit = function (n) { return this.changeBit( n, op_xor ); };// is this supposed to be with the prefix JSBN ?? ATS 2008/11/22
    BigInteger.prototype.flipBit = function (n) { return this.changeBit( n, BigInteger.op_xor ); }; // FIXED 12 DEC,2008 Ats adding suffix on it
    
    // (protected) r = this + a
    BigInteger.prototype.addTo = function (a,r) {
	var i = 0, c = 0, m = Math.min(a.t,this.t);
	while(i < m) {
	    c += this[i]+a[i];
	    r[i++] = c&BigInteger.DM;
	    c >>= BigInteger.DB;
	}
	if(a.t < this.t) {
	    c += a.s;
	    while(i < this.t) {
		c += this[i];
		r[i++] = c&BigInteger.DM;
		c >>= BigInteger.DB;
	    }
	    c += this.s;
	}
	else {
	    c += this.s;
	    while(i < a.t) {
		c += a[i];
		r[i++] = c&BigInteger.DM;
		c >>= BigInteger.DB;
	    }
	    c += a.s;
	}
	r.s = (c<0)?-1:0;
	if(c > 0) r[i++] = c;
	else if(c < -1) r[i++] = BigInteger.DV+c;
	r.t = i;
	r.clamp();
    };
    
    // (public) this + a
    // BigInteger.prototype.add = function (a) { var r = new BigInteger(); this.addTo(a,r); return r; };
    BigInteger.prototype.ope_add = function (a) { var r = new BigInteger(); this.addTo(a,r); return r; };
    BigInteger.prototype[ "add" ] = BigInteger.prototype.ope_add;
    
    // (public) this - a
    // BigInteger.prototype.subtract = function (a) { var r = new BigInteger(); this.subTo(a,r); return r; };
    BigInteger.prototype.ope_subtract = function (a) { var r = new BigInteger(); this.subTo(a,r); return r; };
    BigInteger.prototype[ "subtract" ] = BigInteger.prototype.ope_subtract;
    
    // (public) this * a
    // BigInteger.prototype.multiply = function (a) { var r = new BigInteger(); this.multiplyTo(a,r); return r; };
    BigInteger.prototype.ope_multiply = function (a) { var r = new BigInteger(); this.multiplyTo(a,r); return r; };
    BigInteger.prototype[ "multiply" ] = BigInteger.prototype.ope_multiply;
    
    // (public) this / a
    // BigInteger.prototype.divide = function (a) { var r = new BigInteger(); this.divRemTo(a,r,null); return r; };
    BigInteger.prototype.ope_divide = function (a) { var r = new BigInteger(); this.divRemTo(a,r,null); return r; };
    BigInteger.prototype[ "divide" ] = BigInteger.prototype.ope_divide;
    
    // (public) this % a
    // BigInteger.prototype.remainder = function (a) { var r = new BigInteger(); this.divRemTo(a,null,r); return r; };
    BigInteger.prototype.ope_remainder = function (a) { var r = new BigInteger(); this.divRemTo(a,null,r); return r; };
    BigInteger.prototype[ "remainder" ] = BigInteger.prototype.ope_remainder;
    
    // (public) [this/a,this%a]
    // BigInteger.prototype.divideAndRemainder = function (a) 
    BigInteger.prototype.ope_divideAndRemainder = function (a) {
	var q = new BigInteger(), r = new BigInteger();
	this.divRemTo(a,q,r);
	return new Array(q,r);
    };
    BigInteger.prototype[ "divideAndRemainder" ] = BigInteger.prototype.ope_divideAndRemainder;
    
    // (protected) this *= n, this >= 0, 1 < n < DV
    BigInteger.prototype.dMultiply = function (n) {
	this[this.t] = this.am(0,n-1,this,0,0,this.t);
	++this.t;
	this.clamp();
    };
    
    // (protected) this += n << w words, this >= 0
    BigInteger.prototype.dAddOffset = function (n,w) {
	while(this.t <= w) this[this.t++] = 0;
	this[w] += n;
	while(this[w] >= BigInteger.DV) {
	    this[w] -= BigInteger.DV;
	    if(++w >= this.t) this[this.t++] = 0;
	    ++this[w];
	}
    };
    
    // A "null" reducer 
    var NullExp = function () {
	this.convert = function (x) { return x; };
	this.revert = function (x) { return x; };
	this.mulTo = function (x,y,r) { x.multiplyTo(y,r); };
	this.sqrTo = function (x,r) { x.squareTo(r); };
    };

    
    // (public) this^e
    BigInteger.prototype.ope_pow = function (e) { return this.exp( e, new BigInteger.NullExp() ); };
    BigInteger.prototype["pow"] = BigInteger.prototype.ope_pow;
    
    // (protected) r = lower n words of "this * a", a.t <= n
    // "this" should be the larger one if appropriate.
    BigInteger.prototype.multiplyLowerTo = function (a,n,r) {
	var i = Math.min(this.t+a.t,n);
	r.s = 0; // assumes a,this >= 0
	r.t = i;
	while(i > 0) r[--i] = 0;
	var j;
	for(j = r.t-this.t; i < j; ++i) r[i+this.t] = this.am(0,a[i],r,i,0,this.t);
	for(j = Math.min(a.t,n); i < j; ++i) this.am(0,a[i],r,i,0,n-i);
	r.clamp();
    };
    
    // (protected) r = "this * a" without lower n words, n > 0
    // "this" should be the larger one if appropriate.
    BigInteger.prototype.multiplyUpperTo = function (a,n,r) {
	--n;
	var i = r.t = this.t+a.t-n;
	r.s = 0; // assumes a,this >= 0
	while(--i >= 0) r[i] = 0;
	for(i = Math.max(n-this.t,0); i < a.t; ++i)
	    r[this.t+i-n] = this.am(n-i,a[i],r,0,0,this.t+i-n);
	r.clamp();
	r.drShiftTo(1,r);
    };
    
    // Barrett modular reduction
    var Barrett = function (m) {
	// setup Barrett
	this.r2 = new BigInteger();
	this.q3 = new BigInteger();
	BigInteger.ONE.dlShiftTo(2*m.t,this.r2);
	this.mu = this.r2.divide(m);
	this.m = m;
    
	this.concert = function (x) {
	    if(x.s < 0 || x.t > 2*this.m.t) return x.mod(this.m);
	    else if(x.compareTo(this.m) < 0) return x;
	    else { var r = new BigInteger(); x.copyTo(r); this.reduce(r); return r; }
	};
	
	this.revert = function (x) { return x; };
	
	// x = x mod m (HAC 14.42)
	this.reduce = function (x) {
	    x.drShiftTo(this.m.t-1,this.r2);
	    if(x.t > this.m.t+1) { x.t = this.m.t+1; x.clamp(); }
	    this.mu.multiplyUpperTo(this.r2,this.m.t+1,this.q3);
	    this.m.multiplyLowerTo(this.q3,this.m.t+1,this.r2);
	    while(x.compareTo(this.r2) < 0) x.dAddOffset(1,this.m.t+1);
	    x.subTo(this.r2,x);
	    while(x.compareTo(this.m) >= 0) x.subTo(this.m,x);
	};
	
	// r = x^2 mod m; x != r
	this.sqrTo = function (x,r) { x.squareTo(r); this.reduce(r); };
	
	// r = x*y mod m; x,y != r
	this.mulTo = function (x,y,r) { x.multiplyTo(y,r); this.reduce(r); };
    };

    // *** publish ***
    BigInteger.Barrett = Barrett;
    
    /*
    // (public) this^e % m (HAC 14.85)
    BigInteger.prototype.modPow = function (e,m) {
	var i = e.bitLength(), k, r = new BigInteger(1), z;
	if(i <= 0) return r;
	else if(i < 18) k = 1;
	else if(i < 48) k = 3;
	else if(i < 144) k = 4;
	else if(i < 768) k = 5;
	else k = 6;
	if(i < 8)
	    z = new BigInteger.Classic(m);
	else if(m.isEven())
	    z = new BigInteger.Barrett(m);
	else
	    z = new BigInteger.Montgomery(m);
    
	// precomputation
	var g = new Array(), n = 3, k1 = k-1, km = (1<<k)-1;
	g[1] = z.convert(this);
	if(k > 1) {
	    var g2 = new BigInteger();
	    z.sqrTo(g[1],g2);
	    while(n <= km) {
		g[n] = new BigInteger();
		z.mulTo(g2,g[n-2],g[n]);
		n += 2;
	    }
	}
    
	var j = e.t-1, w, is1 = true, r2 = new BigInteger(), t;
	i = BigInteger.nbits(e[j])-1;
	while(j >= 0) {
	    if(i >= k1) w = (e[j]>>(i-k1))&km;
	    else {
		w = (e[j]&((1<<(i+1))-1))<<(k1-i);
		if(j > 0) w |= e[j-1]>>(BigInteger.DB+i-k1);
	    }
    
	    n = k;
	    while((w&1) == 0) { w >>= 1; --n; }
	    if((i -= n) < 0) { i += BigInteger.DB; --j; }
	    if(is1) {	// ret == 1, don't bother squaring or multiplying it
		g[w].copyTo(r);
		is1 = false;
	    }
	    else {
		while(n > 1) { z.sqrTo(r,r2); z.sqrTo(r2,r); n -= 2; }
		if(n > 0) z.sqrTo(r,r2); else { t = r; r = r2; r2 = t; }
		z.mulTo(r2,g[w],r);
	    }
    
	    while(j >= 0 && (e[j]&(1<<i)) == 0) {
		z.sqrTo(r,r2); t = r; r = r2; r2 = t;
		if(--i < 0) { i = BigInteger.DB-1; --j; }
	    }
	}
	return z.revert(r);
    };
    */

    BigInteger.prototype.modPow = function (e,m) {
	var et = ElapsedTime .create();

	et.start( "modPow" );

	var i = e.bitLength(), k, r = new BigInteger(1), z;
	if(i <= 0) return r;
	else if(i < 18) k = 1;
	else if(i < 48) k = 3;
	else if(i < 144) k = 4;
	else if(i < 768) k = 5;
	else k = 6;
	if(i < 8) {
	    // log( "modPow.Classic" );
	    z = new BigInteger.Classic(m);
	} else if(m.isEven()) {
	    // log( "modPow.Barrett" );
	    z = new BigInteger.Barrett(m);
	} else {
	    // log( "modPow.Montgomery" );
	    z = new BigInteger.Montgomery(m);
	}
    
	// precomputation
	var g = new Array(), n = 3, k1 = k-1, km = (1<<k)-1;
	g[1] = z.convert(this);
	if(k > 1) {
	    var g2 = new BigInteger();
	    z.sqrTo(g[1],g2);
	    while(n <= km) {
		g[n] = new BigInteger();
		z.mulTo(g2,g[n-2],g[n]);
		n += 2;
	    }
	}

    
	var et1 = ElapsedTime .create();
	var et2 = ElapsedTime .create();
	var et3 = ElapsedTime .create();
	var et4 = ElapsedTime .create();
	var j = e.t-1, w, is1 = true, r2 = new BigInteger(), t;
	i = BigInteger.nbits(e[j])-1;

	while(j >= 0) {
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
	    et4.stop()
	}

	et.stop();
	return z.revert(r);
    };
    
    // (public) gcd(this,a) (HAC 14.54)
    BigInteger.prototype.gcd = function (a) {
	var x = (this.s<0)?this.negate():this.clone();
	var y = (a.s<0)?a.negate():a.clone();
	if(x.compareTo(y) < 0) { var t = x; x = y; y = t; }
	var i = x.getLowestSetBit(), g = y.getLowestSetBit();
	if(g < 0) return x;
	if(i < g) g = i;
	if(g > 0) {
	    x.rShiftTo(g,x);
	    y.rShiftTo(g,y);
	}
	while(x.signum() > 0) {
	    if((i = x.getLowestSetBit()) > 0) x.rShiftTo(i,x);
	    if((i = y.getLowestSetBit()) > 0) y.rShiftTo(i,y);
	    if(x.compareTo(y) >= 0) {
		x.subTo(y,x);
		x.rShiftTo(1,x);
	    }
	    else {
		y.subTo(x,y);
		y.rShiftTo(1,y);
	    }
	}
	if(g > 0) y.lShiftTo(g,y);
	return y;
    };
    
    // (protected) this % n, n < 2^26
    BigInteger.prototype.modInt = function (n) {
	if(n <= 0) return 0;
	var d = BigInteger.DV % n, r = (this.s<0)?n-1:0;
	if(this.t > 0)
	    if(d == 0) r = this[0]%n;
	    else for(var i = this.t-1; i >= 0; --i) r = (d*r+this[i])%n;
	return r;
    };
    
    // (public) 1/this % m (HAC 14.61)
    BigInteger.prototype.modInverse = function (m) {
	var ac = m.isEven();
	if((this.isEven() && ac) || m.signum() == 0) return BigInteger.ZERO;
	var u = m.clone(), v = this.clone();
	var a = new BigInteger(1), b = new BigInteger(0), c = new BigInteger(0), d = new BigInteger(1);
	while(u.signum() != 0) {
	    while(u.isEven()) {
		u.rShiftTo(1,u);
		if(ac) {
		    if(!a.isEven() || !b.isEven()) { a.addTo(this,a); b.subTo(m,b); }
		    a.rShiftTo(1,a);
		}
		else if(!b.isEven()) b.subTo(m,b);
		b.rShiftTo(1,b);
	    }
	    while(v.isEven()) {
		v.rShiftTo(1,v);
		if(ac) {
		    if(!c.isEven() || !d.isEven()) { c.addTo(this,c); d.subTo(m,d); }
		    c.rShiftTo(1,c);
		}
		else if(!d.isEven()) d.subTo(m,d);
		d.rShiftTo(1,d);
	    }
	    if(u.compareTo(v) >= 0) {
		u.subTo(v,u);
		if(ac) a.subTo(c,a);
		b.subTo(d,b);
	    }
	    else {
		v.subTo(u,v);
		if(ac) c.subTo(a,c);
		d.subTo(b,d);
	    }
	}
	if(v.compareTo( BigInteger.ONE ) != 0) return BigInteger.ZERO;
	if(d.compareTo(m) >= 0) return d.subtract(m);
	if(d.signum() < 0) d.addTo(m,d); else return d;
	// MODIFIED BY ATS 2008/11/22 FOR COMPATIBILITY TO Flash ActionScript
	// if(d.signum() < 0) return d.add(m); else return d;
	if(d.signum() < 0) return d.ope_add(m); else return d;
    };
    
    // packages.lowprimes = [2,3,5,7,11,13,17,19,23,29,31,37,41,43,47,53,59,61,67,71,73,79,83,89,97,101,103,107,109,113,127,131,137,139,149,151,157,163,167,173,179,181,191,193,197,199,211,223,227,229,233,239,241,251,257,263,269,271,277,281,283,293,307,311,313,317,331,337,347,349,353,359,367,373,379,383,389,397,401,409,419,421,431,433,439,443,449,457,461,463,467,479,487,491,499,503,509];
    // packages.lplim = (1<<26)/packages.lowprimes[packages.lowprimes.length-1];
    var lowprimes = [2,3,5,7,11,13,17,19,23,29,31,37,41,43,47,53,59,61,67,71,73,79,83,89,97,101,103,107,109,113,127,131,137,139,149,151,157,163,167,173,179,181,191,193,197,199,211,223,227,229,233,239,241,251,257,263,269,271,277,281,283,293,307,311,313,317,331,337,347,349,353,359,367,373,379,383,389,397,401,409,419,421,431,433,439,443,449,457,461,463,467,479,487,491,499,503,509];
    var lplim = (1<<26)/lowprimes[lowprimes.length-1];
    BigInteger.lowprimes = lowprimes;
    BigInteger.lplim = lplim;
    
    // (public) test primality with certainty >= 1-.5^t
    BigInteger.prototype.isProbablePrime = function (t) {
	var et1 = ElapsedTime.create();
	et1.start("isProbablePrime");

	var i, x = this.abs();
	if( x.t == 1 && x[0] <= lowprimes[ lowprimes.length-1 ] ) {
	    for ( i = 0; i < lowprimes.length; ++i )
		if ( x[0] == lowprimes[i] ) return true;
	    return false;
	}

	if ( x.isEven() )
	    return false;

	i = 1;
	while ( i < lowprimes.length ) {
	    var m = lowprimes[i];
	    var j = i+1;
	    while( j < lowprimes.length && m < lplim ) {
		m *= lowprimes[j++];
	    }

	    m = x.modInt(m);
	    while( i < j ) {
		if( m % lowprimes[i++] == 0 )
		    return false;
	    }
	}
	// return x.millerRabin(t);
	var et2 = ElapsedTime.create();
	et2.start("isProbablePrime.millerRabin");
	var result = x.millerRabin(t);
	et2.stop();
	et1.stop();
	return result;
    };

    // (protected) true if probably prime (HAC 4.24, Miller-Rabin)
    BigInteger.prototype.millerRabin = function ( t ) {
	var n1 = this.subtract( BigInteger.ONE );
	var k = n1.getLowestSetBit();
	if ( k <= 0)
	    return false;

	var r = n1.shiftRight(k);
	t = (t+1) >> 1;

	if ( t > lowprimes.length )
	    t = lowprimes.length;

	var a = new BigInteger();
	for ( var i = 0; i < t; ++i ) {
	    a.fromInt( lowprimes[i] );
	    var y = a.modPow( r,this );
	    if( y.compareTo( BigInteger.ONE ) != 0 && y.compareTo( n1 ) != 0 ) {
		var j = 1;
		while ( j++ < k && y.compareTo( n1 ) != 0 ) {
		    y = y.modPowInt( 2, this );
		    if ( y.compareTo( BigInteger.ONE ) == 0 )
			return false;
		}
		if ( y.compareTo( n1 ) != 0 )
		    return false;
	    }
	}
	return true;
    };

    ///////////////////////////////////////////////
    // export
    ///////////////////////////////////////////////

    // exporting inner class
    BigInteger.NullExp = NullExp;

    // __package( packages, path ).BigInteger.NullExp = NullExp;

    

    // BigInteger interfaces not implemented in jsbn:
    
    // BigInteger(int signum, byte[] magnitude)
    // double doubleValue()
    // float floatValue()
    // int hashCode()
    // long longValue()
    // static BigInteger valueOf(long val)

}

initBigInteger2( this );

// vim:ts=8 sw=4:noexpandtab:
