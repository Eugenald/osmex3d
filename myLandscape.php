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
		<div  jstcache="0"  id="init" ></div>

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
	this.center;//THREE.Vector3
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
		 delete this.center;this.center=null;
		 this.childs.length = 0;delete this.childs;this.childs=null;
		 delete this.prnt;
		 //this.triangleGeometry.dispose();
		 //this.texture.dispose();
		 delete this.triangleGeometry;this.triangleGeometry=null;
    };
	
}

//Class of cache tileprnt		
function TileCache () {
    this.tile=-1;
	this.refcounter=0;
}

			if ( ! Detector.webgl ) Detector.addGetWebGLMessage();

			var container, stats;
			
			arrCurRoot = new Array();
			arrTile = new Array();
			//Arrgarbg = new Array();
			newArrCR=new Array();
			arrCachePrnt=new Array();
			var timerid=0;
			cur_root=0;
			
			var camera, controls, scene, renderer;
			
			var texture;

			var cross;
			
			triangleGeometry=0;
			
			triangleMesh = new Array();

            var div = document.getElementById('cont');
			//div.style.display="none";
			div.ongetdata =update_data;

            var divInit = document.getElementById('init');
			//div.style.display="none";
			divInit.ongetdata =onceInit;	
			
			init();
			animate();
			
//Object ( dynamically add the necessary tiles)

var TLoad = new function () {
    this.maxid=-1;
	this.startX=-1;
	this.startZ=-1;
	this.stepGrid=-1;
	this.idforloadroot=-1;
	this.ReadyForRoot=true;
    this.indx=0;
    this.ready=true;                 //a flag of readiness
    this.arTileForAdd = new Array(); //the queue of  tiles for loading
	
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

	  //add tile in queue
this.pushTile = function (IdTile) {              
    if(!this.tileinQueue(IdTile)&&IdTile<=this.maxid/*&&!this.isFull()*/)this.arTileForAdd.push(IdTile);
};	

      //load and check flag of readiness
this.loadTile = function () {
   if(this.indx<this.arTileForAdd.length&&this.ready==true){
    id=this.arTileForAdd[this.indx/*this.arTileForAdd.length-1*/];
    if(id>=0){this.indx++;this.ready=false;land_func(id);}
	                               }
};
	
	//set flag of readiness
this.loaded = function () { 
    this.ready=true;
    //return this.arTileForAdd.pop();  //delete the tile from the queue
};
		
}

			function init() {
			
			GlobInit();
			ch=0;
			    zoom=6;
				lon=11.76;
				lat=41.97;
			    x_t=lon2tile(lon,zoom);
				y_t=lat2tile(lat,zoom);
			    //var texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+zoom+'/'+x_t+'/'+y_t+'.png');
                //var texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tiles[0].tex+".png");
				//alert('http://c.tile.openstreetmap.org/'+tiles[0].tex+".png")
				camera = new THREE.PerspectiveCamera( 45, window.innerWidth / window.innerHeight, 0.01, 100 );
				camera.position.set(0, 27.0, 21.0);
				
				
                controls = new THREE.CameraController( camera );
                controls.userZoomSpeed = 0.1;

				//controls.rotateSpeed = 0.01;

				controls.addEventListener( 'change', render );
                //timerid=setInterval(render, 24);
				
				//scene
                scene = new THREE.Scene();
				scene.fog = new THREE.FogExp2( 0xcccccc, 0.002 );
				//var material =  new THREE.MeshLambertMaterial( { color:0xffffff, shading: THREE.FlatShading } );



				/*triangleGeometry.faces[0].vertexColors[0] = new THREE.Color(0xFF0000);
				triangleGeometry.faces[0].vertexColors[1] = new THREE.Color(0x00FF00);
				triangleGeometry.faces[0].vertexColors[2] = new THREE.Color(0x0000FF);*/

				// Create a mesh and insert the geometry and the material. Translate the whole mesh
				// by -1.5 on the x axis and by 4 on the z axis. Finally add the mesh to the scene.
				/*triangleMesh[0] = new THREE.Mesh(tiles[0].triangleGeometry, triangleMaterial);
				triangleMesh[0].position.set(0.0, 0.0, 0.0);
				scene.add(triangleMesh[0]);*/
				
				//fill the meshes of our tiles and add to the scene
		/*for ( i = 0; i <tiles.length;i++) {
		              texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tiles[i].tex+".png")
					  //texture=THREE.ImageUtils.loadTexture('http://otile1.mqcdn.com/tiles/1.0.0/osm/'+tiles[i].tex+".png")
		              triangleMaterial = new THREE.MeshBasicMaterial({
					    'map': texture,
						//wireframe: true,
						side:THREE.DoubleSide,
                        'overdraw': true
				                       });
                 	  triangleMesh[i] = new THREE.Mesh(tiles[i].triangleGeometry, triangleMaterial);
				      triangleMesh[i].position.set(0.0, 0.0, 0.0);
					  scene.add(triangleMesh[i]);
					  triangleMesh[i].visible=false;
					  //console.debug("tile N "+i)

			}
				triangleMesh[0].visible=true;*/

				//triangleMaterial.wireframe=true;
				//triangleMaterial.color=new THREE.Color(0x2ed149);
				
				// lights

				light = new THREE.DirectionalLight( 0xffffff );
				light.position.set( 1, 1, 1 );
				scene.add( light );

				light = new THREE.DirectionalLight( 0x002288 );
				light.position.set( -1, -1, -1 );
				scene.add( light );

				light = new THREE.AmbientLight( 0x222222 );
				scene.add( light );	
				
				// renderer

				renderer = new THREE.WebGLRenderer( { antialias: false } );
				//renderer.setClearColor( scene.fog.color, 1 );
				renderer.setClearColor( new THREE.Color(0x1a3516), 1 );
				renderer.setSize( window.innerWidth, window.innerHeight );

				container = document.getElementById( 'container' );
				container.appendChild( renderer.domElement );

				stats = new Stats();
				stats.domElement.style.position = 'absolute';
				stats.domElement.style.top = '0px';
				stats.domElement.style.zIndex = 100;
				container.appendChild( stats.domElement );

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
			
			
			function onceInit(s) {
			    
			  var jstr=JSON.parse(''+s);
				
			   if(jstr.id==0){
				var tileId=jstr.id;
				TLoad.maxid=parseFloat(jstr.maxid);
				TLoad.startX=parseFloat(jstr.start_xz[0]);
				TLoad.startZ=parseFloat(jstr.start_xz[1]);
				arrTile[tileId]=new Tile();
				arrTile[tileId].id=jstr.id;
				arrTile[tileId].lvl=0;
				arrTile[tileId].prnt=-1;
				arrTile[tileId].tex_x=0;
				arrTile[tileId].tex_z=0;
					
				console.debug("Init done crt Root Tile  "+jstr.id+" ");
				//determine a width and a height of cell
				TLoad.stepGrid=(Math.abs(TLoad.startX)*2)/8;
                var scale=TLoad.stepGrid;
				var offset=0  // no offset for 0st lvl 
				//count 1st coordinates for 1st tile
				var startX=TLoad.startX;
				var startZ=TLoad.startZ;
				var x_=-1;
				var z_=-1;
				var index_=0;
				//Creation of a grid
                    for(var i_=0;i_<9;i_++){
					    z_=startZ+(scale)*i_;
					   for(var j_=0;j_<9;j_++){
					      x_=startX+(scale)*j_;
		                  arrTile[tileId].triangleGeometry.vertices.push(new THREE.Vector3( x_,parseFloat(jstr.verts[index_]),z_));
				          console.debug("index "+index_+" x "+x_+" jstr.verts[index] "+jstr.verts[index_]+" z "+z_);
						  index_++;
						             }
									 j_=0;
											};
				initFaceTex(arrTile[tileId]);							
                vec1=arrTile[tileId].triangleGeometry.vertices[0];
                vec2=arrTile[tileId].triangleGeometry.vertices[8];
                vec3=arrTile[tileId].triangleGeometry.vertices[72];
                vec4=arrTile[tileId].triangleGeometry.vertices[80];
                min1=Math.min(vec4.y,vec3.y);min2=Math.min(vec2.y,vec1.y);min=Math.min(min1,min2);
                max1=Math.max(vec4.y,vec3.y);max2=Math.max(vec2.y,vec1.y);max=Math.max(max1,max2);
                ceny=(min+max)/2.0;
                cenx=(vec2.x+vec1.x)/2.0;
                cenz=(vec2.z+vec3.z)/2.0;
                arrTile[tileId].center=new THREE.Vector3(cenx,ceny,cenz);
				crtMesh(jstr.id);
                arrCurRoot.unshift(tileId);
                jstr=null;					
					  
                            }
				                      }
									  
			
			
			//function is called in response to a request from the server to get the tile by id
			function update_data(s) {
			//clearInterval(timerid);
			//var div = document.getElementById('cont');
            //alert(s);
			    //str_vert=s.trim();//!!! убери в  генераторе в конце пробел
			    //var arr_verts = str_vert.split(/\s+/);
				 tileId=-1;
				var flagroot=false;
				var jstr;
				jstr=JSON.parse(''+s);
				//console.debug("cur_root.id  "+cur_root.id)
				
				//if(TLoad.idforloadroot==jstr.id)flagroot=true;
                 findtile=false;
				 for(j in arrCurRoot){
				     cur_ID=arrCurRoot[j];
					//if(flagroot){
					   //if(cur_ID==jstr.id){console.debug("create tile  "+jstr.id+" "+arrTile[cur_ID]);tile=arrTile[cur_ID];flagroot=true;break;}
					          //  }
					//else{			
                       if(cur_ID*4+1==jstr.id){tileId=cur_ID*4+1;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z;arrTile[cur_ID].childs[0]=jstr.id;console.debug("tex x  "+arrTile[tileId].tex_x+" y "+arrTile[tileId].tex_z);arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}
					   if(cur_ID*4+2==jstr.id){tileId=cur_ID*4+2;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x+1;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z;arrTile[cur_ID].childs[1]=jstr.id;console.debug("tex x  "+arrTile[tileId].tex_x+" y "+arrTile[tileId].tex_z);arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}
					   if(cur_ID*4+3==jstr.id){tileId=cur_ID*4+3;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z+1;arrTile[cur_ID].childs[2]=jstr.id;console.debug("tex x  "+arrTile[tileId].tex_x+" y "+arrTile[tileId].tex_z);arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}
					   if(cur_ID*4+4==jstr.id){tileId=cur_ID*4+4;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x+1;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z+1;arrTile[cur_ID].childs[3]=jstr.id;console.debug("tex x  "+arrTile[tileId].tex_x+" y "+arrTile[tileId].tex_z);arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}
					   // }
					 //delete cur_ID;
                     //console.debug("del cur "+r);
                     cur_ID=null					 
					}
				 //console.debug("!findtile "+(!findtile)+" id "+jstr.id+" triangleMesh[(jstr.id*4+1)] "+triangleMesh[(jstr.id*4+1)]);	
				if(!findtile&&triangleMesh[(jstr.id*4+1)]&&triangleMesh[(jstr.id*4+2)]&&triangleMesh[(jstr.id*4+3)]&&triangleMesh[(jstr.id*4+4)]/*&&arrTile[jstr.id]*/){
				/*console.debug("create tile  "+jstr.id+" "+arrTile[jstr.id]);*/
				tileId=jstr.id;
				arrTile[tileId]=new Tile();
				arrTile[tileId].id=jstr.id;
				var lvl=-1;
				for(t=0;t<=TLoad.maxid;t=(t*4+4)){lvl++;if(jstr.id<=t)break}
				arrTile[tileId].lvl=lvl
				flagroot=true;}
				
				if(flagroot){
				
				    arrTile[tileId].tex_x=arrTile[(tileId*4+1)].tex_x/2;
					arrTile[tileId].tex_z=arrTile[(tileId*4+1)].tex_z/2;
					//console.debug("tex x  "+arrTile[tileId].tex_x+" y "+arrTile[tileId].tex_y);
					crtMesh(jstr.id);
					
					console.debug("crt  "+jstr.id+" ");
					console.debug("del  "+(jstr.id*4+1)+" ");
					console.debug("del  "+(jstr.id*4+2)+" ");
					console.debug("del  "+(jstr.id*4+3)+" ");
					console.debug("del  "+(jstr.id*4+4)+" ");
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
                            }
								
                if(tileId>=0){

				/*var lvl=-1;
				var frstIdlvl=0;
				var countIdlvl;
				for(var t=0;t<=MAXID;t=(t*4+4)){lvl++;if(jstr.id<=t)break;frstIdlvl=t+1;}
				arrTile[tileId].lvl=lvl;
                countIdlvl=Math.pow(4,lvl);
				console.debug("countIdlvl "+countIdlvl+" frstIdlvl "+frstIdlvl)
				
				var col=(tileId-frstIdlvl)%Math.sqrt(countIdlvl);
				var row=parseInt((tileId-frstIdlvl)/Math.sqrt(countIdlvl));
				console.debug("id "+tileId+" col "+col+" row "+row)*/
				//zxy=jstr.tex.split(" ");
                //tile.tex=''+zxy[0]+'/'+zxy[1]+'/'+zxy[2];
				arrTile[tileId].prnt=jstr.id==0?-1:((jstr.id-1)-((jstr.id-1)%4))/4;

				var var1=Math.pow(2,arrTile[tileId].lvl);//number of tiles in row (specific lvl) 
				scale=jstr.id==0?TLoad.stepGrid:TLoad.stepGrid/(var1);//determine a width and a height of cell
				//console.debug("scale "+scale+" tile.id "+arrTile[tileId].id+" tile.lvl "+arrTile[tileId].lvl)
				var offset=jstr.id==0?0:Math.abs(2*TLoad.startX)/(var1);  // determine an offset for 1st tile of specific lvl 
				//count 1st coordinates for concrete tile
				var startX=TLoad.startX+offset*arrTile[tileId].tex_x;
				var startZ=TLoad.startZ+offset*arrTile[tileId].tex_z;
				console.debug("tileId "+tileId)
				console.debug("startX "+startX)
				console.debug("startZ "+startZ)
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
		                  arrTile[tileId].triangleGeometry.vertices.push(new THREE.Vector3( x_,parseFloat(jstr.verts[index_]),z_));
				          //console.debug("index "+index_+" x "+x_+" jstr.verts[index] "+jstr.verts[index_]+" z "+z_);
						  index_++;
						             }
									 j_=0;
											};
				initFaceTex(arrTile[tileId]);							
                vec1=arrTile[tileId].triangleGeometry.vertices[0];
                vec2=arrTile[tileId].triangleGeometry.vertices[8];
                vec3=arrTile[tileId].triangleGeometry.vertices[72];
                vec4=arrTile[tileId].triangleGeometry.vertices[80];
                min1=Math.min(vec4.y,vec3.y);min2=Math.min(vec2.y,vec1.y);min=Math.min(min1,min2);
                max1=Math.max(vec4.y,vec3.y);max2=Math.max(vec2.y,vec1.y);max=Math.max(max1,max2);
                ceny=(min+max)/2.0;
                cenx=(vec2.x+vec1.x)/2.0;
                cenz=(vec2.z+vec3.z)/2.0;
                arrTile[tileId].center=new THREE.Vector3(cenx,ceny,cenz);
				//var tex=''+arrTile[id].lvl+'/'+arrTile[id].tex_x+'/'+arrTile[id].tex_y;
                //arrTile[tileId].texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tex+".png");
				}
				

					 //r=(delete tile);
                    // console.debug("del  "+r);
                      jstr=null;					
					  TLoad.loaded()
//console.debug("load id  "+TLoad.loaded())	
				                      }

			function onWindowResize() {

				camera.aspect = window.innerWidth / window.innerHeight;
				camera.updateProjectionMatrix();

				renderer.setSize( window.innerWidth, window.innerHeight );

				render();

			}

			function animate() {

				requestAnimationFrame( animate );
				controls.update();

			}
			
			function verify(){
				//console.debug("TLoad.arTileForAdd.length "+TLoad.arTileForAdd.length)	
                TLoad.loadTile();
				
			    //timerid=setInterval(verify, 20);
			}
			
			function getDistance(cam,tile){
			    //console.debug("dist for id "+tile)
			    cam_pos=cam.position
				ax=Math.max(cam_pos.x,arrTile[tile].center.x)-Math.min(cam_pos.x,arrTile[tile].center.x);
				ay=Math.max(cam_pos.y,arrTile[tile].center.y)-Math.min(cam_pos.y,arrTile[tile].center.y);
				az=Math.max(cam_pos.z,arrTile[tile].center.z)-Math.min(cam_pos.z,arrTile[tile].center.z);
				cD=Math.sqrt(ax*ax+ay*ay+az*az);
				//cD=1 * cD.toFixed(1)
				//console.debug("cR "+cD+"id "+tile)
                return cD				
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
				//renderer.deallocateTexture(arrTile[id].texture);
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
				console.debug("Delete "+arrTile[id]+" id "+id)
			}
			
			function crtMesh(id){
			    var tex=''+arrTile[id].lvl+'/'+arrTile[id].tex_x+'/'+arrTile[id].tex_z;
                var texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tex+".png",new THREE.UVMapping(),function(){triangleMesh[id].visible=true;renderer.render( scene, camera );})
		        var triangleMaterial = new THREE.MeshBasicMaterial({
				'map':texture,
				//'map': arrTile[id].texture,
				//wireframe: true,
				side:THREE.DoubleSide,
                'overdraw': true
				                });				

                triangleMesh[id] = new THREE.Mesh(arrTile[id].triangleGeometry, triangleMaterial);
				triangleMesh[id].position.set(0.0, 0.0, 0.0);
				scene.add(triangleMesh[id]);
				triangleMesh[id].visible=true;
				
				//console.debug("Crt "+triangleMesh[id]+" id "+id)

			}
			
			function render() {
				//wrt("cur_t_ids.length "+cur_t_ids.length+" ")//div.innerHTML+="cur_t_ids.length "+cur_t_ids.length+" "
				console.debug(" ")
				console.debug(" ")
				//console.debug("tiles.length "+tiles.length)	

				console.debug("arrCurRoot.length "+arrCurRoot.length)	
				//&&TLoad.idforloadroot!=arrCurRoot[j]&&TLoad.ReadyForRoot
				for(j=0;j<arrCurRoot.length;j++){
				  cur_ID=arrCurRoot[j];
				  console.debug("cur_ID "+cur_ID)
				    flagDrop=false;
			        chldsExist=true;
				    //if the file exists but is not loaded call func 'land_func(IdTile)' to get data
					//console.debug("cur_root.childs[3].id "+cur_root.childs[3].id)
					if(arrTile[cur_ID].childs[0]<0){TLoad.pushTile(cur_ID*4+1);chldsExist=false;}
					if(arrTile[cur_ID].childs[1]<0){TLoad.pushTile(cur_ID*4+2);chldsExist=false;}
					if(arrTile[cur_ID].childs[2]<0){TLoad.pushTile(cur_ID*4+3);chldsExist=false;}
					if(arrTile[cur_ID].childs[3]<0){TLoad.pushTile(cur_ID*4+4);chldsExist=false;}
					//console.debug("cur_root.childs[3] "+cur_root.childs[3].id)
					//console.debug("lvl "+cur_root.lvl)
					//console.debug("chldsExist "+chldsExist)
					//break
					
					//console.debug("arrTile[cur_ID].childs[0] "+arrTile[cur_ID].childs[0])
					//console.debug("chldsExist "+chldsExist)
			  if(arrTile[cur_ID].childs[0]>=0&&chldsExist){	 
			  //constant distance of the current tile by level
			  lvlconst=(arrTile[cur_ID].lvl*3.0);
			  //console.debug("tiles[id] "+id+" "+lvlconst+" (lvlconst) "+lvlconst)
			  activatedDistChild=21-lvlconst;
			  //console.debug("arrTile[cur_ID].lvl "+arrTile[cur_ID].lvl)
			  //console.debug("activatedDistChild "+activatedDistChild)
				//console.debug("id "+id)
                //at least one distance less Child
				
				
			    if(getDistance(camera,(cur_ID*4+1))<activatedDistChild||getDistance(camera,(cur_ID*4+2))<activatedDistChild||getDistance(camera,(cur_ID*4+3))<activatedDistChild||getDistance(camera,(cur_ID*4+4))<activatedDistChild){
				flagDrop=true;}
				
				/*if(getDistance(camera,ch_id2)<activatedDistChild){flagDrop=true;}	
				if(getDistance(camera,ch_id3)<activatedDistChild){flagDrop=true;}	
				if(getDistance(camera,ch_id4)<activatedDistChild){flagDrop=true;}*/				
				//drop to the level below (divide by 4 quad)
				   if(flagDrop){
				    
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
						
					//console.debug("break ")
					break;			
                                }
				    
				            }
							
							
				 flagRise=false;
				 //does tile have а parent
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

					//constant distance of the tileparent by level
					lvlconst=(arrTile[cur_ID].lvl-1)*3.0;if(lvlconst<=0)lvlconst=0
				    activatedDistPrnt=21.0-lvlconst
					//console.debug("activatedDistPrnt "+activatedDistPrnt)
					//distance to all tiles is bigger than constant to parent
				 	if(getDistance(camera,ch_id1)>activatedDistPrnt&&getDistance(camera,ch_id2)>activatedDistPrnt&&getDistance(camera,ch_id3)>activatedDistPrnt&&getDistance(camera,ch_id4)>activatedDistPrnt){
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
				renderer.render( scene, camera );
				stats.update();

			
			}

			
			
			function lon2tile(lon,zoom) {
			     return (Math.floor((lon+180)/360*Math.pow(2,zoom)));
			 }
            function lat2tile(lat,zoom)  { 
			    return (Math.floor((1-Math.log(Math.tan(lat*Math.PI/180) + 1/Math.cos(lat*Math.PI/180))/Math.PI)/2 *Math.pow(2,zoom)));
			}



		</script>

	</body>
</html>
