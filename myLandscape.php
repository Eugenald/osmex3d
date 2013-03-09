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


			if ( ! Detector.webgl ) Detector.addGetWebGLMessage();

			var container, stats;
			
			var arrCurRoot = new Array();
			var arrTile = new Array();

			var timerid=0;
			var Exist1stTl=false;
			
			var camera, controls, scene, renderer;
			
			var texture;

			var cross;
			
			var triangleMesh = new Array();

            var div = document.getElementById('cont');
			//div.style.display="none";
			div.ongetdata =responseServer;	
			
			init();
			animate();
			
//Object ( dynamically add the necessary tiles)

var TLoad = new function () {
    this.maxid=999999999;
	//set 1st coordinates for 1st tileRoots
	this.startX=-20;
	this.startZ=-20;
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
			
			    land_func(0);// load 1st tileroots

				camera = new THREE.PerspectiveCamera( 45, window.innerWidth / window.innerHeight, 0.01, 100 );
				camera.position.set(0, 27.0, 35.0);
				
				
                controls = new THREE.CameraController( camera );
                controls.userZoomSpeed = 0.1;

				//controls.rotateSpeed = 0.01;

				controls.addEventListener( 'change', checkTiles );
                //timerid=setInterval(render, 24);
				
				//scene
                scene = new THREE.Scene();
				scene.fog = new THREE.FogExp2( 0xcccccc, 0.002 );

				
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

			
			//function is called in response to a request from the server to get the tile by id
			function responseServer(s) {
			
				tileId=-1;
				var flagroot=false;
				var findtile=false;
				var jstr;
				jstr=JSON.parse(''+s);
				
                if(jstr.id>=0){				
				 for(j in arrCurRoot){
				     cur_ID=arrCurRoot[j];
		
                       if(cur_ID*4+1==jstr.id){tileId=cur_ID*4+1;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z;arrTile[cur_ID].childs[0]=jstr.id;console.debug("tex x  "+arrTile[tileId].tex_x+" y "+arrTile[tileId].tex_z);arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}
					   if(cur_ID*4+2==jstr.id){tileId=cur_ID*4+2;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x+1;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z;arrTile[cur_ID].childs[1]=jstr.id;console.debug("tex x  "+arrTile[tileId].tex_x+" y "+arrTile[tileId].tex_z);arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}
					   if(cur_ID*4+3==jstr.id){tileId=cur_ID*4+3;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z+1;arrTile[cur_ID].childs[2]=jstr.id;console.debug("tex x  "+arrTile[tileId].tex_x+" y "+arrTile[tileId].tex_z);arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}
					   if(cur_ID*4+4==jstr.id){tileId=cur_ID*4+4;arrTile[tileId]=new Tile();arrTile[tileId].id=jstr.id;arrTile[tileId].tex_x=2*arrTile[cur_ID].tex_x+1;arrTile[tileId].tex_z=2*arrTile[cur_ID].tex_z+1;arrTile[cur_ID].childs[3]=jstr.id;console.debug("tex x  "+arrTile[tileId].tex_x+" y "+arrTile[tileId].tex_z);arrTile[tileId].lvl=arrTile[cur_ID].lvl+1;findtile=true;break;}

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
		                  arrTile[tileId].triangleGeometry.vertices.push(new THREE.Vector3( x_,parseFloat(jstr.verts[index_]),z_));
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
				//var tex=''+arrTile[id].lvl+'/'+arrTile[id].tex_x+'/'+arrTile[id].tex_y;
                //arrTile[tileId].texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tex+".png");
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
				
				var ax=Math.max(cam.position.x,cenx)-Math.min(cam.position.x,cenx);
				var ay=Math.max(cam.position.y,0)-Math.min(cam.position.y,0);
				var az=Math.max(cam.position.z,cenz)-Math.min(cam.position.z,cenz);
				var cD=Math.sqrt(ax*ax+ay*ay+az*az);
				//cD=1 * cD.toFixed(1)
				//console.debug("cR "+cD+"lvl "+tlvl)
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
			
			function checkTiles() {
			
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

			  if(arrTile[cur_ID].id*4+1<=TLoad.maxid/*&&chldsExist*/){	 
			  //constant distance of the current tile by level
			  lvlconst=(arrTile[cur_ID].lvl*3.0);
			  //console.debug("tiles[id] "+id+" "+lvlconst+" (lvlconst) "+lvlconst)
			  activatedDistChild=21-lvlconst;
			  //console.debug("arrTile[cur_ID].lvl "+arrTile[cur_ID].lvl)
			  console.debug("activatedDistChild "+activatedDistChild)
                //at least one distance less Child
				
				var distToCh1=getDistance(camera,(arrTile[cur_ID].lvl+1),(arrTile[cur_ID].tex_x*2),(arrTile[cur_ID].tex_z*2));
				var distToCh2=getDistance(camera,(arrTile[cur_ID].lvl+1),(arrTile[cur_ID].tex_x*2+1),(arrTile[cur_ID].tex_z*2));
				var distToCh3=getDistance(camera,(arrTile[cur_ID].lvl+1),(arrTile[cur_ID].tex_x*2),(arrTile[cur_ID].tex_z*2+1));
				var distToCh4=getDistance(camera,(arrTile[cur_ID].lvl+1),(arrTile[cur_ID].tex_x*2+1),(arrTile[cur_ID].tex_z*2+1));
				console.debug("distToCh1 "+distToCh1)
			    if(distToCh1<activatedDistChild||distToCh2<activatedDistChild||distToCh3<activatedDistChild||distToCh4<activatedDistChild)
				{
				flagDrop=true;
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

					//constant distance of the tileparent by level
					lvlconst=(arrTile[cur_ID].lvl-1)*3.0;if(lvlconst<=0)lvlconst=0
				    activatedDistPrnt=21.0-lvlconst
					//console.debug("activatedDistPrnt "+activatedDistPrnt)
					//distance to all tiles is bigger than constant to parent
					var distFromCh1=getDistance(camera,arrTile[ch_id1].lvl,arrTile[ch_id1].tex_x,arrTile[ch_id1].tex_z);
				    var distFromCh2=getDistance(camera,arrTile[ch_id2].lvl,arrTile[ch_id2].tex_x,arrTile[ch_id2].tex_z);
				    var distFromCh3=getDistance(camera,arrTile[ch_id3].lvl,arrTile[ch_id3].tex_x,arrTile[ch_id3].tex_z);
				    var distFromCh4=getDistance(camera,arrTile[ch_id4].lvl,arrTile[ch_id4].tex_x,arrTile[ch_id4].tex_z);
				
				 	if(distFromCh1>activatedDistPrnt&&distFromCh2>activatedDistPrnt&&distFromCh3>activatedDistPrnt&&distFromCh4>activatedDistPrnt){
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
