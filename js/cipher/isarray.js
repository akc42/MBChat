/*
 * isarray.js
 *
 * Copyright(c) 2009 Atsushi Oka [ http://oka.nu/ ]
 * This script file is distributed under the LGPL
 */
var packageRoot = this;
if ( packageRoot.__PACKAGE_ENABLED ) {
    __unit("isarray.js");
    __uses("packages.js");
}
Array.prototype.isArray=true;
