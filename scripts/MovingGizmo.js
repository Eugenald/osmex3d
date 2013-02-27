var OSMEX = OSMEX || { REVISION: '1' };

MIN_OBJ_SCALE = 0.33;
//MAX_OBJ_SCALE = 20; /*not used for now*/

OSMEX.MovingGizmo = function ( ) {
    
    THREE.Object3D.call( this );
    
    this.target = null;
	
    this.AxisX = new OSMEX.MovingArrow( new THREE.Vector3( 1, 0, 0 ), 0xff0000 );  
	
    this.AxisY = new OSMEX.MovingArrow( new THREE.Vector3( 0, 1, 0 ), 0x00ff00 );
	
    this.AxisZ = new OSMEX.MovingArrow( new THREE.Vector3( 0, 0, 1 ), 0x0000ff );
    
	
    this.add(this.AxisX);
	
    this.add(this.AxisY);
	
    this.add(this.AxisZ);

    
    this.setTarget(null);
};

OSMEX.MovingGizmo.prototype = Object.create( THREE.Object3D.prototype );

OSMEX.MovingGizmo.prototype.setTarget = function ( target ) {
    
    this.target = target;
    
    if ( target ) {
        
        var POSITION_PREV=1;
        
        this.traverse( function( object ) { object.visible = true } );
        
        this.AxisX.sizeFunc = function(target) { return function(position) {
                
                    target.position.x = position.x;
                          
        } }(this.target);
   
        
        this.AxisY.sizeFunc = function(target) { return function(position) { 

                    target.position.y = position.y;
              
        } }(this.target);
                
        
        this.AxisZ.sizeFunc = function(target) { return function(position) {
                                                
                    target.position.z = position.z;                                                
              
        } }(this.target);
                
                
       
    }
    else {
        
        this.traverse( function( object ) { object.visible = false } );
        
        this.AxisX.sizeFunc = null;
        
        this.AxisY.sizeFunc = null;
        
        this.AxisZ.sizeFunc = null;
        
    }
}

OSMEX.MovingGizmo.prototype.update = function ( ) {
    
    if(this.target){  
        
        this.position.copy(this.target.position);
        this.rotation.y = this.target.rotation.y;
        
    }
}