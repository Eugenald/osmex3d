var OSMEX = OSMEX || { REVISION: '1' };

OSMEX.RotationTorus = function ( dir, hex ) {
    
    OSMEX.Torus.call( this, dir, new THREE.Vector3( 0, 0, 0 ), hex );

    this.torus.pickable = true;
    this.torus.pickRef = this;
    
    this.rotationFunc = null;
    this.sizeFunc = null;
};

OSMEX.RotationTorus.prototype = Object.create( OSMEX.Torus.prototype );

OSMEX.RotationTorus.prototype.setAngle = function ( BasisVector, CurrentVector ) {
    
    if (this.rotationFunc) this.rotationFunc(BasisVector, CurrentVector );
    
};

OSMEX.RotationTorus.prototype.setAngle = function ( BasisVector, CurrentVector ) {
    
    if (this.rotationFunc) this.rotationFunc(BasisVector, CurrentVector );
    
};