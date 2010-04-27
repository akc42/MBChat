/*
 * binary.js
 * Tools for creating, modifying binary data
 * including base64-encoding, base64-decoding , utf8-encoding and utf8-decoding
 * See binary.readme.txt for further information.
 *
 * Copyright(c) 2009 Atsushi Oka [ http://oka.nu/ ]
 * This script file is distributed under the LGPL
 */
function initBinary( packageRoot ) {
    if ( packageRoot.__PACKAGE_ENABLED ) {
	__unit( "binary.js" );
    }

var i2a  = [
    'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
    'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
    'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '+', '/'
];

function base64_encode( s ) {
    var length = s.length;
    var groupCount = Math.floor( length / 3 );
    var remaining = length - 3 * groupCount;
    var result = "";

    var idx = 0;
    for (var i=0; i<groupCount; i++) {
	var b0 = s[idx++] & 0xff;
	var b1 = s[idx++] & 0xff;
	var b2 = s[idx++] & 0xff;
	result += (i2a[ b0 >> 2]);
	result += (i2a[(b0 << 4) &0x3f | (b1 >> 4)]);
	result += (i2a[(b1 << 2) &0x3f | (b2 >> 6)]);
	result += (i2a[ b2 & 0x3f]);
    }

    if ( remaining == 0 ) {
    } else if ( remaining == 1 ) {
	var b0 = s[idx++] & 0xff;
	result += ( i2a[ b0 >> 2 ] );
	result += ( i2a[ (b0 << 4) & 0x3f] );
	result += ( "==" );
    } else if ( remaining == 2 ) {
	var b0 = s[idx++] & 0xff;
	var b1 = s[idx++] & 0xff;
	result += ( i2a[ b0 >> 2 ] );
	result += ( i2a[(b0 << 4) & 0x3f | (b1 >> 4)]);
	result += ( i2a[(b1 << 2) & 0x3f ] );
	result += ('=');
    } else {
	throw "never happen";
    }
    return result;
}

var a2i = [
    -1,   -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1, -1,
    -1,   -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1, -1,
    -1,   -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  -1,  62,  -1,  -1,  -1, 63,
    52,   53,  54,  55,  56,  57,  58,  59,  60,  61,  -1,  -1,  -1,  -1,  -1, -1,
    -1,    0,   1,   2,   3,   4,   5,   6,   7,   8,   9,  10,  11,  12,  13, 14,
    15,   16,  17,  18,  19,  20,  21,  22,  23,  24,  25,  -1,  -1,  -1,  -1, -1,
    -1,   26,  27,  28,  29,  30,  31,  32,  33,  34,  35,  36,  37,  38,  39, 40,
    41,   42,  43,  44,  45,  46,  47,  48,  49,  50,  51
];

function get_a2i( c ) {
    var result = (0<=c) && (c<a2i.length) ? a2i[ c ] : -1;
    if (result < 0) throw "Illegal character " + c;
    return result;
}

function base64_decode(s) {
    var length = s.length;
    var groupCount = Math.floor( length/4 );
    if ( 4 * groupCount != length )
	throw "String length must be a multiple of four.";

    var missing = 0;
    if (length != 0) {
	if ( s.charAt( length - 1 ) == '=' ) {
	    missing++;
	    groupCount--;
	}
	if ( s.charAt( length - 2 ) == '=' )
	    missing++;
    }

    var len = ( 3 * groupCount - missing );
    if ( len < 0 ) {
	len=0;
    }
    var result = new Array( len );
    // var result = new Array( 3 * groupCount - missing );
    // var result = new Array( 3 * ( groupCount +1 ) - missing );
    var idx_in = 0;
    var idx_out = 0;
    for ( var i=0; i<groupCount; i++ ) {
	var c0 = get_a2i( s.charCodeAt( idx_in++ ) );
	var c1 = get_a2i( s.charCodeAt( idx_in++ ) );
	var c2 = get_a2i( s.charCodeAt( idx_in++ ) );
	var c3 = get_a2i( s.charCodeAt( idx_in++ ) );
	result[ idx_out++ ] = 0xFF & ( (c0 << 2) | (c1 >> 4) );
	result[ idx_out++ ] = 0xFF & ( (c1 << 4) | (c2 >> 2) );
	result[ idx_out++ ] = 0xFF & ( (c2 << 6) | c3 );
    }

    if ( missing == 0 ) {
    } else if ( missing == 1 ) {
	var c0 = get_a2i( s.charCodeAt( idx_in++ ) );
	var c1 = get_a2i( s.charCodeAt( idx_in++ ) );
	var c2 = get_a2i( s.charCodeAt( idx_in++ ) );
	result[ idx_out++ ] = 0xFF & ( (c0 << 2) | (c1 >> 4) );
	result[ idx_out++ ] = 0xFF & ( (c1 << 4) | (c2 >> 2) );

    } else if ( missing == 2 ) {
	var c0 = get_a2i( s.charCodeAt( idx_in++ ) );
	var c1 = get_a2i( s.charCodeAt( idx_in++ ) );
	result[ idx_out++ ] = 0xFF & ( ( c0 << 2 ) | ( c1 >> 4 ) );
    } else {
	throw "never happen";
    }
    return result;
}

function base64x_encode( s ) {
    return base64x_pre_encode( base64_encode(s)  );
}
function base64x_decode( s ) {
    return base64_decode( base64x_pre_decode(s) );
}

var base64x_pre_encode_map = {};
base64x_pre_encode_map["x"] = "xx";
base64x_pre_encode_map["+"] = "xa";
base64x_pre_encode_map["/"] = "xb";
base64x_pre_encode_map["="] = "";


function base64x_pre_encode( s ) {
    var ss = "";
    for ( var i=0; i<s.length; i++ ) {
	var c = s.charAt(i);
	var cc = base64x_pre_encode_map[ c ]; 
	if ( cc != null ) {
	    ss = ss + cc;
	} else {
	    ss = ss + c;
	}
    }
    return ss;
}

var base64x_pre_decode_map = {};
base64x_pre_decode_map['x'] = 'x';
base64x_pre_decode_map['a'] = '+';
base64x_pre_decode_map['b'] = '/';

function base64x_pre_decode( s ) {
    var ss = "";
    for ( var i=0; i<s.length; i++ ) {
	var c = s.charAt(i);
	if ( c == 'x' ) {
	    c = s.charAt(++i);
	    var cc = base64x_pre_decode_map[ c ];
	    if ( cc != null ) {
		ss = ss + cc;
		// ss = ss + '/';
	    } else {
		// throw "invalid character was found. ("+cc+")"; // ignore.
	    }
	} else {
	    ss = ss + c;
	}
    }
    while ( ss.length % 4 != 0 ) {
	ss += "=";
    }
    return ss;
}

function equals( a, b ){
    if ( a.length != b.length )
	return false;
    var size=a.length;
    for ( var i=0;i<size;i++ ){
	// trace( a[i] + "/" + b[i] );
	if ( a[i] != b[i] )
	    return false;
    }
    return true;
}


function hex( i ){
    if ( i == null ) 
	return "??";
    //if ( i < 0 ) i+=256;
    i&=0xff;
    var result = i.toString(16);
    return ( result.length<2 ) ? "0" +result : result;
}

function base16( data, columns, delim ) {
    return base16_encode( data,columns,delim );
}
function base16_encode( data, columns, delim ) {
    if ( delim == null ){
	delim="";
    }
    if ( columns == null ) {
	columns = 256;
    }
    var result ="";
    for ( var i=0; i<data.length; i++ ) {
	if ( ( i % columns == 0 ) && ( 0<i ) )
	    result += "\n";
	result += hex( data[i] ) + delim;
    }
    return result.toUpperCase();
}

var amap = {};
 amap['0'] =   0; amap['1'] =   1; amap['2'] =   2; amap['3'] =   3;
 amap['4'] =   4; amap['5'] =   5; amap['6'] =   6; amap['7'] =   7;
 amap['8'] =   8; amap['9'] =   9; amap['A'] =  10; amap['B'] =  11;
 amap['C'] =  12; amap['D'] =  13; amap['E'] =  14; amap['F'] =  15;
                                   amap['a'] =  10; amap['b'] =  11; 
 amap['c'] =  12; amap['d'] =  13; amap['e'] =  14; amap['f'] =  15;

function get_amap( c ) {
    var cc = amap[c];
    //trace(c + "=>" + cc );
    if ( cc == null ) 
	throw "found an invalid character.";
    return cc;
}

function base16_decode( data ) {
    var ca = [];
    for ( var i=0,j=0; i<data.length; i++ ) {
	var c = data.charAt( i );
	if ( c == "\s" ) {
	    continue;
	} else {
	    ca[j++] = c;
	}
    }
    if ( ca.length % 2 != 0 ) {
	throw "data must be a multiple of two.";
    }

    var result = new Array( ca.length >> 1 );
    for ( var i=0; i<ca.length; i+=2 ) {
	var v = 0xff & ( ( get_amap( ca[i] ) <<4 ) | ( get_amap( ca[i+1] ) ) )  ;
	result[i>>1] = v;
	// trace(  get_amap( ca[i+1] ) )
	// result[i>>1] =  get_amap( ca[i+1] );
    }
    return result;
}
// trace( base16_encode([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,128,255 ] ) );
// trace( base16_encode( base16_decode("000102030405060708090A0B0C0D0E0F1080FF") ) );
// trace( base16_encode( base16_decode( "000102030405060708090A0B0C0D0E0F102030405060708090A0B0C0D0E0F0FF" ) ) );
//                                       000102030405060708090A0B0C0D0E0F102030405060708090A0B0C0D0E0F0FF


/////////////////////////////////////////////////////////////////////////////////////////////

var B10000000 = 0x80;
var B11000000 = 0xC0;
var B11100000 = 0xE0;
var B11110000 = 0xF0;
var B11111000 = 0xF8;
var B11111100 = 0xFC;
var B11111110 = 0xFE;
var B01111111 = 0x7F;
var B00111111 = 0x3F;
var B00011111 = 0x1F;
var B00001111 = 0x0F;
var B00000111 = 0x07;
var B00000011 = 0x03;
var B00000001 = 0x01;

function str2utf8( str ){
    var result = [];
    var length = str.length;
    var idx=0;
    for ( var i=0; i<length; i++ ){
	var c = str.charCodeAt( i );
	if ( c <= 0x7f ) {
	    result[idx++] = c;
	} else if ( c <= 0x7ff ) {
	    result[idx++] = B11000000 | ( B00011111 & ( c >>>  6 ) );
	    result[idx++] = B10000000 | ( B00111111 & ( c >>>  0 ) );
	} else if ( c <= 0xffff ) {
	    result[idx++] = B11100000 | ( B00001111 & ( c >>> 12 ) ) ;
	    result[idx++] = B10000000 | ( B00111111 & ( c >>>  6 ) ) ;
	    result[idx++] = B10000000 | ( B00111111 & ( c >>>  0 ) ) ;
	} else if ( c <= 0x10ffff ) {
	    result[idx++] = B11110000 | ( B00000111 & ( c >>> 18 ) ) ;
	    result[idx++] = B10000000 | ( B00111111 & ( c >>> 12 ) ) ;
	    result[idx++] = B10000000 | ( B00111111 & ( c >>>  6 ) ) ;
	    result[idx++] = B10000000 | ( B00111111 & ( c >>>  0 ) ) ;
	} else {
	    throw "error";
	}
    }
    return result;
}

function utf82str( data ) {
    var result = "";
    var length = data.length;

    for ( var i=0; i<length; ){
	var c = data[i++];
	if ( c < 0x80 ) {
	    result += String.fromCharCode( c );
	} else if ( ( c < B11100000 ) ) {
	    result += String.fromCharCode(
		( ( B00011111 & c         ) <<  6 ) |
		( ( B00111111 & data[i++] ) <<  0 )
	    );
	} else if ( ( c < B11110000 ) ) {
	    result += String.fromCharCode(
		( ( B00001111 & c         ) << 12 ) |
		( ( B00111111 & data[i++] ) <<  6 ) |
		( ( B00111111 & data[i++] ) <<  0 )
	    );
	} else if ( ( c < B11111000 ) ) {
	    result += String.fromCharCode(
		( ( B00000111 & c         ) << 18 ) |
		( ( B00111111 & data[i++] ) << 12 ) |
		( ( B00111111 & data[i++] ) <<  6 ) |
		( ( B00111111 & data[i++] ) <<  0 )
	    );
	} else if ( ( c < B11111100 ) ) {
	    result += String.fromCharCode(
		( ( B00000011 & c         ) << 24 ) |
		( ( B00111111 & data[i++] ) << 18 ) |
		( ( B00111111 & data[i++] ) << 12 ) |
		( ( B00111111 & data[i++] ) <<  6 ) |
		( ( B00111111 & data[i++] ) <<  0 )
	    );
	} else if ( ( c < B11111110 ) ) {
	    result += String.fromCharCode(
		( ( B00000001 & c         ) << 30 ) |
		( ( B00111111 & data[i++] ) << 24 ) |
		( ( B00111111 & data[i++] ) << 18 ) |
		( ( B00111111 & data[i++] ) << 12 ) |
		( ( B00111111 & data[i++] ) <<  6 ) |
		( ( B00111111 & data[i++] ) <<  0 )
	    );
	}
    }
    return result;
}

/////////////////////////////////////////////////////////////////////////////////////////////

// convert unicode character array to string
function char2str( ca ) {
    var result = "";
    for ( var i=0; i<ca.length; i++ ) {
	result += String.fromCharCode( ca[i] );
    }
    return result;
}

// convert string to unicode character array
function str2char( str ) {
    var result = new Array( str.length );
    for ( var i=0; i<str.length; i++ ) {
	result[i] = str.charCodeAt( i );
    }
    return result;
}

/////////////////////////////////////////////////////////////////////////////////////////////

// byte expressions (big endian)
function i2ba_be(i) {
    return [
	0xff & (i>>24),
	0xff & (i>>16),
	0xff & (i>> 8),
	0xff & (i>> 0)
    ];
}
function ba2i_be(bs) {
    return (
	  ( bs[0]<<24 )
	| ( bs[1]<<16 )
	| ( bs[2]<< 8 )
	| ( bs[3]<< 0 )
    );
}
function s2ba_be(i) {
    return [
	0xff & (i>> 8),
	0xff & (i>> 0)
    ];
}
function ba2s_be(bs) {
    return (
	0
	| ( bs[0]<< 8 )
	| ( bs[1]<< 0 )
    );
}

// byte expressions (little endian)
function i2ba_le(i) {
    return [
	0xff & (i>> 0),
	0xff & (i>> 8),
	0xff & (i>>16),
	0xff & (i>>24)
    ];
}
function ba2i_le(bs) {
    return (
	0
	| ( bs[3]<< 0 )
	| ( bs[2]<< 8 )
	| ( bs[1]<<16 )
	| ( bs[0]<<24 )
    );
}
function s2ba_le(i) {
    return [
	0xff & (i>> 0),
	0xff & (i>> 8)
    ];
}
function ba2s_le(bs) {
    return (
	0
	| ( bs[1]<< 0 )
	| ( bs[0]<< 8 )
    );
}

function ia2ba_be( ia ) {
    var length = ia.length <<2;
    var ba = new Array( length );
    for(var ii=0,bi=0;ii<ia.length&&bi<ba.length; ){
        ba[bi++] = 0xff & ( ia[ii] >> 24 );
        ba[bi++] = 0xff & ( ia[ii] >> 16 );
        ba[bi++] = 0xff & ( ia[ii] >>  8 );
        ba[bi++] = 0xff & ( ia[ii] >>  0 );
        ii++;
    }
    return ba;
}
function ba2ia_be( ba ) {
    var length = (ba.length+3)>>2;
    var ia = new Array( length );;
    for(var ii=0,bi=0; ii<ia.length && bi<ba.length; ){
        ia[ii++] = 
            ( bi < ba.length ? (ba[bi++]  << 24 ) : 0 ) |
            ( bi < ba.length ? (ba[bi++]  << 16 ) : 0 ) |
            ( bi < ba.length ? (ba[bi++]  <<  8 ) : 0 ) |
            ( bi < ba.length ? (ba[bi++]/*<< 0*/) : 0 ) ;
    }
    return ia;
}

function ia2ba_le( ia ) {
    var length = ia.length <<2;
    var ba = new Array( length );
    for(var ii=0,bi=0;ii<ia.length&&bi<ba.length; ){
        ba[bi++] = 0xff & ( ia[ii] >>  0 );
        ba[bi++] = 0xff & ( ia[ii] >>  8 );
        ba[bi++] = 0xff & ( ia[ii] >> 16 );
        ba[bi++] = 0xff & ( ia[ii] >> 24 );
        ii++;
    }
    return ba;
}
function ba2ia_le( ba ) {
    var length = (ba.length+3)>>2;
    var ia = new Array( length );;
    for(var ii=0,bi=0; ii<ia.length && bi<ba.length; ){
        ia[ii++] = 
            ( bi < ba.length ? (ba[bi++]/*<< 0*/) : 0 ) |
            ( bi < ba.length ? (ba[bi++]  <<  8 ) : 0 ) |
            ( bi < ba.length ? (ba[bi++]  << 16 ) : 0 ) |
            ( bi < ba.length ? (ba[bi++]  << 24 ) : 0 ) ;
    }
    return ia;
}

/////////////////////////////////////////////////////////////////////////////////////////////

function trim( s ){
    var result = "";
    for ( var idx=0; idx<s.length; idx++ ){
	var c = s.charAt( idx );
	if ( c == "\s" || c == "\t" || c == "\r" || c == "\n" ) {
	} else {
	    result += c;
	}
    }
    return result;
}

/////////////////////////////////////////////////////////////////////////////////////////////

function mktst( encode, decode ) {
    return function ( trial,from,to ) {
	var flg=true;
	for (var i=0; i<trial; i++) {
	    for (var j=from; j<to; j++) {
		var arr = new Array(j);
		for (var k=0; k<j; k++)
		    arr[k] = Math.floor( Math.random() * 256 );

		var s = encode(arr);
		var b = decode(s);

		// trace( "in:"+arr.length);
		// trace( "base64:"+s.length);
		// trace( "out:"+b.length);
		// trace( "in:"+arr);
		// trace( "base64:"+s );
		// trace( "out:"+b );
		trace( "in :"+arr.length + ":"+ base16_encode(arr) );
		trace( "b64:"+s.length+":"+s);
		trace( "out:"+b.length + ":"+ base16_encode(arr) );
		if ( equals( arr, b ) ) {
		    trace( "OK! ( " + i + "," + j + ")" );
		} else {
		    trace( "ERR ( " + i + "," + j + ")" );
		    flg=false;
		}
		trace( "-----------");
	    }
	}
	if ( flg ) {
	    trace( "ALL OK! " );
	} else {
	    trace( "FOUND ERROR!" );
	}
    };
}

// export

// base64
packageRoot.base64_encode = base64_encode;
packageRoot.base64_decode = base64_decode;
packageRoot.base64_test   = mktst( base64_encode, base64_decode );

// base64ex
packageRoot.base64x_encode = base64x_encode;
packageRoot.base64x_decode = base64x_decode;
packageRoot.base64x_test   = mktst( base64x_encode, base64x_decode );

packageRoot.base64x_pre_encode = base64x_pre_encode;
packageRoot.base64x_pre_decode = base64x_pre_decode;

// base16
packageRoot.base16_encode = base16_encode;
packageRoot.base16_decode = base16_decode;
packageRoot.base16        = base16;
packageRoot.hex           = base16;

// utf8
packageRoot.utf82str      = utf82str;
packageRoot.str2utf8      = str2utf8;
packageRoot.str2char      = str2char;
packageRoot.char2str      = char2str;

// byte expressions
packageRoot.i2ba    = i2ba_be;
packageRoot.ba2i    = ba2i_be;
packageRoot.i2ba_be = i2ba_be;
packageRoot.ba2i_be = ba2i_be;
packageRoot.i2ba_le = i2ba_le;
packageRoot.ba2i_le = ba2i_le;

packageRoot.s2ba    = s2ba_be;
packageRoot.ba2s    = ba2s_be;
packageRoot.s2ba_be = s2ba_be;
packageRoot.ba2s_be = ba2s_be;
packageRoot.s2ba_le = s2ba_le;
packageRoot.ba2s_le = ba2s_le;

packageRoot.ba2ia    = ba2ia_be;
packageRoot.ia2ba    = ia2ba_be;
packageRoot.ia2ba_be = ia2ba_be;
packageRoot.ba2ia_be = ba2ia_be;
packageRoot.ia2ba_le = ia2ba_le;
packageRoot.ba2ia_le = ba2ia_le;


// arrays
packageRoot.cmparr        = equals;
}

initBinary(this);


