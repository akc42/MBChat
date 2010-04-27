/*
 * packages.js
 * Simple framework for managing script's dependency.
 * See packages.readme.txt for further information.
 *
 * Copyright(c) 2009 Atsushi Oka [ http://oka.nu/ ]
 * This script file is distributed under the LGPL
 */
function initPackages( __scope ) {
    var __package = function( packageRoot,  pathString ) {
	var paths = pathString.split( "." );
	var currentPackage = packageRoot;
	for ( var i=0; i<paths.length; i++ ) {
	    var id = paths[i];
	    if ( currentPackage[ id ] == null ) {
		currentPackage[ id ] = {};
	    }
	    currentPackage= currentPackage[ id ];
	}
	return currentPackage;
    };

    var __export = function( packageRoot,  pathString , object ) {
	var paths = pathString.split( "." );
	var currentPackage = packageRoot;
	for ( var i=0; i<paths.length; i++ ) {
	    var id = paths[i];
	    if ( i < paths.length -1 ) {
		if ( currentPackage[ id ] == null ) {
		    currentPackage[ id ] = {};
		}
	    } else {
		if ( currentPackage[ id ] == null ) {
		    currentPackage[ id ] = object;
		} else {
		    throw "The specified package path is already defined. " + pathString;
		}
	    }
	    currentPackage= currentPackage[ id ];
	}
	return currentPackage;
    };

    var __import = function( packageRoot,  pathString , object ) {
	var paths = pathString.split( "." );
	var currentPackage = packageRoot;
	var currentPath = "[package root]";
	for ( var i=0; i<paths.length; i++ ) {
	    var id = paths[i];
	    currentPath += "."+id;
	    if ( currentPackage[ id ] == null ) {
		throw pathString + " is not found. " + currentPath + " is null in " +__CURRENT_UNIT.unit_name+".";
	    }
	    currentPackage= currentPackage[ id ];
	}
	return currentPackage;
    };

    var __DEFINED_UNITS={};
    var __CURRENT_UNIT = "";
    var __unit = function( unit_name ) {
	__DEFINED_UNITS[ unit_name ] = true;
	__CURRENT_UNIT = { 
	    unit_name : unit_name, 
	    requring_units : {} 
	};
    }
    var __uses = function( unit_name ) {
	if ( __DEFINED_UNITS[ unit_name ] ) {
	    __CURRENT_UNIT.requring_units[ unit_name ] = true;
	    return true;
	} else {
	    throw "Unit Not Found Error : " + __CURRENT_UNIT.unit_name + " requires " + unit_name ;
	}
    };


    Object.prototype.resolve = function( pathString ) {
	return __package( this, pathString );
    };

    __scope.__package = __package;
    __scope.__import = __import;
    __scope.__export = __export;
    __scope.__unit = __unit;
    __scope.__uses = __uses;
    __scope.__DEFINED_UNITS = __DEFINED_UNITS;
    __scope.__PACKAGE_ENABLED = true;

    __unit( "packages.js" );
}

initPackages( this );


// vim:ts=8:
