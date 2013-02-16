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
        <div id="cont" ></div>

		<div id="container"></div>
		
		<script src="jscripts/three.js"></script>

        <script src="jscripts/CameraController.js"></script>

		<script src="jscripts/Detector.js"></script>
		<script src="jscripts/stats.min.js"></script>
		
		<script type="text/javascript" src="server_scripts/XMLHttpRequest.js"></script>
        <script type="text/javascript" src="server_scripts/Functions.js"></script> 

		<script>
//Class of tile		
function Tile () {
    this.id=-1;
	this.tex;
	this.lvl;//level
	this.center;//THREE.Vector3
    this.childs = new Array();//4 id of descendants 
    this.childs[0];
    this.childs[1];
    this.childs[2];
	this.childs[3];
    this.prnt;//parent
	this.triangleGeometry = new THREE.Geometry();
	
}

			if ( ! Detector.webgl ) Detector.addGetWebGLMessage();

			var container, stats;
			
			arrCurRoot = new Array();
			//Arrgarbg = new Array();
			newArrCR=new Array();
			var MAXID=0;
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
			init();
			animate();
			
<?php
//Choose from db 3 levels of detail and fill them
$query= <<<EOD
SELECT (SELECT MAX(id) FROM tile) as maxid,tile.id,tile.OSMtex,tile.lvl,ar_verts.verts,tile.id_t_c1,tile.id_t_c2,tile.id_t_c3,tile.id_t_c4,
tile.id_t_p FROM ar_verts,tile WHERE tile.id=0 and ar_verts.id=tile.id_av
EOD;
$usr=mysql_query($query);
if(!$usr)exit("Ошибка - ".mysql_error());
while($row = mysql_fetch_array($usr)){ 

$verts=explode(" ",trim($row['verts']));
$maxid=$row['maxid'];
$id=$row['id'];
$tex=$row['OSMtex'];
$lvl=$row['lvl'];
$id_t_c1=$row['id_t_c1'];
$id_t_c2=$row['id_t_c2'];
$id_t_c3=$row['id_t_c3'];
$id_t_c4=$row['id_t_c4'];
$id_t_p=$row['id_t_p'];
$json_data = array ('id'=>$id,'verts'=> $verts);
$json_str = json_encode($json_data);
//<script language='javascript'>
$js= <<<EOL
MAXID=$maxid;
cur_root = new Tile ();
cur_root.id=$id;
zxy='$tex'.split(" ");
cur_root.tex=''+zxy[0]+'/'+zxy[1]+'/'+zxy[2];
cur_root.lvl=$lvl;
cur_root.childs[0]=new Tile();
cur_root.childs[1]=new Tile();
cur_root.childs[2]=new Tile();
cur_root.childs[3]=new Tile();
cur_root.prnt=$id_t_p;
var jstr=JSON.parse('$json_str');
var arr_verts = jstr.verts;
        for(i=0;i<arr_verts.length;i+=3){
		cur_root.triangleGeometry.vertices.push(new THREE.Vector3( parseFloat(arr_verts[i]), /*0*/parseFloat(arr_verts[i+1]),parseFloat(arr_verts[i+2])));
				                        };
vec1=cur_root.triangleGeometry.vertices[0];
vec2=cur_root.triangleGeometry.vertices[8];
vec3=cur_root.triangleGeometry.vertices[72];
vec4=cur_root.triangleGeometry.vertices[80];
min1=Math.min(vec4.y,vec3.y);min2=Math.min(vec2.y,vec1.y);min=Math.min(min1,min2);
max1=Math.max(vec4.y,vec3.y);max2=Math.max(vec2.y,vec1.y);max=Math.max(max1,max2);
ceny=(min+max)/2.0;
cenx=(vec2.x+vec1.x)/2.0;
cenz=(vec2.z+vec3.z)/2.0;
cur_root.center=new THREE.Vector3(cenx,ceny,cenz);
EOL;
print $js;
}	

?>

initFaceTex(cur_root);
triangleMaterial = new THREE.MeshBasicMaterial({
wireframe: true,
side:THREE.DoubleSide,
 'overdraw': true
});
triangleMesh[cur_root.id] = new THREE.Mesh(cur_root.triangleGeometry, triangleMaterial);
triangleMesh[cur_root.id].position.set(0.0, 0.0, 0.0);
scene.add(triangleMesh[cur_root.id]);
//console.debug("tile N "+i)
triangleMesh[cur_root.id].visible=true;

arrCurRoot.push(cur_root);
//Object ( dynamically add the necessary tiles)

var TLoad = new function (maxiId) {
    this.maxid=MAXID;
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
			
			//function is called in response to a request from the server to get the tile by id
			function update_data(s) {
			//clearInterval(timerid);
			//var div = document.getElementById('cont');
            //alert(s);
			    //str_vert=s.trim();//!!! убери в  генераторе в конце пробел
			    //var arr_verts = str_vert.split(/\s+/);
				tile = new Tile ();
				flagroot=false;
				jstr=JSON.parse(''+s);
				//console.debug("cur_root.id  "+cur_root.id)
				
				if(TLoad.idforloadroot==jstr.id)flagroot=true;

				 for(j in arrCurRoot){
				    cur_tile=arrCurRoot[j];
					if(flagroot){
					   if(cur_tile.id==jstr.id){/*console.debug("create tile  "+jstr.id+" "+TLoad.idforloadroot);*/tile=cur_tile;break;}
					            }
					else{			
                       if(cur_tile.id*4+1==jstr.id){tile=cur_tile.childs[0];tile.id=jstr.id;tile.lvl=cur_tile.lvl+1;break;}
					   if(cur_tile.id*4+2==jstr.id){tile=cur_tile.childs[1];tile.id=jstr.id;tile.lvl=cur_tile.lvl+1;break;}
					   if(cur_tile.id*4+3==jstr.id){tile=cur_tile.childs[2];tile.id=jstr.id;tile.lvl=cur_tile.lvl+1;break;}
					   if(cur_tile.id*4+4==jstr.id){tile=cur_tile.childs[3];tile.id=jstr.id;tile.lvl=cur_tile.lvl+1;break;}
					    }
					 delete cur_tile;
                     //console.debug("del cur "+r);
                     cur_tile=null					 
					}
                
				zxy=jstr.tex.split(" ");
                tile.tex=''+zxy[0]+'/'+zxy[1]+'/'+zxy[2];
				tile.prnt=jstr.prnt;
				tile.childs[0]=new Tile();
				tile.childs[1]=new Tile();
				tile.childs[2]=new Tile();
				tile.childs[3]=new Tile();
                //tile.prnt=cur_tile.id;

                var arr_verts = jstr.verts;
                    for(i=0;i<arr_verts.length;i+=3){
		               tile.triangleGeometry.vertices.push(new THREE.Vector3( parseFloat(arr_verts[i]), /*0*/parseFloat(arr_verts[i+1]),parseFloat(arr_verts[i+2])));
				                            };
				initFaceTex(tile);							
                vec1=tile.triangleGeometry.vertices[0];
                vec2=tile.triangleGeometry.vertices[8];
                vec3=tile.triangleGeometry.vertices[72];
                vec4=tile.triangleGeometry.vertices[80];
                min1=Math.min(vec4.y,vec3.y);min2=Math.min(vec2.y,vec1.y);min=Math.min(min1,min2);
                max1=Math.max(vec4.y,vec3.y);max2=Math.max(vec2.y,vec1.y);max=Math.max(max1,max2);
                ceny=(min+max)/2.0;
                cenx=(vec2.x+vec1.x)/2.0;
                cenz=(vec2.z+vec3.z)/2.0;
                tile.center=new THREE.Vector3(cenx,ceny,cenz);
				
				if(flagroot){
				    triangleMaterial = new THREE.MeshBasicMaterial({
					//'map': texture,
					wireframe: true,
					side:THREE.DoubleSide,
                    'overdraw': true
				                       });				

                 	triangleMesh[jstr.id] = new THREE.Mesh(tile.triangleGeometry, triangleMaterial);
				    triangleMesh[jstr.id].position.set(0.0, 0.0, 0.0);
					scene.add(triangleMesh[jstr.id]);
					triangleMesh[jstr.id].visible=true;
					//console.debug("del  "+(jstr.id*4+1)+" "+TLoad.ReadyForRoot);
                    deltilemesh((jstr.id*4+1));
					deltilemesh((jstr.id*4+2));
					deltilemesh((jstr.id*4+3));
					deltilemesh((jstr.id*4+4));
					
					TLoad.idforloadroot=-1;
		            TLoad.ReadyForRoot=true;
					TLoad.arTileForAdd.splice(0,TLoad.arTileForAdd.length);//=new Array();
					TLoad.indx=0;
                            }
					 r=(delete tile);
                     console.debug("del  "+r);				 
					  TLoad.loaded()
//console.debug("load id  "+TLoad.loaded())	
				                      }

			function onWindowResize() {

				camera.aspect = window.innerWidth / window.innerHeight;
				camera.updateProjectionMatrix();

				renderer.setSize( window.innerWidth, window.innerHeight );

				controls.handleResize();

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
			    cam_pos=cam.position
				ax=Math.max(cam_pos.x,tile.center.x)-Math.min(cam_pos.x,tile.center.x);
				//console.debug("cam.x "+cam.x+"tiles["+(id)+"].center.x "+tiles[id].center.x)
				//console.debug("ax "+ax)
				ay=Math.max(cam_pos.y,tile.center.y)-Math.min(cam_pos.y,tile.center.y);
				az=Math.max(cam_pos.z,tile.center.z)-Math.min(cam_pos.z,tile.center.z);
				cD=Math.sqrt(ax*ax+ay*ay+az*az);
				//cD=1 * cD.toFixed(1)
				//console.debug("cR "+cD+"id "+tile.id)
                return cD				
			}
			
			function deltilemesh(id){
			    console.debug("del "+"id "+" "+id)
			    scene.remove(triangleMesh[id]);
			    renderer.deallocateObject(triangleMesh[id]);
				r=delete triangleMesh[id];
				triangleMesh[id]=null
				console.debug("del "+triangleMesh[id]+" id "+id+" "+r)
			}
			
			function render() {
				//wrt("cur_t_ids.length "+cur_t_ids.length+" ")//div.innerHTML+="cur_t_ids.length "+cur_t_ids.length+" "
				console.debug(" ")
				console.debug(" ")
				//console.debug("tiles.length "+tiles.length)	

				j=0;
				new_root=0;
				flagRise=false;
				arrCheckTile = new Array();
				//newArrCR=new Array();
				console.debug("arrCurRoot.length "+arrCurRoot.length)	
				for(;j<arrCurRoot.length&&TLoad.idforloadroot!=arrCurRoot[j].id&&TLoad.ReadyForRoot;j++){
				  cur_root=arrCurRoot[j];
				    flagDrop=false;
			        chldsExist=true;
				    //if the file exists but is not loaded call func 'land_func(IdTile)' to get data
					//console.debug("cur_root.childs[3].id "+cur_root.childs[3].id)
					if(cur_root.childs[0].id<0){TLoad.pushTile(cur_root.id*4+1);chldsExist=false;}
					if(cur_root.childs[1].id<0){TLoad.pushTile(cur_root.id*4+2);chldsExist=false;}
					if(cur_root.childs[2].id<0){TLoad.pushTile(cur_root.id*4+3);chldsExist=false;}
					if(cur_root.childs[3].id<0){TLoad.pushTile(cur_root.id*4+4);chldsExist=false;}
					//console.debug("cur_root.childs[3] "+cur_root.childs[3].id)
					//console.debug("lvl "+cur_root.lvl)
					//console.debug("chldsExist "+chldsExist)
					//break
					console.debug("cur_root.id "+cur_root.id)
			  if(cur_root.childs[0].id>=0&&chldsExist){	 
			  //constant distance of the current tile by level
			  lvlconst=(cur_root.lvl*3.0);
			  //console.debug("tiles[id] "+id+" "+lvlconst+" (lvlconst) "+lvlconst)
			  activatedDistChild=21-lvlconst;
			  //console.debug("activatedDistChild "+activatedDistChild)
				//console.debug("id "+id)
                //at least one distance less Child
				
				
			    if(getDistance(camera,cur_root.childs[0])<activatedDistChild||getDistance(camera,cur_root.childs[1])<activatedDistChild||getDistance(camera,cur_root.childs[2])<activatedDistChild||getDistance(camera,cur_root.childs[3])<activatedDistChild){
				flagDrop=true;}
				
				/*if(getDistance(camera,ch_id2)<activatedDistChild){flagDrop=true;}	
				if(getDistance(camera,ch_id3)<activatedDistChild){flagDrop=true;}	
				if(getDistance(camera,ch_id4)<activatedDistChild){flagDrop=true;}*/				
				//drop to the level below (divide by 4 quad)
				   if(flagDrop){
				    
					deltilemesh(cur_root.id)

                     //texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tile.tex+".png")
		              triangleMaterial = new THREE.MeshBasicMaterial({
					    //'map': texture,
						wireframe: true,
						side:THREE.DoubleSide,
                        'overdraw': true
				                       });				

                 	  triangleMesh[cur_root.childs[0].id] = new THREE.Mesh(cur_root.childs[0].triangleGeometry, triangleMaterial);
				      triangleMesh[cur_root.childs[0].id].position.set(0.0, 0.0, 0.0);
					  scene.add(triangleMesh[cur_root.childs[0].id]);
					  triangleMesh[cur_root.childs[0].id].visible=true;
					  
					  
					  //texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tile.tex+".png")
		              triangleMaterial = new THREE.MeshBasicMaterial({
					    //'map': texture,
						wireframe: true,
						side:THREE.DoubleSide,
                        'overdraw': true
				                       });				

                 	  triangleMesh[cur_root.childs[1].id] = new THREE.Mesh(cur_root.childs[1].triangleGeometry, triangleMaterial);
				      triangleMesh[cur_root.childs[1].id].position.set(0.0, 0.0, 0.0);
					  scene.add(triangleMesh[cur_root.childs[1].id]);
					  triangleMesh[cur_root.childs[1].id].visible=true;
					  
					  
					  //texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tile.tex+".png")
		              triangleMaterial = new THREE.MeshBasicMaterial({
					    //'map': texture,
						wireframe: true,
						side:THREE.DoubleSide,
                        'overdraw': true
				                       });				

                 	  triangleMesh[cur_root.childs[2].id] = new THREE.Mesh(cur_root.childs[2].triangleGeometry, triangleMaterial);
				      triangleMesh[cur_root.childs[2].id].position.set(0.0, 0.0, 0.0);
					  scene.add(triangleMesh[cur_root.childs[2].id]);
					  triangleMesh[cur_root.childs[2].id].visible=true;
					  
					  
					  //texture=THREE.ImageUtils.loadTexture('http://c.tile.openstreetmap.org/'+tile.tex+".png")
		              triangleMaterial = new THREE.MeshBasicMaterial({
					    //'map': texture,
						wireframe: true,
						side:THREE.DoubleSide,
                        'overdraw': true
				                       });				

                 	  triangleMesh[cur_root.childs[3].id] = new THREE.Mesh(cur_root.childs[3].triangleGeometry, triangleMaterial);
				      triangleMesh[cur_root.childs[3].id].position.set(0.0, 0.0, 0.0);
					  scene.add(triangleMesh[cur_root.childs[3].id]);
					  triangleMesh[cur_root.childs[3].id].visible=true;
					  
					 //console.debug("tileDrop "+tileDrop.id)
					// console.debug("ch_id4 "+ch_id4.id)
					del_indx=arrCurRoot.indexOf(cur_root);
					if(del_indx>=0){arrCurRoot.splice(del_indx,1);arrCurRoot.push(cur_root.childs[0]);arrCurRoot.push(cur_root.childs[1]);arrCurRoot.push(cur_root.childs[2]);arrCurRoot.push(cur_root.childs[3]);}
						
					console.debug("break ")
					break;			
                                }
				    
				            }
							
							
				 flagRise=false;
				 //does tile have а parent
				 if(TLoad.ReadyForRoot&&cur_root.prnt>=0){
				 //console.debug("newArrCR.length "+newArrCR.length)
				    prntId=cur_root.prnt;
					arrCheckTile.splice(0,arrCheckTile.length);
					for(i in arrCurRoot){
					     if(arrCurRoot[i].prnt==prntId)arrCheckTile.push(i);
				        }
					if(arrCheckTile.length==4){
                      					
				     /*ch_id1=arrCheckTile[0];console.debug("ch_id1.id "+ch_id1.id)
					 ch_id2=arrCheckTile[1];console.debug("ch_id2.id "+ch_id2.id)
					 ch_id3=arrCheckTile[2];console.debug("ch_id3.id "+ch_id3.id)
					 ch_id4=arrCheckTile[3];console.debug("ch_id4.id "+ch_id4.id)*/
					 

					//constant distance of the tileparent by level
					lvlconst=(cur_root.lvl-1)*3.0;if(lvlconst<=0)lvlconst=0
				    activatedDistPrnt=21.0-lvlconst
					console.debug("activatedDistPrnt "+activatedDistPrnt)
					//distance to all tiles is bigger than constant to parent
				 	if(getDistance(camera,arrCurRoot[arrCheckTile[0]])>activatedDistPrnt&&getDistance(camera,arrCurRoot[arrCheckTile[1]])>activatedDistPrnt&&getDistance(camera,arrCurRoot[arrCheckTile[2]])>activatedDistPrnt&&getDistance(camera,arrCurRoot[arrCheckTile[3]])>activatedDistPrnt){
					flagRise=true;
					new_root=new Tile();
					new_root.id=prntId;
					new_root.lvl=cur_root.lvl-1;
					TLoad.prepareRootID(new_root.id);
					console.debug("new_root.id "+new_root.id)
					break;
					}

							   
								    }
						
				                      }		
							

							
					r=delete cur_root
					cur_root=null
                    //console.debug("del  "+r);					
				                }

                    //rise to a higher level
				    if(flagRise){
                       flagRise=false;					
                       arrCheckTile.sort();
					   console.debug("rise ")
					   //console.debug("arrCheckTile[0] "+arrCheckTile[0])
                       /*new_root.childs[0]=arrCurRoot[arrCheckTile[0]];
					   new_root.childs[1]=arrCurRoot[arrCheckTile[1]];
					   new_root.childs[2]=arrCurRoot[arrCheckTile[2]];
					   new_root.childs[3]=arrCurRoot[arrCheckTile[3]];*/
					   
					   
					   for(v=0;v<arrCurRoot.length;v++){
					        if(v==arrCheckTile[0]||v==arrCheckTile[1]||v==arrCheckTile[2]||v==arrCheckTile[3]){arrCurRoot[v]=0;}//arrCurRoot.splice(v,1);v=-1;}
							}
						arrCurRoot.sort();
						delete arrCurRoot[0];arrCurRoot[0]=null
                        delete arrCurRoot[1];arrCurRoot[0]=null
						delete arrCurRoot[2];arrCurRoot[0]=null
						delete arrCurRoot[3];arrCurRoot[0]=null
						arrCurRoot.shift();
						arrCurRoot.shift();
						arrCurRoot.shift();
						arrCurRoot.shift();
						arrCurRoot.unshift(new_root);
						delete new_root
						new_root=null
                        ;
                       /*arrloc=new Array();
					   for(v in arrCurRoot){
					        if(v==arrCheckTile[0]||v==arrCheckTile[1]||v==arrCheckTile[2]||v==arrCheckTile[3]);
							else{arrloc.push(arrCurRoot[v]);}
							}
					   arrloc.push(new_root);
					   
					   arrCurRoot.splice(0,arrCurRoot.length);
					   for(v in arrloc)arrCurRoot[v]=arrloc[v];
					   arrloc.splice(0,arrloc.length);*/
					   
					   /*arrCurRoot.splice(arrCheckTile[0],1);
					   arrCurRoot.splice(arrCheckTile[1]-1,1);
					   arrCurRoot.splice(arrCheckTile[2]-2,1);
					   arrCurRoot.splice(arrCheckTile[3]-3,1,new_root);*/
					   //arrCurRoot[arrCurRoot.length]=new_root;
					   //arrCurRoot.push(new_root);
				               }
								
						//console.debug("newArrCR.length "+newArrCR.length)		

						
						//for(y=0;y<Arrgarbg.length;y++)console.debug("Arrgarbg[y].id "+Arrgarbg[y].id)
						
						
//--------------------------Invoke the Garbage Colection for unactive tiles-------------------------------//
/*						if(j==arrCurRoot.length&&Arrgarbg.length>0){
						    console.debug("invoke the Garbage Colection for unactive tiles ")
								   arLoclGrbg=new Array()
								   newArrCR=new Array()	
								   y=0	
                            //console.debug("Arrgarbg.length "+Arrgarbg.length)								   
						     for(;y<Arrgarbg.length;y++){
						         prnt=-1;
								 
								 if(Arrgarbg[y].prnt>=0)prnt=Arrgarbg[y].prnt;
								 else{Arrgarbg=new Array();break}
								 //console.debug("Arrgarbg.length "+Arrgarbg.length)
								 y++
								 while(y<Arrgarbg.length){
								    //console.debug("prnt "+prnt)
									console.debug("y "+y)
									console.debug("Arrgarbg[y].prnt "+Arrgarbg[y].prnt)
								    if(Arrgarbg[y].prnt==prnt){y++}
								    else{break;}				
								   }
								 y-- 
								 for(i in arrCurRoot){
					               if(arrCurRoot[i].prnt==prnt){//console.debug("triangleMesh[arrCurRoot[i].childs[0].id] "+triangleMesh[arrCurRoot[i].childs[0].id])
									 if(!triangleMesh[arrCurRoot[i].childs[0].id])arLoclGrbg.push(arrCurRoot[i].id);
										       
											   //deltilemesh(arrCurRoot[i].id)
											   //console.debug("arLoclGrbg.id "+arrCurRoot[i].id)                     
																  }
						                            }
											}
                                 //console.debug("arLoclGrbg.length "+arLoclGrbg.length)												
								 if(arLoclGrbg.length>0){
								     for(c=0;c<arrCurRoot.length;c++){skip=false;//console.debug("arLoclGrbg.id "+arLoclGrbg[g])
								         for(g=0;g<arLoclGrbg.length;g++){if(arrCurRoot[c].id==arLoclGrbg[g])skip=true;}
										 if(!skip)newArrCR.push(arrCurRoot[c]);
										 }
								     }
								 if(newArrCR.length>0)arrCurRoot=newArrCR;
				            Arrgarbg=new Array();        
						  }
*/						
                        						
                //timerid++;if(timerid==70){timerid=0;}		
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
