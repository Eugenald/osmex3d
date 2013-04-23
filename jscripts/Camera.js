var OSMEX = OSMEX || { REVISION: '1' };

OSMEX.Camera = function ( width, height, fov, near, far, orthoNear, orthoFar ) {

    THREE.CombinedCamera.call( this, width, height, fov, near, far, orthoNear, orthoFar );

    var fovTan = Math.tan((fov / 2) * Math.PI/180.0);
    this.unitToPixelScale = height / (2.0 * fovTan);
};

OSMEX.Camera.prototype = Object.create( THREE.CombinedCamera.prototype );

OSMEX.Camera.prototype.setSize = function( width, height ) {

    var fovTan = Math.tan((this.fov / 2) * Math.PI/180.0);
    this.unitToPixelScale = height / (2.0 * fovTan);

    THREE.CombinedCamera.prototype.setSize.call(this, width, height);
};

OSMEX.Camera.prototype.setFov = function( fov ) {

    var height = this.top * 2;
    var fovTan = Math.tan((fov / 2) * Math.PI/180.0);
    this.unitToPixelScale = height / 2.0 * fovTan;

    THREE.CombinedCamera.prototype.setFov.call(this, fov);
};
