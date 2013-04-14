<?php
header('Content-type: text/html; utf-8');

require_once("server_scripts/config.php");
?> 
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>three.js webgl - trackball camera</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
		<style>
			body {
				color: #000;
				font-family:Monospace;
				font-size:13px;
				text-align:center;
				font-weight: bold;

				background-color: #fff;
				margin: 0px;
				overflow: hidden;
			}
		</style>
	</head>

	<body>
        <div  jstcache="0"  id="cont" ></div>
        <div  jstcache="0"  id="build"></div>
		<div  jstcache="0"  id="container"></div>
		
		<script src="jscripts/three.js"></script>

        <script src="jscripts/CameraController.js"></script>

		<script src="jscripts/Detector.js"></script>
		<script src="jscripts/stats.min.js"></script>
		
		<script type="text/javascript" src="server_scripts/XMLHttpRequest.js"></script>
        <script type="text/javascript" src="server_scripts/Functions.js"></script> 

		<script>
//Class of tile		
function Tile () {
    this.id;
	this.refcount=-1;
	this.tex_x;
	this.tex_z;
	this.lvl;//level
    this.childs = new Array();//4 id of descendants 
    this.childs[0]=-1;
    this.childs[1]=-1;
    this.childs[2]=-1;
	this.childs[3]=-1;
    this.prnt;//parent
	this.texture;
	this.triangleGeometry = new THREE.Geometry();
	this.destroy = function () {
         delete this.id;
		 delete this.refcount;
		 delete this.tex;
		 delete this.lvl;
		 this.childs.length = 0;delete this.childs;this.childs=null;
		 delete this.prnt;
		 //this.triangleGeometry.dispose();
		 //this.texture.dispose();
		 delete this.triangleGeometry;this.triangleGeometry=null;
    };
	
}
//Class Building
function TileBlds () {
    this.id;//id of tile
	this.x;
	this.z;
	this.scale_x;
	this.scale_z;
	this.minlon;
	this.minlat;
    this.arrIndxsBlds = new Array();
	this.destroy = function () {
         delete this.id;
		 delete this.scale_x;
		 delete this.scale_z;
		 delete this.minlon;
		 delete this.minlat;
		 delete this.x;
		 delete this.z;
		delete this.arrIndxsBlds
		this.arrIndxsBlds=null;
    };
	
}


			if ( ! Detector.webgl ) Detector.addGetWebGLMessage();

			var container, stats;
			
			var arrCurRoot = new Array();
			var arrTile = new Array();
			var arrTileBlds = new Array();

			var timerid=0;
			var Exist1stTl=false;
			var UnitToPixelScale;
			var tileSizeRoot=3454245.2736;// in [m]
			var lvlbldactive=-1;
			
			var camera, controls, scene, renderer;
			var maxAnisotropy;
			
			var texture;

			var cross;
			
			var triangleMesh = new Array();
			var MeshOfBlds = new Array();
			var arrTex = new Array();

            var div = document.getElementById('cont');
			//div.style.display="none";
			div.ongetdata =responseServer;		
			
			var div_bld = document.getElementById('build');
			div_bld.ongetdata =responseServerCubes;
			div_bld.style.display="none";
			
			init();
			animate();
			
//Object ( dynamically add the necessary tiles)

var TLoad = new function () {
    this.maxid=9999999999999;
	//set 1st coordinates for 1st tileRoots
	this.startX=-1727122.6368;
	this.startZ=-1727122.6368;
	this.stepGrid=-1;
	this.idforloadroot=-1;
	this.ReadyForRoot=true;
    this.indx=0;
	this.indxCube=0;
    this.ready=true;                 //a flag of readiness
    this.arTileForAdd = new Array(); //the queue of  tiles for loading
	this.arTileCubeForAdd = new Array(); //the queue of  tiles for loading
	
this.prepareRootID = function (rootid) {
      if(this.ReadyForRoot){
	    this.idforloadroot=rootid;
		this.ReadyForRoot=false;
		this.arTileForAdd.push(rootid);
	       }
    };	

	   // check the queue overflow
this.isFull = function () {
      if(this.arTileForAdd.length>=256)return true;//queue consist of 256 
	  else{return false;}
    };

	  //check tile on present in the queue	  
this.tileinQueue= function (IdTile) {           
      if(this.arTileForAdd.indexOf(IdTile)>=0)return true;
	  else{return false;}
    };
	
this.tileCubeinQueue= function (strXspaceZ) {           
      if(this.arTileCubeForAdd.indexOf(strXspaceZ)>=0)return true;
	  else{return false;}
    };

	  //add tile in queue
this.pushTile = function (IdTile) {              
    if(!this.tileinQueue(IdTile)&&IdTile<=this.maxid/*&&!this.isFull()*/)this.arTileForAdd.push(IdTile);
};

this.pushTileCube = function (strXspaceZ) {                 
    if(true/*!this.tileCubeinQueue(strXspaceZ)*/){this.arTileCubeForAdd.push(strXspaceZ);}
};	

      //load and check flag of readiness
this.loadTile = function () {
   if(this.ready==true&&(this.indx<this.arTileForAdd.length||this.indxCube<this.arTileCubeForAdd.length)){
      if(this.indx<this.arTileForAdd.length){
         var id=this.arTileForAdd[this.indx/*this.arTileForAdd.length-1*/];
         if(id>=0){this.indx++;this.ready=false;land_func(id);}
	  }
	else{
		if(this.indxCube<this.arTileCubeForAdd.length){
		    var id=this.arTileCubeForAdd[this.indxCube];
		    if(id.length>0){
			   this.indxCube++;this.ready=false;
			   var lanlot=id.split(' ');//console.debug(parseInt(xz[0])+" "+parseInt(xz[1]));
			   build_func(parseFloat(lanlot[0])/*tile_id*/,parseFloat(lanlot[1]),parseFloat(lanlot[2]),parseFloat(lanlot[3]),parseFloat(lanlot[4]));
			   //var xz=id.split(' ');//console.debug(parseInt(xz[0])+" "+parseInt(xz[1]));
			   //build_func(parseInt(xz[0]),parseInt(xz[1]));
			   }
			}
	}
	                               }
};
	
	//set flag of readiness
this.loaded = function () { 
    this.ready=true;
    //return this.arTileForAdd.pop();  //delete the tile from the queue
};
		
}


function getTanDeg(deg) {

               var rad = deg * Math.PI/180;

               return Math.tan(rad)

            }

			function init() {
			    land_func(0);// load 1st tileroots
				camera = new THREE.PerspectiveCamera( 45, window.innerWidth / window.innerHeight, 0.01, 10000000 );
				camera.position.set(0, 3454245.2736, 0.0);
				UnitToPixelScale = window.innerHeight /( 2.0 * getTanDeg(camera.fov / 2.0));

                controls = new THREE.CameraController( camera );
                controls.userZoomSpeed = 0.3;

				//controls.rotateSpeed = 0.01;

				controls.addEventListener( 'change', checkTiles );
                //timerid=setInterval(render, 24);
				
				//scene
                scene = new THREE.Scene();
				//scene.fog = new THREE.FogExp2( 0xcccccc, 0.002 );

				
				// lights

				/*light = new THREE.DirectionalLight( 0xffffff );
				light.position.set( 1, 1, 1 );
				scene.add( light );

				light = new THREE.DirectionalLight( 0x002288 );
				light.position.set( -1, -1, -1 );
				scene.add( light );

				light = new THREE.AmbientLight( 0x222222 );
				scene.add( light );	*/
				
				// renderer

				renderer = new THREE.WebGLRenderer( { antialias: false ,preserveDrawingBuffer: true } );
				//renderer.setClearColor( scene.fog.color, 1 );
				//renderer.setDepthTest(true);
				//renderer.autoClear = true;
				renderer.setClearColor( new THREE.Color(0x1a3516), 1 );
				renderer.setSize( window.innerWidth, window.innerHeight );

				container = document.getElementById( 'container' );
				container.appendChild( renderer.domElement );
				
				maxAnisotropy = renderer.getMaxAnisotropy();

				stats = new Stats();
				stats.domElement.style.position = 'absolute';
				stats.domElement.style.top = '0px';
				stats.domElement.style.zIndex = 100;
				//container.appendChild( stats.domElement );

				//

				window.addEventListener( 'resize', onWindowResize, false );
               //wrt("clear")
			  /* for(var i = 0; i < tiles[0].triangleGeometry.vertices.length; i++) {
                         wrt(""+i+" "+tiles[0].triangleGeometry.vertices[i].x+" "+tiles[0].triangleGeometry.vertices[i].y+" "+tiles[0].triangleGeometry.vertices[i].z)
	                  }
					  wrt("center "+tiles[0].center.x+" "+tiles[0].center.y+" "+tiles[0].center.z)*/
			   /*console.debug("tiles[0].center.x "+tiles[0].center.x)
			   console.debug("tiles[1].center.x "+tiles[1].center.x)
			   console.debug("tiles[2].center.x "+tiles[2].center.x)
			   console.debug("tiles[3].center.x "+tiles[3].center.x)
			   console.debug("tiles[4].center.x "+tiles[4].center.x)*/
			   
			   
			   
			   /*onkeypress = function (event) {
	               if ((event = event || window.event).keyCode == 37)camera.center.x-=0.25
		           if ((event = event || window.event).keyCode == 39)camera.center.x+=0.25
				   if ((event = event || window.event).keyCode == 38)camera.center.z-=0.25
				   if ((event = event || window.event).keyCode == 40)camera.center.z+=0.25
	           }*/
			   
			   //timerid=setInterval(verify, 20);
			   
			   //land_func(300)
			   
			   document.addEventListener('keydown',onDocumentKeyDown,false);

			}
			
			function onDocumentKeyDown(event){
			   var k = 20000.0/3454245.2736;
			   var delta = k * camera.position.y;
			   var dx=0,dz=0;
			   event = event || window.event;
			   var keycode = event.keyCode;
			   switch(keycode){
			   case 37 : //left
			   dx=(-1)*delta*Math.cos( controls.theta );
			   dz=delta*Math.sin( controls.theta );
			   break;
			   case 38 : // up 
			   dx=(-1)*delta*Math.sin( controls.theta );
			   dz=(-1)*delta*Math.cos( controls.theta );
			   break;
			   case 39 : // right
			   dx=delta*Math.cos( controls.theta );
			   dz=(-1)*delta*Math.sin( controls.theta );
			   break;
			   case 40 : //down
			   dx=delta*Math.sin( controls.theta );
			   dz=delta*Math.cos( controls.theta );
			   break;
			   }
			   camera.position.x +=dx;
			   controls.center.x +=dx;
			   camera.position.z +=dz;
			   controls.center.z +=dz;
               checkTiles();
			   }
			   
			   
			
			function initFaceTex(tile) {
					//Faces
                	for(ix=0;ix<8;ix++){//collumn
                	   for(iy=0;iy<8;iy++){//row of quads
                	       tile.triangleGeometry.faces.push(new THREE.Face3(9*ix+iy,9*ix+iy+1,9*ix+iy+9));
                	       tile.triangleGeometry.faces.push(new THREE.Face3(9*ix+iy+1,9*ix+iy+10,9*ix+iy+9));
	                	                }
                					}
	
                    //UV
                	step=1.0/8.0
                	for(v=1.0;v>0;v-=step){
                	  for(u=0.0;u<1;u+=step){
                	tile.triangleGeometry.faceVertexUvs[0].push( [
                            new THREE.UV( u, v ),
                            new THREE.UV( u+step, v ),
                			new THREE.UV( u, v-step )
                        ] );
                	tile.triangleGeometry.faceVertexUvs[0].push( [
                            new THREE.UV( u+step, v ),
                            new THREE.UV( u+step, v-step ),
                            new THREE.UV( u, v-step )
                        ] );
                		                      }
	                	                   }			
			
			}

			
			//function is called in response to a request from the server to get the tile by id
			function responseServer(s) {
			
				tileId=-1;
				var flagroot=false;
				var findtile=false;
				var jstr;
				var flg_empty=false;
				jstr=JSON.parse(''+s);
				if(jstr.id<0){
				var lvl=-1;
				var id =Math.abs(jstr.id);
				for(t=0;/*t<=TLoad.maxid*/;t=(t*4+4)){lvl++;if(id<=t)break}
				if(lvl<=18)jstr.id=id;
				console.debug("lvl "+lvl+" jstr.id "+jstr.id)
				}
				if(jstr.verts[0]==undefined)flg_empty=true;
				
                if(jstr.id>=0){				
				 for(j in arrCurRoot){
				     cur_ID=arrCurRoot[j];
		
                       if(cur_ID*4+1==jstr.id){tileId=cur_ID*4+1;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z;arrTile[cur_ID].childs[0]=jstr.id;arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}
					   if(cur_ID*4+2==jstr.id){tileId=cur_ID*4+2;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x+1;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z;arrTile[cur_ID].childs[1]=jstr.id;arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}
					   if(cur_ID*4+3==jstr.id){tileId=cur_ID*4+3;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z+1;arrTile[cur_ID].childs[2]=jstr.id;arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}
					   if(cur_ID*4+4==jstr.id){tileId=cur_ID*4+4;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x+1;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z+1;arrTile[cur_ID].childs[3]=jstr.id;arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}

                     cur_ID=null					 
					}

				if(!findtile&&triangleMesh[(jstr.id*4+1)]&&triangleMesh[(jstr.id*4+2)]&&triangleMesh[(jstr.id*4+3)]&&triangleMesh[(jstr.id*4+4)]/*&&arrTile[jstr.id]*/){
				/*console.debug("create tile  "+jstr.id+" "+arrTile[jstr.id]);*/
				tileId=jstr.id;
				arrTile[tileId]=new Tile();
				arrTile[tileId].id=jstr.id;
				var lvl=-1;
				for(t=0;t<=TLoad.maxid;t=(t*4+4)){lvl++;if(jstr.id<=t)break}
				arrTile[tileId].lvl=lvl
				arrTile[tileId].tex_x=arrTile[(tileId*4+1)].tex_x/2;
				arrTile[tileId].tex_z=arrTile[(tileId*4+1)].tex_z/2;
				flagroot=true;}
				
				if(!Exist1stTl){
				
				if(jstr.id==0){
				tileId=jstr.id;
				arrTile[tileId]=new Tile();
				arrTile[tileId].id=jstr.id;
				arrTile[tileId].lvl=0;
				//arrTile[tileId].prnt=-1;
				arrTile[tileId].tex_x=0;
				arrTile[tileId].tex_z=0;
					
				console.debug("Init done crt Root Tile  "+jstr.id+" ");
				//determine a width and a height of cell
				TLoad.stepGrid=(Math.abs(TLoad.startX)*2)/8;

                            }
				
				}
								
                if(tileId>=0){
							
				var tex=''+arrTile[tileId].lvl+'/'+arrTile[tileId].tex_x+'/'+arrTile[tileId].tex_z;
				arrTex[tileId]=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tex+".png",new THREE.UVMapping(),function(){/*triangleMesh[tileId].visible=true;*/renderer.render( scene, camera );})

				arrTile[tileId].prnt=jstr.id==0?-1:((jstr.id-1)-((jstr.id-1)%4))/4;

				var var1=Math.pow(2,arrTile[tileId].lvl);//number of tiles in row (specific lvl) 
				scale=jstr.id==0?TLoad.stepGrid:TLoad.stepGrid/(var1);//determine a width and a height of cell
				//console.debug("scale "+scale+" tile.id "+arrTile[tileId].id+" tile.lvl "+arrTile[tileId].lvl)
				var offset=jstr.id==0?0:Math.abs(2*TLoad.startX)/(var1);  // determine an offset for 1st tile of specific lvl 
				//count 1st coordinates for concrete tile
				var startX=TLoad.startX+offset*arrTile[tileId].tex_x;
				var startZ=TLoad.startZ+offset*arrTile[tileId].tex_z;
				//console.debug("tileId "+tileId)
				//console.debug("startX "+startX)
				//console.debug("startZ "+startZ)
				var x_=-1;
				var z_=-1;
				var index_=0;
				var i_=0;
				var j_=0;
				//Creation of a grid
                    for(;i_<9;i_++){
					    z_=startZ+(scale)*i_;
					   for(;j_<9;j_++){
					      x_=startX+(scale)*j_;
		                  arrTile[tileId].triangleGeometry.vertices.push(new THREE.Vector3( x_,0.0/*flg_empty?442.0:(80000*parseFloat(jstr.verts[index_]))*/,z_));
						  //console.debug("flg_empty "+flg_empty);
				          //console.debug("index "+index_+" x "+x_+" jstr.verts[index] "+jstr.verts[index_]+" z "+z_);
						  index_++;
						             }
									 j_=0;
											};
				initFaceTex(arrTile[tileId]);
				
				if(!Exist1stTl){
				  Exist1stTl=true;
				  crtMesh(tileId);
                  arrCurRoot.unshift(tileId);
                  render();				  
					           }
				
				}
				
				if(flagroot){
				
					//console.debug("tex x  "+arrTile[tileId].tex_x+" y "+arrTile[tileId].tex_y);
					console.debug("crt  "+jstr.id+" ");
					console.debug("del  "+(jstr.id*4+1)+" ");
					console.debug("del  "+(jstr.id*4+2)+" ");
					console.debug("del  "+(jstr.id*4+3)+" ");
					console.debug("del  "+(jstr.id*4+4)+" ");
					crtMesh(jstr.id);
					
					
                    deltilemesh((jstr.id*4+1));
					deltilemesh((jstr.id*4+2));
					deltilemesh((jstr.id*4+3));
					deltilemesh((jstr.id*4+4));
					deltile((jstr.id*4+1));
					deltile((jstr.id*4+2));
					deltile((jstr.id*4+3));
					deltile((jstr.id*4+4));
					
					arrCurRoot.unshift(tileId);
					TLoad.idforloadroot=-1;
		            TLoad.ReadyForRoot=true;
					
					TLoad.arTileForAdd.splice(0,TLoad.arTileForAdd.length);
					TLoad.indx=0;
					
					render();
                            }
				
                    }else{console.debug("! Reject request id is out of range!");}
					 //r=(delete tile);
                    // console.debug("del  "+r);
                      jstr=null;					
					  TLoad.loaded()
//console.debug("load id  "+TLoad.loaded())
               /*var c0=triangleMesh[0].geometry.vertices[0];
			   var c1=triangleMesh[0].geometry.vertices[8];
			   var c2=triangleMesh[0].geometry.vertices[72];
			   var c3=triangleMesh[0].geometry.vertices[80];
			   var range_x=Math.max(c1.x,c0.x)-Math.min(c1.x,c0.x);
			   var range_z=Math.max(c0.z,c2.z)-Math.min(c0.z,c2.z);
			   console.debug("c0 "+c0.z)
			   console.debug("c1 "+c1.z)
			   console.debug("c2 "+c2.z)
			   console.debug("range_x "+range_x)
			   console.debug("range_z "+range_z)*/
			   
				                      }
									  
			function responseServerCubes(s) {
			
				var jstr;
				//console.debug(s)
				jstr=JSON.parse(''+s);
				if(jstr.tile_id>=0&&arrTileBlds[jstr.tile_id].id!=undefined){
				   var id=jstr.tile_id;
				   //alert("builtile "+id)
                   for(var j=0;j<jstr.builds.length;j++){
				       var b=parseInt(jstr.builds[j].build_id);
				       //console.debug(" Build id "+b)
					   MeshOfBlds[b] = new THREE.Mesh(
                            new THREE.CubeGeometry(0.25,0.2,0.25),
                           //new THREE.MeshBasicMaterial({color: 0x000000, opacity: 1})
				           new THREE.MeshBasicMaterial({
				           color: 0x000000//,
				           //'map':texture,
				           //wireframe: false,
				           //side:THREE.DoubleSide,
                           //'overdraw': true
				                })
                            );
			    var lon=parseFloat(jstr.builds[j].positionLon);///OSM_w;
                var lat=parseFloat(jstr.builds[j].positionLat);///OSM_h;
			    MeshOfBlds[b].position.set(arrTileBlds[id].x+(lon-arrTileBlds[id].minlon)*arrTileBlds[id].scale_x,0.5,arrTileBlds[id].z-(lat-arrTileBlds[id].minlat)*arrTileBlds[id].scale_z);
                MeshOfBlds[b].scale.set(parseFloat(jstr.builds[j].scaleX),8,parseFloat(jstr.builds[j].scaleZ));  
			    MeshOfBlds[b].rotation.set(parseFloat(jstr.builds[j].rotationX), parseFloat(jstr.builds[j].rotationY), parseFloat(jstr.builds[j].rotationZ));
                scene.add( MeshOfBlds[b]);
				arrTileBlds[id].arrIndxsBlds[j]=b;
					   }
				render();	   
				}
				jstr=null;
				TLoad.loaded()	
               }				
									  

			function onWindowResize() {

				camera.aspect = window.innerWidth / window.innerHeight;
				camera.updateProjectionMatrix();
				
				UnitToPixelScale = window.innerHeight /( 2.0 * getTanDeg(camera.fov / 2.0));

				renderer.setSize( window.innerWidth, window.innerHeight );

				render();

			}

			function animate() {

				requestAnimationFrame( animate );
				controls.update();

			}
			
			/*function verify(){
				//console.debug("TLoad.arTileForAdd.length "+TLoad.arTileForAdd.length)	
                TLoad.loadTile();
				
			    //timerid=setInterval(verify, 20);
			}*/
			
			function getDistance(cam,tlvl,tosmX,tosmZ){
			    //console.debug("dist for xyz "+tlvl+" "+tosmX+" "+tosmZ)
				
				var var1=Math.pow(2,tlvl);//number of tiles in row (specific lvl) 
				var scale=tlvl==0?TLoad.stepGrid:TLoad.stepGrid/(var1);//determine a width and a height of cell
				var offset=tlvl==0?0:Math.abs(2*TLoad.startX)/(var1);  // determine an offset for 1st tile of specific lvl 
				
				var vec1X=TLoad.startX+offset*tosmX;
				var vec1Z=TLoad.startZ+offset*tosmZ;
				
				var vec2X=TLoad.startX+offset*tosmX+(scale)*8;
				var vec2Z=TLoad.startZ+offset*tosmZ;
				
				var vec3X=TLoad.startX+offset*tosmX;
				var vec3Z=TLoad.startZ+offset*tosmZ+(scale)*8;
				
				var vec4X=TLoad.startX+offset*tosmX+(scale)*8;
				var vec4Z=TLoad.startZ+offset*tosmZ+(scale)*8;
				
                var cenx=(vec2X+vec1X)/2.0;
                var cenz=(vec2Z+vec3Z)/2.0;
				
				var tilecenter=new THREE.Vector3( cenx, 0.0, cenz);
				/*tex
				var ax=Math.max(cam.position.x,cenx)-Math.min(cam.position.x,cenx);
				var ay=Math.max(cam.position.y,0)-Math.min(cam.position.y,0);
				var az=Math.max(cam.position.z,cenz)-Math.min(cam.position.z,cenz);
				var cD=Math.sqrt(ax*ax+ay*ay+az*az);*/
				//cD=1 * cD.toFixed(1)
				//console.debug("cR "+cD+"lvl "+tlvl)
                return tilecenter.subSelf(cam.position).length();				
			}
			
			function deltilemesh(id,req){
			    //console.debug("del "+"id "+" "+id)
				if(req==false)req=false;
				else{req=true;}
			    scene.remove(triangleMesh[id]);
				/*triangleMesh[id].geometry.deallocate();
				triangleMesh[id].material.deallocate();
				triangleMesh[id].deallocate();*/

			    renderer.deallocateObject(triangleMesh[id]);
				renderer.deallocateTexture(arrTex[id]);
				delete arrTex[id];
				arrTex[id]=null;
				r=delete triangleMesh[id];
				triangleMesh[id]=null
				if(req){
				  if(triangleMesh[(id*4+1)])deltilemesh((id*4+1));
				  if(triangleMesh[(id*4+2)])deltilemesh((id*4+2));
				  if(triangleMesh[(id*4+3)])deltilemesh((id*4+3));
				  if(triangleMesh[(id*4+4)])deltilemesh((id*4+4));
				}

				//triangleMesh.splice(id,1);
				//console.debug("del "+triangleMesh[id]+" id "+id+" "+r)
			}

            function deltile(id,req){
                if(req==false)req=false;
				else{req=true;}			
				arrTile[id].destroy();
				delete arrTile[id];
				arrTile[id]=null;
				if(req){
				  if(arrTile[(id*4+1)])deltile((id*4+1));
				  if(arrTile[(id*4+2)])deltile((id*4+2));
				  if(arrTile[(id*4+3)])deltile((id*4+3));
				  if(arrTile[(id*4+4)])deltile((id*4+4));
				}
				delbuildsoftile(id);
				console.debug("Delete "+arrTile[id]+" id "+id)
			}
			
			function delbuildsoftile(id){
			    if(arrTileBlds[id]&&!arrTile[(id*4+1)]){
				  //alert("del "+id+" "+arrTileBlds[id].arrIndxsBlds[0])
				  if(arrTileBlds[id].arrIndxsBlds[0]!=undefined)
				    {
					//alert("del arrTileBlds[id].arrIndxsBlds.length() "+arrTileBlds[id].arrIndxsBlds.length())
				     for(var i in arrTileBlds[id].arrIndxsBlds)
		                {
						  var b=arrTileBlds[id].arrIndxsBlds[i];
		                  //alert("del build "+b)
		                  scene.remove(MeshOfBlds[b]);
                          renderer.deallocateObject(MeshOfBlds[b]);
			              //renderer.deallocateTexture(arrTex[id]);
			              //delete arrTex[id];
			              //arrTex[id]=null;
			              delete MeshOfBlds[b];
			              MeshOfBlds[b]=null
		                }
		              //arrTileBlds[id].arrIndxsBlds.splice(0,arrTileBlds[id].arrIndxsBlds.length);
		             }
				  arrTileBlds[id].destroy();
				  delete arrTileBlds[id];
				  arrTileBlds[id]=null;
				 }
			
					 
		 
		   
		
			}
			
			function crtMesh(id){
			    //var tex=''+arrTile[id].lvl+'/'+arrTile[id].tex_x+'/'+arrTile[id].tex_z;
                //var texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tex+".png",new THREE.UVMapping(),function(){triangleMesh[id].visible=true;renderer.render( scene, camera );})
		        //arrTex[id].generateMipmaps = true;//texture
                arrTex[id].magFilter = THREE.LinearFilter;
                arrTex[id].minFilter = THREE.LinearFilter;
				arrTex[id].anisotropy = maxAnisotropy;
				var triangleMaterial = new THREE.MeshBasicMaterial({
				//'map':texture,
				'map': arrTex[id],
				//wireframe: true,
				side:THREE.DoubleSide//,
                //'overdraw': false
				                });				

                triangleMesh[id] = new THREE.Mesh(arrTile[id].triangleGeometry, triangleMaterial);
				triangleMesh[id].position.set(0.0, 0.0, 0.0);
				scene.add(triangleMesh[id]);
				triangleMesh[id].visible=true;
				
				//console.debug("Crt "+triangleMesh[id]+" id "+id)

			}
			
			function checkTiles() {
			
				console.debug(" ")
				console.debug(" ")
				/*console.debug("camera.phi "+controls.phi)
				console.debug("camera.theta "+controls.theta)*/
				//console.debug("fov "+camera.fov)

				console.debug("arrCurRoot.length "+arrCurRoot.length)	
				//&&TLoad.idforloadroot!=arrCurRoot[j]&&TLoad.ReadyForRoot
				for(j=0;j<arrCurRoot.length;j++){
				  cur_ID=arrCurRoot[j];
				  //console.debug("cur_ID "+cur_ID)
				    flagDrop=false;
			        chldsExist=true;
				
			  var dist=getDistance(camera,arrTile[cur_ID].lvl,arrTile[cur_ID].tex_x,arrTile[cur_ID].tex_z);	
			  var pixelTileSize=tileSizeRoot/ Math.pow(2,arrTile[cur_ID].lvl)*UnitToPixelScale/dist;
              //console.debug("UnitToPixelScale "+UnitToPixelScale)
              //if(arrTile[cur_ID].lvl>=17)console.debug("dist "+dist+ "; id "+cur_ID+"; lvl "+arrTile[cur_ID].lvl+" pixelTileSize "+pixelTileSize)	
             // console.debug("dist "+dist+ "; id "+cur_ID+"; lvl "+arrTile[cur_ID].lvl)		  
              //console.debug("pixelTileSize "+pixelTileSize + " "+cur_ID)
			  if(dist<=200&&lvlbldactive<0)lvlbldactive=arrTile[cur_ID].lvl;
			  if(arrTile[cur_ID].lvl==lvlbldactive&&!arrTileBlds[cur_ID])
			  {

				 arrTileBlds[cur_ID]=new TileBlds();
				 arrTileBlds[cur_ID].id=cur_ID;
				 var minlon=tile2lon(arrTile[cur_ID].tex_x,arrTile[cur_ID].lvl)
				 var maxlon=tile2lon(arrTile[cur_ID].tex_x+1,arrTile[cur_ID].lvl)
				 var minlat=tile2lat(arrTile[cur_ID].tex_z+1,arrTile[cur_ID].lvl)
				 var maxlat=tile2lat(arrTile[cur_ID].tex_z,arrTile[cur_ID].lvl)
			     var range_lon=maxlon-minlon;
			     var range_lat=maxlat-minlat;
			     var c0=triangleMesh[cur_ID].geometry.vertices[0];
			     var c1=triangleMesh[cur_ID].geometry.vertices[8];
			     var c2=triangleMesh[cur_ID].geometry.vertices[72];
			     var c3=triangleMesh[cur_ID].geometry.vertices[80];
			     var range_x=Math.max(c1.x,c0.x)-Math.min(c1.x,c0.x);
			     var range_z=Math.max(c0.z,c2.z)-Math.min(c0.z,c2.z);
				 arrTileBlds[cur_ID].scale_x=range_x/range_lon;
			     arrTileBlds[cur_ID].scale_z=range_z/range_lat;
				 arrTileBlds[cur_ID].minlon=minlon;
				 arrTileBlds[cur_ID].minlat=minlat;
				 arrTileBlds[cur_ID].z=c3.z;
				 arrTileBlds[cur_ID].x=c0.x;
				 
                 TLoad.pushTileCube(""+arrTile[cur_ID].id+" "+minlon+" "+minlat+" "+maxlon+" "+maxlat);
				 
			  }
			  //if(flag17&&arrTile[cur_ID].lvl<18)triangleMesh[arrTile[cur_ID].id].visible=false;
			  //if(flag17&&arrTile[cur_ID].id!=rendtile)triangleMesh[arrTile[cur_ID].id].visible=false;
			  if(true/*arrTile[cur_ID].id*4+1<=TLoad.maxid*//*&&chldsExist*/){	 

			    if(pixelTileSize>=384)
				{
				flagDrop=true;
				//if(arrTile[cur_ID].lvl>=14)console.debug("new id load "+cur_ID*4+1)
				if(arrTile[cur_ID].childs[0]<0){TLoad.pushTile(cur_ID*4+1);chldsExist=false;}
				if(arrTile[cur_ID].childs[1]<0){TLoad.pushTile(cur_ID*4+2);chldsExist=false;}
				if(arrTile[cur_ID].childs[2]<0){TLoad.pushTile(cur_ID*4+3);chldsExist=false;}
				if(arrTile[cur_ID].childs[3]<0){TLoad.pushTile(cur_ID*4+4);chldsExist=false;}
				}
							
				//drop to the level below (divide by 4 quad)
				   if(flagDrop&&chldsExist){
				    
					deltilemesh(cur_ID,false)
					deltile(cur_ID,false)

                    crtMesh((cur_ID*4+1));
                    crtMesh((cur_ID*4+2));
                    crtMesh((cur_ID*4+3));					
                    crtMesh((cur_ID*4+4));

					del_indx=j;
					console.debug("DEL "+del_indx)
					
					arrCurRoot.splice(del_indx,1);
					arrCurRoot.push((cur_ID*4+1));
					arrCurRoot.push((cur_ID*4+2));
					arrCurRoot.push((cur_ID*4+3));
					arrCurRoot.push((cur_ID*4+4));
					
                    //render();					
					//console.debug("break ")
					break;			
                                }
				    
				            }
							
							
				 flagRise=false;
				 //does tile have à parent
				 if(TLoad.ReadyForRoot&&arrTile[cur_ID].prnt>=0){
				    //console.debug("(arrTile[cur_ID].lvl-1) "+(arrTile[cur_ID].lvl-1))
				    prntId=(1*arrTile[cur_ID].prnt);
					ch_id1=4*prntId+1;
					ch_id2=4*prntId+2;
					ch_id3=4*prntId+3;
					ch_id4=4*prntId+4;
					allchexist=true;
					/*console.debug(ch_id1+"  arrTile[ch_id1] "+arrTile[ch_id1])
					console.debug(ch_id2+"  arrTile[ch_id2] "+arrTile[ch_id2])
					console.debug(ch_id3+"  arrTile[ch_id3] "+arrTile[ch_id3])
					console.debug(ch_id4+"  arrTile[ch_id4] "+arrTile[ch_id4])*/
					if(!arrTile[ch_id1]){allchexist=false;}
					if(!arrTile[ch_id2]){allchexist=false;}
					if(!arrTile[ch_id3]){allchexist=false;}
					if(!arrTile[ch_id4]){allchexist=false;}
					if(allchexist){				

					var distFromCh1=getDistance(camera,arrTile[ch_id1].lvl,arrTile[ch_id1].tex_x,arrTile[ch_id1].tex_z);
					var pixelTileSize1=tileSizeRoot/ Math.pow(2,arrTile[ch_id1].lvl)*UnitToPixelScale/distFromCh1;
				    var distFromCh2=getDistance(camera,arrTile[ch_id2].lvl,arrTile[ch_id2].tex_x,arrTile[ch_id2].tex_z);
					var pixelTileSize2=tileSizeRoot/ Math.pow(2,arrTile[ch_id2].lvl)*UnitToPixelScale/distFromCh2;
				    var distFromCh3=getDistance(camera,arrTile[ch_id3].lvl,arrTile[ch_id3].tex_x,arrTile[ch_id3].tex_z);
					var pixelTileSize3=tileSizeRoot/ Math.pow(2,arrTile[ch_id3].lvl)*UnitToPixelScale/distFromCh3;
				    var distFromCh4=getDistance(camera,arrTile[ch_id4].lvl,arrTile[ch_id4].tex_x,arrTile[ch_id4].tex_z);
					var pixelTileSize4=tileSizeRoot/ Math.pow(2,arrTile[ch_id4].lvl)*UnitToPixelScale/distFromCh4;
					
				   /* console.debug("\npixelTileSize4 "+pixelTileSize4)
					console.debug("pixelTileSize3 "+pixelTileSize3)
					console.debug("pixelTileSize2 "+pixelTileSize2)
					console.debug("pixelTileSize1 "+pixelTileSize1)
					console.debug("\n\n")*/
				 	if(pixelTileSize1<192&&pixelTileSize2<192&&pixelTileSize3<192&&pixelTileSize4<192){
					flagRise=true;
					count=0;
					for(i=0 ;i< arrCurRoot.length;i++){
					     if(arrTile[arrCurRoot[i]].prnt==prntId){arrCurRoot[i]=0;count++;}
				        }
					arrCurRoot.sort();	
					for(i=0 ;i<count;i++)arrCurRoot.shift();	
					//arrCurRoot.unshift(new_root.id);
					
					/*TLoad.arTileForAdd.splice(0,TLoad.arTileForAdd.length);
					TLoad.indx=0;*/
					
					TLoad.prepareRootID(prntId);
					console.debug("New id "+prntId)
                    console.debug("arrCurRoot.length "+arrCurRoot.length)
					break;
					}

							   
								    }
						
				                      }		
							
                                       }
					
				if(TLoad)TLoad.loadTile();
                render();
			
			}
			
			function render() {
			    /*renderer.render( scene, camera );
				renderer.clear(flase, true, flase);
				*/
                //renderer.clear(true, true, true);
				//renderer.context.depthMask( true );
                renderer.render( scene, camera);
				stats.update();
			}

			
			
			function lon2tile(lon,zoom) {
			     return (Math.floor((lon+180)/360*Math.pow(2,zoom)));
			 }
            function lat2tile(lat,zoom)  { 
			    return (Math.floor((1-Math.log(Math.tan(lat*Math.PI/180) + 1/Math.cos(lat*Math.PI/180))/Math.PI)/2 *Math.pow(2,zoom)));
			}
			
			function tile2lon(x,z) {
			    return (x/Math.pow(2,z)*360-180);
 			}
 			function tile2lat(y,z) {
 			    var n=Math.PI-2*Math.PI*y/Math.pow(2,z);
 			    return (180/Math.PI*Math.atan(0.5*(Math.exp(n)-Math.exp(-n))));
 			}



		</script>

	</body>
</html>
