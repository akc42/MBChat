/*
 * BigInteger.init1.js
 * A class which is a representation of variable lengthed integer.
 * > Basic JavaScript BN library - subset useful for RSA-encryption.
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

function initBigInteger1( packages ) {
    __unit( "BigInteger.init1.js" );
    __uses( "packages.js" );

    ///////////////////////////////////////////////////////////////
    // import
    ///////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////////////
    // implementation
    ///////////////////////////////////////////////////////////////
    
    var installAM = function ( BigInteger ) {
	// // Bits per digit
	// $root.dbits= null;
	// $root.BI_FP= 52;

	////////////////////////////////////////////////////////////
	// am: Compute w_j += (x*this_i), propagate carries,
	// c is initial carry, returns final carry.
	// c < 3*dvalue, x < 2*dvalue, this_i < dvalue
	// We need to select the fastest one that works in this environment.
	////////////////////////////////////////////////////////////
	
	////////////////////////////////////////////////////////////
	// am1: use a single mult and divide to get the high bits,
	// max digit bits should be 26 because
	// max internal value = 2*dvalue^2-2*dvalue (< 2^53)
	////////////////////////////////////////////////////////////
	var am1 = function (i,x,w,j,c,n) {
	    while(--n >= 0) {
		var v = x*this[i++]+w[j]+c;
		c = Math.floor(v/0x4000000);
		w[j++] = v&0x3ffffff;
	    }
	    return c;
	};

	////////////////////////////////////////////////////////////
	// am2 avoids a big mult-and-extract completely.
	// Max digit bits should be <= 30 because we do bitwise ops
	// on values up to 2*hdvalue^2-hdvalue-1 (< 2^31)
	////////////////////////////////////////////////////////////
	var am2= function (i,x,w,j,c,n) {
	    var xl = x&0x7fff, xh = x>>15;
	    while(--n >= 0) {
		var l = this[i]&0x7fff;
		var h = this[i++]>>15;
		var m = xh*l+h*xl;
		l = xl*l+((m&0x7fff)<<15)+w[j]+(c&0x3fffffff);
		c = (l>>>30)+(m>>>15)+xh*h+(c>>>30);
		w[j++] = l&0x3fffffff;
	    }
	    return c;
	};

	////////////////////////////////////////////////////////////
	// Alternately, set max digit bits to 28 since some
	// browsers slow down when dealing with 32-bit numbers.
	////////////////////////////////////////////////////////////
	var am3= function (i,x,w,j,c,n) {
	    var xl = x&0x3fff, xh = x>>14;
	    while(--n >= 0) {
		var l = this[i]&0x3fff;
		var h = this[i++]>>14;
		var m = xh*l+h*xl;
		l = xl*l+((m&0x3fff)<<14)+w[j]+c;
		c = (l>>28)+(m>>14)+xh*h;
		w[j++] = l&0xfffffff;
	    }
	    return c;
	};

	////////////////////////////////////////////////////////////
	// JavaScript engine analysis
	////////////////////////////////////////////////////////////
	var canary= 0xdeadbeefcafe;
	var j_lm= ( ( canary&0xffffff ) == 0xefcafe );

	if ( true ) {
	    BigInteger.prototype.am = am2;
	    BigInteger.dbits = 30;
	    BigInteger.log( "AM_INIT MODIFICATION SUCCEEDED." );
	} else
	if( j_lm && (navigator.appName == "Microsoft Internet Explorer")) {
	    BigInteger.prototype.am = am2;
	    BigInteger.dbits = 30;
	}
	else if( j_lm && (navigator.appName != "Netscape")) {
	    BigInteger.prototype.am = am1;
	    BigInteger.dbits = 26;
	}
	else { // Mozilla/Netscape seems to prefer am3
	    BigInteger.prototype.am = am3;
	    BigInteger.dbits = 28;
	}

	BigInteger.BI_FP = 52;
	BigInteger.DB = BigInteger.dbits;
	BigInteger.DM = ( 1 << BigInteger.DB )-1;
	BigInteger.DV = ( 1 << BigInteger.DB );
	BigInteger.FV = Math.pow( 2, BigInteger.BI_FP );
	BigInteger.F1 = BigInteger.BI_FP - BigInteger.DB;
	BigInteger.F2 = 2 * BigInteger.DB - BigInteger.BI_FP;
    };
    

    ////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////

    // Modular reduction using "classic" algorithm
    var Classic = function (m) { 
	this.m = m;
	// this.convert = function cConvert(x) MODIFIED BY ATS 2008/11/22
	this.convert = function(x) {
	    if( x.s < 0 || x.compareTo(this.m) >= 0 )
		return x.mod(this.m);
	    else
		return x;
	};
	// this.revert = function (x) { return x; };
	// this.reduce = function (x) { x.divRemTo(this.m,null,x); };
	// this.mulTo = function (xy,r) { x.multiplyTo(y,r); this.reduce(r); };
	// this.sqrTo = function (x,r) { x.squareTo(r); this.reduce(r); };
    };
    Classic.prototype.revert = function (x) { return x; };
    Classic.prototype.reduce = function (x) { x.divRemTo(this.m,null,x); };
    Classic.prototype.mulTo = function (x,y,r) { x.multiplyTo(y,r); this.reduce(r); };
    Classic.prototype.sqrTo = function (x,r) { x.squareTo(r); this.reduce(r); };
    Classic.prototype.toString = function() { return "Classic()"; }



    // Montgomery reduction
    var Montgomery = function (m) {
	this.m = m;
	this.mp = m.invDigit();
	this.mpl = this.mp&0x7fff;
	this.mph = this.mp>>15;
	this.um = (1<<(BigInteger.DB-15))-1;
	this.mt2 = 2*m.t;
    };
	    
    // xR mod m
    Montgomery.prototype.convert = function (x) {
	var r = new BigInteger();
	x.abs().dlShiftTo(this.m.t,r);
	r.divRemTo(this.m,null,r);
	if(x.s < 0 && r.compareTo(BigInteger.ZERO) > 0) this.m.subTo(r,r);
	return r;
    };

    // x/R mod m
    Montgomery.prototype.revert = function (x) {
	var r = new BigInteger();
	x.copyTo(r);
	this.reduce(r);
	return r;
    };

    // x = x/R mod m (HAC 14.32)
    Montgomery.prototype.reduce = function (x) {
	while(x.t <= this.mt2)	// pad x so am has enough room later
	    x[x.t++] = 0;
	for(var i = 0; i < this.m.t; ++i) {
	    // faster way of calculating u0 = x[i]*mp mod DV
	    var j = x[i]&0x7fff;
	    var u0 = (j*this.mpl+(((j*this.mph+(x[i]>>15)*this.mpl)&this.um)<<15))&BigInteger.DM;
	    // use am to combine the multiply-shift-add into one call
	    j = i+this.m.t;
	    //trace( "(1)x["+j+"] = " + x[j] );
	    x[j] += this.m.am(0,u0,x,i,0,this.m.t);
	    //trace( "(2)x["+j+"] = " + x[j] );
	    // propagate carry
	    while( x[j] >= BigInteger.DV ) {
		//trace( "(3)x["+j+"] = " + x[j] );
		x[j] -= BigInteger.DV;
		//trace( "(4)x["+j+"] = " + x[j] );

		// FIXED 7 Dec, 2008 http://oka.nu/
		// This is a countermeasure for ActionScript's bug.  Any
		// shortcut operator within reference to an object's field
		// by [] operator which is also with any shortcut operator
		// causes a false operation.

		// x[++j]++;
		j++; x[j]++;

		//trace( "(5)x["+j+"] = " + x[j] );
	    }
	}
	x.clamp();
	x.drShiftTo(this.m.t,x);
	if(x.compareTo(this.m) >= 0) x.subTo(this.m,x);
    };

    // r = "x^2/R mod m"; x != r
    Montgomery.prototype.sqrTo = function (x,r) { x.squareTo(r); this.reduce(r); };

    // r = "xy/R mod m"; x,y != r
    Montgomery.prototype.mulTo = function (x,y,r) { x.multiplyTo(y,r); this.reduce(r); };

    Montgomery.prototype.toString = function() { return "Montgomery()"; }


    ////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////
    
    // (public) Constructor
    var BigInteger = function() {
	if ( arguments.length == 0 ) {
	    // do nothing.
	} else if ( arguments.length == 1 ) {
	    var p0 = arguments[0];
	    var t0 = typeof p0;
	    if ( "number" == t0 ) {
		// fromInt() (protected) set from integer value x, -DV <= x < DV
		if ( ( -1*BigInteger.DV<=p0 ) && ( p0<BigInteger.DV ) ) {
		    this.fromInt(p0);
		} else {
		    this.fromString( p0.toString(16), 16 );
		}
	    } else if ( "string" == t0 ) {
		this.fromString(p0,10);
	    } else {
		this.fromByteArray(p0);
	    }
	} else if ( arguments.length == 2 ) {
	    var p0 = arguments[0];
	    var t0 = typeof p0;
	    var p1 = arguments[1];
	    var t1 = typeof p1;
	    if ( "number" == t0 ) {
		this.fromNumber2(p0,p1);
	    } else if ( "string" == t0 ) {
		this.fromString(p0,p1);
	    } else {
		throw "parameter(1) must be either a number or a string. " + t0;
	    }
	} else if ( arguments.length == 3 ) {
	    var p0 = arguments[0];
	    var t0 = typeof p0;
	    var p1 = arguments[1];
	    var t1 = typeof p1;
	    var p2 = arguments[2];
	    var t2 = typeof p2;
	    if ( "number" == t0 ) {
		this.fromNumber1(p0,p1,p2);
	    } else {
		throw "parameter(1) must be a number. " + t0;
	    }
	}

	// old version.
	// var BigInteger = function (a,b,c) {
	// if ( a != null ) {
	// 	if ( "number" == typeof a )
	// 		this.fromNumber(a,b,c);
	// 	else if( b == null && "string" != typeof a )
	// 		this.fromString(a,256);
	// 	else
	// 		this.fromString(a,b);
	// }
	// }
    };
    BigInteger.prototype.className = "BigInteger";

    var BI_RC = new Array();
    var digit_conversions = function () {
	// Digit conversions
	var rr,vv;
	rr = "0".charCodeAt(0);
	for(vv = 0; vv <= 9; ++vv) BI_RC[rr++] = vv;
	rr = "a".charCodeAt(0);
	for(vv = 10; vv < 36; ++vv) BI_RC[rr++] = vv;
	rr = "A".charCodeAt(0);
	for(vv = 10; vv < 36; ++vv) BI_RC[rr++] = vv;
    };
    digit_conversions();

    BigInteger.intAt= function (s,i) {
	var c = BI_RC[ s.charCodeAt(i) ];
	return ( c == null ) ? -1 : c;
    };

    var BI_RM= "0123456789abcdefghijklmnopqrstuvwxyz";
    BigInteger.int2char= function (n) { return BI_RM.charAt(n); };
    
    
    // returns bit length of the integer x
    BigInteger.nbits= function (x) {
	var r = 1, t;
	if ( (t=x>>>16) != 0) { x = t; r += 16; } 
	if ( (t=x>>  8) != 0) { x = t; r +=  8; }
	if ( (t=x>>  4) != 0) { x = t; r +=  4; }
	if ( (t=x>>  2) != 0) { x = t; r +=  2; }
	if ( (t=x>>  1) != 0) { x = t; r +=  1; }
	return r;
    };

    // (protected) copy this to r
    BigInteger.prototype.copyTo = function (r) {
	for(var i = this.t-1; i >= 0; --i) r[i] = this[i];
	r.t = this.t;
	r.s = this.s;
    };
    
    // (protected) set from integer value x, -DV <= x < DV
    BigInteger.prototype.fromInt = function (x) {
	this.t = 1;
	this.s = (x<0)?-1:0;
	if(x > 0) this[0] = x;
	// else if ( x < -1) this[0] = x + DV; // this is probably a bug. modified. 10 DEC,2008
	else if ( x < -1 ) this[0] = x + BigInteger.DV;
	else this.t = 0;
    };
    
    // (protected) set from string and radix
    BigInteger.prototype.fromString = function ( s, b ) {
	// ElapsedTime.start( "fromString" );
	var k;
	
	     if ( b <= 0  ) throw "bitLength must be larger than 0";
	else if ( b == 2  ) k = 1;
	else if ( b == 4  ) k = 2;
	else if ( b == 8  ) k = 3;
	else if ( b == 16 ) k = 4;
	else if ( b == 32 ) k = 5;
	else if ( b == 256) k = 8; // byte array
	else { 
	    this.fromRadix(s,b);
	    return; 
	}

	this.t = 0;
	this.s = 0;

	var i = s.length;
	var mi = false;
	var sh = 0;

	while ( --i >= 0 ) {
	    var x = ( k==8 ) ? s[i] & 0xff : BigInteger.intAt( s, i );
	    if ( x < 0 ) {
		if ( s.charAt(i) == "-" ) mi = true;
		continue;
	    }
	    mi = false;
	    if(sh == 0) {
		this[ this.t++ ] = x;
	    } else if( sh + k > BigInteger.DB ) {
		this[ this.t-1 ] |= ( x & ( ( 1 << ( BigInteger.DB - sh ) ) - 1 ) ) << sh;
		this[ this.t   ]  = ( x >>         ( BigInteger.DB - sh ) );
		this.t++;
	    } else {
		this[ this.t-1 ] |=  x << sh;
	    }
	    sh += k;
	    if(sh >= BigInteger.DB) sh -= BigInteger.DB;
	}
	if ( k == 8 && ( s[0] & 0x80 ) != 0 ) {
	    this.s = -1;
	    if ( sh > 0 ) this[this.t-1] |= ((1<<(BigInteger.DB-sh))-1)<<sh;
	}
	this.clamp();
	if( mi ) {
	    BigInteger.ZERO.subTo( this, this );
	}
	// ElapsedTime.stop();
    };

    // (protected) set from a byte array.
    BigInteger.prototype.fromByteArray = function ( b ) {
	return this.fromString( b, 256 );
    }

    
    // (protected) clamp off excess high words
    BigInteger.prototype.clamp = function () {
	var c = this.s & BigInteger.DM;
	while ( this.t > 0 && this[this.t-1] == c ) --this.t;
    };
    
    // (public) return string representation in given radix
    BigInteger.prototype.toString = function (b) {
	if(this.s < 0) return "-"+this.negate().toString(b);
	var k;
	// if ( b== null ) b=this; // ADDED BY ATS 2008/12/07
	if(b == 16) k = 4;
	else if(b == 8) k = 3;
	else if(b == 2) k = 1;
	else if(b == 32) k = 5;
	else if(b == 4) k = 2;
	else return this.toRadix(b);
	var km = (1<<k)-1, d, m = false, r = "", i = this.t;
	var p = BigInteger.DB-(i*BigInteger.DB)%k;
	if(i-- > 0) {
	    if(p < BigInteger.DB && (d = this[i]>>p) > 0) { m = true; r = BigInteger.int2char(d); }
	    while(i >= 0) {
		if(p < k) {
		    d = (this[i]&((1<<p)-1))<<(k-p);
		    d |= this[--i]>>(p+=BigInteger.DB-k);
		}
		else {
		    d = (this[i]>>(p-=k))&km;
		    if(p <= 0) { p += BigInteger.DB; --i; }
		}
		if(d > 0) m = true;
		if(m) r += BigInteger.int2char(d);
	    }
	}
	return m?r:"0";
    };
    
    // (public) -this
    BigInteger.prototype.negate = function () { var r = new BigInteger(); BigInteger.ZERO.subTo(this,r); return r; };
    
    // (public) |this|
    BigInteger.prototype.abs = function () { return (this.s<0)?this.negate():this; };
    
    // (public) return + if this > a, - if this < a, 0 if equal
    BigInteger.prototype.compareTo = function (a) {
	var r = this.s-a.s;
	if(r != 0) return r;
	var i = this.t;
	r = i-a.t;
	if(r != 0) return r;
	while(--i >= 0) if((r=this[i]-a[i]) != 0) return r;
	return 0;
    };
    
    
    // (public) return the number of bits in "this"
    BigInteger.prototype.bitLength = function () {
	if ( this.t <= 0 ) return 0;
	return BigInteger.DB * (this.t-1) + BigInteger.nbits( this[ this.t-1 ] ^ ( this.s & BigInteger.DM ) );
    };
    
    // (protected) r = this << n*DB
    BigInteger.prototype.dlShiftTo = function (n,r) {
	var i;
	for(i = this.t-1; i >= 0; --i) r[i+n] = this[i];
	for(i = n-1; i >= 0; --i) r[i] = 0;
	r.t = this.t+n;
	r.s = this.s;
    };
    
    // (protected) r = this >> n*DB
    BigInteger.prototype.drShiftTo = function (n,r) {
	for(var i = n; i < this.t; ++i) r[i-n] = this[i];
	r.t = Math.max(this.t-n,0);
	r.s = this.s;
    };
    
    // (protected) r = this << n
    BigInteger.prototype.lShiftTo = function (n,r) {
	var bs = n % BigInteger.DB;
	var cbs = BigInteger.DB - bs;
	var bm = (1<<cbs)-1;
	var ds = Math.floor( n / BigInteger.DB ), c = (this.s<<bs)&BigInteger.DM, i;
	for(i = this.t-1; i >= 0; --i) {
	    r[i+ds+1] = (this[i]>>cbs)|c;
	    c = (this[i]&bm)<<bs;
	}
	for(i = ds-1; i >= 0; --i) r[i] = 0;
	r[ds] = c;
	r.t = this.t+ds+1;
	r.s = this.s;
	r.clamp();
    };
    
    // (protected) r = this >> n
    BigInteger.prototype.rShiftTo = function (n,r) {
	r.s = this.s;
	var ds = Math.floor( n / BigInteger.DB );
	if(ds >= this.t) { r.t = 0; return; }
	var bs = n % BigInteger.DB;
	var cbs = BigInteger.DB - bs;
	var bm = (1<<bs)-1;
	r[0] = this[ds]>>bs;
	for(var i = ds+1; i < this.t; ++i) {
	    r[i-ds-1] |= (this[i]&bm)<<cbs;
	    r[i-ds] = this[i]>>bs;
	}
	if(bs > 0) r[this.t-ds-1] |= (this.s&bm)<<cbs;
	r.t = this.t-ds;
	r.clamp();
    };
    
    // (protected) r = this - a
    BigInteger.prototype.subTo = function (a,r) {
	var i = 0, c = 0, m = Math.min( a.t, this.t );
	while(i < m) {
	    c += this[i]-a[i];
	    r[i++] = c&BigInteger.DM;
	    c >>= BigInteger.DB;
	}
	if(a.t < this.t) {
	    c -= a.s;
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
		c -= a[i];
		r[i++] = c&BigInteger.DM;
		c >>= BigInteger.DB;
	    }
	    c -= a.s;
	}
	r.s = (c<0)?-1:0;
	if(c < -1) r[i++] = BigInteger.DV+c;
	else if(c > 0) r[i++] = c;
	r.t = i;
	r.clamp();
    };
    
    // (protected) r = this * a, r != this,a (HAC 14.12)
    // "this" should be the larger one if appropriate.
    BigInteger.prototype.multiplyTo = function (a,r) {
	var x = this.abs(), y = a.abs();
	var i = x.t;
	r.t = i+y.t;
	while(--i >= 0) r[i] = 0;
	for(i = 0; i < y.t; ++i) r[i+x.t] = x.am(0,y[i],r,i,0,x.t);
	r.s = 0;
	r.clamp();
	if(this.s != a.s) BigInteger.ZERO.subTo(r,r);
    };
    
    // (protected) r = this^2, r != this (HAC 14.16)
    // BigInteger.prototype.squareTo = function (r) {
    // 	var x = this.abs();
    // 	var i = r.t = 2*x.t;
    // 	while(--i >= 0) r[i] = 0;
    // 	for(i = 0; i < x.t-1; ++i) {
    // 		var c = x.am(i,x[i],r,2*i,0,1);
    // 		if((r[i+x.t]+=x.am(i+1,2*x[i],r,2*i+1,c,x.t-i-1)) >= BigInteger.DV) {
    // 			r[i+x.t] -= BigInteger.DV;
    // 			r[i+x.t+1] = 1;
    // 		}
    // 	}
    // 	if(r.t > 0) r[r.t-1] += x.am(i,x[i],r,2*i,0,1);
    // 	r.s = 0;
    // 	r.clamp();
    // 	trace( "squareTo() 1: " + x.toString(16) );
    // 	trace( "squareTo() 2: " + r.toString(16) );
    // };
    BigInteger.prototype.squareTo = function (r) {
	var x = this.abs();
	var i = r.t = 2 * x.t;
	while ( --i >= 0 ) {
	    r[i] = 0;
	}
	for (i = 0; i < x.t-1; ++i ) {
	    var c = x.am( i, x[i], r, 2*i, 0, 1 );
	    //trace( "squareTo() 0.0: i=" + i + " r=" + r.toString(16) );
	    if ( ( r[i+x.t] += x.am( i+1, 2*x[i], r, 2*i+1, c, x.t-i-1 ) ) >= BigInteger.DV ) {
		r[ i+x.t   ] -= BigInteger.DV;
		r[ i+x.t+1 ]  = 1;
	    }
	    //trace( "squareTo() 0.1: i=" + i + " r=" + r.toString(16) );
	}
	if ( r.t > 0 ) {
	    r[r.t-1] += x.am( i, x[i], r, 2*i, 0, 1 );
	}
	r.s = 0;
	r.clamp();
	//trace( "squareTo() 1: " + x.toString(16) );
	//trace( "squareTo() 2: " + r.toString(16) );
    };
    
    // (protected) divide this by m, quotient and remainder to q, r (HAC 14.20)
    // r != q, this != m.  q or r may be null.
    BigInteger.prototype.divRemTo = function (m,q,r) {
	var pm = m.abs();
	if(pm.t <= 0) return;
	var pt = this.abs();
	if(pt.t < pm.t) {
	    if(q != null) q.fromInt(0);
	    if(r != null) this.copyTo(r);
	    return;
	}
	if(r == null) r = new BigInteger();
	var y = new BigInteger(), ts = this.s, ms = m.s;
	var nsh = BigInteger.DB-BigInteger.nbits(pm[pm.t-1]);	// normalize modulus
	if(nsh > 0) { pm.lShiftTo(nsh,y); pt.lShiftTo(nsh,r); }
	else { pm.copyTo(y); pt.copyTo(r); }
	var ys = y.t;
	var y0 = y[ys-1];
	if(y0 == 0) return;
	var yt = y0*(1<<BigInteger.F1)+((ys>1)?y[ys-2]>>BigInteger.F2:0);
	var d1 = BigInteger.FV/yt, d2 = (1<<BigInteger.F1)/yt, e = 1<<BigInteger.F2;
	var i = r.t, j = i-ys, t = (q==null)?new BigInteger():q;
	y.dlShiftTo(j,t);
	if(r.compareTo(t) >= 0) {
	    r[r.t++] = 1;
	    r.subTo(t,r);
	}
	BigInteger.ONE.dlShiftTo(ys,t);
	t.subTo(y,y);	// "negative" y so we can replace sub with am later
	while(y.t < ys) y[y.t++] = 0;
	while(--j >= 0) {
	    // Estimate quotient digit
	    var qd = (r[--i]==y0)?BigInteger.DM:Math.floor(r[i]*d1+(r[i-1]+e)*d2);
	    if((r[i]+=y.am(0,qd,r,j,0,ys)) < qd) {	// Try it out
		y.dlShiftTo(j,t);
		r.subTo(t,r);
		while(r[i] < --qd) r.subTo(t,r);
	    }
	}
	if(q != null) {
	    r.drShiftTo(ys,q);
	    if(ts != ms) BigInteger.ZERO.subTo(q,q);
	}
	r.t = ys;
	r.clamp();
	if(nsh > 0) r.rShiftTo(nsh,r);	// Denormalize remainder
	if(ts < 0) BigInteger.ZERO.subTo(r,r);
    };
    
    // (public) this mod a
    BigInteger.prototype.mod = function (a) {
	var r = new BigInteger();
	this.abs().divRemTo(a,null,r);
	if(this.s < 0 && r.compareTo(BigInteger.ZERO) > 0) a.subTo(r,r);
	return r;
    };
    
    // (protected) return "-1/this % 2^DB"; useful for Mont. reduction
    // justification:
    //         xy == 1 (mod m)
    //         xy =  1+km
    //   xy(2-xy) = (1+km)(1-km)
    // x[y(2-xy)] = 1-k^2m^2
    // x[y(2-xy)] == 1 (mod m^2)
    // if y is 1/x mod m, then y(2-xy) is 1/x mod m^2
    // should reduce x and y(2-xy) by m^2 at each step to keep size bounded.
    // JS multiply "overflows" differently from C/C++, so care is needed here.
    BigInteger.prototype.invDigit = function () {
	if(this.t < 1) return 0;
	var x = this[0];
	if((x&1) == 0) return 0;
	var y = x&3;		// y == 1/x mod 2^2
	y = (y*(2-(x&0xf)*y))&0xf;	// y == 1/x mod 2^4
	y = (y*(2-(x&0xff)*y))&0xff;	// y == 1/x mod 2^8
	y = (y*(2-(((x&0xffff)*y)&0xffff)))&0xffff;	// y == 1/x mod 2^16
	// last step - calculate inverse mod DV directly;
	// assumes 16 < DB <= 32 and assumes ability to handle 48-bit ints
	y = (y*(2-x*y%BigInteger.DV))%BigInteger.DV;		// y == 1/x mod 2^dbits
	// we really want the negative inverse, and -DV < y < DV
	return (y>0)?BigInteger.DV-y:-y;
    };
    
    // (protected) true iff this is even
    BigInteger.prototype.isEven = function () { return ((this.t>0)?(this[0]&1):this.s) == 0; };
    
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


    // 
    // static methods
    // 

    // // return new, unset BigInteger
    // BigInteger.create = function (i) { 
    // 	var bi = new BigInteger(); 
    // 	if ( typeof i == 'number' ) {
    // 		// return bigint initialized to value
    // 		bi.fromInt(i);
    // 	}
    // 	return bi;
    // };
    //
    // // (public) (static) convert a (hex) string to a bignum object
    // BigInteger.parseBigInt = function (str,r) {
    // 	return new BigInteger(str,r);
    // };
    

    BigInteger.log = function(message){
	// trace( message );
	// alert( message );
	return;
    };

    BigInteger.err = function(message) {
	trace( message );
	// alert( message );
	return;
    };

    
    // "constants"
    BigInteger.ZERO = new BigInteger(0);
    BigInteger.ONE = new BigInteger(1);


    // initialize
    installAM( BigInteger );

    ////////////////////////////////////////////// 
    // export
    ////////////////////////////////////////////// 

    // Inner Classes
    BigInteger.Classic = Classic;
    BigInteger.Montgomery = Montgomery;
    
    // main class
    __export( packages, "titaniumcore.crypto.BigInteger" , BigInteger );
}

initBigInteger1( this );

// vim:ts=8 sw=4:noexpandtab:
