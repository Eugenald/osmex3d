var OSMEX = OSMEX || { REVISION: '1' };

OSMEX.SizerArrow = function ( dir, hex ) {
    
    OSMEX.Arrow.call( this, dir, new THREE.Vector3( 0, 0, 0 ), 40, hex, "sizing" );
    this.name = "SizeArrow";
    
    this.minLength = 5;
    this.maxLength = 120;
	
    this.cube.pickable = true;
    this.cube.pickRef = this;

    this.sizeFunc = null;
};

OSMEX.SizerArrow.prototype = Object.create( OSMEX.Arrow.prototype );

OSMEX.SizerArrow.prototype.trackSizing = function ( sizingVector ) {
    
    var newLen = this.dir.dot(sizingVector);
    
    console.log ("newLen=" + newLen);
    
    // TODO: workaround should be reimplemented
    if (Math.abs(this.len - newLen) < (this.maxLength - this.minLength)) {
        
        if (newLen < this.minLength) {
        
            newLen = this.minLength;
        }
        else if (newLen > this.maxLength) {
        
            newLen = this.maxLength;
        }
    
        if (this.sizeFunc) {
        
            var delta = newLen - this.len;
            this.sizeFunc(delta);
        }
        
        SIZING.setLength(newLen);                    
    }
}
