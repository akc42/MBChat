/*
 * elapse.js
 * Simple performance profiling tool.
 * See elapse.readme.txt for further information.
 *
 * Copyright(c) 2009 Atsushi Oka [ http://oka.nu/ ]
 * This script file is distributed under the LGPL
 */
function init_elapse( packageRoot ) {
var createElapsedTime = function(_name) {
    if ( packageRoot.__PACKAGE_ENABLED ) {
	__unit( "elapse.js" );
    }

    if ( _name==null ) {
	_name = "ElapsedTime";
    }
    return {
	n : _name,
	s : 0,
	e : 0,
	now : function() {
	    return new Date().getTime();
	},
	start : function( n ) {
	    if ( n != null ) {
		this.n = n;
	    }
	    // this.n = n == null ? "ElapsedTime" : n;
	    this.s = this.now();
	},
	stop: function() {
	    this.e = this.now();
	    ElapsedTime.get_db( this.n ).push( this.get() );
	},
	print : function() {
	    trace( this.name() + " : " + ( this.get() / 1000 ) );
	},
	name : function() {
	    return this.n;
	},
	get : function() {
	    return this.e-this.s;
	}
    }
};

ElapsedTime = createElapsedTime();
ElapsedTime.db = {};
ElapsedTime.db.prototype=null;
ElapsedTime.get_db = function( name ) {
    var arr = this.db[ name ];
    if ( arr == null ) {
	arr =[];
	this.db[ name ] = arr;
    }
    return arr;
};
ElapsedTime.display = function( name ) {
    if ( name == null ) {
	var pure = []
	var arr =[];
	var i=0;
	for ( var n in this.db ) {
	    if ( pure[n] === undefined ) {
		arr[i++] = n;
	    }
	}
	arr.sort();
	for ( var i=0;i<arr.length; i++ ){
	    ElapsedTime.display( arr[i] );
	}
    } else {
	var arr = ElapsedTime.get_db( name );
	var accum = 0;
	for ( var i=0; i<arr.length; i++ ) {
	    accum += arr[i];
	}
	trace( "ElapsedTime(" + name + " ): " + accum+ "/"+ arr.length + " AVG=" + ( accum / arr.length / 1000) );
    }
};
ElapsedTime.create = createElapsedTime;
packageRoot.ElapsedTime = ElapsedTime;
}

init_elapse(this);

// vim:ts=8:
